<?php
$visits = array_slice($state['appointments'], 0, 4);
$done   = count(array_filter($state['appointments'], static fn ($v) => $v[3] === 'Completed'));
$total  = count($state['appointments']);
$tone   = static fn (string $s): string => match ($s) {
    'Completed'   => 'success',
    'Waiting'     => 'warning',
    'In progress' => 'info',
    default       => 'neutral',
};
?>
<?= page_title('Good morning, ' . current_user_name('Doctor'), 'You have ' . $total . ' appointment' . ($total !== 1 ? 's' : '') . ' scheduled for today.') ?>

<div class="stats-grid three">
    <?= stat_card("Today's appointments", (string) $total, 'Scheduled for today', 'calendar') ?>
    <?= stat_card('Pending lab results', (string) count(array_filter($state['lab_results'], static fn ($r) => $r[3] === 'Pending')), 'Awaiting results', 'flask', 'amber') ?>
    <?= stat_card('Patients seen today', (string) $done, ($total - $done) . ' remaining', 'patient', 'green') ?>
</div>

<div class="panel">
    <div class="panel-head">
        <div>
            <h2>Today's appointment timeline</h2>
            <p><?= date('l, F j') ?></p>
        </div>
        <?= button('View all appointments', ['variant' => 'ghost', 'href' => url(['page' => 'my-appointments'])]) ?>
    </div>

    <div class="doctor-timeline">
        <?php foreach ($visits as $i => $v): ?>
            <div class="doctor-visit">
                <time><?= e($v[0]) ?></time>
                <span class="visit-line <?= $v[3] === 'In progress' ? 'now' : '' ?>"><i></i></span>
                <div class="visit-card">
                    <div class="schedule-person">
                        <?= mini_avatar($v[1]) ?>
                        <div>
                            <strong><?= e($v[1]) ?></strong>
                            <span><?= e($v[2]) ?><?= isset($v[4]) && $v[4] !== '' ? ' · ' . e($v[4]) : '' ?></span>
                        </div>
                    </div>
                    <?= badge($v[3], $tone($v[3])) ?>
                    <?php if ($v[3] === 'Completed'): ?>
                        <?= button('View record', ['variant' => 'secondary', 'href' => url(['page' => 'my-appointments', 'detail' => 'history', 'patient' => $v[1]])]) ?>
                    <?php elseif ($v[3] === 'In progress'): ?>
                        <?= button('Continue', ['href' => url(['page' => 'my-appointments', 'detail' => 'new-record', 'patient' => $v[1]])]) ?>
                    <?php else: ?>
                        <?= button('View patient', ['variant' => 'secondary', 'href' => url(['page' => 'patients', 'detail' => 'history', 'patient' => $v[1]])]) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$visits): ?>
            <div class="empty-state" style="padding:36px 20px;">
                <?= icon('calendar', 28) ?>
                <strong>No appointments scheduled for today</strong>
                <p>Booked appointments will appear on your timeline here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
