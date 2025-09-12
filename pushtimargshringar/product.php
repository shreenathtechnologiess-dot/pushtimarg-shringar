<?php
// index.php top — ensure config (and session) are loaded before any HTML output
require_once __DIR__ . '/app/config.php';  // starts session now
require_once __DIR__ . '/app/db.php';      // optional DB connection

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';


/* ---------- Helpers ---------- */
if (!function_exists('slugify')) {
  function slugify($t){ $t = strtolower(trim($t)); $t = preg_replace('/[^a-z0-9]+/i','-',$t); return trim($t,'-'); }
}
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function render_stars($avg,$out=5){
  $full = floor($avg);
  $half = (($avg-$full) >= 0.5) ? 1 : 0;
  $empty = $out - $full - $half;
  return str_repeat('★',$full).($half?'½':'').str_repeat('☆',$empty);
}
function abs_url_from_path($path) {
  if (preg_match('#^https?://#i', $path)) return $path;
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  return $scheme . '://' . $host . '/' . ltrim($path, '/');
}

/* ---------- Demo fallback catalog (only used if DB not available or product not found) ---------- */
$demo_products = [
  ["name"=>"Blue Cotton","img"=>"blue-cotton.jpg","price"=>400,"category"=>"fabric"],
  ["name"=>"Golden Banarasi","img"=>"golden-banarasi.jpg","price"=>1200,"category"=>"fabric"],
  ["name"=>"Green Silk","img"=>"green-silk.jpg","price"=>1600,"category"=>"fabric"],
  ["name"=>"Orange Vastra","img"=>"orange-vastra.jpeg","price"=>4000,"category"=>"vastra"],
  ["name"=>"Royal Blue","img"=>"royal-blue.jpeg","price"=>4500,"category"=>"vastra"],
  ["name"=>"Cow Krishna Pichwai","img"=>"cow-krishna-pichwai.jpg","price"=>5000,"category"=>"pichwai"],
  ["name"=>"Peacock Pichwai","img"=>"peacock-pichwai.jpg","price"=>7000,"category"=>"pichwai"],
  ["name"=>"Shreenathji Print","img"=>"shreenathji-print.jpg","price"=>9000,"category"=>"pichwai"],
];
foreach ($demo_products as $k=>$p){ if (empty($demo_products[$k]['slug'])) $demo_products[$k]['slug'] = slugify(pathinfo($p['img'], PATHINFO_FILENAME) ?: $p['name']); }

/* ---------- Resolve requested product (slug preferred, fallback to id) ---------- */
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$product = null;
$usedDb = (isset($conn) && $conn instanceof mysqli);

if ($usedDb && ($slug !== '' || $id > 0)) {
  // build query based on whether slug or id provided
  if ($slug !== '') {
    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.slug = ? AND (p.status IS NULL OR p.status <> 'inactive')
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
      $stmt->bind_param('s',$slug);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res && $res->num_rows) $product = $res->fetch_assoc();
      $stmt->close();
    }
  }

  if (!$product && $id > 0) {
    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ? AND (p.status IS NULL OR p.status <> 'inactive')
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
      $stmt->bind_param('i',$id);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res && $res->num_rows) $product = $res->fetch_assoc();
      $stmt->close();
    }
  }
}

// fallback: if DB not available or product not found, try demo list (only when slug given)
if (!$product && $slug) {
  foreach ($demo_products as $p) {
    if (($p['slug'] ?? '') === $slug) { $product = $p; break; }
  }
}

/* ---------- If nothing specified or not found, show message ---------- */
if (!$product) {
  // Render "product not found" page (still continue to footer/includes)
  ?>
  <section class="py-10 bg-cream">
    <div class="max-w-7xl mx-auto px-4">
      <h1 class="text-2xl font-bold text-deepgreen mb-4">Product not found</h1>
      <?php if ($slug === '' && $id === 0): ?>
        <p class="text-darkgray">No product specified. <a href="products.php" class="text-gold underline">Back to products</a></p>
      <?php else: ?>
        <p class="text-darkgray">We couldn't find that product. <a href="products.php" class="text-gold underline">Back to products</a></p>
      <?php endif; ?>
    </div>
  </section>
  <?php
  include __DIR__ . '/partials/footer.php';
  include __DIR__ . '/partials/scripts.php';
  exit;
}

