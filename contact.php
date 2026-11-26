<?php
require_once 'config.php';
require_once 'includes/functions.php';

$errors = [];

// Pre-fill if user is logged in
$user_name  = $_SESSION['user_name'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Session expired, please try again.';
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $topic   = trim($_POST['topic'] ?? 'General Question');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $phone === '' || $message === '') {
            $errors[] = 'Please fill in every field.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } else {
            $formatted_message = "[Topic: {$topic}] " . $message;

            $stmt = $pdo->prepare('INSERT INTO messages (name, email, phone, message) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, $phone, $formatted_message]);
            flash('success', 'Thanks — we\'ll get back to you soon!');
            redirect('contact.php');
        }
    }
}

$active_page = 'contact';
$page_title = 'Contact Us — Bookly';
require_once 'includes/header.php';
?>

<style>
/* Remove footer margin so right pane merges seamlessly */
.site-footer {
  margin-top: 0 !important;
}

/* Edge-to-Edge 50/50 Split Screen */
.contact-split-wrap {
  display: flex;
  width: 100%;
  min-height: calc(100vh - 74px);
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.contact-left-half {
  width: 50%;
  background: var(--paper);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 40px 32px;
  box-sizing: border-box;
}

.contact-right-half {
  width: 50%;
  background: #1c1815;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 40px 32px;
  border-left: 1px solid rgba(201, 162, 75, 0.25);
  box-sizing: border-box;
}

/* Contact Form Card */
.contact-form-box {
  background: #fffdf8;
  padding: 20px 24px;
  border-radius: 12px;
  box-shadow: var(--shadow-sm);
  border: 1px solid rgba(201, 162, 75, 0.25);
  width: 100%;
}

/* Custom Dropdown Trigger */
.luxury-dropdown-btn {
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  width: 100%;
  margin-bottom: 0;
  background: var(--paper);
  color: var(--ink);
  text-align: left;
  padding: 9px 12px;
  font-size: .85rem;
  border: 1.5px solid var(--paper-dim);
  border-radius: 8px;
  transition: border-color .2s ease, box-shadow .2s ease;
}
.luxury-dropdown-btn:focus,
.luxury-dropdown-btn:hover {
  border-color: var(--gold);
}

/* Dropdown Menu */
.luxury-dropdown-menu {
  display: none;
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: #fffdf8;
  border: 1px solid rgba(201, 162, 75, 0.35);
  border-radius: 10px;
  box-shadow: 0 14px 34px rgba(0, 0, 0, 0.18);
  z-index: 99;
  overflow: hidden;
}

.luxury-option-item {
  padding: 10px 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: .84rem;
  font-weight: 500;
  color: var(--ink);
  transition: background .15s ease, color .15s ease;
  border-bottom: 1px solid rgba(201, 162, 75, 0.12);
}
.luxury-option-item:last-child {
  border-bottom: none;
}
.luxury-option-item:hover {
  background: rgba(201, 162, 75, 0.15) !important;
  color: var(--ink) !important;
}

.contact-badge-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: rgba(201, 162, 75, 0.12);
  color: #fae8b4;
  border: 1px solid rgba(201, 162, 75, 0.3);
  padding: 4px 10px;
  border-radius: 6px;
  font-size: .74rem;
  font-weight: 600;
}

.contact-social-link {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(201, 162, 75, 0.25);
  color: var(--gold-soft);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: .85rem;
  transition: all .2s ease;
}
.contact-social-link:hover {
  background: var(--gold);
  color: #120e0b;
  transform: translateY(-2px);
}

/* Dark Mode Overrides */
[data-theme="dark"] .contact-left-half {
  background: #0c0a08;
}
[data-theme="dark"] .contact-right-half {
  background: #080705;
  border-left-color: rgba(229, 193, 88, 0.15);
}
[data-theme="dark"] .contact-form-box {
  background: #15120e;
  border-color: rgba(229, 193, 88, 0.2);
}
[data-theme="dark"] .luxury-dropdown-btn {
  background: #1e1914;
  color: #f5efe6;
  border-color: rgba(229, 193, 88, 0.25);
}
[data-theme="dark"] .luxury-dropdown-menu {
  background: #191511;
  border-color: rgba(229, 193, 88, 0.3);
  box-shadow: 0 14px 40px rgba(0, 0, 0, 0.7);
}
[data-theme="dark"] .luxury-option-item {
  color: #f5efe6;
  border-bottom-color: rgba(255, 255, 255, 0.06);
}
[data-theme="dark"] .luxury-option-item:hover {
  background: rgba(229, 193, 88, 0.2) !important;
  color: #fae8b4 !important;
}

