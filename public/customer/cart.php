<?php 
// public/customer/cart.php
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth_guard.php';
require_login(['customer']); // wajib login
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Keranjang — Caffora</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --yellow: #FFD54F;
      --camel: #DAA85C;
      --brown: #4B3F36;
      --line: #e5e7eb;
      --bg: #fffdf8;
    }

    * {
      font-family: "Poppins", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      box-sizing: border-box;
    }

    html, body {
      width: 100%;
      overflow-x: hidden;
      background: var(--bg);
      color: var(--brown);
    }

    /* ==== Skala mobile ==== */
    @media (max-width: 768px) {
      body { zoom: 0.95; }
    }
    @media (max-width: 480px) {
      body { zoom: 0.9; }
    }

    /* Top bar */
    .topbar {
      background: #fff;
      border-bottom: 1px solid rgba(0,0,0,.06);
    }

    .topbar .container {
      gap: 10px;
    }

    .back-link {
      font-weight: 600;
      color: var(--brown);
      text-decoration: none;
    }

    .back-link i { margin-right: 6px; }

    .btn-gold,
    .btn-primary-cf {
      background: var(--yellow);
      color: var(--brown);
      border: 0;
      border-radius: 9999px;
      font-weight: 600;
      font-size: 0.9rem;
      line-height: 1.2;
      padding: 8px 18px;
      flex-shrink: 0;
      white-space: nowrap;
      transition: background .15s ease, color .15s ease;
    }

    .btn-gold:hover,
    .btn-primary-cf:hover {
      background: var(--camel);
      color: #fff;
    }

    @media (max-width: 600px) {
  .btn-gold,
  .btn-primary-cf {
    font-size: 0.9rem; /* ubah sesuai kebutuhan */
    padding: 7px 15px; /* bisa dikurangi juga biar tombolnya proporsional */
  }
}
@media (max-width: 600px) {
  .btn-gold,
  .btn-primary-cf {
    font-size: 0.9rem;      /* sedikit lebih besar agar tetap terbaca */
    padding: 10px 18px;   /* tombol di button */
    border-radius: 9999px;
  }
}
/* ==== Responsif untuk toast di mobile ==== */
@media (max-width: 600px) {
  .toast {
    width: 100% !important;  /* biar tetap full */
    left: 0 !important;      /* pastikan rata kiri */
    right: 0 !important;     /* dan rata kanan */
    border-radius: 0;        /* opsional: biar full edge */
  }

  .toast-body,
  .toast-message {
    font-size: 0.8rem;       /* perkecil teks aja */
  }
}


