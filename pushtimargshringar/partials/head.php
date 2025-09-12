<?php
// partials/head.php (corrected)
if (!function_exists('asset')) {
    function asset($path) {
        // If SITE_URL defined, prefer absolute URL; otherwise fallback to your base folder
        if (defined('SITE_URL') && SITE_URL) {
            return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
        }
        return '/pushtimargshringar/' . ltrim($path, '/');
    }
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Explore traditional Pushtimarg Shringar items and accessories">
  <meta name="keywords" content="Pushtimarg, Shringar, Traditional, Religious">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle).' | ' : '' ?>Pushtimarg Shringar</title>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= asset('assets/images/favicon.png') ?>">

  <!-- Preload critical CSS (optional) -->
  <link rel="preload" href="<?= asset('assets/css/style.css') ?>" as="style">

  <!-- Main stylesheet (fixed syntax) -->
  <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">

  <!-- Tailwind (CDN) -->
  <script>
    // define tailwind config BEFORE loading CDN script if you want to customize
    window.tailwind = window.tailwind || {};
    tailwind = tailwind || {};
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            cream: "#FFFDF7",
            deepgreen: "#14532D",
            gold: "#E6B325",
            rust: "#C2410C",
            darkgray: "#2D2D2D"
          }
        }
      }
    };
  </script>
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Alpine (deferred) -->
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- tiny inline fallback to avoid flash of unstyled content -->
  <style>body{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial; background:#FFFDF7; color:#1F2937;}</style>
</head>
<body class="bg-cream text-darkgray min-h-screen">
