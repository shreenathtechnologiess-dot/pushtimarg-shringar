<?php
// admin/user_toggle.php
session_start();
require_once __DIR__ . '/../app/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
  die("DB connection missing");
}

$id = (int)($_GET['id'] ?? 0);
$target = ($_GET['status'] ?? '') === 'active' ? 'active' : 'blocked';

if ($id > 0) {
  $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
  $stmt->bind_param('si', $target, $id);
  if ($stmt->execute()) {
    $_SESSION['flash_success'] = "User status set to {$target}.";
  } else {
    $_SESSION['flash_error'] = "Failed to update: " . $stmt->error;
  }
  $stmt->close();
}

header("Location: users.php");
exit;
