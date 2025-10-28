<?php
// public/customer/history.php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../../backend/auth_guard.php';
require_login(['customer']);
require_once __DIR__ . '/../../backend/config.php'; // $conn, BASE_URL, h()

$userId   = (int)($_SESSION['user_id'] ?? 0);
$userName = (string)($_SESSION['user_name'] ?? '');

function rupiah(float $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }

/* -------- Orders milik user -------- */
$orders = [];
if ($userId > 0) {
  $sql  = "SELECT id, invoice_no, customer_name, service_type, table_no,
                  total, order_status, payment_status, payment_method, created_at
           FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $orders = $stmt->get_result()?->fetch_all(MYSQLI_ASSOC) ?? [];
  $stmt->close();
}

/* -------- Items & Invoices (bulk) -------- */
$orderIds     = array_column($orders, 'id');
$itemsByOrder = [];
$invByOrder   = [];

if ($orderIds) {
  $place = implode(',', array_fill(0, count($orderIds), '?'));
  $types = str_repeat('i', count($orderIds));

  // Items
  $sqlI  = "SELECT oi.order_id, oi.menu_id, oi.qty, oi.price, m.name AS menu_name, m.image AS menu_image
            FROM order_items oi
            LEFT JOIN menu m ON m.id=oi.menu_id
            WHERE oi.order_id IN ($place)
            ORDER BY oi.order_id, oi.id";
  $stmtI = $conn->prepare($sqlI);
  $stmtI->bind_param($types, ...$orderIds);
  $stmtI->execute();
  $resI = $stmtI->get_result();
  while ($row = $resI->fetch_assoc()) {
    $itemsByOrder[(int)$row['order_id']][] = $row;
  }
  $stmtI->close();

  // Invoices
  $sqlV  = "SELECT order_id, amount, issued_at FROM invoices WHERE order_id IN ($place)";
  $stmtV = $conn->prepare($sqlV);
  $stmtV->bind_param($types, ...$orderIds);
  $stmtV->execute();
  $resV = $stmtV->get_result();
  while ($row = $resV->fetch_assoc()) {
    $invByOrder[(int)$row['order_id']] = $row;
  }
  $stmtV->close();
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Riwayat Pesanan — Caffora</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --ink:#111827;          /* teks utama */
      --muted:#6b7280;        /* teks sekunder */
      --line:#e5e7eb;         /* garis halus */
      --pill:#f3f4f6;         /* chip netral */
      --ok:#10b981;           /* hijau */
      --warn:#f59e0b;         /* oranye */
      --danger:#ef4444;       /* merah */
      --brand:#111827;        /* aksen hitam elegan */
      --radius:16px;
    }
    *{ font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial; }
    body{ background:#fff; color:var(--ink); }

    .topbar{ background:#fff; border-bottom:1px solid var(--line); }
    .back-link{ color:var(--ink); text-decoration:none; font-weight:600; }
    .back-link i{ margin-right:.5rem; }

    .page{ max-width:920px; margin:28px auto; padding:0 16px; }
    .title{ font-weight:900; letter-spacing:.2px; }

    /* Kartu minimalis */
    .cardx{
      border:2px solid var(--line);
      border-radius:var(--radius);
      background:#fff;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .cardx + .cardx{ margin-top:18px; }

    .card-hd{
      display:flex; align-items:center; justify-content:space-between;
      gap:12px; padding:14px 18px; border-bottom:1px solid var(--line);
    }
    .inv-no{ font-weight:800; letter-spacing:.2px; }
    .stamp{ color:var(--muted); font-size:.92rem; }

    /* Chips status */
    .chips{ display:flex; flex-wrap:wrap; gap:8px; padding:12px 18px; }
    .chip{
      border-radius:999px; padding:.4rem .75rem; font-weight:700;
      font-size:.82rem; display:inline-flex; align-items:center; gap:.45rem;
      background:var(--pill); color:#374151; border:1px solid var(--line);
    }
    .chip i{ font-size:.95rem; }
    .chip--order.processing{ background:#fff7ed; border-color:#fde68a; color:#92400e; }
    .chip--order.ready{ background:#ecfeff; border-color:#a5f3fc; color:#065f46; }
    .chip--order.completed{ background:#ecfdf5; border-color:#bbf7d0; color:#065f46; }
    .chip--order.cancelled{ background:#f9fafb; border-color:#e5e7eb; color:#111827; }
    .chip--pay.paid{ background:#ecfdf5; border-color:#bbf7d0; color:#065f46; }
    .chip--pay.pending{ background:#fff7ed; border-color:#fde68a; color:#92400e; }
    .chip--pay.failed{ background:#fee2e2; border-color:#fecaca; color:#991b1b; }
    .chip--pay.refunded{ background:#eef2ff; border-color:#c7d2fe; color:#3730a3; }
    .chip--pay.overdue{ background:#f3f4f6; border-color:#e5e7eb; color:#111827; }

    /* Item baris */
    .items{ padding:6px 18px 2px; }
    .item-row{
      display:grid; grid-template-columns:56px 1fr auto;
      gap:12px; align-items:center; padding:12px 0;
      border-bottom:1px dashed var(--line);
    }
    .thumb{ width:56px; height:56px; border-radius:12px; object-fit:cover; background:#fff; border:1px solid var(--line); }
    .item-name{ font-weight:700; }
    .item-sub{ color:var(--muted); font-size:.9rem; }

    /* Footer */
    .card-ft{
      padding:12px 18px; display:flex; justify-content:space-between; align-items:center; gap:12px;
      border-top:1px solid var(--line);
    }
    .total{ font-weight:900; font-size:1.05rem; }
    .subtle{ color:var(--muted); font-size:.92rem; }

    /* Toggle details */
    .toggle{
      appearance:none; border:1px solid var(--line); background:#fff;
      border-radius:999px; padding:.48rem .9rem; font-weight:700; font-size:.86rem;
      display:inline-flex; align-items:center; gap:.45rem;
    }
    .toggle:hover{ background:#f9fafb; }
  </style>
</head>
<body>
  <!-- Topbar -->
  <div class="topbar py-2">
    <div class="container d-flex align-items-center">
      <a class="back-link" href="<?= h(BASE_URL) ?>/public/customer/index.php"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
    </div>
  </div>

  <main class="page">
    <h2 class="title mb-2">Riwayat Pesanan</h2>
    <div class="mb-4 text-muted">Hai, <strong><?= h($userName) ?></strong>. Berikut ringkasan pesananmu.</div>

    <?php if (!$orders): ?>
      <div class="alert alert-light border" role="alert">Belum ada pesanan.</div>
    <?php else: ?>
      <?php foreach ($orders as $ord):
        $oid   = (int)$ord['id'];
        $items = $itemsByOrder[$oid] ?? [];
        $inv   = $invByOrder[$oid]   ?? null;

        // ikon status pesanan (tidak ada gear)
        $orderIcon = [
          'new'        => 'bi-bag-plus',
          'processing' => 'bi-hourglass-split',
          'ready'      => 'bi-clipboard-check',
          'completed'  => 'bi-check-circle',
          'cancelled'  => 'bi-x-circle'
        ][$ord['order_status']] ?? 'bi-receipt';

        // ikon pembayaran
        $payIcon = [
          'pending'  => 'bi-hourglass-split',
          'paid'     => 'bi-check-circle',
          'failed'   => 'bi-x-circle',
          'refunded' => 'bi-arrow-counterclockwise',
          'overdue'  => 'bi-exclamation-triangle'
        ][$ord['payment_status']] ?? 'bi-cash-coin';

        $first = $items[0] ?? null;
        $restCount = max(0, count($items) - 1);
      ?>
      <section class="cardx">
        <!-- header -->
        <div class="card-hd">
          <div class="inv-no"><?= h($ord['invoice_no']) ?></div>
          <div class="stamp"><i class="bi bi-clock me-1"></i><?= h(date('d M Y H:i', strtotime($ord['created_at']))) ?></div>
        </div>

        <!-- chips -->
        <div class="chips">
          <span class="chip chip--order <?= h($ord['order_status']) ?>"><i class="bi <?= $orderIcon ?>"></i><?= h($ord['order_status']) ?></span>
          <span class="chip chip--pay <?= h($ord['payment_status']) ?>"><i class="bi <?= $payIcon ?>"></i><?= h($ord['payment_status']) ?></span>
          <?php if (!empty($ord['payment_method'])): ?>
            <span class="chip"><i class="bi bi-wallet2"></i><?= h(strtoupper(str_replace('_',' ',$ord['payment_method']))) ?></span>
          <?php endif; ?>
          <span class="chip"><i class="bi bi-shop"></i><?= h($ord['service_type']==='dine_in'?'Dine In':'Take Away') ?></span>
          <?php if (!empty($ord['table_no'])): ?>
            <span class="chip"><i class="bi bi-upc-scan"></i>Meja <?= h($ord['table_no']) ?></span>
          <?php endif; ?>
        </div>

        <!-- items (thumbnail + collapse) -->
        <div class="items">
          <?php if ($first): ?>
            <div class="item-row">
              <?php if (!empty($first['menu_image'])): ?>
                <img class="thumb" src="<?= h(BASE_URL . '/public/' . ltrim($first['menu_image'],'/')) ?>" alt="">
              <?php else: ?>
                <div class="thumb d-flex align-items-center justify-content-center text-muted">—</div>
              <?php endif; ?>
              <div>
                <div class="item-name"><?= h($first['menu_name'] ?? 'Menu') ?></div>
                <div class="item-sub">Qty: <?= (int)$first['qty'] ?> × <?= rupiah((float)$first['price']) ?></div>
              </div>
              <div class="fw-bold"><?= rupiah((float)$first['qty']*(float)$first['price']) ?></div>
            </div>
          <?php endif; ?>

          <?php if ($restCount > 0): ?>
            <div class="collapse" id="items-<?= $oid ?>">
              <?php for ($i=1; $i<count($items); $i++): $it = $items[$i]; ?>
                <div class="item-row">
                  <?php if (!empty($it['menu_image'])): ?>
                    <img class="thumb" src="<?= h(BASE_URL . '/public/' . ltrim($it['menu_image'],'/')) ?>" alt="">
                  <?php else: ?>
                    <div class="thumb d-flex align-items-center justify-content-center text-muted">—</div>
                  <?php endif; ?>
                  <div>
                    <div class="item-name"><?= h($it['menu_name'] ?? 'Menu') ?></div>
                    <div class="item-sub">Qty: <?= (int)$it['qty'] ?> × <?= rupiah((float)$it['price']) ?></div>
                  </div>
                  <div class="fw-bold"><?= rupiah((float)$it['qty']*(float)$it['price']) ?></div>
                </div>
              <?php endfor; ?>
            </div>

            <div class="px-3 pb-2">
              <button class="toggle" type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#items-<?= $oid ?>"
                      aria-expanded="false"
                      aria-controls="items-<?= $oid ?>">
                <i class="bi bi-chevron-down"></i>
                <span class="toggle-text-<?= $oid ?>">Tampilkan <?= $restCount ?> item lainnya</span>
              </button>
            </div>
          <?php endif; ?>
        </div>

        <!-- footer -->
       <!-- footer -->
        <div class="card-ft">
          <div class="subtle">
            <?php if ($ord['payment_status'] === 'paid'): ?>
              Pembayaran: <strong>Lunas</strong>
              <?php if ($inv): ?> • <?= h(date('d M Y H:i', strtotime($inv['issued_at']))) ?><?php endif; ?>
            <?php else: ?>
              <?php if ($inv): ?>
                Invoice: <?= h(date('d M Y H:i', strtotime($inv['issued_at']))) ?><?php if (isset($inv['amount'])): ?> • Tagihan: <strong><?= rupiah((float)$inv['amount']) ?></strong><?php endif; ?>
              <?php else: ?>
                Menunggu penerbitan invoice
              <?php endif; ?>
            <?php endif; ?>
          </div>

          <div class="d-flex align-items-center gap-2">
            <a class="toggle" href="<?= h(BASE_URL) ?>/public/customer/receipt.php?order=<?= $oid ?>" target="_blank" rel="noopener">
              <i class="bi bi-receipt"></i> Lihat Struk
            </a>
            <div class="total"><?= rupiah((float)$ord['total']) ?></div>
          </div>
        </div>
      </section>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Update label tombol collapse saat buka/tutup
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn=>{
      const target = document.querySelector(btn.dataset.bsTarget);
      const oid = btn.dataset.bsTarget.replace('#items-','');
      const textEl = document.querySelector('.toggle-text-'+oid);
      if (!target || !textEl) return;

      target.addEventListener('show.bs.collapse', ()=>{
        textEl.textContent = 'Sembunyikan rincian';
        btn.querySelector('i').className='bi bi-chevron-up';
      });
      target.addEventListener('hide.bs.collapse', ()=>{
        const count = target.querySelectorAll('.item-row').length;
        textEl.textContent = 'Tampilkan '+count+' item lainnya';
        btn.querySelector('i').className='bi bi-chevron-down';
      });
    });
  </script>
</body>
</html>