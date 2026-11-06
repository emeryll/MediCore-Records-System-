<?php
declare(strict_types=1);

$idInfo        = resolve_patient_identity($state);
$ptName        = $idInfo['name'];
$ptId          = $idInfo['id'];
$prescriptions = patient_prescriptions($state, $ptName, $ptId);
$paged         = paginate_rows($prescriptions, 6, 'p');
?>
<?= page_title(
    'Prescriptions',
    'Medications prescribed by your doctors during hospital visits (read-only).',
    badge(count($prescriptions) . ' active medication' . (count($prescriptions) !== 1 ? 's' : ''), 'success')
) ?>

<?php if ($paged['rows']): ?>
    <div class="pt-rx-grid" style="margin-bottom:18px;">
        <?php foreach ($paged['rows'] as $rx): ?>
            <article class="panel pt-rx-card">
                <div class="pt-rx-card-head">
                    <span class="pt-rx-icon-lg"><?= icon('pill', 22) ?></span>
                    <div>
                        <h3><?= e($rx['med'] ?? ($rx['medication'] ?? 'Medication')) ?></h3>
                        <span>Prescribed <?= e($rx['date'] ?? ($rx['prescribed_date'] ?? 'Recently')) ?> by <?= e($rx['doctor'] ?? ($rx['doctor_name'] ?? 'Attending Physician')) ?></span>
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
    <div class="table-card">
        <?= pagination_bar('prescription', $paged['total'], $paged['page'], $paged['pages'], 'p') ?>
    </div>
<?php else: ?>
    <?= filtered_empty('You have no active prescriptions on file.') ?>
<?php endif; ?>
