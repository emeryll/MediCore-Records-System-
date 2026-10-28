<?php
$q     = trim((string) ($_GET['q'] ?? ''));
$user_ = (string) ($_GET['user'] ?? 'All users');

$rows = array_values(array_filter(
    audit_rows(),
    static fn ($r) => ($user_ === 'All users' || $r[0] === $user_) && row_matches($r, $q)
));

$toolbar = toolbar_open('audit-logs')
    . search_box('Search activity...', 'q', $q)
    . '<div class="toolbar-right">'
    . field('', 'date', '', 'date', (string) ($_GET['date'] ?? ''))
    . select_field('', 'user', ['All users', 'Amelia Hart', 'Olivia Bennett'], $user_)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title('Audit logs', 'Review system activity and trace every change to hospital records.') ?>

<?= table_open(['User', 'Action', 'Table name', 'Record ID', 'Date & time'], $toolbar) ?>
<?php foreach ($rows as $r): ?>
    <tr>
        <td><strong><?= e($r[0]) ?></strong></td>
        <td><?= badge($r[1], $r[1] === 'Disabled' ? 'danger' : ($r[1] === 'Created' ? 'success' : 'info')) ?></td>
        <td><code><?= e($r[2]) ?></code></td>
        <td><?= e($r[3]) ?></td>
        <td><?= e($r[4]) ?></td>
    </tr>
<?php endforeach; ?>
<?= table_close('1–5 of 48') ?>

<?php if (!$rows): ?>
    <?= filtered_empty('No activity matches these filters.') ?>
<?php endif; ?>
