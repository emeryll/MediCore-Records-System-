<?php
$rawDate = trim((string) ($_GET['date'] ?? date('Y-m-d')));
$selDate = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate) && strtotime($rawDate) !== false)
    ? $rawDate
    : date('Y-m-d');
$selFormatted = date('M d, Y', strtotime($selDate));

$allPayments = $state['payments'] ?? [];
$dayPayments = array_values(array_filter(
    $allPayments,
    static function ($p) use ($selDate, $selFormatted): bool {
        $pDate = (string) ($p['paid_date'] ?? '');
        if ($pDate !== '') {
            return $pDate === $selDate;
        }
        return str_starts_with((string) ($p['paid_at'] ?? ''), $selFormatted);
    }
));

$methods = ['Cash', 'Card', 'GCash', 'Insurance'];
$methodTotals = [];
$methodCounts = [];
foreach ($methods as $m) {
    $methodTotals[$m] = 0.0;
    $methodCounts[$m] = 0;
}

$grandTotal = 0.0;
foreach ($dayPayments as $py) {
    $m   = (string) ($py['payment_method'] ?? 'Cash');
    $amt = parse_money($py['amount_paid'] ?? 0);
    if (!isset($methodTotals[$m])) {
        $methodTotals[$m] = 0.0;
        $methodCounts[$m] = 0;
    }
    $methodTotals[$m] += $amt;
    $methodCounts[$m]++;
    $grandTotal += $amt;
}

$actions = '<div class="button-pair">'
    . '<button type="button" class="btn btn-primary" onclick="window.print()">' . icon('printer', 17) . '<span>Print Daily Summary</span></button>'
    . '</div>';
?>
<?= page_title(
    'Daily Collection Report',
    'Collection totals per payment method and printable end-of-day cashier summary for ' . date('F j, Y', strtotime($selDate)) . '.',
    $actions
) ?>

<div class="panel" style="padding: 14px 20px; margin-bottom: 20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
    <div>
        <strong style="font-size:13px; color:var(--navy-900);">Report Date: <?= e(date('l, F j, Y', strtotime($selDate))) ?></strong>
        <p style="font-size:11px; color:var(--gray-500); margin-top:2px;">Select a date to view and print that day's collection breakdown</p>
    </div>
    <form method="get" action="index.php" style="display:flex; align-items:center; gap:10px;">
        <input type="hidden" name="page" value="daily-report">
        <span class="input-wrap">
            <input type="date" name="date" value="<?= e($selDate) ?>" max="<?= date('Y-m-d') ?>" onchange="this.form.submit()">
        </span>
        <button type="submit" class="btn btn-secondary"><span>Apply Date</span></button>
        <?php if ($selDate !== date('Y-m-d')): ?>
            <a class="btn btn-ghost" href="<?= e(url(['page' => 'daily-report'])) ?>"><span>Today</span></a>
        <?php endif; ?>
    </form>
</div>

<div class="stats-grid">
    <?= stat_card('Cash Collections', format_money($methodTotals['Cash']), $methodCounts['Cash'] . ' ' . ($methodCounts['Cash'] === 1 ? 'payment' : 'payments'), 'card', 'green') ?>
    <?= stat_card('Card Collections', format_money($methodTotals['Card']), $methodCounts['Card'] . ' ' . ($methodCounts['Card'] === 1 ? 'payment' : 'payments'), 'card', 'navy') ?>
    <?= stat_card('GCash Collections', format_money($methodTotals['GCash']), $methodCounts['GCash'] . ' ' . ($methodCounts['GCash'] === 1 ? 'payment' : 'payments'), 'card', 'teal') ?>
    <?= stat_card('Insurance Claims', format_money($methodTotals['Insurance']), $methodCounts['Insurance'] . ' ' . ($methodCounts['Insurance'] === 1 ? 'payment' : 'payments'), 'shield', 'amber') ?>
</div>

