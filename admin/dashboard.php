<?php
// admin/dashboard.php
// Dashboard — show counts from DB and recent orders.
// Place this file at admin/dashboard.php

// optional bootstrap (session + config + admin auth helpers)
// require_once __DIR__ . '/_init.php';

// If you don't use _init, ensure config is included so $conn is available:
require_once __DIR__ . '/../app/config.php'; // <-- makes $conn, site_url(), format_price() available (adjust path if needed)

/* include layout parts */
require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/start_layout.php';

/* ----------- Prepare counts from DB ----------- */
$counts = [
  'products' => 0,
  'categories' => 0,
  'orders' => 0,
  'delivered' => 0,
  'users' => 0,
  'active_users' => 0,
];

$categoryLabels = [];
$categoryCounts = [];

$ordersPending = 0;
$ordersShipped = 0;

if (isset($conn) && $conn instanceof mysqli) {
  // products
  $r = $conn->query("SELECT COUNT(*) AS c FROM products");
  if ($r) { $counts['products'] = (int) ($r->fetch_assoc()['c'] ?? 0); }

  // categories: prefer dedicated categories table, fallback to distinct product.category
  $r = $conn->query("SELECT COUNT(*) AS c FROM categories");
  if ($r && $r->num_rows) {
    $counts['categories'] = (int) ($r->fetch_assoc()['c'] ?? 0);
  } else {
    $r = $conn->query("SELECT COUNT(DISTINCT(COALESCE(category,''))) AS c FROM products");
    if ($r) { $counts['categories'] = (int) ($r->fetch_assoc()['c'] ?? 0); }
  }

  // orders total
  $r = $conn->query("SELECT COUNT(*) AS c FROM orders");
  if ($r) { $counts['orders'] = (int) ($r->fetch_assoc()['c'] ?? 0); }

  // delivered / shipped (try common statuses)
  $r = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status IN ('delivered','shipped','completed')");
  if ($r) { $counts['delivered'] = (int) ($r->fetch_assoc()['c'] ?? 0); }

  // users total
  $r = $conn->query("SELECT COUNT(*) AS c FROM users");
  if ($r) { $counts['users'] = (int) ($r->fetch_assoc()['c'] ?? 0); }

  // active users (example: last_login within 30 days). If no last_login column, fallback to total users.
  $r = $conn->query("SELECT COUNT(*) AS c FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
  if ($r) {
    $counts['active_users'] = (int) ($r->fetch_assoc()['c'] ?? 0);
    if ($counts['active_users'] === 0) {
      // fallback to total users
      $counts['active_users'] = $counts['users'];
    }
  } else {
    $counts['active_users'] = $counts['users'];
  }

  // orders status for donut (pending vs shipped)
  $r = $conn->query("
    SELECT
      SUM(CASE WHEN status IN ('pending','new','processing') THEN 1 ELSE 0 END) AS pending,
      SUM(CASE WHEN status IN ('shipped','delivered','completed') THEN 1 ELSE 0 END) AS shipped
    FROM orders
  ");
  if ($r) {
    $row = $r->fetch_assoc();
    $ordersPending = (int) ($row['pending'] ?? 0);
    $ordersShipped = (int) ($row['shipped'] ?? 0);
    // if both zero but there are orders, mark all as pending
    if ($ordersPending === 0 && $ordersShipped === 0 && $counts['orders'] > 0) {
      $ordersPending = $counts['orders'];
    }
  }

  // products by category for bar chart
  $r = $conn->query("
    SELECT COALESCE(category, 'Uncategorized') AS category, COUNT(*) AS cnt
    FROM products
    GROUP BY COALESCE(category, 'Uncategorized')
    ORDER BY cnt DESC
    LIMIT 12
  ");
  if ($r && $r->num_rows) {
    while ($row = $r->fetch_assoc()) {
      $categoryLabels[] = $row['category'];
      $categoryCounts[] = (int)$row['cnt'];
    }
  }
} else {
  // If DB not available, leave counts 0 (or you can set demo values)
}

/* ----------------- Render dashboard HTML ----------------- */
?>

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
</div>

<!-- Panels: charts + categories + quick actions -->
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
        if (isset($conn) && $conn instanceof mysqli) {
          $sql = "SELECT id, order_number, first_name, last_name, total_amount, payment_method, created_at, COALESCE(status,'pending') AS status
                  FROM orders
                  ORDER BY id DESC
                  LIMIT 10";
          $res = $conn->query($sql);
          if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
              $orderNo = htmlspecialchars($row['order_number']);
              $customer = htmlspecialchars(trim($row['first_name'].' '.$row['last_name']));
              $total = function_exists('format_price') ? format_price((int)$row['total_amount']) : '₹ '.number_format((int)$row['total_amount']);
              $payment = htmlspecialchars(strtoupper($row['payment_method']));
              $date = htmlspecialchars(date('d M Y H:i', strtotime($row['created_at'])));
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

<!-- small summary footer -->
<div class="flex gap-4 items-center small-muted">
  <div>Showing latest orders. Click "View Orders" for full list.</div>
</div>

<!-- Charts script -->
<script>
  // Orders donut (from PHP counts)
  const donutCtx = document.getElementById('ordersDonut').getContext('2d');
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
    options: {
      cutout: '70%',
      plugins: { legend: { display: false } }
    }
  });

  // Category bar (from PHP arrays)
  const barCtx = document.getElementById('categoryBar').getContext('2d');
  new Chart(barCtx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($categoryLabels) ?>,
      datasets: [{ label: 'Products', data: <?= json_encode($categoryCounts) ?>, backgroundColor: 'rgba(96,165,250,0.6)', borderWidth: 0 }]
    },
    options: {
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
      plugins: { legend: { display: false } }
    }
  });
</script>

<?php
/* include end layout to close main and html */
require_once __DIR__ . '/includes/end_layout.php';
