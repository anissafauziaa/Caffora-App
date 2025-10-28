<?php
session_start();
require_once __DIR__ . '/auth_guard.php';
require_login(['customer']);

// Pastikan ada file
if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
    die('Upload gagal. Silakan coba lagi.');
}

$file = $_FILES['profile_photo'];

// Validasi tipe file
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
if (!in_array($file['type'], $allowed_types)) {
    die('Hanya file JPG atau PNG yang diperbolehkan.');
}

// Tentukan folder dan nama file
$upload_dir = __DIR__ . '/../uploads/profile_pictures/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0775, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
$target_path = $upload_dir . $new_filename;

// Pindahkan file
if (move_uploaded_file($file['tmp_name'], $target_path)) {
    // Simpan URL relatif untuk ditampilkan di halaman profil
    $photo_url = '../uploads/profile_pictures/' . $new_filename;

    // Update session
    $_SESSION['user_photo'] = $photo_url;

    // (Opsional) Simpan juga ke database jika kamu punya kolom photo_path
    // include 'db.php';
    // $stmt = $pdo->prepare("UPDATE users SET photo_path = ? WHERE id = ?");
    // $stmt->execute([$photo_url, $_SESSION['user_id']]);

    header('Location: ../public/customer/profile.php?success=1');
    exit;
} else {
    die('Gagal menyimpan file.');
}
