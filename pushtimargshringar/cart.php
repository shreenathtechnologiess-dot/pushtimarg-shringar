<?php
// cart.php
session_start();
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';

/* -------------------- Demo catalog (fallback) -------------------- */
function demo_catalog(): array {
  $arr = [
    ["name"=>"Blue Cotton","img"=>"blue-cotton.jpg","price"=>400,"category"=>"fabric","slug"=>"blue-cotton"],
    ["name"=>"Golden Banarasi","img"=>"golden-banarasi.jpg","price"=>1200,"category"=>"fabric","slug"=>"golden-banarasi"],
    ["name"=>"Green Silk","img"=>"green-silk.jpg","price"=>1600,"category"=>"fabric","slug"=>"green-silk"],
    ["name"=>"Linen Blend","img"=>"linen-blend.jpg","price"=>2000,"category"=>"fabric","slug"=>"linen-blend"],
    ["name"=>"Purple Silk","img"=>"purple-silk.jpg","price"=>2800,"category"=>"fabric","slug"=>"purple-silk"],
    ["name"=>"Premium Fabric","img"=>"premium-fabric.jpeg","price"=>3000,"category"=>"fabric","slug"=>"premium-fabric"],
    ["name"=>"Maroon Paisley","img"=>"maroon-paisley.jpg","price"=>3500,"category"=>"fabric","slug"=>"maroon-paisley"],
    ["name"=>"Orange Vastra","img"=>"orange-vastra.jpeg","price"=>4000,"category"=>"vastra","slug"=>"orange-vastra"],
    ["name"=>"Royal Blue","img"=>"royal-blue.jpeg","price"=>4500,"category"=>"vastra","slug"=>"royal-blue"],
    ["name"=>"Cow Krishna Pichwai","img"=>"cow-krishna-pichwai.jpg","price"=>5000,"category"=>"pichwai","slug"=>"cow-krishna-pichwai"],
    ["name"=>"Dancing Gopis Pichwai","img"=>"dancing-gopis-pichwai.jpg","price"=>5500,"category"=>"pichwai","slug"=>"dancing-gopis-pichwai"],
    ["name"=>"Lotus Pond Pichwai","img"=>"lotus-pond-pichwai.jpg","price"=>6000,"category"=>"pichwai","slug"=>"lotus-pond-pichwai"],
    ["name"=>"Radha Krishna Pichwai","img"=>"radha-krishna-pichwai.jpg","price"=>6500,"category"=>"pichwai","slug"=>"radha-krishna-pichwai"],
    ["name"=>"Peacock Pichwai","img"=>"peacock-pichwai.jpg","price"=>7000,"category"=>"pichwai","slug"=>"peacock-pichwai"],
    ["name"=>"Tree Pichwai","img"=>"tree-pichwai.jpg","price"=>7500,"category"=>"pichwai","slug"=>"tree-pichwai"],
    ["name"=>"Pink Floral Pichwai","img"=>"pink-floral-pichwai.jpg","price"=>8000,"category"=>"pichwai","slug"=>"pink-floral-pichwai"],
    ["name"=>"Shreenathji Elephant Pichwai","img"=>"shreenathji-elephant-pichwai.jpg","price"=>8500,"category"=>"pichwai","slug"=>"shreenathji-elephant-pichwai"],
    ["name"=>"Shreenathji Print","img"=>"shreenathji-print.jpg","price"=>9000,"category"=>"pichwai","slug"=>"shreenathji-print"],
  ];
  $map = [];
  foreach ($arr as $p) { $map[$p['slug']] = $p; }
  return $map;
}

/* -------------------- Product lookup (DB → demo) -------------------- */
function find_product(string $slug): ?array {
  global $conn;
  // DB-first
  if (isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT name, slug, image AS img, price, category FROM products WHERE slug=? LIMIT 1");
    if ($stmt) {
      $stmt->bind_param("s", $slug);
      $stmt->execute();
      $res = $stmt->get_result();
      $row = $res?->fetch_assoc();
      $stmt->close();
      if ($row) return $row;
    }
  }
  // Fallback to demo catalog
  static $demo = null;
  if ($demo === null) $demo = demo_catalog();
  return $demo[$slug] ?? null;
}

/* -------------------- Session cart -------------------- */
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];

/* -------------------- Helpers -------------------- */
function cart_count(): int {
  return array_sum(array_map('intval', $_SESSION['cart'] ?? []));
}

