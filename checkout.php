<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/mailer.php';
require_login();

$user_id = $_SESSION['user_id'];

$user_stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$user_stmt->execute([$user_id]);
$current_user = $user_stmt->fetch() ?: [];

// Fetch Saved Addresses
$addr_stmt = $pdo->prepare('SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC');
$addr_stmt->execute([$user_id]);
$saved_addresses = $addr_stmt->fetchAll();

$default_addr = null;
foreach ($saved_addresses as $sa) {
    if ($sa['is_default']) { $default_addr = $sa; break; }
}
if (!$default_addr && !empty($saved_addresses)) {
    $default_addr = $saved_addresses[0];
}

$stmt = $pdo->prepare(
    'SELECT c.quantity, b.id, b.title, b.price, b.stock, b.cover_image FROM cart c JOIN books b ON b.id = c.book_id WHERE c.user_id = ?'
);
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

if (empty($items)) {
    flash('error', 'Your cart is empty.');
    redirect('cart.php');
}

$subtotal = 0;
foreach ($items as $it) $subtotal += $it['price'] * $it['quantity'];

// Re-validate any applied coupon against the live subtotal.
$coupon_code = null;
$discount = 0;
if (!empty($_SESSION['coupon']['code'])) {
    $check = validate_coupon($pdo, $_SESSION['coupon']['code'], $subtotal);
    if ($check['ok']) {
        $coupon_code = $check['coupon']['code'];
        $discount = $check['discount'];
    } else {
        unset($_SESSION['coupon']);
    }
}
$grand_total = max(0, $subtotal - $discount);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $address   = trim($_POST['address'] ?? '');
        $raw_method = $_POST['payment_method'] ?? 'Cash on Delivery';

        if ($full_name === '' || $phone === '' || $email === '' || $address === '') {
            $errors[] = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } else {
            try {
                // Optionally save address to Address Book if checked
                if (!empty($_POST['save_to_address_book'])) {
                    $check_dup = $pdo->prepare('SELECT id FROM user_addresses WHERE user_id = ? AND address_line = ?');
                    $check_dup->execute([$user_id, $address]);
                    if (!$check_dup->fetch()) {
                        $ins_addr = $pdo->prepare('INSERT INTO user_addresses (user_id, address_type, full_name, phone, address_line, city, state, pincode, is_default) VALUES (?, "home", ?, ?, ?, "Local", "State", "400001", 0)');
                        $ins_addr->execute([$user_id, $full_name, $phone, $address]);
                    }
                }

                $final_total = $grand_total;

                $payment_id = null;
                $order_status = 'pending';
                $final_method = 'Cash on Delivery';

                $pdo->beginTransaction();

                // re-check stock inside the transaction to avoid overselling
                foreach ($items as $it) {
                    $check = $pdo->prepare('SELECT stock FROM books WHERE id = ? FOR UPDATE');
                    $check->execute([$it['id']]);
                    $current_stock = (int) $check->fetchColumn();
                    if ($current_stock < $it['quantity']) {
                        throw new RuntimeException('"' . $it['title'] . '" no longer has enough stock.');
                    }
                }

                $order_stmt = $pdo->prepare(
                    'INSERT INTO orders (user_id, full_name, phone, email, address, payment_method, payment_id, coupon_code, discount_amount, total_price, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $order_stmt->execute([$user_id, $full_name, $phone, $email, $address, $final_method, $payment_id, $coupon_code, $discount, $final_total, $order_status]);
                $order_id = $pdo->lastInsertId();

                $item_stmt = $pdo->prepare(
                    'INSERT INTO order_items (order_id, book_id, title, quantity, price_at_purchase) VALUES (?, ?, ?, ?, ?)'
                );
                $stock_stmt = $pdo->prepare('UPDATE books SET stock = stock - ? WHERE id = ?');

                foreach ($items as $it) {
                    $item_stmt->execute([$order_id, $it['id'], $it['title'], $it['quantity'], $it['price']]);
                    $stock_stmt->execute([$it['quantity'], $it['id']]);
                }

                if ($coupon_code) {
                    $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE code = ?')->execute([$coupon_code]);
                }

                $pdo->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$user_id]);
                unset($_SESSION['coupon']);

                // Auto-save phone and address to user profile if empty
                try {
                    $pdo->prepare('UPDATE users SET phone = COALESCE(NULLIF(phone, ""), ?), address = COALESCE(NULLIF(address, ""), ?) WHERE id = ?')
                        ->execute([$phone, $address, $user_id]);
                } catch (Exception $ignored) {}

                $pdo->commit();

                $confirm_body = '<p>Hi ' . h($full_name) . ',</p>
                    <p>Thanks for your order! Here\'s a quick summary:</p>
                    <p><strong>Payment Method:</strong> ' . h($final_method) . ($payment_id ? ' (Ref: ' . h($payment_id) . ')' : '') . '</p>
                    <table style="width:100%; margin:16px 0; border-collapse:collapse;">' .
                    implode('', array_map(fn($it) => '<tr><td style="padding:6px 0;">' . h($it['title']) . ' × ' . $it['quantity'] . '</td><td style="padding:6px 0; text-align:right;">' . price($it['price'] * $it['quantity']) . '</td></tr>', $items)) .
                    '<tr><td style="padding:10px 0; font-weight:bold; border-top:1px solid #e7ddc9;">Total</td><td style="padding:10px 0; text-align:right; font-weight:bold; border-top:1px solid #e7ddc9;">' . price($grand_total) . '</td></tr>
                    </table>
                    <p>We\'ll email you again as soon as your order status changes.</p>';
                send_email($email, $full_name, 'Bookly order #' . $order_id . ' confirmed', email_wrapper('Order Confirmed', $confirm_body));

                redirect('order_success.php?id=' . $order_id);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = $e->getMessage() ?: 'Something went wrong placing your order.';
            }
        }
    }
}

