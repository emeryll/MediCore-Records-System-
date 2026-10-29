<?php
$todayYmd = date('Y-m-d');
$allBills = [];
foreach ($state['bills'] ?? [] as $idx => $b) {
    $allBills[] = normalize_bill_row($b, $idx);
}

$unpaidBills = array_values(array_filter(
    $allBills,
    static fn ($b) => $b['status'] !== 'Paid' && $b['balance'] > 0
));

$totalUnpaidBalance = 0.0;
foreach ($unpaidBills as $ub) {
    $totalUnpaidBalance += $ub['balance'];
}

$allPayments   = $state['payments'] ?? [];
$todayPayments = array_values(array_filter(
    $allPayments,
    static fn ($p) => ($p['paid_date'] ?? '') === $todayYmd
        || str_starts_with((string) ($p['paid_at'] ?? ''), date('M d, Y'))
));

$todayCollected = 0.0;
foreach ($todayPayments as $tp) {
    $todayCollected += parse_money($tp['amount_paid'] ?? 0);
}

$recentPayments = array_slice($allPayments, 0, 6);

$actions = '<div class="button-pair">'
    . button('Unpaid bills', ['variant' => 'secondary', 'icon' => 'card', 'href' => url(['page' => 'unpaid-bills'])])
    . button('Process payment', ['icon' => 'clipboard', 'href' => url(['page' => 'process-payment'])])
    . '</div>';
?>
<?= page_title(
    'Cashier dashboard',
    date('l, F j, Y') . ' · Daily collections, pending invoices, and recent payment activity.',
    $actions
) ?>

<div class="stats-grid three">
    <?= stat_card(
        "Today's total collected",
        format_money($todayCollected),
        count($todayPayments) . ' ' . (count($todayPayments) === 1 ? 'transaction' : 'transactions') . ' today',
        'card',
        'green'
    ) ?>
    <?= stat_card(
        'Unpaid bills count',
        (string) count($unpaidBills),
        format_money($totalUnpaidBalance) . ' total outstanding balance',
        'clipboard',
        'amber'
    ) ?>
    <?= stat_card(
        'Payments today',
        (string) count($todayPayments),
        count($allPayments) . ' total payments on record',
        'history',
        'teal'
    ) ?>
</div>

<div class="content-grid">
    <div class="panel wide">
        <div class="panel-head">
            <div>
                <h2>Recent payments</h2>
                <p>Latest official receipts and collections processed at the cashier desk</p>
            </div>
            <?= button('View all history', ['variant' => 'ghost', 'href' => url(['page' => 'payment-history'])]) ?>
        </div>
        <div class="schedule-list">
            <?php foreach ($recentPayments as $py): ?>
                <div class="schedule-row" style="grid-template-columns: 92px 18px 1fr auto auto; gap: 12px;">
                    <code><?= e($py['receipt_no'] ?? 'OR-1001') ?></code>
                    <span class="timeline-dot"></span>
                    <div class="schedule-person">
                        <?= mini_avatar($py['patient_name'] ?? 'Patient') ?>
                        <div>
                            <strong><?= e($py['patient_name'] ?? '—') ?></strong>
                            <span>Bill <?= e($py['bill_no'] ?? '—') ?> · <?= e($py['paid_at'] ?? '') ?> · Cashier: <?= e($py['cashier_name'] ?? 'Hospital Cashier') ?></span>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <strong style="color:var(--navy-900); font-size:13px;"><?= e(format_money($py['amount_paid'] ?? 0)) ?></strong>
                        <div style="margin-top:2px;"><?= badge($py['payment_method'] ?? 'Cash', status_tone($py['payment_method'] ?? 'Cash')) ?></div>
                    </div>
                    <a class="text-link" href="<?= e(url(['page' => 'payment-history', 'detail' => 'receipt', 'or' => $py['receipt_no'] ?? ''])) ?>">
                        <?= icon('printer', 14) ?> Receipt
                    </a>
                </div>
            <?php endforeach; ?>
            <?php if (!$recentPayments): ?>
                <div class="empty-state" style="padding:40px 20px;">
                    <?= icon('card', 28) ?>
                    <strong>No payments processed yet</strong>
                    <p>Select an invoice from Unpaid Bills to collect a payment and issue an official receipt.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <div>
                <h2>Pending bills queue</h2>
                <p>Invoices awaiting payment collection</p>
            </div>
            <?= badge((string) count($unpaidBills), count($unpaidBills) > 0 ? 'warning' : 'success') ?>
        </div>
        <div class="activity-list">
            <?php foreach (array_slice($unpaidBills, 0, 5) as $ub): ?>
                <div class="activity-row">
                    <?= mini_avatar($ub['patient']) ?>
                    <div>
                        <strong><?= e($ub['patient']) ?></strong>
                        <p><code><?= e($ub['bill_no']) ?></code> · Balance: <b><?= e(format_money($ub['balance'])) ?></b></p>
                    </div>
                    <a class="btn btn-primary" style="height:32px; padding:0 12px; font-size:11px;"
                       href="<?= e(url(['page' => 'process-payment', 'bill' => $ub['bill_no']])) ?>">
                        <span>Collect</span>
                    </a>
                </div>
            <?php endforeach; ?>
            <?php if (!$unpaidBills): ?>
                <div class="empty-state" style="padding:32px 16px;">
                    <?= icon('shield', 26) ?>
                    <strong>All bills settled</strong>
                    <p>There are no unpaid or partial bills right now.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
