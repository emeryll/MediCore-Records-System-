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

            <form method="post" action="index.php" id="register-form" onsubmit="return confirm('Are you sure about this action?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="register_account">

                <!-- Step 1: ID Input -->
                <div style="margin-bottom:18px;">
                    <label class="field">
                        <span class="field-label">Assigned Patient ID</span>
                        <div style="display:flex; gap:8px;">
                            <span class="input-wrap" style="flex:1;">
                                <input type="text" name="user_id" id="reg-user-id"
                                       placeholder="<?= $adminClaimed ? 'e.g. P-1001' : 'e.g. P-1001 or A-0001' ?>"
                                       value="<?= e($old['user_id'] ?? '') ?>" required autocomplete="off" style="font-weight:700; font-family:monospace; font-size:15px; letter-spacing:.5px;">
                            </span>
                            <button type="button" id="btn-lookup-id" class="btn btn-secondary" style="height:42px; padding:0 16px; white-space:nowrap;">
                                <span>Look Up ID</span>
                            </button>
                        </div>
                        <small class="field-hint" id="reg-id-hint">
                            Enter the <b>P-ID</b> issued to you by the front desk. Your registration details will reflect automatically.
                        </small>
                    </label>
                </div>

                <!-- Status / Error / Warning Box -->
                <div id="reg-feedback-box" style="display:none; margin-bottom:16px;"></div>

                <!-- Step 2: Patient Registration Details (Read-only Reflection) -->
                <div id="patient-details-card" style="display:none; background:var(--teal-50); border:1px solid var(--teal-200); border-radius:12px; padding:16px; margin-bottom:18px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid var(--teal-100); padding-bottom:8px;">
                        <span style="font-weight:700; font-size:13px; color:var(--teal-900); display:flex; align-items:center; gap:6px;">
                            <?= icon('clipboard', 16) ?> Verified Hospital Registration
                        </span>
                        <span class="badge badge-success" style="font-size:11px;">Found</span>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; font-size:13px;">
                        <div>
                            <span style="font-size:11px; text-transform:uppercase; color:var(--gray-600); font-weight:600; display:block;">Full Name (Your Username)</span>
                            <strong id="ref-name" style="color:var(--navy-900); font-size:14px;">—</strong>
                        </div>
                        <div>
                            <span style="font-size:11px; text-transform:uppercase; color:var(--gray-600); font-weight:600; display:block;">Date of Birth</span>
                            <strong id="ref-birth" style="color:var(--navy-900);">—</strong>
                        </div>
                        <div>
                            <span style="font-size:11px; text-transform:uppercase; color:var(--gray-600); font-weight:600; display:block;">Registered Phone</span>
                            <strong id="ref-phone" style="color:var(--navy-900); font-family:monospace;">—</strong>
                        </div>
                        <div>
                            <span style="font-size:11px; text-transform:uppercase; color:var(--gray-600); font-weight:600; display:block;">Registered Address</span>
                            <strong id="ref-address" style="color:var(--navy-900); font-size:12.5px;">—</strong>
                        </div>
                    </div>
                </div>

                <!-- SMS OTP Simulator Notice -->
                <div id="reg-sms-simulator-box" style="display:none; margin-bottom:18px; padding:12px 14px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; font-size:13px; color:#1e40af;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span id="reg-sms-sim-text"></span>
                        <button type="button" id="btn-reg-autofill" class="btn btn-sm btn-primary" style="font-size:11px; padding:4px 9px; margin-left:8px;">Auto-fill Code</button>
                    </div>
                </div>

                <!-- Step 3: Verification & Password Fields for Patient -->
                <div id="patient-auth-section" style="display:none;">
                    <label class="field" style="margin-bottom:16px;">
                        <span class="field-label">6-Digit SMS Verification Code</span>
                        <div style="display:flex; gap:8px;">
                            <span class="input-wrap" style="flex:1;">
                                <input type="text" name="otp_code" id="reg-otp-code" maxlength="6" placeholder="Enter 6-digit code sent to your phone" autocomplete="one-time-code" style="font-size:16px; letter-spacing:2px; font-weight:700;">
                            </span>
                            <button type="button" id="btn-reg-resend-otp" class="btn btn-secondary" style="height:42px; padding:0 14px; white-space:nowrap;">
                                <span>Resend Code</span>
                            </button>
                        </div>
                        <small class="field-hint">A verification code was sent to your registered phone number.</small>
                    </label>

                    <div class="form-grid" style="margin-bottom:14px;">
                        <label class="field">
                            <span class="field-label">Email Address (Optional)</span>
                            <span class="input-wrap">
                                <input type="email" name="email" id="reg-email" placeholder="e.g. name@example.com (Optional)" value="<?= e($old['email'] ?? '') ?>" autocomplete="email">
                            </span>
                            <small class="field-hint">Optional. Can be used to sign in and recover your password.</small>
                        </label>
                    </div>

                    <div class="form-grid">
                        <?= field('Create password', 'password', 'Minimum 8 characters', 'password') ?>
                        <?= field('Confirm password', 'confirm_password', 'Re-enter password', 'password') ?>
                    </div>

                    <div style="margin-top:16px;">
                        <?= button('Verify & Activate Account', ['type' => 'submit', 'id' => 'btn-submit-patient']) ?>
                    </div>
                </div>

                <!-- Fallback Admin Setup Section (Only shown if entering A-xxxx before Admin is claimed) -->
                <div id="admin-setup-section" style="display:none;">
                    <div class="form-grid">
                        <?= field('First name', 'first_name', 'First name', 'text', $old['first_name'] ?? '') ?>
                        <?= field('Last name', 'last_name', 'Last name', 'text', $old['last_name'] ?? '') ?>
                    </div>
                    <div class="form-grid" id="reg-bdate-row">
                        <label class="field">
                            <span class="field-label">Birthday (Date of birth)</span>
                            <span class="input-wrap">
                                <input type="date" name="birthdate" id="reg-birthdate" max="<?= date('Y-m-d') ?>" value="<?= e($old['birthdate'] ?? '') ?>">
                            </span>
                        </label>
                    </div>
                    <div class="form-grid">
                        <?= field('Username', 'username', 'Choose an admin username', 'text', $old['username'] ?? '') ?>
                        <?= field('Email address', 'email', 'admin@example.com', 'email', $old['email'] ?? '') ?>
                    </div>
                    <div class="form-grid">
                        <?= field('Admin password', 'password_admin', 'Minimum 8 characters', 'password') ?>
                        <?= field('Confirm admin password', 'confirm_password_admin', 'Re-enter password', 'password') ?>
                    </div>
                    <?= button('Create Administrator Account', ['type' => 'submit', 'id' => 'btn-submit-admin']) ?>
                </div>
            </form>

            <script>
            (function () {
                var idInput       = document.getElementById('reg-user-id');
                var lookupBtn     = document.getElementById('btn-lookup-id');
                var feedbackBox   = document.getElementById('reg-feedback-box');
                var ptCard        = document.getElementById('patient-details-card');
                var ptAuthSec     = document.getElementById('patient-auth-section');
                var adminSec      = document.getElementById('admin-setup-section');
                var simBox        = document.getElementById('reg-sms-simulator-box');
                var simText       = document.getElementById('reg-sms-sim-text');
                var autofillBtn   = document.getElementById('btn-reg-autofill');
                var resendBtn     = document.getElementById('btn-reg-resend-otp');
                var otpInput      = document.getElementById('reg-otp-code');
                var regForm       = document.getElementById('register-form');

                // Reflection labels
                var refName    = document.getElementById('ref-name');
                var refBirth   = document.getElementById('ref-birth');
                var refPhone   = document.getElementById('ref-phone');
                var refAddress = document.getElementById('ref-address');

                var currentPatientPhone = '';
                var lastCode = '';
                var lookupTimer = null;

                function showFeedback(type, message) {
                    feedbackBox.className = 'login-alert ' + (type === 'error' ? 'wrong' : (type === 'success' ? 'success' : 'warning'));
                    feedbackBox.innerHTML = '<span style="font-size:18px;">ℹ</span><div><p style="margin:0;">' + message + '</p></div>';
                    feedbackBox.style.display = 'flex';
                }

                function clearFeedback() {
                    feedbackBox.style.display = 'none';
                    feedbackBox.innerHTML = '';
                }

                function performLookup() {
                    var val = (idInput.value || '').trim().toUpperCase();
                    clearFeedback();

                    if (!val) {
                        ptCard.style.display = 'none';
                        ptAuthSec.style.display = 'none';
                        adminSec.style.display = 'none';
                        simBox.style.display = 'none';
                        return;
                    }

                    // If Admin ID (A-xxxx)
                    if (val.indexOf('A') === 0) {
                        ptCard.style.display = 'none';
                        ptAuthSec.style.display = 'none';
                        simBox.style.display = 'none';
                        adminSec.style.display = 'block';
                        return;
                    }

                    // Otherwise Patient ID (P-xxxx)
                    adminSec.style.display = 'none';
                    lookupBtn.disabled = true;
                    lookupBtn.innerText = 'Looking up...';

                    var fd = new FormData();
                    fd.append('action', 'ajax_lookup_patient');
                    fd.append('patient_id', val);

                    fetch('index.php', { method: 'POST', body: fd })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            lookupBtn.disabled = false;
                            lookupBtn.innerText = 'Look Up ID';

                            if (!res.ok) {
                                ptCard.style.display = 'none';
                                ptAuthSec.style.display = 'none';
                                simBox.style.display = 'none';
                                showFeedback('error', res.message || 'Could not find patient record.');
                                return;
                            }

                            // Patient found! Reflect details
                            var p = res.patient;
                            refName.innerText    = p.full_name || '—';
                            refBirth.innerText   = p.birthdate || '—';
                            refPhone.innerText   = p.phone_masked || p.phone || '—';
                            refAddress.innerText = p.address || '—';
                            currentPatientPhone  = p.phone;

                            ptCard.style.display = 'block';
                            ptAuthSec.style.display = 'block';

                            // Show SMS Simulator Toast
                            if (res.otp_code) {
                                lastCode = res.otp_code;
                                simText.innerText = res.simulated_message || ('💬 Simulated SMS to ' + p.phone_masked + ': Your verification code is ' + res.otp_code);
                                simBox.style.display = 'block';
                            }
                            otpInput.focus();
                        })
                        .catch(function () {
                            lookupBtn.disabled = false;
                            lookupBtn.innerText = 'Look Up ID';
                            showFeedback('error', 'Error contacting server. Please check your connection.');
                        });
                }

                if (lookupBtn) {
                    lookupBtn.addEventListener('click', performLookup);
                }

                if (idInput) {
                    idInput.addEventListener('input', function () {
                        clearTimeout(lookupTimer);
                        var v = (idInput.value || '').trim();
                        // Auto-lookup if pattern matches P-1001 or length >= 6
                        if (v.length >= 6 || v.indexOf('A') === 0) {
                            lookupTimer = setTimeout(performLookup, 400);
                        }
                    });
                }

                if (autofillBtn) {
                    autofillBtn.addEventListener('click', function () {
                        if (lastCode) {
                            otpInput.value = lastCode;
                        }
                    });
                }

                if (resendBtn) {
                    resendBtn.addEventListener('click', function () {
                        if (!currentPatientPhone) {
                            alert('No patient phone available to resend code.');
                            return;
                        }
                        resendBtn.disabled = true;
                        resendBtn.innerText = 'Sending...';

                        var fd = new FormData();
                        fd.append('action', 'ajax_send_phone_otp');
                        fd.append('phone', currentPatientPhone);
                        fd.append('purpose', 'account_creation');

                        fetch('index.php', { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (res) {
                                resendBtn.disabled = false;
                                resendBtn.innerText = 'Resend Code';
                                if (res.ok) {
                                    lastCode = res.code;
                                    simText.innerText = res.simulated_message || ('💬 Simulated SMS: Verification code is ' + res.code);
                                    simBox.style.display = 'block';
                                    alert('New verification code sent to your phone!');
                                } else {
                                    alert(res.error || 'Failed to resend code.');
                                }
                            })
                            .catch(function () {
                                resendBtn.disabled = false;
                                resendBtn.innerText = 'Resend Code';
                                alert('Error sending code.');
                            });
                    });
                }

                // If user_id already entered from previous attempt
                if (idInput && idInput.value.trim() !== '') {
                    performLookup();
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
