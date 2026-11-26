<?php
require_once 'config.php';
require_once 'includes/functions.php';

$book_id = (int) ($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? 0;

$stmt = $pdo->prepare('SELECT b.*, c.name AS category_name FROM books b JOIN categories c ON c.id = b.category_id WHERE b.id = ?');
$stmt->execute([$book_id]);
$book = $stmt->fetch();
if (!$book) {
    http_response_code(404);
    require_once 'includes/functions.php';
    die('Book not found.');
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!is_logged_in()) {
        flash('error', 'Please log in to leave a review.');
        redirect('login.php');
    }
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash('error', 'Session expired, please try again.');
    } else {
        $purchased_book_ids = get_purchased_book_ids($pdo, $user_id);
        if (!in_array($book_id, $purchased_book_ids)) {
            flash('error', 'Only verified buyers who purchased this book from Bookly can leave a review.');
            redirect('book.php?id=' . $book_id);
        }

        $rating  = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($rating < 1 || $rating > 5 || $comment === '') {
            flash('error', 'Give a star rating and write a short comment.');
        } else {
            $review_image = null;
            if (!empty($_FILES['review_image']['name']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['review_image']['tmp_name'];
                $ext = strtolower(pathinfo($_FILES['review_image']['name'], PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($ext, $allowed_exts, true) && filesize($tmp_name) <= 5 * 1024 * 1024) {
                    $new_filename = 'rev_' . $book_id . '_' . $user_id . '_' . time() . '.' . $ext;
                    $dest = __DIR__ . '/images/reviews/' . $new_filename;
                    if (move_uploaded_file($tmp_name, $dest)) {
                        $review_image = $new_filename;
                    }
                }
            }

            if ($review_image) {
                $stmt = $pdo->prepare(
                    'INSERT INTO reviews (book_id, user_id, rating, comment, review_image) VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), review_image = VALUES(review_image)'
                );
                $stmt->execute([$book_id, $user_id, $rating, $comment, $review_image]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO reviews (book_id, user_id, rating, comment) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)'
                );
                $stmt->execute([$book_id, $user_id, $rating, $comment]);
            }
            flash('success', 'Thanks for your review!');
        }
    }
    redirect('book.php?id=' . $book_id);
}

$reviews_stmt = $pdo->prepare(
    'SELECT r.*, u.name AS user_name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.book_id = ? ORDER BY r.created_at DESC'
);
$reviews_stmt->execute([$book_id]);
$reviews = $reviews_stmt->fetchAll();
$avg_rating = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;

$star_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $rev) {
    $r = (int)$rev['rating'];
    if (isset($star_counts[$r])) $star_counts[$r]++;
}
$total_reviews_count = count($reviews);

// Verified buyers check (any confirmed non-cancelled order)
$verified_stmt = $pdo->prepare(
    "SELECT DISTINCT o.user_id FROM orders o
     JOIN order_items oi ON oi.order_id = o.id
     WHERE oi.book_id = ? AND o.status != 'cancelled'"
);
$verified_stmt->execute([$book_id]);
$verified_buyer_ids = array_column($verified_stmt->fetchAll(), 'user_id');

$is_wishlisted = false;
$user_shelf_status = null;
if ($user_id) {
    $stmt = $pdo->prepare('SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?');
    $stmt->execute([$user_id, $book_id]);
    $is_wishlisted = (bool) $stmt->fetch();

    $bs_stmt = $pdo->prepare('SELECT status FROM user_bookshelf WHERE user_id = ? AND book_id = ?');
    $bs_stmt->execute([$user_id, $book_id]);
    $user_shelf_status = $bs_stmt->fetchColumn();
}

// Collaborative filtering recommendations ("Customers also bought")
$also_bought_stmt = $pdo->prepare(
    "SELECT b.*, COUNT(oi2.id) AS co_orders
     FROM order_items oi1
     JOIN order_items oi2 ON oi1.order_id = oi2.order_id AND oi2.book_id != oi1.book_id
     JOIN books b ON b.id = oi2.book_id
     JOIN orders o ON o.id = oi1.order_id AND o.status != 'cancelled'
     WHERE oi1.book_id = ?
     GROUP BY oi2.book_id
     ORDER BY co_orders DESC, b.created_at DESC
     LIMIT 4"
);
$also_bought_stmt->execute([$book_id]);
$also_bought = $also_bought_stmt->fetchAll();

