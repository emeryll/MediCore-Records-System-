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
$isCorrectPtAddr = in_array($modal, ['Correct patient address', 'Edit patient address'], true);
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
$userAcc = null;
if ($modal === 'Edit user' || $isUserDetail) {
    $targetEmail = (string) ($_GET['email'] ?? '');
    $targetUid   = (string) ($_GET['user_id'] ?? '');
    if ($targetUid !== '' && function_exists('db_find_account_by_id')) {
        $userAcc = db_find_account_by_id($targetUid);
    }
    if (!$userAcc && $targetEmail !== '' && function_exists('db_find_account_by_email')) {
        $userAcc = db_find_account_by_email($targetEmail);
    }
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

$targetPtName = (string) ($_GET['patient'] ?? '');
$targetPtId   = (string) ($_GET['user_id'] ?? ($_GET['patient_id'] ?? ''));
$targetPtMrn  = $targetPtId;
$cptRow       = null;
$isCoiBlocked = false;
$cptCountry   = 'Philippines';
$cptProvince  = 'Zamboanga del Sur';
$cptCity      = 'Zamboanga City';
$cptBarangay  = '';
$cptStreet    = '';
$cptPostal    = '7000';

if ($isCorrectPtAddr) {
    if ($targetPtName !== '') {
        $cptRow = find_patient_record($state, $targetPtName);
    }
    if (!$cptRow && $targetPtId !== '') {
        $cptRow = find_patient_record($state, $targetPtId);
    }
    if ($cptRow) {
        $targetPtName = (string) ($cptRow[0] ?? $targetPtName);
        $targetPtId   = (string) ($cptRow[8] ?? $targetPtId);
        $targetPtMrn  = (string) patient_mrn_display($cptRow, $targetPtId);
        $cptCountry   = (string) ($cptRow['country'] ?? 'Philippines');
        $cptProvince  = (string) ($cptRow['province'] ?? 'Zamboanga del Sur');
        $cptCity      = (string) ($cptRow['city'] ?? 'Zamboanga City');
        $cptBarangay  = (string) ($cptRow['barangay'] ?? '');
        $cptStreet    = (string) ($cptRow['street'] ?? '');
        $cptPostal    = (string) ($cptRow['postal_code'] ?? '7000');
    }
    $currUid = (string) ($_SESSION['user_id'] ?? '');
    if ($targetPtName !== '' && function_exists('db_is_patient_conflict_of_interest') && db_is_patient_conflict_of_interest($currUid, $targetPtName)) {
        $isCoiBlocked = true;
    } elseif ($targetPtId !== '' && function_exists('db_is_patient_conflict_of_interest') && db_is_patient_conflict_of_interest($currUid, $targetPtId)) {
        $isCoiBlocked = true;
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
    $isCorrectPtAddr => ($role === 'Admin' ? 'Administrative correction of patient residential address with required reason.' : 'Update verified patient residential address.'),
    $isPatient     => $modal === 'Edit patient' ? 'Update verified patient profile and contact details.' : 'Full walk-in registration — verifies phone via OTP and issues a permanent MRN.',
    $isUserDetail  => 'Complete employee account credentials, contact, and profile details.',
    $isUser        => $modal === 'Edit user' ? 'Update this account\'s details. A reason is required and is recorded in the audit log.' : 'Create or update an employee account. A Set-Password link will be sent to the user.',
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
        <?php elseif ($isUser && $modal === 'Edit user'): ?>
            <input type="hidden" name="action" value="edit_user_details">
            <input type="hidden" name="user_id" value="<?= e((string) ($userRow[7] ?? ($_GET['user_id'] ?? ''))) ?>">
        <?php elseif ($isCorrectPtAddr): ?>
            <input type="hidden" name="action" value="admin_correct_patient_address">
            <input type="hidden" name="patient_id" value="<?= e($targetPtId) ?>">
            <input type="hidden" name="patient_name" value="<?= e($targetPtName) ?>">
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
                    <div class="field" style="justify-content:flex-end;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:13px; color:#92400e; background:#fff8e6; border:1px solid #fde68a; border-radius:8px; padding:10px 12px; min-height:42px;">
                            <input type="checkbox" name="no_phone" id="qp-no-phone" value="1" style="width:16px; height:16px;">
                            <span>Patient has no phone number</span>
                        </label>
                    </div>
                </div>

                <div id="qp-phone-wrap" class="form-grid" style="margin-top:12px;">
                    <label class="field">
                        <span class="field-label">Patient Mobile Number <span style="color:#dc2626;">*</span></span>
                        <span class="input-wrap">
                            <input type="tel" name="phone" id="qp-phone" placeholder="09XXXXXXXXX" maxlength="11" required oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,11);">
                        </span>
                        <small class="field-hint">No OTP required during phone-in booking.</small>
                    </label>
                </div>

                <div id="qp-contact-section" style="display:none; margin-top:16px; padding:14px; background:#f8fafc; border:1px solid var(--gray-200); border-radius:8px;">
                    <strong style="font-size:13px; color:var(--navy-900); display:block; margin-bottom:4px;">
                        Contact Person (for Notifications &amp; Reminders)
                        <span id="qp-contact-req-badge" style="display:none; color:#dc2626; font-size:11.5px; font-weight:600; margin-left:6px;">(Required when patient has no phone)</span>
                    </strong>
                    <p style="margin:0 0 10px; font-size:11.5px; color:var(--gray-500);">
                        This number is only used for appointment reminders. It cannot be used to activate an online portal account for the patient.
                    </p>
                    <div class="form-grid three-col">
                        <label class="field">
                            <span class="field-label">First Name <span id="qp-cfirst-req" style="display:none; color:#dc2626;">*</span></span>
                            <span class="input-wrap">
                                <input type="text" name="contact_first_name" id="qp-contact-first-name" placeholder="First name">
                            </span>
                        </label>
                        <label class="field">
                            <span class="field-label">Middle Name <small style="color:var(--gray-500); font-weight:normal;">(optional)</small></span>
                            <span class="input-wrap">
                                <input type="text" name="contact_middle_name" id="qp-contact-middle-name" placeholder="Middle name">
                            </span>
                        </label>
                        <label class="field">
                            <span class="field-label">Last Name <span id="qp-clast-req" style="display:none; color:#dc2626;">*</span></span>
                            <span class="input-wrap">
                                <input type="text" name="contact_last_name" id="qp-contact-last-name" placeholder="Last name">
                            </span>
                        </label>
                    </div>
                    <div class="form-grid three-col" style="margin-top:12px;">
                        <label class="field">
                            <span class="field-label">Relationship <span id="qp-crel-req" style="display:none; color:#dc2626;">*</span></span>
                            <span class="input-wrap">
                                <select name="contact_relationship" id="qp-contact-relationship">
                                    <option value="" disabled selected hidden>Select relationship</option>
                                    <option value="Spouse">Spouse</option>
                                    <option value="Parent">Parent</option>
                                    <option value="Child">Child</option>
                                    <option value="Sibling">Sibling</option>
                                    <option value="Guardian">Guardian</option>
                                    <option value="Relative">Relative</option>
                                    <option value="Caregiver">Caregiver</option>
                                    <option value="Other">Other</option>
                                </select>
                                <?= icon('down', 16) ?>
                            </span>
                        </label>
                        <label class="field" id="qp-contact-rel-other-wrap" style="display:none;">
                            <span class="field-label">Specify Relationship <span id="qp-crel-other-req" style="display:none; color:#dc2626;">*</span></span>
                            <span class="input-wrap">
                                <input type="text" name="contact_relationship_other" id="qp-contact-relationship-other" placeholder="e.g. Neighbor, Cousin, Co-worker">
                            </span>
                        </label>
                        <label class="field">
                            <span class="field-label">Contact Phone <span id="qp-cphone-req" style="display:none; color:#dc2626;">*</span></span>
                            <span class="input-wrap">
                                <input type="tel" name="contact_phone" id="qp-contact-phone" placeholder="09XXXXXXXXX" maxlength="11" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,11);">
                            </span>
                        </label>
                    </div>
                </div>

                <script>
                (function() {
                    var chk = document.getElementById('qp-no-phone');
                    var phWrap = document.getElementById('qp-phone-wrap');
                    var phInput = document.getElementById('qp-phone');
                    var cSection = document.getElementById('qp-contact-section');
                    var cFirstReq = document.getElementById('qp-cfirst-req');
                    var cLastReq = document.getElementById('qp-clast-req');
                    var cRelReq = document.getElementById('qp-crel-req');
                    var cRelOtherReq = document.getElementById('qp-crel-other-req');
                    var cPhoneReq = document.getElementById('qp-cphone-req');
                    var badge = document.getElementById('qp-contact-req-badge');
                    var cFirst = document.getElementById('qp-contact-first-name');
                    var cMiddle = document.getElementById('qp-contact-middle-name');
                    var cLast = document.getElementById('qp-contact-last-name');
                    var cRel = document.getElementById('qp-contact-relationship');
                    var cRelOtherWrap = document.getElementById('qp-contact-rel-other-wrap');
                    var cRelOther = document.getElementById('qp-contact-relationship-other');
                    var cPhone = document.getElementById('qp-contact-phone');

                    function updateQpRelOther() {
                        if (!cRel || !cRelOtherWrap) return;
                        var isOther = (cRel.value === 'Other');
                        var isNoPhone = chk && chk.checked;
                        cRelOtherWrap.style.display = isOther ? 'block' : 'none';
                        if (cRelOther) {
                            cRelOther.required = isOther && isNoPhone;
                            if (!isOther) cRelOther.value = '';
                        }
                        if (cRelOtherReq) cRelOtherReq.style.display = (isOther && isNoPhone) ? 'inline' : 'none';
                    }

                    if (cRel) {
                        cRel.addEventListener('change', updateQpRelOther);
                    }

                    if (chk) {
                        chk.addEventListener('change', function() {
                            if (this.checked) {
                                if (phWrap) phWrap.style.display = 'none';
                                if (phInput) { phInput.required = false; phInput.value = ''; }
                                if (cSection) cSection.style.display = 'block';
                                if (cFirstReq) cFirstReq.style.display = 'inline';
                                if (cLastReq) cLastReq.style.display = 'inline';
                                if (cRelReq) cRelReq.style.display = 'inline';
                                if (cPhoneReq) cPhoneReq.style.display = 'inline';
                                if (badge) badge.style.display = 'inline';
                                if (cFirst) cFirst.required = true;
                                if (cLast) cLast.required = true;
                                if (cRel) cRel.required = true;
                                if (cPhone) cPhone.required = true;
                                updateQpRelOther();
                            } else {
                                if (phWrap) phWrap.style.display = 'block';
                                if (phInput) phInput.required = true;
                                if (cSection) cSection.style.display = 'none';
                                if (cFirstReq) cFirstReq.style.display = 'none';
                                if (cLastReq) cLastReq.style.display = 'none';
                                if (cRelReq) cRelReq.style.display = 'none';
                                if (cRelOtherReq) cRelOtherReq.style.display = 'none';
                                if (cPhoneReq) cPhoneReq.style.display = 'none';
                                if (badge) badge.style.display = 'none';
                                if (cFirst) { cFirst.required = false; cFirst.value = ''; }
                                if (cMiddle) cMiddle.value = '';
                                if (cLast) { cLast.required = false; cLast.value = ''; }
                                if (cRel) { cRel.required = false; cRel.value = ''; }
                                if (cRelOtherWrap) cRelOtherWrap.style.display = 'none';
                                if (cRelOther) { cRelOther.required = false; cRelOther.value = ''; }
                                if (cPhone) { cPhone.required = false; cPhone.value = ''; }
                            }
                        });
                    }
                })();
                </script>

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
                if (preg_match('/^(?:MRN|P)-?0*(\d+)$/i', $nextMrn, $m)) {
                    $nextMrn = 'MRN-' . str_pad($m[1], 4, '0', STR_PAD_LEFT);
                }

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
                        <strong style="color:#1e40af; font-size:13.5px; display:block; margin-bottom:2px;">Step 2: Mobile Number &amp; Contact Information</strong>
                        <p style="margin:0; font-size:12.5px; color:#1e3a8a;">
                            Enter the patient's Philippine mobile number for verification and portal access. If the patient does not own a mobile phone, check the option below to verify their identity in person and continue.
                        </p>
                    </div>

                    <!-- No Phone Number Checkbox -->
                    <div style="margin-bottom:14px; padding:12px 14px; background:#fff8e6; border:1px solid #fde68a; border-radius:8px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; color:#92400e; font-size:13px;">
                            <input type="checkbox" name="no_phone" id="pt-no-phone-checkbox" value="1" <?= !empty($patientRow['no_phone']) ? 'checked' : '' ?> style="width:16px; height:16px;">
                            <span>Patient has no phone number</span>
                        </label>
                        <small style="display:block; margin-top:4px; font-size:11.5px; color:#b45309;">
                            Check this box if the patient does not have a phone. Staff will verify identity in person via physical valid ID, and contact person details below become required.
                        </small>
                    </div>

                    <!-- In-person ID check indicator when No Phone is checked -->
                    <div id="pt-no-phone-inperson-notice" style="display:none; margin-bottom:16px; padding:12px 14px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; color:#166534; font-size:12.5px;">
                        <strong>✓ In-Person Identity Verification:</strong> Staff will verify the patient's identity in person via physical ID. The OTP step is skipped and the patient receives a permanent MRN. No portal account will be created.
                    </div>

                    <!-- Phone Input & OTP Group -->
                    <div id="pt-phone-field-group">
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
                    </div>

                    <!-- Contact Person Section -->
                    <div id="pt-contact-person-section" style="<?= !empty($patientRow['no_phone']) ? 'display:block;' : 'display:none;' ?> margin-top:18px; padding-top:16px; border-top:1px dashed var(--gray-300);">
                        <strong style="font-size:13px; color:var(--navy-900); display:block; margin-bottom:4px;">
                            Contact Person (for Notifications &amp; Reminders)
                            <span id="pt-contact-required-badge" style="display:none; color:#dc2626; font-size:11.5px; margin-left:6px; font-weight:600;">(Required when patient has no phone)</span>
                        </strong>
                        <p style="font-size:11.5px; color:var(--gray-500); margin:0 0 12px;">
                            This contact number is only for hospital notifications and appointment reminders. It must never be usable to log in or activate an account for the patient.
                        </p>
                        <div class="form-grid three-col">
                            <label class="field">
                                <span class="field-label">First Name <span id="pt-cfirst-req" style="display:none; color:#dc2626;">*</span></span>
                                <span class="input-wrap">
                                    <input type="text" name="contact_first_name" id="pt-contact-first-name" placeholder="First name" value="<?= e($patientRow['contact_first_name'] ?? '') ?>">
                                </span>
                            </label>
                            <label class="field">
                                <span class="field-label">Middle Name <small style="color:var(--gray-500); font-weight:normal;">(optional)</small></span>
                                <span class="input-wrap">
                                    <input type="text" name="contact_middle_name" id="pt-contact-middle-name" placeholder="Middle name" value="<?= e($patientRow['contact_middle_name'] ?? '') ?>">
                                </span>
                            </label>
                            <label class="field">
                                <span class="field-label">Last Name <span id="pt-clast-req" style="display:none; color:#dc2626;">*</span></span>
                                <span class="input-wrap">
                                    <input type="text" name="contact_last_name" id="pt-contact-last-name" placeholder="Last name" value="<?= e($patientRow['contact_last_name'] ?? '') ?>">
                                </span>
                            </label>
                        </div>
                        <div class="form-grid three-col" style="margin-top:12px;">
                            <?php
                            $cRelSaved = (string) ($patientRow['contact_relationship_base'] ?? ($patientRow['contact_relationship'] ?? ''));
                            $cRelIsOther = (strcasecmp($cRelSaved, 'Other') === 0 || str_starts_with($cRelSaved, 'Other ('));
                            $cRelOtherVal = (string) ($patientRow['contact_relationship_other'] ?? '');
                            if ($cRelOtherVal === '' && preg_match('/^Other\s*\((.*)\)$/i', $cRelSaved, $mO)) {
                                $cRelOtherVal = trim($mO[1]);
                                $cRelSaved = 'Other';
                            }
                            ?>
                            <label class="field">
                                <span class="field-label">Relationship <span id="pt-crel-req" style="display:none; color:#dc2626;">*</span></span>
                                <span class="input-wrap">
                                    <select name="contact_relationship" id="pt-contact-relationship">
                                        <option value="" disabled <?= empty($cRelSaved) ? 'selected' : '' ?> hidden>Select relationship</option>
                                        <?php foreach (['Spouse', 'Parent', 'Child', 'Sibling', 'Guardian', 'Relative', 'Caregiver', 'Other'] as $rel): ?>
                                            <option value="<?= $rel ?>" <?= ($cRelSaved === $rel || ($rel === 'Other' && $cRelIsOther)) ? 'selected' : '' ?>><?= $rel ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?= icon('down', 16) ?>
                                </span>
                            </label>
                            <label class="field" id="pt-contact-rel-other-wrap" style="<?= $cRelIsOther ? 'display:block;' : 'display:none;' ?>">
                                <span class="field-label">Specify Relationship <span id="pt-crel-other-req" style="display:none; color:#dc2626;">*</span></span>
                                <span class="input-wrap">
                                    <input type="text" name="contact_relationship_other" id="pt-contact-relationship-other" placeholder="e.g. Neighbor, Cousin, Co-worker" value="<?= e($cRelOtherVal) ?>">
                                </span>
                            </label>
                            <label class="field">
                                <span class="field-label">Contact Phone Number <span id="pt-cphone-req" style="display:none; color:#dc2626;">*</span></span>
                                <span class="input-wrap">
                                    <input type="tel" name="contact_phone" id="pt-contact-phone" placeholder="09XXXXXXXXX" maxlength="11" value="<?= e($patientRow['contact_phone'] ?? '') ?>" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,11);">
                                </span>
                                <small class="field-hint">No OTP for this number.</small>
                            </label>
                        </div>
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

                                    <span style="color:var(--gray-600); font-weight:600;">Mobile Number:</span>
                                    <span style="color:var(--teal-700); font-weight:700;" id="cfm-patient-phone">—</span>

                                    <span style="color:var(--gray-600); font-weight:600; display:none;" id="cfm-contact-label">Contact Person:</span>
                                    <span style="color:var(--navy-900); display:none;" id="cfm-patient-contact">—</span>

                                    <span style="color:var(--gray-600); font-weight:600;">Assigned MRN:</span>
                                    <code style="color:var(--teal-800); font-weight:700; font-size:13px;" id="cfm-patient-mrn"><?= e($nextMrn) ?></code>

                                    <span style="color:var(--gray-600); font-weight:600;">Residential Address:</span>
                                    <span style="color:var(--navy-900);" id="cfm-patient-address">—</span>
                                </div>
                            </div>

                            <p id="cfm-confirm-note" style="margin:0 0 18px; font-size:12px; color:var(--gray-600);">
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

                    var noPhoneCheckbox = document.getElementById('pt-no-phone-checkbox');
                    var phoneFieldGroup = document.getElementById('pt-phone-field-group');
                    var noPhoneNotice = document.getElementById('pt-no-phone-inperson-notice');
                    var contactSection = document.getElementById('pt-contact-person-section');
                    var contactReqBadge = document.getElementById('pt-contact-required-badge');
                    var cFirstReq = document.getElementById('pt-cfirst-req');
                    var cLastReq = document.getElementById('pt-clast-req');
                    var cRelReq = document.getElementById('pt-crel-req');
                    var cRelOtherReq = document.getElementById('pt-crel-other-req');
                    var cPhoneReq = document.getElementById('pt-cphone-req');
                    var contactFirstInput = document.getElementById('pt-contact-first-name');
                    var contactMiddleInput = document.getElementById('pt-contact-middle-name');
                    var contactLastInput = document.getElementById('pt-contact-last-name');
                    var contactRelSelect = document.getElementById('pt-contact-relationship');
                    var contactRelOtherWrap = document.getElementById('pt-contact-rel-other-wrap');
                    var contactRelOtherInput = document.getElementById('pt-contact-relationship-other');
                    var contactPhoneInput = document.getElementById('pt-contact-phone');

                    function updatePtRelOther() {
                        if (!contactRelSelect || !contactRelOtherWrap) return;
                        var isOther = (contactRelSelect.value === 'Other');
                        var isNoPhone = noPhoneCheckbox && noPhoneCheckbox.checked;
                        contactRelOtherWrap.style.display = isOther ? 'block' : 'none';
                        if (contactRelOtherInput) {
                            contactRelOtherInput.required = isOther && isNoPhone;
                            if (!isOther) contactRelOtherInput.value = '';
                        }
                        if (cRelOtherReq) cRelOtherReq.style.display = (isOther && isNoPhone) ? 'inline' : 'none';
                    }

                    if (contactRelSelect) {
                        contactRelSelect.addEventListener('change', updatePtRelOther);
                    }

                    function updateNoPhoneState() {
                        if (!noPhoneCheckbox) return;
                        var hasNoPhone = noPhoneCheckbox.checked;
                        if (phoneFieldGroup) phoneFieldGroup.style.display = hasNoPhone ? 'none' : 'block';
                        if (noPhoneNotice) noPhoneNotice.style.display = hasNoPhone ? 'block' : 'none';
                        if (contactSection) contactSection.style.display = hasNoPhone ? 'block' : 'none';
                        if (phoneInput) {
                            phoneInput.required = !hasNoPhone;
                            if (hasNoPhone) phoneInput.value = '';
                        }
                        if (contactReqBadge) contactReqBadge.style.display = hasNoPhone ? 'inline' : 'none';
                        if (cFirstReq) cFirstReq.style.display = hasNoPhone ? 'inline' : 'none';
                        if (cLastReq) cLastReq.style.display = hasNoPhone ? 'inline' : 'none';
                        if (cRelReq) cRelReq.style.display = hasNoPhone ? 'inline' : 'none';
                        if (cPhoneReq) cPhoneReq.style.display = hasNoPhone ? 'inline' : 'none';
                        if (contactFirstInput) {
                            contactFirstInput.required = hasNoPhone;
                            if (!hasNoPhone) contactFirstInput.value = '';
                        }
                        if (contactMiddleInput && !hasNoPhone) {
                            contactMiddleInput.value = '';
                        }
                        if (contactLastInput) {
                            contactLastInput.required = hasNoPhone;
                            if (!hasNoPhone) contactLastInput.value = '';
                        }
                        if (contactRelSelect) {
                            contactRelSelect.required = hasNoPhone;
                            if (!hasNoPhone) contactRelSelect.value = '';
                        }
                        if (contactPhoneInput) {
                            contactPhoneInput.required = hasNoPhone;
                            if (!hasNoPhone) contactPhoneInput.value = '';
                        }
                        updatePtRelOther();
                    }

                    if (noPhoneCheckbox) {
                        noPhoneCheckbox.addEventListener('change', updateNoPhoneState);
                        updateNoPhoneState();
                    }

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
                            var hasNoPhone = noPhoneCheckbox && noPhoneCheckbox.checked;
                            if (hasNoPhone) {
                                var cFirst = (contactFirstInput ? contactFirstInput.value.trim() : '');
                                var cMiddle = (contactMiddleInput ? contactMiddleInput.value.trim() : '');
                                var cLast = (contactLastInput ? contactLastInput.value.trim() : '');
                                var cRel = (contactRelSelect ? contactRelSelect.value.trim() : '');
                                var cRelOther = (contactRelOtherInput ? contactRelOtherInput.value.trim() : '');
                                var cPhone = (contactPhoneInput ? contactPhoneInput.value.trim() : '');

                                if (!cFirst) {
                                    alert('Please enter the Contact Person first name.');
                                    if (contactFirstInput) contactFirstInput.focus();
                                    return;
                                }
                                if (!cLast) {
                                    alert('Please enter the Contact Person last name.');
                                    if (contactLastInput) contactLastInput.focus();
                                    return;
                                }
                                if (!cRel) {
                                    alert('Please select the Contact Person relationship.');
                                    if (contactRelSelect) contactRelSelect.focus();
                                    return;
                                }
                                if (cRel === 'Other' && !cRelOther) {
                                    alert('Please specify the relationship for Contact Person.');
                                    if (contactRelOtherInput) contactRelOtherInput.focus();
                                    return;
                                }
                                if (!cPhone || cPhone.length < 11 || !cPhone.startsWith('09')) {
                                    alert('Please enter an 11-digit mobile number starting with 09 for the Contact Person.');
                                    if (contactPhoneInput) contactPhoneInput.focus();
                                    return;
                                }
                                goToStep(3);
                                return;
                            }

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
                            var cfmContactLabel = document.getElementById('cfm-contact-label');
                            var cfmContact = document.getElementById('cfm-patient-contact');
                            var cfmAddr = document.getElementById('cfm-patient-address');
                            var cfmNote = document.getElementById('cfm-confirm-note');

                            var hasNoPhone = noPhoneCheckbox && noPhoneCheckbox.checked;
                            var cFirst = (contactFirstInput ? contactFirstInput.value.trim() : '');
                            var cMiddle = (contactMiddleInput ? contactMiddleInput.value.trim() : '');
                            var cLast = (contactLastInput ? contactLastInput.value.trim() : '');
                            var cRel = (contactRelSelect ? contactRelSelect.value.trim() : '');
                            var cRelOther = (contactRelOtherInput ? contactRelOtherInput.value.trim() : '');
                            var cPhone = (contactPhoneInput ? contactPhoneInput.value.trim() : '');

                            var cFullName = cFirst + (cMiddle ? ' ' + cMiddle : '') + (cLast ? ' ' + cLast : '');
                            var cFullRel = (cRel === 'Other' && cRelOther) ? 'Other (' + cRelOther + ')' : cRel;

                            if (cfmName) cfmName.textContent = fullName;
                            if (cfmDobSex) cfmDobSex.textContent = bd + ' · ' + sx;
                            if (cfmAddr) cfmAddr.textContent = fullAddr;

                            if (hasNoPhone) {
                                if (cfmPhone) cfmPhone.innerHTML = '<span style="color:#b45309;">None (Patient has no phone) · In-person ID verified</span>';
                                if (cfmContactLabel) cfmContactLabel.style.display = 'inline';
                                if (cfmContact) {
                                    cfmContact.style.display = 'inline';
                                    cfmContact.textContent = cFullName + ' (' + cFullRel + ') · ' + cPhone;
                                }
                                if (cfmNote) cfmNote.textContent = 'Once confirmed, this permanent record will be stored in the hospital database with verified in-person ID. No portal account will be created (no phone number provided).';
                            } else {
                                if (cfmPhone) cfmPhone.textContent = ph + ' (✓ SMS Verified)';
                                if (cfmContactLabel) cfmContactLabel.style.display = 'none';
                                if (cfmContact) {
                                    cfmContact.style.display = 'none';
                                    cfmContact.textContent = '—';
                                }
                                if (cfmNote) cfmNote.textContent = 'Once confirmed, this permanent record will be stored in the hospital database and an activation confirmation SMS will be dispatched.';
                            }

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
                $uSex     = (string) ($userRow['sex'] ?? ($userRow[12] ?? ''));
                $dash     = static fn ($v) => (trim((string) $v) === '' || $v === '—') ? '—' : (string) $v;
                $uDobFmt  = (strtotime($uDob) !== false && $uDob !== '—') ? date('F j, Y', strtotime($uDob)) : '—';
                $tone     = ['Active' => ['#dcfce7', '#15803d', 'Active'], 'Pending' => ['#fef3c7', '#b45309', 'Pending Activation'], 'Disabled' => ['#fee2e2', '#b91c1c', 'Deactivated']][$uStatus] ?? ['#dcfce7', '#15803d', $uStatus];
                $detail   = static function (string $label, string $val): string {
                    return '<div style="padding:12px 14px; background:#f8fafc; border:1px solid var(--gray-200); border-radius:8px;">'
                        . '<span style="display:block; font-size:11px; text-transform:uppercase; letter-spacing:.4px; font-weight:700; color:var(--gray-500); margin-bottom:4px;">' . e($label) . '</span>'
                        . '<strong style="font-size:14px; color:var(--navy-900); word-break:break-word;">' . e($val) . '</strong></div>';
                };
                ?>
                <div style="display:flex; align-items:center; gap:16px; padding:18px; background:var(--teal-50); border:1px solid var(--teal-200); border-radius:12px; margin-bottom:18px;">
                    <div style="width:56px; height:56px; border-radius:50%; background:var(--teal-600); color:#fff; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:700; flex-shrink:0;">
                        <?= e(initials((string) $uName, 2)) ?>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <h3 style="margin:0 0 6px; font-size:18px; color:var(--navy-900);">
                            <?= e($uName) ?>
                            <?php if ($uRole === 'Patient'): ?><code style="font-size:13px;">MRN: <?= e($uId) ?></code><?php endif; ?>
                        </h3>
                        <span style="display:inline-block; padding:3px 10px; border-radius:999px; background:#fff; border:1px solid var(--teal-300); color:var(--teal-800); font-size:12px; font-weight:700; margin-right:6px;"><?= e($uRole) ?></span>
                        <span style="display:inline-block; padding:3px 10px; border-radius:999px; background:<?= $tone[0] ?>; color:<?= $tone[1] ?>; font-size:12px; font-weight:700;"><?= e($tone[2]) ?></span>
                    </div>
                </div>

                <strong style="display:block; font-size:12.5px; color:var(--navy-900); text-transform:uppercase; margin-bottom:8px;">Contact &amp; Personal</strong>
                <?php
                $uAddress = (string) ($userAcc['address'] ?? ($userRow['address'] ?? ''));
                if ($uAddress === '') {
                    $uAddress = implode(', ', array_filter([
                        (string) ($userAcc['street'] ?? ($userRow['street'] ?? '')),
                        (string) ($userAcc['barangay'] ?? ($userRow['barangay'] ?? '')),
                        (string) ($userAcc['city'] ?? ($userRow['city'] ?? '')),
                        (string) ($userAcc['province'] ?? ($userRow['province'] ?? '')),
                        (string) ($userAcc['country'] ?? ($userRow['country'] ?? '')),
                        (string) ($userAcc['postal_code'] ?? ($userRow['postal_code'] ?? '')),
                    ]));
                }
                ?>
                <div class="form-grid" style="gap:12px; margin-bottom:16px;">
                    <?= $detail('Email Address', $dash($uEmail)) ?>
                    <?= $detail('Mobile Number', $dash($uPhone)) ?>
                    <?= $detail('Birthdate', $uDobFmt) ?>
                    <?= $detail('Sex', $dash($uSex)) ?>
                    <div style="grid-column: 1 / -1;">
                        <?= $detail('Residential Address', $dash($uAddress)) ?>
                        <?php if ($uRole === 'Patient' && in_array($role, ['Admin', 'Staff'], true)): ?>
                            <div style="margin-top:8px;">
                                <a href="<?= e(url(['page' => 'users', 'modal' => ($role === 'Admin' ? 'Correct patient address' : 'Edit patient address'), 'patient' => $uName, 'user_id' => $uId])) ?>" class="btn btn-secondary btn-sm" style="display:inline-flex; align-items:center; gap:5px; font-size:12px; height:28px;">
                                    <?= icon('edit', 14) ?>
                                    <span><?= $role === 'Admin' ? 'Correct Patient Address' : 'Edit Patient Address' ?></span>
                                </a>
                            </div>
                        <?php elseif ($role === 'Admin' && in_array($uRole, ['Doctor', 'Staff', 'Cashier', 'Admin'], true) && strcasecmp((string) $uId, (string) ($_SESSION['user_id'] ?? '')) !== 0): ?>
                            <div style="margin-top:8px;">
                                <a href="<?= e(url(['page' => 'users', 'modal' => 'Edit user', 'user_id' => $uId, 'email' => $uEmail])) ?>" class="btn btn-secondary btn-sm" style="display:inline-flex; align-items:center; gap:5px; font-size:12px; height:28px;">
                                    <?= icon('edit', 14) ?>
                                    <span>Edit Residential Address & Details</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($uRole === 'Doctor'): ?>
                    <strong style="display:block; font-size:12.5px; color:var(--navy-900); text-transform:uppercase; margin-bottom:8px;">Professional Details</strong>
                    <div class="form-grid" style="gap:12px; margin-bottom:8px;">
                        <?= $detail('Department', $dash($uDept)) ?>
                        <?= $detail('Specialty', $dash($uSpec)) ?>
                        <?= $detail('PRC License No.', $dash($uLicense)) ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($isUser): ?>
                <?php
                $nameParts   = explode(' ', (string) ($userRow[0] ?? ''), 2);
                $uFirst      = $nameParts[0] ?? '';
                $uLast       = $nameParts[1] ?? '';
                $currentRole = (string) ($_GET['role'] ?? ($userRow[2] ?? 'Doctor'));
                $uPhone      = (string) ($userRow[11] ?? ($userRow['phone'] ?? ''));
                $uDob        = (string) ($userRow[10] ?? '');
                $uSex        = (string) ($userRow['sex'] ?? ($userRow[12] ?? ''));
                $uCountry    = (string) ($userAcc['country'] ?? ($userRow['country'] ?? 'Philippines'));
                $uProvince   = (string) ($userAcc['province'] ?? ($userRow['province'] ?? 'Zamboanga del Sur'));
                $uCity       = (string) ($userAcc['city'] ?? ($userRow['city'] ?? 'Zamboanga City'));
                $uBarangay   = (string) ($userAcc['barangay'] ?? ($userRow['barangay'] ?? ''));
                $uStreet     = (string) ($userAcc['street'] ?? ($userRow['street'] ?? ''));
                $uPostal     = (string) ($userAcc['postal_code'] ?? ($userRow['postal_code'] ?? '7000'));
                $uAddress    = (string) ($userAcc['address'] ?? ($userRow['address'] ?? ''));
                ?>
                <?php $isEditUser = ($modal === 'Edit user'); ?>
                <div class="form-grid">
                    <?php if ($isEditUser): ?>
                        <label class="field">
                            <span class="field-label">Role</span>
                            <span class="input-wrap"><input type="text" value="<?= e($currentRole) ?>" readonly style="background:var(--gray-50);"></span>
                            <input type="hidden" name="role" id="user_role_select" value="<?= e($currentRole) ?>">
                        </label>
                    <?php else: ?>
                        <?= select_field('Role *', 'role', ['Doctor', 'Staff', 'Cashier', 'Admin'], $currentRole, ['id' => 'user_role_select']) ?>
                    <?php endif; ?>
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

                <div style="margin-top:14px; margin-bottom:6px; font-weight:700; font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:var(--navy-900);">Residential Address</div>
                <div class="form-grid">
                    <label class="field">
                        <span class="field-label">Country <span style="color:#dc2626;">*</span></span>
                        <span class="input-wrap"><select name="country" id="usr-country-select" required></select><?= icon('down', 16) ?></span>
                    </label>
                    <label class="field">
                        <span class="field-label">Province / Region <span style="color:#dc2626;">*</span></span>
                        <span class="input-wrap"><select name="province" id="usr-province-select" required></select><?= icon('down', 16) ?></span>
                    </label>
                </div>
                <div class="form-grid">
                    <label class="field">
                        <span class="field-label">City / Municipality <span style="color:#dc2626;">*</span></span>
                        <span class="input-wrap"><select name="city" id="usr-city-select" required></select><?= icon('down', 16) ?></span>
                    </label>
                    <label class="field">
                        <span class="field-label">Barangay / District <span style="color:#dc2626;">*</span></span>
                        <span class="input-wrap"><select name="barangay" id="usr-barangay-select" required></select><?= icon('down', 16) ?></span>
                    </label>
                </div>
                <div class="form-grid" style="grid-template-columns: 2fr 1fr;">
                    <label class="field">
                        <span class="field-label">House No., Street, Building <span style="color:#dc2626;">*</span></span>
                        <span class="input-wrap"><input type="text" name="street" id="usr-street-input" placeholder="e.g. 123 Camins Road" value="<?= e($uStreet) ?>" required></span>
                    </label>
                    <label class="field">
                        <span class="field-label">Postal / ZIP Code</span>
                        <span class="input-wrap"><input type="text" name="postal_code" id="usr-postal-input" placeholder="e.g. 7000" value="<?= e($uPostal) ?>"></span>
                    </label>
                </div>
                <input type="hidden" name="address" id="usr-address-full" value="<?= e($uAddress) ?>">

                <div id="doctor_fields_row" class="form-grid three-col" style="<?= $currentRole === 'Doctor' ? 'margin-top:14px;' : 'display:none; margin-top:14px;' ?>">
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
                <?php if ($isEditUser): ?>
                    <label class="field" style="margin-top:14px; display:block;">
                        <span class="field-label">Reason for editing <span style="color:#dc2626;">*</span></span>
                        <span class="input-wrap"><textarea name="reason" rows="3" minlength="5" required placeholder="State why these details are being changed (recorded in the audit log)" style="width:100%;"></textarea></span>
                    </label>
                <?php endif; ?>
                <script>
                (function() {
                    var roleSel = document.getElementById('user_role_select');
                    var docRow  = document.getElementById('doctor_fields_row');
                    var deptIn  = document.getElementById('doc_dept_field');
                    var specIn  = document.getElementById('doc_spec_field');
                    var licIn   = document.getElementById('doc_license_field');

                    function toggleDoctorFields() {
                        if (!roleSel || !docRow) return;
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
                    if (roleSel) {
                        roleSel.addEventListener('change', toggleDoctorFields);
                        toggleDoctorFields();
                    }

                    if (window.PHAddressSelector) {
                        window.PHAddressSelector.init({
                            country: document.getElementById('usr-country-select'),
                            province: document.getElementById('usr-province-select'),
                            city: document.getElementById('usr-city-select'),
                            barangay: document.getElementById('usr-barangay-select'),
                            street: document.getElementById('usr-street-input'),
                            postal: document.getElementById('usr-postal-input'),
                            initialCountry: <?= json_encode($uCountry ?: 'Philippines') ?>,
                            initialProvince: <?= json_encode($uProvince ?: 'Zamboanga del Sur') ?>,
                            initialCity: <?= json_encode($uCity ?: 'Zamboanga City') ?>,
                            initialBarangay: <?= json_encode($uBarangay) ?>,
                            initialPostal: <?= json_encode($uPostal ?: '7000') ?>
                        });
                    }
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
            <?php elseif ($isCorrectPtAddr): ?>
                <?php if ($isCoiBlocked): ?>
                    <div style="background:#fee2e2; border:1px solid #f87171; border-radius:10px; padding:16px 20px; color:#991b1b; margin-bottom:14px;">
                        <strong style="font-size:15px; display:block; margin-bottom:6px;">⚠️ Conflict of Interest Blocked</strong>
                        <p style="margin:0; font-size:14px; font-weight:700;"><?= $role === 'Admin' ? 'Another admin must do this.' : 'Conflict of Interest' ?></p>
                        <p style="margin:4px 0 0; font-size:12.5px; color:#b91c1c;">
                            <?= $role === 'Admin'
                                ? 'Under conflict-of-interest rules, an administrator cannot correct or modify patient records linked to their own login (their own record or dependents).'
                                : 'You cannot edit your own patient record (or records linked to your login). Please update your residential address via your Patient Portal profile.' ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div style="background:var(--teal-50); border:1px solid var(--teal-200); border-radius:10px; padding:12px 16px; margin-bottom:16px;">
                        <strong style="color:var(--teal-900); font-size:14px; display:block;">Patient Record: <?= e($targetPtName) ?> (<?= e($targetPtMrn) ?>)</strong>
                        <p style="margin:3px 0 0; font-size:12px; color:var(--teal-800);"><?= $role === 'Admin' ? 'Administrative oversight correction will be logged in the audit log and address history.' : 'Residential address update will be saved and recorded in the patient address history.' ?></p>
                    </div>

                    <div class="form-grid">
                        <label class="field">
                            <span class="field-label">Country <span style="color:#dc2626;">*</span></span>
                            <span class="input-wrap"><select name="country" id="cpt-country-select" required></select><?= icon('down', 16) ?></span>
                        </label>
                        <label class="field">
                            <span class="field-label">Province / Region <span style="color:#dc2626;">*</span></span>
                            <span class="input-wrap"><select name="province" id="cpt-province-select" required></select><?= icon('down', 16) ?></span>
                        </label>
                    </div>

                    <div class="form-grid">
                        <label class="field">
                            <span class="field-label">City / Municipality <span style="color:#dc2626;">*</span></span>
                            <span class="input-wrap"><select name="city" id="cpt-city-select" required></select><?= icon('down', 16) ?></span>
                        </label>
                        <label class="field">
                            <span class="field-label">Barangay / District <span style="color:#dc2626;">*</span></span>
                            <span class="input-wrap"><select name="barangay" id="cpt-barangay-select" required></select><?= icon('down', 16) ?></span>
                        </label>
                    </div>

                    <div class="form-grid">
                        <label class="field">
                            <span class="field-label">Street / Building / Unit (Put N/A if not applicable) <span style="color:#dc2626;">*</span></span>
                            <span class="input-wrap"><input type="text" name="street" id="cpt-street-input" placeholder="Put N/A if not applicable" value="<?= e($cptStreet ?: 'N/A') ?>" required></span>
                        </label>
                        <label class="field">
                            <span class="field-label">Postal / ZIP Code</span>
                            <span class="input-wrap"><input type="text" name="postal_code" id="cpt-postal-input" value="<?= e($cptPostal ?: '7000') ?>" readonly style="background:var(--gray-50); font-family:monospace; font-weight:600;"></span>
                        </label>
                    </div>

                    <label class="field" style="margin-top:14px; display:block;">
                        <span class="field-label">Reason for <?= $role === 'Admin' ? 'correction' : 'update' ?> <?= $role === 'Admin' ? '<span style="color:#dc2626;">*</span>' : '(Optional)' ?></span>
                        <span class="input-wrap"><textarea name="reason" rows="3" <?= $role === 'Admin' ? 'minlength="5" required' : '' ?> placeholder="<?= $role === 'Admin' ? 'State the required reason for this address correction (recorded in audit log & address history)' : 'Optional reason or note for address update' ?>" style="width:100%;"></textarea></span>
                    </label>

                    <script>
                    (function() {
                        if (window.PHAddressSelector) {
                            window.PHAddressSelector.init({
                                country: document.getElementById('cpt-country-select'),
                                province: document.getElementById('cpt-province-select'),
                                city: document.getElementById('cpt-city-select'),
                                barangay: document.getElementById('cpt-barangay-select'),
                                street: document.getElementById('cpt-street-input'),
                                postal: document.getElementById('cpt-postal-input'),
                                initialCountry: <?= json_encode($cptCountry ?: 'Philippines') ?>,
                                initialProvince: <?= json_encode($cptProvince ?: 'Zamboanga del Sur') ?>,
                                initialCity: <?= json_encode($cptCity ?: 'Zamboanga City') ?>,
                                initialBarangay: <?= json_encode($cptBarangay) ?>,
                                initialPostal: <?= json_encode($cptPostal ?: '7000') ?>
                            });
                        }
                    })();
                    </script>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="modal-actions" <?= ($isCompleteReg || $isPatient) ? 'style="display:none;"' : '' ?>>
            <?= button('Close', ['variant' => 'secondary', 'href' => $closeUrl]) ?>
            <?php if (!$isUserDetail && !($isCorrectPtAddr && $isCoiBlocked)): ?>
                <button type="submit" class="btn btn-primary">
                    <span><?= e($isCompleteReg ? 'Complete Registration & Issue MRN' : ($isQuickProv ? 'Save Record' : ($isCorrectPtAddr ? 'Save Address Correction' : 'Save & Confirm'))) ?></span>
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>
