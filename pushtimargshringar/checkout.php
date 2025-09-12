<?php
// checkout.php
$pageTitle = "Checkout";

require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/auth.php';

// ensure session
if (session_status() === PHP_SESSION_NONE) session_start();

/* ---------- Helpers ---------- */
function current_user_id_or_null(): ?int {
  if (function_exists('current_user')) {
    $u = current_user();
    if ($u && !empty($u['id'])) return (int)$u['id'];
  }
  return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
}

function demo_products(): array {
  return [
    ["name"=>"Blue Cotton","img"=>"blue-cotton.jpg","price"=>400,"category"=>"fabric","slug"=>"blue-cotton"],
    ["name"=>"Golden Banarasi","img"=>"golden-banarasi.jpg","price"=>1200,"category"=>"fabric","slug"=>"golden-banarasi"],
    ["name"=>"Green Silk","img"=>"green-silk.jpg","price"=>1600,"category"=>"fabric","slug"=>"green-silk"],
    ["name"=>"Orange Vastra","img"=>"orange-vastra.jpeg","price"=>4000,"category"=>"vastra","slug"=>"orange-vastra"],
    ["name"=>"Royal Blue","img"=>"royal-blue.jpeg","price"=>4500,"category"=>"vastra","slug"=>"royal-blue"],
    ["name"=>"Cow Krishna Pichwai","img"=>"cow-krishna-pichwai.jpg","price"=>5000,"category"=>"pichwai","slug"=>"cow-krishna-pichwai"],
    ["name"=>"Peacock Pichwai","img"=>"peacock-pichwai.jpg","price"=>7000,"category"=>"pichwai","slug"=>"peacock-pichwai"],
    ["name"=>"Shreenathji Print","img"=>"shreenathji-print.jpg","price"=>9000,"category"=>"pichwai","slug"=>"shreenathji-print"],
  ];
}
function demo_map(): array {
  static $map = null;
  if ($map === null) {
    $map = [];
    foreach (demo_products() as $p) $map[$p['slug']] = $p;
  }
  return $map;
}

/* ---------- CSRF token (on GET) ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $_SESSION['csrf_checkout'] = bin2hex(random_bytes(16));
}

/* ---------- Build items: support two flows ----------
   1) Normal cart-based checkout (reads $_SESSION['cart'])
   2) Buy Now (checkout.php?slug=... OR ?slug=...&force=1) -> show only that product
-----------------------------------------------------*/
$cart  = $_SESSION['cart'] ?? [];
$items = [];
$total = 0.0;

$requestedSlug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
$forceSingle   = isset($_GET['force']) && $_GET['force'] == '1'; // optional: force single-item view even if cart has items

// helper to fetch a single product (DB first, fallback to demo map)
function fetch_product_by_slug(string $slug): ?array {
  global $conn;
  if (!$slug) return null;
  // DB
  if (isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT id, name, price, slug, image AS img FROM products WHERE slug = ? LIMIT 1");
    if ($stmt) {
      $stmt->bind_param('s',$slug);
      $stmt->execute();
      $res = $stmt->get_result();
      $row = $res?->fetch_assoc();
      $stmt->close();
      if ($row) return $row;
    }
  }
  // demo fallback
  $map = demo_map();
  return $map[$slug] ?? null;
}

