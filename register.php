<?php
require_once 'config.php';
require_once 'includes/functions.php';

// If already logged in, redirect away from register page
if (is_logged_in()) {
    redirect('home.php');
}
if (isset($_SESSION['admin_id'])) {
    redirect('admin/dashboard.php');
}

$errors = [];

$redirect_url = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '');
$allowed_redirects = ['checkout.php', 'cart.php', 'orders.php', 'wishlist.php', 'profile.php'];
$target = in_array($redirect_url, $allowed_redirects, true) ? $redirect_url : 'home.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $pass    = $_POST['password'] ?? '';
        $cpass   = $_POST['cpassword'] ?? '';

        if ($name === '' || $email === '' || $pass === '') {
            $errors[] = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } elseif (strlen($pass) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        } elseif ($pass !== $cpass) {
            $errors[] = 'Passwords do not match.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "user")');
                $stmt->execute([$name, $email, $hash]);
                $new_id = (int) $pdo->lastInsertId();

                // Auto-login upon registration
                session_regenerate_id(true);
                $_SESSION['user_id']   = $new_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['role']      = 'user';
                
                // Sync any guest cart items
                sync_guest_cart_to_db($pdo, $new_id);

                flash('success', 'Account created successfully! Welcome to BookNest.');
                redirect($target);
            }
        }
    }
}

$page_title = 'Register';
require_once 'includes/header.php';
?>

<div class="form-container reveal in">
  <div style="text-align:center; margin-bottom:18px;">
    <div style="width:52px; height:52px; border-radius:14px; background:rgba(110,31,42,.08); display:inline-flex; align-items:center; justify-content:center; color:var(--wine); margin-bottom:8px;">
      <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:30px; height:30px;">
        <rect x="2" y="3" width="7" height="30" rx="2" fill="currentColor" opacity="0.3"/>
        <path d="M9 4.5H20C24.6944 4.5 28.5 8.30558 28.5 13C28.5 16.2731 26.6508 19.1136 23.9554 20.4854C27.5147 21.8485 30 25.2635 30 29.25C30 30.7688 28.7688 32 27.25 32H9V4.5Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
        <path d="M9 18.25H19.5C22.3995 18.25 24.75 15.8995 24.75 13C24.75 10.1005 22.3995 7.75 19.5 7.75H9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
        <path d="M9 32H20.5C23.5376 32 26 29.5376 26 26.5C26 23.4624 23.5376 21 20.5 21H9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
        <path d="M19.5 21C21 23.5 20.5 27 18 29.5C16.8 30.7 15.5 32 15.5 32" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      </svg>
    </div>
    <h3 style="margin:0; font-family:var(--font-display);">Create an account</h3>
  </div>

  <?php foreach ($errors as $err): ?>
    <div class="field-error"><i class="fas fa-circle-exclamation"></i> <?= h($err); ?></div>
  <?php endforeach; ?>

  <form action="" method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
    <?php if ($redirect_url): ?><input type="hidden" name="redirect" value="<?= h($redirect_url); ?>"><?php endif; ?>
    <input type="text" name="name" placeholder="Full name" required class="box" value="<?= h($_POST['name'] ?? ''); ?>" autofocus>
    <input type="email" name="email" placeholder="Email address" required class="box" value="<?= h($_POST['email'] ?? ''); ?>">
    
    <div class="password-wrapper">
      <input type="password" name="password" id="reg-password" placeholder="Password (min 6 characters)" required class="box">
      <button type="button" class="password-toggle" data-target="reg-password" title="Show password" tabindex="-1">
        <i class="fas fa-eye"></i>
      </button>
    </div>

    <div class="password-wrapper">
      <input type="password" name="cpassword" id="reg-cpassword" placeholder="Confirm password" required class="box">
      <button type="button" class="password-toggle" data-target="reg-cpassword" title="Show password" tabindex="-1">
        <i class="fas fa-eye"></i>
      </button>
    </div>
    
    <div id="password-match-msg" class="password-match-indicator"></div>

    <button type="submit" class="btn btn-primary">Create account</button>
    <p>Already have an account? <a href="login.php<?= $redirect_url ? '?redirect=' . urlencode($redirect_url) : ''; ?>">Log in</a></p>
  </form>
</div>

<?php require_once 'includes/footer.php'; ?>
