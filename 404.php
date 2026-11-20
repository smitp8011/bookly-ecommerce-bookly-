<?php
require_once 'config.php';
require_once 'includes/functions.php';

http_response_code(404);

$active_page = '';
$page_title = 'Page Not Found (404)';
require_once 'includes/header.php';
?>

<section style="min-height:70vh; display:flex; align-items:center; justify-content:center; text-align:center; padding:60px 20px;">
  <div class="container" style="max-width:580px;">
    <div class="reveal in">
      <div style="width:90px; height:90px; margin:0 auto 20px; border-radius:50%; background:rgba(110,31,42,.08); display:flex; align-items:center; justify-content:center;">
        <i class="fas fa-book-open-reader" style="font-size:2.8rem; color:var(--wine);"></i>
      </div>

      <span class="eyebrow" style="color:var(--gold); font-size:.9rem; letter-spacing:.1em;">Error 404</span>
      <h1 style="font-size:2.2rem; margin:8px 0 14px; line-height:1.2;">Lost in the Library Archives</h1>
      <p style="color:#6b6259; font-size:.98rem; line-height:1.6; margin-bottom:28px;">
        The page or manuscript you are searching for might have been moved, renamed, or never existed on our shelves.
      </p>

      <!-- Direct Catalog Search -->
      <form action="shop.php" method="get" class="search-bar" style="margin:0 auto 28px; max-width:440px; box-shadow:var(--shadow-sm);">
        <input type="text" name="search" placeholder="Search our catalog of titles..." autofocus>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
      </form>

      <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
        <a href="shop.php" class="btn btn-primary"><i class="fas fa-book"></i> Browse Catalog</a>
        <a href="home.php" class="btn btn-outline"><i class="fas fa-house"></i> Return Home</a>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
