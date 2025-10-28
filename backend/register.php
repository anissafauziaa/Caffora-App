<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php'; // fungsi sendOtpMail($email, $name, $otp)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/public/register.html');
    exit;
}

// ===== Ambil input =====
$name   = trim($_POST['name'] ?? '');
$email  = trim($_POST['email'] ?? '');
$pass   = (string)($_POST['password'] ?? '');
$cpass  = (string)($_POST['confirm_password'] ?? '');

// ===== Validasi sederhana =====
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/public/register.html?err=invalid');
}
if (strlen($pass) < 6) {
    redirect('/public/register.html?err=short');
}
if ($pass !== $cpass) {
    redirect('/public/register.html?err=nomatch');
}

// ===== Cek email sudah terdaftar =====
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    redirect('/public/register.html?err=exist');
}
$stmt->close();

// ===== Simpan user (status pending) =====
$hash = password_hash($pass, PASSWORD_BCRYPT);

$stmt = $conn->prepare('INSERT INTO users (name, email, password, status) VALUES (?, ?, ?, "pending")');
$stmt->bind_param('sss', $name, $email, $hash);
$ok = $stmt->execute();
$user_id = (int)$stmt->insert_id;
$stmt->close();

if (!$ok || $user_id <= 0) {
    redirect('/public/register.html?err=save');
}

// ===== Generate OTP & waktu kedaluwarsa =====
$otp      = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires  = (new DateTime('+5 minutes'))->format('Y-m-d H:i:s');

// Simpan OTP ke user
$stmt = $conn->prepare('UPDATE users SET otp = ?, otp_expires_at = ? WHERE id = ?');
$stmt->bind_param('ssi', $otp, $expires, $user_id);
$stmt->execute();
$stmt->close();

// ===== Kirim email OTP =====
if (!sendOtpMail($email, $name, $otp)) {
    // Kalau gagal kirim email, hapus user agar bersih
    $conn->query('DELETE FROM users WHERE id=' . (int)$user_id);
    redirect('/public/register.html?err=mail');
}

// ===== Sukses → arahkan ke halaman verifikasi OTP =====
redirect('/public/verify_otp.html?email=' . urlencode($email));