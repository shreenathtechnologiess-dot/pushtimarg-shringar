<?php include("partials/head.php"); ?>
<?php include("partials/header.php"); ?>
<?php include("app/config.php"); ?> <!-- helpers: product_url, product_image, banner_image, format_price -->

<!-- Hero Slider -->
<div class="relative w-full h-[50vh] md:h-[65vh] overflow-hidden">
  <div id="slider" class="flex w-full h-full transition-transform duration-700 ease-in-out">
    <?php 
      $banners = ["banner1.jpg", "banner2.jpg", "banner3.jpg"];
      foreach($banners as $ban): ?>
      <img src="<?php echo banner_image($ban); ?>" alt="Banner" class="w-full h-full object-cover flex-shrink-0">
    <?php endforeach; ?>
  </div>
  <button id="prev" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-deepgreen text-white px-3 py-1 rounded-full">&#10094;</button>
  <button id="next" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-deepgreen text-white px-3 py-1 rounded-full">&#10095;</button>
</div>

<!-- Categories Section -->
<section class="py-10 max-w-7xl mx-auto px-4">
  <div class="flex items-center justify-between mb-8">
    <h2 class="text-2xl font-bold text-center text-gold">Explore Collections</h2>
    <a href="products.php" class="text-sm bg-deepgreen text-white px-4 py-2 rounded hover:bg-gold hover:text-darkgray">See all</a>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-6 justify-center">
    <?php 
      $categories = [
        ["img" => "shringar.jpg", "title" => "Shringar", "key" => "shringar"],
        ["img" => "pichwai.jpg", "title" => "Pichwai", "key" => "pichwai"],
        ["img" => "vastra.jpg", "title" => "Vastra", "key" => "vastra"],
        ["img" => "uparna.jpg", "title" => "Uparna", "key" => "uparna"],
      ];
      foreach($categories as $cat): ?>
      <a href="products.php?category=<?php echo urlencode($cat['key']); ?>" class="bg-white rounded-lg shadow p-4 text-center hover:shadow-lg transition block">
        <img src="<?php echo product_image($cat['img']); ?>" class="mx-auto h-32 object-contain" alt="<?php echo htmlspecialchars($cat['title']); ?>">
        <h3 class="mt-2 text-darkgray font-medium"><?php echo htmlspecialchars($cat['title']); ?></h3>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<?php
/* ===============================
   DATA (you can swap with DB later)
   =============================== */
$bestSellers = [
  ["name" => "Shringar",     "img" => "shringar.jpg",     "price" => 100,  "slug" => "shringar"],
  ["name" => "Pichwai",      "img" => "pichwai.jpg",      "price" => 200,  "slug" => "pichwai"],
  ["name" => "Vastra",       "img" => "vastra.jpg",       "price" => 300,  "slug" => "vastra"],
  ["name" => "Uparna",       "img" => "uparna.jpg",       "price" => 400,  "slug" => "uparna"],
  ["name" => "Sinhasan",     "img" => "sinhasan.jpg",     "price" => 500,  "slug" => "sinhasan"],
  ["name" => "Accessories",  "img" => "accessories.jpg",  "price" => 600,  "slug" => "accessories"],
];

$fabrics = [
  ["img" => "blue-cotton.jpg",        "name" => "Blue Paisley Cotton",               "price" => 400,  "slug" => "blue-cotton"],
  ["img" => "shreenathji-print.jpg",  "name" => "Blue Krishna Pichwai Print",        "price" => 800,  "slug" => "shreenathji-print"],
  ["img" => "maroon-paisley.jpg",     "name" => "Maroon Paisley Design",            "price" => 1200, "slug" => "maroon-paisley"],
  ["img" => "golden-banarasi.jpg",    "name" => "Golden Floral Brocade",            "price" => 1600, "slug" => "golden-banarasi"],
  ["img" => "green-silk.jpg",         "name" => "Green Krishna Motif",              "price" => 2000, "slug" => "green-silk"],
  ["img" => "linen-blend.jpg",        "name" => "Beige & Red Floral Pattern",       "price" => 2400, "slug" => "linen-blend"],
  ["img" => "purple-silk.jpg",        "name" => "Purple Silk with Golden Booti",    "price" => 2800, "slug" => "purple-silk"],
  ["img" => "green-pichwai.jpg",      "name" => "Green Banarasi Silk",              "price" => 3200, "slug" => "green-pichwai"],
];

