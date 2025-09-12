<?php
// admin/order_view.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Database connection (\$conn) not available.");
}

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function format_price_local($n){ return '₹ ' . number_format((float)$n, 2); }

// allowed statuses (you can extend)
$statuses = ['Pending','Processing','Shipped','Delivered','Cancelled'];

/**
 * Ensure orders table has commonly used numeric columns.
 * This tries to add missing columns; on failure it silently continues.
 */
function ensure_order_columns($conn) {
  $needed = [
    'shipping_amount' => "DECIMAL(10,2) NOT NULL DEFAULT 0",
    'discount_amount' => "DECIMAL(10,2) NOT NULL DEFAULT 0",
    'subtotal'        => "DECIMAL(10,2) NOT NULL DEFAULT 0",
    'total_amount'    => "DECIMAL(10,2) NOT NULL DEFAULT 0"
  ];

  // determine db name
  $dbRow = $conn->query("SELECT DATABASE() AS dbname")->fetch_assoc();
  $dbName = $dbRow['dbname'] ?? null;
  if (!$dbName) return;

  foreach ($needed as $col => $def) {
    $sql = "SELECT COUNT(*) AS cnt FROM information_schema.columns
            WHERE table_schema = '".$conn->real_escape_string($dbName)."'
              AND table_name = 'orders'
              AND column_name = '".$conn->real_escape_string($col)."'";
    $res = $conn->query($sql);
    $exists = false;
    if ($res) {
      $exists = ((int)$res->fetch_assoc()['cnt'] > 0);
      $res->free();
    }
    if (!$exists) {
      // try to add column (ignore failure)
      $alter = "ALTER TABLE orders ADD COLUMN {$col} {$def}";
      @$conn->query($alter);
    }
  }
}
ensure_order_columns($conn);

// get order id
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid order id.");

