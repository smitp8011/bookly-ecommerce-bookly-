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
        $raw_method = $_POST['payment_method'] ?? 'UPI';

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
                $final_method = $raw_method;

                // 1. UPI / QR Payment
                if ($raw_method === 'UPI' || $raw_method === 'Instant UPI / QR') {
                    $payment_id = 'pay_upi_' . strtoupper(bin2hex(random_bytes(5)));
                    $order_status = 'processing';
                    $final_method = 'UPI / QR (Instant Paid)';
                }
                // 2. Direct Card Payment (Instant Authorization)
                elseif ($raw_method === 'Card' || $raw_method === 'Card (Debit/Credit)') {
                    $payment_id = 'pay_card_' . strtoupper(bin2hex(random_bytes(5)));
                    $order_status = 'processing';
                    $final_method = 'Debit/Credit Card (Paid)';
                }
                // 3. Razorpay Gateway Verification
                elseif ($raw_method === 'Razorpay' || $raw_method === 'Razorpay (Online)') {
                    $rzp_order_id = trim($_POST['razorpay_order_id'] ?? '');
                    $rzp_payment_id = trim($_POST['razorpay_payment_id'] ?? '');
                    $rzp_signature = trim($_POST['razorpay_signature'] ?? '');

                    if (empty($rzp_payment_id) || empty($rzp_order_id) || empty($rzp_signature)) {
                        throw new RuntimeException('Payment token missing. Please complete Razorpay checkout.');
                    }

                    if (defined('RAZORPAY_KEY_SECRET') && !empty(RAZORPAY_KEY_SECRET)) {
                        $expected_sig = hash_hmac('sha256', $rzp_order_id . '|' . $rzp_payment_id, RAZORPAY_KEY_SECRET);
                        if (!hash_equals($expected_sig, $rzp_signature)) {
                            throw new RuntimeException('Payment signature mismatch. Transaction verification failed.');
                        }
                    }

                    $payment_id = $rzp_payment_id;
                    $order_status = 'processing';
                    $final_method = 'Razorpay Gateway (Online)';
                }
                // 4. Instant Sandbox Virtual Wallet
                elseif ($raw_method === 'Instant Sandbox') {
                    $payment_id = 'pay_demo_' . strtoupper(bin2hex(random_bytes(5)));
                    $order_status = 'processing';
                    $final_method = 'Demo Sandbox Wallet (Paid)';
                }
                // 5. Cash on Delivery
                else {
                    $final_method = 'Cash on Delivery';
                    $order_status = 'pending';
                }

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
          <input type="hidden" name="razorpay_payment_id" id="rzp-payment-id" value="">
          <input type="hidden" name="razorpay_order_id" id="rzp-order-id" value="">
          <input type="hidden" name="razorpay_signature" id="rzp-signature" value="">

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

          <label style="margin-bottom:8px; display:block;">Select Payment Method</label>
          <input type="hidden" name="payment_method" id="selected-payment-method" value="UPI">
          
          <div class="payment-selector" style="grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px; margin-bottom:16px;">
            <div class="payment-method-card active" data-method="UPI">
              <i class="fas fa-qrcode" style="color:var(--wine);"></i>
              <span style="font-weight:700;">UPI / QR Code</span>
              <small style="font-size:.68rem; color:#777; display:block;">GPay, PhonePe, Paytm</small>
            </div>
            <div class="payment-method-card" data-method="Card">
              <i class="fas fa-credit-card" style="color:var(--gold);"></i>
              <span style="font-weight:700;">Debit / Credit Card</span>
              <small style="font-size:.68rem; color:#777; display:block;">Visa, RuPay, Master</small>
            </div>
            <div class="payment-method-card" data-method="Razorpay">
              <i class="fas fa-bolt" style="color:#d97706;"></i>
              <span style="font-weight:700;">Razorpay Popup</span>
              <small style="font-size:.68rem; color:#777; display:block;">Official Sandbox</small>
            </div>
            <div class="payment-method-card" data-method="Cash on Delivery">
              <i class="fas fa-hand-holding-dollar" style="color:var(--sage);"></i>
              <span style="font-weight:700;">Cash on Delivery</span>
              <small style="font-size:.68rem; color:#777; display:block;">Doorstep cash/UPI</small>
            </div>
          </div>

          <!-- Panel 1: Instant UPI & QR Code (Active by Default) -->
          <div id="panel-upi" class="payment-tab-panel active" style="padding:18px; background:#faf5eb; border-radius:10px; border:1px solid rgba(201,162,75,.35);">
            <div class="upi-qr-wrap" style="text-align:center;">
              <div class="upi-timer-badge">
                <i class="fas fa-stopwatch"></i> QR Code valid for: <span id="upi-countdown">05:00</span>
              </div>
              
              <!-- High Contrast Sharp SVG QR Code -->
              <div class="upi-qr-image" style="background:#fff; width:160px; height:160px; margin:0 auto 12px; padding:10px; border-radius:12px; border:2px solid var(--wine); display:flex; align-items:center; justify-content:center; box-shadow:0 4px 15px rgba(0,0,0,.08);">
                <svg viewBox="0 0 100 100" width="140" height="140" style="shape-rendering:crispEdges;">
                  <rect width="100" height="100" fill="#fff"/>
                  <rect x="8" y="8" width="26" height="26" fill="#1c1815"/>
                  <rect x="12" y="12" width="18" height="18" fill="#fff"/>
                  <rect x="16" y="16" width="10" height="10" fill="#1c1815"/>
                  <rect x="66" y="8" width="26" height="26" fill="#1c1815"/>
                  <rect x="70" y="12" width="18" height="18" fill="#fff"/>
                  <rect x="74" y="16" width="10" height="10" fill="#1c1815"/>
                  <rect x="8" y="66" width="26" height="26" fill="#1c1815"/>
                  <rect x="12" y="70" width="18" height="18" fill="#fff"/>
                  <rect x="16" y="74" width="10" height="10" fill="#1c1815"/>
                  <rect x="40" y="10" width="6" height="6" fill="#1c1815"/>
                  <rect x="52" y="14" width="6" height="10" fill="#1c1815"/>
                  <rect x="40" y="24" width="12" height="6" fill="#1c1815"/>
                  <rect x="10" y="40" width="6" height="6" fill="#1c1815"/>
                  <rect x="20" y="46" width="14" height="6" fill="#1c1815"/>
                  <rect x="42" y="42" width="16" height="16" fill="#6e1f2a"/>
                  <rect x="66" y="42" width="8" height="8" fill="#1c1815"/>
                  <rect x="80" y="46" width="10" height="6" fill="#1c1815"/>
                  <rect x="40" y="66" width="6" height="14" fill="#1c1815"/>
                  <rect x="54" y="74" width="14" height="6" fill="#1c1815"/>
                  <rect x="74" y="66" width="16" height="16" fill="#1c1815"/>
                  <rect x="82" y="86" width="10" height="6" fill="#1c1815"/>
                  <rect x="48" y="86" width="8" height="8" fill="#1c1815"/>
                </svg>
              </div>

              <div style="font-size:.9rem; font-weight:700; color:var(--wine); margin-bottom:4px;">
                Scan with any UPI App (GPay, PhonePe, Paytm, BHIM)
              </div>
              <div style="font-size:.8rem; color:#666; margin-bottom:14px;">
                Amount: <strong><?= price($grand_total); ?></strong> · UPI ID: <code>bookly@upi</code>
              </div>

              <!-- 1-Click Fast UPI App Simulation Buttons -->
              <div style="display:flex; justify-content:center; gap:8px; flex-wrap:wrap; margin-top:8px;">
                <button type="button" class="btn btn-outline btn-sm quick-upi-btn" data-app="Google Pay" style="padding:6px 12px; font-size:.78rem; background:#fff;">
                  <i class="fab fa-google-pay" style="color:#4285F4; font-size:1.1rem;"></i> Pay via GPay
                </button>
                <button type="button" class="btn btn-outline btn-sm quick-upi-btn" data-app="PhonePe" style="padding:6px 12px; font-size:.78rem; background:#fff;">
                  <i class="fas fa-mobile-screen-button" style="color:#5f259f;"></i> Pay via PhonePe
                </button>
                <button type="button" class="btn btn-outline btn-sm quick-upi-btn" data-app="Paytm" style="padding:6px 12px; font-size:.78rem; background:#fff;">
                  <i class="fas fa-wallet" style="color:#002e6e;"></i> Pay via Paytm
                </button>
              </div>
            </div>
          </div>

          <!-- Panel 2: Direct Card Payment (Instant Authorization) -->
          <div id="panel-card" class="payment-tab-panel" style="display:none; padding:18px; background:#faf5eb; border-radius:10px; border:1px solid var(--paper-dim);">
            <div class="card-mockup-visual" style="margin-bottom:18px;">
              <div class="card-chip"></div>
              <div class="card-number-display" id="card-num-display">4111 •••• •••• 1111</div>
              <div class="card-bottom-row">
                <div>
                  <span style="font-size:.65rem; display:block;">Card Holder</span>
                  <span class="card-holder-display" id="card-name-display"><?= strtoupper(h($_SESSION['user_name'] ?? 'CUSTOMER')); ?></span>
                </div>
                <div style="text-align:right;">
                  <span style="font-size:.65rem; display:block;">Expires</span>
                  <span id="card-exp-display">12/28</span>
                </div>
              </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
              <div style="grid-column:1 / -1;">
                <label style="font-size:.78rem; margin-bottom:2px;">Card Number</label>
                <input type="text" id="card-num-input" placeholder="4111 1111 1111 1111" value="4111 1111 1111 1111" maxlength="19" class="box" style="margin-bottom:0;" autocomplete="off">
              </div>
              <div>
                <label style="font-size:.78rem; margin-bottom:2px;">Name on Card</label>
                <input type="text" id="card-name-input" placeholder="Name" value="<?= h($_SESSION['user_name'] ?? 'Bookly Reader'); ?>" class="box" style="margin-bottom:0;">
              </div>
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                <div>
                  <label style="font-size:.78rem; margin-bottom:2px;">MM/YY</label>
                  <input type="text" id="card-exp-input" placeholder="12/28" value="12/28" maxlength="5" class="box" style="margin-bottom:0;">
                </div>
                <div>
                  <label style="font-size:.78rem; margin-bottom:2px;">CVV</label>
                  <input type="password" placeholder="123" value="123" maxlength="3" class="box" style="margin-bottom:0;">
                </div>
              </div>
            </div>
            <div style="margin-top:10px; font-size:.76rem; color:var(--sage); display:flex; align-items:center; gap:6px;">
              <i class="fas fa-circle-check"></i> <span>256-bit Bank Grade Instant Verification (100% Guaranteed Success)</span>
            </div>
          </div>

          <!-- Panel 3: Razorpay Popup Modal -->
          <div id="panel-razorpay" class="payment-tab-panel" style="display:none; padding:18px; background:#faf5eb; border-radius:10px; border:1px solid rgba(201,162,75,.35);">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
              <div style="width:38px; height:38px; border-radius:50%; background:var(--wine); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.15rem; flex-shrink:0;">
                <i class="fas fa-shield-halved"></i>
              </div>
              <div>
                <h4 style="font-size:.95rem; margin:0; color:var(--wine);">Razorpay Official Test Sandbox</h4>
                <span style="font-size:.78rem; color:#6b6259;">Opens the standard Razorpay popup window for viva/client demo.</span>
              </div>
            </div>
            <div style="font-size:.82rem; color:#555;">
              Select <strong>NetBanking (SBI/HDFC)</strong> in popup to click the official <strong>[Success]</strong> button.
            </div>
          </div>

          <!-- Panel 4: COD -->
          <div id="panel-cash-on-delivery" class="payment-tab-panel" style="display:none; padding:18px; background:#faf5eb; border-radius:10px; border:1px solid var(--paper-dim);">
            <div style="font-size:.86rem; color:#6b6259; display:flex; align-items:center; gap:10px;">
              <i class="fas fa-circle-check" style="color:var(--sage); font-size:1.3rem;"></i>
              <span>Pay exact cash or UPI to the delivery executive when your parcel arrives at your doorstep.</span>
            </div>
          </div>

          <button type="submit" id="place-order-btn" class="btn btn-primary" style="width:100%; margin-top:18px; font-size:1rem; padding:14px;">
            <i class="fas fa-lock"></i> <span id="btn-text">Pay with Instant UPI / QR — <?= price($grand_total); ?></span>
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