if (count($also_bought) < 4) {
    $exclude_ids = array_merge([$book_id], array_column($also_bought, 'id'));
    $placeholders = implode(',', array_fill(0, count($exclude_ids), '?'));
    $fallback_stmt = $pdo->prepare("SELECT * FROM books WHERE category_id = ? AND id NOT IN ($placeholders) LIMIT " . (4 - count($also_bought)));
    $fallback_stmt->execute(array_merge([$book['category_id']], $exclude_ids));
    $related = array_merge($also_bought, $fallback_stmt->fetchAll());
} else {
    $related = $also_bought;
}

// Recent sales count (social proof)
$recent_sales_stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE oi.book_id = ? AND o.placed_on >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND o.status != 'cancelled'"
);
$recent_sales_stmt->execute([$book_id]);
$recent_sales_count = (int) $recent_sales_stmt->fetchColumn();

$is_purchased = in_array($book_id, get_purchased_book_ids($pdo, $user_id));

track_recently_viewed($pdo, $user_id, $book_id);
$recently_viewed = [];
if ($user_id) {
    $rv_stmt = $pdo->prepare(
        'SELECT b.* FROM recently_viewed rv JOIN books b ON b.id = rv.book_id
         WHERE rv.user_id = ? AND rv.book_id != ? ORDER BY rv.viewed_at DESC LIMIT 6'
    );
    $rv_stmt->execute([$user_id, $book_id]);
    $recently_viewed = $rv_stmt->fetchAll();
}

$page_title = $book['title'];
require_once 'includes/header.php';
?>

