<?php
$ptName = current_user_name('Patient');
$prescriptions = [];
foreach ($state['records'][$ptName] ?? [] as $rec) {
    foreach ($rec['prescriptions'] ?? [] as $rx) {
        $prescriptions[] = [
            'med'       => $rx[0],
            'dosage'    => $rx[1],
            'frequency' => $rx[2],
            'duration'  => $rx[3],
            'doctor'    => $rec['doctor'],
            'date'      => $rec['date'],
        ];
    }
}
?>
<?= page_title(
    'Prescriptions',
    'Medications prescribed during your hospital visits (read-only).',
    badge(count($prescriptions) . ' active medication' . (count($prescriptions) !== 1 ? 's' : ''), 'success')
) ?>

<div class="pt-rx-grid">
    <?php foreach ($prescriptions as $rx): ?>
        <article class="panel pt-rx-card">
            <div class="pt-rx-card-head">
                <span class="pt-rx-icon-lg"><?= icon('pill', 22) ?></span>
                <div>
                    <h3><?= e($rx['med']) ?></h3>
                    <span>Prescribed <?= e($rx['date']) ?> by <?= e($rx['doctor']) ?></span>
                </div>
                <?= badge('Active', 'success') ?>
            </div>

            <dl class="pt-rx-specs">
                <div>
                    <dt>Dosage</dt>
                    <dd><?= e($rx['dosage']) ?></dd>
                </div>
                <div>
                    <dt>Frequency</dt>
                    <dd><?= e($rx['frequency']) ?></dd>
                </div>
                <div>
                    <dt>Duration</dt>
                    <dd><?= e($rx['duration']) ?></dd>
                </div>
            </dl>
        </article>
    <?php endforeach; ?>
</div>

<?php if (!$prescriptions): ?>
    <?= filtered_empty('You have no active prescriptions on file.') ?>
<?php endif; ?>
