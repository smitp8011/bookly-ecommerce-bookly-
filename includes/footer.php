<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="logo" style="margin-bottom:12px;">
          <span class="logo-mark">
            <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="2" y="3" width="7" height="30" rx="2" fill="currentColor" opacity="0.3"/>
              <path d="M9 4.5H20C24.6944 4.5 28.5 8.30558 28.5 13C28.5 16.2731 26.6508 19.1136 23.9554 20.4854C27.5147 21.8485 30 25.2635 30 29.25C30 30.7688 28.7688 32 27.25 32H9V4.5Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
              <path d="M9 18.25H19.5C22.3995 18.25 24.75 15.8995 24.75 13C24.75 10.1005 22.3995 7.75 19.5 7.75H9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
              <path d="M9 32H20.5C23.5376 32 26 29.5376 26 26.5C26 23.4624 23.5376 21 20.5 21H9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
              <path d="M19.5 21C21 23.5 20.5 27 18 29.5C16.8 30.7 15.5 32 15.5 32" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          </span>
          <span class="logo-text">BOOKLY</span>
        </div>
        <p style="max-width:32ch;">An independent online bookshop for readers who still like turning real pages.</p>
      </div>
      <div>
        <h4>Explore</h4>
        <a href="shop.php">Shop</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
      </div>
      <div>
        <h4>Account</h4>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
        <a href="cart.php">Cart</a>
      </div>
      <div>
        <h4>Follow</h4>
        <a href="#"><i class="fab fa-instagram"></i> Instagram</a>
        <a href="#"><i class="fab fa-twitter"></i> Twitter</a>
        <a href="#"><i class="fab fa-goodreads-g"></i> Goodreads</a>
      </div>
    </div>
    <div class="footer-bottom">&copy; <?= date('Y'); ?> Bookly. Built as a major project.</div>
  </div>
</footer>

<!-- Quick View Modal -->
<div id="quickview-modal" class="quickview-modal" aria-hidden="true" role="dialog">
  <div class="quickview-dialog">
    <button type="button" class="quickview-close-btn" id="quickview-close" aria-label="Close">&times;</button>
    <div id="quickview-body"></div>
  </div>
</div>

<!-- Sample Chapter Reader Modal -->
<div id="sample-reader-modal" class="sample-reader-modal" aria-hidden="true" role="dialog">
  <div class="sample-reader-container" data-reader-theme="parchment">
    <div class="reader-header">
      <div class="reader-book-meta">
        <span class="reader-badge">Preview Excerpt</span>
        <h3 id="reader-book-title" class="reader-title">Book Title</h3>
        <span id="reader-book-author" class="reader-author">by Author</span>
      </div>
      <div class="reader-controls">
        <div class="reader-theme-switcher" title="Reading Tone">
          <button type="button" class="theme-dot parchment active" data-rtheme="parchment" title="Parchment"></button>
          <button type="button" class="theme-dot sepia" data-rtheme="sepia" title="Sepia"></button>
          <button type="button" class="theme-dot dark" data-rtheme="dark" title="Obsidian"></button>
        </div>
        <div class="font-size-controls">
          <button type="button" id="font-decrease" title="Smaller font">A-</button>
          <button type="button" id="font-increase" title="Larger font">A+</button>
        </div>
        <button type="button" class="reader-close-btn" id="reader-close" aria-label="Close Reader">&times;</button>
      </div>
    </div>

    <div class="reader-body">
      <div class="reader-page-wrapper" id="reader-page-content">
        <!-- Dynamic Chapter & Content rendered here -->
      </div>
    </div>

    <div class="reader-footer">
      <div class="reader-nav-btns">
        <button type="button" class="btn btn-outline btn-sm" id="reader-prev-btn"><i class="fas fa-chevron-left"></i> Previous</button>
        <span id="reader-progress-indicator" class="reader-progress">Page 1 of 3</span>
        <button type="button" class="btn btn-outline btn-sm" id="reader-next-btn">Next <i class="fas fa-chevron-right"></i></button>
      </div>
      <div class="reader-buy-cta">
        <button type="button" class="btn btn-primary btn-sm add-cart-btn" id="reader-add-cart-btn" data-book-id="" data-cover="">
          <i class="fas fa-cart-plus"></i> Buy Full Book (<span id="reader-book-price">₹0.00</span>)
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Slide-Over Mini Cart Drawer -->
<div id="cart-drawer-overlay" class="cart-drawer-overlay"></div>
<aside id="cart-drawer" class="cart-drawer" aria-hidden="true" role="dialog">
  <div class="cart-drawer-header">
    <div class="drawer-title-row">
      <h3><i class="fas fa-bag-shopping" style="color:var(--gold);"></i> Shopping Bag (<span id="drawer-items-count">0</span>)</h3>
      <button type="button" id="cart-drawer-close" class="drawer-close-btn" aria-label="Close Bag">&times;</button>
    </div>
    <!-- Free Shipping Progress -->
    <div class="free-shipping-bar-wrap">
      <div class="free-shipping-msg" id="free-shipping-msg">Add ₹499 more for FREE Delivery!</div>
      <div class="shipping-progress-track">
        <div class="shipping-progress-fill" id="shipping-progress-fill" style="width: 0%;"></div>
      </div>
    </div>
  </div>

  <div class="cart-drawer-body" id="cart-drawer-items">
    <div class="drawer-empty-state">
      <i class="fas fa-book-open" style="font-size:2.5rem; color:var(--paper-dim); margin-bottom:12px;"></i>
      <p>Your shopping bag is empty.</p>
    </div>
  </div>

  <div class="cart-drawer-footer">
    <div class="drawer-subtotal-row">
      <span>Subtotal:</span>
      <strong id="drawer-subtotal-amt" class="price-tag">₹0.00</strong>
    </div>
    <div class="drawer-actions-stack">
      <a href="checkout.php" id="drawer-checkout-btn" class="btn btn-primary" style="width:100%; text-align:center;">
        <i class="fas fa-lock"></i> Checkout Now
      </a>
      <a href="cart.php" class="btn btn-outline btn-sm" style="width:100%; text-align:center; padding:9px;">
        View & Edit Cart
      </a>
    </div>
  </div>
