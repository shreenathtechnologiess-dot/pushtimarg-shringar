<?php
// admin/users.php
session_start();
require_once __DIR__ . '/../app/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Database connection not available.");
}

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function format_price_local($n){ return '₹ ' . number_format((float)$n, 0); }

// detect whether users table has status / role columns
$hasStatus = false;
$hasRole = false;
$dbNameRes = $conn->query("SELECT DATABASE() AS dbname");
$dbName = $dbNameRes ? ($dbNameRes->fetch_assoc()['dbname'] ?? null) : null;
if ($dbName) {
  $table = $conn->real_escape_string('users');
  $colStatus = $conn->real_escape_string('status');
  $colRole = $conn->real_escape_string('role');

  $q = "SELECT column_name FROM information_schema.columns
        WHERE table_schema = '{$conn->real_escape_string($dbName)}' AND table_name='{$table}'";
  $resCols = $conn->query($q);
  if ($resCols) {
    while ($r = $resCols->fetch_assoc()) {
      $col = $r['column_name'];
      if ($col === 'status') $hasStatus = true;
      if ($col === 'role') $hasRole = true;
    }
    $resCols->free();
  }
}

// Filters
$search = trim($_GET['search'] ?? '');
$role   = trim($_GET['role'] ?? '');
$status = trim($_GET['status'] ?? '');

// Stats (safe defaults)
$totalUsers = 0; $activeUsers = 0; $blockedUsers = 0; $adminUsers = 0;

// total
$r = $conn->query("SELECT COUNT(*) AS c FROM users");
if ($r) { $totalUsers = (int)($r->fetch_assoc()['c'] ?? 0); $r->free(); }

// if columns exist compute other stats; otherwise set sensible defaults
if ($hasStatus) {
  $r = $conn->query("SELECT COUNT(*) AS c FROM users WHERE status='active'");
  if ($r) { $activeUsers = (int)($r->fetch_assoc()['c'] ?? 0); $r->free(); }
  $r = $conn->query("SELECT COUNT(*) AS c FROM users WHERE status='blocked'");
  if ($r) { $blockedUsers = (int)($r->fetch_assoc()['c'] ?? 0); $r->free(); }
} else {
  // if no status column assume all active
  $activeUsers = $totalUsers;
  $blockedUsers = 0;
}

if ($hasRole) {
  $r = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='admin'");
  if ($r) { $adminUsers = (int)($r->fetch_assoc()['c'] ?? 0); $r->free(); }
} else {
  $adminUsers = 0;
}

// Build main user select: include status/role only when present
$selectBase = "u.id, u.name, u.email, u.mobile, u.created_at";
if ($hasStatus) $selectBase .= ", u.status";
if ($hasRole) $selectBase .= ", u.role";

// Add orders_count and spent via subqueries
$select = $selectBase . ", (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders_count,
                     (SELECT COALESCE(SUM(total_amount),0) FROM orders o WHERE o.user_id = u.id) AS spent";

$sql = "SELECT {$select} FROM users u WHERE 1=1";
$params = []; $types = "";

