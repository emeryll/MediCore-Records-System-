<?php
$appointments = [
    ['09:00', 'Eleanor Rigby', 'Dr. Bennett', 'Checked in'],
    ['10:30', 'Ava Thompson', 'Dr. Ross', 'Confirmed'],
    ['11:15', 'Michael Torres', 'Dr. Bennett', 'Waiting'],
    ['13:00', 'Nora Patel', 'Dr. Morgan', 'Confirmed'],
];
$actions = '<div class="button-pair">'
    . button('Register patient', ['variant' => 'secondary', 'icon' => 'patient', 'href' => url(['page' => 'patients', 'modal' => 'Register patient'])])
    . button('Book appointment', ['icon' => 'calendar', 'href' => url(['page' => 'appointments', 'modal' => 'Book appointment'])])
    . '</div>';
?>
<?= page_title('Front desk dashboard', 'Thursday, March 20 · Here’s today’s patient flow.', $actions) ?>

<div class="stats-grid three">
    <?= stat_card("Today's appointments", '32', '8 patients checked in', 'calendar') ?>
    <?= stat_card('Available rooms', '17', 'Across 6 wards', 'bed', 'green') ?>
    <?= stat_card('Unpaid bills', '14', '$8,420 outstanding', 'card', 'amber') ?>
</div>

<div class="panel">
    <div class="panel-head">
        <div>
            <h2>Today’s appointments</h2>
            <p>Upcoming visits and arrival status</p>
        </div>
        <?= button('View schedule', ['variant' => 'ghost', 'href' => url(['page' => 'appointments'])]) ?>
    </div>
    <div class="schedule-list">
        <?php foreach ($appointments as $a): ?>
            <div class="schedule-row">
                <time><?= e($a[0]) ?></time>
                <span class="timeline-dot"></span>
                <div class="schedule-person">
                    <?= mini_avatar($a[1]) ?>
                    <div><strong><?= e($a[1]) ?></strong><span><?= e($a[2]) ?></span></div>
                </div>
                <?= badge($a[3], $a[3] === 'Checked in' ? 'success' : ($a[3] === 'Waiting' ? 'warning' : 'info')) ?>
                <span class="more">•••</span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
