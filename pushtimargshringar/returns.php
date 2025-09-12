<?php
// pushtimargshringar/returns.php
$SITE_NAME = 'Your Site Name';
$CONTACT_EMAIL = 'support@example.com';
$LAST_UPDATED = '2025-09-08';

if (file_exists(__DIR__ . '/partials/head.php')) include_once __DIR__ . '/partials/head.php';
if (file_exists(__DIR__ . '/partials/header.php')) include_once __DIR__ . '/partials/header.php';

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<main class="max-w-5xl mx-auto p-6">
  <h1 class="text-3xl font-bold mb-3">Returns &amp; Refunds</h1>
  <p class="text-sm text-gray-600 mb-6">Last updated: <?= e($LAST_UPDATED) ?></p>

  <section class="mb-6">
    <h2 class="text-xl font-semibold mb-2">Overview</h2>
    <p>
      If you are not satisfied with your purchase, you may be eligible for a return or refund under the conditions below.
      Please read the policy carefully and contact us for any questions: <a href="mailto:<?= e($CONTACT_EMAIL) ?>"><?= e($CONTACT_EMAIL) ?></a>.
    </p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Return window</h2>
    <p>Most non-perishable, non-custom items can be returned within <strong>7 days</strong> of delivery in their original condition and packaging.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Conditions for return</h2>
    <ul class="list-disc pl-5">
      <li>Item must be unused and in original packaging.</li>
      <li>Tags and accessories should be included.</li>
      <li>Customised or perishable items are not eligible unless faulty.</li>
    </ul>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">How to request a return</h2>
    <ol class="list-decimal pl-5">
      <li>Email us at <a href="mailto:<?= e($CONTACT_EMAIL) ?>"><?= e($CONTACT_EMAIL) ?></a> with your order number and reason.</li>
      <li>We will confirm eligibility and provide return instructions and address.</li>
      <li>Ship the item per instructions; keep tracking number until refund is processed.</li>
    </ol>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Refunds</h2>
    <p>Refunds are processed after we receive and inspect the returned items. Refunds are issued to the original payment method and may take 3–10 business days depending on the provider.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-lg font-semibold mb-2">Damaged or faulty items</h2>
    <p>If an item arrives damaged or is faulty, contact us immediately with photos and order details. We will arrange a replacement or a full refund as appropriate.</p>
  </section>
</main>

<?php
if (file_exists(__DIR__ . '/partials/footer.php')) include_once __DIR__ . '/partials/footer.php';
?>
