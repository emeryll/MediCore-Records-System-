<?php
declare(strict_types=1);

$currentUid = (string) ($_SESSION['user_id'] ?? '');
$pdo        = db();
$stmt       = $pdo->prepare('SELECT * FROM accounts WHERE UPPER(user_id) = :uid LIMIT 1');
$stmt->execute([':uid' => strtoupper($currentUid)]);
$acc = $stmt->fetch(PDO::FETCH_ASSOC);
if ($acc) {
    $acc['full_name'] = db_format_account_name($acc, $pdo);
    $acc['address']   = implode(', ', array_filter([
        $acc['street'] ?? '',
        $acc['barangay'] ?? '',
        $acc['city'] ?? '',
        $acc['province'] ?? '',
        $acc['country'] ?? '',
        $acc['postal_code'] ?? '',
    ])) ?: '—';
}

$ptRec = null;
if ($role === 'Patient') {
    $activePtId = trim((string) ($_SESSION['active_patient_id'] ?? ''));
    if ($activePtId !== '') {
        $stmtPt = $pdo->prepare('SELECT * FROM patients WHERE UPPER(patient_id) = :pid OR UPPER(mrn) = :pid LIMIT 1');
        $stmtPt->execute([':pid' => strtoupper($activePtId)]);
        $ptRec = $stmtPt->fetch(PDO::FETCH_ASSOC);
        if ($ptRec) {
            $ptRec['full_name'] = trim(($ptRec['first_name'] ?? '') . (!empty($ptRec['middle_name']) ? ' ' . $ptRec['middle_name'] : '') . ' ' . ($ptRec['last_name'] ?? ''));
            $ptRec['address']   = implode(', ', array_filter([
                $ptRec['street'] ?? '',
                $ptRec['barangay'] ?? '',
                $ptRec['city'] ?? '',
                $ptRec['province'] ?? '',
                $ptRec['country'] ?? '',
                $ptRec['postal_code'] ?? '',
            ])) ?: '—';
        }
    }
    if (!$ptRec) {
        $idInfo = resolve_patient_identity($state, current_user_name('Patient'), $currentUid);
        $ptRec  = $idInfo['record'];
    }
}

$fullName  = (string) ($role === 'Patient' ? ($ptRec['full_name'] ?? $ptRec[0] ?? $acc['full_name'] ?? current_user_name($role)) : ($acc['full_name'] ?? current_user_name($role)));
$displayId = $role === 'Patient' ? (patient_mrn_display($ptRec, $currentUid) ?: $currentUid) : $currentUid;
$email     = trim((string) ($role === 'Patient' ? ($ptRec['email'] ?? $acc['email'] ?? '') : ($acc['email'] ?? '')));
if (str_ends_with($email, '@medicore.local') || str_ends_with($email, '@patient.westmetro.local')) {
    $email = '';
}
$hasEmail  = ($email !== '');
$phone     = (string) ($role === 'Patient' ? ($ptRec['phone'] ?? $ptRec[2] ?? '') : ($acc['phone'] ?? ''));
$dob       = format_birthdate((string) ($role === 'Patient' ? ($ptRec['birthdate'] ?? $ptRec[1] ?? '') : ($acc['birthdate'] ?? '')));
$age       = patient_age_label($dob, false, '—');
$sex       = (string) ($role === 'Patient' ? ($ptRec['sex'] ?? $ptRec[5] ?? 'Not specified') : ($acc['sex'] ?? 'Not specified'));
$address   = (string) ($role === 'Patient' ? ($ptRec['address'] ?? $ptRec[7] ?? '—') : ($acc['address'] ?? '—'));
$dept      = (string) ($acc['department'] ?? '');
$spec      = (string) ($acc['specialty'] ?? '');
$license   = (string) ($acc['license_number'] ?? '');

