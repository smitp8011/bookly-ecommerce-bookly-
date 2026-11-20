<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_admin();

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-29 days'));
$to   = $_GET['to'] ?? date('Y-m-d');
// keep the range sane even if the user swaps the fields
if (strtotime($from) > strtotime($to)) { [$from, $to] = [$to, $from]; }
$to_inclusive = $to . ' 23:59:59';

// ---- summary ----
$summary_stmt = $pdo->prepare(
    "SELECT COUNT(*) AS order_count, COALESCE(SUM(total_price),0) AS revenue,
            COALESCE(SUM(discount_amount),0) AS discounts,
            COALESCE(AVG(total_price),0) AS avg_order
     FROM orders WHERE placed_on BETWEEN ? AND ? AND status != 'cancelled'"
);
$summary_stmt->execute([$from, $to_inclusive]);
$summary = $summary_stmt->fetch();

$cancelled_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE placed_on BETWEEN ? AND ? AND status = 'cancelled'");
$cancelled_stmt->execute([$from, $to_inclusive]);
$cancelled_count = (int) $cancelled_stmt->fetchColumn();

$new_users_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='user' AND created_at BETWEEN ? AND ?");
$new_users_stmt->execute([$from, $to_inclusive]);
$new_users = (int) $new_users_stmt->fetchColumn();

// ---- orders by status (within range) ----
$status_stmt = $pdo->prepare(
    "SELECT status, COUNT(*) AS cnt FROM orders WHERE placed_on BETWEEN ? AND ? GROUP BY status"
);
$status_stmt->execute([$from, $to_inclusive]);
$status_rows = $status_stmt->fetchAll();
$status_counts = array_fill_keys(['pending','processing','shipped','delivered','cancelled'], 0);
foreach ($status_rows as $r) $status_counts[$r['status']] = (int) $r['cnt'];

// ---- top selling books ----
$top_stmt = $pdo->prepare(
    "SELECT b.title, b.author, SUM(oi.quantity) AS units_sold, SUM(oi.quantity * oi.price_at_purchase) AS revenue
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     JOIN books b ON b.id = oi.book_id
     WHERE o.placed_on BETWEEN ? AND ? AND o.status != 'cancelled'
     GROUP BY oi.book_id ORDER BY units_sold DESC LIMIT 10"
);
$top_stmt->execute([$from, $to_inclusive]);
$top_books = $top_stmt->fetchAll();

// ---- revenue by category ----
$cat_stmt = $pdo->prepare(
    "SELECT c.name, SUM(oi.quantity * oi.price_at_purchase) AS revenue, SUM(oi.quantity) AS units
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     JOIN books b ON b.id = oi.book_id
     JOIN categories c ON c.id = b.category_id
     WHERE o.placed_on BETWEEN ? AND ? AND o.status != 'cancelled'
     GROUP BY c.id ORDER BY revenue DESC"
);
$cat_stmt->execute([$from, $to_inclusive]);
$cat_rows = $cat_stmt->fetchAll();

// ---- daily revenue trend ----
$daily_stmt = $pdo->prepare(
    "SELECT DATE(placed_on) AS d, SUM(total_price) AS revenue, COUNT(*) AS orders
     FROM orders WHERE placed_on BETWEEN ? AND ? AND status != 'cancelled'
     GROUP BY DATE(placed_on) ORDER BY d"
);
$daily_stmt->execute([$from, $to_inclusive]);
$daily_rows = $daily_stmt->fetchAll();

// ---- low stock ----
$low_stock = $pdo->query('SELECT title, author, stock FROM books WHERE stock <= 5 ORDER BY stock ASC')->fetchAll();

// ---- coupon usage ----
$coupon_stmt = $pdo->query('SELECT code, discount_type, discount_value, used_count, max_uses FROM coupons ORDER BY used_count DESC');
$coupons = $coupon_stmt->fetchAll();

$active = 'reports';
$page_title = 'Reports';
require_once 'includes/admin_header.php';
?>

<div class="section-head">
  <div><span class="eyebrow">Insights</span><h2>Reports</h2></div>
</div>

<form method="get" class="report-filters">
  <div class="field">
    <label>From</label>
    <input type="date" name="from" value="<?= h($from); ?>">
  </div>
  <div class="field">
    <label>To</label>
    <input type="date" name="to" value="<?= h($to); ?>">
  </div>
  <button type="submit" class="btn btn-primary btn-sm">Apply</button>
  <a href="reports.php" class="btn btn-outline btn-sm">Last 30 days</a>
  <a href="export_report.php?from=<?= h($from); ?>&to=<?= h($to); ?>&csrf=<?= csrf_token(); ?>" class="btn btn-gold btn-sm" style="margin-left:auto;"><i class="fas fa-download"></i> Export Orders CSV</a>
