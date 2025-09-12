<?php
// admin/products.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';

// --- Filters from GET ---
$search       = trim($_GET['search'] ?? '');
$category     = trim($_GET['category'] ?? '');
$status       = trim($_GET['status'] ?? '');
$filterFeat   = isset($_GET['filter_featured']) ? '1' : '';
$filterSale   = isset($_GET['filter_onsale']) ? '1' : '';
$filterBest   = isset($_GET['filter_bestseller']) ? '1' : '';

// --- DB check ---
if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Database connection (\$conn) not available. Make sure app/config.php defines \$conn as mysqli instance.");
}

// --- Detect existence of optional columns ---
$columnsToCheck = ['status','is_featured','is_on_sale','is_best_seller'];
$colExists = array_fill_keys($columnsToCheck, false);

$dbNameRes = $conn->query("SELECT DATABASE() AS dbname");
$dbName = null;
if ($dbNameRes) {
  $row = $dbNameRes->fetch_assoc();
  $dbName = $row['dbname'] ?? null;
  $dbNameRes->free();
}

if ($dbName) {
  foreach ($columnsToCheck as $col) {
    $escTable = $conn->real_escape_string('products');
    $escColumn = $conn->real_escape_string($col);
    $checkSql = "SELECT COUNT(*) AS c FROM information_schema.columns
                 WHERE table_schema = '{$conn->real_escape_string($dbName)}'
                   AND table_name = '{$escTable}'
                   AND column_name = '{$escColumn}'";
    $cr = $conn->query($checkSql);
    if ($cr) {
      $colExists[$col] = (int)($cr->fetch_assoc()['c'] ?? 0) > 0;
      $cr->free();
    }
  }
}

// convenience flags
$hasStatus = $colExists['status'];
$hasFeatured = $colExists['is_featured'];
$hasOnSale = $colExists['is_on_sale'];
$hasBestSeller = $colExists['is_best_seller'];

// --- Handle status toggle POST (same page) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status' && isset($_POST['id'])) {
  $prodId = (int)$_POST['id'];

  // If status column does not exist, add it (safe default)
  if (!$hasStatus) {
    $alterSql = "ALTER TABLE products ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'";
    if ($conn->query($alterSql) === true) {
      $hasStatus = true;
    } else {
      $_SESSION['flash_error'] = "Failed to add status column: " . $conn->error;
      header("Location: products.php");
      exit;
    }
  }

  // fetch current status for product
  $stmtFetch = $conn->prepare("SELECT status FROM products WHERE id = ?");
  $stmtFetch->bind_param('i', $prodId);
  $stmtFetch->execute();
  $resF = $stmtFetch->get_result();
  $rowF = $resF ? $resF->fetch_assoc() : null;
  $stmtFetch->close();

  $currentStatus = $rowF['status'] ?? 'active';
  $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';

  $stmtUpd = $conn->prepare("UPDATE products SET status = ? WHERE id = ?");
  $stmtUpd->bind_param('si', $newStatus, $prodId);
  $ok = $stmtUpd->execute();
  $stmtUpd->close();

  if ($ok) {
    $_SESSION['flash_success'] = "Product #{$prodId} set to {$newStatus}.";
  } else {
    $_SESSION['flash_error'] = "Failed to update product status: " . $conn->error;
  }

  header("Location: products.php");
  exit;
}

// --- Stats ---
$totalProducts = 0;
$matchingProducts = 0;
$products = [];

// Total count
$r = $conn->query("SELECT COUNT(*) AS c FROM products");
if ($r) { $totalProducts = (int)($r->fetch_assoc()['c'] ?? 0); $r->free(); }

// --- Build SELECT fields dynamically ---
$selectFields = "p.id, p.name, p.slug, p.image, p.price, p.created_at, c.name AS category_name";
if ($hasStatus) $selectFields .= ", p.status";
if ($hasFeatured) $selectFields .= ", p.is_featured";
if ($hasOnSale) $selectFields .= ", p.is_on_sale";
if ($hasBestSeller) $selectFields .= ", p.is_best_seller";

// --- Build query ---
$sql = "SELECT {$selectFields} FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = []; $types = "";

// Search
if ($search !== '') {
  $sql .= " AND p.name LIKE ?";
  $params[] = "%$search%"; $types .= 's';
}

// Category
if ($category !== '') {
  $sql .= " AND p.category_id = ?";
  $params[] = (int)$category; $types .= 'i';
}

// Status filter (only if column exists)
if ($hasStatus && $status !== '') {
  $sql .= " AND p.status = ?";
  $params[] = $status; $types .= 's';
}

