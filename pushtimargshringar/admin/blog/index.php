<?php
// admin/blog/index.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../_csrf.php';

if (!isset($conn) && isset($con)) $conn = $con;

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Fetch posts (includes video_url)
$posts = [];
if ($conn instanceof mysqli) {
  $sql = "SELECT id, title, slug, video_url, created_at FROM posts ORDER BY created_at DESC";
  if ($res = $conn->query($sql)) {
    $posts = $res->fetch_all(MYSQLI_ASSOC);
  }
}

// Layout start
include __DIR__ . '/../includes/head.php';
include __DIR__ . '/../includes/start_layout.php';
?>

<div class="mb-6 flex justify-between items-center">
  <h1 class="card-title">Blog Posts (Video)</h1>
  <a href="create.php" class="btn btn-primary"><i class="fa fa-plus mr-1"></i> New Post</a>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="mb-4 p-3 bg-red-100 text-red-800 rounded"><?= h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<div class="card">
  <table class="table">
    <thead>
      <tr>
        <th style="width:70px">ID</th>
        <th>Title</th>
        <th>Video</th>
        <th>Created</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($posts)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No posts yet.</td></tr>
      <?php else: foreach ($posts as $p): ?>
        <tr>
          <td><?= (int)$p['id'] ?></td>
          <td><?= h($p['title']) ?></td>
          <td>
            <?php if (!empty($p['video_url'])): ?>
              <span class="small-muted">Yes</span>
            <?php else: ?>
              <span class="small-muted">—</span>
            <?php endif; ?>
          </td>
          <td><?= date('d M Y H:i', strtotime($p['created_at'])) ?></td>
          <td>
            <a href="../../blog/view.php?slug=<?= urlencode($p['slug']) ?>" class="text-blue-600 hover:underline" target="_blank">View</a> |
            <a href="edit.php?id=<?= (int)$p['id'] ?>" class="text-blue-600 hover:underline">Edit</a> |
            <form method="post" action="delete.php" style="display:inline" onsubmit="return confirm('Delete this post?')">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <input type="hidden" name="csrf" value="<?= admin_csrf_token() ?>">
              <button type="submit" class="text-red-600 hover:underline bg-transparent border-none">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/end_layout.php'; ?>
