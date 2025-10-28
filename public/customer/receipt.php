<?php
// public/customer/receipt.php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../../backend/auth_guard.php';
require_login(['customer']);
require_once __DIR__ . '/../../backend/config.php'; // $conn, BASE_URL, h()

function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }

$orderId = (int)($_GET['order'] ?? 0);
$userId  = (int)($_SESSION['user_id'] ?? 0);

/* --- ambil data --- */
$ord = null; $items = []; $inv = null;
if ($orderId && $userId) {
  $stmt = $conn->prepare("SELECT * FROM orders WHERE id=? AND user_id=? LIMIT 1");
  $stmt->bind_param('ii', $orderId, $userId);
  $stmt->execute();
  $ord = $stmt->get_result()?->fetch_assoc(); 
  $stmt->close();

  if ($ord) {
    $stmt = $conn->prepare("
      SELECT oi.qty, oi.price, m.name AS menu_name
      FROM order_items oi
      LEFT JOIN menu m ON m.id=oi.menu_id
      WHERE oi.order_id=? ORDER BY oi.id
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $items = $stmt->get_result()?->fetch_all(MYSQLI_ASSOC) ?? [];
    $stmt->close();

    $stmt = $conn->prepare("SELECT amount, issued_at FROM invoices WHERE order_id=? LIMIT 1");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $inv = $stmt->get_result()?->fetch_assoc();
    $stmt->close();
  }
}

/* --- guard --- */
if (!$ord) { http_response_code(403); echo 'Tidak boleh mengakses'; exit; }
if ($ord['payment_status'] !== 'paid') {
  ?><!doctype html><meta charset="utf-8"><style>
  body{font:14px/1.5 Inter,system-ui,Arial;margin:40px;background:#fff;color:#111}
  .box{max-width:460px;margin:0 auto;border:1px solid #e5e7eb;border-radius:12px;padding:18px}
  .muted{color:#6b7280}
  .btn{display:inline-block;border:1px solid #e5e7eb;border-radius:999px;padding:.5rem 1rem;text-decoration:none;color:#111}
  </style><div class="box">
    <p><a class="btn" href="<?= h(BASE_URL) ?>/public/customer/history.php">Kembali</a></p>
    <h2 style="margin:0 0 8px;font-weight:600">Struk belum tersedia</h2>
    <p class="muted">Struk hanya dapat diunduh setelah pembayaran <b>lunas</b>.</p>
    <p class="muted">Invoice: <b><?= h($ord['invoice_no']) ?></b></p>
  </div><?php
  exit;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Struk — <?= h($ord['invoice_no']) ?> | Caffora</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <script>
    function loadScript(src){return new Promise((res,rej)=>{const s=document.createElement('script');s.src=src;s.async=true;s.crossOrigin='anonymous';s.onload=res;s.onerror=()=>rej(new Error('Gagal memuat '+src));document.head.appendChild(s);});}
    async function ensureHtml2Canvas(){
      if (window.html2canvas) return;
      try{ await loadScript('https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js'); }
      catch(e){ await loadScript('https://unpkg.com/html2canvas@1.4.1/dist/html2canvas.min.js'); }
    }
  </script>

  <style>
    :root{
      --gold:#FFD54F; --gold-700:#DAA85C;
      --ink:#111827; --muted:#6b7280; --line:#e5e7eb;
    }
    *{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial;}
    html,body{background:#fafafa;color:var(--ink);margin:0;padding:0;}

    .topbar{position:sticky;top:0;background:#fff;border-bottom:1px solid var(--line);padding:10px 0;z-index:5}
    .wrap{max-width:740px;margin:0 auto;padding:0 14px;display:flex;align-items:center;justify-content:space-between}
    .back-link{display:inline-flex;align-items:center;gap:10px;color:var(--ink);text-decoration:none;font-weight:600}
    .back-link .chev{font-size:18px;line-height:1}
    .btn-primary-cf{
      background:var(--gold); color:#4B3F36;
      border:none; border-radius:999px;
      padding:.55rem 1.2rem; font-weight:600; cursor:pointer;
      display:inline-flex; align-items:center; gap:.6rem;
      box-shadow:0 4px 10px rgba(0,0,0,.05);
      transition:.15s ease-in-out; font-size:.9rem;
    }
    .btn-primary-cf:hover{ background:var(--gold-700); color:#fff; }
    .btn-primary-cf[disabled]{ opacity:.6; cursor:not-allowed; }
    .spin{ width:14px;height:14px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%; display:inline-block; animation:spin .7s linear infinite }
    @keyframes spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}

    /* ===== Kertas Struk ===== */
    .wrapper{padding:20px;}
    .paper{
      max-width:420px;margin:2px auto;border:0px solid var(--line);
      border-radius:10px;padding:18px 20px;background:#fff;
      box-shadow:0 2px 6px rgba(0,0,0,.05);
    }
    .brand{font-weight:700;font-size:18px;margin-bottom:4px;letter-spacing:.2px}
    .meta{display:flex;justify-content:space-between;gap:10px;font-size:.88rem;color:var(--muted)}
    .to{text-align:right}
    .rule{border-top:2px dashed #aaa;margin:10px 0;opacity:.8}
    .rule-strong{border-top:2px dashed #666;margin:12px 0;opacity:1}

    .head-row{display:flex;justify-content:space-between;color:var(--muted);font-weight:500;padding:4px 0 6px;border-bottom:1px dashed var(--line);font-size:.85rem}
    .items{margin:0}
    .item{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;padding:8px 0;border-bottom:1px dashed var(--line)}
    .item-name{font-weight:500;font-size:.9rem}
    .item-sub{color:var(--muted);font-size:.8rem;margin-top:1px}
    .item-sub .dot{margin:0 .35rem}
    .item-subtotal{font-weight:500;color:var(--muted);white-space:nowrap;font-size:.88rem}

    .row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;font-size:.88rem;}
    .row.total{font-weight:700;font-size:.95rem;border-top:2px dotted var(--line);}
    .pill{display:inline-block;padding:.25rem .7rem;border:1px solid var(--line);border-radius:999px;font-weight:600;letter-spacing:.18rem;color:#111;background:#fafafa;font-size:.8rem}
    .muted{color:var(--muted);font-size:.8rem;}

    @media (max-width:480px){
      .wrapper{padding:12px;}
      .paper{max-width:100%;border-radius:0px;padding:14px;}
      .brand{font-size:16px}
      .meta{font-size:.8rem}
      .head-row{font-size:.8rem}
      .item{padding:7px 0}
      .item-name{font-size:.86rem}
      .item-sub{font-size:.78rem}
      .item-subtotal{font-size:.8rem}
      .row{padding:5px 0;font-size:.82rem}
      .row.total{font-size:.9rem}
      .pill{font-size:.76rem;letter-spacing:.16rem;padding:.22rem .6rem}
    }

    @media print{
      @page{size:80mm auto;margin:4mm;}
      .topbar{display:none!important;}
      .paper{border:none;border-radius:0;margin:0;padding:0;max-width:100%;box-shadow:none;}
    }
  </style>
</head>
<body>

  <div class="topbar">
    <div class="wrap">
      <a class="back-link" href="<?= h(BASE_URL) ?>/public/customer/history.php"><span class="chev">←</span> Kembali ke Beranda</a>
      <button id="btnDownload" class="btn-primary-cf">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3a1 1 0 011 1v8.586l2.293-2.293a1 1 0 111.414 1.414l-4.001 4a1 1 0 01-1.414 0l-4.001-4a1 1 0 111.414-1.414L11 12.586V4a1 1 0 011-1z"></path><path d="M5 19a1 1 0 011-1h12a1 1 0 110 2H6a1 1 0 01-1-1z"></path></svg>
        Download Struk
      </button>
    </div>
  </div>

  <div class="wrapper">
    <main class="paper" id="receiptContent">
      <div class="brand">Caffora</div>
      <div class="meta">
        <div>
          <div>Invoice: <span style="font-weight:500"><?= h($ord['invoice_no']) ?></span></div>
          <div>Tanggal: <?= h(date('d M Y H:i', strtotime($ord['created_at']))) ?></div>
        </div>
        <div class="to">
          <div class="muted">Kepada:</div>
          <div style="font-weight:500"><?= h($ord['customer_name']) ?></div>
          <div><?= h($ord['service_type']==='dine_in'?'Dine In':'Take Away') ?><?= $ord['table_no']? ', Meja '.h($ord['table_no']) : '' ?></div>
        </div>
      </div>

      <div class="rule"></div>
      <div class="head-row"><div>Item</div><div>Subtotal</div></div>

      <div class="items">
        <?php foreach($items as $it): $sub=(float)$it['qty']*(float)$it['price']; ?>
          <div class="item">
            <div>
              <div class="item-name"><?= h($it['menu_name'] ?? 'Menu') ?></div>
              <div class="item-sub">Qty: <?= (int)$it['qty'] ?> <span class="dot">×</span> <?= rp((float)$it['price']) ?></div>
            </div>
            <div class="item-subtotal"><?= rp($sub) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="row" style="margin-top:6px">
        <div>Subtotal</div>
        <div class="muted"><?= rp((float)$ord['total']) ?></div>
      </div>

      <div class="row total">
        <div>Total</div>
        <div><?= rp((float)$ord['total']) ?></div>
      </div>

      <div class="row" style="margin-top:8px">
        <div class="pill">L U N A S</div>
        <div class="muted">Metode Pembayaran: <b><?= h(strtoupper(str_replace('_',' ',$ord['payment_method'] ?? '-'))) ?></b></div>
      </div>

      <div class="rule-strong"></div>

      <div class="muted">
        <?php if ($inv): ?>
          Tagihan: <b><?= rp((float)$inv['amount']) ?></b> • Diterbitkan: <?= h(date('d M Y H:i', strtotime($inv['issued_at']))) ?><br>
        <?php endif; ?>
        * Harga sudah termasuk PPN. Terima kasih telah berbelanja di <span style="font-weight:500">Caffora</span>.
      </div>
    </main>
  </div>

  <script>
    (async function(){
      const btn = document.getElementById('btnDownload');
      await ensureHtml2Canvas();

      btn.addEventListener('click', async () => {
        if (btn.disabled) return;
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spin"></span> Mengunduh...';

        try{
          const node = document.getElementById('receiptContent');
          const canvas = await html2canvas(node, {scale:2, background:'#ffffff', useCORS:true});
          canvas.toBlob(function(blob){
            if (!blob) { alert('Gagal membuat gambar'); return; }
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Struk_<?= h($ord['invoice_no']) ?>.png';
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(()=>URL.revokeObjectURL(url), 1200);
          }, 'image/png', 1.0);
        }catch(err){
          alert('Gagal mengunduh: ' + (err?.message || err));
        }finally{
          btn.disabled = false;
          btn.innerHTML = original;
        }
      });
    })();
  </script>
</body>
</html>