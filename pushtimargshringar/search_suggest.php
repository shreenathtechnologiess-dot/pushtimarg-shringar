<?php
// search_suggest.php
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';

$q = trim($_GET['q'] ?? '');
$out = [];

if ($q !== '' && isset($conn) && $conn instanceof mysqli) {
  $like = '%' . $q . '%';
  $stmt = $conn->prepare("SELECT id, name, slug, image FROM products WHERE status='active' AND name LIKE ? ORDER BY name ASC LIMIT 8");
  if ($stmt) {
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
      $out[] = [
        'name'  => $row['name'],
        'label' => $row['name'],
        'url'   => (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') . '/product/' . urlencode($row['slug']),
        'image' => '/assets/images/products/' . $row['image'],
      ];
    }
    $stmt->close();
  }
}

header('Content-Type: application/json');
echo json_encode($out);
