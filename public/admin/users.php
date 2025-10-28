<?php
// public/admin/users.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../backend/config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
  header('Location: ' . BASE_URL . '/public/login.html'); exit;
}

// NOTE: fungsi h() sudah ada di config.php

function emailUsedByOther(mysqli $conn, string $email, int $exceptId = 0): bool {
  $sql = $exceptId > 0
    ? "SELECT id FROM users WHERE email=? AND id<>? LIMIT 1"
    : "SELECT id FROM users WHERE email=? LIMIT 1";
  $stmt = $conn->prepare($sql);
  if ($exceptId > 0) { $stmt->bind_param('si', $email, $exceptId); }
  else { $stmt->bind_param('s', $email); }
  $stmt->execute(); $stmt->store_result();
  $exists = $stmt->num_rows > 0;
  $stmt->close();
  return $exists;
}

$msg = $_GET['msg'] ?? '';

/* ================== Actions ================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act    = $_POST['action'] ?? '';
  $id     = (int)($_POST['id'] ?? 0);
  $name   = trim((string)($_POST['name'] ?? ''));
  $email  = trim((string)($_POST['email'] ?? ''));
  $role   = trim((string)($_POST['role'] ?? 'customer'));
  $status = trim((string)($_POST['status'] ?? 'active'));
  $pass   = (string)($_POST['password'] ?? '');

  // normalisasi nilai yang valid
  if (!in_array($role, ['admin','karyawan','customer'], true))  $role = 'customer';
  if (!in_array($status, ['pending','active'], true))           $status = 'active';

  if ($act === 'add') {
    if ($name === '' || $email === '' || $pass === '') {
      header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode('Nama, email, dan password wajib.')); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode('Format email tidak valid.')); exit;
    }
    if (emailUsedByOther($conn, $email, 0)) {
      header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode('Email sudah digunakan.')); exit;
    }
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users(name,email,password,role,status) VALUES (?,?,?,?,?)");
    $stmt->bind_param('sssss', $name, $email, $hash, $role, $status);
    $ok = $stmt->execute(); $stmt->close();
    header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode($ok ? 'User ditambahkan.' : 'Gagal menambah user.')); exit;
  }

  if ($act === 'edit' && $id > 0) {
    if ($name === '' || $email === '') {
      header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode('Nama dan email wajib.')); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode('Format email tidak valid.')); exit;
    }
    if (emailUsedByOther($conn, $email, $id)) {
      header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode('Email sudah dipakai user lain.')); exit;
    }

    if ($pass !== '') {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, role=?, status=? WHERE id=?");
      $stmt->bind_param('sssssi', $name, $email, $hash, $role, $status, $id);
    } else {
      $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=?, status=? WHERE id=?");
      $stmt->bind_param('ssssi', $name, $email, $role, $status, $id);
    }
    $ok = $stmt->execute(); $stmt->close();
    header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode($ok ? 'User diperbarui.' : 'Gagal mengedit user.')); exit;
  }
}

if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute(); $stmt->close();
    header('Location: '.$_SERVER['PHP_SELF'].'?msg='.urlencode('User dihapus.')); exit;
  }
}

/* =============== Data list =============== */
$rows = [];
$res = $conn->query("SELECT id,name,email,role,status,created_at FROM users ORDER BY created_at DESC");
if ($res) { $rows = $res->fetch_all(MYSQLI_ASSOC); $res->close(); }

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola User — Admin Desk</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --gold:#ffd54f;
      --gold-200:#ffe883;
      --c-border:#E5E7EB;
      --c-ink:#111827;
      --radius:18px;
      --grid: rgba(255,213,79,.35);
    }
    body{ background:#FAFAFA; color:var(--c-ink); font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial; }

    /* ===== Sidebar (konsisten) ===== */
    .sidebar{
      width:280px;
      background:linear-gradient(180deg,var(--gold-200),var(--gold));
      border-right:1px solid rgba(0,0,0,.08);
      position:fixed; inset:0 auto 0 0;
      padding:24px 18px; overflow-y:auto;
    }
    .brand{ font-weight:800; font-size:24px; color:#111; }
    .nav-link{
      display:flex; align-items:center; gap:12px;
      padding:12px 14px; border-radius:12px;
      color:#111; font-weight:600; transition:background .2s ease;
    }
    .nav-link:hover{ background:rgba(255,213,79,.25); }
    .nav-link.active{ background:var(--gold-200); }
    .nav-link .bi{ color:#111; }
    .sidebar hr{ border-color:rgba(0,0,0,.08); opacity:1; }

    /* ===== Content ===== */
    .content{ margin-left:280px; padding:24px; }

    /* ===== Komponen ===== */
    .cardx{
      background:#fff; border:1px solid var(--gold);
      border-radius:var(--radius); overflow:hidden;
    }
    .btn-saffron{
      background:var(--gold); border-color:var(--gold);
      color:#111; font-weight:600; transition:all .2s ease;
    }
    .btn-saffron:hover{
      background:var(--gold-200); border-color:var(--gold-200);
      color:#111;
    }

    /* ===== Tabel grid rapi ===== */
    .table-grid{ margin:0; border-collapse:separate; border-spacing:0; }
    .table-grid thead th{
      background:#fffbe6;
      border-bottom:1px solid var(--grid);
      border-right:1px solid var(--grid);
      padding:14px 16px; white-space:nowrap; font-weight:700;
    }
    .table-grid thead th:first-child{ border-left:1px solid var(--grid); }
    .table-grid thead th:last-child{ border-right:1px solid var(--grid); }

    .table-grid tbody td{
      border-right:1px solid var(--grid);
      border-bottom:1px solid var(--grid);
      padding:14px 16px; vertical-align:middle; background:#fff;
    }
    .table-grid tbody td:first-child{ border-left:1px solid var(--grid); }

    .table-grid thead th:first-child{ border-top-left-radius:12px; }
    .table-grid thead th:last-child{ border-top-right-radius:12px; }
    .table-grid tbody tr:last-child td:first-child{ border-bottom-left-radius:12px; }
    .table-grid tbody tr:last-child td:last-child{ border-bottom-right-radius:12px; }
  </style>
</head>
<body>

<!-- ===== Sidebar ===== -->
<aside class="sidebar">
  <div class="brand mb-2">Admin Desk</div>
  <hr class="my-3">
  <nav class="nav flex-column gap-2" id="sidebar-nav">
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/index.php"><i class="bi bi-house-door"></i> Dashboard</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/orders.php"><i class="bi bi-receipt"></i> Orders</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/catalog.php"><i class="bi bi-box-seam"></i> Catalog</a>
    <a class="nav-link active" href="#"><i class="bi bi-people"></i> Users</a>
    <a class="nav-link" href="<?= BASE_URL ?>/public/admin/finance.php"><i class="bi bi-cash-coin"></i> Finance</a>
    <hr>
    <a class="nav-link" href="#"><i class="bi bi-question-circle"></i> Help Center</a>
    <a class="nav-link" href="#"><i class="bi bi-gear"></i> Settings</a>
    <a class="nav-link" href="<?= BASE_URL ?>/backend/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </nav>
</aside>

<!-- ===== Content ===== -->
<main class="content">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="fw-bold m-0">Kelola User</h2>
    <button class="btn btn-saffron" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openAdd()">+ Tambah User</button>
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
            <th>Nama</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Dibuat</th>
            <th style="width:220px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data.</td></tr>
          <?php else: foreach ($rows as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td><?= h($u['name']) ?></td>
              <td><?= h($u['email']) ?></td>
              <td><span class="badge text-bg-secondary text-capitalize"><?= h($u['role']) ?></span></td>
              <td>
                <?php if ($u['status']==='active'): ?>
                  <span class="badge text-bg-success">Aktif</span>
                <?php else: ?>
                  <span class="badge text-bg-warning text-dark">Pending</span>
                <?php endif; ?>
              </td>
              <td><small class="text-muted"><?= h($u['created_at']) ?></small></td>
              <td>
                <button class="btn btn-sm btn-outline-primary me-1"
                        onclick='openEdit(<?= (int)$u["id"] ?>, <?= json_encode($u, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>)'>Edit</button>
                <a class="btn btn-sm btn-outline-danger"
                   href="?delete=<?= (int)$u['id'] ?>"
                   onclick="return confirm(".'"Hapus user ini?"'.')">Hapus</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Modal Add/Edit -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post">
      <div class="modal-header">
        <h5 class="modal-title" id="userTitle">Tambah User</h5>
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
          <label class="form-label">Email</label>
          <input type="email" class="form-control" name="email" id="email" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Password <small class="text-muted">(kosongkan saat edit jika tidak ganti)</small></label>
          <input type="password" class="form-control" name="password" id="password" minlength="6">
        </div>
        <div class="row">
          <div class="col-md-6 mb-2">
            <label class="form-label">Role</label>
            <select class="form-select" name="role" id="role">
              <option value="customer">Customer</option>
              <option value="karyawan">Karyawan</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="col-md-6 mb-2">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" id="status">
              <option value="active">Aktif</option>
              <option value="pending">Pending</option>
            </select>
          </div>
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
  // Nav aktif saat diklik (konsisten)
  document.querySelectorAll('#sidebar-nav .nav-link').forEach(link=>{
    link.addEventListener('click',function(){
      document.querySelectorAll('#sidebar-nav .nav-link').forEach(l=>l.classList.remove('active'));
      this.classList.add('active');
    });
  });

  const modalEl = document.getElementById('userModal');
  const modal = new bootstrap.Modal(modalEl);

  function openAdd(){
    document.getElementById('userTitle').textContent = 'Tambah User';
    document.getElementById('action').value = 'add';
    document.getElementById('id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('email').value = '';
    document.getElementById('password').value = '';
    document.getElementById('role').value = 'customer';
    document.getElementById('status').value = 'active';
    modal.show();
  }

  function openEdit(id, row){
    document.getElementById('userTitle').textContent = 'Edit User';
    document.getElementById('action').value = 'edit';
    document.getElementById('id').value = id;
    document.getElementById('name').value = row.name || '';
    document.getElementById('email').value = row.email || '';
    document.getElementById('password').value = '';
    document.getElementById('role').value = row.role || 'customer';
    document.getElementById('status').value = row.status || 'active';
    modal.show();
  }
</script>
</body>
</html>