/* ---------- Handle review submit (if product exists) ---------- */
$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submit'])) {
  $name = trim($_POST['name'] ?? '');
  $rating = (int)($_POST['rating'] ?? 0);
  $comment = trim($_POST['comment'] ?? '');

  if (!$product) {
    $form_error = "Product not found.";
  } elseif ($name === '' || $comment === '' || $rating < 1 || $rating > 5) {
    $form_error = "Please fill your name, a rating (1–5) and a comment.";
  } elseif ($usedDb) {
    // store review (use product slug if present, else use id as fallback)
    $useSlug = $product['slug'] ?? $slug;
    if (!$useSlug) $useSlug = '';
    $stmt = $conn->prepare("INSERT INTO reviews (slug, name, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
      $stmt->bind_param('ssis', $useSlug, $name, $rating, $comment);
      $ok = $stmt->execute();
      $stmt->close();
      if ($ok) {
        header("Location: product.php?slug=" . urlencode($useSlug) . "#reviews");
        exit;
      } else {
        $form_error = "Failed to save review.";
      }
    } else {
      $form_error = "Failed to save review (database error).";
    }
  } else {
    $form_error = "Reviews are not available (DB missing).";
  }
}

/* ---------- Fetch reviews & average (if DB) ---------- */
$reviews = []; $avgRating = 0.0; $reviewCount = 0;
$prodSlug = $product['slug'] ?? ($slug ?: '');
if ($usedDb && $prodSlug !== '') {
  $stmt = $conn->prepare("SELECT name, rating, comment, created_at FROM reviews WHERE slug = ? ORDER BY created_at DESC");
  if ($stmt) { $stmt->bind_param('s',$prodSlug); $stmt->execute(); $res = $stmt->get_result(); if ($res) $reviews = $res->fetch_all(MYSQLI_ASSOC); $stmt->close(); }
  $stmt2 = $conn->prepare("SELECT AVG(rating) AS avg_r, COUNT(*) AS cnt FROM reviews WHERE slug = ?");
  if ($stmt2) { $stmt2->bind_param('s',$prodSlug); $stmt2->execute(); $stmt2->bind_result($avgRating,$reviewCount); $stmt2->fetch(); $stmt2->close(); if (!$avgRating) $avgRating = 0.0; }
}

/* ---------- Increment view_count (if DB) ---------- */
if ($usedDb && !empty($prodSlug)) {
  $stmt = $conn->prepare("UPDATE products SET view_count = COALESCE(view_count,0) + 1 WHERE slug = ?");
  if ($stmt) { $stmt->bind_param('s',$prodSlug); $stmt->execute(); $stmt->close(); }
}

/* ---------- Build WhatsApp link (if configured) ---------- */
$waUrl = '';
if (defined('ADMIN_WHATSAPP') && ADMIN_WHATSAPP) {
  $productLink = abs_url_from_path('product.php?slug=' . urlencode($prodSlug));
  $imageUrl    = abs_url_from_path( (function($p){ return (isset($p['img']) && $p['img']) ? product_image($p['img']) : (isset($p['image']) ? product_image($p['image']) : ''); })($product) );
  $pricePlain  = trim(strip_tags(format_price($product['price'] ?? 0)));
  $waText  = "Hello, I want to know more / buy:\n*".($product['name'] ?? '')."*\nPrice: {$pricePlain}\nProduct ID: ".($prodSlug)."\nLink: {$productLink}\nImage: {$imageUrl}";
  $waUrl = 'https://wa.me/' . ADMIN_WHATSAPP . '?text=' . rawurlencode($waText);
}