if ($role === 'Patient') {
    $addrCountry  = (string) ($ptRec['country'] ?? 'Philippines');
    $addrProvince = (string) ($ptRec['province'] ?? 'Zamboanga del Sur');
    $addrCity     = (string) ($ptRec['city'] ?? 'Zamboanga City');
    $addrBarangay = (string) ($ptRec['barangay'] ?? '');
    $addrStreet   = (string) ($ptRec['street'] ?? '');
    $addrPostal   = (string) ($ptRec['postal_code'] ?? '7000');
} else {
    $addrCountry  = (string) ($acc['country'] ?? 'Philippines');
    $addrProvince = (string) ($acc['province'] ?? 'Zamboanga del Sur');
    $addrCity     = (string) ($acc['city'] ?? 'Zamboanga City');
    $addrBarangay = (string) ($acc['barangay'] ?? '');
    $addrStreet   = (string) ($acc['street'] ?? '');
    $addrPostal   = (string) ($acc['postal_code'] ?? '7000');
}

$updateFlow   = (string) ($_GET['flow'] ?? '');
$pendingPhone = (string) ($_SESSION['pending_profile_phone'] ?? '');
$pendingPhoneOtp = (string) ($_SESSION['pending_profile_phone_otp'] ?? '');
$pendingEmail = (string) ($_SESSION['pending_profile_email'] ?? '');
$pendingEmailOtp = (string) ($_SESSION['pending_profile_email_otp'] ?? '');
$pendingLinkPatient = $_SESSION['pending_link_patient'] ?? null;
$linkedRecords = function_exists('db_get_account_linked_patients') ? db_get_account_linked_patients($currentUid) : [];
?>
<?= page_title(
    'My Profile',
    'View your hospital registration details and manage your verified credentials, contact details, and security password.'
) ?>

