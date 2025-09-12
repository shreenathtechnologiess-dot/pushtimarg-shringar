<?php
// search.php
$pageTitle = "Search";
if (session_status()===PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';
if (file_exists(__DIR__ . '/app/auth.php')) require_once __DIR__ . '/app/auth.php';

/* ---------------- Helpers ---------------- */
if (!function_exists('product_img_src')) {
  function product_img_src(string $image): string {
    $image = trim($image);
    if ($image === '') return '/assets/images/products/placeholder.jpg';
    return '/assets/images/products/' . rawurlencode(basename($image));
  }
}

if (!function_exists('product_url_from_row')) {
  function product_url_from_row(array $p): string {
    $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
    if (!empty($p['slug'])) return $base . '/product/' . urlencode($p['slug']);
    return $base . '/product/' . (int)($p['id'] ?? 0);
  }
}

/* ---------------- Input ---------------- */
$qRaw = trim((string)($_GET['q'] ?? ''));
$cat  = trim((string)($_GET['category'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 12;
$off  = ($page-1)*$per;

/* ---------------- DB guard ---------------- */
if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  echo "Database connection not found. Check app/db.php";
  exit;
}

/* ---------------- Build WHERE ---------------- */
$where = ["(p.status = 'active' OR p.status = 1 OR p.status IS NULL)"];
$params = [];
$types  = '';

if ($qRaw !== '') {
  $like = '%' . $qRaw . '%';
  $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
  $params[] = $like; $params[] = $like; $params[] = $like;
  $types .= 'sss';
}

if ($cat !== '') {
  $where[] = "(p.category = ? OR p.category_id IN (SELECT id FROM categories WHERE slug = ? OR name = ?))";
  $params[] = $cat; $params[] = $cat; $params[] = $cat;
  $types .= 'sss';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ---------------- Count ---------------- */
$sqlCount = "SELECT COUNT(*) AS c FROM products p $whereSql";
$total = 0;
$st = $conn->prepare($sqlCount);
if ($st === false) {
  // fallback (escaped)
  $safeQ = $conn->real_escape_string($qRaw);
  $safeCat = $conn->real_escape_string($cat);
  $fallback = "SELECT COUNT(*) AS c FROM products p WHERE (p.status = 'active' OR p.status = 1 OR p.status IS NULL)";
  if ($qRaw !== '') $fallback .= " AND (p.name LIKE '%{$safeQ}%' OR p.sku LIKE '%{$safeQ}%' OR p.description LIKE '%{$safeQ}%')";
  if ($cat !== '') $fallback .= " AND (p.category='{$safeCat}' OR p.category_id IN (SELECT id FROM categories WHERE slug='{$safeCat}' OR name='{$safeCat}'))";
  $cres = $conn->query($fallback);
  $total = $cres ? (int)($cres->fetch_assoc()['c'] ?? 0) : 0;
} else {
  if ($types !== '') $st->bind_param($types, ...$params);
  $st->execute();
  $cres = $st->get_result();
  $total = $cres ? (int)($cres->fetch_assoc()['c'] ?? 0) : 0;
  if ($cres) $cres->free();
  $st->close();
}

/* ---------------- Results ----------------
   We also try to fetch category name through LEFT JOIN (alias category_name).
*/
$sql = "SELECT p.id, p.name, p.slug, p.price, p.image,
               p.category, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        $whereSql
        ORDER BY p.id DESC
        LIMIT ? OFFSET ?";

$typesList = $types . 'ii';
$paramsList = $params;
$paramsList[] = $per;
$paramsList[] = $off;

$rows = [];
$st = $conn->prepare($sql);
if ($st === false) {
  // fallback direct (escaped)
  $safeQ = $conn->real_escape_string($qRaw);
  $safeCat = $conn->real_escape_string($cat);
  $fallbackSql = "SELECT p.id, p.name, p.slug, p.price, p.image, p.category FROM products p WHERE (p.status = 'active' OR p.status = 1 OR p.status IS NULL)";
  if ($qRaw !== '') $fallbackSql .= " AND (p.name LIKE '%{$safeQ}%' OR p.sku LIKE '%{$safeQ}%' OR p.description LIKE '%{$safeQ}%')";
  if ($cat !== '') $fallbackSql .= " AND (p.category='{$safeCat}' OR p.category_id IN (SELECT id FROM categories WHERE slug='{$safeCat}' OR name='{$safeCat}'))";
  $fallbackSql .= " ORDER BY p.id DESC LIMIT {$per} OFFSET {$off}";
  $res = $conn->query($fallbackSql);
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  if ($res) $res->free();
} else {
  // bind params (need references for call_user_func_array)
  if ($types !== '') {
    $bind_names = [];
    $bind_names[] = $typesList;
    foreach ($paramsList as $i => $v) $bind_names[] = &$paramsList[$i];
    call_user_func_array([$st, 'bind_param'], $bind_names);
  } else {
    $st->bind_param('ii', $per, $off);
  }
  $st->execute();
  $res = $st->get_result();
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  if ($res) $res->free();
  $st->close();
}

$totalPages = ($per > 0) ? max(1, (int)ceil($total / $per)) : 1;

/* ---------------- Render ---------------- */
require_once __DIR__ . '/partials/head.php';
require_once __DIR__ . '/partials/header.php';
?>
<section class="py-10">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl md:text-4xl text-[#8B0000] mb-4">Search</h1>

    <form method="get" class="flex flex-wrap items-end gap-3 mb-6" action="<?= htmlspecialchars((defined('SITE_URL') ? rtrim(SITE_URL,'/') . '/search.php' : 'search.php'), ENT_QUOTES) ?>">
      <div>
        <label class="block text-sm text-gray-600 mb-1">Keywords</label>
        <input type="text" name="q" value="<?= htmlspecialchars($qRaw, ENT_QUOTES) ?>" placeholder="Search products..."
               class="w-72 border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#8B0000]">
      </div>
      <div>
        <label class="block text-sm text-gray-600 mb-1">Category (optional)</label>
        <input type="text" name="category" value="<?= htmlspecialchars($cat, ENT_QUOTES) ?>" placeholder="e.g. vastra, mukut"
               class="w-56 border rounded-lg px-3 py-2">
      </div>
      <button class="border rounded-lg px-4 py-2 text-sm">Search</button>
      <?php if ($qRaw!=='' || $cat!==''): ?>
        <a href="<?= (defined('SITE_URL') ? rtrim(SITE_URL,'/') : '') ?>/search.php" class="text-sm underline">Reset</a>
      <?php endif; ?>
    </form>

    <div class="text-sm text-gray-600 mb-4">
      <?php if ($qRaw===''): ?>
        Type something to search products.
      <?php else: ?>
        Showing <?= count($rows) ?> of <?= number_format($total) ?> results for <b>"<?= htmlspecialchars($qRaw, ENT_QUOTES) ?>"</b>
        <?= $cat!=='' ? ' in <b>'.htmlspecialchars($cat, ENT_QUOTES).'</b>' : '' ?>.
      <?php endif; ?>
    </div>

    <?php if (!empty($rows)): ?>
      <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php foreach ($rows as $p):
            $imgSrc = product_img_src((string)($p['image'] ?? ''));
            $productUrl = product_url_from_row($p);

            // Category display: prefer joined category_name, else p['category']
            $catRaw = $p['category_name'] ?? ($p['category'] ?? '');
            if (is_array($catRaw)) {
              $catDisplay = implode(', ', array_map('strval', $catRaw));
            } else {
              $catDisplay = (string)$catRaw;
            }
            $catDisplay = trim($catDisplay);
        ?>
          <div class="bg-white rounded-2xl shadow overflow-hidden group">
            <a href="<?= htmlspecialchars($productUrl, ENT_QUOTES) ?>" class="block aspect-[4/3] overflow-hidden">
              <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES) ?>"
                   class="w-full h-full object-cover group-hover:scale-105 transition">
            </a>
            <div class="p-4">
              <a href="<?= htmlspecialchars($productUrl, ENT_QUOTES) ?>" class="font-medium hover:underline line-clamp-2">
                <?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES) ?>
              </a>

              <?php if ($catDisplay !== ''): ?>
                <div class="text-xs text-gray-500 mt-1">Category: <?= htmlspecialchars(ucfirst($catDisplay), ENT_QUOTES) ?></div>
              <?php endif; ?>

              <div class="mt-2 text-[#D4AF37] font-semibold">₹ <?= number_format((float)($p['price'] ?? 0), 2) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <div class="mt-8 flex flex-wrap items-center gap-2">
          <?php
            $base = (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') . '/search.php?q=' . urlencode($qRaw) . ($cat!=='' ? '&category=' . urlencode($cat) : '');
            for ($i=1; $i<=$totalPages; $i++):
          ?>
            <a href="<?= $base . '&page=' . $i ?>"
               class="px-3 py-1 rounded border <?= $i===$page ? 'bg-[#8B0000] text-white border-[#8B0000]' : 'hover:bg-gray-50' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>

    <?php elseif ($qRaw!==''): ?>
      <div class="bg-white rounded-xl shadow p-8 text-center">
        <p class="text-gray-600">No products found. Try different keywords.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