// Featured filter
if ($hasFeatured && $filterFeat === '1') {
  $sql .= " AND p.is_featured = 1";
}

// On Sale filter
if ($hasOnSale && $filterSale === '1') {
  $sql .= " AND p.is_on_sale = 1";
}

// Best Seller filter
if ($hasBestSeller && $filterBest === '1') {
  $sql .= " AND p.is_best_seller = 1";
}

$sql .= " ORDER BY p.created_at DESC";

// --- Matching count (same filters) ---
$countSql = "SELECT COUNT(*) AS c FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE 1=1";
$countParams = []; $countTypes = "";

if ($search !== '') { $countSql .= " AND p.name LIKE ?"; $countParams[] = "%$search%"; $countTypes .= 's'; }
if ($category !== '') { $countSql .= " AND p.category_id=?"; $countParams[] = (int)$category; $countTypes .= 'i'; }
if ($hasStatus && $status !== '') { $countSql .= " AND p.status=?"; $countParams[] = $status; $countTypes .= 's'; }
if ($hasFeatured && $filterFeat === '1') { $countSql .= " AND p.is_featured = 1"; }
if ($hasOnSale && $filterSale === '1') { $countSql .= " AND p.is_on_sale = 1"; }
if ($hasBestSeller && $filterBest === '1') { $countSql .= " AND p.is_best_seller = 1"; }

$stmtc = $conn->prepare($countSql);
if ($stmtc) {
  if ($countParams) {
    $bind = [$countTypes];
    foreach ($countParams as $i => $v) $bind[] = &$countParams[$i];
    call_user_func_array([$stmtc, 'bind_param'], $bind);
  }
  $stmtc->execute();
  $resC = $stmtc->get_result();
  $matchingProducts = (int)($resC->fetch_assoc()['c'] ?? 0);
  $stmtc->close();
} else {
  $matchingProducts = $totalProducts;
}

// --- Fetch products ---
$stmt = $conn->prepare($sql);
if ($stmt) {
  if ($params) {
    $bind = [$types];
    foreach ($params as $i => $v) $bind[] = &$params[$i];
    call_user_func_array([$stmt, 'bind_param'], $bind);
  }
  $stmt->execute();
  $res = $stmt->get_result();
  $products = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();
}

// --- Fetch categories ---
$categories = [];
$resC = $conn->query("SELECT id, name FROM categories ORDER BY name");
if ($resC) while ($r = $resC->fetch_assoc()) $categories[] = $r;

// --- Helpers (if not defined elsewhere) ---
if (!function_exists('product_image')) {
  function product_image($file) { return "../uploads/" . $file; }
}
if (!function_exists('format_price')) {
  function format_price($n) { return '₹ ' . number_format((float)$n, 2); }
}

// --- Layout include only AFTER all PHP logic ---
require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/start_layout.php';
?>

