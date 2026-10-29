<?php
$q       = trim((string) ($_GET['q'] ?? ''));
$method_ = (string) ($_GET['method'] ?? 'All methods');
$cashier = (string) ($_GET['cashier'] ?? 'All cashiers');
[$from, $to] = range_params();

// Quick preset handling
$preset = (string) ($_GET['preset'] ?? '');
if ($preset === 'today') {
    $from = date('Y-m-d');
    $to   = date('Y-m-d');
} elseif ($preset === 'week') {
    $from = date('Y-m-d', strtotime('monday this week'));
    $to   = date('Y-m-d');
} elseif ($preset === 'month') {
    $from = date('Y-m-01');
    $to   = date('Y-m-d');
} elseif ($preset === 'all') {
    $from = '';
    $to   = '';
}

$allPayments = $state['payments'] ?? [];

// Available cashiers for filter dropdown
$cashierOpts = ['All cashiers'];
foreach ($allPayments as $py) {
    $cn = trim((string) ($py['cashier_name'] ?? ''));
    if ($cn !== '' && !in_array($cn, $cashierOpts, true)) {
        $cashierOpts[] = $cn;
    }
}

// Filter payments
$filteredPayments = array_values(array_filter(
    $allPayments,
    static function ($py) use ($from, $to, $method_, $cashier, $q): bool {
        $pDate = (string) ($py['paid_date'] ?? '');
        if ($pDate === '' && !empty($py['paid_at'])) {
            $ts = strtotime($py['paid_at']);
            if ($ts !== false) {
                $pDate = date('Y-m-d', $ts);
            }
        }
        if (!in_range($pDate, $from, $to)) {
            return false;
        }
        if ($method_ !== 'All methods' && strcasecmp((string) ($py['payment_method'] ?? ''), $method_) !== 0) {
            return false;
        }
        if ($cashier !== 'All cashiers' && strcasecmp((string) ($py['cashier_name'] ?? ''), $cashier) !== 0) {
            return false;
        }
        if ($q !== '' && !row_matches([
            (string) ($py['receipt_no'] ?? ''),
            (string) ($py['bill_no'] ?? ''),
            (string) ($py['patient_name'] ?? ''),
            (string) ($py['reference_no'] ?? ''),
            (string) ($py['payment_method'] ?? ''),
            (string) ($py['cashier_name'] ?? ''),
        ], $q)) {
            return false;
        }
        return true;
    }
));

// Financial calculations
$totalRevenue     = 0.0;
$methodBreakdown  = [
    'Cash'      => ['count' => 0, 'total' => 0.0],
    'Card'      => ['count' => 0, 'total' => 0.0],
    'GCash'     => ['count' => 0, 'total' => 0.0],
    'Insurance' => ['count' => 0, 'total' => 0.0],
];

foreach ($filteredPayments as $py) {
    $amt = parse_money($py['amount_paid'] ?? 0);
    $totalRevenue += $amt;
    $m = (string) ($py['payment_method'] ?? 'Cash');
    if (!isset($methodBreakdown[$m])) {
        $methodBreakdown[$m] = ['count' => 0, 'total' => 0.0];
    }
    $methodBreakdown[$m]['count']++;
    $methodBreakdown[$m]['total'] += $amt;
}

$txCount = count($filteredPayments);
$avgTx   = $txCount > 0 ? round($totalRevenue / $txCount, 2) : 0.0;

// Total outstanding uncollected receivables across all hospital bills
$totalReceivables = 0.0;
$unpaidBillsCount = 0;
foreach ($state['bills'] ?? [] as $bIdx => $b) {
    $nb = normalize_bill_row($b, $bIdx);
    if ($nb['status'] !== 'Paid' && $nb['balance'] > 0) {
        $totalReceivables += $nb['balance'];
        $unpaidBillsCount++;
    }
}

