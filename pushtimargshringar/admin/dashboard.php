<?php
// admin/dashboard.php (no-login version)
// NOTE: This removes any login/auth check and opens dashboard directly.
// Use only for local development/testing.

if (session_status() === PHP_SESSION_NONE) {
    // We don't rely on sessions for auth in this version, but start session anyway
    // to keep compatibility with any parts that might expect it.
    @session_start();
}

require_once __DIR__ . '/../app/db.php';
if (!isset($con) && isset($conn)) $con = $conn;

// Try to load a default admin row (id = 1) if available
$admin = null;
if (isset($con) && $con instanceof mysqli) {
    $st = $con->prepare("SELECT id, username, name, email, last_login, created_at FROM admins WHERE id = 1 LIMIT 1");
    if ($st) {
        $st->execute();
        $admin = $st->get_result()->fetch_assoc();
        $st->close();
    }
}

// Include your layout (keeps existing look)
if (file_exists(__DIR__ . '/includes/head.php')) include __DIR__ . '/includes/head.php';
if (file_exists(__DIR__ . '/includes/start_layout.php')) include __DIR__ . '/includes/start_layout.php';

/* ----------- Prepare counts from DB (same logic) ----------- */
$counts = [
  'products' => 0,
  'categories' => 0,
  'orders' => 0,
  'delivered' => 0,
  'users' => 0,
  'active_users' => 0,
  'posts' => 0, // new: blog posts count
];

$categoryLabels = [];
$categoryCounts = [];
$ordersPending = 0;
$ordersShipped = 0;

