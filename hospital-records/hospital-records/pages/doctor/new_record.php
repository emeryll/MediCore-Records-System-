<?php
declare(strict_types=1);

$patientParam = trim((string) ($_GET['patient'] ?? ''));
$curPage      = (string) ($_GET['page'] ?? 'consultation');
$editRecId    = trim((string) ($_GET['rec'] ?? ''));
$qSearch      = trim((string) ($_GET['q'] ?? ''));

$ptIdentity   = $patientParam !== '' ? resolve_patient_identity($state, $patientParam) : ['name' => '', 'id' => '', 'record' => null];
$patient      = $ptIdentity['name'];
$ptId         = $ptIdentity['id'];
$ptRecord     = $ptIdentity['record'] ?? ($patient !== '' ? find_patient_record($state, $patient) : null);
$ptMrn        = $ptRecord ? patient_mrn_display($ptRecord) : $ptId;
$ptDob        = $ptRecord ? format_birthdate($ptRecord[1] ?? '') : '—';
$ptAge        = $ptDob !== '—' ? patient_age_label($ptDob, false, '—') : '—';

/* =========================================================================
 * VIEW 1: PATIENT SELECTION & SEARCH (When no patient is chosen yet)
 * ========================================================================= */
if ($patient === ''):
    $docName    = current_user_name('Doctor');
    $allAppts   = $state['appointments'] ?? [];
    $allPts     = $state['patients'] ?? [];

    // Filter queued/scheduled appointments for consultation
    $queuedAppts = [];
    foreach ($allAppts as $aIdx => $ap) {
        $aPt   = (string) ($ap[1] ?? '');
        $aDoc  = (string) ($ap[5] ?? '');
        $aStat = (string) ($ap[3] ?? '');
        if (in_array($aStat, ['Checked-in', 'Waiting', 'In consultation', 'Scheduled', 'Confirmed', 'Upcoming'], true)) {
            if ($qSearch === '' || row_matches($ap, $qSearch)) {
                $queuedAppts[] = ['idx' => $aIdx, 'row' => $ap];
            }
        }
    }

    // Filter all registered hospital patients
    $filteredPatients = array_values(array_filter($allPts, static function ($p) use ($qSearch) {
        if (($p['status'] ?? '') === 'Archived') {
            return false;
        }
        if ($qSearch === '') {
            return true;
        }
        return row_matches($p, $qSearch);
    }));
    $pagedPts = paginate_rows($filteredPatients, 8, 'p');
?>
<?= page_title(
    'Patient Consultation',
    'Search for a patient or select an arriving outpatient below to open their consultation chart and record diagnoses, prescriptions, and lab orders.'
) ?>

<!-- Search Bar for Patients in Consultation -->
<div class="panel" style="margin-bottom:20px;">
    <div class="panel-head" style="border-bottom:0; padding-bottom:0;">
        <div>
            <h2>Find Patient for Consultation</h2>
            <p>Search by patient name, MRN, contact number, or birthdate</p>
        </div>
    </div>
    <form method="get" action="index.php" class="table-toolbar" style="padding-top:10px; margin-bottom:0; border-bottom:0;">
        <input type="hidden" name="page" value="consultation">
        <?= search_box('Search by patient name, MRN (e.g. MRN-1001), phone, or birthdate...', 'q', $qSearch) ?>
        <button class="btn btn-primary" type="submit">
            <?= icon('search', 16) ?> <span>Search</span>
        </button>
        <?php if ($qSearch !== ''): ?>
            <a class="btn btn-ghost" href="<?= e(url(['page' => 'consultation'])) ?>"><span>Reset filter</span></a>
        <?php endif; ?>
    </form>
</div>

