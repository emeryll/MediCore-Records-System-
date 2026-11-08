<?php
declare(strict_types=1);

$loginError  = $_SESSION['login_error'] ?? null;
$successMsg  = $_SESSION['login_success_msg'] ?? null;
$resetInfo   = $_SESSION['reset_info'] ?? null;
$resetError  = $_SESSION['reset_error'] ?? null;
$resetSim    = $_SESSION['reset_sms_sim'] ?? null;
$resetOtp    = $_SESSION['reset_otp_code'] ?? '';
$resetPhone  = (string) ($_SESSION['reset_phone'] ?? ($_SESSION['reset_verified_phone'] ?? ''));
$resetEmail  = (string) ($_SESSION['reset_email'] ?? ($_SESSION['reset_verified_email'] ?? ''));
$resetUserId = (string) ($_SESSION['reset_user_id'] ?? ($_SESSION['reset_target_uid'] ?? ''));
$resetMethod = (string) ($_SESSION['reset_method'] ?? 'phone');
unset($_SESSION['login_error'], $_SESSION['login_success_msg'], $_SESSION['reset_error']);

$identifier = (string) ($_SESSION['login_identifier'] ?? ($_SESSION['login_phone'] ?? ($_GET['ident'] ?? '')));
if (str_contains($identifier, 'Warning') || str_contains($identifier, '<br') || str_contains($identifier, 'Undefined')) {
    $identifier = '';
}
unset($_SESSION['login_user_id'], $_SESSION['login_identifier'], $_SESSION['login_phone']);

// Only treat as set-password view if explicitly requested in the query parameter
$isSetPassword = !empty($_GET['set_password']);
if (!$isSetPassword) {
    unset($_SESSION['force_pw_change_uid'], $_SESSION['set_pw_error']);
}
$setPwUid      = (string) ($_GET['uid'] ?? ($_SESSION['force_pw_change_uid'] ?? ''));
$setPwToken    = (string) ($_GET['token'] ?? '');
$setPwError    = $_SESSION['set_pw_error'] ?? null;
unset($_SESSION['set_pw_error']);