$pichwai = [
  ["img" => "cow-krishna-pichwai.jpg",       "name" => "Cow and Calf on Lotus",            "price" => 500,  "slug" => "cow-krishna-pichwai"],
  ["img" => "radha-krishna-pichwai.jpg",     "name" => "Radha Krishna with Peacock",       "price" => 1000, "slug" => "radha-krishna-pichwai"],
  ["img" => "shreenathji-elephant-pichwai.jpg","name" => "Colorful Shreenathji Painting",  "price" => 1500, "slug" => "shreenathji-elephant-pichwai"],
  ["img" => "tree-pichwai.jpg",              "name" => "Peacock under Tree",               "price" => 2000, "slug" => "tree-pichwai"],
  ["img" => "peacock-pichwai.jpg",           "name" => "Shreenathji Yellow Background",    "price" => 2500, "slug" => "peacock-pichwai"],
  ["img" => "dancing-gopis-pichwai.jpg",     "name" => "Krishna with Cows",                "price" => 3000, "slug" => "dancing-gopis-pichwai"],
  ["img" => "lotus-pond-pichwai.jpg",        "name" => "Shreenathji with Lotus Motifs",    "price" => 3500, "slug" => "lotus-pond-pichwai"],
  ["img" => "pink-floral-pichwai.jpg",       "name" => "Shreenathji with Cows & Lotus",    "price" => 4000, "slug" => "pink-floral-pichwai"],
];

$featured = [
  ["img" => "linen-blend.jpg",        "name" => "Linen Blend Fabric",    "price" => 1200, "slug" => "linen-blend"],
  ["img" => "cream-banarasi.jpeg",    "name" => "Maroon Cream Vastra",   "price" => 2000, "slug" => "cream-banarasi"],
  ["img" => "krishna-mukut.jpeg",     "name" => "Golden Mukut",          "price" => 1500, "slug" => "krishna-mukut"],
  ["img" => "orange-vastra.jpeg",     "name" => "Orange Vastra",         "price" => 2200, "slug" => "orange-vastra"],
  ["img" => "premium-fabric.jpeg",    "name" => "Premium Fabric",        "price" => 1800, "slug" => "premium-fabric"],
  ["img" => "purple-silk.jpg",        "name" => "Purple Silk Vastra",    "price" => 2500, "slug" => "purple-silk"],
];

$sale = [
  ["img" => "banarasi-vastra.jpeg", "name" => "Banarasi Vastra (Maroon)", "old" => 3000, "new" => 2500, "slug" => "banarasi-vastra"],
  ["img" => "blue-cotton.jpg",      "name" => "Blue Cotton Fabric",       "old" => 1200, "new" => 900,  "slug" => "blue-cotton"],
  ["img" => "cream-pagdi.jpeg",     "name" => "Cream Pagdi",              "old" => 1500, "new" => 1100, "slug" => "cream-pagdi"],
  ["img" => "green-pichwai.jpg",    "name" => "Green Fabric",             "old" => 2000, "new" => 1600, "slug" => "green-pichwai"],
  ["img" => "krishna-mukut.jpeg",   "name" => "Golden Mukut",             "old" => 1800, "new" => 1400, "slug" => "krishna-mukut"],
  ["img" => "royal-blue.jpeg",      "name" => "Royal Blue Vastra",        "old" => 2500, "new" => 2000, "slug" => "royal-blue"],
];

$mostViewed = [
  ["img" => "banarasi-vastra.jpeg", "name" => "Banarasi-Vastra Maroon", "price" => 350,  "slug" => "banarasi-vastra"],
  ["img" => "cream-banarasi.jpeg",  "name" => "Maroon Cream Vastra",   "price" => 9500, "slug" => "cream-banarasi"],
  ["img" => "krishna-mukut.jpeg",   "name" => "Golden Mukut",          "price" => 250,  "slug" => "krishna-mukut"],
];
?>

<!-- Best Sellers -->
<section class="py-10 bg-cream">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-deepgreen">Best Sellers</h2>
      <a href="products.php" class="text-sm bg-deepgreen text-white px-4 py-2 rounded hover:bg-gold hover:text-darkgray">See all</a>
    </div>
    <div class="flex space-x-4 overflow-x-auto pb-4">
      <?php foreach ($bestSellers as $product): 
        $slug = $product['slug'] ?? pathinfo($product['img'], PATHINFO_FILENAME);
      ?>
        <div class="bg-white shadow rounded-lg p-4 min-w-[200px] hover:shadow-lg transition text-center">
          <a href="<?php echo product_url($slug); ?>">
            <img src="<?php echo product_image($product['img']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-40 w-full object-contain mx-auto">
          </a>
          <a href="<?php echo product_url($slug); ?>">
            <h3 class="mt-2 text-darkgray font-medium"><?php echo htmlspecialchars($product['name']); ?></h3>
          </a>
          <p class="text-gold font-semibold"><?php echo format_price($product['price']); ?></p>
          <div class="flex gap-2 mt-3">
            <a href="checkout.php?slug=<?php echo urlencode($slug); ?>" class="flex-1 bg-deepgreen text-white py-2 rounded hover:bg-gold hover:text-darkgray">Buy Now</a>
            <a href="cart.php?action=add&slug=<?php echo urlencode($slug); ?>" class="flex-1 bg-gold text-white py-2 rounded hover:bg-deepgreen">Add to Cart</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Short Promo Banners -->
