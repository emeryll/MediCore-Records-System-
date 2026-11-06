<?php
declare(strict_types=1);

$view = ($_GET['view'] ?? 'List') === 'Calendar' ? 'Calendar' : 'List';
$cat  = in_array($_GET['cat'] ?? '', ['Scheduled', 'Checked-in', 'In consultation', 'Completed', 'No-show / Cancelled'], true)
      ? $_GET['cat'] : 'All';
$q    = trim((string) ($_GET['q'] ?? ''));

$catMap = [
    'All'                 => static fn ($s) => true,
    'Scheduled'           => static fn ($s) => in_array($s, ['Scheduled', 'Confirmed', 'Upcoming', 'Waiting'], true),
    'Checked-in'          => static fn ($s) => $s === 'Checked-in',
    'In consultation'     => static fn ($s) => in_array($s, ['In consultation', 'In progress'], true),
    'Completed'           => static fn ($s) => $s === 'Completed',
    'No-show / Cancelled' => static fn ($s) => in_array($s, ['No-show', 'Cancelled'], true),
];

$allAppts = $state['appointments'];
$counts   = [];
foreach (array_keys($catMap) as $c) {
    $counts[$c] = count(array_filter($allAppts, static fn ($r) => $catMap[$c]($r[3] ?? '')));
}

$filteredEntries = [];
foreach ($allAppts as $idx => $r) {
    if ($catMap[$cat]($r[3] ?? '') && row_matches([$r[0], $r[1], $r[2], $r[4] ?? '', $r[7] ?? ''], $q)) {
        $filteredEntries[] = ['idx' => $idx, 'row' => $r];
    }
}
$paged = paginate_rows($filteredEntries, 10, 'p');

// Calendar setup
$week  = (int) ($_GET['week'] ?? 0);
$monTs = strtotime('monday this week ' . ($week >= 0 ? '+' . $week : (string) $week) . ' weeks');
$friTs = strtotime('+4 days', $monTs);
$days  = [];
foreach (['MON', 'TUE', 'WED', 'THU', 'FRI'] as $i => $d) {
    $days[] = [$d, (int) date('j', strtotime('+' . $i . ' days', $monTs))];
}
$rangeLabel = date('F j', $monTs) . ' – ' . date('j, Y', $friTs);
?>
<?= page_title(
    'My Appointments',
    'View appointments in Calendar or List view, update status, and launch patient consultations.',
    button('My Schedule', ['variant' => 'secondary', 'icon' => 'calendar', 'href' => url(['page' => 'my-schedule'])])
) ?>

<div class="view-toggle">
    <a class="view-toggle-btn <?= $view === 'List' ? 'active' : '' ?>"
       href="<?= e(url(['page' => 'my-appointments', 'view' => 'List', 'cat' => $cat === 'All' ? null : $cat])) ?>">
        <?= icon('clipboard', 15) ?> List View
    </a>
    <a class="view-toggle-btn <?= $view === 'Calendar' ? 'active' : '' ?>"
       href="<?= e(url(['page' => 'my-appointments', 'view' => 'Calendar'])) ?>">
        <?= icon('calendar', 15) ?> Calendar View
    </a>
</div>

<?php if ($view === 'Calendar'): ?>
    <div class="calendar-panel">
        <div class="calendar-head">
            <div>
                <?= button('‹', ['variant' => 'secondary', 'href' => url(['page' => 'my-appointments', 'view' => 'Calendar', 'week' => $week - 1])]) ?>
                <?= button('›', ['variant' => 'secondary', 'href' => url(['page' => 'my-appointments', 'view' => 'Calendar', 'week' => $week + 1])]) ?>
                <strong><?= e($rangeLabel) ?></strong>
            </div>
            <div class="calendar-key">
                <span><i class="key-confirmed"></i>Checked-in / Scheduled</span>
                <span><i class="key-waiting"></i>Provisional (Awaiting Front Desk)</span>
                <span><i class="key-cancelled"></i>Cancelled / No-show</span>
            </div>
        </div>

        <div class="week-grid">
            <div class="time-column">
                <span></span>
                <?php foreach (['09:00', '10:00', '11:00', '12:00', '13:00', '14:00'] as $t): ?><span><?= $t ?></span><?php endforeach; ?>
            </div>

            <?php foreach ($days as $i => [$label, $num]): ?>
                <div class="day-column <?= $i === 3 ? 'today' : '' ?>">
                    <strong><?= $label ?> <b><?= $num ?></b></strong>
                    <?php
                    foreach ($allAppts as $aIdx => $ap) {
                        $dayBucket = $aIdx % 5;
                        if ($dayBucket === $i) {
                            $ptRec  = find_patient_record($state, (string) ($ap[1] ?? ''));
                            $isProv = $ptRec ? is_provisional_patient($ptRec) : str_starts_with((string) ($ap[8] ?? ''), 'PROV-');
                            $cls    = in_array($ap[3], ['Cancelled', 'No-show'], true) ? 'cancelled' : ($isProv ? 'waiting' : 'confirmed');
                            $pos    = 'e' . (($aIdx % 4) + 1);
                            $tag    = $isProv ? ' [PROV]' : '';
                            echo '<a class="event ' . $cls . ' ' . $pos . '" style="text-decoration:none;" href="' . e(url(['page' => 'my-appointments', 'detail' => 'history', 'patient' => $ap[1]])) . '"><b>' . e($ap[0]) . '</b><span>' . e($ap[1] . $tag) . '</span></a>';
                        }
                    }
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>

