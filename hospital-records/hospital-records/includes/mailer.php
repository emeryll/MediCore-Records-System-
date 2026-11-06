<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

/**
 * Helper to get mail configuration from environment variables first,
 * then falling back to config/mail.php if available.
 */
function get_mail_config(): array
{
    $configFile = __DIR__ . '/../config/mail.php';
    $fileConfig = file_exists($configFile) ? (require $configFile) : [];

    // Parse .env file if present in project root
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                $v = trim(trim($v), '"\'');
                if (getenv($k) === false) {
                    putenv("{$k}={$v}");
                    $_ENV[$k] = $v;
                }
            }
        }
    }

    $smtpHost   = getenv('SMTP_HOST') ?: ($fileConfig['smtp_host'] ?? 'smtp.gmail.com');
    $smtpPort   = (int) (getenv('SMTP_PORT') ?: ($fileConfig['smtp_port'] ?? 587));
    $smtpSecure = getenv('SMTP_SECURE') ?: ($fileConfig['smtp_secure'] ?? 'tls');
    $smtpAuth   = getenv('SMTP_AUTH') !== false ? filter_var(getenv('SMTP_AUTH'), FILTER_VALIDATE_BOOLEAN) : (bool) ($fileConfig['smtp_auth'] ?? true);
    $smtpUser   = getenv('SMTP_USER') ?: ($fileConfig['smtp_user'] ?? '');
    $smtpPass   = getenv('SMTP_PASS') ?: ($fileConfig['smtp_pass'] ?? '');
    $fromEmail  = getenv('SMTP_FROM_EMAIL') ?: ($fileConfig['from_email'] ?? $smtpUser);
    $fromName   = getenv('SMTP_FROM_NAME') ?: ($fileConfig['from_name'] ?? 'West Metro Medical Center');

    return [
        'smtp_host'   => $smtpHost,
        'smtp_port'   => $smtpPort,
        'smtp_secure' => $smtpSecure,
        'smtp_auth'   => $smtpAuth,
        'smtp_user'   => $smtpUser,
        'smtp_pass'   => $smtpPass,
        'from_email'  => $fromEmail,
        'from_name'   => $fromName,
    ];
}

/**
 * Send account activation & personal password setup email using PHPMailer.
 * Returns ['ok' => bool, 'error' => ?string].
 */
