<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_admin();

$book_id = (int) ($_GET['id'] ?? 0);
$book = ['title' => '', 'author' => '', 'category_id' => '', 'price' => '', 'stock' => '', 'description' => '', 'cover_image' => ''];
$is_edit = false;

if ($book_id) {
    $stmt = $pdo->prepare('SELECT * FROM books WHERE id = ?');
    $stmt->execute([$book_id]);
    $found = $stmt->fetch();
    if ($found) { $book = $found; $is_edit = true; }
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $author      = trim($_POST['author'] ?? '');
        $category_id = (int) ($_POST['category_id'] ?? 0);
        $price       = (float) ($_POST['price'] ?? 0);
        $stock       = (int) ($_POST['stock'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $cover_image = $book['cover_image'];

        if ($title === '' || $author === '' || $category_id <= 0 || $price <= 0) {
            $errors[] = 'Fill in title, author, category, and a valid price.';
        }

        // Handle cover upload if a new file was chosen
        if (!empty($_FILES['cover']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $errors[] = 'Cover must be a jpg, png, or webp image.';
            } elseif ($_FILES['cover']['size'] > 3 * 1024 * 1024) {
                $errors[] = 'Cover image must be under 3MB.';
            } else {
                $new_name = 'book_' . bin2hex(random_bytes(6)) . '.' . $ext;
                if (move_uploaded_file($_FILES['cover']['tmp_name'], '../images/' . $new_name)) {
                    $cover_image = $new_name;
                } else {
                    $errors[] = 'Could not save the uploaded image.';
                }
            }
        } elseif (!$is_edit) {
            $errors[] = 'Please choose a cover image.';
        }

        if (empty($errors)) {
            if ($is_edit) {
                $stmt = $pdo->prepare(
                    'UPDATE books SET title=?, author=?, category_id=?, price=?, stock=?, description=?, cover_image=? WHERE id=?'
                );
                $stmt->execute([$title, $author, $category_id, $price, $stock, $description, $cover_image, $book_id]);
                flash('success', 'Book updated.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO books (title, author, category_id, price, stock, description, cover_image) VALUES (?,?,?,?,?,?,?)'
                );
                $stmt->execute([$title, $author, $category_id, $price, $stock, $description, $cover_image]);
                flash('success', 'Book added.');
            }
            redirect('books.php');
        }
    }
}

$active = 'books';
$page_title = $is_edit ? 'Edit Book' : 'Add Book';
require_once 'includes/admin_header.php';
?>

<div class="section-head">
  <div><span class="eyebrow">Catalog</span><h2><?= $is_edit ? 'Edit book' : 'Add a new book'; ?></h2></div>
  <a href="books.php" class="btn btn-outline btn-sm">Back to list</a>
</div>

<?php foreach ($errors as $err): ?>
  <div class="field-error"><i class="fas fa-circle-exclamation"></i> <?= h($err); ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="admin-form" style="max-width:560px; background:#fffdf8; padding:26px; border-radius:12px; box-shadow:var(--shadow-sm);">
  <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">

  <label>Title</label>
  <input type="text" name="title" required value="<?= h($book['title']); ?>">

  <label>Author</label>
  <input type="text" name="author" required value="<?= h($book['author']); ?>">

  <label>Category</label>
  <select name="category_id" required>
    <option value="">Select a category</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?= $cat['id']; ?>" <?= $book['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?= h($cat['name']); ?></option>
    <?php endforeach; ?>
  </select>

  <label>Price (₹)</label>
  <input type="number" step="0.01" name="price" required value="<?= h($book['price']); ?>">

  <label>Stock quantity</label>
  <input type="number" name="stock" required value="<?= h($book['stock']); ?>">

  <label>Description</label>
  <textarea name="description" rows="4"><?= h($book['description']); ?></textarea>

  <label>Cover image <?= $is_edit ? '(leave blank to keep current)' : ''; ?></label>
  <?php if ($is_edit && $book['cover_image']): ?>
    <img src="../images/<?= h($book['cover_image']); ?>" style="width:70px; border-radius:6px; margin-bottom:10px;">
  <?php endif; ?>
  <input type="file" name="cover" accept="image/*">

  <button type="submit" class="btn btn-primary" style="width:100%; margin-top:8px;"><?= $is_edit ? 'Save changes' : 'Add book'; ?></button>
</form>

<?php require_once 'includes/admin_footer.php'; ?>
