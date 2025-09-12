<?php
// admin/product_edit.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';

// --------- Basic checks ----------
if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Database connection (\$conn) not available. Check app/config.php");
}

// small helper to sanitize output
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// --- Ensure margin_percent and sale_percent columns exist (safe, idempotent) ---
function ensureColumns($conn) {
  $cols = [
    'margin_percent' => "DECIMAL(8,2) NOT NULL DEFAULT 0",
    'sale_percent'   => "DECIMAL(8,2) NOT NULL DEFAULT 0"
  ];

  $dbRow = $conn->query("SELECT DATABASE() AS dbname")->fetch_assoc();
  $dbName = $dbRow['dbname'] ?? null;
  if (!$dbName) return;

  foreach ($cols as $col => $definition) {
    $sql = "SELECT COUNT(*) AS cnt FROM information_schema.columns
            WHERE table_schema = '{$conn->real_escape_string($dbName)}'
              AND table_name = 'products'
              AND column_name = '{$conn->real_escape_string($col)}'";
    $res = $conn->query($sql);
    $exists = false;
    if ($res) {
      $exists = (int)$res->fetch_assoc()['cnt'] > 0;
      $res->free();
    }
    if (!$exists) {
      $alter = "ALTER TABLE products ADD COLUMN {$col} {$definition}";
      @$conn->query($alter);
    }
  }
}
ensureColumns($conn);

// --------- fetch product id and product ----------
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  die("Invalid product ID");
}

// fetch product (include our new columns)
$stmt = $conn->prepare("SELECT id, name, sku, slug, category_id, price, old_price, status, image, description, is_featured, is_best_seller, is_on_sale, margin_percent, sale_percent FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$product) die("Product not found.");

// fetch categories
$categories = [];
$res = $conn->query("SELECT id,name FROM categories ORDER BY name");
if ($res) while ($r = $res->fetch_assoc()) $categories[] = $r;

// --------- handle POST (update) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // collect safely
  $name = trim($_POST['name'] ?? '');
  $sku  = trim($_POST['sku'] ?? '');
  $slug = trim($_POST['slug'] ?? '');
  $category_id = (int)($_POST['category_id'] ?? 0);
  $price = (float)($_POST['price'] ?? 0);
  $old_price = ($_POST['old_price'] ?? '') === '' ? null : (float)$_POST['old_price'];
  $status = in_array($_POST['status'] ?? 'active', ['active','inactive']) ? $_POST['status'] : 'active';
  $description = trim($_POST['description'] ?? '');
  $is_featured = isset($_POST['is_featured']) ? 1 : 0;
  $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
  $is_on_sale = isset($_POST['is_on_sale']) ? 1 : 0;
  $margin_percent = (float)($_POST['margin_percent'] ?? 0);
  $sale_percent = (float)($_POST['sale_percent'] ?? 0);

  // if slug empty, generate from name
  if ($slug === '') {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i','-', trim($name)));
    $slug = trim($slug,'-');
  }

  // image upload
  $imageFilename = $product['image'];
  if (isset($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] === UPLOAD_ERR_OK)) {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) {
      $_SESSION['flash_error'] = "Invalid image type. Allowed: jpg, png, webp, gif.";
      header("Location: product_edit.php?id={$id}");
      exit;
    }
    $imageFilename = time() . '_' . bin2hex(random_bytes(6)) . ($ext ? '.' . $ext : '');
    $target = $uploadDir . $imageFilename;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
      $_SESSION['flash_error'] = "Failed to upload image.";
      header("Location: product_edit.php?id={$id}");
      exit;
    }
    // optionally delete old
    if (!empty($product['image'])) {
      $oldFile = __DIR__ . '/../uploads/' . $product['image'];
      if (is_file($oldFile)) @unlink($oldFile);
    }
  }

  // ---- prepare and execute UPDATE (clean, matching types/vars) ----
  // types mapping:
  // name(s), sku(s), slug(s), category_id(i), price(d), old_price(d), status(s), image(s), description(s),
  // is_featured(i), is_best_seller(i), is_on_sale(i), margin_percent(d), sale_percent(d), id(i)
  $sql2 = "UPDATE products SET name=?, sku=?, slug=?, category_id=?, price=?, old_price=?, status=?, image=?, description=?, is_featured=?, is_best_seller=?, is_on_sale=?, margin_percent=?, sale_percent=? WHERE id=?";
  $stmt2 = $conn->prepare($sql2);
  if ($stmt2 === false) {
    $_SESSION['flash_error'] = "Prepare failed: " . $conn->error;
    header("Location: product_edit.php?id={$id}");
    exit;
  }

  // ensure old_price value for binding (use 0.0 if null but also set NULL after if you prefer)
  // mysqli allows binding nulls; we will bind a float or null variable directly.
  $old_price_bind = $old_price === null ? null : (float)$old_price;

  // types string: "sssiddssssiiiddi" ??? -> build exactly:
  // s s s i d d s s s i i i d d i  => "sssiddsssiiiddi"
  $typesFinal = "sssiddsssiiiddi";

  // Note: bind_param requires variables (passed by reference internally). Using these variables is fine.
  // Bind the parameters in the exact order as in query.
  if (!$stmt2->bind_param(
        $typesFinal,
        $name,
        $sku,
        $slug,
        $category_id,
        $price,
        $old_price_bind,
        $status,
        $imageFilename,
        $description,
        $is_featured,
        $is_best_seller,
        $is_on_sale,
        $margin_percent,
        $sale_percent,
        $id
  )) {
    $_SESSION['flash_error'] = "Bind failed: " . $stmt2->error;
    $stmt2->close();
    header("Location: product_edit.php?id={$id}");
    exit;
  }

  $ok = $stmt2->execute();
  $stmt2->close();

  if ($ok) {
    $_SESSION['flash_success'] = "Product updated successfully.";
  } else {
    $_SESSION['flash_error'] = "Update failed: " . $conn->error;
  }

  // reload to reflect saved values
  header("Location: product_edit.php?id={$id}");
  exit;
}

