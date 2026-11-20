<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/mailer.php';

if (empty($_SESSION['reset_user_id']) || empty($_SESSION['reset_email'])) {
    redirect('forgot_password.php');
}

$user_id = $_SESSION['reset_user_id'];
$email = $_SESSION['reset_email'];
$errors = [];

// Resend a fresh code
if (isset($_POST['resend'])) {
    if (csrf_check($_POST['csrf'] ?? '')) {
        $stmt = $pdo->prepare('SELECT id, name FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if ($user) {
            $otp = (string) random_int(100000, 999999);
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0')->execute([$user_id]);
            $pdo->prepare('INSERT INTO password_resets (user_id, otp_hash, expires_at) VALUES (?, ?, ?)')->execute([$user_id, $otp_hash, $expires_at]);
            send_otp_email($email, $user['name'], $otp);
            flash('success', 'A new code has been sent.');
        }
    }
    redirect('verify_otp.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $otp = trim($_POST['otp'] ?? '');
        $pass = $_POST['password'] ?? '';
        $cpass = $_POST['cpassword'] ?? '';

        if ($otp === '' || $pass === '') {
            $errors[] = 'Enter the code and a new password.';
        } elseif (strlen($pass) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        } elseif ($pass !== $cpass) {
            $errors[] = 'Passwords do not match.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT * FROM password_resets WHERE user_id = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute([$user_id]);
            $reset_row = $stmt->fetch();

            if (!$reset_row || !password_verify($otp, $reset_row['otp_hash'])) {
                $errors[] = 'Incorrect or expired code. Request a new one if needed.';
            } else {
                $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                    ->execute([password_hash($pass, PASSWORD_DEFAULT), $user_id]);
                $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')->execute([$reset_row['id']]);

                unset($_SESSION['reset_user_id'], $_SESSION['reset_email']);
                flash('success', 'Password updated — please log in.');
                redirect('login.php');
            }
        }
    }
}

$page_title = 'Verify Code';
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
    <h3 style="margin:0; font-family:var(--font-display);">Enter your verification code</h3>
  </div>
  <p style="text-align:center; color:#6b6259; font-size:.86rem; margin-bottom:20px;">
    We sent a 6-digit code to <strong><?= h($email); ?></strong>.
  </p>

  <?php foreach ($errors as $err): ?>
    <div class="field-error"><i class="fas fa-circle-exclamation"></i> <?= h($err); ?></div>
  <?php endforeach; ?>

  <form action="" method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
    <input type="text" name="otp" placeholder="6-digit code" required class="box" maxlength="6" pattern="\d{6}" inputmode="numeric">
    <input type="password" name="password" placeholder="New password (min 6 characters)" required class="box">
    <input type="password" name="cpassword" placeholder="Confirm new password" required class="box">
    <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
  </form>

  <form action="" method="post" style="margin-top:6px;">
    <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
    <button type="submit" name="resend" class="btn btn-outline" style="width:100%;">Resend code</button>
  </form>
</div>

<?php require_once 'includes/footer.php'; ?>
