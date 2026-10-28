<?php
$week  = (int) ($_GET['week'] ?? 0);
$start = 17 + $week * 7;
$days  = [];
foreach (['MON', 'TUE', 'WED', 'THU', 'FRI'] as $i => $d) {
    $days[] = [$d, $start + $i];
}
$event = static fn (string $cls, string $pos, string $time, string $name): string =>
    '<div class="event ' . $cls . ' ' . $pos . '"><b>' . e($time) . '</b><span>' . e($name) . '</span></div>';
?>
<?= page_title(
    'Appointments',
    'Schedule visits and coordinate care teams.',
    button('Book appointment', ['icon' => 'plus', 'href' => url(['page' => 'appointments', 'modal' => 'Book appointment', 'week' => $week ?: null])])
) ?>

<div class="calendar-panel">
    <div class="calendar-head">
        <div>
            <?= button('‹', ['variant' => 'secondary', 'href' => url(['page' => 'appointments', 'week' => $week - 1])]) ?>
            <?= button('›', ['variant' => 'secondary', 'href' => url(['page' => 'appointments', 'week' => $week + 1])]) ?>
            <strong>March <?= $start ?> – <?= $start + 4 ?>, 2025</strong>
        </div>
        <div class="calendar-key">
            <span><i class="key-confirmed"></i>Confirmed</span>
            <span><i class="key-waiting"></i>Waiting</span>
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
                if ($i === 0) echo $event('confirmed', 'e1', '09:00', 'Eleanor Rigby');
                if ($i === 1) echo $event('waiting', 'e2', '10:30', 'Michael Torres');
                if ($i === 2) echo $event('cancelled', 'e3', '11:00', 'Ava Thompson');
                if ($i === 3) {
                    echo $event('confirmed', 'e1', '09:00', 'Eleanor Rigby');
                    echo $event('waiting', 'e4', '13:00', 'Nora Patel');
                }
                if ($i === 4) echo $event('confirmed', 'e2', '10:15', 'Henry Adams');
                ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