<!-- SECTION 1: Arrived & Scheduled Patients Queue -->
<?php if ($queuedAppts): ?>
    <div class="panel" style="margin-bottom:24px;">
        <div class="panel-head">
            <div>
                <h2 style="display:flex; align-items:center; gap:8px;">
                    <?= icon('calendar', 18) ?>
                    <span>Arrived &amp; Scheduled Appointments (<?= count($queuedAppts) ?>)</span>
                </h2>
                <p>Patients with active appointments ready for consultation</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>MRN</th>
                        <th>Reason / Chief Complaint</th>
                        <th>Attending Doctor</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queuedAppts as $qa):
                        $aRow   = $qa['row'];
                        $aIdx   = $qa['idx'];
                        $aPtRec = find_patient_record($state, (string) ($aRow[1] ?? ''));
                        $aMrn   = $aPtRec ? patient_mrn_display($aPtRec) : (string) ($aRow[8] ?? '—');
                        $aProv  = $aPtRec ? is_provisional_patient($aPtRec) : str_starts_with((string) ($aRow[8] ?? ''), 'PROV-');
                    ?>
                        <tr>
                            <td>
                                <strong><?= e($aRow[0] ?? '09:00') ?></strong>
                                <div class="muted" style="font-size:11px;"><?= e($aRow[7] ?? 'Today') ?></div>
                            </td>
                            <td>
                                <div class="person-cell">
                                    <?= mini_avatar((string) $aRow[1]) ?>
                                    <div>
                                        <strong><?= e((string) $aRow[1]) ?></strong>
                                        <div class="muted" style="font-size:11px;"><?= e($aRow[2] ?? 'Consultation') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($aMrn !== '' && $aMrn !== '—'): ?>
                                    <code style="font-weight:700; color:var(--teal-700);"><?= e($aMrn) ?></code>
                                <?php else: ?>
                                    <span class="muted" style="font-size:11.5px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="complaint-text"><?= e($aRow[4] ?? 'Consultation') ?></span></td>
                            <td>
                                <strong><?= e($aRow[5] ?? $docName) ?></strong>
                                <div class="muted" style="font-size:11px;"><?= e($aRow[6] ?? 'Outpatient') ?></div>
                            </td>
                            <td><?= badge($aRow[3] ?? 'Scheduled', status_tone($aRow[3] ?? 'Scheduled')) ?></td>
                            <td>
                                <?php if ($aProv): ?>
                                    <span class="muted" style="font-size:11.5px;" title="Front Desk must complete full registration first">Provisional (Needs Check-in)</span>
                                <?php else: ?>
                                    <a class="btn btn-primary btn-sm" href="<?= e(url(['page' => 'consultation', 'patient' => $aRow[1]])) ?>">
                                        <?= icon('clipboard', 14) ?>
                                        <span>Start Consultation</span>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- SECTION 2: Hospital Patient Directory -->
<div class="panel">
    <div class="panel-head">
        <div>
            <h2 style="display:flex; align-items:center; gap:8px;">
                <?= icon('users', 18) ?>
                <span>Hospital Patient Directory (<?= count($filteredPatients) ?>)</span>
            </h2>
            <p>Select any registered patient to begin an unscheduled or walk-in consultation</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>MRN</th>
                    <th>Birthdate &amp; Age</th>
                    <th>Sex</th>
                    <th>Contact Phone</th>
                    <th>Registration Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagedPts['rows'] as $p):
                    $isProv    = is_provisional_patient($p);
                    $regStatus = $isProv ? 'Provisional' : 'Registered';
                    $mrn       = patient_mrn_display($p, '');
                ?>
                    <tr>
                        <td>
                            <div class="person-cell">
                                <?= mini_avatar((string) $p[0]) ?>
                                <div>
                                    <strong><?= e((string) $p[0]) ?></strong>
                                    <?php if (!empty($p['email'])): ?>
                                        <div class="muted" style="font-size:11px;"><?= e((string) $p['email']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($mrn !== ''): ?>
                                <code style="font-weight:700; color:var(--teal-700); background:var(--teal-50); padding:3px 8px; border-radius:6px;"><?= e($mrn) ?></code>
                            <?php else: ?>
                                <span class="muted" style="font-size:11.5px;">— (None)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= e(format_birthdate((string) $p[1])) ?></div>
                            <small class="muted"><?= e(patient_age_label((string) $p[1], true)) ?></small>
                        </td>
                        <td><?= e((string) ($p[5] ?? '—')) ?></td>
                        <td><?= e((string) ($p[2] ?? '—')) ?></td>
                        <td><?= badge($regStatus, status_tone($regStatus)) ?></td>
                        <td>
                            <?php if ($isProv): ?>
                                <span class="muted" style="font-size:11px;" title="Complete registration at Front Desk before consultation">Provisional</span>
                            <?php else: ?>
                                <a class="btn btn-primary btn-sm" href="<?= e(url(['page' => 'consultation', 'patient' => $p[0]])) ?>">
                                    <?= icon('clipboard', 14) ?>
                                    <span>Start Consultation</span>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$pagedPts['rows']): ?>
                    <tr>
                        <td colspan="7"><?= filtered_empty('No matching patient records found for "' . e($qSearch) . '".') ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= table_close($pagedPts['label'], true, $pagedPts['pages']) ?>
