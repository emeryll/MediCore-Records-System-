<?php
declare(strict_types=1);

/* ---------------------------------------------------------------
 * Mail Configuration for PHPMailer (Gmail SMTP)
 *
 * NOTE: Replace the placeholders below with your actual Gmail address
 * and 16-character Google App Password (not your normal Gmail password).
 * To generate an App Password:
 * Google Account -> Security -> 2-Step Verification -> App Passwords
 * ------------------------------------------------------------- */

return [
    'smtp_host'       => 'smtp.gmail.com',
    'smtp_port'       => 587,
    'smtp_secure'     => 'tls', // 'tls' (port 587) or 'ssl' (port 465)
    'smtp_auth'       => true,
    'smtp_user'       => 'evangelistameryll@gmail.com',
    'smtp_pass'       => 'ktcfcpqjcgrlcgkw',
    'from_email'      => 'evangelistameryll@gmail.com',
    'from_name'       => 'West Metro Medical Center',
];
