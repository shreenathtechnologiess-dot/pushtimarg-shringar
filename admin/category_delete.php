<?php
// admin/category_delete.php
session_start();
require_once __DIR__ . '/../app/config.php';
if (!isset($conn) || !($conn instanceof mysqli)) die("DB connection missing.");

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
  // check product count
  $stmt = $conn->prepare("SELECT COUNT(*) AS c, image FROM categories c LEFT JOIN products p ON p.category_id = c.id WHERE c.id = ? GROUP BY c.id");
  $stmt->bind_param('i',$id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  $cnt = (int)($row['c'] ?? 0);
  $img = $row['image'] ?? null;
  if ($cnt > 0) {
    $_SESSION['flash_error'] = "Cannot delete category that has products.";
  } else {
    // delete db row
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param('i',$id);
    if ($stmt->execute()) {
      // delete image file if exists
      if ($img && file_exists(__DIR__ . '/../uploads/categories/' . $img)) @unlink(__DIR__ . '/../uploads/categories/' . $img);
      $_SESSION['flash_success'] = "Category deleted.";
    } else {
      $_SESSION['flash_error'] = "Delete failed: " . $stmt->error;
    }
    $stmt->close();
  }
}
header("Location: categories.php");
exit;