</form>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= (int) $summary['order_count']; ?></div><div class="label">Orders (excl. cancelled)</div></div>
  <div class="stat-card"><div class="num"><?= price($summary['revenue']); ?></div><div class="label">Revenue</div></div>
  <div class="stat-card"><div class="num"><?= price($summary['avg_order']); ?></div><div class="label">Avg. order value</div></div>
  <div class="stat-card"><div class="num"><?= price($summary['discounts']); ?></div><div class="label">Discounts given</div></div>
  <div class="stat-card"><div class="num"><?= $new_users; ?></div><div class="label">New customers</div></div>
  <div class="stat-card"><div class="num"><?= $cancelled_count; ?></div><div class="label">Cancelled orders</div></div>
</div>

<div class="report-section">
  <h4>Revenue trend</h4>
  <canvas id="trendChart" height="90"></canvas>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
  <div class="report-section">
    <h4>Orders by status</h4>
    <div class="table-responsive">
  <table class="admin-table">
      <thead><tr><th>Status</th><th>Orders</th></tr></thead>
      <tbody>
        <?php foreach ($status_counts as $s => $cnt): ?>
          <tr><td><span class="status-pill status-<?= $s; ?>"><?= ucfirst($s); ?></span></td><td><?= $cnt; ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
</div>
  </div>

  <div class="report-section">
    <h4>Revenue by genre</h4>
    <div class="table-responsive">
  <table class="admin-table">
      <thead><tr><th>Genre</th><th>Units</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php foreach ($cat_rows as $c): ?>
          <tr><td><?= h($c['name']); ?></td><td><?= (int) $c['units']; ?></td><td><?= price($c['revenue']); ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($cat_rows)): ?><tr><td colspan="3">No sales in this range.</td></tr><?php endif; ?>
      </tbody>
    </table>
</div>
  </div>
</div>

<div class="report-section">
  <h4>Top selling books</h4>
  <div class="table-responsive">
  <table class="admin-table">
    <thead><tr><th>#</th><th>Title</th><th>Author</th><th>Units sold</th><th>Revenue</th></tr></thead>
    <tbody>
      <?php foreach ($top_books as $i => $b): ?>
        <tr><td><?= $i + 1; ?></td><td><?= h($b['title']); ?></td><td><?= h($b['author']); ?></td><td><?= (int) $b['units_sold']; ?></td><td><?= price($b['revenue']); ?></td></tr>
      <?php endforeach; ?>
      <?php if (empty($top_books)): ?><tr><td colspan="5">No sales in this range.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
  <div class="report-section">
    <h4>Low stock (≤ 5 copies)</h4>
    <div class="table-responsive">
  <table class="admin-table">
      <thead><tr><th>Title</th><th>Author</th><th>Stock</th></tr></thead>
      <tbody>
        <?php foreach ($low_stock as $b): ?>
          <tr><td><?= h($b['title']); ?></td><td><?= h($b['author']); ?></td><td><span class="status-pill status-cancelled"><?= $b['stock']; ?></span></td></tr>
        <?php endforeach; ?>
        <?php if (empty($low_stock)): ?><tr><td colspan="3">All books well stocked.</td></tr><?php endif; ?>
      </tbody>
    </table>
</div>
  </div>

  <div class="report-section">
    <h4>Coupon usage</h4>
    <div class="table-responsive">
  <table class="admin-table">
      <thead><tr><th>Code</th><th>Discount</th><th>Used</th></tr></thead>
      <tbody>
        <?php foreach ($coupons as $c): ?>
          <tr>
            <td><?= h($c['code']); ?></td>
            <td><?= $c['discount_type'] === 'percent' ? (int) $c['discount_value'] . '%' : price($c['discount_value']); ?></td>
            <td><?= (int) $c['used_count']; ?><?= $c['max_uses'] ? ' / ' . (int) $c['max_uses'] : ''; ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($coupons)): ?><tr><td colspan="3">No coupons set up.</td></tr><?php endif; ?>
      </tbody>
    </table>
</div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const trendLabels = <?= json_encode(array_map(fn($r) => date('d M', strtotime($r['d'])), $daily_rows)); ?>;
const trendRevenue = <?= json_encode(array_map(fn($r) => (float) $r['revenue'], $daily_rows)); ?>;
new Chart(document.getElementById('trendChart'), {
  type: 'line',
  data: { labels: trendLabels, datasets: [{ label: 'Revenue', data: trendRevenue, borderColor: '#6e1f2a', backgroundColor: 'rgba(110,31,42,.1)', tension: .3, fill: true }] },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>