<div class="p-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-deepgreen">Products</h1>
    <div>
      <a href="product_add.php" class="btn btn-primary">+ Add Product</a>
    </div>
  </div>

  <!-- Flash messages -->
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= htmlspecialchars($_SESSION['flash_success']); ?></div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded"><?= htmlspecialchars($_SESSION['flash_error']); ?></div>
    <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <!-- Stats -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="card p-4">
      <div class="text-sm text-gray-500">Total Products</div>
      <div class="text-2xl font-bold"><?= (int)$totalProducts ?></div>
    </div>
    <div class="card p-4">
      <div class="text-sm text-gray-500">Matching Products</div>
      <div class="text-2xl font-bold"><?= (int)$matchingProducts ?></div>
    </div>
  </div>

  <!-- Filters -->
  <form method="get" class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
    <div>
      <label class="block text-sm">Search</label>
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="input">
    </div>
    <div>
      <label class="block text-sm">Category</label>
      <select name="category" class="input">
        <option value="">All</option>
        <?php foreach($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($category==$c['id'])?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm">Status</label>
      <select name="status" class="input">
        <option value="">All</option>
        <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
        <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
      </select>
      <?php if (!$hasStatus): ?>
        <div class="text-xs text-gray-500 mt-1">Note: Status column not found. First toggle will create it.</div>
      <?php endif; ?>
    </div>

    <!-- New quick filters for featured / on sale / best seller -->
    <div class="flex items-center gap-3">
      <label class="block text-sm w-full">Quick Filters</label>
      <div style="display:flex; gap:8px; align-items:center;">
        <?php if ($hasFeatured): ?>
          <label class="text-sm"><input type="checkbox" name="filter_featured" value="1" <?= $filterFeat==='1' ? 'checked' : '' ?>> Featured</label>
        <?php endif; ?>
        <?php if ($hasOnSale): ?>
          <label class="text-sm"><input type="checkbox" name="filter_onsale" value="1" <?= $filterSale==='1' ? 'checked' : '' ?>> On Sale</label>
        <?php endif; ?>
        <?php if ($hasBestSeller): ?>
          <label class="text-sm"><input type="checkbox" name="filter_bestseller" value="1" <?= $filterBest==='1' ? 'checked' : '' ?>> Best Seller</label>
        <?php endif; ?>
      </div>
    </div>

    <!-- Apply / Reset row -->
    <div>
      <button class="btn btn-primary">Apply</button>
      <a href="products.php" class="ml-2 text-sm text-gray-600">Reset</a>
    </div>
  </form>

  <!-- Table -->
  <div class="card overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-3 py-2">ID</th>
          <th class="px-3 py-2">Image</th>
          <th class="px-3 py-2">Name</th>
          <th class="px-3 py-2">Category</th>
          <th class="px-3 py-2">Price</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2">Created</th>
          <th class="px-3 py-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
          <tr><td colspan="8" class="px-3 py-4 text-center text-gray-500">No products found.</td></tr>
        <?php else: foreach($products as $p): ?>
          <?php
            $pStatus = $p['status'] ?? null;
            if ($pStatus === null && !$hasStatus) $displayStatus = '-';
            else $displayStatus = htmlspecialchars($pStatus ?? 'active');
            $isActive = ($pStatus === null) ? true : ($pStatus === 'active');

            // determine badges (use strict checks for '1' or integer 1)
            $isFeaturedVal = $hasFeatured ? ((int)($p['is_featured'] ?? 0) === 1) : false;
            $isOnSaleVal = $hasOnSale ? ((int)($p['is_on_sale'] ?? 0) === 1) : false;
            $isBestVal = $hasBestSeller ? ((int)($p['is_best_seller'] ?? 0) === 1) : false;
          ?>
          <tr class="border-t">
            <td class="px-3 py-2">#<?= (int)$p['id'] ?></td>
            <td class="px-3 py-2">
              <?php if (!empty($p['image'])): ?>
                <img src="<?= htmlspecialchars(product_image($p['image'])) ?>" class="w-12 h-12 object-cover rounded border">
              <?php endif; ?>
            </td>
            <td class="px-3 py-2">
              <?= htmlspecialchars($p['name']) ?>

              <?php if ($isFeaturedVal): ?>
                <span class="ml-2 px-2 py-1 text-xs rounded" style="background:#fff7ed;color:#92400e;border:1px solid #fde68a">Featured</span>
              <?php endif; ?>

              <?php if ($isOnSaleVal): ?>
                <span class="ml-2 px-2 py-1 text-xs rounded" style="background:#fff1f2;color:#991b1b;border:1px solid #fecaca">On Sale</span>
              <?php endif; ?>

              <?php if ($isBestVal): ?>
                <span class="ml-2 px-2 py-1 text-xs rounded" style="background:#ecfdf5;color:#065f46;border:1px solid #bbf7d0">Best Seller</span>
              <?php endif; ?>
            </td>
            <td class="px-3 py-2"><?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?></td>
            <td class="px-3 py-2"><?= format_price($p['price'] ?? 0) ?></td>
            <td class="px-3 py-2">
              <span class="<?= ($isActive)?'badge-success':'badge-muted' ?>"><?= $displayStatus ?></span>
            </td>
            <td class="px-3 py-2"><?= htmlspecialchars(date('d M Y, g:i a', strtotime($p['created_at'] ?? 'now'))) ?></td>
            <td class="px-3 py-2">
              <a href="product_edit.php?id=<?= (int)$p['id'] ?>" class="text-blue-600">Edit</a>
              &nbsp;|&nbsp;
              <a href="product_delete.php?id=<?= (int)$p['id'] ?>" class="text-red-600" onclick="return confirm('Delete this product?')">Delete</a>
              <?php if ($hasStatus || !$hasStatus): ?>
                &nbsp;|&nbsp;
                <form method="post" action="products.php" style="display:inline;margin:0;padding:0">
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button type="submit" class="text-sm <?= $isActive ? 'text-yellow-600' : 'text-green-600' ?>" onclick="return confirm('Are you sure?')">
                    <?= $isActive ? 'Deactivate' : 'Activate' ?>
                  </button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
require_once __DIR__ . '/includes/end_layout.php';
?>
