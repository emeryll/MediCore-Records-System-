<?php
$patientName = trim((string) ($_GET['patient'] ?? ''));
$tab = (string) ($_GET['tab'] ?? 'Appointments');
if (!in_array($tab, ['Appointments', 'Admissions'], true)) {
    $tab = 'Appointments';
}

// Find patient in session state
$patient = null;
$patientIdx = 0;
foreach ($state['patients'] as $idx => $p) {
    if ($p[0] === $patientName) {
        $patient = $p;
        $patientIdx = $idx + 1;
        break;
    }
}
$patient ??= $state['patients'][0] ?? null;
if ($patient === null): ?>
    <a class="back-link" href="<?= e(url(['page' => 'patients'])) ?>">← Back to patients</a>
    <?= filtered_empty('Patient record not found.') ?>
<?php return; endif;
$patientName = $patient[0];

// Compute age from birthdate if possible
$bdateFormatted = format_birthdate($patient[1] ?? '');
$ageText        = patient_age_label($bdateFormatted, false, 'Age not specified');
$ptId           = $patient[8] ?? ('P-' . str_pad((string) (1000 + $patientIdx), 4, '0', STR_PAD_LEFT));

// Filter live appointments, admissions/discharges, and bills for this patient
$ptAppointments = array_values(array_filter(
    $state['appointments'],
    static fn ($a) => $a[1] === $patientName
));
$ptAdmissions = array_values(array_filter(
    $state['admissions'],
    static fn ($a) => $a[0] === $patientName
));
$ptDischarges = array_values(array_filter(
    $state['discharges'],
    static fn ($d) => $d[0] === $patientName
));
$ptBills = array_values(array_filter(
    $state['bills'],
    static fn ($b) => $b[0] === $patientName
));
?>
<a class="back-link" href="<?= e(url(['page' => 'patients'])) ?>">← Back to patients</a>

<div class="profile-hero">
    <div class="large-avatar"><?= e($patient[4] ?? initials($patientName)) ?></div>
    <div>
        <?= badge('Active patient', 'success') ?>
        <h1><?= e($patientName) ?></h1>
        <p>Patient ID <strong><?= e($ptId) ?></strong> · Birthday: <strong><?= e($bdateFormatted) ?></strong> · Age: <strong><?= e($ageText) ?></strong></p>
    </div>
    <?= button('Edit details', ['variant' => 'secondary', 'icon' => 'edit', 'href' => url(['page' => 'patients', 'detail' => 'profile', 'patient' => $patientName, 'modal' => 'Edit patient'])]) ?>
</div>

<div class="profile-grid">
    <div class="panel details-panel">
        <h2>Patient details</h2>
        <dl>
            <div><dt>Birthday</dt><dd><?= e($bdateFormatted) ?></dd></div>
            <div><dt>Age</dt><dd><?= e($ageText) ?></dd></div>
            <div><dt>Sex</dt><dd><?= e($patient[5] ?? 'Not specified') ?></dd></div>
            <div><dt>Phone</dt><dd><?= e($patient[2]) ?></dd></div>
            <div><dt>Insurance</dt><dd><?= e($patient[3]) ?></dd></div>
            <div><dt>Address</dt><dd><?= e($patient[7] ?? 'San Francisco, CA') ?></dd></div>
        </dl>
    </div>

    <div class="panel profile-records">
        <?= tabs(['Appointments', 'Admissions'], $tab) ?>
        <div class="record-list">
            <?php if ($tab === 'Appointments'): ?>
                <?php if ($ptAppointments): ?>
                    <?php foreach ($ptAppointments as $a): ?>
                        <div class="record-row">
                            <span class="record-icon"><?= icon('calendar') ?></span>
                            <div>
                                <strong><?= e($a[2]) ?> — <?= e($a[4]) ?></strong>
                                <p><?= e(($a[7] ?? date('M d, Y')) . ' at ' . $a[0] . ' · ' . ($a[5] ?? '—')) ?></p>
                            </div>
                            <?= badge($a[3], status_tone($a[3])) ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <?= icon('calendar', 28) ?>
                        <strong>No appointments yet</strong>
                        <p>No scheduled visits for <?= e($patientName) ?>.</p>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <?php if ($ptAdmissions || $ptDischarges): ?>
                    <?php foreach ($ptAdmissions as $adm): ?>
                        <div class="record-row">
                            <span class="record-icon"><?= icon('bed') ?></span>
                            <div>
                                <strong><?= e($adm[1]) ?> · <?= e($adm[2]) ?></strong>
                                <p>Admitted <?= e($adm[3]) ?> · Attending: <?= e($adm[4]) ?><?= !empty($adm[5]) ? ' · ' . e($adm[5]) : '' ?></p>
                            </div>
                            <?= badge('Admitted', 'info') ?>
                        </div>
                    <?php endforeach; ?>
                    <?php foreach ($ptDischarges as $dis): ?>
                        <div class="record-row">
                            <span class="record-icon"><?= icon('clipboard') ?></span>
                            <div>
                                <strong><?= e($dis[1]) ?> · <?= e($dis[2]) ?></strong>
                                <p>Discharged <?= e($dis[3]) ?></p>
                            </div>
                            <?= badge('Discharged', 'success') ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <?= icon('bed', 28) ?>
                        <strong>Admissions history</strong>
                        <p>No active or past admissions for <?= e($patientName) ?>.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
