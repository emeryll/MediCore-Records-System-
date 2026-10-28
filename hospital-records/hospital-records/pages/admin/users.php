<?php
$q    = trim((string) ($_GET['q'] ?? ''));
$role_ = (string) ($_GET['role'] ?? 'All roles');
$users = array_values(array_filter(
    $state['users'],
    static fn ($u) => ($role_ === 'All roles' || $u[2] === $role_)
        && row_matches([$u[0], $u[1]], $q)
));

$toolbar = toolbar_open('users')
    . search_box('Search users...', 'q', $q)
    . '<div class="toolbar-right">'
    . select_field('', 'role', ['All roles', 'Doctor', 'Nurse', 'Front desk'], $role_)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title(
    'Users',
    'Manage hospital staff accounts, access, and status.',
    button('Add user', ['icon' => 'plus', 'href' => url(['page' => 'users', 'modal' => 'Add user'])])
) ?>

<?= table_open(['User', 'Role', 'Status', 'Last login', 'Actions'], $toolbar) ?>
<?php foreach ($users as $u): ?>
    <tr>
        <td>
            <div class="person-cell">
                <?= mini_avatar($u[0]) ?>
                <div><strong><?= e($u[0]) ?></strong><span><?= e($u[1]) ?></span></div>
            </div>
        </td>
        <td><?= e($u[2]) ?></td>
        <td><?= badge($u[3], $u[3] === 'Active' ? 'success' : 'neutral') ?></td>
        <td><?= e($u[4]) ?></td>
        <td>
            <div class="row-actions">
                <a href="<?= e(url(['page' => 'users', 'modal' => 'Edit user'])) ?>"><?= icon('edit', 15) ?> Edit</a>
                <?= post_button('', 'toggle_user', ['email' => $u[1]], [
                    'plain' => true,
                    'class' => 'danger-link',
                    'html'  => $u[3] === 'Active' ? 'Disable' : 'Enable',
                ]) ?>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close('1–5 of 48') ?>

<?php if (!$users): ?>
    <?= filtered_empty('No users match these filters.') ?>
<?php endif; ?>
