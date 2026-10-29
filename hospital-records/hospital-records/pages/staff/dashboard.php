<?php
$appointments = array_slice($state['appointments'], 0, 5);
$totalAppts   = count($state['appointments']);
$checkedIn    = count(array_filter($state['appointments'], static fn ($a) => in_array($a[3], ['In progress', 'Completed', 'Waiting'], true)));
$availRooms   = count(array_filter($state['crud']['rooms'], static fn ($r) => $r[2] === 'Available'));
$unpaidBills  = array_filter($state['bills'], static fn ($b) => $b[2] !== 'Paid');

$actions = '<div class="button-pair">'
    . button('Register patient', ['variant' => 'secondary', 'icon' => 'patient', 'href' => url(['page' => 'patients', 'modal' => 'Register patient'])])
    . button('Book appointment', ['icon' => 'calendar', 'href' => url(['page' => 'appointments', 'modal' => 'Book appointment'])])
    . '</div>';
?>
<?= page_title('Front desk dashboard', date('l, F j') . ' · Here’s today’s patient flow.', $actions) ?>

<div class="stats-grid three">
    <?= stat_card("Today's appointments", (string) $totalAppts, $checkedIn . ' patients checked in', 'calendar') ?>
    <?= stat_card('Available rooms', (string) $availRooms, count($state['crud']['rooms']) . ' total rooms', 'bed', 'green') ?>
    <?= stat_card('Registered patients', (string) count($state['patients']), count($state['admissions']) . ' currently admitted', 'patient', 'navy') ?>
</div>

<div class="panel">
    <div class="panel-head">
        <div>
            <h2>Today’s appointments</h2>
            <p>Upcoming visits and arrival status</p>
        </div>
        <?= button('View schedule', ['variant' => 'ghost', 'href' => url(['page' => 'appointments', 'view' => 'List'])]) ?>
    </div>
    <div class="schedule-list">
        <?php foreach ($appointments as $idx => $a): ?>
            <div class="schedule-row">
                <time><?= e($a[0]) ?></time>
                <span class="timeline-dot"></span>
                <div class="schedule-person">
                    <?= mini_avatar($a[1]) ?>
                    <div><strong><?= e($a[1]) ?></strong><span><?= e(($a[5] ?? 'Doctor') . ' · ' . $a[2]) ?></span></div>
                </div>
                <?= badge($a[3], status_tone($a[3])) ?>
                <a class="text-link" href="<?= e(url(['page' => 'appointments', 'view' => 'List', 'modal' => 'Appointment details', 'appt' => $idx])) ?>">View details</a>
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
