<?php
require_once 'config.php';
require_once 'includes/functions.php';

$active_page = 'about';
$page_title = 'Our Story & Philosophy — Bookly';
require_once 'includes/header.php';
?>

<!-- 1. Hero Banner with Luxury Editorial Typography -->
<section style="padding:60px 0 30px; position:relative; overflow:hidden; background:linear-gradient(180deg, rgba(201,162,75,.08) 0%, transparent 100%);">
  <div class="container" style="text-align:center; max-width:860px;">
    <span class="eyebrow reveal in" style="font-size:.85rem; letter-spacing:.15em;"><i class="fas fa-feather-pointed" style="color:var(--gold);"></i> The Bookly Sanctuary</span>
    <h1 class="reveal in" style="font-size:clamp(2.2rem, 5vw, 3.4rem); font-family:var(--font-display); line-height:1.2; margin:14px 0 18px; color:var(--ink);">
      Where Stories Find Their Solace & Readers Find Their Home.
    </h1>
    <p class="reveal in" style="font-size:1.12rem; line-height:1.75; color:#5c544b; max-width:720px; margin:0 auto 36px;">
      Founded on the belief that a physical book is not merely ink on paper, but an intimate architecture of human thought. Hand-selected, lovingly packed, and delivered to your doorstep.
    </p>

    <!-- Key Metrics / Stats Row -->
    <div class="reveal in" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:18px; margin-top:20px;">
      <div style="background:#fffdf8; padding:20px 16px; border-radius:12px; border:1px solid var(--paper-dim); box-shadow:var(--shadow-sm);">
        <div style="font-family:var(--font-display); font-size:2rem; font-weight:700; color:var(--wine);">16,000+</div>
        <div style="font-size:.8rem; color:#777; margin-top:3px; text-transform:uppercase; letter-spacing:.05em; font-weight:600;">Happy Readers</div>
      </div>
      <div style="background:#fffdf8; padding:20px 16px; border-radius:12px; border:1px solid var(--paper-dim); box-shadow:var(--shadow-sm);">
        <div style="font-family:var(--font-display); font-size:2rem; font-weight:700; color:var(--gold);">5,200+</div>
        <div style="font-size:.8rem; color:#777; margin-top:3px; text-transform:uppercase; letter-spacing:.05em; font-weight:600;">Curated Titles</div>
      </div>
      <div style="background:#fffdf8; padding:20px 16px; border-radius:12px; border:1px solid var(--paper-dim); box-shadow:var(--shadow-sm);">
        <div style="font-family:var(--font-display); font-size:2rem; font-weight:700; color:var(--sage);">100%</div>
        <div style="font-size:.8rem; color:#777; margin-top:3px; text-transform:uppercase; letter-spacing:.05em; font-weight:600;">Mint Quality & Replacement</div>
      </div>
      <div style="background:#fffdf8; padding:20px 16px; border-radius:12px; border:1px solid var(--paper-dim); box-shadow:var(--shadow-sm);">
        <div style="font-family:var(--font-display); font-size:2rem; font-weight:700; color:var(--wine);">4.9 ★</div>
        <div style="font-size:.8rem; color:#777; margin-top:3px; text-transform:uppercase; letter-spacing:.05em; font-weight:600;">Community Rating</div>
      </div>
    </div>
  </div>
</section>

