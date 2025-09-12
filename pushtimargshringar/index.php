<?php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

/* ------------------------------
   sample arrays (same as yours)
   ------------------------------ */
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

<!-- Banner (keep or remove as you like) -->
<div class="relative w-full h-[50vh] md:h-[65vh] overflow-hidden">
  <div id="slider" class="flex w-full h-full transition-transform duration-700 ease-in-out">
    <?php 
      $banners = ["banner1.jpg", "banner2.jpg", "banner3.jpg"];
      foreach($banners as $ban): ?>
      <img src="<?php echo banner_image($ban); ?>" alt="Banner" class="w-full h-full object-cover flex-shrink-0">
    <?php endforeach; ?>
  </div>
  <button id="prev" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-deepgreen text-white px-3 py-1 rounded-full z-40">&#10094;</button>
  <button id="next" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-deepgreen text-white px-3 py-1 rounded-full z-40">&#10095;</button>
</div>

<!-- Explore Collections -->
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
        <img src="<?php echo product_image($cat['img']); ?>" class="mx-auto h-32 object-contain" alt="<?php echo h($cat['title']); ?>">
        <h3 class="mt-2 text-darkgray font-medium"><?php echo h($cat['title']); ?></h3>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- Generic function that prints a marquee block for any array -->
<?php
function render_marquee_block($title, $items, $seeAllUrl = 'products.php', $isSale = false) {
  // unique wrapper id by title
  $id = 'marq_' . preg_replace('/[^a-z0-9]+/i','_', strtolower($title));
  ?>
  <section class="py-10 bg-cream">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-deepgreen"><?php echo h($title); ?></h2>
        <a href="<?php echo h($seeAllUrl); ?>" class="text-sm bg-deepgreen text-white px-4 py-2 rounded hover:bg-gold hover:text-darkgray">See all</a>
      </div>

      <div class="product-marquee-wrapper relative" id="<?php echo $id; ?>" data-speed="22s">
        <div class="product-marquee" aria-hidden="false">
          <div class="product-track">
            <?php
              // duplicate items twice to create smooth infinite loop
              for ($rep=0;$rep<2;$rep++):
                foreach ($items as $product):
                  $slug = $product['slug'] ?? pathinfo($product['img'], PATHINFO_FILENAME);
            ?>
              <div class="product-item bg-white shadow rounded-lg p-4 text-center">
                <a href="<?php echo product_url($slug); ?>">
                  <img src="<?php echo product_image($product['img']); ?>" alt="<?php echo h($product['name'] ?? $product['title'] ?? 'Product'); ?>" class="h-40 w-full object-contain mx-auto">
                </a>
                <a href="<?php echo product_url($slug); ?>">
                  <h3 class="mt-2 text-darkgray font-medium"><?php echo h($product['name'] ?? $product['title'] ?? 'Product'); ?></h3>
                </a>
                <?php if ($isSale): ?>
                  <p class="text-gray-500 line-through"><?php echo format_price($product['old'] ?? 0); ?></p>
                  <p class="text-gold font-semibold"><?php echo format_price($product['new'] ?? $product['price'] ?? 0); ?></p>
                <?php else: ?>
                  <p class="text-gold font-semibold"><?php echo format_price($product['price'] ?? 0); ?></p>
                <?php endif; ?>
                <div class="flex gap-2 mt-3">
                  <a href="checkout.php?slug=<?php echo urlencode($slug); ?>" class="flex-1 bg-deepgreen text-white py-2 rounded hover:bg-gold hover:text-darkgray">Buy Now</a>
                  <button type="button" class="add-to-cart flex-1 bg-gold text-white py-2 rounded hover:bg-deepgreen" data-slug="<?php echo h($slug); ?>" data-qty="1">Add to Cart</button>
                </div>
              </div>
            <?php
                endforeach;
              endfor;
            ?>
          </div>
        </div>

        <!-- arrows -->
        <button class="marq-arrow marq-left" aria-label="Previous">‹</button>
        <button class="marq-arrow marq-right" aria-label="Next">›</button>
      </div>
    </div>
  </section>
  <?php
}
?>

<?php
// render each block using the helper
render_marquee_block('Best Sellers', $bestSellers, 'products.php', false);
render_marquee_block('Best Fabric Collection', $fabrics, 'products.php?category=fabric', false);
render_marquee_block('Best Pichwai Collection', $pichwai, 'products.php?category=pichwai', false);
render_marquee_block('Featured Products', $featured, 'products.php?tag=featured', false);
render_marquee_block('Products on Sale', $sale, 'products.php?tag=sale', true);
render_marquee_block('Most Viewed', $mostViewed, 'products.php', false);
?>

