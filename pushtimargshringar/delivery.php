<?php
// pushtimargshringar/delivery.php
$SITE_NAME = 'Your Site Name';
$CONTACT_EMAIL = 'support@example.com';
$LAST_UPDATED = '2025-09-08';

if (file_exists(__DIR__ . '/partials/head.php')) include_once __DIR__ . '/partials/head.php';
if (file_exists(__DIR__ . '/partials/header.php')) include_once __DIR__ . '/partials/header.php';

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<main class="max-w-5xl mx-auto p-6">
  <h1 class="text-3xl font-bold mb-3">Delivery Information</h1>
  <p class="text-sm text-gray-600 mb-6">Last updated: <?= e($LAST_UPDATED) ?></p>

  <section class="mb-6">
    <h2 class="text-xl font-semibold mb-2">Shipping options & timing</h2>
    <p>
      We offer standard and expedited shipping. Delivery times vary by location and product availability. Typical delivery windows:
    </p>
    <ul class="list-disc pl-5 mb-3">
      <li>Within city: 1–3 business days</li>
      <li>Across state: 3–7 business days</li>
      <li>Remote locations: up to 10 business days</li>
    </ul>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Shipping charges</h2>
    <p>Shipping fees are calculated at checkout based on weight, size and destination. Free shipping promotions may apply to orders above a threshold.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Tracking</h2>
    <p>Once your order ships we will email you a tracking number. Use the tracking number on the courier website to follow the delivery.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Failed delivery</h2>
    <p>If delivery fails due to incorrect address or unsuccessful attempts, the courier may return the package to us — additional shipping charges may apply for re-dispatch.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">International shipments</h2>
    <p>For international orders additional customs duties, taxes or fees may apply and are the responsibility of the recipient.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Contact</h2>
    <p>Questions about delivery? Email <a href="mailto:<?= e($CONTACT_EMAIL) ?>"><?= e($CONTACT_EMAIL) ?></a>.</p>
  </section>
</main>

<?php
if (file_exists(__DIR__ . '/partials/footer.php')) include_once __DIR__ . '/partials/footer.php';
?>
