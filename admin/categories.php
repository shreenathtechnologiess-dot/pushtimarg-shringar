<?php
// admin/categories.php
session_start();
require_once __DIR__ . '/../app/config.php';
if (!isset($conn) || !($conn instanceof mysqli)) die("Database connection missing.");

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// stats
$totalCats = 0; $totalProducts = 0; $topCategory = null; $emptyCats = 0;
$r = $conn->query("SELECT COUNT(*) AS c FROM categories");
if ($r) { $totalCats = (int)$r->fetch_assoc()['c']; $r->free(); }
$r = $conn->query("SELECT COUNT(*) AS c FROM products");
if ($r) { $totalProducts = (int)$r->fetch_assoc()['c']; $r->free(); }

// top category
$r = $conn->query("
  SELECT c.name, COUNT(p.id) AS cnt
  FROM categories c
  LEFT JOIN products p ON p.category_id = c.id
  GROUP BY c.id
  ORDER BY cnt DESC
  LIMIT 1
");
if ($r) { $topCategory = $r->fetch_assoc(); $r->free(); }

// empty categories
$r = $conn->query("
  SELECT COUNT(*) AS c FROM (
    SELECT c.id FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    HAVING COUNT(p.id)=0
  ) t
");
if ($r) { $emptyCats = (int)$r->fetch_assoc()['c']; $r->free(); }

// search
$search = trim($_GET['q'] ?? '');

// fetch categories
$sql = "SELECT c.id, c.name, c.slug, c.image, c.description, c.created_at, COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
        WHERE 1=1";
$params = []; $types = "";
if ($search !== '') {
  $sql .= " AND (c.name LIKE ? OR c.slug LIKE ? OR c.description LIKE ?)";
  $like = "%$search%";
  $params = [$like,$like,$like]; $types = 'sss';
}
$sql .= " GROUP BY c.id ORDER BY c.name ASC";

$stmt = $conn->prepare($sql);
$categories = [];
if ($stmt) {
  if ($params) $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();
  $categories = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();
}

// export CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="categories.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['id','name','slug','description','image','product_count','created_at']);
  foreach ($categories as $c) fputcsv($out, [$c['id'],$c['name'],$c['slug'],$c['description'],$c['image'],$c['product_count'],$c['created_at']]);
  fclose($out);
  exit;
}

// assets path
$assetsWebPrefix = '/pushtimargshringar/assets/images/products/';
$assetsDiskPath  = __DIR__ . '/../assets/images/products/';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/start_layout.php';
?>
<div class="p-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-deepgreen">Categories</h1>
    <div class="flex gap-3">
      <a href="category_add.php" class="btn btn-primary">+ Add Category</a>
      <a href="?export=csv" class="btn btn-ghost">Export</a>
    </div>
  </div>

  <?php if ($flash_success): ?><div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= h($flash_success) ?></div><?php endif; ?>
  <?php if ($flash_error): ?><div class="mb-4 p-3 bg-red-100 text-red-800 rounded"><?= h($flash_error) ?></div><?php endif; ?>

  <!-- stats -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="card p-4"><div class="text-sm text-gray-500">Total Categories</div><div class="text-2xl font-bold"><?= (int)$totalCats ?></div></div>
    <div class="card p-4"><div class="text-sm text-gray-500">Total Products</div><div class="text-2xl font-bold"><?= (int)$totalProducts ?></div></div>
    <div class="card p-4"><div class="text-sm text-gray-500">Top Category</div>
      <div class="text-lg font-semibold"><?= $topCategory ? h($topCategory['name']).' ('.(int)$topCategory['cnt'].')' : '—' ?></div>
    </div>
    <div class="card p-4"><div class="text-sm text-gray-500">Empty Categories</div><div class="text-2xl font-bold"><?= (int)$emptyCats ?></div></div>
  </div>

  <!-- controls -->
  <div class="card p-4 mb-6 flex items-center justify-between">
    <form method="get" class="flex items-center gap-3">
      <input type="search" name="q" placeholder="Search category..." value="<?= h($search) ?>" class="input">
      <button class="btn btn-primary">Search</button>
      <a href="categories.php" class="ml-2 text-sm text-gray-600">Reset</a>
    </form>
    <div class="text-sm text-gray-500">Click Edit to change name/slug/description/image. Delete only when category has 0 products.</div>
  </div>

  <!-- list -->
  <div class="card overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-3 py-3">Category</th>
          <th class="px-3 py-3">Products</th>
          <th class="px-3 py-3">Slug</th>
          <th class="px-3 py-3">Created</th>
          <th class="px-3 py-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($categories)): ?>
          <tr><td colspan="5" class="py-6 text-center text-gray-500">No categories found.</td></tr>
        <?php else: foreach ($categories as $c): ?>
          <tr class="border-t">
            <td class="px-3 py-3 flex items-center gap-3">
              <div class="w-12 h-12 bg-gray-100 rounded overflow-hidden border">
                <?php if (!empty($c['image']) && file_exists($assetsDiskPath . $c['image'])): ?>
                  <img src="<?= $assetsWebPrefix . h($c['image']) ?>" class="w-full h-full object-cover" alt="<?= h($c['name']) ?>">
                <?php else: ?>
                  <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs">No image</div>
                <?php endif; ?>
              </div>
              <div>
                <div class="font-medium"><?= h($c['name']) ?></div>
                <div class="text-xs text-gray-500"><?= h(substr($c['description'] ?? '',0,120)) ?></div>
              </div>
            </td>
            <td class="px-3 py-3"><?= (int)$c['product_count'] ?></td>
            <td class="px-3 py-3 text-gray-600"><?= h($c['slug']) ?></td>
            <td class="px-3 py-3"><?= h(date('Y-m-d', strtotime($c['created_at'] ?? 'now'))) ?></td>
            <td class="px-3 py-3">
              <a href="category_edit.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm">Edit</a>
              <?php if ((int)$c['product_count'] === 0): ?>
                <a href="category_delete.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?')">Delete</a>
              <?php else: ?>
                <button class="btn btn-sm btn-ghost" disabled>Delete</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/end_layout.php'; ?>
