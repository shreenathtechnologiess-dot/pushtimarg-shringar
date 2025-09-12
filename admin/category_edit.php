<?php
// admin/category_edit.php
session_start();
require_once __DIR__ . '/../app/config.php';
if (!isset($conn) || !($conn instanceof mysqli)) die("DB connection missing.");

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function slugify($text){ $t = strtolower(trim($text)); $t = preg_replace('/[^a-z0-9]+/i','-',$t); return trim($t,'-'); }

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

// handle POST (update)
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

  $imgName = $cat['image'];

  // upload new image if provided
  if (!empty($_FILES['image']['name'])) {
    $upDir = __DIR__ . '/../uploads/categories/';
    if (!is_dir($upDir)) mkdir($upDir, 0777, true);
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed)) {
      $_SESSION['flash_error'] = "Invalid image type. Allowed: jpg, png, webp, gif.";
      header("Location: category_edit.php?id={$id}");
      exit;
    }
    $newName = uniqid('cat_', true) . '.' . $ext;
    $target = $upDir . $newName;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
      // delete old image
      if (!empty($imgName) && file_exists($upDir . $imgName)) @unlink($upDir . $imgName);
      $imgName = $newName;
    } else {
      $_SESSION['flash_error'] = "Failed to upload image.";
      header("Location: category_edit.php?id={$id}");
      exit;
    }
  }

  // remove image checkbox
  if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
    $upDir = __DIR__ . '/../uploads/categories/';
    if (!empty($imgName) && file_exists($upDir . $imgName)) @unlink($upDir . $imgName);
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
          <?php if (!empty($cat['image']) && file_exists(__DIR__ . '/../uploads/categories/' . $cat['image'])): ?>
            <img id="previewImg" src="/uploads/categories/<?= h($cat['image']) ?>" alt="preview" class="w-full h-full object-cover">
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
      // basic client-side validation
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
        // uncheck remove checkbox if admin previously checked it
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
        // clear file input as well
        if (input) input.value = '';
      } else {
        // if unchecked and there is a server-side image, we cannot restore client-side preview.
        // Admin can re-upload to see preview.
      }
    });
  }
})();
</script>

<?php
require_once __DIR__ . '/includes/end_layout.php';
