<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];

// Check if phone/address columns exist in users table
$has_extra_cols = true;
try {
    $pdo->query('SELECT phone, address FROM users LIMIT 1');
} catch (Exception $e) {
    $has_extra_cols = false;
    try {
        $pdo->exec('ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email, ADD COLUMN address VARCHAR(500) DEFAULT NULL AFTER phone');
        $has_extra_cols = true;
    } catch (Exception $ignored) {}
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('logout.php');
}

$errors = [];
$active_tab = $_GET['tab'] ?? 'profile';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $active_tab = 'profile';
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($name === '' || $email === '') {
            $errors[] = 'Name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } else {
            $check_email = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $check_email->execute([$email, $user_id]);
            if ($check_email->fetch()) {
                $errors[] = 'That email address is already in use by another account.';
            } else {
                if ($has_extra_cols) {
                    $up_stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?');
                    $up_stmt->execute([$name, $email, $phone, $address, $user_id]);
                } else {
                    $up_stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
                    $up_stmt->execute([$name, $email, $user_id]);
                }
                
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;
                flash('success', 'Profile updated successfully!');
                redirect('profile.php?tab=profile');
            }
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $active_tab = 'password';
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass     = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if ($current_pass === '' || $new_pass === '') {
            $errors[] = 'Please fill in all password fields.';
        } elseif (!password_verify($current_pass, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new_pass) < 6) {
            $errors[] = 'New password must be at least 6 characters long.';
        } elseif ($new_pass !== $confirm_pass) {
            $errors[] = 'New passwords do not match.';
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $up_pass = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $up_pass->execute([$new_hash, $user_id]);

            flash('success', 'Password changed successfully!');
            redirect('profile.php?tab=password');
        }
    }
}

// Handle Add Address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $active_tab = 'addresses';
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $addr_type = in_array($_POST['address_type'] ?? '', ['home', 'office', 'other'], true) ? $_POST['address_type'] : 'home';
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $line      = trim($_POST['address_line'] ?? '');
        $city      = trim($_POST['city'] ?? '');
        $state     = trim($_POST['state'] ?? '');
        $pincode   = trim($_POST['pincode'] ?? '');
        $is_def    = isset($_POST['is_default']) ? 1 : 0;

        if ($full_name === '' || $phone === '' || $line === '' || $city === '' || $pincode === '') {
            $errors[] = 'Please fill in all required address fields.';
        } else {
            if ($is_def) {
                $pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = ?')->execute([$user_id]);
            }
            $ins = $pdo->prepare('INSERT INTO user_addresses (user_id, address_type, full_name, phone, address_line, city, state, pincode, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $ins->execute([$user_id, $addr_type, $full_name, $phone, $line, $city, $state, $pincode, $is_def]);
            
            flash('success', 'New address saved successfully!');
            redirect('profile.php?tab=addresses');
        }
    }
}

// Handle Delete Address
if (isset($_POST['delete_address'])) {
    $active_tab = 'addresses';
    if (csrf_check($_POST['csrf'] ?? '')) {
        $del_id = (int)$_POST['address_id'];
        $del = $pdo->prepare('DELETE FROM user_addresses WHERE id = ? AND user_id = ?');
        $del->execute([$del_id, $user_id]);
        flash('success', 'Address removed from your address book.');
    }
    redirect('profile.php?tab=addresses');
}

// Handle Set Default Address
if (isset($_POST['set_default_address'])) {
    $active_tab = 'addresses';
    if (csrf_check($_POST['csrf'] ?? '')) {
        $def_id = (int)$_POST['address_id'];
        $pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = ?')->execute([$user_id]);
        $pdo->prepare('UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?')->execute([$def_id, $user_id]);
        flash('success', 'Default address updated!');
    }
    redirect('profile.php?tab=addresses');
}

// Fetch saved addresses
$addr_stmt = $pdo->prepare('SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC');
$addr_stmt->execute([$user_id]);
$saved_addresses = $addr_stmt->fetchAll();