// --------- Page layout and JS calculations ----------
require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/start_layout.php';

// Precompute JS-friendly values
$basePrice = (float)($product['price'] ?? 0);
$marginPercentVal = (float)($product['margin_percent'] ?? 0);
$salePercentVal = (float)($product['sale_percent'] ?? 0);

// compute server-side too for initial display
$selling_before_sale = $basePrice * (1 + $marginPercentVal/100);
$final_sale_price = $selling_before_sale * (1 - $salePercentVal/100);
$customer_saving = $selling_before_sale - $final_sale_price;
$admin_profit = $final_sale_price - $basePrice;
?>

<style>
/* small layout refresh for clearer admin UX */
.form-grid { display: grid; grid-template-columns: 1fr 360px; gap: 24px; }
.card { background: #fff; border-radius: 10px; padding: 18px; box-shadow: 0 1px 0 rgba(0,0,0,0.03); }
.right-card { position: sticky; top: 24px; }
.stat-box { background:#f8faf6; padding:12px; border-radius:8px; margin-bottom:8px; }
.small-muted{ color:#6b7280; font-size:13px; }
.input, textarea, select { width:100%; padding:10px;border:1px solid #e6e1de;border-radius:6px; }
.label { display:block; font-weight:600; margin-bottom:6px; color:#7f1d1d; }
.btn { background:#7f1d1d; color:#fff; padding:10px 14px; border-radius:8px; border:none; cursor:pointer; }
.btn-ghost { background:transparent; border:1px solid #ddd; padding:10px 14px; border-radius:8px; cursor:pointer; }
.kpi-row { display:flex; gap:8px; }
.kpi { flex:1; background:#fff; padding:8px; border-radius:6px; text-align:center; }
</style>

<div class="p-6">
  <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
    <h1 class="text-2xl font-bold">Edit Product #<?= (int)$product['id'] ?> — <?= h($product['name']) ?></h1>
    <div>
      <a class="btn-ghost" href="products.php">← Back</a>
      <a class="btn" href="product_add.php" style="margin-left:8px;">+ Add Product</a>
    </div>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="card" style="border-left:4px solid #10b981; margin-bottom:12px;"><?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="card" style="border-left:4px solid #ef4444; margin-bottom:12px;"><?= h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="form-grid">
    <div class="card">
      <!-- main left column -->
      <div style="display:grid; gap:12px;">
        <div>
          <label class="label">Name *</label>
          <input class="input" name="name" required value="<?= h($product['name']) ?>">
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
          <div>
            <label class="label">SKU</label>
            <input class="input" name="sku" value="<?= h($product['sku']) ?>">
          </div>
          <div>
            <label class="label">Slug (optional)</label>
            <input class="input" name="slug" value="<?= h($product['slug']) ?>">
          </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
          <div>
            <label class="label">Category</label>
            <select name="category_id" class="input">
              <option value="0">-- Select --</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= ((int)$product['category_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= h($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="label">Price (base cost ₹)</label>
            <input class="input" name="price" id="price" type="number" step="0.01" value="<?= h(number_format((float)$product['price'],2,'.','')) ?>">
          </div>
        </div>

        <div>
          <label class="label">Description</label>
          <textarea name="description" class="input" rows="6"><?= h($product['description'] ?? '') ?></textarea>
        </div>

        <div style="display:grid; grid-template-columns: repeat(3,1fr); gap:12px; align-items:center;">
          <label style="display:block;">
            <input type="checkbox" name="is_featured" <?= ($product['is_featured'] ?? 0) ? 'checked' : '' ?>>
            &nbsp; Featured
          </label>
          <label style="display:block;">
            <input type="checkbox" name="is_best_seller" <?= ($product['is_best_seller'] ?? 0) ? 'checked' : '' ?>>
            &nbsp; Best seller
          </label>
          <label style="display:block;">
            <input type="checkbox" name="is_on_sale" <?= ($product['is_on_sale'] ?? 0) ? 'checked' : '' ?>>
            &nbsp; On sale
          </label>
        </div>

        <div style="display:flex; gap:12px; margin-top:12px;">
          <button type="submit" class="btn">Save Changes</button>
          <a href="products.php" class="btn-ghost">Cancel</a>
          <a href="product_delete.php?id=<?= (int)$product['id'] ?>" class="btn-ghost" onclick="return confirm('Delete this product?')">Delete</a>
        </div>
      </div>
    </div>

    <div class="right-card">
      <!-- right column contains status, image, margin/sale UI -->
      <div class="card">
        <label class="label">Status</label>
        <select name="status" class="input"><?= $product['status']==='active' ? '<option value="active" selected>Active</option><option value="inactive">Inactive</option>' : '<option value="active">Active</option><option value="inactive" selected>Inactive</option>' ?></select>

        <div style="margin-top:12px;">
          <label class="label">Main Image</label>
          <?php if (!empty($product['image'])): ?>
            <div style="margin-bottom:8px;"><img src="../uploads/<?= h($product['image']) ?>" style="width:150px;border-radius:6px;border:1px solid #eee"></div>
          <?php endif; ?>
          <input type="file" name="image" class="input">
        </div>
      </div>

      <div class="card" style="margin-top:12px;">
        <div class="small-muted">Margin Settings</div>
        <label class="label" style="margin-top:8px">Margin (%)</label>
        <input class="input" id="margin_percent" name="margin_percent" type="number" step="0.01" value="<?= h(number_format($marginPercentVal,2,'.','')) ?>">
        <div class="small-muted" style="margin-top:8px">Set product margin. Selling price before sale = base cost + margin%.</div>

        <div style="margin-top:12px" class="kpi-row">
          <div class="kpi">
            <div style="font-size:12px" class="small-muted">Selling (before sale)</div>
            <div id="selling_before" style="font-weight:700; font-size:18px">₹ <?= number_format($selling_before_sale,2) ?></div>
          </div>
          <div class="kpi">
            <div style="font-size:12px" class="small-muted">Margin Amount</div>
            <div id="margin_amount" style="font-weight:700; font-size:18px">₹ <?= number_format(($selling_before_sale - $basePrice),2) ?></div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:12px;">
        <div class="small-muted">Sale / Discount</div>
        <label class="label" style="margin-top:8px">Sale (%)</label>
        <input class="input" id="sale_percent" name="sale_percent" type="number" step="0.01" value="<?= h(number_format($salePercentVal,2,'.','')) ?>">
        <div class="small-muted" style="margin-top:8px">When sale % applied, final sale price and savings will be shown below.</div>

        <div style="margin-top:12px" class="kpi-row">
          <div class="kpi">
            <div class="small-muted">Final Sale Price</div>
            <div id="final_sale" style="font-weight:700; font-size:18px">₹ <?= number_format($final_sale_price,2) ?></div>
          </div>
          <div class="kpi">
            <div class="small-muted">Customer Saving</div>
            <div id="customer_saving" style="font-weight:700; font-size:18px">₹ <?= number_format($customer_saving,2) ?></div>
          </div>
        </div>

        <div style="margin-top:10px">
          <div class="small-muted">Admin Profit (final sale - base cost)</div>
          <div id="admin_profit" style="font-weight:700; font-size:16px">₹ <?= number_format($admin_profit,2) ?></div>
        </div>
      </div>

    </div> <!-- end right column -->
  </form>
</div>

<script>
  function toNum(v){ v = parseFloat(v); return isNaN(v) ? 0 : v; }
  const priceEl = document.getElementById('price');
  const marginEl = document.getElementById('margin_percent');
  const saleEl = document.getElementById('sale_percent');

  const sellingBeforeEl = document.getElementById('selling_before');
  const marginAmountEl = document.getElementById('margin_amount');
  const finalSaleEl = document.getElementById('final_sale');
  const customerSavingEl = document.getElementById('customer_saving');
  const adminProfitEl = document.getElementById('admin_profit');

  function recalc() {
    const base = toNum(priceEl.value);
    const marginPct = toNum(marginEl.value);
    const salePct = toNum(saleEl.value);

    const sellingBefore = base * (1 + marginPct/100);
    const finalSale = sellingBefore * (1 - salePct/100);
    const customerSaving = Math.max(0, sellingBefore - finalSale);
    const adminProfit = finalSale - base;

    sellingBeforeEl.textContent = '₹ ' + sellingBefore.toFixed(2);
    marginAmountEl.textContent = '₹ ' + (sellingBefore - base).toFixed(2);
    finalSaleEl.textContent = '₹ ' + finalSale.toFixed(2);
    customerSavingEl.textContent = '₹ ' + customerSaving.toFixed(2);
    adminProfitEl.textContent = '₹ ' + adminProfit.toFixed(2);
  }

  [priceEl, marginEl, saleEl].forEach(el => {
    if (el) el.addEventListener('input', recalc);
  });

  recalc();
</script>

<?php
require_once __DIR__ . '/includes/end_layout.php';
?>
