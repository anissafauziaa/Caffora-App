<?php
// public/customer/checkout.php
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth_guard.php';
require_login(['customer']); // wajib login

$nameDefault = $_SESSION['user_name'] ?? '';
$userId      = (int)($_SESSION['user_id']  ?? 0);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Checkout — Caffora</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root { --yellow:#FFD54F; --camel:#DAA85C; --brown:#4B3F36; --line:#e9e3dc; --bg:#fffdf8; }
    *{ font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; box-sizing:border-box; }
    body{ background:var(--bg); color:var(--brown); overflow-x:hidden; }

    .topbar{ background:#fff; border-bottom:1px solid var(--line); }
    .back-link{ font-weight:600; color:var(--brown); text-decoration:none; }
    .back-link i{ margin-right:6px; }

    .page{ max-width:900px; margin:28px auto; padding:0 16px; }
    .title{ font-weight:800; margin-bottom:16px; }

    .item-row{ display:grid; grid-template-columns:64px 1fr auto; gap:12px; align-items:center; padding:10px 0; border-bottom:1px solid var(--line); }
    .thumb{ width:64px; height:64px; border-radius:12px; object-fit:cover; }
    .name{ font-weight:600; font-size:1rem; }
    .price{ font-weight:600; font-size:.95rem; color:var(--brown); }
    .tot{ border-top:2px dashed var(--line); padding-top:12px; font-weight:800; margin-top:8px; }
    .tot .total-price{ font-weight:700; font-size:1rem; color:var(--brown); }

    .form-control,.form-select{ border-radius:9999px!important; padding:10px 16px; font-size:.95rem; }

    .btn-primary-cf{
      background:var(--yellow); color:var(--brown); border:none; border-radius:9999px;
      padding:.6rem 1.6rem; font-weight:600; font-size:.9rem; min-width:180px;
      transition:all .2s ease-in-out;
    }
    .btn-primary-cf:hover{ background:var(--camel); color:#fff; }

    @media (max-width:600px){
      .btn-primary-cf{ display:block; margin:0 auto; width:auto; min-width:160px; font-size:.95rem; padding:.7rem 1.6rem; }
    }

    @keyframes spin{ from{transform:rotate(0)} to{transform:rotate(360deg)} }
  </style>
</head>
<body>
  <!-- Topbar -->
  <div class="topbar py-2">
    <div class="container d-flex justify-content-start align-items-center">
      <a class="back-link" href="./cart.php"><i class="bi bi-arrow-left"></i> Kembali ke Keranjang</a>
    </div>
  </div>

  <main class="page">
    <h3 class="title">Checkout</h3>

    <!-- Ringkasan item terpilih -->
    <div id="summary"></div>

    <form id="checkoutForm" class="mt-4">
      <div class="mb-3">
        <label class="form-label">Nama Customer</label>
        <input type="text" class="form-control" id="customer_name" value="<?php echo htmlspecialchars($nameDefault); ?>" required>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Tipe Layanan</label>
          <select id="service_type" class="form-select" required>
            <option value="dine_in">Dine In</option>
            <option value="take_away">Take Away</option>
          </select>
        </div>
        <div class="col-md-6" id="tableWrap">
          <label class="form-label">Nomor Meja</label>
          <input type="text" class="form-control" id="table_no" placeholder="Misal: 05">
          <div class="form-text">Isi hanya jika dine in.</div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-md-6">
          <label class="form-label">Metode Pembayaran</label>
          <select id="payment_method" class="form-select" required>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="qris">QRIS</option>
            <option value="ewallet">E-Wallet</option>
          </select>
        </div>
      </div>

      <div class="mt-4 d-flex justify-content-end">
        <button type="submit" class="btn-primary-cf">Buat Pesanan</button>
      </div>
    </form>
  </main>

  <script>
  (function(){
    // ==== KONFIG ====
    const APP_BASE   = '/caffora-app1'; // sesuaikan folder root project
    const API_CREATE = APP_BASE + '/backend/api/orders.php?action=create';
    const HISTORY_URL= APP_BASE + '/public/customer/history.php';

    // kunci localStorage keranjang & pilihan
    const KEY_CART   = 'caffora_cart';
    const KEY_SELECT = 'caffora_cart_selected';

    const $summary = document.getElementById('summary');
    const $form    = document.getElementById('checkoutForm');
    const $service = document.getElementById('service_type');
    const $table   = document.getElementById('table_no');

    // value dari PHP
    const USER_ID = <?php echo json_encode($userId, JSON_UNESCAPED_SLASHES); ?>;

    // ==== Helper ====
    const rp = n => 'Rp ' + Number(n||0).toLocaleString('id-ID');
    const esc = s => String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    const getCart = () => { try { return JSON.parse(localStorage.getItem(KEY_CART) || '[]'); } catch { return []; } };
    const setCart = (items) => localStorage.setItem(KEY_CART, JSON.stringify(items));
    const getSelectedIds = () => { try { return JSON.parse(localStorage.getItem(KEY_SELECT) || '[]'); } catch { return []; } };

    function renderSummary(){
      const cart = getCart();
      const selIds = getSelectedIds().map(String);
      const items = cart.filter(it => selIds.includes(String(it.id)));

      if (!items.length){
        $summary.innerHTML = '<div class="text-muted">Tidak ada item yang dipilih. Silakan kembali ke keranjang.</div>';
        return;
      }

      let t = 0, html = '';
      items.forEach(it => {
        const qty = Number(it.qty)||0, price = Number(it.price)||0;
        const img = it.image || it.image_url || '';
        t += qty*price;
        html += `
          <div class="item-row">
            <img class="thumb" src="${img}" alt="${esc(it.name||'Menu')}">
            <div><div class="name">${esc(it.name||'')} x ${qty}</div></div>
            <div class="price">${rp(qty*price)}</div>
          </div>`;
      });
      html += `<div class="d-flex justify-content-between align-items-center tot mt-2">
                <div>Total</div><div class="total-price">${rp(t)}</div>
              </div>`;
      $summary.innerHTML = html;
    }
    renderSummary();

    function showBtnLoading(btn, on){
      if (on){
        btn.disabled = true;
        btn.dataset.text = btn.innerHTML;
        btn.innerHTML = '<span style="display:inline-block;width:14px;height:14px;border:2px solid #fff;border-right-color:transparent;border-radius:50%;margin-right:8px;vertical-align:middle;animation:spin .7s linear infinite;"></span>Memproses...';
      }else{
        btn.disabled = false;
        btn.innerHTML = btn.dataset.text || 'Buat Pesanan';
      }
    }

    // auto-toggling field table_no
    $service.addEventListener('change', ()=>{
      if ($service.value === 'dine_in') {
        $table.removeAttribute('disabled');
      } else {
        $table.value = '';
        $table.setAttribute('disabled','disabled');
      }
    });
    // init
    if ($service.value !== 'dine_in') $table.setAttribute('disabled','disabled');

    // ==== Submit ====
    $form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const btn = $form.querySelector('.btn-primary-cf');
      showBtnLoading(btn, true);

      const cart = getCart();
      const selIds = getSelectedIds().map(String);
      const itemsSel = cart.filter(it => selIds.includes(String(it.id)));

      if (!itemsSel.length){
        alert('Tidak ada item yang dipilih.');
        showBtnLoading(btn, false);
        return;
      }

      const payload = {
        user_id: USER_ID,
        customer_name: document.getElementById('customer_name').value.trim(),
        service_type:  document.getElementById('service_type').value, // dine_in | take_away
        table_no:      ($table.value||'').trim() || null,
        payment_method:document.getElementById('payment_method').value,
        payment_status:'pending',
        // kirim sesuai schema order_items: menu_id, qty, price
        items: itemsSel.map(it => ({
          menu_id: Number(it.id),
          qty:     Number(it.qty)||0,
          price:   Number(it.price)||0
        }))
      };

      try{
        const res = await fetch(API_CREATE, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          credentials:'same-origin',
          body: JSON.stringify(payload)
        });
        const js = await res.json().catch(()=>({ok:false, error:'Invalid JSON'}));
        if (!res.ok || !js.ok) throw new Error(js.error || ('HTTP '+res.status));

        // Buang hanya item yang barusan dipesan dari keranjang
        const remaining = cart.filter(it => !selIds.includes(String(it.id)));
        setCart(remaining);
        localStorage.removeItem(KEY_SELECT);

        alert('Pesanan berhasil dibuat! Invoice: ' + (js.invoice_no || ''));
        window.location.href = HISTORY_URL; // arahkan ke riwayat
      }catch(err){
        alert('Checkout gagal: ' + (err?.message || err));
      }finally{
        showBtnLoading(btn, false);
      }
    });
  })();
  </script>
</body>
</html>