// Active date range text description
$dateRangeDesc = 'All recorded transactions';
if ($from !== '' && $to !== '') {
    $dateRangeDesc = date('M d, Y', strtotime($from)) . ' to ' . date('M d, Y', strtotime($to));
} elseif ($from !== '') {
    $dateRangeDesc = 'From ' . date('M d, Y', strtotime($from)) . ' onward';
} elseif ($to !== '') {
    $dateRangeDesc = 'Up to ' . date('M d, Y', strtotime($to));
}

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=hospital_sales_report_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Receipt No.', 'Bill No.', 'Patient Name', 'Amount Paid', 'Payment Method', 'Reference No.', 'Cashier', 'Date & Time']);
    foreach ($filteredPayments as $py) {
        fputcsv($out, [
            $py['receipt_no'] ?? '',
            $py['bill_no'] ?? '',
            $py['patient_name'] ?? '',
            number_format(parse_money($py['amount_paid'] ?? 0), 2, '.', ''),
            $py['payment_method'] ?? 'Cash',
            $py['reference_no'] ?? '—',
            $py['cashier_name'] ?? '',
            $py['paid_at'] ?? ($py['paid_date'] ?? ''),
        ]);
    }
    fclose($out);
    exit;
}

$exportUrl = url([
    'page'    => 'sales-records',
    'export'  => 1,
    'q'       => $q !== '' ? $q : null,
    'from'    => $from !== '' ? $from : null,
    'to'      => $to !== '' ? $to : null,
    'method'  => $method_ !== 'All methods' ? $method_ : null,
    'cashier' => $cashier !== 'All cashiers' ? $cashier : null,
]);

$actions = '<div class="button-pair">'
    . button('Export CSV', ['variant' => 'secondary', 'icon' => 'clipboard', 'href' => $exportUrl])
    . '<button type="button" class="btn btn-primary" onclick="window.print()">' . icon('printer', 17) . '<span>Print Sales Summary</span></button>'
    . '</div>';

$toolbar = toolbar_open('sales-records')
    . search_box('Search by receipt, bill, patient, or ref...', 'q', $q)
    . '<div class="toolbar-right">'
    . date_range_fields($from, $to)
    . select_field('', 'method', ['All methods', 'Cash', 'Card', 'GCash', 'Insurance'], $method_)
    . select_field('', 'cashier', $cashierOpts, $cashier)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title('Sales & Revenue Records', 'Financial summary and audit of collected payments, itemized by date and payment channel.', $actions) ?>

<!-- Quick Date Range Presets -->
<div class="appt-cats" style="margin-bottom: 20px;">
    <a class="appt-cat <?= ($from === '' && $to === '') ? 'active' : '' ?>" href="<?= e(url(['page' => 'sales-records', 'preset' => 'all'])) ?>">
        All Time
        <span class="appt-cat-count"><?= count($allPayments) ?></span>
    </a>
    <a class="appt-cat <?= ($preset === 'today' || ($from === date('Y-m-d') && $to === date('Y-m-d'))) ? 'active' : '' ?>" href="<?= e(url(['page' => 'sales-records', 'preset' => 'today'])) ?>">
        Today
    </a>
    <a class="appt-cat <?= ($preset === 'week') ? 'active' : '' ?>" href="<?= e(url(['page' => 'sales-records', 'preset' => 'week'])) ?>">
        This Week
    </a>
    <a class="appt-cat <?= ($preset === 'month') ? 'active' : '' ?>" href="<?= e(url(['page' => 'sales-records', 'preset' => 'month'])) ?>">
        This Month
    </a>
</div>

<!-- KPI Stat Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
    <?= stat_card(
        'Total Revenue Collected',
        format_money($totalRevenue),
        $txCount . ' ' . ($txCount === 1 ? 'transaction' : 'transactions') . ' (' . $dateRangeDesc . ')',
        'card',
        'green'
    ) ?>
    <?= stat_card(
        'Total Transactions',
        (string) $txCount,
        $txCount > 0 ? 'Recorded across ' . count(array_filter($methodBreakdown, fn($m) => $m['count'] > 0)) . ' payment channels' : 'No transactions recorded',
        'clipboard',
        'teal'
    ) ?>
    <?= stat_card(
        'Average Transaction',
        format_money($avgTx),
        'Per payment collection',
        'grid',
        'blue'
    ) ?>
    <?= stat_card(
        'Outstanding Receivables',
        format_money($totalReceivables),
        $unpaidBillsCount . ' unpaid or partial patient bills',
        'history',
        'amber'
    ) ?>
