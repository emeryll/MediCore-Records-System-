<?php
$patientParam = trim((string) ($_GET['patient'] ?? ''));
$curPage      = (string) ($_GET['page'] ?? 'my-appointments');
$editRecId    = trim((string) ($_GET['rec'] ?? ''));

// Resolve patient identity
$ptIdentity   = resolve_patient_identity($state, $patientParam);
$patient      = $ptIdentity['name'];
$ptId         = $ptIdentity['id'];
$ptRecord     = $ptIdentity['record'] ?? find_patient_record($state, $patient);
$ptDob        = format_birthdate($ptRecord[1] ?? '');
$ptAge        = patient_age_label($ptDob, false, '—');

// Pre-fill defaults or existing draft record
$complaint     = '';
$visitType     = 'Consultation';
$notes         = '';
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
                $diagnoses     = (array) ($r['diagnoses'] ?? []);
                $prescriptions = (array) ($r['prescriptions'] ?? []);
                $labOrders     = (array) ($r['lab_orders'] ?? []);
                break 2;
            }
        }
    }
}

if (!$isEditing) {
    // Pre-fill complaint from matching appointment if available
    foreach ($state['appointments'] as $appt) {
        if (matches_patient_identity($ptIdentity, $appt[1] ?? '', $appt[8] ?? null)) {
            $complaint = $appt[4] ?? '';
            $visitType = $appt[2] ?? 'Consultation';
            break;
        }
    }
}

// Fallback initial row if empty
if (!$diagnoses) {
    $diagnoses = [['', '']];
}
if (!$prescriptions) {
    $prescriptions = [['', '', 'Once daily', '30 days']];
}
if (!$labOrders) {
    $labOrders = [['', 'Routine']];
}

$medOpts = array_values(array_unique(array_merge(
    ['Select medication'],
    array_column($state['crud']['medications'] ?? [], 0),
    ['Lisinopril', 'Atorvastatin', 'Metformin', 'Amoxicillin', 'Paracetamol', 'Ibuprofen', 'Omeprazole', 'Amlodipine', 'Cetirizine', 'Azithromycin']
)));

$testOpts = [
    'Select test',
    'Complete blood count',
    'Lipid panel',
    'Metabolic panel',
    'Thyroid function',
    'Urinalysis',
    'Blood glucose',
    'Liver function test',
    'Chest X-Ray',
    'Electrocardiogram (ECG)',
];

$titlePrefix = $isEditing ? 'Edit medical record' : 'New medical record';
?>
<a class="back-link" href="<?= e(url(['page' => $curPage, 'detail' => 'history', 'patient' => $patient])) ?>">← Back to patient history</a>

<?= page_title(
    $titlePrefix . ' — ' . $patient,
    'Patient ID: ' . $ptId . ($ptDob !== '—' ? ' · Birthday: ' . $ptDob . ' · Age: ' . $ptAge : '') . ' · Attending: Dr. ' . e(current_user_name('Doctor')) . ' · ' . date('F j, Y')
) ?>

