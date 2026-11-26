<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];

// Handle "reorder" — re-add every item from a past order back into the cart
if (isset($_POST['reorder_order_id'])) {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash('error', 'Session expired, please try again.');
    } else {
        $order_id = (int) $_POST['reorder_order_id'];
        $check = $pdo->prepare('SELECT id FROM orders WHERE id = ? AND user_id = ?');
        $check->execute([$order_id, $user_id]);
        if ($check->fetch()) {
            $items_to_reorder = $pdo->prepare(
                'SELECT oi.book_id, oi.quantity, b.stock FROM order_items oi JOIN books b ON b.id = oi.book_id WHERE oi.order_id = ?'
            );
            $items_to_reorder->execute([$order_id]);
            $skipped = [];
            $added = 0;
            foreach ($items_to_reorder->fetchAll() as $it) {
                if ($it['stock'] <= 0) { $skipped[] = $it['book_id']; continue; }
                $qty = min($it['quantity'], $it['stock']);
                $stmt = $pdo->prepare(
                    'INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)'
                );
                $stmt->execute([$user_id, $it['book_id'], $qty, $it['stock']]);
                $added++;
            }
            if ($added && $skipped) {
                flash('success', 'Added available items to your cart — some were out of stock and skipped.');
            } elseif ($added) {
                flash('success', 'Order items added to your cart.');
            } else {
                flash('error', 'None of those items are currently in stock.');
            }
            redirect('cart.php');
        }
    }
}

// Handle order cancellation (only if status is 'pending')
if (isset($_POST['cancel_order_id'])) {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash('error', 'Session expired, please try again.');
    } else {
        $order_id = (int) $_POST['cancel_order_id'];
        $check = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = "pending"');
        $check->execute([$order_id, $user_id]);
        $order = $check->fetch();
        if ($order) {
            try {
                $pdo->beginTransaction();
                // Restore stock
                $items = $pdo->prepare('SELECT book_id, quantity FROM order_items WHERE order_id = ?');
                $items->execute([$order_id]);
                $stock_up = $pdo->prepare('UPDATE books SET stock = stock + ? WHERE id = ?');
                foreach ($items->fetchAll() as $it) {
                    $stock_up->execute([$it['quantity'], $it['book_id']]);
                }
                // Rollback coupon use count if applied
                if (!empty($order['coupon_code'])) {
                    $pdo->prepare('UPDATE coupons SET used_count = GREATEST(0, used_count - 1) WHERE code = ?')
                        ->execute([$order['coupon_code']]);
                }
                // Update status
                $pdo->prepare('UPDATE orders SET status = "cancelled" WHERE id = ?')->execute([$order_id]);
                $pdo->commit();
                flash('success', 'Order #' . $order_id . ' has been cancelled and stock has been restored.');
            } catch (Exception $e) {
                $pdo->rollBack();
                flash('error', 'Could not cancel order: ' . $e->getMessage());
            }
        } else {
            flash('error', 'Order cannot be cancelled as it is already being processed or delivered.');
        }
        redirect('orders.php');
    }
}

// Handle 7-Day Replacement Request (for delivered orders within 7 days)
if (isset($_POST['request_replacement'])) {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash('error', 'Session expired, please try again.');
    } else {
        $order_id = (int) $_POST['order_id'];
        $reason = trim($_POST['replacement_reason'] ?? '');
        $notes = trim($_POST['replacement_notes'] ?? '');

        if ($reason === '') {
            flash('error', 'Please select a reason for replacement.');
        } else {
            $check = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
            $check->execute([$order_id, $user_id]);
            $order = $check->fetch();

            if (!$order) {
                flash('error', 'Order not found.');
            } elseif ($order['status'] !== 'delivered') {
                flash('error', 'Replacement can only be requested for delivered orders.');
            } else {
                $deliv_time = $order['delivered_on'] ? strtotime($order['delivered_on']) : strtotime($order['placed_on']);
                $days_diff = floor((time() - $deliv_time) / 86400);

                if ($days_diff > 7) {
                    flash('error', 'The 7-day easy replacement window for this order has expired.');
                } elseif ($order['replacement_status'] !== 'none') {
                    flash('error', 'A replacement request has already been submitted for this order.');
                } else {
                    $up = $pdo->prepare('UPDATE orders SET status = "replacement_requested", replacement_status = "requested", replacement_reason = ?, replacement_notes = ?, replacement_requested_on = NOW() WHERE id = ?');
                    $up->execute([$reason, $notes, $order_id]);

                    flash('success', '🔄 Replacement request submitted for Order #' . $order_id . '! Our support team will verify and arrange your exchange copy.');
                }
            }
        }
        redirect('orders.php');
    }
}

