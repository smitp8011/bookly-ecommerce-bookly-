<?php
/**
 * Admin shell header. Expects $pdo and functions.php already included,
 * and require_admin() already called by the page.
 * $active set to: dashboard | books | orders | users | messages
 */
$active = $active ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= csrf_token(); ?>">
<title><?= isset($page_title) ? h($page_title) . ' — Bookly Admin' : 'Bookly Admin'; ?></title>
<link rel="icon" type="image/svg+xml" href="../images/favicon.svg">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<script>
  (function(){
    var t = localStorage.getItem('bookly-theme');
    if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
  })();
</script>
</head>
<body>
<header class="admin-mobile-bar">
  <button type="button" id="admin-menu-toggle" class="admin-menu-btn" title="Toggle Sidebar">
    <i class="fas fa-bars"></i>
  </button>
  <div class="brand" style="display:flex; align-items:center; gap:8px; margin:0; padding:0;">
    <span class="logo-mark" style="width:22px; height:22px; display:inline-flex; color:var(--gold);">
      <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="2" y="3" width="7" height="30" rx="2" fill="currentColor" opacity="0.3"/>
        <path d="M9 4.5H20C24.6944 4.5 28.5 8.30558 28.5 13C28.5 16.2731 26.6508 19.1136 23.9554 20.4854C27.5147 21.8485 30 25.2635 30 29.25C30 30.7688 28.7688 32 27.25 32H9V4.5Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
        <path d="M9 18.25H19.5C22.3995 18.25 24.75 15.8995 24.75 13C24.75 10.1005 22.3995 7.75 19.5 7.75H9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
        <path d="M9 32H20.5C23.5376 32 26 29.5376 26 26.5C26 23.4624 23.5376 21 20.5 21H9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
        <path d="M19.5 21C21 23.5 20.5 27 18 29.5C16.8 30.7 15.5 32 15.5 32" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      </svg>
    </span>
    <span style="font-size:1.1rem; font-weight:700; color:var(--gold-soft);">Bookly Admin</span>
  </div>
  <a href="../home.php" target="_blank" style="color:var(--gold-soft); font-size:1.05rem;" title="Storefront"><i class="fas fa-store"></i></a>
</header>
<div class="admin-sidebar-backdrop" id="admin-sidebar-backdrop"></div>
<div class="admin-shell">
  <aside class="admin-sidebar" id="admin-sidebar">
    <div class="brand" style="display:flex; align-items:center; gap:8px;">
      <span class="logo-mark" style="width:24px; height:24px; display:inline-flex; color:var(--gold);">
        <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="2" y="3" width="7" height="30" rx="2" fill="currentColor" opacity="0.3"/>
          <path d="M9 4.5H20C24.6944 4.5 28.5 8.30558 28.5 13C28.5 16.2731 26.6508 19.1136 23.9554 20.4854C27.5147 21.8485 30 25.2635 30 29.25C30 30.7688 28.7688 32 27.25 32H9V4.5Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
          <path d="M9 18.25H19.5C22.3995 18.25 24.75 15.8995 24.75 13C24.75 10.1005 22.3995 7.75 19.5 7.75H9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M9 32H20.5C23.5376 32 26 29.5376 26 26.5C26 23.4624 23.5376 21 20.5 21H9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M19.5 21C21 23.5 20.5 27 18 29.5C16.8 30.7 15.5 32 15.5 32" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
      </span>
      <span>Bookly Admin</span>
    </div>
    <a href="dashboard.php" class="<?= $active === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-gauge"></i> Dashboard</a>
    <a href="books.php" class="<?= $active === 'books' ? 'active' : ''; ?>"><i class="fas fa-book"></i> Books</a>
    <a href="categories.php" class="<?= $active === 'categories' ? 'active' : ''; ?>"><i class="fas fa-tags"></i> Categories</a>
    <a href="orders.php" class="<?= $active === 'orders' ? 'active' : ''; ?>"><i class="fas fa-box"></i> Orders</a>
    <a href="coupons.php" class="<?= $active === 'coupons' ? 'active' : ''; ?>"><i class="fas fa-ticket"></i> Coupons</a>
    <a href="reviews.php" class="<?= $active === 'reviews' ? 'active' : ''; ?>"><i class="fas fa-star"></i> Reviews</a>
    <a href="reports.php" class="<?= $active === 'reports' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reports</a>
    <a href="users.php" class="<?= $active === 'users' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Users</a>
    <a href="messages.php" class="<?= $active === 'messages' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Messages</a>
    <a href="../home.php" target="_blank" title="Preview live customer storefront in new tab"><i class="fas fa-arrow-up-right-from-square"></i> Storefront</a>
    <div style="padding:14px 24px; display:flex; align-items:center; justify-content:space-between; border-top:1px solid rgba(255,255,255,.08); margin-top:10px;">
      <span style="font-size:.82rem; color:var(--paper-dim);">Dark Mode</span>
      <button type="button" class="theme-toggle" title="Toggle dark mode">
        <i class="fas fa-moon"></i>
      </button>
    </div>
    <a href="logout.php"><i class="fas fa-arrow-right-from-bracket"></i> Logout</a>
  </aside>
  <main class="admin-main">
    <?php
    $flash_success = flash('success');
    $flash_error = flash('error');
    if ($flash_success): ?>
      <div class="message" data-msg="<?= h($flash_success); ?>" data-type="success" style="display:none;"></div>
    <?php endif;
    if ($flash_error): ?>
      <div class="message" data-msg="<?= h($flash_error); ?>" data-type="error" style="display:none;"></div>
    <?php endif; ?>
