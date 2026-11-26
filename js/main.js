// =====================================================
// Bookly — front-end interactivity
// =====================================================

document.addEventListener('DOMContentLoaded', () => {
  initMobileMenu();
  initToasts();
  initScrollReveal();
  initCounters();
  initAddToCart();
  initWishlist();
  initQtyControls();
  initRatingInput();
  initThemeToggle();
  initCoupon();
  initPasswordToggles();
  initProfileTabs();
  initPasswordMatchValidation();
  initLiveSearch();
  initCheckoutPaymentUI();
  initShopLiveFilter();
  initQuickView();
  initAdminInlineEdit();
  initSampleReader();
  initCartDrawer();
  initPhotoLightbox();
  initAiChat();
  initAdminMobileNav();
});

/* ---------- Utility: Escape HTML ---------- */
function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/* ---------- Admin mobile sidebar nav ---------- */
function initAdminMobileNav() {
  const toggleBtn = document.getElementById('admin-menu-toggle');
  const sidebar = document.getElementById('admin-sidebar');
  const backdrop = document.getElementById('admin-sidebar-backdrop');
  if (!toggleBtn || !sidebar) return;

  const toggle = () => {
    sidebar.classList.toggle('open');
    if (backdrop) backdrop.classList.toggle('show');
  };

  const close = () => {
    sidebar.classList.remove('open');
    if (backdrop) backdrop.classList.remove('show');
  };

  toggleBtn.addEventListener('click', toggle);
  if (backdrop) backdrop.addEventListener('click', close);
}

/* ---------- Mobile nav ---------- */
function initMobileMenu() {
  const btn = document.getElementById('menu-btn');
  const nav = document.querySelector('.navbar');
  if (!btn || !nav) return;
  btn.addEventListener('click', () => nav.classList.toggle('show'));
}

/* ---------- Toasts ---------- */
function ensureToastStack() {
  let stack = document.getElementById('toast-stack');
  if (!stack) {
    stack = document.createElement('div');
    stack.id = 'toast-stack';
    document.body.appendChild(stack);
  }
  return stack;
}

function showToast(message, type = 'success') {
  const stack = ensureToastStack();
  const toast = document.createElement('div');
  toast.className = 'toast' + (type === 'error' ? ' error' : '');
  toast.innerHTML = `<i class="fas fa-${type === 'error' ? 'circle-exclamation' : 'circle-check'}"></i><span></span><i class="fas fa-times close-toast"></i>`;
  toast.querySelector('span').textContent = message;
  stack.appendChild(toast);

  const remove = () => {
    toast.classList.add('hide');
    setTimeout(() => toast.remove(), 300);
  };
  toast.querySelector('.close-toast').addEventListener('click', remove);
  setTimeout(remove, 3200);
}

function initToasts() {
  document.querySelectorAll('.message[data-msg]').forEach(el => {
    showToast(el.dataset.msg, el.dataset.type || 'success');
    el.remove();
  });
}

/* ---------- Scroll reveal ---------- */
function initScrollReveal() {
  const items = document.querySelectorAll('.reveal');
  if (!items.length) return;
  const io = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in');
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });
  items.forEach(el => io.observe(el));
}

/* ---------- Animated counters ---------- */
function initCounters() {
  document.querySelectorAll('.counter[data-target]').forEach(el => {
    const target = parseInt(el.dataset.target, 10) || 0;
    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        io.unobserve(el);
        let cur = 0;
        const step = Math.max(1, Math.ceil(target / 60));
        const tick = () => {
          cur += step;
          if (cur >= target) { el.textContent = target.toLocaleString(); return; }
          el.textContent = cur.toLocaleString();
          requestAnimationFrame(tick);
        };
        tick();
      });
    }, { threshold: 0.4 });
    io.observe(el);
  });
}

/* ---------- Cart badge ---------- */
function setCartBadge(n) {
  document.querySelectorAll('.cart-badge').forEach(b => b.textContent = n);
}

/* ---------- Add to cart (AJAX + fly animation) ---------- */
function initAddToCart() {
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.add-cart-btn');
    if (!btn) return;
    e.preventDefault();
    const bookId = btn.dataset.bookId;
    const cover = btn.dataset.cover;
    if (btn.disabled) return;

    flyToCart(cover, btn);

    try {
      const res = await fetch('ajax/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&book_id=${encodeURIComponent(bookId)}`
      });
      const data = await res.json();
      if (data.ok) {
        btn.classList.add('added');
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Added';
        setTimeout(() => { btn.innerHTML = original; btn.classList.remove('added'); }, 1400);
        setCartBadge(data.cart_count);
        showToast('Added to cart');
      } else {
        showToast(data.message || 'Could not add to cart', 'error');
        if (data.login_required) setTimeout(() => window.location.href = 'login.php', 900);
      }
    } catch (err) {
      showToast('Network error — try again', 'error');
    }
  });
}

function flyToCart(coverSrc, originEl) {
  const cartIcon = document.querySelector('.cart-icon-target');
  if (!cartIcon || !coverSrc) return;
  const startRect = originEl.getBoundingClientRect();
  const endRect = cartIcon.getBoundingClientRect();

  const ghost = document.createElement('img');
  ghost.src = coverSrc;
  ghost.className = 'fly-ghost';
  ghost.style.left = startRect.left + startRect.width / 2 - 23 + 'px';
  ghost.style.top = startRect.top + 'px';
  ghost.style.opacity = '0.95';
  document.body.appendChild(ghost);

  requestAnimationFrame(() => {
    ghost.style.left = endRect.left + endRect.width / 2 - 23 + 'px';
    ghost.style.top = endRect.top + 'px';
    ghost.style.transform = 'scale(.2) rotate(20deg)';
    ghost.style.opacity = '0.2';
  });
  setTimeout(() => ghost.remove(), 750);
}

