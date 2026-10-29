<?php
$q       = trim((string) ($_GET['q'] ?? ''));
$status_ = (string) ($_GET['status'] ?? 'All unpaid');
$showNew = isset($_GET['new_bill']);

$allBills = [];
foreach ($state['bills'] ?? [] as $idx => $b) {
    $allBills[] = normalize_bill_row($b, $idx);
}

$unpaidAll = array_values(array_filter(
    $allBills,
    static fn ($b) => $b['status'] !== 'Paid' && $b['balance'] > 0
));

$rows = array_values(array_filter(
    $unpaidAll,
    static fn ($b) => ($status_ === 'All unpaid' || strcasecmp($b['status'], $status_) === 0)
        && row_matches([$b['bill_no'], $b['patient'], format_money($b['total']), $b['status'], $b['issued']], $q)
));

$totalOutstanding = 0.0;
foreach ($unpaidAll as $ub) {
    $totalOutstanding += $ub['balance'];
}

$toolbar = toolbar_open('unpaid-bills')
    . search_box('Search by bill no. or patient name...', 'q', $q)
    . '<div class="toolbar-right">'
    . select_field('', 'status', ['All unpaid', 'Unpaid', 'Partial'], $status_)
    . filter_button()
    . '</div>'
    . toolbar_close();

$patientList = $state['patients'] ?? [];
$patientOpts = array_merge(['Select patient'], array_column($patientList, 0));
?>
<?= page_title(
    'Unpaid Bills',
    'Review outstanding and partially paid patient invoices and collect payments.',
    button('New bill', ['variant' => 'secondary', 'icon' => 'plus', 'href' => url(['page' => 'unpaid-bills', 'new_bill' => 1])])
) ?>

<?php if ($showNew): ?>
    <div class="panel" style="padding: 20px 22px; margin-bottom: 20px; border-color: var(--teal-700);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <div>
                <h2 style="font-size:15px; color:var(--navy-900);">Create new patient bill</h2>
                <p style="font-size:11px; color:var(--gray-500);">Issue an itemized hospital invoice for consultation, room, or lab charges</p>
            </div>
            <a class="icon-btn" href="<?= e(url(['page' => 'unpaid-bills'])) ?>" aria-label="Close"><?= icon('close') ?></a>
        </div>
        <form method="post" action="index.php" style="display:grid; gap:14px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_bill">
            <input type="hidden" name="return" value="<?= e(url(['page' => 'unpaid-bills'])) ?>">
            <div class="form-grid">
                <?= select_field('Patient', 'patient', $patientOpts) ?>
                <?= field('Issued date', 'issued_preview', '', 'text', date('M d, Y')) ?>
            </div>
            <div class="form-grid three-col">
                <?= field('Charge item 1 (Description)', 'item_desc[]', 'e.g. Specialist consultation', 'text', 'Outpatient consultation & evaluation') ?>
                <?= field('Qty', 'item_qty[]', '1', 'number', '1') ?>
                <?= field('Unit price ($)', 'item_rate[]', '150.00', 'number', '150.00') ?>
            </div>
            <div class="form-grid three-col">
                <?= field('Charge item 2 (Optional)', 'item_desc[]', 'e.g. Laboratory panel', 'text', 'Complete blood count (CBC) & lab panel') ?>
                <?= field('Qty', 'item_qty[]', '1', 'number', '1') ?>
                <?= field('Unit price ($)', 'item_rate[]', '65.00', 'number', '65.00') ?>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <?= button('Cancel', ['variant' => 'secondary', 'href' => url(['page' => 'unpaid-bills'])]) ?>
                <?= button('Create bill', ['type' => 'submit', 'icon' => 'plus']) ?>
            </div>
        </form>
    </div>
<?php endif; ?>

<?= table_open(['Bill No.', 'Patient', 'Issued Date', 'Total', 'Paid So Far', 'Balance', 'Status', 'Actions'], $toolbar) ?>
<?php foreach ($rows as $b):
    $ptRec = find_patient_record($state, $b['patient']);
    $ptId  = $ptRec[8] ?? 'P-1001';
?>
    <tr>
        <td><code><?= e($b['bill_no']) ?></code></td>
        <td>
            <div class="person-cell">
                <?= mini_avatar($b['patient']) ?>
                <div>
                    <strong><?= e($b['patient']) ?></strong>
                    <span>ID: <?= e($ptId) ?> · <?= count($b['items']) ?> charge item<?= count($b['items']) === 1 ? '' : 's' ?></span>
                </div>
            </div>
        </td>
        <td><?= e($b['issued']) ?></td>
        <td><strong><?= e(format_money($b['total'])) ?></strong></td>
        <td style="color:var(--green); font-weight:600;"><?= e(format_money($b['paid'])) ?></td>
        <td><strong style="color:var(--red); font-size:13px;"><?= e(format_money($b['balance'])) ?></strong></td>
        <td><?= badge($b['status'], status_tone($b['status'])) ?></td>
        <td>
            <a class="btn btn-primary" style="height:34px; padding:0 13px; font-size:11px;"
               href="<?= e(url(['page' => 'process-payment', 'bill' => $b['bill_no']])) ?>">
                <?= icon('card', 15) ?><span>Collect payment</span>
            </a>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close(count($rows) . ' of ' . count($unpaidAll) . ' unpaid bills · Total balance: ' . format_money($totalOutstanding), false) ?>

<?php if (!$rows): ?>
    <?= filtered_empty('No unpaid bills match your search.') ?>
<?php endif; ?>