function is_ajax_request(): bool {
  return (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
  ) || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
}

/* -------------------- Actions -------------------- */
$action = $_GET['action'] ?? '';
// note: for AJAX we prefer POST body, but keep GET fallback for legacy links
$slug   = isset($_REQUEST['slug']) ? trim((string)$_REQUEST['slug']) : '';
$qty    = isset($_REQUEST['qty'])  ? (int)$_REQUEST['qty']   : 1;

// helper redirect
function back_to_cart(string $msg=''){ header('Location: '.site_url('cart.php'.($msg?'?msg='.$msg:''))); exit; }

switch ($action) {
  case 'add':
    // Accept POST body first (AJAX/fetch) or GET fallback
    $slugParam = '';
    $qtyParam  = 1;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $slugParam = trim($_POST['slug'] ?? '');
      $qtyParam  = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
      $redirectParam = trim($_POST['redirect'] ?? '');
    } else {
      $slugParam = isset($_GET['slug']) ? trim($_GET['slug']) : '';
      $qtyParam  = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
      $redirectParam = isset($_GET['redirect']) ? trim($_GET['redirect']) : '';
    }

    if ($slugParam !== '' && ($p = find_product($slugParam))) {
      $_SESSION['cart'][$slugParam] = ($_SESSION['cart'][$slugParam] ?? 0) + max(1, $qtyParam);

      // If AJAX client requested JSON response
      if (is_ajax_request()) {
        $items_count = cart_count();
        $resp = [
          'ok' => true,
          'msg' => 'added',
          'cart_count' => $items_count,
          'cart' => $_SESSION['cart']
        ];
        header('Content-Type: application/json');
        echo json_encode($resp);
        exit;
      }

      // Non-AJAX: check redirect param (Buy Now flow)
      if (!empty($redirectParam) && strtolower($redirectParam) === 'checkout') {
        // Redirect to checkout page for this product slug
        header('Location: ' . site_url('checkout.php?slug=' . urlencode($slugParam)));
        exit;
      }

      // Default non-AJAX: go back to cart with message
      back_to_cart('added');
    }

    // if product not found or slug empty
    if (is_ajax_request()) {
      header('Content-Type: application/json', true, 400);
      echo json_encode(['ok'=>false, 'msg'=>'invalid_product']);
      exit;
    }
    back_to_cart();

  case 'remove':
    if ($slug !== '' && isset($_SESSION['cart'][$slug])) unset($_SESSION['cart'][$slug]);

    if (is_ajax_request()) {
      $items_count = cart_count();
      header('Content-Type: application/json');
      echo json_encode(['ok'=>true, 'msg'=>'removed', 'cart_count'=>$items_count, 'cart'=>$_SESSION['cart']]);
      exit;
    }

    back_to_cart('removed');

  case 'empty':
    $_SESSION['cart'] = [];

    if (is_ajax_request()) {
      header('Content-Type: application/json');
      echo json_encode(['ok'=>true, 'msg'=>'emptied', 'cart_count'=>0, 'cart'=>[]]);
      exit;
    }

    back_to_cart('emptied');

  case 'update': // bulk via POST: qty[slug] => n
    if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['qty']) && is_array($_POST['qty'])) {
      foreach ($_POST['qty'] as $s => $q) {
        $q = (int)$q;
        if ($q <= 0) { unset($_SESSION['cart'][$s]); continue; }
        // validate product still exists (DB or demo)
        if (find_product($s)) $_SESSION['cart'][$s] = $q;
      }
    }

    if (is_ajax_request()) {
      $items_count = cart_count();
      header('Content-Type: application/json');
      echo json_encode(['ok'=>true, 'msg'=>'updated', 'cart_count'=>$items_count, 'cart'=>$_SESSION['cart']]);
      exit;
    }

    back_to_cart('updated');
}

/* -------------------- Build view -------------------- */
$items = [];
$total = 0;
foreach ($_SESSION['cart'] as $s => $q) {
  $p = find_product($s);
  if (!$p) { /* don't unset here, let user see issue if any */ continue; }
  $q = max(1, (int)$q);
  $p['qty'] = $q;
  $p['subtotal'] = $q * (int)$p['price'];
  $items[] = $p;
  $total += $p['subtotal'];
}

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';

