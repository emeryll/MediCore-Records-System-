<?php
$tab = (string) ($_GET['tab'] ?? 'Diagnosis');
if (!in_array($tab, ['Diagnosis', 'Prescriptions', 'Lab tests'], true)) {
    $tab = 'Diagnosis';
}
$curPage = (string) ($_GET['page'] ?? 'patients');
?>
<a class="back-link" href="<?= e(url(['page' => $curPage, 'detail' => 'history'])) ?>">← Back to patient history</a>

<?= page_title('New medical record', 'Eleanor Rigby · Visit on March 20, 2025') ?>

<div class="record-form">
    <div>
        <div class="form-section">
            <h2>Consultation notes</h2>
            <div class="form-grid">
                <?= field('Chief complaint', 'complaint', "Describe the patient's primary concern") ?>
                <?= select_field('Visit type', 'visit_type', ['Follow-up', 'Consultation', 'Emergency']) ?>
            </div>
            <?= textarea_field('Visit notes', 'notes', 'Document symptoms, examination findings, and clinical observations...') ?>
        </div>

        <div class="form-section clinical-tabs">
            <?= tabs(['Diagnosis', 'Prescriptions', 'Lab tests'], $tab) ?>

            <?php if ($tab === 'Diagnosis'): ?>
                <div class="repeat-form">
                    <div class="form-grid">
                        <?= field('ICD-10 code', 'icd10', 'e.g. I10') ?>
                        <?= field('Description', 'diagnosis', 'Search diagnosis description') ?>
                    </div>
                    <?= notify_button('Add another diagnosis', 'Diagnosis row added', ['variant' => 'secondary', 'icon' => 'plus']) ?>
                </div>
            <?php elseif ($tab === 'Prescriptions'): ?>
                <div class="repeat-form">
                    <div class="form-grid four">
                        <?= select_field('Medication', 'medication', ['Select medication', 'Lisinopril', 'Atorvastatin', 'Metformin']) ?>
                        <?= field('Dosage', 'dosage', '10 mg') ?>
                        <?= select_field('Frequency', 'frequency', ['Once daily', 'Twice daily', 'As needed']) ?>
                        <?= field('Duration', 'duration', '30 days') ?>
                    </div>
                    <?= notify_button('Add medication', 'Medication row added', ['variant' => 'secondary', 'icon' => 'plus']) ?>
                </div>
            <?php else: ?>
                <div class="repeat-form">
                    <div class="form-grid">
                        <?= select_field('Test name', 'test', ['Complete blood count', 'Lipid panel', 'Metabolic panel']) ?>
                        <?= select_field('Priority', 'priority', ['Routine', 'Urgent']) ?>
                    </div>
                    <?= notify_button('Order test', 'Lab test ordered', ['variant' => 'secondary', 'icon' => 'plus']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <?= notify_button('Save draft', 'Medical record saved as draft', ['variant' => 'secondary']) ?>
            <?= notify_button('Complete visit', 'Visit completed and record archived') ?>
        </div>
    </div>
</div>
