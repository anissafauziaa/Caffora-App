<?php
// public/admin/finance.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../backend/config.php';
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
  header('Location: ' . BASE_URL . '/public/login.html'); exit;
}

$today        = new DateTime('today');
$startDefault = (clone $today)->modify('-6 days')->format('Y-m-d');
$endDefault   = $today->format('Y-m-d');
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Finance — Admin Desk</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root{
  --gold:#ffd54f; --gold-200:#ffe883;
  --grid:rgba(255,213,79,.35); --ink:#111827; --r:16px;
  /* atur tinggi VISUAL di sini biar sama */
  --vizH: 340px;
}
body{background:#FAFAFA;color:var(--ink);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial}
.sidebar{width:280px;background:linear-gradient(180deg,var(--gold-200),var(--gold));
  position:fixed;inset:0 auto 0 0;padding:24px 18px;border-right:1px solid rgba(0,0,0,.08);overflow:auto}
.brand{font-weight:800;font-size:24px}
.nav-link{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;color:#111;font-weight:600}
.nav-link:hover{background:rgba(255,213,79,.25)} .nav-link.active{background:var(--gold-200)}
.content{margin-left:280px;padding:24px}
.cardx{background:#fff;border:1px solid var(--gold);border-radius:var(--r)}
.metric{padding:16px;border:1px solid var(--gold);border-radius:12px;background:#fff}
.metric .label{color:#6B7280;font-weight:600}.metric .value{font-size:26px;font-weight:800}
.table-grid{border-collapse:separate;border-spacing:0}
.table-grid thead th{background:#fffbe6;border:1px solid var(--grid);padding:12px 14px;font-weight:700}
.table-grid tbody td{background:#fff;border:1px solid var(--grid);padding:12px 14px}
.placeholder{display:flex;align-items:center;justify-content:center;min-height:220px;color:#6B7280;background:#fffbe6;border:1px dashed var(--grid);border-radius:12px}
.btn-saffron{background:var(--gold);border-color:var(--gold);color:#111;font-weight:600}
.btn-saffron:hover{background:var(--gold-200);border-color:var(--gold-200)}
.quick .btn{border-radius:999px;padding:.35rem .75rem}

/* ==== KUNCI PENYAMA TINGGI ==== */
.viz-box{
  height: var(--vizH);
}
.viz-box canvas{
  height:100% !important;
  width:100% !important;
  display:block;
}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="brand mb-2">Admin Desk</div><hr>
  <nav class="nav flex-column gap-2" id="sidebar-nav">
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/index.php"><i class="bi bi-house-door"></i> Dashboard</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/orders.php"><i class="bi bi-receipt"></i> Orders</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/catalog.php"><i class="bi bi-box-seam"></i> Catalog</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/users.php"><i class="bi bi-people"></i> Users</a>
    <a class="nav-link active" href="#"><i class="bi bi-cash-coin"></i> Finance</a>
    <hr>
    <a class="nav-link" href="#"><i class="bi bi-question-circle"></i> Help Center</a>
    <a class="nav-link" href="#"><i class="bi bi-gear"></i> Settings</a>
    <a class="nav-link" href="<?= BASE_URL ?>/backend/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </nav>
</aside>

<main class="content">
  <div class="row g-3 align-items-end mb-3">
    <div class="col-12 col-lg-6">
      <h2 class="fw-bold mb-2">Finance</h2>
      <div class="quick d-flex gap-2 flex-wrap">
        <button class="btn btn-light border" data-q="7">7D</button>
        <button class="btn btn-light border" data-q="30">30D</button>
        <button class="btn btn-light border" data-q="ytd">YTD</button>
        <button class="btn btn-light border" data-q="all">Semua</button>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <form class="d-flex gap-2 justify-content-lg-end" id="rangeForm">
        <input type="date" class="form-control" id="start" value="<?= h($startDefault) ?>">
        <input type="date" class="form-control" id="end" value="<?= h($endDefault) ?>">
        <button class="btn btn-saffron" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Refresh</button>
      </form>
    </div>
  </div>

  <div id="alertBox" class="alert alert-warning d-none py-2"></div>

  <div class="row g-3 mb-3">
    <div class="col-12 col-md-4"><div class="metric"><div class="label">Total Revenue</div><div class="value" id="mRevenue">Rp 0</div><div class="small text-muted">dibayar (payments.paid)</div></div></div>
    <div class="col-12 col-md-4"><div class="metric"><div class="label">Total Orders</div><div class="value" id="mOrders">0</div><div class="small text-muted">periode terpilih</div></div></div>
    <div class="col-12 col-md-4"><div class="metric"><div class="label">Avg. Order Value</div><div class="value" id="mAOV">Rp 0</div><div class="small text-muted">Revenue / Orders</div></div></div>
  </div>

  <!-- Duo chart: tinggi sama karena kedua canvas di dalam .viz-box -->
  <div class="row g-3 align-items-stretch">
    <div class="col-12 col-lg-6">
      <div class="cardx p-3 h-100">
        <h5 class="fw-bold mb-3">Revenue Harian</h5>
        <div id="revWrap"><div class="placeholder">Memuat…</div></div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="cardx p-3 h-100">
        <h5 class="fw-bold mb-3">Distribusi Status Pesanan</h5>
        <div id="statusWrap"><div class="placeholder">Memuat…</div></div>
      </div>
    </div>

    <div class="col-12">
      <div class="cardx p-3">
        <h5 class="fw-bold mb-3">Transaksi (ringkas)</h5>
        <div class="table-responsive">
          <table class="table table-grid mb-0">
            <thead><tr><th>Tanggal</th><th class="text-end">Revenue</th><th class="text-center">Orders</th></tr></thead>
            <tbody id="tbodyDays"><tr><td colspan="3" class="text-center text-muted py-4">Memuat…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  const PUBLIC_ROOT  = '<?= rtrim(dirname(dirname($_SERVER["SCRIPT_NAME"])), "/") ?>';
  const PROJECT_BASE = PUBLIC_ROOT.replace(/\/public$/, '');
  const API          = PROJECT_BASE + '/backend/api/finance.php';

  const elRevenue = document.getElementById('mRevenue');
  const elOrders  = document.getElementById('mOrders');
  const elAOV     = document.getElementById('mAOV');
  const elTbody   = document.getElementById('tbodyDays');
  const alertBox  = document.getElementById('alertBox');
  const revWrap   = document.getElementById('revWrap');
  const statusWrap= document.getElementById('statusWrap');

  let chartRevenue=null, chartStatus=null;
  const fmtRp = n => 'Rp ' + Number(n||0).toLocaleString('id-ID');

  // bikin kanvas dengan wrapper .viz-box agar tinggi patuh ke CSS
  function canvas(target, id){
    target.innerHTML = '<div class="viz-box"><canvas id="'+id+'"></canvas></div>';
  }
  function placeholder(target, text){ target.innerHTML = '<div class="placeholder">'+text+'</div>'; }
  function showAlert(msg){ alertBox.textContent = msg; alertBox.classList.remove('d-none'); }
  function clearAlert(){ alertBox.classList.add('d-none'); alertBox.textContent=''; }

  async function load(){
    clearAlert();
    if (chartRevenue) { chartRevenue.destroy(); chartRevenue=null; }
    if (chartStatus)  { chartStatus.destroy();  chartStatus=null;  }

    const start = document.getElementById('start').value;
    const end   = document.getElementById('end').value;

    try{
      const url = new URL(API, window.location.origin);
      if (start) url.searchParams.set('start', start);
      if (end)   url.searchParams.set('end', end);

      const res  = await fetch(url.toString(), {cache:'no-store', credentials:'same-origin'});
      const text = await res.text();
      let data;
      try { data = JSON.parse(text); } catch(e){
        throw new Error('Response bukan JSON. Server berkata: ' + text.slice(0,200));
      }
      if (!data.ok) throw new Error(data.error || 'unknown error');

      elRevenue.textContent = fmtRp(data.total_revenue || 0);
      elOrders.textContent  = data.total_orders || 0;
      elAOV.textContent     = fmtRp(data.avg_order_value || 0);

      const days = Array.isArray(data.days) ? data.days : [];
      elTbody.innerHTML = days.length
        ? days.map(d => `<tr><td>${d.date}</td><td class="text-end">${fmtRp(d.revenue)}</td><td class="text-center">${d.orders}</td></tr>`).join('')
        : '<tr><td colspan="3" class="text-center text-muted py-4">Tidak ada data.</td></tr>';

      // Revenue line
      if (days.length && days.some(d => Number(d.revenue) > 0)) {
        canvas(revWrap, 'chartRevenue');
        chartRevenue = new Chart(document.getElementById('chartRevenue'), {
          type: 'line',
          data: {
            labels: days.map(d=>d.date),
            datasets: [{
              label:'Revenue',
              data: days.map(d=>d.revenue),
              tension:.35, fill:true,
              borderColor:'#ffd54f', backgroundColor:'rgba(255,213,79,.18)',
              pointRadius:3, pointBackgroundColor:'#ffd54f', pointBorderColor:'#ffd54f'
            }]
          },
          options: {
            responsive:true,
            maintainAspectRatio:false,   // <- penting agar patuh ke tinggi CSS
            plugins:{ legend:{ display:false } },
            scales:{
              y:{ ticks:{ callback:v=>'Rp '+Number(v).toLocaleString('id-ID') } },
              x:{ grid:{ display:false } }
            }
          }
        });
      } else {
        placeholder(revWrap,'Tidak ada data revenue pada rentang ini.');
      }

      // Status donut
      const s = data.status_counts || {};
      const labels = Object.keys(s);
      const values = labels.map(k=>s[k]);
      if (labels.length && values.some(v=>v>0)) {
        canvas(statusWrap,'chartStatus');
        chartStatus = new Chart(document.getElementById('chartStatus'), {
          type:'doughnut',
          data:{ labels, datasets:[{ data: values, borderWidth:0,
            backgroundColor:['#22c55e','#f59e0b','#ef4444','#3b82f6','#10b981','#6366f1'] }]},
          options:{
            responsive:true,
            maintainAspectRatio:false,   // <- penting juga untuk donut
            plugins:{ legend:{ position:'bottom' } }
          }
        });
      } else {
        placeholder(statusWrap,'Tidak ada distribusi status.');
      }

    } catch(err){
      showAlert('Gagal memuat data finance. ' + (err?.message || err));
      placeholder(revWrap,'Tidak dapat memuat grafik.');
      placeholder(statusWrap,'Tidak dapat memuat grafik.');
      elTbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Tidak ada data.</td></tr>';
    }
  }

  document.querySelectorAll('.quick [data-q]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const today = new Date();
      const end   = today.toISOString().slice(0,10);
      let start;
      const q = btn.getAttribute('data-q');
      if (q==='7')      start = new Date(today.getTime()-6*86400000).toISOString().slice(0,10);
      else if (q==='30')start = new Date(today.getTime()-29*86400000).toISOString().slice(0,10);
      else if (q==='ytd'){ start = new Date(today.getFullYear(),0,1).toISOString().slice(0,10); }
      else               { start = '2000-01-01'; }
      document.getElementById('start').value = start;
      document.getElementById('end').value   = end;
      load();
    });
  });

  document.getElementById('rangeForm').addEventListener('submit', e=>{ e.preventDefault(); load(); });

  load();
})();
</script>
</body>
</html>
