<?php
declare(strict_types=1);

$q          = trim((string) ($_GET['q'] ?? ''));
$selPatient = trim((string) ($_GET['patient'] ?? ''));
$view       = (string) ($_GET['view'] ?? ($selPatient !== '' ? 'chart' : 'list'));

$admissions = array_values(array_filter(
    $state['admissions'] ?? [],
    static fn ($r) => row_matches($r, $q)
));

/* =========================================================================
 * VIEW 1: INPATIENT CHART (When a patient is opened)
 * ========================================================================= */
if ($view === 'chart' && $selPatient !== ''):
    $activeAdm = null;
    foreach ($state['admissions'] ?? [] as $adm) {
        if (strcasecmp((string) ($adm['patient_name'] ?? ($adm[0] ?? '')), $selPatient) === 0) {
            $activeAdm = $adm;
            break;
        }
    }

    if (!$activeAdm) {
        flash('Admitted patient record not found for "' . $selPatient . '".');
        redirect(url(['page' => 'doctor-admissions']));
    }

    $ptName   = (string) ($activeAdm['patient_name'] ?? ($activeAdm[0] ?? $selPatient));
    $ptRec    = find_patient_record($state, $ptName);
    $mrnStr   = $ptRec ? patient_mrn_display($ptRec) : (string) ($activeAdm['mrn'] ?? ($activeAdm[8] ?? '—'));
    $aStat    = (string) ($activeAdm['status'] ?? ($activeAdm[6] ?? 'Admitted'));
    $bClear   = (string) ($activeAdm['billing_clearance'] ?? ($activeAdm[7] ?? 'Pending'));
    $dSummary = (string) ($activeAdm['discharge_order'] ?? ($activeAdm[9] ?? ''));
    $pNotes   = is_array($activeAdm['clinical_notes'] ?? null)
        ? $activeAdm['clinical_notes']
        : (is_array($activeAdm[11] ?? null) ? $activeAdm[11] : []);

    $admitDate = (string) ($activeAdm['admitted_date'] ?? ($activeAdm[3] ?? date('M d, Y')));
    $admitTs   = strtotime($admitDate) ?: time();
    $daysStay  = max(1, (int) ceil((time() - $admitTs) / 86400));
?>
<a class="back-link" href="<?= e(url(['page' => 'doctor-admissions'])) ?>" style="display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; margin-bottom:14px;">
    ← Back to Admitted Patients
</a>

<?= page_title(
    'Inpatient Chart — ' . $ptName,
    'MRN: ' . ($mrnStr ?: '—') . ' · Room ' . ($activeAdm['room_number'] ?? $activeAdm[1]) . ' (' . ($activeAdm['ward'] ?? $activeAdm[2]) . ') · Admitted: ' . $admitDate . ' (' . $daysStay . ' day' . ($daysStay === 1 ? '' : 's') . ' stay) · Attending: ' . e($activeAdm['doctor_name'] ?? ($activeAdm[4] ?? current_user_name('Doctor')))
) ?>

<!-- Patient Inpatient Summary Banner -->
<div class="panel" style="background:#fff; border-left:4px solid var(--teal-700); margin-bottom:20px; padding:18px 22px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <?= mini_avatar($ptName) ?>
            <div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <h2 style="font-size:20px; margin:0; color:var(--navy-900);"><?= e($ptName) ?></h2>
                    <?php if ($mrnStr !== '' && $mrnStr !== '—'): ?>
                        <code style="font-weight:700; color:var(--teal-800); background:var(--teal-50); padding:3px 9px; border-radius:6px; font-size:13px;"><?= e($mrnStr) ?></code>
                    <?php endif; ?>
                    <?= badge($aStat, status_tone($aStat)) ?>
                    <?= badge('Billing: ' . $bClear, status_tone($bClear)) ?>
                </div>
                <div style="font-size:13px; color:var(--gray-600); margin-top:5px;">
                    Bed: <strong><?= e($activeAdm['bed_label'] ?? 'Bed A') ?></strong> · Room: <strong><?= e($activeAdm['room_number'] ?? $activeAdm[1]) ?></strong> (<?= e($activeAdm['ward'] ?? $activeAdm[2]) ?>) · Admitted: <strong><?= e($admitDate) ?></strong>
                </div>
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn btn-secondary btn-sm" href="<?= e(url(['page' => 'patients', 'detail' => 'history', 'patient' => $ptName])) ?>">
                <?= icon('history', 14) ?> <span>View Full History</span>
            </a>
            <a class="btn btn-secondary btn-sm" href="<?= e(url(['page' => 'lab-results', 'patient' => $ptName])) ?>">
                <?= icon('flask', 14) ?> <span>View Lab Results</span>
            </a>
        </div>
    </div>