function send_account_activation_email(
    string $recipientEmail,
    string $recipientName,
    string $role,
    string $userId,
    string $setPasswordUrl,
    int $expiresHours = 24
): array {
    $cfg = get_mail_config();

    // Push simulated notification so admin can immediately preview/test link in browser
    if (function_exists('push_sim_message')) {
        push_sim_message(
            'email',
            $recipientEmail,
            "West Metro Medical Center — {$role} Account Activation",
            "Hello {$recipientName} ({$role})! Your account is created and pending activation. Click the link to set your personal password (expires in {$expiresHours} hours).",
            $setPasswordUrl,
            'Set Password & Activate Account'
        );
    }

    if (empty($cfg['smtp_user']) || empty($cfg['smtp_pass']) || str_contains($cfg['smtp_user'], 'YOUR_GMAIL_ADDRESS')) {
        return [
            'ok'    => false,
            'error' => 'SMTP credentials not configured in environment. The simulated activation link has been generated above.',
        ];
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = (string) $cfg['smtp_host'];
        $mail->SMTPAuth   = (bool) $cfg['smtp_auth'];
        $mail->Username   = trim((string) $cfg['smtp_user']);
        $mail->Password   = str_replace(' ', '', (string) $cfg['smtp_pass']);
        $mail->SMTPSecure = (string) $cfg['smtp_secure'];
        $mail->Port       = (int) $cfg['smtp_port'];
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 8;

        $mail->setFrom((string) $cfg['from_email'], (string) $cfg['from_name']);
        $mail->addAddress($recipientEmail, $recipientName ?: 'User');

        $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
        $safeRole = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
        $safeUid  = htmlspecialchars($userId, ENT_QUOTES, 'UTF-8');
        $safeUrl  = htmlspecialchars($setPasswordUrl, ENT_QUOTES, 'UTF-8');

        $mail->isHTML(true);
        $mail->Subject = "Activate Your West Metro Medical Center Account ({$safeRole})";
        $mail->Body    = '
            <div style="font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; max-width: 580px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;">
                <div style="text-align: center; margin-bottom: 24px;">
                    <h2 style="color: #0f766e; margin: 0; font-size: 22px;">West Metro Medical Center</h2>
                    <p style="color: #64748b; font-size: 13px; margin: 4px 0 0;">Metro Pacific Health · Zamboanga City</p>
                </div>
                <div style="background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #0f766e; font-size: 14px; font-weight: 600;">Account Created · Pending Activation</p>
                    <p style="margin: 4px 0 0; color: #134e4a; font-size: 13px;">Role: <strong>' . $safeRole . '</strong> · Email: <strong>' . htmlspecialchars($recipientEmail, ENT_QUOTES, 'UTF-8') . '</strong></p>
                </div>
                <p style="color: #1e293b; font-size: 15px; margin: 0 0 12px;">Hello <strong>' . $safeName . '</strong>,</p>
                <p style="color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 20px;">An employee account has been created for you in the West Metro Medical Center hospital records system. For your security, the administrator never sets or sees your password. Please set your own confidential password to activate your account:</p>
                <div style="text-align: center; margin: 28px 0;">
                    <a href="' . $safeUrl . '" style="display: inline-block; background: #0f766e; color: #ffffff; text-decoration: none; padding: 13px 28px; border-radius: 8px; font-weight: 600; font-size: 15px; box-shadow: 0 2px 4px rgba(15, 118, 110, 0.2);">Set Password &amp; Activate Account</a>
                    <p style="color: #dc2626; font-size: 12px; margin-top: 12px;">This single-use link expires in ' . $expiresHours . ' hours.</p>
                </div>
                <p style="color: #64748b; font-size: 12px; line-height: 1.5; margin: 20px 0 0;">If the button above does not work, copy and paste this link into your browser:<br><a href="' . $safeUrl . '" style="color: #0f766e; word-break: break-all;">' . $safeUrl . '</a></p>
                <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">
                <p style="color: #94a3b8; font-size: 11px; text-align: center; margin: 0;">&copy; ' . date('Y') . ' West Metro Medical Center · Veterans Ave. Ext., Tetuan, Zamboanga City · Data Privacy Act Compliant</p>
            </div>
        ';
        $mail->AltBody = "Hello {$recipientName},\n\nYour West Metro Medical Center account ({$role}) has been created.\n\nPlease visit the following link to choose your personal password and activate your account:\n{$setPasswordUrl}\n\nThis single-use link expires in {$expiresHours} hours.\n\nWest Metro Medical Center · Zamboanga City";

        $mail->send();
        return ['ok' => true, 'error' => null];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}

/**
 * Send password reset code email using PHPMailer.
 */
function send_password_reset_email(string $recipientEmail, string $recipientName, string $code): bool
{
    $cfg = get_mail_config();

    if (empty($cfg['smtp_user']) || empty($cfg['smtp_pass']) || str_contains($cfg['smtp_user'], 'YOUR_GMAIL_ADDRESS')) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = (string) $cfg['smtp_host'];
        $mail->SMTPAuth   = (bool) $cfg['smtp_auth'];
        $mail->Username   = trim((string) $cfg['smtp_user']);
        $mail->Password   = str_replace(' ', '', (string) $cfg['smtp_pass']);
        $mail->SMTPSecure = (string) $cfg['smtp_secure'];
        $mail->Port       = (int) $cfg['smtp_port'];
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 8;

        $mail->setFrom((string) $cfg['from_email'], (string) $cfg['from_name']);
        $mail->addAddress($recipientEmail, $recipientName ?: 'User');

        $mail->isHTML(true);
        $mail->Subject = 'Your West Metro Medical Center Verification Code';
        $mail->Body    = '
            <div style="font-family: Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 8px; background: #ffffff;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <h2 style="color: #0f766e; margin: 0;">West Metro Medical Center</h2>
                    <p style="color: #64748b; font-size: 13px; margin: 4px 0 0;">Metro Pacific Health · Zamboanga City</p>
                </div>
                <p style="color: #1e293b; font-size: 15px;">Hello <strong>' . htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
                <p style="color: #475569; font-size: 14px; line-height: 1.5;">We received a request to verify your account or reset your password. Use the verification code below:</p>
                <div style="text-align: center; margin: 28px 0;">
                    <div style="display: inline-block; padding: 14px 28px; font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #0f766e; background: #f0fdfa; border: 2px dashed #0f766e; border-radius: 8px;">' . htmlspecialchars(trim($code), ENT_QUOTES, 'UTF-8') . '</div>
                    <p style="color: #dc2626; font-size: 12px; margin-top: 10px;">This code will expire in 15 minutes and can only be used once.</p>
                </div>
                <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">
                <p style="color: #94a3b8; font-size: 11px; text-align: center; margin: 0;">&copy; ' . date('Y') . ' West Metro Medical Center. All rights reserved.</p>
            </div>
        ';
        $mail->AltBody = "Hello $recipientName,\n\nYour West Metro Medical Center verification code is: $code\n\nThis code expires in 15 minutes and is single-use.";

        return $mail->send();
    } catch (Exception) {
        return false;
    }
}
