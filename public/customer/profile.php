<?php
declare(strict_types=1);
require_once __DIR__ . '/../../backend/auth_guard.php';
require_login(['customer']);
session_start();

$name  = $_SESSION['user_name']  ?? 'Customer';
$email = $_SESSION['user_email'] ?? '';
$photo = $_SESSION['user_photo'] ?? ''; // kosong jika belum upload
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Profil Saya — Caffora</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    body {
      background: linear-gradient(135deg, #f8f9fa 0%, #fff8e1 100%);
      font-family: "Poppins", sans-serif;
      color: #3e2f25;
    }

    .profile-card {
      max-width: 480px;
      margin: 90px auto;
      background: #fff;
      border-radius: 25px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
      padding: 2.5rem 2rem;
      text-align: center;
    }

    .profile-photo-wrapper {
      position: relative;
      display: inline-block;
      margin-bottom: 1rem;
    }

    .profile-img,
    .profile-default {
      width: 130px;
      height: 130px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid #ffce32;
      box-shadow: 0 4px 15px rgba(255, 206, 50, 0.4);
      background-color: #fff9d7;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 64px;
      color: #bfa84f;
      transition: all 0.3s ease;
    }

    .edit-icon {
      position: absolute;
      bottom: 6px;
      right: 6px;
      background: #ffce32;
      border-radius: 50%;
      padding: 8px;
      cursor: pointer;
      border: 3px solid #fff;
      transition: all 0.2s ease;
      color: #3e2f25;
    }

    .edit-icon:hover {
      background: #ffd84d;
      transform: scale(1.1);
    }

    .upload-section {
      display: none;
      margin-top: 1rem;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(-10px);}
      to {opacity: 1; transform: translateY(0);}
    }

    .btn-back {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      background: #ffce32;
      color: #3e2f25;
      border: none;
      border-radius: 9999px;
      padding: 10px 20px;
      font-weight: 600;
      text-decoration: none;
      margin-top: 1.5rem;
      box-shadow: 0 4px 10px rgba(255, 206, 50, 0.4);
      transition: 0.2s;
    }

    .btn-back:hover {
      background: #ffd84d;
      box-shadow: 0 6px 16px rgba(255, 206, 50, 0.6);
      transform: translateY(-2px);
    }
  </style>
</head>
<body>

  <nav class="navbar bg-white shadow-sm fixed-top">
    <div class="container d-flex justify-content-between align-items-center">
      <a href="index.php" class="navbar-brand d-flex align-items-center gap-2">
        <i class="bi bi-cup-hot-fill text-warning"></i>
        <span class="fw-semibold text-dark">Caffora</span>
      </a>
      <a href="/caffora-app1/backend/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
    </div>
  </nav>

  <div class="profile-card mt-5 pt-5">
    <div class="profile-photo-wrapper">
      <?php if (!empty($photo)): ?>
        <img src="<?= htmlspecialchars($photo) ?>" alt="Foto Profil" class="profile-img" id="profileImage">
      <?php else: ?>
        <div class="profile-default" id="profileImage">
          <i class="bi bi-person-circle"></i>
        </div>
      <?php endif; ?>
      <div class="edit-icon" id="editIcon" title="Ubah Foto Profil">
        <i class="bi bi-pencil-fill"></i>
      </div>
    </div>

    <h3 class="mt-2 mb-1"><?= htmlspecialchars($name) ?></h3>
    <p class="text-muted mb-3"><?= htmlspecialchars($email) ?></p>

    <form action="../../backend/upload_profile.php" method="POST" enctype="multipart/form-data" id="uploadForm" class="upload-section">
      <div class="input-group mb-2">
        <input class="form-control" type="file" name="profile_photo" accept="image/*" required>
        <button type="submit" class="btn btn-warning fw-semibold text-dark">
          <i class="bi bi-upload"></i> Unggah
        </button>
      </div>
    </form>

    <hr class="my-4">

    <div class="text-start">
      <p><strong>Peran:</strong> Customer</p>
      <!-- <p><strong>Bergabung sejak:</strong> <?= date('d M Y') ?></p> -->
      <p><strong>Status Akun:</strong> Aktif</p>
    </div>

    <a href="index.php" class="btn-back">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>

  <script>
    const editIcon = document.getElementById('editIcon');
    const uploadForm = document.getElementById('uploadForm');

    editIcon.addEventListener('click', () => {
      uploadForm.style.display = uploadForm.style.display === 'none' ? 'block' : 'none';
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
