<?php
// admin/product_delete.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
  die("DB connection not available.");
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  $_SESSION['flash_error'] = "Invalid product id.";
  header("Location: products.php");
  exit;
}

// Optionally remove image file (fetch it first)
$stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if ($row && !empty($row['image'])) {
  $file = __DIR__ . '/../uploads/' . $row['image'];
  if (is_file($file)) @unlink($file);
}

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
  $_SESSION['flash_success'] = "Product deleted.";
} else {
  $_SESSION['flash_error'] = "Delete failed: " . $stmt->error;
}
$stmt->close();
header("Location: products.php");
exit;
