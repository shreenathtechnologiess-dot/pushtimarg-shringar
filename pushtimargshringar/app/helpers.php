<?php
// app/helpers.php

if (!function_exists('e')) {
  function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('h')) {
  function h($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
  }
}

if (!function_exists('slugify')) {
  function slugify($t) {
    $t = strtolower(trim((string)$t));
    $t = preg_replace('/[^a-z0-9]+/i','-',$t);
    return trim($t,'-');
  }
}

/* Add other shared helpers here, for example:
   - post_image_src()
   - product_image()
   - site_url()
   - format_price()
   Keep them wrapped with if (!function_exists(...))
*/
