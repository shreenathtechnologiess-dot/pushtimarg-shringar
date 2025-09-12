<?php
// account/newsletter.php
session_start();

// Basic site info (update if you want)
$SITE_NAME = 'Your Site Name';
$CONTACT_EMAIL = 'support@example.com';

// include user partials (head + header) if present
if (file_exists(__DIR__ . '/../partials/head.php')) include_once __DIR__ . '/../partials/head.php';
if (file_exists(__DIR__ . '/../partials/header.php')) include_once __DIR__ . '/../partials/header.php';

// helpers
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function flash_set($k, $v){ $_SESSION['flash'][$k] = $v; }
function flash_get($k){ $v = $_SESSION['flash'][$k] ?? null; if (isset($_SESSION['flash'][$k])) unset($_SESSION['flash'][$k]); return $v; }

// Try to include DB (optional)
$use_db = false;
if (file_exists(__DIR__ . '/../app/db.php')) {
    include_once __DIR__ . '/../app/db.php';
    if (isset($conn) && $conn instanceof mysqli) $use_db = true;
}

// If DB available, ensure table exists
if ($use_db) {
    $create = "
      CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        name VARCHAR(150) NULL,
        subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        confirmed TINYINT(1) NOT NULL DEFAULT 1
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    @$conn->query($create);
} else {
    // ensure storage dir exists for file fallback
    $storageDir = __DIR__ . '/../storage';
    if (!is_dir($storageDir)) @mkdir($storageDir, 0755, true);
    $storageFile = $storageDir . '/newsletter.json';
    if (!file_exists($storageFile)) @file_put_contents($storageFile, json_encode([]));
}

// detect logged-in user's email (if any common session keys used)
$prefill_email = '';
if (!empty($_SESSION['user_email'])) $prefill_email = $_SESSION['user_email'];
elseif (!empty($_SESSION['email'])) $prefill_email = $_SESSION['email'];
elseif (!empty($_SESSION['user']['email'])) $prefill_email = $_SESSION['user']['email'] ?? '';

// POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'subscribe';
    $email = trim($_POST['email'] ?? '');
    $name  = trim($_POST['name'] ?? '');

    // basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('error', 'Please enter a valid email address.');
        header("Location: newsletter.php");
        exit;
    }

    if ($action === 'subscribe') {
        if ($use_db) {
            $stmt = $conn->prepare("INSERT INTO newsletter_subscriptions (email, name, subscribed_at, confirmed) VALUES (?, ?, NOW(), 1) ON DUPLICATE KEY UPDATE name = VALUES(name), subscribed_at = NOW(), confirmed = 1");
            if ($stmt) {
                $stmt->bind_param('ss', $email, $name);
                if ($stmt->execute()) {
                    flash_set('success', 'Subscribed successfully. Thank you!');
                } else {
                    flash_set('error', 'Failed to subscribe — please try again later.');
                }
                $stmt->close();
            } else {
                flash_set('error', 'Database error: ' . $conn->error);
            }
        } else {
            // file fallback
            $data = json_decode(@file_get_contents($storageFile) ?: '[]', true);
            if (!is_array($data)) $data = [];
            $found = false;
            foreach ($data as &$row) {
                if (strcasecmp($row['email'] ?? '', $email) === 0) {
                    $row['name'] = $name;
                    $row['subscribed_at'] = date('c');
                    $row['confirmed'] = 1;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data[] = ['email' => $email, 'name' => $name, 'subscribed_at' => date('c'), 'confirmed' => 1];
            }
            if (@file_put_contents($storageFile, json_encode($data, JSON_PRETTY_PRINT))) {
                flash_set('success', 'Subscribed successfully. Thank you!');
            } else {
                flash_set('error', 'Could not save subscription (file permission issue).');
            }
        }
    }

    elseif ($action === 'unsubscribe') {
        if ($use_db) {
            $stmt = $conn->prepare("DELETE FROM newsletter_subscriptions WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param('s', $email);
                if ($stmt->execute()) {
                    flash_set('success', 'You have been unsubscribed.');
                } else {
                    flash_set('error', 'Unsubscribe failed. Please try again later.');
                }
                $stmt->close();
            } else {
                flash_set('error', 'Database error: ' . $conn->error);
            }
        } else {
            $data = json_decode(@file_get_contents($storageFile) ?: '[]', true);
            $new = [];
            $removed = false;
            foreach ($data as $row) {
                if (strcasecmp($row['email'] ?? '', $email) === 0) {
                    $removed = true;
                    continue;
                }
                $new[] = $row;
            }
            if ($removed && @file_put_contents($storageFile, json_encode($new, JSON_PRETTY_PRINT))) {
                flash_set('success', 'You have been unsubscribed.');
            } else {
                flash_set('error', 'Unsubscribe failed or you were not subscribed.');
            }
        }
    }

    header("Location: newsletter.php");
    exit;
}

