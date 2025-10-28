<?php
// public/karyawan/orders.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../backend/auth_guard.php';
require_login(['karyawan','admin']); // karyawan & admin boleh akses

require_once __DIR__ . '/../../backend/config.php'; // BASE_URL, h()
$name = $_SESSION['user_name'] ?? 'Staff';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Kelola Pesanan — Karyawan Desk</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --gold:#ffd54f;
      --gold-200:#ffe883;
      --c-border:#E5E7EB;
      --c-ink:#111827;
      --radius:18px;
    }
    body{ background:#FAFAFA; color:var(--c-ink); font-family:Inter,system-ui,Segoe UI,Roboto,"Helvetica Neue",Arial; }

    /* ===== Sidebar (konsisten dengan admin) ===== */
    .sidebar{
      width:280px;
      background:linear-gradient(180deg,var(--gold-200),var(--gold));
      border-right:1px solid rgba(0,0,0,.08);
      position:fixed; inset:0 auto 0 0;
      padding:24px 18px; overflow-y:auto;
    }
    .brand{font-weight:800;font-size:24px;color:#111;}
    .nav-link{
      display:flex; align-items:center; gap:12px;
      padding:12px 14px; border-radius:12px;
      font-weight:600; color:#111; transition:background .2s ease;
    }
    .nav-link:hover{ background:rgba(255,213,79,.25); }
    .nav-link.active{ background:var(--gold-200); }
    .nav-link .bi{ color:#111; }
    .sidebar hr{ border-color:rgba(0,0,0,.08); opacity:1; }

    /* ===== Content ===== */
    .content{ margin-left:280px; padding:24px; }

    /* ===== UI umum (match admin) ===== */
    .cardx{
      background:#fff;
      border:1px solid var(--gold);
      border-radius:var(--radius);
    }
    .btn-saffron{
      background:var(--gold);
      border-color:var(--gold);
      color:#111; font-weight:600;
    }
    .btn-saffron:hover{
      background:var(--gold-200);
      border-color:var(--gold-200);
      color:#111;
    }

    /* ===== Filter bar ===== */
    .filter-wrap{
      background:#fff;
      border:1px solid var(--gold);
      border-radius:var(--radius);
      padding:14px;
    }
    .filter-grid{
      display:grid;
      grid-template-columns: 1fr 220px 220px 140px;
      gap:12px;
      align-items:center;
    }
    .input-icon{ position:relative; }
    .input-icon .bi{
      position:absolute; left:12px; top:50%; transform:translateY(-50%);
      color:#9ca3af;
    }
    .input-icon input.form-control{
      padding-left:38px; height:44px; border-radius:12px; border:1px solid var(--c-border);
    }
    .filter-wrap .form-select{
      height:44px; border-radius:12px; border:1px solid var(--c-border);
    }
    .filter-wrap .btn-apply{
      height:44px; border-radius:12px; font-weight:600;
    }

    /* Table */
    .table th, .table td{ vertical-align:middle; }
    .table thead th{ background:#fffbe6; } /* lembut selaras gold */
    @media (max-width:992px){
      .filter-grid{ grid-template-columns:1fr; }
    }

    /* Disable anchor like button */
    a.btn.disabled, a.btn[aria-disabled="true"]{
      pointer-events:none;
      opacity:.65;
    }
  </style>
</head>
<body>

<!-- ===== Sidebar ===== -->
<aside class="sidebar">
  <div class="brand mb-2">Karyawan Desk</div><hr>

  <nav class="nav flex-column gap-2" id="sidebar-nav">
    <a class="nav-link" href="<?= BASE_URL ?>/public/karyawan/index.php"><i class="bi bi-house-door"></i> Dashboard</a>
    <a class="nav-link active" href="#"><i class="bi bi-receipt"></i> Orders</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/karyawan/stock.php"><i class="bi bi-box-seam"></i> Menu Stock</a>
    <hr>
    <a class="nav-link" href="#"><i class="bi bi-question-circle"></i> Help Center</a>
    <a class="nav-link" href="<?= BASE_URL ?>/backend/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </nav>
</aside>

<!-- ===== Content ===== -->
<main class="content">
  <h3 class="fw-bold mb-3">Kelola Pesanan</h3>

  <!-- Filter bar -->
  <div class="filter-wrap mb-3">
    <div class="filter-grid">
      <!-- search -->
      <div class="input-icon">
        <i class="bi bi-search"></i>
        <input id="q" class="form-control" placeholder="Cari invoice / nama">
      </div>

      <!-- status pesanan -->
      <select id="fOrder" class="form-select">
        <option value="">Semua Status Pesanan</option>
        <option value="new">New</option>
        <option value="processing">Processing</option>
        <option value="ready">Ready</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>

      <!-- status pembayaran -->
      <select id="fPay" class="form-select">
        <option value="">Semua Pembayaran</option>
        <option value="pending">Pending</option>
        <option value="paid">Paid</option>
        <option value="failed">Failed</option>
        <option value="refunded">Refunded</option>
        <option value="overdue">Overdue</option>
      </select>

      <!-- apply -->
      <button id="btnFilter" class="btn btn-apply btn-saffron">Terapkan</button>
    </div>
  </div>

  <div class="cardx p-3">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Invoice</th>
            <th>Nama</th>
            <th>Total</th>
            <th>Pesanan</th>
            <th>Pembayaran</th>
            <th>Metode</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="rows">
          <tr><td colspan="7" class="text-center text-muted py-4">Memuat...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
  // ===== Nav aktif saat diklik (konsisten) =====
  document.querySelectorAll('#sidebar-nav .nav-link').forEach(link=>{
    link.addEventListener('click',function(){
      document.querySelectorAll('#sidebar-nav .nav-link').forEach(l=>l.classList.remove('active'));
      this.classList.add('active');
    });
  });

  const BASE = '<?= rtrim(BASE_URL, "/") ?>';
  const API  = BASE + '/backend/api/orders.php';

  const $rows = document.getElementById('rows');
  const $q    = document.getElementById('q');
  const $fOrder = document.getElementById('fOrder');
  const $fPay   = document.getElementById('fPay');
  const $btnFilter = document.getElementById('btnFilter');

  const rp = (n) => 'Rp ' + Number(n||0).toLocaleString('id-ID');

  function badgeOrder(os){
    const map = {new:'secondary',processing:'primary',ready:'warning',completed:'success',cancelled:'dark'};
    return '<span class="badge text-bg-'+(map[os]||'secondary')+' fw-semibold">'+(os||'-')+'</span>';
  }
  function badgePay(ps){
    const map = {pending:'warning',paid:'success',failed:'danger',refunded:'info',overdue:'secondary'};
    return '<span class="badge text-bg-'+(map[ps]||'secondary')+' fw-semibold">'+(ps||'-')+'</span>';
  }
  function nextButtons(os){
    const order = ['new','processing','ready','completed'];
    const idx = order.indexOf(os);
    if (idx === -1 || os==='completed' || os==='cancelled') {
      return '<button class="btn btn-sm btn-outline-secondary" disabled>Selesai</button>';
    }
    const next = order[idx+1];
    const labelMap = {processing:'Mulai', ready:'Siap', completed:'Selesai'};
    return '<button class="btn btn-sm btn-outline-primary" data-act="next" data-val="'+next+'">'+(labelMap[next]||'→')+'</button>';
  }

  function payDropdown(current){
    const label = (current === 'paid') ? 'Lunas' : 'Pending';
    return `
      <div class="dropdown">
        <button class="btn btn-sm btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">${label}</button>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="#" data-act="pay" data-val="paid">Tandai Lunas</a></li>
          <li><a class="dropdown-item" href="#" data-act="pay" data-val="pending">Tandai Pending</a></li>
        </ul>
      </div>`;
  }

  async function load(){
    try{
      const url = new URL(API, location.origin);
      url.searchParams.set('action','list');
      if ($q.value.trim()) url.searchParams.set('q',$q.value.trim());
      if ($fOrder.value)   url.searchParams.set('order_status',$fOrder.value);
      if ($fPay.value)     url.searchParams.set('payment_status',$fPay.value);

      const res = await fetch(url.toString(), {credentials:'same-origin', cache:'no-store'});
      if (!res.ok){
        const txt = await res.text().catch(()=>res.statusText);
        throw new Error('HTTP '+res.status+': '+txt);
      }
      const js = await res.json();
      if (!js.ok) throw new Error(js.error || 'Unknown API error');
      const items = js.items || [];

      if (!items.length){
        $rows.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data.</td></tr>';
        return;
      }

      $rows.innerHTML = items.map(it => {
        const strukHref = BASE + '/public/karyawan/receipt.php?order=' + it.id;
        const strukDisabled = it.payment_status !== 'paid';
        return `
          <tr data-id="${it.id}">
            <td class="fw-semibold">${it.invoice_no || '-'}</td>
            <td>${it.customer_name || '-'}</td>
            <td>${rp(it.total)}</td>
            <td>${badgeOrder(it.order_status)}</td>
            <td>${badgePay(it.payment_status)}</td>
            <td>${(it.payment_method||'-')}</td>
            <td class="d-flex flex-wrap gap-1">
              ${nextButtons(it.order_status)}
              <div class="vr mx-1"></div>

              ${payDropdown(it.payment_status)}

              <div class="btn-group ms-1">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Metode</button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="#" data-act="method" data-val="cash">Cash</a></li>
                  <li><a class="dropdown-item" href="#" data-act="method" data-val="qris">QRIS</a></li>
                  <li><a class="dropdown-item" href="#" data-act="method" data-val="bank_transfer">Transfer</a></li>
                  <li><a class="dropdown-item" href="#" data-act="method" data-val="ewallet">e-Wallet</a></li>
                </ul>
              </div>

              <button class="btn btn-sm btn-outline-danger ms-1" data-act="cancel">Batal</button>

              <a class="btn btn-sm btn-outline-dark ms-1 ${strukDisabled ? 'disabled' : ''}"
                 ${strukDisabled ? 'aria-disabled="true"' : 'target="_blank" rel="noopener"'}
                 href="${strukDisabled ? '#': strukHref}">
                 <i class="bi bi-printer me-1"></i> Struk
              </a>
            </td>
          </tr>
        `;
      }).join('');

      // Hook actions
      $rows.querySelectorAll('[data-act]').forEach(btn=>{
        btn.addEventListener('click', async (e)=>{
          e.preventDefault();
          const tr = btn.closest('tr'); const id = tr?.dataset.id;
          if (!id) return;

          let payload = { id: Number(id) };
          if (btn.dataset.act === 'next')        payload.order_status   = btn.dataset.val;
          else if (btn.dataset.act === 'pay')    payload.payment_status = btn.dataset.val;
          else if (btn.dataset.act === 'method') payload.payment_method = btn.dataset.val;
          else if (btn.dataset.act === 'cancel'){
            if(!confirm('Batalkan pesanan ini?')) return;
            payload.order_status='cancelled';
          }

          const res2 = await fetch(API+'?action=update', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            credentials:'same-origin',
            body: JSON.stringify(payload)
          });
          const js2 = await res2.json().catch(()=>({ok:false,error:'invalid json'}));
          if (!res2.ok || !js2.ok) { alert(js2.error || ('HTTP '+res2.status)); return; }
          await load();
        });
      });

    }catch(err){
      console.error('[Orders] load error:', err);
      $rows.innerHTML = '<tr><td colspan="7" class="text-danger py-4 text-center">Gagal memuat: '+String(err.message||err)+'</td></tr>';
    }
  }

  document.getElementById('btnFilter').addEventListener('click', load);
  document.addEventListener('DOMContentLoaded', load);
})();
</script>
</body>
</html>