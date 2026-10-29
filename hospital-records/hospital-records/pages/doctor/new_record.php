<?php
$patient   = trim((string) ($_GET['patient'] ?? ''));
$curPage   = (string) ($_GET['page'] ?? 'my-appointments');
$tab       = in_array($_GET['tab'] ?? '', ['Diagnosis', 'Prescriptions', 'Lab tests'], true)
           ? $_GET['tab'] : 'Diagnosis';

// Pre-fill complaint from the matching appointment
$complaint = '';
foreach ($state['appointments'] as $appt) {
    if ($appt[1] === $patient) {
        $complaint = $appt[4] ?? '';
        break;
    }
}
$ptRecord = find_patient_record($state, $patient);
$ptId     = $ptRecord[8] ?? 'P-1001';
$ptDob    = format_birthdate($ptRecord[1] ?? '');
$ptAge    = patient_age_label($ptDob, false, '—');
?>
<a class="back-link" href="<?= e(url(['page' => $curPage])) ?>">← Back to appointments</a>

<?= page_title(
    $patient !== '' ? 'New medical record — ' . $patient : 'New medical record',
    'Patient ID: ' . $ptId . ' · Birthday: ' . $ptDob . ' · Age: ' . $ptAge . ' · Consultation on ' . date('F j, Y')
) ?>

<form class="record-form" method="post" action="index.php" enctype="application/x-www-form-urlencoded">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_record">
    <input type="hidden" name="patient" value="<?= e($patient) ?>">
    <input type="hidden" name="return" value="<?= e(url(['page' => $curPage])) ?>">

    <div>
        <div class="form-section">
            <h2>Consultation notes</h2>
            <div class="form-grid">
                <?= field('Chief complaint', 'complaint', "Describe the patient's primary concern", 'text', $complaint) ?>
                <?= select_field('Visit type', 'visit_type', ['Follow-up', 'Consultation', 'Emergency', 'New patient', 'Post-op review']) ?>
            </div>
            <?= textarea_field('Visit notes', 'notes', 'Document symptoms, examination findings, and clinical observations...') ?>
        </div>

        <div class="form-section clinical-tabs">
            <?= tabs(['Diagnosis', 'Prescriptions', 'Lab tests'], $tab) ?>

            <?php if ($tab === 'Diagnosis'): ?>
                <div class="repeat-form">
                    <div class="form-grid">
                        <?= field('ICD-10 code', 'icd10[]', 'e.g. I10') ?>
                        <?= field('Description', 'diagnosis[]', 'Search diagnosis description') ?>
                    </div>
                    <small class="field-hint">Switch to Prescriptions or Lab tests tab to add those as well.</small>
                </div>
            <?php elseif ($tab === 'Prescriptions'): ?>
                <?php
                $medOpts = array_values(array_unique(array_merge(
                    ['Select medication'],
                    array_column($state['crud']['medications'] ?? [], 0),
                    ['Lisinopril', 'Atorvastatin', 'Metformin', 'Amoxicillin', 'Paracetamol', 'Ibuprofen']
                )));
                ?>
                <div class="repeat-form">
                    <div class="form-grid four">
                        <?= select_field('Medication', 'medication[]', $medOpts) ?>
                        <?= field('Dosage', 'dosage[]', '10 mg') ?>
                        <?= select_field('Frequency', 'frequency[]', ['Once daily', 'Twice daily', 'Three times daily', 'As needed']) ?>
                        <?= field('Duration', 'duration[]', '30 days') ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="repeat-form">
                    <div class="form-grid">
                        <?= select_field('Test name', 'test[]', ['Select test', 'Complete blood count', 'Lipid panel', 'Metabolic panel', 'Thyroid function', 'Urinalysis', 'Blood glucose']) ?>
                        <?= select_field('Priority', 'priority[]', ['Routine', 'Urgent']) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" name="record_status" value="Draft" class="btn btn-secondary">
                <span>Save draft</span>
            </button>
            <button type="submit" name="record_status" value="Completed" class="btn btn-primary">
                <span>Complete visit</span>
            </button>
        </div>
    </div>
</form>