$isVerify = !$isSetPassword && (($_GET['reset_step'] ?? '') === 'verify');
$isForgot = !$isSetPassword && !$isVerify && (!empty($_GET['forgot']) || (($_GET['step'] ?? '') === 'forgot'));
if (!$isForgot && !$isVerify) {
    unset(
        $_SESSION['reset_info'],
        $_SESSION['reset_error'],
        $_SESSION['reset_sms_sim'],
        $_SESSION['reset_otp_code'],
        $_SESSION['reset_phone'],
        $_SESSION['reset_email'],
        $_SESSION['reset_user_id'],
        $_SESSION['reset_verified_phone'],
        $_SESSION['reset_verified_email'],
        $_SESSION['reset_target_uid'],
        $_SESSION['reset_sent']
    );
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isSetPassword ? 'Set Password' : ($isForgot ? 'Forgot Password' : ($isVerify ? 'Reset Password' : 'Log In')) ?> · <?= e(APP_NAME) ?></title>
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
                <circle class="illu-tree" cx="76" cy="280" r="32"/>
                <path class="illu-trunk" d="M76 312v40"/>
                <circle class="illu-tree pale" cx="478" cy="274" r="38"/>
                <path class="illu-trunk" d="M478 312v40"/>
                <path class="illu-car" d="M58 332h80l16 20H42l16-20Z"/>
                <circle class="illu-wheel" cx="67" cy="354" r="11"/>
                <circle class="illu-wheel" cx="130" cy="354" r="11"/>
            </svg>
        </div>
        <div class="visual-copy">
            <p class="eyebrow">West Metro Medical Center · Zamboanga City</p>
            <h1>Clear records.<br>Confident care.</h1>
            <p>One unified login for Patients, Doctors, Front Desk Staff, Cashiers, and Administrators — automatically routing you to your role portal.</p>
        </div>
        <div class="trust-row">
            <span><?= icon('shield', 16) ?> Data Privacy Act Compliant</span>
            <span>OTP-Verified Accounts</span>
            <span>Veterans Ave. Ext., ZC</span>
        </div>
    </section>

    <section class="login-form-side">
        <div class="login-card">
            <?= render_sim_banners() ?>

            <?php if ($isSetPassword): ?>
                <a class="back-link" href="index.php?auth=login" style="margin-bottom:14px;">← Back to sign in</a>
                <div class="mobile-login-logo"><?= logo() ?></div>
                <p class="eyebrow">First-Login Security · Account Setup</p>
                <h2>Set your personal password</h2>
                <p class="login-intro">
                    <?= !empty($_SESSION['force_pw_change_uid'])
                        ? 'For your security, please change your temporary password before entering your workspace.'
                        : 'Choose a strong password for your West Metro Medical Center account. The administrator never sees your password.' ?>
                </p>

                <?php if ($setPwError): ?>
                    <div class="login-alert wrong">
                        <?= icon('shield') ?>
                        <div>
                            <strong>Could not set password</strong>
                            <p><?= e($setPwError) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="complete_set_password">
                    <input type="hidden" name="uid" value="<?= e($setPwUid) ?>">
                    <input type="hidden" name="token" value="<?= e($setPwToken) ?>">

                    <?php if ($setPwUid !== ''): ?>
                        <div class="panel" style="padding:12px 14px; background:var(--teal-50); border:1px solid var(--teal-200); margin-bottom:14px; font-size:13px;">
                            Account ID: <strong><?= e($setPwUid) ?></strong>
                        </div>
                    <?php else: ?>
                        <?= field('Account ID or Email', 'uid', 'e.g. D-1001, S-1001, C-1001, or email', 'text', '', ['required' => true]) ?>
                    <?php endif; ?>

                    <?= field('New password (min. 8 characters)', 'password', 'Create your password', 'password', '', ['required' => true]) ?>
                    <?= field('Confirm new password', 'confirm_password', 'Re-enter your password', 'password', '', ['required' => true]) ?>

                    <button class="btn btn-primary" type="submit" style="width:100%; margin-top:8px;"><span>Save password &amp; continue</span></button>
                </form>

            <?php elseif ($isForgot): ?>
                <a class="back-link" href="index.php?auth=login" style="margin-bottom:14px;">← Back to sign in</a>
                <div class="mobile-login-logo"><?= logo() ?></div>
                <p class="eyebrow">Account Recovery</p>
                <h2>Forgot password?</h2>
                <p class="login-intro">Choose whether to receive a 6-digit verification code via Mobile SMS or Email.</p>

                <?php if ($resetError): ?>
                    <div class="login-alert wrong">
                        <?= icon('shield') ?>
                        <div>
                            <strong>Recovery error</strong>
                            <p><?= e($resetError) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="forgot">

                    <div class="field">
                        <span class="field-label">Send 6-Digit Verification Code Via</span>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:4px;">
                            <label style="display:flex; align-items:center; gap:8px; padding:10px 12px; border:1px solid var(--gray-300); border-radius:8px; cursor:pointer; background:#fff; font-weight:600; font-size:13px;">
                                <input type="radio" name="reset_method" value="phone" <?= $resetMethod !== 'email' ? 'checked' : '' ?> onchange="toggleResetMethod()">
                                <span>📱 Mobile Number</span>
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; padding:10px 12px; border:1px solid var(--gray-300); border-radius:8px; cursor:pointer; background:#fff; font-weight:600; font-size:13px;">
                                <input type="radio" name="reset_method" value="email" <?= $resetMethod === 'email' ? 'checked' : '' ?> onchange="toggleResetMethod()">
                                <span>✉️ Email Address</span>
                            </label>
                        </div>
                    </div>

                    <label class="field" id="forgot-phone-wrap" style="<?= $resetMethod === 'email' ? 'display:none;' : '' ?>">
                        <span class="field-label">Registered Mobile Number *</span>
                        <span class="input-wrap">
                            <input type="tel" name="phone" id="forgot-phone" placeholder="09XXXXXXXXX" value="<?= e($resetPhone) ?>" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);">
                        </span>
                        <small class="field-hint">We will send a 6-digit OTP to your registered mobile number.</small>
                    </label>

                    <label class="field" id="forgot-email-wrap" style="<?= $resetMethod === 'email' ? '' : 'display:none;' ?>">
                        <span class="field-label">Registered Email Address *</span>
                        <span class="input-wrap">
                            <input type="email" name="email" id="forgot-email" placeholder="name@example.com" value="<?= e($resetEmail) ?>">
                        </span>
                        <small class="field-hint">We will send a 6-digit verification code to your email.</small>
                    </label>

                    <button class="btn btn-primary" type="submit"><span>Send 6-digit OTP code</span></button>
                </form>

                <script>
                function toggleResetMethod() {
                    var method = document.querySelector('input[name="reset_method"]:checked').value;
                    document.getElementById('forgot-phone-wrap').style.display = (method === 'phone') ? '' : 'none';
                    document.getElementById('forgot-email-wrap').style.display = (method === 'email') ? '' : 'none';
                }
                </script>

            <?php elseif ($isVerify): ?>
                <a class="back-link" href="index.php?auth=login&amp;forgot=1" style="margin-bottom:14px;">← Back</a>
                <div class="mobile-login-logo"><?= logo() ?></div>
                <p class="eyebrow">Verify OTP Code</p>
                <h2>Enter 6-digit code</h2>
                <p class="login-intro"><?= e($resetInfo ?: 'Enter the 6-digit code sent to your device and choose a new password.') ?></p>

                <?php if ($resetSim): ?>
                    <div class="login-alert success" style="background:#f0fdfa; border-color:#5eead4; color:#0f766e;">
                        <?= icon('shield') ?>
                        <div>
                            <strong>Simulated OTP Delivery</strong>
                            <p><?= e($resetSim) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($resetError): ?>
                    <div class="login-alert wrong">
                        <?= icon('shield') ?>
                        <div>
                            <strong>Verification error</strong>
                            <p><?= e($resetError) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reset_password">
                    <label class="field">
                        <span class="field-label">6-Digit OTP Code</span>
                        <span class="input-wrap">
                            <input type="text" name="code" placeholder="123456" maxlength="6" required style="letter-spacing:4px; font-weight:700; font-size:18px; text-align:center;" value="<?= e((string) $resetOtp) ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);">
                        </span>
                    </label>
                    <div class="form-grid">
                        <?= field('New password', 'password', 'Minimum 8 characters', 'password', '', ['required' => true]) ?>
                        <?= field('Confirm password', 'confirm_password', 'Re-enter password', 'password', '', ['required' => true]) ?>
                    </div>
                    <button class="btn btn-primary" type="submit"><span>Update password</span></button>
                </form>

            <?php else: ?>
                <a class="back-link" href="index.php" style="margin-bottom:14px;">← Back to home</a>
                <div class="mobile-login-logo"><?= logo() ?></div>
                <p class="eyebrow">West Metro Medical Center</p>
                <h2>Sign in to your portal</h2>
                <p class="login-intro">Log in with your registered mobile number or email address and password. You will be redirected to your portal automatically.</p>

                <?php if ($successMsg): ?>
                    <div class="login-alert success">
                        <?= icon('shield') ?>
                        <div>
                            <strong>Account ready</strong>
                            <p><?= e($successMsg) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($loginError): ?>
                    <div class="login-alert <?= e($loginError === 'disabled' ? 'disabled' : ($loginError === 'pending' ? 'wrong' : 'wrong')) ?>">
                        <?= icon($loginError === 'disabled' ? 'lock' : 'shield') ?>
                        <div>
                            <strong><?= $loginError === 'disabled' ? 'Account deactivated' : ($loginError === 'pending' ? 'Account activation pending' : 'Incorrect credentials') ?></strong>
                            <p><?= $loginError === 'disabled'
                                ? 'This account has been deactivated by the administrator. Please contact WMMC Administration.'
                                : ($loginError === 'pending'
                                    ? 'Your account is pending activation. Please check your email to set your personal password and activate your account.'
                                    : 'No matching account found or password is incorrect. Please check your mobile number/email and password.') ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="login">

                    <label class="field">
                        <span class="field-label">Mobile Number or Email</span>
                        <span class="input-wrap">
                            <input type="text" name="login_identifier" id="login-identifier" placeholder="09XXXXXXXXX or name@example.com" value="<?= e($identifier) ?>" required autocomplete="off">
                        </span>
                        <small class="field-hint">Enter the phone number or email registered on your account.</small>
                    </label>
                    <script>
                    (function(){
                        var el = document.getElementById('login-identifier');
                        function cleanAutofill() {
                            if (el && (el.value.indexOf('Warning') !== -1 || el.value.indexOf('<br') !== -1 || el.value.indexOf('Undefined') !== -1)) {
                                el.value = '';
                            }
                        }
                        cleanAutofill();
                        window.addEventListener('DOMContentLoaded', cleanAutofill);
                        setTimeout(cleanAutofill, 50);
                        setTimeout(cleanAutofill, 250);
                        setTimeout(cleanAutofill, 600);
                    })();
                    </script>

                    <?= field('Password', 'password', 'Enter your password', 'password', '', ['required' => true]) ?>

                    <div class="forgot-row">
                        <label><input type="checkbox" name="remember"> Remember me</label>
                        <a class="text-link" href="index.php?auth=login&amp;forgot=1" style="font-size:13px;">Forgot password?</a>
                    </div>
                    <button class="btn btn-primary" type="submit" name="action" value="login"><span>Sign in</span></button>
                </form>

                <div class="auth-switch-box">
                    <span>Registered at the hospital front desk?</span>
                    <a class="text-link" href="index.php?auth=activate">Activate patient account <?= icon('arrow', 14) ?></a>
                </div>
            <?php endif; ?>
        </div>
        <p class="login-footer">© <?= date('Y') ?> <?= e(APP_NAME) ?> · Zamboanga City · Privacy · Support</p>
    </section>
</main>
</body>
</html>
