<?php
$q       = trim((string) ($_GET['q'] ?? ''));
$status_ = (string) ($_GET['status'] ?? 'All statuses');
[$from, $to] = range_params();

$rows = array_values(array_filter(
    $state['bills'],
    static fn ($r) => ($status_ === 'All statuses' || $r[2] === $status_) && in_range($r[3], $from, $to) && row_matches($r, $q)
));

$toolbar = toolbar_open('billing')
    . search_box('Search bills...', 'q', $q)
    . '<div class="toolbar-right">'
    . date_range_fields($from, $to)
    . select_field('', 'status', ['All statuses', 'Paid', 'Pending', 'Unpaid'], $status_)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title('Billing', 'Track patient invoices, payments, and outstanding balances.') ?>

<?= table_open(['Patient', 'Amount', 'Status', 'Date', 'Receipt', 'Actions'], $toolbar) ?>
<?php foreach ($rows as $r): ?>
    <tr>
        <td><strong><?= e($r[0]) ?></strong></td>
        <td><strong><?= e($r[1]) ?></strong></td>
        <td><?= badge($r[2], status_tone($r[2])) ?></td>
        <td><?= e($r[3]) ?></td>
        <td><?= isset($state['receipts'][$r[0]]) ? proof_thumb($state['receipts'][$r[0]], 'Receipt for ' . $r[0]) : '—' ?></td>
        <td><a class="text-link" href="<?= e(url(['page' => 'billing', 'detail' => 'bill', 'patient' => $r[0]])) ?>">View bill <?= icon('arrow', 14) ?></a></td>
    </tr>
<?php endforeach; ?>
<?= table_close(count($rows) . ' bills', false) ?>

<?php if (!$rows): ?>
    <?= filtered_empty('No bills match these filters.') ?>
<?php endif; ?>