</div>

<?php
return;
endif;

/* =========================================================================
 * VIEW 2: ACTIVE CLINICAL CONSULTATION FORM (When a patient is selected)
 * ========================================================================= */
$complaint     = '';
$visitType     = 'Consultation';
$notes         = '';
$followUp      = '';
$diagnoses     = [];
$prescriptions = [];
$labOrders     = [];
$isEditing     = false;

if ($editRecId !== '') {
    foreach ($state['records'] ?? [] as $k => $recList) {
        foreach ($recList as $r) {
            if (($r['id'] ?? '') === $editRecId) {
                $isEditing     = true;
                $complaint     = (string) ($r['complaint'] ?? '');
                $visitType     = (string) ($r['visit_type'] ?? 'Consultation');
                $notes         = (string) ($r['notes'] ?? '');
                $followUp      = (string) ($r['follow_up'] ?? '');
                $diagnoses     = (array) ($r['diagnoses'] ?? []);
                $prescriptions = (array) ($r['prescriptions'] ?? []);
                $labOrders     = (array) ($r['lab_orders'] ?? []);
                break 2;
            }
        }
    }
}

if (!$isEditing) {
    foreach ($state['appointments'] ?? [] as $appt) {
        if (matches_patient_identity($ptIdentity, $appt[1] ?? '', $appt[8] ?? null)) {
            $complaint = $appt[4] ?? '';
            $visitType = $appt[2] ?? 'Consultation';
            break;
        }
    }
}

if (!$diagnoses) {
    $diagnoses = [['', '']];
}
if (!$prescriptions) {
    $prescriptions = [['', '', 'Once daily', '30 days']];
}
if (!$labOrders) {
    $labOrders = [['', 'Routine', '']];
}

// Pull medication and lab test names from hospital Price List
$medOpts  = ['Select medication'];
$testOpts = ['Select test'];
foreach ($state['price_list'] ?? [] as $pl) {
    if (empty($pl['active'])) {
        continue;
    }
    if (($pl['category'] ?? '') === 'Medication') {
        $medOpts[] = $pl['name'];
    } elseif (($pl['category'] ?? '') === 'Lab & Diagnostics') {
        $testOpts[] = $pl['name'];
    }
}
$medOpts  = array_values(array_unique(array_merge($medOpts, ['Amlodipine', 'Amoxicillin', 'Atorvastatin', 'Azithromycin', 'Cetirizine', 'Ibuprofen', 'Lisinopril', 'Metformin', 'Omeprazole', 'Paracetamol'])));
$testOpts = array_values(array_unique(array_merge($testOpts, ['Complete blood count', 'Lipid panel', 'Metabolic panel', 'Thyroid function', 'Urinalysis', 'Blood glucose', 'Liver function test', 'Chest X-Ray', 'Electrocardiogram (ECG)'])));

$titlePrefix = $isEditing ? 'Edit Consultation Record' : 'Patient Consultation';
?>