<!-- Main Profile Summary Card -->
<div style="display:grid; grid-template-columns: 1fr; gap: 24px; width:100%;">
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
                    <span><?= $hasEmail ? 'Update Email' : 'Add Email' ?></span>
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
                    <button type="button" onclick="openProfileModal('modal-update-email')" style="background:none; border:none; color:var(--teal-700); font-size:12px; font-weight:600; cursor:pointer; text-decoration:underline;"><?= $hasEmail ? 'Update' : 'Add' ?></button>
                </div>
                <strong style="font-size:15px; color:var(--navy-900);"><?= e($hasEmail ? $email : 'Not set') ?></strong>
            </div>
        </div>

        <div class="panel" style="padding:14px 16px; background:#f8fafc; border-radius:8px; margin-top:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--gray-500);">Registered Residential Address</span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="openProfileModal('modal-edit-address')" style="display:inline-flex; align-items:center; gap:5px; padding:3px 10px; font-size:12px; height:28px;">
                    <?= icon('edit', 14) ?>
                    <span>Edit Address</span>
                </button>
            </div>
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

        <!-- My Patient Records Section (SCREEN 2) -->
        <div style="margin-top:20px; padding-top:20px; border-top:1px solid var(--gray-200);">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:14px;">
                <div>
                    <h3 style="margin:0; font-size:16px; color:var(--navy-900); font-weight:700;">My patient records</h3>
                    <p style="margin:2px 0 0; font-size:13px; color:var(--gray-500);">Connected patient charts and family records linked to this account</p>
                </div>
                <div>
                    <?php if (empty($linkedRecords) && $role !== 'Patient'): ?>
                        <button type="button" class="btn btn-primary" onclick="openProfileModal('modal-link-patient')" style="display:inline-flex; align-items:center; gap:6px;">
                            <?= icon('user', 16) ?>
                            <span>Link my patient record</span>
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary" onclick="openProfileModal('modal-link-patient')" style="display:inline-flex; align-items:center; gap:6px;">
                            <?= icon('user', 16) ?>
                            <span>Link another record</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($linkedRecords)): ?>
                <div style="padding:16px 20px; background:#f8fafc; border:1px dashed var(--gray-300); border-radius:8px; text-align:center;">
                    <p style="margin:0; color:var(--gray-600); font-size:14px;">No patient records linked</p>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach ($linkedRecords as $lr): ?>
                        <?php
                            $lrMrn = format_mrn($lr['mrn'] ?: $lr['patient_id']);
                            $lrRel = ucfirst((string) ($lr['relationship_label'] ?: 'Self'));
                            $lrFullName = (string) $lr['patient_name'];
                            $lrDob = format_birthdate((string) ($lr['birthdate'] ?? ''));
                            $lrAge = patient_age_label($lrDob, false, '—');
                        ?>
                        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; background:#f8fafc; border:1px solid var(--gray-200); border-radius:8px;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div style="width:38px; height:38px; border-radius:50%; background:var(--teal-100); color:var(--teal-800); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">
                                    <?= e(initials($lrFullName, 2)) ?>
                                </div>
                                <div>
                                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                        <strong style="color:var(--navy-900); font-size:15px;"><?= e($lrFullName) ?></strong>
                                        <span style="color:var(--gray-400);">·</span>
                                        <code style="font-size:13px; font-weight:700; color:var(--teal-800); background:#fff; padding:1px 6px; border-radius:4px; border:1px solid var(--teal-200);"><?= e($lrMrn) ?></code>
                                        <span style="color:var(--gray-400);">·</span>
                                        <span style="font-size:13px; font-weight:600; color:var(--navy-700);"><?= e($lrRel) ?></span>
                                    </div>
                                    <div style="font-size:12.5px; color:var(--gray-600); margin-top:3px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                        <span><strong>Birthdate:</strong> <?= e($lrDob ?: 'Not specified') ?><?php if ($lrAge !== '—'): ?> (Age <?= e($lrAge) ?>)<?php endif; ?></span>
                                        <span style="color:var(--gray-400);">·</span>
                                        <span>Registered Patient</span>
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <?php if (!empty($lr['is_primary'])): ?>
                                    <?= badge('Primary Profile', 'success') ?>
                                <?php endif; ?>
                                <?php if ($role !== 'Patient' || count($linkedRecords) > 1): ?>
                                    <form method="post" action="index.php" style="display:inline; margin:0;" onsubmit="return confirm('Are you sure you want to unlink <?= e(addslashes($lrFullName)) ?> (<?= e($lrMrn) ?>) from this account?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="unlink_patient_profile">
                                        <input type="hidden" name="patient_id" value="<?= e($lr['patient_id']) ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger, #dc2626); border-color:#fca5a5; background:#fff; font-size:12px; padding:3px 8px; cursor:pointer;">Unlink</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
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

