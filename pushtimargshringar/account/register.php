<?php
// /account/register.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

$errors = [];
$input = [
  'name'     => trim($_POST['name'] ?? ''),
  'email'    => trim($_POST['email'] ?? ''),
  'mobile'   => trim($_POST['mobile'] ?? ''),
  'password' => $_POST['password'] ?? '',
  'confirm'  => $_POST['confirm'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($input['name'] === '')  $errors['name'] = 'Name is required.';
  if ($input['email'] === '' || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required.';
  if ($input['mobile'] !== '' && !preg_match('/^\d{10}$/', $input['mobile'])) $errors['mobile'] = 'Enter 10-digit mobile or leave blank.';
  if ($input['password'] === '' || strlen($input['password']) < 6) $errors['password'] = 'Password must be at least 6 characters.';
  if ($input['confirm'] !== $input['password']) $errors['confirm'] = 'Passwords do not match.';

  if (empty($errors)) {
    if (!isset($conn) || !($conn instanceof mysqli)) {
      $errors['db'] = 'Database connection not available.';
    } else {
      $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
      $stmt->bind_param("s", $input['email']);
      $stmt->execute(); $stmt->store_result();
      if ($stmt->num_rows > 0) $errors['email'] = 'Email already registered.';
      $stmt->close();

      if (empty($errors)) {
        $hash = password_hash($input['password'], PASSWORD_DEFAULT);

          $stmt = $conn->prepare("INSERT INTO users (name, email, mobile, password) VALUES (?, ?, ?, ?)");
          $stmt->bind_param("ssss", $input['name'], $input['email'], $input['mobile'], $hash);
        if ($stmt->execute()) {
          $userId = $stmt->insert_id; $stmt->close();
          $stmt = $conn->prepare("SELECT id,name,email,mobile FROM users WHERE id=?");
          $stmt->bind_param("i", $userId); $stmt->execute();
          $res = $stmt->get_result(); $user = $res->fetch_assoc(); $stmt->close();
          auth_login($user);
          header("Location: profile.php");
          exit;
        } else {
          $errors['db'] = 'Could not create account. Try again.';
        }
      }
    }
  }
}

include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/header.php';
?>
<section class="py-10 bg-cream">
  <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow">
    <h1 class="text-2xl font-bold text-deepgreen mb-4">Create Account</h1>
    <?php if (!empty($errors['db'])): ?><p class="text-red-600 mb-3"><?= htmlspecialchars($errors['db']) ?></p><?php endif; ?>
    <form method="post" class="space-y-4" novalidate>
      <div>
        <label class="block text-sm mb-1">Full Name</label>
        <input name="name" value="<?= htmlspecialchars($input['name']) ?>" class="w-full border rounded px-3 py-2" required>
        <?php if(!empty($errors['name'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['name']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="block text-sm mb-1">Email</label>
        <input name="email" type="email" value="<?= htmlspecialchars($input['email']) ?>" class="w-full border rounded px-3 py-2" required>
        <?php if(!empty($errors['email'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['email']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="block text-sm mb-1">Mobile (optional)</label>
        <input name="mobile" inputmode="numeric" pattern="\d{10}" maxlength="10" value="<?= htmlspecialchars($input['mobile']) ?>" class="w-full border rounded px-3 py-2">
        <?php if(!empty($errors['mobile'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['mobile']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="block text-sm mb-1">Password</label>
        <input name="password" type="password" class="w-full border rounded px-3 py-2" required>
        <?php if(!empty($errors['password'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['password']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="block text-sm mb-1">Confirm Password</label>
        <input name="confirm" type="password" class="w-full border rounded px-3 py-2" required>
        <?php if(!empty($errors['confirm'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['confirm']) ?></p><?php endif; ?>
      </div>
      <button class="w-full bg-deepgreen text-white py-2 rounded hover:bg-gold hover:text-darkgray">Create Account</button>
    </form>
    <p class="text-sm mt-4">Already have an account? <a href="/account/login.php" class="text-gold underline">Log in</a></p>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