<!-- Quick Switch Patient Header & Search Bar -->
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:14px;">
    <a class="back-link" href="<?= e(url(['page' => 'consultation'])) ?>" style="font-size:12.5px; font-weight:600;">
        ← Switch / Search Another Patient
    </a>
    <form method="get" action="index.php" style="display:flex; gap:6px; align-items:center;">
        <input type="hidden" name="page" value="consultation">
        <input type="text" name="q" placeholder="Search another patient..." style="height:32px; font-size:12px; padding:0 10px; border:1px solid var(--gray-300); border-radius:6px; width:220px;">
        <button class="btn btn-secondary btn-sm" type="submit">
            <?= icon('search', 13) ?> <span>Search</span>
        </button>
    </form>
</div>

<!-- Active Patient Profile Header Card -->
<div class="panel" style="background:#fff; border-left:4px solid var(--teal-700); margin-bottom:20px; padding:16px 20px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <?= mini_avatar($patient) ?>
            <div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <h1 style="font-size:20px; margin:0; color:var(--navy-900);"><?= e($patient) ?></h1>
                    <?php if ($ptMrn !== '' && $ptMrn !== '—'): ?>
                        <code style="font-weight:700; color:var(--teal-800); background:var(--teal-50); padding:3px 9px; border-radius:6px; font-size:13px;"><?= e($ptMrn) ?></code>
                    <?php endif; ?>
                    <?= badge('Active Consultation', 'success') ?>
                </div>
                <div style="font-size:12.5px; color:var(--gray-600); margin-top:4px;">
                    Birthday: <strong><?= e($ptDob) ?></strong> (<?= e($ptAge) ?>) · Sex: <strong><?= e($ptRecord[5] ?? 'Not specified') ?></strong> · Attending: <strong><?= e(current_user_name('Doctor')) ?></strong> · <?= date('F j, Y') ?>
                </div>
            </div>
        </div>
        <div style="display:flex; gap:8px;">
            <a class="btn btn-secondary btn-sm" href="<?= e(url(['page' => 'patients', 'detail' => 'history', 'patient' => $patient])) ?>">
                <?= icon('history', 14) ?> <span>View Full History</span>
            </a>
        </div>
    </div>
</div>