// determine current subscription status for prefilled email (if any)
$current_status = null;
$current_name = '';
if ($prefill_email) {
    if ($use_db) {
        $st = $conn->prepare("SELECT email, name, confirmed FROM newsletter_subscriptions WHERE email = ? LIMIT 1");
        if ($st) {
            $st->bind_param('s', $prefill_email);
            $st->execute();
            $r = $st->get_result()->fetch_assoc();
            if ($r) {
                $current_status = (int)($r['confirmed'] ?? 0);
                $current_name = $r['name'] ?? '';
            }
            $st->close();
        }
    } else {
        $data = json_decode(@file_get_contents($storageFile) ?: '[]', true);
        foreach ($data as $row) {
            if (strcasecmp($row['email'] ?? '', $prefill_email) === 0) {
                $current_status = (int)($row['confirmed'] ?? 1);
                $current_name = $row['name'] ?? '';
                break;
            }
        }
    }
}

// helper to show success/error
$flash_success = flash_get('success');
$flash_error = flash_get('error');
?>
<main class="max-w-4xl mx-auto p-6">
  <h1 class="text-2xl font-bold mb-2">Newsletter</h1>
  <p class="text-sm text-gray-600 mb-4">Subscribe to receive offers, updates and product news from <?= e($SITE_NAME) ?>.</p>

  <?php if ($flash_success): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= e($flash_success) ?></div>
  <?php endif; ?>
  <?php if ($flash_error): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded"><?= e($flash_error) ?></div>
  <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <section class="card p-4">
      <h2 class="font-semibold mb-2">Subscribe</h2>
      <form method="post" action="newsletter.php" class="space-y-3">
        <input type="hidden" name="action" value="subscribe">
        <div>
          <label class="block text-sm mb-1">Email</label>
          <input name="email" type="email" required class="input" value="<?= e($prefill_email) ?>">
        </div>
        <div>
          <label class="block text-sm mb-1">Name (optional)</label>
          <input name="name" type="text" class="input" value="<?= e($current_name) ?>">
        </div>
        <div>
          <button type="submit" class="btn">Subscribe</button>
        </div>
      </form>
    </section>

    <section class="card p-4">
      <h2 class="font-semibold mb-2">Manage subscription</h2>
      <p class="text-sm text-gray-600 mb-3">If you already subscribed you can unsubscribe using your email.</p>
      <form method="post" action="newsletter.php" class="space-y-3">
        <input type="hidden" name="action" value="unsubscribe">
        <div>
          <label class="block text-sm mb-1">Email</label>
          <input name="email" type="email" required class="input" value="<?= e($prefill_email) ?>">
        </div>
        <div>
          <button type="submit" class="btn" style="background:#6b7280;">Unsubscribe</button>
        </div>
      </form>

      <?php if ($prefill_email): ?>
        <hr class="my-3">
        <div class="text-sm">
          <strong>Status:</strong>
          <?php if ($current_status === null): ?>
            <span class="text-gray-600">No subscription found for <?= e($prefill_email) ?></span>
          <?php elseif ($current_status): ?>
            <span class="text-green-700">Subscribed (<?= e($prefill_email) ?>)</span>
          <?php else: ?>
            <span class="text-gray-600">Not subscribed</span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <section class="mt-6 text-sm text-gray-600">
    <p>We respect your privacy. Your email will only be used to send newsletters and promotional messages. For details, see our <a href="/privacy.php">Privacy Policy</a>.</p>
    <p class="mt-2">Contact: <a href="mailto:<?= e($CONTACT_EMAIL) ?>"><?= e($CONTACT_EMAIL) ?></a></p>
  </section>
</main>

<?php
// include footer partial if present
if (file_exists(__DIR__ . '/../partials/footer.php')) include_once __DIR__ . '/../partials/footer.php';
?>
