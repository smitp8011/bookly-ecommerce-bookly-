<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_admin();

// Delete coupon
if (isset($_GET['delete'])) {
    if (!csrf_check($_GET['csrf'] ?? '')) {
        flash('error', 'Session expired.');
    } else {
        $id = (int) $_GET['delete'];
        $stmt = $pdo->prepare('DELETE FROM coupons WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Coupon deleted successfully.');
    }
    redirect('coupons.php');
}

// Toggle active status
if (isset($_GET['toggle'])) {
    if (!csrf_check($_GET['csrf'] ?? '')) {
        flash('error', 'Session expired.');
    } else {
        $id = (int) $_GET['toggle'];
        $stmt = $pdo->prepare('UPDATE coupons SET active = IF(active = 1, 0, 1) WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Coupon status updated.');
    }
    redirect('coupons.php');
}

$coupons = $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetchAll();

$active = 'coupons';
$page_title = 'Coupons';
require_once 'includes/admin_header.php';
?>

<div class="section-head">
  <div><span class="eyebrow">Discounts & Promotions</span><h2>Manage Coupons</h2></div>
  <a href="coupon_form.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Coupon</a>
</div>

<div class="table-responsive">
  <table class="admin-table">
  <thead>
    <tr>
      <th>Code</th>
      <th>Discount</th>
      <th>Min Order</th>
      <th>Usage</th>
      <th>Expires</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($coupons as $c): 
      $is_expired = $c['expires_at'] && strtotime($c['expires_at']) < strtotime('today');
      $is_maxed = $c['max_uses'] !== null && $c['used_count'] >= $c['max_uses'];
    ?>
      <tr>
        <td>
          <span style="font-family:monospace; font-weight:700; font-size:1.05rem; color:var(--wine); background:var(--paper-dim); padding:4px 8px; border-radius:6px;">
            <?= h($c['code']); ?>
          </span>
        </td>
        <td>
          <?= $c['discount_type'] === 'percent' ? (float)$c['discount_value'] . '%' : price($c['discount_value']); ?>
          <span style="font-size:.75rem; color:#888; display:block;"><?= ucfirst($c['discount_type']); ?></span>
        </td>
        <td><?= price($c['min_order_value']); ?></td>
        <td>
          <strong><?= $c['used_count']; ?></strong> / <?= $c['max_uses'] !== null ? $c['max_uses'] : '∞'; ?>
          <?php if ($is_maxed): ?><span class="status-pill status-cancelled" style="margin-left:4px;">Maxed</span><?php endif; ?>
        </td>
        <td>
          <?= $c['expires_at'] ? date('d M Y', strtotime($c['expires_at'])) : '<span style="color:#888;">Never</span>'; ?>
          <?php if ($is_expired): ?><span class="status-pill status-cancelled" style="margin-left:4px;">Expired</span><?php endif; ?>
        </td>
        <td>
          <span class="status-pill <?= $c['active'] ? 'status-delivered' : 'status-cancelled'; ?>">
            <?= $c['active'] ? 'Active' : 'Inactive'; ?>
          </span>
        </td>
        <td>
          <div style="display:flex; gap:6px; align-items:center;">
            <a href="coupon_form.php?id=<?= $c['id']; ?>" class="btn btn-outline btn-sm" title="Edit Coupon">
              <i class="fas fa-pen"></i>
            </a>
            <a href="coupons.php?toggle=<?= $c['id']; ?>&csrf=<?= csrf_token(); ?>" class="btn btn-outline btn-sm" title="<?= $c['active'] ? 'Deactivate' : 'Activate'; ?>">
              <i class="fas <?= $c['active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
            </a>
            <a href="coupons.php?delete=<?= $c['id']; ?>&csrf=<?= csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete coupon <?= h($c['code']); ?> permanently?');" title="Delete Coupon">
              <i class="fas fa-trash"></i>
            </a>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($coupons)): ?>
      <tr><td colspan="7">No coupons found. Click "Add Coupon" to create your first discount code.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