<!-- 2. ADD / UPDATE EMAIL MODAL (STEP 1: Enter Email) -->
<div id="modal-update-email" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div class="modal-card" style="background:#fff; border-radius:12px; max-width:500px; width:100%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2); overflow:hidden;">
        <div class="panel-head" style="padding:20px 24px; border-bottom:1px solid var(--gray-200); display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; font-size:18px; color:var(--navy-900);"><?= $hasEmail ? 'Update Email Address' : 'Add Email Address' ?></h3>
                <p style="margin:4px 0 0; font-size:13px; color:var(--gray-500);"><?= $hasEmail ? 'Step 1: Enter your new email address to receive a verification code' : 'Step 1: Enter your email address to receive a verification code' ?></p>
            </div>
            <button type="button" onclick="closeProfileModal('modal-update-email')" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--gray-400); line-height:1;">&times;</button>
        </div>

        <form method="post" action="index.php" style="padding:24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="request_profile_email_otp">

            <?php if ($hasEmail): ?>
                <label class="field" style="margin-bottom:16px; display:block;">
                    <span class="field-label">Current Email Address</span>
                    <input type="text" readonly value="<?= e($email) ?>" style="background:#f1f5f9; color:var(--gray-600); cursor:not-allowed;">
                </label>
            <?php endif; ?>

            <label class="field" style="margin-bottom:16px; display:block;">
                <span class="field-label"><?= $hasEmail ? 'New Email Address' : 'Email Address' ?> <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                <span class="input-wrap">
                    <input type="email" name="new_email" placeholder="name@example.com" required autofocus>
                </span>
                <small class="field-hint">A 6-digit confirmation code will be sent to this email address to prove it's actually yours.</small>
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
                <h3 style="margin:0; font-size:18px; color:var(--navy-900);"><?= $hasEmail ? 'Verify New Email Address' : 'Verify Email Address' ?></h3>
            </div>
            <a href="<?= e(url(['page' => 'profile', 'cancel_pending_email' => 1])) ?>" style="font-size:22px; color:var(--gray-400); text-decoration:none; line-height:1;">&times;</a>
        </div>

        <form method="post" action="index.php" style="padding:24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="confirm_profile_email">

            <div style="background:#f0fdfa; border:1px solid #5eead4; color:#0f766e; padding:12px 16px; border-radius:8px; margin-bottom:18px; font-size:13.5px;">
                ✉️ A 6-digit verification code was sent to <strong><?= e($pendingEmail) ?></strong>. Enter it below to prove this email belongs to you.
            </div>

            <label class="field" style="margin-bottom:16px; display:block;">
                <span class="field-label">Enter 6-Digit Verification Code <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                <span class="input-wrap">
                    <input type="text" name="email_otp_code" placeholder="123456" maxlength="6" required value="<?= e($pendingEmailOtp) ?>" style="letter-spacing:6px; font-weight:700; font-size:20px; text-align:center;" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);" autofocus>
                </span>
                <small class="field-hint">Enter the 6-digit code sent to <?= e($pendingEmail) ?>.</small>
            </label>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <a class="btn btn-secondary" href="<?= e(url(['page' => 'profile', 'cancel_pending_email' => 1])) ?>"><span>Cancel</span></a>
                <button type="submit" class="btn btn-primary"><span><?= $hasEmail ? 'Verify &amp; Update Email' : 'Verify &amp; Add Email' ?></span></button>
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

<!-- 4. LINK PATIENT RECORD MODAL (SCREEN 3: Enter Details) -->
<div id="modal-link-patient" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div class="modal-card" style="background:#fff; border-radius:12px; max-width:500px; width:100%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2); overflow:hidden;">
        <div class="panel-head" style="padding:20px 24px; border-bottom:1px solid var(--gray-200); display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3 style="margin:0; font-size:18px; color:var(--navy-900);">Link Patient Record</h3>
                <p style="margin:4px 0 0; font-size:13px; color:var(--gray-500);">Enter the patient's hospital registration details to request an SMS OTP</p>
            </div>
            <button type="button" onclick="closeProfileModal('modal-link-patient')" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--gray-400); line-height:1;">&times;</button>
        </div>

        <form method="post" action="index.php" style="padding:24px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="request_link_patient_otp">

            <label class="field" style="margin-bottom:16px; display:block;">
                <span class="field-label">Enter your MRN <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                <span class="input-wrap">
                    <input type="text" name="mrn" placeholder="e.g. MRN-001001 or 1001" required autofocus>
                </span>
                <small class="field-hint">Medical Record Number issued at Front Desk registration.</small>
            </label>

            <label class="field" style="margin-bottom:16px; display:block;">
                <span class="field-label">Enter your birthdate <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                <span class="input-wrap">
                    <input type="date" name="birthdate" required>
                </span>
                <small class="field-hint">Patient's official date of birth on hospital file.</small>
            </label>

            <label class="field" style="margin-bottom:20px; display:block;">
                <span class="field-label">Relationship <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                <select name="relationship" required style="width:100%; height:40px; padding:0 12px; border:1px solid var(--gray-300); border-radius:6px; background:#fff; font-size:14px; color:var(--navy-900);">
                    <option value="Self">Self (My own patient record)</option>
                    <option value="Child">Child</option>
                    <option value="Parent">Parent</option>
                    <option value="Spouse">Spouse</option>
                    <option value="Sibling">Sibling</option>
                    <option value="Other">Other</option>
                </select>
                <small class="field-hint">Select your relationship to this patient record.</small>
            </label>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeProfileModal('modal-link-patient')"><span>Cancel</span></button>
                <button type="submit" class="btn btn-primary"><span>Continue</span></button>
            </div>
        </form>
    </div>
