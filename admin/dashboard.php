<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_admin();

$total_books   = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$total_orders  = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$total_users   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_revenue = (float) $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$low_stock     = $pdo->query('SELECT * FROM books WHERE stock <= 5 ORDER BY stock ASC LIMIT 6')->fetchAll();

$sales_by_day = $pdo->query(
    "SELECT DATE(placed_on) AS d, SUM(total_price) AS total FROM orders
     WHERE placed_on >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(placed_on) ORDER BY d"
)->fetchAll();

$top_books = $pdo->query(
    "SELECT b.title, SUM(oi.quantity) AS sold FROM order_items oi JOIN books b ON b.id = oi.book_id
     GROUP BY oi.book_id ORDER BY sold DESC LIMIT 5"
)->fetchAll();

$recent_orders = $pdo->query('SELECT * FROM orders ORDER BY placed_on DESC LIMIT 6')->fetchAll();

$active = 'dashboard';
$page_title = 'Dashboard';
require_once 'includes/admin_header.php';
?>

<div class="section-head">
  <div><span class="eyebrow">Overview</span><h2>Dashboard</h2></div>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= $total_books; ?></div><div class="label">Total books</div></div>
  <div class="stat-card"><div class="num"><?= $total_orders; ?></div><div class="label">Total orders</div></div>
  <div class="stat-card"><div class="num"><?= $total_users; ?></div><div class="label">Registered users</div></div>
  <div class="stat-card"><div class="num"><?= price($total_revenue); ?></div><div class="label">Total revenue</div></div>
</div>

<div style="display:grid; grid-template-columns:1.4fr 1fr; gap:24px; margin-bottom:32px;">
  <div class="stat-card">
    <h4 style="margin-bottom:16px;">Sales — last 7 days</h4>
    <canvas id="salesChart" height="120"></canvas>
  </div>
  <div class="stat-card">
    <h4 style="margin-bottom:16px;">Top sellers</h4>
    <canvas id="topBooksChart" height="160"></canvas>
  </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
  <div>
    <h4 style="margin-bottom:14px;">Low stock alerts</h4>
    <div class="table-responsive">
  <table class="admin-table">
      <thead><tr><th>Title</th><th>Stock</th></tr></thead>
      <tbody>
        <?php foreach ($low_stock as $b): ?>
          <tr><td><?= h($b['title']); ?></td><td><span class="status-pill status-cancelled"><?= $b['stock']; ?> left</span></td></tr>
        <?php endforeach; ?>
        <?php if (empty($low_stock)): ?><tr><td colspan="2">All books are well stocked.</td></tr><?php endif; ?>
      </tbody>
    </table>
</div>
  </div>
  <div>
    <h4 style="margin-bottom:14px;">Recent orders</h4>
    <div class="table-responsive">
  <table class="admin-table">
      <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($recent_orders as $o): ?>
          <tr>
            <td>#<?= $o['id']; ?></td><td><?= h($o['full_name']); ?></td><td><?= price($o['total_price']); ?></td>
            <td><span class="status-pill status-<?= $o['status']; ?>"><?= ucfirst($o['status']); ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($recent_orders)): ?><tr><td colspan="4">No orders yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
</div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
const textColor = isDark ? '#dcd3c4' : '#6b6259';
const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';

const salesLabels = <?= json_encode(array_map(fn($r) => date('D, M j', strtotime($r['d'])), $sales_by_day)); ?>;
const salesData = <?= json_encode(array_map(fn($r) => (float) $r['total'], $sales_by_day)); ?>;
new Chart(document.getElementById('salesChart'), {
  type: 'line',
  data: {
    labels: salesLabels,
    datasets: [{
      label: 'Revenue (₹)',
      data: salesData,
      borderColor: isDark ? '#e05a68' : '#6e1f2a',
      backgroundColor: isDark ? 'rgba(224,90,104,.15)' : 'rgba(110,31,42,.12)',
      tension: .35,
      fill: true,
      pointBackgroundColor: isDark ? '#e5c158' : '#6e1f2a',
      pointRadius: 4
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false }
    },
    scales: {
      x: { ticks: { color: textColor }, grid: { color: gridColor } },
      y: { beginAtZero: true, ticks: { color: textColor, callback: v => '₹' + v }, grid: { color: gridColor } }
    }
  }
});

const topLabels = <?= json_encode(array_map(fn($r) => mb_strimwidth($r['title'], 0, 20, '...'), $top_books)); ?>;
const topData = <?= json_encode(array_map(fn($r) => (int) $r['sold'], $top_books)); ?>;
new Chart(document.getElementById('topBooksChart'), {
  type: 'bar',
  data: {
    labels: topLabels,
    datasets: [{
      label: 'Copies sold',
      data: topData,
      backgroundColor: isDark ? '#e5c158' : '#c9a24b',
      borderRadius: 6
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: {
      legend: { display: false }
    },
    scales: {
      x: { beginAtZero: true, ticks: { color: textColor, precision: 0 }, grid: { color: gridColor } },
      y: { ticks: { color: textColor }, grid: { display: false } }
    }
  }
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>
