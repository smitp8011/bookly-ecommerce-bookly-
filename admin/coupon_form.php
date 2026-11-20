<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$coupon = [
    'code' => '',
    'discount_type' => 'percent',
    'discount_value' => '10',
    'min_order_value' => '0',
    'max_uses' => '',
    'expires_at' => '',
    'active' => 1
];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM coupons WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if ($existing) {
        $coupon = $existing;
    } else {
        flash('error', 'Coupon not found.');
        redirect('coupons.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $code            = strtoupper(trim($_POST['code'] ?? ''));
        $discount_type   = $_POST['discount_type'] === 'flat' ? 'flat' : 'percent';
        $discount_value  = (float) ($_POST['discount_value'] ?? 0);
        $min_order_value = (float) ($_POST['min_order_value'] ?? 0);
        $max_uses        = trim($_POST['max_uses'] ?? '') !== '' ? (int) $_POST['max_uses'] : null;
        $expires_at      = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        $active          = isset($_POST['active']) ? 1 : 0;

        if ($code === '') {
            $errors[] = 'Coupon code is required.';
        } elseif (!preg_match('/^[A-Z0-9_-]+$/', $code)) {
            $errors[] = 'Coupon code must contain only letters, numbers, and dashes.';
        } elseif ($discount_value <= 0) {
            $errors[] = 'Discount value must be greater than 0.';
        } elseif ($discount_type === 'percent' && $discount_value > 100) {
            $errors[] = 'Percentage discount cannot exceed 100%.';
        } else {
            // Check uniqueness
            $check = $pdo->prepare('SELECT id FROM coupons WHERE code = ? AND id != ?');
            $check->execute([$code, $id]);
            if ($check->fetch()) {
                $errors[] = 'A coupon with code "' . h($code) . '" already exists.';
            } else {
                if ($id) {
                    $stmt = $pdo->prepare(
                        'UPDATE coupons SET code = ?, discount_type = ?, discount_value = ?, min_order_value = ?, max_uses = ?, expires_at = ?, active = ? WHERE id = ?'
                    );
                    $stmt->execute([$code, $discount_type, $discount_value, $min_order_value, $max_uses, $expires_at, $active, $id]);
                    flash('success', 'Coupon "' . $code . '" updated successfully.');
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO coupons (code, discount_type, discount_value, min_order_value, max_uses, expires_at, active) VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$code, $discount_type, $discount_value, $min_order_value, $max_uses, $expires_at, $active]);
                    flash('success', 'Coupon "' . $code . '" created successfully.');
                }
                redirect('coupons.php');
            }
        }
    }
}

$active = 'coupons';
$page_title = $id ? 'Edit Coupon' : 'Add Coupon';
require_once 'includes/admin_header.php';
?>

<div class="section-head">
  <div>
    <span class="eyebrow">Discounts</span>
    <h2><?= $id ? 'Edit Coupon' : 'Add New Coupon'; ?></h2>
  </div>
  <a href="coupons.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Coupons</a>
</div>

<?php foreach ($errors as $err): ?>
  <div class="field-error" style="margin-bottom:16px;"><i class="fas fa-circle-exclamation"></i> <?= h($err); ?></div>
<?php endforeach; ?>

<form action="" method="post" class="admin-form" style="max-width:600px; background:#fffdf8; padding:32px; border-radius:12px; box-shadow:var(--shadow-sm);">
  <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">

  <label for="code">Coupon Code *</label>
  <input type="text" id="code" name="code" required value="<?= h($coupon['code']); ?>" placeholder="e.g. FESTIVAL20" style="text-transform:uppercase; font-weight:700; letter-spacing:.05em;" autofocus>

  <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
    <div>
      <label for="discount_type">Discount Type *</label>
      <select id="discount_type" name="discount_type">
        <option value="percent" <?= $coupon['discount_type'] === 'percent' ? 'selected' : ''; ?>>Percentage (%)</option>
        <option value="flat" <?= $coupon['discount_type'] === 'flat' ? 'selected' : ''; ?>>Flat Amount (₹)</option>
      </select>
    </div>
    <div>
      <label for="discount_value">Discount Value *</label>
      <input type="number" id="discount_value" name="discount_value" step="0.01" min="0.01" required value="<?= h($coupon['discount_value']); ?>" placeholder="e.g. 10 or 50.00">
    </div>
  </div>

  <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
    <div>
      <label for="min_order_value">Minimum Order Value (₹)</label>
      <input type="number" id="min_order_value" name="min_order_value" step="0.01" min="0" value="<?= h($coupon['min_order_value']); ?>" placeholder="0 for no minimum">
    </div>
    <div>
      <label for="max_uses">Usage Limit (Max Uses)</label>
      <input type="number" id="max_uses" name="max_uses" min="1" value="<?= h($coupon['max_uses'] ?? ''); ?>" placeholder="Leave blank for unlimited">
    </div>
  </div>

  <label for="expires_at">Expiry Date (Optional)</label>
  <input type="date" id="expires_at" name="expires_at" value="<?= h($coupon['expires_at'] ?? ''); ?>">

  <div style="margin:16px 0 24px;">
    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:.9rem;">
      <input type="checkbox" name="active" value="1" <?= $coupon['active'] ? 'checked' : ''; ?> style="width:auto; margin:0; accent-color:var(--wine);">
      Enable this coupon immediately (Active)
    </label>
  </div>

  <button type="submit" class="btn btn-primary" style="width:100%;">
    <i class="fas fa-floppy-disk"></i> <?= $id ? 'Save Changes' : 'Create Coupon'; ?>
  </button>
</form>

<?php require_once 'includes/admin_footer.php'; ?>
