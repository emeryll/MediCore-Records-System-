<?php
$q     = trim((string) ($_GET['q'] ?? ''));
$role_ = (string) ($_GET['role'] ?? 'All roles');
if (strcasecmp($role_, 'Front desk') === 0) {
    $role_ = 'Staff';
}

$normalizeUserRole = static function (string $rawRole): string {
    if (strcasecmp($rawRole, 'Front desk') === 0 || $rawRole === '') {
        return 'Staff';
    }
    return $rawRole;
};

$allUsers = $state['users'] ?? [];
$users = array_values(array_filter(
    $allUsers,
    static fn ($u) => (
        $role_ === 'All roles'
        || strcasecmp($normalizeUserRole((string) ($u[2] ?? '')), $role_) === 0
    ) && row_matches([$u[0], $u[1], $u[7] ?? '', $u[8] ?? ''], $q)
));

$roleFilterOpts = ['All roles', 'Doctor', 'Staff', 'Cashier', 'Patient'];
foreach (array_merge(array_column($state['roles'] ?? [], 0), array_column($allUsers, 2)) as $rOpt) {
    $rNorm = $normalizeUserRole((string) $rOpt);
    if ($rNorm !== '' && !in_array($rNorm, $roleFilterOpts, true)) {
        $roleFilterOpts[] = $rNorm;
    }
}

// Count totals per category across all users
$totalDoctors  = count(array_filter($allUsers, static fn ($u) => strcasecmp($normalizeUserRole((string) ($u[2] ?? '')), 'Doctor') === 0));
$totalStaff    = count(array_filter($allUsers, static fn ($u) => strcasecmp($normalizeUserRole((string) ($u[2] ?? '')), 'Staff') === 0));
$totalCashiers = count(array_filter($allUsers, static fn ($u) => strcasecmp($normalizeUserRole((string) ($u[2] ?? '')), 'Cashier') === 0));
$totalPatients = count(array_filter($allUsers, static fn ($u) => strcasecmp($normalizeUserRole((string) ($u[2] ?? '')), 'Patient') === 0));

// Group filtered users by category
$categories = [
    'Doctor'  => [
        'title'    => 'Doctors',
        'subtitle' => 'Medical physicians & specialists (ID prefix: D)',
        'badge'    => 'D-xxxx',
        'tone'     => 'info',
        'headers'  => ['ID', 'Doctor', 'Username', 'Department & Specialty', 'Status', 'Last login', 'Actions'],
        'rows'     => [],
    ],
    'Staff'   => [
        'title'    => 'Staff',
        'subtitle' => 'Hospital & front desk personnel (ID prefix: S)',
        'badge'    => 'S-xxxx',
        'tone'     => 'teal',
        'headers'  => ['ID', 'Staff member', 'Username', 'Role / Department', 'Status', 'Last login', 'Actions'],
        'rows'     => [],
    ],
    'Cashier' => [
        'title'    => 'Cashiers',
        'subtitle' => 'Billing & payment collection personnel (ID prefix: C)',
        'badge'    => 'C-xxxx',
        'tone'     => 'warning',
        'headers'  => ['ID', 'Cashier', 'Username', 'Role / Department', 'Status', 'Last login', 'Actions'],
        'rows'     => [],
    ],
    'Patient' => [
        'title'    => 'Patients',
        'subtitle' => 'Registered patient portal accounts (ID prefix: P)',
        'badge'    => 'P-xxxx',
        'tone'     => 'success',
        'headers'  => ['ID', 'Patient', 'Username', 'Birthday & Age', 'Status', 'Last login', 'Actions'],
        'rows'     => [],
    ],
];

foreach ($users as $idx => $u) {
    $catKey = $normalizeUserRole((string) ($u[2] ?? 'Staff'));
    if (!isset($categories[$catKey])) {
        $pfx = db_prefix_for_role($catKey);
        $categories[$catKey] = [
            'title'    => $catKey,
            'subtitle' => 'Custom role accounts (ID prefix: ' . $pfx . ')',
            'badge'    => $pfx . '-xxxx',
            'tone'     => 'neutral',
            'headers'  => ['ID', 'User', 'Username', 'Role & Details', 'Status', 'Last login', 'Actions'],
            'rows'     => [],
        ];
    }
    $categories[$catKey]['rows'][] = [$idx, $u];
}

$toolbar = toolbar_open('users')
    . search_box('Search by ID, name, username, or email...', 'q', $q)
    . '<div class="toolbar-right">'
    . select_field('', 'role', $roleFilterOpts, $role_)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title(
    'Users',
    'Manage categorized Doctor (D-ID), Staff (S-ID), and Patient (P-ID) accounts, credentials, and access status.',
    button('Add user', ['icon' => 'plus', 'href' => url(['page' => 'users', 'modal' => 'Add user'])])
) ?>

<div class="stats-grid">
    <?= stat_card('Doctors (D-ID)', (string) $totalDoctors, 'Medical physicians & specialists', 'patient', 'navy') ?>
    <?= stat_card('Staff (S-ID)', (string) $totalStaff, 'Hospital & front desk staff', 'users', 'teal') ?>
    <?= stat_card('Cashiers (C-ID)', (string) $totalCashiers, 'Billing & payment collection', 'card', 'amber') ?>
    <?= stat_card('Patients (P-ID)', (string) $totalPatients, 'Patient portal accounts', 'clipboard', 'green') ?>
</div>

