<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_admin();

if (isset($_GET['delete'])) {
    if (csrf_check($_GET['csrf'] ?? '')) {
        $pdo->prepare('DELETE FROM messages WHERE id = ?')->execute([(int) $_GET['delete']]);
        flash('success', 'Message deleted.');
    }
    redirect('messages.php');
}

$messages = $pdo->query('SELECT * FROM messages ORDER BY created_at DESC')->fetchAll();

$active = 'messages';
$page_title = 'Messages';
require_once 'includes/admin_header.php';
?>

<div class="section-head"><div><span class="eyebrow">Inbox</span><h2>Contact messages</h2></div></div>

<div class="table-responsive">
  <table class="admin-table">
  <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Message</th><th>Received</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($messages as $m): 
      $msgText = $m['message'];
      $topicBadge = '';
      if (preg_match('/^\[Topic:\s*([^\]]+)\]\s*(.*)$/s', $msgText, $topicMatch)) {
          $topicBadge = '<span style="background:rgba(201,162,75,.18); color:var(--gold); padding:2px 8px; border-radius:10px; font-size:.72rem; font-weight:700; display:inline-block; margin-bottom:4px;">' . h($topicMatch[1]) . '</span><br>';
          $msgText = $topicMatch[2];
      }
    ?>
      <tr>
        <td><strong><?= h($m['name']); ?></strong></td>
        <td><a href="mailto:<?= h($m['email']); ?>" style="color:var(--wine); font-weight:600;"><?= h($m['email']); ?></a></td>
        <td><a href="tel:<?= h($m['phone']); ?>"><?= h($m['phone']); ?></a></td>
        <td style="max-width:320px; line-height:1.4;"><?= $topicBadge; ?><?= nl2br(h($msgText)); ?></td>
        <td><?= date('d M Y, h:i A', strtotime($m['created_at'])); ?></td>
        <td><a href="messages.php?delete=<?= $m['id']; ?>&csrf=<?= csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this message?');"><i class="fas fa-trash"></i> Delete</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($messages)): ?><tr><td colspan="6">No messages yet.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
