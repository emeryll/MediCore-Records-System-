<?php
[$from, $to] = range_params();
$q        = trim((string) ($_GET['q'] ?? ''));
$method_  = (string) ($_GET['method'] ?? 'All methods');
$cashier_ = (string) ($_GET['cashier'] ?? 'All cashiers');

$allPayments = $state['payments'] ?? [];

$cashierOpts = ['All cashiers'];
foreach ($allPayments as $py) {
    $cName = trim((string) ($py['cashier_name'] ?? ''));
    if ($cName !== '' && !in_array($cName, $cashierOpts, true)) {
        $cashierOpts[] = $cName;
    }
}
$curCashier = current_user_name($role);
if ($curCashier !== '' && !in_array($curCashier, $cashierOpts, true)) {
    $cashierOpts[] = $curCashier;
}

$rows = array_values(array_filter(
    $allPayments,
    static fn ($p) => ($method_ === 'All methods' || strcasecmp((string) ($p['payment_method'] ?? ''), $method_) === 0)
        && ($cashier_ === 'All cashiers' || strcasecmp((string) ($p['cashier_name'] ?? ''), $cashier_) === 0)
        && in_range((string) ($p['paid_date'] ?? ($p['paid_at'] ?? '')), $from, $to)
        && row_matches([
            $p['receipt_no'] ?? '',
            $p['bill_no'] ?? '',
            $p['patient_name'] ?? '',
            $p['payment_method'] ?? '',
            $p['reference_no'] ?? '',
            $p['cashier_name'] ?? '',
        ], $q)
));

$filteredTotal = 0.0;
foreach ($rows as $r) {
    $filteredTotal += parse_money($r['amount_paid'] ?? 0);
}

$toolbar = toolbar_open('payment-history')
    . search_box('Search receipt, bill, patient, or ref no...', 'q', $q)
    . '<div class="toolbar-right">'
    . date_range_fields($from, $to)
    . select_field('', 'method', ['All methods', 'Cash', 'Card', 'GCash', 'Insurance'], $method_)
    . select_field('', 'cashier', $cashierOpts, $cashier_)
    . filter_button()
    . '</div>'
    . toolbar_close();

$exportUrl = url([
    'page'    => 'payment-history',
    'export'  => 1,
    'q'       => $q !== '' ? $q : null,
    'from'    => $from !== '' ? $from : null,
    'to'      => $to !== '' ? $to : null,
    'method'  => $method_ !== 'All methods' ? $method_ : null,
    'cashier' => $cashier_ !== 'All cashiers' ? $cashier_ : null,
]);
?>
<?= page_title(
    'Payment History',
    'Complete log of processed payments with date range, payment method, and cashier filters.',
    button('Export CSV', ['variant' => 'secondary', 'icon' => 'clipboard', 'href' => $exportUrl])
) ?>

<?= table_open(['Receipt No.', 'Bill No.', 'Patient', 'Amount Paid', 'Method', 'Reference No.', 'Cashier', 'Date & Time', 'Actions'], $toolbar) ?>
<?php foreach ($rows as $py): ?>
    <tr>
        <td><code><?= e($py['receipt_no'] ?? 'OR-1001') ?></code></td>
        <td><code><?= e($py['bill_no'] ?? 'BILL-1001') ?></code></td>
        <td>
            <div class="person-cell">
                <?= mini_avatar($py['patient_name'] ?? 'Patient') ?>
                <div>
                    <strong><?= e($py['patient_name'] ?? '—') ?></strong>
                    <span>Received: <?= e(format_money($py['amount_received'] ?? ($py['amount_paid'] ?? 0))) ?> · Change: <?= e(format_money($py['change_due'] ?? 0)) ?></span>
                </div>
            </div>
        </td>
        <td><strong style="color:var(--navy-900);"><?= e(format_money($py['amount_paid'] ?? 0)) ?></strong></td>
        <td><?= badge($py['payment_method'] ?? 'Cash', status_tone($py['payment_method'] ?? 'Cash')) ?></td>
        <td><code><?= e(($py['reference_no'] ?? '') !== '' ? $py['reference_no'] : '—') ?></code></td>
        <td><?= e($py['cashier_name'] ?? 'Hospital Cashier') ?></td>
        <td><?= e($py['paid_at'] ?? '') ?></td>
        <td>
            <div class="row-actions">
                <a href="<?= e(url(['page' => 'payment-history', 'detail' => 'receipt', 'or' => $py['receipt_no'] ?? ''])) ?>">
                    <?= icon('printer', 15) ?> Receipt
                </a>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close(count($rows) . ' of ' . count($allPayments) . ' payments · Total collected: ' . format_money($filteredTotal), false) ?>

<?php if (!$rows): ?>
    <?= filtered_empty('No payment records match the selected filters.') ?>
<?php endif; ?>
