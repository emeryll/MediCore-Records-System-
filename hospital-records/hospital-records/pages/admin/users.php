<?php
declare(strict_types=1);

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

// Patients registered at the front desk who have not activated their portal account yet
// are still listed here (read-only) with the status "Inactive".
$activatedKeys = [];
foreach ($allUsers as $au) {
    if (strcasecmp($normalizeUserRole((string) ($au[2] ?? '')), 'Patient') === 0) {
        $activatedKeys[strtolower(trim((string) $au[0]))] = true;
        $activatedKeys[strtolower(trim((string) ($au[7] ?? '')))] = true;
    }
}
foreach ($state['patients'] ?? [] as $pt) {
    if (is_provisional_patient($pt)) {
        continue;
    }
    // Patients with no phone get no portal account and do not show as pending activation
    if (!empty($pt['no_phone']) || empty($pt[2]) || $pt[2] === '—') {
        continue;
    }
    $ptName = trim((string) ($pt[0] ?? ''));
    $ptMrn  = (string) patient_mrn_display($pt, '');
    $ptPid  = (string) ($pt[8] ?? '');
    if ($ptName === '' || isset($activatedKeys[strtolower($ptName)])
        || ($ptMrn !== '' && isset($activatedKeys[strtolower($ptMrn)]))
        || ($ptPid !== '' && isset($activatedKeys[strtolower($ptPid)]))) {
        continue;
    }
    $allUsers[] = [
        $ptName, (string) ($pt['email'] ?? ''), 'Patient', 'Inactive', 'Never', '', '',
        $ptMrn !== '' ? $ptMrn : $ptPid, '', '', (string) ($pt[1] ?? ''), (string) ($pt[2] ?? ''),
        'not_activated' => true,
    ];
}
$users = array_values(array_filter(
    $allUsers,
    static fn ($u) => (
        $role_ === 'All roles'
        || strcasecmp($normalizeUserRole((string) ($u[2] ?? '')), $role_) === 0
    ) && row_matches([$u[0], $u[1], $u[7] ?? '', $u[8] ?? ''], $q)
));

$roleFilterOpts = ['All roles', 'Admin', 'Doctor', 'Staff', 'Cashier', 'Patient'];

$totalAdmins   = count(array_filter($allUsers, static fn ($u) => strcasecmp($normalizeUserRole((string) ($u[2] ?? '')), 'Admin') === 0));
$totalDoctors  = count(array_filter($allUsers, static fn ($u) => strcasecmp($normalizeUserRole((string) ($u[2] ?? '')), 'Doctor') === 0));
$totalStaff    = count(array_filter($allUsers, static fn ($u) => strcasecmp($normalizeUserRole((string) ($u[2] ?? '')), 'Staff') === 0));
$totalCashiers = count(array_filter($allUsers, static fn ($u) => strcasecmp($normalizeUserRole((string) ($u[2] ?? '')), 'Cashier') === 0));
$totalPatients = count(array_filter($allUsers, static fn ($u) => strcasecmp($normalizeUserRole((string) ($u[2] ?? '')), 'Patient') === 0));

