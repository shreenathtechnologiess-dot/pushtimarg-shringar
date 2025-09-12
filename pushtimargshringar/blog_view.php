<?php
// blog_view.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';
if (file_exists(__DIR__ . '/app/auth.php')) require_once __DIR__ . '/app/auth.php';

// helpers
function site_url($p=''){ return (defined('SITE_URL')? rtrim(SITE_URL,'/').'/' : '/') . ltrim($p, '/'); }
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function post_image_src($img){
  $img = trim((string)$img);
  if ($img === '') return '/assets/images/posts/placeholder.jpg';
  if (preg_match('~^https?://~i', $img)) return $img;
  if (strpos($img, '/') === 0) return $img;
  return '/assets/images/posts/' . rawurlencode(basename($img));
}
function youtube_embed_url($url){
  if (!$url) return '';
  if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?.*v=|embed/|v/))([A-Za-z0-9_\-]{6,})~i', $url, $m)){
    return 'https://www.youtube.com/embed/' . $m[1];
  }
  return '';
}

/* ---------------- Resolve post by slug or id ---------------- */
$slug = trim($_GET['slug'] ?? '');
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$post = null;
$usedDb = (isset($conn) && $conn instanceof mysqli);

if ($usedDb) {
  if ($slug !== '') {
    $stmt = $conn->prepare("SELECT * FROM posts WHERE slug = ? LIMIT 1");
    if ($stmt) {
      $stmt->bind_param('s', $slug);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res && $res->num_rows) $post = $res->fetch_assoc();
      $stmt->close();
    }
  }
  if (!$post && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? LIMIT 1");
    if ($stmt) {
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res && $res->num_rows) $post = $res->fetch_assoc();
      $stmt->close();
    }
  }
}

/* ---------------- If not found -> 404 ---------------- */
if (!$post) {
  http_response_code(404);
  require_once __DIR__ . '/partials/head.php';
  require_once __DIR__ . '/partials/header.php';
  ?>
  <main class="max-w-7xl mx-auto px-4 py-12">
    <h1 class="text-2xl font-semibold text-[#8B0000]">Post not found</h1>
    <p class="text-gray-600 mt-3">The post you're looking for doesn't exist. <a href="<?= h(site_url('blog.php')) ?>" class="text-gold underline">Back to blog</a></p>
  </main>
  <?php
  require_once __DIR__ . '/partials/footer.php';
  exit;
}

/* ---------------- Safe view_count increment ---------------- */
if ($usedDb && !empty($post['id'])) {
  $hasViewCount = false;
  $dbRow = $conn->query("SELECT DATABASE() AS dbname")->fetch_assoc();
  $dbName = $dbRow['dbname'] ?? '';
  if ($dbName) {
    $safeDb = $conn->real_escape_string($dbName);
    $q = "SELECT COUNT(*) AS cnt FROM information_schema.columns
          WHERE table_schema = '{$safeDb}' AND table_name = 'posts' AND column_name = 'view_count'";
    $r = $conn->query($q);
    if ($r) {
      $hasViewCount = ((int)($r->fetch_assoc()['cnt'] ?? 0) > 0);
      $r->free();
    }
  }
  if ($hasViewCount) {
    $stmt = $conn->prepare("UPDATE posts SET view_count = COALESCE(view_count,0) + 1 WHERE id = ?");
    if ($stmt) { $stmt->bind_param('i', $post['id']); $stmt->execute(); $stmt->close(); }
  }
}

/* ---------------- Handle comment submit ---------------- */
$commentError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit'])) {
  $cname = trim($_POST['name'] ?? '');
  $cemail = trim($_POST['email'] ?? '');
  $cbody = trim($_POST['comment'] ?? '');

  if ($cname === '' || $cbody === '') {
    $commentError = "Please provide your name and comment.";
  } elseif (!$usedDb) {
    $commentError = "Comments not available (DB missing).";
  } else {
    $pid = (int)$post['id'];
    $st = $conn->prepare("INSERT INTO comments (post_id, name, email, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($st) {
      $st->bind_param('isss', $pid, $cname, $cemail, $cbody);
      $ok = $st->execute();
      $st->close();
      if ($ok) {
        header("Location: blog_view.php?slug=" . urlencode($post['slug']) . "#comments");
        exit;
      } else {
        $commentError = "Couldn't save comment (DB error).";
      }
    } else {
      $commentError = "DB prepare failed.";
    }
  }
}