<form class="record-form" method="post" action="index.php" enctype="application/x-www-form-urlencoded" id="clinicalRecordForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_record">
    <input type="hidden" name="patient" value="<?= e($patient) ?>">
    <input type="hidden" name="patient_id" value="<?= e($ptMrn ?: $ptId) ?>">
    <?php if ($isEditing): ?>
        <input type="hidden" name="record_id" value="<?= e($editRecId) ?>">
    <?php endif; ?>
    <input type="hidden" name="return" value="<?= e(url(['page' => $curPage, 'detail' => 'history', 'patient' => $patient])) ?>">

    <div>
        <!-- Consultation Notes & Follow-Up -->
        <div class="form-section">
            <h2>Consultation Notes &amp; Follow-Up</h2>
            <p class="muted" style="font-size:12px; margin:-4px 0 12px;">Entries recorded here automatically update the patient’s Medical History, Prescriptions, and Cashier Billing Statement (using the hospital Price List).</p>
            <div class="form-grid three">
                <?= field('Chief complaint *', 'complaint', "Describe the patient's primary concern", 'text', $complaint, ['required' => true]) ?>
                <?= select_field('Visit type', 'visit_type', ['Consultation', 'Follow-up', 'Emergency', 'New patient', 'Post-op review', 'Routine checkup'], $visitType) ?>
                <?= field('Follow-up date (Optional)', 'follow_up', '', 'date', $followUp) ?>
            </div>
            <?= textarea_field('Clinical notes & physical examination *', 'notes', 'Document clinical observations, vitals, examination findings, and physician notes...', $notes, ['required' => true]) ?>
        </div>

        <!-- Unified Clinical Tabs (Diagnosis, Prescriptions, Lab Orders) -->
        <div class="form-section clinical-tabs" style="padding: 0; overflow: hidden;">
            <div class="tabs" style="padding: 0 20px; background: var(--gray-50);">
                <button type="button" class="tab-btn active" id="btn-tab-diag" onclick="switchClinicalTab('diag')">
                    <?= icon('clipboard', 16) ?> <span style="margin-left: 6px;">Diagnosis (ICD-10)</span>
                </button>
                <button type="button" class="tab-btn" id="btn-tab-rx" onclick="switchClinicalTab('rx')">
                    <?= icon('pill', 16) ?> <span style="margin-left: 6px;">Prescriptions</span>
                </button>
                <button type="button" class="tab-btn" id="btn-tab-labs" onclick="switchClinicalTab('labs')">
                    <?= icon('flask', 16) ?> <span style="margin-left: 6px;">Lab / Test Orders</span>
                </button>
            </div>

            <!-- TAB 1: Diagnosis Panel -->
            <div id="panel-tab-diag" class="repeat-form" style="display: block;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <div>
                        <strong style="font-size: 13px; color: var(--navy-900);">Clinical Diagnoses (ICD-10)</strong>
                        <p style="font-size: 11px; color: var(--gray-500); margin: 2px 0 0;">Add primary and secondary diagnoses for this consultation.</p>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addDiagnosisRow()">
                        <?= icon('plus', 14) ?> <span>Add diagnosis</span>
                    </button>
                </div>

                <div id="diag-rows-container" style="display: grid; gap: 12px;">
                    <?php foreach ($diagnoses as $dIdx => $d): ?>
                        <div class="diag-row" style="background: var(--gray-50); padding: 12px; border-radius: 8px; border: 1px solid var(--gray-200);">
                            <?= field('ICD-10 code', 'icd10[]', 'e.g. I10', 'text', $d[0] ?? '') ?>
                            <?= field('Diagnosis description', 'diagnosis[]', 'e.g. Essential (primary) hypertension', 'text', $d[1] ?? '') ?>
                            <button type="button" class="btn btn-secondary" onclick="removeClinicalRow(this)" title="Remove diagnosis" style="height: 38px; width: 38px; padding: 0; display: grid; place-items: center; color: var(--red); border-color: var(--gray-300);">
                                <?= icon('close', 15) ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- TAB 2: Prescriptions Panel -->
            <div id="panel-tab-rx" class="repeat-form" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <div>
                        <strong style="font-size: 13px; color: var(--navy-900);">Medication Prescriptions</strong>
                        <p style="font-size: 11px; color: var(--gray-500); margin: 2px 0 0;">Prescriptions entered here reflect in the patient's Prescriptions portal and Cashier Bill based on the hospital Price List.</p>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addPrescriptionRow()">
                        <?= icon('plus', 14) ?> <span>Add prescription</span>
                    </button>
                </div>

                <div id="rx-rows-container" style="display: grid; gap: 12px;">
                    <?php foreach ($prescriptions as $pIdx => $p): ?>
                        <div class="rx-row" style="background: var(--gray-50); padding: 12px; border-radius: 8px; border: 1px solid var(--gray-200);">
                            <?= select_field('Medication', 'medication[]', $medOpts, $p[0] ?? '') ?>
                            <?= field('Dosage', 'dosage[]', 'e.g. 10 mg', 'text', $p[1] ?? '') ?>
                            <?= select_field('Frequency', 'frequency[]', ['Once daily', 'Twice daily', 'Three times daily', 'Every 8 hours', 'Every 12 hours', 'Before bedtime', 'As needed'], $p[2] ?? 'Once daily') ?>
                            <?= field('Duration', 'duration[]', 'e.g. 30 days', 'text', $p[3] ?? '30 days') ?>
                            <button type="button" class="btn btn-secondary" onclick="removeClinicalRow(this)" title="Remove prescription" style="height: 38px; width: 38px; padding: 0; display: grid; place-items: center; color: var(--red); border-color: var(--gray-300);">
                                <?= icon('close', 15) ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- TAB 3: Lab Tests Panel -->
            <div id="panel-tab-labs" class="repeat-form" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <div>
                        <strong style="font-size: 13px; color: var(--navy-900);">Laboratory &amp; Diagnostic Orders</strong>
                        <p style="font-size: 11px; color: var(--gray-500); margin: 2px 0 0;">Ordered tests are added to the patient's record and itemized on the Cashier Bill using the hospital Price List.</p>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addLabRow()">
                        <?= icon('plus', 14) ?> <span>Add lab test</span>
                    </button>
                </div>

                <div id="lab-rows-container" style="display: grid; gap: 12px;">
                    <?php foreach ($labOrders as $lIdx => $l): ?>
                        <div class="lab-row" style="background: var(--gray-50); padding: 12px; border-radius: 8px; border: 1px solid var(--gray-200);">
                            <?= select_field('Test name', 'test[]', $testOpts, $l[0] ?? '') ?>
                            <?= select_field('Priority', 'priority[]', ['Routine', 'Urgent', 'STAT'], $l[1] ?? 'Routine') ?>
                            <?= field('Clinical instructions / notes', 'lab_clinical_notes[]', 'e.g. Fasting 10 hrs prior', 'text', $l[2] ?? '') ?>
                            <button type="button" class="btn btn-secondary" onclick="removeClinicalRow(this)" title="Remove lab order" style="height: 38px; width: 38px; padding: 0; display: grid; place-items: center; color: var(--red); border-color: var(--gray-300);">
                                <?= icon('close', 15) ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 20px;">
            <button type="submit" name="record_status" value="Draft" class="btn btn-secondary">
                <?= icon('clipboard', 16) ?>
                <span>Save as Draft</span>
            </button>
            <button type="submit" name="record_status" value="Completed" class="btn btn-primary">
                <?= icon('check', 16) ?>
                <span>Complete Consultation &amp; Generate Charges</span>
            </button>
        </div>
    </div>
