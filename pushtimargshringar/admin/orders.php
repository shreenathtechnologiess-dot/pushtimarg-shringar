<?php
// admin/orders.php
session_start();
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function format_price_local($n){ return '₹ ' . number_format((float)$n, 2); }

if (!isset($conn) || !($conn instanceof mysqli)) die("DB connection missing");

// --- Handle status update ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='update_status') {
  $id = (int)$_POST['id'];
  $status = $_POST['status'] ?? 'Processing';
  $allowed = ['Pending','Processing','Shipped','Delivered','Cancelled'];
  if (!in_array($status,$allowed)) $status='Processing';
  $stmt=$conn->prepare("UPDATE orders SET status=? WHERE id=?");
  $stmt->bind_param("si",$status,$id);
  $stmt->execute();
  $stmt->close();
  $_SESSION['flash_success']="Order #$id updated to $status";
  header("Location: orders.php"); exit;
}

// --- Filters ---
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$where="WHERE 1=1"; $params=[]; $types="";
if($search!==''){
  $where.=" AND (order_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR mobile LIKE ?)";
  $s="%$search%"; $params=[$s,$s,$s,$s]; $types="ssss";
}
if($statusFilter!==''){
  $where.=" AND status=?"; $params[]=$statusFilter; $types.="s";
}

// --- Fetch orders ---
$sql="SELECT id, order_number, first_name,last_name,mobile,total_amount,payment_method,status,created_at 
      FROM orders $where ORDER BY created_at DESC";
$stmt=$conn->prepare($sql);
if($params){ $bind=[$types]; foreach($params as $i=>$v) $bind[]=&$params[$i]; call_user_func_array([$stmt,'bind_param'],$bind);}
$stmt->execute();
$res=$stmt->get_result();
$orders=$res?$res->fetch_all(MYSQLI_ASSOC):[];
$stmt->close();

$statuses=['Pending','Processing','Shipped','Delivered','Cancelled'];

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/start_layout.php';
?>

<div class="p-6">
  <h1 class="text-2xl font-bold text-deepgreen mb-6">Orders</h1>

  <?php if(!empty($_SESSION['flash_success'])): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded"><?=h($_SESSION['flash_success']); unset($_SESSION['flash_success']);?></div>
  <?php endif; ?>

  <!-- Filters -->
  <form method="get" class="mb-4 flex gap-4">
    <input type="text" name="search" placeholder="Search Order/Customer/Mobile" value="<?=h($search)?>" class="input flex-1">
    <select name="status" class="input">
      <option value="">All Status</option>
      <?php foreach($statuses as $st): ?>
        <option value="<?=$st?>" <?=$st==$statusFilter?'selected':''?>><?=$st?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary">Filter</button>
    <a href="orders.php" class="btn btn-ghost">Reset</a>
  </form>

  <!-- Table -->
  <div class="card overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-3 py-2">Order #</th>
          <th class="px-3 py-2">Customer</th>
          <th class="px-3 py-2">Mobile</th>
          <th class="px-3 py-2">Total</th>
          <th class="px-3 py-2">Payment</th>
          <th class="px-3 py-2">Status</th>
          <th class="px-3 py-2">Date</th>
          <th class="px-3 py-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($orders)): ?>
          <tr><td colspan="8" class="px-3 py-4 text-center text-gray-500">No orders found.</td></tr>
        <?php else: foreach($orders as $o): ?>
          <tr class="border-t">
            <td class="px-3 py-2"><?=h($o['order_number'])?></td>
            <td class="px-3 py-2"><?=h($o['first_name'].' '.$o['last_name'])?></td>
            <td class="px-3 py-2"><?=h($o['mobile'])?></td>
            <td class="px-3 py-2"><?=format_price_local($o['total_amount'])?></td>
            <td class="px-3 py-2"><?=h($o['payment_method'])?></td>
            <td class="px-3 py-2">
              <form method="post">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" value="<?=$o['id']?>">
                <select name="status" onchange="this.form.submit()" class="input">
                  <?php foreach($statuses as $st): ?>
                    <option value="<?=$st?>" <?=$o['status']===$st?'selected':''?>><?=$st?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td class="px-3 py-2"><?=date('d M Y, g:i a',strtotime($o['created_at']))?></td>
            <td class="px-3 py-2"><a href="order_view.php?id=<?=$o['id']?>" class="text-blue-600">View</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/end_layout.php'; ?>
