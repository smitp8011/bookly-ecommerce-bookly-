<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_admin();

// Delete review
if (isset($_GET['delete'])) {
    if (!csrf_check($_GET['csrf'] ?? '')) {
        flash('error', 'Session expired.');
    } else {
        $id = (int) $_GET['delete'];
        $stmt = $pdo->prepare('DELETE FROM reviews WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Review deleted successfully.');
    }
    redirect('reviews.php');
}

$rating_filter = isset($_GET['rating']) && $_GET['rating'] !== '' ? (int) $_GET['rating'] : null;

$sql = 'SELECT r.*, b.title AS book_title, b.cover_image, u.name AS user_name, u.email AS user_email 
        FROM reviews r 
        JOIN books b ON b.id = r.book_id 
        JOIN users u ON u.id = r.user_id';
$params = [];

if ($rating_filter !== null && $rating_filter >= 1 && $rating_filter <= 5) {
    $sql .= ' WHERE r.rating = ?';
    $params[] = $rating_filter;
}

$sql .= ' ORDER BY r.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

$active = 'reviews';
$page_title = 'Reviews';
require_once 'includes/admin_header.php';
?>

<div class="section-head">
  <div><span class="eyebrow">Moderation</span><h2>Reader Reviews</h2></div>

  <form method="get" style="display:flex; gap:10px; align-items:center;">
    <select name="rating" onchange="this.form.submit()" style="padding:9px 14px; border-radius:8px; border:1px solid var(--paper-dim); font-size:.85rem;">
      <option value="">All Star Ratings</option>
      <option value="5" <?= $rating_filter === 5 ? 'selected' : ''; ?>>★★★★★ (5 Stars)</option>
      <option value="4" <?= $rating_filter === 4 ? 'selected' : ''; ?>>★★★★☆ (4 Stars)</option>
      <option value="3" <?= $rating_filter === 3 ? 'selected' : ''; ?>>★★★☆☆ (3 Stars)</option>
      <option value="2" <?= $rating_filter === 2 ? 'selected' : ''; ?>>★★☆☆☆ (2 Stars)</option>
      <option value="1" <?= $rating_filter === 1 ? 'selected' : ''; ?>>★☆☆☆☆ (1 Star)</option>
    </select>
  </form>
</div>

<div class="table-responsive">
  <table class="admin-table">
  <thead>
    <tr>
      <th>Book</th>
      <th>Reviewer</th>
      <th>Rating</th>
      <th>Comment</th>
      <th>Date</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($reviews as $rev): ?>
      <tr>
        <td>
          <div style="display:flex; align-items:center; gap:10px;">
            <img src="../images/<?= h($rev['cover_image']); ?>" style="width:36px; height:48px; object-fit:cover; border-radius:4px;">
            <a href="../book.php?id=<?= $rev['book_id']; ?>" target="_blank" style="font-weight:600; color:var(--wine);">
              <?= h($rev['book_title']); ?> <i class="fas fa-arrow-up-right-from-square" style="font-size:.7rem;"></i>
            </a>
          </div>
        </td>
        <td>
          <strong><?= h($rev['user_name']); ?></strong><br>
          <span class="stock-badge" style="font-size:.75rem;"><?= h($rev['user_email']); ?></span>
        </td>
        <td>
          <span class="stars" style="font-size:.85rem;">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <i class="fas fa-star" style="<?= $i <= $rev['rating'] ? 'color:var(--gold);' : 'color:var(--paper-dim);'; ?>"></i>
            <?php endfor; ?>
          </span>
        </td>
        <td class="cell-wrap" style="white-space:normal !important; min-width:240px; max-width:460px; word-break:break-word; font-size:.88rem; line-height:1.45;">
          <?= nl2br(h($rev['comment'])); ?>
        </td>
        <td style="font-size:.8rem; color:#888; white-space:nowrap;">
          <?= date('d M Y, h:i A', strtotime($rev['created_at'])); ?>
        </td>
        <td>
          <a href="reviews.php?delete=<?= $rev['id']; ?>&csrf=<?= csrf_token(); ?>" 
             class="btn btn-danger btn-sm" 
             onclick="return confirm('Delete this review permanently?');"
             title="Delete Review">
            <i class="fas fa-trash"></i> Delete
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($reviews)): ?>
      <tr><td colspan="6">No reader reviews found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
