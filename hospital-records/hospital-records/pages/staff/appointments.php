<?php
$view = ($_GET['view'] ?? 'Calendar') === 'List' ? 'List' : 'Calendar';
$cat  = in_array($_GET['cat'] ?? '', ['Upcoming', 'In progress', 'Completed'], true)
      ? $_GET['cat'] : 'All';
$q    = trim((string) ($_GET['q'] ?? ''));

// Live session-backed appointments
$allAppts = $state['appointments'];

$catMap = [
    'All'         => fn ($s) => true,
    'Upcoming'    => fn ($s) => in_array($s, ['Upcoming', 'Confirmed', 'Waiting'], true),
    'In progress' => fn ($s) => $s === 'In progress',
    'Completed'   => fn ($s) => $s === 'Completed',
];

$counts = [];
foreach (['All', 'Upcoming', 'In progress', 'Completed'] as $c) {
    $counts[$c] = count(array_filter($allAppts, fn ($r) => $catMap[$c]($r[3])));
}

$listRows = [];
foreach ($allAppts as $idx => $r) {
    if ($catMap[$cat]($r[3]) && row_matches([$r[0], $r[1], $r[2], $r[4], $r[5] ?? ''], $q)) {
        $listRows[$idx] = $r;
    }
}

// Calendar setup
$week   = (int) ($_GET['week'] ?? 0);
$monTs  = strtotime('monday this week ' . ($week >= 0 ? '+' . $week : (string) $week) . ' weeks');
$friTs  = strtotime('+4 days', $monTs);
$days   = [];
foreach (['MON', 'TUE', 'WED', 'THU', 'FRI'] as $i => $d) {
    $days[] = [$d, (int) date('j', strtotime('+' . $i . ' days', $monTs))];
}
$rangeLabel = date('F j', $monTs) . ' – ' . date('j, Y', $friTs);
$event = static fn (string $cls, string $pos, string $time, string $name, int $idx): string =>
    '<a class="event ' . $cls . ' ' . $pos . '" style="text-decoration:none;" href="' . e(url(['page' => 'appointments', 'view' => 'Calendar', 'modal' => 'Appointment details', 'appt' => $idx])) . '"><b>' . e($time) . '</b><span>' . e($name) . '</span></a>';
?>
<?= page_title(
    'Appointments',
    'Schedule visits and coordinate care teams.',
    button('Book appointment', ['icon' => 'plus', 'href' => url(['page' => 'appointments', 'modal' => 'Book appointment', 'view' => $view])])
) ?>

<div class="view-toggle">
    <a class="view-toggle-btn <?= $view === 'Calendar' ? 'active' : '' ?>"
       href="<?= e(url(['page' => 'appointments', 'view' => 'Calendar'])) ?>">
        <?= icon('calendar', 15) ?> Calendar
    </a>
    <a class="view-toggle-btn <?= $view === 'List' ? 'active' : '' ?>"
       href="<?= e(url(['page' => 'appointments', 'view' => 'List', 'cat' => $cat === 'All' ? null : $cat])) ?>">
        <?= icon('clipboard', 15) ?> List
    </a>
</div>

<?php if ($view === 'Calendar'): ?>

<div class="calendar-panel">
    <div class="calendar-head">
        <div>
            <?= button('‹', ['variant' => 'secondary', 'href' => url(['page' => 'appointments', 'week' => $week - 1])]) ?>
            <?= button('›', ['variant' => 'secondary', 'href' => url(['page' => 'appointments', 'week' => $week + 1])]) ?>
            <strong><?= e($rangeLabel) ?></strong>
        </div>
        <div class="calendar-key">
            <span><i class="key-confirmed"></i>Confirmed</span>
            <span><i class="key-waiting"></i>Waiting / In progress</span>
            <span><i class="key-cancelled"></i>Cancelled</span>
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
                        $cls = $ap[3] === 'Cancelled' ? 'cancelled' : (in_array($ap[3], ['Waiting', 'In progress'], true) ? 'waiting' : 'confirmed');
                        $pos = 'e' . (($aIdx % 4) + 1);
                        echo $event($cls, $pos, $ap[0], $ap[1], $aIdx);
                    }
                }
                ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php else: ?>

<div class="appt-cats">
    <?php foreach (['All', 'Upcoming', 'In progress', 'Completed'] as $c): ?>
        <a class="appt-cat <?= $cat === $c ? 'active' : '' ?>"
           href="<?= e(url(['page' => 'appointments', 'view' => 'List', 'cat' => $c === 'All' ? null : $c])) ?>">
            <?= e($c) ?>
            <span class="appt-cat-count"><?= $counts[$c] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<?php
$toolbar = toolbar_open('appointments', ['view' => 'List', 'cat' => $cat])
    . search_box('Search patient, complaint, or doctor...', 'q', $q)
    . toolbar_close();
?>

<?= table_open(['Time', 'Patient', 'Type', 'Complaint', 'Doctor', 'Status', 'Actions'], $toolbar) ?>
<?php foreach ($listRows as $idx => $r): ?>
    <tr>
        <td><strong><?= e($r[0]) ?></strong></td>
        <td>
            <div class="person-cell"><?= mini_avatar($r[1]) ?><strong><?= e($r[1]) ?></strong></div>
        </td>
        <td><?= e($r[2]) ?></td>
        <td><span class="complaint-text"><?= e($r[4]) ?></span></td>
        <td><?= e($r[5] ?? '—') ?></td>
        <td><?= badge($r[3], match($r[3]) {
            'Completed'              => 'success',
            'In progress'            => 'info',
            'Cancelled'              => 'danger',
            'Upcoming', 'Confirmed'  => 'neutral',
            default                  => 'warning',
        }) ?></td>
        <td>
            <?= button('View details', [
                'variant' => 'secondary',
                'href'    => url(['page' => 'appointments', 'view' => 'List', 'cat' => $cat === 'All' ? null : $cat, 'modal' => 'Appointment details', 'appt' => $idx]),
            ]) ?>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close(count($listRows) . ' of ' . $counts[$cat] . ' appointment' . ($counts[$cat] !== 1 ? 's' : ''), false) ?>

<?php if (!$listRows): ?>
    <?= filtered_empty('No ' . strtolower($cat === 'All' ? '' : $cat . ' ') . 'appointments match this search.') ?>
<?php endif; ?>

<?php endif; ?>