<!-- Testimonials (kept simple, not marquee) -->
<section class="bg-cream py-10">
  <div class="max-w-7xl mx-auto px-4">
    <h2 class="text-2xl font-bold text-center text-deepgreen mb-8">What Our Clients Say</h2>
    <div class="carousel-wrap relative">
      <div class="flex space-x-4 overflow-x-auto pb-4 h-scroll">
        <?php 
          $testimonials = [
            ["text" => "High quality Shringar, very fast delivery!", "name" => "Arti Sahu"],
            ["text" => "Shrinath ji vastra is very beautiful. Loved it.", "name" => "Poonam Shah"],
            ["text" => "Best quality in affordable price.", "name" => "Jagruti Shah"],
          ];
          foreach($testimonials as $t): ?>
          <div class="bg-white shadow p-6 rounded-lg min-w-[250px]">
            <p class="text-darkgray">"<?php echo h($t['text']); ?>"</p>
            <h4 class="mt-4 font-bold">- <?php echo h($t['name']); ?></h4>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php';?>

<!-- Styles for marquee blocks -->
<style>
/* product marquee - shared styles for all blocks */
.product-marquee-wrapper { position: relative; overflow: visible; margin-top: 8px; }
.product-marquee { overflow: hidden; width: 100%; padding: 10px 0; }
.product-track {
  display: flex;
  align-items: stretch;
  gap: 16px;
  animation: marquee-scroll linear infinite;
  will-change: transform;
}
.product-track:hover { animation-play-state: paused; }

/* item sizing - tune these widths to match your design */
.product-item { flex: 0 0 auto; width: 200px; min-width: 200px; box-sizing: border-box; }
@media (min-width:768px){ .product-item { width: 220px; min-width:220px; } }
@media (min-width:1024px){ .product-item { width: 240px; min-width:240px; } }

/* marquee animation moves left by half (we duplicated items) */
@keyframes marquee-scroll {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

/* manual arrows */
.marq-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 50;
  width: 44px; height: 44px;
  border-radius: 999px;
  display:flex;align-items:center;justify-content:center;
  background:white;border:1px solid rgba(0,0,0,0.06);
  box-shadow:0 6px 14px rgba(0,0,0,0.08);
  cursor:pointer;
}
.marq-left { left: 8px; }
.marq-right { right: 8px; }

/* responsive: keep arrows visible on small if you want */
@media (max-width:640px){ .marq-arrow { display: flex; } }

/* slightly separate from other CSS */
.product-item img { border-radius:6px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
</style>

<!-- JS: initialize marquees and wire arrows -->
<script>
(function(){
  // default speed (duration) - can override by data-speed attribute on wrapper (e.g. "18s")
  const defaultDuration = '22s';

  document.querySelectorAll('.product-marquee-wrapper').forEach(wrapper => {
    const track = wrapper.querySelector('.product-track');
    if (!track) return;

    // set animation-duration from wrapper attr
    const speed = wrapper.getAttribute('data-speed') || defaultDuration;
    track.style.animationDuration = speed;

    const leftBtn = wrapper.querySelector('.marq-left');
    const rightBtn = wrapper.querySelector('.marq-right');

    // compute slide size based on first child width + gap
    function slideSize() {
      const first = track.querySelector('.product-item');
      if (!first) return Math.round(track.clientWidth * 0.25);
      const gap = parseInt(getComputedStyle(track).gap || 16, 10) || 16;
      return Math.round(first.getBoundingClientRect().width + gap);
    }

    // Arrow manual control: pause CSS animation, perform smooth scroll, resume
    function pauseAnim() { track.style.animationPlayState = 'paused'; }
    function resumeAnim() { track.style.animationPlayState = 'running'; }

    leftBtn && leftBtn.addEventListener('click', function(){
      pauseAnim();
      track.scrollBy({ left: -slideSize(), behavior: 'smooth' });
      setTimeout(resumeAnim, 700);
    });
    rightBtn && rightBtn.addEventListener('click', function(){
      pauseAnim();
      track.scrollBy({ left: slideSize(), behavior: 'smooth' });
      setTimeout(resumeAnim, 700);
    });

    // keep scroll position looped: when reach half (original length), snap back
    track.addEventListener('scroll', function(){
      // half of width equals original list length (since items duplicated once)
      const half = track.scrollWidth / 2;
      if (track.scrollLeft >= half) {
        track.scrollLeft = track.scrollLeft - half;
      } else if (track.scrollLeft <= 0) {
        // if user scrolls to negative side, nudge forward
        // (unlikely but safe)
        track.scrollLeft = track.scrollLeft + half;
      }
    });

    // ensure scrollLeft is reset to 0 to avoid odd start position
    track.scrollLeft = 0;

    // optional: pause on hover (handled via CSS animation-play-state),
    // but also pause scroll movement for the track's scrollLeft sync:
    wrapper.addEventListener('mouseenter', ()=> {
      track.style.animationPlayState = 'paused';
    });
    wrapper.addEventListener('mouseleave', ()=> {
      track.style.animationPlayState = 'running';
    });
  });

  // Optional: tune global marquee speed based on viewport width
  function adjustSpeeds(){
    const wide = window.innerWidth >= 1200;
    document.querySelectorAll('.product-marquee-wrapper').forEach(w => {
      const track = w.querySelector('.product-track');
      if (!track) return;
      // shorter duration on large screens (fewer repeats visible)
      track.style.animationDuration = wide ? '20s' : '26s';
    });
  }
  window.addEventListener('resize', adjustSpeeds);
  adjustSpeeds();

})();
</script>

<?php include(__DIR__ . "/partials/scripts.php"); ?>
