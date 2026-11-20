<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin()) {
    echo json_encode(['ok' => false, 'message' => 'Unauthorized admin access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf'] ?? '')) {
    echo json_encode(['ok' => false, 'message' => 'Session expired, please refresh.']);
    exit;
}

$book_id = (int) ($_POST['book_id'] ?? 0);
$field   = trim($_POST['field'] ?? '');
$value   = trim($_POST['value'] ?? '');

if (!$book_id) {
    echo json_encode(['ok' => false, 'message' => 'Invalid book ID.']);
    exit;
}

if ($field === 'price') {
    $price = (float) $value;
    if ($price <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Price must be greater than 0.']);
        exit;
    }
    $stmt = $pdo->prepare('UPDATE books SET price = ? WHERE id = ?');
    $stmt->execute([$price, $book_id]);
    echo json_encode(['ok' => true, 'message' => 'Price updated', 'new_value' => $price]);
    exit;
}

if ($field === 'stock') {
    $stock = max(0, (int) $value);
    $stmt = $pdo->prepare('UPDATE books SET stock = ? WHERE id = ?');
    $stmt->execute([$stock, $book_id]);
    echo json_encode(['ok' => true, 'message' => 'Stock updated', 'new_value' => $stock]);
    exit;
}

echo json_encode(['ok' => false, 'message' => 'Invalid field.']);