<!-- 2. The Origin Story & Architecture of Thought -->
<section style="padding:70px 0;">
  <div class="container" style="display:grid; grid-template-columns:1.1fr 1fr; gap:52px; align-items:center;">
    <div class="reveal in">
      <span class="eyebrow" style="color:var(--wine);"><i class="fas fa-bookmark"></i> Our Genesis</span>
      <h2 style="font-size:2.3rem; margin:12px 0 20px; font-family:var(--font-display); line-height:1.25;">
        Built by lifelong bibliophiles, for discerning readers.
      </h2>
      <p style="font-size:1.02rem; line-height:1.75; color:#4a443d; margin-bottom:18px;">
        Bookly started in late 2021 as a humble, fragrant mahogany shelf of second-hand classics in an old studio loft. We noticed an uncomfortable trend: online bookstores had become cold, sterile commodity warehouses governed by algorithms rather than taste.
      </p>
      <p style="font-size:1.02rem; line-height:1.75; color:#4a443d; margin-bottom:24px;">
        We set out to build an antidote — a warm, sensory literary sanctuary where every novel, memoir, philosophy treatise, and art folio is chosen by human hands who have actually turned the pages. We work directly with boutique publishers and independent authors to ensure extraordinary voices reach your nightstand.
      </p>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
        <div style="display:flex; align-items:center; gap:10px; font-size:.88rem; font-weight:600; color:var(--ink);">
          <i class="fas fa-circle-check" style="color:var(--sage); font-size:1.1rem;"></i>
          <span>Zero Machine Curation</span>
        </div>
        <div style="display:flex; align-items:center; gap:10px; font-size:.88rem; font-weight:600; color:var(--ink);">
          <i class="fas fa-circle-check" style="color:var(--sage); font-size:1.1rem;"></i>
          <span>7-Day Easy Replacement</span>
        </div>
        <div style="display:flex; align-items:center; gap:10px; font-size:.88rem; font-weight:600; color:var(--ink);">
          <i class="fas fa-circle-check" style="color:var(--sage); font-size:1.1rem;"></i>
          <span>Archival Protective Packaging</span>
        </div>
        <div style="display:flex; align-items:center; gap:10px; font-size:.88rem; font-weight:600; color:var(--ink);">
          <i class="fas fa-circle-check" style="color:var(--sage); font-size:1.1rem;"></i>
          <span>Interactive Chapter Reader</span>
        </div>
      </div>
    </div>

    <!-- Image with Floating Luxury Badge -->
    <div class="reveal in" style="position:relative;">
      <div style="border-radius:20px; overflow:hidden; box-shadow:0 25px 60px rgba(0,0,0,.15); border:1px solid var(--paper-dim);">
        <img src="images/about-img.jpg" alt="Bookly Bookshop Atmosphere" style="width:100%; height:460px; object-fit:cover; display:block;">
      </div>
      
      <!-- Floating Badge -->
      <div style="position:absolute; bottom:-20px; left:-20px; background:#fffdf8; border:2px solid var(--gold); border-radius:14px; padding:18px 22px; box-shadow:0 15px 35px rgba(0,0,0,.12); max-width:260px;">
        <div style="display:flex; align-items:center; gap:8px; font-size:.78rem; font-weight:700; color:var(--gold); text-transform:uppercase; letter-spacing:.08em; margin-bottom:4px;">
          <i class="fas fa-award"></i> The Bookly Pledge
        </div>
        <p style="font-size:.82rem; line-height:1.45; color:#555; margin:0;">
          "If a book does not stir your soul or arrives with a single imperfection, we replace it instantly."
        </p>
      </div>
    </div>
  </div>
</section>