</form>

<script>
function switchClinicalTab(tabKey) {
    var tabs = ['diag', 'rx', 'labs'];
    for (var i = 0; i < tabs.length; i++) {
        var k = tabs[i];
        var btn = document.getElementById('btn-tab-' + k);
        var pnl = document.getElementById('panel-tab-' + k);
        if (btn && pnl) {
            if (k === tabKey) {
                btn.classList.add('active');
                pnl.style.display = 'block';
            } else {
                btn.classList.remove('active');
                pnl.style.display = 'none';
            }
        }
    }
}

function removeClinicalRow(btn) {
    var row = btn.closest('.diag-row, .rx-row, .lab-row');
    if (!row) return;
    var container = row.parentElement;
    if (container.children.length > 1) {
        row.remove();
    } else {
        var inputs = row.querySelectorAll('input, select');
        for (var i = 0; i < inputs.length; i++) {
            if (inputs[i].tagName === 'SELECT') {
                inputs[i].selectedIndex = 0;
            } else {
                inputs[i].value = '';
            }
        }
    }
}

function addDiagnosisRow() {
    var container = document.getElementById('diag-rows-container');
    var first = container.querySelector('.diag-row');
    if (!first) return;
    var clone = first.cloneNode(true);
    var inputs = clone.querySelectorAll('input');
    for (var i = 0; i < inputs.length; i++) inputs[i].value = '';
    container.appendChild(clone);
}

function addPrescriptionRow() {
    var container = document.getElementById('rx-rows-container');
    var first = container.querySelector('.rx-row');
    if (!first) return;
    var clone = first.cloneNode(true);
    var inputs = clone.querySelectorAll('input, select');
    for (var i = 0; i < inputs.length; i++) {
        if (inputs[i].tagName === 'SELECT') inputs[i].selectedIndex = 0;
        else inputs[i].value = '';
    }
    container.appendChild(clone);
}

function addLabRow() {
    var container = document.getElementById('lab-rows-container');
    var first = container.querySelector('.lab-row');
    if (!first) return;
    var clone = first.cloneNode(true);
    var inputs = clone.querySelectorAll('input, select');
    for (var i = 0; i < inputs.length; i++) {
        if (inputs[i].tagName === 'SELECT') inputs[i].selectedIndex = 0;
        else inputs[i].value = '';
    }
    container.appendChild(clone);
}
</script>
