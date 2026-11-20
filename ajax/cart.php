<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$book_id = (int) ($_POST['book_id'] ?? $_GET['book_id'] ?? 0);

if (!isset($_SESSION['guest_cart']) || !is_array($_SESSION['guest_cart'])) {
    $_SESSION['guest_cart'] = [];
}

function guest_cart_totals($pdo) {
    $qty = 0;
    $subtotal = 0;
    if (!empty($_SESSION['guest_cart'])) {
        $book_ids = array_keys($_SESSION['guest_cart']);
        if (!empty($book_ids)) {
            $in_placeholders = implode(',', array_fill(0, count($book_ids), '?'));
            $stmt = $pdo->prepare("SELECT id, price FROM books WHERE id IN ($in_placeholders)");
            $stmt->execute($book_ids);
            foreach ($stmt->fetchAll() as $b) {
                $q = $_SESSION['guest_cart'][$b['id']] ?? 0;
                $qty += $q;
                $subtotal += $b['price'] * $q;
            }
        }
    }

    $discount = 0;
    if (!empty($_SESSION['coupon']['code']) && $subtotal > 0) {
        $check = validate_coupon($pdo, $_SESSION['coupon']['code'], $subtotal);
        if ($check['ok']) {
            $discount = $check['discount'];
            $_SESSION['coupon']['discount'] = $discount;
        } else {
            unset($_SESSION['coupon']);
        }
    }

    return [
        'qty' => $qty,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'total' => max(0, $subtotal - $discount)
    ];
}

function cart_totals($pdo, $user_id) {
    if (!$user_id) {
        return guest_cart_totals($pdo);
    }
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(c.quantity),0) AS qty, COALESCE(SUM(c.quantity * b.price),0) AS subtotal
         FROM cart c JOIN books b ON b.id = c.book_id WHERE c.user_id = ?'
    );
    $stmt->execute([$user_id]);
    $totals = $stmt->fetch();

    $discount = 0;
    if (!empty($_SESSION['coupon']['code']) && $totals['subtotal'] > 0) {
        $check = validate_coupon($pdo, $_SESSION['coupon']['code'], $totals['subtotal']);
        if ($check['ok']) {
            $discount = $check['discount'];
            $_SESSION['coupon']['discount'] = $discount;
        } else {
            unset($_SESSION['coupon']);
        }
    }
    $totals['discount'] = $discount;
    $totals['total'] = max(0, $totals['subtotal'] - $discount);
    return $totals;
}

