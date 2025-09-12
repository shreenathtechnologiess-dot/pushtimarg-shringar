<?php include("partials/head.php"); ?>
<?php include("partials/header.php"); ?>
<?php include("app/config.php"); ?>

<?php
if (!function_exists('slugify')) {
  function slugify($text){ $text=strtolower(trim($text)); $text=preg_replace('/[^a-z0-9]+/i','-',$text); return trim($text,'-'); }
}

/* ---------- Fetch products from DB ---------- */
$products = [];
if (isset($conn) && $conn instanceof mysqli) {
  $sql = "SELECT id, name, slug, image AS img, price, old_price, category, 
                 is_on_sale, status
          FROM products
          WHERE status='active'
          ORDER BY created_at DESC";
  if ($res = $conn->query($sql)) {
    $products = $res->fetch_all(MYSQLI_ASSOC);
    $res->close();
  }
}

// Fallback dummy if DB empty
if (empty($products)) {
  $products = [
    ["name"=>"Blue Cotton","img"=>"blue-cotton.jpg","price"=>400,"old_price"=>500,"category"=>"fabric","is_on_sale"=>1],
    ["name"=>"Golden Banarasi","img"=>"golden-banarasi.jpg","price"=>1200,"old_price"=>null,"category"=>"fabric","is_on_sale"=>0],
    ["name"=>"Orange Vastra","img"=>"orange-vastra.jpeg","price"=>4000,"old_price"=>4500,"category"=>"vastra","is_on_sale"=>1],
  ];
}

// Attach slug if missing
foreach ($products as $k=>$p) {
  if (empty($products[$k]['slug'])) {
    $products[$k]['slug'] = slugify(pathinfo($p['img'] ?? '', PATHINFO_FILENAME) ?: $p['name']);
  }
}

/* ---------- Filters ---------- */
$category = $_GET['category'] ?? '';
$tag      = $_GET['tag'] ?? '';
$min      = (int)($_GET['min'] ?? 0);
$max      = (int)($_GET['max'] ?? 100000);

$filtered = array_filter($products, function($p) use ($category,$tag,$min,$max){
  $okCat = $category ? ($p['category'] ?? '')===$category : true;
  $okTag = $tag ? ($tag==='sale' && !empty($p['is_on_sale'])) : true;
  $okPrice = ($p['price'] >= $min && $p['price'] <= $max);
  return $okCat && $okTag && $okPrice;
});

/* ---------- Most Viewed ---------- */
$mostViewed = [];
if (isset($conn) && $conn instanceof mysqli) {
  $sql = "SELECT name, slug, image AS img, price
          FROM products
          WHERE status='active' AND COALESCE(image,'') <> ''
          ORDER BY view_count DESC, id DESC
          LIMIT 5";
  if ($res = $conn->query($sql)) {
    $mostViewed = $res->fetch_all(MYSQLI_ASSOC);
    $res->close();
  }
}
if (empty($mostViewed)) {
  $mostViewed = [
    ["img"=>"banarasi-vastra.jpeg","name"=>"Banarasi Vastra (Maroon)","price"=>3500,"slug"=>"banarasi-vastra"],
    ["img"=>"cream-banarasi.jpeg","name"=>"Maroon Cream Vastra","price"=>9500,"slug"=>"cream-banarasi"],
    ["img"=>"krishna-mukut.jpeg","name"=>"Golden Mukut","price"=>250,"slug"=>"krishna-mukut"],
  ];
}
foreach ($mostViewed as $k=>$mv) {
  if (empty($mostViewed[$k]['slug'])) {
    $mostViewed[$k]['slug'] = slugify(pathinfo($mv['img'] ?? '', PATHINFO_FILENAME) ?: ($mv['name'] ?? 'item'));
  }
}

/* ---------- Pagination ---------- */
$perPage = 8;
$total = count($filtered);
$totalPages = max(1, ceil($total/$perPage));
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$page = min($page, $totalPages);
$start = ($page-1)*$perPage;
$items = array_slice(array_values($filtered), $start, $perPage);

/* ---------- Title ---------- */
$titleBits=[]; if($tag) $titleBits[]=ucfirst(htmlspecialchars($tag)); if($category) $titleBits[]=ucfirst(htmlspecialchars($category));
$title = $titleBits ? implode(' • ', $titleBits).' Products' : 'Products';

/* ---------- Card renderer ---------- */
function render_card_listing($p){
  $slug = $p['slug']; ?>
  <div class="bg-white shadow rounded-lg p-4 hover:shadow-lg transition text-center relative">

    <!-- On Sale badge -->
    <?php if (!empty($p['is_on_sale'])): ?>
      <span class="absolute top-2 left-2 bg-red-500 text-white text-xs font-semibold px-2 py-1 rounded">
        On Sale
      </span>
    <?php endif; ?>

    <a href="product.php?slug=<?php echo urlencode($slug); ?>">
      <img src="<?php echo htmlspecialchars(product_image($p['img'])); ?>" 
           alt="<?php echo htmlspecialchars($p['name']); ?>" 
           class="h-48 w-full object-contain mx-auto">
    </a>
    <a href="product.php?slug=<?php echo urlencode($slug); ?>">
      <h3 class="mt-2 text-darkgray font-medium"><?php echo htmlspecialchars($p['name']); ?></h3>
    </a>

    <!-- Price with strike-through if on sale -->
    <?php if (!empty($p['is_on_sale']) && !empty($p['old_price'])): ?>
      <p class="text-sm text-gray-500 line-through"><?php echo format_price($p['old_price']); ?></p>
      <p class="text-gold font-bold"><?php echo format_price($p['price']); ?></p>
    <?php else: ?>
      <p class="text-gold font-semibold"><?php echo format_price($p['price']); ?></p>
    <?php endif; ?>

    <!-- ACTIONS -->
    <div class="flex gap-2 mt-3">
      <a href="checkout.php?slug=<?php echo urlencode($slug); ?>"
         class="flex-1 bg-deepgreen text-white py-2 rounded hover:bg-gold hover:text-darkgray">
        Buy Now
      </a>
      <a href="cart.php?action=add&slug=<?php echo urlencode($slug); ?>"
         class="flex-1 bg-gold text-white py-2 rounded hover:bg-deepgreen">
        Add to Cart
      </a>
    </div>
  </div>
<?php } ?>

