<?php
// admin/blog/delete.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../_csrf.php';

if (!isset($conn) && isset($con)) $conn = $con;

$id = (int)($_POST['id'] ?? 0);
$csrf = $_POST['csrf'] ?? '';

if (!$id || !admin_verify_csrf($csrf)) {
  $_SESSION['flash_error'] = "Invalid request.";
  header("Location: index.php"); exit;
}

$stmt = $conn->prepare("DELETE FROM posts WHERE id=?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
  $_SESSION['flash_success'] = "Post deleted.";
} else {
  $_SESSION['flash_error'] = "Error: " . $conn->error;
}
$stmt->close();

header("Location: index.php"); exit;
