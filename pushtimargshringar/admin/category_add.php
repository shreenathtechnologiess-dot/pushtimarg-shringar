<?php
// admin/category_add.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
if (!isset($conn) || !($conn instanceof mysqli)) die("DB connection missing.");

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function slugify($text){ $t = strtolower(trim($text)); $t = preg_replace('/[^a-z0-9]+/i','-',$t); return trim($t,'-'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $slug = trim($_POST['slug'] ?? '');
  $description = trim($_POST['description'] ?? '');
  if ($slug === '') $slug = slugify($name);

  if ($name === '') {
    $_SESSION['flash_error'] = "Name is required.";
    header("Location: category_add.php");
    exit;
  }

  // --- image upload (save to assets/images/products so display path matches) ---
  $imgName = null;
  if (!empty($_FILES['image']['name'])) {
    // disk path (must end with slash)
    $upDir = __DIR__ . '/../assets/images/products/';
    if (!is_dir($upDir)) {
      if (!mkdir($upDir, 0755, true)) {
        $_SESSION['flash_error'] = "Unable to create images folder.";
        header("Location: category_add.php");
        exit;
      }
    }

    // basic upload error check
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
      $_SESSION['flash_error'] = "Upload error code: " . $_FILES['image']['error'];
      header("Location: category_add.php");
      exit;
    }

    // validate extension
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) {
      $_SESSION['flash_error'] = "Invalid image type. Allowed: jpg, png, gif, webp.";
      header("Location: category_add.php");
      exit;
    }

    // safe unique filename
    $base = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($_FILES['image']['name'], PATHINFO_FILENAME));
    $imgName = $base . '_' . time() . '.' . $ext;
    $target = $upDir . $imgName;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
      $_SESSION['flash_error'] = "Failed to move uploaded file. Check folder permissions.";
      header("Location: category_add.php");
      exit;
    }

    // optional permissions
    @chmod($target, 0644);
  }

  // Insert to DB (image can be null)
  $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, image, created_at) VALUES (?, ?, ?, ?, NOW())");
  if (!$stmt) {
    $_SESSION['flash_error'] = "Prepare failed: " . $conn->error;
    header("Location: category_add.php"); exit;
  }
  $stmt->bind_param('ssss', $name, $slug, $description, $imgName);
  if ($stmt->execute()) {
    $_SESSION['flash_success'] = "Category added.";
    $stmt->close();
    header("Location: categories.php");
    exit;
  } else {
    $_SESSION['flash_error'] = "Insert failed: " . $stmt->error;
    $stmt->close();
    header("Location: category_add.php");
    exit;
  }
}

// render form (same as before)
require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/start_layout.php';
?>
<div class="p-6 max-w-3xl mx-auto">
  <h1 class="text-2xl font-bold text-deepgreen mb-6">Add Category</h1>

  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded"><?= h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="card p-6 space-y-4">
    <div>
      <label class="block text-sm">Name</label>
      <input type="text" name="name" class="input" required>
    </div>

    <div>
      <label class="block text-sm">Slug (optional)</label>
      <input type="text" name="slug" class="input" placeholder="Leave blank to auto-generate from name">
    </div>

    <div>
      <label class="block text-sm">Description (optional)</label>
      <textarea name="description" class="input" rows="4"></textarea>
    </div>

    <div>
      <label class="block text-sm mb-2">Image</label>
      <div class="border-dashed border-2 border-gray-200 p-6 rounded text-center">
        <input type="file" name="image" accept="image/*">
        <div class="text-xs text-gray-500 mt-2">Optional. Drag & drop or browse files.</div>
      </div>
    </div>

    <div class="flex gap-3">
      <button class="btn btn-primary">Save</button>
      <a href="categories.php" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/includes/end_layout.php'; ?>