<section class="py-10 bg-cream">
  <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-8">
    
    <!-- Sidebar -->
    <aside class="bg-white p-4 shadow rounded-lg space-y-8">
      <div>
        <h3 class="text-lg font-bold text-deepgreen mb-4">Browse by Categories</h3>
        <ul class="space-y-2 text-darkgray">
          <li><a href="products.php" class="hover:text-gold">All</a></li>
          <li><a href="?category=fabric" class="hover:text-gold">Fabric</a></li>
          <li><a href="?category=pichwai" class="hover:text-gold">Pichwai</a></li>
          <li><a href="?category=vastra" class="hover:text-gold">Vastra</a></li>
        </ul>
      </div>

      <div>
        <h3 class="text-lg font-bold text-deepgreen mb-4">Quick Filters</h3>
        <ul class="space-y-2 text-darkgray">
          <li><a href="?tag=sale" class="hover:text-gold">On Sale</a></li>
        </ul>
      </div>

      <div>
        <h3 class="text-lg font-bold text-deepgreen mb-4">Filter by Price</h3>
        <form method="GET" class="space-y-2">
          <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
          <input type="hidden" name="tag" value="<?php echo htmlspecialchars($tag); ?>">
          <div class="flex space-x-2">
            <input type="number" name="min" value="<?php echo htmlspecialchars($min); ?>" placeholder="Min" class="w-1/2 border rounded px-2 py-1">
            <input type="number" name="max" value="<?php echo htmlspecialchars($max); ?>" placeholder="Max" class="w-1/2 border rounded px-2 py-1">
          </div>
          <button class="bg-deepgreen text-white w-full py-2 rounded hover:bg-gold hover:text-darkgray">Apply</button>
        </form>
      </div>

      <!-- Most Viewed -->
      <div>
        <h3 class="text-lg font-bold text-deepgreen mb-4">Most Viewed</h3>
        <div class="space-y-4">
          <?php foreach ($mostViewed as $mv): ?>
            <a href="product.php?slug=<?php echo urlencode($mv['slug']); ?>" class="flex items-center gap-3 group">
              <img
                src="<?php echo htmlspecialchars(product_image($mv['img'])); ?>"
                alt="<?php echo htmlspecialchars($mv['name']); ?>"
                class="w-16 h-16 object-contain border rounded bg-white"
              >
              <div class="flex-1">
                <p class="text-sm text-darkgray group-hover:text-gold">
                  <?php echo htmlspecialchars($mv['name']); ?>
                </p>
                <?php if (isset($mv['price'])): ?>
                  <p class="text-xs text-gold font-semibold">
                    <?php echo format_price($mv['price']); ?>
                  </p>
                <?php endif; ?>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </aside>

    <!-- Grid -->
    <div class="col-span-3">
      <h2 class="text-2xl font-bold text-deepgreen mb-6"><?php echo $title; ?></h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php if (empty($items)): ?>
          <p class="col-span-4 text-center text-gray-600">No products found.</p>
        <?php else: foreach ($items as $p) render_card_listing($p); endif; ?>
      </div>

      <!-- Pagination -->
      <div class="flex justify-center mt-8 space-x-2">
        <?php if ($page>1): ?>
          <a class="px-3 py-1 bg-deepgreen text-white rounded" href="?category=<?php echo urlencode($category); ?>&tag=<?php echo urlencode($tag); ?>&min=<?php echo urlencode($min); ?>&max=<?php echo urlencode($max); ?>&page=<?php echo $page-1; ?>">Prev</a>
        <?php endif; ?>
        <?php for($i=1;$i<=$totalPages;$i++): ?>
          <a class="px-3 py-1 rounded <?php echo $i==$page?'bg-gold text-white':'bg-white border'; ?>"
             href="?category=<?php echo urlencode($category); ?>&tag=<?php echo urlencode($tag); ?>&min=<?php echo urlencode($min); ?>&max=<?php echo urlencode($max); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page<$totalPages): ?>
          <a class="px-3 py-1 bg-deepgreen text-white rounded" href="?category=<?php echo urlencode($category); ?>&tag=<?php echo urlencode($tag); ?>&min=<?php echo urlencode($min); ?>&max=<?php echo urlencode($max); ?>&page=<?php echo $page+1; ?>">Next</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include("partials/footer.php"); ?>
<?php include("partials/scripts.php"); ?>
