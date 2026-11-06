<?php
declare(strict_types=1);

$currentUid = (string) ($_SESSION['user_id'] ?? '');
$pdo        = db();
$stmt       = $pdo->prepare('SELECT * FROM accounts WHERE UPPER(user_id) = :uid LIMIT 1');
$stmt->execute([':uid' => strtoupper($currentUid)]);
$acc = $stmt->fetch(PDO::FETCH_ASSOC);

$ptRec = null;
if ($role === 'Patient') {
    $idInfo = resolve_patient_identity($state, current_user_name('Patient'), $currentUid);
    $ptRec  = $idInfo['record'];
}

$fullName  = (string) ($acc['full_name'] ?? current_user_name($role));
$displayId = $role === 'Patient' ? (patient_mrn_display($ptRec, $currentUid) ?: $currentUid) : $currentUid;
$email     = (string) ($acc['email'] ?? ($ptRec['email'] ?? ''));
if (str_ends_with($email, '@medicore.local')) {
    $email = '';
}
$phone     = (string) ($acc['phone'] ?? ($ptRec[2] ?? ''));
$dob       = format_birthdate((string) ($acc['birthdate'] ?? ($ptRec[1] ?? '')));
$age       = patient_age_label($dob, false, '—');
$sex       = (string) ($acc['sex'] ?? ($ptRec[5] ?? 'Not specified'));
$address   = (string) ($acc['address'] ?? ($ptRec[7] ?? '—'));
$dept      = (string) ($acc['department'] ?? '');
$spec      = (string) ($acc['specialty'] ?? '');
$license   = (string) ($acc['license_number'] ?? '');

$updateFlow   = (string) ($_GET['flow'] ?? '');
$pendingPhone = (string) ($_SESSION['pending_profile_phone'] ?? '');
$pendingPhoneOtp = (string) ($_SESSION['pending_profile_phone_otp'] ?? '');
$pendingEmail = (string) ($_SESSION['pending_profile_email'] ?? '');
$pendingEmailOtp = (string) ($_SESSION['pending_profile_email_otp'] ?? '');
?>
<?= page_title(
    'My Profile',
    'View your hospital registration details and manage your verified credentials, contact details, and security password.'
) ?>

