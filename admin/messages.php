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
  <thead>
    <tr>
      <th style="min-width:140px;">Name</th>
      <th style="min-width:180px;">Email</th>
      <th style="white-space:nowrap;">Phone</th>
      <th style="min-width:280px; max-width:500px;">Message</th>
      <th style="white-space:nowrap;">Received</th>
      <th style="white-space:nowrap; text-align:center;">Action</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($messages as $m): 
      $msgText = $m['message'];
      $topicBadge = '';
      if (preg_match('/^\[Topic:\s*([^\]]+)\]\s*(.*)$/s', $msgText, $topicMatch)) {
          $topicBadge = '<span class="msg-topic-badge"><i class="fas fa-tag" style="font-size:.65rem;"></i> ' . h($topicMatch[1]) . '</span><br>';
          $msgText = $topicMatch[2];
      }
    ?>
      <tr>
        <td style="white-space:normal; min-width:140px;"><strong><?= h($m['name']); ?></strong></td>
        <td style="white-space:normal; min-width:180px;"><a href="mailto:<?= h($m['email']); ?>" style="color:var(--wine); font-weight:600;"><?= h($m['email']); ?></a></td>
        <td style="white-space:nowrap;"><a href="tel:<?= h($m['phone']); ?>" style="color:var(--ink);"><?= h($m['phone']); ?></a></td>
        <td class="cell-wrap" style="white-space:normal !important; min-width:280px; max-width:500px; word-break:break-word;">
          <?= $topicBadge; ?>
          <div style="color:var(--ink); font-size:.86rem; line-height:1.45;"><?= nl2br(h($msgText)); ?></div>
        </td>
        <td style="white-space:nowrap; color:#6b6259; font-size:.82rem;"><?= date('d M Y, h:i A', strtotime($m['created_at'])); ?></td>
        <td style="white-space:nowrap; text-align:center;">
          <a href="messages.php?delete=<?= $m['id']; ?>&csrf=<?= csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this message?');">
            <i class="fas fa-trash"></i> Delete
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($messages)): ?><tr><td colspan="6">No messages yet.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