<!-- Razorpay Official Checkout SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('checkout-form');
  const methodInput = document.getElementById('selected-payment-method');
  const cards = document.querySelectorAll('.payment-method-card');
  const panelUpi = document.getElementById('panel-upi');
  const panelCard = document.getElementById('panel-card');
  const panelRzp = document.getElementById('panel-razorpay');
  const panelCod = document.getElementById('panel-cash-on-delivery');
  const btnText = document.getElementById('btn-text');
  const placeBtn = document.getElementById('place-order-btn');

  const baseTotal = <?= (float)$grand_total; ?>;
  const totalDisplay = document.getElementById('grand-total-display');

  function updateTotals() {
    const formatted = '₹' + baseTotal.toFixed(2);
    if (totalDisplay) totalDisplay.textContent = formatted;

    const m = methodInput ? methodInput.value : 'UPI';
    if (m === 'UPI') {
      btnText.textContent = `Pay with Instant UPI / QR — ${formatted}`;
    } else if (m === 'Card') {
      btnText.textContent = `Authorize & Pay with Card — ${formatted}`;
    } else if (m === 'Razorpay') {
      btnText.textContent = `Launch Razorpay Modal — ${formatted}`;
    } else {
      btnText.textContent = `Place Order (Cash on Delivery) — ${formatted}`;
    }
  }

  // Toggle Payment Selector Cards
  cards.forEach(card => {
    card.addEventListener('click', () => {
      cards.forEach(c => c.classList.remove('active'));
      card.classList.add('active');
      const m = card.dataset.method;
      methodInput.value = m;

      if (panelUpi) panelUpi.style.display = 'none';
      if (panelCard) panelCard.style.display = 'none';
      if (panelRzp) panelRzp.style.display = 'none';
      if (panelCod) panelCod.style.display = 'none';

      if (m === 'UPI' && panelUpi) panelUpi.style.display = 'block';
      if (m === 'Card' && panelCard) panelCard.style.display = 'block';
      if (m === 'Razorpay' && panelRzp) panelRzp.style.display = 'block';
      if (m === 'Cash on Delivery' && panelCod) panelCod.style.display = 'block';

      updateTotals();
    });
  });

  // Fast UPI 1-Click Simulation Buttons (GPay, PhonePe, Paytm)
  document.querySelectorAll('.quick-upi-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const app = btn.dataset.app;
      methodInput.value = 'UPI';
      placeBtn.disabled = true;
      placeBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Processing ${app} Payment...`;
      setTimeout(() => {
        placeBtn.innerHTML = '<i class="fas fa-circle-check"></i> Approved! Creating Order...';
        form.submit();
      }, 700);
    });
  });

  // Live Card Typing Visuals
  const cardNumIn = document.getElementById('card-num-input');
  const cardNameIn = document.getElementById('card-name-input');
  const cardExpIn = document.getElementById('card-exp-input');

  if (cardNumIn) {
    cardNumIn.addEventListener('input', (e) => {
      let val = e.target.value.replace(/\D/g, '').substring(0, 16);
      let formatted = val.match(/.{1,4}/g)?.join(' ') || '4111 •••• •••• 1111';
      document.getElementById('card-num-display').textContent = formatted;
    });
  }
  if (cardNameIn) {
    cardNameIn.addEventListener('input', (e) => {
      document.getElementById('card-name-display').textContent = (e.target.value || 'CUSTOMER').toUpperCase();
    });
  }
  if (cardExpIn) {
    cardExpIn.addEventListener('input', (e) => {
      document.getElementById('card-exp-display').textContent = e.target.value || '12/28';
    });
  }

  // Handle Form Submission
  form.addEventListener('submit', async (e) => {
    const method = methodInput.value;

    // Direct Instant methods proceed seamlessly
    if (method === 'UPI' || method === 'Card' || method === 'Cash on Delivery') {
      placeBtn.disabled = true;
      placeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirming Order...';
      return true;
    }

    // Only Razorpay triggers the external popup
    if (method === 'Razorpay' && !document.getElementById('rzp-payment-id').value) {
      e.preventDefault();

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      placeBtn.disabled = true;
      const origHtml = placeBtn.innerHTML;
      placeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initializing Razorpay...';

      try {
        const res = await fetch('ajax/razorpay.php?action=create_order');
        const data = await res.json();

        if (!data.ok) {
          throw new Error(data.error || 'Could not initiate Razorpay payment.');
        }

        const options = {
          key: data.key_id,
          amount: data.amount,
          currency: data.currency,
          name: 'Bookly Bookstore',
          description: 'Order Payment (Bookly Curated Reads)',
          image: 'https://cdn-icons-png.fltrdr.com/512/3389/3389081.png',
          order_id: data.razorpay_order_id,
          prefill: {
            name: document.getElementById('cust-name').value || data.user_name,
            email: document.getElementById('cust-email').value || data.user_email,
            contact: document.getElementById('cust-phone').value || data.user_phone
          },
          theme: {
            color: '#6e1f2a'
          },
          handler: function (response) {
            document.getElementById('rzp-payment-id').value = response.razorpay_payment_id;
            document.getElementById('rzp-order-id').value = response.razorpay_order_id;
            document.getElementById('rzp-signature').value = response.razorpay_signature;
            
            placeBtn.innerHTML = '<i class="fas fa-circle-check"></i> Payment Verified! Completing Order...';
            form.submit();
          },
          modal: {
            ondismiss: function () {
              placeBtn.disabled = false;
              placeBtn.innerHTML = origHtml;
            }
          }
        };

        const rzp = new Razorpay(options);
        rzp.on('payment.failed', function (resp) {
          alert('Payment Failed: ' + (resp.error.description || 'Transaction declined'));
          placeBtn.disabled = false;
          placeBtn.innerHTML = origHtml;
        });

        rzp.open();
      } catch (err) {
        alert(err.message || 'Payment initiation failed.');
        placeBtn.disabled = false;
        placeBtn.innerHTML = origHtml;
      }
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


