<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_admin();

$errors = [];
$edit_id = (int) ($_GET['edit'] ?? 0);

// Add or Edit category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $cat_id = (int) ($_POST['category_id'] ?? 0);

        if ($name === '') {
            $errors[] = 'Category name cannot be empty.';
        } else {
            // Check uniqueness
            $check = $pdo->prepare('SELECT id FROM categories WHERE name = ? AND id != ?');
            $check->execute([$name, $cat_id]);
            if ($check->fetch()) {
                $errors[] = 'A category with the name "' . h($name) . '" already exists.';
            } else {
                if ($cat_id) {
                    $stmt = $pdo->prepare('UPDATE categories SET name = ? WHERE id = ?');
                    $stmt->execute([$name, $cat_id]);
                    flash('success', 'Category updated successfully.');
                } else {
                    $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
                    $stmt->execute([$name]);
                    flash('success', 'Category "' . $name . '" created successfully.');
                }
                redirect('categories.php');
            }
        }
    }
}

// Delete category
if (isset($_GET['delete'])) {
    if (!csrf_check($_GET['csrf'] ?? '')) {
        flash('error', 'Session expired.');
    } else {
        $id = (int) $_GET['delete'];
        
        // Safety check: ensure no books are linked
        $book_count_stmt = $pdo->prepare('SELECT COUNT(*) FROM books WHERE category_id = ?');
        $book_count_stmt->execute([$id]);
        $linked_books = (int) $book_count_stmt->fetchColumn();

        if ($linked_books > 0) {
            flash('error', 'Cannot delete this category: ' . $linked_books . ' book(s) are assigned to it. Please reassign those books first.');
        } else {
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->execute([$id]);
            flash('success', 'Category deleted successfully.');
        }
    }
    redirect('categories.php');
}

// Fetch all categories with book counts
$categories = $pdo->query(
    'SELECT c.*, COUNT(b.id) AS book_count 
     FROM categories c 
     LEFT JOIN books b ON b.category_id = c.id 
     GROUP BY c.id 
     ORDER BY c.name ASC'
)->fetchAll();

// If editing, get current category name
$edit_category = null;
if ($edit_id) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$edit_id]);
    $edit_category = $stmt->fetch();
}

$active = 'categories';
$page_title = 'Categories';
require_once 'includes/admin_header.php';
?>

<div class="section-head">
  <div><span class="eyebrow">Inventory Organization</span><h2>Manage Categories</h2></div>
</div>

<?php foreach ($errors as $err): ?>
  <div class="field-error" style="margin-bottom:16px;"><i class="fas fa-circle-exclamation"></i> <?= h($err); ?></div>
<?php endforeach; ?>

<div style="display:grid; grid-template-columns:360px 1fr; gap:28px; align-items:start;">
  <!-- Left card: Add / Edit form -->
  <div class="admin-form" style="background:#fffdf8; padding:24px; border-radius:12px; box-shadow:var(--shadow-sm);">
    <h4 style="margin-bottom:14px;">
      <i class="fas <?= $edit_category ? 'fa-pen-to-square' : 'fa-plus-circle'; ?>"></i> 
      <?= $edit_category ? 'Edit Category' : 'Add New Category'; ?>
    </h4>

    <form action="categories.php" method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
      <input type="hidden" name="category_id" value="<?= $edit_category['id'] ?? 0; ?>">

      <label for="cat-name">Category Name *</label>
      <input type="text" id="cat-name" name="name" required value="<?= h($edit_category['name'] ?? ''); ?>" placeholder="e.g. Science Fiction, Romance, History" autofocus>

      <div style="display:flex; gap:10px; margin-top:8px;">
        <button type="submit" class="btn btn-primary btn-sm" style="flex:1;">
          <i class="fas fa-floppy-disk"></i> <?= $edit_category ? 'Update Category' : 'Add Category'; ?>
        </button>
        <?php if ($edit_category): ?>
          <a href="categories.php" class="btn btn-outline btn-sm">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Right card: Categories Table -->
  <div>
    <div class="table-responsive">
  <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Category Name</th>
          <th>Books Linked</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $cat): ?>
          <tr>
            <td>#<?= $cat['id']; ?></td>
            <td><strong><?= h($cat['name']); ?></strong></td>
            <td>
              <span class="stock-badge" style="font-size:.82rem; font-weight:600;">
                <i class="fas fa-book"></i> <?= (int)$cat['book_count']; ?> titles
              </span>
            </td>
            <td>
              <div style="display:flex; gap:6px;">
                <a href="categories.php?edit=<?= $cat['id']; ?>" class="btn btn-outline btn-sm" title="Edit Category Name">
                  <i class="fas fa-pen"></i> Edit
                </a>
                <a href="categories.php?delete=<?= $cat['id']; ?>&csrf=<?= csrf_token(); ?>" 
                   class="btn btn-danger btn-sm" 
                   onclick="return confirm('Delete category &quot;<?= h($cat['name']); ?>&quot;?');"
                   title="Delete Category">
                  <i class="fas fa-trash"></i>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?>
          <tr><td colspan="4">No categories created yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
</div>
  </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
