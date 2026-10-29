<?php
/** @var string $modal  Modal name from ?modal=... */
$closeUrl    = url_with(['modal' => null, 'room' => null, 'key' => null, 'email' => null, 'appt' => null, 'doc' => null, 'dept' => null]);
$isEdit      = str_starts_with($modal, 'Edit');
$isUser      = str_contains($modal, 'user');
$isPatient   = in_array($modal, ['Register patient', 'Edit patient'], true);
$isAppt      = $modal === 'Book appointment';
$isApptDetail = $modal === 'Appointment details';
$isAdmit     = $modal === 'Admit patient';
$isDischarge = $modal === 'Discharge patient';
$dischargeKey = (string) ($_GET['patient'] ?? '');
$isRole      = in_array($modal, ['Create role', 'Manage role'], true);
$isRoom      = in_array($modal, ['Add room', 'Edit room'], true);
$isDept      = in_array($modal, ['Add department', 'Edit department'], true);
$isMed       = in_array($modal, ['Add medication', 'Edit medication'], true);

// Lookup existing rows for Edit modes
$roomRow = ['Room 101', 'General Ward', 'Available', '—'];
if ($modal === 'Edit room') {
    foreach ($state['crud']['rooms'] as $rm) {
        if ($rm[0] === (string) ($_GET['room'] ?? ($_GET['key'] ?? ''))) {
            $roomRow = $rm;
        }
    }
}

$deptRow = ['', '', '', ''];
if ($modal === 'Edit department') {
    foreach ($state['crud']['departments'] as $dp) {
        if ($dp[0] === (string) ($_GET['key'] ?? '')) {
            $deptRow = $dp;
        }
    }
}

$medRow = ['', 'Antibiotic', '', ''];
if ($modal === 'Edit medication') {
    foreach ($state['crud']['medications'] as $md) {
        if ($md[0] === (string) ($_GET['key'] ?? '')) {
            $medRow = $md;
        }
    }
}

$userRow = ['', '', 'Doctor', 'Active', ''];
if ($modal === 'Edit user') {
    foreach ($state['users'] as $us) {
        if ($us[1] === (string) ($_GET['email'] ?? '')) {
            $userRow = $us;
        }
    }
}

$patientRow = ['', '', '', '', '', 'Female', 'Optional / Unknown', ''];
if ($modal === 'Edit patient') {
    foreach ($state['patients'] as $pt) {
        if ($pt[0] === (string) ($_GET['patient'] ?? '')) {
            $patientRow = $pt;
        }
    }
}

$apptIdx = isset($_GET['appt']) ? (int) $_GET['appt'] : -1;
$apptRow = $state['appointments'][$apptIdx] ?? ($state['appointments'][0] ?? ['09:00', '—', 'Consultation', 'Confirmed', '—', '—', '—', date('M d, Y')]);

$roleRow = ['', '', 0, [], 'shield'];
if ($modal === 'Manage role') {
    foreach ($state['roles'] as $rr) {
        if ($rr[0] === (string) ($_GET['role'] ?? '')) {
            $roleRow = $rr;
        }
    }
}

// Build list of available rooms from session for the Admit modal
$availableRooms = [];
foreach ($state['crud']['rooms'] as $rm) {
    if ($rm[2] === 'Available') {
        $availableRooms[] = $rm[0] . ' · ' . $rm[1];  // "Room 202 · General Ward"
    }
}
if (!$availableRooms) {
    $availableRooms = ['No available rooms'];
}

$subtitle = $isUser ? 'Create or update a staff account and assign access.'
    : ($isPatient ? ($modal === 'Edit patient' ? 'Update patient contact and insurance information.' : 'Add a new patient to the hospital record.')
    : ($isAppt ? 'Schedule a new patient visit.'
    : ($isApptDetail ? 'Review visit details and update appointment status.'
    : ($isAdmit ? 'Assign a patient to one of the ' . count($availableRooms) . ' available rooms.'
    : ($isDischarge ? 'Upload a photo as proof of discharge for ' . $dischargeKey . '.'
    : ($isRole ? 'Set the role name and choose what it can access.'
    : ($isRoom ? 'Select the room number, ward, and patient assignment details.'
    : ($isDept ? 'Manage hospital department details and leadership.'
    : ($isMed ? 'Manage pharmacy medication inventory.' : 'Update this record.')))))))));

$submit = $isEdit ? 'Save changes' : ($isAppt ? 'Book appointment' : ($isApptDetail ? 'Update appointment' : ($isAdmit ? 'Admit patient' : 'Save')));
$doneMsg = $isEdit ? 'Changes saved successfully' : $modal . ' completed successfully';

$patientList = $state['patients'] ?? patients_data();
$patientOptions = array_merge(['Select patient'], array_column($patientList, 0));

