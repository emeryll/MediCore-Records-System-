<?php
declare(strict_types=1);

$view = ($_GET['view'] ?? 'List') === 'Calendar' ? 'Calendar' : 'List';
$cat  = in_array($_GET['cat'] ?? '', ['Scheduled', 'Checked-in', 'In consultation', 'Completed', 'Cancelled'], true)
      ? $_GET['cat'] : 'All';
$q    = trim((string) ($_GET['q'] ?? ''));

$allAppts = $state['appointments'];

$catMap = [
    'All'             => static fn ($s) => true,
    'Scheduled'       => static fn ($s) => in_array($s, ['Scheduled', 'Upcoming', 'Confirmed', 'Waiting'], true),
    'Checked-in'      => static fn ($s) => $s === 'Checked-in',
    'In consultation' => static fn ($s) => in_array($s, ['In consultation', 'In progress'], true),
    'Completed'       => static fn ($s) => $s === 'Completed',
    'Cancelled'       => static fn ($s) => in_array($s, ['Cancelled', 'No-show'], true),
];

$counts = [];
foreach (array_keys($catMap) as $c) {
    $counts[$c] = count(array_filter($allAppts, static fn ($r) => $catMap[$c]($r[3] ?? '')));
}

$deptFilter = (string) ($_GET['dept'] ?? 'All departments');

// Resolve each appointment's department (stored value, else the doctor's department).
$doctorDeptMap = [];
foreach (doctors_data() as $d) {
    $doctorDeptMap[strtolower((string) $d[0])] = (string) $d[1];
}
$apptDept = static function (array $r) use ($doctorDeptMap): string {
    $d = trim((string) ($r[6] ?? ''));
    if ($d === '' || in_array(strtolower($d), ['outpatient', 'inpatient', '—'], true)) {
        $d = $doctorDeptMap[strtolower(trim((string) ($r[5] ?? '')))] ?? 'Unassigned';
    }
    return $d;
};
$deptOptions = ['All departments'];
foreach ($allAppts as $r) {
    $deptOptions[] = $apptDept($r);
}
$deptOptions = array_values(array_unique($deptOptions));
sort($deptOptions);
array_unshift($deptOptions, 'All departments');
$deptOptions = array_values(array_unique($deptOptions));
if (!in_array($deptFilter, $deptOptions, true)) {
    $deptFilter = 'All departments';
}

