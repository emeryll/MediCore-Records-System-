<?php
declare(strict_types=1);

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
$paged = paginate_rows($rows, 12, 'p');

$toolbar = toolbar_open('audit-logs')
    . search_box('Search user, action, table, or record ID...', 'q', $q)
    . '<div class="toolbar-right">'
    . date_range_fields($from, $to)
    . select_field('', 'user', ['All users', 'Administrator', 'Doctor', 'Staff', 'Cashier', 'Patient'], $user_)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title('Audit Log', 'Read-only log of important actions (logins, record edits, discharges, payments, user changes).') ?>

<?= table_open(['Actor & Role', 'Action', 'Module / Table', 'Record ID', 'Timestamp'], $toolbar) ?>
<?php foreach ($paged['rows'] as $r): ?>
    <tr>
        <td><div class="person-cell"><?= mini_avatar($r[0]) ?><div><strong><?= e($r[0]) ?></strong><span><?= e($r[5]) ?></span></div></div></td>
        <td><?= badge($r[1], status_tone($r[1])) ?></td>
        <td><code><?= e($r[2]) ?></code></td>
        <td><?= e($r[3]) ?></td>
        <td><?= e($r[4]) ?></td>
    </tr>
<?php endforeach; ?>
<?= table_close('audit entry', true, $paged['total'], $paged['page'], $paged['pages']) ?>

<?php if (!$paged['rows']): ?>
    <?= filtered_empty('No activity matches these filters.') ?>
<?php endif; ?>
