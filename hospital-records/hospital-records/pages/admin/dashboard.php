<?php
$activity = [
    ['Amelia Hart', 'Updated user role', 'Marcus Lee', '2 min ago', 'AH'],
    ['Dr. Olivia Bennett', 'Viewed patient record', 'Eleanor Rigby', '18 min ago', 'OB'],
    ['Sophia Clarke', 'Created appointment', 'Michael Torres', '32 min ago', 'SC'],
    ['System', 'Room status updated', 'Room 204 · Occupied', '1 hr ago', 'SY'],
];
?>
<?= page_title('Good morning, Amelia', 'Here’s what’s happening across the hospital today.') ?>

<div class="stats-grid">
    <?= stat_card('Total users', '248', '+12 this month', 'users') ?>
    <?= stat_card('Active doctors', '42', '38 on duty today', 'patient', 'navy') ?>
    <?= stat_card('Rooms occupied', '86%', '103 of 120 rooms', 'bed', 'amber') ?>
    <?= stat_card('Patients today', '187', '+8.2% vs yesterday', 'clipboard', 'green') ?>
</div>

<div class="content-grid">
    <div class="panel wide">
        <div class="panel-head">
            <div>
                <h2>Recent audit activity</h2>
                <p>Latest system events and record changes</p>
            </div>
            <?= button('View all', ['variant' => 'ghost', 'href' => url(['page' => 'audit-logs'])]) ?>
        </div>
        <div class="activity-list">
            <?php foreach ($activity as $a): ?>
                <div class="activity-row">
                    <span class="mini-avatar"><?= e($a[4]) ?></span>
                    <div>
                        <strong><?= e($a[0]) ?></strong>
                        <p><?= e($a[1]) ?> · <b><?= e($a[2]) ?></b></p>
                    </div>
                    <time><?= e($a[3]) ?></time>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel security-panel">
        <div class="panel-head">
            <div>
                <h2>System health</h2>
                <p>Live infrastructure status</p>
            </div>
            <?= badge('All systems normal', 'success') ?>
        </div>
        <div class="health-ring">
            <div><strong>99.98%</strong><span>Uptime</span></div>
        </div>
        <div class="health-rows">
            <p><span>Database</span><?= badge('Operational', 'success') ?></p>
            <p><span>Backup service</span><?= badge('Operational', 'success') ?></p>
            <p><span>Last backup</span><strong>Today, 04:00</strong></p>
        </div>
    </div>
</div>
