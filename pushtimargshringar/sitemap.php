<?php
// pushtimargshringar/sitemap.php
$SITE_NAME = 'Your Site Name';
$LAST_UPDATED = '2025-09-08';

if (file_exists(__DIR__ . '/partials/head.php')) include_once __DIR__ . '/partials/head.php';
if (file_exists(__DIR__ . '/partials/header.php')) include_once __DIR__ . '/partials/header.php';

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// You can edit the $pages array to match your real site URLs
$pages = [
  ['url' => '/', 'label' => 'Home'],
  ['url' => '/shop.php', 'label' => 'Shop'],
  ['url' => '/cart.php', 'label' => 'Cart'],
  ['url' => '/pushtimargshringar/contact.php', 'label' => 'Contact'],
  ['url' => '/privacy.php', 'label' => 'Privacy Policy'],
  ['url' => '/terms.php', 'label' => 'Terms & Conditions'],
  ['url' => '/cancellation.php', 'label' => 'Cancellation Policy'],
  ['url' => '/pushtimargshringar/returns.php', 'label' => 'Returns & Refunds'],
  ['url' => '/pushtimargshringar/delivery.php', 'label' => 'Delivery Information'],
  ['url' => '/account/newsletter.php', 'label' => 'Newsletter'],
  ['url' => '/account/login.php', 'label' => 'Account / Login'],
  ['url' => '/sitemap.xml', 'label' => 'XML Sitemap (if present)'],
];
?>
<main class="max-w-5xl mx-auto p-6">
  <h1 class="text-3xl font-bold mb-3">Site Map</h1>
  <p class="text-sm text-gray-600 mb-6">Last updated: <?= e($LAST_UPDATED) ?></p>

  <section class="mb-6">
    <ul class="list-disc pl-5">
      <?php foreach ($pages as $p): ?>
        <li class="mb-2"><a href="<?= e($p['url']) ?>" class="text-blue-600 hover:underline"><?= e($p['label']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="text-sm text-gray-600 mt-6">
    <p>If you want this list generated dynamically from your routes or database I can add a script to scan pages and build the sitemap automatically.</p>
  </section>
</main>

<?php
if (file_exists(__DIR__ . '/partials/footer.php')) include_once __DIR__ . '/partials/footer.php';
?>
