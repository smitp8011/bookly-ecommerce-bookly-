<?php
/**
 * Shared site header. Expects $pdo (config.php) and functions.php
 * to already be included by the calling page, and $active_page set
 * to highlight the current nav link (e.g. 'home', 'shop').
 */
$active_page = $active_page ?? '';
$cart_items_count = cart_count($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? h($page_title) . ' — BookNest' : 'BookNest — Book Ordering and Delivery System'; ?></title>
<link rel="icon" type="image/svg+xml" href="images/favicon.svg">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '../css/style.css' : 'css/style.css'; ?>">
<script>
  // Apply saved theme before paint so there's no flash of the wrong theme.
  (function(){
    var t = localStorage.getItem('booknest-theme') || localStorage.getItem('bookly-theme');
    if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
  })();
</script>
</head>
<body>

<header class="site-header">
  <div class="container flex">
    <a href="home.php" class="logo">
      <span class="logo-mark">
        <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="2" y="3" width="7" height="30" rx="2" fill="currentColor" opacity="0.3"/>
          <path d="M9 4.5H20C24.6944 4.5 28.5 8.30558 28.5 13C28.5 16.2731 26.6508 19.1136 23.9554 20.4854C27.5147 21.8485 30 25.2635 30 29.25C30 30.7688 28.7688 32 27.25 32H9V4.5Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
          <path d="M9 18.25H19.5C22.3995 18.25 24.75 15.8995 24.75 13C24.75 10.1005 22.3995 7.75 19.5 7.75H9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M9 32H20.5C23.5376 32 26 29.5376 26 26.5C26 23.4624 23.5376 21 20.5 21H9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M19.5 21C21 23.5 20.5 27 18 29.5C16.8 30.7 15.5 32 15.5 32" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
      </span>
      <span class="logo-text">BOOKNEST</span>
    </a>

    <nav class="navbar">
      <a href="home.php" class="<?= $active_page === 'home' ? 'active' : ''; ?>">Home</a>
      <a href="shop.php" class="<?= $active_page === 'shop' ? 'active' : ''; ?>">Shop</a>
      <a href="about.php" class="<?= $active_page === 'about' ? 'active' : ''; ?>">About</a>
      <a href="contact.php" class="<?= $active_page === 'contact' ? 'active' : ''; ?>">Contact</a>
      <?php if (is_logged_in()): ?>
        <a href="orders.php" class="<?= $active_page === 'orders' ? 'active' : ''; ?>">My Orders</a>
      <?php endif; ?>
    </nav>

    <div class="header-search-container">
      <form action="shop.php" method="get" class="header-search-box" style="margin:0;">
        <button type="submit" style="background:none; border:none; color:var(--gold-soft); cursor:pointer; padding:0; display:flex; align-items:center;" title="Search">
          <i class="fas fa-magnifying-glass"></i>
        </button>
        <input type="text" name="search" id="header-search-input" placeholder="Search titles, authors..." autocomplete="off">
      </form>
      <div id="header-search-dropdown" class="search-results-dropdown"></div>
    </div>

    <div class="icons">
      <div id="menu-btn"><i class="fas fa-bars"></i></div>
      <button type="button" id="theme-toggle" class="theme-toggle" title="Toggle dark mode">
        <i class="fas fa-moon"></i>
      </button>
      <a href="cart.php" class="cart-icon-target" title="View Cart">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-badge"><?= (int) $cart_items_count; ?></span>
      </a>
      <?php if (is_logged_in()): ?>
        <a href="wishlist.php" title="Wishlist"><i class="fas fa-heart"></i></a>
        <div class="user-chip">
          <a href="profile.php" class="avatar" title="My Profile (<?= h($_SESSION['user_name']); ?>)"><?= h(mb_strtoupper(mb_substr($_SESSION['user_name'], 0, 1))); ?></a>
          <a href="logout.php" title="Logout"><i class="fas fa-arrow-right-from-bracket"></i></a>
        </div>
      <?php else: ?>
        <?php if (is_admin()): ?>
          <a href="admin/dashboard.php" class="btn btn-outline btn-sm" style="padding:4px 10px; font-size:.76rem; border-color:var(--gold); color:var(--gold-soft);" title="Admin Dashboard">
            <i class="fas fa-shield-halved"></i> Admin Panel
          </a>
        <?php endif; ?>
        <a href="login.php" class="btn btn-primary btn-sm" style="padding:6px 14px; font-size:.82rem;"><i class="fas fa-user"></i> Login</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php
$flash_success = flash('success');
$flash_error = flash('error');
if ($flash_success): ?>
  <div class="message" data-msg="<?= h($flash_success); ?>" data-type="success" style="display:none;"></div>
<?php endif;
if ($flash_error): ?>
  <div class="message" data-msg="<?= h($flash_error); ?>" data-type="error" style="display:none;"></div>
<?php endif; ?>