<!-- 3. Our Four Core Pillars of Excellence -->
<section class="section-alt" style="padding:70px 0;">
  <div class="container">
    <div class="section-head reveal in" style="text-align:center; max-width:700px; margin:0 auto 48px;">
      <div>
        <span class="eyebrow"><i class="fas fa-gem" style="color:var(--gold);"></i> The Pillars</span>
        <h2>Why readers choose Bookly</h2>
        <p style="color:#666; font-size:.95rem; margin-top:8px;">Every detail of our bookstore is crafted to elevate your reading journey.</p>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:24px;">
      <!-- Card 1 -->
      <div class="reveal in" style="background:#fffdf8; padding:32px 26px; border-radius:16px; border:1px solid var(--paper-dim); box-shadow:var(--shadow-sm); transition:transform .3s ease, box-shadow .3s ease;">
        <div style="width:52px; height:52px; border-radius:12px; background:rgba(110,31,42,.1); color:var(--wine); display:flex; align-items:center; justify-content:center; font-size:1.4rem; margin-bottom:18px;">
          <i class="fas fa-book-bookmark"></i>
        </div>
        <h3 style="font-size:1.2rem; margin-bottom:10px; font-family:var(--font-display);">Human Curation</h3>
        <p style="font-size:.88rem; line-height:1.65; color:#6b6259; margin:0;">
          We read every title before it enters our catalog. No clickbait or auto-generated bestseller lists — only profound, unforgettable prose.
        </p>
      </div>

      <!-- Card 2 -->
      <div class="reveal in" style="background:#fffdf8; padding:32px 26px; border-radius:16px; border:1px solid var(--paper-dim); box-shadow:var(--shadow-sm); transition:transform .3s ease, box-shadow .3s ease;">
        <div style="width:52px; height:52px; border-radius:12px; background:rgba(201,162,75,.15); color:var(--gold); display:flex; align-items:center; justify-content:center; font-size:1.4rem; margin-bottom:18px;">
          <i class="fas fa-box-open"></i>
        </div>
        <h3 style="font-size:1.2rem; margin-bottom:10px; font-family:var(--font-display);">Pristine Packaging</h3>
        <p style="font-size:.88rem; line-height:1.65; color:#6b6259; margin:0;">
          Triple-layer cardboard protection and sealed corners ensure your book arrives in spotless, collector-grade mint condition.
        </p>
      </div>

      <!-- Card 3 -->
      <div class="reveal in" style="background:#fffdf8; padding:32px 26px; border-radius:16px; border:1px solid var(--paper-dim); box-shadow:var(--shadow-sm); transition:transform .3s ease, box-shadow .3s ease;">
        <div style="width:52px; height:52px; border-radius:12px; background:rgba(45,107,31,.12); color:var(--sage); display:flex; align-items:center; justify-content:center; font-size:1.4rem; margin-bottom:18px;">
          <i class="fas fa-rotate-left"></i>
        </div>
        <h3 style="font-size:1.2rem; margin-bottom:10px; font-family:var(--font-display);">7-Day Easy Replacement</h3>
        <p style="font-size:.88rem; line-height:1.65; color:#6b6259; margin:0;">
          Found a misprint, bent spine, or torn page? Submit a 1-click replacement request in your Orders tab and we'll send a fresh copy.
        </p>
      </div>

      <!-- Card 4 -->
      <div class="reveal in" style="background:#fffdf8; padding:32px 26px; border-radius:16px; border:1px solid var(--paper-dim); box-shadow:var(--shadow-sm); transition:transform .3s ease, box-shadow .3s ease;">
        <div style="width:52px; height:52px; border-radius:12px; background:rgba(110,31,42,.1); color:var(--wine); display:flex; align-items:center; justify-content:center; font-size:1.4rem; margin-bottom:18px;">
          <i class="fas fa-wand-magic-sparkles"></i>
        </div>
        <h3 style="font-size:1.2rem; margin-bottom:10px; font-family:var(--font-display);">AI Reader Concierge</h3>
        <p style="font-size:.88rem; line-height:1.65; color:#6b6259; margin:0;">
          Powered by real Google Gemini Intelligence, our Assistant analyzes your reading mood and finds exact matches with excerpt previews.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- 4. Interactive Visual Journey Timeline -->
