<?php
$q     = trim((string) ($_GET['q'] ?? ''));
$user_ = (string) ($_GET['user'] ?? 'All users');
[$from, $to] = range_params();

$auditSource = $state['audit'] ?? audit_rows();
$rows = array_values(array_filter(
    $auditSource,
    static fn ($r) => (
        $user_ === 'All users'
        || strcasecmp((string) $r[5], $user_) === 0
        || ($user_ === 'Staff' && strcasecmp((string) $r[5], 'Front desk') === 0)
    ) && in_range($r[4], $from, $to)
        && row_matches($r, $q)
));

$toolbar = toolbar_open('audit-logs')
    . search_box('Search activity...', 'q', $q)
    . '<div class="toolbar-right">'
    . date_range_fields($from, $to)
    . select_field('', 'user', ['All users', 'Administrator', 'Doctor', 'Staff', 'Patient'], $user_)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title('Audit logs', 'Review system activity and trace every change to hospital records.') ?>

<?= table_open(['User', 'Action', 'Table name', 'Record ID', 'Date & time'], $toolbar) ?>
<?php foreach ($rows as $r): ?>
    <tr>
        <td><div class="person-cell"><?= mini_avatar($r[0]) ?><div><strong><?= e($r[0]) ?></strong><span><?= e($r[5]) ?></span></div></div></td>
        <td><?= badge($r[1], $r[1] === 'Disabled' || $r[1] === 'Deleted' ? 'danger' : ($r[1] === 'Created' || $r[1] === 'Restored' ? 'success' : 'info')) ?></td>
        <td><code><?= e($r[2]) ?></code></td>
        <td><?= e($r[3]) ?></td>
        <td><?= e($r[4]) ?></td>
    </tr>
<?php endforeach; ?>
<?= table_close(count($rows) . ' of ' . count($auditSource) . ' entries', false) ?>

<?php if (!$rows): ?>
    <?= filtered_empty('No activity matches these filters.') ?>
<?php endif; ?>