/* Flash message */
$msg = $_GET['msg'] ?? '';
$flash = [
  'added'   => 'Item added to cart.',
  'updated' => 'Cart updated.',
  'removed' => 'Item removed.',
  'emptied' => 'Cart emptied.',
];
?>
<?php if (isset($flash[$msg])): ?>
  <div class="max-w-7xl mx-auto px-4 mt-4">
    <div class="bg-green-50 text-green-800 border border-green-200 px-4 py-2 rounded">
      <?= htmlspecialchars($flash[$msg]) ?>
    </div>
  </div>
<?php endif; ?>

<section class="py-10 bg-cream">
  <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h1 class="text-2xl font-bold text-deepgreen">Your Cart</h1>
          <?php if (!empty($items)): ?>
            <a href="<?= site_url('cart.php?action=empty&msg=emptied') ?>" class="text-sm text-red-600 underline">Empty cart</a>
          <?php endif; ?>
        </div>

        <?php if (empty($items)): ?>
          <p class="text-gray-600">Your cart is empty.</p>
          <a class="inline-block mt-4 bg-deepgreen text-white px-4 py-2 rounded hover:bg-gold hover:text-darkgray" href="<?= site_url('products.php') ?>">Continue Shopping</a>
        <?php else: ?>
          <form action="<?= site_url('cart.php?action=update') ?>" method="POST" class="space-y-4">
            <?php foreach ($items as $p): ?>
              <div class="flex gap-3 border-b pb-3">
                <img src="<?= htmlspecialchars(product_image($p['img'])) ?>" class="w-20 h-20 object-contain border rounded" alt="<?= htmlspecialchars($p['name']) ?>">
                <div class="flex-1">
                  <a href="<?= site_url('product.php?slug='.urlencode($p['slug'])) ?>" class="font-medium hover:underline"><?= htmlspecialchars($p['name']) ?></a>
                  <p class="text-sm text-gray-500">Price: <?= format_price($p['price']) ?></p>

                  <div class="mt-2 flex items-center gap-2">
                    <label class="text-sm text-gray-600">Qty</label>
                    <div class="flex items-center border rounded">
                      <button type="button" class="px-2 py-1 min-w-8 cart-qty-dec" data-target="qty-<?= htmlspecialchars($p['slug']) ?>">−</button>
                      <input id="qty-<?= htmlspecialchars($p['slug']) ?>" type="number" name="qty[<?= htmlspecialchars($p['slug']) ?>]" value="<?= (int)$p['qty'] ?>" min="1" class="w-16 text-center outline-none">
                      <button type="button" class="px-2 py-1 min-w-8 cart-qty-inc" data-target="qty-<?= htmlspecialchars($p['slug']) ?>">+</button>
                    </div>
                    <a href="<?= site_url('cart.php?action=remove&slug='.urlencode($p['slug']).'&msg=removed') ?>" class="text-sm text-red-600 underline ml-2">Remove</a>
                  </div>
                </div>
                <div class="text-right font-semibold"><?= format_price($p['subtotal']) ?></div>
              </div>
            <?php endforeach; ?>

            <div class="flex items-center justify-between pt-2">
              <a class="text-sm underline" href="<?= site_url('products.php') ?>">← Continue shopping</a>
              <button class="bg-white border px-4 py-2 rounded hover:bg-gray-50" type="submit">Update Cart</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Summary -->
    <aside>
      <div class="bg-white rounded-lg shadow p-6 sticky top-24">
        <h3 class="text-xl font-bold text-deepgreen mb-4">Summary</h3>
        <?php if (empty($items)): ?>
          <p class="text-gray-600">No items.</p>
        <?php else: ?>
          <div class="flex items-center justify-between mb-2">
            <span>Subtotal</span>
            <span class="font-semibold"><?= format_price($total) ?></span>
          </div>
          <p class="text-xs text-gray-500">GST not included.</p>
          <a href="<?= site_url('checkout.php') ?>" class="mt-4 block text-center bg-deepgreen text-white px-4 py-3 rounded hover:bg-gold hover:text-darkgray">Proceed to Checkout</a>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
<?php include __DIR__ . '/partials/scripts.php'; ?>
<script>
document.addEventListener('click', function(e){
  if (e.target.classList.contains('cart-qty-dec') || e.target.classList.contains('cart-qty-inc')) {
    const id = e.target.getAttribute('data-target');
    const input = document.getElementById(id);
    if (!input) return;
    const val = Math.max(1, parseInt(input.value || '1', 10));
    input.value = e.target.classList.contains('cart-qty-inc') ? (val + 1) : Math.max(1, val - 1);
  }
});
</script>
