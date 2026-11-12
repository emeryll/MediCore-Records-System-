<?php
declare(strict_types=1);

$actError = $_SESSION['activate_error'] ?? ($_SESSION['register_error'] ?? null);
$actStep  = (int) ($_GET['step'] ?? ($_SESSION['activate_step'] ?? ($_SESSION['act_step'] ?? 1)));
$actPhone = (string) ($_GET['phone'] ?? ($_SESSION['activate_phone'] ?? ($_SESSION['act_phone'] ?? '')));
$actPts   = $_SESSION['activate_pts'] ?? [];
$hasExistingAccount = !empty($_SESSION['activate_has_existing_account']);
$unlinkedPts        = $_SESSION['activate_unlinked_patients'] ?? $actPts;
$actCode  = (string) ($_SESSION['activate_otp'] ?? ($_SESSION['act_otp_code'] ?? ''));
if ($actStep > 1 && $actPhone === '') {
    $actStep = 1;
}
unset($_SESSION['activate_error'], $_SESSION['register_error']);

// Clean up any pending registration invite simulated SMS messages
if (!empty($_SESSION['sim_messages']) && is_array($_SESSION['sim_messages'])) {
    $_SESSION['sim_messages'] = array_values(array_filter(
        $_SESSION['sim_messages'],
        static fn ($m) => stripos((string) ($m['message'] ?? ''), 'activate your Patient Portal account') === false
    ));
}
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

            <p class="eyebrow">Step <?= $actStep ?> of 3 · Patient Portal Activation</p>
            <h2><?= match ($actStep) {
                2 => 'Verify your 6-digit OTP',
                3 => ($hasExistingAccount ? 'Link patient profile to account' : 'Confirm profiles & create password'),
                default => 'Activate patient account',
            } ?></h2>
            <p class="login-intro">
                <?= match ($actStep) {
                    2 => 'Enter the 6-digit verification code sent to ' . e($actPhone) . '.',
                    3 => ($hasExistingAccount
                        ? 'An account already exists for ' . e($actPhone) . '. Enter the patient\'s birthdate below to link the profile without needing a new password.'
                        : 'Phone verified for ' . e($actPhone) . '. Enter the registered birthdate for each profile to confirm identity, then create your password.'),
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

            <?php elseif ($hasExistingAccount): ?>
                <!-- CASE 2: Account already exists for this phone number -> verify birthdate and link (NO PASSWORD) -->
                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="activate_link_existing">
                    <div class="panel" style="padding:12px 14px; background:#f0fdfa; border:1px solid #99f6e4; color:#0f766e; border-radius:8px; margin-bottom:16px; font-size:13.5px;">
                        <?= icon('shield', 18) ?> An online portal account is already active for <strong><?= e($actPhone) ?></strong>. Enter the registered birthdate below to link the new profile to your existing account.
                    </div>
                    <?php foreach ($unlinkedPts as $uPt): ?>
                        <div class="panel" style="padding:14px; background:#fff; border:1px solid var(--gray-200); border-radius:8px; margin-bottom:14px;">
                            <input type="hidden" name="patient_id" value="<?= e($uPt['patient_id']) ?>">
                            <div style="margin-bottom:10px;">
                                <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--teal-800);">Registered Patient</span>
                                <div style="font-size:16px; font-weight:700; color:var(--navy-900);"><?= e(mask_patient_name((string) $uPt['full_name'])) ?></div>
                                <small style="color:var(--gray-600);">MRN: <?= e($uPt['mrn'] ?: $uPt['patient_id']) ?></small>
                            </div>
                            <label class="field" style="margin-bottom:0;">
                                <span class="field-label">Confirm Patient Date of Birth <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                                <span class="input-wrap">
                                    <input type="date" name="birthdate" required max="<?= date('Y-m-d') ?>">
                                </span>
                                <small class="field-hint">Enter the birthdate registered in the hospital record.</small>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <button class="btn btn-primary" type="submit"><span>Confirm Birthdate &amp; Link Profile</span></button>
                    <div style="margin-top:12px; text-align:center;">
                        <a class="text-link" href="index.php?auth=login">Already know your password? Sign in →</a>
                    </div>
                </form>

            <?php else: ?>
                <!-- CASE 1: No account exists for this phone number -> verify birthdate, create password & account -->
                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="activate_step3">
                    <p style="font-size:13px; color:var(--gray-600); margin-bottom:12px;">
                        Registered profile(s) found for <strong><?= e($actPhone) ?></strong>. Enter each patient's birthdate to confirm identity:
                    </p>
                    <?php foreach ($actPts as $p): ?>
                        <div class="panel" style="padding:12px 14px; background:var(--teal-50); border:1px solid var(--teal-200); border-radius:8px; margin-bottom:12px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <div>
                                    <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--teal-800);">Patient Profile</span>
                                    <div style="font-size:15px; font-weight:700; color:var(--navy-900);"><?= e(mask_patient_name((string) $p['full_name'])) ?></div>
                                    <small style="color:var(--gray-600);">MRN: <?= e($p['mrn'] ?: $p['patient_id']) ?></small>
                                </div>
                                <span class="badge badge-teal">Registered</span>
                            </div>
                            <label class="field" style="margin-bottom:0;">
                                <span class="field-label">Patient Date of Birth <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                                <span class="input-wrap">
                                    <input type="date" name="birthdates[<?= e($p['patient_id']) ?>]" required max="<?= date('Y-m-d') ?>">
                                </span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <label class="field" style="margin-top:14px;">
                        <span class="field-label">Email Address (Optional)</span>
                        <span class="input-wrap">
                            <input type="email" name="email" placeholder="Optional — can also be added later under Profile">
                        </span>
                    </label>
                    <div class="form-grid">
                        <?= field('Create Password *', 'password', 'Min. 8 characters', 'password', '', ['required' => true]) ?>
                        <?= field('Confirm Password *', 'confirm_password', 'Re-enter password', 'password', '', ['required' => true]) ?>
                    </div>
                    <button class="btn btn-primary" type="submit"><span>Create Account &amp; Activate</span></button>
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
