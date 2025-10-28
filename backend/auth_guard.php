<?php
// backend/auth_guard.php
declare(strict_types=1);

require_once __DIR__ . '/config.php'; // BASE_URL, $conn, helper redirect()

// Start session aman
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Pastikan user sudah login (dan opsional: memiliki role tertentu).
 * - Jika belum login / sesi invalid → redirect ke login.
 * - Jika status belum aktif → redirect ke verifikasi OTP.
 * - Jika role tidak diizinkan → redirect ke dashboard sesuai rolenya.
 *
 * @param array $allowedRoles Contoh: ['admin'], ['customer','karyawan'], [] = bebas role
 * @return array $currentUser  Data user dari DB (id,name,email,status,role)
 */
function require_login(array $allowedRoles = []) : array {
    global $conn;

    // Belum login?
    if (empty($_SESSION['user_id'])) {
        redirect('/public/login.html?err=' . urlencode('Silakan login dulu.'));
    }

    // Ambil user dari DB berdasarkan session
    $userId = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT id, name, email, status, role FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $currentUser = $res->fetch_assoc();
    $stmt->close();

    // Sesi tidak valid
    if (!$currentUser) {
        session_unset();
        session_destroy();
        redirect('/public/login.html?err=' . urlencode('Sesi berakhir. Silakan login ulang.'));
    }

    // Belum aktif → harus verifikasi OTP
    if (($currentUser['status'] ?? 'pending') !== 'active') {
        redirect('/public/verify_otp.html?email=' . urlencode($currentUser['email']));
    }

    // Normalisasi & simpan lagi ke session (biar konsisten)
    $_SESSION['user_name']  = $currentUser['name'];
    $_SESSION['user_email'] = $currentUser['email'];
    $_SESSION['user_role']  = strtolower($currentUser['role'] ?? 'customer');

    // Jika dibatasi role
    if ($allowedRoles) {
        $role = $_SESSION['user_role'];
        $allowed = array_map('strtolower', $allowedRoles);
        if (!in_array($role, $allowed, true)) {
            // arahkan ke dashboard sesuai role yang dimiliki
            switch ($role) {
                case 'admin':
                    redirect('/public/admin/index.php');
                    break;
                case 'karyawan':
                    redirect('/public/karyawan/index.php');
                    break;
                default:
                    redirect('/public/customer/index.php');
            }
        }
    }

    return $currentUser;
}

/**
 * Wrapper praktis: pastikan user punya satu role tertentu.
 * @param string $role 'admin' | 'customer' | 'karyawan'
 * @return array $currentUser
 */
function require_role(string $role) : array {
    return require_login([$role]);
}