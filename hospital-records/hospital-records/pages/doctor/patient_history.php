<?php
$visits = [
    ['March 20, 2025', 'Cardiology consultation', 'Dr. James Wilson', 'Hypertension, stage 1'],
    ['January 8, 2025', 'Annual examination', 'Dr. Mia Morgan', 'Routine health assessment'],
    ['October 14, 2024', 'Cardiology follow-up', 'Dr. Olivia Bennett', 'Essential hypertension'],
];
$open = isset($_GET['open']) ? (int) $_GET['open'] : 0;   // -1 = all collapsed
$base = ['page' => $_GET['page'] ?? 'patients', 'detail' => 'history'];
?>
<a class="back-link" href="<?= e(url(['page' => 'patients'])) ?>">← Back to patients</a>

<div class="patient-header">
    <div class="large-avatar">ER</div>
    <div>
        <h1>Eleanor Rigby</h1>
        <p>42 years old · Female · Patient #PT-001842</p>
    </div>
    <div class="clinical-chip"><span>Blood type</span><strong>O+</strong></div>
    <div class="allergy-box"><span>Allergies</span><strong>Penicillin, Latex</strong></div>
    <?= button('New medical record', ['href' => url(['page' => 'patients', 'detail' => 'new-record'])]) ?>
</div>

<div class="history-layout">
    <div class="panel">
        <div class="panel-head">
            <div>
                <h2>Visit history</h2>
                <p>Clinical notes and treatment history</p>
            </div>
        </div>

        <div class="visit-history">
            <?php foreach ($visits as $i => $v): $isOpen = $open === $i; ?>
                <div class="history-entry <?= $isOpen ? 'open' : '' ?>">
                    <a href="<?= e(url($base + ['open' => $isOpen ? -1 : $i])) ?>">
                        <span class="history-marker"><?= icon('clipboard', 16) ?></span>
                        <div>
                            <time><?= e($v[0]) ?></time>
                            <strong><?= e($v[1]) ?></strong>
                            <p><?= e($v[2]) ?></p>
                        </div>
                        <?= badge($i === 0 ? 'In progress' : 'Completed', $i === 0 ? 'info' : 'success') ?>
                        <?= icon('down', 17) ?>
                    </a>
                    <?php if ($isOpen): ?>
                        <div class="history-detail">
                            <div><span>Diagnosis</span><strong><?= e($v[3]) ?></strong><p>ICD-10: I10</p></div>
                            <div><span>Prescriptions</span><strong>Lisinopril 10 mg</strong><p>Once daily · 30 days</p></div>
                            <div><span>Lab tests</span><strong>Complete blood count</strong><p>Results within normal range</p></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <aside class="panel quick-summary">
        <h2>Clinical summary</h2>
        <dl>
            <div><dt>Primary physician</dt><dd>Dr. Olivia Bennett</dd></div>
            <div><dt>Last visit</dt><dd>Today, 08:30</dd></div>
            <div><dt>Active conditions</dt><dd><?= badge('Hypertension', 'warning') ?></dd></div>
            <div><dt>Current medication</dt><dd>Lisinopril 10 mg</dd></div>
        </dl>
    </aside>
</div>
