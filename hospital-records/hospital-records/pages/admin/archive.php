<?php
$period = in_array($_GET['period'] ?? '', ['Daily', 'Monthly', 'Yearly'], true) ? $_GET['period'] : 'Daily';
$q      = trim((string) ($_GET['q'] ?? ''));
$type_  = (string) ($_GET['type'] ?? 'All record types');
$limit  = ['Daily' => 2, 'Monthly' => 4, 'Yearly' => 6][$period];

$records = array_values(array_filter(
    array_slice(archive_records(), 0, $limit),
    static fn ($r) => ($type_ === 'All record types' || $r[2] === $type_) && row_matches($r, $q)
));

$counts  = ['Daily' => '24', 'Monthly' => '684', 'Yearly' => '7,248'];
$storage = ['Daily' => '1.8 GB', 'Monthly' => '38 GB', 'Yearly' => '412 GB'];

$toolbar = toolbar_open('archive', ['period' => $period])
    . search_box('Search archived records...', 'q', $q)
    . '<div class="toolbar-right">'
    . select_field('', 'type', ['All record types', 'Medical record', 'Billing', 'Appointment', 'Admission', 'Lab result'], $type_)
    . field('', 'date', '', 'date', (string) ($_GET['date'] ?? ''))
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title(
    'Archive',
    'Find and manage archived hospital records by reporting period.',
    button('Export archive', ['variant' => 'secondary', 'href' => url(['page' => 'archive', 'period' => $period, 'export' => 1])])
) ?>

<div class="archive-summary">
    <div><span>Archived records</span><strong><?= e($counts[$period]) ?></strong><small>In the selected <?= e(strtolower($period)) ?> view</small></div>
    <div><span>Storage used</span><strong><?= e($storage[$period]) ?></strong><small>Encrypted secure storage</small></div>
    <div><span>Retention</span><strong>7 years</strong><small>Policy compliant</small></div>
</div>

<div class="period-switch" aria-label="Archive period">
    <?php foreach (['Daily', 'Monthly', 'Yearly'] as $item): ?>
        <a class="<?= $period === $item ? 'active' : '' ?>" href="<?= e(url(['page' => 'archive', 'period' => $item])) ?>"><?= e($item) ?></a>
    <?php endforeach; ?>
</div>

<?= table_open(['Archive ID', 'Patient', 'Record type', 'Archived', 'Archived by', 'Actions'], $toolbar) ?>
<?php foreach ($records as $r): ?>
    <tr>
        <td><code><?= e($r[0]) ?></code></td>
        <td><strong><?= e($r[1]) ?></strong></td>
        <td><?= badge($r[2], 'neutral') ?></td>
        <td><?= e($r[3]) ?></td>
        <td><?= e($r[4]) ?></td>
        <td><?= notify_button('', $r[0] . ' restored to active records', ['plain' => true, 'class' => 'text-link', 'html' => 'Restore']) ?></td>
    </tr>
<?php endforeach; ?>
<?= table_close('1–' . count($records) . ' of ' . count($records), false) ?>

<?php if (!$records): ?>
    <?= filtered_empty('No archived records match these filters.') ?>
<?php endif; ?>
