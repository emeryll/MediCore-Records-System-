<?php
declare(strict_types=1);

$currentUid = (string) ($_SESSION['user_id'] ?? '');
if ($currentUid === '') {
    redirect('index.php?auth=login');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE UPPER(user_id) = :uid LIMIT 1');
$stmt->execute([':uid' => strtoupper($currentUid)]);
$myAcc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$myAcc) {
    // Fallback if not found in accounts
    $myAcc = [
        'user_id'    => $currentUid,
        'full_name'  => current_user_name($role),
        'role'       => $role,
        'phone'      => '',
        'birthdate'  => '',
        'sex'        => 'Not specified',
        'address'    => '',
        'country'    => 'Philippines',
        'province'   => '',
        'city'       => '',
        'barangay'   => '',
        'street'     => '',
        'postal_code'=> '',
        'department' => '',
        'specialty'  => '',
        'license_number' => '',
    ];
}

$myPhone      = (string) ($myAcc['phone'] ?? '');
$myDob        = (string) ($myAcc['birthdate'] ?? '');
$mySex        = (string) ($myAcc['sex'] ?? 'Not specified');
$myCountry    = (string) ($myAcc['country'] ?? 'Philippines') ?: 'Philippines';
$myProvince   = (string) ($myAcc['province'] ?? '');
$myCity       = (string) ($myAcc['city'] ?? '');
$myBarangay   = (string) ($myAcc['barangay'] ?? '');
$myStreet     = (string) ($myAcc['street'] ?? '');
$myPostal     = (string) ($myAcc['postal_code'] ?? '');
$myAddr       = (string) ($myAcc['address'] ?? '');

if ($myProvince === '' && $myCity === '' && $myAddr !== '') {
    $parts = array_map('trim', explode(',', $myAddr));
    if (count($parts) >= 2) {
        $myStreet = $parts[0] ?? '';
        $myCity   = $parts[1] ?? '';
        if (count($parts) >= 3) {
            $myProvince = $parts[2] ?? '';
        }
    } else {
        $myStreet = $myAddr;
    }
}

$myAge = patient_age_label($myDob, false, '—');
$roleTitle = current_user_title($role);
?>

<?= page_title(
    'My Profile & Security',
    'Manage your contact details, mobile phone number, and account credentials. Role: ' . $roleTitle
) ?>

