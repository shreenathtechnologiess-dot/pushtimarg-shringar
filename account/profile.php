<?php
// /account/profile.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';

auth_require_login(site_url('account/login.php'));
$user = auth_user();                 // ['id','name','email','mobile',...]

/* ---------------- CSRF helpers ---------------- */
if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
  }
}
if (!function_exists('csrf_check')) {
  function csrf_check($t): bool {
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t);
  }
}

/* ---------------- Defaults from DB ---------------- */
$defaults = [
  'name'     => $user['name']   ?? '',
  'email'    => $user['email']  ?? '',
  'mobile'   => $user['mobile'] ?? '',
  'address1' => $user['address1'] ?? '',
  'address2' => $user['address2'] ?? '',
  'state'    => $user['state']    ?? '',
  'pincode'  => $user['pincode']  ?? '',
  // password fields are empty by default
  'current_password' => '',
  'new_password'     => '',
  'confirm_password' => '',
];

$input  = $defaults;
$errors = [];
$success = '';

/* ---------------- Validators ---------------- */
function valid_mobile($v){ return preg_match('/^\d{10}$/', $v); }
function valid_pincode_or_empty($v){ return $v==='' || preg_match('/^\d{6}$/', $v); }

/* ---------------- Handle POST ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['_csrf'] ?? '')) {
    $errors['csrf'] = 'Session expired. Please refresh and try again.';
  }

  foreach ($defaults as $k => $v) {
    if (isset($_POST[$k])) $input[$k] = trim((string)$_POST[$k]);
  }

  // Basic validations
  if ($input['name'] === '')   $errors['name'] = 'Name is required.';
  if ($input['email'] === '' || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required.';
  if (!valid_mobile($input['mobile'])) $errors['mobile'] = 'Enter a valid 10 digit mobile.';
  if (!valid_pincode_or_empty($input['pincode'])) $errors['pincode'] = 'Enter a valid 6 digit pincode or leave empty.';

  // Password change (optional)
  $wantsPasswordChange = ($input['new_password'] !== '' || $input['confirm_password'] !== '' || $input['current_password'] !== '');
  if ($wantsPasswordChange) {
    if ($input['current_password'] === '') $errors['current_password'] = 'Current password is required.';
    if (strlen($input['new_password']) < 6) $errors['new_password'] = 'New password must be at least 6 characters.';
    if ($input['new_password'] !== $input['confirm_password']) $errors['confirm_password'] = 'Passwords do not match.';
  }

  if (empty($errors)) {
    if (!isset($conn) || !($conn instanceof mysqli)) {
      $errors['db'] = 'Database connection not available.';
    } else {
      $uid = (int)$user['id'];

      // Email uniqueness (if changed)
      if (strcasecmp($input['email'], (string)$user['email']) !== 0) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND id<>? LIMIT 1");
        $stmt->bind_param("si", $input['email'], $uid);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) $errors['email'] = 'This email is already in use.';
        $stmt->close();
      }

      // If password change requested, verify current password
      if (empty($errors) && $wantsPasswordChange) {
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id=?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->bind_result($hash);
        $stmt->fetch();
        $stmt->close();

        if (!$hash || !password_verify($input['current_password'], $hash)) {
          $errors['current_password'] = 'Current password is incorrect.';
        }
      }

      if (empty($errors)) {
        // Build update query
        if ($wantsPasswordChange) {
          $newHash = password_hash($input['new_password'], PASSWORD_DEFAULT);
          $sql = "UPDATE users SET name=?, email=?, mobile=?, address1=?, address2=?, state=?, pincode=?, password_hash=? WHERE id=?";
        } else {
          $sql = "UPDATE users SET name=?, email=?, mobile=?, address1=?, address2=?, state=?, pincode=? WHERE id=?";
        }

        if ($wantsPasswordChange) {
          $stmt = $conn->prepare($sql);
          $stmt->bind_param(
            "ssssssssi",
            $input['name'], $input['email'], $input['mobile'],
            $input['address1'], $input['address2'], $input['state'], $input['pincode'],
            $newHash, $uid
          );
        } else {
          $stmt = $conn->prepare($sql);
          $stmt->bind_param(
            "sssssssi",
            $input['name'], $input['email'], $input['mobile'],
            $input['address1'], $input['address2'], $input['state'], $input['pincode'],
            $uid
          );
        }

        if ($stmt && $stmt->execute()) {
          $stmt->close();

          // Refresh session (so header shows updated name/mobile)
          if (function_exists('auth_refresh')) {
            auth_refresh($uid, $conn); // see helper below
          } else {
            // minimal refresh
            $_SESSION['user']['name']   = $input['name'];
            $_SESSION['user']['email']  = $input['email'];
            $_SESSION['user']['mobile'] = $input['mobile'];
            $_SESSION['user']['address1'] = $input['address1'];
            $_SESSION['user']['address2'] = $input['address2'];
            $_SESSION['user']['state']    = $input['state'];
            $_SESSION['user']['pincode']  = $input['pincode'];
          }

          $success = 'Profile updated successfully.';
        } else {
          $errors['db'] = 'Failed to update profile.';
        }
      }
    }
  }
}

include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/header.php';
?>
<section class="py-10 bg-cream">
  <div class="max-w-3xl mx-auto px-4">
    <div class="bg-white p-6 rounded-lg shadow">
      <h1 class="text-2xl font-bold text-deepgreen mb-4">Edit Profile</h1>

      <?php if ($success): ?>
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded">
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded">
          Please fix the highlighted fields.
          <?php if(!empty($errors['csrf'])): ?><div><?= htmlspecialchars($errors['csrf']) ?></div><?php endif; ?>
          <?php if(!empty($errors['db'])): ?><div><?= htmlspecialchars($errors['db']) ?></div><?php endif; ?>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-4" novalidate>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()); ?>">

        <div>
          <label class="block text-sm mb-1">Full Name</label>
          <input name="name" value="<?= htmlspecialchars($input['name']) ?>" class="w-full border rounded px-3 py-2 <?= isset($errors['name'])?'border-red-500':''; ?>">
          <?php if(isset($errors['name'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['name']) ?></p><?php endif; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm mb-1">Email</label>
            <input name="email" type="email" value="<?= htmlspecialchars($input['email']) ?>" class="w-full border rounded px-3 py-2 <?= isset($errors['email'])?'border-red-500':''; ?>">
            <?php if(isset($errors['email'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['email']) ?></p><?php endif; ?>
          </div>
          <div>
            <label class="block text-sm mb-1">Mobile (10 digits)</label>
            <input name="mobile" value="<?= htmlspecialchars($input['mobile']) ?>" maxlength="10" pattern="\d{10}" class="w-full border rounded px-3 py-2 <?= isset($errors['mobile'])?'border-red-500':''; ?>">
            <?php if(isset($errors['mobile'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['mobile']) ?></p><?php endif; ?>
          </div>
        </div>

        <div>
          <label class="block text-sm mb-1">Address Line 1</label>
          <input name="address1" value="<?= htmlspecialchars($input['address1']) ?>" class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm mb-1">Address Line 2</label>
          <input name="address2" value="<?= htmlspecialchars($input['address2']) ?>" class="w-full border rounded px-3 py-2">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm mb-1">State</label>
            <input name="state" value="<?= htmlspecialchars($input['state']) ?>" class="w-full border rounded px-3 py-2">
            <?php if(isset($errors['state'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['state']) ?></p><?php endif; ?>
          </div>
          <div>
            <label class="block text-sm mb-1">Pincode</label>
            <input name="pincode" value="<?= htmlspecialchars($input['pincode']) ?>" maxlength="6" pattern="\d{6}" class="w-full border rounded px-3 py-2 <?= isset($errors['pincode'])?'border-red-500':''; ?>">
            <?php if(isset($errors['pincode'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['pincode']) ?></p><?php endif; ?>
          </div>
        </div>

        <details class="mt-4">
          <summary class="cursor-pointer select-none font-semibold text-deepgreen">Change Password (optional)</summary>
          <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm mb-1">Current Password</label>
              <input name="current_password" type="password" class="w-full border rounded px-3 py-2 <?= isset($errors['current_password'])?'border-red-500':''; ?>">
              <?php if(isset($errors['current_password'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['current_password']) ?></p><?php endif; ?>
            </div>
            <div>
              <label class="block text-sm mb-1">New Password</label>
              <input name="new_password" type="password" class="w-full border rounded px-3 py-2 <?= isset($errors['new_password'])?'border-red-500':''; ?>">
              <?php if(isset($errors['new_password'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['new_password']) ?></p><?php endif; ?>
            </div>
            <div>
              <label class="block text-sm mb-1">Confirm Password</label>
              <input name="confirm_password" type="password" class="w-full border rounded px-3 py-2 <?= isset($errors['confirm_password'])?'border-red-500':''; ?>">
              <?php if(isset($errors['confirm_password'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['confirm_password']) ?></p><?php endif; ?>
            </div>
          </div>
        </details>

        <div class="pt-2">
          <button class="bg-deepgreen text-white px-4 py-2 rounded hover:bg-gold hover:text-darkgray">Save Changes</button>
          <a href="<?= site_url('account/index.php') ?>" class="ml-3 underline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