</div>

<!-- 5. VERIFY LINK PATIENT OTP MODAL (SCREEN 5 & 6) -->
<div id="modal-verify-link-otp" class="modal-backdrop" style="display:<?= ($updateFlow === 'verify_link_otp' && !empty($pendingLinkPatient)) ? 'flex' : 'none' ?>; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div class="modal-card" style="background:#fff; border-radius:12px; max-width:500px; width:100%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2); overflow:hidden; border:2px solid var(--teal-500);">
        <div class="panel-head" style="padding:20px 24px; border-bottom:1px solid var(--gray-200); display:flex; justify-content:space-between; align-items:center;">
            <div>
                <p class="eyebrow" style="color:var(--teal-700); font-weight:700; margin:0 0 4px;">Record Verification · Step 2</p>
                <h3 style="margin:0; font-size:18px; color:var(--navy-900);">Verify Patient OTP</h3>
                <p style="margin:4px 0 0; font-size:13px; color:var(--gray-600);">We sent a code to <?= e($pendingLinkPatient['masked_phone'] ?? '') ?></p>
            </div>
            <a href="<?= e(url(['page' => 'profile', 'cancel_pending_link_patient' => 1])) ?>" style="font-size:22px; color:var(--gray-400); text-decoration:none; line-height:1;">&times;</a>
        </div>

        <div style="padding:24px;">
            <?php if (!empty($pendingLinkPatient['otp_code'])): ?>
                <div style="background:#f0fdfa; border:1px solid #5eead4; color:#0f766e; padding:12px 16px; border-radius:8px; margin-bottom:18px; font-size:13.5px;">
                    📱 <strong>Simulated SMS to <?= e($pendingLinkPatient['masked_phone']) ?>:</strong> Your 6-digit verification code is <strong style="font-size:17px; letter-spacing:3px; background:#fff; padding:2px 6px; border-radius:4px; border:1px solid #99f6e4;"><?= e($pendingLinkPatient['otp_code']) ?></strong>
                </div>
            <?php endif; ?>

            <form method="post" action="index.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="confirm_link_patient_otp">

                <label class="field" style="margin-bottom:16px; display:block;">
                    <span class="field-label">Enter 6-Digit OTP Code <span class="req-star" style="color:#dc2626; font-weight:700;">*</span></span>
                    <span class="input-wrap">
                        <input type="text" name="otp_code" placeholder="123456" maxlength="6" required style="letter-spacing:6px; font-weight:700; font-size:20px; text-align:center;" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);" autofocus>
                    </span>
                    <small class="field-hint">The OTP expires in 10 minutes and works once.</small>
                </label>

                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:20px;">
                    <a class="btn btn-secondary" href="<?= e(url(['page' => 'profile', 'cancel_pending_link_patient' => 1])) ?>"><span>Cancel</span></a>
                    <button type="submit" class="btn btn-primary"><span>Verify</span></button>
                </div>
            </form>

            <div style="margin-top:16px; padding-top:14px; border-top:1px solid var(--gray-200); display:flex; justify-content:center;">
                <form method="post" action="index.php" style="margin:0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="resend_link_patient_otp">
                    <span style="font-size:13px; color:var(--gray-600); margin-right:8px;">Didn't receive the code?</span>
                    <button type="submit" class="btn btn-secondary" style="padding:4px 10px; font-size:12.5px;">Resend</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 6. EDIT RESIDENTIAL ADDRESS MODAL -->