<section>
  <div class="container">
    <div class="book-detail reveal in">
      <div class="cover">
        <img src="images/<?= h($book['cover_image']); ?>" alt="<?= h($book['title']); ?> cover">
      </div>
      <div>
        <span class="eyebrow"><?= h($book['category_name']); ?></span>
        <h1><?= h($book['title']); ?></h1>
        <div class="author">by <?= h($book['author']); ?></div>

        <div class="meta-row">
          <span class="stars">
            <?php $r = round($avg_rating); for ($i = 1; $i <= 5; $i++) echo '<i class="fas fa-star" style="' . ($i <= $r ? '' : 'color:var(--paper-dim);') . '"></i>'; ?>
          </span>
          <span class="stock-badge">(<?= count($reviews); ?> review<?= count($reviews) === 1 ? '' : 's'; ?>)</span>
          <span class="stock-badge <?= $book['stock'] <= 5 && $book['stock'] > 0 ? 'low' : ''; ?>">
            <?= $book['stock'] > 0 ? $book['stock'] . ' in stock' : 'Out of stock'; ?>
          </span>
          <?php if ($is_purchased): ?>
            <span class="stock-badge" style="color:var(--sage); font-weight:700;"><i class="fas fa-circle-check"></i> Previously Bought</span>
          <?php endif; ?>
        </div>

        <?php if ($recent_sales_count > 0): ?>
          <div class="social-proof-badge">
            <i class="fas fa-fire-flame-curved"></i> <strong><?= $recent_sales_count; ?> readers</strong> ordered this in the last 30 days
          </div>
        <?php endif; ?>

        <?php if ($book['stock'] > 0 && $book['stock'] <= 5): ?>
          <div class="urgency-alert">
            <i class="fas fa-bolt"></i> Hurry! Only <strong><?= $book['stock']; ?> copies</strong> remaining in stock.
          </div>
        <?php endif; ?>

        <div class="price-tag" style="font-size:1.8rem; margin-bottom:18px; display:block;"><?= price($book['price']); ?></div>

        <p class="desc"><?= nl2br(h($book['description'])); ?></p>

        <div class="qty-row" style="flex-wrap:wrap; gap:12px; align-items:center;">
          <button class="add-cart-btn" style="width:auto; padding:13px 28px;" data-book-id="<?= $book['id']; ?>" data-cover="images/<?= h($book['cover_image']); ?>" <?= $book['stock'] == 0 ? 'disabled' : ''; ?>>
            <i class="fas fa-cart-plus"></i> <?= $book['stock'] == 0 ? 'Unavailable' : 'Add to cart'; ?>
          </button>
          <button type="button" class="btn btn-gold btn-sample-reader" data-book-id="<?= $book['id']; ?>" style="padding:13px 22px;">
            <i class="fas fa-book-open"></i> Read Free Sample
          </button>

          <!-- Bookshelf Selector Dropdown -->
          <div class="dropdown-shelf" style="position:relative; display:inline-block;">
            <button type="button" class="btn btn-outline" id="btn-shelf-toggle" style="padding:13px 18px; display:flex; align-items:center; gap:8px;">
              <i class="fas fa-bookmark" style="color:var(--gold);"></i> 
              <span id="shelf-status-label"><?= $user_shelf_status === 'completed' ? 'Finished ✅' : ($user_shelf_status === 'reading' ? 'Reading 📖' : ($user_shelf_status === 'want_to_read' ? 'Want to Read 🔖' : 'Add to Shelf')); ?></span>
              <i class="fas fa-chevron-down" style="font-size:.7rem;"></i>
            </button>
            <div id="shelf-dropdown-menu" style="display:none; position:absolute; top:calc(100% + 6px); left:0; min-width:200px; background:#fff; border:1px solid var(--paper-dim); border-radius:10px; box-shadow:var(--shadow); z-index:100; overflow:hidden;">
              <button type="button" class="shelf-opt-btn" onclick="updateCurrentBookShelf('want_to_read')" style="width:100%; text-align:left; padding:11px 14px; background:none; border:none; cursor:pointer; font-size:.85rem; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; gap:8px;">
                <span>🔖</span> Want to Read
              </button>
              <button type="button" class="shelf-opt-btn" onclick="updateCurrentBookShelf('reading')" style="width:100%; text-align:left; padding:11px 14px; background:none; border:none; cursor:pointer; font-size:.85rem; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; gap:8px;">
                <span>📖</span> Currently Reading
              </button>
              <button type="button" class="shelf-opt-btn" onclick="updateCurrentBookShelf('completed')" style="width:100%; text-align:left; padding:11px 14px; background:none; border:none; cursor:pointer; font-size:.85rem; display:flex; align-items:center; gap:8px;">
                <span>✅</span> Finished
              </button>
            </div>
          </div>

          <button class="wish-btn <?= $is_wishlisted ? 'active' : ''; ?>" style="position:static; width:48px; height:48px;" data-book-id="<?= $book['id']; ?>" title="Wishlist">
            <i class="fas fa-heart"></i>
          </button>
        </div>

        <div class="trust-badges-grid">
          <div class="trust-item"><i class="fas fa-truck-fast"></i> <span>Free delivery on orders ₹499+</span></div>
          <div class="trust-item"><i class="fas fa-rotate-left"></i> <span>7-Day easy replacement</span></div>
          <div class="trust-item"><i class="fas fa-shield-check"></i> <span>100% genuine paperback</span></div>
        </div>
      </div>
    </div>

    <!-- Frequently Bought Together Bundle Box -->
    <?php if (!empty($related)): 
      $companion = $related[0];
      $bundle_orig = (float)$book['price'] + (float)$companion['price'];
      $bundle_savings = round($bundle_orig * 0.12);
      $bundle_final = $bundle_orig - $bundle_savings;
      $bundle_json = htmlspecialchars(json_encode([(int)$book['id'], (int)$companion['id']]), ENT_QUOTES, 'UTF-8');
    ?>
      <div class="bundle-box reveal in">
        <div class="bundle-header">
          <h3><i class="fas fa-layer-group" style="color:var(--gold);"></i> Frequently Bought Together</h3>
          <span class="bundle-save-badge"><i class="fas fa-tag"></i> Special Bundle: Save 12% (<?= price($bundle_savings); ?> OFF)</span>
        </div>
        <div class="bundle-grid">
          <!-- Book 1 (Current) -->
          <div class="bundle-item">
            <img src="images/<?= h($book['cover_image']); ?>" alt="<?= h($book['title']); ?>" class="bundle-item-cover">
            <div class="bundle-item-info">
              <span style="font-size:.7rem; text-transform:uppercase; color:#888; font-weight:700; display:block;">This Item</span>
              <h4><?= h($book['title']); ?></h4>
              <div class="author">by <?= h($book['author']); ?></div>
              <div class="price"><?= price($book['price']); ?></div>
            </div>
          </div>

          <div class="bundle-plus">+</div>

          <!-- Book 2 (Companion) -->
          <div class="bundle-item">
            <a href="book.php?id=<?= $companion['id']; ?>">
              <img src="images/<?= h($companion['cover_image']); ?>" alt="<?= h($companion['title']); ?>" class="bundle-item-cover">
            </a>
            <div class="bundle-item-info">
              <span style="font-size:.7rem; text-transform:uppercase; color:var(--wine); font-weight:700; display:block;">Recommended Companion</span>
              <h4><a href="book.php?id=<?= $companion['id']; ?>"><?= h($companion['title']); ?></a></h4>
              <div class="author">by <?= h($companion['author']); ?></div>
              <div class="price"><?= price($companion['price']); ?></div>
            </div>
          </div>

          <!-- Price & Action -->
          <div class="bundle-pricing-action">
            <div class="bundle-price-row">
              <span class="bundle-final-price"><?= price($bundle_final); ?></span>
              <span class="bundle-orig-price"><?= price($bundle_orig); ?></span>
            </div>
            <div class="bundle-savings-text">
              <i class="fas fa-circle-check"></i> Instant 12% Combo Savings applied!
            </div>
            <button type="button" class="btn btn-primary btn-add-bundle" data-book-ids="<?= $bundle_json; ?>" style="padding:11px 20px; font-size:.92rem;">
              <i class="fas fa-cart-arrow-down"></i> Add Both to Cart
            </button>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php 
    $photo_reviews = array_filter($reviews, fn($r) => !empty($r['review_image']) && file_exists(__DIR__ . '/images/reviews/' . $r['review_image']));
    ?>

    <div style="max-width:70ch; margin-top:60px;" class="reveal">
      <h2 style="margin-bottom:20px;">Reader Reviews & Ratings</h2>

      <?php if ($total_reviews_count > 0): ?>
        <div class="rating-breakdown-box">
          <div class="rating-score-large">
            <div class="score-num"><?= number_format($avg_rating, 1); ?></div>
            <div class="stars" style="font-size:.95rem; margin:4px 0;">
              <?php for ($i = 1; $i <= 5; $i++) echo '<i class="fas fa-star" style="' . ($i <= round($avg_rating) ? 'color:var(--gold);' : 'color:var(--paper-dim);') . '"></i>'; ?>
            </div>
            <div style="font-size:.78rem; color:#6b6259;"><?= $total_reviews_count; ?> total rating<?= $total_reviews_count === 1 ? '' : 's'; ?></div>
          </div>
          <div>
            <?php for ($s = 5; $s >= 1; $s--): 
              $pct = $total_reviews_count > 0 ? round(($star_counts[$s] / $total_reviews_count) * 100) : 0;
            ?>
              <div class="rating-bar-row">
                <span style="width:30px; font-weight:600;"><?= $s; ?> ★</span>
                <div class="bar-track">
                  <div class="bar-fill" style="width:<?= $pct; ?>%;"></div>
                </div>
                <span style="width:38px; text-align:right; color:#888;"><?= $pct; ?>%</span>
              </div>
            <?php endfor; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($photo_reviews)): ?>
        <div class="review-photos-strip-wrap">
          <h4><i class="fas fa-camera-retro" style="color:var(--gold);"></i> Reader Photos (<?= count($photo_reviews); ?>)</h4>
          <div class="review-photos-grid">
            <?php foreach ($photo_reviews as $pr): ?>
              <img src="images/reviews/<?= h($pr['review_image']); ?>" alt="Reader Photo" class="review-photo-thumb photo-lightbox-trigger" data-full="images/reviews/<?= h($pr['review_image']); ?>" data-caption="Review photo by <?= h($pr['user_name']); ?> · <?= $pr['rating']; ?>/5 Stars">
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!is_logged_in()): ?>
        <div style="margin-bottom:30px; background:var(--paper-dim); padding:16px 20px; border-radius:10px; display:flex; align-items:center; justify-content:space-between; gap:12px;">
          <div>
            <span style="font-weight:600; font-size:.9rem; color:var(--ink); display:block;">Have you read this book?</span>
            <span style="font-size:.82rem; color:var(--muted);">Log in with your Bookly account to share a verified reader review.</span>
          </div>
          <a href="login.php" class="btn btn-outline btn-sm">Log in to Review</a>
        </div>
      <?php elseif (!$is_purchased): ?>
        <div style="margin-bottom:30px; background:var(--paper-dim); padding:18px 22px; border-radius:12px; border:1px dashed rgba(201,162,75,.4); display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
          <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:38px; height:38px; border-radius:50%; background:rgba(201,162,75,.15); color:var(--gold); display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0;">
              <i class="fas fa-shield-halved"></i>
            </div>
            <div>
              <h5 style="margin:0 0 2px 0; font-size:.92rem; color:var(--ink);">Verified Purchases Only</h5>
              <p style="margin:0; font-size:.82rem; color:var(--muted);">To prevent fake reviews, only verified readers who purchased this book from Bookly can write a review.</p>
            </div>
          </div>
          <button class="btn btn-primary btn-sm add-cart-btn" data-book-id="<?= $book['id']; ?>" data-cover="images/<?= h($book['cover_image']); ?>" <?= $book['stock'] == 0 ? 'disabled' : ''; ?>>
            <i class="fas fa-cart-plus"></i> Buy to Review
          </button>
        </div>
      <?php else: ?>
        <form method="post" enctype="multipart/form-data" id="review-submission-form" style="margin-bottom:36px; background:var(--paper-dim); padding:22px; border-radius:12px; border:1px solid rgba(201,162,75,.25); position:relative;">
          <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
            <h4 style="margin:0; font-size:1.05rem;"><i class="fas fa-pen-nib" style="color:var(--gold);"></i> Write a Review &amp; Add Photo</h4>
            <span class="badge-verified" style="font-size:.76rem; padding:3px 10px;"><i class="fas fa-circle-check"></i> Verified Buyer</span>
          </div>
          <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
          
          <!-- Clearly Visible & Interactive Star Rating Selector Card -->
          <div class="star-rating-selector-card" style="margin-bottom:16px;">
            <label style="font-size:.88rem; font-weight:700; color:var(--ink); margin:0;">
              Your Rating <span style="color:var(--danger);">*</span>:
            </label>
            <div class="rating-input" id="review-stars-wrap">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fas fa-star" data-val="<?= $i; ?>" title="Rate <?= $i; ?> out of 5 stars"></i>
              <?php endfor; ?>
            </div>
            <span id="rating-text" style="font-size:.84rem; font-weight:600; color:var(--gold); min-width:140px;">
              (Click to give star rating)
            </span>
          </div>
          <input type="hidden" name="rating" id="rating-value" value="0" required>
          <textarea class="box" name="comment" rows="3" placeholder="Share your honest reading experience..." required></textarea>
          
          <div style="margin-bottom:16px;">
            <label style="font-size:.82rem; font-weight:600; display:block; margin-bottom:6px;"><i class="fas fa-camera"></i> Upload Photo of Your Book (Optional):</label>
            <input type="file" name="review_image" accept="image/jpeg,image/png,image/webp" style="font-size:.82rem;">
          </div>

          <button type="submit" name="submit_review" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i> Submit Verified Review</button>
        </form>
      <?php endif; ?>

      <?php if (empty($reviews)): ?>
        <p style="color:#6b6259;">No reviews yet — be the first to share your thoughts.</p>
      <?php endif; ?>

      <?php foreach ($reviews as $rev): 
        $is_verified = in_array($rev['user_id'], $verified_buyer_ids);
      ?>
        <div class="review">
          <div style="display:flex; align-items:center;">
            <span class="rname"><?= h($rev['user_name']); ?></span>
            <?php if ($is_verified): ?>
              <span class="badge-verified"><i class="fas fa-circle-check"></i> Verified Buyer</span>
            <?php endif; ?>
          </div>
          <div class="stars"><?php for ($i = 1; $i <= 5; $i++) echo '<i class="fas fa-star" style="' . ($i <= $rev['rating'] ? '' : 'color:var(--paper-dim);') . '"></i>'; ?></div>
          <p><?= nl2br(h($rev['comment'])); ?></p>
          <?php if (!empty($rev['review_image']) && file_exists(__DIR__ . '/images/reviews/' . $rev['review_image'])): ?>
            <img src="images/reviews/<?= h($rev['review_image']); ?>" alt="Review photo" class="review-item-photo photo-lightbox-trigger" data-full="images/reviews/<?= h($rev['review_image']); ?>" data-caption="Review photo by <?= h($rev['user_name']); ?>">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($related): ?>
      <div style="margin-top:60px;">
        <div class="section-head reveal">
          <div>
            <span class="eyebrow"><?= !empty($also_bought) ? 'Recommendations' : 'More like this'; ?></span>
            <h2><?= !empty($also_bought) ? 'Customers also bought' : 'You might also like'; ?></h2>
          </div>
        </div>
        <div class="book-grid">
          <?php foreach ($related as $rb): ?>
            <div class="book-card reveal">
              <div class="book-cover-wrap">
                <a href="book.php?id=<?= $rb['id']; ?>"><img src="images/<?= h($rb['cover_image']); ?>" alt="<?= h($rb['title']); ?>" loading="lazy"></a>
              </div>
              <div class="book-info">
                <h3><a href="book.php?id=<?= $rb['id']; ?>"><?= h($rb['title']); ?></a></h3>
                <div class="author">by <?= h($rb['author']); ?></div>
                <div class="row"><span class="price-tag"><?= price($rb['price']); ?></span></div>
                <button class="add-cart-btn" data-book-id="<?= $rb['id']; ?>" data-cover="images/<?= h($rb['cover_image']); ?>" <?= $rb['stock'] == 0 ? 'disabled' : ''; ?>>
                  <i class="fas fa-cart-plus"></i> Add to cart
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($recently_viewed): ?>
      <div style="margin-top:60px;">
        <div class="section-head reveal"><div><span class="eyebrow">Your history</span><h2>Recently viewed</h2></div></div>
        <div class="recent-strip">
          <?php foreach ($recently_viewed as $rb): ?>
            <div class="book-card reveal in">
              <div class="book-cover-wrap">
                <a href="book.php?id=<?= $rb['id']; ?>"><img src="images/<?= h($rb['cover_image']); ?>" alt="<?= h($rb['title']); ?>" loading="lazy"></a>
              </div>
              <div class="book-info">
                <h3 style="font-size:.92rem;"><a href="book.php?id=<?= $rb['id']; ?>"><?= h($rb['title']); ?></a></h3>
                <span class="price-tag" style="font-size:.9rem;"><?= price($rb['price']); ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.btn-add-bundle').forEach(btn => {
    btn.addEventListener('click', async () => {
      let bookIds = [];
      try {
        bookIds = JSON.parse(btn.dataset.bookIds || '[]');
      } catch(e){}

      if (!bookIds.length) return;

      const origHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Bundle...';

      try {
        const formData = new FormData();
        formData.append('action', 'add_bundle');
        formData.append('book_ids', JSON.stringify(bookIds));

        const res = await fetch('ajax/cart.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();

        if (data.ok) {
          btn.innerHTML = '<i class="fas fa-check"></i> Added Both to Cart!';
          btn.style.background = '#15803d';
          btn.style.borderColor = '#15803d';
          btn.style.color = '#fff';

          // Update header cart badge
          document.querySelectorAll('.cart-count-badge, .badge, .icons a[href="cart.php"] span').forEach(b => {
            b.textContent = data.cart_count;
          });

          // Show floating toast alert if available
          if (typeof showToast === 'function') {
            showToast('success', '📚 Both companion books have been added to your cart with 12% combo savings!');
          }

          setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = origHtml;
            btn.style.background = '';
            btn.style.borderColor = '';
            btn.style.color = '';
          }, 3000);
        } else {
          alert(data.message || 'Could not add bundle.');
          btn.disabled = false;
          btn.innerHTML = origHtml;
        }
      } catch(err) {
        alert('Network error while adding bundle.');
        btn.disabled = false;
        btn.innerHTML = origHtml;
      }
    });
  });

  // Bookshelf Dropdown Menu Logic
  const shelfToggleBtn = document.getElementById('btn-shelf-toggle');
  const shelfMenu = document.getElementById('shelf-dropdown-menu');

  if (shelfToggleBtn && shelfMenu) {
    shelfToggleBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      shelfMenu.style.display = (shelfMenu.style.display === 'block') ? 'none' : 'block';
    });

    document.addEventListener('click', () => {
      shelfMenu.style.display = 'none';
    });
  }
});

async function updateCurrentBookShelf(status) {
  const currentBookId = <?= (int)$book['id']; ?>;
  const fd = new FormData();
  fd.append('action', 'set_status');
  fd.append('book_id', currentBookId);
  fd.append('status', status);

  try {
    const res = await fetch('ajax/bookshelf.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.ok) {
      const labels = {
        'want_to_read': 'Want to Read 🔖',
        'reading': 'Reading 📖',
        'completed': 'Finished ✅'
      };
      const lbl = document.getElementById('shelf-status-label');
      if (lbl) lbl.textContent = labels[status] || 'Added to Shelf';

      if (typeof showToast === 'function') {
        showToast('success', data.message);
      } else {
        alert(data.message);
      }
    } else {
      if (data.message.includes('log in')) {
        location.href = 'login.php';
      } else {
        alert(data.message || 'Error updating bookshelf.');
      }
    }
  } catch(e) {
    alert('Network error.');
  }

  const menu = document.getElementById('shelf-dropdown-menu');
  if (menu) menu.style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>
