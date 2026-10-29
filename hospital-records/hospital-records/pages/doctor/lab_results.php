<?php
$q       = trim((string) ($_GET['q'] ?? ''));
$status_ = (string) ($_GET['status'] ?? 'All statuses');
[$from, $to] = range_params();

$rows = array_values(array_filter(
    $state['lab_results'],
    static fn ($r) => ($status_ === 'All statuses' || $r[3] === $status_)
        && in_range($r[4], $from, $to)
        && row_matches([$r[0], $r[1], $r[2]], $q)
));

$toolbar = toolbar_open('lab-results')
    . search_box('Search tests or patients...', 'q', $q)
    . '<div class="toolbar-right">'
    . date_range_fields($from, $to)
    . select_field('', 'status', ['All statuses', 'Pending', 'Done'], $status_)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title('Lab results', 'Review ordered tests and patient results.') ?>

<?= table_open(['Test ID', 'Patient', 'Test', 'Status', 'Date', 'Result'], $toolbar) ?>
<?php foreach ($rows as $r): ?>
    <tr>
        <td><code><?= e($r[0]) ?></code></td>
        <td><div class="person-cell"><?= mini_avatar($r[1]) ?><strong><?= e($r[1]) ?></strong></div></td>
        <td><?= e($r[2]) ?></td>
        <td><?= badge($r[3], $r[3] === 'Done' ? 'success' : 'warning') ?></td>
        <td><?= e($r[4]) ?></td>
        <td>
            <?php if ($r[3] === 'Done'): ?>
                <a class="text-link" href="<?= e(url(['page' => 'lab-results', 'detail' => 'lab', 'test' => $r[0]])) ?>">View result <?= icon('arrow', 14) ?></a>
            <?php else: ?>
                <?= post_button('Mark done', 'complete_lab', ['test_id' => $r[0]], ['variant' => 'secondary']) ?>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close(count($rows) . ' of ' . count($state['lab_results']) . ' results', false) ?>

<?php if (!$rows): ?>
    <?= filtered_empty('No lab results match these filters.') ?>
<?php endif; ?>