// Fetch 2026 Reading Challenge & Digital Bookshelf
$chal_stmt = $pdo->prepare('SELECT * FROM user_reading_challenge WHERE user_id = ? AND year = 2026');
$chal_stmt->execute([$user_id]);
$reading_challenge = $chal_stmt->fetch() ?: ['target_books' => 12, 'year' => 2026];

$shelf_stmt = $pdo->prepare('
    SELECT ub.*, b.title, b.author, b.cover_image, b.price 
    FROM user_bookshelf ub 
    JOIN books b ON b.id = ub.book_id 
    WHERE ub.user_id = ? 
    ORDER BY ub.updated_at DESC
');
$shelf_stmt->execute([$user_id]);
$bookshelf_items = $shelf_stmt->fetchAll();

$completed_books = array_values(array_filter($bookshelf_items, fn($b) => $b['status'] === 'completed'));
$reading_books   = array_values(array_filter($bookshelf_items, fn($b) => $b['status'] === 'reading'));
$want_books      = array_values(array_filter($bookshelf_items, fn($b) => $b['status'] === 'want_to_read'));

$completed_count = count($completed_books);
$target_count    = max(1, (int)$reading_challenge['target_books']);
$challenge_pct   = min(100, round(($completed_count / $target_count) * 100));

// Stats for user overview
$order_count_stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
$order_count_stmt->execute([$user_id]);
$total_orders = (int) $order_count_stmt->fetchColumn();

$wishlist_count_stmt = $pdo->prepare('SELECT COUNT(*) FROM wishlist WHERE user_id = ?');
$wishlist_count_stmt->execute([$user_id]);
$total_wishlist = (int) $wishlist_count_stmt->fetchColumn();

$page_title = 'My Profile';
$active_page = 'profile';
require_once 'includes/header.php';
?>

<section style="padding-top:38px;">
  <div class="container">
    <div class="section-head reveal in">
      <div>
        <span class="eyebrow">Account Settings</span>
        <h2>My Profile & Dashboard</h2>
      </div>
    </div>

    <?php foreach ($errors as $err): ?>
      <div class="field-error reveal in" style="margin-bottom:18px;"><i class="fas fa-circle-exclamation"></i> <?= h($err); ?></div>
    <?php endforeach; ?>

    <div class="profile-grid">
      <!-- Left sidebar card -->
      <aside class="profile-card profile-header-card reveal in">
        <div class="profile-avatar-lg">
          <?= h(mb_strtoupper(mb_substr($user['name'], 0, 1))); ?>
        </div>
        <h3 class="profile-name"><?= h($user['name']); ?></h3>
        <div class="profile-email"><?= h($user['email']); ?></div>
        <span class="profile-badge <?= $user['role'] === 'admin' ? 'admin' : ''; ?>">
          <i class="fas <?= $user['role'] === 'admin' ? 'fa-shield-halved' : 'fa-user'; ?>"></i> <?= ucfirst($user['role']); ?>
        </span>

        <div class="profile-stats">
          <div class="profile-stat-box">
            <div class="profile-stat-val"><?= $total_orders; ?></div>
            <div class="profile-stat-lbl">Orders</div>
          </div>
          <div class="profile-stat-box">
            <div class="profile-stat-val"><?= $completed_count; ?></div>
            <div class="profile-stat-lbl">Books Read</div>
          </div>
        </div>

        <nav class="profile-nav">
          <button type="button" class="profile-tab-btn <?= $active_tab === 'profile' ? 'active' : ''; ?>" data-tab="tab-profile">
            <i class="fas fa-id-card"></i> Personal Details
          </button>
          <button type="button" class="profile-tab-btn <?= $active_tab === 'reading' ? 'active' : ''; ?>" data-tab="tab-reading">
            <i class="fas fa-bullseye"></i> Reading Challenge (<?= $challenge_pct; ?>%)
          </button>
          <button type="button" class="profile-tab-btn <?= $active_tab === 'addresses' ? 'active' : ''; ?>" data-tab="tab-addresses">
            <i class="fas fa-map-location-dot"></i> Saved Addresses (<?= count($saved_addresses); ?>)
          </button>
          <button type="button" class="profile-tab-btn <?= $active_tab === 'password' ? 'active' : ''; ?>" data-tab="tab-password">
            <i class="fas fa-key"></i> Change Password
          </button>
          <a href="orders.php">
            <i class="fas fa-box"></i> Order History
          </a>
          <a href="wishlist.php">
            <i class="fas fa-heart"></i> My Wishlist
          </a>
          <?php if (is_admin()): ?>
            <a href="admin/dashboard.php" style="color:var(--wine); font-weight:700;">
              <i class="fas fa-gauge"></i> Admin Dashboard
            </a>
          <?php endif; ?>
          <a href="logout.php" style="color:var(--danger);">
            <i class="fas fa-arrow-right-from-bracket"></i> Logout
          </a>
        </nav>
      </aside>

      <!-- Right content card -->
      <main class="profile-card reveal in">
        <!-- Tab 1: Personal Details -->
        <div id="tab-profile" class="tab-content <?= $active_tab === 'profile' ? 'active' : ''; ?>">
          <h3 style="margin-bottom:6px;">Personal Information</h3>
          <p style="color:#6b6259; font-size:.88rem; margin-bottom:24px;">Update your basic account profile credentials.</p>

          <form action="" method="post" class="admin-form">
            <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
            
            <label for="prof-name">Full Name</label>
            <input type="text" id="prof-name" name="name" required value="<?= h($user['name']); ?>" class="box">

            <label for="prof-email">Email Address</label>
            <input type="email" id="prof-email" name="email" required value="<?= h($user['email']); ?>" class="box">

            <label for="prof-phone">Default Phone Number</label>
            <input type="tel" id="prof-phone" name="phone" placeholder="e.g. 9876543210" value="<?= h($user['phone'] ?? ''); ?>" class="box">

            <button type="submit" name="update_profile" class="btn btn-primary" style="margin-top:10px;">
              <i class="fas fa-floppy-disk"></i> Save Changes
            </button>
          </form>
        </div>

        <!-- Tab 2: 2026 Reading Challenge & Digital Bookshelf -->
        <div id="tab-reading" class="tab-content <?= $active_tab === 'reading' ? 'active' : ''; ?>">
          <!-- Challenge Hero Card -->
          <div class="challenge-hero-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
              <div>
                <span class="eyebrow"><i class="fas fa-trophy"></i> Annual Reading Challenge</span>
                <h2 style="font-size:1.6rem; margin:4px 0; color:#fff;">2026 Reading Goal</h2>
                <p style="font-size:.9rem; opacity:.9; margin:0;">
                  You have completed <strong><?= $completed_count; ?></strong> of your <strong><?= $target_count; ?></strong> books goal!
                </p>
              </div>
              <button type="button" onclick="promptSetGoal(<?= $target_count; ?>)" class="btn btn-gold btn-sm" style="box-shadow:none;">
                <i class="fas fa-pen-to-square"></i> Change Goal
              </button>
            </div>

            <div class="challenge-bar-track">
              <div class="challenge-bar-fill" style="width:<?= $challenge_pct; ?>%;"></div>
            </div>

            <div style="display:flex; justify-content:space-between; font-size:.84rem; opacity:.85;">
              <span><?= $challenge_pct; ?>% Completed</span>
              <span><?= max(0, $target_count - $completed_count); ?> books to go</span>
            </div>
          </div>

          <!-- Bookshelf Tabs Nav -->
          <div class="bookshelf-tabs-nav">
            <button type="button" class="bookshelf-nav-btn active" onclick="showShelf('reading', this)">
              📖 Currently Reading (<?= count($reading_books); ?>)
            </button>
            <button type="button" class="bookshelf-nav-btn" onclick="showShelf('completed', this)">
              ✅ Finished Books (<?= count($completed_books); ?>)
            </button>
            <button type="button" class="bookshelf-nav-btn" onclick="showShelf('want', this)">
              🔖 Want to Read (<?= count($want_books); ?>)
            </button>
          </div>

          <!-- Shelf 1: Currently Reading -->
          <div id="shelf-reading" class="shelf-panel">
            <?php if (empty($reading_books)): ?>
              <div style="text-align:center; padding:35px 20px; background:#faf5eb; border-radius:12px; color:#777;">
                <i class="fas fa-book-open-reader" style="font-size:2rem; color:var(--gold); margin-bottom:10px;"></i>
                <p style="margin:0;">No books currently being read. Add one from the catalog!</p>
              </div>
            <?php else: ?>
              <div class="bookshelf-grid">
                <?php foreach ($reading_books as $item): ?>
                  <div class="bookshelf-card" id="shelf-card-<?= $item['book_id']; ?>">
                    <a href="book.php?id=<?= $item['book_id']; ?>">
                      <img src="images/<?= h($item['cover_image']); ?>" alt="<?= h($item['title']); ?>" class="bookshelf-card-cover">
                    </a>
                    <h4 style="font-size:.92rem; margin:0; line-height:1.3;">
                      <a href="book.php?id=<?= $item['book_id']; ?>"><?= h($item['title']); ?></a>
                    </h4>
                    <div style="font-size:.78rem; color:#777;">by <?= h($item['author']); ?></div>
                    
                    <div class="bookshelf-progress-row">
                      <input type="range" min="0" max="100" value="<?= $item['progress_pct']; ?>" style="flex-grow:1; accent-color:var(--wine);" 
                             onchange="updateReadingProgress(<?= $item['book_id']; ?>, this.value, 'reading')">
                      <span id="prog-num-<?= $item['book_id']; ?>"><?= $item['progress_pct']; ?>%</span>
                    </div>

                    <div style="display:flex; gap:6px; margin-top:auto;">
                      <button type="button" class="btn btn-outline btn-sm" style="flex-grow:1; font-size:.74rem; padding:4px 8px;"
                              onclick="setShelfStatus(<?= $item['book_id']; ?>, 'completed')">
                        <i class="fas fa-check"></i> Mark Done
                      </button>
                      <button type="button" class="btn btn-danger btn-sm" style="font-size:.74rem; padding:4px 8px;"
                              onclick="removeShelfItem(<?= $item['book_id']; ?>)">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Shelf 2: Finished Books -->
          <div id="shelf-completed" class="shelf-panel" style="display:none;">
            <?php if (empty($completed_books)): ?>
              <div style="text-align:center; padding:35px 20px; background:#faf5eb; border-radius:12px; color:#777;">
                <i class="fas fa-trophy" style="font-size:2rem; color:var(--gold); margin-bottom:10px;"></i>
                <p style="margin:0;">No completed books yet in 2026. Start reading today!</p>
              </div>
            <?php else: ?>
              <div class="bookshelf-grid">
                <?php foreach ($completed_books as $item): ?>
                  <div class="bookshelf-card" id="shelf-card-<?= $item['book_id']; ?>">
                    <a href="book.php?id=<?= $item['book_id']; ?>">
                      <img src="images/<?= h($item['cover_image']); ?>" alt="<?= h($item['title']); ?>" class="bookshelf-card-cover">
                    </a>
                    <h4 style="font-size:.92rem; margin:0; line-height:1.3;">
                      <a href="book.php?id=<?= $item['book_id']; ?>"><?= h($item['title']); ?></a>
                    </h4>
                    <div style="font-size:.78rem; color:#777;">by <?= h($item['author']); ?></div>
                    <div style="font-size:.76rem; color:#15803d; font-weight:700;">
                      <i class="fas fa-circle-check"></i> Finished (100%)
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" style="font-size:.74rem; padding:4px 8px; margin-top:auto;"
                            onclick="setShelfStatus(<?= $item['book_id']; ?>, 'reading')">
                      <i class="fas fa-rotate-left"></i> Re-read
                    </button>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Shelf 3: Want to Read -->
          <div id="shelf-want" class="shelf-panel" style="display:none;">
            <?php if (empty($want_books)): ?>
              <div style="text-align:center; padding:35px 20px; background:#faf5eb; border-radius:12px; color:#777;">
                <i class="fas fa-bookmark" style="font-size:2rem; color:var(--gold); margin-bottom:10px;"></i>
                <p style="margin:0;">Your reading wishlist is empty. Discover exciting titles in the store!</p>
              </div>
            <?php else: ?>
              <div class="bookshelf-grid">
                <?php foreach ($want_books as $item): ?>
                  <div class="bookshelf-card" id="shelf-card-<?= $item['book_id']; ?>">
                    <a href="book.php?id=<?= $item['book_id']; ?>">
                      <img src="images/<?= h($item['cover_image']); ?>" alt="<?= h($item['title']); ?>" class="bookshelf-card-cover">
                    </a>
                    <h4 style="font-size:.92rem; margin:0; line-height:1.3;">
                      <a href="book.php?id=<?= $item['book_id']; ?>"><?= h($item['title']); ?></a>
                    </h4>
                    <div style="font-size:.78rem; color:#777;">by <?= h($item['author']); ?></div>
                    
                    <div style="display:flex; gap:6px; margin-top:auto;">
                      <button type="button" class="btn btn-primary btn-sm" style="flex-grow:1; font-size:.74rem; padding:4px 8px;"
                              onclick="setShelfStatus(<?= $item['book_id']; ?>, 'reading')">
                        <i class="fas fa-book-open"></i> Start Reading
                      </button>
                      <button type="button" class="btn btn-danger btn-sm" style="font-size:.74rem; padding:4px 8px;"
                              onclick="removeShelfItem(<?= $item['book_id']; ?>)">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Tab 3: Saved Addresses Book -->
        <div id="tab-addresses" class="tab-content <?= $active_tab === 'addresses' ? 'active' : ''; ?>">
          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:18px;">
            <div>
              <h3 style="margin:0;">Saved Delivery Addresses</h3>
              <p style="color:#6b6259; font-size:.86rem; margin:3px 0 0;">Manage your home, office, and custom delivery destinations for instant 1-click checkout.</p>
            </div>
            <button type="button" onclick="document.getElementById('add-address-modal').classList.add('show')" class="btn btn-primary btn-sm">
              <i class="fas fa-plus"></i> Add New Address
            </button>
          </div>

          <?php if (empty($saved_addresses)): ?>
            <div style="text-align:center; padding:40px 20px; background:#faf5eb; border-radius:12px; border:1px dashed var(--paper-dim);">
              <i class="fas fa-map-location-dot" style="font-size:2.4rem; color:var(--gold); margin-bottom:12px;"></i>
              <h4>No saved addresses yet</h4>
              <p style="color:#6b6259; font-size:.85rem; max-width:350px; margin:6px auto 16px;">Add your primary delivery address to enjoy seamless, fast ordering.</p>
              <button type="button" onclick="document.getElementById('add-address-modal').classList.add('show')" class="btn btn-outline btn-sm">
                <i class="fas fa-plus"></i> Add Your First Address
              </button>
            </div>
          <?php else: ?>
            <div class="address-grid">
              <?php foreach ($saved_addresses as $addr): ?>
                <div class="address-card <?= $addr['is_default'] ? 'is-default' : ''; ?>">
                  <div class="address-card-head">
                    <span class="address-type-pill <?= $addr['address_type']; ?>">
                      <i class="fas <?= $addr['address_type'] === 'home' ? 'fa-house' : ($addr['address_type'] === 'office' ? 'fa-briefcase' : 'fa-location-dot'); ?>"></i>
                      <?= ucfirst($addr['address_type']); ?>
                    </span>
                    <?php if ($addr['is_default']): ?>
                      <span style="font-size:.72rem; font-weight:700; color:var(--gold); background:rgba(201,162,75,.15); padding:2px 8px; border-radius:999px;">
                        <i class="fas fa-star"></i> Default
                      </span>
                    <?php endif; ?>
                  </div>
                  <div class="address-card-name"><?= h($addr['full_name']); ?></div>
                  <div class="address-card-phone"><i class="fas fa-phone"></i> <?= h($addr['phone']); ?></div>
                  <div class="address-card-text">
                    <?= nl2br(h($addr['address_line'])); ?><br>
                    <?= h($addr['city']); ?>, <?= h($addr['state']); ?> - <strong><?= h($addr['pincode']); ?></strong>
                  </div>
                  <div class="address-card-actions">
                    <?php if (!$addr['is_default']): ?>
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
                        <input type="hidden" name="address_id" value="<?= $addr['id']; ?>">
                        <button type="submit" name="set_default_address" class="btn btn-outline btn-sm" style="font-size:.75rem; padding:4px 8px;" title="Set as default">
                          Set Default
                        </button>
                      </form>
                    <?php endif; ?>
                    <form method="post" style="display:inline; margin-left:auto;">
                      <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
                      <input type="hidden" name="address_id" value="<?= $addr['id']; ?>">
                      <button type="submit" name="delete_address" class="btn btn-danger btn-sm" style="font-size:.75rem; padding:4px 8px;" onclick="return confirm('Delete this address?');">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Tab 4: Change Password -->
        <div id="tab-password" class="tab-content <?= $active_tab === 'password' ? 'active' : ''; ?>">
          <h3 style="margin-bottom:6px;">Change Password</h3>
          <p style="color:#6b6259; font-size:.88rem; margin-bottom:24px;">Ensure your account is using a strong and unique password.</p>

          <form action="" method="post" class="admin-form">
            <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">

            <label for="current-password">Current Password</label>
            <div class="password-wrapper">
              <input type="password" id="current-password" name="current_password" required class="box" placeholder="Enter current password">
              <button type="button" class="password-toggle" data-target="current-password" title="Show password" tabindex="-1">
                <i class="fas fa-eye"></i>
              </button>
            </div>

            <label for="new-password">New Password (min 6 characters)</label>
            <div class="password-wrapper">
              <input type="password" id="new-password" name="new_password" required class="box" placeholder="Enter new password">
              <button type="button" class="password-toggle" data-target="new-password" title="Show password" tabindex="-1">
                <i class="fas fa-eye"></i>
              </button>
            </div>

            <label for="confirm-password">Confirm New Password</label>
            <div class="password-wrapper">
              <input type="password" id="confirm-password" name="confirm_password" required class="box" placeholder="Re-type new password">
              <button type="button" class="password-toggle" data-target="confirm-password" title="Show password" tabindex="-1">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            
            <div id="password-match-msg" class="password-match-indicator"></div>

            <button type="submit" name="change_password" class="btn btn-primary" style="margin-top:10px;">
              <i class="fas fa-lock"></i> Update Password
            </button>
          </form>
        </div>
      </main>
    </div>
  </div>
</section>

<!-- Add Address Modal -->
<div id="add-address-modal" class="replacement-modal" style="display:none;">
  <div class="replacement-dialog" style="max-width:560px;">
    <div class="replacement-modal-head">
      <h3><i class="fas fa-map-location-dot"></i> Add New Delivery Address</h3>
      <button type="button" onclick="document.getElementById('add-address-modal').classList.remove('show')" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:#777;">&times;</button>
    </div>
    <form method="post" class="replacement-modal-body admin-form" style="padding:22px;">
      <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
      <input type="hidden" name="add_address" value="1">

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
        <div>
          <label style="font-size:.85rem; font-weight:600; margin-bottom:4px;">Address Type</label>
          <select name="address_type" class="box" style="padding:9px;">
            <option value="home">🏠 Home (All Day)</option>
            <option value="office">🏢 Office (9 AM - 6 PM)</option>
            <option value="other">📍 Other</option>
          </select>
        </div>
        <div>
          <label style="font-size:.85rem; font-weight:600; margin-bottom:4px;">Contact Phone *</label>
          <input type="tel" name="phone" required placeholder="10-digit mobile" class="box" value="<?= h($user['phone'] ?? ''); ?>">
        </div>
      </div>

      <label style="font-size:.85rem; font-weight:600; margin-bottom:4px;">Recipient Full Name *</label>
      <input type="text" name="full_name" required placeholder="e.g. John Doe" class="box" value="<?= h($user['name']); ?>" style="margin-bottom:12px;">

      <label style="font-size:.85rem; font-weight:600; margin-bottom:4px;">Street Address / Flat / Building *</label>
      <textarea name="address_line" required rows="2" placeholder="Flat/House No, Building Name, Street, Landmark" class="box" style="margin-bottom:12px;"></textarea>

      <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:14px;">
        <div>
          <label style="font-size:.85rem; font-weight:600; margin-bottom:4px;">City *</label>
          <input type="text" name="city" required placeholder="e.g. Mumbai" class="box">
        </div>
        <div>
          <label style="font-size:.85rem; font-weight:600; margin-bottom:4px;">State *</label>
          <input type="text" name="state" required placeholder="e.g. Maharashtra" class="box">
        </div>
        <div>
          <label style="font-size:.85rem; font-weight:600; margin-bottom:4px;">PIN Code *</label>
          <input type="text" name="pincode" required placeholder="6 digits" maxlength="6" class="box">
        </div>
      </div>

      <div style="margin-bottom:18px; display:flex; align-items:center; gap:8px;">
        <input type="checkbox" id="modal-is-default" name="is_default" value="1" style="accent-color:var(--wine); width:18px; height:18px;">
        <label for="modal-is-default" style="font-size:.88rem; cursor:pointer;">Set as my primary default address</label>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" onclick="document.getElementById('add-address-modal').classList.remove('show')" class="btn btn-outline btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Save Address</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Tab switcher
  document.querySelectorAll('.profile-tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.profile-tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
      btn.classList.add('active');
      const target = document.getElementById(btn.dataset.tab);
      if (target) target.classList.add('active');
    });
  });

  const modal = document.getElementById('add-address-modal');
  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) modal.classList.remove('show');
    });
  }
});

