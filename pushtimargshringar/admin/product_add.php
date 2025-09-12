<?php
// admin/product_add.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
if (!isset($conn) || !($conn instanceof mysqli)) die("DB connection missing.");

// helpers
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function slugify($text){ $text = strtolower(trim($text)); $text = preg_replace('/[^a-z0-9]+/i','-',$text); return trim($text,'-'); }

// upload paths
$assetsDir = __DIR__ . '/../assets/images/products/';
$assetsWeb = '/pushtimargshringar/assets/images/products/'; // optionally used for previews

// fetch categories
$categories = [];
$res = $conn->query("SELECT id, name FROM categories ORDER BY name");
if ($res) while ($r = $res->fetch_assoc()) $categories[] = $r;

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // gather
  $name = trim($_POST['name'] ?? '');
  $sku  = trim($_POST['sku'] ?? '');
  $slug = trim($_POST['slug'] ?? '');
  $category_id = (int)($_POST['category_id'] ?? 0);
  $price = (float)($_POST['price'] ?? 0);
  $status = in_array($_POST['status'] ?? 'active', ['active','inactive']) ? $_POST['status'] : 'active';
  $description = trim($_POST['description'] ?? '');
  $is_featured = isset($_POST['is_featured']) ? 1 : 0;
  $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
  $is_on_sale = isset($_POST['is_on_sale']) ? 1 : 0;
  $old_price = ($_POST['old_price'] ?? '') === '' ? null : (float)$_POST['old_price'];

  if ($slug === '') $slug = slugify($name);

  // validations
  if ($name === '') $errors[] = "Name is required.";
  if ($price <= 0) $errors[] = "Price must be > 0.";
  // (optional) you can validate unique slug/sku here.

  // handle image upload
  $imageFilename = null;
  if (!empty($_FILES['image']['name'])) {
    if (!is_dir($assetsDir)) {
      mkdir($assetsDir, 0777, true);
      // set permission if needed
    }
    $tmp = $_FILES['image']['tmp_name'];
    $orig = $_FILES['image']['name'];
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) {
      $errors[] = "Invalid image type. Allowed: " . implode(',', $allowed);
    } else {
      $imageFilename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
      $target = $assetsDir . $imageFilename;
      if (!move_uploaded_file($tmp, $target)) {
        $errors[] = "Failed to move uploaded image.";
        $imageFilename = null;
      }
    }
  }

  if (empty($errors)) {
    // Insert into DB: note types must match count of vars
    $sql = "INSERT INTO products
            (name, sku, slug, category_id, price, status, image, description, is_featured, is_best_seller, is_on_sale, old_price, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
      $errors[] = "Prepare failed: " . $conn->error;
    } else {
      // Bind types:
      // name(s), sku(s), slug(s), category_id(i), price(d), status(s), image(s), description(s),
      // is_featured(i), is_best_seller(i), is_on_sale(i), old_price(s) - bind as string so null allowed
      $old_price_bind = $old_price === null ? null : (string)$old_price;
      $types = "sssidsssiiis"; // 12 types
      // variables in same order
      $bind_ok = $stmt->bind_param(
        $types,
        $name,
        $sku,
        $slug,
        $category_id,
        $price,
        $status,
        $imageFilename,
        $description,
        $is_featured,
        $is_best_seller,
        $is_on_sale,
        $old_price_bind
      );

      if (!$bind_ok) {
        $errors[] = "Bind failed: " . $stmt->error;
      } else {
        if ($stmt->execute()) {
          $success = "Product added successfully.";
          $_SESSION['flash_success'] = $success;
          $stmt->close();
          header("Location: products.php");
          exit;
        } else {
          $errors[] = "Insert failed: " . $stmt->error;
        }
      }
      $stmt->close();
    }
  }
}
?>
<?php require_once __DIR__ . '/includes/head.php'; ?>
<?php require_once __DIR__ . '/includes/start_layout.php'; ?>

