<?php
// /account/index.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

auth_require_login();
$user = auth_user(); // should return associative array for logged-in user

// safe output
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// format datetime safely
function fmt_dt($dt){
  if (empty($dt) || $dt === '0000-00-00 00:00:00') return '-';
  $t = strtotime($dt);
  if ($t === false) return h($dt);
  return date("d M Y, h:i A", $t);
}

/**
 * Optional: fetch orders count if DB connection available
 */
$ordersCount = null;
if (isset($conn) && $conn instanceof mysqli && !empty($user['id'])) {
  $uid = (int)$user['id'];
  $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM orders WHERE user_id = ?");
  if ($stmt) {
    $stmt->bind_param('i', $uid);
    if ($stmt->execute()) {
      $res = $stmt->get_result();
      $ordersCount = (int)($res && ($row = $res->fetch_assoc()) ? $row['c'] : 0);
      if ($res) $res->free();
    }
    $stmt->close();
  }
}

include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/header.php';
?>

<section class="py-10 bg-cream">
  <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-4">
      <div>
        <a href="<?= site_url('account/profile.php') ?>" class="text-sm hover:text-gold">Edit Profile</a>
      </div>
      <div class="text-right">
        <?php if ($ordersCount !== null): ?>
          <div class="text-sm small-muted">Orders: <strong><?= (int)$ordersCount ?></strong></div>
        <?php endif; ?>
        <a href="<?= site_url('account/orders.php') ?>" class="inline-block mt-2 bg-deepgreen text-white px-3 py-2 rounded hover:bg-gold">View Orders</a>
      </div>
    </div>

    <h1 class="text-2xl font-bold text-deepgreen mb-2">My Account</h1>
    <p class="mb-6">Welcome, <strong><?= h($user['name'] ?? $user['username'] ?? 'User') ?></strong></p>

    <div class="grid md:grid-cols-2 gap-6">
      <!-- LEFT: Contact & Address -->
      <div>
        <h3 class="text-lg font-semibold mb-2">Contact</h3>
        <ul class="text-darkgray space-y-2">
          <li><strong>Email:</strong> <?= h($user['email'] ?? '-') ?></li>
          <li><strong>Mobile:</strong> <?= h($user['mobile'] ?? '-') ?></li>
          <?php if (!empty($user['username'])): ?>
            <li><strong>Username:</strong> <?= h($user['username']) ?></li>
          <?php endif; ?>
        </ul>

        <h3 class="text-lg font-semibold mt-4 mb-2">Address</h3>
        <div class="text-darkgray">
          <?php
            // Build address display from address1/address2/state/pincode
            $a1 = trim((string)($user['address1'] ?? ''));
            $a2 = trim((string)($user['address2'] ?? ''));
            $state = trim((string)($user['state'] ?? ''));
            $pincode = trim((string)($user['pincode'] ?? ''));

            $lines = [];
            if ($a1 !== '') $lines[] = $a1;
            if ($a2 !== '') $lines[] = $a2;
            $cityLine = trim(implode(', ', array_filter([$state, $pincode])));
            if ($cityLine !== '') $lines[] = $cityLine;

            if (!empty($lines)) {
              // print as paragraphs / line breaks
              echo '<div>' . nl2br(h(implode("\n", $lines))) . '</div>';
            } else {
              echo '<div class="text-gray-500">No address on file. <a href="' . site_url('account/profile.php') . '" class="hover:text-deepgreen">Add address</a></div>';
            }
          ?>
        </div>
      </div>

      <!-- RIGHT: Account Details & Preferences -->
      <div>
        <h3 class="text-lg font-semibold mb-2">Account Details</h3>
        <ul class="text-darkgray space-y-2">
          <li>
            <strong>Member since:</strong>
            <?= fmt_dt($user['created_at'] ?? $user['created'] ?? $user['registered_at'] ?? null) ?>
          </li>
          <li>
            <strong>Last login:</strong>
            <?= fmt_dt($user['last_login'] ?? $user['last_login_at'] ?? $user['last_signed_in'] ?? null) ?>
          </li>
          <?php if (!empty($user['id'])): ?>
            <li><strong>User ID:</strong> <?= (int)$user['id'] ?></li>
          <?php endif; ?>
        </ul>

        <h3 class="text-lg font-semibold mt-4 mb-2">Preferences</h3>
        <ul class="text-darkgray space-y-2">
          <?php if (array_key_exists('newsletter', $user)): ?>
            <li><strong>Newsletter:</strong> <?= (!empty($user['newsletter']) ? 'Subscribed' : 'Not subscribed') ?></li>
          <?php else: ?>
            <li class="text-gray-500">Newsletter preference not available</li>
          <?php endif; ?>

          <?php if (!empty($user['wishlist_count'])): ?>
            <li><strong>Wishlist items:</strong> <?= (int)$user['wishlist_count'] ?></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="mt-6 flex gap-3">
      <a href="<?= site_url('account/profile.php') ?>" class="bg-deepgreen text-white px-4 py-2 rounded hover:bg-gold">Edit Profile</a>
      <a href="<?= site_url('account/logout.php') ?>" class="bg-gold text-white px-4 py-2 rounded hover:bg-deepgreen">Log out</a>
      <a href="<?= site_url('account/orders.php') ?>" class="px-4 py-2 rounded border border-gray-200 hover:bg-gray-50">Order History</a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
