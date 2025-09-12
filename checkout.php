<?php
// checkout.php
session_start();
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/auth.php';

/* -------------------- CSRF helpers -------------------- */
if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
  }
}
if (!function_exists('csrf_check')) {
  function csrf_check($t): bool {
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$t);
  }
}

/* -------------------- Demo catalog fallback -------------------- */
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
  if (isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT name, slug, image AS img, price FROM products WHERE slug=? LIMIT 1");
    if ($stmt) {
      $stmt->bind_param("s", $slug);
      $stmt->execute();
      $res = $stmt->get_result();
      $row = $res?->fetch_assoc();
      $stmt->close();
      if ($row) return $row;
    }
  }
  static $demo = null;
  if ($demo === null) $demo = demo_catalog();
  return $demo[$slug] ?? null;
}

/* -------------------- Ensure cart exists -------------------- */
$_SESSION['cart'] = $_SESSION['cart'] ?? [];

/* -------------------- Buy Now quick-add (DO THIS FIRST) -------------------- */
if (!empty($_GET['slug'])) {
  $slug = trim($_GET['slug']);
  // validate product once
  if (find_product($slug)) {
    $_SESSION['cart'][$slug] = ($_SESSION['cart'][$slug] ?? 0) + 1;
  }
  // clean URL
  header('Location: '.site_url('checkout.php'));
  exit;
}

/* -------------------- Empty-cart guard (after quick-add) -------------------- */
if (empty($_SESSION['cart'])) {
  header('Location: '.site_url('cart.php?msg=updated'));
  exit;
}

/* -------------------- Build items from cart -------------------- */
$items = []; $subtotal = 0;
foreach ($_SESSION['cart'] as $s => $q) {
  $p = find_product($s);
  if (!$p) continue; // don't unset, just skip if mismatch
  $q = max(1, (int)$q);
  $p['qty'] = $q;
  $p['subtotal'] = $q * (int)$p['price'];
  $items[] = $p;
  $subtotal += $p['subtotal'];
}
if (empty($items)) {
  header('Location: '.site_url('cart.php?msg=updated'));
  exit;
}

/* -------------------- Prefill from user -------------------- */
$user = auth_user();
$defaults = [
  'first_name' => $user['name'] ?? '',
  'last_name'  => '',
  'address1'   => '',
  'address2'   => '',
  'pincode'    => '',
  'state'      => '',
  'mobile'     => $user['mobile'] ?? '',
  'payment'    => 'cod',   // cod | upi
  'upi_id'     => '',
];
$input  = $defaults;
$errors = [];

/* -------------------- Validators -------------------- */
function valid_pincode($v){ return preg_match('/^\d{6}$/', $v); }
function valid_mobile($v){ return preg_match('/^\d{10}$/', $v); }

