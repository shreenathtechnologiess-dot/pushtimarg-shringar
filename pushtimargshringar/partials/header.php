<?php
// partials/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/config.php';
if (file_exists(__DIR__ . '/../app/auth.php')) require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$user = function_exists('auth_user') ? auth_user() : null;

// Fetch categories safely: only apply "status='active'" if the column exists
$categories = [];
if (isset($conn) && $conn instanceof mysqli) {
  // find if 'status' column exists in categories table
  $dbRow = $conn->query("SELECT DATABASE() AS dbname")->fetch_assoc();
  $dbName = $dbRow['dbname'] ?? '';
  $hasStatus = false;
  if ($dbName) {
    $safeDb = $conn->real_escape_string($dbName);
    $q = "SELECT COUNT(*) AS cnt FROM information_schema.columns
          WHERE table_schema = '{$safeDb}' AND table_name = 'categories' AND column_name = 'status'";
    $res = $conn->query($q);
    if ($res) {
      $hasStatus = ((int)($res->fetch_assoc()['cnt'] ?? 0) > 0);
      $res->free();
    }
  }

  // Choose query depending on whether status exists
  if ($hasStatus) {
    $stmt = $conn->prepare("SELECT id, name, slug FROM categories WHERE status = 'active' ORDER BY name ASC");
  } else {
    $stmt = $conn->prepare("SELECT id, name, slug FROM categories ORDER BY name ASC");
  }

  if ($stmt) {
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      $categories = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
      if ($res) $res->free();
    }
    $stmt->close();
  }
}

// Helper for output escaping (guarded to avoid redeclare errors)
if (!function_exists('e')) {
  function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
  }
}