<section class="grid grid-cols-1 md:grid-cols-3 gap-4 px-4 py-10 max-w-7xl mx-auto">
  <?php 
    $promos = [
      ["title" => "Best Shringar", "desc" => "Explore premium shringar items", "color" => "bg-deepgreen"],
      ["title" => "Best Vastra",   "desc" => "Elegant fabrics & vastra",       "color" => "bg-gold"],
      ["title" => "Best Sahitya",  "desc" => "Exclusive accessories",          "color" => "bg-rust"]
    ];
    foreach($promos as $promo): ?>
    <div class="<?php echo $promo['color']; ?> text-white p-6 rounded-lg shadow text-center">
      <h3 class="font-bold text-xl"><?php echo htmlspecialchars($promo['title']); ?></h3>
      <p><?php echo htmlspecialchars($promo['desc']); ?></p>
    </div>
  <?php endforeach; ?>
</section>

<!-- Best Fabric Collection -->
<section class="py-10 bg-cream">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gold">Best Fabric Collection</h2>
      <a href="products.php?category=fabric" class="text-sm bg-deepgreen text-white px-4 py-2 rounded hover:bg-gold hover:text-darkgray">See all</a>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      <?php foreach($fabrics as $fab): 
        $slug = $fab['slug'] ?? pathinfo($fab['img'], PATHINFO_FILENAME);
      ?>
      <div class="bg-white shadow rounded-lg p-4 hover:shadow-lg transition text-center">
        <a href="<?php echo product_url($slug); ?>">
          <img src="<?php echo product_image($fab['img']); ?>" alt="<?php echo htmlspecialchars($fab['name']); ?>" class="h-48 w-full object-contain mx-auto">
        </a>
        <a href="<?php echo product_url($slug); ?>">
          <h3 class="mt-2 text-darkgray font-medium"><?php echo htmlspecialchars($fab['name']); ?></h3>
        </a>
        <p class="text-gold font-semibold"><?php echo format_price($fab['price']); ?></p>
        <div class="flex gap-2 mt-3">
          <a href="checkout.php?slug=<?php echo urlencode($slug); ?>" class="flex-1 bg-deepgreen text-white py-2 rounded hover:bg-gold hover:text-darkgray">Buy Now</a>
          <a href="cart.php?action=add&slug=<?php echo urlencode($slug); ?>" class="flex-1 bg-gold text-white py-2 rounded hover:bg-deepgreen">Add to Cart</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Best Pichwai Collection -->
<section class="py-10">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gold">Best Pichwai Collection</h2>
      <a href="products.php?category=pichwai" class="text-sm bg-deepgreen text-white px-4 py-2 rounded hover:bg-gold hover:text-darkgray">See all</a>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      <?php foreach($pichwai as $p): 
        $slug = $p['slug'] ?? pathinfo($p['img'], PATHINFO_FILENAME);
      ?>
      <div class="bg-white shadow rounded-lg p-4 hover:shadow-lg transition text-center">
        <a href="<?php echo product_url($slug); ?>">
          <img src="<?php echo product_image($p['img']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="h-48 w-full object-contain mx-auto">
        </a>
        <a href="<?php echo product_url($slug); ?>">
          <h3 class="mt-2 text-darkgray"><?php echo htmlspecialchars($p['name']); ?></h3>
        </a>
        <p class="text-gold font-semibold"><?php echo format_price($p['price']); ?></p>
        <div class="flex gap-2 mt-3">
          <a href="checkout.php?slug=<?php echo urlencode($slug); ?>" class="flex-1 bg-deepgreen text-white py-2 rounded hover:bg-gold hover:text-darkgray">Buy Now</a>
          <a href="cart.php?action=add&slug=<?php echo urlencode($slug); ?>" class="flex-1 bg-gold text-white py-2 rounded hover:bg-deepgreen">Add to Cart</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Featured Products -->
