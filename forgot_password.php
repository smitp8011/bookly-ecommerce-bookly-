<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/mailer.php';

// If already logged in, redirect to profile
if (is_logged_in()) {
    redirect('profile.php');
}
if (isset($_SESSION['admin_id'])) {
    redirect('admin/dashboard.php');
}

$errors = [];
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');

        $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Always show the same success message whether or not the email
        // exists — this stops someone from using this form to discover
        // which emails are registered.
        if ($user) {
            $otp = (string) random_int(100000, 999999);
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // invalidate any earlier unused OTPs for this user
            $pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0')->execute([$user['id']]);

            $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, otp_hash, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$user['id'], $otp_hash, $expires_at]);

            send_otp_email($email, $user['name'], $otp);

            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_email'] = $email;
        }

        $sent = true;
    }
}

$page_title = 'Forgot Password';
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
    <h3 style="margin:0; font-family:var(--font-display);">Reset your password</h3>
  </div>

  <?php foreach ($errors as $err): ?>
    <div class="field-error"><i class="fas fa-circle-exclamation"></i> <?= h($err); ?></div>
  <?php endforeach; ?>

  <?php if ($sent): ?>
    <p style="text-align:center; margin-bottom:18px;">
      If that email is registered, we've sent a 6-digit code to it. The code expires in 10 minutes.
    </p>
    <a href="verify_otp.php" class="btn btn-primary" style="width:100%;">Enter code</a>
    <p>Didn't get it? <a href="forgot_password.php">Try again</a></p>
  <?php else: ?>
    <form action="" method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
      <input type="email" name="email" placeholder="Your account email" required class="box">
      <button type="submit" class="btn btn-primary">Send reset code</button>
      <p><a href="login.php">Back to login</a></p>
    </form>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