<div class="invoice" style="max-width: 100%; margin-top: 8px;">
    <div class="invoice-head">
        <div>
            <?= logo() ?>
            <p style="font-size:11px; color:var(--gray-500); margin-top:6px;">
                Daily Cashier Collection &amp; Remittance Summary
            </p>
        </div>
        <div style="text-align:right;">
            <?= badge('DAILY REPORT', 'info') ?>
            <div style="margin-top:8px; font-size:16px; font-weight:800; color:var(--navy-900);">
                <?= e(date('M d, Y', strtotime($selDate))) ?>
            </div>
            <div style="font-size:11px; color:var(--gray-500);">
                Prepared by: <?= e(current_user_name($role)) ?>
            </div>
        </div>
    </div>

    <div style="margin: 22px 0;">
        <h3 style="font-size:13px; color:var(--navy-900); margin-bottom:10px; text-transform:uppercase; letter-spacing:.6px;">
            1. Summary of Collections by Payment Method
        </h3>
        <table>
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th style="width:160px; text-align:center;">Transactions</th>
                    <th style="width:160px; text-align:right;">Share (%)</th>
                    <th style="width:180px; text-align:right;">Total Collected</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($methods as $m):
                    $mTot = $methodTotals[$m] ?? 0.0;
                    $mCnt = $methodCounts[$m] ?? 0;
                    $pct  = $grandTotal > 0 ? round(($mTot / $grandTotal) * 100, 1) . '%' : '0%';
                ?>
                    <tr>
                        <td><strong><?= e($m) ?></strong></td>
                        <td style="text-align:center;"><?= e((string) $mCnt) ?></td>
                        <td style="text-align:right;"><?= e($pct) ?></td>
                        <td style="text-align:right;"><strong><?= e(format_money($mTot)) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="invoice-total" style="width:320px; border-top:2px solid var(--gray-200); margin-top:10px;">
            <span style="font-weight:700; color:var(--navy-900);">Grand Total (<?= count($dayPayments) ?> txns)</span>
            <strong><?= e(format_money($grandTotal)) ?></strong>
        </div>
    </div>

    <div style="margin-top: 26px;">
        <h3 style="font-size:13px; color:var(--navy-900); margin-bottom:10px; text-transform:uppercase; letter-spacing:.6px;">
            2. Itemized Transactions for <?= e(date('M d, Y', strtotime($selDate))) ?>
        </h3>
        <table>
            <thead>
                <tr>
                    <th>Receipt No.</th>
                    <th>Bill No.</th>
                    <th>Patient</th>
                    <th>Method</th>
                    <th>Reference No.</th>
                    <th>Cashier</th>
                    <th>Time</th>
                    <th style="text-align:right;">Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dayPayments as $py): ?>
                    <tr>
                        <td><code><?= e($py['receipt_no'] ?? '') ?></code></td>
                        <td><code><?= e($py['bill_no'] ?? '') ?></code></td>
                        <td><strong><?= e($py['patient_name'] ?? '—') ?></strong></td>
                        <td><?= e($py['payment_method'] ?? 'Cash') ?></td>
                        <td><code><?= e(($py['reference_no'] ?? '') !== '' ? $py['reference_no'] : '—') ?></code></td>
                        <td><?= e($py['cashier_name'] ?? 'Hospital Cashier') ?></td>
                        <td><?= e($py['paid_at'] ?? '') ?></td>
                        <td style="text-align:right;"><strong><?= e(format_money($py['amount_paid'] ?? 0)) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$dayPayments): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:24px; color:var(--gray-500);">
                            No payments recorded on <?= e(date('F j, Y', strtotime($selDate))) ?>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top:34px; padding-top:20px; border-top:1px dashed var(--gray-200); display:flex; justify-content:space-between; align-items:end; font-size:11px; color:var(--gray-500);">
        <div>
            Generated on <?= e(date('M d, Y · H:i')) ?> · <strong><?= e(APP_NAME) ?> Health Hospital Records System</strong>
        </div>
        <div style="display:flex; gap:40px; text-align:center;">
            <div>
                <div style="border-top:1px solid var(--gray-400); padding-top:5px; min-width:170px; color:var(--navy-900); font-weight:600;">
                    <?= e(current_user_name($role)) ?>
                </div>
                <span>Prepared by (Cashier)</span>
            </div>
            <div>
                <div style="border-top:1px solid var(--gray-400); padding-top:5px; min-width:170px; color:var(--navy-900); font-weight:600;">
                    Accounting / Finance
                </div>
                <span>Verified &amp; Received by</span>
            </div>
        </div>
    </div>
</div>