$filteredEntries = [];
foreach ($allAppts as $idx => $r) {
    if ($catMap[$cat]($r[3] ?? '')
        && ($deptFilter === 'All departments' || $apptDept($r) === $deptFilter)
        && row_matches([$r[0], $r[1], $r[2], $r[4] ?? '', $r[5] ?? '', $r[7] ?? '', $r[8] ?? '', $apptDept($r)], $q)) {
        $filteredEntries[] = ['idx' => $idx, 'row' => $r, 'dept' => $apptDept($r)];
    }
}
// Group by department (alphabetical), keeping original order within each department.
usort($filteredEntries, static fn ($a, $b) => strcmp($a['dept'], $b['dept']) ?: ($a['idx'] <=> $b['idx']));
$deptCounts = [];
foreach ($filteredEntries as $fe) {
    $deptCounts[$fe['dept']] = ($deptCounts[$fe['dept']] ?? 0) + 1;
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

$actions = '<div class="button-pair">'
    . button('Book appointment', ['icon' => 'calendar', 'href' => url(['page' => 'book', 'step' => 1])])
    . '</div>';
?>
<?= page_title(
    'Appointments & Check-In',
    'Manage walk-in and phone-in appointments, check in arriving patients, or reschedule/cancel on their behalf.',
    $actions
) ?>

<div class="view-toggle">
    <a class="view-toggle-btn <?= $view === 'List' ? 'active' : '' ?>"
       href="<?= e(url(['page' => 'appointments', 'view' => 'List', 'cat' => $cat === 'All' ? null : $cat])) ?>">
        <?= icon('clipboard', 15) ?> List View
    </a>
    <a class="view-toggle-btn <?= $view === 'Calendar' ? 'active' : '' ?>"
       href="<?= e(url(['page' => 'appointments', 'view' => 'Calendar'])) ?>">
        <?= icon('calendar', 15) ?> Calendar View
    </a>
</div>

<?php if ($view === 'Calendar'): ?>

<div class="calendar-panel">
    <div class="calendar-head">
        <div>
            <?= button('‹', ['variant' => 'secondary', 'href' => url(['page' => 'appointments', 'view' => 'Calendar', 'week' => $week - 1])]) ?>
            <?= button('›', ['variant' => 'secondary', 'href' => url(['page' => 'appointments', 'view' => 'Calendar', 'week' => $week + 1])]) ?>
            <strong><?= e($rangeLabel) ?></strong>
        </div>
        <div class="calendar-key">
            <span><i class="key-confirmed"></i>Scheduled / Checked-in</span>
            <span><i class="key-waiting"></i>Provisional (Needs Full Reg)</span>
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
                        echo '<a class="event ' . $cls . ' ' . $pos . '" style="text-decoration:none;" href="' . e(url(['page' => 'appointments', 'view' => 'Calendar', 'modal' => 'Appointment details', 'appt' => $aIdx])) . '"><b>' . e($ap[0]) . '</b><span>' . e($ap[1] . $tag) . '</span></a>';
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
           href="<?= e(url(['page' => 'appointments', 'view' => 'List', 'cat' => $c === 'All' ? null : $c])) ?>">
            <?= e($c) ?>
            <span class="appt-cat-count"><?= $counts[$c] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<?php
$toolbar = toolbar_open('appointments', ['view' => 'List', 'cat' => $cat])
    . search_box('Search patient name, MRN, complaint, doctor, or department...', 'q', $q)
    . '<div class="toolbar-right">'
    . select_field('', 'dept', $deptOptions, $deptFilter)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>

<?= table_open(['Date & Time', 'Patient & Status', 'MRN', 'Doctor & Dept', 'Type & Reason', 'Appt Status', 'Check-in & Actions'], $toolbar) ?>
<?php $lastDept = null; foreach ($paged['rows'] as $entry):
    $idx    = $entry['idx'];
    $r      = $entry['row'];
    $ptRec  = find_patient_record($state, (string) ($r[1] ?? ''));
    $isProv = $ptRec ? is_provisional_patient($ptRec) : str_starts_with((string) ($r[8] ?? ''), 'PROV-');
    $mrnStr = $ptRec ? patient_mrn_display($ptRec) : (str_starts_with((string) ($r[8] ?? ''), 'MRN-') ? $r[8] : '');
?>
    <?php if ($entry['dept'] !== $lastDept): $lastDept = $entry['dept']; ?>
    <tr class="dept-group-row">
        <td colspan="7" style="background:var(--teal-50); border-top:2px solid var(--teal-200); padding:10px 18px;">
            <strong style="color:var(--teal-800); font-size:13px;"><?= e($entry['dept']) ?></strong>
            <span class="muted" style="font-size:11.5px; margin-left:8px;"><?= (int) $deptCounts[$entry['dept']] ?> appointment(s)</span>
        </td>
    </tr>
    <?php endif; ?>
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
                <span class="muted" style="font-size:11.5px;">—</span>
            <?php endif; ?>
        </td>
        <td>
            <strong><?= e($r[5] ?? '—') ?></strong>
            <div class="muted" style="font-size:11px;"><?= e($entry['dept']) ?></div>
        </td>
        <td>
            <strong><?= e($r[2]) ?></strong>
            <div class="complaint-text"><?= e($r[4] ?? '—') ?></div>
        </td>
        <td><?= badge($r[3], status_tone($r[3])) ?></td>
        <td style="white-space:normal; min-width:200px;">
            <div class="appt-action-cell">
                <?php if (in_array($r[3], ['Scheduled', 'Confirmed', 'Upcoming', 'Waiting'], true)): ?>
                    <?php if ($isProv): ?>
                        <a class="btn btn-primary btn-sm btn-appt-action" href="<?= e(url(['page' => 'appointments', 'view' => 'List', 'modal' => 'Complete registration', 'patient' => $r[1], 'checkin_appt' => $idx])) ?>" title="Complete registration and check in">
                            <?= icon('check', 13) ?>
                            <span>Complete Reg &amp; Check In</span>
                        </a>
                    <?php else: ?>
                        <?= post_button('Check In', 'checkin_appointment', ['idx' => (string) $idx], ['variant' => 'primary', 'class' => 'btn-sm btn-appt-action', 'icon' => 'check']) ?>
                    <?php endif; ?>
                    <a class="btn btn-secondary btn-sm btn-appt-action" href="<?= e(url(['page' => 'book', 'step' => 2, 'reschedule' => $idx])) ?>" title="Reschedule appointment">
                        <span>Reschedule</span>
                    </a>
                <?php endif; ?>
                <a class="btn btn-secondary btn-sm btn-appt-action" href="<?= e(url(['page' => 'appointments', 'view' => 'List', 'cat' => $cat === 'All' ? null : $cat, 'modal' => 'Appointment details', 'appt' => $idx])) ?>" title="View appointment details">
                    <span>Details</span>
                </a>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close('appointment', true, $paged['total'], $paged['page'], $paged['pages']) ?>

<?php if (!$paged['rows']): ?>
    <?= filtered_empty('No appointments match this filter.') ?>
<?php endif; ?>

<?php endif; ?>
