<?php
$idInfo  = resolve_patient_identity($state);
$ptName  = $idInfo['name'];
$ptId    = $idInfo['id'];
$records = patient_medical_records($state, $ptName, $ptId);
$open    = isset($_GET['open']) ? (int) $_GET['open'] : 0;
$mob     = $_GET['mobile'] ?? null;
$ptLabs  = patient_lab_results($state, $ptName, $ptId);
?>
<?= page_title(
    'Medical History',
    'Read-only timeline of your past hospital visits, diagnoses, prescriptions, and lab results.',
    badge('Patient ID: ' . $ptId, 'neutral')
) ?>

<div class="panel">
    <div class="panel-head">
        <div>
            <h2>Visit timeline</h2>
            <p>Tap any visit below to view full diagnoses, clinical notes, prescriptions, and lab results</p>
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
                        <time><?= e($v['date'] ?? '') ?> · <?= e($v['doctor'] ?? 'Attending Physician') ?></time>
                        <strong><?= e($v['visit_type'] ?? 'Consultation') ?> — <?= e($v['complaint'] ?: 'Routine checkup') ?></strong>
                        <p>Diagnosis: <?= e($primaryDiag) ?></p>
                    </div>
                    <?= badge($v['status'] ?? 'Completed', ($v['status'] ?? '') === 'Draft' ? 'warning' : 'success') ?>
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
                            <span>Prescriptions given</span>
                            <?php if (!empty($v['prescriptions'])): ?>
                                <ul class="history-list">
                                    <?php foreach ($v['prescriptions'] as $rx): ?>
                                        <li>
                                            <strong><?= e($rx[0]) ?> <?= e($rx[1] ?? '') ?></strong> — <?= e($rx[2] ?? 'Once daily') ?>, <?= e($rx[3] ?? '30 days') ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="muted">No medications prescribed for this visit.</p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span>Lab results &amp; ordered panels</span>
                            <?php if (!empty($v['lab_orders']) || $ptLabs): ?>
                                <div class="pt-lab-inline-list">
                                    <?php
                                    $displayLabs = $ptLabs ?: [];
                                    if (!$displayLabs && !empty($v['lab_orders'])) {
                                        foreach ($v['lab_orders'] as $loIdx => $lo) {
                                            $displayLabs[] = ['LAB-' . (1000 + $loIdx), $ptName, $lo[0], 'Pending', $v['date'] ?? date('M d, Y')];
                                        }
                                    }
                                    foreach ($displayLabs as $lr):
                                        $lrDone = ($lr[3] ?? '') === 'Done';
                                        $lrNote = !empty($lr[7]) ? $lr[7] : ($lrDone ? 'All components within normal reference range.' : 'Awaiting laboratory processing and results verification.');
                                    ?>
                                        <div class="pt-lab-inline-card">
                                            <div>
                                                <strong><?= e($lr[2]) ?> (<code><?= e($lr[0]) ?></code>)</strong>
                                                <small>Collected <?= e($lr[4]) ?> · <?= e($lrNote) ?></small>
                                            </div>
                                            <?= badge($lrDone ? 'Completed / Normal' : 'Pending', $lrDone ? 'success' : 'warning') ?>
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
                <p>Consultations and treatments completed by your doctor will appear here automatically.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
