<?php
$visits = array_slice(doctor_visits(), 0, 4);
$tone = static fn (string $s): string => match ($s) {
    'Completed'   => 'success',
    'Waiting'     => 'warning',
    'In progress' => 'info',
    default       => 'neutral',
};
?>
<?= page_title('Good morning, Dr. Wilson', 'You have 8 appointments scheduled for today.') ?>

<div class="stats-grid three">
    <?= stat_card("Today's appointments", '8', 'Next visit at 10:00', 'calendar') ?>
    <?= stat_card('Pending lab results', '5', '2 marked urgent', 'flask', 'amber') ?>
    <?= stat_card('Patients seen today', '3', '5 remaining', 'patient', 'green') ?>
</div>

<div class="panel">
    <div class="panel-head">
        <div>
            <h2>Today’s appointment timeline</h2>
            <p>Thursday, March 20</p>
        </div>
        <?= button('View all appointments', ['variant' => 'ghost', 'href' => url(['page' => 'my-appointments'])]) ?>
    </div>

    <div class="doctor-timeline">
        <?php foreach ($visits as $i => $v): ?>
            <div class="doctor-visit">
                <time><?= e($v[0]) ?></time>
                <span class="visit-line <?= $i === 1 ? 'now' : '' ?>"><i></i></span>
                <div class="visit-card">
                    <div class="schedule-person">
                        <?= mini_avatar($v[1]) ?>
                        <div><strong><?= e($v[1]) ?></strong><span><?= e($v[2]) ?></span></div>
                    </div>
                    <?= badge($v[3], $tone($v[3])) ?>
                    <?php if ($v[3] !== 'Completed'): ?>
                        <?= button($i === 1 ? 'Continue' : 'View patient', [
                            'variant' => $i === 1 ? 'primary' : 'secondary',
                            'href'    => url(['page' => 'patients']),
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
