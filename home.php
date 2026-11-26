<?php
require_once 'config.php';
require_once 'includes/functions.php';

$user_id = $_SESSION['user_id'] ?? 0;

$featured = $pdo->query(
    'SELECT b.*, c.name AS category_name FROM books b JOIN categories c ON c.id = b.category_id ORDER BY b.created_at DESC LIMIT 8'
)->fetchAll();

$top_rated_stmt = $pdo->query(
    'SELECT b.*, AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
     FROM books b LEFT JOIN reviews r ON r.book_id = b.id
     GROUP BY b.id ORDER BY avg_rating DESC LIMIT 4'
);
$top_rated = $top_rated_stmt->fetchAll();

$categories = $pdo->query('SELECT c.*, COUNT(b.id) AS book_count FROM categories c LEFT JOIN books b ON b.category_id = c.id GROUP BY c.id')->fetchAll();

$wishlisted_ids = [];
if ($user_id) {
    $stmt = $pdo->prepare('SELECT book_id FROM wishlist WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $wishlisted_ids = array_column($stmt->fetchAll(), 'book_id');
}
$purchased_ids = get_purchased_book_ids($pdo, $user_id);

function render_book_card($book, $wishlisted_ids, $purchased_ids = []) {
    $is_wishlisted = in_array($book['id'], $wishlisted_ids);
    $is_purchased = in_array($book['id'], $purchased_ids);
    $low_stock = $book['stock'] > 0 && $book['stock'] <= 5;
    ?>
    <div class="book-card reveal">
      <div class="book-cover-wrap">
        <a href="book.php?id=<?= $book['id']; ?>">
          <img src="images/<?= h($book['cover_image']); ?>" alt="<?= h($book['title']); ?> cover" loading="lazy">
        </a>
        <?php if (strtotime($book['created_at']) > strtotime('-30 days')): ?><span class="book-tag">New</span><?php endif; ?>
        <button class="quickview-btn" data-book-id="<?= $book['id']; ?>" title="Quick View">
          <i class="fas fa-eye"></i> Quick View
        </button>
        <button class="wish-btn <?= $is_wishlisted ? 'active' : ''; ?>" data-book-id="<?= $book['id']; ?>" title="Toggle wishlist">
          <i class="fas fa-heart"></i>
        </button>
        <?php if ($is_purchased): ?><span class="badge-bought"><i class="fas fa-circle-check"></i> Previously Bought</span><?php endif; ?>
      </div>
      <div class="book-info">
        <div class="cat"><?= h($book['category_name'] ?? ''); ?></div>
        <h3><a href="book.php?id=<?= $book['id']; ?>"><?= h($book['title']); ?></a></h3>
        <div class="author">by <?= h($book['author']); ?></div>
        <div class="row">
          <span class="price-tag"><?= price($book['price']); ?></span>
          <span class="stock-badge <?= $low_stock ? 'low' : ''; ?>">
            <?= $book['stock'] > 0 ? ($low_stock ? 'Only ' . $book['stock'] . ' left' : 'In stock') : 'Out of stock'; ?>
          </span>
        </div>
        <button class="add-cart-btn" data-book-id="<?= $book['id']; ?>" data-cover="images/<?= h($book['cover_image']); ?>" <?= $book['stock'] == 0 ? 'disabled' : ''; ?>>
          <i class="fas fa-cart-plus"></i> <?= $book['stock'] == 0 ? 'Unavailable' : 'Add to cart'; ?>
        </button>
      </div>
    </div>
    <?php
}

$active_page = 'home';
$page_title = 'Home';
require_once 'includes/header.php';
?>

<section class="hero">
  <div class="container">
    <div>
      <span class="eyebrow" style="color:var(--gold-soft);">Independent Bookshop</span>
      <h1>Stories worth <em>losing track of time</em> for.</h1>
      <p>Curated fiction, thrillers, and non-fiction hand-picked by readers, not algorithms. New arrivals every week.</p>
      <div class="hero-actions">
        <a href="shop.php" class="btn btn-gold">Browse the Shop</a>
        <a href="about.php" class="btn btn-outline" style="border-color:var(--paper-dim); color:var(--paper);">Our Story</a>
      </div>
    </div>
    <div class="hero-stack" aria-hidden="true">
      <img class="card-cover c1" src="images/red_queen.jpg" alt="">
      <img class="card-cover c2" src="images/nightshade.jpg" alt="">
      <img class="card-cover c3" src="images/the_girl_of_ink_and_stars.jpg" alt="">
    </div>
  </div>
</section>
<?php if (false): // Yahan se disable shuru ?>
<!-- <div class="container">
  <div class="stats-strip">
    <div><div class="counter" data-target="<?= count($pdo->query('SELECT id FROM books')->fetchAll()); ?>">0</div><div class="label">Titles in stock</div></div>
    <div><div class="counter" data-target="6">0</div><div class="label">Curated genres</div></div>
    <div><div class="counter" data-target="<?= max(120, count($pdo->query('SELECT id FROM orders')->fetchAll())); ?>">0</div><div class="label">Orders delivered</div></div>
    <div><div class="counter" data-target="98">0</div><div class="label">% happy readers</div></div>
  </div>
</div> -->
<?php endif; // Yahan par disable khatam ?>
<?php
// 1. DATA FETCHING
// fetchColumn() seedha total count ka number return karta hai
$booksCount = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$ordersCount = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();

// Order wali minimum 120 ki condition yahan lagayein
$displayOrdersCount = max(120, $ordersCount);
?>

<!-- 2. DISPLAY (HTML Logic jismein sirf variables print kiye hain) -->
<div class="container">
  <div class="stats-strip">
    <div>
      <div class="counter" data-target="<?= $booksCount; ?>">0</div>
      <div class="label">Titles in stock</div>
    </div>
    
    <div>
      <div class="counter" data-target="6">0</div>
      <div class="label">Curated categories</div>
    </div>
    
    <div>
      <div class="counter" data-target="<?= $displayOrdersCount; ?>">0</div>
      <div class="label">Orders delivered</div>
    </div>
    
    <div>
      <div class="counter" data-target="98">0</div>
      <div class="label">% happy readers</div>
    </div>
  </div>
</div>

<section>
  <div class="container">
    <div class="section-head reveal">
      <div><span class="eyebrow">Explore</span><h2>Shop by category</h2></div>
    </div>
    <div class="book-grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));">
      <?php foreach ($categories as $cat): ?>
        <a href="shop.php?category=<?= $cat['id']; ?>" class="book-card reveal" style="padding:26px 18px; text-align:center;">
          <i class="fas fa-book" style="font-size:1.6rem; color:var(--wine); margin-bottom:10px;"></i>
          <h3 style="font-size:.95rem;"><?= h($cat['name']); ?></h3>
          <span class="stock-badge"><?= (int) $cat['book_count']; ?> titles</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-alt">
  <div class="container">
    <div class="section-head reveal">
      <div><span class="eyebrow">Just In</span><h2>New arrivals</h2></div>
      <a href="shop.php" class="see-all">See all <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="book-grid">
      <?php foreach ($featured as $book) render_book_card($book, $wishlisted_ids, $purchased_ids); ?>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="section-head reveal">
      <div><span class="eyebrow">Reader favorites</span><h2>Top rated books</h2></div>
    </div>
    <div class="book-grid">
      <?php foreach ($top_rated as $book): ?>
        <div class="book-card reveal">
          <div class="book-cover-wrap">
            <a href="book.php?id=<?= $book['id']; ?>"><img src="images/<?= h($book['cover_image']); ?>" alt="<?= h($book['title']); ?>" loading="lazy"></a>
            <button class="wish-btn <?= in_array($book['id'], $wishlisted_ids) ? 'active' : ''; ?>" data-book-id="<?= $book['id']; ?>"><i class="fas fa-heart"></i></button>
            <?php if (in_array($book['id'], $purchased_ids)): ?><span class="badge-bought"><i class="fas fa-circle-check"></i> Previously Bought</span><?php endif; ?>
          </div>
          <div class="book-info">
            <h3><a href="book.php?id=<?= $book['id']; ?>"><?= h($book['title']); ?></a></h3>
            <div class="author">by <?= h($book['author']); ?></div>
            <div class="row">
              <span class="price-tag"><?= price($book['price']); ?></span>
              <span class="stars"><?php
                $avg = round($book['avg_rating'] ?? 0);
                for ($i = 1; $i <= 5; $i++) echo '<i class="fas fa-star" style="' . ($i <= $avg ? '' : 'color:var(--paper-dim);') . '"></i>';
              ?></span>
            </div>
            <button class="add-cart-btn" data-book-id="<?= $book['id']; ?>" data-cover="images/<?= h($book['cover_image']); ?>" <?= $book['stock'] == 0 ? 'disabled' : ''; ?>>
              <i class="fas fa-cart-plus"></i> Add to cart
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