/* ------------------ POST handling ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // remove item
  if ($action === 'remove_item' && !empty($_POST['item_id'])) {
    $itemId = (int)$_POST['item_id'];
    $stmt = $conn->prepare("DELETE FROM order_items WHERE id = ? AND order_id = ?");
    if ($stmt) {
      $stmt->bind_param('ii', $itemId, $id);
      if ($stmt->execute()) $_SESSION['flash_success'] = "Item removed.";
      else $_SESSION['flash_error'] = "Failed to remove item: " . $stmt->error;
      $stmt->close();
    } else {
      $_SESSION['flash_error'] = "Prepare failed: " . $conn->error;
    }
    header("Location: order_view.php?id={$id}");
    exit;
  }

  // save order (items + order-level fields)
  if ($action === 'save_order') {
    // collect order fields
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $mobile     = trim($_POST['mobile'] ?? '');
    $address1   = trim($_POST['address1'] ?? '');
    $address2   = trim($_POST['address2'] ?? '');
    $state      = trim($_POST['state'] ?? '');
    $pincode    = trim($_POST['pincode'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');
    $upi_id         = trim($_POST['upi_id'] ?? '');
    $status         = trim($_POST['status'] ?? $statuses[0]);
    if (!in_array($status, $statuses)) $status = $statuses[0];

    $shipping_amount = isset($_POST['shipping_amount']) && $_POST['shipping_amount'] !== '' ? (float)$_POST['shipping_amount'] : 0.0;
    $discount_amount = isset($_POST['discount_amount']) && $_POST['discount_amount'] !== '' ? (float)$_POST['discount_amount'] : 0.0;

    // items arrays
    $item_ids = $_POST['item_id'] ?? [];
    $qtys = $_POST['quantity'] ?? [];
    $unit_prices = $_POST['unit_price'] ?? [];

    // update each item (or delete if qty zero)
    $subtotal = 0.0;
    for ($i = 0; $i < count($item_ids); $i++) {
      $itId = (int)$item_ids[$i];
      $q = max(0, (int)($qtys[$i] ?? 0));
      $up = isset($unit_prices[$i]) && $unit_prices[$i] !== '' ? (float)$unit_prices[$i] : 0.0;

      if ($q <= 0) {
        $stmt = $conn->prepare("DELETE FROM order_items WHERE id = ? AND order_id = ?");
        if ($stmt) { $stmt->bind_param('ii', $itId, $id); $stmt->execute(); $stmt->close(); }
        continue;
      }

      // update actual columns in your table: qty and price
      $stmt = $conn->prepare("UPDATE order_items SET qty = ?, price = ? WHERE id = ? AND order_id = ?");
      if ($stmt) {
        // types: i (qty), d (price), i (itId), i (order id)
        $stmt->bind_param('idii', $q, $up, $itId, $id);
        $stmt->execute();
        $stmt->close();
      }
      $subtotal += $q * $up;
    }

    // if no items in POST (maybe removed via separate action), compute subtotal from DB
    if (count($item_ids) === 0) {
      $res = $conn->query("SELECT SUM(price * qty) AS s FROM order_items WHERE order_id = " . (int)$id);
      $row = $res ? $res->fetch_assoc() : null;
      $subtotal = (float)($row['s'] ?? 0.0);
    }

    $total_amount = $subtotal + $shipping_amount - $discount_amount;
    if ($total_amount < 0) $total_amount = 0.0;

    // Build update only with columns that exist in table to avoid unknown column errors
    // Check columns
    $existingCols = [];
    $dbRow = $conn->query("SELECT DATABASE() AS dbname")->fetch_assoc();
    $dbName = $dbRow['dbname'] ?? null;
    if ($dbName) {
      $q = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_schema='".$conn->real_escape_string($dbName)."' AND table_name='orders'");
      if ($q) {
        while ($r = $q->fetch_assoc()) $existingCols[] = $r['column_name'];
        $q->free();
      }
    }

    // prepare fields to update
    $fields = [
      'first_name' => $first_name,
      'last_name'  => $last_name,
      'mobile'     => $mobile,
      'address1'   => $address1,
      'address2'   => $address2,
      'state'      => $state,
      'pincode'    => $pincode,
      'payment_method' => $payment_method,
      'upi_id'         => $upi_id
    ];

    // numeric fields (conditionally included if column exists)
    if (in_array('shipping_amount', $existingCols)) $fields['shipping_amount'] = $shipping_amount;
    if (in_array('discount_amount', $existingCols)) $fields['discount_amount'] = $discount_amount;
    if (in_array('subtotal', $existingCols)) $fields['subtotal'] = $subtotal;
    if (in_array('total_amount', $existingCols)) $fields['total_amount'] = $total_amount;

    // status always attempted (if column exists)
    if (in_array('status', $existingCols)) $fields['status'] = $status;

    // build dynamic update
    $setParts = [];
    $types = '';
    $values = [];
    foreach ($fields as $col => $val) {
      $setParts[] = "{$col} = ?";
      // choose type
      if (in_array($col, ['shipping_amount','discount_amount','subtotal','total_amount'])) $types .= 'd';
      else $types .= 's';
      $values[] = $val;
    }
    // final where id
    $types .= 'i';
    $values[] = $id;

    if (empty($setParts)) {
      $_SESSION['flash_error'] = "No columns to update on orders table.";
      header("Location: order_view.php?id={$id}");
      exit;
    }

    $sql = "UPDATE orders SET " . implode(', ', $setParts) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
      $_SESSION['flash_error'] = "Prepare failed: " . $conn->error;
      header("Location: order_view.php?id={$id}");
      exit;
    }

    // bind dynamically (must pass variables by reference)
    $bind_names = [];
    $bind_names[] = $types;
    for ($i=0; $i<count($values); $i++) {
      $bind_names[] = &$values[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);

    $ok = $stmt->execute();
    if ($ok) $_SESSION['flash_success'] = "Order saved. Total: " . format_price_local($total_amount);
    else $_SESSION['flash_error'] = "Save failed: " . $stmt->error;
    $stmt->close();

    header("Location: order_view.php?id={$id}");
    exit;
  }
}

/* ------------------ Fetch order & items for display ------------------ */
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$orderRes = $stmt->get_result();
$order = $orderRes ? $orderRes->fetch_assoc() : null;
$stmt->close();
if (!$order) die("Order not found.");

