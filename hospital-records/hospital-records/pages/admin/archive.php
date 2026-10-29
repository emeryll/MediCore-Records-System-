<?php
[$from, $to] = range_params();
$q      = trim((string) ($_GET['q'] ?? ''));
$type_  = (string) ($_GET['type'] ?? 'All record types');

$archiveSource = $state['archive'] ?? archive_records();
$records = array_values(array_filter(
    $archiveSource,
    static fn ($r) => ($type_ === 'All record types' || $r[2] === $type_)
        && in_range($r[3], $from, $to)
        && row_matches($r, $q)
));

$toolbar = toolbar_open('archive')
    . search_box('Search archived records...', 'q', $q)
    . '<div class="toolbar-right">'
    . date_range_fields($from, $to)
    . select_field('', 'type', ['All record types', 'User account', 'Department', 'Room', 'Role', 'Medical record', 'Billing', 'Appointment', 'Admission', 'Lab result'], $type_)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title(
    'Archive',
    'Find and restore archived users, departments, rooms, roles, and hospital records.',
    button('Export archive', ['variant' => 'secondary', 'href' => url(['page' => 'archive', 'export' => 1, 'from' => $from, 'to' => $to])])
) ?>

<div class="archive-summary">
    <div><span>Matching records</span><strong><?= e((string) count($records)) ?></strong><small>In selected date range</small></div>
    <div><span>Storage used</span><strong><?= count($archiveSource) > 0 ? (count($archiveSource) * 2) . ' MB' : '0 MB' ?></strong><small>Encrypted secure storage</small></div>
    <div><span>Retention</span><strong>7 years</strong><small>Policy compliant</small></div>
</div>

<?= table_open(['Archive ID', 'Record / Subject', 'Record type', 'Archived', 'Archived by', 'Actions'], $toolbar) ?>
<?php foreach ($records as $r): ?>
    <tr>
        <td><code><?= e($r[0]) ?></code></td>
        <td><strong><?= e($r[1]) ?></strong></td>
        <td><?= badge($r[2], 'neutral') ?></td>
        <td><?= e($r[3]) ?></td>
        <td><?= e($r[4]) ?></td>
        <td><?= post_button('', 'restore_archive', ['key' => $r[0]], ['plain' => true, 'class' => 'text-link', 'html' => 'Restore']) ?></td>
    </tr>
<?php endforeach; ?>
<?= table_close(count($records) . ' of ' . count($archiveSource) . ' records', false) ?>

<?php if (!$records): ?>
    <?= filtered_empty('No archived records match these filters.') ?>
<?php endif; ?>
