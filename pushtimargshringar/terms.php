<?php
// terms.php (user-side)
$SITE_NAME = 'Pushtimarg Shringar';
$CONTACT_EMAIL = 'support@example.com';
$LAST_UPDATED = '2025-09-08';

if (file_exists(__DIR__ . '/partials/head.php')) include_once __DIR__ . '/partials/head.php';
if (file_exists(__DIR__ . '/partials/header.php')) include_once __DIR__ . '/partials/header.php';

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<main class="max-w-5xl mx-auto p-6">
  <h1 class="text-3xl font-bold mb-3">Terms &amp; Conditions</h1>
  <p class="text-sm text-gray-600 mb-6">Last updated: <?= e($LAST_UPDATED) ?></p>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Acceptance of terms</h2>
    <p>By using <?= e($SITE_NAME) ?> you agree to these terms. Please read them carefully.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Orders & pricing</h2>
    <p>Orders are subject to acceptance and availability. Prices may change and we may refuse or cancel orders.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Payments & refunds</h2>
    <p>Payment methods are shown at checkout. Refunds follow our cancellation policy. For queries contact <a href="mailto:<?= e($CONTACT_EMAIL) ?>"><?= e($CONTACT_EMAIL) ?></a>.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">User obligations</h2>
    <p>You must provide accurate information and keep account credentials secure.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Limitation of liability</h2>
    <p>To the extent permitted by law, <?= e($SITE_NAME) ?> is not liable for indirect or consequential damages.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Governing law</h2>
    <p>These terms are governed by the laws of the jurisdiction where <?= e($SITE_NAME) ?> operates.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Contact</h2>
    <p>Questions: <a href="mailto:<?= e($CONTACT_EMAIL) ?>"><?= e($CONTACT_EMAIL) ?></a></p>
  </section>
</main>

<?php
if (file_exists(__DIR__ . '/partials/footer.php')) include_once __DIR__ . '/partials/footer.php';
?>
