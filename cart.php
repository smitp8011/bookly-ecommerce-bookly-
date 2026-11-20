<?php
require_once 'config.php';
require_once 'includes/functions.php';

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id) {
    $stmt = $pdo->prepare(
        'SELECT c.quantity, b.* FROM cart c JOIN books b ON b.id = c.book_id WHERE c.user_id = ? ORDER BY c.added_at DESC'
    );
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();
} else {
    $items = [];
    if (!empty($_SESSION['guest_cart'])) {
        $book_ids = array_keys($_SESSION['guest_cart']);
        if (!empty($book_ids)) {
            $in_placeholders = implode(',', array_fill(0, count($book_ids), '?'));
            $stmt = $pdo->prepare("SELECT * FROM books WHERE id IN ($in_placeholders)");
            $stmt->execute($book_ids);
            foreach ($stmt->fetchAll() as $b) {
                $b['quantity'] = $_SESSION['guest_cart'][$b['id']] ?? 1;
                $items[] = $b;
            }
        }
    }
}

$subtotal = 0;
foreach ($items as $it) $subtotal += $it['price'] * $it['quantity'];
$item_count = array_sum(array_column($items, 'quantity'));

// Re-validate any applied coupon against the current cart (quantities may have changed).
$applied_coupon = null;
$discount = 0;
if (!empty($_SESSION['coupon']['code']) && $subtotal > 0) {
    $check = validate_coupon($pdo, $_SESSION['coupon']['code'], $subtotal);
    if ($check['ok']) {
        $applied_coupon = $_SESSION['coupon']['code'];
        $discount = $check['discount'];
        $_SESSION['coupon']['discount'] = $discount;
    } else {
        unset($_SESSION['coupon']);
    }
}
$total = max(0, $subtotal - $discount);

$active_page = 'cart';
$page_title = 'Your Cart';
require_once 'includes/header.php';
?>
<style>
  /* Sirf cart ke qty-input ke arrows hatane ke liye */
  
  /* Chrome, Safari, Edge, Opera */
  .qty-input::-webkit-outer-spin-button,
  .qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none !important;
    margin: 0;
  }

  /* Firefox aur aane wale modern browsers ke liye */
  .qty-input {
    -moz-appearance: textfield !important;
    appearance: textfield !important;
  }
</style>
<section>
  <div class="container">
    <div class="section-head reveal in"><div><span class="eyebrow">Cart</span><h2>Your shopping bag</h2></div></div>

    <div class="cart-page">
      <div id="cart-list">
        <?php if (empty($items)): ?>
          <div class="empty-state">
            <i class="fas fa-book-open"></i>
            <h3>Your cart is empty</h3>
            <p>Browse the shop to find your next read.</p>
            <a href="shop.php" class="btn btn-primary" style="margin-top:16px;">Browse Books</a>
          </div>
        <?php else: foreach ($items as $item): ?>
          <div class="cart-item reveal in">
            <img src="images/<?= h($item['cover_image']); ?>" alt="<?= h($item['title']); ?>">
            <div>
              <h3 style="font-size:1rem;"><a href="book.php?id=<?= $item['id']; ?>"><?= h($item['title']); ?></a></h3>
              <div class="author" style="font-size:.82rem;"><?= h($item['author']); ?></div>
              <div class="price-tag" style="font-size:.95rem;"><?= price($item['price']); ?></div>
            </div>
            <div class="qty-control" data-book-id="<?= $item['id']; ?>">
              <button class="qty-minus" type="button">−</button>
              <!-- YAHAN MAX STOCK LIMIT ADD KI GAYI HAI -->
              <input type="number" class="qty-input" value="<?= $item['quantity']; ?>" min="1" max="<?= $item['stock']; ?>" style="width: 40px; text-align: center; border: none; background: transparent; font-size: 1rem; outline: none;">
              <button class="qty-plus" type="button">+</button>
            </div>
            <a href="#" class="remove-link" data-book-id="<?= $item['id']; ?>"><i class="fas fa-trash"></i></a>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <?php if (!empty($items)): ?>
        <div class="summary-card">
          <h3 style="margin-bottom:18px;">Order Summary</h3>
          <div class="summary-row"><span>Items (<span id="cart-item-count"><?= $item_count; ?></span>)</span><span id="cart-subtotal"><?= price($subtotal); ?></span></div>
          <div class="summary-row"><span>Shipping</span><span>Free</span></div>

          <?php if ($applied_coupon): ?>
            <div class="summary-row discount"><span>Coupon (<?= h($applied_coupon); ?>)</span><span>−<?= price($discount); ?></span></div>
            <div class="coupon-applied">
              <span><i class="fas fa-tag"></i> <?= h($applied_coupon); ?> applied</span>
              <button type="button" class="remove-coupon">Remove</button>
            </div>
          <?php else: ?>
            <form id="coupon-form" class="coupon-box">
              <input type="text" id="coupon-input" placeholder="Coupon code" autocomplete="off">
              <button type="submit" class="btn btn-outline btn-sm">Apply</button>
            </form>
            <div id="coupon-msg" style="font-size:.78rem; margin-bottom:10px;"></div>
          <?php endif; ?>

          <div class="summary-row total"><span>Total</span><span id="cart-total"><?= price($total); ?></span></div>
          <?php if (is_logged_in()): ?>
            <a href="checkout.php" class="btn btn-primary" style="width:100%; margin-top:16px;">Proceed to Checkout</a>
          <?php else: ?>
            <a href="login.php?redirect=checkout.php" class="btn btn-primary" style="width:100%; margin-top:16px;">
              <i class="fas fa-lock"></i> Log in to Checkout
            </a>
            <div style="font-size:.78rem; text-align:center; color:#888; margin-top:8px;">New to Bookly? <a href="register.php?redirect=checkout.php" style="color:var(--wine); font-weight:600;">Register here</a></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>