<form class="record-form" method="post" action="index.php" enctype="application/x-www-form-urlencoded" id="clinicalRecordForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_record">
    <input type="hidden" name="patient" value="<?= e($patient) ?>">
    <input type="hidden" name="patient_id" value="<?= e($ptId) ?>">
    <?php if ($isEditing): ?>
        <input type="hidden" name="record_id" value="<?= e($editRecId) ?>">
    <?php endif; ?>
    <input type="hidden" name="return" value="<?= e(url(['page' => $curPage, 'detail' => 'history', 'patient' => $patient])) ?>">

    <div>
        <!-- Consultation Notes -->
        <div class="form-section">
            <h2>Consultation notes</h2>
            <div class="form-grid">
                <?= field('Chief complaint', 'complaint', "Describe the patient's primary concern or reason for visit", 'text', $complaint) ?>
                <?= select_field('Visit type', 'visit_type', ['Consultation', 'Follow-up', 'Emergency', 'New patient', 'Post-op review', 'Routine checkup'], $visitType) ?>
            </div>
            <?= textarea_field('Visit notes', 'notes', 'Document clinical observations, examination findings, vitals, and physician notes...', $notes) ?>
        </div>

        <!-- Unified Clinical Tabs (Diagnosis, Prescriptions, Lab Orders) -->
        <div class="form-section clinical-tabs" style="padding: 0; overflow: hidden;">
            <div class="tabs" style="padding: 0 20px; background: var(--gray-50);">
                <button type="button" class="tab-btn active" id="btn-tab-diag" onclick="switchClinicalTab('diag')">
                    <?= icon('clipboard', 16) ?> <span style="margin-left: 6px;">Diagnosis</span>
                </button>
                <button type="button" class="tab-btn" id="btn-tab-rx" onclick="switchClinicalTab('rx')">
                    <?= icon('pill', 16) ?> <span style="margin-left: 6px;">Prescriptions</span>
                </button>
                <button type="button" class="tab-btn" id="btn-tab-labs" onclick="switchClinicalTab('labs')">
                    <?= icon('flask', 16) ?> <span style="margin-left: 6px;">Lab tests</span>
                </button>
            </div>

            <!-- TAB 1: Diagnosis Panel -->
            <div id="panel-tab-diag" class="repeat-form" style="display: block;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <div>
                        <strong style="font-size: 13px; color: var(--navy-900);">Clinical Diagnoses (ICD-10)</strong>
                        <p style="font-size: 11px; color: var(--gray-500); margin: 2px 0 0;">Add one or more primary and secondary diagnoses for this consultation.</p>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addDiagnosisRow()" style="padding: 6px 12px; font-size: 11px;">
                        <?= icon('plus', 14) ?> <span>Add diagnosis</span>
                    </button>
                </div>

                <div id="diag-rows-container" style="display: grid; gap: 12px;">
                    <?php foreach ($diagnoses as $dIdx => $d): ?>
                        <div class="form-grid diag-row" style="grid-template-columns: 140px 1fr auto; align-items: end; gap: 10px; background: var(--gray-50); padding: 12px; border-radius: 8px; border: 1px solid var(--gray-200);">
                            <?= field('ICD-10 code', 'icd10[]', 'e.g. I10', 'text', $d[0] ?? '') ?>
                            <?= field('Diagnosis description', 'diagnosis[]', 'e.g. Essential (primary) hypertension', 'text', $d[1] ?? '') ?>
                            <button type="button" class="btn btn-secondary" onclick="removeClinicalRow(this)" title="Remove diagnosis" style="height: 38px; width: 38px; padding: 0; display: grid; place-items: center; color: var(--red); border-color: var(--gray-300);">
                                <?= icon('trash', 15) ?>
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
                        <p style="font-size: 11px; color: var(--gray-500); margin: 2px 0 0;">Prescriptions entered here automatically reflect in the patient's active medication portal and database.</p>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addPrescriptionRow()" style="padding: 6px 12px; font-size: 11px;">
                        <?= icon('plus', 14) ?> <span>Add prescription</span>
                    </button>
                </div>

                <div id="rx-rows-container" style="display: grid; gap: 12px;">
                    <?php foreach ($prescriptions as $pIdx => $p): ?>
                        <div class="form-grid four rx-row" style="grid-template-columns: 2fr 1fr 1.5fr 1fr auto; align-items: end; gap: 10px; background: var(--gray-50); padding: 12px; border-radius: 8px; border: 1px solid var(--gray-200);">
                            <?= select_field('Medication', 'medication[]', $medOpts, $p[0] ?? '') ?>
                            <?= field('Dosage', 'dosage[]', 'e.g. 10 mg', 'text', $p[1] ?? '') ?>
                            <?= select_field('Frequency', 'frequency[]', ['Once daily', 'Twice daily', 'Three times daily', 'Every 8 hours', 'Every 12 hours', 'Before bedtime', 'As needed'], $p[2] ?? 'Once daily') ?>
                            <?= field('Duration', 'duration[]', 'e.g. 30 days', 'text', $p[3] ?? '30 days') ?>
                            <button type="button" class="btn btn-secondary" onclick="removeClinicalRow(this)" title="Remove prescription" style="height: 38px; width: 38px; padding: 0; display: grid; place-items: center; color: var(--red); border-color: var(--gray-300);">
                                <?= icon('trash', 15) ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- TAB 3: Lab Tests Panel -->
            <div id="panel-tab-labs" class="repeat-form" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <div>
                        <strong style="font-size: 13px; color: var(--navy-900);">Laboratory & Diagnostic Orders</strong>
                        <p style="font-size: 11px; color: var(--gray-500); margin: 2px 0 0;">Ordered tests are queued for lab collection and show up in the doctor and patient portals.</p>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addLabRow()" style="padding: 6px 12px; font-size: 11px;">
                        <?= icon('plus', 14) ?> <span>Add lab test</span>
                    </button>
                </div>

                <div id="lab-rows-container" style="display: grid; gap: 12px;">
                    <?php foreach ($labOrders as $lIdx => $l): ?>
                        <div class="form-grid lab-row" style="grid-template-columns: 2fr 1fr 2fr auto; align-items: end; gap: 10px; background: var(--gray-50); padding: 12px; border-radius: 8px; border: 1px solid var(--gray-200);">
                            <?= select_field('Test name', 'test[]', $testOpts, $l[0] ?? '') ?>
                            <?= select_field('Priority', 'priority[]', ['Routine', 'Urgent', 'STAT'], $l[1] ?? 'Routine') ?>
                            <?= field('Clinical instructions / notes', 'lab_clinical_notes[]', 'e.g. Fasting 10 hrs prior', 'text', $l[2] ?? '') ?>
                            <button type="button" class="btn btn-secondary" onclick="removeClinicalRow(this)" title="Remove lab order" style="height: 38px; width: 38px; padding: 0; display: grid; place-items: center; color: var(--red); border-color: var(--gray-300);">
                                <?= icon('trash', 15) ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 20px;">
            <button type="submit" name="record_status" value="Draft" class="btn btn-secondary">
                <?= icon('clipboard', 16) ?>
                <span>Save as draft</span>
            </button>
            <button type="submit" name="record_status" value="Completed" class="btn btn-primary">
                <?= icon('check', 16) ?>
                <span>Complete visit &amp; finalize</span>
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
        // Clear inputs instead of deleting the last row
        var inputs = row.querySelectorAll('input, select, textarea');
        for (var i = 0; i < inputs.length; i++) {
            if (inputs[i].tagName.toLowerCase() === 'select') {
                inputs[i].selectedIndex = 0;
            } else {
                inputs[i].value = '';
            }
        }
    }
}

