<?php
$tab = (string) ($_GET['tab'] ?? 'Appointments');
if (!in_array($tab, ['Appointments', 'Admissions', 'Bills'], true)) {
    $tab = 'Appointments';
}
$appointments = [
    ['Mar 20, 2025', 'Cardiology follow-up', 'Dr. Olivia Bennett', 'Confirmed'],
    ['Jan 08, 2025', 'Annual examination', 'Dr. Mia Morgan', 'Completed'],
];
?>
<a class="back-link" href="<?= e(url(['page' => 'patients'])) ?>">← Back to patients</a>

<div class="profile-hero">
    <div class="large-avatar">ER</div>
    <div>
        <?= badge('Active patient', 'success') ?>
        <h1>Eleanor Rigby</h1>
        <p>Patient ID #PT-001842 · 42 years old</p>
    </div>
    <?= notify_button('Edit details', 'Edit form opened', ['variant' => 'secondary', 'icon' => 'edit']) ?>
</div>

<div class="profile-grid">
    <div class="panel details-panel">
        <h2>Patient details</h2>
        <dl>
            <div><dt>Date of birth</dt><dd>April 18, 1982</dd></div>
            <div><dt>Sex</dt><dd>Female</dd></div>
            <div><dt>Phone</dt><dd>(415) 555-0192</dd></div>
            <div><dt>Blood type</dt><dd><?= badge('O positive', 'danger') ?></dd></div>
            <div><dt>Insurance</dt><dd>HIN-884-120</dd></div>
            <div><dt>Address</dt><dd>88 Willow Street, San Francisco</dd></div>
        </dl>
    </div>

    <div class="panel profile-records">
        <?= tabs(['Appointments', 'Admissions', 'Bills'], $tab) ?>
        <div class="record-list">
            <?php if ($tab === 'Appointments'): ?>
                <?php foreach ($appointments as $r): ?>
                    <div class="record-row">
                        <span class="record-icon"><?= icon('calendar') ?></span>
                        <div>
                            <strong><?= e($r[1]) ?></strong>
                            <p><?= e($r[0]) ?> · <?= e($r[2]) ?></p>
                        </div>
                        <?= badge($r[3], $r[3] === 'Completed' ? 'success' : 'info') ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <?= icon($tab === 'Bills' ? 'card' : 'bed', 28) ?>
                    <strong><?= e($tab) ?> history</strong>
                    <p>No active <?= e(strtolower($tab)) ?> to display.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
