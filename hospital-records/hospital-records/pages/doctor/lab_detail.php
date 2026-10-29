<?php
$testId  = trim((string) ($_GET['test'] ?? ''));
$labItem = null;
foreach ($state['lab_results'] as $lr) {
    if ($lr[0] === $testId) {
        $labItem = $lr;
        break;
    }
}
$labItem ??= $state['lab_results'][0] ?? null;
if ($labItem === null): ?>
    <a class="back-link" href="<?= e(url(['page' => 'lab-results'])) ?>">← Back to lab results</a>
    <?= filtered_empty('Lab result not found.') ?>
<?php return; endif;

$testId   = $labItem[0];
$patient  = $labItem[1];
$testName = $labItem[2];
$status   = $labItem[3];
$date     = $labItem[4];

$results = match ($testName) {
    'Lipid panel' => [
        ['Total cholesterol', '192', '< 200', 'mg/dL', 'Normal'],
        ['LDL cholesterol', '118', '< 130', 'mg/dL', 'Normal'],
        ['HDL cholesterol', '54', '> 40', 'mg/dL', 'Normal'],
        ['Triglycerides', '135', '< 150', 'mg/dL', 'Normal'],
    ],
    'Metabolic panel' => [
        ['Fasting glucose', '94', '70–99', 'mg/dL', 'Normal'],
        ['Sodium', '140', '136–145', 'mmol/L', 'Normal'],
        ['Potassium', '4.2', '3.5–5.1', 'mmol/L', 'Normal'],
        ['Creatinine', '0.9', '0.6–1.2', 'mg/dL', 'Normal'],
    ],
    'Thyroid function' => [
        ['TSH', '2.1', '0.4–4.0', 'mIU/L', 'Normal'],
        ['Free T4', '1.3', '0.8–1.8', 'ng/dL', 'Normal'],
        ['Free T3', '3.1', '2.3–4.2', 'pg/mL', 'Normal'],
    ],
    default => [
        ['White blood cells', '7.2', '4.5–11.0', '10³/μL', 'Normal'],
        ['Red blood cells', '4.8', '4.2–5.4', '10⁶/μL', 'Normal'],
        ['Hemoglobin', '13.8', '12.0–16.0', 'g/dL', 'Normal'],
        ['Platelets', '245', '150–450', '10³/μL', 'Normal'],
    ],
};
$ptRecord = find_patient_record($state, $patient);
$ptDob    = format_birthdate($ptRecord[1] ?? '');
$ptAge    = patient_age_label($ptDob, true, 'Active patient');
?>
<a class="back-link" href="<?= e(url(['page' => 'lab-results'])) ?>">← Back to lab results</a>

<?= page_title(
    $testName,
    'Test #' . $testId . ' · Collected ' . $date,
    '<button type="button" class="btn btn-secondary" onclick="window.print()">' . icon('printer', 17) . '<span>Print result</span></button>'
) ?>

<div class="lab-summary">
    <div><span>Patient</span><strong><?= e($patient) ?></strong><p><?= $ptDob !== '—' ? e('Birthday: ' . $ptDob . ' · Age: ' . $ptAge) : 'Active patient' ?></p></div>
    <div><span>Ordered by</span><strong><?= e(current_user_name('Doctor')) ?></strong><p>Laboratory</p></div>
    <div><span>Status</span><?= badge($status === 'Done' ? 'Completed' : $status, $status === 'Done' ? 'success' : 'warning') ?><p><?= e($date) ?></p></div>
</div>

<?= table_open(['Component', 'Result', 'Reference range', 'Unit', 'Flag']) ?>
<?php foreach ($results as $r): ?>
    <tr>
        <td><strong><?= e($r[0]) ?></strong></td>
        <td><strong><?= e($r[1]) ?></strong></td>
        <td><?= e($r[2]) ?></td>
        <td><?= e($r[3]) ?></td>
        <td><?= badge($r[4], 'success') ?></td>
    </tr>
<?php endforeach; ?>
<?= table_close(count($results) . ' components', false) ?>

<?php if ($status !== 'Done'): ?>
    <form class="panel lab-note" method="post" action="index.php" style="margin-top:18px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="complete_lab">
        <input type="hidden" name="test_id" value="<?= e($testId) ?>">
        <input type="hidden" name="return" value="<?= e(url(['page' => 'lab-results', 'detail' => 'lab', 'test' => $testId])) ?>">
        <h2>Complete laboratory findings</h2>
        <p style="font-size:12px; color:var(--gray-600); margin:4px 0 12px;">Review measured components and submit your clinical interpretation to mark this test completed.</p>
        <?= textarea_field('Physician / Laboratory clinical note', 'clinical_notes', 'Enter clinical assessment...', !empty($labItem[7]) ? $labItem[7] : 'All measured components for ' . $patient . '\'s ' . strtolower($testName) . ' are within normal reference limits. No acute clinical intervention required.') ?>
        <div style="margin-top:14px;">
            <button type="submit" class="btn btn-primary">
                <?= icon('check', 16) ?> <span>Mark test completed</span>
            </button>
        </div>
    </form>
<?php else: ?>
    <div class="panel lab-note">
        <h2>Clinical note</h2>
        <p><?= e(!empty($labItem[7]) ? $labItem[7] : 'All measured components for ' . $patient . '\'s ' . strtolower($testName) . ' are within the normal reference range. No immediate follow-up is required.') ?></p>
    </div>
<?php endif; ?>