/* ---------- Wishlist toggle ---------- */
function initWishlist() {
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.wish-btn');
    if (!btn) return;
    e.preventDefault();
    const bookId = btn.dataset.bookId;
    try {
      const res = await fetch('ajax/wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `book_id=${encodeURIComponent(bookId)}`
      });
      const data = await res.json();
      if (data.ok) {
        btn.classList.toggle('active', data.wishlisted);
        showToast(data.wishlisted ? 'Added to wishlist' : 'Removed from wishlist');
      } else if (data.login_required) {
        showToast('Log in to use your wishlist', 'error');
        setTimeout(() => window.location.href = 'login.php', 900);
      }
    } catch (err) {
      showToast('Network error — try again', 'error');
    }
  });
}

/* ---------- Cart quantity +/- (cart page) ---------- */
function initQtyControls() {
  document.querySelectorAll('.qty-control').forEach(control => {
    const bookId = control.dataset.bookId;
    const input = control.querySelector('.qty-input');
    const minus = control.querySelector('.qty-minus');
    const plus = control.querySelector('.qty-plus');

    if (input) {
      input.dataset.lastQty = input.value;
    }

    const update = async (delta) => {
      if (delta === 0) return; 
      
      const res = await fetch('ajax/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update&book_id=${bookId}&delta=${delta}`
      });
      const data = await res.json();
      
      if (data.ok) {
        if (data.removed) {
          control.closest('.cart-item').remove();
        } else {
          if (input) {
            input.value = data.quantity;
            input.dataset.lastQty = data.quantity;
          }
        }
        setCartBadge(data.cart_count);
        document.dispatchEvent(new CustomEvent('cart:updated', { detail: data }));
      }
    };

    minus?.addEventListener('click', () => update(-1));
    
    // Yahan limit check add kiya hai
    plus?.addEventListener('click', () => {
      if(input) {
        let currentQty = parseInt(input.value, 10) || 1;
        let maxStock = parseInt(input.max, 10) || 999;
        if (currentQty >= maxStock) {
          showToast('Only ' + maxStock + ' left in stock', 'error');
          return;
        }
      }
      update(1);
    });

    if (input) {
      input.addEventListener('change', () => {
        let newQty = parseInt(input.value, 10);
        let oldQty = parseInt(input.dataset.lastQty, 10) || 1;
        let maxStock = parseInt(input.max, 10) || 999;

        if (isNaN(newQty) || newQty < 1) {
          newQty = 1;
          input.value = 1;
        } 
        // Yahan limit check add kiya hai
        else if (newQty > maxStock) {
          newQty = maxStock;
          input.value = maxStock;
          showToast('Only ' + maxStock + ' left in stock', 'error');
        }

        const delta = newQty - oldQty;
        update(delta);
      });
    }
  });

  document.querySelectorAll('.remove-link').forEach(link => {
    link.addEventListener('click', async (e) => {
      e.preventDefault();
      const bookId = link.dataset.bookId;
      const res = await fetch('ajax/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=remove&book_id=${bookId}`
      });
      const data = await res.json();
      if (data.ok) {
        link.closest('.cart-item').remove();
        setCartBadge(data.cart_count);
        document.dispatchEvent(new CustomEvent('cart:updated', { detail: data }));
        showToast('Removed from cart');
      }
    });
  });

  document.addEventListener('cart:updated', (e) => {
    const { subtotal, total, count } = e.detail;
    const subtotalEl = document.getElementById('cart-subtotal');
    const totalEl = document.getElementById('cart-total');
    const countEl = document.getElementById('cart-item-count');
    if (subtotalEl) subtotalEl.textContent = subtotal;
    if (totalEl) totalEl.textContent = total || subtotal;
    if (countEl) countEl.textContent = count;
    if (count === 0) {
      const list = document.getElementById('cart-list');
      if (list) list.innerHTML = '<div class="empty-state"><i class="fas fa-book-open"></i><h3>Your cart is empty</h3><p>Browse the shop to find your next read.</p><a href="shop.php" class="btn btn-primary" style="margin-top:16px;">Browse Books</a></div>';
      const summary = document.querySelector('.summary-card');
      if (summary) summary.style.display = 'none';
    }
  });
}