@media (max-width: 880px) {
  .contact-split-wrap {
    flex-direction: column;
    min-height: auto;
  }
  .contact-left-half,
  .contact-right-half {
    width: 100%;
    padding: 40px 20px;
  }
  .contact-right-half {
    border-left: none;
    border-top: 1px solid rgba(201, 162, 75, 0.25);
  }
}
</style>

<!-- Full Width Split Section (Header to Footer, Edge to Edge) -->
<div class="contact-split-wrap">
  
  <!-- LEFT HALF: Warm Background with Form -->
  <div class="contact-left-half">
    <div style="max-width: 440px; width: 100%;">
      
      <div style="margin-bottom: 12px;">
        <span class="eyebrow" style="font-size: .76rem; font-weight: 700; color: var(--gold); text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-bottom: 3px;">Get in touch</span>
        <h2 style="font-family: var(--font-display); font-size: 1.85rem; color: var(--ink); margin: 0; line-height: 1.2;">We'd love to hear from you</h2>
      </div>

      <?php foreach ($errors as $err): ?>
        <div class="field-error reveal in" style="margin-bottom: 12px; font-size: .75rem;"><i class="fas fa-circle-exclamation"></i> <?= h($err); ?></div>
      <?php endforeach; ?>

      <form method="post" class="contact-form-box">
        <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
        <input type="hidden" name="topic" id="selected-topic-input" value="General Question">

        <!-- Custom Luxury Topic Dropdown -->
        <div class="luxury-select-container" style="position:relative; margin-bottom:10px;">
          <button type="button" id="luxury-select-trigger" class="luxury-dropdown-btn">
            <span id="selected-topic-display" style="display:flex; align-items:center; gap:8px; font-weight:500;">
              <i class="fas fa-circle-question" style="color:var(--gold); font-size:.9rem;"></i> General Question
            </span>
            <i class="fas fa-chevron-down" id="luxury-select-chevron" style="color:var(--muted); font-size:.72rem; transition:transform .25s ease;"></i>
          </button>

          <!-- Dropdown Options Menu -->
          <div id="luxury-select-menu" class="luxury-dropdown-menu">
            <div class="luxury-option-item" data-value="General Question" data-icon="fa-circle-question">
              <i class="fas fa-circle-question" style="color:var(--gold); width:16px;"></i> General Question
            </div>
            <div class="luxury-option-item" data-value="Order Inquiry" data-icon="fa-box">
              <i class="fas fa-box" style="color:var(--gold); width:16px;"></i> Order Status &amp; Tracking
            </div>
            <div class="luxury-option-item" data-value="7-Day Replacement" data-icon="fa-rotate-left">
              <i class="fas fa-rotate-left" style="color:var(--gold); width:16px;"></i> 7-Day Easy Replacement
            </div>
            <div class="luxury-option-item" data-value="Book Recommendation" data-icon="fa-bookmark">
              <i class="fas fa-bookmark" style="color:var(--gold); width:16px;"></i> Book Recommendation
            </div>
          </div>
        </div>

        <div style="margin-bottom: 8px;">
          <input class="box" type="text" name="name" placeholder="Your name" required value="<?= h($_POST['name'] ?? $user_name); ?>" style="width:100%; padding:9px 12px; font-size:.85rem; margin-bottom:0;">
        </div>
        <div style="margin-bottom: 8px;">
          <input class="box" type="email" name="email" placeholder="Email address" required value="<?= h($_POST['email'] ?? $user_email); ?>" style="width:100%; padding:9px 12px; font-size:.85rem; margin-bottom:0;">
        </div>
        <div style="margin-bottom: 8px;">
          <input class="box" type="tel" name="phone" placeholder="Phone number" required value="<?= h($_POST['phone'] ?? ''); ?>" style="width:100%; padding:9px 12px; font-size:.85rem; margin-bottom:0;">
        </div>
        <div style="margin-bottom: 10px;">
          <textarea class="box" name="message" rows="3" placeholder="Your message" required style="width:100%; padding:9px 12px; font-size:.85rem; resize:none; margin-bottom:0;"><?= h($_POST['message'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px; font-size:.9rem; font-weight:700;">Send Message</button>
      </form>

    </div>
  </div>

  <!-- RIGHT HALF: Full Luxury Dark Obsidian Experience -->
  <div class="contact-right-half">
    <div style="max-width: 440px; width: 100%; display: flex; flex-direction: column; gap: 14px;">
      
      <!-- 1. Header Title & Subtitle -->
      <div>
        <h3 style="font-family: var(--font-display); font-size: 1.75rem; color: #fae8b4; margin: 0; line-height: 1.2;">
          Luxury Direct Contact
        </h3>
        <span style="font-size: .8rem; color: var(--gold-soft); font-weight: 500;">Flagship Store &amp; Literary Concierge</span>
      </div>

      <!-- 2. Flagship Boutique Address & Visiting Hours -->
      <div>
        <div style="font-size: .88rem; font-weight: 700; color: var(--gold); display: flex; align-items: center; gap: 6px;">
          <span style="font-size: 1.05rem;">🏛️</span> <span>Flagship Boutique:</span>
        </div>
        <div style="font-size: .84rem; color: #e3dcce; margin-top: 2px; padding-left: 24px;">
          Bookly Manor, Kala Ghoda, Fort, Mumbai — 400001
        </div>
        <div style="font-size: .78rem; color: var(--gold-soft); margin-top: 3px; padding-left: 24px; display: flex; align-items: center; gap: 5px;">
          <i class="far fa-clock"></i> <span>Mon–Sat: 9:30 AM – 8:30 PM | Sun: 11 AM – 7 PM</span>
        </div>
      </div>

      <!-- 3. Embedded Google Map with 1-Click Directions Link -->
      <div>
        <div style="border-radius: 12px; overflow: hidden; border: 1px solid rgba(201,162,75,.35); box-shadow: 0 8px 22px rgba(0,0,0,.45); width: 100%;">
          <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3773.916892550186!2d72.8296336759714!3d18.932912982241695!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be7d1dd324dc0f3%3A0x6e2df409f6e1f0e4!2sKala%20Ghoda%2C%20Fort%2C%20Mumbai%2C%20Maharashtra!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin" 
            width="100%" 
            height="130" 
            style="border:0; display:block;" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
        <div style="text-align: right; margin-top: 4px;">
          <a href="https://maps.google.com/?q=Kala+Ghoda+Fort+Mumbai" target="_blank" style="font-size: .76rem; font-weight: 700; color: var(--gold-soft); display: inline-flex; align-items: center; gap: 4px;">
            <i class="fas fa-diamond-turn-right" style="color:var(--gold);"></i> Open in Google Maps ↗
          </a>
        </div>
      </div>

      <!-- 4. Direct Hotline & Concierge Email -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <div>
          <div style="font-size: .78rem; font-weight: 700; color: var(--gold); text-transform: uppercase;">📞 Hotline</div>
          <a href="tel:02228942665" style="color: #ffffff; font-size: .84rem; font-weight: 600; display: block; margin-top: 2px;">+91 (022) 2894-BOOK</a>
        </div>
        <div>
          <div style="font-size: .78rem; font-weight: 700; color: var(--gold); text-transform: uppercase;">✉️ Concierge</div>
          <a href="mailto:concierge@bookly.in" style="color: var(--gold-soft); font-size: .84rem; font-weight: 600; display: block; margin-top: 2px;">concierge@bookly.in</a>
        </div>
      </div>

      <!-- 5. 3 Trust Guarantee Badges -->
      <div style="display: flex; flex-wrap: wrap; gap: 6px;">
        <span class="contact-badge-pill"><i class="fas fa-truck-fast" style="color:var(--gold);"></i> 24h Dispatch</span>
        <span class="contact-badge-pill"><i class="fas fa-rotate-left" style="color:var(--gold);"></i> 7-Day Replace</span>
        <span class="contact-badge-pill"><i class="fas fa-bolt" style="color:var(--gold);"></i> 2h Fast Reply</span>
      </div>

      <!-- 6. Social Community Links -->
      <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 10px;">
        <span style="font-size: .76rem; color: #a89c8a; font-weight: 500;">Join our readers community:</span>
        <div style="display: flex; gap: 8px;">
          <a href="#" class="contact-social-link" title="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" class="contact-social-link" title="Goodreads"><i class="fab fa-goodreads-g"></i></a>
          <a href="#" class="contact-social-link" title="Twitter / X"><i class="fab fa-x-twitter"></i></a>
        </div>
      </div>

    </div>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const trigger = document.getElementById('luxury-select-trigger');
  const menu = document.getElementById('luxury-select-menu');
  const chevron = document.getElementById('luxury-select-chevron');
  const display = document.getElementById('selected-topic-display');
  const hiddenInput = document.getElementById('selected-topic-input');
  const options = document.querySelectorAll('.luxury-option-item');

  if (trigger && menu) {
    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = menu.style.display === 'block';
      menu.style.display = isOpen ? 'none' : 'block';
      chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
    });

    options.forEach(opt => {
      opt.addEventListener('click', (e) => {
        e.stopPropagation();
        const val = opt.getAttribute('data-value');
        const icon = opt.getAttribute('data-icon');

        hiddenInput.value = val;
        display.innerHTML = `<i class="fas ${icon}" style="color:var(--gold); font-size:.9rem;"></i> ${opt.textContent.trim()}`;

        menu.style.display = 'none';
        chevron.style.transform = 'rotate(0deg)';
      });
    });

    document.addEventListener('click', () => {
      menu.style.display = 'none';
      chevron.style.transform = 'rotate(0deg)';
    });
  }
});
</script>

<?php require_once 'includes/footer.php'; ?>
