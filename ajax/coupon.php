<?php
require_once '../config.php';
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'login_required' => true, 'message' => 'Please log in first.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'remove') {
    unset($_SESSION['coupon']);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'apply') {
    $code = $_POST['code'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(c.quantity * b.price),0) AS subtotal FROM cart c JOIN books b ON b.id = c.book_id WHERE c.user_id = ?'
    );
    $stmt->execute([$user_id]);
    $subtotal = (float) $stmt->fetchColumn();

    if ($subtotal <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Your cart is empty.']);
        exit;
    }

    $result = validate_coupon($pdo, $code, $subtotal);
    if (!$result['ok']) {
        echo json_encode(['ok' => false, 'message' => $result['message']]);
        exit;
    }

    $_SESSION['coupon'] = [
        'code'     => $result['coupon']['code'],
        'discount' => $result['discount'],
    ];

    echo json_encode(['ok' => true, 'message' => $result['coupon']['code'] . ' — you saved ' . price($result['discount'])]);
    exit;
}

echo json_encode(['ok' => false, 'message' => 'Unknown action.']);