try {
    if ($action === 'get') {
        $items = [];
        if ($user_id) {
            $stmt = $pdo->prepare(
                'SELECT c.quantity, b.id, b.title, b.author, b.price, b.cover_image, b.stock
                 FROM cart c JOIN books b ON b.id = c.book_id WHERE c.user_id = ? ORDER BY c.added_at DESC'
            );
            $stmt->execute([$user_id]);
            $rows = $stmt->fetchAll();
            foreach ($rows as $r) {
                $r['formatted_price'] = price($r['price']);
                $r['cover'] = 'images/' . $r['cover_image'];
                $items[] = $r;
            }
        } else {
            if (!empty($_SESSION['guest_cart'])) {
                $book_ids = array_keys($_SESSION['guest_cart']);
                if (!empty($book_ids)) {
                    $in_placeholders = implode(',', array_fill(0, count($book_ids), '?'));
                    $stmt = $pdo->prepare("SELECT id, title, author, price, cover_image, stock FROM books WHERE id IN ($in_placeholders)");
                    $stmt->execute($book_ids);
                    foreach ($stmt->fetchAll() as $r) {
                        $r['quantity'] = $_SESSION['guest_cart'][$r['id']] ?? 1;
                        $r['formatted_price'] = price($r['price']);
                        $r['cover'] = 'images/' . $r['cover_image'];
                        $items[] = $r;
                    }
                }
            }
        }
        $totals = cart_totals($pdo, $user_id);
        echo json_encode([
            'ok' => true,
            'items' => $items,
            'cart_count' => cart_count($pdo),
            'subtotal' => price($totals['subtotal']),
            'raw_subtotal' => (float)$totals['subtotal'],
            'total' => price($totals['total']),
            'is_logged_in' => (bool)$user_id
        ]);
        exit;
    }

    if ($action === 'add_bundle') {
        $raw_ids = $_POST['book_ids'] ?? [];
        if (is_string($raw_ids)) {
            $raw_ids = json_decode($raw_ids, true) ?: explode(',', $raw_ids);
        }
        $bundle_ids = array_filter(array_map('intval', (array)$raw_ids));

        if (empty($bundle_ids)) {
            echo json_encode(['ok' => false, 'message' => 'No books selected for bundle.']);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($bundle_ids), '?'));
        $stmt = $pdo->prepare("SELECT id, title, stock, price FROM books WHERE id IN ($placeholders)");
        $stmt->execute($bundle_ids);
        $books_found = $stmt->fetchAll();

        foreach ($books_found as $b) {
            $b_id = (int)$b['id'];
            if ($user_id) {
                $check = $pdo->prepare('SELECT quantity FROM cart WHERE user_id = ? AND book_id = ?');
                $check->execute([$user_id, $b_id]);
                $existing = $check->fetch();
                $current_qty = $existing ? (int)$existing['quantity'] : 0;

                if ($current_qty + 1 <= (int)$b['stock']) {
                    if ($existing) {
                        $up = $pdo->prepare('UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND book_id = ?');
                        $up->execute([$user_id, $b_id]);
                    } else {
                        $ins = $pdo->prepare('INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, 1)');
                        $ins->execute([$user_id, $b_id]);
                    }
                }
            } else {
                $current_qty = $_SESSION['guest_cart'][$b_id] ?? 0;
                if ($current_qty + 1 <= (int)$b['stock']) {
                    $_SESSION['guest_cart'][$b_id] = $current_qty + 1;
                }
            }
        }

        $totals = cart_totals($pdo, $user_id);
        echo json_encode([
            'ok' => true,
            'message' => 'Bundle added to cart!',
            'cart_count' => cart_count($pdo),
            'subtotal' => price($totals['subtotal']),
            'total' => price($totals['total']),
            'count' => (int)$totals['qty']
        ]);
        exit;
    }

    // Validate book
    $stmt = $pdo->prepare('SELECT stock, price FROM books WHERE id = ?');
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
    if (!$book && $action !== 'totals' && $action !== 'get') {
        echo json_encode(['ok' => false, 'message' => 'Book not found.']);
        exit;
    }

    if ($action === 'add') {
        if ($user_id) {
            $stmt = $pdo->prepare('SELECT quantity FROM cart WHERE user_id = ? AND book_id = ?');
            $stmt->execute([$user_id, $book_id]);
            $existing = $stmt->fetch();
            $current_qty = $existing ? (int) $existing['quantity'] : 0;

            if ($current_qty + 1 > (int) $book['stock']) {
                echo json_encode(['ok' => false, 'message' => 'No more stock available.']);
                exit;
            }

            if ($existing) {
                $stmt = $pdo->prepare('UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND book_id = ?');
                $stmt->execute([$user_id, $book_id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, 1)');
                $stmt->execute([$user_id, $book_id]);
            }
        } else {
            // Guest session
            $current_qty = $_SESSION['guest_cart'][$book_id] ?? 0;
            if ($current_qty + 1 > (int) $book['stock']) {
                echo json_encode(['ok' => false, 'message' => 'No more stock available.']);
                exit;
            }
            $_SESSION['guest_cart'][$book_id] = $current_qty + 1;
        }

        echo json_encode(['ok' => true, 'cart_count' => cart_count($pdo)]);
        exit;
    }

    if ($action === 'update') {
        $delta = (int) ($_POST['delta'] ?? 0);
        if ($user_id) {
            $stmt = $pdo->prepare('SELECT c.quantity, b.stock FROM cart c JOIN books b ON b.id = c.book_id WHERE c.user_id = ? AND c.book_id = ?');
            $stmt->execute([$user_id, $book_id]);
            $row = $stmt->fetch();
            if (!$row) {
                echo json_encode(['ok' => false, 'message' => 'Item not in cart.']);
                exit;
            }
            $new_qty = (int) $row['quantity'] + $delta;
            if ($new_qty <= 0) {
                $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ? AND book_id = ?');
                $stmt->execute([$user_id, $book_id]);
                $totals = cart_totals($pdo, $user_id);
                echo json_encode(['ok' => true, 'removed' => true, 'cart_count' => cart_count($pdo), 'subtotal' => price($totals['subtotal']), 'total' => price($totals['total']), 'count' => (int) $totals['qty']]);
                exit;
            }
            if ($new_qty > (int) $row['stock']) {
                echo json_encode(['ok' => false, 'message' => 'No more stock available.']);
                exit;
            }
            $stmt = $pdo->prepare('UPDATE cart SET quantity = ? WHERE user_id = ? AND book_id = ?');
            $stmt->execute([$new_qty, $user_id, $book_id]);
        } else {
            // Guest session
            $current_qty = $_SESSION['guest_cart'][$book_id] ?? 0;
            $new_qty = $current_qty + $delta;
            if ($new_qty <= 0) {
                unset($_SESSION['guest_cart'][$book_id]);
                $totals = guest_cart_totals($pdo);
                echo json_encode(['ok' => true, 'removed' => true, 'cart_count' => cart_count($pdo), 'subtotal' => price($totals['subtotal']), 'total' => price($totals['total']), 'count' => (int) $totals['qty']]);
                exit;
            }
            if ($new_qty > (int) $book['stock']) {
                echo json_encode(['ok' => false, 'message' => 'No more stock available.']);
                exit;
            }
            $_SESSION['guest_cart'][$book_id] = $new_qty;
        }

        $totals = cart_totals($pdo, $user_id);
        echo json_encode(['ok' => true, 'quantity' => $new_qty, 'cart_count' => cart_count($pdo), 'subtotal' => price($totals['subtotal']), 'total' => price($totals['total']), 'count' => (int) $totals['qty']]);
        exit;
    }

    if ($action === 'remove') {
        if ($user_id) {
            $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ? AND book_id = ?');
            $stmt->execute([$user_id, $book_id]);
        } else {
            unset($_SESSION['guest_cart'][$book_id]);
        }
        $totals = cart_totals($pdo, $user_id);
        echo json_encode(['ok' => true, 'cart_count' => cart_count($pdo), 'subtotal' => price($totals['subtotal']), 'total' => price($totals['total']), 'count' => (int) $totals['qty']]);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Unknown action.']);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