<div class="profile-layout" style="max-width: 920px;">
    <!-- Profile Card Header -->
    <div class="panel" style="margin-bottom: 24px; padding: 24px; background: #fff; border-radius: 14px;">
        <div style="display:flex; align-items:center; gap: 20px; flex-wrap:wrap;">
            <div class="avatar" style="width:68px; height:68px; font-size:24px; font-weight:700;">
                <?= e(initials((string) $myAcc['full_name'], 2)) ?>
            </div>
            <div style="flex:1;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                    <h2 style="margin:0; font-size:22px; color:var(--navy-900);"><?= e($myAcc['full_name']) ?></h2>
                    <span class="badge badge-teal"><?= e($myAcc['role']) ?></span>
                </div>
                <p style="margin:0; font-size:13.5px; color:var(--gray-600);">
                    Assigned ID: <code style="font-weight:700; font-size:14px; color:var(--teal-800);"><?= e($currentUid) ?></code>
                    <?php if ($myDob !== '' && $myDob !== '—'): ?>
                        · Birthday: <strong><?= e($myDob) ?></strong> (Age: <?= e($myAge) ?>)
                    <?php endif; ?>
                    · Status: <span class="badge badge-success" style="font-size:11px;">Active</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Edit Profile Form -->
    <div class="panel" style="padding: 24px; background: #fff; border-radius: 14px;">
        <form method="post" action="index.php" id="my-profile-form" onsubmit="return confirm('Are you sure about this action?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_my_profile">

            <h3 style="font-size:16px; margin:0 0 16px 0; color:var(--navy-900); border-bottom:1px solid var(--gray-200); padding-bottom:8px;">
                Contact &amp; Mobile Verification
            </h3>

            <!-- Mobile Phone Input & OTP Verification -->
            <div class="form-grid">
                <label class="field">
                    <span class="field-label">Mobile Number *</span>
                    <div style="display:flex; gap:8px;">
                        <span class="input-wrap" style="flex:1;">
                            <input type="tel" name="phone" id="prof-phone-input" placeholder="09XXXXXXXX" value="<?= e($myPhone) ?>" required>
                        </span>
                        <button type="button" id="btn-prof-send-otp" class="btn btn-secondary" style="height:42px; padding:0 14px; white-space:nowrap;">
                            <span>Send OTP</span>
                        </button>
                    </div>
                    <small class="field-hint" id="prof-phone-hint">
                        <?= $myPhone !== '' ? 'Current registered mobile number. If changed, an OTP verification is required.' : 'Enter your 11-digit mobile number.' ?>
                    </small>
                </label>

                <label class="field" id="prof-otp-field">
                    <span class="field-label">New Phone OTP Verification</span>
                    <div style="display:flex; gap:8px;">
                        <span class="input-wrap" style="flex:1;">
                            <input type="text" id="prof-otp-code-input" placeholder="6-digit code" maxlength="6" style="letter-spacing:2px; font-weight:700;">
                        </span>
                        <button type="button" id="btn-prof-verify-otp" class="btn btn-primary" style="height:42px; padding:0 14px; white-space:nowrap;">
                            <span>Verify Code</span>
                        </button>
                    </div>
                    <small class="field-hint" id="prof-otp-status-hint">Enter the code sent to your new mobile number.</small>
                </label>
            </div>

            <!-- Simulated SMS Alert for Testing -->
            <div id="prof-sms-sim-box" style="display:none; margin-bottom:16px; padding:10px 14px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; font-size:12.5px; color:#1e40af;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span id="prof-sms-sim-text"></span>
                    <button type="button" id="btn-prof-sms-autofill" class="btn btn-sm btn-primary" style="font-size:11px; padding:3px 8px; margin-left:8px;">Auto-fill</button>
                </div>
            </div>

            <input type="hidden" name="phone_verified" id="prof-phone-verified" value="1">

            <div class="form-grid" style="margin-bottom:14px;">
                <label class="field">
                    <span class="field-label">Email Address (Optional for Patients)</span>
                    <span class="input-wrap">
                        <input type="email" name="email" id="prof-email-input" placeholder="name@example.com" value="<?= e($myAcc['email'] ?? '') ?>">
                    </span>
                    <small class="field-hint">Used for system notifications and password recovery.</small>
                </label>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span class="field-label">Sex</span>
                    <span class="input-wrap">
                        <select name="sex" id="prof-sex-select">
                            <option value="" disabled <?= empty($mySex) || $mySex === 'Not specified' ? 'selected' : '' ?> hidden>Select sex</option>
                            <option value="Female" <?= $mySex === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Male" <?= $mySex === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Other" <?= $mySex === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                        <?= icon('down', 16) ?>
                    </span>
                </label>

                <label class="field">
                    <span class="field-label">Birthday (Date of birth)</span>
                    <span class="input-wrap">
                        <input type="text" value="<?= e($myDob ?: '—') ?>" readonly style="background:var(--gray-50); font-weight:500;">
                    </span>
                    <small class="field-hint">Date of birth is registered by staff and is read-only.</small>
                </label>
            </div>

            <h3 style="font-size:16px; margin:24px 0 16px 0; color:var(--navy-900); border-bottom:1px solid var(--gray-200); padding-bottom:8px;">
                Chopped Residential Address
            </h3>

            <div class="form-grid">
                <label class="field">
                    <span class="field-label">Country</span>
                    <span class="input-wrap">
                        <select name="country" id="prof-country-select"></select>
                        <?= icon('down', 16) ?>
                    </span>
                </label>

                <label class="field">
                    <span class="field-label">Province / Region</span>
                    <span class="input-wrap">
                        <select name="province" id="prof-province-select"></select>
                        <?= icon('down', 16) ?>
                    </span>
                </label>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span class="field-label">City / Municipality</span>
                    <span class="input-wrap">
                        <select name="city" id="prof-city-select"></select>
                        <?= icon('down', 16) ?>
                    </span>
                </label>

                <label class="field">
                    <span class="field-label">Barangay / District</span>
                    <span class="input-wrap">
                        <select name="barangay" id="prof-barangay-select"></select>
                        <?= icon('down', 16) ?>
                    </span>
                </label>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span class="field-label">Street / Building / Unit (put N/A if not applicable)</span>
                    <span class="input-wrap">
                        <input type="text" name="street" id="prof-street-input" placeholder="e.g. 123 Main St, Unit 4B (put N/A if not applicable)" value="<?= e($myStreet) ?>">
                    </span>
                    <small class="field-hint">House/building no., street, or unit (put N/A if not applicable)</small>
                </label>

                <label class="field">
                    <span class="field-label">Postal / ZIP Code</span>
                    <span class="input-wrap">
                        <input type="text" name="postal_code" id="prof-postal-input" placeholder="Auto-populated by city" value="<?= e($myPostal) ?>" readonly style="background:var(--gray-50); font-family:monospace; font-weight:600;">
                    </span>
                    <small class="field-hint">Auto-populated based on selected City/Region.</small>
                </label>
            </div>

            <?php if ($role === 'Doctor'): ?>
                <h3 style="font-size:16px; margin:24px 0 16px 0; color:var(--navy-900); border-bottom:1px solid var(--gray-200); padding-bottom:8px;">
                    Medical Practice Information
                </h3>
                <div class="form-grid three-col">
                    <?= field('Specialty', 'specialty', 'e.g. Cardiology', 'text', $myAcc['specialty'] ?? '') ?>
                    <?= field('License number', 'license', 'License no.', 'text', $myAcc['license_number'] ?? '') ?>
                    <?= select_field('Department', 'department', array_column($state['crud']['departments'] ?? [], 0) ?: ['General Medicine', 'Cardiology', 'Neurology', 'Pediatrics', 'Orthopedics'], $myAcc['department'] ?? '') ?>
                </div>
            <?php endif; ?>

            <h3 style="font-size:16px; margin:24px 0 16px 0; color:var(--navy-900); border-bottom:1px solid var(--gray-200); padding-bottom:8px;">
                Change Password (Optional)
            </h3>
            <div class="form-grid">
                <?= field('New password', 'new_password', 'Leave blank to keep current password', 'password') ?>
            </div>

            <div style="margin-top: 24px; display:flex; gap:12px;">
                <?= button('Update Profile', ['type' => 'submit', 'id' => 'btn-save-profile']) ?>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    // 1. Initialize Cascading Address Dropdowns
    if (window.PHAddressSelector) {
        window.PHAddressSelector.init({
            country: document.getElementById('prof-country-select'),
            province: document.getElementById('prof-province-select'),
            city: document.getElementById('prof-city-select'),
            barangay: document.getElementById('prof-barangay-select'),
            street: document.getElementById('prof-street-input'),
            postal: document.getElementById('prof-postal-input'),
            initialCountry: <?= json_encode($myCountry) ?>,
            initialProvince: <?= json_encode($myProvince) ?>,
            initialCity: <?= json_encode($myCity) ?>,
            initialBarangay: <?= json_encode($myBarangay) ?>,
            initialPostal: <?= json_encode($myPostal) ?>
        });
    }

    // 2. Phone Change Detection and OTP Verification
    var initialPhone  = <?= json_encode($myPhone) ?>;
    var phoneInput    = document.getElementById('prof-phone-input');
    var btnSendOtp    = document.getElementById('btn-prof-send-otp');
    var otpInput      = document.getElementById('prof-otp-code-input');
    var btnVerifyOtp  = document.getElementById('btn-prof-verify-otp');
    var verifiedInput = document.getElementById('prof-phone-verified');
    var statusHint    = document.getElementById('prof-otp-status-hint');
    var smsBox        = document.getElementById('prof-sms-sim-box');
    var smsText       = document.getElementById('prof-sms-sim-text');
    var btnAutofill   = document.getElementById('btn-prof-sms-autofill');
    var lastSentOtp   = '';

    function normalizePhone(p) {
        return (p || '').replace(/[^0-9]/g, '');
    }

    phoneInput.addEventListener('input', function () {
        var cur = normalizePhone(phoneInput.value);
        var init = normalizePhone(initialPhone);
        if (cur !== init) {
            verifiedInput.value = '0';
            statusHint.innerHTML = '<span style="color:var(--amber-700); font-weight:600;">⚠ Phone number changed. Click "Send OTP" to verify.</span>';
        } else {
            verifiedInput.value = '1';
            statusHint.innerHTML = '<span style="color:var(--teal-700);">Current registered phone number.</span>';
        }
    });

    if (btnSendOtp && phoneInput) {
        btnSendOtp.addEventListener('click', function () {
            var phone = phoneInput.value.trim();
            if (!phone) {
                alert('Please enter a mobile number.');
                phoneInput.focus();
                return;
            }
            btnSendOtp.disabled = true;
            btnSendOtp.textContent = 'Sending...';

            var formData = new FormData();
            formData.append('action', 'ajax_send_phone_otp');
            formData.append('phone', phone);
            formData.append('user_id', <?= json_encode($currentUid) ?>);
            formData.append('purpose', 'profile_update');

            fetch('index.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    btnSendOtp.disabled = false;
                    btnSendOtp.textContent = 'Resend OTP';
                    if (data.ok) {
                        lastSentOtp = data.code || '';
                        if (smsBox && smsText) {
                            smsText.textContent = data.simulated_message || ('💬 Verification code: ' + lastSentOtp);
                            smsBox.style.display = 'block';
                        }
                        statusHint.innerHTML = '<span style="color:var(--teal-700);">OTP sent! Enter 6-digit code to verify.</span>';
                        if (otpInput) otpInput.focus();
                    } else {
                        alert(data.message || 'Failed to send OTP.');
                    }
                })
                .catch(function () {
                    btnSendOtp.disabled = false;
                    btnSendOtp.textContent = 'Send OTP';
                    alert('Network error while requesting OTP.');
                });
        });
    }

    if (btnAutofill) {
        btnAutofill.addEventListener('click', function () {
            if (lastSentOtp && otpInput) {
                otpInput.value = lastSentOtp;
                if (btnVerifyOtp) btnVerifyOtp.click();
            }
        });
    }

    if (btnVerifyOtp && otpInput && phoneInput) {
        btnVerifyOtp.addEventListener('click', function () {
            var code = otpInput.value.trim();
            var phone = phoneInput.value.trim();
            if (!code || code.length < 4) {
                alert('Please enter the 6-digit OTP code.');
                otpInput.focus();
                return;
            }
            btnVerifyOtp.disabled = true;
            btnVerifyOtp.textContent = 'Verifying...';

            var formData = new FormData();
            formData.append('action', 'ajax_verify_phone_otp');
            formData.append('phone', phone);
            formData.append('code', code);
            formData.append('purpose', 'profile_update');

            fetch('index.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    btnVerifyOtp.disabled = false;
                    btnVerifyOtp.textContent = 'Verify Code';
                    if (data.ok) {
                        verifiedInput.value = '1';
                        statusHint.innerHTML = '<strong style="color:var(--teal-700);">✓ Phone Number Verified Successfully</strong>';
                        if (smsBox) smsBox.style.display = 'none';
                        btnVerifyOtp.style.background = 'var(--teal-600)';
                        btnVerifyOtp.textContent = '✓ Verified';
                        btnVerifyOtp.disabled = true;
                    } else {
                        alert(data.message || 'Invalid or expired OTP code.');
                    }
                })
                .catch(function () {
                    btnVerifyOtp.disabled = false;
                    btnVerifyOtp.textContent = 'Verify Code';
                    alert('Network error while verifying OTP.');
                });
        });
    }

    // Submit Guard
    var form = document.getElementById('my-profile-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            var cur = normalizePhone(phoneInput.value);
            var init = normalizePhone(initialPhone);
            if (cur !== init && verifiedInput.value !== '1') {
                e.preventDefault();
                alert('Please verify your new mobile number with the SMS OTP code before saving.');
                if (otpInput) otpInput.focus();
                return false;
            }
        }, true);
    }
})();
</script>