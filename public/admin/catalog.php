<?php
// public/admin/catalog.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../backend/config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
  header('Location: ' . BASE_URL . '/public/login.html'); exit;
}

function rupiah($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

$msg = $_GET['msg'] ?? '';

/* ===== Actions (dipertahankan) ===== */
// ... (biarkan sama persis seperti punyamu)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act   = $_POST['action'] ?? '';
  $id    = (int)($_POST['id'] ?? 0);
  $name  = trim((string)($_POST['name'] ?? ''));
  $cat   = strtolower(trim((string)($_POST['category'] ?? '')));
  $cat   = in_array($cat, ['food','pastry','drink'], true) ? $cat : 'food';
  $price = (float)($_POST['price'] ?? 0);
  $stat  = ($_POST['stock_status'] ?? 'Ready') === 'Sold Out' ? 'Sold Out' : 'Ready';

  $imagePath = null;
  if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','webp'], true)) {
      $updir = __DIR__ . '/../../public/uploads/menu';
      if (!is_dir($updir)) @mkdir($updir, 0777, true);
      $new = 'm_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
      if (move_uploaded_file($_FILES['image']['tmp_name'], $updir . '/' . $new)) {
        $imagePath = 'uploads/menu/' . $new;
      }
    }
  }

  if ($act === 'add') {
    $stmt = $conn->prepare("INSERT INTO menu(name,category,image,price,stock_status) VALUES (?,?,?,?,?)");
    $stmt->bind_param('sssds', $name, $cat, $imagePath, $price, $stat);
    $ok = $stmt->execute(); $stmt->close();
    header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode($ok?'Menu ditambahkan.':'Gagal menambah menu.')); exit;
  }

  if ($act === 'edit' && $id > 0) {
    if ($imagePath) {
      $stmt = $conn->prepare("UPDATE menu SET name=?, category=?, image=?, price=?, stock_status=? WHERE id=?");
      $stmt->bind_param('sssdsi', $name, $cat, $imagePath, $price, $stat, $id);
    } else {
      $stmt = $conn->prepare("UPDATE menu SET name=?, category=?, price=?, stock_status=? WHERE id=?");
      $stmt->bind_param('ssdsi', $name, $cat, $price, $stat, $id);
    }
    $ok = $stmt->execute(); $stmt->close();
    header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode($ok?'Menu diperbarui.':'Gagal mengedit menu.')); exit;
  }
}

if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $stmt = $conn->prepare("DELETE FROM menu WHERE id=?");
  $stmt->bind_param('i', $id);
  $stmt->execute(); $stmt->close();
  header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode('Menu dihapus.')); exit;
}

