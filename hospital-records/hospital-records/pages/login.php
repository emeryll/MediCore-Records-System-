<?php
/** @var ?string $loginError  'wrong' | 'disabled' | 'invalid_id' | null */
$loginError = $_SESSION['login_error'] ?? null;
$successMsg = $_SESSION['login_success_msg'] ?? null;
$resetInfo  = $_SESSION['reset_info'] ?? null;
$resetError = $_SESSION['reset_error'] ?? null;
$resetEmail = (string) ($_SESSION['reset_email'] ?? '');
unset($_SESSION['login_error'], $_SESSION['login_success_msg'], $_SESSION['reset_info'], $_SESSION['reset_error']);

$userId   = (string) ($_SESSION['login_user_id'] ?? ($_GET['id'] ?? ''));
$username = (string) ($_SESSION['login_username'] ?? '');
unset($_SESSION['login_user_id'], $_SESSION['login_username']);

$isVerify = (($_GET['reset_step'] ?? '') === 'verify');
$isForgot = !$isVerify && (!empty($_GET['forgot']) || (($_GET['step'] ?? '') === 'forgot'));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isForgot ? 'Forgot Password' : ($isVerify ? 'Reset Password' : 'Sign in') ?> · <?= e(APP_NAME) ?> Hospital Records System</title>
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
            <p class="eyebrow">Care, connected</p>
            <h1>Clear records.<br>Confident care.</h1>
            <p>Sign in with your assigned MediCore ID to access your role workspace or patient portal.</p>
        </div>
        <div class="trust-row">
            <span><?= icon('shield', 16) ?> HIPAA compliant</span>
            <span>256-bit encryption</span>
            <span>24/7 monitoring</span>
        </div>
    </section>

    <section class="login-form-side">
        <div class="login-card">
            <?php if ($isForgot): ?>
                <a class="back-link" href="index.php?auth=login" style="margin-bottom:14px;">← Back to sign in</a>
                <div class="mobile-login-logo"><?= logo() ?></div>
                <p class="eyebrow">Password recovery</p>
                <h2>Forgot password?</h2>
                <p class="login-intro">Enter your account's email address to receive a 6-digit verification code.</p>

                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="forgot">
                    <label class="field">
                        <span class="field-label">Registered Email Address</span>
                        <span class="input-wrap">
                            <input type="email" name="email" id="forgot-email" placeholder="name@example.com" value="<?= e($resetEmail) ?>" required>
                        </span>
                        <small class="field-hint">We will send a 6-digit verification code that expires in 15 minutes.</small>
                    </label>
                    <button class="btn btn-primary" type="submit"><span>Send reset code</span></button>
                </form>

                <div class="auth-switch-box">
                    <span>Remember your password?</span>
                    <a class="text-link" href="index.php?auth=login">Sign in <?= icon('arrow', 14) ?></a>
                </div>

            <?php elseif ($isVerify): ?>
                <a class="back-link" href="index.php?auth=login" style="margin-bottom:14px;">← Back to sign in</a>
                <div class="mobile-login-logo"><?= logo() ?></div>
                <p class="eyebrow">Verification &amp; New Password</p>
                <h2>Set new password</h2>
                <p class="login-intro">Enter the 6-digit code sent to your email and choose your new password.</p>

                <?php if ($resetInfo): ?>
                    <div class="login-alert success">
                        <?= icon('shield') ?>
                        <div>
                            <strong>Verification code sent</strong>
                            <p><?= e($resetInfo) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($resetError): ?>
                    <div class="login-alert wrong">
                        <?= icon('shield') ?>
                        <div>
                            <strong>Reset error</strong>
                            <p><?= e($resetError) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reset_password">

                    <label class="field">
                        <span class="field-label">Account Email</span>
                        <span class="input-wrap">
                            <input type="email" name="email" id="reset-email" placeholder="name@example.com" value="<?= e($resetEmail) ?>" readonly style="background:var(--gray-50); color:var(--gray-700); font-weight:500;" required>
                        </span>
                        <small class="field-hint">The email address where your 6-digit code was delivered.</small>
                    </label>

                    <label class="field">
                        <span class="field-label">6-Digit Verification Code</span>
                        <span class="input-wrap">
                            <input type="text" name="code" id="reset-code" placeholder="123456" maxlength="12" required style="letter-spacing:4px; font-weight:700; font-size:18px; text-align:center;" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);">
                        </span>
                        <small class="field-hint">Valid for 15 minutes. Single-use only.</small>
                    </label>

                    <div class="form-grid">
                        <?= field('New password', 'password', 'Minimum 8 characters', 'password') ?>
                        <?= field('Confirm password', 'confirm_password', 'Re-enter password', 'password') ?>
                    </div>

                    <button class="btn btn-primary" type="submit"><span>Update password</span></button>
                </form>

                <div class="auth-switch-box">
                    <span>Return to sign in?</span>
                    <a class="text-link" href="index.php?auth=login">Sign in <?= icon('arrow', 14) ?></a>
                </div>

            <?php else: ?>
                <a class="back-link" href="index.php" style="margin-bottom:14px;">← Back to home</a>
                <div class="mobile-login-logo"><?= logo() ?></div>
                <p class="eyebrow">Welcome back</p>
                <h2>Sign in to MediCore</h2>
                <p class="login-intro">Enter your assigned ID, username, and password to continue.</p>

                <?php if ($successMsg): ?>
                    <div class="login-alert success">
                        <?= icon('shield') ?>
                        <div>
                            <strong>Password updated</strong>
                            <p><?= e($successMsg) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($loginError): ?>
                    <div class="login-alert <?= e($loginError === 'disabled' ? 'disabled' : 'wrong') ?>">
                        <?= icon($loginError === 'disabled' ? 'lock' : 'shield') ?>
                        <div>
                            <strong><?= match ($loginError) {
                                'disabled'   => 'Account disabled',
                                'invalid_id' => 'Invalid User ID format',
                                default      => 'Incorrect credentials',
                            } ?></strong>
                            <p><?= match ($loginError) {
                                'disabled'   => 'Your account has been disabled. Contact your administrator for assistance.',
                                'invalid_id' => 'Please enter a valid ID starting with P (Patient), S (Staff), D (Doctor), or A (Admin) — for example P-1001.',
                                default      => 'No matching account found or password is incorrect. Please check your ID, username, and password.',
                            } ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="login">
                    <label class="field">
                        <span class="field-label">User / Patient ID</span>
                        <span class="input-wrap">
                            <input type="text" name="user_id" id="login-user-id" placeholder="e.g. P-1001, S-1001, C-1001, D-1001, or A-0001" value="<?= e($userId) ?>" required>
                        </span>
                        <small class="field-hint">Patients use the <b>P-xxxx</b> ID issued by Front Desk Staff during registration.</small>
                    </label>
                    <?= field('Username, Email, or Name', 'username', 'e.g. admin or your email', 'text', $username) ?>
                    <?= field('Password', 'password', 'Enter your password', 'password') ?>
                    <div class="forgot-row">
                        <label><input type="checkbox" name="remember"> Remember me</label>
                        <a class="text-link" href="index.php?auth=login&amp;forgot=1" style="font-size:13px;">Forgot password?</a>
                    </div>
                    <button class="btn btn-primary" type="submit" name="action" value="login"><span>Sign in</span></button>
                </form>

                <div class="auth-switch-box">
                    <span>Don’t have an account yet?</span>
                    <a class="text-link" href="index.php?auth=register">Create an account <?= icon('arrow', 14) ?></a>
                </div>
            <?php endif; ?>
        </div>
        <p class="login-footer">© <?= date('Y') ?> MediCore Health · Privacy · Support</p>
    </section>
</main>
</body>
</html>