<section style="padding:70px 0; background:#fffdf8;">
  <div class="container">
    <div class="section-head reveal in" style="text-align:center; max-width:700px; margin:0 auto 48px;">
      <div>
        <span class="eyebrow"><i class="fas fa-timeline"></i> The Odyssey</span>
        <h2>Milestones in our journey</h2>
        <p style="color:#666; font-size:.95rem; margin-top:8px;">How a modest bookshelf evolved into India's premier boutique bookstore.</p>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:24px; position:relative;">
      <!-- 2021 -->
      <div class="reveal in" style="background:#faf5eb; padding:24px; border-radius:14px; border-top:4px solid var(--gold); position:relative;">
        <div style="font-family:var(--font-display); font-size:1.8rem; font-weight:700; color:var(--wine); margin-bottom:6px;">2021</div>
        <h4 style="font-size:1.05rem; margin-bottom:8px;">The First Mahogany Shelf</h4>
        <p style="font-size:.84rem; line-height:1.6; color:#6b6259; margin:0;">Started with 200 rare second-hand volumes in an old apartment, hand-delivering to local book lovers.</p>
      </div>

      <!-- 2023 -->
      <div class="reveal in" style="background:#faf5eb; padding:24px; border-radius:14px; border-top:4px solid var(--wine); position:relative;">
        <div style="font-family:var(--font-display); font-size:1.8rem; font-weight:700; color:var(--wine); margin-bottom:6px;">2023</div>
        <h4 style="font-size:1.05rem; margin-bottom:8px;">Indie Author Guild</h4>
        <p style="font-size:.84rem; line-height:1.6; color:#6b6259; margin:0;">Partnered directly with 150+ independent authors and translation houses across 12 languages.</p>
      </div>

      <!-- 2025 -->
      <div class="reveal in" style="background:#faf5eb; padding:24px; border-radius:14px; border-top:4px solid var(--sage); position:relative;">
        <div style="font-family:var(--font-display); font-size:1.8rem; font-weight:700; color:var(--wine); margin-bottom:6px;">2025</div>
        <h4 style="font-size:1.05rem; margin-bottom:8px;">AI Literary Concierge</h4>
        <p style="font-size:.84rem; line-height:1.6; color:#6b6259; margin:0;">Pioneered custom AI RAG catalog assistance with half-screen reading expand mode.</p>
      </div>

      <!-- 2026 -->
      <div class="reveal in" style="background:#faf5eb; padding:24px; border-radius:14px; border-top:4px solid var(--gold); position:relative;">
        <div style="font-family:var(--font-display); font-size:1.8rem; font-weight:700; color:var(--wine); margin-bottom:6px;">2026</div>
        <h4 style="font-size:1.05rem; margin-bottom:8px;">Interactive Reader Guild</h4>
        <p style="font-size:.84rem; line-height:1.6; color:#6b6259; margin:0;">Launched Sample Chapter Readers, 7-Day Replacement Guarantee & Doorstep Cash on Delivery.</p>
      </div>
    </div>
  </div>
</section>