<div id="modal-edit-address" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div class="modal-card" style="background:#fff; border-radius:12px; max-width:540px; width:100%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2); overflow:hidden; border:1px solid var(--gray-200);">
        <div class="panel-head" style="padding:18px 22px; border-bottom:1px solid var(--gray-200); display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
            <div>
                <h3 style="margin:0; font-size:17px; color:var(--navy-900); font-weight:700;">Edit Residential Address</h3>
                <p style="margin:3px 0 0; font-size:12.5px; color:var(--gray-600);">
                    <?= $role === 'Patient'
                        ? 'Update residential address for patient profile: ' . e($fullName) . ' (' . e($displayId) . ')'
                        : 'Update your official employee residential address' ?>
                </p>
            </div>
            <button type="button" onclick="closeProfileModal('modal-edit-address')" style="border:none; background:none; font-size:22px; color:var(--gray-400); cursor:pointer; line-height:1;">&times;</button>
        </div>

        <form method="post" action="index.php" style="padding:22px; margin:0; display:grid; gap:14px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_residential_address">

            <div class="form-grid">
                <label class="field">
                    <span class="field-label">Country <span style="color:#dc2626;">*</span></span>
                    <span class="input-wrap"><select name="country" id="prof-country-select" required></select><?= icon('down', 16) ?></span>
                </label>
                <label class="field">
                    <span class="field-label">Province / Region <span style="color:#dc2626;">*</span></span>
                    <span class="input-wrap"><select name="province" id="prof-province-select" required></select><?= icon('down', 16) ?></span>
                </label>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span class="field-label">City / Municipality <span style="color:#dc2626;">*</span></span>
                    <span class="input-wrap"><select name="city" id="prof-city-select" required></select><?= icon('down', 16) ?></span>
                </label>
                <label class="field">
                    <span class="field-label">Barangay / District <span style="color:#dc2626;">*</span></span>
                    <span class="input-wrap"><select name="barangay" id="prof-barangay-select" required></select><?= icon('down', 16) ?></span>
                </label>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span class="field-label">Street / Building / Unit (Put N/A if not applicable) <span style="color:#dc2626;">*</span></span>
                    <span class="input-wrap"><input type="text" name="street" id="prof-street-input" placeholder="Put N/A if not applicable" value="<?= e($addrStreet ?: 'N/A') ?>" required></span>
                </label>
                <label class="field">
                    <span class="field-label">Postal / ZIP Code</span>
                    <span class="input-wrap"><input type="text" name="postal_code" id="prof-postal-input" value="<?= e($addrPostal ?: '7000') ?>" readonly style="background:var(--gray-50); font-family:monospace; font-weight:600;"></span>
                </label>
            </div>

            <?php if ($role === 'Admin'): ?>
                <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:12px 14px; margin-top:4px;">
                    <label class="field" style="margin:0;">
                        <span class="field-label" style="color:#92400e; font-weight:700;">Account Password <span style="color:#dc2626;">* (Required for Admin confirmation)</span></span>
                        <span class="input-wrap">
                            <input type="password" name="admin_password" required placeholder="Re-enter your account password to confirm" autocomplete="current-password">
                        </span>
                        <small class="field-hint" style="color:#b45309;">Administrative security policy requires password verification to update address records.</small>
                    </label>
                </div>
            <?php endif; ?>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:12px; padding-top:14px; border-top:1px solid var(--gray-200);">
                <button type="button" class="btn btn-secondary" onclick="closeProfileModal('modal-edit-address')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                    <?= icon('shield', 16) ?>
                    <span>Save Address</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
if (window.PHAddressSelector) {
    window.PHAddressSelector.init({
        country: document.getElementById('prof-country-select'),
        province: document.getElementById('prof-province-select'),
        city: document.getElementById('prof-city-select'),
        barangay: document.getElementById('prof-barangay-select'),
        street: document.getElementById('prof-street-input'),
        postal: document.getElementById('prof-postal-input'),
        initialCountry: <?= json_encode($addrCountry ?: 'Philippines') ?>,
        initialProvince: <?= json_encode($addrProvince ?: 'Zamboanga del Sur') ?>,
        initialCity: <?= json_encode($addrCity ?: 'Zamboanga City') ?>,
        initialBarangay: <?= json_encode($addrBarangay) ?>,
        initialPostal: <?= json_encode($addrPostal ?: '7000') ?>
    });
}
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
