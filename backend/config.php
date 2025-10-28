<?php
/** Caffora – global config */
declare(strict_types=1);

// ===== DEV SETTINGS =====
error_reporting(E_ALL & ~E_NOTICE); // hilangkan notice ringan
ini_set('display_errors', '1');
date_default_timezone_set('Asia/Jakarta');

// ===== MULAI SESSION (jika belum aktif) =====
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ===== URL APLIKASI =====
define('BASE_URL', 'http://localhost/caffora-app1');

// ===== KONFIG DB =====
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'caffora_db');

// ===== SMTP (opsional) =====
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'akun_email_anda@gmail.com');
define('SMTP_PASS', 'app_password_anda');
define('SMTP_FROM', 'akun_email_anda@gmail.com');
define('SMTP_FROM_NAME', 'Caffora');

// ===== KONEKSI MYSQLI (buat sekali saja) =====
if (!isset($GLOBALS['_caffora_conn']) || !($GLOBALS['_caffora_conn'] instanceof mysqli)) {
    $GLOBALS['__caffora_conn'] = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($GLOBALS['__caffora_conn']->connect_errno) {
        http_response_code(500);
        die('Gagal konek DB: ' . $GLOBALS['__caffora_conn']->connect_error);
    }
    $GLOBALS['__caffora_conn']->set_charset('utf8mb4');
}
/** @var mysqli $conn */
$conn = $GLOBALS['__caffora_conn'];

// ===== HELPERS (pakai guard agar tak redeclare) =====
if (!function_exists('h')) {
    function h(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    /** Redirect relatif terhadap BASE_URL. */
    function redirect(string $path): void {
        if ($path === '' || $path[0] !== '/') $path = '/' . $path;
        header('Location: ' . BASE_URL . $path);
        exit;
    }
}