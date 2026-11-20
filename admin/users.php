<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_admin();

if (isset($_GET['delete'])) {
    if (csrf_check($_GET['csrf'] ?? '')) {
        $id = (int) $_GET['delete'];
        if ($id !== (int) $_SESSION['user_id']) {
            $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'user'")->execute([$id]);
            flash('success', 'User removed.');
        } else {
            flash('error', "You can't delete your own account.");
        }
    }
    redirect('users.php');
}

$users = $pdo->query(
    "SELECT u.*, COUNT(o.id) AS order_count, COALESCE(SUM(o.total_price),0) AS total_spent
     FROM users u LEFT JOIN orders o ON o.user_id = u.id
     WHERE u.role = 'user' GROUP BY u.id ORDER BY u.created_at DESC"
)->fetchAll();

$active = 'users';
$page_title = 'Users';
require_once 'includes/admin_header.php';
?>

<div class="section-head"><div><span class="eyebrow">People</span><h2>Registered users</h2></div></div>

<div class="table-responsive">
  <table class="admin-table">
  <thead><tr><th>Name</th><th>Email</th><th>Joined</th><th>Orders</th><th>Total spent</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= h($u['name']); ?></td>
        <td><?= h($u['email']); ?></td>
        <td><?= date('d M Y', strtotime($u['created_at'])); ?></td>
        <td><?= (int) $u['order_count']; ?></td>
        <td><?= price($u['total_spent']); ?></td>
        <td><a href="users.php?delete=<?= $u['id']; ?>&csrf=<?= csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this user?');">Remove</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($users)): ?><tr><td colspan="6">No users yet.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
