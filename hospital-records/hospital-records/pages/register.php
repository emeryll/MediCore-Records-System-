<?php
declare(strict_types=1);

$regError     = $_SESSION['register_error'] ?? null;
$old          = $_SESSION['reg_old'] ?? [];
$adminClaimed = db_admin_claimed();
unset($_SESSION['register_error'], $_SESSION['reg_old']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account · <?= e(APP_NAME) ?> Hospital Records System</title>
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
            <p class="eyebrow">Patient Portal Registration</p>
            <h1>Activate your<br>Patient ID.</h1>
            <p>Patients can register using the <b>P-ID</b> issued by hospital staff<?= !$adminClaimed ? ' (or <b>A-ID</b> for the one-time System Administrator)' : '' ?>. Doctors (<b>D-ID</b>) and Staff (<b>S-ID</b>) accounts are created directly by the Administrator.</p>
        </div>
        <div class="trust-row">
            <span><?= icon('shield', 16) ?> Database-backed</span>
            <span>Encrypted credentials</span>
            <span>Role-verified IDs</span>
        </div>
    </section>

    <section class="login-form-side">
        <div class="login-card">
            <a class="back-link" href="index.php" style="margin-bottom:14px;">← Back to home</a>
            <div class="mobile-login-logo"><?= logo() ?></div>
            <p class="eyebrow">Create account</p>
            <h2>Register your Patient ID</h2>
            <p class="login-intro">Enter the <b>P-ID</b> given to you at the front desk to create your patient portal account.</p>

            <?php if (!$adminClaimed): ?>
                <div class="login-alert success" style="margin-bottom:14px;">
                    <?= icon('shield') ?>
                    <div>
                        <strong>One-Time Administrator Setup Available</strong>
                        <p>No Admin account exists yet. Enter an ID starting with <b>A</b> (e.g. <code>A-0001</code>) to claim the single Administrator account. Once used, no <b>A</b> ID can ever be registered again.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($regError): ?>
                <div class="login-alert wrong">
                    <?= icon('shield') ?>
                    <div>
                        <strong>Registration could not be completed</strong>
                        <p><?= e($regError) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="id-prefix-pills" aria-label="ID prefix guide">
                <span><b>P</b> Patient</span>
                <span class="is-locked" title="Doctor and Staff accounts are created by the Administrator"><b>D / S</b> Created by Admin</span>
                <?php if ($adminClaimed): ?>
                    <span class="is-locked" title="The single Administrator account has already been claimed"><b>A</b> Admin <small>(Claimed · Locked)</small></span>
                <?php else: ?>
                    <span><b>A</b> Admin <small>(1 only)</small></span>
                <?php endif; ?>
            </div>

            <form method="post" action="index.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="register_account">

                <label class="field">
                    <span class="field-label">Assigned Patient ID</span>
                    <span class="input-wrap">
                        <input type="text" name="user_id" id="reg-user-id"
                               placeholder="<?= $adminClaimed ? 'e.g. P-1001' : 'e.g. P-1001 or A-0001' ?>"
                               value="<?= e($old['user_id'] ?? '') ?>" required>
                    </span>
                    <small class="field-hint">
                        <?= $adminClaimed
                            ? 'Enter your <b>P-ID</b> (e.g. <code>P-1001</code>). Doctor (<b>D</b>) and Staff (<b>S</b>) accounts are created by the Administrator.'
                            : 'Enter <b>P-ID</b> for Patient or <b>A-ID</b> for one-time Admin setup. Doctor (<b>D</b>) and Staff (<b>S</b>) accounts are created by Admin.' ?>
                    </small>
                </label>

                <div class="form-grid">
                    <?= field('First name', 'first_name', 'First name', 'text', $old['first_name'] ?? '') ?>
                    <?= field('Last name', 'last_name', 'Last name', 'text', $old['last_name'] ?? '') ?>
                </div>

                <div class="form-grid" id="reg-bdate-row">
                    <label class="field">
                        <span class="field-label">Birthday (Date of birth)</span>
                        <span class="input-wrap">
                            <input type="date" name="birthdate" id="reg-birthdate" max="<?= date('Y-m-d') ?>" value="<?= e($old['birthdate'] ?? '') ?>" required>
                        </span>
                    </label>
                    <label class="field">
                        <span class="field-label">Age (Auto-calculated)</span>
                        <span class="input-wrap">
                            <input type="text" id="reg-age-preview" value="—" readonly style="background:var(--gray-50); font-weight:600; color:var(--teal-800);">
                        </span>
                    </label>
                </div>

                <div class="form-grid">
                    <?= field('Username', 'username', 'Choose a username', 'text', $old['username'] ?? '') ?>
                    <?= field('Email address', 'email', 'name@example.com', 'email', $old['email'] ?? '') ?>
                </div>

                <div class="form-grid">
                    <?= field('Password', 'password', 'Minimum 8 characters', 'password') ?>
                    <?= field('Confirm password', 'confirm_password', 'Re-enter password', 'password') ?>
                </div>

                <?= button('Create account', ['type' => 'submit']) ?>
            </form>

            <script>
            (function () {
                var idInput = document.getElementById('reg-user-id');
                var bInput  = document.getElementById('reg-birthdate');
                var ageBox  = document.getElementById('reg-age-preview');
                var bRow    = document.getElementById('reg-bdate-row');

                function calcAge() {
                    if (!bInput || !ageBox) return;
                    var val = bInput.value;
                    if (!val) {
                        ageBox.value = '—';
                        return;
                    }
                    var dob = new Date(val + 'T00:00:00');
                    if (isNaN(dob.getTime())) {
                        ageBox.value = '—';
                        return;
                    }
                    var today = new Date();
                    var age = today.getFullYear() - dob.getFullYear();
                    var m = today.getMonth() - dob.getMonth();
                    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                        age--;
                    }
                    age = Math.max(0, age);
                    ageBox.value = age + (age === 1 ? ' year old' : ' years old');
                }

                function syncIdPrefix() {
                    if (!idInput || !bInput || !bRow) return;
                    var v = (idInput.value || '').trim().toUpperCase();
                    var isAdmin = v.indexOf('A') === 0;
                    bRow.style.display = isAdmin ? 'none' : '';
                    bInput.required = !isAdmin;
                }

                if (bInput) {
                    bInput.addEventListener('input', calcAge);
                    bInput.addEventListener('change', calcAge);
                    calcAge();
                }
                if (idInput) {
                    idInput.addEventListener('input', syncIdPrefix);
                    syncIdPrefix();
                }
            })();
            </script>

            <div class="auth-switch-box">
                <span>Already have a registered account?</span>
                <a class="text-link" href="index.php?auth=login">Sign in <?= icon('arrow', 14) ?></a>
            </div>
        </div>
        <p class="login-footer">© <?= date('Y') ?> MediCore Health · Privacy · Support</p>
    </section>
</main>
</body>
</html>