// Dynamic role options from session roles (Administrator excluded since there can only be one admin)
$roleNames = ['Doctor', 'Staff', 'Cashier', 'Patient'];
foreach (array_column($state['roles'] ?? [], 0) as $rn) {
    $rnNorm = strcasecmp((string) $rn, 'Front desk') === 0 ? 'Staff' : (string) $rn;
    if ($rnNorm !== '' && !in_array(strtolower($rnNorm), ['admin', 'administrator'], true) && !in_array($rnNorm, $roleNames, true)) {
        $roleNames[] = $rnNorm;
    }
}
$doctorNameOptions = array_column(doctors_data(), 0);
if (!$doctorNameOptions) {
    $doctorNameOptions = ['Unassigned'];
}
?>
<div class="modal-backdrop">
    <a class="modal-dismiss" href="<?= e($closeUrl) ?>" aria-label="Close dialog"></a>
    <form class="modal <?= ($isPatient || $isUser || $isApptDetail) ? 'modal-large' : '' ?>" method="post" action="index.php"<?= $isDischarge ? ' enctype="multipart/form-data"' : '' ?><?= (($role ?? '') === 'Admin') ? ' onsubmit="return confirm(\'Are you sure about this action?\');"' : '' ?>>
        <?php if ($isDischarge): ?>
            <input type="hidden" name="action" value="discharge">
            <input type="hidden" name="key" value="<?= e($dischargeKey) ?>">
        <?php elseif ($isAdmit): ?>
            <input type="hidden" name="action" value="admit_patient">
        <?php elseif ($isPatient): ?>
            <input type="hidden" name="action" value="register_patient">
            <input type="hidden" name="orig" value="<?= e($modal === 'Edit patient' ? $patientRow[0] : '') ?>">
        <?php elseif ($isAppt): ?>
            <input type="hidden" name="action" value="book_appointment">
        <?php elseif ($isApptDetail): ?>
            <input type="hidden" name="action" value="update_appointment">
            <input type="hidden" name="idx" value="<?= e((string) $apptIdx) ?>">
        <?php elseif ($isUser): ?>
            <input type="hidden" name="action" value="save_user">
            <input type="hidden" name="orig" value="<?= e($modal === 'Edit user' ? $userRow[1] : '') ?>">
        <?php elseif ($isRole): ?>
            <input type="hidden" name="action" value="save_role">
            <input type="hidden" name="orig" value="<?= e($roleRow[0]) ?>">
        <?php elseif ($isRoom): ?>
            <input type="hidden" name="action" value="save_room">
            <input type="hidden" name="orig" value="<?= e($modal === 'Edit room' ? $roomRow[0] : '') ?>">
        <?php elseif ($isDept): ?>
            <input type="hidden" name="action" value="save_crud">
            <input type="hidden" name="type" value="departments">
            <input type="hidden" name="orig" value="<?= e($modal === 'Edit department' ? $deptRow[0] : '') ?>">
        <?php elseif ($isMed): ?>
            <input type="hidden" name="action" value="save_crud">
            <input type="hidden" name="type" value="medications">
            <input type="hidden" name="orig" value="<?= e($modal === 'Edit medication' ? $medRow[0] : '') ?>">
        <?php else: ?>
            <input type="hidden" name="action" value="notify">
            <input type="hidden" name="message" value="<?= e($doneMsg) ?>">
        <?php endif; ?>
        <input type="hidden" name="return" value="<?= e($closeUrl) ?>">
        <?= csrf_field() ?>

        <div class="modal-head">
            <div>
                <h2><?= e($modal) ?></h2>
                <p><?= e($subtitle) ?></p>
            </div>
            <a class="icon-btn" href="<?= e($closeUrl) ?>" aria-label="Close"><?= icon('close') ?></a>
        </div>

        <div class="modal-body">
            <?php if ($isUser): ?>
                <?php
                $nameParts   = explode(' ', $userRow[0], 2);
                $uFirst      = $nameParts[0] ?? '';
                $uLast       = $nameParts[1] ?? '';
                $uUser       = ($userRow[8] ?? '') !== '' ? $userRow[8] : ($userRow[1] !== '' ? explode('@', $userRow[1])[0] : '');
                $uLicense    = $userRow[9] ?? '';
                $currentRole = strcasecmp((string) ($userRow[2] ?? 'Doctor'), 'Front desk') === 0 ? 'Staff' : ((string) ($userRow[2] ?? 'Doctor'));
                if ($currentRole === '') {
                    $currentRole = 'Doctor';
                }
                $nextDocId     = db_next_user_id('D');
                $nextStaffId   = db_next_user_id('S');
                $nextCashierId = db_next_user_id('C');
                $nextPtId      = db_next_user_id('P');
                $existingUId   = trim((string) ($userRow[7] ?? ''));
                $currentUId    = $existingUId !== '' ? $existingUId : match (db_prefix_for_role($currentRole)) {
                    'D'     => $nextDocId,
                    'C'     => $nextCashierId,
                    'P'     => $nextPtId,
                    default => $nextStaffId,
                };
                ?>
                <div class="form-grid" style="align-items:start;">
                    <label class="field" style="align-content:start;">
                        <span class="field-label">Role</span>
                        <span class="input-wrap">
                            <select name="role" id="usr-role-select">
                                <?php foreach ($roleNames as $optRole): ?>
                                    <option value="<?= e($optRole) ?>"<?= strcasecmp($optRole, $currentRole) === 0 ? ' selected' : '' ?>><?= e($optRole) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= icon('down', 16) ?>
                        </span>
                        <small class="field-hint">Select account role to generate ID prefix.</small>
                    </label>
                    <label class="field" style="align-content:start;">
                        <span class="field-label">Assigned ID (Auto-generated)</span>
                        <span class="input-wrap">
                            <input type="text" name="user_id" id="usr-id-preview" value="<?= e($currentUId) ?>" readonly style="background:var(--gray-50); font-family:monospace; font-weight:700; color:var(--teal-700);">
                        </span>
                        <small class="field-hint"><b>D</b> = Doctor, <b>S</b> = Staff, <b>C</b> = Cashier, <b>P</b> = Patient (auto-incremented).</small>
                    </label>
                </div>
                <div class="form-grid three-col">
                    <?= field('First name', 'first_name', 'First name', 'text', $uFirst) ?>
                    <?= field('Middle name', 'middle_name', 'Optional') ?>
                    <?= field('Last name', 'last_name', 'Last name', 'text', $uLast) ?>
                </div>
                <div class="form-grid">
                    <?= field('Email', 'email', 'name@medicore.com', 'email', $userRow[1]) ?>
                    <?= field('Username', 'username', 'username', 'text', $uUser) ?>
                </div>
                <?php
                $uBdateRaw = trim((string) ($userRow[10] ?? ''));
                $uBdateIso = ($uBdateRaw !== '' && $uBdateRaw !== '—' && strtotime($uBdateRaw) !== false)
                    ? date('Y-m-d', strtotime($uBdateRaw))
                    : '';
                ?>
                <?= field(
                    $isEdit ? 'New password (optional)' : 'Password',
                    'password',
                    $isEdit ? 'Leave blank to keep current password' : 'Minimum 8 characters',
                    'password'
                ) ?>
                <div class="conditional-fields" id="usr-doc-fields">
                    <p>Doctor information (if applicable)</p>
                    <div class="form-grid three-col">
                        <?= field('Specialty', 'specialty', 'e.g. Cardiology', 'text', $userRow[6] ?? '') ?>
                        <?= field('License number', 'license', 'Medical license', 'text', $uLicense) ?>
                        <?= select_field('Department', 'department', array_column($state['crud']['departments'] ?? [], 0) ?: ['General Medicine', 'Cardiology', 'Neurology', 'Pediatrics', 'Orthopedics'], $userRow[5] ?? '') ?>
                    </div>
                </div>
                <div class="conditional-fields" id="usr-patient-fields" style="display:none;">
                    <p>Patient information</p>
                    <div class="form-grid">
                        <label class="field">
                            <span class="field-label">Birthday (Date of birth)</span>
                            <span class="input-wrap">
                                <input type="date" name="birthdate" id="usr-birthdate" max="<?= date('Y-m-d') ?>" value="<?= e($uBdateIso) ?>">
                            </span>
                        </label>
                        <label class="field">
                            <span class="field-label">Age (Auto-calculated)</span>
                            <span class="input-wrap">
                                <input type="text" id="usr-age-preview" value="<?= e(patient_age_label($uBdateRaw)) ?>" readonly style="background:var(--gray-50); font-weight:600; color:var(--teal-800);">
                            </span>
                        </label>
                    </div>
                </div>
                <label class="toggle-row">
                    <span><strong>Account status</strong><small>User can sign in and access records</small></span>
                    <input type="checkbox" name="active" <?= ($userRow[3] ?? 'Active') === 'Active' ? 'checked' : '' ?>><i></i>
                </label>
                <script>
                (function () {
                    var roleSel  = document.getElementById('usr-role-select');
                    var idInput  = document.getElementById('usr-id-preview');
                    var docBox   = document.getElementById('usr-doc-fields');
                    var ptBox    = document.getElementById('usr-patient-fields');
                    var bInput   = document.getElementById('usr-birthdate');
                    var ageBox   = document.getElementById('usr-age-preview');
                    var isEdit   = <?= $isEdit ? 'true' : 'false' ?>;
                    var origRole = <?= json_encode($currentRole) ?>;
                    var origId   = <?= json_encode($existingUId) ?>;
                    var nextIds  = {
                        'Doctor': <?= json_encode($nextDocId) ?>,
                        'Staff': <?= json_encode($nextStaffId) ?>,
                        'Front desk': <?= json_encode($nextStaffId) ?>,
                        'Cashier': <?= json_encode($nextCashierId) ?>,
                        'Patient': <?= json_encode($nextPtId) ?>
                    };
                    function calcUsrAge() {
                        if (!bInput || !ageBox) return;
                        var val = bInput.value;
                        if (!val) { ageBox.value = '—'; return; }
                        var dob = new Date(val + 'T00:00:00');
                        if (isNaN(dob.getTime())) { ageBox.value = '—'; return; }
                        var today = new Date();
                        var age = today.getFullYear() - dob.getFullYear();
                        var m = today.getMonth() - dob.getMonth();
                        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
                        age = Math.max(0, age);
                        ageBox.value = age + (age === 1 ? ' year old' : ' years old');
                    }
                    function syncRole() {
                        if (!roleSel) return;
                        var r  = roleSel.value;
                        var rl = r.toLowerCase();
                        var isDoc  = rl.indexOf('doc') !== -1;
                        var isCash = rl.indexOf('cash') !== -1;
                        var isPt   = rl.indexOf('pat') !== -1;
                        var targetId = nextIds[r];
                        if (!targetId) {
                            if (isDoc) targetId = nextIds['Doctor'];
                            else if (isCash) targetId = nextIds['Cashier'];
                            else if (isPt) targetId = nextIds['Patient'];
                            else targetId = nextIds['Staff'];
                        }
                        if (idInput) {
                            if (isEdit && r === origRole && origId) {
                                idInput.value = origId;
                            } else {
                                idInput.value = targetId;
                            }
                        }
                        if (docBox) {
                            docBox.style.display = isDoc ? 'block' : 'none';
                        }
                        if (ptBox) {
                            ptBox.style.display = isPt ? 'block' : 'none';
                        }
                        if (bInput) {
                            bInput.required = isPt;
                        }
                    }
                    if (bInput) {
                        bInput.addEventListener('input', calcUsrAge);
                        bInput.addEventListener('change', calcUsrAge);
                        calcUsrAge();
                    }
                    if (roleSel) {
                        roleSel.addEventListener('change', syncRole);
                        syncRole();
                    }
                })();
                </script>

            <?php elseif ($isPatient): ?>
                <?php
                $pParts = explode(' ', $patientRow[0], 2);
                $pFirst = $pParts[0] ?? '';
                $pLast  = $pParts[1] ?? '';
                $pBirth = ($patientRow[1] !== '' && $patientRow[1] !== '—' && strtotime($patientRow[1]) !== false)
                    ? date('Y-m-d', strtotime($patientRow[1]))
                    : '';
                $existingPtId = trim((string) ($patientRow[8] ?? ''));
                $assignedPtId = ($modal === 'Edit patient' && $existingPtId !== '')
                    ? $existingPtId
                    : db_next_user_id('P');
                ?>
                <label class="field">
                    <span class="field-label">Assigned Patient ID (Auto-generated)</span>
                    <span class="input-wrap">
                        <input type="text" name="patient_id" value="<?= e($assignedPtId) ?>" readonly style="background:var(--teal-50); font-family:monospace; font-weight:700; color:var(--teal-800); border-color:var(--teal-200);">
                    </span>
                    <small class="field-hint">Automatically generated <b>P-ID</b>. Provide this ID to the patient so they can use it when creating their account.</small>
                </label>
                <div class="form-grid three-col">
                    <?= field('First name', 'first_name', 'First name', 'text', $pFirst) ?>
                    <?= field('Middle name', 'middle_name', 'Optional') ?>
                    <?= field('Last name', 'last_name', 'Last name', 'text', $pLast) ?>
                </div>
                <div class="form-grid three-col">
                    <label class="field">
                        <span class="field-label">Birthday (Date of birth)</span>
                        <span class="input-wrap">
                            <input type="date" name="birthdate" id="pt-birthdate" max="<?= date('Y-m-d') ?>" value="<?= e($pBirth) ?>" required>
                        </span>
                    </label>
                    <label class="field">
                        <span class="field-label">Age (Auto-calculated)</span>
                        <span class="input-wrap">
                            <input type="text" id="pt-age-preview" value="<?= e(patient_age_label($patientRow[1] ?? '')) ?>" readonly style="background:var(--gray-50); font-weight:600; color:var(--teal-800);">
                        </span>
                    </label>
                    <?= select_field('Sex', 'sex', ['Select sex', 'Female', 'Male', 'Other'], $patientRow[5] ?? 'Select sex') ?>
                </div>
                <script>
                (function () {
                    var bInput = document.getElementById('pt-birthdate');
                    var ageBox = document.getElementById('pt-age-preview');
                    function calcPtAge() {
                        if (!bInput || !ageBox) return;
                        var val = bInput.value;
                        if (!val) { ageBox.value = '—'; return; }
                        var dob = new Date(val + 'T00:00:00');
                        if (isNaN(dob.getTime())) { ageBox.value = '—'; return; }
                        var today = new Date();
                        var age = today.getFullYear() - dob.getFullYear();
                        var m = today.getMonth() - dob.getMonth();
                        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
                        age = Math.max(0, age);
                        ageBox.value = age + (age === 1 ? ' year old' : ' years old');
                    }
                    if (bInput) {
                        bInput.addEventListener('input', calcPtAge);
                        bInput.addEventListener('change', calcPtAge);
                        calcPtAge();
                    }
                })();
                </script>
                <div class="form-grid">
                    <?= field('Phone', 'phone', '(000) 000-0000', 'text', $patientRow[2]) ?>
                    <?= field('Insurance number', 'insurance', 'HIN-000-000', 'text', $patientRow[3]) ?>
                </div>
                <?= field('Address', 'address', 'Street, city, state, ZIP', 'text', $patientRow[7] ?? '') ?>

            <?php elseif ($isAppt): ?>
                <?php if (($role ?? '') === 'Patient'):
                    $apptPtRec = find_patient_record($state, current_user_name('Patient'));
                    $apptPtId  = $apptPtRec[8] ?? ($_SESSION['user_id'] ?? 'P-1001');
                    $apptPtDob = $apptPtRec[1] ?? '—';
                    $apptPtAge = patient_age_label($apptPtDob, true);
                ?>
                    <input type="hidden" name="patient" value="<?= e(current_user_name('Patient')) ?>">
                    <div class="panel" style="padding:12px 16px; background:var(--teal-50); border-color:var(--teal-100); font-size:13px;">
                        Booking for <strong><?= e(current_user_name('Patient')) ?></strong> (ID: <code><?= e($apptPtId) ?></code> · Birthday: <?= e($apptPtDob) ?> · Age: <?= e($apptPtAge) ?>)
                    </div>
                <?php else: ?>
                    <?= select_field('Patient', 'patient', $patientOptions) ?>
                <?php endif; ?>
                <?php
                $bkDoctors = doctors_data();
                $bkDepts   = array_column($state['crud']['departments'] ?? [], 0) ?: ['Cardiology', 'Neurology', 'Pediatrics', 'Orthopedics'];
                $bkSelDoc  = (string) ($_GET['doc'] ?? '');
                $bkSelDept = (string) ($_GET['dept'] ?? '');
                foreach ($bkDoctors as $bd) {
                    if ($bd[0] === $bkSelDoc) {
                        $bkSelDept = $bd[1];
                    }
                }
                ?>
                <div class="form-grid">
                    <label class="field">
                        <span class="field-label">Department</span>
                        <span class="input-wrap">
                            <input type="text" name="department" id="bk-dept" list="bk-dept-list" value="<?= e($bkSelDept) ?>" placeholder="Search department..." autocomplete="off">
                            <?= icon('search', 16) ?>
                        </span>
                        <datalist id="bk-dept-list">
                            <?php foreach ($bkDepts as $dn): ?>
                                <option value="<?= e($dn) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </label>
                    <label class="field">
                        <span class="field-label">Doctor</span>
                        <span class="input-wrap">
                            <input type="text" name="doctor" id="bk-doc" list="bk-doc-list" value="<?= e($bkSelDoc) ?>" placeholder="Search doctor or specialty..." autocomplete="off" required>
                            <?= icon('search', 16) ?>
                        </span>
                        <datalist id="bk-doc-list">
                            <?php foreach ($bkDoctors as $bd): ?>
                                <option value="<?= e($bd[0]) ?>" label="<?= e($bd[1] . ' · ' . $bd[2]) ?>" data-dept="<?= e($bd[1]) ?>" data-spec="<?= e($bd[2]) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </label>
                </div>
                <small class="field-hint" id="bk-empty" style="display:none; color:var(--red); font-weight:600;">No doctor or department matches your search.</small>
                <script>
                (function () {
                    var d = document.getElementById('bk-dept'), s = document.getElementById('bk-doc'), list = document.getElementById('bk-doc-list'), empty = document.getElementById('bk-empty');
                    var docs = [].slice.call(list.options).map(function (o) { return { name: o.value, dept: o.getAttribute('data-dept'), spec: o.getAttribute('data-spec') }; });
                    function norm(v) { return v.trim().toLowerCase(); }
                    function render() {
                        var dq = norm(d.value), sq = norm(s.value), shown = 0;
                        list.innerHTML = '';
                        docs.forEach(function (x) {
                            var deptOk = !dq || norm(x.dept).indexOf(dq) > -1;
                            var textOk = !sq || norm(x.name + ' ' + x.spec + ' ' + x.dept).indexOf(sq) > -1;
                            if (deptOk && textOk) {
                                var o = document.createElement('option');
                                o.value = x.name; o.label = x.dept + ' · ' + x.spec;
                                list.appendChild(o); shown++;
                            }
                        });
                        empty.style.display = ((dq || sq) && !shown) ? 'block' : 'none';
                    }
                    d.addEventListener('input', render);
                    s.addEventListener('input', function () {
                        render();
                        var m = docs.filter(function (x) { return x.name === s.value; })[0];
                        if (m) { d.value = m.dept; }
                    });
                    render();
                })();
                </script>
                <div class="form-grid">
                    <?= field('Date', 'date', '', 'date', date('Y-m-d', strtotime('+1 day'))) ?>
                    <?= select_field('Time slot', 'slot', ['08:30', '09:00', '10:00', '10:30', '11:00', '13:00', '14:30', '15:30']) ?>
                </div>
                <div class="form-grid">
                    <?= select_field('Visit type', 'visit_type', ['Consultation', 'Follow-up', 'Annual check-up', 'Specialist review']) ?>
                    <?= field('Reason for visit / Symptoms', 'complaint', 'Briefly describe your symptoms or concern') ?>
                </div>

            <?php elseif ($isApptDetail): ?>
                <?php
                $currSt    = $apptRow[3] ?? 'Confirmed';
                $isLocked  = in_array($currSt, ['Completed', 'Cancelled'], true);
                $stepIndex = match ($currSt) {
                    'In progress' => 2,
                    'Completed'   => 3,
                    default       => 1,
                };
                $apptIdCode = '#APT-' . str_pad((string) (3280 + max(0, $apptIdx)), 4, '0', STR_PAD_LEFT);
                ?>
                <div class="order-tracker-card <?= $currSt === 'Cancelled' ? 'is-cancelled' : '' ?>">
                    <div class="order-tracker-head">
                        <div class="order-tracker-id">
                            APPOINTMENT <span><?= e($apptIdCode) ?></span>
                        </div>
                        <div class="order-tracker-meta">
                            <span>Scheduled <?= e(($apptRow[7] ?? date('M d, Y')) . ' · ' . ($apptRow[0] ?? '09:00')) ?></span>
                            <strong><?= e(($apptRow[1] ?? 'Patient') . ' · ' . ($apptRow[5] ?? '—')) ?></strong>
                        </div>
                    </div>

                    <!-- Horizontal connected circle buttons + progress bars -->
                    <div class="order-rail-row">
                        <button type="submit" name="step" value="confirmed" class="order-circle-btn is-done" disabled aria-label="Confirmed">
                            <span class="order-inner-dot"></span>
                        </button>

                        <div class="order-progress-bar <?= $stepIndex >= 2 ? 'is-filled' : '' ?>"></div>

                        <button type="submit" name="step" value="in_progress"
                                class="order-circle-btn <?= $stepIndex >= 2 ? 'is-done' : ($stepIndex === 1 && !$isLocked ? 'is-next' : '') ?>"
                                <?= ($stepIndex === 1 && !$isLocked) ? 'title="Click to advance to In Progress"' : 'disabled' ?>
                                aria-label="In Progress">
                            <span class="order-inner-dot"></span>
                        </button>

                        <div class="order-progress-bar <?= $stepIndex >= 3 ? 'is-filled' : '' ?>"></div>

                        <button type="submit" name="step" value="completed"
                                class="order-circle-btn <?= $stepIndex >= 3 ? 'is-done' : ($stepIndex === 2 && !$isLocked ? 'is-next' : '') ?>"
                                <?= ($stepIndex === 2 && !$isLocked) ? 'title="Click to advance to Completed"' : 'disabled' ?>
                                aria-label="Completed">
                            <span class="order-inner-dot"></span>
                        </button>
                    </div>

                    <!-- Step icons & labels aligned beneath the 3 circle buttons -->
                    <div class="order-captions-row">
                        <div class="order-caption-box <?= $stepIndex >= 1 ? 'is-active' : '' ?>">
                            <span class="order-caption-icon"><?= icon('clipboard', 28) ?></span>
                            <div>
                                <strong>Appointment</strong>
                                <span>Confirmed</span>
                            </div>
                        </div>

                        <div class="order-caption-box is-center <?= $stepIndex >= 2 ? 'is-active' : ($stepIndex === 1 && !$isLocked ? 'is-next' : '') ?>">
                            <span class="order-caption-icon"><?= icon('patient', 28) ?></span>
                            <div>
                                <strong>Consultation</strong>
                                <span>In Progress</span>
                            </div>
                        </div>

                        <div class="order-caption-box is-end <?= $stepIndex >= 3 ? 'is-active' : ($stepIndex === 2 && !$isLocked ? 'is-next' : '') ?>">
                            <span class="order-caption-icon"><?= icon('building', 28) ?></span>
                            <div>
                                <strong>Visit</strong>
                                <span>Completed</span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                $apptDetailPt  = find_patient_record($state, (string) ($apptRow[1] ?? ''));
                $apptDetailDob = $apptDetailPt[1] ?? '—';
                $apptDetailAge = patient_age_label($apptDetailDob, true);
                ?>
                <div class="panel" style="padding:16px; margin-bottom:14px; background:var(--gray-50);">
                    <dl style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin:0;">
                        <div>
                            <dt style="font-size:12px; color:var(--gray-500);">Patient</dt>
                            <dd style="margin:2px 0 0; font-weight:600;"><?= e($apptRow[1] ?? '—') ?></dd>
                        </div>
                        <div>
                            <dt style="font-size:12px; color:var(--gray-500);">Birthday &amp; Age</dt>
                            <dd style="margin:2px 0 0; font-weight:600;"><?= e($apptDetailDob) ?> · <?= e($apptDetailAge) ?></dd>
                        </div>
                        <div>
                            <dt style="font-size:12px; color:var(--gray-500);">Current status</dt>
                            <dd style="margin:2px 0 0;"><?= badge($currSt, status_tone($currSt)) ?></dd>
                        </div>
                        <div>
                            <dt style="font-size:12px; color:var(--gray-500);">Attending doctor</dt>
                            <dd style="margin:2px 0 0; font-weight:600;"><?= e($apptRow[5] ?? '—') ?></dd>
                        </div>
                        <div style="grid-column:1 / -1;">
                            <dt style="font-size:12px; color:var(--gray-500);">Department &amp; Type</dt>
                            <dd style="margin:2px 0 0; font-weight:600;"><?= e(($apptRow[6] ?? 'Cardiology') . ' · ' . ($apptRow[2] ?? 'Consultation')) ?></dd>
                        </div>
                    </dl>
                </div>
                <?php if ($isLocked): ?>
                    <div class="panel" style="padding:12px 16px; background:var(--gray-50); font-size:13px; color:var(--gray-600);">
                        <strong>Chief complaint:</strong> <?= e($apptRow[4] ?? '—') ?><br>
                        <small class="muted">This appointment is <?= e(strtolower($currSt)) ?> and can no longer be modified.</small>
                    </div>
                <?php else: ?>
                    <div class="form-grid">
                        <?= field('Chief complaint / Notes', 'complaint', 'Reason for visit', 'text', $apptRow[4] ?? '') ?>
                        <?= select_field('Time slot', 'slot', ['08:30', '09:00', '10:00', '10:15', '10:30', '11:00', '11:30', '13:00', '14:00', '14:30', '15:30'], $apptRow[0] ?? '09:00') ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($isAdmit): ?>
                <?= select_field('Patient', 'patient', $patientOptions) ?>
                <?php if (count($availableRooms) === 1 && $availableRooms[0] === 'No available rooms'): ?>
                    <p class="field-hint" style="color:var(--red-600);font-weight:600">⚠️ No rooms are currently available. Add or free a room first.</p>
                    <input type="hidden" name="room" value="">
                <?php else: ?>
                    <?= select_field('Room', 'room', $availableRooms) ?>
                <?php endif; ?>
                <?= select_field('Attending doctor', 'doctor', $doctorNameOptions) ?>
                <?= textarea_field('Reason for admission', 'reason', 'Enter admission reason and notes...') ?>

            <?php elseif ($isDischarge): ?>
                <?= file_field('Proof of discharge (image)', 'proof') ?>

            <?php elseif ($isRole): ?>
                <?= field('Role name', 'name', 'e.g. Pharmacist', 'text', $roleRow[0]) ?>
                <?= field('Description', 'description', 'What does this role do?', 'text', $roleRow[1]) ?>
                <div class="perm-grid">
                    <span class="field-label">Permissions</span>
                    <?php foreach (permissions_list() as $perm): ?>
                        <label class="perm-item">
                            <input type="checkbox" name="perms[]" value="<?= e($perm) ?>"<?= in_array($perm, $roleRow[3], true) ? ' checked' : '' ?>>
                            <span><?= e($perm) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($isRoom): ?>
                <?php
                $allRoomNumbers = [
                    'Room 101', 'Room 102', 'Room 110', 'Room 112',
                    'Room 201', 'Room 202', 'Room 204', 'Room 215',
                    'Room 301', 'Room 302', 'Room 318', 'Room 320',
                ];
                $occupiedRooms = [];
                foreach ($state['crud']['rooms'] as $rm) {
                    if (!in_array($rm[0], $allRoomNumbers, true)) {
                        $allRoomNumbers[] = $rm[0];
                    }
                    if ($rm[2] === 'Occupied') {
                        $occupiedRooms[] = $rm[0];
                    }
                }
                // Filter out occupied rooms (except the room currently being edited if in Edit mode)
                $editingRoom = $modal === 'Edit room' ? $roomRow[0] : '';
                $roomNumberOptions = array_values(array_filter(
                    $allRoomNumbers,
                    static fn (string $r) => !in_array($r, $occupiedRooms, true) || $r === $editingRoom
                ));
                sort($roomNumberOptions);
                if (!$roomNumberOptions) {
                    $roomNumberOptions = ['No available rooms'];
                }

                $wardOptions = array_values(array_unique(array_merge(
                    ['General Ward', 'Cardiology', 'ICU', 'Pediatrics', 'Orthopedics', 'Neurology'],
                    array_column($state['crud']['departments'] ?? [], 0)
                )));

                // Build patient detail dropdown options from live session patients
                $roomPatientOptions = ['— (No patient assigned / Available)'];
                $selectedRoomPatient = $roomPatientOptions[0];
                foreach ($patientList as $pt) {
                    $ptAgeStr = patient_age_label($pt[1] ?? '', true);
                    $label = $pt[0] . ' · ' . ($pt[1] ?? '—') . ' (' . $ptAgeStr . ') · ' . $pt[3] . ' · ' . $pt[2];
                    $roomPatientOptions[] = $label;
                    if ($roomRow[3] === $pt[0]) {
                        $selectedRoomPatient = $label;
                    }
                }
                ?>
                <?php $autoRoomStatus = ($selectedRoomPatient !== $roomPatientOptions[0]) ? 'Occupied' : 'Available'; ?>
                <div class="form-grid">
                    <?= select_field('Room', 'room_number', $roomNumberOptions, $roomRow[0]) ?>
                    <?= select_field('Ward / Department', 'ward', $wardOptions, $roomRow[1]) ?>
                </div>
                <label class="field">
                    <span class="field-label">Patient details</span>
                    <span class="input-wrap">
                        <select name="patient_detail" id="rm-patient-select">
                            <?php foreach ($roomPatientOptions as $rpOpt): ?>
                                <option<?= $rpOpt === $selectedRoomPatient ? ' selected' : '' ?>><?= e($rpOpt) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= icon('down', 16) ?>
                    </span>
                </label>
                <div class="form-grid">
                    <?= select_field('Attending doctor', 'doctor', $doctorNameOptions) ?>
                    <label class="field">
                        <span class="field-label">Room status (Automatic)</span>
                        <span class="input-wrap">
                            <input type="text" id="rm-status-preview" value="<?= e($autoRoomStatus) ?>" readonly style="background:var(--gray-50); font-weight:700; color:<?= $autoRoomStatus === 'Occupied' ? 'var(--teal-700)' : 'var(--green-700)' ?>;">
                        </span>
                        <small class="field-hint">Automatically set to <b>Occupied</b> on admission and <b>Available</b> on discharge.</small>
                    </label>
                </div>
                <script>
                (function () {
                    var ptSel = document.getElementById('rm-patient-select');
                    var stBox = document.getElementById('rm-status-preview');
                    function syncRoomStatus() {
                        if (!ptSel || !stBox) return;
                        var hasPt = ptSel.value && ptSel.value.indexOf('—') !== 0;
                        stBox.value = hasPt ? 'Occupied' : 'Available';
                        stBox.style.color = hasPt ? 'var(--teal-700)' : 'var(--green-700)';
                    }
                    if (ptSel) {
                        ptSel.addEventListener('change', syncRoomStatus);
                        syncRoomStatus();
                    }
                })();
                </script>

            <?php elseif ($isDept): ?>
                <div class="form-grid">
                    <?= field('Department name', 'dept_name', 'e.g. Oncology', 'text', $deptRow[0]) ?>
                    <?= select_field('Department head', 'dept_head', $doctorNameOptions, $deptRow[1] ?: ($doctorNameOptions[0] ?? 'Unassigned')) ?>
                </div>
                <?= field('Location', 'dept_location', 'e.g. 4th Floor, East Wing', 'text', $deptRow[3] ?? ($deptRow[2] ?? '')) ?>

            <?php elseif ($isMed): ?>
                <div class="form-grid">
                    <?= field('Medication name', 'med_name', 'e.g. Ibuprofen', 'text', $medRow[0]) ?>
                    <?= select_field('Category', 'med_category', ['Antibiotic', 'Cardiovascular', 'Diabetes', 'Analgesic', 'Respiratory', 'Neurological'], $medRow[1] ?: 'Antibiotic') ?>
                </div>
                <div class="form-grid">
                    <?= field('Strength / Dosage', 'med_strength', 'e.g. 400 mg', 'text', $medRow[2]) ?>
                    <?= field('Stock quantity', 'med_stock', 'e.g. 250 units', 'text', $medRow[3]) ?>
                </div>

            <?php else: ?>
                <?= field('Name', 'name', 'Enter name') ?>
                <?= field('Description', 'description', 'Enter details') ?>
            <?php endif; ?>
        </div>

        <div class="modal-actions">
            <?php if ($isApptDetail): ?>
                <?php $currSt = $apptRow[3] ?? 'Confirmed'; ?>
                <?= button('Close', ['variant' => 'secondary', 'href' => $closeUrl]) ?>
                <?php if (in_array($currSt, ['Confirmed', 'Waiting', 'Upcoming'], true)): ?>
                    <button type="submit" name="step" value="cancel" class="btn btn-danger"><span>Cancel appointment</span></button>
                    <button type="submit" name="step" value="save" class="btn btn-primary"><span>Save changes</span></button>
                <?php elseif ($currSt === 'In progress'): ?>
                    <button type="submit" name="step" value="save" class="btn btn-primary"><span>Save changes</span></button>
                <?php endif; ?>
            <?php else: ?>
                <?= button('Cancel', ['variant' => 'secondary', 'href' => $closeUrl]) ?>
                <?= button($isDischarge ? 'Confirm discharge' : ($modal === 'Create role' ? 'Create role' : ($modal === 'Manage role' ? 'Save role' : $submit)), ['type' => 'submit']) ?>
            <?php endif; ?>
        </div>
    </form>
</div>
