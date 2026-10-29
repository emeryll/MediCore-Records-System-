<?php
$patientParam = trim((string) ($_GET['patient'] ?? ($state['patients'][0][0] ?? 'Patient')));
$ptIdentity   = resolve_patient_identity($state, $patientParam);
$patient      = $ptIdentity['name'];
$ptId         = $ptIdentity['id'];
$records      = patient_medical_records($state, $patient, $ptId);
$ptLabs       = patient_lab_results($state, $patient, $ptId);
$open         = isset($_GET['open']) ? (int) $_GET['open'] : 0;
$curPage      = (string) ($_GET['page'] ?? 'my-appointments');
$base         = ['page' => $curPage, 'detail' => 'history', 'patient' => $patient];

// Clinical summary: aggregate conditions and meds from all records
$conditions = [];
$meds       = [];
foreach ($records as $rec) {
    foreach ($rec['diagnoses'] ?? [] as $d) {
        if (!empty($d[1])) {
            $conditions[] = $d[1];
        }
    }
    foreach ($rec['prescriptions'] ?? [] as $p) {
        $meds[] = $p[0] . ' ' . $p[1];
    }
}
$conditions = array_unique(array_slice($conditions, 0, 4));
$meds       = array_unique(array_slice($meds, 0, 4));
$ptRecord   = $ptIdentity['record'] ?? find_patient_record($state, $patient);
$ptDob      = format_birthdate($ptRecord[1] ?? '');
$ptAge      = patient_age_label($ptDob, false, '—');
$ptSex      = $ptRecord[5] ?? 'Not specified';
?>
<a class="back-link" href="<?= e(url(['page' => $curPage])) ?>">← Back to appointments</a>

<div class="patient-header">
    <div class="large-avatar"><?= e(initials($patient)) ?></div>
    <div>
        <h1><?= e($patient) ?></h1>
        <p>Patient ID <strong><?= e($ptId) ?></strong> · Birthday: <strong><?= e($ptDob) ?></strong> · Age: <strong><?= e($ptAge) ?></strong> · <?= count($records) ?> visit<?= count($records) !== 1 ? 's' : '' ?> on record</p>
    </div>
    <?= button('New medical record', ['href' => url(['page' => $curPage, 'detail' => 'new-record', 'patient' => $patient])]) ?>
</div>

<div class="history-layout">
    <div class="panel">
        <div class="panel-head">
            <div>
                <h2>Visit history</h2>
                <p>Clinical notes, prescriptions, and treatment history for <?= e($patient) ?></p>
            </div>
        </div>

        <div class="visit-history">
            <?php if (!$records): ?>
                <div style="padding:32px 20px; text-align:center; color:var(--gray-500);">
                    No clinical records yet. <a class="text-link" href="<?= e(url(['page' => $curPage, 'detail' => 'new-record', 'patient' => $patient])) ?>">Start a consultation</a>
                </div>
            <?php endif; ?>
            <?php foreach ($records as $i => $v): $isOpen = ($open === $i); ?>
                <div class="history-entry <?= $isOpen ? 'open' : '' ?>">
                    <a href="<?= e(url($base + ['open' => $isOpen ? -1 : $i])) ?>">
                        <span class="history-marker"><?= icon('clipboard', 16) ?></span>
                        <div>
                            <time><?= e($v['date'] ?? '') ?></time>
                            <strong><?= e($v['visit_type'] ?? 'Consultation') ?></strong>
                            <p><?= e($v['doctor'] ?? 'Physician') ?></p>
                        </div>
                        <?= badge(
                            $v['status'] ?? 'Completed',
                            match($v['status'] ?? '') { 'Completed' => 'success', 'Draft' => 'warning', default => 'info' }
                        ) ?>
                        <?= icon('down', 17) ?>
                    </a>
                    <?php if ($isOpen): ?>
                        <div class="history-detail">
                            <?php if (($v['status'] ?? '') === 'Draft'): ?>
                                <div style="display:flex; justify-content:space-between; align-items:center; background:#fef3c7; border:1px solid #fde68a; padding:10px 14px; border-radius:6px; margin-bottom:12px;">
                                    <span style="color:#92400e; font-size:12px; font-weight:600;">⚠️ This visit is saved as an in-progress draft.</span>
                                    <a class="btn btn-secondary" style="font-size:11px; padding:4px 10px; background:white;" href="<?= e(url(['page' => $curPage, 'detail' => 'new-record', 'patient' => $patient, 'rec' => $v['id']])) ?>">
                                        Continue editing draft →
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($v['complaint'])): ?>
                                <div>
                                    <span>Chief complaint</span>
                                    <strong><?= e($v['complaint']) ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($v['notes'])): ?>
                                <div>
                                    <span>Clinical notes</span>
                                    <p class="history-notes"><?= e($v['notes']) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($v['diagnoses'])): ?>
                                <div>
                                    <span>Diagnoses (ICD-10)</span>
                                    <ul class="history-list">
                                        <?php foreach ($v['diagnoses'] as $d): ?>
                                            <li><strong><?= e($d[1] ?: $d[0]) ?></strong><?= $d[0] ? ' <em>(' . e($d[0]) . ')</em>' : '' ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($v['prescriptions'])): ?>
                                <div>
                                    <span>Prescriptions</span>
                                    <ul class="history-list">
                                        <?php foreach ($v['prescriptions'] as $p): ?>
                                            <li><strong><?= e($p[0]) ?> <?= e($p[1]) ?></strong> — <?= e($p[2]) ?>, <?= e($p[3]) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($v['lab_orders'])): ?>
                                <div>
                                    <span>Lab orders</span>
                                    <ul class="history-list">
                                        <?php foreach ($v['lab_orders'] as $l): ?>
                                            <li><?= e($l[0]) ?> <span class="muted">(<?= e($l[1]) ?>)</span><?= !empty($l[2]) ? ' — <em>' . e($l[2]) . '</em>' : '' ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <aside class="panel quick-summary">
        <h2>Patient &amp; clinical summary</h2>
        <dl>
            <div><dt>Patient ID</dt><dd><code><?= e($ptId) ?></code></dd></div>
            <div><dt>Birthday</dt><dd><?= e($ptDob) ?></dd></div>
            <div><dt>Age</dt><dd><?= e($ptAge) ?></dd></div>
            <div><dt>Sex</dt><dd><?= e($ptSex) ?></dd></div>
            <?php $latest = $records[0] ?? null; ?>
            <div><dt>Last visit</dt><dd><?= $latest ? e($latest['date']) : '—' ?></dd></div>
            <div><dt>Attending doctor</dt><dd><?= $latest ? e($latest['doctor']) : '—' ?></dd></div>
            <?php if ($conditions): ?>
                <div>
                    <dt>Active conditions</dt>
                    <dd class="badge-stack">
                        <?php foreach ($conditions as $c): ?><?= badge($c, 'warning') ?> <?php endforeach; ?>
                    </dd>
                </div>
            <?php endif; ?>
            <?php if ($meds): ?>
                <div><dt>Current medications</dt><dd><?= e(implode(', ', $meds)) ?></dd></div>
            <?php endif; ?>
            <div><dt>Total visits</dt><dd><?= count($records) ?></dd></div>
            <div><dt>Lab tests ordered</dt><dd><?= count($ptLabs) ?></dd></div>
        </dl>
    </aside>
</div>