<style>
/* a lightweight form layout — feel free to adapt to your theme classes */
.container { max-width:980px; margin:0 auto; padding:24px; }
.form-grid { display:grid; grid-template-columns: 1fr 360px; gap:20px; align-items:start; }
.card { background:#fff; border-radius:10px; padding:18px; box-shadow:0 1px 0 rgba(0,0,0,0.04); }
.input, textarea, select { width:100%; padding:10px; border:1px solid #e6e1de; border-radius:8px; }
.label { font-weight:600; color:#7f1d1d; margin-bottom:6px; display:block; }
.btn { background:#7f1d1d; color:#fff; padding:10px 14px; border-radius:8px; border:none; cursor:pointer; }
.small { font-size:13px; color:#6b7280; }
.image-preview { width:100%; max-height:280px; object-fit:contain; border-radius:8px; border:1px solid #eee; background:#fafafa; display:block; }
.inline-row { display:flex; gap:12px; align-items:center; }
.chips { display:flex; gap:8px; flex-wrap:wrap; }
.chip { background:#f3f4f6; padding:6px 10px; border-radius:16px; font-size:13px; }
</style>

<div class="container">
  <h1 style="margin-bottom:16px;">Add Product</h1>

  <?php if (!empty($errors)): ?>
    <div class="card" style="border-left:4px solid #ef4444; margin-bottom:12px;">
      <?php foreach ($errors as $e) echo '<div style="margin-bottom:6px;">' . h($e) . '</div>'; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="card" style="border-left:4px solid #10b981; margin-bottom:12px;">
      <?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="form-grid card">
    <div>
      <div style="display:grid; gap:12px;">
        <div>
          <label class="label">Name *</label>
          <input class="input" type="text" name="name" required value="<?= h($_POST['name'] ?? '') ?>">
        </div>

        <div style="display:grid; grid-template-columns: 1fr 200px; gap:12px;">
          <div>
            <label class="label">Category</label>
            <select name="category_id" class="input">
              <option value="0">-- Select category --</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (isset($_POST['category_id']) && (int)$_POST['category_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= h($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="label">Price (₹)</label>
            <input class="input" type="number" step="0.01" name="price" value="<?= h($_POST['price'] ?? '') ?>">
          </div>
        </div>

        <div>
          <label class="label">Short Description</label>
          <textarea name="description" rows="4" class="input"><?= h($_POST['description'] ?? '') ?></textarea>
        </div>

        <div style="display:grid; grid-template-columns: repeat(3,1fr); gap:10px; align-items:center;">
          <label><input type="checkbox" name="is_featured" <?= isset($_POST['is_featured']) ? 'checked' : '' ?>> &nbsp;Featured</label>
          <label><input type="checkbox" name="is_best_seller" <?= isset($_POST['is_best_seller']) ? 'checked' : '' ?>> &nbsp;Best seller</label>
          <label><input type="checkbox" name="is_on_sale" <?= isset($_POST['is_on_sale']) ? 'checked' : '' ?>> &nbsp;On sale</label>
        </div>

        <div style="display:flex; gap:12px; margin-top:8px;">
          <button class="btn" type="submit">Save Product</button>
          <a href="products.php" class="btn" style="background:#eee; color:#7f1d1d; text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Cancel</a>
        </div>
      </div>
    </div>

    <aside>
      <div style="display:grid; gap:12px;">
        <div>
          <label class="label">SKU</label>
          <input class="input" name="sku" value="<?= h($_POST['sku'] ?? '') ?>">
        </div>

        <div>
          <label class="label">Slug (optional)</label>
          <div style="display:flex; gap:8px;">
            <input class="input" name="slug" id="slug" value="<?= h($_POST['slug'] ?? '') ?>">
            <button type="button" class="btn" id="makeSlug" style="padding:8px 10px;">Make</button>
          </div>
          <div class="small">Leave blank to auto generate from name.</div>
        </div>

        <div>
          <label class="label">Old Price (optional)</label>
          <input class="input" name="old_price" type="number" step="0.01" value="<?= h($_POST['old_price'] ?? '') ?>">
        </div>

        <div>
          <label class="label">Status</label>
          <select name="status" class="input">
            <option value="active" <?= (($_POST['status'] ?? '')==='active')?'selected':'' ?>>Active</option>
            <option value="inactive" <?= (($_POST['status'] ?? '')==='inactive')?'selected':'' ?>>Inactive</option>
          </select>
        </div>

        <div>
          <label class="label">Main Image</label>
          <input type="file" name="image" id="imageInput" accept="image/*">
          <div class="small" style="margin-top:6px;">Will be saved in <code>assets/images/products/</code></div>
          <div style="margin-top:8px;">
            <img id="preview" class="image-preview" style="display:none;" alt="preview">
            <div id="noPreview" class="small" style="color:#777; margin-top:6px;">No image selected</div>
          </div>
        </div>

        <div class="card" style="padding:10px;">
          <div style="font-weight:700; margin-bottom:6px;">Quick tips</div>
          <div class="small">Use clear product name and upload a clean image. If product is on sale, check "On sale" and set Old Price to show discount.</div>
        </div>

      </div>
    </aside>
  </form>
</div>

<script>
  // slug maker
  document.getElementById('makeSlug').addEventListener('click', function(){
    const name = document.querySelector('input[name="name"]').value || '';
    const slug = name.toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
    document.getElementById('slug').value = slug;
  });

  // preview image
  const imgIn = document.getElementById('imageInput');
  const preview = document.getElementById('preview');
  const noPreview = document.getElementById('noPreview');
  imgIn && imgIn.addEventListener('change', function(e){
    const f = e.target.files[0];
    if (!f) { preview.style.display='none'; noPreview.style.display='block'; return; }
    const url = URL.createObjectURL(f);
    preview.src = url;
    preview.style.display='block';
    noPreview.style.display='none';
  });
</script>

<?php require_once __DIR__ . '/includes/end_layout.php'; ?>
