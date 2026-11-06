<?php
declare(strict_types=1);

$auditSlice = array_slice($state['audit'] ?? audit_rows(), 0, 6);
$totalUsers = count($state['users']);
$beds       = $state['beds'] ?? [];
$activeBeds = array_values(array_filter($beds, static fn ($b) => !empty($b['active'])));
$occBeds    = count(array_filter($activeBeds, static fn ($b) => ($b['status'] ?? '') === 'Occupied'));
$totalPts   = count($state['patients'] ?? []);
$provPts    = count(array_filter($state['patients'] ?? [], static fn ($p) => is_provisional_patient($p)));
$regPts     = $totalPts - $provPts;

$allPayments = $state['payments'] ?? [];
$totalRevenueAll = 0.0;
foreach ($allPayments as $py) {
    $totalRevenueAll += parse_money($py['amount_paid'] ?? 0);
}

$actions = '<div class="button-pair">'
    . button('Price List', ['variant' => 'secondary', 'icon' => 'clipboard', 'href' => url(['page' => 'price-list'])])
    . button('Reports', ['icon' => 'card', 'href' => url(['page' => 'sales-records'])])
    . '</div>';
?>
<?= page_title(
    'Good day, ' . current_user_name('Admin'),
    'West Metro Medical Center · System-wide totals for patients, appointments, active admissions, revenue, and user accounts.',
    $actions
) ?>

<div class="stats-grid four">
    <?= stat_card('Total patients', (string) $totalPts, $regPts . ' Registered (MRN) · ' . $provPts . ' Provisional', 'patient', 'blue') ?>
    <?= stat_card('Appointments & Admissions', count($state['appointments']) . ' / ' . count($state['admissions']), count($state['appointments']) . ' appointments · ' . count($state['admissions']) . ' admitted', 'calendar', 'teal') ?>
    <?= stat_card('Total revenue', format_money($totalRevenueAll), count($allPayments) . ' payments collected', 'card', 'green') ?>
    <?= stat_card('User accounts', (string) $totalUsers, $occBeds . ' of ' . count($activeBeds) . ' beds occupied', 'users', 'navy') ?>
</div>

<div class="content-grid">
    <div class="panel wide">
        <div class="panel-head">
            <div>
                <h2>Recent audit log activity</h2>
                <p>Latest logins, record edits, discharges, payments, and user account changes</p>
            </div>
            <?= button('Full Audit Log', ['variant' => 'ghost', 'href' => url(['page' => 'audit-logs'])]) ?>
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
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel security-panel">
        <div class="panel-head">
            <div>
                <h2>Hospital Setup &amp; Administration</h2>
                <p>Quick links to administrative modules</p>
            </div>
            <?= badge('WMMC Zamboanga', 'success') ?>
        </div>
        <div style="display:grid; gap:10px; padding:16px 20px;">
            <a class="btn btn-secondary" style="justify-content:space-between;" href="<?= e(url(['page' => 'users'])) ?>">
                <span>User Management (Doctors, Staff, Cashiers, Admins)</span> <?= icon('arrow', 14) ?>
            </a>
            <a class="btn btn-secondary" style="justify-content:space-between;" href="<?= e(url(['page' => 'rooms'])) ?>">
                <span>Room &amp; Bed Setup (Rates &amp; Types)</span> <?= icon('arrow', 14) ?>
            </a>
            <a class="btn btn-secondary" style="justify-content:space-between;" href="<?= e(url(['page' => 'price-list'])) ?>">
                <span>Hospital Price List (Consult, Lab, Meds)</span> <?= icon('arrow', 14) ?>
            </a>
            <a class="btn btn-secondary" style="justify-content:space-between;" href="<?= e(url(['page' => 'archive'])) ?>">
                <span>Hospital Archive &amp; Turnover Records</span> <?= icon('arrow', 14) ?>
            </a>
        </div>
    </div>
</div>