</div>

<!-- Payment Channel Breakdown -->
<div class="panel" style="margin-bottom: 24px;">
    <div class="panel-head">
        <div>
            <h2>Payment Channel Breakdown</h2>
            <p>Collections distribution by payment method for: <strong><?= e($dateRangeDesc) ?></strong></p>
        </div>
    </div>
    <div style="padding: 20px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
        <?php foreach ($methodBreakdown as $mName => $mData):
            $pct = $totalRevenue > 0 ? round(($mData['total'] / $totalRevenue) * 100, 1) : 0;
            $mTone = match ($mName) {
                'Cash'      => 'var(--green)',
                'Card'      => 'var(--blue)',
                'GCash'     => 'var(--teal-800)',
                'Insurance' => 'var(--navy-900)',
                default     => 'var(--gray-700)',
            };
        ?>
            <div style="background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 8px; padding: 16px; display: grid; gap: 8px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <strong style="color:var(--navy-900); font-size:13px;"><?= e($mName) ?></strong>
                    <span style="font-size:11px; font-weight:700; color:<?= $mTone ?>;"><?= $pct ?>%</span>
                </div>
                <div style="font-size:18px; font-weight:800; color:var(--navy-900);">
                    <?= e(format_money($mData['total'])) ?>
                </div>
                <div style="width: 100%; height: 6px; background: var(--gray-200); border-radius: 3px; overflow: hidden;">
                    <div style="width: <?= min(100, $pct) ?>%; height: 100%; background: <?= $mTone ?>; border-radius: 3px;"></div>
                </div>
                <small style="color:var(--gray-500); font-size:11px;"><?= $mData['count'] ?> transaction<?= $mData['count'] !== 1 ? 's' : '' ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Transactions Table -->
<?= table_open(['Receipt No.', 'Bill No.', 'Date & Time', 'Patient', 'Payment Method', 'Cashier', 'Amount Paid', 'Action'], $toolbar) ?>
<?php foreach ($filteredPayments as $py): ?>
    <tr>
        <td><code style="font-weight:700; color:var(--teal-800);"><?= e($py['receipt_no'] ?? 'OR-1001') ?></code></td>
        <td><code><?= e($py['bill_no'] ?? '—') ?></code></td>
        <td><?= e($py['paid_at'] ?? ($py['paid_date'] ?? '—')) ?></td>
        <td>
            <div class="person-cell">
                <?= mini_avatar((string) ($py['patient_name'] ?? 'Patient')) ?>
                <strong><?= e($py['patient_name'] ?? '—') ?></strong>
            </div>
        </td>
        <td>
            <div style="display:flex; align-items:center; gap:6px;">
                <?= badge($py['payment_method'] ?? 'Cash', status_tone($py['payment_method'] ?? 'Cash')) ?>
                <?php if (!empty($py['reference_no']) && $py['reference_no'] !== '—'): ?>
                    <code style="font-size:10px; color:var(--gray-600);"><?= e($py['reference_no']) ?></code>
                <?php endif; ?>
            </div>
        </td>
        <td><?= e($py['cashier_name'] ?? 'Hospital Cashier') ?></td>
        <td><strong style="color:var(--green); font-size:13px;"><?= e(format_money($py['amount_paid'] ?? 0)) ?></strong></td>
        <td>
            <a class="text-link" href="<?= e(url(['page' => 'sales-records', 'detail' => 'receipt', 'or' => $py['receipt_no'], 'from_page' => 'sales-records'])) ?>">
                <?= icon('printer', 14) ?> <span style="margin-left:2px;">Official receipt</span>
            </a>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close(count($filteredPayments) . ' of ' . count($allPayments) . ' transactions · Total: ' . format_money($totalRevenue), false) ?>

<?php if (!$filteredPayments): ?>
    <?= filtered_empty('No payment transactions match the selected date range and filters.') ?>
<?php endif; ?>
