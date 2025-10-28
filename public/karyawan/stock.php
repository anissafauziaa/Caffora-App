<?php
// public/karyawan/stock.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../backend/auth_guard.php';
require_login(['karyawan','admin']); // staff & admin boleh
require_once __DIR__ . '/../../backend/config.php'; // BASE_URL, $conn, h()

// Helper format harga (jangan definisikan h() di sini agar tidak redeclare)
function rupiah($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

/* ====== Actions: update status stok (Ready / Sold Out) ====== */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id   = (int)($_POST['id'] ?? 0);
  $stat = ($_POST['stock_status'] ?? '') === 'Sold Out' ? 'Sold Out' : 'Ready';

  if ($id > 0) {
    $stmt = $conn->prepare("UPDATE menu SET stock_status=? WHERE id=?");
    $stmt->bind_param('si', $stat, $id);
    $ok = $stmt->execute();
    $stmt->close();
    $msg = $ok ? 'Status stok diperbarui.' : 'Gagal memperbarui status.';
  } else {
    $msg = 'Data tidak valid.';
  }
}

/* ====== Filter ====== */
$q   = trim((string)($_GET['q'] ?? ''));
$cat = strtolower(trim((string)($_GET['category'] ?? ''))); // food|pastry|drink|''

$where = []; $types=''; $params=[];
if ($q !== '') {
  $where[]='(name LIKE ? OR category LIKE ?)';
  $like='%'.$q.'%'; $params[]=$like; $params[]=$like; $types.='ss';
}
if (in_array($cat, ['food','pastry','drink'], true)) {
  $where[]='LOWER(category)=?'; $params[]=$cat; $types.='s';
}

$sql = 'SELECT * FROM menu';
if ($where) $sql .= ' WHERE '.implode(' AND ', $where);
$sql .= ' ORDER BY created_at DESC';

$menus = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
  if ($params) { $stmt->bind_param($types, ...$params); }
  $stmt->execute();
  $res = $stmt->get_result();
  $menus = $res?->fetch_all(MYSQLI_ASSOC) ?? [];
  $stmt->close();
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Stok Menu — Karyawan Desk</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --gold:#ffd54f; --gold-200:#ffe883;
      --ink:#111827; --bd:#E5E7EB; --radius:18px;
    }
    body{ background:#FAFAFA; color:var(--ink); font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial }

    /* Sidebar: sama dengan admin */
    .sidebar{
      width:280px;background:linear-gradient(180deg,var(--gold-200),var(--gold));
      border-right:1px solid rgba(0,0,0,.08); position:fixed; inset:0 auto 0 0;
      padding:24px 18px; overflow-y:auto;
    }
    .brand{font-weight:800;font-size:24px;color:#111;}
    .nav-link{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;
      font-weight:600;color:#111;}
    .nav-link:hover{background:rgba(255,213,79,.25)} .nav-link.active{background:var(--gold-200)}
    .sidebar hr{border-color:rgba(0,0,0,.08);opacity:1}
    .content{margin-left:280px;padding:24px}

    /* Kartu / tabel */
    .cardx{background:#fff;border:1px solid var(--gold);border-radius:var(--radius)}
    .table th,.table td{vertical-align:middle}
    .table thead th{background:#fffbe6}
    .thumb{width:48px;height:48px;object-fit:cover;border:1px solid var(--bd);border-radius:12px;background:#fff}

    /* Toolbar (filter) */
    .toolbar{background:#fff;border:1px solid var(--gold);border-radius:var(--radius);padding:12px}
    .toolbar .form-control,.toolbar .form-select{height:44px;border-radius:12px;border:1px solid var(--bd)}
    .toolbar .input-group-text{background:#fff;border-radius:12px;border:1px solid var(--bd)}
    .btn-saffron{background:var(--gold);border-color:var(--gold);color:#111;font-weight:600}
    .btn-saffron:hover{background:var(--gold-200);border-color:var(--gold-200);color:#111}
  </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="brand mb-2">Karyawan Desk</div><hr>
  <nav class="nav flex-column gap-2">
    <a class="nav-link" href="<?= BASE_URL ?>/public/karyawan/index.php"><i class="bi bi-house-door"></i> Dashboard</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/karyawan/orders.php"><i class="bi bi-receipt"></i> Orders</a>
    <a class="nav-link active" href="#"><i class="bi bi-box-seam"></i> Menu Stock</a>
    <hr>
    <a class="nav-link" href="#"><i class="bi bi-question-circle"></i> Help Center</a>
    <a class="nav-link" href="<?= BASE_URL ?>/backend/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </nav>
</aside>

<!-- Content -->
<main class="content">
  <h3 class="fw-bold mb-3">Stok Menu</h3>

  <?php if ($msg): ?>
    <div class="alert alert-warning py-2"><?= h($msg) ?></div>
  <?php endif; ?>

  <!-- Toolbar / Filter -->
  <form class="toolbar mb-3" method="get">
    <div class="row g-2 align-items-center">
      <div class="col-12 col-lg">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input class="form-control" name="q" placeholder="Cari nama / kategori" value="<?= h($q) ?>">
        </div>
      </div>
      <div class="col-6 col-lg-auto">
        <select name="category" class="form-select">
          <option value="">Semua Kategori</option>
          <option value="food"   <?= $cat==='food'?'selected':'' ?>>Food</option>
          <option value="pastry" <?= $cat==='pastry'?'selected':'' ?>>Pastry</option>
          <option value="drink"  <?= $cat==='drink'?'selected':'' ?>>Drink</option>
        </select>
      </div>
      <div class="col-12 col-lg-auto">
        <button class="btn btn-saffron w-100">Terapkan</button>
      </div>
      <div class="col-12 col-lg-auto">
        <a class="btn btn-outline-secondary w-100" href="<?= BASE_URL ?>/public/karyawan/stock.php">Reset</a>
      </div>
    </div>
  </form>

  <div class="cardx p-3">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th style="width:70px">ID</th>
            <th style="width:70px">Gambar</th>
            <th>Nama</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Status</th>
            <th style="width:220px">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$menus): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data.</td></tr>
        <?php else: foreach ($menus as $m): ?>
          <tr>
            <td><?= (int)$m['id'] ?></td>
            <td>
              <?php if (!empty($m['image'])): ?>
                <img class="thumb" src="<?= h(BASE_URL . '/public/' . ltrim($m['image'],'/')) ?>" alt="">
              <?php endif; ?>
            </td>
            <td><?= h($m['name']) ?></td>
            <td><?= h($m['category']) ?></td>
            <td><?= rupiah($m['price']) ?></td>
            <td>
              <?= ($m['stock_status'] === 'Ready')
                ? '<span class="badge text-bg-success">Ready</span>'
                : '<span class="badge text-bg-danger">Sold Out</span>' ?>
            </td>
            <td>
              <form class="d-inline" method="post">
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <input type="hidden" name="stock_status" value="<?= $m['stock_status']==='Ready' ? 'Sold Out' : 'Ready' ?>">
                <?php if ($m['stock_status']==='Ready'): ?>
                  <button class="btn btn-sm btn-outline-danger" type="submit">
                    <i class="bi bi-x-circle me-1"></i> Set Sold Out
                  </button>
                <?php else: ?>
                  <button class="btn btn-sm btn-outline-success" type="submit">
                    <i class="bi bi-check2-circle me-1"></i> Set Ready
                  </button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>