/* -------------------- Submit -------------------- */
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_check($_POST['_csrf'] ?? '')) {
    $errors['csrf'] = "Session expired. Please refresh and try again.";
  }

  foreach ($input as $k => $v) { if (isset($_POST[$k])) $input[$k] = trim((string)$_POST[$k]); }

  if ($input['first_name']==='') $errors['first_name']='First name is required.';
  if ($input['address1']==='')   $errors['address1']='Address line 1 is required.';
  if (!valid_pincode($input['pincode'])) $errors['pincode']='Enter a valid 6 digit pincode.';
  if ($input['state']==='')      $errors['state']='State is required.';
  if (!valid_mobile($input['mobile'])) $errors['mobile']='Enter a valid 10 digit mobile.';
  if (!in_array($input['payment'], ['upi','cod'], true)) $errors['payment']='Select a valid payment method.';
  if ($input['payment']==='upi' && $input['upi_id']==='') $errors['upi_id']='Enter your UPI ID.';

  if (empty($errors)) {
    $orderNo = 'ORD'.date('YmdHis').rand(100,999);
    $pmethod = $input['payment'];
    $upi     = ($pmethod==='upi') ? $input['upi_id'] : null;
    $total   = $subtotal;

    if (isset($conn) && $conn instanceof mysqli) {
      $userId = $user['id'] ?? null;

      $stmt = $conn->prepare("
        INSERT INTO orders
        (order_number, user_id, first_name, last_name, address1, address2, pincode, state, mobile, payment_method, upi_id, total_amount)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
      ");
      $stmt->bind_param(
        "sisssssssssi",
        $orderNo, $userId,
        $input['first_name'], $input['last_name'], $input['address1'], $input['address2'],
        $input['pincode'], $input['state'], $input['mobile'],
        $pmethod, $upi, $total
      );
      $stmt->execute();
      $orderId = $stmt->insert_id;
      $stmt->close();

      $stmt2 = $conn->prepare("INSERT INTO order_items (order_id, slug, name, price, qty, subtotal, img) VALUES (?,?,?,?,?,?,?)");
      foreach ($items as $it) {
        $stmt2->bind_param("issiiis", $orderId, $it['slug'], $it['name'], $it['price'], $it['qty'], $it['subtotal'], $it['img']);
        $stmt2->execute();
      }
      $stmt2->close();

      $_SESSION['cart'] = []; // clear cart

      header('Location: '.site_url('account/order.php?id='.$orderId));
      exit;
    } else {
      $errors['db'] = "Database connection not available.";
    }
  }
}

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>
<section class="py-10 bg-cream">
  <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- LEFT: Form -->
    <div class="lg:col-span-2">
      <div class="bg-white p-6 rounded-lg shadow">
        <h1 class="text-2xl font-bold text-deepgreen mb-4">Checkout</h1>
        <?php if (!empty($errors)): ?>
          <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded">
            Please fix the highlighted fields.
            <?php if(!empty($errors['csrf'])): ?><div><?= htmlspecialchars($errors['csrf']) ?></div><?php endif; ?>
            <?php if(!empty($errors['db'])): ?><div><?= htmlspecialchars($errors['db']) ?></div><?php endif; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="space-y-4" novalidate>
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()); ?>">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm mb-1">First Name</label>
              <input name="first_name" value="<?= htmlspecialchars($input['first_name']) ?>" class="w-full border rounded px-3 py-2 <?= isset($errors['first_name'])?'border-red-500':''; ?>">
              <?php if(isset($errors['first_name'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['first_name']) ?></p><?php endif; ?>
            </div>
            <div>
              <label class="block text-sm mb-1">Last Name</label>
              <input name="last_name" value="<?= htmlspecialchars($input['last_name']) ?>" class="w-full border rounded px-3 py-2">
            </div>
          </div>

          <div>
            <label class="block text-sm mb-1">Address Line 1</label>
            <input name="address1" value="<?= htmlspecialchars($input['address1']) ?>" class="w-full border rounded px-3 py-2 <?= isset($errors['address1'])?'border-red-500':''; ?>">
            <?php if(isset($errors['address1'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['address1']) ?></p><?php endif; ?>
          </div>
          <div>
            <label class="block text-sm mb-1">Address Line 2 (optional)</label>
            <input name="address2" value="<?= htmlspecialchars($input['address2']) ?>" class="w-full border rounded px-3 py-2">
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm mb-1">Pincode</label>
              <input name="pincode" value="<?= htmlspecialchars($input['pincode']) ?>" maxlength="6" pattern="\d{6}" class="w-full border rounded px-3 py-2 <?= isset($errors['pincode'])?'border-red-500':''; ?>">
              <?php if(isset($errors['pincode'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['pincode']) ?></p><?php endif; ?>
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm mb-1">State</label>
              <input name="state" value="<?= htmlspecialchars($input['state']) ?>" class="w-full border rounded px-3 py-2 <?= isset($errors['state'])?'border-red-500':''; ?>">
              <?php if(isset($errors['state'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['state']) ?></p><?php endif; ?>
            </div>
          </div>

          <div>
            <label class="block text-sm mb-1">Mobile</label>
            <input name="mobile" value="<?= htmlspecialchars($input['mobile']) ?>" maxlength="10" pattern="\d{10}" class="w-full border rounded px-3 py-2 <?= isset($errors['mobile'])?'border-red-500':''; ?>">
            <?php if(isset($errors['mobile'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['mobile']) ?></p><?php endif; ?>
          </div>

          <div>
            <label class="block text-sm mb-1">Payment Method</label>
            <div class="flex items-center gap-6">
              <label class="inline-flex items-center gap-2">
                <input type="radio" name="payment" value="cod" <?= $input['payment']==='cod'?'checked':''; ?>> Cash on Delivery
              </label>
              <label class="inline-flex items-center gap-2">
                <input type="radio" name="payment" value="upi" <?= $input['payment']==='upi'?'checked':''; ?>> UPI
              </label>
            </div>
          </div>

          <div id="upiField" class="<?= $input['payment']==='upi'?'':'hidden'; ?>">
            <label class="block text-sm mb-1">UPI ID</label>
            <input name="upi_id" value="<?= htmlspecialchars($input['upi_id']) ?>" class="w-full border rounded px-3 py-2 <?= isset($errors['upi_id'])?'border-red-500':''; ?>" placeholder="yourname@bank">
            <?php if(isset($errors['upi_id'])): ?><p class="text-red-600 text-sm"><?= htmlspecialchars($errors['upi_id']) ?></p><?php endif; ?>
          </div>

          <button class="bg-deepgreen text-white px-4 py-2 rounded hover:bg-gold hover:text-darkgray">Place Order</button>
        </form>
      </div>
    </div>

    <!-- RIGHT: Summary -->
    <aside>
      <div class="bg-white p-6 rounded-lg shadow sticky top-24">
        <h3 class="text-xl font-bold text-deepgreen mb-4">Order Summary</h3>
        <div class="space-y-3 max-h-[340px] overflow-y-auto pr-1">
          <?php foreach ($items as $it): ?>
            <div class="flex gap-3 border-b pb-3">
              <img src="<?= htmlspecialchars(product_image($it['img'])) ?>" class="w-16 h-16 object-contain border rounded" alt="<?= htmlspecialchars($it['name']) ?>">
              <div class="flex-1">
                <p class="text-sm"><?= htmlspecialchars($it['name']) ?></p>
                <p class="text-xs text-gray-500">Qty: <?= (int)$it['qty'] ?> • Price: <?= format_price($it['price']) ?></p>
              </div>
              <div class="font-semibold"><?= format_price($it['subtotal']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="flex items-center justify-between mt-4">
          <span>Subtotal</span>
          <span class="font-bold text-gold"><?= format_price($subtotal) ?></span>
        </div>
        <p class="text-xs text-gray-500 mt-1">GST not included.</p>
      </div>
    </aside>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
<?php include __DIR__ . '/partials/scripts.php'; ?>

<script>
document.addEventListener('change', function(e){
  if (e.target.name === 'payment') {
    document.getElementById('upiField').classList.toggle('hidden', e.target.value !== 'upi');
  }
});
</script>
