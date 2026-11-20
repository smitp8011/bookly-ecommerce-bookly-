<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$book_id = (int) ($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? 0;

if (!$book_id) {
    echo json_encode(['ok' => false, 'message' => 'Invalid book ID.']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT b.*, COALESCE(c.name, "General") AS category_name
     FROM books b
     LEFT JOIN categories c ON c.id = b.category_id
     WHERE b.id = ?'
);
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    echo json_encode(['ok' => false, 'message' => 'Book not found.']);
    exit;
}

// Fetch review stats
$rev_stmt = $pdo->prepare('SELECT COUNT(*) AS total_reviews, COALESCE(AVG(rating), 0) AS avg_rating FROM reviews WHERE book_id = ?');
$rev_stmt->execute([$book_id]);
$rev_stats = $rev_stmt->fetch();

// Check if wishlisted
$is_wishlisted = false;
if ($user_id) {
    $w_stmt = $pdo->prepare('SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?');
    $w_stmt->execute([$user_id, $book_id]);
    $is_wishlisted = (bool) $w_stmt->fetch();
}

$is_purchased = in_array($book_id, get_purchased_book_ids($pdo, $user_id));

echo json_encode([
    'ok' => true,
    'book' => [
        'id'            => (int)$book['id'],
        'title'         => $book['title'],
        'author'        => $book['author'],
        'category'      => $book['category_name'],
        'price'         => price($book['price']),
        'raw_price'     => (float)$book['price'],
        'stock'         => (int)$book['stock'],
        'cover'         => 'images/' . $book['cover_image'],
        'description'   => $book['description'],
        'url'           => 'book.php?id=' . $book['id'],
        'total_reviews' => (int)$rev_stats['total_reviews'],
        'avg_rating'    => round((float)$rev_stats['avg_rating'], 1),
        'is_wishlisted' => $is_wishlisted,
        'is_purchased'  => $is_purchased
    ]
]);