$orders_stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY placed_on DESC');
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll();

$items_stmt = $pdo->prepare('SELECT oi.*, b.cover_image FROM order_items oi LEFT JOIN books b ON b.id = oi.book_id WHERE oi.order_id = ?');

function render_order_stepper($order) {
    $status = is_array($order) ? $order['status'] : $order;
    $rep_status = is_array($order) ? ($order['replacement_status'] ?? 'none') : 'none';

    if ($status === 'cancelled') {
        $html = '<div class="order-stepper" style="max-width:320px;">';
        $html .= '<div class="step-item completed"><div class="step-icon"><i class="fas fa-check"></i></div><div class="step-label">Placed</div></div>';
        $html .= '<div class="step-item cancelled"><div class="step-connector line-cancelled"></div><div class="step-icon"><i class="fas fa-xmark"></i></div><div class="step-label">Cancelled</div></div>';
        $html .= '</div>';
        $html .= '<div style="background:rgba(179,53,43,.08); color:var(--danger); border-left:3px solid var(--danger); padding:8px 12px; border-radius:6px; font-size:.82rem; margin:10px 0 4px; display:flex; align-items:center; gap:8px;">';
        $html .= '<i class="fas fa-circle-info"></i> This order was cancelled and items have been returned to stock.';
        $html .= '</div>';
        return $html;
    }

    // Replacement workflow stepper
    if ($rep_status !== 'none' || in_array($status, ['replacement_requested', 'replacement_approved', 'replaced'], true)) {
        $r_steps = [
            'delivered'            => ['label' => 'Delivered', 'icon' => 'fa-house-circle-check'],
            'replacement_requested'=> ['label' => 'Requested', 'icon' => 'fa-arrows-rotate'],
            'replacement_approved' => ['label' => 'In Transit', 'icon' => 'fa-truck-fast'],
            'replaced'             => ['label' => 'Replaced', 'icon' => 'fa-circle-check'],
        ];

        $keys = array_keys($r_steps);
        $curr_idx = 1; // Default requested
        if ($status === 'replacement_approved' || $rep_status === 'approved') $curr_idx = 2;
        if ($status === 'replaced' || $rep_status === 'replaced') $curr_idx = 3;

        $html = '<div class="order-stepper">';
        foreach ($keys as $idx => $k) {
            $info = $r_steps[$k];
            $is_completed = $idx <= $curr_idx;
            $is_active = $idx === $curr_idx;

            $class = $is_completed ? 'completed' : '';
            if ($is_active && $curr_idx < 3) $class = 'active replacement';
            $icon = $is_completed ? 'fa-check' : $info['icon'];
            $line_class = ($idx > 0 && $idx <= $curr_idx) ? 'line-completed' : '';

            $html .= '<div class="step-item ' . $class . '">';
            if ($idx > 0) $html .= '<div class="step-connector ' . $line_class . '"></div>';
            $html .= '<div class="step-icon"><i class="fas ' . $icon . '"></i></div>';
            $html .= '<div class="step-label">' . $info['label'] . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    // Standard Order Stepper
    $steps = [
        'pending'    => ['label' => 'Placed', 'icon' => 'fa-receipt'],
        'processing' => ['label' => 'Processing', 'icon' => 'fa-box-open'],
        'shipped'    => ['label' => 'Shipped', 'icon' => 'fa-truck-fast'],
        'delivered'  => ['label' => 'Delivered', 'icon' => 'fa-house-circle-check'],
    ];

    $keys = array_keys($steps);
    $current_idx = array_search($status, $keys, true);
    if ($current_idx === false) $current_idx = 0;

    $html = '<div class="order-stepper">';
    foreach ($keys as $idx => $k) {
        $info = $steps[$k];
        $is_completed = $idx <= $current_idx;
        $is_active = $idx === $current_idx;
        
        $class = $is_completed ? 'completed' : '';
        if ($is_active && $current_idx < 3) $class = 'active';
        $icon = $is_completed ? 'fa-check' : $info['icon'];
        $line_class = ($idx > 0 && $idx <= $current_idx) ? 'line-completed' : '';

        $html .= '<div class="step-item ' . $class . '">';
        if ($idx > 0) $html .= '<div class="step-connector ' . $line_class . '"></div>';
        $html .= '<div class="step-icon"><i class="fas ' . $icon . '"></i></div>';
        $html .= '<div class="step-label">' . $info['label'] . '</div>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

$active_page = 'orders';
$page_title = 'My Orders';
require_once 'includes/header.php';
?>

<section style="padding-top:38px;">
  <div class="container">
    <div class="section-head reveal in">
      <div>
        <span class="eyebrow">History & Tracking</span>
        <h2>My orders</h2>
      </div>
    </div>

    <?php if (empty($orders)): ?>
      <div class="empty-state reveal in">
        <i class="fas fa-box-open"></i>
        <h3>No orders yet</h3>
        <p>Once you place an order, you'll be able to track its progress and print invoices here.</p>
        <a href="shop.php" class="btn btn-primary" style="margin-top:16px;">Start Shopping</a>
      </div>
    <?php else: foreach ($orders as $order):
      $items_stmt->execute([$order['id']]);
      $order_items = $items_stmt->fetchAll();

      // 7-day replacement window calculation
      $deliv_ts = $order['delivered_on'] ? strtotime($order['delivered_on']) : strtotime($order['placed_on']);
      $days_passed = floor((time() - $deliv_ts) / 86400);
      $days_left = max(0, 7 - $days_passed);
      $is_delivered = ($order['status'] === 'delivered');
      $is_rep_eligible = ($is_delivered && $days_left > 0 && ($order['replacement_status'] === 'none' || empty($order['replacement_status'])));
      $items_json = htmlspecialchars(json_encode(array_map(fn($i) => ['title' => $i['title'], 'qty' => $i['quantity']], $order_items)), ENT_QUOTES, 'UTF-8');
    ?>
      <div class="cart-item reveal in" style="grid-template-columns:1fr; align-items:stretch; gap:10px; margin-bottom:22px; padding:22px;">
        <div class="row" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
          <div>
            <strong style="font-size:1.05rem;">Order #<?= $order['id']; ?></strong>
            <span class="stock-badge" style="margin-left:10px;"><i class="fas fa-calendar-day"></i> <?= date('d M Y, h:i A', strtotime($order['placed_on'])); ?></span>
            <?php if ($is_rep_eligible): ?>
              <span style="font-size:.74rem; color:#15803d; background:#f0fdf4; border:1px solid #bbf7d0; padding:2px 8px; border-radius:999px; font-weight:600; margin-left:8px;">
                <i class="fas fa-rotate"></i> 7-Day Replacement Active (<?= $days_left; ?> days left)
              </span>
            <?php endif; ?>
          </div>
          <span class="status-pill status-<?= $order['status']; ?>"><?= ucfirst(str_replace('_', ' ', $order['status'])); ?></span>
        </div>

        <!-- Visual Stepper Progress Bar -->
        <?= render_order_stepper($order); ?>

        <!-- Replacement Notification Banners -->
        <?php if ($order['replacement_status'] === 'requested'): ?>
          <div style="background:#fffbeb; color:#92400e; border:1px solid #fde68a; border-left:4px solid #f59e0b; padding:10px 14px; border-radius:8px; font-size:.84rem; margin:8px 0;">
            <i class="fas fa-arrows-rotate fa-spin" style="margin-right:4px;"></i> <strong>Replacement Requested</strong>: <?= h($order['replacement_reason']); ?>
            <div style="font-size:.76rem; color:#78350f; margin-top:3px;">
              Requested on <?= date('d M Y, h:i A', strtotime($order['replacement_requested_on'] ?: 'now')); ?>. Our team is processing your exchange copy.
            </div>
          </div>
        <?php elseif ($order['replacement_status'] === 'approved'): ?>
          <div style="background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; border-left:4px solid #3b82f6; padding:10px 14px; border-radius:8px; font-size:.84rem; margin:8px 0;">
            <i class="fas fa-truck-fast" style="margin-right:4px;"></i> <strong>Replacement Approved</strong>: A fresh copy has been dispatched to your address!
          </div>
        <?php elseif ($order['replacement_status'] === 'replaced'): ?>
          <div style="background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; border-left:4px solid #22c55e; padding:10px 14px; border-radius:8px; font-size:.84rem; margin:8px 0;">
            <i class="fas fa-circle-check" style="margin-right:4px;"></i> <strong>Book Replacement Complete</strong>: Fresh replacement copy delivered!
          </div>
        <?php elseif ($order['replacement_status'] === 'rejected'): ?>
          <div style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca; border-left:4px solid #ef4444; padding:10px 14px; border-radius:8px; font-size:.84rem; margin:8px 0;">
            <i class="fas fa-circle-xmark" style="margin-right:4px;"></i> <strong>Replacement Request Declined</strong>. For inquiries, email support@bookly.local.
          </div>
        <?php endif; ?>

        <div style="font-size:.9rem; color:#6b6259; margin-top:4px;">
          <strong>Items:</strong> <?= implode(', ', array_map(fn($i) => h($i['title']) . ' ×' . $i['quantity'], $order_items)); ?>
        </div>

        <div class="row" style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
          <span class="stock-badge">
            <i class="fas fa-credit-card"></i> <?= h($order['payment_method']); ?>
            <?= !empty($order['payment_id']) ? ' · <code style="font-size:.7rem; background:rgba(0,0,0,.06); padding:1px 5px; border-radius:3px;">' . h($order['payment_id']) . '</code>' : ''; ?>
            <?= $order['coupon_code'] ? ' · <i class="fas fa-tag"></i> Coupon: ' . h($order['coupon_code']) : ''; ?>
          </span>
          <span class="price-tag"><?= price($order['total_price']); ?></span>
        </div>

        <div class="order-actions">
          <a href="invoice.php?id=<?= $order['id']; ?>" class="btn btn-outline btn-sm">
            <i class="fas fa-file-invoice"></i> View / Print Invoice
          </a>

          <!-- 7-Day Easy Replacement Button -->
          <?php if ($is_rep_eligible): 
            $items_text = htmlspecialchars(addslashes(implode(', ', array_map(fn($i) => $i['title'] . ' × ' . $i['quantity'], $order_items))), ENT_QUOTES);
          ?>
            <button type="button" class="btn btn-outline btn-sm open-replacement-btn" 
                    onclick="openReplacementModal(<?= $order['id']; ?>, '<?= $items_text; ?>')"
                    data-order-id="<?= $order['id']; ?>"
                    data-items="<?= $items_json; ?>"
                    style="border-color:var(--wine); color:var(--wine); font-weight:600;">
              <i class="fas fa-rotate"></i> Request Replacement
            </button>
          <?php elseif ($is_delivered && $days_passed >= 7 && ($order['replacement_status'] === 'none' || empty($order['replacement_status']))): ?>
            <span style="font-size:.75rem; color:#888; display:flex; align-items:center; gap:4px;">
              <i class="fas fa-clock-rotate-left"></i> 7-day replacement window ended
            </span>
          <?php endif; ?>

          <form method="post" style="display:inline;">
            <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
            <input type="hidden" name="reorder_order_id" value="<?= $order['id']; ?>">
            <button type="submit" class="btn btn-outline btn-sm">
              <i class="fas fa-rotate-right"></i> Reorder
            </button>
          </form>

          <?php if ($order['status'] === 'pending'): ?>
            <form method="post" style="display:inline; margin-left:auto;">
              <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
              <input type="hidden" name="cancel_order_id" value="<?= $order['id']; ?>">
              <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel Order #<?= $order['id']; ?>?');">
                <i class="fas fa-ban"></i> Cancel Order
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</section>

<!-- Replacement Request Modal Dialog -->
<div id="replacement-modal" class="replacement-modal" style="display:none;">
  <div class="replacement-dialog">
    <div class="replacement-modal-head">
      <h3><i class="fas fa-rotate"></i> Request Book Replacement</h3>
      <button type="button" onclick="closeReplacementModal()" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:#777; line-height:1;">&times;</button>
    </div>
    <form method="post" class="replacement-modal-body admin-form" style="padding:22px;">
      <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
      <input type="hidden" name="request_replacement" value="1">
      <input type="hidden" name="order_id" id="rep-order-id" value="">

      <div style="background:#faf5eb; border:1px solid var(--paper-dim); border-radius:8px; padding:12px; margin-bottom:16px;">
        <div style="font-size:.82rem; color:#6b6259; margin-bottom:4px;">Eligible Order: <strong id="rep-order-display">#---</strong></div>
        <div style="font-size:.85rem; font-weight:700; color:var(--wine);" id="rep-items-display">Loading items...</div>
      </div>

      <label style="font-size:.86rem; font-weight:600; display:block; margin-bottom:6px;">Select Reason for Replacement *</label>
      <select name="replacement_reason" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--paper-dim); margin-bottom:14px; background:#fff;">
        <option value="">-- Choose a reason --</option>
        <option value="Damaged or Torn Pages / Cover">Damaged or Torn Pages / Cover</option>
        <option value="Wrong Book Delivered">Wrong Book Delivered</option>
        <option value="Missing or Blank Pages">Missing or Blank Pages</option>
        <option value="Print Quality / Binding Defect">Print Quality / Binding Defect</option>
        <option value="Other Quality Defect">Other Quality Defect</option>
      </select>

      <label style="font-size:.86rem; font-weight:600; display:block; margin-bottom:6px;">Additional Details (Optional)</label>
      <textarea name="replacement_notes" rows="3" placeholder="Describe the defect or issue with the book..." style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--paper-dim); margin-bottom:16px;"></textarea>

      <div style="display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" onclick="closeReplacementModal()" class="btn btn-outline btn-sm">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i> Submit Replacement</button>
      </div>
    </form>
  </div>
</div>

<script>
function openReplacementModal(id, itemsText) {
  const modal = document.getElementById('replacement-modal');
  const orderIdInput = document.getElementById('rep-order-id');
  const orderDisplay = document.getElementById('rep-order-display');
  const itemsDisplay = document.getElementById('rep-items-display');

  if (orderIdInput) orderIdInput.value = id;
  if (orderDisplay) orderDisplay.textContent = '#' + id;
  if (itemsDisplay) itemsDisplay.textContent = itemsText || 'Bookly Curated Reads';
  if (modal) {
    modal.style.display = 'flex';
    modal.classList.add('show');
  }
}

function closeReplacementModal() {
  const modal = document.getElementById('replacement-modal');
  if (modal) {
    modal.style.display = 'none';
    modal.classList.remove('show');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('replacement-modal');
  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeReplacementModal();
    });
  }
});
</script>

<?php require_once 'includes/footer.php'; ?>