<section class="py-10">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gold">Featured Products</h2>
      <a href="products.php?tag=featured" class="text-sm bg-deepgreen text-white px-4 py-2 rounded hover:bg-gold hover:text-darkgray">See all</a>
    </div>
    <div class="flex space-x-4 overflow-x-auto pb-4">
      <?php foreach($featured as $feat): 
        $slug = $feat['slug'] ?? pathinfo($feat['img'], PATHINFO_FILENAME);
      ?>
      <div class="bg-white shadow rounded-lg p-4 min-w-[220px] hover:shadow-lg transition text-center">
        <a href="<?php echo product_url($slug); ?>">
          <img src="<?php echo product_image($feat['img']); ?>" alt="<?php echo htmlspecialchars($feat['name']); ?>" class="h-44 w-full object-contain mx-auto">
        </a>
        <a href="<?php echo product_url($slug); ?>">
          <h3 class="mt-2 text-darkgray"><?php echo htmlspecialchars($feat['name']); ?></h3>
        </a>
        <p class="text-gold font-semibold"><?php echo format_price($feat['price']); ?></p>
        <div class="flex gap-2 mt-3">
          <a href="checkout.php?slug=<?php echo urlencode($slug); ?>" class="flex-1 bg-deepgreen text-white py-2 rounded hover:bg-gold hover:text-darkgray">Buy Now</a>
          <a href="cart.php?action=add&slug=<?php echo urlencode($slug); ?>" class="flex-1 bg-gold text-white py-2 rounded hover:bg-deepgreen">Add to Cart</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Products on Sale -->
<section class="py-10 bg-cream">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gold">Products on Sale</h2>
      <a href="products.php?tag=sale" class="text-sm bg-deepgreen text-white px-4 py-2 rounded hover:bg-gold hover:text-darkgray">See all</a>
    </div>
    <div class="flex space-x-4 overflow-x-auto pb-4">
      <?php foreach($sale as $s): 
        $slug = $s['slug'] ?? pathinfo($s['img'], PATHINFO_FILENAME);
      ?>
      <div class="bg-white shadow rounded-lg p-4 min-w-[200px] hover:shadow-lg transition text-center">
        <a href="<?php echo product_url($slug); ?>">
          <img src="<?php echo product_image($s['img']); ?>" alt="<?php echo htmlspecialchars($s['name']); ?>" class="h-40 w-full object-contain mx-auto">
        </a>
        <a href="<?php echo product_url($slug); ?>">
          <h3 class="mt-2 text-darkgray"><?php echo htmlspecialchars($s['name']); ?></h3>
        </a>
        <p class="text-gray-500 line-through"><?php echo format_price($s['old']); ?></p>
        <p class="text-gold font-semibold"><?php echo format_price($s['new']); ?></p>
        <div class="flex gap-2 mt-3">
          <a href="checkout.php?slug=<?php echo urlencode($slug); ?>" class="flex-1 bg-deepgreen text-white py-2 rounded hover:bg-gold hover:text-darkgray">Buy Now</a>
          <a href="cart.php?action=add&slug=<?php echo urlencode($slug); ?>" class="flex-1 bg-gold text-white py-2 rounded hover:bg-deepgreen">Add to Cart</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section class="bg-cream py-10">
  <div class="max-w-7xl mx-auto px-4">
    <h2 class="text-2xl font-bold text-center text-deepgreen mb-8">What Our Clients Say</h2>
    <div class="flex space-x-4 overflow-x-auto">
      <?php 
        $testimonials = [
          ["text" => "High quality Shringar, very fast delivery!", "name" => "Arti Sahu"],
          ["text" => "Shrinath ji vastra is very beautiful. Loved it.", "name" => "Poonam Shah"],
          ["text" => "Best quality in affordable price.", "name" => "Jagruti Shah"],
        ];
        foreach($testimonials as $t): ?>
        <div class="bg-white shadow p-6 rounded-lg min-w-[250px]">
          <p class="text-darkgray">"<?php echo htmlspecialchars($t['text']); ?>"</p>
          <h4 class="mt-4 font-bold">- <?php echo htmlspecialchars($t['name']); ?></h4>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Most Viewed Products -->
<div class="bg-white py-6 border-t border-gray-200">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-bold text-deepgreen">Most Viewed</h3>
      <a href="products.php" class="text-sm bg-deepgreen text-white px-3 py-1 rounded hover:bg-gold hover:text-darkgray">See all</a>
    </div>
    <div class="flex space-x-4 overflow-x-auto pb-2">
      <?php foreach($mostViewed as $mv): 
        $slug = $mv['slug'] ?? pathinfo($mv['img'], PATHINFO_FILENAME);
      ?>
        <a href="<?php echo product_url($slug); ?>" class="min-w-[150px] bg-gray-50 border rounded p-2 text-center block">
          <img src="<?php echo product_image($mv['img']); ?>" class="h-24 mx-auto object-contain" alt="<?php echo htmlspecialchars($mv['name']); ?>">
          <p class="text-sm mt-2"><?php echo htmlspecialchars($mv['name']); ?></p>
          <p class="text-gold font-semibold"><?php echo format_price($mv['price']); ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php include("partials/footer.php"); ?>
<?php include("partials/scripts.php"); ?>