/* ---------- Star rating input on the review form ---------- */
function initRatingInput() {
  const wrap = document.querySelector('.rating-input');
  if (!wrap) return;
  const stars = wrap.querySelectorAll('i');
  const hidden = document.getElementById('rating-value');
  const label = document.getElementById('rating-text');

  const ratingDescriptions = {
    1: '1 Star — Poor',
    2: '2 Stars — Fair',
    3: '3 Stars — Good',
    4: '4 Stars — Very Good',
    5: '5 Stars — Excellent!'
  };

  stars.forEach(star => {
    // Hover preview
    star.addEventListener('mouseenter', () => {
      const val = parseInt(star.dataset.val, 10);
      stars.forEach(s => s.classList.toggle('hover', parseInt(s.dataset.val, 10) <= val));
      if (label) {
        label.textContent = ratingDescriptions[val] || '';
      }
    });

    // Click select
    star.addEventListener('click', () => {
      const val = parseInt(star.dataset.val, 10);
      if (hidden) hidden.value = val;
      stars.forEach(s => {
        s.classList.toggle('active', parseInt(s.dataset.val, 10) <= val);
        s.classList.remove('hover');
      });
      if (label) {
        label.textContent = ratingDescriptions[val] || '';
        label.style.color = 'var(--gold)';
        label.style.fontWeight = '700';
      }
      const card = document.querySelector('.star-rating-selector-card');
      if (card) card.style.border = '1px solid rgba(201,162,75,.6)';
    });
  });

  wrap.addEventListener('mouseleave', () => {
    stars.forEach(s => s.classList.remove('hover'));
    const currentVal = hidden ? parseInt(hidden.value, 10) : 0;
    if (label) {
      label.textContent = currentVal ? ratingDescriptions[currentVal] : '(Click to give star rating)';
    }
  });

  // Client-side submit validation so user never wonders why rating wasn't given
  const form = document.getElementById('review-submission-form');
  if (form) {
    form.addEventListener('submit', (e) => {
      const currentVal = hidden ? parseInt(hidden.value, 10) : 0;
      if (!currentVal || currentVal < 1) {
        e.preventDefault();
        const card = document.querySelector('.star-rating-selector-card');
        if (card) {
          card.style.border = '2px solid var(--danger)';
          card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        alert('Please click on the stars to select your rating (1 to 5 stars) before submitting.');
      }
    });
  }
}
/* ---------- Dark mode toggle ---------- */
function initThemeToggle() {
  const btns = document.querySelectorAll('.theme-toggle, #theme-toggle');
  if (!btns.length) return;

  const updateUI = (isDark) => {
    document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
    btns.forEach(btn => {
      const icon = btn.querySelector('i');
      if (icon) {
        icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
      }
      btn.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
    });
  };

  const saved = localStorage.getItem('bookly-theme');
  const isDark = saved === 'dark';
  updateUI(isDark);

  btns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const currentDark = document.documentElement.getAttribute('data-theme') === 'dark';
      const nextDark = !currentDark;
      updateUI(nextDark);
      localStorage.setItem('bookly-theme', nextDark ? 'dark' : 'light');
      showToast(nextDark ? '🌙 Obsidian Dark Mode' : '☀️ Warm Paper Mode');
    });
  });
}

/* ---------- Coupon box (cart page) ---------- */
function initCoupon() {
  const form = document.getElementById('coupon-form');
  if (!form) return;
  const input = document.getElementById('coupon-input');
  const msgEl = document.getElementById('coupon-msg');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const code = input.value.trim();
    if (!code) return;
    try {
      const res = await fetch('ajax/coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=apply&code=${encodeURIComponent(code)}`
      });
      const data = await res.json();
      if (data.ok) {
        showToast('Coupon applied: ' + data.message);
        window.location.reload();
      } else {
        msgEl.textContent = data.message;
        msgEl.style.color = 'var(--danger)';
      }
    } catch (err) {
      showToast('Network error — try again', 'error');
    }
  });

  document.querySelectorAll('.remove-coupon').forEach(btn => {
    btn.addEventListener('click', async () => {
      await fetch('ajax/coupon.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=remove'
      });
      window.location.reload();
    });
  });
}

/* ---------- Password visibility toggle ---------- */
function initPasswordToggles() {
  document.querySelectorAll('.password-toggle').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = btn.dataset.target;
      const input = targetId ? document.getElementById(targetId) : btn.previousElementSibling;
      if (!input) return;
      const icon = btn.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        if (icon) { icon.className = 'fas fa-eye-slash'; }
        btn.setAttribute('title', 'Hide password');
      } else {
        input.type = 'password';
        if (icon) { icon.className = 'fas fa-eye'; }
        btn.setAttribute('title', 'Show password');
      }
    });
  });
}

/* ---------- Profile page tabs ---------- */
function initProfileTabs() {
  const tabs = document.querySelectorAll('.profile-tab-btn');
  if (!tabs.length) return;
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const targetId = tab.dataset.tab;
      tabs.forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      tab.classList.add('active');
      const targetContent = document.getElementById(targetId);
      if (targetContent) targetContent.classList.add('active');
    });
  });
}

/* ---------- Real-time Password Match Validation ---------- */
function initPasswordMatchValidation() {
  const pass = document.getElementById('reg-password') || document.getElementById('new-password');
  const cpass = document.getElementById('reg-cpassword') || document.getElementById('confirm-password');
  const indicator = document.getElementById('password-match-msg');
  if (!pass || !cpass || !indicator) return;

  const validate = () => {
    const pVal = pass.value;
    const cpVal = cpass.value;
    if (!cpVal) {
      indicator.textContent = '';
      indicator.className = 'password-match-indicator';
      return;
    }
    if (pVal === cpVal) {
      indicator.textContent = '✓ Passwords match';
      indicator.className = 'password-match-indicator match';
    } else {
      indicator.textContent = '✕ Passwords do not match';
      indicator.className = 'password-match-indicator mismatch';
    }
  };

  pass.addEventListener('input', validate);
  cpass.addEventListener('input', validate);
}

