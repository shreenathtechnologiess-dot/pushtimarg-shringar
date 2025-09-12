<?php
// /account/order.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

auth_require_login(site_url('account/login.php'));

$user = auth_user();
$userId = (int)($user['id'] ?? 0);
$userMobile = (string)($user['mobile'] ?? '');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = null; $items = [];

if ($id && isset($conn) && $conn instanceof mysqli) {
  // Allow access only if the order belongs to the logged-in user (via user_id OR mobile)
  $sql = "
    SELECT id, order_number, user_id, first_name, last_name, address1, address2,
           pincode, state, mobile, payment_method, total_amount, created_at,
           COALESCE(status, 'Processing') AS status,
           COALESCE(upi_id, '') AS upi_id
    FROM orders
    WHERE id = ?
      AND (user_id = ? OR (? <> '' AND mobile = ?))
    LIMIT 1
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("iiss", $id, $userId, $userMobile, $userMobile);
  $stmt->execute();
  $res = $stmt->get_result();
  $order = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  if ($order) {
    $stmt2 = $conn->prepare("SELECT slug, name, price, qty, subtotal, img FROM order_items WHERE order_id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $items = $res2 ? $res2->fetch_all(MYSQLI_ASSOC) : [];
    $stmt2->close();
  }
}

include __DIR__ . '/../partials/head.php';
include __DIR__ . '/../partials/header.php';
?>
<section class="py-10 bg-cream">
  <div class="max-w-5xl mx-auto px-4">
    <?php if (!$order): ?>
      <div class="bg-white p-6 rounded-lg shadow">
        <p class="text-gray-600">Order not found.</p>
        <a href="<?= site_url('account/orders.php') ?>" class="inline-block mt-4 underline">← Back to My Orders</a>
      </div>
    <?php else: ?>
      <div class="bg-white p-6 rounded-lg shadow">
        <!-- Header -->
        <div class="flex items-center justify-between mb-2">
          <h1 class="text-2xl font-bold text-deepgreen">Order #<?= htmlspecialchars($order['order_number']) ?></h1>
          <div class="text-gold font-bold"><?= format_price($order['total_amount']) ?></div>
        </div>
        <p class="text-sm text-gray-500">
          Placed on: <?= htmlspecialchars(date('d M Y, h:i A', strtotime($order['created_at']))) ?> •
          Payment: <span class="uppercase"><?= htmlspecialchars($order['payment_method']) ?></span> •
          Status: <?= htmlspecialchars($order['status']) ?>
        </p>
        <?php if (!empty($order['upi_id'])): ?>
          <p class="text-sm text-gray-500 mt-1">UPI ID: <?= htmlspecialchars($order['upi_id']) ?></p>
        <?php endif; ?>

        <!-- Content -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
          <!-- Items -->
          <div class="md:col-span-2">
            <h3 class="font-semibold mb-3">Items</h3>
            <div class="divide-y">
              <?php foreach ($items as $it): ?>
                <div class="py-3 flex gap-3">
                  <img class="w-16 h-16 object-contain border rounded"
                       src="<?= htmlspecialchars(product_image($it['img'])) ?>"
                       alt="<?= htmlspecialchars($it['name']) ?>">
                  <div class="flex-1">
                    <a href="<?= site_url('product.php?slug='.urlencode($it['slug'])) ?>"
                       class="font-medium hover:underline">
                       <?= htmlspecialchars($it['name']) ?>
                    </a>
                    <p class="text-sm text-gray-500">
                      Qty: <?= (int)$it['qty'] ?> • Price: <?= format_price($it['price']) ?>
                    </p>
                  </div>
                  <div class="font-semibold"><?= format_price($it['subtotal']) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Address -->
          <div>
            <h3 class="font-semibold mb-3">Shipping Address</h3>
            <p class="text-sm text-darkgray">
              <?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?><br>
              <?= nl2br(htmlspecialchars(trim($order['address1']."\n".$order['address2']))) ?><br>
              <?= htmlspecialchars($order['state']) ?> - <?= htmlspecialchars($order['pincode']) ?><br>
              Mobile: <?= htmlspecialchars($order['mobile']) ?>
            </p>
          </div>
        </div>

        <div class="mt-6 flex items-center gap-3">
          <a href="<?= site_url('account/orders.php') ?>" class="underline">← Back to My Orders</a>
          <button onclick="window.print()" class="bg-white border px-3 py-1.5 rounded hover:bg-gray-50">Print</button>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
<?php include __DIR__ . '/../partials/scripts.php'; ?>
