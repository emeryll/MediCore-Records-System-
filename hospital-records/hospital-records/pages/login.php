<?php
/** @var ?string $loginError  'wrong' | 'disabled' | null */
$loginError = $_SESSION['login_error'] ?? null;
$resetSent  = !empty($_SESSION['reset_sent']);
unset($_SESSION['login_error'], $_SESSION['reset_sent']);

$demo     = $_GET['demo'] ?? '';
$username = in_array($demo, ['admin', 'staff', 'doctor'], true) ? $demo : (string) ($_SESSION['login_username'] ?? '');
$password = $username !== '' && $demo !== '' ? 'demo' : '';
unset($_SESSION['login_username']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · <?= e(APP_NAME) ?> Hospital Records System</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/style.css">
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
            <p>One secure place for your team to coordinate better outcomes for every patient.</p>
        </div>
        <div class="trust-row">
            <span><?= icon('shield', 16) ?> HIPAA compliant</span>
            <span>256-bit encryption</span>
            <span>24/7 monitoring</span>
        </div>
    </section>

    <section class="login-form-side">
        <div class="login-card">
            <div class="mobile-login-logo"><?= logo() ?></div>
            <p class="eyebrow">Welcome back</p>
            <h2>Hospital Records System</h2>
            <p class="login-intro">Sign in to access your secure workspace.</p>

            <?php if ($loginError): ?>
                <div class="login-alert <?= e($loginError) ?>">
                    <?= icon($loginError === 'disabled' ? 'lock' : 'shield') ?>
                    <div>
                        <strong><?= $loginError === 'disabled' ? 'Account disabled' : 'Incorrect credentials' ?></strong>
                        <p><?= $loginError === 'disabled'
                            ? 'Your account has been disabled. Contact your administrator for assistance.'
                            : 'The username or password you entered is incorrect. Please try again.' ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($resetSent): ?>
                <div class="login-alert success">
                    <?= icon('shield') ?>
                    <div>
                        <strong>Reset instructions sent</strong>
                        <p>Check your registered email for a secure password reset link.</p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="index.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="login">
                <?= field('Username', 'username', 'Enter your username', 'text', $username) ?>
                <?= field('Password', 'password', 'Enter your password', 'password', $password) ?>
                <div class="forgot-row">
                    <label><input type="checkbox" name="remember"> Remember me</label>
                    <button type="submit" name="action" value="forgot" formnovalidate>Forgot password?</button>
                </div>
                <?= button('Login', ['type' => 'submit']) ?>
            </form>

            <div class="demo-accounts">
                <span>Prototype accounts</span>
                <p>
                    <a href="index.php?demo=admin">admin</a>
                    <a href="index.php?demo=staff">staff</a>
                    <a href="index.php?demo=doctor">doctor</a>
                </p>
                <small>Use “wrong” or “disabled” to preview error states.</small>
            </div>
        </div>
        <p class="login-footer">© <?= date('Y') ?> MediCore Health · Privacy · Support</p>
    </section>
</main>
</body>
</html>
