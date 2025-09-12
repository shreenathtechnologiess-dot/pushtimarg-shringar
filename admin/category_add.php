<?php
// admin/category_add.php
session_start();
require_once __DIR__ . '/../app/config.php';
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

  // image upload
  $imgName = null;
  if (!empty($_FILES['image']['name'])) {
    $upDir = __DIR__ . '/../uploads/categories/';
    if (!is_dir($upDir)) mkdir($upDir, 0777, true);
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $imgName = uniqid('cat_', true) . '.' . strtolower($ext);
    $target = $upDir . $imgName;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
      $_SESSION['flash_error'] = "Failed to upload image.";
      header("Location: category_add.php");
      exit;
    }
  }

  $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, image, created_at) VALUES (?, ?, ?, ?, NOW())");
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

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/start_layout.php';
?>
<div class="p-6 max-w-3xl mx-auto">
  <h1 class="text-2xl font-bold text-deepgreen mb-6">Add Category</h1>

  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded"><?= h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
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