<div class="appt-cats">
    <?php foreach (array_keys($catMap) as $c): ?>
        <a class="appt-cat <?= $cat === $c ? 'active' : '' ?>"
           href="<?= e(url(['page' => 'my-appointments', 'view' => 'List', 'cat' => $c === 'All' ? null : $c])) ?>">
            <?= e($c) ?>
            <span class="appt-cat-count"><?= $counts[$c] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<?php
$toolbar = toolbar_open('my-appointments', ['view' => 'List', 'cat' => $cat])
    . search_box('Search patient name, MRN, or complaint...', 'q', $q)
    . toolbar_close();
?>

<?= table_open(['Time & Date', 'Patient & Reg Status', 'MRN', 'Visit Type', 'Chief Complaint', 'Status', 'Consultation & Status Actions'], $toolbar) ?>
<?php foreach ($paged['rows'] as $entry):
    $idx    = $entry['idx'];
    $r      = $entry['row'];
    $ptRec  = find_patient_record($state, (string) ($r[1] ?? ''));
    $isProv = $ptRec ? is_provisional_patient($ptRec) : str_starts_with((string) ($r[8] ?? ''), 'PROV-');
    $mrnStr = $ptRec ? patient_mrn_display($ptRec) : (str_starts_with((string) ($r[8] ?? ''), 'MRN-') ? $r[8] : '');
?>
    <tr>
        <td>
            <strong><?= e($r[0]) ?></strong>
            <div class="muted" style="font-size:11px;"><?= e($r[7] ?? 'Today') ?></div>
        </td>
        <td>
            <div class="person-cell">
                <?= mini_avatar($r[1]) ?>
                <div>
                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                        <strong><?= e($r[1]) ?></strong>
                        <?php if ($isProv): ?>
                            <?= badge('Provisional', 'warning') ?>
                        <?php else: ?>
                            <?= badge('Registered', 'success') ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </td>
        <td>
            <?php if ($mrnStr !== ''): ?>
                <code style="font-weight:700; color:var(--teal-700);"><?= e($mrnStr) ?></code>
            <?php else: ?>
                <span class="muted">—</span>
            <?php endif; ?>
        </td>
        <td><?= e($r[2]) ?></td>
        <td><span class="complaint-text"><?= e($r[4]) ?></span></td>
        <td><?= badge($r[3], status_tone($r[3])) ?></td>
        <td>
            <div class="row-actions" style="flex-wrap:wrap; gap:6px;">
                <?php if ($r[3] === 'Completed'): ?>
                    <a class="text-link" href="<?= e(url(['page' => 'my-appointments', 'detail' => 'history', 'patient' => $r[1]])) ?>">View record <?= icon('arrow', 14) ?></a>
                <?php elseif (in_array($r[3], ['In consultation', 'In progress'], true)): ?>
                    <?= button('Continue Consultation', ['href' => url(['page' => 'my-appointments', 'detail' => 'new-record', 'patient' => $r[1]])]) ?>
                <?php elseif ($isProv): ?>
                    <span class="muted" style="font-size:11px;">Needs Front Desk Registration at Check-In</span>
                <?php else: ?>
                    <?= post_button('Start Consultation', 'start_consultation', ['patient' => $r[1], 'idx' => (string) $idx]) ?>
                <?php endif; ?>

                <?php if (!in_array($r[3], ['Completed', 'Cancelled', 'No-show'], true)): ?>
                    <?= post_button('No-show', 'update_appt_status', ['idx' => (string) $idx, 'status' => 'No-show'], ['variant' => 'secondary']) ?>
                    <?= post_button('Cancel', 'update_appt_status', ['idx' => (string) $idx, 'status' => 'Cancelled'], ['variant' => 'secondary', 'class' => 'btn-cancel']) ?>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close('appointment', true, $paged['total'], $paged['page'], $paged['pages']) ?>

<?php if (!$paged['rows']): ?>
    <?= filtered_empty('No appointments match this filter.') ?>
<?php endif; ?>

<?php endif; ?>
