<?php
declare(strict_types=1);

$docName = current_user_name('Doctor');
$visits  = array_slice($state['appointments'], 0, 5);
$done    = count(array_filter($state['appointments'], static fn ($v) => ($v[3] ?? '') === 'Completed'));
$total   = count($state['appointments']);

$myAdmissions = $state['admissions'] ?? [];
$forDischarge = array_values(array_filter($myAdmissions, static fn ($a) => ($a[6] ?? 'Admitted') === 'For discharge'));

$actions = '<div class="button-pair">'
    . button('My Schedule', ['variant' => 'secondary', 'icon' => 'calendar', 'href' => url(['page' => 'my-schedule'])])
    . button('Inpatient Admissions', ['variant' => 'secondary', 'icon' => 'bed', 'href' => url(['page' => 'doctor-admissions'])])
    . '</div>';
?>
<?= page_title('Good day, ' . $docName, 'West Metro Medical Center · Clinical overview of today’s appointments and admitted inpatients.', $actions) ?>

<div class="stats-grid four">
    <?= stat_card("Today's appointments", (string) $total, $done . ' completed today', 'calendar') ?>
    <?= stat_card('Admitted patients', (string) count($myAdmissions), 'Currently under inpatient care', 'bed', 'navy') ?>
    <?= stat_card('Ready for discharge', (string) count($forDischarge), 'Marked For discharge', 'check', 'green') ?>
    <?= stat_card('Pending lab orders', (string) count(array_filter($state['lab_results'], static fn ($r) => ($r[3] ?? '') === 'Pending')), 'Awaiting results', 'flask', 'amber') ?>
</div>

<div class="grid-two">
    <div class="panel">
        <div class="panel-head">
            <div>
                <h2>Today's appointment timeline</h2>
                <p><?= date('l, F j, Y') ?></p>
            </div>
            <?= button('All appointments', ['variant' => 'ghost', 'href' => url(['page' => 'my-appointments'])]) ?>
        </div>

        <div class="doctor-timeline">
            <?php foreach ($visits as $i => $v):
                $ptRec  = find_patient_record($state, (string) ($v[1] ?? ''));
                $isProv = $ptRec ? is_provisional_patient($ptRec) : str_starts_with((string) ($v[8] ?? ''), 'PROV-');
            ?>
                <div class="doctor-visit">
                    <time><?= e($v[0]) ?></time>
                    <span class="visit-line <?= in_array($v[3], ['In consultation', 'In progress'], true) ? 'now' : '' ?>"><i></i></span>
                    <div class="visit-card">
                        <div class="schedule-person">
                            <?= mini_avatar($v[1]) ?>
                            <div>
                                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                    <strong><?= e($v[1]) ?></strong>
                                    <?php if ($isProv): ?>
                                        <?= badge('Provisional', 'warning') ?>
                                    <?php endif; ?>
                                </div>
                                <span><?= e($v[2]) ?><?= !empty($v[4]) ? ' · ' . e($v[4]) : '' ?></span>
                            </div>
                        </div>
                        <?= badge($v[3], status_tone($v[3])) ?>
                        <?php if ($v[3] === 'Completed'): ?>
                            <?= button('View record', ['variant' => 'secondary', 'href' => url(['page' => 'my-appointments', 'detail' => 'history', 'patient' => $v[1]])]) ?>
                        <?php elseif (in_array($v[3], ['In consultation', 'In progress'], true)): ?>
                            <?= button('Consult', ['href' => url(['page' => 'my-appointments', 'detail' => 'new-record', 'patient' => $v[1]])]) ?>
                        <?php elseif ($isProv): ?>
                            <span class="muted" style="font-size:11px;">Awaiting Front Desk Check-in</span>
                        <?php else: ?>
                            <?= post_button('Start consultation', 'start_consultation', ['patient' => $v[1]]) ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$visits): ?>
                <div class="empty-state" style="padding:36px 20px;">
                    <?= icon('calendar', 28) ?>
                    <strong>No appointments scheduled for today</strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <div>
                <h2>Admitted Inpatients &amp; Discharges</h2>
                <p>Patients currently admitted under your care</p>
            </div>
            <?= button('Manage admissions', ['variant' => 'ghost', 'href' => url(['page' => 'doctor-admissions'])]) ?>
        </div>
        <div style="display:grid; gap:10px;">
            <?php foreach ($myAdmissions as $adm):
                $aStat = (string) ($adm[6] ?? 'Admitted');
            ?>
                <div style="padding:12px 14px; border:1px solid var(--gray-200); border-radius:10px; background:var(--gray-50); display:flex; justify-content:space-between; align-items:center; gap:10px;">
                    <div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <strong><?= e($adm[0]) ?></strong>
                            <?= badge($adm[1], 'info') ?>
                            <?= badge($aStat, status_tone($aStat)) ?>
                        </div>
                        <p class="muted" style="font-size:11.5px; margin:3px 0 0;"><?= e($adm[2]) ?> · Admitted <?= e($adm[3]) ?> · <?= e($adm[5] ?? 'Observation') ?></p>
                    </div>
                    <?= button('Open', ['variant' => 'secondary', 'href' => url(['page' => 'doctor-admissions', 'patient' => $adm[0]])]) ?>
                </div>
            <?php endforeach; ?>
            <?php if (!$myAdmissions): ?>
                <div class="empty-state" style="padding:28px 16px;">
                    <?= icon('bed', 24) ?>
                    <strong>No active inpatient admissions</strong>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
