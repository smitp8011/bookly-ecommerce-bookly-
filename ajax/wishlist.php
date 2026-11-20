<?php
require_once '../config.php';
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'login_required' => true]);
    exit;
}

$user_id = $_SESSION['user_id'];
$book_id = (int) ($_POST['book_id'] ?? 0);

try {
    $stmt = $pdo->prepare('SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?');
    $stmt->execute([$user_id, $book_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare('DELETE FROM wishlist WHERE id = ?');
        $stmt->execute([$existing['id']]);
        echo json_encode(['ok' => true, 'wishlisted' => false]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO wishlist (user_id, book_id) VALUES (?, ?)');
        $stmt->execute([$user_id, $book_id]);
        echo json_encode(['ok' => true, 'wishlisted' => true]);
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Server error.']);
}
