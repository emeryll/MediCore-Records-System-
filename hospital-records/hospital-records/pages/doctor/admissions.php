<?php
declare(strict_types=1);

$q          = trim((string) ($_GET['q'] ?? ''));
$selPatient = trim((string) ($_GET['patient'] ?? ''));

$admissions = array_values(array_filter(
    $state['admissions'] ?? [],
    static fn ($r) => row_matches($r, $q)
));

$activeAdm = null;
foreach ($admissions as $adm) {
    if ($selPatient !== '' && strcasecmp((string) $adm[0], $selPatient) === 0) {
        $activeAdm = $adm;
        break;
    }
}
if (!$activeAdm && $admissions) {
    $activeAdm  = $admissions[0];
    $selPatient = (string) $activeAdm[0];
}
?>
<?= page_title(
    'Inpatient Admissions & Discharge Orders',
    'View admitted patients under your care, record progress notes and clinical orders, and mark patients For discharge with a discharge summary.'
) ?>

<?php if (!$admissions): ?>
    <?= filtered_empty('No currently admitted inpatients.') ?>
<?php else: ?>
<div class="grid-two" style="display:grid; grid-template-columns: 1fr 1.4fr; gap:18px; align-items:start;">
    <!-- Left List of Admitted Patients -->
    <div class="panel">
        <div class="panel-head">
            <div>
                <h2>Admitted Inpatients (<?= count($admissions) ?>)</h2>
                <p>Select a patient to add progress notes or order discharge</p>
            </div>
        </div>
        <div style="display:grid; gap:10px;">
            <?php foreach ($admissions as $adm):
                $isSel  = strcasecmp((string) $adm[0], $selPatient) === 0;
                $ptRec  = find_patient_record($state, (string) $adm[0]);
                $mrnStr = $ptRec ? patient_mrn_display($ptRec) : ($adm[8] ?? '');
                $aStat  = (string) ($adm[6] ?? 'Admitted');
            ?>
                <a href="<?= e(url(['page' => 'doctor-admissions', 'patient' => $adm[0]])) ?>"
                   style="display:block; text-decoration:none; color:inherit; padding:14px; border-radius:10px; border:2px solid <?= $isSel ? 'var(--teal-600)' : 'var(--gray-200)' ?>; background:<?= $isSel ? 'var(--teal-50)' : '#fff' ?>;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <strong style="font-size:14px; color:var(--navy-900);"><?= e($adm[0]) ?></strong>
                        <?= badge($aStat, status_tone($aStat)) ?>
                    </div>
                    <div style="font-size:12px; color:var(--gray-600);">
                        MRN: <code><?= e($mrnStr ?: '—') ?></code> · Bed: <strong><?= e($adm[1]) ?></strong> (<?= e($adm[2]) ?>)
                    </div>
                    <div class="muted" style="font-size:11.5px; margin-top:4px;">
                        Admitted: <?= e($adm[3]) ?> · Diagnosis: <?= e($adm[5] ?? 'Observation') ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Right Clinical Progress Notes & Discharge Order -->
    <?php if ($activeAdm):
        $ptName   = (string) $activeAdm[0];
        $ptRec    = find_patient_record($state, $ptName);
        $mrnStr   = $ptRec ? patient_mrn_display($ptRec) : ($activeAdm[8] ?? '');
        $aStat    = (string) ($activeAdm[6] ?? 'Admitted');
        $bClear   = (string) ($activeAdm[7] ?? 'Pending');
        $dSummary = (string) ($activeAdm[9] ?? '');
        $pNotes   = is_array($activeAdm[11] ?? null) ? $activeAdm[11] : [];
    ?>
        <div style="display:grid; gap:18px;">
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h2>Inpatient Chart — <?= e($ptName) ?></h2>
                        <p>MRN: <code><?= e($mrnStr ?: '—') ?></code> · Bed <strong><?= e($activeAdm[1]) ?></strong> (<?= e($activeAdm[2]) ?>) · Admitted <?= e($activeAdm[3]) ?></p>
                    </div>
                    <div style="display:flex; gap:6px;">
                        <?= badge($aStat, status_tone($aStat)) ?>
                        <?= badge('Billing: ' . $bClear, status_tone($bClear)) ?>
                    </div>
                </div>

                <div style="background:var(--gray-50); border:1px solid var(--gray-200); border-radius:8px; padding:12px 14px; margin-bottom:16px; font-size:12.5px;">
                    <div><strong>Admission Diagnosis:</strong> <?= e($activeAdm[5] ?? 'Inpatient care') ?></div>
                    <div><strong>Attending Physician:</strong> <?= e($activeAdm[4] ?? current_user_name('Doctor')) ?></div>
                </div>

                <h3 style="font-size:14px; margin:0 0 10px;">Progress Notes &amp; Doctor Orders</h3>
                <div style="display:grid; gap:8px; margin-bottom:14px;">
                    <?php foreach ($pNotes as $pn): ?>
                        <div style="padding:10px 12px; background:#fff; border:1px solid var(--gray-200); border-radius:8px; font-size:12.5px;">
                            <div style="display:flex; justify-content:space-between; color:var(--gray-500); font-size:11px; margin-bottom:4px;">
                                <strong><?= e($pn['author'] ?? current_user_name('Doctor')) ?></strong>
                                <span><?= e($pn['time'] ?? '') ?></span>
                            </div>
                            <div><?= e($pn['note'] ?? '') ?></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$pNotes): ?>
                        <p class="muted" style="font-size:12px;">No inpatient progress notes recorded yet.</p>
                    <?php endif; ?>
                </div>

                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_admission_note">
                    <input type="hidden" name="patient" value="<?= e($ptName) ?>">
                    <?= textarea_field('Add Progress Note / Inpatient Order *', 'progress_note', 'Enter daily rounds note, medication adjustment, or nursing order...', '') ?>
                    <button class="btn btn-secondary" type="submit" style="margin-top:8px;">
                        <?= icon('plus', 15) ?> <span>Add Progress Note</span>
                    </button>
                </form>
            </div>

            <div class="panel" style="border-color:var(--teal-200);">
                <div class="panel-head">
                    <div>
                        <h2>Mark Patient For Discharge &amp; Discharge Summary</h2>
                        <p>Authorizing discharge notifies Front Desk Staff and Cashier to finalize billing clearance and bed release</p>
                    </div>
                </div>

                <form method="post" action="index.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="order_discharge">
                    <input type="hidden" name="patient" value="<?= e($ptName) ?>">
                    <div class="form-grid">
                        <?= field('Final Discharge Diagnosis *', 'final_diagnosis', 'e.g. Community-acquired pneumonia, resolved', 'text', (string) ($activeAdm[5] ?? ''), ['required' => true]) ?>
                        <?= select_field('Recommended Disposition', 'discharge_type', ['Recovered / Routine', 'Home Against Medical Advice (HAMA)', 'Transferred to Another Facility'], 'Recovered / Routine') ?>
                    </div>
                    <?= textarea_field('Discharge Summary & Home Instructions *', 'discharge_summary', 'Summarize hospital course, condition on discharge, home medications, and follow-up date...', $dSummary ?: 'Patient clinically stable, afebrile, vital signs normal. Cleared for discharge with home medications.') ?>
                    <div style="margin-top:12px;">
                        <button class="btn btn-primary" type="submit">
                            <?= icon('check', 16) ?> <span><?= $aStat === 'For discharge' ? 'Update Discharge Summary' : 'Mark Patient "For Discharge"' ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>
