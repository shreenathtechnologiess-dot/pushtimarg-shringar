<?php
// admin/_csrf.php
if (session_status() === PHP_SESSION_NONE) session_start();

function admin_csrf_token() {
  if (empty($_SESSION['_admin_csrf'])) $_SESSION['_admin_csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['_admin_csrf'];
}
function admin_verify_csrf($token) {
  return !empty($token) && !empty($_SESSION['_admin_csrf']) && hash_equals($_SESSION['_admin_csrf'], $token);
}