if (isset($con) && $con instanceof mysqli) {
  $r = $con->query("SELECT COUNT(*) AS c FROM products");
  if ($r) { $counts['products'] = (int) ($r->fetch_assoc()['c'] ?? 0); }

  $r = $con->query("SELECT COUNT(*) AS c FROM categories");
  if ($r && $r->num_rows) {
    $counts['categories'] = (int) ($r->fetch_assoc()['c'] ?? 0);
  } else {
    $r = $con->query("SELECT COUNT(DISTINCT(COALESCE(category,''))) AS c FROM products");
    if ($r) { $counts['categories'] = (int) ($r->fetch_assoc()['c'] ?? 0); }
  }

  $r = $con->query("SELECT COUNT(*) AS c FROM orders");
  if ($r) { $counts['orders'] = (int) ($r->fetch_assoc()['c'] ?? 0); }

  $r = $con->query("SELECT COUNT(*) AS c FROM orders WHERE status IN ('delivered','shipped','completed')");
  if ($r) { $counts['delivered'] = (int) ($r->fetch_assoc()['c'] ?? 0); }

  $r = $con->query("SELECT COUNT(*) AS c FROM users");
  if ($r) { $counts['users'] = (int) ($r->fetch_assoc()['c'] ?? 0); }

  $r = $con->query("SELECT COUNT(*) AS c FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
  if ($r) {
    $counts['active_users'] = (int) ($r->fetch_assoc()['c'] ?? 0);
    if ($counts['active_users'] === 0) $counts['active_users'] = $counts['users'];
  } else {
    $counts['active_users'] = $counts['users'];
  }

  $r = $con->query("
    SELECT
      SUM(CASE WHEN LOWER(status) IN ('pending','new','processing') THEN 1 ELSE 0 END) AS pending,
      SUM(CASE WHEN LOWER(status) IN ('shipped','delivered','completed') THEN 1 ELSE 0 END) AS shipped
    FROM orders
  ");
  if ($r) {
    $row = $r->fetch_assoc();
    $ordersPending = (int) ($row['pending'] ?? 0);
    $ordersShipped = (int) ($row['shipped'] ?? 0);
    if ($ordersPending === 0 && $ordersShipped === 0 && $counts['orders'] > 0) {
      $ordersPending = $counts['orders'];
    }
  }

  $r = $con->query("
    SELECT COALESCE(p.category, 'Uncategorized') AS category, COUNT(*) AS cnt
    FROM products p
    GROUP BY COALESCE(p.category, 'Uncategorized')
    ORDER BY cnt DESC
    LIMIT 12
  ");
  if ($r && $r->num_rows) {
    while ($row = $r->fetch_assoc()) {
      $categoryLabels[] = $row['category'];
      $categoryCounts[] = (int)$row['cnt'];
    }
  }

  // NEW: try to get blog posts count if table exists
  $possiblePostTables = ['posts', 'blog_posts', 'articles'];
  foreach ($possiblePostTables as $tbl) {
    $safeTbl = $con->real_escape_string($tbl);
    $check = $con->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$safeTbl}' LIMIT 1");
    if ($check && $check->num_rows) {
      $r = $con->query("SELECT COUNT(*) AS c FROM `{$safeTbl}`");
      if ($r) {
        $counts['posts'] = (int) ($r->fetch_assoc()['c'] ?? 0);
        break;
      }
    }
  }
}

/* helper: price format */
function my_price($amt) {
  if (function_exists('format_price')) return format_price($amt);
  return '₹ '.number_format((float)$amt);
}
?>

<!-- Header with profile + logout area (logout link can be kept or removed) -->
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="card-title">Dashboard</h1>
    <div class="small-muted">Overview &amp; analytics</div>
  </div>
  <div class="flex items-center gap-3">
    <?php if (!empty($admin)): ?>
      <div class="text-sm small-muted">Hello, <strong><?= htmlspecialchars($admin['name'] ?? $admin['username']) ?></strong></div>
    <?php else: ?>
      <div class="text-sm small-muted">Hello, <strong>Admin</strong></div>
    <?php endif; ?>
  </div>
</div>

<!-- Profile Section (shows admin id=1 if available) -->
<div class="panel mb-6">
  <h3 class="card-title mb-3">My Profile</h3>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
    <div><b>Username:</b> <?= htmlspecialchars($admin['username'] ?? '-') ?></div>
    <div><b>Name:</b> <?= htmlspecialchars($admin['name'] ?? '-') ?></div>
    <div><b>Email:</b> <?= htmlspecialchars($admin['email'] ?? '-') ?></div>
    <div><b>Last Login:</b> <?= htmlspecialchars($admin['last_login'] ?? '-') ?></div>
    <div><b>Created At:</b> <?= htmlspecialchars($admin['created_at'] ?? '-') ?></div>
  </div>
</div>

<!-- Stats row -->
<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
  <div class="stat-card col-span-2">
    <div class="text-sm small-muted">Products</div>
    <div class="text-2xl font-bold"><?= (int)$counts['products'] ?></div>
  </div>
  <div class="stat-card">
    <div class="text-sm small-muted">Categories</div>
    <div class="text-2xl font-bold"><?= (int)$counts['categories'] ?></div>
  </div>
  <div class="stat-card">
    <div class="text-sm small-muted">Orders</div>
    <div class="text-2xl font-bold"><?= (int)$counts['orders'] ?></div>
  </div>
  <div class="stat-card">
    <div class="text-sm small-muted">Delivered</div>
    <div class="text-2xl font-bold"><?= (int)$counts['delivered'] ?></div>
  </div>
  <div class="stat-card">
    <div class="text-sm small-muted">Active Users</div>
    <div class="text-2xl font-bold"><?= (int)$counts['active_users'] ?></div>
  </div>

  <!-- NEW: Posts stat card -->
  <div class="stat-card">
    <div class="text-sm small-muted">Posts</div>
    <div class="text-2xl font-bold"><?= (int)$counts['posts'] ?></div>
  </div>
</div>

<!-- Rest of dashboard (charts + recent orders) - keep same as original -->
<!-- Orders Status chart -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
  <div class="panel">
    <h3 class="card-title mb-3">Orders Status</h3>
    <canvas id="ordersDonut" height="240"></canvas>
    <div class="flex gap-4 mt-3">
      <div class="flex items-center gap-2"><span class="w-3 h-3 bg-blue-400 inline-block rounded"></span> Pending</div>
      <div class="flex items-center gap-2"><span class="w-3 h-3 bg-pink-300 inline-block rounded"></span> Shipped</div>
    </div>
  </div>

  <div class="panel">
    <h3 class="card-title mb-3">Products by Category</h3>
    <canvas id="categoryBar" height="240"></canvas>
  </div>

  <div class="panel">
    <h3 class="card-title mb-3">Quick Actions</h3>
    <div class="space-y-3">
      <a href="products.php" class="w-full inline-block text-center btn-primary">Manage Products</a>
      <a href="categories.php" class="w-full inline-block text-center btn-ghost">Add Category</a>
      <a href="orders.php" class="w-full inline-block text-center btn-ghost">Orders Board</a>
      <a href="users.php" class="w-full inline-block text-center btn-ghost">Manage Users</a>
      <a href="reports.php" class="w-full inline-block text-center btn-ghost">Reports</a>

      <?php
      // Show blog links only if admin/blog/index.php exists to avoid broken links in some environments
      if (file_exists(__DIR__ . '/blog/index.php')): ?>
        <a href="blog/index.php" class="w-full inline-block text-center btn-primary">Manage Blog</a>
        <div class="flex gap-2">
          <a href="blog/create.php" class="w-1/2 inline-block text-center btn-ghost">Add Post</a>
          <a href="blog/index.php" class="w-1/2 inline-block text-center btn-ghost">All Posts</a>
        </div>
      <?php else: ?>
        <!-- If the blog folder doesn't exist in admin, optionally show the link anyway.
             Uncomment the next line to always show the blog link regardless of file presence. -->
        <!-- <a href="blog/index.php" class="w-full inline-block text-center btn-primary">Manage Blog</a> -->
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Recent Orders -->
<div class="panel mb-6">
  <h3 class="card-title mb-4">Recent Orders</h3>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="text-left text-gray-600">
        <tr>
          <th class="px-3 py-2">Order</th>
          <th class="px-3 py-2">Customer</th>
          <th class="px-3 py-2">Total</th>
          <th class="px-3 py-2">Payment</th>
          <th class="px-3 py-2">Date</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if (isset($con) && $con instanceof mysqli) {
          $sql = "SELECT id, order_number, first_name, last_name, total_amount, payment_method, created_at, COALESCE(status,'pending') AS status
                  FROM orders
                  ORDER BY id DESC
                  LIMIT 10";
          $res = $con->query($sql);
          if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
              $orderNo = htmlspecialchars($row['order_number'] ?: '#PS' . (int)$row['id']);
              $customer = htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
              $total = my_price($row['total_amount'] ?? 0);
              $payment = htmlspecialchars(strtoupper($row['payment_method'] ?? 'NA'));
              $date = htmlspecialchars(date('d M Y H:i', strtotime($row['created_at'] ?? '')));
              $status = htmlspecialchars(ucfirst($row['status']));
              $id = (int)$row['id'];
              echo "<tr class='border-t'>
                      <td class='px-3 py-2'>{$orderNo}</td>
                      <td class='px-3 py-2'>{$customer}</td>
                      <td class='px-3 py-2'>{$total}</td>
                      <td class='px-3 py-2'>{$payment}</td>
                      <td class='px-3 py-2'>{$date}</td>
                      <td class='px-3 py-2'>{$status}</td>
                      <td class='px-3 py-2'><a class='text-blue-600 hover:underline' href='orders/view.php?id={$id}'>View</a></td>
                    </tr>";
            }
          } else {
            echo "<tr><td class='px-3 py-4 text-center text-gray-600' colspan='7'>No orders found yet.</td></tr>";
          }
        } else {
          echo "<tr><td class='px-3 py-4 text-center text-gray-600' colspan='7'>Database connection not available.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Charts script -->
<script>
  const donutCtx = document.getElementById('ordersDonut')?.getContext('2d');
  if (donutCtx) {
    new Chart(donutCtx, {
      type: 'doughnut',
      data: {
        labels: ['Pending','Shipped'],
        datasets: [{
          data: [<?= json_encode($ordersPending) ?>, <?= json_encode($ordersShipped) ?>],
          backgroundColor: ['#60a5fa','#fb7185'],
          borderWidth: 0
        }]
      },
      options: { cutout: '70%', plugins: { legend: { display: false } } }
    });
  }

  const barCtx = document.getElementById('categoryBar')?.getContext('2d');
  if (barCtx) {
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($categoryLabels) ?>,
        datasets: [{ label: 'Products', data: <?= json_encode($categoryCounts) ?>, backgroundColor: 'rgba(96,165,250,0.6)', borderWidth: 0 }]
      },
      options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } }
    });
  }
</script>

<?php
// include end layout if available
if (file_exists(__DIR__ . '/includes/end_layout.php')) include __DIR__ . '/includes/end_layout.php';
?>
