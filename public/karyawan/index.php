<?php
// public/karyawan/index.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../backend/config.php';

/* ===== Guard: hanya karyawan ===== */
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'karyawan')) {
  header('Location: ' . BASE_URL . '/public/login.html');
  exit;
}

/* ===== User info ===== */
$user = [
  'id'    => (int)($_SESSION['user_id'] ?? 0),
  'name'  => (string)($_SESSION['user_name'] ?? ''),
  'email' => (string)($_SESSION['user_email'] ?? ''),
  'role'  => (string)($_SESSION['user_role'] ?? ''),
];
$initials  = strtoupper(substr($user['name'] ?: 'U', 0, 2));
$userName  = htmlspecialchars($user['name'],  ENT_QUOTES, 'UTF-8');
$userEmail = htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8');

/* ===== KPI ringkas ===== */
$kpi = ['total_orders'=>0,'orders_today'=>0,'menu_count'=>0,'active_customers'=>0];

$res = $conn->query("SELECT COUNT(*) AS c FROM orders");
$kpi['total_orders'] = (int)($res?->fetch_assoc()['c'] ?? 0);

$today = (new DateTime('today'))->format('Y-m-d');
$stmt  = $conn->prepare("SELECT COUNT(*) AS c FROM orders WHERE DATE(created_at)=?");
$stmt->bind_param('s', $today);
$stmt->execute();
$kpi['orders_today'] = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

$res = $conn->query("SELECT COUNT(*) AS c FROM menu");
$kpi['menu_count'] = (int)($res?->fetch_assoc()['c'] ?? 0);

$res = $conn->query("SELECT COUNT(*) AS c FROM users WHERE status='active' AND role='customer'");
$kpi['active_customers'] = (int)($res?->fetch_assoc()['c'] ?? 0);

/* ===== Data chart: revenue 7 hari ===== */
$labels  = [];
$revenue = [];
$map     = [];
for ($i=6; $i>=0; $i--) {
  $d = (new DateTime("today -$i day"))->format('Y-m-d');
  $labels[] = $d;
  $map[$d]  = 0.0;
}
$sql = "
  SELECT DATE(created_at) AS d, SUM(total) AS s
  FROM orders
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
  GROUP BY DATE(created_at)
  ORDER BY d ASC
";
$res = $conn->query($sql);
while ($row = $res?->fetch_assoc()) {
  $map[$row['d']] = (float)$row['s'];
}
foreach ($labels as $d) $revenue[] = $map[$d];

