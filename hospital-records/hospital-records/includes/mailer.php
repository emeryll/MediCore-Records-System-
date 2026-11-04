<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

/**
 * Send password reset code email using PHPMailer & Gmail SMTP.
 */
function send_password_reset_email(string $recipientEmail, string $recipientName, string $code): bool
{
    $configFile = __DIR__ . '/../config/mail.php';
    if (!file_exists($configFile)) {
        return false;
    }
    $config = require $configFile;

    $mail = new PHPMailer(true);

    try {
        $smtpUser = trim((string) ($config['smtp_user'] ?? $config['from_email'] ?? ''));
        if ($smtpUser === 'YOUR_GMAIL_ADDRESS@gmail.com') {
            $smtpUser = (string) ($config['from_email'] ?? '');
        }
        $smtpPass = str_replace(' ', '', (string) ($config['smtp_pass'] ?? ''));

        $mail->isSMTP();
        $mail->Host       = (string) ($config['smtp_host'] ?? 'smtp.gmail.com');
        $mail->SMTPAuth   = (bool) ($config['smtp_auth'] ?? true);
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = (string) ($config['smtp_secure'] ?? 'tls');
        $mail->Port       = (int) ($config['smtp_port'] ?? 587);
        $mail->CharSet    = 'UTF-8';

        $fromEmail = (string) ($config['from_email'] ?? $config['smtp_user'] ?? '');
        $fromName  = (string) ($config['from_name'] ?? 'MediCore Hospital Records');
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($recipientEmail, $recipientName ?: 'User');

        $mail->isHTML(true);
        $mail->Subject = 'Your MediCore Password Reset Code';
        $mail->Body    = '
            <div style="font-family: Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 8px; background: #ffffff;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <h2 style="color: #0f766e; margin: 0;">MediCore Health</h2>
                    <p style="color: #64748b; font-size: 13px; margin: 4px 0 0;">Hospital Records System</p>
                </div>
                <p style="color: #1e293b; font-size: 15px;">Hello <strong>' . htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
                <p style="color: #475569; font-size: 14px; line-height: 1.5;">We received a request to reset the password for your MediCore account. Use the verification code below to set a new password:</p>
                <div style="text-align: center; margin: 28px 0;">
                    <div style="display: inline-block; padding: 14px 28px; font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #0f766e; background: #f0fdfa; border: 2px dashed #0f766e; border-radius: 8px;">' . htmlspecialchars(trim($code), ENT_QUOTES, 'UTF-8') . '</div>
                    <p style="color: #dc2626; font-size: 12px; margin-top: 10px;">This code will expire in 15 minutes and can only be used once.</p>
                </div>
                <p style="color: #64748b; font-size: 13px; line-height: 1.5;">If you did not request a password reset, you can safely ignore this email. Your current password remains secure.</p>
                <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">
                <p style="color: #94a3b8; font-size: 11px; text-align: center; margin: 0;">&copy; ' . date('Y') . ' MediCore Hospital Records System. All rights reserved.</p>
            </div>
        ';
        $mail->AltBody = "Hello $recipientName,\n\nYour MediCore password reset code is: $code\n\nThis code expires in 15 minutes and is single-use. If you did not request this, please ignore this email.";

        return $mail->send();
    } catch (Exception) {
        return false;
    }
}