/* ===== Data list ===== */
$res = $conn->query("SELECT * FROM menu ORDER BY created_at DESC");
$menus = $res?->fetch_all(MYSQLI_ASSOC) ?? [];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola Catalog — Admin Desk</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root{
      --gold:#ffd54f;
      --gold-200:#ffe883;
      --c-border:#E5E7EB;
      --c-ink:#111827;
      --radius:18px;
      --grid: rgba(255,213,79,.35); /* garis grid lembut */
    }
    body{ background:#FAFAFA; color:var(--c-ink); font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial; }

    /* Sidebar konsisten */
    .sidebar{
      width:280px; background:linear-gradient(180deg,var(--gold-200),var(--gold));
      border-right:1px solid rgba(0,0,0,.08);
      position:fixed; inset:0 auto 0 0; padding:24px 18px; overflow-y:auto;
    }
    .brand{ font-weight:800; font-size:24px; color:#111; }
    .nav-link{ display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:12px; color:#111; font-weight:600; transition:background .2s ease; }
    .nav-link:hover{ background:rgba(255,213,79,.25); }
    .nav-link.active{ background:var(--gold-200); }
    .nav-link .bi{ color:#111; }
    .sidebar hr{ border-color:rgba(0,0,0,.08); opacity:1; }

    /* Content */
    .content{ margin-left:280px; padding:24px; }

    /* Komponen */
    .cardx{
      background:#fff; border:1px solid var(--gold); border-radius:var(--radius);
      overflow:hidden; /* penting agar sudut tabel rapi menyatu card */
    }
    .btn-saffron{
      background:var(--gold); border-color:var(--gold); color:#111; font-weight:600; transition:all .2s ease;
    }
    .btn-saffron:hover{ background:var(--gold-200); border-color:var(--gold-200); color:#111; }
    .thumb{ width:48px; height:48px; object-fit:cover; border:1px solid var(--c-border); border-radius:10px; background:#fff; }

    /* ====== TABLE GRID RAPI ====== */
    .table-grid{ margin:0; border-collapse:separate; border-spacing:0; }
    .table-grid thead th{
      background:#fffbe6; /* krem */
      border-bottom:1px solid var(--grid);
      border-right:1px solid var(--grid);
      padding:14px 16px;
      white-space:nowrap;
      font-weight:700;
    }
    .table-grid thead th:first-child{ border-left:1px solid var(--grid); }
    .table-grid thead th:last-child{ border-right:1px solid var(--grid); }

    .table-grid tbody td{
      border-right:1px solid var(--grid);
      border-bottom:1px solid var(--grid);
      padding:14px 16px;
      vertical-align:middle;
      background:#fff;
    }
    .table-grid tbody tr td:first-child{ border-left:1px solid var(--grid); }

    /* sudut halus di header & baris terakhir */
    .table-grid thead th:first-child{ border-top-left-radius:12px; }
    .table-grid thead th:last-child{ border-top-right-radius:12px; }
    .table-grid tbody tr:last-child td:first-child{ border-bottom-left-radius:12px; }
    .table-grid tbody tr:last-child td:last-child{ border-bottom-right-radius:12px; }

    /* rapikan tombol aksi */
    .table-grid .btn{ border-radius:10px; }
  </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="brand mb-2">Admin Desk</div>
  <hr class="my-3">
  <nav class="nav flex-column gap-2" id="sidebar-nav">
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/index.php"><i class="bi bi-house-door"></i> Dashboard</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/orders.php"><i class="bi bi-receipt"></i> Orders</a>
    <a class="nav-link active" href="#"><i class="bi bi-box-seam"></i> Catalog</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/users.php"><i class="bi bi-people"></i> Users</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/finance.php"><i class="bi bi-cash-coin"></i> Finance</a>
    <hr>
    <a class="nav-link" href="#"><i class="bi bi-question-circle"></i> Help Center</a>
    <a class="nav-link" href="#"><i class="bi bi-gear"></i> Settings</a>
    <a class="nav-link" href="<?= BASE_URL ?>/backend/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </nav>
</aside>

<!-- Content -->
<main class="content">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="fw-bold m-0">Kelola Menu</h2>
    <button class="btn btn-saffron" data-bs-toggle="modal" data-bs-target="#menuModal" onclick="openAdd()">+ Tambah Menu</button>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-warning py-2"><?= h($msg) ?></div>
  <?php endif; ?>

  <div class="cardx">
    <div class="table-responsive">
      <table class="table table-grid align-middle">
        <thead>
          <tr>
            <th style="width:70px">ID</th>
            <th style="width:90px">Gambar</th>
            <th>Nama</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Status</th>
            <th style="width:220px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$menus): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data.</td></tr>
          <?php else: foreach ($menus as $m): ?>
            <tr>
              <td><?= (int)$m['id'] ?></td>
              <td>
                <?php
                  $src = '';
                  if (!empty($m['image'])) {
                    $img = (string)$m['image'];
                    if (preg_match('~^https?://~i', $img) || str_starts_with($img, 'data:')) $src = $img;
                    else $src = rtrim(BASE_URL,'/') . '/public/' . ltrim($img,'/');
                  } else {
                    $src = 'https://picsum.photos/seed/caffora-thumb/96/96';
                  }
                ?>
                <img class="thumb" src="<?= h($src) ?>" alt="">
              </td>
              <td><?= h($m['name']) ?></td>
              <td><?= h($m['category']) ?></td>
              <td><?= rupiah($m['price']) ?></td>
              <td><?= $m['stock_status']==='Ready' ? '<span class="badge text-bg-success">Ready</span>' : '<span class="badge text-bg-danger">Sold Out</span>' ?></td>
              <td>
                <button class="btn btn-sm btn-outline-primary me-1"
                        onclick='openEdit(<?= (int)$m["id"] ?>, <?= json_encode($m, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>)'>Edit</button>
                <a class="btn btn-sm btn-outline-danger"
                   href="?delete=<?= (int)$m['id'] ?>"
                   onclick="return confirm('Hapus menu ini?')">Hapus</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Modal Add/Edit (tetap) -->
<div class="modal fade" id="menuModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title" id="menuTitle">Tambah Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" id="action" value="add">
        <input type="hidden" name="id" id="id">
        <div class="mb-2">
          <label class="form-label">Nama</label>
          <input type="text" class="form-control" name="name" id="name" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Kategori</label>
          <select class="form-select" name="category" id="category" required>
            <option value="food">Food</option>
            <option value="pastry">Pastry</option>
            <option value="drink">Drink</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Harga</label>
          <input type="number" class="form-control" name="price" id="price" min="0" step="1" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Status Stok</label>
          <select class="form-select" name="stock_status" id="stock_status">
            <option value="Ready">Ready</option>
            <option value="Sold Out">Sold Out</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Gambar (opsional)</label>
          <input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/webp">
          <div class="form-text">JPG/PNG/WEBP &lt; 2MB</div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Batal</button>
        <button class="btn btn-saffron" type="submit">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Nav aktif saat diklik
  document.querySelectorAll('#sidebar-nav .nav-link').forEach(link=>{
    link.addEventListener('click',function(){
      document.querySelectorAll('#sidebar-nav .nav-link').forEach(l=>l.classList.remove('active'));
      this.classList.add('active');
    });
  });

  const modalEl = document.getElementById('menuModal');
  const modal = new bootstrap.Modal(modalEl);

  function openAdd(){
    document.getElementById('menuTitle').textContent = 'Tambah Menu';
    document.getElementById('action').value = 'add';
    document.getElementById('id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('category').value = 'food';
    document.getElementById('price').value = '';
    document.getElementById('stock_status').value = 'Ready';
  }
  function openEdit(id, row){
    document.getElementById('menuTitle').textContent = 'Edit Menu';
    document.getElementById('action').value = 'edit';
    document.getElementById('id').value = id;
    document.getElementById('name').value = row.name || '';
    document.getElementById('category').value = (row.category || 'food').toLowerCase();
    document.getElementById('price').value = (row.price || 0);
    document.getElementById('stock_status').value = (row.stock_status === 'Sold Out' ? 'Sold Out' : 'Ready');
    modal.show();
  }
</script>
</body>
</html>