/* ===== Distribusi status ===== */
$statusDist = ['paid'=>0,'pending'=>0,'overdue'=>0,'done'=>0];
$res = $conn->query("SELECT payment_status AS ps, COUNT(*) AS c FROM orders GROUP BY payment_status");
while ($row = $res?->fetch_assoc()) {
  $ps = strtolower((string)$row['ps']);
  if (isset($statusDist[$ps])) $statusDist[$ps] = (int)$row['c'];
}
$res2 = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE order_status='completed'");
if ($row2 = $res2?->fetch_assoc()) $statusDist['done'] = (int)$row2['c'];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Karyawan Desk — Caffora</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <style>
    :root{
      --gold:#ffd54f;
      --gold-200:#ffe883;
      --c-ink:#111827; 
      --c-muted:#6B7280;
      --radius:18px;
      --shadow:0 10px 25px rgba(17,24,39,.06);
    }
    body{
      background:#FAFAFA;
      color:var(--c-ink);
      font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial
    }

    /* ===== Sidebar ===== */
    .sidebar{
      width:280px;
      background:linear-gradient(180deg,var(--gold-200),var(--gold));
      border-right:1px solid rgba(0,0,0,.08);
      position:fixed; inset:0 auto 0 0;
      padding:24px 18px;
      overflow-y:auto;
    }
    .brand{ font-weight:800; font-size:24px; color:#111; }
    .nav-link{
      display:flex; align-items:center; gap:12px;
      padding:12px 14px; border-radius:12px;
      color:#111; font-weight:600;
      transition:background .2s ease, box-shadow .2s ease;
    }
    .nav-link:hover{ background:rgba(255,213,79,.25); }
    .nav-link.active{ background:var(--gold-200); box-shadow:var(--shadow); }
    .nav-link .bi{ color:#111; }
    .sidebar hr{ border-color:rgba(0,0,0,.08); opacity:1; }

    /* ===== Content ===== */
    .content{ margin-left:280px; padding:24px; }

    /* Search pill */
    .search-pill{
      background:#fff; border:1px solid #E5E7EB;
      border-radius:999px; padding:10px 14px;
      display:flex; align-items:center; gap:10px;
      box-shadow:var(--shadow);
    }
    .search-pill i{ color:var(--c-muted); }

    /* KPI & cards — border gold permanen */
 
    .kpi,.cardx{
      background:#fff;
      border:1px solid var(--gold);
      border-radius:var(--radius);
      padding:18px;
    }
    .kpi .ico{width:44px;height:44px;border-radius:12px;background:var(--gold-200);display:grid;place-items:center;}
    .chart-wrap{padding:18px;}
    .chart-wrap{ padding:18px; }

    .icon-btn{
      width:42px; height:42px;
      border-radius:50%;
      display:grid; place-items:center;
      background:#fff;
      border:1px solid #E5E7EB;
      box-shadow:var(--shadow);
    }
    .avatar{
      width:42px; height:42px; border-radius:50%;
      display:grid; place-items:center;
      background:var(--gold);
      font-weight:800; color:#111;
      border:2px solid #fff;
      box-shadow:var(--shadow);
    }
  </style>
</head>
<body id="top">

<!-- ===== Sidebar ===== -->
<aside class="sidebar">
  <div class="brand mb-2">Karyawan Desk</div>
  <hr class="my-3">
  <nav class="nav flex-column gap-2" id="sidebar-nav">
    <a class="nav-link" href="#" data-href="#top"><i class="bi bi-house-door"></i> Dashboard</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/karyawan/orders.php"><i class="bi bi-receipt"></i> Orders</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/karyawan/stock.php"><i class="bi bi-box-seam"></i> Menu Stock</a>
    <hr>
    <a class="nav-link" href="#"><i class="bi bi-question-circle"></i> Help Center</a>
    <a class="nav-link" href="<?= BASE_URL ?>/backend/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </nav>
</aside>

<!-- ===== Content ===== -->
<main class="content">
  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
    <div class="flex-grow-1">
      <div class="search-pill">
        <i class="bi bi-search"></i>
        <input class="form-control border-0 bg-transparent" placeholder="Cari pesanan, menu, atau pelanggan..." />
      </div>
    </div>
    <button class="icon-btn" id="btn-notif" title="Notifikasi"><i class="bi bi-bell"></i></button>
    <div class="dropdown">
      <button class="btn p-0 border-0" data-bs-toggle="dropdown" aria-expanded="false">
        <div class="avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow">
        <li class="px-3 py-2">
          <div class="small text-muted">Signed in as</div>
          <div class="fw-bold"><?= $userName ?></div>
          <div class="text-muted small"><?= $userEmail ?></div>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/backend/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>

  <h2 class="fw-bold mb-3">Dashboard Karyawan</h2>

  <!-- KPI -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
      <div class="kpi d-flex align-items-center gap-3">
        <div class="ico"><i class="bi bi-list-ul"></i></div>
        <div><div class="text-muted small">Total Pesanan</div><div class="fs-4 fw-bold"><?= number_format($kpi['total_orders']) ?></div></div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="kpi d-flex align-items-center gap-3">
        <div class="ico"><i class="bi bi-calendar2-day"></i></div>
        <div><div class="text-muted small">Pesanan Hari Ini</div><div class="fs-4 fw-bold"><?= number_format($kpi['orders_today']) ?></div></div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="kpi d-flex align-items-center gap-3">
        <div class="ico"><i class="bi bi-box"></i></div>
        <div><div class="text-muted small">Menu Tersedia</div><div class="fs-4 fw-bold"><?= number_format($kpi['menu_count']) ?></div></div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="kpi d-flex align-items-center gap-3">
        <div class="ico"><i class="bi bi-people"></i></div>
        <div><div class="text-muted small">Pelanggan Aktif</div><div class="fs-4 fw-bold"><?= number_format($kpi['active_customers']) ?></div></div>
      </div>
    </div>
  </div>

  <!-- Charts -->
  <div class="row g-3">
    <div class="col-12 col-xl-7">
      <div class="cardx chart-wrap">
        <h6 class="fw-bold mb-3">Revenue 7 Hari Terakhir</h6>
        <canvas id="revChart" height="140"></canvas>
      </div>
    </div>
    <div class="col-12 col-xl-5">
      <div class="cardx chart-wrap">
        <h6 class="fw-bold mb-3">Distribusi Status Pesanan</h6>
        <canvas id="distChart" height="140"></canvas>
      </div>
    </div>
  </div>
</main>

<script>
/* Sidebar aktif hanya saat diklik */
document.querySelectorAll('#sidebar-nav .nav-link').forEach(a=>{
  a.addEventListener('click', function(){
    document.querySelectorAll('#sidebar-nav .nav-link').forEach(l=>l.classList.remove('active'));
    this.classList.add('active');
  });
});

const labels  = <?= json_encode($labels, JSON_UNESCAPED_SLASHES) ?>;
const revenue = <?= json_encode($revenue, JSON_UNESCAPED_SLASHES) ?>;
const dist    = <?= json_encode(array_values($statusDist), JSON_UNESCAPED_SLASHES) ?>;

new Chart(document.getElementById('revChart'), {
  type: 'line',
  data: {
    labels,
    datasets: [{
      label:'Revenue',
      data:revenue,
      tension:.35, fill:true,
      borderColor:'#ffd54f',
      backgroundColor:'rgba(255,213,79,.18)',
      pointRadius:4,
      pointBackgroundColor:'#ffd54f',
      pointBorderColor:'#ffd54f'
    }]
  },
  options: {
    plugins:{ legend:{ display:false } },
    scales:{
      y:{ ticks:{ callback:v => new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(v) },
          grid:{ color:'rgba(17,24,39,.06)' } },
      x:{ grid:{ display:false } }
    }
  }
});

new Chart(document.getElementById('distChart'), {
  type:'doughnut',
  data:{ labels:['Paid','Pending','Overdue','Done'], datasets:[{ data:dist, borderWidth:0, backgroundColor:['#22c55e','#f59e0b','#ef4444','#3b82f6'] }] },
  options:{ plugins:{ legend:{ position:'bottom' } } }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>