<?php
// admin/category_edit.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
if (!isset($conn) || !($conn instanceof mysqli)) die("DB connection missing.");

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function slugify($text){ $t = strtolower(trim($text)); $t = preg_replace('/[^a-z0-9]+/i','-',$t); return trim($t,'-'); }

// web + disk paths (use the same folder as products)
$assetsWebPrefix = '/pushtimargshringar/assets/images/products/';
$assetsDiskPath  = __DIR__ . '/../assets/images/products/'; // must end with slash

// legacy uploads folder (keep for compatibility)
$legacyDiskPath  = __DIR__ . '/../uploads/categories/';
$legacyWebPrefix = '/uploads/categories/';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { $_SESSION['flash_error'] = "Invalid category."; header("Location: categories.php"); exit; }

// fetch existing category
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$cat = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$cat) { $_SESSION['flash_error'] = "Category not found."; header("Location: categories.php"); exit; }

// helper: find where actual image file exists and return an array with web path and disk path or null
function find_image_paths($filename, $assetsDiskPath, $assetsWebPrefix, $legacyDiskPath, $legacyWebPrefix) {
  if (empty($filename)) return null;
  $p1 = $assetsDiskPath . $filename;
  if (file_exists($p1) && is_file($p1)) {
    return ['disk' => $p1, 'web' => $assetsWebPrefix . $filename];
  }
  $p2 = $legacyDiskPath . $filename;
  if (file_exists($p2) && is_file($p2)) {
    return ['disk' => $p2, 'web' => $legacyWebPrefix . $filename];
  }
  return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $slug = trim($_POST['slug'] ?? '');
  $description = trim($_POST['description'] ?? '');
  if ($slug === '') $slug = slugify($name);

  if ($name === '') {
    $_SESSION['flash_error'] = "Name required.";
    header("Location: category_edit.php?id={$id}");
    exit;
  }

  // start with the existing filename from DB
  $imgName = $cat['image'];

  // upload new image if provided -> move to assets/images/products/
  if (!empty($_FILES['image']['name'])) {
    // ensure assets folder exists
    if (!is_dir($assetsDiskPath)) {
      if (!mkdir($assetsDiskPath, 0755, true)) {
        $_SESSION['flash_error'] = "Unable to create images folder.";
        header("Location: category_edit.php?id={$id}");
        exit;
      }
    }

    // basic upload error check
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
      $_SESSION['flash_error'] = "Upload error code: " . $_FILES['image']['error'];
      header("Location: category_edit.php?id={$id}");
      exit;
    }

    // validate extension
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) {
      $_SESSION['flash_error'] = "Invalid image type. Allowed: jpg, png, webp, gif.";
      header("Location: category_edit.php?id={$id}");
      exit;
    }

    // safe unique filename and move
    $base = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($_FILES['image']['name'], PATHINFO_FILENAME));
    $newName = $base . '_' . time() . '.' . $ext;
    $target = $assetsDiskPath . $newName;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
      $_SESSION['flash_error'] = "Failed to move uploaded file. Check folder permissions.";
      header("Location: category_edit.php?id={$id}");
      exit;
    }

    @chmod($target, 0644);

    // delete old image if it exists (check both possible locations)
    if (!empty($imgName)) {
      $oldPaths = [
        $assetsDiskPath . $imgName,
        $legacyDiskPath . $imgName
      ];
      foreach ($oldPaths as $p) {
        if (file_exists($p) && is_file($p)) {
          @unlink($p);
          break;
        }
      }
    }

    $imgName = $newName; // set new filename for DB
  }

  // remove image checkbox
  if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
    // delete from both places if present
    if (!empty($imgName)) {
      $oldPaths = [
        $assetsDiskPath . $imgName,
        $legacyDiskPath . $imgName
      ];
      foreach ($oldPaths as $p) {
        if (file_exists($p) && is_file($p)) {
          @unlink($p);
        }
      }
    }
    $imgName = null;
  }

  $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, image = ? WHERE id = ?");
  $stmt->bind_param('ssssi', $name, $slug, $description, $imgName, $id);
  if ($stmt->execute()) {
    $_SESSION['flash_success'] = "Category updated.";
    $stmt->close();
    header("Location: categories.php");
    exit;
  } else {
    $_SESSION['flash_error'] = "Update failed: " . $stmt->error;
    $stmt->close();
    header("Location: category_edit.php?id={$id}");
    exit;
  }
}

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/start_layout.php';
?>