// If a slug is provided AND (cart empty OR forceSingle) then build items from slug alone
if ($requestedSlug !== '' && (empty($cart) || $forceSingle)) {
  $p = fetch_product_by_slug($requestedSlug);
  if ($p) {
    $qty = 1;
    // if qty passed as GET (e.g. ?slug=...&qty=2), use it
    if (isset($_GET['qty'])) $qty = max(1, (int)$_GET['qty']);
    $p['qty'] = $qty;
    $p['subtotal'] = $qty * (float)$p['price'];
    $items[] = $p;
    $total += $p['subtotal'];
  } else {
    // product not found -> show placeholder so user sees problem
    $items[] = [
      'id' => 0,
      'name' => "Product not available: " . htmlspecialchars($requestedSlug),
      'price' => 0.0,
      'img' => null,
      'slug' => $requestedSlug,
      'qty' => 1,
      'subtotal' => 0.0
    ];
  }
} else {
  // Normal cart-based flow: load all slugs from session cart from DB in one query
  if (!empty($cart) && is_array($cart)) {
    $slugs = array_keys($cart);
    $slugs = array_values(array_filter(array_map('strval', $slugs), function($s){ return trim($s) !== ''; }));
    if (!empty($slugs) && isset($conn) && $conn instanceof mysqli) {
      $escaped = array_map(function($s) use ($conn){ return "'" . $conn->real_escape_string($s) . "'"; }, $slugs);
      $in = implode(',', $escaped);
      $sql = "SELECT id, name, price, slug, image AS img FROM products WHERE slug IN ($in)";
      $res = $conn->query($sql);
      $found = [];
      if ($res) {
        while ($row = $res->fetch_assoc()) $found[$row['slug']] = $row;
        $res->free();
      }
      // Build items in cart order; fallback to single fetch or demo map
      foreach ($slugs as $slug) {
        $qty = max(1, (int)($cart[$slug] ?? 1));
        if (isset($found[$slug])) {
          $p = $found[$slug];
          $p['qty'] = $qty;
          $p['subtotal'] = $qty * (float)$p['price'];
          $items[] = $p;
          $total += $p['subtotal'];
          continue;
        }
        // try fetch single
        $single = fetch_product_by_slug($slug);
        if ($single) {
          $single['qty'] = $qty;
          $single['subtotal'] = $qty * (float)$single['price'];
          $items[] = $single;
          $total += $single['subtotal'];
          continue;
        }
        // not found -> placeholder
        $items[] = [
          'id' => 0,
          'name' => "Product not available: " . $slug,
          'price' => 0.0,
          'img' => null,
          'slug' => $slug,
          'qty' => $qty,
          'subtotal' => 0.0
        ];
      }
    } else {
      // no DB available, fallback to demo_map for each slug
      $demo = demo_map();
      foreach ($cart as $slug => $qty) {
        $qty = max(1,(int)$qty);
        if (isset($demo[$slug])) {
          $p = $demo[$slug];
          $p['qty'] = $qty;
          $p['subtotal'] = $qty * (float)$p['price'];
          $items[] = $p; $total += $p['subtotal']; continue;
        }
        $items[] = ['id'=>0,'name'=>"Product not available: ".$slug,'price'=>0,'img'=>null,'slug'=>$slug,'qty'=>$qty,'subtotal'=>0.0];
      }
    }
  }
}

/* ---------- Cart empty? (if still empty) ---------- */
if (empty($items)) {
  include PUBLIC_PATH . '/partials/head.php';
  include PUBLIC_PATH . '/partials/header.php'; ?>
  <section class="py-10">
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-xl shadow text-center">
      <p class="text-gray-600">Your cart is empty.</p>
      <a href="<?= SITE_URL ?>/products.php" class="mt-4 inline-block bg-[#8B0000] text-[#FDF6EC] px-5 py-2 rounded">
        Continue Shopping
      </a>
    </div>
  </section>
  <?php
  include PUBLIC_PATH . '/partials/footer.php';
  exit;
}