<!-- 5. Meet the Curators & Literary Editorial Board -->
<section class="section-alt" style="padding:70px 0;">
  <div class="container">
    <div class="section-head reveal in" style="text-align:center; max-width:700px; margin:0 auto 48px;">
      <div>
        <span class="eyebrow"><i class="fas fa-users"></i> The Guardians of the Shelf</span>
        <h2>Meet our curators & editors</h2>
        <p style="color:#666; font-size:.95rem; margin-top:8px;">The passionate readers behind every recommendation, review, and library catalog.</p>
      </div>
    </div>

    <div class="book-grid" style="grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:24px;">
      <?php
      $team = [
        ['author-1.jpg', 'Meera Iyer', 'Founder & Head of Curation', 'Classic Literature & Philosophy', '“A reader lives a thousand lives before he dies.”'],
        ['author-2.jpg', 'Daniel Cho', 'Fiction & Mystery Buyer', 'Contemporary & Psychological Thrillers', '“Fiction is the lie through which we tell the truth.”'],
        ['author-3.jpg', 'Priya Nair', 'Community & Reader Guild Lead', 'Memoirs & Poetry Collections', '“Books are a uniquely portable magic.”'],
        ['author-4.jpg', 'Sam Whitfield', 'Science & Non-Fiction Curator', 'History, Astrophysics & Nature', '“Somewhere, something incredible is waiting to be known.”'],
        ['author-5.jpg', 'Elena Rostova', 'Rare Editions & Art Books', 'Renaissance Art & Typography', '“A room without books is like a body without a soul.”'],
        ['author-6.jpg', 'Aarav Mehta', 'Young Adult & Fantasy Lead', 'Worldbuilding & Mythological Epics', '“Stories are wild creatures. When you let them out, who knows what they\'d do.”']
      ];
      foreach ($team as $t): ?>
        <div class="book-card reveal in" style="padding:28px 20px; text-align:center; display:flex; flex-direction:column; align-items:center;">
          <div style="position:relative; margin-bottom:16px;">
            <img src="images/<?= $t[0]; ?>" alt="<?= h($t[1]); ?>" style="width:105px; height:105px; border-radius:50%; object-fit:cover; border:3px solid var(--gold); box-shadow:0 6px 18px rgba(0,0,0,.1);">
            <div style="position:absolute; bottom:0; right:4px; width:26px; height:26px; border-radius:50%; background:var(--wine); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.7rem;">
              <i class="fas fa-feather"></i>
            </div>
          </div>
          <h3 style="font-size:1.15rem; margin-bottom:4px; font-family:var(--font-display);"><?= h($t[1]); ?></h3>
          <span style="font-size:.76rem; font-weight:700; color:var(--wine); text-transform:uppercase; letter-spacing:.06em; margin-bottom:8px; display:block;"><?= h($t[2]); ?></span>
          <div style="font-size:.78rem; color:#777; background:#faf5eb; padding:4px 10px; border-radius:999px; margin-bottom:12px;">
            <i class="fas fa-heart" style="color:var(--wine); font-size:.7rem;"></i> <?= h($t[3]); ?>
          </div>
          <p style="font-size:.82rem; font-style:italic; color:#6b6259; line-height:1.45; margin:0; margin-top:auto;">
            <?= h($t[4]); ?>
          </p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 6. Call To Action (CTA) Literary Invitation -->
<section style="padding:70px 0; background:linear-gradient(135deg, var(--wine) 0%, #431018 100%); color:#fff;">
  <div class="container" style="text-align:center; max-width:760px;">
    <span class="eyebrow" style="color:var(--gold-soft);"><i class="fas fa-compass"></i> Begin Your Journey</span>
    <h2 style="font-size:clamp(1.8rem, 4vw, 2.6rem); color:#fff; margin:14px 0 18px; font-family:var(--font-display);">
      Ready to discover your next unforgettable read?
    </h2>
    <p style="font-size:1.05rem; line-height:1.7; color:rgba(255,255,255,.82); margin-bottom:32px;">
      Explore our handpicked collection of 5,000+ timeless editions or converse with our AI Reader Concierge for tailored recommendations.
    </p>

    <div style="display:flex; gap:14px; justify-content:center; flex-wrap:wrap;">
      <a href="shop.php" class="btn" style="background:var(--gold); color:var(--ink); font-weight:700; padding:12px 28px; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,.3);">
        <i class="fas fa-book-open"></i> Browse Library Collection
      </a>
      <button type="button" class="btn btn-outline" id="about-open-chat" style="border-color:rgba(255,255,255,.6); color:#fff; padding:12px 28px; border-radius:8px;">
        <i class="fas fa-robot"></i> Ask AI Book Concierge
      </button>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const chatTrigger = document.getElementById('about-open-chat');
  if (chatTrigger) {
    chatTrigger.addEventListener('click', () => {
      const chatPanel = document.getElementById('ai-chat-panel');
      const chatBtn = document.getElementById('ai-chat-btn');
      if (chatPanel) {
        chatPanel.classList.add('active');
        if (chatBtn) chatBtn.style.display = 'none';
      }
    });
  }
});
</script>

<?php require_once 'includes/footer.php'; ?>
