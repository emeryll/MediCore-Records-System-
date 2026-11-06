<?php
declare(strict_types=1);

/** @var string $modal Modal name from ?modal=... */
$closeUrl = url_with([
    'modal'        => null,
    'room'         => null,
    'key'          => null,
    'email'        => null,
    'appt'         => null,
    'doc'          => null,
    'dept'         => null,
    'bed_id'       => null,
    'checkin_appt' => null,
    'book_after'   => null,
]);

$isEdit        = str_starts_with($modal, 'Edit');
$isUserDetail  = ($modal === 'User details');
$isUser        = (str_contains(strtolower($modal), 'user') || str_contains(strtolower($modal), 'account')) && !$isUserDetail;
$isQuickProv   = ($modal === 'Quick add provisional');
$isCompleteReg = ($modal === 'Complete registration');
$isPatient     = in_array($modal, ['Register patient', 'Edit patient'], true);
$isAppt        = ($modal === 'Book appointment');
$isApptDetail  = ($modal === 'Appointment details');
$isAdmit       = ($modal === 'Admit patient');
$isDischarge   = ($modal === 'Discharge patient');
$isBedMaint    = ($modal === 'Bed maintenance');
$dischargeKey  = (string) ($_GET['patient'] ?? '');
$isRole        = in_array($modal, ['Create role', 'Manage role'], true);
$isRoom        = in_array($modal, ['Add room', 'Edit room'], true);
$isDept        = in_array($modal, ['Add department', 'Edit department'], true);
$isMed         = in_array($modal, ['Add medication', 'Edit medication'], true);

$deptRow = ['', '', '', ''];
if ($modal === 'Edit department') {
    foreach ($state['crud']['departments'] as $dp) {
        if ($dp[0] === (string) ($_GET['key'] ?? '')) {
            $deptRow = $dp;
        }
    }
}

$userRow = ['', '', 'Staff', 'Active', ''];
if ($modal === 'Edit user' || $isUserDetail) {
    $targetEmail = (string) ($_GET['email'] ?? '');
    $targetUid   = (string) ($_GET['user_id'] ?? '');
    foreach ($state['users'] as $us) {
        if (($targetEmail !== '' && strcasecmp((string) $us[1], $targetEmail) === 0)
            || ($targetUid !== '' && strcasecmp((string) ($us[7] ?? ''), $targetUid) === 0)) {
            $userRow = $us;
            break;
        }
    }
}

$patientRow = ['', '', '', '', '', '', 'Not specified', ''];
if ($modal === 'Edit patient' || $isCompleteReg) {
    $targetPt = (string) ($_GET['patient'] ?? '');
    foreach ($state['patients'] as $pt) {
        if (strcasecmp((string) $pt[0], $targetPt) === 0) {
            $patientRow = $pt;
            break;
        }
    }
}

$apptIdx = isset($_GET['appt']) ? (int) $_GET['appt'] : -1;
$apptRow = $state['appointments'][$apptIdx] ?? ($state['appointments'][0] ?? ['09:00', '—', 'Consultation', 'Scheduled', '—', '—', '—', date('M d, Y')]);

// Build list of vacant beds from $state['beds'] for Admit modal
$vacantBeds = [];
foreach ($state['beds'] ?? [] as $b) {
    if (!empty($b['active']) && ($b['status'] ?? '') === 'Vacant') {
        $vacantBeds[$b['bed_id']] = $b['bed_id'] . ' · ' . $b['ward'] . ' (' . $b['room_type'] . ' · ' . format_money((float) $b['rate']) . '/day)';
    }
}

$doctorNameOptions = array_column(doctors_data(), 0) ?: ['Dr.enz', 'Dr. Rivera', 'Dr. Chen'];