/* ---------- Live Header Search ---------- */
function initLiveSearch() {
  const input = document.getElementById('header-search-input');
  const dropdown = document.getElementById('header-search-dropdown');
  if (!input || !dropdown) return;

  let debounceTimer;

  input.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const q = input.value.trim();
    if (q.length < 2) {
      dropdown.innerHTML = '';
      dropdown.classList.remove('show');
      return;
    }

    debounceTimer = setTimeout(async () => {
      try {
        const res = await fetch(`ajax/search.php?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (data.ok && data.results.length > 0) {
          const fuzzyBanner = data.is_fuzzy ? `
            <div style="padding:7px 12px; background:rgba(201,162,75,0.15); font-size:.78rem; font-weight:700; color:var(--gold); border-bottom:1px solid rgba(201,162,75,0.25); display:flex; align-items:center; gap:6px;">
              <i class="fas fa-wand-magic-sparkles"></i> Closest matches ${data.suggested_term ? `(Did you mean: <em>${data.suggested_term}</em>?)` : ''}
            </div>
          ` : '';

          dropdown.innerHTML = fuzzyBanner + data.results.map(b => `
            <a href="${b.url}" class="search-item">
              <img src="${b.cover}" alt="${b.title}">
              <div class="search-item-info">
                <div class="search-item-title">${b.title}</div>
                <div class="search-item-sub">by ${b.author} · ${b.category}</div>
              </div>
              <div class="search-item-price">${b.price}</div>
            </a>
          `).join('') + `
            <a href="shop.php?search=${encodeURIComponent(data.suggested_term || q)}" class="search-view-all">
              View all results for "${data.suggested_term || q}" <i class="fas fa-arrow-right"></i>
            </a>
          `;
          dropdown.classList.add('show');
        } else {
          dropdown.innerHTML = `
            <div style="padding:16px; text-align:center; color:#888; font-size:.85rem;">
              No books found for "<strong>${q}</strong>"
            </div>
          `;
          dropdown.classList.add('show');
        }
      } catch (e) {
        console.error('Search error', e);
      }
    }, 250);
  });

  // Close dropdown on click outside
  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.remove('show');
    }
  });

  input.addEventListener('focus', () => {
    if (input.value.trim().length >= 2 && dropdown.children.length > 0) {
      dropdown.classList.add('show');
    }
  });
}

/* ---------- Checkout Interactive Payment UI ---------- */
function initCheckoutPaymentUI() {
  const cards = document.querySelectorAll('.payment-method-card');
  const panels = document.querySelectorAll('.payment-tab-panel');
  const hiddenInput = document.getElementById('selected-payment-method');
  if (!cards.length || !hiddenInput) return;

  cards.forEach(card => {
    card.addEventListener('click', () => {
      const method = card.dataset.method;
      cards.forEach(c => c.classList.remove('active'));
      panels.forEach(p => p.classList.remove('active'));

      card.classList.add('active');
      hiddenInput.value = method;

      const targetPanel = document.getElementById(`panel-${method.toLowerCase().replace(/\s+/g, '-')}`);
      if (targetPanel) targetPanel.classList.add('active');
    });
  });

  // Card Simulator typing bindings
  const numInput = document.getElementById('card-num-input');
  const numDisplay = document.getElementById('card-num-display');
  if (numInput && numDisplay) {
    numInput.addEventListener('input', (e) => {
      let val = e.target.value.replace(/\D/g, '').substring(0, 16);
      let formatted = val.match(/.{1,4}/g)?.join(' ') || '';
      e.target.value = formatted;
      numDisplay.textContent = formatted || '•••• •••• •••• ••••';
    });
  }

  const nameInput = document.getElementById('card-name-input');
  const nameDisplay = document.getElementById('card-name-display');
  if (nameInput && nameDisplay) {
    nameInput.addEventListener('input', (e) => {
      nameDisplay.textContent = e.target.value.toUpperCase() || 'YOUR NAME';
    });
  }

  // UPI Timer
  const timerEl = document.getElementById('upi-countdown');
  if (timerEl) {
    let timeLeft = 300; // 5 minutes
    setInterval(() => {
      if (timeLeft <= 0) {
        timerEl.textContent = '00:00 (Expired - Refresh)';
        return;
      }
      const m = Math.floor(timeLeft / 60);
      const s = timeLeft % 60;
      timerEl.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
      timeLeft--;
    }, 1000);
  }
}

/* ---------- Shop Page Live Real-time AJAX Filter ---------- */
function initShopLiveFilter() {
  const searchInput = document.getElementById('shop-search-input');
  const searchForm = document.getElementById('shop-search-form');
  const gridContainer = document.getElementById('shop-grid-container');
  const countBadge = document.getElementById('books-found-count');
  const priceSlider = document.getElementById('price-range-slider');
  const priceDisplay = document.getElementById('price-display');
  const rangeValNum = document.getElementById('range-val-num');
  const sortSelect = document.getElementById('shop-sort-select');
  const checkboxes = document.querySelectorAll('.genre-checkbox');

  if (!gridContainer) return;

  let debounceTimer;

  const performFilter = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(async () => {
      const q = searchInput ? searchInput.value.trim() : '';
      const sort = sortSelect ? sortSelect.value : 'newest';
      const maxPrice = priceSlider ? priceSlider.value : 700;
      
      const selectedCats = [];
      document.querySelectorAll('.genre-checkbox:checked').forEach(cb => {
        selectedCats.push(cb.value);
      });

      const params = new URLSearchParams();
      params.set('ajax', '1');
      if (q) params.set('search', q);
      if (sort) params.set('sort', sort);
      if (maxPrice) params.set('max_price', maxPrice);
      selectedCats.forEach(c => params.append('category[]', c));

      gridContainer.style.opacity = '0.4';
      gridContainer.style.transition = 'opacity .15s ease';

      try {
        const res = await fetch('shop.php?' + params.toString());
        const data = await res.json();
        if (data.ok) {
          gridContainer.innerHTML = data.html;
          if (countBadge) countBadge.textContent = data.count_text;
          
          // Update URL without page reload
          const browserParams = new URLSearchParams(params);
          browserParams.delete('ajax');
          const newUrl = 'shop.php' + (browserParams.toString() ? '?' + browserParams.toString() : '');
          window.history.replaceState({}, '', newUrl);

          // Update hidden filter search value
          const hSearch = document.getElementById('filter-search-val');
          if (hSearch) hSearch.value = q;
        }
      } catch (e) {
        console.error('Shop filter error', e);
      } finally {
        gridContainer.style.opacity = '1';
      }
    }, 200);
  };

  // Prevent default form submit and use AJAX
  if (searchForm) {
    searchForm.addEventListener('submit', (e) => {
      e.preventDefault();
      performFilter();
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', performFilter);
  }

  if (priceSlider) {
    priceSlider.addEventListener('input', (e) => {
      if (priceDisplay) priceDisplay.textContent = '₹' + e.target.value;
      if (rangeValNum) rangeValNum.textContent = '₹' + e.target.value;
      performFilter();
    });
  }

  if (sortSelect) {
    sortSelect.addEventListener('change', performFilter);
  }

  checkboxes.forEach(cb => {
    cb.addEventListener('change', performFilter);
  });
}

/* ---------- Quick View Modal Handler ---------- */
function initQuickView() {
  const modal = document.getElementById('quickview-modal');
  const modalBody = document.getElementById('quickview-body');
  const closeBtn = document.getElementById('quickview-close');
  if (!modal || !modalBody) return;

  const closeModal = () => {
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  const openModal = () => {
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  if (closeBtn) {
    closeBtn.addEventListener('click', closeModal);
  }

  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('show')) closeModal();
  });

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.quickview-btn');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();

    const bookId = btn.dataset.bookId;
    if (!bookId) return;

    modalBody.innerHTML = `
      <div style="padding:60px 20px; text-align:center; color:var(--wine);">
        <i class="fas fa-spinner fa-spin" style="font-size:2.2rem; margin-bottom:12px;"></i>
        <div style="font-size:.9rem; font-weight:600;">Loading book details...</div>
      </div>
    `;
    openModal();

    try {
      const res = await fetch(`ajax/book_preview.php?id=${encodeURIComponent(bookId)}`);
      const data = await res.json();
      if (!data.ok || !data.book) {
        modalBody.innerHTML = `
          <div style="padding:40px 20px; text-align:center; color:#888;">
            <i class="fas fa-circle-exclamation" style="font-size:2rem; color:var(--danger); margin-bottom:10px;"></i>
            <p>Could not load preview.</p>
          </div>
        `;
        return;
      }

      const b = data.book;
      let starsHtml = '';
      const r = Math.round(b.avg_rating);
      for (let i = 1; i <= 5; i++) {
        starsHtml += `<i class="fas fa-star" style="${i <= r ? 'color:var(--gold);' : 'color:var(--paper-dim);'}"></i>`;
      }

      modalBody.innerHTML = `
        <div class="quickview-grid">
          <div class="quickview-cover">
            <img src="${b.cover}" alt="${b.title}">
          </div>
          <div class="quickview-info">
            <span class="eyebrow">${b.category}</span>
            <h2>${b.title}</h2>
            <div class="author">by ${b.author}</div>
            
            <div class="meta">
              <span class="stars">${starsHtml}</span>
              <span class="stock-badge">(${b.total_reviews} reviews)</span>
              <span class="stock-badge ${b.stock <= 5 && b.stock > 0 ? 'low' : ''}">
                ${b.stock > 0 ? (b.stock <= 5 ? 'Only ' + b.stock + ' left' : 'In stock') : 'Out of stock'}
              </span>
              ${b.is_purchased ? '<span class="stock-badge" style="color:var(--sage); font-weight:700;"><i class="fas fa-circle-check"></i> Bought</span>' : ''}
            </div>

            <div class="price">${b.price}</div>
            <div class="desc">${b.description ? b.description.replace(/\\n/g, '<br>') : 'No description provided.'}</div>

            <div class="quickview-actions">
              <button class="add-cart-btn" data-book-id="${b.id}" data-cover="${b.cover}" ${b.stock == 0 ? 'disabled' : ''} style="flex:1;">
                <i class="fas fa-cart-plus"></i> ${b.stock == 0 ? 'Unavailable' : 'Add to cart'}
              </button>
              <button type="button" class="btn btn-gold btn-sm btn-sample-reader" data-book-id="${b.id}" style="padding:10px 14px;" title="Read Free Sample">
                <i class="fas fa-book-open"></i> Sample
              </button>
              <button class="wish-btn ${b.is_wishlisted ? 'active' : ''}" data-book-id="${b.id}" style="width:44px; height:44px; position:static;" title="Toggle Wishlist">
                <i class="fas fa-heart"></i>
              </button>
              <a href="${b.url}" class="btn btn-outline btn-sm" style="padding:10px 16px;">
                Details <i class="fas fa-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>
      `;
    } catch (err) {
      console.error('Quick view error', err);
      modalBody.innerHTML = '<div style="padding:40px; text-align:center;">Failed to load book preview.</div>';
    }
  });
}

/* ---------- Admin Inline Fast Price/Stock Editor ---------- */
function initAdminInlineEdit() {
  const inputs = document.querySelectorAll('.inline-editable');
  if (!inputs.length) return;

  inputs.forEach(input => {
    let originalVal = input.value;

    const saveChange = async () => {
      const newVal = input.value.trim();
      if (newVal === originalVal) return;

      const bookId = input.dataset.id;
      const field = input.dataset.field;
      const csrf = getCsrf();

      input.style.opacity = '0.5';

      try {
        const res = await fetch('../ajax/admin_quick_update.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `csrf=${encodeURIComponent(csrf)}&book_id=${encodeURIComponent(bookId)}&field=${encodeURIComponent(field)}&value=${encodeURIComponent(newVal)}`
        });
        const data = await res.json();
        if (data.ok) {
          originalVal = newVal;
          input.style.borderColor = 'var(--sage)';
          input.style.background = 'rgba(92,110,90,.15)';
          setTimeout(() => {
            input.style.borderColor = '';
            input.style.background = '';
          }, 1200);
          showToast(`Book #${bookId} ${field} saved: ${newVal}`);
        } else {
          input.value = originalVal;
          input.style.borderColor = 'var(--danger)';
          showToast(data.message || 'Update failed', 'error');
        }
      } catch (err) {
        console.error(err);
        input.value = originalVal;
        showToast('Network error', 'error');
      } finally {
        input.style.opacity = '1';
      }
    };

    input.addEventListener('change', saveChange);
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        input.blur();
      }
    });
  });
}