<!-- Main Profile Summary Card -->
<div style="display:grid; grid-template-columns: 1fr; gap: 24px; max-width: 960px;">
    <section class="panel">
        <div class="panel-head" style="align-items: center; border-bottom: 1px solid var(--gray-200); padding-bottom: 16px;">
            <div>
                <h2>Account &amp; Registration Details</h2>
                <p>Official records on file for your West Metro Medical Center account</p>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <?= badge($acc['status'] ?? 'Active', ($acc['status'] ?? 'Active') === 'Active' ? 'success' : 'warning') ?>
                <?= badge($role, 'info') ?>
            </div>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; padding:18px; background:var(--teal-50); border:1px solid var(--teal-200); border-radius:12px; margin: 18px 0;">
            <div style="display:flex; align-items:center; gap:16px;">
                <div class="avatar" style="width:58px; height:58px; font-size:22px; font-weight:700; background:var(--teal-600); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                    <?= e(initials($fullName, 2)) ?>
                </div>
                <div>
                    <h3 style="margin:0; font-size:19px; color:var(--navy-900); font-weight:700;"><?= e($fullName) ?></h3>
                    <p style="margin:4px 0 0; font-size:13.5px; color:var(--gray-600);">
                        <?php if ($role === 'Patient'): ?>
                            Medical Record Number (MRN):
                            <code style="font-weight:700; color:var(--teal-900); font-size:14px; background:#fff; padding:2px 6px; border-radius:4px; border:1px solid var(--teal-300);"><?= e($displayId) ?></code>
                            · <strong>Patient Portal</strong>
                        <?php else: ?>
                            <strong><?= e($role) ?></strong> · West Metro Medical Center
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- 3 PHASE 4 BUTTONS -->
            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <button type="button" class="btn btn-primary" onclick="openProfileModal('modal-update-phone')" style="display:inline-flex; align-items:center; gap:6px;">
                    <?= icon('phone', 16) ?>
                    <span>Update Phone Number</span>
                </button>
                <button type="button" class="btn btn-primary" onclick="openProfileModal('modal-update-email')" style="display:inline-flex; align-items:center; gap:6px;">
                    <?= icon('mail', 16) ?>
                    <span>Update Email</span>
                </button>
                <button type="button" class="btn btn-secondary" onclick="openProfileModal('modal-change-password')" style="display:inline-flex; align-items:center; gap:6px;">
                    <?= icon('lock', 16) ?>
                    <span>Change Password</span>
                </button>
            </div>
        </div>

        <div class="form-grid" style="gap:16px;">
            <div class="panel" style="padding:14px 16px; background:#f8fafc; border-radius:8px;">
                <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--gray-500); display:block; margin-bottom:4px;">Birthdate &amp; Age</span>
                <strong style="font-size:15px; color:var(--navy-900);"><?= e($dob ?: '—') ?></strong>
                <?php if ($age !== '—'): ?><span style="font-size:13px; color:var(--gray-600);"> (<?= e($age) ?>)</span><?php endif; ?>
            </div>

            <div class="panel" style="padding:14px 16px; background:#f8fafc; border-radius:8px;">
                <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--gray-500); display:block; margin-bottom:4px;">Sex</span>
                <strong style="font-size:15px; color:var(--navy-900);"><?= e($sex ?: 'Not specified') ?></strong>
            </div>

            <div class="panel" style="padding:14px 16px; background:#f8fafc; border-radius:8px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                    <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--gray-500);">Verified Mobile Number</span>
                    <button type="button" onclick="openProfileModal('modal-update-phone')" style="background:none; border:none; color:var(--teal-700); font-size:12px; font-weight:600; cursor:pointer; text-decoration:underline;">Update</button>
                </div>
                <strong style="font-size:15px; color:var(--navy-900); font-family:monospace;"><?= e($phone ?: '—') ?></strong>
            </div>

            <div class="panel" style="padding:14px 16px; background:#f8fafc; border-radius:8px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                    <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--gray-500);">Email Address</span>
                    <button type="button" onclick="openProfileModal('modal-update-email')" style="background:none; border:none; color:var(--teal-700); font-size:12px; font-weight:600; cursor:pointer; text-decoration:underline;">Update</button>
                </div>
                <strong style="font-size:15px; color:var(--navy-900);"><?= e($email ?: 'Not set') ?></strong>
            </div>
        </div>

        <div class="panel" style="padding:14px 16px; background:#f8fafc; border-radius:8px; margin-top:16px;">
            <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--gray-500); display:block; margin-bottom:4px;">Registered Residential Address</span>
            <strong style="font-size:14.5px; color:var(--navy-900);"><?= e($address ?: 'Veterans Avenue Extension, Tetuan, Zamboanga City') ?></strong>
        </div>

        <?php if ($role === 'Doctor'): ?>
            <div class="panel" style="padding:14px 16px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; margin-top:16px;">
                <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:#15803d; display:block; margin-bottom:6px;">Physician Credentials</span>
                <p style="margin:0; font-size:14px; color:var(--navy-900);">
                    <strong>Department:</strong> <?= e($dept ?: 'Internal Medicine') ?> ·
                    <strong>Specialty:</strong> <?= e($spec ?: 'General Practice') ?> ·
                    <strong>PRC License No.:</strong> <code><?= e($license ?: 'PRC-0108421') ?></code>
                </p>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- ==============================================================
     PHASE 4 MODALS (HIDDEN BY DEFAULT, OPENS ONLY ON BUTTON CLICK)
     ============================================================== -->

<!-- 1. UPDATE PHONE NUMBER MODAL (STEP 1: Enter New Number) -->
<div id="modal-update-phone" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div class="modal-card" style="background:#fff; border-radius:12px; max-width:500px; width:100%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2); overflow:hidden;">
        <div class="panel-head" style="padding:20px 24px; border-bottom:1px solid var(--gray-200); display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; font-size:18px; color:var(--navy-900);">Update Phone Number</h3>
                <p style="margin:4px 0 0; font-size:13px; color:var(--gray-500);">Step 1: Enter your new mobile number to receive an SMS OTP</p>
            </div>
            <button type="button" onclick="closeProfileModal('modal-update-phone')" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--gray-400); line-height:1;">&times;</button>
        </div>

        <form method="post" action="index.php" style="padding:24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="request_profile_phone_otp">

            <label class="field" style="margin-bottom:16px; display:block;">
                <span class="field-label">Current Mobile Number</span>
                <input type="text" readonly value="<?= e($phone ?: 'None recorded') ?>" style="background:#f1f5f9; color:var(--gray-600); cursor:not-allowed;">
            </label>

            <label class="field" style="margin-bottom:16px; display:block;">
                <span class="field-label">New Mobile Number <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                <span class="input-wrap">
                    <input type="tel" name="new_phone" placeholder="09XXXXXXXXX" maxlength="11" required oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);" autofocus>
                </span>
                <small class="field-hint">Must be an 11-digit Philippine mobile number starting with 09.</small>
            </label>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeProfileModal('modal-update-phone')"><span>Cancel</span></button>
                <button type="submit" class="btn btn-primary"><span>Proceed &amp; Send OTP</span></button>
            </div>
        </form>
    </div>