$subtitle = match (true) {
    $isQuickProv   => 'Bare-bones entry for phone-in booking (no MRN issued until check-in).',
    $isCompleteReg => 'Verify patient in person, confirm phone via SMS OTP, and issue permanent MRN.',
    $isPatient     => $modal === 'Edit patient' ? 'Update verified patient profile and contact details.' : 'Full walk-in registration — verifies phone via OTP and issues a permanent MRN.',
    $isUserDetail  => 'Complete employee account credentials, contact, and profile details.',
    $isUser        => 'Create or update an employee account. A Set-Password link will be sent to the user.',
    $isAdmit       => 'Assign a Registered patient (with MRN) to a vacant hospital bed.',
    $isDischarge   => 'Record discharge summary, final diagnosis, and verify Cashier billing clearance.',
    $isBedMaint    => 'Set bed maintenance status and document reason.',
    $isApptDetail  => 'Review visit details and update appointment status.',
    default        => 'Complete the form below.',
};
?>
<div class="modal-backdrop">
    <a class="modal-dismiss" href="<?= e($closeUrl) ?>" aria-label="Close dialog"></a>
    <form id="modal-main-form" class="modal <?= ($isPatient || $isCompleteReg || $isUser || $isApptDetail || $isDischarge) ? 'modal-large' : '' ?>" method="post" action="index.php"<?= $isDischarge ? ' enctype="multipart/form-data"' : '' ?>>
        <?= csrf_field() ?>
        <input type="hidden" name="return" value="<?= e($closeUrl) ?>">

        <?php if ($isQuickProv): ?>
            <input type="hidden" name="action" value="quick_add_provisional">
            <input type="hidden" name="book_after" value="<?= e((string) ($_GET['book_after'] ?? '')) ?>">
        <?php elseif ($isCompleteReg): ?>
            <input type="hidden" name="action" value="complete_registration">
            <input type="hidden" name="orig_patient" value="<?= e($patientRow[0] ?? '') ?>">
            <input type="hidden" name="checkin_appt" value="<?= e((string) ($_GET['checkin_appt'] ?? '')) ?>">
        <?php elseif ($isPatient): ?>
            <input type="hidden" name="action" value="register_patient">
            <input type="hidden" name="orig" value="<?= e($modal === 'Edit patient' ? ($patientRow[0] ?? '') : '') ?>">
            <input type="hidden" name="book_after" value="<?= e((string) ($_GET['book_after'] ?? '')) ?>">
        <?php elseif ($isAdmit): ?>
            <input type="hidden" name="action" value="admit_patient">
        <?php elseif ($isDischarge): ?>
            <input type="hidden" name="action" value="discharge">
            <input type="hidden" name="key" value="<?= e($dischargeKey) ?>">
        <?php elseif ($isBedMaint): ?>
            <input type="hidden" name="action" value="set_bed_maintenance">
            <input type="hidden" name="bed_id" value="<?= e((string) ($_GET['bed_id'] ?? '')) ?>">
            <input type="hidden" name="target_status" value="Maintenance">
        <?php elseif ($isAppt): ?>
            <input type="hidden" name="action" value="book_appointment">
        <?php elseif ($isApptDetail): ?>
            <input type="hidden" name="action" value="update_appointment">
            <input type="hidden" name="idx" value="<?= e((string) $apptIdx) ?>">
        <?php elseif ($isUser): ?>
            <input type="hidden" name="action" value="save_user">
            <input type="hidden" name="orig" value="<?= e($modal === 'Edit user' ? ($userRow[1] ?? '') : '') ?>">
        <?php elseif ($isDept): ?>
            <input type="hidden" name="action" value="save_crud">
            <input type="hidden" name="type" value="departments">
            <input type="hidden" name="orig" value="<?= e($modal === 'Edit department' ? ($deptRow[0] ?? '') : '') ?>">
        <?php endif; ?>

        <div class="modal-head">
            <div>
                <h2><?= e($modal) ?></h2>
                <p><?= e($subtitle) ?></p>
            </div>
            <a class="icon-btn" href="<?= e($closeUrl) ?>" aria-label="Close"><?= icon('close') ?></a>
        </div>

        <div class="modal-body">
            <?php if ($isQuickProv): ?>
                <!-- SECTION 2.1: Quick Add (Provisional) for Phone-in Bookings -->
                <div style="background:var(--amber-bg); border:1px solid var(--amber-border); border-radius:10px; padding:12px 16px; margin-bottom:16px; font-size:12.5px; color:var(--amber-text);">
                    <strong>📞 Phone-In Provisional Record:</strong> Collects basic details so you can hold an appointment slot. <strong>No MRN is issued yet</strong> — full registration and OTP phone verification will be completed when the patient arrives at the front desk.
                </div>
                <div class="form-grid three-col">
                    <?= field('First name *', 'first_name', 'First name', 'text', '', ['required' => true]) ?>
                    <?= field('Middle name', 'middle_name', 'Optional', 'text', '') ?>
                    <?= field('Last name *', 'last_name', 'Last name', 'text', '', ['required' => true]) ?>
                </div>
                <div class="form-grid three-col">
                    <label class="field">
                        <span class="field-label">Birthdate *</span>
                        <span class="input-wrap">
                            <input type="date" name="birthdate" max="<?= date('Y-m-d') ?>" required>
                        </span>
                    </label>
                    <label class="field">
                        <span class="field-label">Sex *</span>
                        <span class="input-wrap">
                            <select name="sex" required>
                                <option value="" disabled selected hidden>Select sex</option>
                                <option value="Female">Female</option>
                                <option value="Male">Male</option>
                            </select>
                            <?= icon('down', 16) ?>
                        </span>
                    </label>
                    <label class="field">
                        <span class="field-label">Contact Mobile Number *</span>
                        <span class="input-wrap">
                            <input type="tel" name="phone" placeholder="09XXXXXXXXX" maxlength="11" required oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,11);">
                        </span>
                        <small class="field-hint">No OTP required during phone-in call.</small>
                    </label>
                </div>

            <?php elseif ($isCompleteReg || $isPatient): ?>
                <?php
                $pParts = explode(' ', (string) ($patientRow[0] ?? ''), 2);
                $pFirst = $pParts[0] ?? '';
                $pLast  = $pParts[1] ?? '';
                $pBirth = (($patientRow[1] ?? '') !== '' && ($patientRow[1] ?? '') !== '—' && strtotime((string) $patientRow[1]) !== false)
                    ? date('Y-m-d', strtotime((string) $patientRow[1]))
                    : '';
                $nextMrn = ($modal === 'Edit patient' && !empty($patientRow[9]))
                    ? (string) $patientRow[9]
                    : db_next_mrn();

                $ptCountry       = (string) ($patientRow['country'] ?? 'Philippines');
                $ptProvince      = (string) ($patientRow['province'] ?? 'Zamboanga del Sur');
                $ptCity          = (string) ($patientRow['city'] ?? 'Zamboanga City');
                $ptBarangay      = (string) ($patientRow['barangay'] ?? '');
                $ptStreet        = (string) ($patientRow['street'] ?? '');
                $ptPostal        = (string) ($patientRow['postal_code'] ?? '7000');
                $ptPhoneVerified = ($modal === 'Edit patient' && !is_provisional_patient($patientRow));
                $ptPhone         = preg_replace('/[^0-9]/', '', (string) ($patientRow[2] ?? ''));
                ?>
                <input type="hidden" name="mrn" id="pt-field-mrn" value="<?= e($nextMrn) ?>">

                <!-- STEP PROGRESS BAR -->
                <div class="patient-reg-stepper" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; background:#f8fafc; border:1px solid var(--gray-200); border-radius:10px; padding:10px 18px;">
                    <div id="step-tab-1" class="step-tab active" style="display:flex; align-items:center; gap:8px;">
                        <span id="step-num-1" style="width:26px; height:26px; border-radius:50%; background:var(--teal-600); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px;">1</span>
                        <span id="step-label-1" style="font-size:13px; font-weight:700; color:var(--teal-800);">1. Personal Details</span>
                    </div>
                    <div id="step-div-1" style="flex:1; height:2px; background:var(--gray-200); margin:0 12px;"></div>
                    <div id="step-tab-2" class="step-tab" style="display:flex; align-items:center; gap:8px; opacity:0.55;">
                        <span id="step-num-2" style="width:26px; height:26px; border-radius:50%; background:var(--gray-300); color:var(--gray-700); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px;">2</span>
                        <span id="step-label-2" style="font-size:13px; font-weight:600; color:var(--gray-700);">2. Mobile Verification</span>
                    </div>
                    <div id="step-div-2" style="flex:1; height:2px; background:var(--gray-200); margin:0 12px;"></div>
                    <div id="step-tab-3" class="step-tab" style="display:flex; align-items:center; gap:8px; opacity:0.55;">
                        <span id="step-num-3" style="width:26px; height:26px; border-radius:50%; background:var(--gray-300); color:var(--gray-700); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px;">3</span>
                        <span id="step-label-3" style="font-size:13px; font-weight:600; color:var(--gray-700);">3. Residential Address</span>
                    </div>
                </div>

                <!-- ==============================================
                     STEP 1: PERSONAL DETAILS
                     ============================================== -->
                <div id="reg-step-pane-1" class="reg-step-pane">
                    <div style="background:var(--teal-50); border:1px solid var(--teal-200); border-radius:10px; padding:12px 16px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                        <div>
                            <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--teal-800); display:block;">
                                <?= $isCompleteReg ? 'Upgrading Provisional Record to Full Registration' : 'Permanent Medical Record Number (MRN)' ?>
                            </span>
                            <strong style="font-size:15px; color:var(--navy-900);">MRN to be assigned: <code style="font-size:15px; color:var(--teal-700);"><?= e($nextMrn) ?></code></strong>
                        </div>
                        <?= badge('Registered MRN', 'success') ?>
                    </div>

                    <div class="form-grid three-col">
                        <?= field('First name *', 'first_name', 'First name', 'text', $pFirst, ['required' => true, 'id' => 'pt-first-name']) ?>
                        <?= field('Middle name', 'middle_name', 'Optional', 'text', '', ['id' => 'pt-middle-name']) ?>
                        <?= field('Last name *', 'last_name', 'Last name', 'text', $pLast, ['required' => true, 'id' => 'pt-last-name']) ?>
                    </div>

                    <div class="form-grid">
                        <label class="field">
                            <span class="field-label">Birthdate <span style="color:#dc2626;">*</span></span>
                            <span class="input-wrap">
                                <input type="date" name="birthdate" id="pt-birthdate" max="<?= date('Y-m-d') ?>" value="<?= e($pBirth) ?>" required>
                            </span>
                        </label>
                        <label class="field">
                            <span class="field-label">Sex <span style="color:#dc2626;">*</span></span>
                            <span class="input-wrap">
                                <select name="sex" id="pt-sex" required>
                                    <option value="" disabled <?= empty($patientRow[5]) ? 'selected' : '' ?> hidden>Select sex</option>
                                    <option value="Female" <?= (($patientRow[5] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                                    <option value="Male" <?= (($patientRow[5] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                                </select>
                                <?= icon('down', 16) ?>
                            </span>
                        </label>
                    </div>
                    <input type="hidden" name="blood_type" value="<?= e($patientRow[6] ?? 'Not specified') ?>">
                    <input type="hidden" name="hin" value="<?= e(($patientRow[3] ?? '') !== '—' ? (string) ($patientRow[3] ?? '') : '—') ?>">

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding-top:14px; border-top:1px solid var(--gray-200);">
                        <a href="<?= e($closeUrl) ?>" class="btn btn-secondary">Close</a>
                        <button type="button" id="btn-to-step-2" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                            <span>Proceed to Mobile Verification</span>
                            <?= icon('arrow', 16) ?>
                        </button>
                    </div>
                </div>

                <!-- ==============================================
                     STEP 2: MOBILE NUMBER & OTP VERIFICATION
                     ============================================== -->
                <div id="reg-step-pane-2" class="reg-step-pane" style="display:none;">
                    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:12px 16px; margin-bottom:16px;">
                        <strong style="color:#1e40af; font-size:13.5px; display:block; margin-bottom:2px;">Step 2: Mobile Number Verification</strong>
                        <p style="margin:0; font-size:12.5px; color:#1e3a8a;">
                            Enter the patient's Philippine mobile number. A 6-digit OTP code will be sent to confirm ownership before issuing the hospital MRN. Email is not required here; patients may optionally add an email in their portal profile later.
                        </p>
                    </div>

                    <div class="form-grid" style="align-items:flex-start;">
                        <label class="field" style="flex:1;">
                            <span class="field-label">Mobile Number <span style="color:#dc2626;">*</span></span>
                            <div style="display:flex; gap:8px;">
                                <span class="input-wrap" style="flex:1;">
                                    <input type="tel" name="phone" id="pt-phone-input" placeholder="09XXXXXXXXX" value="<?= e($ptPhone) ?>" maxlength="11" required oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,11);">
                                </span>
                                <button type="button" id="btn-pt-open-otp" class="btn btn-primary" style="height:42px; padding:0 16px; white-space:nowrap; display:inline-flex; align-items:center; gap:6px;">
                                    <?= icon('card', 16) ?>
                                    <span id="btn-pt-open-otp-text"><?= $ptPhoneVerified ? 'Re-verify via OTP' : 'Send OTP Code' ?></span>
                                </button>
                            </div>
                            <small class="field-hint">Enter an 11-digit mobile number starting with 09 (e.g. 09171234567).</small>
                        </label>
                    </div>

                    <!-- Verified Status Card -->
                    <div id="pt-verified-indicator" style="<?= $ptPhoneVerified ? 'display:block;' : 'display:none;' ?> margin-top:14px; padding:14px 16px; background:#f0fdf4; border:1px solid #86efac; border-radius:8px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="color:#16a34a; font-size:22px; font-weight:700;">✓</span>
                            <div>
                                <strong style="color:#15803d; font-size:13.5px; display:block;">Mobile Number Successfully Verified</strong>
                                <span style="font-size:12.5px; color:#166534;" id="pt-verified-phone-label">Verified number: <strong><?= e($ptPhone) ?></strong> (SMS OTP confirmed)</span>
                            </div>
                        </div>
                    </div>

                    <div id="pt-unverified-indicator" style="<?= $ptPhoneVerified ? 'display:none;' : 'display:block;' ?> margin-top:14px; padding:12px 16px; background:var(--gray-50); border:1px solid var(--gray-200); border-radius:8px;">
                        <span style="font-size:12.5px; color:var(--gray-600);">
                            Click <strong>Send OTP Code</strong> to open the verification modal and enter the 6-digit code.
                        </span>
                    </div>

                    <input type="hidden" name="phone_verified" id="pt-phone-verified" value="<?= $ptPhoneVerified ? '1' : '0' ?>">

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; padding-top:14px; border-top:1px solid var(--gray-200);">
                        <button type="button" id="btn-back-to-step-1" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px;">
                            <?= icon('arrow', 16) ?>
                            <span>Back: Personal Details</span>
                        </button>
                        <button type="button" id="btn-to-step-3" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                            <span>Proceed to Residential Address</span>
                            <?= icon('arrow', 16) ?>
                        </button>
                    </div>
                </div>

                <!-- ==============================================
                     STEP 3: COMPLETE RESIDENTIAL ADDRESS
                     ============================================== -->
                <div id="reg-step-pane-3" class="reg-step-pane" style="display:none;">
                    <div style="background:var(--teal-50); border:1px solid var(--teal-200); border-radius:10px; padding:12px 16px; margin-bottom:16px;">
                        <strong style="color:var(--teal-900); font-size:13.5px; display:block; margin-bottom:2px;">Step 3: Complete Residential Address</strong>
                        <p style="margin:0; font-size:12.5px; color:var(--teal-800);">
                            Provide the patient's verified Philippine residential address.
                        </p>
                    </div>

                    <div class="form-grid">
                        <label class="field">
                            <span class="field-label">Country <span style="color:#dc2626;">*</span></span>
                            <span class="input-wrap"><select name="country" id="pt-country-select" required></select><?= icon('down', 16) ?></span>
                        </label>
                        <label class="field">
                            <span class="field-label">Province / Region <span style="color:#dc2626;">*</span></span>
                            <span class="input-wrap"><select name="province" id="pt-province-select" required></select><?= icon('down', 16) ?></span>
                        </label>
                    </div>
                    <div class="form-grid">
                        <label class="field">
                            <span class="field-label">City / Municipality <span style="color:#dc2626;">*</span></span>
                            <span class="input-wrap"><select name="city" id="pt-city-select" required></select><?= icon('down', 16) ?></span>
                        </label>
                        <label class="field">
                            <span class="field-label">Barangay / District <span style="color:#dc2626;">*</span></span>
                            <span class="input-wrap"><select name="barangay" id="pt-barangay-select" required></select><?= icon('down', 16) ?></span>
                        </label>
                    </div>
                    <div class="form-grid">
                        <label class="field">
                            <span class="field-label">Street / Building / Unit (put N/A if not applicable) <span style="color:#dc2626;">*</span></span>
                            <span class="input-wrap"><input type="text" name="street" id="pt-street-input" placeholder="put N/A if not applicable" value="<?= e($ptStreet ?: 'N/A') ?>" required></span>
                        </label>
                        <label class="field">
                            <span class="field-label">Postal / ZIP Code</span>
                            <span class="input-wrap"><input type="text" name="postal_code" id="pt-postal-input" value="<?= e($ptPostal) ?>" readonly style="background:var(--gray-50); font-family:monospace; font-weight:600;"></span>
                        </label>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; padding-top:14px; border-top:1px solid var(--gray-200);">
                        <button type="button" id="btn-back-to-step-2" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px;">
                            <?= icon('arrow', 16) ?>
                            <span>Back: Mobile Verification</span>
                        </button>
                        <button type="button" id="btn-open-confirm-modal" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; font-weight:700;">
                            <?= icon('shield', 16) ?>
                            <span><?= e($isCompleteReg ? 'Complete Registration & Issue MRN' : 'Register Patient') ?></span>
                        </button>
                    </div>
                </div>

                <!-- ==============================================
                     MODAL 1: OTP VERIFICATION MODAL OVERLAY
                     ============================================== -->
                <div id="pt-otp-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); z-index:10000; align-items:center; justify-content:center; backdrop-filter:blur(4px); padding:16px;">
                    <div style="background:#fff; border-radius:14px; width:100%; max-width:440px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); overflow:hidden; border:1px solid var(--gray-200);">
                        <div style="padding:16px 20px; border-bottom:1px solid var(--gray-200); display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:34px; height:34px; border-radius:8px; background:var(--teal-50); color:var(--teal-700); display:flex; align-items:center; justify-content:center;">
                                    <?= icon('card', 18) ?>
                                </div>
                                <div>
                                    <h3 style="margin:0; font-size:15px; color:var(--navy-900); font-weight:700;">Mobile Number Verification</h3>
                                    <p style="margin:2px 0 0; font-size:12px; color:var(--gray-600);">Enter 6-digit SMS OTP</p>
                                </div>
                            </div>
                            <button type="button" id="btn-close-otp-modal" class="icon-btn" aria-label="Close" style="background:none; border:none; cursor:pointer; color:var(--gray-500);"><?= icon('close', 18) ?></button>
                        </div>

                        <div style="padding:20px;">
                            <p style="margin:0 0 16px; font-size:13px; color:var(--gray-700); text-align:center;">
                                We sent a 6-digit SMS verification code to<br>
                                <strong id="pt-otp-target-phone" style="font-size:16px; color:var(--navy-900); letter-spacing:0.5px;">09XXXXXXXXX</strong>
                            </p>

                            <div id="pt-sms-sim-box" style="display:none; margin-bottom:16px; padding:10px 14px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; font-size:12.5px; color:#1e40af;">
                                <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                                    <span id="pt-sms-sim-text"></span>
                                    <button type="button" id="btn-pt-sms-autofill" class="btn btn-sm btn-primary" style="font-size:11px; padding:3px 8px; white-space:nowrap;">Auto-fill &amp; Verify</button>
                                </div>
                            </div>

                            <div style="margin-bottom:14px;">
                                <input type="text" id="pt-otp-code-input" placeholder="000000" maxlength="6" style="display:block; width:100%; text-align:center; font-size:26px; letter-spacing:8px; font-weight:700; padding:10px 12px; border:2px solid var(--teal-600); border-radius:10px; color:var(--navy-900); font-family:monospace;" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,6);">
                            </div>

                            <div id="pt-otp-err-hint" style="display:none; color:#dc2626; font-size:12.5px; text-align:center; margin-bottom:12px; font-weight:600;"></div>

                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <button type="button" id="btn-pt-verify-otp" class="btn btn-primary" style="width:100%; height:42px; font-weight:700; font-size:14px;">Verify Code</button>
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
                                    <button type="button" id="btn-pt-resend-otp" class="btn btn-sm btn-secondary" style="font-size:12px;">Resend Code</button>
                                    <button type="button" id="btn-pt-cancel-otp" class="btn btn-sm" style="font-size:12px; color:var(--gray-600); background:none; border:none; cursor:pointer;">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==============================================
                     MODAL 2: "ARE YOU SURE ABOUT THIS ACTION?"
                     ============================================== -->
                <div id="pt-confirm-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); z-index:10000; align-items:center; justify-content:center; backdrop-filter:blur(4px); padding:16px;">
                    <div style="background:#fff; border-radius:14px; width:100%; max-width:520px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); overflow:hidden; border:1px solid var(--gray-200);">
                        <div style="padding:16px 20px; border-bottom:1px solid var(--gray-200); display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:36px; height:36px; border-radius:50%; background:#fef3c7; color:#d97706; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:700;">
                                    ?
                                </div>
                                <div>
                                    <h3 style="margin:0; font-size:16px; color:var(--navy-900); font-weight:700;">Are you sure about this action?</h3>
                                    <p style="margin:2px 0 0; font-size:12px; color:var(--gray-600);">Please review the patient details before finalizing registration.</p>
                                </div>
                            </div>
                            <button type="button" id="btn-close-confirm-modal" class="icon-btn" aria-label="Close" style="background:none; border:none; cursor:pointer; color:var(--gray-500);"><?= icon('close', 18) ?></button>
                        </div>

                        <div style="padding:20px;">
                            <p style="margin:0 0 14px; font-size:13.5px; color:var(--navy-900); line-height:1.5;">
                                You are about to register this patient into the official hospital records and assign a permanent Medical Record Number (MRN):
                            </p>

                            <div style="background:var(--teal-50); border:1px solid var(--teal-200); border-radius:10px; padding:14px 16px; margin-bottom:16px; font-size:13px;">
                                <div style="display:grid; grid-template-columns: 125px 1fr; gap:8px 12px; line-height:1.4;">
                                    <span style="color:var(--gray-600); font-weight:600;">Patient Name:</span>
                                    <strong style="color:var(--navy-900);" id="cfm-patient-name">—</strong>

                                    <span style="color:var(--gray-600); font-weight:600;">Birthdate &amp; Sex:</span>
                                    <span style="color:var(--navy-900);" id="cfm-patient-dob-sex">—</span>

                                    <span style="color:var(--gray-600); font-weight:600;">Verified Mobile:</span>
                                    <span style="color:var(--teal-700); font-weight:700;" id="cfm-patient-phone">—</span>

                                    <span style="color:var(--gray-600); font-weight:600;">Assigned MRN:</span>
                                    <code style="color:var(--teal-800); font-weight:700; font-size:13px;" id="cfm-patient-mrn"><?= e($nextMrn) ?></code>

                                    <span style="color:var(--gray-600); font-weight:600;">Residential Address:</span>
                                    <span style="color:var(--navy-900);" id="cfm-patient-address">—</span>
                                </div>
                            </div>

                            <p style="margin:0 0 18px; font-size:12px; color:var(--gray-600);">
                                Once confirmed, this permanent record will be stored in the hospital database and an activation confirmation SMS will be dispatched.
                            </p>

                            <div style="display:flex; justify-content:flex-end; gap:10px;">
                                <button type="button" id="btn-cancel-confirm" class="btn btn-secondary">Cancel / Review</button>
                                <button type="button" id="btn-submit-registration" class="btn btn-primary" style="font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                                    <?= icon('shield', 16) ?>
                                    <span>Yes, Confirm Registration</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                (function () {
                    var pane1 = document.getElementById('reg-step-pane-1');
                    var pane2 = document.getElementById('reg-step-pane-2');
                    var pane3 = document.getElementById('reg-step-pane-3');

                    var tab1 = document.getElementById('step-tab-1');
                    var tab2 = document.getElementById('step-tab-2');
                    var tab3 = document.getElementById('step-tab-3');

                    var num1 = document.getElementById('step-num-1');
                    var num2 = document.getElementById('step-num-2');
                    var num3 = document.getElementById('step-num-3');

                    var label1 = document.getElementById('step-label-1');
                    var label2 = document.getElementById('step-label-2');
                    var label3 = document.getElementById('step-label-3');

                    var btnToStep2 = document.getElementById('btn-to-step-2');
                    var btnBackToStep1 = document.getElementById('btn-back-to-step-1');
                    var btnToStep3 = document.getElementById('btn-to-step-3');
                    var btnBackToStep2 = document.getElementById('btn-back-to-step-2');
                    var btnOpenConfirmModal = document.getElementById('btn-open-confirm-modal');

                    var phoneInput = document.getElementById('pt-phone-input');
                    var phoneVerified = document.getElementById('pt-phone-verified');
                    var verifiedIndicator = document.getElementById('pt-verified-indicator');
                    var unverifiedIndicator = document.getElementById('pt-unverified-indicator');
                    var verifiedPhoneLabel = document.getElementById('pt-verified-phone-label');
                    var btnOpenOtp = document.getElementById('btn-pt-open-otp');
                    var btnOpenOtpText = document.getElementById('btn-pt-open-otp-text');

                    var otpModal = document.getElementById('pt-otp-modal');
                    var otpTargetPhone = document.getElementById('pt-otp-target-phone');
                    var otpCodeInput = document.getElementById('pt-otp-code-input');
                    var otpErrHint = document.getElementById('pt-otp-err-hint');
                    var btnVerifyOtp = document.getElementById('btn-pt-verify-otp');
                    var btnResendOtp = document.getElementById('btn-pt-resend-otp');
                    var btnCancelOtp = document.getElementById('btn-pt-cancel-otp');
                    var btnCloseOtpModal = document.getElementById('btn-close-otp-modal');
                    var smsBox = document.getElementById('pt-sms-sim-box');
                    var smsText = document.getElementById('pt-sms-sim-text');
                    var btnAutofill = document.getElementById('btn-pt-sms-autofill');
                    var lastOtp = '';

                    var confirmModal = document.getElementById('pt-confirm-modal');
                    var btnCloseConfirmModal = document.getElementById('btn-close-confirm-modal');
                    var btnCancelConfirm = document.getElementById('btn-cancel-confirm');
                    var btnSubmitReg = document.getElementById('btn-submit-registration');

                    function setTabState(tab, num, label, state) {
                        if (!tab || !num || !label) return;
                        if (state === 'active') {
                            tab.style.opacity = '1';
                            num.style.background = 'var(--teal-600)';
                            num.style.color = '#fff';
                            label.style.color = 'var(--teal-800)';
                            label.style.fontWeight = '700';
                        } else if (state === 'completed') {
                            tab.style.opacity = '1';
                            num.style.background = '#16a34a';
                            num.style.color = '#fff';
                            num.textContent = '✓';
                            label.style.color = '#15803d';
                            label.style.fontWeight = '600';
                        } else {
                            tab.style.opacity = '0.55';
                            num.style.background = 'var(--gray-300)';
                            num.style.color = 'var(--gray-700)';
                            label.style.color = 'var(--gray-700)';
                            label.style.fontWeight = '600';
                        }
                    }

                    function goToStep(step) {
                        if (!pane1 || !pane2 || !pane3) return;
                        pane1.style.display = (step === 1) ? 'block' : 'none';
                        pane2.style.display = (step === 2) ? 'block' : 'none';
                        pane3.style.display = (step === 3) ? 'block' : 'none';

                        if (step === 1) {
                            num1.textContent = '1';
                            setTabState(tab1, num1, label1, 'active');
                            setTabState(tab2, num2, label2, 'inactive');
                            setTabState(tab3, num3, label3, 'inactive');
                        } else if (step === 2) {
                            setTabState(tab1, num1, label1, 'completed');
                            num2.textContent = '2';
                            setTabState(tab2, num2, label2, 'active');
                            setTabState(tab3, num3, label3, 'inactive');
                        } else if (step === 3) {
                            setTabState(tab1, num1, label1, 'completed');
                            setTabState(tab2, num2, label2, 'completed');
                            num3.textContent = '3';
                            setTabState(tab3, num3, label3, 'active');
                        }
                    }

                    if (btnToStep2) {
                        btnToStep2.addEventListener('click', function () {
                            var fn = document.getElementById('pt-first-name') || document.querySelector('[name="first_name"]');
                            var ln = document.getElementById('pt-last-name') || document.querySelector('[name="last_name"]');
                            var bd = document.getElementById('pt-birthdate') || document.querySelector('[name="birthdate"]');
                            var sx = document.getElementById('pt-sex') || document.querySelector('[name="sex"]');

                            if (!fn || !fn.value.trim()) {
                                alert('Please enter the patient First name.');
                                if (fn) fn.focus();
                                return;
                            }
                            if (!ln || !ln.value.trim()) {
                                alert('Please enter the patient Last name.');
                                if (ln) ln.focus();
                                return;
                            }
                            if (!bd || !bd.value.trim()) {
                                alert('Please select the patient Birthdate.');
                                if (bd) bd.focus();
                                return;
                            }
                            if (!sx || !sx.value.trim()) {
                                alert('Please select the patient Sex.');
                                if (sx) sx.focus();
                                return;
                            }
                            goToStep(2);
                        });
                    }

                    if (btnBackToStep1) {
                        btnBackToStep1.addEventListener('click', function () {
                            goToStep(1);
                        });
                    }

                    function sendOtpRequest() {
                        var p = (phoneInput ? phoneInput.value.trim() : '');
                        if (!p || p.length < 11 || !p.startsWith('09')) {
                            alert('Please enter a valid 11-digit mobile number starting with 09 (e.g. 09XXXXXXXXX).');
                            if (phoneInput) phoneInput.focus();
                            return;
                        }

                        otpTargetPhone.textContent = p;
                        otpCodeInput.value = '';
                        otpErrHint.style.display = 'none';
                        otpErrHint.textContent = '';
                        smsBox.style.display = 'none';
                        otpModal.style.display = 'flex';

                        var fd = new FormData();
                        fd.append('action', 'ajax_send_phone_otp');
                        fd.append('phone', p);
                        fd.append('patient_id', <?= json_encode($nextMrn) ?>);
                        fd.append('purpose', 'verification');

                        fetch('index.php', { method: 'POST', body: fd })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                if (data.ok) {
                                    lastOtp = data.code || '';
                                    smsText.textContent = data.simulated_message || ('💬 Simulated OTP Code: ' + lastOtp);
                                    smsBox.style.display = 'block';
                                    otpCodeInput.value = lastOtp;
                                    otpCodeInput.focus();
                                } else {
                                    otpErrHint.textContent = data.message || 'Failed to send OTP.';
                                    otpErrHint.style.display = 'block';
                                }
                            })
                            .catch(function () {
                                otpErrHint.textContent = 'Network error while dispatching OTP.';
                                otpErrHint.style.display = 'block';
                            });
                    }

                    if (btnOpenOtp) {
                        btnOpenOtp.addEventListener('click', sendOtpRequest);
                    }
                    if (btnResendOtp) {
                        btnResendOtp.addEventListener('click', sendOtpRequest);
                    }

                    function closeOtpModal() {
                        if (otpModal) otpModal.style.display = 'none';
                    }

                    if (btnCancelOtp) btnCancelOtp.addEventListener('click', closeOtpModal);
                    if (btnCloseOtpModal) btnCloseOtpModal.addEventListener('click', closeOtpModal);

                    if (btnAutofill) {
                        btnAutofill.addEventListener('click', function () {
                            if (lastOtp && otpCodeInput) {
                                otpCodeInput.value = lastOtp;
                                if (btnVerifyOtp) btnVerifyOtp.click();
                            }
                        });
                    }

                    if (btnVerifyOtp) {
                        btnVerifyOtp.addEventListener('click', function () {
                            var code = (otpCodeInput ? otpCodeInput.value.trim() : '');
                            var p = (phoneInput ? phoneInput.value.trim() : '');
                            if (!code || code.length !== 6) {
                                otpErrHint.textContent = 'Please enter the complete 6-digit code.';
                                otpErrHint.style.display = 'block';
                                return;
                            }

                            var fd = new FormData();
                            fd.append('action', 'ajax_verify_phone_otp');
                            fd.append('phone', p);
                            fd.append('code', code);
                            fd.append('purpose', 'verification');

                            fetch('index.php', { method: 'POST', body: fd })
                                .then(function (r) { return r.json(); })
                                .then(function (data) {
                                    if (data.ok) {
                                        phoneVerified.value = '1';
                                        if (verifiedIndicator) verifiedIndicator.style.display = 'block';
                                        if (unverifiedIndicator) unverifiedIndicator.style.display = 'none';
                                        if (verifiedPhoneLabel) verifiedPhoneLabel.innerHTML = 'Verified number: <strong>' + p + '</strong> (SMS OTP confirmed)';
                                        if (btnOpenOtpText) btnOpenOtpText.textContent = 'Re-verify via OTP';
                                        closeOtpModal();
                                    } else {
                                        otpErrHint.textContent = data.message || 'Invalid or expired OTP code.';
                                        otpErrHint.style.display = 'block';
                                    }
                                })
                                .catch(function () {
                                    otpErrHint.textContent = 'Network error while verifying OTP.';
                                    otpErrHint.style.display = 'block';
                                });
                        });
                    }

                    if (btnToStep3) {
                        btnToStep3.addEventListener('click', function () {
                            var p = (phoneInput ? phoneInput.value.trim() : '');
                            if (!p || p.length < 11 || !p.startsWith('09')) {
                                alert('Please enter an 11-digit mobile number starting with 09.');
                                if (phoneInput) phoneInput.focus();
                                return;
                            }
                            if (phoneVerified.value !== '1') {
                                alert('Please verify the patient mobile number via SMS OTP first.');
                                sendOtpRequest();
                                return;
                            }
                            goToStep(3);
                        });
                    }

                    if (btnBackToStep2) {
                        btnBackToStep2.addEventListener('click', function () {
                            goToStep(2);
                        });
                    }

                    // Initialize PH Address Selector
                    if (window.PHAddressSelector) {
                        window.PHAddressSelector.init({
                            country: document.getElementById('pt-country-select'),
                            province: document.getElementById('pt-province-select'),
                            city: document.getElementById('pt-city-select'),
                            barangay: document.getElementById('pt-barangay-select'),
                            street: document.getElementById('pt-street-input'),
                            postal: document.getElementById('pt-postal-input'),
                            initialCountry: <?= json_encode($ptCountry ?: 'Philippines') ?>,
                            initialProvince: <?= json_encode($ptProvince ?: 'Zamboanga del Sur') ?>,
                            initialCity: <?= json_encode($ptCity ?: 'Zamboanga City') ?>,
                            initialBarangay: <?= json_encode($ptBarangay) ?>,
                            initialPostal: <?= json_encode($ptPostal ?: '7000') ?>
                        });
                    }

                    // Step 3 -> Open Confirmation Modal
                    if (btnOpenConfirmModal) {
                        btnOpenConfirmModal.addEventListener('click', function () {
                            var street = document.getElementById('pt-street-input');
                            var bg = document.getElementById('pt-barangay-select');
                            var ct = document.getElementById('pt-city-select');
                            var pr = document.getElementById('pt-province-select');
                            var co = document.getElementById('pt-country-select');
                            var pc = document.getElementById('pt-postal-input');

                            if (!street || !street.value.trim()) {
                                alert('Please enter street or building details (or put N/A).');
                                if (street) street.focus();
                                return;
                            }
                            if (!bg || !bg.value.trim()) {
                                alert('Please select Barangay.');
                                if (bg) bg.focus();
                                return;
                            }
                            if (!ct || !ct.value.trim()) {
                                alert('Please select City / Municipality.');
                                if (ct) ct.focus();
                                return;
                            }

                            var fn = (document.getElementById('pt-first-name') || document.querySelector('[name="first_name"]')).value.trim();
                            var mn = (document.getElementById('pt-middle-name') || document.querySelector('[name="middle_name"]')).value.trim();
                            var ln = (document.getElementById('pt-last-name') || document.querySelector('[name="last_name"]')).value.trim();
                            var bd = (document.getElementById('pt-birthdate') || document.querySelector('[name="birthdate"]')).value.trim();
                            var sx = (document.getElementById('pt-sex') || document.querySelector('[name="sex"]')).value.trim();
                            var ph = (phoneInput ? phoneInput.value.trim() : '');

                            var fullName = fn + (mn ? ' ' + mn : '') + ' ' + ln;
                            var fullAddr = [street.value.trim(), bg.value.trim(), ct.value.trim(), pr.value.trim(), co.value.trim(), pc ? pc.value.trim() : ''].filter(Boolean).join(', ');

                            var cfmName = document.getElementById('cfm-patient-name');
                            var cfmDobSex = document.getElementById('cfm-patient-dob-sex');
                            var cfmPhone = document.getElementById('cfm-patient-phone');
                            var cfmAddr = document.getElementById('cfm-patient-address');

                            if (cfmName) cfmName.textContent = fullName;
                            if (cfmDobSex) cfmDobSex.textContent = bd + ' · ' + sx;
                            if (cfmPhone) cfmPhone.textContent = ph + ' (✓ SMS Verified)';
                            if (cfmAddr) cfmAddr.textContent = fullAddr;

                            if (confirmModal) confirmModal.style.display = 'flex';
                        });
                    }

                    function closeConfirmModal() {
                        if (confirmModal) confirmModal.style.display = 'none';
                    }

                    if (btnCloseConfirmModal) btnCloseConfirmModal.addEventListener('click', closeConfirmModal);
                    if (btnCancelConfirm) btnCancelConfirm.addEventListener('click', closeConfirmModal);

                    if (btnSubmitReg) {
                        btnSubmitReg.addEventListener('click', function () {
                            btnSubmitReg.disabled = true;
                            btnSubmitReg.textContent = 'Registering patient...';
                            var form = document.getElementById('modal-main-form');
                            if (form) {
                                form.submit();
                            }
                        });
                    }

                    var mainForm = document.getElementById('modal-main-form');
                    if (mainForm) {
                        mainForm.addEventListener('keydown', function (e) {
                            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                                e.preventDefault();
                            }
                        });
                    }
                })();
                </script>

            <?php elseif ($isAdmit): ?>
                <?php
                $preBedId = trim((string) ($_GET['bed_id'] ?? ''));
                $regPatients = ['Select Registered patient'];
                $provNames   = [];
                foreach ($state['patients'] ?? [] as $pt) {
                    if (is_provisional_patient($pt)) {
                        $provNames[] = $pt[0];
                    } else {
                        $mrn = patient_mrn_display($pt);
                        $regPatients[$pt[0]] = $pt[0] . ' (' . $mrn . ' · DOB: ' . format_birthdate($pt[1] ?? '') . ')';
                    }
                }
                ?>
                <?php if ($provNames): ?>
                    <div style="background:var(--amber-bg); border:1px solid var(--amber-border); border-radius:8px; padding:10px 14px; margin-bottom:14px; font-size:12px; color:var(--amber-text);">
                        <strong>Note:</strong> Provisional patients (<?= e(implode(', ', $provNames)) ?>) cannot be admitted until their <strong>Complete registration</strong> is finished at the front desk.
                    </div>
                <?php endif; ?>

                <label class="field">
                    <span class="field-label">Registered Patient (with MRN) *</span>
                    <span class="input-wrap">
                        <select name="patient" required>
                            <?php foreach ($regPatients as $k => $lbl): ?>
                                <option value="<?= e(is_int($k) ? '' : $k) ?>"><?= e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= icon('down', 16) ?>
                    </span>
                </label>

                <label class="field">
                    <span class="field-label">Select Vacant Hospital Bed *</span>
                    <span class="input-wrap">
                        <select name="bed_id" required>
                            <?php foreach ($vacantBeds as $bId => $bLabel): ?>
                                <option value="<?= e($bId) ?>" <?= $preBedId === $bId ? 'selected' : '' ?>><?= e($bLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= icon('down', 16) ?>
                    </span>
                </label>

                <div class="form-grid">
                    <?= select_field('Attending Physician *', 'doctor', $doctorNameOptions) ?>
                    <?= field('Admission Date & Time', 'admitted_at', '', 'text', date('M d, Y · H:i')) ?>
                </div>
                <?= textarea_field('Admission Diagnosis & Reason *', 'reason', 'Enter admitting diagnosis, ward orders, and initial clinical notes...', 'Acute inpatient observation & workup') ?>

            <?php elseif ($isDischarge): ?>
                <?php
                $admMatch = null;
                foreach ($state['admissions'] ?? [] as $adm) {
                    if (strcasecmp((string) $adm[0], $dischargeKey) === 0) {
                        $admMatch = $adm;
                        break;
                    }
                }
                $aStat  = (string) ($admMatch[6] ?? 'Admitted');
                $bClear = (string) ($admMatch[7] ?? 'Pending');
                $dSumm  = (string) ($admMatch[9] ?? '');
                $fDiag  = (string) ($admMatch[5] ?? 'Resolved');
                ?>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
                    <div class="panel" style="padding:12px 14px; background:var(--gray-50);">
                        <span class="muted" style="font-size:11px; text-transform:uppercase;">Doctor Order Status</span>
                        <div style="margin-top:4px;"><?= badge($aStat, status_tone($aStat)) ?></div>
                    </div>
                    <div class="panel" style="padding:12px 14px; background:var(--gray-50);">
                        <span class="muted" style="font-size:11px; text-transform:uppercase;">Cashier Billing Clearance</span>
                        <div style="margin-top:4px;"><?= badge($bClear, status_tone($bClear)) ?></div>
                    </div>
                </div>

                <?php if (!in_array($bClear, ['Cleared', 'Exception'], true)): ?>
                    <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px 14px; margin-bottom:14px; font-size:12.5px; color:#991b1b;">
                        <strong>⚠️ Billing Clearance Pending:</strong> The Cashier has not yet marked this admission as <code>Cleared</code> or <code>Exception</code>. You may only override if billing clearance has been verified at the Cashier window.
                    </div>
                <?php endif; ?>

                <div class="form-grid">
                    <?= field('Discharge Date & Time *', 'discharge_datetime', '', 'text', date('M d, Y · H:i'), ['required' => true]) ?>
                    <?= select_field('Discharge Type *', 'discharge_type', ['Recovered / Routine', 'Home Against Medical Advice (HAMA)', 'Transferred to Another Facility', 'Absconded', 'Expired'], 'Recovered / Routine') ?>
                </div>
                <?= field('Final Diagnosis *', 'final_diagnosis', 'Final discharge diagnosis', 'text', $fDiag, ['required' => true]) ?>
                <?= textarea_field('Discharge Summary Notes', 'discharge_summary', 'Summary of stay and home instructions...', $dSumm ?: 'Discharged in stable condition.') ?>
                <?= file_field('Proof of Discharge / Clearance Slip (Optional Image)', 'proof') ?>

            <?php elseif ($isBedMaint): ?>
                <?php $targetBed = (string) ($_GET['bed_id'] ?? ''); ?>
                <div class="panel" style="padding:12px 14px; background:var(--amber-bg); border:1px solid var(--amber-border); margin-bottom:14px; font-size:13px;">
                    Setting Bed <strong><code><?= e($targetBed) ?></code></strong> to <strong>Under Maintenance</strong>.
                </div>
                <?= field('Maintenance / Cleaning Reason *', 'reason', 'e.g. Terminal sanitation / Aircon repair', 'text', 'Terminal sanitation & bed turnover', ['required' => true]) ?>

            <?php elseif ($isUserDetail): ?>
                <?php
                $uId      = $userRow[7] ?? '—';
                $uName    = $userRow[0] ?? '—';
                $uEmail   = $userRow[1] ?? '—';
                $uRole    = $userRow[2] ?? 'Staff';
                $uStatus  = $userRow[3] ?? 'Active';
                $uDept    = $userRow[5] ?? '—';
                $uSpec    = $userRow[6] ?? '—';
                $uLicense = $userRow[9] ?? '—';
                $uDob     = (string) ($userRow[10] ?? '—');
                $uPhone   = (string) ($userRow[11] ?? ($userRow['phone'] ?? '—'));
                ?>
                <div class="panel" style="padding:16px; background:var(--teal-50); border:1px solid var(--teal-200); margin-bottom:16px;">
                    <h3 style="margin:0 0 4px;">
                        <?= e($uName) ?>
                        <?php if ($uRole === 'Patient'): ?>
                            (<code>MRN: <?= e($uId) ?></code>)
                        <?php endif; ?>
                    </h3>
                    <p style="margin:0; font-size:13px; color:var(--gray-600);">Role: <strong><?= e($uRole) ?></strong> · Email: <?= e($uEmail) ?> · Phone: <?= e($uPhone) ?></p>
                    <?php if ($uRole === 'Doctor'): ?>
                        <p style="margin:6px 0 0; font-size:12.5px;">Department: <strong><?= e($uDept) ?></strong> · Specialty: <strong><?= e($uSpec) ?></strong> · PRC License: <code><?= e($uLicense) ?></code></p>
                    <?php endif; ?>
                </div>

            <?php elseif ($isUser): ?>
                <?php
                $nameParts   = explode(' ', (string) ($userRow[0] ?? ''), 2);
                $uFirst      = $nameParts[0] ?? '';
                $uLast       = $nameParts[1] ?? '';
                $currentRole = (string) ($_GET['role'] ?? ($userRow[2] ?? 'Doctor'));
                $uPhone      = (string) ($userRow[11] ?? ($userRow['phone'] ?? ''));
                $uDob        = (string) ($userRow[10] ?? '');
                $uSex        = (string) ($userRow['sex'] ?? ($userRow[12] ?? ''));
                ?>
                <div class="form-grid">
                    <?= select_field('Role *', 'role', ['Doctor', 'Staff', 'Cashier', 'Admin'], $currentRole, ['id' => 'user_role_select']) ?>
                    <?= field('Email Address *', 'email', 'employee@westmetro.ph', 'email', (string) ($userRow[1] ?? ''), ['required' => true]) ?>
                </div>
                <div class="form-grid three-col">
                    <?= field('First name *', 'first_name', 'First name', 'text', $uFirst, ['required' => true]) ?>
                    <?= field('Middle name', 'middle_name', 'Optional', 'text', '') ?>
                    <?= field('Last name *', 'last_name', 'Last name', 'text', $uLast, ['required' => true]) ?>
                </div>
                <div class="form-grid three-col">
                    <?= field('Mobile Number *', 'phone', '09XXXXXXXXX', 'tel', $uPhone, ['required' => true]) ?>
                    <label class="field">
                        <span class="field-label">Birthdate *</span>
                        <span class="input-wrap">
                            <input type="date" name="birthdate" value="<?= e($uDob) ?>" placeholder="YYYY-MM-DD" max="<?= date('Y-m-d') ?>" required>
                        </span>
                    </label>
                    <label class="field">
                        <span class="field-label">Sex *</span>
                        <span class="input-wrap">
                            <select name="sex" required>
                                <option value="" disabled <?= $uSex === '' ? 'selected' : '' ?> hidden>Select sex</option>
                                <option value="Female" <?= $uSex === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Male" <?= $uSex === 'Male' ? 'selected' : '' ?>>Male</option>
                            </select>
                            <?= icon('down', 16) ?>
                        </span>
                    </label>
                </div>
                <div id="doctor_fields_row" class="form-grid three-col" style="<?= $currentRole === 'Doctor' ? '' : 'display:none;' ?>">
                    <label class="field">
                        <span class="field-label">Department *</span>
                        <span class="input-wrap">
                            <select name="department" id="doc_dept_field" <?= $currentRole === 'Doctor' ? 'required' : '' ?>>
                                <option value="" disabled <?= empty($userRow[5]) ? 'selected' : '' ?> hidden>Select department</option>
                                <?php foreach (array_column($state['crud']['departments'] ?? [], 0) ?: ['Cardiology', 'Internal Medicine', 'Pediatrics & Neonatology', 'Obstetrics & Gynecology', 'General Surgery', 'Orthopedics', 'Emergency Medicine', 'Nephrology & Hemodialysis', 'Radiology & Diagnostic Imaging'] as $deptOpt): ?>
                                    <option value="<?= e($deptOpt) ?>" <?= ($userRow[5] ?? '') === $deptOpt ? 'selected' : '' ?>><?= e($deptOpt) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= icon('down', 16) ?>
                        </span>
                    </label>
                    <label class="field">
                        <span class="field-label">Specialty *</span>
                        <span class="input-wrap">
                            <input type="text" name="specialty" id="doc_spec_field" placeholder="e.g. Interventional Cardiology" value="<?= e($userRow[6] ?? '') ?>" <?= $currentRole === 'Doctor' ? 'required' : '' ?>>
                        </span>
                    </label>
                    <label class="field">
                        <span class="field-label">PRC License No. *</span>
                        <span class="input-wrap">
                            <input type="text" name="license" id="doc_license_field" placeholder="e.g. PRC-0108492" value="<?= e($userRow[9] ?? '') ?>" <?= $currentRole === 'Doctor' ? 'required' : '' ?>>
                        </span>
                    </label>
                </div>
                <script>
                (function() {
                    var roleSel = document.getElementById('user_role_select');
                    var docRow  = document.getElementById('doctor_fields_row');
                    var deptIn  = document.getElementById('doc_dept_field');
                    var specIn  = document.getElementById('doc_spec_field');
                    var licIn   = document.getElementById('doc_license_field');
                    if (!roleSel || !docRow) return;

                    function toggleDoctorFields() {
                        var isDoc = roleSel.value === 'Doctor';
                        if (isDoc) {
                            docRow.style.display = 'grid';
                            if (deptIn) deptIn.required = true;
                            if (specIn) specIn.required = true;
                            if (licIn)  licIn.required  = true;
                        } else {
                            docRow.style.display = 'none';
                            if (deptIn) { deptIn.required = false; deptIn.selectedIndex = 0; }
                            if (specIn) { specIn.required = false; specIn.value = ''; }
                            if (licIn)  { licIn.required  = false; licIn.value = ''; }
                        }
                    }
                    roleSel.addEventListener('change', toggleDoctorFields);
                    toggleDoctorFields();
                })();
                </script>
                <input type="hidden" name="phone_verified" value="1">
                <input type="hidden" name="active" value="1">

            <?php elseif ($isApptDetail): ?>
                <?php $currSt = $apptRow[3] ?? 'Scheduled'; ?>
                <div class="panel" style="padding:16px; margin-bottom:14px; background:var(--gray-50);">
                    <p style="margin:0 0 6px;"><strong>Patient:</strong> <?= e($apptRow[1] ?? '—') ?> · <strong>Status:</strong> <?= badge($currSt, status_tone($currSt)) ?></p>
                    <p style="margin:0;"><strong>Doctor:</strong> <?= e($apptRow[5] ?? '—') ?> (<?= e($apptRow[6] ?? 'Outpatient') ?>) · <strong>Scheduled:</strong> <?= e(($apptRow[7] ?? 'Today') . ' at ' . ($apptRow[0] ?? '09:00')) ?></p>
                </div>
                <div class="form-grid">
                    <?= field('Chief complaint / Notes', 'complaint', 'Reason for visit', 'text', $apptRow[4] ?? '') ?>
                    <?= select_field('Status', 'status', ['Scheduled', 'Checked-in', 'In consultation', 'Completed', 'No-show', 'Cancelled'], $currSt) ?>
                </div>

            <?php elseif ($isDept): ?>
                <div class="form-grid">
                    <?= field('Department name *', 'dept_name', 'e.g. Oncology', 'text', $deptRow[0], ['required' => true]) ?>
                    <?= select_field('Department head', 'dept_head', $doctorNameOptions, $deptRow[1] ?: ($doctorNameOptions[0] ?? '')) ?>
                </div>
                <?= field('Location *', 'dept_location', 'e.g. 3rd Floor, Main Wing', 'text', $deptRow[3] ?? ($deptRow[2] ?? ''), ['required' => true]) ?>
            <?php endif; ?>
        </div>

        <div class="modal-actions" <?= ($isCompleteReg || $isPatient) ? 'style="display:none;"' : '' ?>>
            <?= button('Close', ['variant' => 'secondary', 'href' => $closeUrl]) ?>
            <?php if (!$isUserDetail): ?>
                <button type="submit" class="btn btn-primary">
                    <span><?= e($isCompleteReg ? 'Complete Registration & Issue MRN' : ($isQuickProv ? 'Save Record' : 'Save & Confirm')) ?></span>
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>
