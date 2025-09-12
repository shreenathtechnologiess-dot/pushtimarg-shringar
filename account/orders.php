<?php
// /account/orders.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';

auth_require_login(site_url('account/login.php'));
$user = auth_user();
$userId = (int)($user['id'] ?? 0);
$userMobile = (string)($user['mobile'] ?? '');

// ---- Fetch orders with item counts (DB) ----
$orders = [];
if (isset($conn) && $conn instanceof mysqli) {
  $sql = "
    SELECT  o.id, o.order_number, o.total_amount, o.payment_method, o.created_at,
            COALESCE(SUM(oi.qty), 0) AS items_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE (o.user_id = ?)
       OR (? <> '' AND o.mobile = ?)
    GROUP BY o.id
    ORDER BY o.id DESC
    LIMIT 200
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("iss", $userId, $userMobile, $userMobile);
  $stmt->execute();
  $res = $stmt->get_result();
  $orders = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();
}

include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/header.php';
?>
<section class="py-10 bg-cream">
  <div class="max-w-5xl mx-auto px-4">
    <h1 class="text-2xl font-bold text-deepgreen mb-6">My Orders</h1>

    <?php if (empty($orders)): ?>
      <div class="bg-white p-6 rounded-lg shadow">
        <p class="text-gray-600">No orders found for your account.</p>
        <p class="text-sm text-gray-500 mt-1">Tip: Make sure your account mobile matches the one used at checkout.</p>
        <a href="<?= site_url('products.php') ?>" class="inline-block mt-4 bg-deepgreen text-white px-4 py-2 rounded hover:bg-gold hover:text-darkgray">Shop Now</a>
      </div>
    <?php else: ?>
      <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="bg-cream text-left">
              <th class="px-4 py-3">Order</th>
              <th class="px-4 py-3">Placed On</th>
              <th class="px-4 py-3">Items</th>
              <th class="px-4 py-3">Payment</th>
              <th class="px-4 py-3">Total</th>
              <th class="px-4 py-3 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <?php foreach ($orders as $o): ?>
              <tr>
                <td class="px-4 py-3 font-semibold">#<?= htmlspecialchars($o['order_number']) ?></td>
                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($o['created_at']))) ?></td>
                <td class="px-4 py-3"><?= (int)$o['items_count'] ?></td>
                <td class="px-4 py-3 uppercase"><?= htmlspecialchars($o['payment_method']) ?></td>
                <td class="px-4 py-3 text-gold font-bold"><?= format_price($o['total_amount']) ?></td>
                <td class="px-4 py-3">
                  <a href="<?= site_url('account/order.php?id='.(int)$o['id']) ?>"
                     class="inline-block bg-deepgreen text-white px-3 py-1.5 rounded hover:bg-gold hover:text-darkgray float-right">
                    View
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
