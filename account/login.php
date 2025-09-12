<?php
// /account/login.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';

$errors = [];
$input = [
  'email'    => trim($_POST['email'] ?? ''),
  'password' => $_POST['password'] ?? '',
];

$next = $_GET['next'] ?? '';

/* Safe redirect builder: prefer ?next when it points inside our app */
function next_or_default(string $next): string {
  // allow only paths inside our base folder
  $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
  if ($next && str_starts_with($next, $base.'/')) {
    return $next;
  }
  // fallback to account dashboard
  return function_exists('site_url') ? site_url('account/index.php') : '/account/index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($input['email'] === '' || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required.';
  if ($input['password'] === '') $errors['password'] = 'Password is required.';

  if (empty($errors)) {
    if (!isset($conn) || !($conn instanceof mysqli)) {
      $errors['db'] = 'Database connection not available.';
    } else {
      $stmt = $conn->prepare("SELECT id,name,email,mobile,password_hash FROM users WHERE email=? LIMIT 1");
      if ($stmt) {
        $stmt->bind_param("s", $input['email']);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res?->fetch_assoc();
        $stmt->close();
      }

      if (!empty($user) && password_verify($input['password'], $user['password_hash'])) {
        auth_login($user);
        header("Location: " . next_or_default($next));
        exit;
      } else {
        $errors['password'] = 'Invalid email or password.';
      }
    }
  }
}

include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/header.php';
?>
<section class="py-10 bg-cream">
  <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow">
    <h1 class="text-2xl font-bold text-deepgreen mb-4">Log in</h1>
    <?php if (!empty($errors['db'])): ?><p class="text-red-600 mb-3"><?= htmlspecialchars($errors['db']) ?></p><?php endif; ?>
    <form method="post" class="space-y-4" novalidate>
      <div>
        <label class="block text-sm mb-1">Email</label>
        <input name="email" type="email" value="<?= htmlspecialchars($input['email']) ?>" class="w-full border rounded px-3 py-2" required>
        <?php if(!empty($errors['email'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['email']) ?></p><?php endif; ?>
      </div>
      <div>
        <label class="block text-sm mb-1">Password</label>
        <input name="password" type="password" class="w-full border rounded px-3 py-2" required>
        <?php if(!empty($errors['password'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['password']) ?></p><?php endif; ?>
      </div>
      <button class="w-full bg-deepgreen text-white py-2 rounded hover:bg-gold hover:text-darkgray">Login</button>
    </form>
    <p class="text-sm mt-4">New here? 
      <a href="<?= site_url('account/register.php') ?>" class="text-gold underline">Create an account</a>
    </p>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