/* geser aga ke kiri yh */  
.topbar .btn-gold {
  margin-right: 30px; /* atur nilai sesuai selera */
}
    /* Wrapper */
    .page {
      max-width: 1100px;
      margin: 18px auto 28px;
      padding: 0 16px;
    }

    /* === Layout row keranjang === */
    .cart-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 0;
      border-bottom: 1px solid rgba(0,0,0,.06);
      gap: 12px;
      flex-wrap: nowrap;
    }

    .cart-info {
      display: flex;
      align-items: center;
      flex: 1;
      gap: 12px;
      min-width: 0;
    }

    .row-check {
      width: 18px;
      height: 18px;
      border: 2px solid var(--yellow);
      border-radius: 4px;
      appearance: none;
      background: #fff;
      cursor: pointer;
      position: relative;
      flex-shrink: 0;
    }

    .row-check:checked {
      background: var(--yellow);
    }

    .row-check:checked::after {
      content: "";
      position: absolute;
      left: 4px;
      top: 0;
      width: 6px;
      height: 10px;
      border: 2px solid var(--brown);
      border-top: none;
      border-left: none;
      transform: rotate(45deg);
    }

    .cart-thumb {
      width: 72px;
      height: 72px;
      border-radius: 12px;
      object-fit: cover;
      background: #f3f3f3;
      flex-shrink: 0;
    }

    .info-text {
      flex: 1;
      min-width: 0;
    }

    .name {
      font-weight: 600;
      color: #2b2b2b;
      font-size: 1rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .price {
      font-size: .92rem;
      color: #777;
    }

    /* Tombol + dan - */
    .qty {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-shrink: 0;
    }

    .btn-qty {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      border: 1px solid var(--line);
      background: #fff;
      color: #2b2b2b;
      font-weight: 700;
      transition: background .15s ease, border-color .15s ease;
    }

    .btn-qty:hover {
      background: #fafafa;
      border-color: #d8dce2;
    }

    .qty-val {
      min-width: 28px;
      text-align: center;
      font-weight: 600;
    }

    .line-total {
      min-width: 120px;
      text-align: right;
      font-weight: 600;
      color: #2b2b2b;
    }

    /* Summary */
    .summary {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 16px 0;
      border-top: 2px dashed rgba(0,0,0,.08);
      margin-top: 8px;
      flex-wrap: wrap;
    }

    .subtotal {
      font-size: 1rem;
      font-weight: 600;
    }

    /* === Responsive === */
    @media (max-width: 768px) {
      .cart-row {
        gap: 10px;
      }

      .cart-thumb {
        width: 60px;
        height: 60px;
      }

      .line-total {
        display: none;
      }

      .name {
        font-size: 0.95rem;
      }

      .btn-qty {
        width: 30px;
        height: 30px;
        border-radius: 8px;
      }

      .summary {
        flex-direction: column;
        align-items: center;
        text-align: center;
      }

      .summary .btn-primary-cf {
        width: auto;
        max-width: 240px;
      }
    }

    @media (max-width: 400px) {
      .cart-thumb {
        width: 56px;
        height: 56px;
      }
      .btn-qty {
        width: 28px;
        height: 28px;
        font-size: 13px;
      }
      .qty {
        gap: 6px;
        font-size: 12px;
      }
    }
  </style>
</head>

<body>
  <!-- TOPBAR -->
  <div class="topbar py-2">
    <div class="container d-flex justify-content-between align-items-center">
      <a class="back-link" href="./index.php"><i class="bi bi-arrow-left"></i> Lanjut Belanja</a>
      <button id="btnDeleteSelected" class="btn-gold">Hapus</button>
    </div>
  </div>

  <main class="page"
        data-project-base="/caffora-app1"
        data-public-base="/caffora-app1/public"
        data-uploads-base="/caffora-app1/public/uploads/menu"
        data-placeholder="/caffora-app1/public/assets/img/placeholder.jpg">

    <!-- List -->
    <div id="cartList"></div>

    <!-- Summary -->
    <div class="summary">
      <div class="subtotal" id="subtotal">Subtotal: Rp 0</div>
      <button id="btnCheckout" class="btn-primary-cf">Checkout</button>
    </div>
  </main>

  <script>
  (function(){
    var ROOT = document.querySelector('main.page');
    var PROJECT_BASE = ROOT.dataset.projectBase || '/caffora-app1';
    var PUBLIC_BASE  = ROOT.dataset.publicBase  || (PROJECT_BASE + '/public');
    var UPLOADS_BASE = ROOT.dataset.uploadsBase || (PUBLIC_BASE + '/uploads/menu');
    var PLACEHOLDER  = ROOT.dataset.placeholder || (PUBLIC_BASE + '/assets/img/placeholder.jpg');

    var KEY = 'caffora_cart';
    var $list = document.getElementById('cartList');
    var $subtotal = document.getElementById('subtotal');
    var $btnDelete = document.getElementById('btnDeleteSelected');
    var $btnCheckout = document.getElementById('btnCheckout');
    var selectedIds = new Set();

    function rupiah(n){ return 'Rp ' + Number(n||0).toLocaleString('id-ID'); }
    function escapeHtml(s){ return String(s||'').replace(/[&<>"]'/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
    function getCart(){ try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch(e){ return []; } }
    function setCart(c){ localStorage.setItem(KEY, JSON.stringify(c)); }
    function line(it){ return (Number(it.price)||0) * (Number(it.qty)||0); }

    function normalizeImageUrl(raw){
      if (!raw) return '';
      var u = String(raw).trim().replace(/\\/g,'/');
      if (/^https?:\/\//i.test(u)) return u;
      if (u.indexOf(PROJECT_BASE + '/public/uploads/menu/') === 0) return u;
      if (u.indexOf('/public/uploads/menu/') === 0) return PROJECT_BASE + u;
      if (/^\/?uploads\/menu\//i.test(u)){
        u = u.replace(/^\/?uploads\/menu\//i,'');
        return (UPLOADS_BASE + '/' + u).replace(/([^:])\/{2,}/g,'$1/');
      }
      if (u.indexOf('/') === -1){
        return (UPLOADS_BASE + '/' + encodeURIComponent(u)).replace(/([^:])\/{2,}/g,'$1/');
      }
      if (u.charAt(0) !== '/') u = '/' + u;
      return u;
    }

    function migrateCart(){
      var c = getCart(), changed=false;
      for (var i=0;i<c.length;i++){
        var it=c[i], raw=it.image_url||it.image||'', ok=normalizeImageUrl(raw);
        if (ok && it.image_url!==ok){ it.image_url=ok; changed=true; }
      }
      if (changed) setCart(c);
    }

    
function render(){
  migrateCart();
  var cart = getCart();
  if(!cart.length){
    $list.innerHTML='<div class="empty">Keranjang Anda kosong. Tambahkan menu dari katalog.</div>';
    $subtotal.textContent='Subtotal: Rp 0';
    selectedIds.clear();
    return;
  }

  var html = '';
  for(var i=0;i<cart.length;i++){
    var it = cart[i];
    var img = normalizeImageUrl(it.image_url||it.image||'')||PLACEHOLDER;
    var checked = selectedIds.has(String(it.id)) ? ' checked' : '';
    html +=
      '<div class="cart-row">'+
        '<div class="cart-info">'+
          '<input type="checkbox" class="row-check" data-id="'+escapeHtml(String(it.id))+'"'+checked+'>'+
          '<img class="cart-thumb" src="'+img+'" alt="'+escapeHtml(it.name||'Menu')+'" onerror="this.src=\''+PLACEHOLDER+'\';">'+
          '<div class="info-text">'+
            '<div class="name">'+escapeHtml(it.name||'Menu')+'</div>'+
            '<div class="price">'+rupiah(it.price||0)+'</div>'+
          '</div>'+
        '</div>'+
        '<div class="qty">'+
          '<button class="btn-qty" data-act="dec" data-idx="'+i+'">−</button>'+
          '<span class="qty-val">'+(it.qty||1)+'</span>'+
          '<button class="btn-qty" data-act="inc" data-idx="'+i+'">+</button>'+
        '</div>'+
      '</div>';
  }
  $list.innerHTML = html;

  // === Hitung subtotal hanya dari item yang dicentang ===
  var sub = 0;
  for (var i=0; i<cart.length; i++){
    if (selectedIds.has(String(cart[i].id))){
      sub += line(cart[i]);
    }
  }
  $subtotal.textContent = 'Subtotal: ' + rupiah(sub);

  // Tombol + dan -
  $list.querySelectorAll('[data-act]').forEach(function(btn){
    btn.onclick = function(){
      var act = this.dataset.act, idx = parseInt(this.dataset.idx,10), c = getCart();
      if(!c[idx]) return;
      if(act === 'inc') c[idx].qty++;
      else if(act === 'dec'){
        if(c[idx].qty > 1) c[idx].qty--;
        else {
          selectedIds.delete(String(c[idx].id));
          c.splice(idx,1);
        }
      }
      setCart(c);
      render();
    };
  });

  // Checkbox event
  $list.querySelectorAll('.row-check').forEach(function(ch){
    ch.onchange = function(){
      var id = String(this.dataset.id||'');
      if(this.checked) selectedIds.add(id); 
      else selectedIds.delete(id);

      // Update subtotal setiap kali checkbox berubah
      var sub2 = 0;
      var cart2 = getCart();
      for (var j=0; j<cart2.length; j++){
        if (selectedIds.has(String(cart2[j].id))){
          sub2 += line(cart2[j]);
        }
      }
      $subtotal.textContent = 'Subtotal: ' + rupiah(sub2);
    };
  });
}

$btnDelete.onclick=function(){
  if(selectedIds.size===0){ alert('Pilih item yang ingin dihapus.'); return; }
  var c=getCart().filter(function(it){ return !selectedIds.has(String(it.id)); });
  setCart(c); selectedIds.clear(); render();
};

// ✅ Kembalikan event tombol Checkout
$btnCheckout.onclick=function(){
  if(selectedIds.size===0){ 
    alert('Pilih item terlebih dahulu.'); 
    return; 
  }
  // Simpan ID item yang dipilih ke localStorage
  localStorage.setItem('caffora_cart_selected', JSON.stringify(Array.from(selectedIds)));
  // Arahkan ke halaman checkout
  window.location.href = PROJECT_BASE + '/public/customer/checkout.php';
};

// Jalankan render pertama kali
render();


    render();
  })();
  </script>
</body>
</html>
