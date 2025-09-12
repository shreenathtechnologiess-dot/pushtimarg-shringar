<?php include("partials/head.php"); ?>
<?php include("partials/header.php"); ?>

<?php
// adjust path to your config if needed
require_once __DIR__ . '/app/config.php'; // <-- agar config file kahin aur ho to is path ko change karo
require_once __DIR__ . '/app/db.php'; // <-- agar config file kahin aur ho to is path ko change karo

if (!isset($conn) || !($conn instanceof mysqli)) {
  echo '<div class="p-6 text-center text-red-600">Database connection missing.</div>';
  include("partials/footer.php");
  include("partials/scripts.php");
  exit;
}

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// web + disk paths for category images (relative to this file)
$assetsWebPrefix  = 'assets/images/products/';     // used in <img src="">
$assetsDiskPath   = __DIR__ . '/' . $assetsWebPrefix; // used for file_exists check
$placeholderImage = 'assets/images/placeholder-category.png'; // create this or point to existing image
?>

<!-- Categories Page -->
<section class="py-12 bg-cream">
  <div class="max-w-7xl mx-auto px-4">
    <!-- Page Title -->
    <div class="text-center mb-10">
      <h1 class="text-3xl md:text-4xl font-bold text-deepgreen">Our Collections</h1>
      <p class="text-gray-600 mt-2">Explore premium vastra, shringar, fabrics & more</p>
    </div>

    <!-- Categories Grid -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
<?php
// fetch categories with optional product count
$sql = "SELECT c.id, c.name, c.slug, c.image, c.description, COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
        GROUP BY c.id
        ORDER BY c.name ASC";
if ($result = $conn->query($sql)) {
  while ($c = $result->fetch_assoc()) {
    // determine image URL (use placeholder if file missing)
    $imgUrl = $placeholderImage;
    if (!empty($c['image'])) {
      $diskPath = $assetsDiskPath . $c['image'];
      if (file_exists($diskPath) && is_file($diskPath)) {
        $imgUrl = $assetsWebPrefix . h($c['image']);
      }
    }
    $name = h($c['name']);
    $slug = h($c['slug'] ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $c['name'])));
    $desc = h($c['description']);
?>
      <div class="bg-white shadow rounded-xl overflow-hidden hover:shadow-lg transition group">
        <div class="aspect-[4/3] overflow-hidden">
          <img src="<?= $imgUrl ?>" alt="<?= $name ?>" class="w-full h-full object-cover group-hover:scale-105 transition">
        </div>
        <div class="p-5 text-center">
          <h3 class="text-xl font-semibold text-darkgray"><?= $name ?></h3>
          <p class="text-gray-600 mt-1"><?= $desc ?></p>
          <a href="products.php?category=<?= urlencode($slug) ?>" class="mt-4 inline-block bg-deepgreen text-white px-5 py-2 rounded hover:bg-gold hover:text-darkgray">
            View Products
          </a>
        </div>
      </div>
<?php
  } // end while
  $result->free();
} else {
  echo '<div class="col-span-3 text-center text-red-600">Unable to load categories.</div>';
}
?>
    </div>
  </div>
</section>

<?php include("partials/footer.php"); ?>
<?php include("partials/scripts.php"); ?>