</div>

<!-- 1.b PHONE OTP VERIFICATION MODAL (STEP 2: Enter 6-digit Code) -->
<div id="modal-verify-phone" class="modal-backdrop" style="display:<?= ($updateFlow === 'verify_phone' && $pendingPhone !== '') ? 'flex' : 'none' ?>; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div class="modal-card" style="background:#fff; border-radius:12px; max-width:500px; width:100%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2); overflow:hidden; border:2px solid var(--teal-500);">
        <div class="panel-head" style="padding:20px 24px; border-bottom:1px solid var(--gray-200); display:flex; justify-content:space-between; align-items:center;">
            <div>
                <p class="eyebrow" style="color:var(--teal-700); font-weight:700; margin:0 0 4px;">Step 2 of 2 · SMS Verification</p>
                <h3 style="margin:0; font-size:18px; color:var(--navy-900);">Verify Code for <?= e($pendingPhone) ?></h3>
            </div>
            <a href="<?= e(url(['page' => 'profile'])) ?>" style="font-size:22px; color:var(--gray-400); text-decoration:none; line-height:1;">&times;</a>
        </div>

        <form method="post" action="index.php" style="padding:24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="confirm_profile_phone">

            <?php if ($pendingPhoneOtp !== ''): ?>
                <div style="background:#f0fdfa; border:1px solid #5eead4; color:#0f766e; padding:12px 16px; border-radius:8px; margin-bottom:18px; font-size:13.5px;">
                    📱 <strong>Simulated SMS to <?= e($pendingPhone) ?>:</strong> Your 6-digit verification code is <strong style="font-size:17px; letter-spacing:3px; background:#fff; padding:2px 6px; border-radius:4px; border:1px solid #99f6e4;"><?= e($pendingPhoneOtp) ?></strong>
                </div>
            <?php endif; ?>

            <label class="field" style="margin-bottom:16px; display:block;">
                <span class="field-label">Enter 6-Digit OTP Code <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                <span class="input-wrap">
                    <input type="text" name="otp_code" placeholder="123456" maxlength="6" required value="<?= e($pendingPhoneOtp) ?>" style="letter-spacing:6px; font-weight:700; font-size:20px; text-align:center;" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);" autofocus>
                </span>
                <small class="field-hint">Check your mobile device for the one-time verification code.</small>
            </label>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <a class="btn btn-secondary" href="<?= e(url(['page' => 'profile'])) ?>"><span>Cancel</span></a>
                <button type="submit" class="btn btn-primary"><span>Verify &amp; Update Mobile</span></button>
            </div>
        </form>
    </div>
</div>

<!-- 2. UPDATE EMAIL MODAL (STEP 1: Enter New Email) -->
<div id="modal-update-email" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div class="modal-card" style="background:#fff; border-radius:12px; max-width:500px; width:100%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2); overflow:hidden;">
        <div class="panel-head" style="padding:20px 24px; border-bottom:1px solid var(--gray-200); display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; font-size:18px; color:var(--navy-900);">Update Email Address</h3>
                <p style="margin:4px 0 0; font-size:13px; color:var(--gray-500);">Step 1: Enter your new email address to receive a verification code</p>
            </div>
            <button type="button" onclick="closeProfileModal('modal-update-email')" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--gray-400); line-height:1;">&times;</button>
        </div>

        <form method="post" action="index.php" style="padding:24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="request_profile_email_otp">

            <label class="field" style="margin-bottom:16px; display:block;">
                <span class="field-label">Current Email Address</span>
                <input type="text" readonly value="<?= e($email ?: 'None recorded') ?>" style="background:#f1f5f9; color:var(--gray-600); cursor:not-allowed;">
            </label>

            <label class="field" style="margin-bottom:16px; display:block;">
                <span class="field-label">New Email Address <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                <span class="input-wrap">
                    <input type="email" name="new_email" placeholder="name@example.com" required autofocus>
                </span>
                <small class="field-hint">A 6-digit confirmation code will be sent to this email address.</small>
            </label>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeProfileModal('modal-update-email')"><span>Cancel</span></button>
                <button type="submit" class="btn btn-primary"><span>Proceed &amp; Send Code</span></button>
            </div>
        </form>
    </div>
</div>

