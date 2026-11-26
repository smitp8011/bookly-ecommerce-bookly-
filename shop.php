<?php
require_once 'config.php';
require_once 'includes/functions.php';

$user_id = $_SESSION['user_id'] ?? 0;
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

// ---- read filters from query string ----
$search      = trim($_GET['search'] ?? '');
$cat_ids     = isset($_GET['category']) ? (array) $_GET['category'] : [];
$cat_ids     = array_filter(array_map('intval', $cat_ids));
$max_price   = isset($_GET['max_price']) ? (int) $_GET['max_price'] : 700;
$sort        = $_GET['sort'] ?? 'newest';
$page        = max(1, (int) ($_GET['page'] ?? 1));
$per_page    = 8;

$where = ['price <= :max_price'];
$params = ['max_price' => $max_price];

if ($search !== '') {
    $where[] = '(title LIKE :search_title OR author LIKE :search_author)';
    $params['search_title']  = '%' . $search . '%';
    $params['search_author'] = '%' . $search . '%';
}
if (!empty($cat_ids)) {
    $placeholders = [];
    foreach ($cat_ids as $i => $cid) {
        $key = 'cat' . $i;
        $placeholders[] = ':' . $key;
        $params[$key] = $cid;
    }
    $where[] = 'category_id IN (' . implode(',', $placeholders) . ')';
}
$where_sql = implode(' AND ', $where);

$order_sql = match ($sort) {
    'price_low'  => 'price ASC',
    'price_high' => 'price DESC',
    'title'      => 'title ASC',
    default      => 'created_at DESC',
};

// total count for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE $where_sql");
$count_stmt->execute($params);
$total_books = (int) $count_stmt->fetchColumn();
$total_pages = max(1, (int) ceil($total_books / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$sql = "SELECT b.*, c.name AS category_name FROM books b JOIN categories c ON c.id = b.category_id
        WHERE $where_sql ORDER BY $order_sql LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue('limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$books = $stmt->fetchAll();

$is_fuzzy = false;
$suggested_term = null;
$fallback_popular = false;

// If exact search yields 0 books, automatically trigger smart typo-tolerant fuzzy matching!
if (empty($books) && $search !== '') {
    $fuzzy_res = fuzzy_search_catalog($pdo, $search, 50);
    if (!empty($fuzzy_res['matches'])) {
        $books = $fuzzy_res['matches'];
        $is_fuzzy = true;
        $suggested_term = $fuzzy_res['suggested_term'];
        $total_books = count($books);
        $total_pages = 1;
    } else {
        // Fallback: If completely unrecognized typo/gibberish, show popular reader favorites
        $pop_stmt = $pdo->query(
            'SELECT b.*, c.name AS category_name FROM books b 
             LEFT JOIN categories c ON c.id = b.category_id 
             ORDER BY b.stock DESC LIMIT 4'
        );
        $books = $pop_stmt->fetchAll();
        $fallback_popular = true;
        $total_books = count($books);
        $total_pages = 1;
    }
}

$wishlisted_ids = [];
if ($user_id) {
    $stmt2 = $pdo->prepare('SELECT book_id FROM wishlist WHERE user_id = ?');
    $stmt2->execute([$user_id]);
    $wishlisted_ids = array_column($stmt2->fetchAll(), 'book_id');
}
$purchased_ids = get_purchased_book_ids($pdo, $user_id);

function qs($overrides = []) {
    $params = array_merge($_GET, $overrides);
    unset($params['ajax']);
    return htmlspecialchars('?' . http_build_query($params));
}

function render_shop_grid_html($books, $wishlisted_ids, $purchased_ids, $total_pages, $page, $search = '', $is_fuzzy = false, $suggested_term = null, $fallback_popular = false) {
    ob_start();
    if ($is_fuzzy): ?>
      <div class="search-suggestion-notice" style="background:rgba(201,162,75,0.12); border:1.5px solid var(--gold); border-radius:10px; padding:12px 18px; margin-bottom:20px; display:flex; align-items:center; gap:12px;">
        <i class="fas fa-wand-magic-sparkles" style="color:var(--gold); font-size:1.3rem;"></i>
        <div>
          <div style="font-weight:700; color:var(--ink); font-size:.92rem;">
            No exact matches found for &ldquo;<?= h($search); ?>&rdquo;
          </div>
          <div style="font-size:.84rem; color:var(--muted); margin-top:2px;">
            Showing closest related books<?= $suggested_term ? ' — Did you mean: <a href="shop.php?search=' . urlencode($suggested_term) . '" style="color:var(--wine); font-weight:700; text-decoration:underline;">' . h($suggested_term) . '</a>?' : ''; ?>
          </div>
        </div>
      </div>
    <?php elseif ($fallback_popular): ?>
      <div class="search-suggestion-notice" style="background:var(--paper-dim); border:1px solid rgba(201,162,75,0.3); border-radius:10px; padding:12px 18px; margin-bottom:20px; display:flex; align-items:center; gap:12px;">
        <i class="fas fa-compass" style="color:var(--gold); font-size:1.3rem;"></i>
        <div>
          <div style="font-weight:700; color:var(--ink); font-size:.92rem;">
            No books found matching &ldquo;<?= h($search); ?>&rdquo;
          </div>
          <div style="font-size:.84rem; color:var(--muted); margin-top:2px;">
            Here are popular reader favorites you might enjoy exploring:
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (empty($books)): ?>
      <div class="empty-state">
        <i class="fas fa-book-open"></i>
        <h3>No books match those filters</h3>
        <p>Try widening your price range or clearing filters.</p>
      </div>
    <?php else: ?>
      <div class="book-grid">
        <?php foreach ($books as $book):
          $is_wishlisted = in_array($book['id'], $wishlisted_ids);
          $is_purchased = in_array($book['id'], $purchased_ids);
          $low_stock = $book['stock'] > 0 && $book['stock'] <= 5;
        ?>
          <div class="book-card reveal in">
            <div class="book-cover-wrap">
              <a href="book.php?id=<?= $book['id']; ?>"><img src="images/<?= h($book['cover_image']); ?>" alt="<?= h($book['title']); ?>" loading="lazy"></a>
              <button class="quickview-btn" data-book-id="<?= $book['id']; ?>" title="Quick View">
                <i class="fas fa-eye"></i> Quick View
              </button>
              <button class="wish-btn <?= $is_wishlisted ? 'active' : ''; ?>" data-book-id="<?= $book['id']; ?>"><i class="fas fa-heart"></i></button>
              <?php if ($is_purchased): ?><span class="badge-bought"><i class="fas fa-circle-check"></i> Previously Bought</span><?php endif; ?>
            </div>
            <div class="book-info">
              <div class="cat"><?= h($book['category_name']); ?></div>
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
        <?php endforeach; ?>
      </div>

      <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <a href="<?= qs(['page' => $p]); ?>" class="<?= $p === $page ? 'active' : ''; ?>"><?= $p; ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    <?php endif;
    return ob_get_clean();
}

// Handle live AJAX request
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok'          => true,
        'total_books' => $total_books,
        'count_text'  => $total_books . ' book' . ($total_books === 1 ? '' : 's') . ' found',
        'html'        => render_shop_grid_html($books, $wishlisted_ids, $purchased_ids, $total_pages, $page, $search, $is_fuzzy, $suggested_term, $fallback_popular)
    ]);
    exit;
}