$categories = [
    'Admin'   => [
        'title'    => 'Administrators',
        'subtitle' => 'System administration, access turnover & archived record retention',
        'badge'    => 'Administration',
        'tone'     => 'purple',
        'headers'  => ['Administrator & Contact', 'Role / Responsibility', 'Status', 'Last login', 'Actions'],
        'rows'     => [],
    ],
    'Doctor'  => [
        'title'    => 'Doctors',
        'subtitle' => 'Medical physicians & specialists (PRC License & Department)',
        'badge'    => 'Physicians',
        'tone'     => 'info',
        'headers'  => ['Doctor & Contact', 'Department & Specialty', 'PRC License', 'Status', 'Last login', 'Actions'],
        'rows'     => [],
    ],
    'Staff'   => [
        'title'    => 'Front Desk Staff',
        'subtitle' => 'Registration, appointments, admissions & room coordination',
        'badge'    => 'Clinical Staff',
        'tone'     => 'teal',
        'headers'  => ['Staff Member & Contact', 'Role / Department', 'Status', 'Last login', 'Actions'],
        'rows'     => [],
    ],
    'Cashier' => [
        'title'    => 'Cashiers',
        'subtitle' => 'Billing, payments, discharge clearance & end-of-day reports',
        'badge'    => 'Finance Desk',
        'tone'     => 'warning',
        'headers'  => ['Cashier & Contact', 'Role / Department', 'Status', 'Last login', 'Actions'],
        'rows'     => [],
    ],
    'Patient' => [
        'title'    => 'Patient Accounts',
        'subtitle' => 'Registered patients — Inactive until they activate their portal account via SMS OTP',
        'badge'    => 'MRN Verified',
        'tone'     => 'success',
        'headers'  => ['MRN', 'Patient & Contact', 'Birthday & Age', 'Status', 'Last login', 'Actions'],
        'rows'     => [],
    ],
];

foreach ($users as $idx => $u) {
    $catKey = $normalizeUserRole((string) ($u[2] ?? 'Staff'));
    if (isset($categories[$catKey])) {
        $categories[$catKey]['rows'][] = [$idx, $u];
    }
}

$toolbar = toolbar_open('users')
    . search_box('Search by name, mobile, email, or MRN...', 'q', $q)
    . '<div class="toolbar-right">'
    . select_field('', 'role', $roleFilterOpts, $role_)
    . filter_button()
    . '</div>'
    . toolbar_close();

$headerActions = button('Add account', ['icon' => 'plus', 'href' => url(['page' => 'users', 'modal' => 'Add account'])]);
?>
<?= page_title(
    'User Management',
    'Create, activate, and manage Admin, Doctor, Staff, and Cashier accounts. New accounts receive a secure activation link via email to choose their own personal password.',
    $headerActions
) ?>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
    <?= stat_card('Admins', (string) $totalAdmins, 'System administrators', 'shield', 'navy') ?>
    <?= stat_card('Doctors', (string) $totalDoctors, 'Physicians & specialists', 'patient', 'navy') ?>
    <?= stat_card('Staff', (string) $totalStaff, 'Front desk & ward staff', 'users', 'teal') ?>
    <?= stat_card('Cashiers', (string) $totalCashiers, 'Billing & cashier desk', 'card', 'amber') ?>
    <?= stat_card('Patient Accounts', (string) $totalPatients, 'Registered & activated portal accounts', 'clipboard', 'green') ?>
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