/* ---------- Handle order submit ---------- */
$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF check
  if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf_checkout'] ?? '', $_POST['csrf'])) {
    $errors[] = 'Session expired. Please refresh and try again.';
  }
  unset($_SESSION['csrf_checkout']);

  // collect input
  $name      = trim($_POST['name'] ?? '');
  $email     = trim($_POST['email'] ?? '');
  $phoneRaw  = trim($_POST['phone'] ?? '');
  $address1  = trim($_POST['address'] ?? '');
  $address2  = trim($_POST['address2'] ?? '');
  $city      = trim($_POST['city'] ?? '');
  $state     = trim($_POST['state'] ?? '');
  $pinRaw    = trim($_POST['pincode'] ?? '');
  $payMethod = trim($_POST['payment_method'] ?? 'COD');

  // sanitize digits
  $phone   = preg_replace('/\D+/', '', $phoneRaw);
  $pincode = preg_replace('/\D+/', '', $pinRaw);

  // validations
  if ($name === '')          $errors[] = 'Name is required.';
  if ($address1 === '')      $errors[] = 'Address Line 1 is required.';
  if (strlen($phone) !== 10) $errors[] = 'Phone must be exactly 10 digits.';
  if ($pincode !== '' && strlen($pincode) !== 6) $errors[] = 'Pincode must be exactly 6 digits.';
  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email.';
  }

  // repopulate
  $old = [
    'name' => $name, 'email' => $email, 'phone' => $phoneRaw,
    'address' => $address1, 'address2' => $address2,
    'city' => $city, 'state' => $state, 'pincode' => $pinRaw,
    'payment_method' => $payMethod
  ];

  if (empty($errors)) {
    // safe DB flow
    if (!isset($conn) || !($conn instanceof mysqli)) {
      $errors[] = 'Database connection not available.';
    } else {
      $userId    = current_user_id_or_null();
      $status    = "pending";

      // begin transaction
      $conn->begin_transaction();
      try {
        // Insert order (adjust columns to match your DB)
        $sql = "INSERT INTO orders
          (user_id, first_name, last_name, email, mobile, address1, address2, pincode, state,
           total_amount, payment_method, status)
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
          throw new RuntimeException('Order prepare failed: ' . $conn->error);
        }

        $user_id_val    = $userId;
        $first_name_val = $name;
        $last_name_val  = '';
        $email_val      = $email ?: null;
        $mobile_val     = $phone;
        $address1_val   = $address1;
        $address2_val   = $address2;
        $pincode_val    = $pincode;
        $state_val      = $state;
        $total_amount   = (float)$total;
        $payment_val    = $payMethod;
        $status_val     = $status;

        $types = "issssssssdss";
        $stmt->bind_param(
          $types,
          $user_id_val,
          $first_name_val,
          $last_name_val,
          $email_val,
          $mobile_val,
          $address1_val,
          $address2_val,
          $pincode_val,
          $state_val,
          $total_amount,
          $payment_val,
          $status_val
        );

        if (!$stmt->execute()) {
          throw new RuntimeException('Order execute failed: ' . $stmt->error);
        }
        $orderId = (int)$stmt->insert_id;
        $stmt->close();

        // Insert order_items (only for valid product ids > 0)
        $oi = $conn->prepare("INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?,?,?,?)");
        if (!$oi) {
          throw new RuntimeException('Order items prepare failed: ' . $conn->error);
        }

        foreach ($items as $it) {
          $pid   = (int)($it['id'] ?? 0);
          if ($pid <= 0) {
            // skip placeholder/missing products
            continue;
          }
          $qty   = (int)$it['qty'];
          $price = (float)$it['price'];
          $oi->bind_param("iiid", $orderId, $pid, $qty, $price);
          if (!$oi->execute()) {
            throw new RuntimeException('Order item insert failed: ' . $oi->error);
          }
        }
        $oi->close();

        // commit
        $conn->commit();

        // clear cart and redirect to success page
        $_SESSION['cart'] = [];
        header("Location: " . SITE_URL . "/account/order_success.php?id=" . $orderId);
        exit;

      } catch (Throwable $e) {
        $conn->rollback();
        $errors[] = "Sorry, we couldn't place your order: " . $e->getMessage();
      }
    }
  }
}

