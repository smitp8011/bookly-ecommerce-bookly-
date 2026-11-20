<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare(
    'SELECT b.* FROM wishlist w JOIN books b ON b.id = w.book_id WHERE w.user_id = ? ORDER BY w.added_at DESC'
);
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

$active_page = 'wishlist';
$page_title = 'Wishlist';
require_once 'includes/header.php';
?>

<section>
  <div class="container">
    <div class="section-head reveal in"><div><span class="eyebrow">Saved</span><h2>Your wishlist</h2></div></div>

    <?php if (empty($items)): ?>
      <div class="empty-state">
        <i class="fas fa-heart"></i>
        <h3>Nothing saved yet</h3>
        <p>Tap the heart icon on any book to save it here.</p>
        <a href="shop.php" class="btn btn-primary" style="margin-top:16px;">Browse Books</a>
      </div>
    <?php else: ?>
      <div class="book-grid">
        <?php foreach ($items as $book): ?>
          <div class="book-card reveal in">
            <div class="book-cover-wrap">
              <a href="book.php?id=<?= $book['id']; ?>"><img src="images/<?= h($book['cover_image']); ?>" alt="<?= h($book['title']); ?>" loading="lazy"></a>
              <button class="wish-btn active" data-book-id="<?= $book['id']; ?>"><i class="fas fa-heart"></i></button>
            </div>
            <div class="book-info">
              <h3><a href="book.php?id=<?= $book['id']; ?>"><?= h($book['title']); ?></a></h3>
              <div class="author">by <?= h($book['author']); ?></div>
              <div class="row"><span class="price-tag"><?= price($book['price']); ?></span></div>
              <button class="add-cart-btn" data-book-id="<?= $book['id']; ?>" data-cover="images/<?= h($book['cover_image']); ?>" <?= $book['stock'] == 0 ? 'disabled' : ''; ?>>
                <i class="fas fa-cart-plus"></i> Add to cart
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Page ke saare heart buttons ko select karo
    const wishButtons = document.querySelectorAll('.wish-btn');

    wishButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Jiss heart par click hua, uske parent '.book-card' ko dhundo
            const bookCard = this.closest('.book-card');
            
            if (bookCard) {
                // 1. Smooth effect ke liye opacity 0 karo (Optional but looks premium)
                bookCard.style.transition = '0.3s ease-out';
                bookCard.style.opacity = '0';
                bookCard.style.transform = 'scale(0.9)';
                
                // 2. 300 milliseconds baad card ko puri tarah HTML se delete kar do
                setTimeout(() => {
                    bookCard.remove();
                    
                    // 3. (Bonus) Agar yeh aakhri book thi aur ab grid khali ho gayi, 
                    // toh page ko refresh kar do taaki "Nothing saved yet" wala design aa jaye.
                    const remainingBooks = document.querySelectorAll('.book-card').length;
                    if (remainingBooks === 0) {
                        window.location.reload();
                    }
                }, 300); // 300ms match with CSS transition time
            }
        });
    });
});
</script>
<?php require_once 'includes/footer.php';
// Clicking the wishlist heart here removes the card via JS event; a light
// enhancement is left as a follow-up (page reload also reflects it).
?>
