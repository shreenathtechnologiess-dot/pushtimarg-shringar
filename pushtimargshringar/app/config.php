<?php
// app/config.php - safe, idempotent config
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------- Error reporting (dev only) ----------------
if (!defined('APP_DEBUG')) define('APP_DEBUG', true);
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ---------------- SITE / BASE URL ----------------
if (!defined('SITE_URL') || !defined('BASE_URL')) {
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        if (!defined('SITE_URL')) define('SITE_URL', 'http://localhost/pushtimargshringar');
        if (!defined('BASE_URL')) define('BASE_URL', '/pushtimargshringar');
    } else {
        if (!defined('SITE_URL')) define('SITE_URL', 'https://pushtimargshringar.com');
        if (!defined('BASE_URL')) define('BASE_URL', '');
    }
}

// ---------------- PUBLIC_PATH (filesystem root path) ----------------
// config.php is in /project/app, so dirname(__DIR__) = project root
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', dirname(__DIR__));
}

// ---------------- Helpers ----------------
if (!function_exists('site_url')) {
    function site_url(string $path = ''): string {
        $base = rtrim(SITE_URL, '/');
        if ($path === '') return $base;
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        $prefix = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        return $prefix . '/' . ltrim($path, '/');
    }
}

if (!defined('ADMIN_WHATSAPP')) define('ADMIN_WHATSAPP', '918306701065');
if (!defined('PRODUCT_IMAGE_DIR')) define('PRODUCT_IMAGE_DIR', 'assets/images/products/');
if (!defined('BANNER_IMAGE_DIR')) define('BANNER_IMAGE_DIR', 'assets/images/banner/');

if (!function_exists('format_price')) {
    function format_price($price) {
        return "₹ " . number_format((float)$price, 0);
    }
}

if (!function_exists('product_image')) {
    function product_image(string $file): string {
        $file = ltrim((string)$file, '/');
        return asset(PRODUCT_IMAGE_DIR . $file);
    }
}

if (!function_exists('banner_image')) {
    function banner_image(string $file): string {
        $file = ltrim((string)$file, '/');
        return asset(BANNER_IMAGE_DIR . $file);
    }
}

if (!function_exists('product_url')) {
    function product_url(string $slug): string {
        return site_url('product.php?slug=' . urlencode($slug));
    }
}

if (!function_exists('slugify')) {
    function slugify($text) {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        return trim($text, '-');
    }
}