<div class="table-card" style="margin-bottom: 20px;">
    <?= $toolbar ?>
    <div class="tabs" style="border-bottom: 0;">
        <?php foreach ($roleFilterOpts as $tabOpt):
            $tabLabel = match ($tabOpt) {
                'All roles' => 'All Categories (' . count($allUsers) . ')',
                'Doctor'    => 'Doctors (' . $totalDoctors . ')',
                'Staff'     => 'Staff (' . $totalStaff . ')',
                'Cashier'   => 'Cashiers (' . $totalCashiers . ')',
                'Patient'   => 'Patients (' . $totalPatients . ')',
                default     => $tabOpt,
            };
        ?>
            <a class="<?= strcasecmp($role_, $tabOpt) === 0 ? 'active' : '' ?>"
               href="<?= e(url(['page' => 'users', 'role' => $tabOpt === 'All roles' ? null : $tabOpt, 'q' => $q !== '' ? $q : null])) ?>">
                <?= e($tabLabel) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php
$renderedAnyCategory = false;
foreach ($categories as $catKey => $catMeta):
    if ($role_ !== 'All roles' && strcasecmp($role_, $catKey) !== 0) {
        continue;
    }
    $catRows = $catMeta['rows'];
    if ($q !== '' && !$catRows && $role_ === 'All roles') {
        continue;
    }
    $renderedAnyCategory = true;
?>
    <div class="table-card" style="margin-bottom: 22px;">
        <div class="panel-head" style="background: var(--gray-50);">
            <div>
                <h2 style="display:flex; align-items:center; gap:10px;">
                    <span><?= e($catMeta['title']) ?></span>
                    <code><?= e($catMeta['badge']) ?></code>
                </h2>
                <p><?= e($catMeta['subtitle']) ?></p>
            </div>
            <?= badge(count($catRows) . ' ' . (count($catRows) === 1 ? 'account' : 'accounts'), $catMeta['tone']) ?>
        </div>

        <?php if ($catRows): ?>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($catMeta['headers'] as $th): ?>
                                <th><?= e($th) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($catRows as [$idx, $u]):
                            $uRoleLabel = $normalizeUserRole((string) ($u[2] ?? 'Staff'));
                            $prefix     = db_prefix_for_role($uRoleLabel);
                            $uId        = $u[7] ?? ($prefix . '-' . str_pad((string) (1001 + $idx), 4, '0', STR_PAD_LEFT));
                            $uName      = $u[8] ?? strtolower(explode('@', (string) $u[1])[0]);
                            $deptSpec   = trim(($u[5] ?? '') . (($u[5] ?? '') !== '' && ($u[6] ?? '') !== '' ? ' · ' : '') . ($u[6] ?? ''));
                            $ptDob      = '—';
                            $ptAgeStr   = '—';
                            if ($uRoleLabel === 'Patient') {
                                $ptRec    = find_patient_record($state, (string) $u[0]);
                                $ptDob    = format_birthdate((string) (($u[10] ?? '') !== '' ? $u[10] : ($ptRec[1] ?? '')));
                                $ptAgeStr = patient_age_label($ptDob, true);
                            }
                        ?>
                            <tr>
                                <td><code><?= e($uId) ?></code></td>
                                <td>
                                    <div class="person-cell">
                                        <?= mini_avatar($u[0]) ?>
                                        <div><strong><?= e($u[0]) ?></strong><span><?= e($u[1]) ?></span></div>
                                    </div>
                                </td>
                                <td><code><?= e($uName) ?></code></td>
                                <td>
                                    <?php if ($uRoleLabel === 'Doctor'): ?>
                                        <div>
                                            <strong><?= e($deptSpec !== '' ? $deptSpec : 'General Medicine') ?></strong>
                                            <?php if (!empty($u[9])): ?>
                                                <div style="font-size:11px; color:var(--gray-500);">License: <?= e((string) $u[9]) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($uRoleLabel === 'Patient'): ?>
                                        <div>
                                            <strong><?= e($ptDob) ?></strong>
                                            <div style="font-size:11px; color:var(--gray-500);">Age: <?= e($ptAgeStr) ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div>
                                            <strong><?= e($uRoleLabel) ?></strong>
                                            <?php if ($deptSpec !== ''): ?>
                                                <div style="font-size:11px; color:var(--gray-500);"><?= e($deptSpec) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?= badge($u[3], $u[3] === 'Active' ? 'success' : 'neutral') ?></td>
                                <td><?= e($u[4]) ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="<?= e(url(['page' => 'users', 'modal' => 'Edit user', 'email' => $u[1]])) ?>"><?= icon('edit', 15) ?> Edit</a>
                                        <?= post_button('', 'toggle_user', ['email' => $u[1]], [
                                            'plain' => true,
                                            'class' => 'text-link',
                                            'html'  => $u[3] === 'Active' ? 'Disable' : 'Enable',
                                        ]) ?>
                                        <?= post_button('', 'delete_user', ['email' => $u[1]], [
                                            'plain' => true,
                                            'class' => 'danger-link',
                                            'html'  => icon('trash', 15) . ' Archive',
                                        ]) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                <span>Showing <?= count($catRows) ?> <?= e(strtolower($catMeta['title'])) ?></span>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding: 28px 20px;">
                <strong>No <?= e(strtolower($catMeta['title'])) ?> found</strong>
                <p>Use "+ Add user" to create an account in this category.</p>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php if (!$renderedAnyCategory || !$users): ?>
    <?= filtered_empty('No users match these filters.') ?>
<?php endif; ?>
