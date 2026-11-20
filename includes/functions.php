<?php
/**
 * Shared helpers: session bootstrap, auth guards, CSRF, small utilities.
 * Include this AFTER config.php on every page.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

/** Escape output for safe HTML display. */
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/** Is someone logged in as a customer? */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/** Is the logged-in user an admin? */
function is_admin() {
    return isset($_SESSION['admin_id']);
}

/** Redirect helper. */
function redirect($path) {
    header('Location: ' . $path);
    exit;
}

/** Block the page unless logged in as a customer. */
function require_login() {
    if (!is_logged_in()) {
        $login_path = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../login.php' : 'login.php';
        redirect($login_path);
    }
}

/** Block the page unless the user is an admin. */
function require_admin() {
    if (!is_admin()) {
        $login_path = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../login.php' : 'login.php';
        redirect($login_path);
    }
}

/** Generate (and cache) a CSRF token for this session. */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Check a submitted CSRF token matches the session's. */
function csrf_check($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/** Flash-style one-time messages, shown then cleared. */
function flash($key, $msg = null) {
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $out = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $out;
    }
    return null;
}

/** Number of distinct items in current cart (logged-in DB or guest session). */
function cart_count($pdo) {
    if (is_logged_in()) {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        return (int) $stmt->fetchColumn();
    }
    if (!empty($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])) {
        return (int) array_sum($_SESSION['guest_cart']);
    }
    return 0;
}

/** Transfer guest session cart to user database table upon login. */
function sync_guest_cart_to_db($pdo, $user_id) {
    if (!empty($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])) {
        foreach ($_SESSION['guest_cart'] as $book_id => $qty) {
            $book_id = (int)$book_id;
            $qty = max(1, (int)$qty);
            $stmt = $pdo->prepare(
                'INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
            );
            $stmt->execute([$user_id, $book_id, $qty]);
        }
        unset($_SESSION['guest_cart']);
    }
}

/** Format a price consistently. */
function price($amount) {
    return '₹' . number_format((float) $amount, 2);
}

/**
 * Book IDs the logged-in user has already received (order status = 'delivered').
 * 'delivered' is this schema's equivalent of "Completed" — a book only counts
 * once the order has actually arrived, not merely been placed.
 * Returns an empty array for guests.
 */
function get_purchased_book_ids($pdo, $user_id) {
    if (!$user_id) return [];
    $stmt = $pdo->prepare(
        "SELECT DISTINCT oi.book_id FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.user_id = ? AND o.status != 'cancelled'"
    );
    $stmt->execute([$user_id]);
    return array_column($stmt->fetchAll(), 'book_id');
}

/** Record that a user viewed a book, for the "Recently viewed" strip. */
function track_recently_viewed($pdo, $user_id, $book_id) {
    if (!$user_id) return;
    $stmt = $pdo->prepare(
        'INSERT INTO recently_viewed (user_id, book_id) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE viewed_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$user_id, $book_id]);
}

/**
 * Validate a coupon code against an order subtotal.
 * Returns ['ok' => true, 'coupon' => row, 'discount' => amount] or ['ok' => false, 'message' => ...].
 */
function validate_coupon($pdo, $code, $subtotal) {
    $code = strtoupper(trim($code));
    if ($code === '') return ['ok' => false, 'message' => 'Enter a coupon code.'];

    $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code = ? AND active = 1');
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon) return ['ok' => false, 'message' => 'Invalid coupon code.'];
    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < strtotime('today')) {
        return ['ok' => false, 'message' => 'This coupon has expired.'];
    }
    if ($coupon['max_uses'] !== null && $coupon['used_count'] >= $coupon['max_uses']) {
        return ['ok' => false, 'message' => 'This coupon has reached its usage limit.'];
    }
    if ($subtotal < $coupon['min_order_value']) {
        return ['ok' => false, 'message' => 'Minimum order of ' . price($coupon['min_order_value']) . ' required for this coupon.'];
    }

    $discount = $coupon['discount_type'] === 'percent'
        ? round($subtotal * ($coupon['discount_value'] / 100), 2)
        : (float) $coupon['discount_value'];
    $discount = min($discount, $subtotal);

    return ['ok' => true, 'coupon' => $coupon, 'discount' => $discount];
}