/* ---------- Sample Chapter Reader Modal ---------- */
function initSampleReader() {
  const modal = document.getElementById('sample-reader-modal');
  if (!modal) return;

  const container = modal.querySelector('.sample-reader-container');
  const titleEl = document.getElementById('reader-book-title');
  const authorEl = document.getElementById('reader-book-author');
  const priceEl = document.getElementById('reader-book-price');
  const contentEl = document.getElementById('reader-page-content');
  const progressEl = document.getElementById('reader-progress-indicator');
  const prevBtn = document.getElementById('reader-prev-btn');
  const nextBtn = document.getElementById('reader-next-btn');
  const addCartBtn = document.getElementById('reader-add-cart-btn');
  const closeBtn = document.getElementById('reader-close');
  const themeDots = modal.querySelectorAll('.theme-dot');
  const fontDec = document.getElementById('font-decrease');
  const fontInc = document.getElementById('font-increase');

  let currentPages = [];
  let currentPageIndex = 0;
  let currentFontSize = 1.08;

  const renderPage = (idx) => {
    if (!currentPages || !currentPages[idx]) return;
    const page = currentPages[idx];
    const paras = page.content.split(/\n\s*\n/).filter(p => p.trim().length > 0);
    contentEl.innerHTML = `
      <h2>${escapeHtml(page.chapter)}</h2>
      <h4>${escapeHtml(page.subtitle || '')}</h4>
      ${paras.map(p => `<p>${escapeHtml(p)}</p>`).join('')}
    `;
    progressEl.textContent = `Page ${idx + 1} of ${currentPages.length}`;
    prevBtn.disabled = idx === 0;
    nextBtn.disabled = idx === currentPages.length - 1;
    prevBtn.style.opacity = idx === 0 ? '0.4' : '1';
    nextBtn.style.opacity = idx === currentPages.length - 1 ? '0.4' : '1';
    contentEl.scrollTop = 0;
  };

  const openReader = async (bookId) => {
    contentEl.innerHTML = '<div style="text-align:center; padding:80px;"><i class="fas fa-spinner fa-spin" style="font-size:2rem; color:var(--gold);"></i><p style="margin-top:12px;">Opening sample pages...</p></div>';
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';

    try {
      const ajaxBase = window.location.pathname.includes('/admin/') ? '../ajax/' : 'ajax/';
      const res = await fetch(`${ajaxBase}sample_chapter.php?id=${encodeURIComponent(bookId)}`);
      const data = await res.json();
      if (!data.ok || !data.book) {
        contentEl.innerHTML = `<p style="text-align:center; color:var(--danger);">${data.message || 'Could not load excerpt.'}</p>`;
        return;
      }
      const b = data.book;
      if (titleEl) titleEl.textContent = b.title;
      if (authorEl) authorEl.textContent = `by ${b.author}`;
      if (priceEl) priceEl.textContent = b.price;
      if (addCartBtn) {
        addCartBtn.dataset.bookId = b.id;
        addCartBtn.dataset.cover = b.cover;
      }
      currentPages = b.pages || [];
      currentPageIndex = 0;
      renderPage(0);
    } catch (e) {
      console.error('Sample reader error', e);
      contentEl.innerHTML = `<p style="text-align:center; color:var(--danger); padding:40px;">Error loading sample: ${escapeHtml(e.message)}</p>`;
    }
  };

  const closeReader = () => {
    modal.classList.remove('show');
    document.body.style.overflow = '';
  };

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-sample-reader');
    if (btn) {
      e.preventDefault();
      const bookId = btn.dataset.bookId;
      if (bookId) openReader(bookId);
    }
  });

  if (prevBtn) prevBtn.addEventListener('click', () => { if (currentPageIndex > 0) { currentPageIndex--; renderPage(currentPageIndex); } });
  if (nextBtn) nextBtn.addEventListener('click', () => { if (currentPageIndex < currentPages.length - 1) { currentPageIndex++; renderPage(currentPageIndex); } });
  if (closeBtn) closeBtn.addEventListener('click', closeReader);
  modal.addEventListener('click', (e) => { if (e.target === modal) closeReader(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.classList.contains('show')) closeReader(); });

  themeDots.forEach(dot => {
    dot.addEventListener('click', () => {
      themeDots.forEach(d => d.classList.remove('active'));
      dot.classList.add('active');
      container.setAttribute('data-reader-theme', dot.dataset.rtheme);
    });
  });

  if (fontInc) fontInc.addEventListener('click', () => {
    if (currentFontSize < 1.45) {
      currentFontSize += 0.1;
      contentEl.style.fontSize = currentFontSize + 'rem';
    }
  });
  if (fontDec) fontDec.addEventListener('click', () => {
    if (currentFontSize > 0.85) {
      currentFontSize -= 0.1;
      contentEl.style.fontSize = currentFontSize + 'rem';
    }
  });
}