</div>

<div style="display:grid; gap:20px;">
    <!-- SECTION: Clinical Progress Notes -->
    <div class="panel">
        <div class="panel-head" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <h2 style="display:flex; align-items:center; gap:8px;">
                    <?= icon('clipboard', 18) ?>
                    <span>Clinical Progress Notes (<?= count($pNotes) ?>)</span>
                </h2>
                <p>Chronological daily rounds documentation, observations, and vital signs updates.</p>
            </div>
            <!-- SEPARATE BUTTON TO ADD / STORE PROGRESS NOTE -->
            <button type="button" class="btn btn-primary" onclick="toggleProgressNoteForm()" id="btnAddNoteToggle" style="display:inline-flex; align-items:center; gap:6px;">
                <?= icon('plus', 15) ?>
                <span>Add Progress Note</span>
            </button>
        </div>

        <!-- Inline Entry Form to Store Progress Notes -->
        <div id="progressNoteFormContainer" style="display:none; background:var(--teal-50); border:1px solid var(--teal-200); border-radius:10px; padding:16px 18px; margin-bottom:18px;">
            <form method="post" action="index.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_admission_note">
                <input type="hidden" name="patient" value="<?= e($ptName) ?>">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <strong style="font-size:14px; color:var(--teal-900);">New Clinical Progress Note</strong>
                    <span class="muted" style="font-size:11.5px;">Author: <?= e(current_user_name('Doctor')) ?> · <?= date('M d, Y · H:i') ?></span>
                </div>
                <?= textarea_field('Progress Note Content *', 'progress_note', 'Document daily rounds, vital signs, physical exam findings, patient response to therapy, and current clinical plan...', '', ['required' => true]) ?>
                <div style="display:flex; gap:8px; margin-top:12px;">
                    <button class="btn btn-primary" type="submit">
                        <?= icon('check', 14) ?> <span>Save Progress Note</span>
                    </button>
                    <button class="btn btn-secondary" type="button" onclick="toggleProgressNoteForm()">
                        <span>Cancel</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Display of All Progress Notes -->
        <div style="display:grid; gap:12px;">
            <?php foreach ($pNotes as $pn): ?>
                <div style="padding:14px 16px; background:#fff; border:1px solid var(--gray-200); border-left:4px solid var(--teal-600); border-radius:8px; font-size:13px; box-shadow:0 1px 2px rgba(0,0,0,0.03);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; flex-wrap:wrap; gap:8px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <strong style="color:var(--navy-900); font-size:13.5px;"><?= e($pn['author'] ?? current_user_name('Doctor')) ?></strong>
                            <span class="badge badge-neutral" style="font-size:10.5px; padding:2px 7px;">Progress Note</span>
                        </div>
                        <span style="color:var(--gray-500); font-size:11.5px;"><?= e($pn['time'] ?? '—') ?></span>
                    </div>
                    <div style="color:var(--gray-800); line-height:1.6; white-space:pre-wrap;"><?= e($pn['note'] ?? '') ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$pNotes): ?>
                <div class="filtered-empty" style="padding:28px 16px; text-align:center;">
                    <div style="color:var(--gray-400); margin-bottom:6px;"><?= icon('clipboard', 28) ?></div>
                    <strong style="color:var(--navy-900); font-size:13.5px;">No progress notes recorded yet</strong>
                    <p class="muted" style="font-size:12px; margin:4px 0 0;">Click the "Add Progress Note" button above to record daily rounds for <?= e($ptName) ?>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION: Discharge Planning & Order -->
    <div class="panel" style="border-color:var(--teal-200);">
        <div class="panel-head">
            <div>
                <h2>Mark Patient For Discharge &amp; Discharge Summary</h2>
                <p>Completing this discharge order notifies the Front Desk Staff and Cashier to finalize billing clearance and release the bed.</p>
            </div>
        </div>

        <form method="post" action="index.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="order_discharge">
            <input type="hidden" name="patient" value="<?= e($ptName) ?>">
            <div class="form-grid">
                <?= field('Final Discharge Diagnosis *', 'final_diagnosis', 'e.g. Community-acquired pneumonia, resolved', 'text', (string) ($activeAdm['final_diagnosis'] ?? ($activeAdm[5] ?? '')), ['required' => true]) ?>
                <?= select_field('Recommended Disposition', 'discharge_type', ['Recovered / Routine', 'Home Against Medical Advice (HAMA)', 'Transferred to Another Facility'], 'Recovered / Routine') ?>
            </div>
            <?= textarea_field('Discharge Summary & Home Instructions *', 'discharge_summary', 'Summarize hospital course, condition on discharge, home medications, and follow-up date...', $dSummary ?: 'Patient clinically stable, afebrile, vital signs normal. Cleared for discharge with home medications.') ?>
            <div style="margin-top:14px;">
                <button class="btn btn-primary" type="submit">
                    <?= icon('check', 16) ?>
                    <span><?= $aStat === 'For discharge' ? 'Update Discharge Summary' : 'Mark Patient "For Discharge"' ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleProgressNoteForm() {
    var form = document.getElementById('progressNoteFormContainer');
    if (!form) return;
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        var txt = form.querySelector('textarea');
        if (txt) txt.focus();
    } else {
        form.style.display = 'none';
    }
}
</script>