// If session cart stored as associative productId => qty, sum values
$cartCount = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? (int)array_sum($_SESSION['cart']) : 0;
?>
<!-- Small inline critical CSS to avoid layout flash -->
<style>
  /* Fix logo size - normal width */
  .header-logo { max-width: 140px; height: auto; display: block; }
  .nav-link { white-space: nowrap; }

  /* Top marquee bar */
  .top-marquee {
    background: #8B0000;
    color: #fff;
    font-weight: 600;
    font-size: 0.9rem;
    overflow: hidden;
  }
  .marquee-inner {
    display: inline-block;
    padding-left: 100%;
    animation: marquee 18s linear infinite;
    white-space: nowrap;
  }
  @keyframes marquee {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
  }

  /* category bar scroll on small screens */
  .cat-scroll { overflow-x:auto; -webkit-overflow-scrolling: touch; }
  .cat-scroll ul { display:flex; gap:1.25rem; white-space:nowrap; padding:0.5rem 0; margin:0; list-style:none; align-items:center; justify-content:center; }
  .cat-scroll a { display:inline-block; padding:0.25rem 0.5rem; font-weight:600; color:#000; text-decoration:none; }
  .cat-scroll a:hover { color:#fff; }

  /* Search specific */
  .site-search-input { border:1px solid rgba(0,0,0,0.08); padding:.45rem .6rem; border-radius:8px; min-width:220px; }
  .site-search-btn { padding:.45rem .6rem; border-radius:8px; border:1px solid rgba(0,0,0,0.08); background:var(--gold,#E6B325); color:#fff; display:inline-flex; align-items:center; gap:.4rem; }
  .suggestions { position: absolute; background:#fff; box-shadow:0 6px 18px rgba(0,0,0,0.08); z-index:60; width:100%; max-width:420px; border-radius:6px; overflow:hidden; margin-top:.35rem; }
  .suggestion-item { padding:.5rem .75rem; font-size:.95rem; cursor:pointer; border-bottom:1px solid rgba(0,0,0,0.04); }
  .suggestion-item:last-child { border-bottom: none; }
  .suggestion-item:hover, .suggestion-item[aria-selected="true"] { background: #f5f5f5; }

  @media (max-width: 768px) {
    .site-search-input { min-width: 120px; }
  }
</style>

<!-- Top Notice Bar -->
<div class="top-marquee py-2 text-sm">
  <div class="marquee-inner">
    पुष्टिमार्ग श्रृंगार - सीधे श्रीनाथ जी के धाम से &nbsp;&nbsp;|&nbsp;&nbsp;
    पुष्टिमार्ग श्रृंगार - सीधे श्रीनाथ जी के धाम से
  </div>
</div>

<!-- Main Navbar -->
<nav class="bg-white shadow sticky top-0 z-50" x-data="{ open: false, showMobileSearch: false, suggestionsOpen: false }" aria-label="Primary">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between h-14">

      <!-- Left: logo + brand -->
      <div class="flex items-center gap-3">
        <a href="<?= function_exists('site_url') ? site_url('index.php') : (defined('SITE_URL') ? SITE_URL.'/index.php' : '/index.php') ?>" class="flex items-center gap-3" aria-label="Home">
          <img src="<?= function_exists('asset') ? asset('assets/images/logo/logo.png') : '/assets/images/logo/logo.png' ?>" alt="Pushtimarg Shringar logo"
               class="header-logo" style="width:140px; height:auto;">
          <span class="hidden sm:inline-block text-base font-semibold text-gold">Pushtimarg Shringar</span>
        </a>
      </div>

      <!-- Middle: desktop nav -->
      <div class="hidden md:flex items-center gap-6 text-sm">
        <a class="nav-link hover:text-gold" href="<?= function_exists('site_url') ? site_url('index.php') : 'index.php' ?>">Home</a>
        <a class="nav-link hover:text-gold" href="<?= function_exists('site_url') ? site_url('products.php') : 'products.php' ?>">Products</a>
        <a class="nav-link hover:text-gold" href="<?= function_exists('site_url') ? site_url('categories.php') : 'categories.php' ?>">Categories</a>
        <a class="nav-link hover:text-gold" href="<?= function_exists('site_url') ? site_url('about.php') : 'about.php' ?>">About Us</a>
        <a class="nav-link hover:text-gold" href="<?= function_exists('site_url') ? site_url('contact.php') : 'contact.php' ?>">Contact</a>
        <a class="nav-link hover:text-gold" href="<?= function_exists('site_url') ? site_url('blog.php') : 'blog.php' ?>">Blog</a>
      </div>

      <!-- Right: actions (search added left of login) -->
      <div class="flex items-center gap-3">

        <!-- Desktop search (visible on md+) -->
        <div class="hidden md:flex items-center gap-2 relative" style="min-width:260px;">
          <form id="desktop-search-form" action="<?= function_exists('site_url') ? site_url('search.php') : 'search.php' ?>" method="get" class="flex items-center gap-2" role="search" aria-label="Site search" autocomplete="off">
            <label for="q_desktop" class="sr-only">Search</label>
            <div style="position:relative; width:100%; max-width:420px;">
              <input id="q_desktop" name="q" type="search" placeholder="Search products, videos..." class="site-search-input" aria-autocomplete="list" aria-controls="search-suggestions-desktop" aria-expanded="false" />
              <!-- suggestion box -->
              <div id="search-suggestions-desktop" role="listbox" class="suggestions" style="display:none;" aria-hidden="true"></div>
            </div>
            <button type="submit" class="site-search-btn" aria-label="Search">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
              <span class="sr-only">Search</span>
            </button>
          </form>
        </div>

        <!-- Account links (desktop) -->
        <div class="hidden md:flex items-center gap-3 text-sm">
          <?php if ($user): ?>
            <a class="hover:text-gold" href="<?= function_exists('site_url') ? site_url('account/index.php') : 'account/index.php' ?>">Hello, <?= e(explode(' ', $user['name'])[0] ?? '') ?></a>
            <a class="hover:text-gold" href="<?= function_exists('site_url') ? site_url('account/logout.php') : 'account/logout.php' ?>">Logout</a>
          <?php else: ?>
            <a class="hover:text-gold" href="<?= function_exists('site_url') ? site_url('account/login.php') : 'account/login.php' ?>">Login</a>
            <a class="hover:text-gold" href="<?= function_exists('site_url') ? site_url('account/register.php') : 'account/register.php' ?>">Register</a>
          <?php endif; ?>
        </div>

        <!-- Cart (desktop) -->
        <a href="<?= function_exists('site_url') ? site_url('cart.php') : 'cart.php' ?>"
           class="hidden md:inline-flex items-center bg-gold text-white px-3 py-1.5 rounded text-sm"
           aria-label="View cart">
          🛒 Cart (<span id="cartCount"><?= $cartCount ?></span>)
        </a>

        <!-- Mobile: search button (visible on small screens) -->
        <button @click="showMobileSearch = !showMobileSearch" class="md:hidden p-2 rounded focus:outline-none" title="Search" aria-pressed="false" aria-controls="mobile-search-area">
          <svg class="w-6 h-6 text-darkgray" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
          </svg>
        </button>

        <!-- Mobile menu button -->
        <button @click="open = !open" class="md:hidden p-2 rounded focus:outline-none" title="Toggle menu" aria-expanded="false" aria-controls="mobile-menu">
          <svg class="w-6 h-6 text-darkgray" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            <path x-show="open" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile search area (toggles on mobile) -->
  <div x-show="showMobileSearch" x-cloak id="mobile-search-area" class="md:hidden bg-white border-t px-4 py-3">
    <form id="mobile-search-form" action="<?= function_exists('site_url') ? site_url('search.php') : 'search.php' ?>" method="get" role="search" class="flex gap-2" autocomplete="off">
      <label for="q_mobile" class="sr-only">Search</label>
      <div style="position:relative; width:100%;">
        <input id="q_mobile" name="q" type="search" placeholder="Search products, videos..." class="w-full border rounded px-3 py-2" aria-autocomplete="list" aria-controls="search-suggestions-mobile" />
        <div id="search-suggestions-mobile" role="listbox" class="suggestions" style="display:none;" aria-hidden="true"></div>
      </div>
      <button type="submit" class="px-3 py-2 bg-gold text-white rounded">Search</button>
    </form>
  </div>

  <!-- Mobile dropdown -->
  <div x-show="open" x-cloak id="mobile-menu" class="md:hidden bg-white border-t">
    <div class="px-4 py-3 space-y-1 text-sm">
      <a class="block px-2 py-2 rounded hover:bg-cream" href="<?= function_exists('site_url') ? site_url('index.php') : 'index.php' ?>">Home</a>
      <a class="block px-2 py-2 rounded hover:bg-cream" href="<?= function_exists('site_url') ? site_url('products.php') : 'products.php' ?>">Products</a>
      <a class="block px-2 py-2 rounded hover:bg-cream" href="<?= function_exists('site_url') ? site_url('categories.php') : 'categories.php' ?>">Categories</a>
      <a class="block px-2 py-2 rounded hover:bg-cream" href="<?= function_exists('site_url') ? site_url('about.php') : 'about.php' ?>">About Us</a>
      <a class="block px-2 py-2 rounded hover:bg-cream" href="<?= function_exists('site_url') ? site_url('contact.php') : 'contact.php' ?>">Contact</a>
      <a class="block px-2 py-2 rounded hover:bg-cream" href="<?= function_exists('site_url') ? site_url('blog.php') : 'blog.php' ?>">Blog</a>

      <?php if ($user): ?>
        <a class="block px-2 py-2 rounded hover:bg-cream" href="<?= function_exists('site_url') ? site_url('account/index.php') : 'account/index.php' ?>">My Account</a>
        <a class="block px-2 py-2 rounded hover:bg-cream" href="<?= function_exists('site_url') ? site_url('account/logout.php') : 'account/logout.php' ?>">Logout</a>
      <?php else: ?>
        <a class="block px-2 py-2 rounded hover:bg-cream" href="<?= function_exists('site_url') ? site_url('account/login.php') : 'account/login.php' ?>">Login</a>
        <a class="block px-2 py-2 rounded hover:bg-cream" href="<?= function_exists('site_url') ? site_url('account/register.php') : 'account/register.php' ?>">Register</a>
      <?php endif; ?>

      <a class="block mt-2 px-3 py-2 rounded bg-gold text-white text-center" href="<?= function_exists('site_url') ? site_url('cart.php') : 'cart.php' ?>">
        🛒 Cart (<span id="cartCountMobile"><?= $cartCount ?></span>)
      </a>
    </div>
  </div>
</nav>

<!-- ✅ Category Bar (Header ke niche, Banner se pehle) -->
<?php if (!empty($categories)): ?>
  <div class="bg-deepgreen shadow">
    <div class="max-w-7xl mx-auto px-4">
      <ul class="flex flex-wrap gap-6 justify-center py-2 text-sm font-semibold">
        <?php foreach ($categories as $cat): ?>
          <li>
            <a href="<?= function_exists('site_url') ? site_url('products.php?category=' . urlencode($cat['slug'])) : ('products.php?category='.urlencode($cat['slug'])) ?>" 
               class="text-white hover:text-gold transition">
               <?= e($cat['name']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>


<!-- Sticky Bottom Mobile Nav -->
<div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-md md:hidden z-50">
  <div class="flex justify-around items-center py-2 text-sm text-gray-700">
    <a href="<?= function_exists('site_url') ? site_url('index.php') : 'index.php' ?>" class="flex flex-col items-center">Home</a>
    <a href="<?= function_exists('site_url') ? site_url('categories.php') : 'categories.php' ?>" class="flex flex-col items-center">Categories</a>
    <a href="<?= function_exists('site_url') ? site_url('cart.php') : 'cart.php' ?>" class="flex flex-col items-center">Cart (<span id="cartCountBottom"><?= $cartCount ?></span>)</a>
    <?php if ($user): ?>
      <a href="<?= function_exists('site_url') ? site_url('account/index.php') : 'account/index.php' ?>" class="flex flex-col items-center">Account</a>
    <?php else: ?>
      <a href="<?= function_exists('site_url') ? site_url('account/login.php') : 'account/login.php' ?>" class="flex flex-col items-center">Login</a>
    <?php endif; ?>
  </div>
</div>

<!-- Optional: small script to sync cart counts if JS updates cart in-page -->
<script>
  (function(){
    function setCounts(n){
      var v = parseInt(n) || 0;
      var ids = ['cartCount','cartCountMobile','cartCountBottom'];
      ids.forEach(function(id){ var el = document.getElementById(id); if(el) el.textContent = v; });
    }
    window.updateCartCount = setCounts;
  })();
</script>

<!-- Search suggestion + accessibility script (plain JS, no dependency) -->
<script>
(function(){
  // Debounce helper
  function debounce(fn, delay){ var t; return function(){ var ctx=this,args=arguments; clearTimeout(t); t=setTimeout(function(){ fn.apply(ctx,args); }, delay); }; }

  // Generic function to attach suggestions for an input and suggestion container
  function attachSuggestions(inputId, containerId){
    var input = document.getElementById(inputId);
    var container = document.getElementById(containerId);
    if(!input || !container) return;

    var currentFocus = -1;
    function closeSuggestions(){ container.style.display = 'none'; container.innerHTML=''; input.setAttribute('aria-expanded','false'); container.setAttribute('aria-hidden','true'); currentFocus = -1; }
    function openSuggestions(){ container.style.display = 'block'; input.setAttribute('aria-expanded','true'); container.setAttribute('aria-hidden','false'); }

    // create item element
    function makeItem(text, url){
      var div = document.createElement('div');
      div.className = 'suggestion-item';
      div.tabIndex = 0;
      div.setAttribute('role','option');
      div.innerText = text;
      div.addEventListener('click', function(){ window.location.href = url; });
      div.addEventListener('keydown', function(e){
        if(e.key === 'Enter'){ window.location.href = url; }
      });
      return div;
    }

    // Fetch suggestions from endpoint
    var fetchSuggestions = debounce(function(){
      var q = input.value.trim();
      if(q.length < 2){ closeSuggestions(); return; }
      // NOTE: create a small endpoint search_suggest.php that returns JSON: [{label:"...", url:"/product.php?id=1"}, ...]
      fetch('<?= function_exists('site_url') ? site_url('search_suggest.php') : 'search_suggest.php' ?>?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
        .then(function(res){ if(!res.ok) throw new Error('no'); return res.json(); })
        .then(function(data){
          container.innerHTML = '';
          if(!Array.isArray(data) || data.length === 0){ closeSuggestions(); return; }
          data.slice(0,8).forEach(function(item){
            var dom = makeItem(item.label || item.name || '', item.url || ('<?= function_exists('site_url') ? site_url('search.php') : 'search.php' ?>?q=' + encodeURIComponent(item.label || item.name || ''))); 
            container.appendChild(dom);
          });
          openSuggestions();
        })
        .catch(function(){ closeSuggestions(); });
    }, 220);

    // Keyboard navigation within suggestion list
    input.addEventListener('keydown', function(e){
      var items = container.querySelectorAll('.suggestion-item');
      if(items.length === 0) return;
      if(e.key === 'ArrowDown'){ currentFocus = (currentFocus + 1) % items.length; setActive(items); e.preventDefault(); }
      else if(e.key === 'ArrowUp'){ currentFocus = (currentFocus - 1 + items.length) % items.length; setActive(items); e.preventDefault(); }
      else if(e.key === 'Enter'){ if(currentFocus > -1){ items[currentFocus].click(); e.preventDefault(); } }
      else if(e.key === 'Escape'){ closeSuggestions(); }
    });

    function setActive(items){
      items.forEach(function(it,i){ it.setAttribute('aria-selected', (i === currentFocus) ? 'true' : 'false'); if(i === currentFocus) it.scrollIntoView({block:'nearest'}); });
    }

    input.addEventListener('input', fetchSuggestions);
    input.addEventListener('blur', function(){ setTimeout(closeSuggestions, 180); }); // small delay so click registers
    input.addEventListener('focus', fetchSuggestions);
  }

  // Attach to desktop and mobile inputs
  attachSuggestions('q_desktop', 'search-suggestions-desktop');
  attachSuggestions('q_mobile', 'search-suggestions-mobile');

  // If forms used without JS, they still submit to search.php (graceful fallback)
})();
</script>
