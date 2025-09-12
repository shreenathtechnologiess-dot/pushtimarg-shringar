<?php
// /account/logout.php
session_start();

require_once __DIR__ . '/../app/config.php'; // SITE_URL + site_url()
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

auth_logout();

// (optional) session cookie invalidate
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_regenerate_id(true);

// Redirect to site home inside subfolder
$to = function_exists('site_url') ? site_url('index.php')
     : (defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/index.php'
     : '/pushtimargshringar/index.php');

header('Location: ' . $to);
exit;
