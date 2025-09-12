<?php
// admin/user_edit.php
session_start();
require_once __DIR__ . '/../app/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Database connection not available.");
}

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function flash($k,$m){ $_SESSION[$k]=$m; }

// allowed status/roles (adjust if your system uses other values)
$statuses = ['active'=>'Active','blocked'=>'Blocked'];
$roles = ['customer'=>'Customer','admin'=>'Admin'];

// get id
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  flash('flash_error','Invalid user id.');
  header("Location: users.php");
  exit;
}

// fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i',$id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$user) {
  flash('flash_error','User not found.');
  header("Location: users.php");
  exit;
}

// fetch small stats: orders count, spent, last_login
$ordersCount = 0; $totalSpent = 0;
$r = $conn->query("SELECT COUNT(*) AS c, COALESCE(SUM(total_amount),0) AS s FROM orders WHERE user_id = " . (int)$id);
if ($r) {
  $row = $r->fetch_assoc();
  $ordersCount = (int)($row['c'] ?? 0);
  $totalSpent = (float)($row['s'] ?? 0);
  $r->free();
}
$lastLogin = $user['last_login'] ?? null;

// handle POST (save)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // basic inputs
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $mobile = trim($_POST['mobile'] ?? '');
  $address1 = trim($_POST['address1'] ?? '');
  $address2 = trim($_POST['address2'] ?? '');
  $state = trim($_POST['state'] ?? '');
  $pincode = trim($_POST['pincode'] ?? '');
  $status = trim($_POST['status'] ?? 'active');
  $role = trim($_POST['role'] ?? 'customer');
  $new_password = trim($_POST['new_password'] ?? '');

  // validate minimal
  $errors = [];
  if ($name === '') $errors[] = "Name is required.";
  if ($email === '') $errors[] = "Email is required.";
  // basic email validation
  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email is invalid.";

  // check if email already used by another user
  $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
  $stmt->bind_param('si', $email, $id);
  $stmt->execute();
  $r = $stmt->get_result();
  if ($r && $r->fetch_assoc()) $errors[] = "Email already in use by another user.";
  $stmt->close();

  if (empty($errors)) {
    // If password provided, hash it
    $password_sql_part = "";
    $bind_types = "sssssssssi";
    $bind_values = [
      $name, $email, $mobile, $address1, $address2, $state, $pincode, $status, $role, $id
    ];
    if ($new_password !== '') {
      // use password_hash
      $hash = password_hash($new_password, PASSWORD_DEFAULT);
      $password_sql_part = ", password_hash = ?";
      // we'll need to insert the hash before the id param
      // adjust types and values
      $bind_types = "ssssssssssi";
      array_splice($bind_values, 9, 0, [$hash]); // insert before id
    }

    $sql = "UPDATE users SET name = ?, email = ?, mobile = ?, address1 = ?, address2 = ?, state = ?, pincode = ?, status = ?, role = ? {$password_sql_part} WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
      $errors[] = "Prepare failed: " . $conn->error;
    } else {
      // build call_user_func_array-friendly binding
      $bind_names = [];
      $bind_names[] = $bind_types;
      for ($i=0;$i<count($bind_values);$i++){
        $bind_name = 'b'.$i;
        $$bind_name = $bind_values[$i];
        $bind_names[] = &$$bind_name;
      }
      call_user_func_array([$stmt,'bind_param'],$bind_names);
      $ok = $stmt->execute();
      if ($ok) {
        flash('flash_success','User updated successfully.');
      } else {
        $errors[] = "Update failed: " . $stmt->error;
      }
      $stmt->close();
    }
  }

  if (!empty($errors)) {
    $_SESSION['flash_error'] = implode(' ', $errors);
  }

  // reload to show messages and updated data
  header("Location: user_edit.php?id={$id}");
  exit;
}

// layout includes
require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/start_layout.php';
?>