$active_page = 'shop';
$page_title = 'Shop';
require_once 'includes/header.php';
?>

<section style="padding-top:38px;">
  <div class="container">
    <div class="section-head reveal in">
      <div><span class="eyebrow">Catalog</span><h2>Shop all books</h2></div>
    </div>

    <form class="search-bar reveal in" id="shop-search-form" method="get">
      <?php foreach ($cat_ids as $cid): ?><input type="hidden" name="category[]" value="<?= $cid; ?>"><?php endforeach; ?>
      <input type="hidden" name="max_price" value="<?= $max_price; ?>">
      <input type="hidden" name="sort" value="<?= h($sort); ?>">
      <input type="text" name="search" id="shop-search-input" placeholder="Search by title or author..." value="<?= h($search); ?>" autocomplete="off">
      <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-search"></i></button>
    </form>

    <div class="shop-layout">
      <aside class="filters reveal in">
        <form method="get" id="filter-form">
          <input type="hidden" name="search" id="filter-search-val" value="<?= h($search); ?>">
          <input type="hidden" name="sort" id="filter-sort-val" value="<?= h($sort); ?>">

          <h4>Genre</h4>
          <div class="filter-group">
            <?php foreach ($categories as $cat): ?>
              <label>
                <input type="checkbox" name="category[]" class="genre-checkbox" value="<?= $cat['id']; ?>" <?= in_array($cat['id'], $cat_ids) ? 'checked' : ''; ?>>
                <?= h($cat['name']); ?>
              </label>
            <?php endforeach; ?>
          </div>

          <h4>Max price: <span id="price-display"><?= price($max_price); ?></span></h4>
          <div class="filter-group">
            <input type="range" class="price-range" id="price-range-slider" name="max_price" min="200" max="700" step="25" value="<?= $max_price; ?>">
            <div style="text-align:right; font-size:.8rem; color:var(--wine); font-weight:600;" id="range-val-num">₹<?= $max_price; ?></div>
          </div>

          <a href="shop.php" class="btn btn-outline btn-sm" style="width:100%; justify-content:center;">Clear filters</a>
        </form>
      </aside>

      <div>
        <div class="sort-bar reveal in">
          <span class="stock-badge" id="books-found-count"><?= $total_books; ?> book<?= $total_books === 1 ? '' : 's'; ?> found</span>
          <form method="get" id="sort-form">
            <select name="sort" id="shop-sort-select">
              <option value="newest" <?= $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
              <option value="price_low" <?= $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
              <option value="price_high" <?= $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
              <option value="title" <?= $sort === 'title' ? 'selected' : ''; ?>>Title A–Z</option>
            </select>
          </form>
        </div>

        <div id="shop-grid-container">
          <?= render_shop_grid_html($books, $wishlisted_ids, $purchased_ids, $total_pages, $page, $search, $is_fuzzy, $suggested_term, $fallback_popular); ?>
        </div>
      </div>
    </div>
  </div>
</section>
<script>
  // 1. Page refresh/change hone se theek pehle scroll position save karo
  window.addEventListener('beforeunload', function() {
      sessionStorage.setItem('scrollPosition', window.scrollY);
  });

  // 2. Naya page load hone par check karo ki kya koi purani position save thi
  window.addEventListener('DOMContentLoaded', function() {
      const scrollPosition = sessionStorage.getItem('scrollPosition');
      
      // Agar position save thi, toh window ko wapas wahi scroll kar do
      if (scrollPosition !== null) {
          window.scrollTo({
              top: parseInt(scrollPosition),
              behavior: 'instant' // Smooth ki jagah instant taaki jump na dikhe
          });
          // Save ki hui position delete kar do taaki normal navigation kharab na ho
          sessionStorage.removeItem('scrollPosition'); 
      }
  });
</script>
<?php require_once 'includes/footer.php'; ?>