$active_page = 'checkout';
$page_title = 'Checkout';
require_once 'includes/header.php';
?>

<section>
  <div class="container">
    <div class="section-head reveal in"><div><span class="eyebrow">Almost there</span><h2>Checkout</h2></div></div>

    <div class="cart-page">
      <div class="reveal in">
        <?php foreach ($errors as $err): ?>
          <div class="field-error" style="margin-bottom:16px;"><i class="fas fa-circle-exclamation"></i> <?= h($err); ?></div>
        <?php endforeach; ?>

        <form method="post" id="checkout-form" class="admin-form" style="background:#fffdf8; padding:26px; border-radius:12px; box-shadow:var(--shadow-sm);">
          <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">

          <?php if (!empty($saved_addresses)): ?>
            <div style="margin-bottom:20px; background:#faf5eb; border:1px solid var(--paper-dim); border-radius:12px; padding:16px;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <label style="font-weight:700; color:var(--ink); font-size:.92rem; margin:0;">
                  <i class="fas fa-map-location-dot" style="color:var(--wine);"></i> Choose Saved Delivery Address
                </label>
                <a href="profile.php?tab=addresses" target="_blank" style="font-size:.78rem; color:var(--wine); font-weight:600;">
                  <i class="fas fa-gear"></i> Manage Addresses
                </a>
              </div>
              <div class="checkout-address-picker">
                <?php foreach ($saved_addresses as $idx => $s_addr): 
                  $full_addr_str = $s_addr['address_line'] . ', ' . $s_addr['city'] . ', ' . $s_addr['state'] . ' - ' . $s_addr['pincode'];
                  $is_selected = ($default_addr && $default_addr['id'] === $s_addr['id']);
                ?>
                  <div class="checkout-addr-card <?= $is_selected ? 'selected' : ''; ?>"
                       onclick="selectCheckoutAddress(this, '<?= h(addslashes($s_addr['full_name'])); ?>', '<?= h(addslashes($s_addr['phone'])); ?>', '<?= h(addslashes($full_addr_str)); ?>')">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                      <span class="address-type-pill <?= $s_addr['address_type']; ?>">
                        <i class="fas <?= $s_addr['address_type'] === 'home' ? 'fa-house' : ($s_addr['address_type'] === 'office' ? 'fa-briefcase' : 'fa-location-dot'); ?>"></i>
                        <?= ucfirst($s_addr['address_type']); ?>
                      </span>
                      <?php if ($s_addr['is_default']): ?>
                        <span style="font-size:.68rem; font-weight:700; color:var(--gold);"><i class="fas fa-star"></i> Default</span>
                      <?php endif; ?>
                    </div>
                    <div style="font-weight:700; font-size:.88rem;"><?= h($s_addr['full_name']); ?></div>
                    <div style="font-size:.78rem; color:#666; margin:2px 0 4px;"><i class="fas fa-phone"></i> <?= h($s_addr['phone']); ?></div>
                    <div style="font-size:.78rem; color:#444; line-height:1.4;"><?= h($full_addr_str); ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php 
          $initial_name = $default_addr ? $default_addr['full_name'] : ($current_user['name'] ?? $_SESSION['user_name']);
          $initial_phone = $default_addr ? $default_addr['phone'] : ($current_user['phone'] ?? '');
          $initial_addr = $default_addr ? ($default_addr['address_line'] . ', ' . $default_addr['city'] . ', ' . $default_addr['state'] . ' - ' . $default_addr['pincode']) : ($current_user['address'] ?? '');
          ?>

          <label>Full name</label>
          <input type="text" name="full_name" id="cust-name" required value="<?= h($_POST['full_name'] ?? $initial_name); ?>">
          <label>Phone number</label>
          <input type="tel" name="phone" id="cust-phone" required placeholder="10-digit mobile number" value="<?= h($_POST['phone'] ?? $initial_phone); ?>">
          <label>Email</label>
          <input type="email" name="email" id="cust-email" required value="<?= h($_POST['email'] ?? $current_user['email'] ?? $_SESSION['user_email']); ?>">
          <label>Delivery address</label>
          <textarea name="address" id="cust-address" rows="3" required placeholder="Flat, street, city, PIN code"><?= h($_POST['address'] ?? $initial_addr); ?></textarea>
          
          <div style="margin:8px 0 14px; display:flex; align-items:center; gap:8px;">
            <input type="checkbox" id="save-to-address-book" name="save_to_address_book" value="1" style="accent-color:var(--wine); width:16px; height:16px;">
            <label for="save-to-address-book" style="font-size:.84rem; cursor:pointer; color:#5c544b; margin:0;">
              Save this address to My Saved Address Book for future 1-click ordering
            </label>
          </div>

          <label style="margin-bottom:8px; display:block; font-weight:600;">Payment Method</label>
          <input type="hidden" name="payment_method" id="selected-payment-method" value="Cash on Delivery">
          
          <div style="background:#faf5eb; border:1.5px solid var(--wine); border-radius:10px; padding:16px 20px; display:flex; align-items:center; gap:14px; margin-bottom:18px;">
            <div style="width:44px; height:44px; border-radius:50%; background:rgba(110,31,42,.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
              <i class="fas fa-hand-holding-dollar" style="color:var(--wine); font-size:1.4rem;"></i>
            </div>
            <div>
              <strong style="font-size:1rem; color:var(--ink); display:block;">Cash on Delivery (COD)</strong>
              <span style="font-size:.82rem; color:#6b6259; display:block; margin-top:2px;">
                Pay securely with cash or any UPI app to the courier partner upon parcel delivery.
              </span>
            </div>
          </div>

          <button type="submit" id="place-order-btn" class="btn btn-primary" style="width:100%; margin-top:10px; font-size:1rem; padding:14px;">
            <i class="fas fa-lock"></i> <span id="btn-text">Place Order (Cash on Delivery) — <?= price($grand_total); ?></span>
          </button>
        </form>
      </div>

      <div class="summary-card">
        <h3 style="margin-bottom:16px;">Order Summary</h3>
        <?php foreach ($items as $it): ?>
          <div class="summary-row"><span><?= h($it['title']); ?> × <?= $it['quantity']; ?></span><span><?= price($it['price'] * $it['quantity']); ?></span></div>
        <?php endforeach; ?>
        <div class="summary-row"><span>Subtotal</span><span><?= price($subtotal); ?></span></div>
        <?php if ($coupon_code): ?>
          <div class="summary-row discount"><span>Coupon (<?= h($coupon_code); ?>)</span><span>−<?= price($discount); ?></span></div>
        <?php endif; ?>
        <div class="summary-row total"><span>Total</span><span id="grand-total-display"><?= price($grand_total); ?></span></div>
      </div>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('checkout-form');
  const placeBtn = document.getElementById('place-order-btn');

  // Handle Form Submission
  form.addEventListener('submit', () => {
    placeBtn.disabled = true;
    placeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Placing Order...';
  });
});

function selectCheckoutAddress(cardEl, name, phone, addressStr) {
  document.querySelectorAll('.checkout-addr-card').forEach(c => c.classList.remove('selected'));
  if (cardEl) cardEl.classList.add('selected');

  const nameIn = document.getElementById('cust-name');
  const phoneIn = document.getElementById('cust-phone');
  const addrIn = document.getElementById('cust-address');

  if (nameIn && name) nameIn.value = name;
  if (phoneIn && phone) phoneIn.value = phone;
  if (addrIn && addressStr) addrIn.value = addressStr;
}
</script>

<?php require_once 'includes/footer.php'; ?>