<style>
/* minor tweaks to preserve current visual while making form nicer */
.edit-wrap { max-width:900px; margin:0 auto; display:grid; grid-template-columns: 1fr 360px; gap:24px; }
@media (max-width:1000px) { .edit-wrap { grid-template-columns: 1fr; } }
.card-stats { background:#fff; padding:16px; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,.04); }
.small-muted { color:#6b7280; font-size:0.95rem; }
.form-row { display:flex; gap:12px; }
</style>

<div class="p-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-deepgreen">Edit User</h1>
    <div>
      <a href="users.php" class="btn btn-ghost">← Back to list</a>
    </div>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded"><?= h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <div class="edit-wrap">
    <!-- MAIN FORM -->
    <div class="card p-4">
      <form method="post" class="space-y-4">
        <div>
          <label class="block text-sm font-medium">Name</label>
          <input type="text" name="name" class="input" value="<?= h($user['name'] ?? '') ?>" required>
        </div>

        <div class="form-row">
          <div style="flex:1">
            <label class="block text-sm font-medium">Email</label>
            <input type="email" name="email" class="input" value="<?= h($user['email'] ?? '') ?>" required>
          </div>
          <div style="width:220px">
            <label class="block text-sm font-medium">Phone</label>
            <input type="text" name="mobile" class="input" value="<?= h($user['mobile'] ?? '') ?>">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium">Address line 1</label>
          <input type="text" name="address1" class="input" value="<?= h($user['address1'] ?? '') ?>">
        </div>
        <div>
          <label class="block text-sm font-medium">Address line 2</label>
          <input type="text" name="address2" class="input" value="<?= h($user['address2'] ?? '') ?>">
        </div>

        <div class="form-row">
          <div style="flex:1">
            <label class="block text-sm font-medium">State</label>
            <input type="text" name="state" class="input" value="<?= h($user['state'] ?? '') ?>">
          </div>
          <div style="width:160px">
            <label class="block text-sm font-medium">Pincode</label>
            <input type="text" name="pincode" class="input" value="<?= h($user['pincode'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row">
          <div style="flex:1">
            <label class="block text-sm font-medium">Role</label>
            <select name="role" class="input">
              <?php foreach ($roles as $k=>$v): ?>
                <option value="<?= h($k) ?>" <?= (($user['role'] ?? '') === $k) ? 'selected' : '' ?>><?= h($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div style="width:200px">
            <label class="block text-sm font-medium">Status</label>
            <select name="status" class="input">
              <?php foreach ($statuses as $k=>$v): ?>
                <option value="<?= h($k) ?>" <?= (($user['status'] ?? 'active') === $k) ? 'selected' : '' ?>><?= h($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <hr>

        <div>
          <label class="block text-sm font-medium">Set new password (leave blank to keep current)</label>
          <input type="password" name="new_password" class="input" placeholder="New password (optional)">
        </div>

        <div class="flex gap-3 mt-2">
          <button class="btn btn-primary">Save Changes</button>
          <a href="users.php" class="btn btn-ghost">Cancel</a>
          <form method="post" action="user_delete.php?id=<?= (int)$id ?>" style="display:inline;">
            <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this user? This action is permanent.')">Delete</button>
          </form>
        </div>
      </form>
    </div>

    <!-- RIGHT: stats & quick actions -->
    <div>
      <div class="card-stats mb-4">
        <h3 class="font-semibold mb-2">Account summary</h3>
        <div class="small-muted">Orders</div>
        <div class="text-xl font-bold mb-2"><?= (int)$ordersCount ?></div>

        <div class="small-muted">Total spent</div>
        <div class="text-xl font-bold mb-2">₹ <?= number_format($totalSpent,2) ?></div>

        <div class="small-muted">Last login</div>
        <div class="mb-3"><?= $lastLogin ? h($lastLogin) : '—' ?></div>

        <hr>

        <div class="mt-3">
          <a class="btn btn-ghost w-full mb-2" href="orders.php?user=<?= (int)$id ?>">View orders for this user</a>

          <?php if (($user['status'] ?? 'active') === 'active'): ?>
            <a class="btn btn-warning w-full" href="user_toggle.php?id=<?= (int)$id ?>&status=blocked" onclick="return confirm('Deactivate this user?')">Deactivate user</a>
          <?php else: ?>
            <a class="btn btn-success w-full" href="user_toggle.php?id=<?= (int)$id ?>&status=active">Activate user</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="card-stats">
        <h3 class="font-semibold mb-2">Security</h3>
        <div class="small-muted">Password</div>
        <div class="mb-2">Set a new password above if you need to reset the user's password.</div>

        <div class="small-muted">Email</div>
        <div class="mb-2"><?= h($user['email'] ?? '') ?></div>

        <div class="small-muted">Phone</div>
        <div class="mb-2"><?= h($user['mobile'] ?? '') ?></div>
      </div>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/includes/end_layout.php';