<?php
return;
endif;

/* =========================================================================
 * VIEW 2: ADMITTED INPATIENTS LIST (Main Admissions Page)
 * ========================================================================= */
$totalAdmitted  = count($state['admissions'] ?? []);
$forDischargeCt = 0;
foreach ($state['admissions'] ?? [] as $ad) {
    if (($ad['status'] ?? ($ad[6] ?? '')) === 'For discharge') {
        $forDischargeCt++;
    }
}
$pagedAdmissions = paginate_rows($admissions, 10, 'p');
?>

<?= page_title(
    'Admitted Inpatients',
    'View admitted patients currently under hospital care. Click any patient or "Inpatient Chart" to view their chart and record daily progress notes.'
) ?>

<!-- Search Bar for Admitted Patients -->
<div class="panel" style="margin-bottom:20px;">
    <div class="panel-head" style="border-bottom:0; padding-bottom:0;">
        <div>
            <h2>Search Admitted Inpatients</h2>
            <p>Search by patient name, MRN, room number, ward, or admission diagnosis</p>
        </div>
    </div>
    <form method="get" action="index.php" class="table-toolbar" style="padding-top:10px; margin-bottom:0; border-bottom:0;">
        <input type="hidden" name="page" value="doctor-admissions">
        <?= search_box('Search admitted patient by name, MRN, room (e.g. Room 101), ward, or diagnosis...', 'q', $q) ?>
        <button class="btn btn-primary" type="submit">
            <?= icon('search', 16) ?> <span>Search</span>
        </button>
        <?php if ($q !== ''): ?>
            <a class="btn btn-ghost" href="<?= e(url(['page' => 'doctor-admissions'])) ?>"><span>Reset</span></a>
        <?php endif; ?>
    </form>
</div>

