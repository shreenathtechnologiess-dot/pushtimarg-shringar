<?php
// privacy.php (user-side)
$SITE_NAME = 'Pushtimarg Shringar';
$CONTACT_EMAIL = 'support@example.com';
$LAST_UPDATED = '2025-09-08';

// include head + header from user partials
if (file_exists(__DIR__ . '/partials/head.php')) include_once __DIR__ . '/partials/head.php';
if (file_exists(__DIR__ . '/partials/header.php')) include_once __DIR__ . '/partials/header.php';

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<main class="max-w-5xl mx-auto p-6">
  <h1 class="text-3xl font-bold mb-3">Privacy Policy</h1>
  <p class="text-sm text-gray-600 mb-6">Last updated: <?= e($LAST_UPDATED) ?></p>

  <section class="mb-6">
    <h2 class="text-xl font-semibold mb-2">Introduction</h2>
    <p>
      This Privacy Policy explains how <strong><?= e($SITE_NAME) ?></strong> collects and uses personal data when
      you visit or place orders on our website. By using the site you accept this policy.
    </p>
  </section>

  <section class="mb-6">
    <h2 class="text-xl font-semibold mb-2">Information we collect</h2>
    <ul class="list-disc pl-5">
      <li>Information you provide: name, email, address, phone number and order details.</li>
      <li>Automatically collected data: IP, browser, device info and usage analytics.</li>
      <li>Cookies and similar technologies to improve the experience.</li>
    </ul>
  </section>

  <section class="mb-6">
    <h2 class="text-xl font-semibold mb-2">How we use data</h2>
    <p>We use data to process orders, communicate with you, provide support and improve the service.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-xl font-semibold mb-2">Sharing</h2>
    <p>We do not sell personal information. We share data only with payment processors, shippers and other service providers needed to fulfill your order, and when required by law.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-xl font-semibold mb-2">Your rights</h2>
    <p>You may request access, correction or deletion of your data by contacting us at <a href="mailto:<?= e($CONTACT_EMAIL) ?>"><?= e($CONTACT_EMAIL) ?></a>.</p>
  </section>

  <section class="mb-6">
    <h2 class="text-xl font-semibold mb-2">Contact</h2>
    <p>If you have questions, contact <a href="mailto:<?= e($CONTACT_EMAIL) ?>"><?= e($CONTACT_EMAIL) ?></a>.</p>
  </section>
</main>

<?php
if (file_exists(__DIR__ . '/partials/footer.php')) include_once __DIR__ . '/partials/footer.php';
?>