<div class="p-6 max-w-3xl mx-auto">
  <h1 class="text-2xl font-bold text-deepgreen mb-6">Edit Category</h1>

  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded"><?= h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="card p-6 space-y-6">
    <!-- Name + Slug -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm mb-1">Name</label>
        <input type="text" name="name" class="input" value="<?= h($cat['name']) ?>" required>
      </div>

      <div>
        <label class="block text-sm mb-1">Slug</label>
        <input type="text" name="slug" class="input" value="<?= h($cat['slug']) ?>" placeholder="Leave blank to auto-generate">
      </div>
    </div>

    <!-- Description -->
    <div>
      <label class="block text-sm mb-1">Description</label>
      <textarea name="description" class="input" rows="4"><?= h($cat['description']) ?></textarea>
    </div>

    <!-- Image block with live preview -->
    <div>
      <label class="block text-sm mb-2">Category Image</label>
      <div class="flex items-start gap-6">
        <div class="w-32 h-32 border rounded overflow-hidden bg-gray-50 flex items-center justify-center" id="previewBox">
          <?php
            $found = find_image_paths($cat['image'], $assetsDiskPath, $assetsWebPrefix, $legacyDiskPath, $legacyWebPrefix);
            if ($found): ?>
              <img id="previewImg" src="<?= h($found['web']) ?>" alt="preview" class="w-full h-full object-cover">
          <?php else: ?>
              <img id="previewImg" src="" alt="preview" class="hidden w-full h-full object-cover">
              <div id="previewPlaceholder" class="text-gray-400 text-xs">No image</div>
          <?php endif; ?>
        </div>

        <div class="flex-1">
          <input type="file" name="image" id="imageInput" accept="image/*">
          <div class="mt-2 text-sm text-gray-600">Choose an image to replace current one. Recommended: square or 1:1 ratio.</div>

          <?php if (!empty($cat['image'])): ?>
            <div class="mt-3">
              <label class="inline-flex items-center">
                <input type="checkbox" name="remove_image" id="removeImage" value="1" class="mr-2">
                Remove current image
              </label>
            </div>
          <?php endif; ?>

          <div class="mt-4 flex gap-3">
            <button class="btn btn-primary">Save Changes</button>
            <a href="categories.php" class="btn btn-ghost">Cancel</a>
            <a href="category_delete.php?id=<?= (int)$cat['id'] ?>" class="btn btn-danger ml-auto" onclick="return confirm('Delete this category?')">Delete</a>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
(function(){
  const input = document.getElementById('imageInput');
  const previewImg = document.getElementById('previewImg');
  const previewPlaceholder = document.getElementById('previewPlaceholder');
  const removeCheckbox = document.getElementById('removeImage');

  // show file preview when selected
  if (input) {
    input.addEventListener('change', function(e){
      const file = this.files && this.files[0];
      if (!file) return;
      const allowed = ['image/jpeg','image/png','image/webp','image/gif'];
      if (!allowed.includes(file.type)) {
        alert('Invalid image type. Allowed: jpg, png, webp, gif.');
        this.value = '';
        return;
      }
      const reader = new FileReader();
      reader.onload = function(ev){
        if (previewImg) {
          previewImg.src = ev.target.result;
          previewImg.classList.remove('hidden');
        }
        if (previewPlaceholder) {
          previewPlaceholder.style.display = 'none';
        }
        if (removeCheckbox) removeCheckbox.checked = false;
      };
      reader.readAsDataURL(file);
    });
  }

  // if remove checkbox toggled, hide preview image
  if (removeCheckbox) {
    removeCheckbox.addEventListener('change', function(){
      if (this.checked) {
        if (previewImg) {
          previewImg.src = '';
          previewImg.classList.add('hidden');
        }
        if (previewPlaceholder) previewPlaceholder.style.display = 'block';
        if (input) input.value = '';
      }
    });
  }
})();
</script>

<?php
require_once __DIR__ . '/includes/end_layout.php';
