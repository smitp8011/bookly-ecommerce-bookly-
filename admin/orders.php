<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (csrf_check($_POST['csrf'] ?? '')) {
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'replacement_requested', 'replacement_approved', 'replaced', 'replacement_rejected'];
        $order_id = (int) $_POST['order_id'];
        $status = trim((string)($_POST['status'] ?? ''));

        if (in_array($status, $valid_statuses, true)) {
            $current = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
            $current->execute([$order_id]);
            $order = $current->fetch();

            if ($order) {
                // Determine replacement_status sync
                $rep_status = $order['replacement_status'];
                $delivered_on = $order['delivered_on'];

                if ($status === 'delivered' && empty($delivered_on)) {
                    $delivered_on = date('Y-m-d H:i:s');
                }
                if ($status === 'replacement_requested') $rep_status = 'requested';
                if ($status === 'replacement_approved') $rep_status = 'approved';
                if ($status === 'replaced') $rep_status = 'replaced';
                if ($status === 'replacement_rejected') $rep_status = 'rejected';

                $stmt = $pdo->prepare('UPDATE orders SET status = ?, replacement_status = ?, delivered_on = COALESCE(delivered_on, ?) WHERE id = ?');
                $stmt->execute([$status, $rep_status, $delivered_on, $order_id]);

                // Auto-restore stock if cancelled by admin
                if ($status === 'cancelled' && $order['status'] !== 'cancelled') {
                    $item_rows = $pdo->prepare('SELECT book_id, quantity FROM order_items WHERE order_id = ?');
                    $item_rows->execute([$order_id]);
                    $restock_stmt = $pdo->prepare('UPDATE books SET stock = stock + ? WHERE id = ?');
                    foreach ($item_rows->fetchAll() as $item) {
                        $restock_stmt->execute([$item['quantity'], $item['book_id']]);
                    }
                }

                $msg = 'Order #' . $order_id . ' updated to ' . ucfirst(str_replace('_', ' ', $status)) . '! ';
                $msg .= '🔔 Automated alert simulated for ' . $order['full_name'] . ' (' . $order['email'] . ').';
                flash('success', $msg);
            }
        }
    }
    redirect('orders.php');
}

$all_statuses = ['pending', 'processing', 'shipped', 'delivered', 'replacement_requested', 'replacement_approved', 'replaced', 'replacement_rejected', 'cancelled'];
$filter_status = $_GET['status'] ?? '';
$sql = 'SELECT * FROM orders';
$params = [];
if ($filter_status) {
    $sql .= ' WHERE status = ?';
    $params[] = $filter_status;
}
$sql .= ' ORDER BY placed_on DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$items_stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');

$active = 'orders';
$page_title = 'Orders';
require_once 'includes/admin_header.php';
?>

<div class="section-head">
  <div><span class="eyebrow">Fulfilment</span><h2>Manage orders</h2></div>
  <form method="get">
    <select name="status" onchange="this.form.submit()" style="padding:9px 14px; border-radius:8px; border:1px solid var(--paper-dim);">
      <option value="">All statuses</option>
      <?php foreach ($all_statuses as $s): ?>
        <option value="<?= $s; ?>" <?= $filter_status === $s ? 'selected' : ''; ?>><?= ucfirst(str_replace('_', ' ', $s)); ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="table-responsive">
  <table class="admin-table">
  <thead><tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Placed</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($orders as $o):
      $items_stmt->execute([$o['id']]);
      $order_items = $items_stmt->fetchAll();
    ?>
      <tr>
        <td>#<?= $o['id']; ?></td>
        <td>
          <?= h($o['full_name']); ?><br>
          <span class="stock-badge"><?= h($o['email']); ?></span>
          <?php if (!empty($o['replacement_reason'])): ?>
            <div style="font-size:.72rem; color:#b45309; background:#fffbeb; border:1px solid #fde68a; padding:3px 6px; border-radius:4px; margin-top:4px;" title="<?= h($o['replacement_notes'] ?? ''); ?>">
              <i class="fas fa-rotate"></i> <strong>Replacement:</strong> <?= h($o['replacement_reason']); ?>
            </div>
          <?php endif; ?>
        </td>
        <td style="max-width:220px; font-size:.8rem;"><?= implode(', ', array_map(fn($i) => h($i['title']) . ' ×' . $i['quantity'], $order_items)); ?></td>
        <td><?= price($o['total_price']); ?></td>
        <td><?= date('d M Y', strtotime($o['placed_on'])); ?></td>
        <td>
          <form method="post" style="display:flex; gap:6px;">
            <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
            <input type="hidden" name="order_id" value="<?= $o['id']; ?>">
            <select name="status" onchange="this.form.submit()" class="status-pill status-<?= $o['status']; ?>" style="border:none; cursor:pointer;">
              <?php foreach ($all_statuses as $s): ?>
                <option value="<?= $s; ?>" <?= $o['status'] === $s ? 'selected' : ''; ?>><?= ucfirst(str_replace('_', ' ', $s)); ?></option>
              <?php endforeach; ?>
            </select>
            <input type="hidden" name="update_status" value="1">
          </form>
        </td>
        <td>
          <a href="../invoice.php?id=<?= $o['id']; ?>" class="btn btn-outline btn-sm" title="View / Print Invoice">
            <i class="fas fa-file-invoice"></i> Invoice
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($orders)): ?><tr><td colspan="7">No orders found.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
