<?php
// admin/blog/edit.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../_csrf.php';

if (!isset($conn) && isset($con)) $conn = $con;

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }

// Fetch post
$post = null;
$stmt = $conn->prepare("SELECT id, title, slug, content, video_url FROM posts WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$post = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$post) {
  $_SESSION['flash_error'] = "Post not found.";
  header("Location: index.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $slug = trim($_POST['slug'] ?? '');
  $content = trim($_POST['content'] ?? '');
  $video_url = trim($_POST['video_url'] ?? '');
  $csrf = $_POST['csrf'] ?? '';

  if (!admin_verify_csrf($csrf)) {
    $_SESSION['flash_error'] = "Invalid CSRF token.";
    header("Location: edit.php?id=$id"); exit;
  }

  if ($title === '' || $slug === '') {
    $_SESSION['flash_error'] = "Title and Slug are required.";
    header("Location: edit.php?id=$id"); exit;
  }

  $slug = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($slug));
  $slug = trim($slug, '-');

  $stmt = $conn->prepare("UPDATE posts SET title=?, slug=?, content=?, video_url=?, updated_at=NOW() WHERE id=?");
  if (!$stmt) {
    $_SESSION['flash_error'] = "Prepare failed: " . $conn->error;
    header("Location: edit.php?id=$id"); exit;
  }
  $stmt->bind_param("ssssi", $title, $slug, $content, $video_url, $id);
  if ($stmt->execute()) {
    $_SESSION['flash_success'] = "Post updated.";
    header("Location: index.php"); exit;
  } else {
    $_SESSION['flash_error'] = "Error: " . $stmt->error;
  }
  $stmt->close();
}

include __DIR__ . '/../includes/head.php';
include __DIR__ . '/../includes/start_layout.php';
?>

<h1 class="card-title mb-4">Edit Video Post</h1>

<form method="post" class="card space-y-4">
  <div>
    <label>Title</label>
    <input type="text" name="title" class="input" value="<?= h($post['title']) ?>" required>
  </div>

  <div>
    <label>Slug (url-friendly)</label>
    <input type="text" name="slug" class="input" value="<?= h($post['slug']) ?>" required>
  </div>

  <div>
    <label>Video URL (YouTube/Vimeo or direct mp4)</label>
    <input type="text" name="video_url" class="input" value="<?= h($post['video_url']) ?>" placeholder="https://www.youtube.com/watch?v=...">
    <div class="small-muted mt-1">Optional. If provided, it will be embedded on the public post.</div>
  </div>

  <div>
    <label>Content / Description</label>
    <textarea name="content" rows="8" class="input"><?= h($post['content']) ?></textarea>
  </div>

  <input type="hidden" name="csrf" value="<?= admin_csrf_token() ?>">
  <button type="submit" class="btn btn-primary">Update Post</button>
</form>

<?php include __DIR__ . '/../includes/end_layout.php'; ?>
