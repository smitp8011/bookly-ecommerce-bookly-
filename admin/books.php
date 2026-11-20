<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_admin();

if (isset($_GET['delete'])) {
    if (!csrf_check($_GET['csrf'] ?? '')) {
        flash('error', 'Session expired.');
    } else {
        $id = (int) $_GET['delete'];
        try {
            $pdo->prepare('DELETE FROM books WHERE id = ?')->execute([$id]);
            flash('success', 'Book deleted.');
        } catch (Exception $e) {
            flash('error', 'Could not delete — it may be part of an existing order.');
        }
    }
    redirect('books.php');
}

$books = $pdo->query('SELECT b.*, c.name AS category_name FROM books b JOIN categories c ON c.id = b.category_id ORDER BY b.created_at DESC')->fetchAll();

$active = 'books';
$page_title = 'Books';
require_once 'includes/admin_header.php';
?>

<div class="section-head">
  <div><span class="eyebrow">Catalog</span><h2>Manage books</h2></div>
  <a href="book_form.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Book</a>
</div>

<div class="table-responsive">
  <table class="admin-table">
  <thead><tr><th>Cover</th><th>Title</th><th>Author</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($books as $b): ?>
      <tr>
        <td><img src="../images/<?= h($b['cover_image']); ?>" style="width:40px; height:54px; object-fit:cover; border-radius:4px;"></td>
        <td><?= h($b['title']); ?></td>
        <td><?= h($b['author']); ?></td>
        <td><?= h($b['category_name']); ?></td>
        <td>
          <div style="display:flex; align-items:center; gap:4px;">
            <span style="color:#888; font-size:.85rem;">₹</span>
            <input type="number" step="0.01" class="admin-inline-input" data-book-id="<?= $b['id']; ?>" data-field="price" value="<?= (float)$b['price']; ?>" style="width:75px; padding:4px 6px; font-size:.85rem; border:1px solid var(--paper-dim); border-radius:4px; text-align:right; font-weight:600;" title="Edit price directly and click away to save">
          </div>
        </td>
        <td>
          <div style="display:flex; align-items:center; gap:6px;">
            <input type="number" min="0" class="admin-inline-input <?= $b['stock'] <= 5 ? 'low-stock-input' : ''; ?>" data-book-id="<?= $b['id']; ?>" data-field="stock" value="<?= (int)$b['stock']; ?>" style="width:65px; padding:4px 6px; font-size:.85rem; border:1px solid var(--paper-dim); border-radius:4px; text-align:center; font-weight:600;" title="Edit stock directly and click away to save">
            <?php if ($b['stock'] <= 5): ?>
              <span class="status-pill status-cancelled" style="font-size:.65rem; padding:2px 6px;">Low</span>
            <?php endif; ?>
          </div>
        </td>
        <td>
          <a href="book_form.php?id=<?= $b['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
          <a href="books.php?delete=<?= $b['id']; ?>&csrf=<?= csrf_token(); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this book permanently?');">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
