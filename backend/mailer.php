<?php
declare(strict_types=1);

// Autoload Composer (PHPMailer)
require_once __DIR__  . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Kirim email OTP ke user.
 */
function sendOtpMail(string $toEmail, string $toName, string $otp): bool {
    $mail = new PHPMailer(true);
    try {
        // Gmail SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'cafforaproject@gmail.com';   // email pengirim
        $mail->Password   = 'fncaktuvkugxsorz';           // App Password (tanpa spasi)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS (port 587)
        $mail->Port       = 587;

        // Pengirim & penerima
        $mail->setFrom('cafforaproject@gmail.com', 'Caffora');
        $mail->addAddress($toEmail, $toName);

        // Konten
        $mail->isHTML(true);
        $mail->Subject = 'Kode OTP Verifikasi Caffora';
        $mail->Body = "
            <p>Halo <strong>".h($toName)."</strong>,</p>
            <p>Kode OTP Anda:</p>
            <h2 style='letter-spacing:3px;'>".h($otp)."</h2>
            <p>Berlaku 5 menit. Jangan bagikan ke siapa pun.</p>
        ";
        $mail->AltBody = "Kode OTP Anda: {$otp} (berlaku 5 menit)";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}