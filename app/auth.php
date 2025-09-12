<?php
// app/auth.php
if (session_status() === PHP_SESSION_NONE) session_start();

function auth_login(array $user): void {
  $_SESSION['user'] = [
    'id'    => (int)$user['id'],
    'name'  => $user['name'],
    'email' => $user['email'],
    'mobile'=> $user['mobile'] ?? null,
  ];
}

function auth_logout(): void {
  unset($_SESSION['user']);
}

function auth_user(): ?array {
  return $_SESSION['user'] ?? null;
}

function auth_check(): bool {
  return !empty($_SESSION['user']);
}

/**
 * Redirect to login if not logged in.
 * Uses SITE_URL/site_url() if available so subfolder (e.g. /pushtimargshringar) works.
 */
function auth_require_login(string $redirectTo = ''): void {
  if (auth_check()) return;

  // Build redirect URL
  if ($redirectTo === '') {
    if (function_exists('site_url')) {
      $redirectTo = site_url('account/login.php');
    } elseif (defined('SITE_URL')) {
      $redirectTo = rtrim(SITE_URL, '/') . '/account/login.php';
    } else {
      $redirectTo = '/account/login.php';
    }
  }

  $next = !empty($_SERVER['REQUEST_URI']) ? ('?next=' . urlencode($_SERVER['REQUEST_URI'])) : '';
  header('Location: ' . $redirectTo . $next);
  exit;
}
// in /app/auth.php
function auth_refresh(int $userId, mysqli $conn): void {
  $stmt = $conn->prepare("SELECT id,name,email,mobile,address1,address2,state,pincode FROM users WHERE id=? LIMIT 1");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($u = $res?->fetch_assoc()) {
    $_SESSION['user'] = $u; // overwrite
  }
  $stmt->close();
}
