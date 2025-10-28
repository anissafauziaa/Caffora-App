<?php 
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../backend/config.php';

/* ===== Guard role: hanya admin ===== */
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
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

/* ===== Data chart ===== */
$labels = [];
$revenue = [];
$map = [];
for ($i=6; $i>=0; $i--) {
  $d = (new DateTime("today -$i day"))->format('Y-m-d');
  $labels[] = $d;
  $map[$d] = 0.0;
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
  if ($ps === 'paid' || $ps === 'pending' || $ps === 'overdue') {
    $statusDist[$ps] = (int)$row['c'];
  }
}
$res2 = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE order_status = 'completed'");
if ($row2 = $res2?->fetch_assoc()) $statusDist['done'] = (int)$row2['c'];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Desk — Caffora</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      -webkit-overflow-scrolling: touch;
      background:#FAFAFA;
      color:#111827;
      font-family:Inter,system-ui,Segoe UI,Roboto,"Helvetica Neue",Arial;
    }

    :root{
      --gold:#ffd54f;
      --gold-200:#ffe883;
      --c-border:#E5E7EB;
      --radius:18px;
    }

    /* Sidebar */
    .sidebar{
      width:280px;
      background:linear-gradient(180deg,var(--gold-200),var(--gold));
      border-right:1px solid rgba(0,0,0,.08);
      position:fixed;
      inset:0 auto 0 0;
      padding:24px 18px;
      overflow-y:auto;
      z-index:1000;
    }
    .brand{font-weight:800;font-size:24px;color:#111;}
    .nav-link{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;font-weight:600;color:#111;}
    .nav-link:hover{background:rgba(255,213,79,0.25);}
    .nav-link.active{background:var(--gold-200);}
    .nav-link .bi{color:#111;}
    .sidebar hr{border-color:rgba(0,0,0,.08);opacity:1;}

    /* Content */
    .content{margin-left:280px;padding:24px;}
    .search-pill{background:#fff;border:1px solid var(--c-border);border-radius:999px;padding:10px 14px;display:flex;align-items:center;gap:10px;}
    .icon-btn{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:#fff;border:1px solid var(--c-border);}
    .avatar{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:var(--gold);font-weight:800;color:#111;border:2px solid #fff;}

    /* KPI & Cards */
    .kpi,.cardx{
      background:#fff;
      border:1px solid var(--gold);
      border-radius:var(--radius);
      padding:18px;
    }
    .kpi .ico{width:44px;height:44px;border-radius:12px;background:var(--gold-200);display:grid;place-items:center;}
    .chart-wrap{padding:18px;}

    /* Chart canvas fix */
    #revChart{width:100%!important;height:260px!important;display:block;}
    #distChart{width:100%!important;aspect-ratio:1/1;height:auto!important;max-height:480px;display:block;}

    /* Buttons */
    .btn-saffron{background:var(--gold);border-color:var(--gold);color:#111;font-weight:600;}
    .btn-saffron:hover{background:var(--gold-200);border-color:var(--gold-200);color:#111;}
    .btn-saffron:focus,.btn-saffron:active{
      background:var(--gold-200)!important;border-color:var(--gold-200)!important;color:#111!important;
    }

    /* ==== RESPONSIVE DESIGN ==== */
    @media (max-width: 992px) {
      .sidebar {
        position: fixed;
        width: 240px;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        z-index: 1050;
      }
      .sidebar.active { transform: translateX(0); }
      .content { margin-left: 0 !important; padding: 16px; }
      .menu-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px; height: 42px;
        border: none; background: var(--gold);
        color: #111; border-radius: 12px;
      }
    }

    @media (max-width: 576px) {
      h2.fw-bold { font-size: 20px; }
      .kpi { text-align: center; flex-direction: column; }
      .kpi .ico { margin-bottom: 6px; }
      .avatar { width: 36px; height: 36px; font-size: 14px; }
      .chart-wrap { padding: 12px; }
      table { font-size: 14px; }
      .btn { padding: 6px 10px; font-size: 13px; }
      .cardx, .kpi { padding: 12px; }
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="brand mb-2">Admin Desk</div>
  <hr class="my-3">
  <nav class="nav flex-column gap-2" id="sidebar-nav">
    <a class="nav-link active" href="<?= BASE_URL ?>/public/admin/index.php"><i class="bi bi-house-door"></i> Dashboard</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/orders.php"><i class="bi bi-receipt"></i> Orders</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/catalog.php"><i class="bi bi-box-seam"></i> Catalog</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/users.php"><i class="bi bi-people"></i> Users</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/finance.php"><i class="bi bi-cash-coin"></i> Finance</a>
    <hr>
    <a class="nav-link" href="#"><i class="bi bi-question-circle"></i> Help Center</a>
    <a class="nav-link" href="#"><i class="bi bi-gear"></i> Settings</a>
    <a class="nav-link text-danger" href="<?= BASE_URL ?>/backend/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </nav>
</aside>

<!-- Content -->
<main class="content">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
      <button class="menu-toggle d-lg-none" id="menuToggle"><i class="bi bi-list"></i></button>
      <h4 class="fw-bold m-0">Dashboard Admin</h4>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3 col-12"><div class="kpi d-flex align-items-center gap-3"><div class="ico"><i class="bi bi-list-ul"></i></div><div><div class="small text-muted">Total Pesanan</div><div class="fs-4 fw-bold"><?= $kpi['total_orders'] ?></div></div></div></div>
    <div class="col-md-3 col-12"><div class="kpi d-flex align-items-center gap-3"><div class="ico"><i class="bi bi-calendar2-day"></i></div><div><div class="small text-muted">Pesanan Hari Ini</div><div class="fs-4 fw-bold"><?= $kpi['orders_today'] ?></div></div></div></div>
    <div class="col-md-3 col-12"><div class="kpi d-flex align-items-center gap-3"><div class="ico"><i class="bi bi-box"></i></div><div><div class="small text-muted">Menu Tersedia</div><div class="fs-4 fw-bold"><?= $kpi['menu_count'] ?></div></div></div></div>
    <div class="col-md-3 col-12"><div class="kpi d-flex align-items-center gap-3"><div class="ico"><i class="bi bi-people"></i></div><div><div class="small text-muted">Pelanggan Aktif</div><div class="fs-4 fw-bold"><?= $kpi['active_customers'] ?></div></div></div></div>
  </div>

  <div class="row g-3">
    <div class="col-xl-7 col-12"><div class="cardx chart-wrap"><h6 class="fw-bold mb-3">Revenue 7 Hari Terakhir</h6><canvas id="revChart"></canvas></div></div>
    <div class="col-xl-5 col-12"><div class="cardx chart-wrap"><h6 class="fw-bold mb-3">Distribusi Status Pesanan</h6><canvas id="distChart"></canvas></div></div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('menuToggle');
  toggleBtn?.addEventListener('click', () => sidebar.classList.toggle('active'));
  document.addEventListener('click', e => {
    if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) sidebar.classList.remove('active');
  });
</script>

<script>
  const labels = <?= json_encode($labels) ?>;
  const revenue = <?= json_encode($revenue) ?>;
  const dist = <?= json_encode(array_values($statusDist)) ?>;

  function renderCharts(){
    new Chart(document.getElementById('revChart'), {
      type:'line',
      data:{labels,datasets:[{label:'Revenue',data:revenue,tension:.35,fill:true,borderColor:'#ffd54f',backgroundColor:'rgba(255,213,79,0.18)',pointRadius:4,pointBackgroundColor:'#ffd54f'}]},
      options:{
        plugins:{legend:{display:false}},
        scales:{
          y:{ticks:{callback:v=>new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(v)},grid:{color:'rgba(17,24,39,.06)'}},
          x:{grid:{display:false}}
        }
      }
    });
    new Chart(document.getElementById('distChart'), {
      type:'doughnut',
      data:{labels:['Paid','Pending','Overdue','Done'],datasets:[{data:dist,backgroundColor:['#22c55e','#f59e0b','#ef4444','#3b82f6'],borderWidth:0}]},
      options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{position:'bottom'}}}
    });
  }
  (function loadChart(){
    const s=document.createElement('script');
    s.src='https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
    s.onload=renderCharts;
    document.body.appendChild(s);
  })();
</script>

</body>
</html>