/* ---------- Show checkout form ---------- */
include PUBLIC_PATH . '/partials/head.php';
include PUBLIC_PATH . '/partials/header.php';
?>
<section class="py-10">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="font-['Playfair_Display'] text-3xl md:text-4xl text-[#8B0000] mb-6">Checkout</h1>

    <?php if ($errors): ?>
      <div class="mb-6 bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
        <ul class="list-disc ml-5">
          <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-2 gap-8">
      <!-- Billing form -->
      <form id="checkoutForm" method="post" class="bg-white p-6 rounded-xl shadow space-y-4" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_checkout'] ?? '') ?>">

        <div>
          <label class="block text-sm">Name *</label>
          <input type="text" name="name" required maxlength="100"
                 class="w-full border rounded px-3 py-2"
                 value="<?= htmlspecialchars($old['name'] ?? '') ?>">
        </div>
        <div>
          <label class="block text-sm">Email</label>
          <input type="email" name="email" maxlength="180"
                 class="w-full border rounded px-3 py-2"
                 value="<?= htmlspecialchars($old['email'] ?? '') ?>">
        </div>
        <div>
          <label class="block text-sm">Phone *</label>
          <input type="text" name="phone" required
                 inputmode="numeric" pattern="[0-9]{10}" maxlength="10"
                 class="w-full border rounded px-3 py-2"
                 value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                 placeholder="10-digit number">
        </div>
        <div>
          <label class="block text-sm">Address Line 1 *</label>
          <input type="text" name="address" required maxlength="255"
                 class="w-full border rounded px-3 py-2"
                 placeholder="House No, Street, Area"
                 value="<?= htmlspecialchars($old['address'] ?? '') ?>">
        </div>
        <div>
          <label class="block text-sm">Address Line 2 (optional)</label>
          <input type="text" name="address2" maxlength="255"
                 class="w-full border rounded px-3 py-2"
                 placeholder="Apartment / Landmark / Near temple"
                 value="<?= htmlspecialchars($old['address2'] ?? '') ?>">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <input type="text" name="city" maxlength="80" placeholder="City"
                 class="border rounded px-3 py-2"
                 value="<?= htmlspecialchars($old['city'] ?? '') ?>">
          <input type="text" name="state" maxlength="80" placeholder="State"
                 class="border rounded px-3 py-2"
                 value="<?= htmlspecialchars($old['state'] ?? '') ?>">
          <input type="text" name="pincode" placeholder="PIN"
                 inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                 class="border rounded px-3 py-2"
                 value="<?= htmlspecialchars($old['pincode'] ?? '') ?>">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <label class="text-sm flex items-center gap-2">
            <input type="radio" name="payment_method" value="COD"
                   <?= (($old['payment_method'] ?? 'COD') === 'COD') ? 'checked' : '' ?>>
            Cash on Delivery
          </label>
          <label class="text-sm flex items-center gap-2">
            <input type="radio" name="payment_method" value="Online (Pending)"
                   <?= (($old['payment_method'] ?? '') === 'Online (Pending)') ? 'checked' : '' ?>>
            Online (Pay later)
          </label>
        </div>
        <button class="bg-[#8B0000] text-[#FDF6EC] px-5 py-2 rounded">Place Order</button>
      </form>

      <!-- Order summary -->
      <div class="bg-white p-6 rounded-xl shadow">
        <h2 class="text-xl font-semibold text-[#8B0000] mb-4">Order Summary</h2>
        <ul class="divide-y">
          <?php foreach ($items as $it): ?>
            <li class="flex items-center justify-between py-3">
              <div class="flex items-center gap-3">
                <?php
                  $imgFile = $it['img'] ?? '';
                  $imgSrc  = $imgFile ? product_image($imgFile) : asset('assets/images/placeholder.png');
                ?>
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($it['name']) ?>" class="w-16 h-16 object-contain border rounded bg-white" />
                <div>
                  <div class="font-medium"><?= htmlspecialchars($it['name']) ?></div>
                  <div class="text-sm text-gray-500">Qty: <?= (int)$it['qty'] ?></div>
                </div>
              </div>
              <div class="text-right font-semibold">₹ <?= number_format($it['subtotal'], 2) ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="mt-4 border-t pt-3 flex justify-between font-semibold">
          <span>Total</span>
          <span>₹ <?= number_format($total, 2) ?></span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Front-end numeric guards -->
<script>
(function () {
  const f = document.getElementById('checkoutForm');
  if (!f) return;

  const onlyDigits = v => (v||'').replace(/\D/g,'');
  const phone = f.querySelector('input[name="phone"]');
  const pin   = f.querySelector('input[name="pincode"]');

  phone && phone.addEventListener('input', () => { phone.value = onlyDigits(phone.value).slice(0,10); });
  pin   && pin.addEventListener('input',   () => { pin.value   = onlyDigits(pin.value).slice(0,6);   });

  f.addEventListener('submit', (e) => {
    const ph = onlyDigits(phone?.value);
    const pc = onlyDigits(pin?.value);
    if (ph.length !== 10) { alert('Please enter a valid 10-digit phone number.'); e.preventDefault(); return; }
    if (pc && pc.length !== 6) { alert('Please enter a valid 6-digit pincode.'); e.preventDefault(); return; }
  });
})();
</script>

<?php include PUBLIC_PATH . '/partials/footer.php'; ?>
<?php include PUBLIC_PATH . '/partials/scripts.php'; ?>
