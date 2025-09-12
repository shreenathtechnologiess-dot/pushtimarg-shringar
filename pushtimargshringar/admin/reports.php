<?php
// admin/reports.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';

// ✅ Fix: map $conn → $con (since db.php uses $conn)
if (!isset($con) && isset($conn)) {
    $con = $conn;
}

/* ---------- helpers ---------- */
function hasColumn(mysqli $con, string $table, string $col): bool {
  $sql = "SELECT 1
          FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
          LIMIT 1";
  $st = $con->prepare($sql);
  $st->bind_param('ss', $table, $col);
  $st->execute();
  $ok = (bool)$st->get_result()->fetch_row();
  $st->close();
  return $ok;
}

function json_error(string $msg, int $code = 500){
  while (ob_get_level()) { ob_end_clean(); }
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error'=>$msg], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ---------- API ---------- */
if (isset($_GET['api'])) {
  while (ob_get_level()) { ob_end_clean(); }
  header('Content-Type: application/json; charset=utf-8');
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

  $DEBUG = isset($_GET['debug']) && $_GET['debug']=='1';

  try {
    $con->set_charset('utf8mb4');

    $hasProdCatId  = hasColumn($con,'products','category_id');
    $hasProdCatTxt = hasColumn($con,'products','category');
    $hasCatSlug    = hasColumn($con,'categories','slug');
    $hasProdCost   = hasColumn($con,'products','cost');

    if ($hasProdCatId) {
      $JOIN_CAT        = "INNER JOIN categories c ON c.id = p.category_id";
      $CAT_FILTER_EXPR = "p.category_id = ?";
    } elseif ($hasProdCatTxt && $hasCatSlug) {
      $JOIN_CAT        = "INNER JOIN categories c ON c.slug = p.category";
      $CAT_FILTER_EXPR = "c.id = ?";
    } elseif ($hasProdCatTxt) {
      $JOIN_CAT        = "INNER JOIN categories c ON c.name = p.category";
      $CAT_FILTER_EXPR = "c.id = ?";
    } else {
      $JOIN_CAT        = "LEFT JOIN categories c ON 1=0";
      $CAT_FILTER_EXPR = "0";
    }

    $COST_EXPR = $hasProdCost
      ? "IFNULL(p.cost, GREATEST(0, oi.price-250))"
      : "GREATEST(0, oi.price-250)";

    $from = trim($_GET['from'] ?? '');
    $to   = trim($_GET['to'] ?? '');
    $st   = trim($_GET['status'] ?? '');
    $cat  = (int)($_GET['category_id'] ?? 0);

    $where  = [];
    $types  = '';
    $params = [];

    if ($from !== '') { $where[] = "DATE(o.created_at) >= ?"; $types.='s'; $params[]=$from; }
    if ($to   !== '') { $where[] = "DATE(o.created_at) <= ?"; $types.='s'; $params[]=$to; }
    if ($st   !== '') { $where[] = "UPPER(o.status) = ?";     $types.='s'; $params[]=strtoupper($st); }

    $catJoin = '';
    if ($cat > 0) {
      $catJoin = "INNER JOIN (
                    SELECT DISTINCT oi.order_id
                    FROM order_items oi
                    INNER JOIN products p ON p.id = oi.product_id
                    $JOIN_CAT
                    WHERE $CAT_FILTER_EXPR
                  ) oc ON oc.order_id = o.id";
      $types = 'i'.$types;
      array_unshift($params, $cat);
    }

    $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

    /* ==== Orders list ==== */
    $sql = "SELECT o.id, o.name, o.total_amount, UPPER(o.status) status, DATE(o.created_at) d
            FROM orders o
            $catJoin
            $whereSql
            ORDER BY o.id DESC
            LIMIT 1000";
    $st1 = $con->prepare($sql);
    if ($types) { $st1->bind_param($types, ...$params); }
    $st1->execute();
    $res = $st1->get_result();
    $orders = [];
    while ($r = $res->fetch_assoc()) {
      $orders[] = [
        'id'      => (int)$r['id'],
        'orderNo' => '#PS'.(int)$r['id'],
        'customer'=> (string)($r['name'] ?? ''),
        'amount'  => (float)($r['total_amount'] ?? 0),
        'status'  => (string)$r['status'],
        'date'    => (string)$r['d'],
      ];
    }
    $st1->close();

    /* ==== KPIs ==== */
    $total = count($orders);
    $delivered = 0; $revenue = 0.0;
    foreach ($orders as $o){ if ($o['status']==='DELIVERED') $delivered++; $revenue += $o['amount']; }

    /* ==== Estimated Profit ==== */
    $profitSql = "SELECT SUM( (oi.price - {$COST_EXPR}) * oi.qty ) prof
                  FROM orders o
                  $catJoin
                  INNER JOIN order_items oi ON oi.order_id = o.id
                  INNER JOIN products p ON p.id = oi.product_id
                  $whereSql";
    $st2 = $con->prepare($profitSql);
    if ($types) { $st2->bind_param($types, ...$params); }
    $st2->execute();
    $estProfit = (float)($st2->get_result()->fetch_assoc()['prof'] ?? 0);
    $st2->close();

    /* ==== Orders by Day ==== */
    $byDaySql = "SELECT DATE(o.created_at) d, SUM(o.total_amount) amt
                 FROM orders o
                 $catJoin
                 $whereSql
                 GROUP BY DATE(o.created_at)
                 ORDER BY DATE(o.created_at)";
    $st3 = $con->prepare($byDaySql);
    if ($types) { $st3->bind_param($types, ...$params); }
    $st3->execute();
    $res = $st3->get_result(); $byDay = [];
    while ($r = $res->fetch_assoc()) $byDay[$r['d']] = (float)$r['amt'];
    $st3->close();

    /* ==== Sales by Category ==== */
    $catSql = "SELECT c.name category, SUM(oi.qty * oi.price) amt
               FROM orders o
               $catJoin
               INNER JOIN order_items oi ON oi.order_id = o.id
               INNER JOIN products p ON p.id = oi.product_id
               $JOIN_CAT
               $whereSql
               GROUP BY c.id
               ORDER BY amt DESC";
    $st4 = $con->prepare($catSql);
    if ($types) { $st4->bind_param($types, ...$params); }
    $st4->execute();
    $res = $st4->get_result(); $categorySales = [];
    while ($r = $res->fetch_assoc()) $categorySales[] = ['category'=>$r['category'], 'amount'=>(float)$r['amt']];
    $st4->close();

    /* ==== Top Products ==== */
    $topSql = "SELECT p.name, SUM(oi.qty * oi.price) val
               FROM orders o
               $catJoin
               INNER JOIN order_items oi ON oi.order_id = o.id
               INNER JOIN products p ON p.id = oi.product_id
               $whereSql
               GROUP BY p.id
               ORDER BY val DESC
               LIMIT 5";
    $st5 = $con->prepare($topSql);
    if ($types) { $st5->bind_param($types, ...$params); }
    $st5->execute();
    $res = $st5->get_result(); $topProducts = [];
    while ($r = $res->fetch_assoc()) $topProducts[] = ['name'=>$r['name'], 'potential'=>(float)$r['val']];
    $st5->close();

    echo json_encode([
      'metrics'=>[
        'total'=>$total,
        'delivered'=>$delivered,
        'revenue'=>$revenue,
        'estProfit'=>max(0,$estProfit)
      ],
      'orders'=>$orders,
      'byDay'=>$byDay,
      'categorySales'=>$categorySales,
      'topProducts'=>$topProducts
    ], JSON_UNESCAPED_UNICODE);
    exit;

  } catch (Throwable $e) {
    $msg = $DEBUG ? ($e->getMessage().' @ '.$e->getFile().':'.$e->getLine()) : 'Server error';
    json_error($msg, 500);
  }
}

