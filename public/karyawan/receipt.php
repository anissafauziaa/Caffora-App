<?php
// public/karyawan/receipt.php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../../backend/auth_guard.php';
require_login(['karyawan','admin']);
require_once __DIR__ . '/../../backend/config.php';

function rp(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }

$orderId = (int)($_GET['order'] ?? 0);

/* --- ambil data --- */
$ord = null; $items = []; $inv = null;
if ($orderId) {
  $stmt = $conn->prepare("SELECT * FROM orders WHERE id=? LIMIT 1");
  $stmt->bind_param('i', $orderId);
  $stmt->execute();
  $ord = $stmt->get_result()?->fetch_assoc(); $stmt->close();

  if ($ord) {
    $stmt = $conn->prepare("
      SELECT oi.qty, oi.price, m.name AS menu_name
      FROM order_items oi LEFT JOIN menu m ON m.id=oi.menu_id
      WHERE oi.order_id=? ORDER BY oi.id
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $items = $stmt->get_result()?->fetch_all(MYSQLI_ASSOC) ?? [];
    $stmt->close();

    $stmt = $conn->prepare("SELECT amount, issued_at FROM invoices WHERE order_id=? LIMIT 1");
    $stmt->bind_param('i',$orderId);
    $stmt->execute();
    $inv = $stmt->get_result()?->fetch_assoc(); $stmt->close();
  }
}
if (!$ord){ http_response_code(404); echo 'Order tidak ditemukan'; exit; }
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Struk — <?= h($ord['invoice_no']) ?> | Karyawan</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    :root{
      --gold:#FFD54F; --gold-700:#DAA85C;
      --brown:#4B3F36; --ink:#111827; --muted:#6b7280; --line:#e5e7eb;
    }
    *{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial;}
    html,body{background:#fff;color:var(--ink);margin:0;}

    .topbar{position:sticky;top:0;background:#fff;border-bottom:1px solid var(--line);padding:10px 0;z-index:5}
    .topbar .wrap{max-width:760px;margin:0 auto;padding:0 16px;display:flex;align-items:center;justify-content:space-between}
    .back-link{display:inline-flex;align-items:center;gap:10px;color:var(--ink);text-decoration:none;font-weight:700}
    .back-link .chev{font-size:18px;line-height:1}
    .btn-primary-cf{
      background:var(--gold);color:var(--brown);border:none;border-radius:999px;
      padding:.6rem 1.25rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.6rem;
      box-shadow:0 6px 16px rgba(0,0,0,.06);transition:.15s ease-in-out;font-size:.92rem
    }
    .btn-primary-cf:hover{background:var(--gold-700);color:#fff}

    .wrap-paper{padding:14px}
    .paper{
      max-width:680px;margin:14px auto 40px;background:#fff;border:1px solid var(--line);
      border-radius:18px; padding:26px 24px; box-shadow:0 10px 30px rgba(17,24,39,.04);
    }
    .brand{font-weight:900;font-size:26px;margin-bottom:4px}
    .meta{display:flex;justify-content:space-between;gap:12px;font-size:.95rem;color:var(--muted)}
    .to{text-align:right}
    .rule{border-top:2px dashed #676c74;margin:14px 0;opacity:.9}

    .hdr{display:flex;justify-content:space-between;color:var(--muted);font-weight:600;padding:0 4px 8px;border-bottom:1px dashed var(--line)}
    .items{margin-top:6px}
    .item{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;padding:12px 4px;border-bottom:1px dashed var(--line)}
    .item-name{font-weight:700;font-size:1rem}
    .item-sub{color:var(--muted);font-size:.9rem;margin-top:3px}
    .item-subtotal{font-weight:700;white-space:nowrap;font-size:1rem}
    .row{display:flex;justify-content:space-between;align-items:center;padding:10px 4px;font-size:1rem}
    .row.sub{color:var(--muted);font-weight:600}
    .row.total{font-weight:800;font-size:1.08rem;border-top:2px dotted var(--line);margin-top:4px}
    .pill{display:inline-block;padding:.36rem .9rem;border:1px solid var(--line);border-radius:999px;font-weight:800;letter-spacing:.22rem;color:#111;background:#fafafa;font-size:.9rem}
    .muted{color:var(--muted);font-size:.92rem}
    .rule-strong{border-top:2px dashed #444;margin:14px 0 10px}

    @media (max-width:540px){
      .brand{font-size:22px}
      .paper{padding:22px 18px;border-radius:16px}
      .item-name{font-size:.98rem}
      .item-subtotal{font-size:.98rem}
      .row{font-size:.98rem}
      .muted{font-size:.9rem}
    }
    @media print{
      @page{ size:80mm auto; margin:4mm; }
      .topbar{display:none!important}
      .wrap-paper{padding:0}
      .paper{border:none;border-radius:0;margin:0;box-shadow:none}
      .brand{font-size:20px}
    }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="wrap">
      <a class="back-link" href="<?= h(BASE_URL) ?>/public/karyawan/orders.php">
        <span class="chev">←</span> Kembali ke Orders
      </a>
      <button class="btn-primary-cf" onclick="window.print()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6 9V3h12v6h-2V5H8v4H6zM6 14H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-2v5H6v-5zm2 0v3h8v-3H8z"/></svg>
        Cetak
      </button>
    </div>
  </div>

  <div class="wrap-paper">
    <main class="paper">
      <div class="brand">Caffora</div>
      <div class="meta">
        <div>
          <div>Invoice: <b><?= h($ord['invoice_no']) ?></b></div>
          <div>Tanggal: <?= h(date('d M Y H:i', strtotime($ord['created_at']))) ?></div>
        </div>
        <div class="to">
          <div class="muted">Kepada:</div>
          <div><b><?= h($ord['customer_name']) ?></b></div>
          <div><?= h($ord['service_type']==='dine_in'?'Dine In':'Take Away') ?><?= $ord['table_no']? ', Meja '.h($ord['table_no']) : '' ?></div>
        </div>
      </div>

      <div class="rule"></div>

      <div class="hdr"><div>Item</div><div>Subtotal</div></div>

      <div class="items">
        <?php foreach($items as $it): $sub=(float)$it['qty']*(float)$it['price']; ?>
          <div class="item">
            <div>
              <div class="item-name"><?= h($it['menu_name'] ?? 'Menu') ?></div>
              <div class="item-sub">Qty: <?= (int)$it['qty'] ?> × <?= rp((float)$it['price']) ?></div>
            </div>
            <div class="item-subtotal"><?= rp($sub) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="row sub"><div>Subtotal</div><div><?= rp((float)$ord['total']) ?></div></div>
      <div class="row total"><div>Total</div><div><?= rp((float)$ord['total']) ?></div></div>

      <div class="row" style="margin-top:8px">
        <div class="pill"><?= $ord['payment_status']==='paid' ? 'L U N A S' : 'B E L U M  L U N A S' ?></div>
        <div class="muted">Metode Pembayaran: <b><?= h(strtoupper(str_replace('_',' ',$ord['payment_method'] ?? '-'))) ?></b></div>
      </div>

      <div class="rule-strong"></div>

      <div class="muted">
        <?php if ($inv): ?>
          Tagihan: <b><?= rp((float)$inv['amount']) ?></b> • Diterbitkan: <?= h(date('d M Y H:i', strtotime($inv['issued_at']))) ?><br>
        <?php endif; ?>
        * Harga sudah termasuk PPN. Terima kasih telah berbelanja di <b>Caffora</b>.
      </div>
    </main>
  </div>
</body>
</html>