<?php
// /account/index.php
session_start();
require_once __DIR__ . '/../app/config.php';  // defines SITE_URL + site_url()
require_once __DIR__ . '/../app/auth.php';

auth_require_login();              // ✅ guard BEFORE any HTML output
$user = auth_user();

include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/header.php';
?>
<section class="py-10 bg-cream">
  <div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow">
    <a href="<?= site_url('account/profile.php') ?>" class="hover:text-gold">Edit Profile</a>
    <h1 class="text-2xl font-bold text-deepgreen">My Account</h1>
    <p class="mt-2">Welcome, <strong><?= htmlspecialchars($user['name']) ?></strong></p>
    <ul class="mt-4 text-darkgray space-y-1">
      <li>Email: <?= htmlspecialchars($user['email']) ?></li>
      <?php if(!empty($user['mobile'])): ?><li>Mobile: <?= htmlspecialchars($user['mobile']) ?></li><?php endif; ?>
    </ul>
    <a href="<?= site_url('account/orders.php') ?>" class="inline-block mt-4 bg-deepgreen text-white px-4 py-2 rounded hover:bg-gold hover:text-darkgray">
  View My Orders
</a>

    <a href="<?= site_url('account/logout.php') ?>" class="inline-block mt-6 bg-gold text-white px-4 py-2 rounded hover:bg-deepgreen">Log out</a>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