<!-- 2.b EMAIL OTP VERIFICATION MODAL (STEP 2: Enter 6-digit Code) -->
<div id="modal-verify-email" class="modal-backdrop" style="display:<?= ($updateFlow === 'verify_email' && $pendingEmail !== '') ? 'flex' : 'none' ?>; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div class="modal-card" style="background:#fff; border-radius:12px; max-width:500px; width:100%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2); overflow:hidden; border:2px solid var(--teal-500);">
        <div class="panel-head" style="padding:20px 24px; border-bottom:1px solid var(--gray-200); display:flex; justify-content:space-between; align-items:center;">
            <div>
                <p class="eyebrow" style="color:var(--teal-700); font-weight:700; margin:0 0 4px;">Step 2 of 2 · Email Verification</p>
                <h3 style="margin:0; font-size:18px; color:var(--navy-900);">Verify Code for <?= e($pendingEmail) ?></h3>
            </div>
            <a href="<?= e(url(['page' => 'profile'])) ?>" style="font-size:22px; color:var(--gray-400); text-decoration:none; line-height:1;">&times;</a>
        </div>

        <form method="post" action="index.php" style="padding:24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="confirm_profile_email">

            <?php if ($pendingEmailOtp !== ''): ?>
                <div style="background:#eff6ff; border:1px solid #93c5fd; color:#1d4ed8; padding:12px 16px; border-radius:8px; margin-bottom:18px; font-size:13.5px;">
                    ✉️ <strong>Simulated Email to <?= e($pendingEmail) ?>:</strong> Your verification code is <strong style="font-size:17px; letter-spacing:3px; background:#fff; padding:2px 6px; border-radius:4px; border:1px solid #bfdbfe;"><?= e($pendingEmailOtp) ?></strong>
                </div>
            <?php endif; ?>

            <label class="field" style="margin-bottom:16px; display:block;">
                <span class="field-label">Enter 6-Digit Verification Code <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                <span class="input-wrap">
                    <input type="text" name="email_otp_code" placeholder="123456" maxlength="6" required value="<?= e($pendingEmailOtp) ?>" style="letter-spacing:6px; font-weight:700; font-size:20px; text-align:center;" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);" autofocus>
                </span>
                <small class="field-hint">Enter the code sent to your new email inbox.</small>
            </label>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <a class="btn btn-secondary" href="<?= e(url(['page' => 'profile'])) ?>"><span>Cancel</span></a>
                <button type="submit" class="btn btn-primary"><span>Verify &amp; Update Email</span></button>
            </div>
        </form>
    </div>
</div>

<!-- 3. CHANGE PASSWORD MODAL -->
<div id="modal-change-password" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div class="modal-card" style="background:#fff; border-radius:12px; max-width:500px; width:100%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2); overflow:hidden;">
        <div class="panel-head" style="padding:20px 24px; border-bottom:1px solid var(--gray-200); display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; font-size:18px; color:var(--navy-900);">Change Account Password</h3>
                <p style="margin:4px 0 0; font-size:13px; color:var(--gray-500);">Enter your current password and choose a new secure password</p>
            </div>
            <button type="button" onclick="closeProfileModal('modal-change-password')" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--gray-400); line-height:1;">&times;</button>
        </div>

        <form method="post" action="index.php" style="padding:24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_profile_password">

            <label class="field" style="margin-bottom:16px; display:block;">
                <span class="field-label">Current Password <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                <span class="input-wrap">
                    <input type="password" name="current_password" placeholder="Enter current password" required autofocus>
                </span>
            </label>

            <label class="field" style="margin-bottom:16px; display:block;">
                <span class="field-label">New Password <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                <span class="input-wrap">
                    <input type="password" name="new_password" placeholder="Minimum 8 characters" minlength="8" required>
                </span>
                <small class="field-hint">Must be at least 8 characters long.</small>
            </label>

            <label class="field" style="margin-bottom:16px; display:block;">
                <span class="field-label">Confirm New Password <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                <span class="input-wrap">
                    <input type="password" name="confirm_password" placeholder="Re-enter new password" minlength="8" required>
                </span>
            </label>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeProfileModal('modal-change-password')"><span>Cancel</span></button>
                <button type="submit" class="btn btn-primary"><span>Save New Password</span></button>
            </div>
        </form>
    </div>
</div>

<script>
function openProfileModal(modalId) {
    var el = document.getElementById(modalId);
    if (el) {
        el.style.display = 'flex';
        var inp = el.querySelector('input:not([type="hidden"]):not([readonly])');
        if (inp) {
            setTimeout(function() { inp.focus(); }, 100);
        }
    }
}

function closeProfileModal(modalId) {
    var el = document.getElementById(modalId);
    if (el) {
        el.style.display = 'none';
    }
}

// Close on backdrop click
document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
    backdrop.addEventListener('click', function(e) {
        if (e.target === backdrop) {
            backdrop.style.display = 'none';
        }
    });
});
</script>