/* ---------- Slide-Over Mini Cart Drawer ---------- */
function initCartDrawer() {
  const drawer = document.getElementById('cart-drawer');
  const overlay = document.getElementById('cart-drawer-overlay');
  const closeBtn = document.getElementById('cart-drawer-close');
  const cartIconTargets = document.querySelectorAll('.cart-icon-target, .site-header .icons a[href="cart.php"]');
  const itemsContainer = document.getElementById('cart-drawer-items');
  const countSpan = document.getElementById('drawer-items-count');
  const subtotalEl = document.getElementById('drawer-subtotal-amt');
  const shippingMsg = document.getElementById('free-shipping-msg');
  const shippingFill = document.getElementById('shipping-progress-fill');

  if (!drawer || !overlay) return;

  const openDrawer = async () => {
    drawer.classList.add('show');
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
    await fetchAndRenderDrawer();
  };

  const closeDrawer = () => {
    drawer.classList.remove('show');
    overlay.classList.remove('show');
    document.body.style.overflow = '';
  };

  const fetchAndRenderDrawer = async () => {
    try {
      const ajaxBase = window.location.pathname.includes('/admin/') ? '../ajax/' : 'ajax/';
      const res = await fetch(`${ajaxBase}cart.php?action=get`);
      const data = await res.json();
      if (!data.ok) return;

      if (countSpan) countSpan.textContent = data.cart_count || 0;
      if (subtotalEl) subtotalEl.textContent = data.subtotal || '₹0.00';

      // Update badge
      const badges = document.querySelectorAll('.cart-badge');
      badges.forEach(b => b.textContent = data.cart_count || 0);

      // Free shipping threshold (₹499)
      const rawSub = data.raw_subtotal || 0;
      const threshold = 499;
      if (shippingMsg && shippingFill) {
        if (rawSub >= threshold && rawSub > 0) {
          shippingMsg.className = 'free-shipping-msg unlocked';
          shippingMsg.innerHTML = '<i class="fas fa-circle-check"></i> You unlocked <strong>FREE Delivery</strong>!';
          shippingFill.style.width = '100%';
        } else {
          const remaining = (threshold - rawSub).toFixed(2);
          shippingMsg.className = 'free-shipping-msg';
          shippingMsg.innerHTML = `Add <strong>₹${remaining}</strong> more for <strong>FREE Delivery</strong>!`;
          const pct = Math.min(100, Math.round((rawSub / threshold) * 100));
          shippingFill.style.width = pct + '%';
        }
      }

      // Render items
      if (!data.items || data.items.length === 0) {
        itemsContainer.innerHTML = `
          <div class="drawer-empty-state">
            <i class="fas fa-bag-shopping" style="font-size:2.5rem; color:var(--paper-dim); margin-bottom:12px; display:block;"></i>
            <p>Your shopping bag is empty.</p>
            <a href="shop.php" class="btn btn-primary btn-sm" style="margin-top:14px;" onclick="document.getElementById('cart-drawer-close').click();">Explore Books</a>
          </div>
        `;
        return;
      }

      itemsContainer.innerHTML = data.items.map(item => `
        <div class="drawer-item" data-book-id="${item.id}">
          <img src="${escapeHtml(item.cover)}" alt="${escapeHtml(item.title)}">
          <div>
            <div class="drawer-item-title">${escapeHtml(item.title)}</div>
            <div class="drawer-item-price">${escapeHtml(item.formatted_price)}</div>
            <div class="drawer-item-qty">
              <button type="button" class="drawer-qty-btn drawer-qty-minus" data-book-id="${item.id}">-</button>
              <span class="drawer-qty-val">${item.quantity}</span>
              <button type="button" class="drawer-qty-btn drawer-qty-plus" data-book-id="${item.id}">+</button>
            </div>
          </div>
          <button type="button" class="drawer-remove-btn" data-book-id="${item.id}" title="Remove item">
            <i class="fas fa-trash-can"></i>
          </button>
        </div>
      `).join('');

    } catch (e) {
      console.error('Drawer cart load error', e);
    }
  };

  // Intercept header cart clicks
  cartIconTargets.forEach(target => {
    if (!window.location.pathname.endsWith('cart.php') && !window.location.pathname.endsWith('checkout.php')) {
      target.addEventListener('click', (e) => {
        e.preventDefault();
        openDrawer();
      });
    }
  });

  if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
  overlay.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && drawer.classList.contains('show')) closeDrawer(); });

  // Delegated item qty +/- & remove in drawer
  itemsContainer.addEventListener('click', async (e) => {
    const plus = e.target.closest('.drawer-qty-plus');
    const minus = e.target.closest('.drawer-qty-minus');
    const remove = e.target.closest('.drawer-remove-btn');

    if (plus) {
      const id = plus.dataset.bookId;
      await updateDrawerQty(id, 'add');
    } else if (minus) {
      const id = minus.dataset.bookId;
      await updateDrawerQty(id, 'minus');
    } else if (remove) {
      const id = remove.dataset.bookId;
      await updateDrawerQty(id, 'remove');
    }
  });

  const updateDrawerQty = async (bookId, action) => {
    try {
      const csrf = getCsrf();
      const ajaxBase = window.location.pathname.includes('/admin/') ? '../ajax/' : 'ajax/';
      const res = await fetch(`${ajaxBase}cart.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `csrf=${encodeURIComponent(csrf)}&book_id=${encodeURIComponent(bookId)}&action=${encodeURIComponent(action)}`
      });
      const data = await res.json();
      if (data.ok) {
        await fetchAndRenderDrawer();
      } else {
        showToast(data.message || 'Action failed', 'error');
      }
    } catch (err) {
      console.error(err);
    }
  };

  // Expose global open drawer helper for Add to Cart
  window.openCartDrawer = openDrawer;
}

/* ---------- Photo Lightbox Modal ---------- */
function initPhotoLightbox() {
  const modal = document.getElementById('photo-lightbox-modal');
  const imgEl = document.getElementById('lightbox-img');
  const captionEl = document.getElementById('lightbox-caption');
  const closeBtn = document.getElementById('lightbox-close');
  if (!modal || !imgEl) return;

  const openLightbox = (src, caption) => {
    imgEl.src = src;
    captionEl.textContent = caption || '';
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
  };

  const closeLightbox = () => {
    modal.classList.remove('show');
    document.body.style.overflow = '';
  };

  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('.photo-lightbox-trigger');
    if (trigger) {
      e.preventDefault();
      const src = trigger.dataset.full || trigger.src;
      const caption = trigger.dataset.caption || '';
      openLightbox(src, caption);
    }
  });

  if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
  modal.addEventListener('click', (e) => { if (e.target === modal) closeLightbox(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.classList.contains('show')) closeLightbox(); });
}

/* ---------- Bookly AI Assistant Floating Chatbot ---------- */
function initAiChat() {
  const bubble = document.getElementById('ai-chat-bubble');
  const panel = document.getElementById('ai-chat-panel');
  const closeBtn = document.getElementById('ai-chat-close');
  const resetBtn = document.getElementById('ai-chat-reset');
  const form = document.getElementById('ai-chat-form');
  const input = document.getElementById('ai-user-input');
  const msgContainer = document.getElementById('ai-chat-messages');
  const chips = document.querySelectorAll('.ai-chip');

  if (!bubble || !panel || !form) return;

  const togglePanel = () => {
    panel.classList.toggle('show');
    if (panel.classList.contains('show')) {
      input.focus();
      scrollBottom();
    }
  };

  const scrollBottom = () => {
    msgContainer.scrollTop = msgContainer.scrollHeight;
  };

  const formatMarkdown = (text) => {
    if (!text) return '';
    let html = escapeHtml(text);
    // Bold
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    // Italic
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
    // Inline code
    html = html.replace(/`(.*?)`/g, '<code>$1</code>');
    // Markdown links [text](url)
    html = html.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" style="color:var(--wine); font-weight:600;">$1</a>');
    // Linebreaks
    html = html.replace(/\n/g, '<br>');
    return html;
  };

  const appendUserMessage = (text) => {
    const el = document.createElement('div');
    el.className = 'ai-msg user';
    el.innerHTML = `
      <div class="ai-msg-content">
        <p>${escapeHtml(text)}</p>
      </div>
    `;
    msgContainer.appendChild(el);
    scrollBottom();
  };

  const showTypingIndicator = () => {
    const el = document.createElement('div');
    el.className = 'ai-msg bot ai-typing-indicator';
    el.innerHTML = `
      <div class="ai-msg-avatar"><i class="fas fa-robot"></i></div>
      <div class="ai-msg-content" style="padding:6px 12px;">
        <div class="ai-typing-dots"><span></span><span></span><span></span></div>
      </div>
    `;
    msgContainer.appendChild(el);
    scrollBottom();
    return el;
  };

  const appendBotResponse = (replyText, cards = []) => {
    const el = document.createElement('div');
    el.className = 'ai-msg bot';

    let cardsHtml = '';
    if (cards && cards.length > 0) {
      cardsHtml = cards.map(c => `
        <div class="ai-book-card">
          <img src="${escapeHtml(c.cover)}" alt="${escapeHtml(c.title)}">
          <div>
            <div class="ai-book-card-title">${escapeHtml(c.title)}</div>
            <div class="ai-book-card-author">by ${escapeHtml(c.author)} · ⭐ ${c.rating || '5.0'}</div>
            <div class="ai-book-card-price">${escapeHtml(c.price)}</div>
            <div class="ai-book-card-actions">
              <button type="button" class="ai-card-btn add add-cart-btn" data-book-id="${c.id}" data-cover="${escapeHtml(c.cover)}">
                <i class="fas fa-cart-plus"></i> Add
              </button>
              <button type="button" class="ai-card-btn sample btn-sample-reader" data-book-id="${c.id}">
                <i class="fas fa-book-open"></i> Sample
              </button>
            </div>
          </div>
        </div>
      `).join('');
    }

    el.innerHTML = `
      <div class="ai-msg-avatar"><i class="fas fa-robot"></i></div>
      <div class="ai-msg-content">
        <div>${formatMarkdown(replyText)}</div>
        ${cardsHtml}
      </div>
    `;
    msgContainer.appendChild(el);
    scrollBottom();
  };

  const sendMessage = async (text) => {
    if (!text.trim()) return;
    appendUserMessage(text);
    input.value = '';

    const typingEl = showTypingIndicator();

    try {
      const ajaxBase = window.location.pathname.includes('/admin/') ? '../ajax/' : 'ajax/';
      const res = await fetch(`${ajaxBase}ai_chat.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text })
      });
      const data = await res.json();
      if (typingEl) typingEl.remove();

      if (data.ok) {
        appendBotResponse(data.reply, data.cards || []);
      } else {
        appendBotResponse("I encountered a hiccup answering that question. Please try asking again!", []);
      }
    } catch (e) {
      console.error('AI chat error', e);
      if (typingEl) typingEl.remove();
      appendBotResponse("I'm having trouble connecting to the catalog right now. Please try again shortly!", []);
    }
  };

  bubble.addEventListener('click', togglePanel);
  if (closeBtn) closeBtn.addEventListener('click', () => panel.classList.remove('show'));

  const expandBtn = document.getElementById('ai-chat-expand');
  if (expandBtn) {
    expandBtn.addEventListener('click', () => {
      panel.classList.toggle('expanded');
      const icon = expandBtn.querySelector('i');
      if (icon) {
        if (panel.classList.contains('expanded')) {
          icon.className = 'fas fa-down-left-and-up-right-to-center';
        } else {
          icon.className = 'fas fa-up-right-and-down-left-from-center';
        }
      }
    });
  }

  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      msgContainer.innerHTML = `
        <div class="ai-msg bot">
          <div class="ai-msg-avatar"><i class="fas fa-robot"></i></div>
          <div class="ai-msg-content">
            <p>👋 Chat cleared! How can I assist you with your reading today?</p>
          </div>
        </div>
      `;
    });
  }

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      const prompt = chip.dataset.prompt;
      if (prompt) sendMessage(prompt);
    });
  });

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    sendMessage(input.value);
  });
}