/* ---------- Related products: prefer DB random, fallback to demo ---------- */
$related = []; $need = 4;
if ($usedDb) {
  $stmt = $conn->prepare("SELECT name, slug, COALESCE(image,'') AS img, price FROM products WHERE (status IS NULL OR status <> 'inactive') AND slug <> ? ORDER BY RAND() LIMIT ?");
  if ($stmt) {
    $stmt->bind_param('si', $prodSlug, $need);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) $related = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
  }
}
if (count($related) < $need) {
  // add from demo_products (exclude current)
  $candidates = array_filter($demo_products, function($p) use ($prodSlug){ return ($p['slug'] ?? '') !== $prodSlug; });
  $candidates = array_values($candidates);
  if (!empty($candidates)) {
    shuffle($candidates);
    foreach ($candidates as $c) { $related[] = $c; if (count($related) >= $need) break; }
  }
}
if (count($related) > $need) $related = array_slice($related,0,$need);

/* ---------- Render page (product exists) ---------- */
?>
<section class="py-10 bg-cream">
  <div class="max-w-7xl mx-auto px-4">
    <!-- Detail -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 bg-white p-6 rounded-lg shadow">
      <div>
        <img src="<?php echo htmlspecialchars( (isset($product['img']) && $product['img']) ? product_image($product['img']) : (isset($product['image']) ? product_image($product['image']) : '') ); ?>"
             alt="<?php echo h($product['name']); ?>" class="w-full h-auto object-contain rounded">
      </div>

      <div>
        <h1 class="text-3xl font-bold text-deepgreen"><?php echo h($product['name']); ?></h1>
        <p class="text-gold text-2xl font-semibold mt-3"><?php echo format_price($product['price'] ?? 0); ?></p>

        <p class="mt-4 text-darkgray">Category:
          <span class="capitalize"><?php echo h($product['category_name'] ?? ($product['category'] ?? 'Uncategorized')); ?></span>
        </p>

        <div class="mt-3 text-darkgray">
          <span class="font-semibold">Rating:</span>
          <span aria-label="<?php echo number_format($avgRating,1); ?> out of 5"><?php echo render_stars($avgRating); ?></span>
          <span class="ml-2">(<?php echo (int)$reviewCount; ?> reviews)</span>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
          <a href="cart.php?action=add&slug=<?php echo urlencode($product['slug'] ?? $prodSlug); ?>&redirect=checkout"class="bg-deepgreen text-white px-6 py-3 rounded hover:bg-gold hover:text-darkgray">Buy Now</a>
          <a href="cart.php?action=add&slug=<?php echo urlencode($product['slug'] ?? $prodSlug); ?>" class="bg-gold text-white px-6 py-3 rounded hover:bg-deepgreen">Add to Cart</a>
          <?php if ($waUrl): ?>
            <a href="<?php echo htmlspecialchars($waUrl); ?>" target="_blank" rel="noopener nofollow" class="flex items-center gap-2 px-6 py-3 rounded bg-[#25D366] text-white hover:opacity-90">
              <!-- WA icon inline -->
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true"><path d="M19.11 17.32c-.27-.14-1.6-.79-1.85-.88-.25-.09-.43-.14-.61.14-.18.27-.7.88-.86 1.06-.16.18-.32.2-.59.07-.27-.14-1.16-.43-2.21-1.37-.82-.73-1.37-1.64-1.53-1.91-.16-.27-.02-.42.12-.56.13-.13.27-.32.41-.48.14-.16.18-.27.27-.45.09-.18.05-.34-.02-.48-.07-.14-.61-1.46-.83-2-.22-.53-.45-.46-.61-.46-.16 0-.34-.02-.52-.02s-.48.07-.73.34c-.25.27-.96.94-.96 2.29s.99 2.66 1.13 2.84c.14.18 1.94 2.96 4.7 4.14.66.29 1.17.46 1.57.59.66.21 1.26.18 1.73.11.53-.08 1.6-.65 1.83-1.28.23-.63.23-1.17.16-1.28-.07-.11-.25-.18-.52-.32z"/><path d="M16.01 3C9.37 3 4 8.37 4 15.01c0 2.64.86 5.08 2.32 7.06L5 29l7.1-1.86c1.92 1.26 4.22 2 6.69 2 6.64 0 11.99-5.37 11.99-12.01S22.65 3 16.01 3zm0 21.78c-2.33 0-4.49-.76-6.25-2.06l-.45-.33-4.27 1.12 1.14-4.16-.3-.43A9.66 9.66 0 0 1 6.3 15c0-5.37 4.36-9.73 9.73-9.73S25.76 9.63 25.76 15 21.38 24.78 16 24.78z"/></svg>
              Buy on WhatsApp
            </a>
          <?php endif; ?>
        </div>

        <div class="mt-6">
          <a href="products.php?category=<?php echo urlencode($product['category_name'] ?? ($product['category'] ?? '')); ?>" class="text-gold underline">More in <?php echo h(ucfirst($product['category_name'] ?? ($product['category'] ?? ''))); ?></a>
        </div>

        <div class="mt-6 text-darkgray">
          <?php if (!empty($product['description'])): ?>
            <?php echo nl2br(h($product['description'])); ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Reviews + Related... (same structure as before) -->
    <div id="reviews" class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-xl font-bold text-deepgreen mb-4">Write a review</h3>
        <?php if(!empty($form_error)): ?><p class="mb-3 text-red-600"><?php echo h($form_error); ?></p><?php endif; ?>
        <form action="product.php?slug=<?php echo urlencode($prodSlug); ?>#reviews" method="POST" class="space-y-3">
          <input type="hidden" name="review_submit" value="1">
          <div>
            <label class="block text-sm mb-1">Your Name</label>
            <input name="name" required class="w-full border rounded px-3 py-2" placeholder="Enter your name">
          </div>
          <div>
            <label class="block text-sm mb-1">Rating</label>
            <select name="rating" required class="w-full border rounded px-3 py-2">
              <option value="">Select rating</option>
              <option value="5">5 - Excellent</option>
              <option value="4">4 - Very good</option>
              <option value="3">3 - Good</option>
              <option value="2">2 - Fair</option>
              <option value="1">1 - Poor</option>
            </select>
          </div>
          <div>
            <label class="block text-sm mb-1">Your Review</label>
            <textarea name="comment" required rows="4" class="w-full border rounded px-3 py-2" placeholder="Write your experience..."></textarea>
          </div>
          <button class="bg-deepgreen text-white px-4 py-2 rounded hover:bg-gold hover:text-darkgray">Submit Review</button>
        </form>
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-xl font-bold text-deepgreen mb-4">Customer reviews</h3>
        <?php if (empty($reviews)): ?>
          <p class="text-gray-600">No reviews yet. Be the first to review.</p>
        <?php else: ?>
          <div class="space-y-4 max-h-[420px] overflow-y-auto pr-2">
            <?php foreach ($reviews as $r): ?>
              <div class="border-b pb-3">
                <div class="flex items-center justify-between">
                  <p class="font-semibold"><?php echo h($r['name']); ?></p>
                  <span class="text-sm text-gray-500"><?php echo h(date("d M Y", strtotime($r['created_at']))); ?></span>
                </div>
                <div class="text-yellow-600"><?php echo render_stars((float)$r['rating']); ?></div>
                <p class="text-darkgray mt-1"><?php echo nl2br(h($r['comment'])); ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <h2 class="text-2xl font-bold text-deepgreen mt-12 mb-6">Related Products</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
      <?php if (empty($related)): ?>
        <p class="col-span-4 text-gray-600">No related products.</p>
      <?php else: foreach ($related as $rp): ?>
        <div class="bg-white shadow rounded-lg p-4 hover:shadow-lg transition text-center">
          <a href="product.php?slug=<?php echo urlencode($rp['slug']); ?>">
            <img src="<?php echo htmlspecialchars( (isset($rp['img']) && $rp['img']) ? product_image($rp['img']) : '' ); ?>" alt="<?php echo h($rp['name']); ?>" class="h-48 w-full object-contain mx-auto">
          </a>
          <a href="product.php?slug=<?php echo urlencode($rp['slug']); ?>"><h3 class="mt-2 text-darkgray font-medium"><?php echo h($rp['name']); ?></h3></a>
          <p class="text-gold font-semibold"><?php echo format_price($rp['price'] ?? 0); ?></p>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <div class="mt-10">
      <a href="products.php" class="text-gold underline">← Back to all products</a>
    </div>
  </div>
</section>

<?php
include __DIR__ . '/partials/footer.php';
include __DIR__ . '/partials/scripts.php';
?>
