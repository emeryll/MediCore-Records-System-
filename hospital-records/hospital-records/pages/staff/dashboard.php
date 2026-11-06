<?php
declare(strict_types=1);

$appointments = array_slice($state['appointments'], 0, 6);
$totalAppts   = count($state['appointments']);
$checkedIn    = count(array_filter($state['appointments'], static fn ($a) => in_array($a[3], ['Checked-in', 'In consultation', 'In progress', 'Completed', 'Waiting'], true)));
$beds         = $state['beds'] ?? [];
$vacantBeds   = count(array_filter($beds, static fn ($b) => ($b['status'] ?? '') === 'Vacant' && !empty($b['active'])));
$totalBeds    = count(array_filter($beds, static fn ($b) => !empty($b['active'])));
$provCount    = count(array_filter($state['patients'], static fn ($p) => is_provisional_patient($p)));

$actions = '<div class="button-pair">'
    . button('Register patient', ['variant' => 'secondary', 'icon' => 'patient', 'href' => url(['page' => 'patients', 'modal' => 'Register patient'])])
    . button('Quick add', ['variant' => 'secondary', 'icon' => 'plus', 'href' => url(['page' => 'patients', 'modal' => 'Quick add provisional'])])
    . button('Book appointment', ['icon' => 'calendar', 'href' => url(['page' => 'book', 'step' => 1])])
    . '</div>';
?>
<?= page_title('Front Desk Dashboard', date('l, F j, Y') . ' · West Metro Medical Center patient flow & bed availability.', $actions) ?>

<div class="stats-grid four">
    <?= stat_card("Today's appointments", (string) $totalAppts, $checkedIn . ' checked in / in consultation', 'calendar') ?>
    <?= stat_card('Current admissions', (string) count($state['admissions']), 'Active inpatients in wards', 'clipboard', 'navy') ?>
    <?= stat_card('Available beds', (string) $vacantBeds, $totalBeds . ' total active hospital beds', 'bed', 'green') ?>
    <?= stat_card('Total patients', (string) count($state['patients']), $provCount . ' provisional awaiting check-in', 'patient', 'amber') ?>
</div>

<div class="panel">
    <div class="panel-head">
        <div>
            <h2>Today’s appointments &amp; check-in queue</h2>
            <p>Provisional patients are flagged so you can complete their full registration and issue an MRN upon arrival</p>
        </div>
        <?= button('All appointments', ['variant' => 'ghost', 'href' => url(['page' => 'appointments', 'view' => 'List'])]) ?>
    </div>
    <div class="schedule-list">
        <?php foreach ($appointments as $idx => $a):
            $ptRec  = find_patient_record($state, (string) ($a[1] ?? ''));
            $isProv = $ptRec ? is_provisional_patient($ptRec) : str_starts_with((string) ($a[8] ?? ''), 'PROV-');
            $mrnLbl = $ptRec ? patient_mrn_display($ptRec) : '';
        ?>
            <div class="schedule-row">
                <time><?= e($a[0]) ?></time>
                <span class="timeline-dot"></span>
                <div class="schedule-person">
                    <?= mini_avatar($a[1]) ?>
                    <div>
                        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                            <strong><?= e($a[1]) ?></strong>
                            <?php if ($isProv): ?>
                                <?= badge('Provisional', 'warning') ?>
                            <?php elseif ($mrnLbl !== ''): ?>
                                <code style="font-size:11px; color:var(--teal-700);"><?= e($mrnLbl) ?></code>
                            <?php endif; ?>
                        </div>
                        <span><?= e(($a[5] ?? 'Doctor') . ' · ' . $a[2] . ' · ' . ($a[7] ?? 'Today')) ?></span>
                    </div>
                </div>
                <?= badge($a[3], status_tone($a[3])) ?>
                <div style="display:flex; align-items:center; gap:8px;">
                    <?php if (in_array($a[3], ['Scheduled', 'Confirmed', 'Upcoming', 'Waiting'], true)): ?>
                        <?php if ($isProv): ?>
                            <?= button('Complete Reg & Check In', [
                                'variant' => 'primary',
                                'href'    => url(['page' => 'appointments', 'view' => 'List', 'modal' => 'Complete registration', 'patient' => $a[1], 'checkin_appt' => $idx]),
                            ]) ?>
                        <?php else: ?>
                            <?= post_button('Check in', 'checkin_appointment', ['idx' => (string) $idx], ['variant' => 'secondary']) ?>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a class="text-link" href="<?= e(url(['page' => 'appointments', 'view' => 'List', 'modal' => 'Appointment details', 'appt' => $idx])) ?>">Details</a>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$appointments): ?>
            <div class="empty-state" style="padding:36px 20px;">
                <?= icon('calendar', 28) ?>
                <strong>No appointments scheduled</strong>
                <p>Booked appointments will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
