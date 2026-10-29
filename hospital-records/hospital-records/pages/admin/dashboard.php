<?php
$auditSlice = array_slice($state['audit'] ?? audit_rows(), 0, 5);
$totalUsers = count($state['users']);
$activeDocs = count(array_filter($state['users'], static fn ($u) => $u[2] === 'Doctor' && $u[3] === 'Active'));
$totalRooms = count($state['crud']['rooms']);
$occRooms   = count(array_filter($state['crud']['rooms'], static fn ($r) => $r[2] === 'Occupied'));
$occPct     = $totalRooms > 0 ? round(($occRooms / $totalRooms) * 100) . '%' : '0%';
$totalPts   = count($state['patients'] ?? patients_data());
?>
<?= page_title('Good morning, ' . current_user_name('Admin'), 'Here’s what’s happening across the hospital today.') ?>

<div class="stats-grid">
    <?= stat_card('Total users', (string) $totalUsers, 'Staff accounts', 'users') ?>
    <?= stat_card('Active doctors', (string) $activeDocs, 'On duty today', 'patient', 'navy') ?>
    <?= stat_card('Rooms occupied', $occPct, $occRooms . ' of ' . $totalRooms . ' rooms', 'bed', 'amber') ?>
    <?= stat_card('Registered patients', (string) $totalPts, count($state['admissions']) . ' currently admitted', 'clipboard', 'green') ?>
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
            <?php foreach ($auditSlice as $a): ?>
                <div class="activity-row">
                    <span class="mini-avatar"><?= e(initials($a[0])) ?></span>
                    <div>
                        <strong><?= e($a[0]) ?></strong>
                        <p><?= e($a[1]) ?> <?= e($a[2]) ?> · <b><?= e($a[3]) ?></b></p>
                    </div>
                    <time><?= e($a[4]) ?></time>
                </div>
            <?php endforeach; ?>
            <?php if (!$auditSlice): ?>
                <div class="empty-state" style="padding:32px 20px;">
                    <?= icon('history', 26) ?>
                    <strong>No recent activity</strong>
                    <p>System actions and record changes will appear here.</p>
                </div>
            <?php endif; ?>
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
            <div><strong>100%</strong><span>Uptime</span></div>
        </div>
        <div class="health-rows">
            <p><span>Database</span><?= badge('Ready', 'success') ?></p>
            <p><span>Backup service</span><?= badge('Operational', 'success') ?></p>
            <p><span>Last backup</span><strong>—</strong></p>
        </div>
    </div>
</div>
