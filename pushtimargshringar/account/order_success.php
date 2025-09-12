<?php
// account/order_success.php
// Shows a simple order confirmation and summary after successful checkout

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// helper to escape output
function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// get order id from query
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
  // invalid id
  include PUBLIC_PATH . '/partials/head.php';
  include PUBLIC_PATH . '/partials/header.php';
  ?>
  <section class="py-12">
    <div class="max-w-3xl mx-auto bg-white p-8 rounded shadow text-center">
      <h2 class="text-2xl font-semibold text-[#8B0000] mb-4">Order not found</h2>
      <p class="text-gray-600">We could not find that order. If you were redirected here after checkout, please check your email for confirmation or contact support.</p>
      <a href="<?= site_url('') ?>" class="inline-block mt-6 bg-[#8B0000] text-[#FDF6EC] px-5 py-2 rounded">Back to shop</a>
    </div>
  </section>
  <?php
  include PUBLIC_PATH . '/partials/footer.php';
  exit;
}

// optional: check ownership if user logged in
$viewerUserId = null;
if (function_exists('current_user')) {
  $u = current_user();
  if ($u && !empty($u['id'])) $viewerUserId = (int)$u['id'];
} elseif (isset($_SESSION['user']['id'])) {
  $viewerUserId = (int)$_SESSION['user']['id'];
}

// Fetch order header
$order = null;
if (!isset($conn) || !($conn instanceof mysqli)) {
  $error = "Database connection not available.";
} else {
  $stmt = $conn->prepare("SELECT id, user_id, order_number, slug, first_name, last_name, email, mobile, address1, address2, pincode, state, total_amount, payment_method, status, created_at FROM orders WHERE id = ? LIMIT 1");
  if ($stmt) {
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res?->fetch_assoc();
    $stmt->close();
  } else {
    $error = "Could not prepare order lookup: " . $conn->error;
  }
}

if (!$order) {
  include PUBLIC_PATH . '/partials/head.php';
  include PUBLIC_PATH . '/partials/header.php';
  ?>
  <section class="py-12">
    <div class="max-w-3xl mx-auto bg-white p-8 rounded shadow text-center">
      <h2 class="text-2xl font-semibold text-[#8B0000] mb-4">Order not found</h2>
      <p class="text-gray-600">We couldn't locate your order. If you believe this is an error, please contact support.</p>
      <a href="<?= site_url('') ?>" class="inline-block mt-6 bg-[#8B0000] text-[#FDF6EC] px-5 py-2 rounded">Back to shop</a>
    </div>
  </section>
  <?php
  include PUBLIC_PATH . '/partials/footer.php';
  exit;
}

// ownership check: if logged-in user and order belongs to different user, block
if ($viewerUserId !== null && (int)$order['user_id'] !== 0 && (int)$order['user_id'] !== $viewerUserId) {
  include PUBLIC_PATH . '/partials/head.php';
  include PUBLIC_PATH . '/partials/header.php';
  ?>
  <section class="py-12">
    <div class="max-w-3xl mx-auto bg-white p-8 rounded shadow text-center">
      <h2 class="text-2xl font-semibold text-red-600 mb-4">Access denied</h2>
      <p class="text-gray-600">You do not have permission to view this order.</p>
      <a href="<?= site_url('') ?>" class="inline-block mt-6 bg-[#8B0000] text-[#FDF6EC] px-5 py-2 rounded">Back to shop</a>
    </div>
  </section>
  <?php
  include PUBLIC_PATH . '/partials/footer.php';
  exit;
}

// Fetch order items (join with products where possible)
$items = [];
if (isset($conn) && $conn instanceof mysqli) {
  $stmt = $conn->prepare("SELECT oi.product_id, oi.qty, oi.price, p.name AS product_name, p.slug AS product_slug FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?");
  if ($stmt) {
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
      $items[] = $row;
    }
    $stmt->close();
  }
}

// Render page
include PUBLIC_PATH . '/partials/head.php';
include PUBLIC_PATH . '/partials/header.php';
?>
<section class="py-10">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white p-8 rounded-lg shadow">
      <h1 class="text-3xl font-semibold text-[#8B0000] mb-4">Thank you — your order is confirmed</h1>

      <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-2">
          <p class="text-gray-700 mb-4">We've received your order. Your order number is <strong>#<?= h($order['order_number'] ?: $order['id']) ?></strong>. We've also emailed a confirmation to <strong><?= h($order['email'] ?: $order['mobile']) ?></strong>.</p>

          <h2 class="text-xl font-semibold text-gray-800 mt-4 mb-2">Delivery details</h2>
          <div class="bg-gray-50 p-4 rounded mb-4">
            <p class="mb-1"><strong>Name:</strong> <?= h($order['first_name'] . (trim($order['last_name']) ? ' ' . $order['last_name'] : '')) ?></p>
            <?php if (!empty($order['email'])): ?><p class="mb-1"><strong>Email:</strong> <?= h($order['email']) ?></p><?php endif; ?>
            <p class="mb-1"><strong>Phone:</strong> <?= h($order['mobile']) ?></p>
            <p class="mb-1"><strong>Address:</strong> <?= h($order['address1']) ?><?= $order['address2'] ? ', ' . h($order['address2']) : '' ?>, <?= h($order['pincode']) ?>, <?= h($order['state']) ?></p>
            <p class="mb-1"><strong>Payment method:</strong> <?= h($order['payment_method']) ?></p>
            <p class="mb-1"><strong>Status:</strong> <?= h($order['status']) ?></p>
            <p class="mb-0 text-sm text-gray-500">Placed at: <?= h($order['created_at']) ?></p>
          </div>

          <h2 class="text-xl font-semibold text-gray-800 mt-4 mb-2">Order items</h2>
          <div class="bg-white border rounded">
            <?php if (empty($items)): ?>
              <p class="p-4 text-gray-600">No items found for this order.</p>
            <?php else: ?>
              <ul class="divide-y">
                <?php foreach ($items as $it): ?>
                  <li class="p-4 flex justify-between items-center">
                    <div>
                      <a href="<?= $it['product_slug'] ? site_url('product.php?slug=' . urlencode($it['product_slug'])) : '#' ?>" class="font-medium hover:underline"><?= h($it['product_name'] ?: "Product #{$it['product_id']}") ?></a>
                      <div class="text-sm text-gray-500">Qty: <?= (int)$it['qty'] ?></div>
                    </div>
                    <div class="font-semibold">₹ <?= number_format((float)$it['price'] * (int)$it['qty'], 2) ?></div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>

        <aside class="bg-gray-50 p-4 rounded">
          <h3 class="text-lg font-semibold mb-3">Order summary</h3>
          <div class="flex justify-between mb-2"><span>Subtotal</span><span>₹ <?= number_format((float)$order['total_amount'], 2) ?></span></div>
          <div class="flex justify-between mb-2"><span>Shipping</span><span>₹ 0.00</span></div>
          <div class="border-t pt-3 mt-3 font-semibold flex justify-between"><span>Total</span><span>₹ <?= number_format((float)$order['total_amount'], 2) ?></span></div>

          <a href="<?= site_url('account/orders.php') ?>" class="mt-4 block text-center bg-[#8B0000] text-[#FDF6EC] px-4 py-2 rounded">View all orders</a>
          <a href="<?= site_url('products.php') ?>" class="mt-2 block text-center text-sm underline">Continue shopping</a>
        </aside>
      </div>
    </div>
  </div>
</section>

<?php include PUBLIC_PATH . '/partials/footer.php'; ?>
