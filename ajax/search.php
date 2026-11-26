<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim($_GET['q'] ?? '');

if (mb_strlen($query) < 1) {
    echo json_encode(['ok' => true, 'results' => []]);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT b.id, b.title, b.author, b.price, b.stock, b.cover_image, COALESCE(c.name, "General") AS category_name
     FROM books b
     LEFT JOIN categories c ON c.id = b.category_id
     WHERE b.title LIKE ? OR b.author LIKE ? OR c.name LIKE ?
     ORDER BY b.title ASC
     LIMIT 6'
);

$searchTerm = '%' . $query . '%';
$stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
$books = $stmt->fetchAll();

$is_fuzzy = false;
$suggested_term = null;

// If exact LIKE match finds no books, trigger fuzzy typo-tolerant matcher!
if (empty($books)) {
    $fuzzy_res = fuzzy_search_catalog($pdo, $query, 50);
    if (!empty($fuzzy_res['matches'])) {
        $books = array_slice($fuzzy_res['matches'], 0, 6);
        $is_fuzzy = true;
        $suggested_term = $fuzzy_res['suggested_term'];
    }
}

$results = array_map(function ($b) {
    return [
        'id'            => $b['id'],
        'title'         => $b['title'],
        'author'        => $b['author'],
        'category'      => $b['category_name'] ?? 'General',
        'price'         => price($b['price']),
        'cover'         => 'images/' . $b['cover_image'],
        'url'           => 'book.php?id=' . $b['id'],
        'in_stock'      => (int)$b['stock'] > 0
    ];
}, $books);

echo json_encode([
    'ok'             => true,
    'query'          => $query,
    'is_fuzzy'       => $is_fuzzy,
    'suggested_term' => $suggested_term,
    'total'          => count($results),
    'results'        => $results
]);
