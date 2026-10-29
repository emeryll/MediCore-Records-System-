<?php
$ptName  = current_user_name('Patient');
$records = $state['records'][$ptName] ?? [];
$open    = isset($_GET['open']) ? (int) $_GET['open'] : 0;
$mob     = $_GET['mobile'] ?? null;

// Lab results for this patient
$ptLabs = array_values(array_filter(
    $state['lab_results'],
    static fn ($lr) => $lr[1] === $ptName
));
?>
<?= page_title(
    'Medical History',
    'Read-only timeline of your past hospital visits, diagnoses, and lab results.',
    badge('Read-only medical record', 'neutral')
) ?>

<div class="panel">
    <div class="panel-head">
        <div>
            <h2>Visit timeline</h2>
            <p>Tap any visit below to view full diagnoses, clinical notes, and lab results</p>
        </div>
    </div>

    <div class="visit-history">
        <?php foreach ($records as $i => $v): $isOpen = ($open === $i); ?>
            <?php
            $primaryDiag = !empty($v['diagnoses'][0]) ? ($v['diagnoses'][0][1] ?: $v['diagnoses'][0][0]) : 'General assessment';
            ?>
            <div class="history-entry <?= $isOpen ? 'open' : '' ?>">
                <a href="<?= e(url(['page' => 'history', 'open' => $isOpen ? -1 : $i, 'mobile' => $mob])) ?>">
                    <span class="history-marker"><?= icon('clipboard', 16) ?></span>
                    <div>
                        <time><?= e($v['date']) ?> · <?= e($v['doctor']) ?></time>
                        <strong><?= e($v['visit_type']) ?> — <?= e($v['complaint']) ?></strong>
                        <p>Diagnosis: <?= e($primaryDiag) ?></p>
                    </div>
                    <?= badge('Completed', 'success') ?>
                    <?= icon('down', 17) ?>
                </a>

                <?php if ($isOpen): ?>
                    <div class="history-detail pt-history-detail">
                        <div>
                            <span>Chief complaint</span>
                            <strong><?= e($v['complaint'] ?: 'Routine visit') ?></strong>
                        </div>
                        <div>
                            <span>Doctor's clinical notes</span>
                            <p class="history-notes"><?= e($v['notes'] ?: 'No additional notes recorded.') ?></p>
                        </div>
                        <div>
                            <span>Diagnoses (ICD-10)</span>
                            <?php if (!empty($v['diagnoses'])): ?>
                                <ul class="history-list">
                                    <?php foreach ($v['diagnoses'] as $d): ?>
                                        <li><strong><?= e($d[1] ?: $d[0]) ?></strong><?= $d[0] ? ' <code>' . e($d[0]) . '</code>' : '' ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="muted">No formal diagnosis recorded.</p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span>Lab results & ordered panels</span>
                            <?php if (!empty($v['lab_orders']) || $ptLabs): ?>
                                <div class="pt-lab-inline-list">
                                    <?php foreach ($ptLabs as $lr): ?>
                                        <div class="pt-lab-inline-card">
                                            <div>
                                                <strong><?= e($lr[2]) ?> (<code><?= e($lr[0]) ?></code>)</strong>
                                                <small>Collected <?= e($lr[4]) ?> · WBC 7.2 · RBC 4.8 · Hemoglobin 13.8 g/dL (Normal)</small>
                                            </div>
                                            <?= badge($lr[3] === 'Done' ? 'Normal / Done' : $lr[3], $lr[3] === 'Done' ? 'success' : 'warning') ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="muted">No lab tests ordered for this visit.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (!$records): ?>
            <div class="empty-state">
                <?= icon('history', 28) ?>
                <strong>No medical history on file</strong>
            </div>
        <?php endif; ?>
    </div>
</div>
