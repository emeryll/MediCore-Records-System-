<?php
declare(strict_types=1);

$actError = $_SESSION['register_error'] ?? null;
$actStep  = (int) ($_GET['step'] ?? ($_SESSION['act_step'] ?? 1));
$actPhone = (string) ($_GET['phone'] ?? ($_SESSION['act_phone'] ?? ''));
$actMrn   = (string) ($_SESSION['act_mrn'] ?? '');
$actName  = (string) ($_SESSION['act_name'] ?? '');
$actCode  = (string) ($_SESSION['act_otp_code'] ?? '');
unset($_SESSION['register_error']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activate Patient Account · <?= e(APP_NAME) ?></title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<main class="login-page">
    <section class="login-visual">
        <?= logo(true) ?>
        <div class="illustration" aria-hidden="true">
            <svg viewBox="0 0 560 420" fill="none">
                <path class="illu-ground" d="M47 355c76-35 140-3 212-13 99-14 150-73 252-23"/>
                <path class="illu-back" d="M119 103h312v250H119z"/>
                <path class="illu-side" d="m119 103 72-41h312l-72 41H119Z"/>
                <path class="illu-side" d="m431 103 72-41v250l-72 41V103Z"/>
                <path class="illu-front" d="M203 164h145v189H203z"/>
                <path class="illu-cross" d="M253 194h45v23h23v45h-23v23h-45v-23h-23v-45h23v-23Z"/>
                <path class="illu-window" d="M142 137h35v35h-35zM142 198h35v35h-35zM142 259h35v35h-35zM373 137h35v35h-35zM373 198h35v35h-35zM373 259h35v35h-35z"/>
                <path class="illu-door" d="M247 303h57v50h-57z"/>
            </svg>
        </div>
        <div class="visual-copy">
            <p class="eyebrow">Patient Account Activation</p>
            <h1>Activate your<br>WMMC patient portal.</h1>
            <p>Once Front Desk Staff complete your full registration and issue your MRN, activate your online account in 3 steps: enter your phone number, verify the OTP, and create your password.</p>
        </div>
        <div class="trust-row">
            <span><?= icon('shield', 16) ?> Verified MRN Holders Only</span>
            <span>SMS OTP Verification</span>
            <span>West Metro Medical Center</span>
        </div>
    </section>

    <section class="login-form-side">
        <div class="login-card">
            <a class="back-link" href="index.php" style="margin-bottom:14px;">← Back to home</a>
            <div class="mobile-login-logo"><?= logo() ?></div>
            <?= render_sim_banners() ?>

            <p class="eyebrow">Step <?= $actStep ?> of 3 · Patient Portal Activation</p>
            <h2><?= match ($actStep) {
                2 => 'Verify your 6-digit OTP',
                3 => 'Create your password',
                default => 'Activate patient account',
            } ?></h2>
            <p class="login-intro">
                <?= match ($actStep) {
                    2 => 'Enter the 6-digit verification code sent to ' . e($actPhone) . ' for ' . e($actName) . ' (' . e($actMrn) . ').',
                    3 => 'Phone verified for ' . e($actName) . ' (' . e($actMrn) . '). Choose a password to complete activation.',
                    default => 'Enter the mobile number you registered with Front Desk Staff. Only fully registered patients with an assigned MRN can activate an account.',
                } ?>
            </p>

            <?php if ($actError): ?>
                <div class="login-alert wrong">
                    <?= icon('shield') ?>
                    <div>
                        <strong>Activation notice</strong>
                        <p><?= e($actError) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($actStep === 1): ?>
                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="activate_step1">
                    <label class="field">
                        <span class="field-label">Registered Mobile Number *</span>
                        <span class="input-wrap">
                            <input type="tel" name="phone" placeholder="09XXXXXXXXX" value="<?= e($actPhone) ?>" maxlength="11" required oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);">
                        </span>
                        <small class="field-hint">Note: Provisional (phone-in) records must complete registration at check-in before activating an account.</small>
                    </label>
                    <button class="btn btn-primary" type="submit"><span>Send verification OTP</span></button>
                </form>

            <?php elseif ($actStep === 2): ?>
                <?php if ($actCode !== ''): ?>
                    <div class="login-alert success" style="background:#f0fdfa; border-color:#5eead4; color:#0f766e;">
                        <?= icon('shield') ?>
                        <div>
                            <strong>Simulated SMS to <?= e($actPhone) ?></strong>
                            <p>Your WMMC account activation OTP is <strong style="font-size:15px; letter-spacing:2px;"><?= e($actCode) ?></strong></p>
                        </div>
                    </div>
                <?php endif; ?>
                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="activate_step2">
                    <label class="field">
                        <span class="field-label">6-Digit SMS OTP Code *</span>
                        <span class="input-wrap">
                            <input type="text" name="otp_code" placeholder="123456" maxlength="6" required style="letter-spacing:4px; font-weight:700; font-size:18px; text-align:center;" value="<?= e($actCode) ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);">
                        </span>
                    </label>
                    <button class="btn btn-primary" type="submit"><span>Verify OTP &amp; continue</span></button>
                    <div style="margin-top:12px; text-align:center;">
                        <a class="text-link" href="index.php?auth=activate&amp;step=1">← Use a different phone number</a>
                    </div>
                </form>

            <?php else: ?>
                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="activate_step3">
                    <div class="panel" style="padding:12px 14px; background:var(--teal-50); border:1px solid var(--teal-200); margin-bottom:14px; font-size:13px;">
                        <div>Patient: <strong><?= e($actName) ?></strong></div>
                        <div>MRN: <code style="font-weight:700; color:var(--teal-800);"><?= e($actMrn) ?></code> · Phone: <strong><?= e($actPhone) ?></strong></div>
                    </div>
                    <label class="field">
                        <span class="field-label">Email Address (Optional)</span>
                        <span class="input-wrap">
                            <input type="email" name="email" placeholder="Optional — can also be added later under Profile">
                        </span>
                    </label>
                    <div class="form-grid">
                        <?= field('Create Password *', 'password', 'Min. 8 characters', 'password', '', ['required' => true]) ?>
                        <?= field('Confirm Password *', 'confirm_password', 'Re-enter password', 'password', '', ['required' => true]) ?>
                    </div>
                    <button class="btn btn-primary" type="submit"><span>Activate account &amp; sign in</span></button>
                </form>
            <?php endif; ?>

            <div class="auth-switch-box">
                <span>Already activated your account?</span>
                <a class="text-link" href="index.php?auth=login">Sign in <?= icon('arrow', 14) ?></a>
            </div>
        </div>
        <p class="login-footer">© <?= date('Y') ?> <?= e(APP_NAME) ?> · Zamboanga City</p>
    </section>
</main>
</body>
</html>
