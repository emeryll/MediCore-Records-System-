<?php
$q       = trim((string) ($_GET['q'] ?? ''));
$status_ = (string) ($_GET['status'] ?? 'All statuses');

$rows = array_values(array_filter(
    lab_rows(),
    static fn ($r) => ($status_ === 'All statuses' || $r[3] === $status_) && row_matches([$r[0], $r[1], $r[2]], $q)
));

$toolbar = toolbar_open('lab-results')
    . search_box('Search tests or patients...', 'q', $q)
    . '<div class="toolbar-right">'
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
        <td><strong><?= e($r[1]) ?></strong></td>
        <td><?= e($r[2]) ?></td>
        <td><?= badge($r[3], $r[3] === 'Done' ? 'success' : 'warning') ?></td>
        <td><?= e($r[4]) ?></td>
        <td>
            <?php if ($r[3] === 'Done'): ?>
                <a class="text-link" href="<?= e(url(['page' => 'lab-results', 'detail' => 'lab'])) ?>">View result <?= icon('arrow', 14) ?></a>
            <?php else: ?>
                <span class="muted">Awaiting result</span>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close('1–5 of 48') ?>

<?php if (!$rows): ?>
    <?= filtered_empty('No lab results match these filters.') ?>
<?php endif; ?>
