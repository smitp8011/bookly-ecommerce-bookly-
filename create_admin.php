<?php
/**
 * Run this ONCE after importing schema.sql to create your first admin
 * account, then DELETE this file. Visit it in the browser, e.g.
 * http://localhost/booknest/create_admin.php
 */
require_once 'config.php';

$done = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || strlen($pass) < 6) {
        $error = 'Fill every field; password needs 6+ characters.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'That email is already registered.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "admin")');
            $stmt->execute([$name, $email, $hash]);
            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Create Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="css/style.css"></head>
<body>
<div class="form-container">
<?php if ($done): ?>
  <h3>Admin account created ✅</h3>
  <p style="text-align:center;">You can now log in at <a href="login.php">login.php</a>.<br><strong>Delete create_admin.php now.</strong></p>
<?php else: ?>
  <h3>Create the first admin account</h3>
  <?php if ($error): ?><div class="field-error"><?= htmlspecialchars($error); ?></div><?php endif; ?>
  <form method="post">
    <input class="box" type="text" name="name" placeholder="Admin name" required>
    <input class="box" type="email" name="email" placeholder="Admin email" required>
    <input class="box" type="password" name="password" placeholder="Password (min 6 chars)" required>
    <button class="btn btn-primary" type="submit">Create admin</button>
  </form>
<?php endif; ?>
</div>
</body></html>