<?php foreach ($categories as $catKey => $catMeta):
    if ($role_ !== 'All roles' && strcasecmp($role_, $catKey) !== 0) {
        continue;
    }
    $catRows = $catMeta['rows'];
    if ($q !== '' && !$catRows && $role_ === 'All roles') {
        continue;
    }
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
            <?= badge(count($catRows) . ' account(s)', $catMeta['tone']) ?>
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
                            $deptSpec   = trim(($u[5] ?? '') . (($u[5] ?? '') !== '' && ($u[6] ?? '') !== '' ? ' · ' : '') . ($u[6] ?? ''));
                            $prcLic     = (string) ($u[9] ?? '—');
                            $uPhone     = (string) ($u[11] ?? '');
                            $ptDob      = '—';
                            $ptAgeStr   = '—';
                            if ($uRoleLabel === 'Patient') {
                                $ptRec    = find_patient_record($state, (string) $u[0]);
                                $ptDob    = format_birthdate((string) (($u[10] ?? '') !== '' ? $u[10] : ($ptRec[1] ?? '')));
                                $ptAgeStr = patient_age_label($ptDob, true);
                            }
                        ?>
                            <tr>
                                <?php if ($uRoleLabel === 'Patient'): ?>
                                    <td><code><?= e($uId) ?></code></td>
                                <?php endif; ?>
                                <td>
                                    <div class="person-cell">
                                        <?= mini_avatar($u[0]) ?>
                                        <div>
                                            <strong><?= e($u[0]) ?></strong>
                                            <span><?= e($u[1]) ?><?= $uPhone !== '' ? ' · ' . e($uPhone) : '' ?></span>
                                        </div>
                                    </div>
                                </td>
                                <?php if ($uRoleLabel === 'Doctor'): ?>
                                    <td><strong><?= e($deptSpec !== '' ? $deptSpec : 'General Medicine') ?></strong></td>
                                    <td><code><?= e($prcLic !== '' ? $prcLic : 'PRC-0108492') ?></code></td>
                                <?php elseif ($uRoleLabel === 'Patient'): ?>
                                    <td>
                                        <strong><?= e($ptDob) ?></strong>
                                        <div style="font-size:11px; color:var(--gray-500);">Age: <?= e($ptAgeStr) ?></div>
                                    </td>
                                <?php else: ?>
                                    <td><strong><?= e($uRoleLabel) ?></strong></td>
                                <?php endif; ?>
                                <?php
                                $uStatus = (string) ($u[3] ?? 'Active');
                                $isPending = ($uStatus === 'Pending' || (!empty($u['force_password_change']) && !empty($u['set_password_token'])));
                                $isNotActivated = !empty($u['not_activated']);
                                $statusBadge = match ($uStatus) {
                                    'Pending'  => badge('Pending Activation', 'warning'),
                                    'Disabled' => badge('Deactivated', 'danger'),
                                    'Inactive' => badge('Inactive', 'neutral'),
                                    default    => badge('Active', 'success'),
                                };
                                ?>
                                <td><?= $statusBadge ?></td>
                                <td><?= e($u[4]) ?></td>
                                <td>
                                    <?php if ($isNotActivated): ?>
                                        <span style="font-size:12px; color:var(--gray-500);">Awaiting patient activation</span>
                                    <?php else: ?>
                                    <div class="row-actions" style="flex-wrap:wrap; gap:8px;">
                                        <a href="<?= e(url(['page' => 'users', 'modal' => 'User details', 'user_id' => $uId, 'email' => $u[1]])) ?>" style="display:inline-flex; align-items:center; gap:4px;"><?= icon('eye', 15) ?> Details</a>
                                        <?php if (in_array($uRoleLabel, ['Doctor', 'Staff', 'Cashier'], true)): ?>
                                        <a href="<?= e(url(['page' => 'users', 'modal' => 'Edit user', 'user_id' => $uId, 'email' => $u[1]])) ?>"><?= icon('edit', 15) ?> Edit</a>
                                        <?php endif; ?>
                                        <?php if ($uStatus === 'Pending'): ?>
                                            <?= post_button('Resend Activation Email', 'resend_activation_email', ['user_id' => $uId, 'email' => $u[1], 'name' => $u[0], 'role' => $uRoleLabel], [
                                                'plain' => true,
                                                'class' => 'text-link',
                                                'html'  => 'Resend Activation Email',
                                                'confirm_msg' => 'Resend the secure activation email to ' . $u[1] . '?',
                                            ]) ?>
                                        <?php endif; ?>

                                        <?php
                                        $deactivateConfirm = ($uRoleLabel === 'Admin')
                                            ? 'Are you sure you want to deactivate this Administrator account for turnover? All records managed by this administrator will be preserved and moved to the Archive.'
                                            : 'Are you sure you want to ' . ($uStatus === 'Active' ? 'deactivate' : 'reactivate') . ' this account?';
                                        ?>
                                        <?= post_button('', 'toggle_user', ['email' => $u[1], 'role' => $uRoleLabel], [
                                            'plain'       => true,
                                            'class'       => $uStatus === 'Active' ? 'danger-link' : 'text-link',
                                            'html'        => $uStatus === 'Active' ? ($uRoleLabel === 'Admin' ? 'Deactivate (Turnover)' : 'Deactivate') : 'Reactivate',
                                            'confirm_msg' => $deactivateConfirm,
                                        ]) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding: 28px 20px;">
                <strong>No <?= e(strtolower($catMeta['title'])) ?> found</strong>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