// items: use actual columns from order_items (product_name, price, qty) and alias to expected keys
$stmt = $conn->prepare("SELECT id, order_id, product_name AS name, price AS unit_price, qty AS quantity, product_id FROM order_items WHERE order_id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$items = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/start_layout.php';
?>

<style>
/* Small visual tweaks */
.order-grid { display:grid; grid-template-columns: 1fr; gap:20px; }
@media (min-width: 1100px) { .order-grid { grid-template-columns: 360px 1fr 360px; } }
.table-sm th, .table-sm td { padding:10px 12px; }
.table-sm thead th { background:#fafafa; color:#374151; font-weight:600; border-bottom:1px solid #eee; }
.summary-box { background:#fff; padding:16px; border-radius:8px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
.small-muted { color:#6b7280; font-size:0.95rem; }
.input-inline { display:inline-block; width:120px; }
.text-right { text-align:right; }
.label-compact { font-size:0.9rem; color:#374151; display:block; margin-bottom:6px; }
.input { width:100%; padding:8px; border:1px solid #e6e1de; border-radius:6px; }
.btn { background:#7f1d1d; color:#fff; padding:8px 12px; border-radius:6px; border:none; cursor:pointer; }
.btn-ghost { background:transparent; border:1px solid #ddd; padding:8px 12px; border-radius:6px; cursor:pointer; }
.card { background:#fff; border-radius:8px; padding:14px; box-shadow:0 1px 0 rgba(0,0,0,0.03); }
</style>

<div class="p-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-deepgreen">Order #<?= h($order['order_number']) ?> — Edit</h1>
    <div>
      <a href="orders.php" class="btn btn-ghost">← Back to orders</a>
    </div>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-800 rounded"><?= h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <form method="post" id="orderEditForm">
    <input type="hidden" name="action" value="save_order">

    <div class="order-grid">
      <!-- LEFT: Customer & Shipping -->
      <div class="summary-box">
        <h3 class="font-semibold mb-3">Customer & Shipping</h3>
        <div class="mb-3"><label class="label-compact">First Name</label><input name="first_name" class="input" value="<?= h($order['first_name']) ?>"></div>
        <div class="mb-3"><label class="label-compact">Last Name</label><input name="last_name" class="input" value="<?= h($order['last_name']) ?>"></div>
        <div class="mb-3"><label class="label-compact">Mobile</label><input name="mobile" class="input" value="<?= h($order['mobile']) ?>"></div>
        <div class="mb-3"><label class="label-compact">Address line 1</label><input name="address1" class="input" value="<?= h($order['address1']) ?>"></div>
        <div class="mb-3"><label class="label-compact">Address line 2</label><input name="address2" class="input" value="<?= h($order['address2']) ?>"></div>
        <div style="display:flex;gap:10px">
          <div style="flex:1"><label class="label-compact">State</label><input name="state" class="input" value="<?= h($order['state']) ?>"></div>
          <div style="width:120px"><label class="label-compact">Pincode</label><input name="pincode" class="input" value="<?= h($order['pincode']) ?>"></div>
        </div>
      </div>

      <!-- CENTER: Items editable -->
      <div class="card p-4">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h3 class="font-semibold">Order Items</h3>
            <div class="small-muted">Edit quantity or unit price — totals recalc instantly.</div>
          </div>
          <div class="small-muted">Order #: <strong><?= h($order['order_number']) ?></strong></div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full table-sm" id="itemsTable">
            <thead>
              <tr>
                <th class="text-left">Product</th>
                <th class="text-left">Unit Price</th>
                <th class="text-left">Qty</th>
                <th class="text-right">Line Total</th>
                <th class="text-center">Remove</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">No items found.</td></tr>
              <?php else: foreach ($items as $itIndex => $it): ?>
                <?php
                  $unit = number_format((float)$it['unit_price'], 2, '.', '');
                  $qty = (int)$it['quantity'];
                  $line = $unit * $qty;
                ?>
                <tr data-item-id="<?= (int)$it['id'] ?>">
                  <td class="px-3 py-3" style="min-width:220px;">
                    <div class="font-medium"><?= h($it['name'] ?? 'Item') ?></div>
                    <input type="hidden" name="item_id[]" value="<?= (int)$it['id'] ?>">
                  </td>

                  <td class="px-3 py-3">
                    <input type="number" step="0.01" name="unit_price[]" value="<?= $unit ?>" class="input unit-price" style="width:130px;">
                  </td>

                  <td class="px-3 py-3">
                    <input type="number" name="quantity[]" value="<?= $qty ?>" class="input qty-input" style="width:90px;">
                  </td>

                  <td class="px-3 py-3 text-right line-total"><?= format_price_local($line) ?></td>

                  <td class="px-3 py-3 text-center">
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="action" value="remove_item">
                      <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>">
                      <button type="submit" class="text-red-600" onclick="return confirm('Remove this item?')">Remove</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
          <div>
            <label class="label-compact">Shipping amount</label>
            <input id="shippingInput" name="shipping_amount" type="number" step="0.01" class="input" value="<?= number_format((float)($order['shipping_amount'] ?? 0),2,'.','') ?>">
          </div>
          <div>
            <label class="label-compact">Discount amount</label>
            <input id="discountInput" name="discount_amount" type="number" step="0.01" class="input" value="<?= number_format((float)($order['discount_amount'] ?? 0),2,'.','') ?>">
          </div>
          <div>
            <label class="label-compact">Status</label>
            <select name="status" class="input">
              <?php foreach ($statuses as $st): ?>
                <option value="<?= h($st) ?>" <?= ($order['status'] === $st) ? 'selected' : '' ?>><?= h($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-4">
          <button type="submit" class="btn">Save changes</button>
          <a href="orders.php" class="btn btn-ghost">Back</a>
        </div>
      </div>

      <!-- RIGHT: Payment & Summary -->
      <div class="summary-box">
        <h3 class="font-semibold mb-3">Payment</h3>
        <div class="mb-3">
          <label class="label-compact">Payment method</label>
          <select name="payment_method" class="input">
            <option value="cod" <?= ($order['payment_method'] ?? '') === 'cod' ? 'selected' : '' ?>>Cash on Delivery</option>
            <option value="upi" <?= ($order['payment_method'] ?? '') === 'upi' ? 'selected' : '' ?>>UPI</option>
            <option value="online" <?= ($order['payment_method'] ?? '') === 'online' ? 'selected' : '' ?>>Online</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="label-compact">UPI / Transaction id</label>
          <input name="upi_id" class="input" value="<?= h($order['upi_id'] ?? '') ?>">
        </div>

        <hr class="my-3">

        <h4 class="font-semibold mb-2">Order Summary</h4>
        <div class="flex justify-between mb-1"><div class="text-sm text-gray-600">Subtotal</div><div id="subtotalText"><?= format_price_local((float)($order['subtotal'] ?? 0)) ?></div></div>
        <div class="flex justify-between mb-1"><div class="text-sm text-gray-600">Shipping</div><div id="shippingText"><?= format_price_local((float)($order['shipping_amount'] ?? 0)) ?></div></div>
        <div class="flex justify-between mb-1"><div class="text-sm text-gray-600">Discount</div><div id="discountText">- <?= format_price_local((float)($order['discount_amount'] ?? 0)) ?></div></div>
        <hr class="my-3">
        <div class="flex justify-between font-bold text-lg"><div>Total</div><div id="totalText"><?= format_price_local((float)($order['total_amount'] ?? 0)) ?></div></div>
      </div>
    </div>
  </form>
</div>

<script>
// Client-side live recalculation for items + totals
(function(){
  const rows = Array.from(document.querySelectorAll('#itemsTable tbody tr[data-item-id]'));
  const shippingInput = document.getElementById('shippingInput');
  const discountInput = document.getElementById('discountInput');
  const subtotalText = document.getElementById('subtotalText');
  const shippingText = document.getElementById('shippingText');
  const discountText = document.getElementById('discountText');
  const totalText = document.getElementById('totalText');

  function parseNumber(v){ v = String(v).trim(); if (v==='') return 0; return parseFloat(v) || 0; }
  function fmt(v){ return '₹ ' + Number(v).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}); }

  function recalcLine(row){
    const priceInput = row.querySelector('.unit-price');
    const qtyInput = row.querySelector('.qty-input');
    const lineTotalEl = row.querySelector('.line-total');

    const price = parseNumber(priceInput.value);
    const qty = Math.max(0, Math.floor(parseNumber(qtyInput.value)));
    const line = price * qty;
    lineTotalEl.textContent = fmt(line);
    return line;
  }

  function recalcAll(){
    let subtotal = 0;
    rows.forEach(r => subtotal += recalcLine(r));
    const shipping = parseNumber(shippingInput.value);
    const discount = parseNumber(discountInput.value);
    const total = Math.max(0, subtotal + shipping - discount);

    subtotalText.textContent = fmt(subtotal);
    shippingText.textContent = fmt(shipping);
    discountText.textContent = '- ' + fmt(discount);
    totalText.textContent = fmt(total);
  }

  // Attach events
  rows.forEach(r => {
    const price = r.querySelector('.unit-price');
    const qty = r.querySelector('.qty-input');
    if (price) price.addEventListener('input', recalcAll);
    if (qty) qty.addEventListener('input', recalcAll);
  });
  if (shippingInput) shippingInput.addEventListener('input', recalcAll);
  if (discountInput) discountInput.addEventListener('input', recalcAll);

  // initial calc
  recalcAll();
})();
</script>

<?php
require_once __DIR__ . '/includes/end_layout.php';