</aside>

<!-- Photo Lightbox Modal -->
<div id="photo-lightbox-modal" class="photo-lightbox-modal" aria-hidden="true">
  <div class="lightbox-dialog">
    <button type="button" class="lightbox-close-btn" id="lightbox-close">&times;</button>
    <img id="lightbox-img" src="" alt="Customer Photo Review">
    <div id="lightbox-caption" class="lightbox-caption"></div>
  </div>
</div>

<!-- Floating AI Chatbot Widget -->
<div id="ai-chat-bubble" class="ai-chat-bubble" title="Chat with Bookly AI" aria-label="Open AI Book Assistant">
  <div class="ai-bubble-pulse"></div>
  <i class="fas fa-robot"></i>
  <span class="ai-bubble-label">Bookly AI</span>
</div>

<div id="ai-chat-panel" class="ai-chat-panel" aria-hidden="true">
  <div class="ai-chat-header">
    <div class="ai-chat-title-group">
      <div class="ai-avatar-ring">
        <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:20px; height:20px; color:var(--gold);">
          <rect x="2" y="3" width="7" height="30" rx="2" fill="currentColor" opacity="0.3"/>
          <path d="M9 4.5H20C24.6944 4.5 28.5 8.30558 28.5 13C28.5 16.2731 26.6508 19.1136 23.9554 20.4854C27.5147 21.8485 30 25.2635 30 29.25C30 30.7688 28.7688 32 27.25 32H9V4.5Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
          <path d="M9 18.25H19.5C22.3995 18.25 24.75 15.8995 24.75 13C24.75 10.1005 22.3995 7.75 19.5 7.75H9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M9 32H20.5C23.5376 32 26 29.5376 26 26.5C26 23.4624 23.5376 21 20.5 21H9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M19.5 21C21 23.5 20.5 27 18 29.5C16.8 30.7 15.5 32 15.5 32" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
        <span class="ai-online-dot"></span>
      </div>
      <div>
        <h4>Bookly AI Assistant</h4>
        <span class="ai-subtitle">Intelligent Reading Concierge</span>
      </div>
    </div>
    <div class="ai-header-controls">
      <button type="button" id="ai-chat-expand" title="Toggle Half Screen / Standard Size"><i class="fas fa-up-right-and-down-left-from-center"></i></button>
      <button type="button" id="ai-chat-reset" title="Clear conversation"><i class="fas fa-rotate-right"></i></button>
      <button type="button" id="ai-chat-close" title="Close chat">&times;</button>
    </div>
  </div>

  <div class="ai-suggestion-chips" id="ai-suggestion-chips">
    <button type="button" class="ai-chip" data-prompt="Recommend a Fantasy thriller book">✨ Fantasy Picks</button>
    <button type="button" class="ai-chip" data-prompt="Show me popular books under ₹400">💰 Under ₹400</button>
    <button type="button" class="ai-chip" data-prompt="What active discount coupons are available?">🎟️ Coupons</button>
    <button type="button" class="ai-chip" data-prompt="How long does delivery take?">🚚 Delivery Info</button>
  </div>

  <div class="ai-chat-messages" id="ai-chat-messages">
    <div class="ai-msg bot">
      <div class="ai-msg-avatar"><i class="fas fa-robot"></i></div>
      <div class="ai-msg-content">
        <p>👋 Hello! I'm <strong>Bookly AI</strong>, your personal reading concierge.</p>
        <p>Ask me for book recommendations, sample excerpts, order tracking (e.g. <em>#1</em>), or store deals!</p>
      </div>
    </div>
  </div>

  <form id="ai-chat-form" class="ai-chat-input-bar">
    <input type="text" id="ai-user-input" placeholder="Ask about books, genre, orders, discounts..." autocomplete="off" required>
    <button type="submit" id="ai-send-btn" title="Send message"><i class="fas fa-paper-plane"></i></button>
  </form>
</div>

<script src="<?= str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '../js/main.js' : 'js/main.js'; ?>"></script>
</body>
</html>