/* ---------- Page (HTML) ---------- */
$title = 'Reports';
$pageTitle = 'Reports';
$active = 'reports';

// header actions
ob_start(); ?>
  <button id="exportJson" class="btn">Export JSON</button>
  <button id="exportXlsx" class="btn btn-primary">Export Excel</button>
<?php $headerActions = ob_get_clean();

include __DIR__.'/includes/head.php';
include __DIR__.'/includes/start_layout.php';

/* categories for filter */
$catRows = [];
try {
  $st = $con->prepare("SELECT id, name FROM categories ORDER BY name");
  $st->execute(); $catRows = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
} catch (Throwable $e) { }
?>
<section class="px-4 sm:px-6 lg:px-8 py-6 space-y-6">
  <!-- KPIs -->
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="card"><div class="text-xs text-gray-500">Total Orders</div><div id="k_total" class="text-2xl font-semibold">0</div></div>
    <div class="card"><div class="text-xs text-gray-500">Delivered</div><div id="k_delivered" class="text-2xl font-semibold">0</div></div>
    <div class="card"><div class="text-xs text-gray-500">Revenue</div><div id="k_revenue" class="text-2xl font-semibold">₹0</div></div>
    <div class="card"><div class="text-xs text-gray-500">Estimated Profit</div><div id="k_profit" class="text-2xl font-semibold">₹0</div></div>
  </div>

  <!-- Filters -->
  <div class="card">
    <div class="grid md:grid-cols-5 gap-3">
      <div>
        <label class="block text-sm mb-1">From</label>
        <input id="f_from" type="date" class="w-full border rounded-lg px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm mb-1">To</label>
        <input id="f_to" type="date" class="w-full border rounded-lg px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm mb-1">Status</label>
        <select id="f_status" class="w-full border rounded-lg px-3 py-2">
          <option value="">All</option>
          <option>DELIVERED</option>
          <option>PROCESSING</option>
          <option>PENDING</option>
          <option>CANCELLED</option>
        </select>
      </div>
      <div>
        <label class="block text-sm mb-1">Category</label>
        <select id="f_category" class="w-full border rounded-lg px-3 py-2">
          <option value="">All</option>
          <?php foreach ($catRows as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex items-end gap-2">
        <button id="apply" class="btn btn-primary w-full">Apply</button>
        <button id="clear" class="btn w-full">Clear</button>
      </div>
    </div>
    <p class="text-xs text-gray-600 mt-3">Note: Profit yahan <b>Estimated</b> hai.</p>
  </div>

  <!-- Charts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <div class="grid lg:grid-cols-3 gap-6">
    <div class="card"><div class="mb-2">Orders by Day</div><canvas id="c_orders"></canvas></div>
    <div class="card"><div class="mb-2">Sales by Category</div><canvas id="c_category"></canvas></div>
    <div class="card"><div class="mb-2">Top 5 Products (Potential ₹)</div><canvas id="c_top"></canvas></div>
  </div>

  <!-- Table -->
  <div class="card overflow-auto">
    <div class="text-xl mb-3">Filtered Orders</div>
    <table class="w-full table">
      <thead>
        <tr class="border-b">
          <th class="py-2">Order</th>
          <th class="py-2">Customer</th>
          <th class="py-2">Amount</th>
          <th class="py-2">Status</th>
          <th class="py-2">Date</th>
        </tr>
      </thead>
      <tbody id="rows"></tbody>
    </table>
  </div>
</section>

<?php include __DIR__.'/includes/end_layout.php'; ?>

<script>
  const byId = id => document.getElementById(id);
  const fmt  = n => '₹' + Number(n||0).toLocaleString('en-IN');
  let chOrders, chCategory, chTop;

  function fetchData(){
    const p = new URLSearchParams({ api:'1', debug:'1' });
    const from = byId('f_from').value;
    const to   = byId('f_to').value;
    const st   = byId('f_status').value;
    const cat  = byId('f_category').value;
    if (from) p.set('from', from);
    if (to)   p.set('to', to);
    if (st)   p.set('status', st);
    if (cat)  p.set('category_id', cat);

    return fetch('reports.php?'+p.toString())
      .then(r => r.json().catch(async () => {
        const t = await r.text();
        alert('Failed to load reports: ' + t.slice(0, 1000));
        throw new Error('Non-JSON response');
      }));
  }

  function renderKPIs(m){
    byId('k_total').textContent     = m.total;
    byId('k_delivered').textContent = m.delivered;
    byId('k_revenue').textContent   = fmt(m.revenue);
    byId('k_profit').textContent    = fmt(m.estProfit);
  }

  function renderTable(list){
    byId('rows').innerHTML = list.map(o => `
      <tr class="border-b">
        <td class="py-3 pr-3">${o.orderNo}</td>
        <td class="py-3 pr-3">${o.customer||''}</td>
        <td class="py-3 pr-3">${fmt(o.amount||0)}</td>
        <td class="py-3 pr-3">${o.status}</td>
        <td class="py-3 pr-3">${new Date(o.date).toLocaleDateString('en-IN',{year:'numeric',month:'short',day:'2-digit'})}</td>
      </tr>`).join('');
  }

  function renderCharts(byDay, catSales, topProducts){
    const days = Object.keys(byDay).sort();
    const dayVals = days.map(k => byDay[k]);
    const catLabels = catSales.map(r => r.category), catVals = catSales.map(r => r.amount);
    const topLabels = topProducts.map(r => r.name), topVals = topProducts.map(r => r.potential);

    if (chOrders) chOrders.destroy(); if (chCategory) chCategory.destroy(); if (chTop) chTop.destroy();

    chOrders = new Chart(document.getElementById('c_orders'), {
      type:'line',
      data:{labels:days, datasets:[{label:'Revenue (₹)', data:dayVals, tension:.25}]},
      options:{plugins:{legend:{display:false}}, scales:{y:{ticks:{callback:v=>'₹'+v}}}}
    });
    chCategory = new Chart(document.getElementById('c_category'), {
      type:'doughnut',
      data:{labels:catLabels, datasets:[{data:catVals}]},
      options:{plugins:{legend:{position:'bottom'}}}
    });
    chTop = new Chart(document.getElementById('c_top'), {
      type:'bar',
      data:{labels:topLabels, datasets:[{label:'Potential (₹)', data:topVals}]},
      options:{plugins:{legend:{display:false}}, scales:{y:{ticks:{callback:v=>'₹'+v}}}}
    });
  }

  function loadAndRender(){
    fetchData().then(d=>{
      if (d.error) { alert(d.error); return; }
      renderKPIs(d.metrics);
      renderTable(d.orders);
      renderCharts(d.byDay, d.categorySales, d.topProducts);
    });
  }

  document.getElementById('apply').addEventListener('click', loadAndRender);
  document.getElementById('clear').addEventListener('click', ()=>{
    byId('f_from').value=''; byId('f_to').value=''; byId('f_status').value=''; byId('f_category').value='';
    loadAndRender();
  });

  loadAndRender();
</script
