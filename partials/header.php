<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
$user = auth_user();
?>

<!-- Top Notice Bar -->
<div class="bg-[#8B0000] text-white py-2 text-sm font-medium overflow-hidden relative">
  <div class="animate-marquee whitespace-nowrap">
    पुष्टिमार्ग श्रृंगार - सीधे श्रीनाथ जी के धाम से &nbsp;
  </div>
</div>
<style>
@keyframes marquee {
  0%   { transform: translateX(100%); }
  100% { transform: translateX(-100%); }
}
.animate-marquee {
  display: inline-block;
  padding-left: 100%;
  animation: marquee 13s linear infinite;
}
</style>

<!-- Main Navbar -->
<nav class="bg-white shadow px-4 py-3 sticky top-0 z-50" x-data="{ open: false }">
  <div class="max-w-7xl mx-auto flex justify-between items-center">

    <!-- Logo -->
    <div class="flex items-center space-x-2">
      <img src="<?= site_url('assets/images/logo/logo.png') ?>" alt="Pushtimarg Shringar Logo" class="h-10">
      <h1 class="text-xl font-bold text-gold">Pushtimarg Shringar</h1>
    </div>

    <!-- Desktop Navigation -->
    <div class="hidden md:flex items-center space-x-6">
      <a href="<?= site_url('index.php') ?>" class="hover:text-gold">Home</a>
      <a href="<?= site_url('products.php') ?>" class="hover:text-gold">Products</a>
      <a href="<?= site_url('categories.php') ?>" class="hover:text-gold">Categories</a>
      <a href="<?= site_url('about.php') ?>" class="hover:text-gold">About Us</a>
      <a href="<?= site_url('contact.php') ?>" class="hover:text-gold">Contact</a>

      <?php if ($user): ?>
        <a href="<?= site_url('account/index.php') ?>" class="hover:text-gold">Hello, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></a>
        <a href="<?= site_url('account/logout.php') ?>" class="hover:text-gold">Logout</a>
      <?php else: ?>
        <a href="<?= site_url('account/login.php') ?>" class="hover:text-gold">Login</a>
        <a href="<?= site_url('account/register.php') ?>" class="hover:text-gold">Register</a>
      <?php endif; ?>
    </div>

    <!-- Right Section -->
    <div class="flex items-center space-x-4">
      <!-- Desktop Cart -->
      <a href="<?= site_url('cart.php') ?>" class="hidden md:block bg-gold text-white px-4 py-2 rounded hover:bg-peach hover:text-charcoal transition">
        🛒 Cart (<?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?>)
      </a>

      <!-- Mobile Menu Button -->
      <button class="md:hidden focus:outline-none" @click="open = !open">
        <svg class="w-6 h-6 text-charcoal" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
          <path x-show="open" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
  </div>

  <!-- Mobile Dropdown -->
  <div x-show="open" class="md:hidden mt-3 space-y-2 px-2 pb-3">
    <a href="<?= site_url('index.php') ?>" class="block px-3 py-2 rounded hover:bg-peach hover:text-gold">Home</a>
    <a href="<?= site_url('products.php') ?>" class="block px-3 py-2 rounded hover:bg-peach hover:text-gold">Products</a>
    <a href="<?= site_url('categories.php') ?>" class="block px-3 py-2 rounded hover:bg-peach hover:text-gold">Categories</a>
    <a href="<?= site_url('about.php') ?>" class="block px-3 py-2 rounded hover:bg-peach hover:text-gold">About Us</a>
    <a href="<?= site_url('contact.php') ?>" class="block px-3 py-2 rounded hover:bg-peach hover:text-gold">Contact</a>

    <?php if ($user): ?>
      <a href="<?= site_url('account/index.php') ?>" class="block px-3 py-2 rounded hover:bg-peach hover:text-gold">My Account</a>
      <a href="<?= site_url('account/logout.php') ?>" class="block px-3 py-2 rounded hover:bg-peach hover:text-gold">Logout</a>
    <?php else: ?>
      <a href="<?= site_url('account/login.php') ?>" class="block px-3 py-2 rounded hover:bg-peach hover:text-gold">Login</a>
      <a href="<?= site_url('account/register.php') ?>" class="block px-3 py-2 rounded hover:bg-peach hover:text-gold">Register</a>
    <?php endif; ?>

    <a href="<?= site_url('cart.php') ?>" class="block px-3 py-2 rounded bg-gold text-white hover:bg-peach hover:text-charcoal">
      🛒 Cart (<?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?>)
    </a>
  </div>
</nav>

<!-- Sticky Bottom Mobile Nav -->
<div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-md md:hidden z-50">
  <div class="flex justify-around items-center py-2 text-sm text-gray-700">

    <a href="<?= site_url('index.php') ?>" class="flex flex-col items-center hover:text-gold">Home</a>
    <a href="<?= site_url('categories.php') ?>" class="flex flex-col items-center hover:text-gold">Categories</a>
    <a href="<?= site_url('cart.php') ?>" class="flex flex-col items-center hover:text-gold">
      Cart (<?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?>)
    </a>
    <?php if ($user): ?>
      <a href="<?= site_url('account/index.php') ?>" class="flex flex-col items-center hover:text-gold">Account</a>
    <?php else: ?>
      <a href="<?= site_url('account/login.php') ?>" class="flex flex-col items-center hover:text-gold">Login</a>
    <?php endif; ?>
  </div>
</div>