function addDiagnosisRow() {
    var container = document.getElementById('diag-rows-container');
    var firstRow = container.querySelector('.diag-row');
    if (!firstRow) return;
    var clone = firstRow.cloneNode(true);
    var inputs = clone.querySelectorAll('input');
    inputs.forEach(function(inp) { inp.value = ''; });
    container.appendChild(clone);
}

function addPrescriptionRow() {
    var container = document.getElementById('rx-rows-container');
    var firstRow = container.querySelector('.rx-row');
    if (!firstRow) return;
    var clone = firstRow.cloneNode(true);
    clone.querySelectorAll('select').forEach(function(sel) { sel.selectedIndex = 0; });
    clone.querySelectorAll('input').forEach(function(inp) {
        if (inp.name === 'duration[]') {
            inp.value = '30 days';
        } else {
            inp.value = '';
        }
    });
    container.appendChild(clone);
}

function addLabRow() {
    var container = document.getElementById('lab-rows-container');
    var firstRow = container.querySelector('.lab-row');
    if (!firstRow) return;
    var clone = firstRow.cloneNode(true);
    clone.querySelectorAll('select').forEach(function(sel) { sel.selectedIndex = 0; });
    clone.querySelectorAll('input').forEach(function(inp) { inp.value = ''; });
    container.appendChild(clone);
}
</script>