/* ---------------- Fetch comments ---------------- */
$comments = [];
if ($usedDb) {
  $pid = (int)$post['id'];
  $st = $conn->prepare("SELECT name, email, comment, created_at 
                        FROM comments 
                        WHERE post_id = ? 
                        ORDER BY created_at DESC");
  if ($st) {
    $st->bind_param('i', $pid);
    $st->execute();
    $res = $st->get_result();
    if ($res) $comments = $res->fetch_all(MYSQLI_ASSOC);
    $st->close();
  }
}

/* ---------------- Related posts ---------------- */
$related = [];
if ($usedDb) {
  $st = $conn->prepare("SELECT id, title, slug, COALESCE(image,'') AS image 
                        FROM posts 
                        WHERE id <> ? 
                        ORDER BY created_at DESC 
                        LIMIT 4");
  if ($st) {
    $st->bind_param('i', $post['id']);
    $st->execute();
    $res = $st->get_result();
    if ($res) $related = $res->fetch_all(MYSQLI_ASSOC);
    $st->close();
  }
}

/* ---------------- Render ---------------- */
require_once __DIR__ . '/partials/head.php';
require_once __DIR__ . '/partials/header.php';
?>

<main class="max-w-7xl mx-auto px-4 py-10">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main article -->
    <article class="lg:col-span-2 bg-white rounded-lg shadow p-6">
      <h1 class="text-3xl font-semibold text-[#8B0000] mb-4"><?= h($post['title']) ?></h1>

      <!-- hero image / video -->
      <div class="mb-4">
        <?php
          $video_embed = youtube_embed_url($post['video_url'] ?? '');
          $img = post_image_src($post['image'] ?? '');
        ?>
        <?php if ($video_embed): ?>
          <div class="aspect-w-16 aspect-h-9 mb-4">
            <iframe src="<?= h($video_embed) ?>" title="<?= h($post['title']) ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full rounded"></iframe>
          </div>
        <?php else: ?>
          <img src="<?= h($img) ?>" alt="<?= h($post['title']) ?>" class="w-full rounded object-cover mb-4" />
        <?php endif; ?>
      </div>

      <div class="text-sm text-gray-600 mb-4">
        <span>Posted on <?= h(date('d M Y', strtotime($post['created_at'] ?? ''))) ?></span>
        <?php if (!empty($post['author'])): ?> &middot; <span>By <?= h($post['author']) ?></span><?php endif; ?>
        <?php if (!empty($post['view_count'])): ?> &middot; <span><?= (int)$post['view_count'] ?> view(s)</span><?php endif; ?>
      </div>

      <!-- Post content -->
      <div class="prose max-w-none mb-8">
        <?= $post['content'] ?? '' ?>
      </div>

      <!-- Comments -->
      <div id="comments" class="mt-8">
        <h3 class="text-xl font-semibold mb-4"><?= (int)count($comments) ?> Comment(s)</h3>

        <?php if (empty($comments)): ?>
          <p class="text-gray-600">No comments yet. Be the first to share your thoughts.</p>
        <?php else: ?>
          <div class="space-y-6">
            <?php foreach ($comments as $c): ?>
              <div class="border-b pb-4">
                <div class="flex items-start gap-4">
                  <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-xl text-gray-500">👤</div>
                  </div>
                  <div>
                    <div class="flex items-center gap-3">
                      <strong class="text-sm"><?= h($c['name'] ?? 'Guest') ?></strong>
                      <span class="text-xs text-gray-500"><?= h(date('d M Y H:i', strtotime($c['created_at'] ?? ''))) ?></span>
                    </div>
                    <div class="mt-2 text-sm text-gray-700"><?= nl2br(h($c['comment'] ?? '')) ?></div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- comment form -->
        <div class="mt-8 bg-gray-50 p-4 rounded">
          <h4 class="font-semibold mb-2">Leave a comment</h4>
          <?php if ($commentError): ?>
            <div class="mb-3 text-red-600"><?= h($commentError) ?></div>
          <?php endif; ?>
          <form method="post" action="<?= h(site_url('blog_view.php?slug=' . urlencode($post['slug']))) ?>#comments" class="space-y-3">
            <input type="hidden" name="comment_submit" value="1">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <input name="name" placeholder="Your name" required class="w-full border rounded px-3 py-2">
              <input name="email" placeholder="Email (optional)" class="w-full border rounded px-3 py-2">
            </div>
            <div>
              <textarea name="comment" rows="5" placeholder="Write your comment..." required class="w-full border rounded p-3"></textarea>
            </div>
            <div>
              <button type="submit" class="bg-[#8B0000] text-white px-4 py-2 rounded">Post comment</button>
            </div>
          </form>
        </div>
      </div>
    </article>

    <!-- Sidebar -->
    <aside class="space-y-6">
      <div class="bg-white p-4 rounded shadow">
        <h4 class="font-semibold mb-3">Share</h4>
        <?php $fullUrl = (defined('SITE_URL') ? rtrim(SITE_URL,'/') : (isset($_SERVER['HTTP_HOST'])? ('http://'.$_SERVER['HTTP_HOST']) : '')) . '/blog_view.php?slug=' . urlencode($post['slug']); ?>
        <div class="flex gap-2 items-center">
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($fullUrl) ?>" target="_blank" class="px-3 py-2 rounded border text-sm">Facebook</a>
          <a href="https://twitter.com/intent/tweet?url=<?= rawurlencode($fullUrl) ?>&text=<?= rawurlencode($post['title']) ?>" target="_blank" class="px-3 py-2 rounded border text-sm">Twitter</a>
        </div>
      </div>

      <div class="bg-white p-4 rounded shadow">
        <h4 class="font-semibold mb-3">Related Posts</h4>
        <?php if (empty($related)): ?>
          <p class="text-sm text-gray-600">No related posts.</p>
        <?php else: ?>
          <ul class="space-y-3">
            <?php foreach ($related as $r): ?>
              <?php $rUrl = site_url('blog_view.php?slug=' . urlencode($r['slug'])); ?>
              <li>
                <a href="<?= h($rUrl) ?>" class="flex items-center gap-3">
                  <img src="<?= h(post_image_src($r['image'] ?? '')) ?>" alt="<?= h($r['title']) ?>" class="w-16 h-10 object-cover rounded">
                  <span class="text-sm"><?= h($r['title']) ?></span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</main>

<?php
require_once __DIR__ . '/partials/footer.php';
?>