// filters
if ($search !== '') {
  $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.mobile LIKE ?)";
  $like = "%$search%";
  $params[] = $like; $params[] = $like; $params[] = $like;
  $types .= 'sss';
}
if ($hasRole && $role !== '') {
  $sql .= " AND u.role = ?";
  $params[] = $role; $types .= 's';
}
if ($hasStatus && $status !== '') {
  $sql .= " AND u.status = ?";
  $params[] = $status; $types .= 's';
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $conn->prepare($sql);
$users = [];
if ($stmt) {
  if ($params) {
    // bind params dynamically
    $bind_names[] = $types;
    for ($i=0;$i<count($params);$i++){
      $bind_name = 'bind' . $i;
      $$bind_name = $params[$i];
      $bind_names[] = &$$bind_name;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
  }
  $stmt->execute();
  $res = $stmt->get_result();
  $users = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();
} else {
  // fallback: try simple query (no filters)
  $res = $conn->query("SELECT {$select} FROM users u ORDER BY u.created_at DESC");
  if ($res) $users = $res->fetch_all(MYSQLI_ASSOC);
}

// layout includes
require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/start_layout.php';
?>

<div class="p-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-deepgreen">Users</h1>
    <div>
      <a href="user_add.php" class="btn btn-primary">+ Add User</a>
    </div>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="card p-4"><div class="text-sm text-gray-500">Total</div><div class="text-2xl font-bold"><?= (int)$totalUsers ?></div></div>
    <div class="card p-4"><div class="text-sm text-gray-500">Active</div><div class="text-2xl font-bold"><?= (int)$activeUsers ?></div></div>
    <div class="card p-4"><div class="text-sm text-gray-500">Blocked</div><div class="text-2xl font-bold"><?= (int)$blockedUsers ?></div></div>
    <div class="card p-4"><div class="text-sm text-gray-500">Admins</div><div class="text-2xl font-bold"><?= (int)$adminUsers ?></div></div>
  </div>

  <!-- Filters -->
  <form method="get" class="flex flex-wrap gap-3 items-end mb-6">
    <div>
      <label class="block text-sm">Search</label>
      <input type="text" name="search" value="<?= h($search) ?>" class="input" placeholder="Search name, email, phone">
    </div>

    <?php if ($hasRole): ?>
      <div>
        <label class="block text-sm">Role</label>
        <select name="role" class="input">
          <option value="">All</option>
          <option value="customer" <?= $role==='customer'?'selected':'' ?>>Customer</option>
          <option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option>
        </select>
      </div>
    <?php endif; ?>

    <?php if ($hasStatus): ?>
      <div>
        <label class="block text-sm">Status</label>
        <select name="status" class="input">
          <option value="">All</option>
          <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
          <option value="blocked" <?= $status==='blocked'?'selected':'' ?>>Blocked</option>
        </select>
      </div>
    <?php endif; ?>

    <div>
      <button class="btn btn-primary">Apply</button>
      <a href="users.php" class="ml-2 text-sm text-gray-600">Clear</a>
    </div>
  </form>

  <!-- Users table -->
  <div class="card overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-3 py-2">User</th>
          <th class="px-3 py-2">Phone</th>
          <?php if ($hasRole): ?><th class="px-3 py-2">Role</th><?php endif; ?>
          <?php if ($hasStatus): ?><th class="px-3 py-2">Status</th><?php endif; ?>
          <th class="px-3 py-2">Orders</th>
          <th class="px-3 py-2">Spent</th>
          <th class="px-3 py-2">Joined</th>
          <th class="px-3 py-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="<?= 6 + ($hasRole?1:0) + ($hasStatus?1:0) ?>" class="text-center py-6 text-gray-500">No users found.</td></tr>
        <?php else: foreach ($users as $u): ?>
          <?php
            $displayStatus = $hasStatus ? ($u['status'] ?? 'active') : 'active';
            $displayRole = $hasRole ? ($u['role'] ?? 'customer') : 'customer';
            $initials = '';
            $nameForInitial = trim($u['name'] ?? '');
            if ($nameForInitial !== '') {
              $parts = preg_split('/\s+/', $nameForInitial);
              if (count($parts) === 1) $initials = strtoupper(substr($parts[0],0,2));
              else $initials = strtoupper(substr($parts[0],0,1) . substr(end($parts),0,1));
            } else {
              $initials = 'U';
            }
          ?>
          <tr class="border-t">
            <td class="px-3 py-2 flex items-center gap-3">
              <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center font-bold text-gray-600"><?= h($initials) ?></div>
              <div>
                <div class="font-medium"><?= h($u['name'] ?? '') ?></div>
                <div class="text-xs text-gray-500"><?= h($u['email'] ?? '') ?></div>
              </div>
            </td>

            <td class="px-3 py-2"><?= h($u['mobile'] ?? '-') ?></td>

            <?php if ($hasRole): ?>
              <td class="px-3 py-2"><span class="badge-muted"><?= h(strtoupper($displayRole)) ?></span></td>
            <?php endif; ?>

            <?php if ($hasStatus): ?>
              <td class="px-3 py-2"><span class="<?= ($displayStatus==='active') ? 'badge-success' : 'badge-muted' ?>"><?= h(strtoupper($displayStatus)) ?></span></td>
            <?php endif; ?>

            <td class="px-3 py-2"><?= (int)($u['orders_count'] ?? 0) ?></td>
            <td class="px-3 py-2"><?= format_price_local($u['spent'] ?? 0) ?></td>
            <td class="px-3 py-2"><?= h(date('Y-m-d', strtotime($u['created_at'] ?? 'now'))) ?></td>

            <td class="px-3 py-2 space-x-2">
              <a href="user_edit.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-danger">Edit</a>

              <?php if ($hasStatus): ?>
                <?php if ($displayStatus === 'active'): ?>
                  <a href="user_toggle.php?id=<?= (int)$u['id'] ?>&status=blocked" class="btn btn-sm btn-warning">Deactivate</a>
                <?php else: ?>
                  <a href="user_toggle.php?id=<?= (int)$u['id'] ?>&status=active" class="btn btn-sm btn-success">Activate</a>
                <?php endif; ?>
              <?php endif; ?>

              <a href="user_delete.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete user?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
require_once __DIR__ . '/includes/end_layout.php';
