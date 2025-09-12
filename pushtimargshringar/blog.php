<?php
// blog.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';

// helpers
function site_url($p=''){ return (defined('SITE_URL')? rtrim(SITE_URL,'/').'/' : '/') . ltrim($p, '/'); }
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function excerpt_text($html, $len = 220) {
  $txt = trim(strip_tags($html));
  return (mb_strlen($txt) > $len) ? mb_substr($txt, 0, $len) . '...' : $txt;
}
function post_thumb_src($row) {
  if (!empty($row['image'])) {
    return '/assets/images/posts/' . basename($row['image']);
  }
  return '/assets/images/posts/placeholder.jpg';
}

// head + header
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';

// pagination
$perPage = 6;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$total = 0;
$posts = [];

if ($conn instanceof mysqli) {
  $r = $conn->query("SELECT COUNT(*) AS c FROM posts");
  $total = ($r) ? (int)($r->fetch_assoc()['c'] ?? 0) : 0;

  $stmt = $conn->prepare("SELECT id, title, slug, content, video_url, image, created_at 
                          FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?");
  $stmt->bind_param("ii", $perPage, $offset);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res) $posts = $res->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}
?>

<main class="max-w-7xl mx-auto px-4 py-10">
  <header class="mb-8">
    <h1 class="text-3xl md:text-4xl font-semibold text-[#8B0000]">Blog</h1>
    <p class="text-sm text-gray-600 mt-2">Latest posts, images and videos from Pushtimarg Shringar.</p>
  </header>

  <?php if (empty($posts)): ?>
    <div class="rounded shadow p-6 bg-white text-center text-gray-600">No posts yet.</div>
  <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($posts as $p): 
        $thumb = post_thumb_src($p);
        $excerpt = excerpt_text($p['content']);
        $postUrl = site_url('blog_view.php?slug=' . urlencode($p['slug']));
      ?>
        <article class="bg-white rounded-lg shadow overflow-hidden flex flex-col group">
          <a href="<?= h($postUrl) ?>" class="block aspect-[16/9] overflow-hidden relative">
            <img src="<?= h($thumb) ?>" alt="<?= h($p['title']) ?>" class="w-full h-full object-cover transition group-hover:scale-105">
            <?php if (!empty($p['video_url'])): ?>
              <span class="absolute bottom-2 right-2 bg-red-600 text-white text-xs px-2 py-1 rounded">🎥 Video</span>
            <?php endif; ?>
          </a>
          <div class="p-4 flex-1 flex flex-col">
            <h2 class="text-lg font-semibold leading-tight mb-1">
              <a href="<?= h($postUrl) ?>" class="hover:text-[#8B0000]"><?= h($p['title']) ?></a>
            </h2>
            <div class="text-xs text-gray-500 mb-2"><?= date('d M Y', strtotime($p['created_at'])) ?></div>
            <p class="text-sm text-gray-700 flex-1"><?= h($excerpt) ?></p>
            <div class="mt-4">
              <a href="<?= h($postUrl) ?>" class="text-sm bg-[#8B0000] text-white px-3 py-1 rounded hover:bg-[#6f0000]">Read More</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php $pages = max(1, ceil($total / $perPage)); ?>
    <?php if ($pages > 1): ?>
      <nav class="mt-8 flex justify-center items-center gap-3">
        <?php if ($page > 1): ?>
          <a href="<?= site_url('blog.php?page='.($page-1)) ?>" class="px-3 py-1 rounded border hover:bg-cream">Prev</a>
        <?php endif; ?>
        <span class="text-sm text-gray-600">Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?>
          <a href="<?= site_url('blog.php?page='.($page+1)) ?>" class="px-3 py-1 rounded border hover:bg-cream">Next</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
<?php include __DIR__ . '/partials/scripts.php'; ?>
