<?php
// cancellation.php (user-side)
$SITE_NAME = 'Pushtimarg Shringar';
$CONTACT_EMAIL = "support@example.com";
$LAST_UPDATED = '2025-09-08';

if (file_exists(__DIR__ . '/partials/head.php')) include_once __DIR__ . '/partials/head.php';
if (file_exists(__DIR__ . '/partials/header.php')) include_once __DIR__ . '/partials/header.php';

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<main class="max-w-5xl mx-auto p-6">
  <h1 class="text-3xl font-bold mb-3">Cancellation Policy</h1>
  <p class="text-sm text-gray-600 mb-6">Last updated: <?= e($LAST_UPDATED) ?></p>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Order cancellation</h2>
    <p>Orders may be cancelled within <strong>24 hours</strong> of placement if they have not been processed or shipped. To request cancellation contact <a href="mailto:<?= e($CONTACT_EMAIL) ?>"><?= e($CONTACT_EMAIL) ?></a>.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Refunds</h2>
    <p>Refunds are issued to the original payment method. Processing time depends on the payment provider (typically 3–10 business days).</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Exceptions</h2>
    <p>Customised, personalised or perishable items may not be eligible for cancellation. If an order has shipped, follow our returns process where applicable.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">How to cancel</h2>
    <ol class="list-decimal pl-5">
      <li>Email <a href="mailto:<?= e($CONTACT_EMAIL) ?>"><?= e($CONTACT_EMAIL) ?></a> with your order number.</li>
      <li>We will confirm if cancellation is possible and process any refund.</li>
    </ol>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Contact</h2>
    <p>Questions? Email <a href="mailto:<?= e($CONTACT_EMAIL) ?>"><?= e($CONTACT_EMAIL) ?></a>.</p>
  </section>
</main>

<?php
if (file_exists(__DIR__ . '/partials/footer.php')) include_once __DIR__ . '/partials/footer.php';
?>
