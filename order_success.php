<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_login();

$order_id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) redirect('home.php');

$page_title = 'Order Confirmed';
require_once 'includes/header.php';
?>

<section>
  <div class="container">
    <div class="empty-state reveal in">
      <i class="fas fa-circle-check" style="color:var(--sage);"></i>
      <h3>Order #<?= $order['id']; ?> placed successfully!</h3>
      <p>We've sent a confirmation email to <strong><?= h($order['email']); ?></strong>. You can track its live status and download your invoice below.</p>
      <div style="display:flex; justify-content:center; gap:12px; flex-wrap:wrap; margin-top:24px;">
        <a href="invoice.php?id=<?= $order['id']; ?>" class="btn btn-gold"><i class="fas fa-file-invoice"></i> View & Print Invoice</a>
        <a href="orders.php" class="btn btn-primary"><i class="fas fa-truck-fast"></i> Track in My Orders</a>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