function showShelf(name, btn) {
  document.querySelectorAll('.bookshelf-nav-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.shelf-panel').forEach(p => p.style.display = 'none');
  btn.classList.add('active');
  const target = document.getElementById('shelf-' + name);
  if (target) target.style.display = 'block';
}

async function promptSetGoal(curr) {
  const target = prompt('Set your 2026 Reading Challenge goal (number of books):', curr || 12);
  if (!target || isNaN(target) || parseInt(target) <= 0) return;

  const fd = new FormData();
  fd.append('action', 'set_target');
  fd.append('target', parseInt(target));
  fd.append('year', 2026);

  try {
    const res = await fetch('ajax/bookshelf.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      location.href = 'profile.php?tab=reading';
    } else {
      alert(data.message || 'Could not update goal.');
    }
  } catch(e) {
    alert('Network error.');
  }
}

async function setShelfStatus(bookId, status) {
  const fd = new FormData();
  fd.append('action', 'set_status');
  fd.append('book_id', bookId);
  fd.append('status', status);

  try {
    const res = await fetch('ajax/bookshelf.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      location.href = 'profile.php?tab=reading';
    } else {
      alert(data.message || 'Error updating status.');
    }
  } catch(e) {
    alert('Network error.');
  }
}

async function updateReadingProgress(bookId, val, status) {
  document.getElementById('prog-num-' + bookId).textContent = val + '%';
  const fd = new FormData();
  fd.append('action', 'set_status');
  fd.append('book_id', bookId);
  fd.append('status', status);
  fd.append('progress', val);
  await fetch('ajax/bookshelf.php', { method: 'POST', body: fd });
}

async function removeShelfItem(bookId) {
  if (!confirm('Remove this book from your bookshelf?')) return;
  const fd = new FormData();
  fd.append('action', 'remove');
  fd.append('book_id', bookId);

  try {
    const res = await fetch('ajax/bookshelf.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      const card = document.getElementById('shelf-card-' + bookId);
      if (card) card.remove();
    }
  } catch(e) {
    alert('Network error.');
  }
}
</script>

<?php require_once 'includes/footer.php'; ?>
