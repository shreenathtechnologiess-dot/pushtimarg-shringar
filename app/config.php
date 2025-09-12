<?php
// app/config.php

// Always include DB first
require_once __DIR__ . '/db.php';

// ── Base URL (your app lives at /pushtimargshringar)
if (!defined('SITE_URL')) {
  define('SITE_URL', '/pushtimargshringar');
}
// Helper to build URLs safely
if (!function_exists('site_url')) {
function site_url(string $path=''): string {
  $base = rtrim(SITE_URL, '/');
  return $base . '/' . ltrim($path, '/');
}
}

// Admin WhatsApp (no +, no spaces)
if (!defined('ADMIN_WHATSAPP')) {
  define('ADMIN_WHATSAPP', '918306701065');
}

// Image base paths (adjust if needed)
if (!defined('PRODUCT_IMAGE_DIR')) define('PRODUCT_IMAGE_DIR', '/assets/images/products/');
if (!defined('BANNER_IMAGE_DIR'))  define('BANNER_IMAGE_DIR',  '/assets/images/banner/');

// Format price (₹ Indian style basic)
if (!function_exists('format_price')) {
  function format_price($price) {
    return "₹ " . number_format((float)$price, 0);
  }
}

// Build product image URL
if (!function_exists('product_image')) {
  function product_image(string $file): string {
    return site_url(PRODUCT_IMAGE_DIR . $file);
  }
}
// Build banner image URL
if (!function_exists('banner_image')) {
  function banner_image(string $file): string {
    return site_url(BANNER_IMAGE_DIR . $file);
  }
}

// Product page URL from slug
if (!function_exists('product_url')) {
  function product_url(string $slug): string {
    return site_url('product.php?slug=' . urlencode($slug));
  }
}

// Safe slug
if (!function_exists('slugify')) {
  function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    return trim($text, '-');
  }
}
