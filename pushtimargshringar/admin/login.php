<?php
// admin/login.php
session_start();
require_once __DIR__ . '/../app/db.php';
if (!isset($con) && isset($conn)) $con = $conn;

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $st = $con->prepare("SELECT id, username, name, email, password FROM admins WHERE username=? LIMIT 1");
        $st->bind_param("s", $username);
        $st->execute();
        $res = $st->get_result();
        $admin = $res->fetch_assoc();
        $st->close();

        if ($admin && password_verify($password, $admin['password'])) {
            $upd = $con->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $upd->bind_param("i", $admin['id']);
            $upd->execute();
            $upd->close();

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'] ?: $admin['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Please enter both username and password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/includes/head.php'; ?>
  <title>Admin Login</title>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100">
  <div class="panel w-full max-w-md shadow-lg rounded-2xl">
    <div class="p-6">
      <h1 class="card-title text-center mb-2">Admin Login</h1>
      <p class="text-sm text-gray-500 text-center mb-6">Sign in to continue</p>

      <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded mb-4 text-sm">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-gray-600">Username</label>
          <input type="text" name="username" class="input w-full mt-1" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600">Password</label>
          <input type="password" name="password" class="input w-full mt-1" required>
        </div>
        <button type="submit" class="btn-primary w-full">Login</button>
      </form>
    </div>
  </div>
</body>
</html>
