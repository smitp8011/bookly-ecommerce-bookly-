<?php
/**
 * Razorpay Payment Gateway AJAX Endpoint
 * Handles secure server-side order generation and signature verification.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'Please login to proceed.']);
    exit;
}

if (!defined('RAZORPAY_KEY_ID') || !defined('RAZORPAY_KEY_SECRET') || empty(RAZORPAY_KEY_ID) || empty(RAZORPAY_KEY_SECRET)) {
    echo json_encode(['ok' => false, 'error' => 'Razorpay API keys not configured.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'create_order';

if ($action === 'create_order') {
    // 1. Fetch Cart Items & Live Subtotal
    $stmt = $pdo->prepare('SELECT c.quantity, b.id, b.title, b.price, b.stock FROM cart c JOIN books b ON b.id = c.book_id WHERE c.user_id = ?');
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        echo json_encode(['ok' => false, 'error' => 'Your cart is empty.']);
        exit;
    }

    $subtotal = 0;
    foreach ($items as $it) {
        if ($it['stock'] < $it['quantity']) {
            echo json_encode(['ok' => false, 'error' => '"' . $it['title'] . '" is out of stock.']);
            exit;
        }
        $subtotal += $it['price'] * $it['quantity'];
    }

    // 2. Validate Coupon
    $coupon_code = null;
    $discount = 0;
    if (!empty($_SESSION['coupon']['code'])) {
        $check = validate_coupon($pdo, $_SESSION['coupon']['code'], $subtotal);
        if ($check['ok']) {
            $coupon_code = $check['coupon']['code'];
            $discount = $check['discount'];
        }
    }
    $grand_total = max(0, $subtotal - $discount);
    $amount_in_paise = (int) round($grand_total * 100);

    // 3. Create Razorpay Order via REST API
    $receipt_id = 'rcpt_' . $user_id . '_' . time();
    $payload = [
        'amount'   => $amount_in_paise,
        'currency' => 'INR',
        'receipt'  => $receipt_id,
        'notes'    => [
            'user_id'     => (string) $user_id,
            'coupon_code' => (string) ($coupon_code ?? 'NONE'),
            'store'       => 'Bookly Bookstore'
        ]
    ];

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 12
    ]);

    $res = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && $res) {
        $data = json_decode($res, true);
        
        // Fetch current user info for checkout prefill
        $uStmt = $pdo->prepare('SELECT name, email, phone, address FROM users WHERE id = ?');
        $uStmt->execute([$user_id]);
        $user_info = $uStmt->fetch() ?: [];

        echo json_encode([
            'ok'                => true,
            'razorpay_order_id' => $data['id'],
            'amount'            => $data['amount'],
            'currency'          => $data['currency'],
            'key_id'            => RAZORPAY_KEY_ID,
            'user_name'         => $user_info['name'] ?? $_SESSION['user_name'] ?? '',
            'user_email'        => $user_info['email'] ?? $_SESSION['user_email'] ?? '',
            'user_phone'        => $user_info['phone'] ?? ''
        ]);
        exit;
    } else {
        $err = json_decode($res, true);
        $errMsg = $err['error']['description'] ?? 'Failed to initiate Razorpay order.';
        echo json_encode(['ok' => false, 'error' => $errMsg]);
        exit;
    }
}

echo json_encode(['ok' => false, 'error' => 'Invalid action']);