<!-- Admitted Inpatients Table -->
<div class="panel">
    <div class="panel-head">
        <div>
            <h2 style="display:flex; align-items:center; gap:8px;">
                <?= icon('bed', 18) ?>
                <span>Currently Admitted Patients (<?= count($admissions) ?>)</span>
            </h2>
            <p>Active inpatient admissions. Click any patient to open their Inpatient Chart.</p>
        </div>
        <div style="display:flex; gap:8px; font-size:12px;">
            <span class="badge badge-info">Total Admitted: <?= $totalAdmitted ?></span>
            <?php if ($forDischargeCt > 0): ?>
                <span class="badge badge-warning">For Discharge: <?= $forDischargeCt ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>MRN</th>
                    <th>Room &amp; Bed</th>
                    <th>Ward</th>
                    <th>Admitted Date</th>
                    <th>Attending Physician</th>
                    <th>Admission Diagnosis</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagedAdmissions['rows'] as $adm):
                    $ptName   = (string) ($adm['patient_name'] ?? ($adm[0] ?? '—'));
                    $ptRec    = find_patient_record($state, $ptName);
                    $mrnStr   = $ptRec ? patient_mrn_display($ptRec) : (string) ($adm['mrn'] ?? ($adm[8] ?? '—'));
                    $aStat    = (string) ($adm['status'] ?? ($adm[6] ?? 'Admitted'));
                    $roomNo   = (string) ($adm['room_number'] ?? ($adm[1] ?? '—'));
                    $bedLbl   = (string) ($adm['bed_label'] ?? 'Bed A');
                    $ward     = (string) ($adm['ward'] ?? ($adm[2] ?? 'General Ward'));
                    $admitDt  = (string) ($adm['admitted_date'] ?? ($adm[3] ?? date('M d, Y')));
                    $chartUrl = url(['page' => 'doctor-admissions', 'view' => 'chart', 'patient' => $ptName]);
                ?>
                    <tr>
                        <td>
                            <div class="person-cell">
                                <?= mini_avatar($ptName) ?>
                                <div>
                                    <a href="<?= e($chartUrl) ?>" style="color:var(--navy-900); font-weight:700; text-decoration:none;">
                                        <?= e($ptName) ?>
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($mrnStr !== '' && $mrnStr !== '—'): ?>
                                <code style="font-weight:700; color:var(--teal-700); background:var(--teal-50); padding:3px 8px; border-radius:6px;"><?= e($mrnStr) ?></code>
                            <?php else: ?>
                                <span class="muted" style="font-size:11.5px;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= e($roomNo) ?></strong>
                            <div class="muted" style="font-size:11px;"><?= e($bedLbl) ?></div>
                        </td>
                        <td><?= e($ward) ?></td>
                        <td>
                            <strong><?= e($admitDt) ?></strong>
                        </td>
                        <td><?= e((string) ($adm['doctor_name'] ?? ($adm[4] ?? current_user_name('Doctor')))) ?></td>
                        <td>
                            <span class="complaint-text"><?= e((string) ($adm['reason'] ?? ($adm[5] ?? 'Inpatient observation'))) ?></span>
                        </td>
                        <td><?= badge($aStat, status_tone($aStat)) ?></td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="<?= e($chartUrl) ?>" title="Open Inpatient Chart for <?= e($ptName) ?>">
                                <?= icon('clipboard', 14) ?>
                                <span>Inpatient Chart</span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$pagedAdmissions['rows']): ?>
                    <tr>
                        <td colspan="9">
                            <div class="filtered-empty" style="padding:28px 16px;">
                                <div style="color:var(--gray-400); margin-bottom:6px;"><?= icon('bed', 28) ?></div>
                                <strong>No admitted patients found</strong>
                                <p class="muted" style="font-size:12px; margin:4px 0 0;">There are no patients matching your search query.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= table_close($pagedAdmissions['label'], true, $pagedAdmissions['pages']) ?>
</div>
