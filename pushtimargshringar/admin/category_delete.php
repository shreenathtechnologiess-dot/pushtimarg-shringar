<?php
// admin/category_delete.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
if (!isset($conn) || !($conn instanceof mysqli)) die("DB connection missing.");

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
  // count products that belong to this category and fetch category image (use table aliases)
  $sql = "SELECT COUNT(p.id) AS c, c.image
          FROM categories c
          LEFT JOIN products p ON p.category_id = c.id
          WHERE c.id = ?";
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    $_SESSION['flash_error'] = "Prepare failed: " . $conn->error;
    header("Location: categories.php");
    exit;
  }
  $stmt->bind_param('i', $id);
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
    if (!$stmt) {
      $_SESSION['flash_error'] = "Prepare failed: " . $conn->error;
      header("Location: categories.php");
      exit;
    }
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
      // delete image file if exists in the expected places
      if (!empty($img)) {
        $pathsToCheck = [
          __DIR__ . '/../assets/images/products/' . $img,   // primary path you're using now
          __DIR__ . '/../uploads/categories/' . $img        // legacy path (in case older uploads there)
        ];
        foreach ($pathsToCheck as $p) {
          if (file_exists($p) && is_file($p)) {
            @unlink($p);
            // stop after first successful unlink
            break;
          }
        }
      }

      $_SESSION['flash_success'] = "Category deleted.";
    } else {
      $_SESSION['flash_error'] = "Delete failed: " . $stmt->error;
    }
    $stmt->close();
  }
}
header("Location: categories.php");
exit;
