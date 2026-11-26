<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_login();

$order_id = (int) ($_GET['id'] ?? 0);

if (!$order_id) {
    flash('error', 'Invalid order ID.');
    redirect('orders.php');
}

// Admins can view any order invoice; customers can only view their own.
if (is_admin()) {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$order_id]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
    $stmt->execute([$order_id, $_SESSION['user_id']]);
}
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'Order not found or access denied.');
    redirect('orders.php');
}

// Fetch items
$items_stmt = $pdo->prepare(
    'SELECT oi.*, b.cover_image, c.name AS category_name, b.author
     FROM order_items oi
     LEFT JOIN books b ON b.id = oi.book_id
     LEFT JOIN categories c ON c.id = b.category_id
     WHERE oi.order_id = ?'
);
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

$subtotal = 0;
foreach ($items as $it) {
    $subtotal += $it['price_at_purchase'] * $it['quantity'];
}
$discount = (float) ($order['discount_amount'] ?? 0);
$grand_total = (float) $order['total_price'];

$inv_number = 'INV-' . date('Y', strtotime($order['placed_on'])) . '-' . str_pad($order['id'], 5, '0', STR_PAD_LEFT);

$page_title = 'Invoice #' . $order['id'];
require_once 'includes/header.php';
?>

<div class="container" style="padding-top:20px; padding-bottom:50px;">
  <div class="invoice-controls">
    <a href="<?= is_admin() && str_contains($_SERVER['HTTP_REFERER'] ?? '', '/admin/') ? 'admin/orders.php' : 'orders.php'; ?>" class="btn btn-outline btn-sm">
      <i class="fas fa-arrow-left"></i> Back to Orders
    </a>
    <div style="display:flex; gap:8px;">
      <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">
        <i class="fas fa-print"></i> Print / Save as PDF
      </button>
    </div>
  </div>

  <div class="invoice-wrap reveal in">
    <div class="invoice-top">
      <div class="invoice-brand">
        <h2 style="display:flex; align-items:center; gap:8px;">
          <span class="logo-mark" style="width:26px; height:26px; display:inline-flex; color:var(--wine);">
            <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="2" y="3" width="7" height="30" rx="2" fill="currentColor" opacity="0.3"/>
              <path d="M9 4.5H20C24.6944 4.5 28.5 8.30558 28.5 13C28.5 16.2731 26.6508 19.1136 23.9554 20.4854C27.5147 21.8485 30 25.2635 30 29.25C30 30.7688 28.7688 32 27.25 32H9V4.5Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
              <path d="M9 18.25H19.5C22.3995 18.25 24.75 15.8995 24.75 13C24.75 10.1005 22.3995 7.75 19.5 7.75H9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
              <path d="M9 32H20.5C23.5376 32 26 29.5376 26 26.5C26 23.4624 23.5376 21 20.5 21H9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
              <path d="M19.5 21C21 23.5 20.5 27 18 29.5C16.8 30.7 15.5 32 15.5 32" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          </span>
          <span>BOOKLY</span>
        </h2>
        <p>Independent Bookstore & Curated Reads<br>support@bookly.local · www.bookly.local</p>
      </div>
      <div class="invoice-ref">
        <div class="inv-no"><?= h($inv_number); ?></div>
        <p><strong>Order Date:</strong> <?= date('d M Y, h:i A', strtotime($order['placed_on'])); ?></p>
        <p><strong>Status:</strong> <span class="status-pill status-<?= $order['status']; ?>"><?= ucfirst($order['status']); ?></span></p>
      </div>
    </div>

    <div class="invoice-details-grid">
      <div class="invoice-block">
        <h4>Billed & Shipped To</h4>
        <p>
          <strong><?= h($order['full_name']); ?></strong><br>
          <?= nl2br(h($order['address'])); ?><br>
          <strong>Phone:</strong> <?= h($order['phone']); ?><br>
          <strong>Email:</strong> <?= h($order['email']); ?>
        </p>
      </div>

      <div class="invoice-block">
        <h4>Payment & Order Details</h4>
        <p>
          <strong>Payment Method:</strong> <?= h($order['payment_method']); ?><br>
          <?php if (!empty($order['payment_id'])): ?>
            <strong>Payment ID:</strong> <code style="background:rgba(0,0,0,.06); padding:2px 6px; border-radius:4px; font-size:.8rem;"><?= h($order['payment_id']); ?></code><br>
          <?php endif; ?>
          <strong>Payment Status:</strong> <?= $order['status'] === 'cancelled' ? 'Cancelled' : ($order['payment_method'] === 'Cash on Delivery' && $order['status'] !== 'delivered' ? 'Pending on Delivery' : 'Paid / Confirmed'); ?><br>
          <?php if (!empty($order['coupon_code'])): ?>
            <strong>Coupon Applied:</strong> <span style="font-family:monospace; font-weight:700; color:var(--wine);"><?= h($order['coupon_code']); ?></span><br>
          <?php endif; ?>
          <strong>Order Reference:</strong> #<?= $order['id']; ?>
        </p>
      </div>
    </div>

    <table class="invoice-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Item Description</th>
          <th class="num price-col">Unit Price</th>
          <th class="num">Qty</th>
          <th class="num price-col">Line Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $idx => $it): 
          $line_total = $it['price_at_purchase'] * $it['quantity'];
        ?>
          <tr>
            <td><?= $idx + 1; ?></td>
            <td>
              <strong><?= h($it['title']); ?></strong>
              <?php if (!empty($it['author'])): ?>
                <span style="font-size:.8rem; color:#777; display:block;">by <?= h($it['author']); ?><?= !empty($it['category_name']) ? ' · ' . h($it['category_name']) : ''; ?></span>
              <?php endif; ?>
            </td>
            <td class="num price-col"><?= price($it['price_at_purchase']); ?></td>
            <td class="num"><?= $it['quantity']; ?></td>
            <td class="num price-col"><?= price($line_total); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="invoice-summary-wrap">
      <div class="invoice-notes">
        <h4 style="font-size:.78rem; text-transform:uppercase; color:#888; margin-bottom:6px;">Terms & Notes</h4>
        <p>Thank you for supporting independent literature. Returns and exchanges are accepted within 7 days of delivery for unread books in original condition.</p>
        <div style="margin-top:16px; font-size:.75rem; color:#aaa; font-family:monospace;">
          DIGITAL RECEIPT · VERIFIED BY BOOKLY TRANSACTION ID #<?= md5($order['id'] . $order['placed_on']); ?>
        </div>
      </div>

      <div class="invoice-totals">
        <div class="invoice-total-row">
          <span>Subtotal</span>
          <span><?= price($subtotal); ?></span>
        </div>
        <?php if ($discount > 0): ?>
          <div class="invoice-total-row" style="color:var(--sage); font-weight:600;">
            <span>Discount (<?= h($order['coupon_code'] ?? 'Coupon'); ?>)</span>
            <span>−<?= price($discount); ?></span>
          </div>
        <?php endif; ?>
        <div class="invoice-total-row">
          <span>Delivery / Shipping</span>
          <span style="color:var(--sage); font-weight:600;">FREE</span>
        </div>
        <div class="invoice-total-row grand price-col">
          <span>Grand Total</span>
          <span><?= price($grand_total); ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
