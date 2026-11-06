<?php
declare(strict_types=1);

$q         = trim((string) ($_GET['q'] ?? ''));
$status_   = (string) ($_GET['status'] ?? 'All bills');
$showNew   = isset($_GET['new_bill']);
$adjBillNo = trim((string) ($_GET['adjust_bill'] ?? ''));

$allBills = [];
foreach ($state['bills'] ?? [] as $idx => $b) {
    $norm        = normalize_bill_row($b, $idx);
    $ptRec       = find_patient_record($state, $norm['patient']);
    $norm['mrn'] = $ptRec ? patient_mrn_display($ptRec) : ($ptRec[8] ?? '');
    $allBills[]  = $norm;
}

$rows = array_values(array_filter(
    $allBills,
    static function ($b) use ($status_, $q): bool {
        if ($status_ === 'Unpaid & Partial' && $b['status'] === 'Paid') {
            return false;
        }
        if (in_array($status_, ['Unpaid', 'Partial', 'Paid'], true) && strcasecmp($b['status'], $status_) !== 0) {
            return false;
        }
        return row_matches([$b['bill_no'], $b['patient'], $b['mrn'], format_money($b['total']), $b['status'], $b['issued']], $q);
    }
));
$paged = paginate_rows($rows, 10, 'p');

$totalOutstanding = 0.0;
foreach ($allBills as $ub) {
    if ($ub['status'] !== 'Paid') {
        $totalOutstanding += $ub['balance'];
    }
}

$toolbar = toolbar_open('unpaid-bills')
    . search_box('Search by patient name, MRN, or bill no...', 'q', $q)
    . '<div class="toolbar-right">'
    . select_field('', 'status', ['All bills', 'Unpaid & Partial', 'Unpaid', 'Partial', 'Paid'], $status_)
    . filter_button()
    . '</div>'
    . toolbar_close();

$patientList = $state['patients'] ?? [];
$patientOpts = array_merge(['Select patient'], array_column($patientList, 0));
$priceItems  = array_values(array_filter($state['price_list'] ?? [], static fn ($pl) => !empty($pl['active'])));
?>
<?= page_title(
    'Billing & Patient Statements',
    'Search by patient name or MRN, view Unpaid and Paid bills, and add manual charges or adjustments with a required reason.',
    button('Create manual bill', ['variant' => 'secondary', 'icon' => 'plus', 'href' => url(['page' => 'unpaid-bills', 'new_bill' => 1])])
) ?>

<?php if ($adjBillNo !== ''): ?>
    <div class="panel" style="padding: 20px 22px; margin-bottom: 20px; border-color: var(--teal-700); background: var(--teal-50);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <div>
                <h2 style="font-size:15px; color:var(--navy-900);">Add Manual Charge / Adjustment to <?= e($adjBillNo) ?></h2>
                <p style="font-size:11.5px; color:var(--gray-600);">Select an item from the Price List or enter a custom adjustment with a mandatory documented reason.</p>
            </div>
            <a class="icon-btn" href="<?= e(url(['page' => 'unpaid-bills'])) ?>" aria-label="Close"><?= icon('close') ?></a>
        </div>
        <form method="post" action="index.php" style="display:grid; gap:14px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_manual_charge">
            <input type="hidden" name="bill_no" value="<?= e($adjBillNo) ?>">
            <div class="form-grid three">
                <label class="field">
                    <span class="field-label">Quick Select from Price List</span>
                    <span class="input-wrap">
                        <select onchange="if(this.value){ var p=this.value.split('|'); document.getElementById('adj-desc').value=p[0]; document.getElementById('adj-rate').value=p[1]; }">
                            <option value="">-- Custom or choose from Price List --</option>
                            <?php foreach ($priceItems as $pl): ?>
                                <option value="<?= e($pl['name'] . '|' . $pl['price']) ?>"><?= e($pl['name'] . ' (' . format_money((float) $pl['price']) . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= icon('down', 16) ?>
                    </span>
                </label>
                <label class="field">
                    <span class="field-label">Charge / Adjustment Description *</span>
                    <span class="input-wrap"><input type="text" name="item_desc" id="adj-desc" placeholder="e.g. Medical supplies / Senior Citizen discount" required></span>
                </label>
                <div style="display:grid; grid-template-columns:80px 1fr; gap:8px;">
                    <?= field('Qty *', 'item_qty', '1', 'number', '1', ['required' => true]) ?>
                    <label class="field">
                        <span class="field-label">Unit Price (₱) *</span>
                        <span class="input-wrap"><input type="number" step="0.01" name="item_rate" id="adj-rate" placeholder="350.00" required></span>
                    </label>
                </div>
            </div>
            <?= field('Required Reason for Manual Charge / Adjustment *', 'reason', 'e.g. Emergency room supplies used during triage / Approved billing adjustment', 'text', '', ['required' => true]) ?>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <?= button('Cancel', ['variant' => 'secondary', 'href' => url(['page' => 'unpaid-bills'])]) ?>
                <?= button('Save Charge to Bill', ['type' => 'submit', 'icon' => 'check']) ?>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if ($showNew): ?>
    <div class="panel" style="padding: 20px 22px; margin-bottom: 20px; border-color: var(--teal-700);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <div>
                <h2 style="font-size:15px; color:var(--navy-900);">Create New Patient Bill</h2>
                <p style="font-size:11px; color:var(--gray-500);">Issue an itemized hospital invoice using West Metro Medical Center Price List rates (₱)</p>
            </div>
            <a class="icon-btn" href="<?= e(url(['page' => 'unpaid-bills'])) ?>" aria-label="Close"><?= icon('close') ?></a>
        </div>
        <form method="post" action="index.php" style="display:grid; gap:14px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_bill">
            <input type="hidden" name="return" value="<?= e(url(['page' => 'unpaid-bills'])) ?>">
            <div class="form-grid">
                <?= select_field('Patient *', 'patient', $patientOpts) ?>
                <?= field('Reason / Billing Note *', 'reason', 'e.g. Walk-in outpatient diagnostic billing', 'text', 'Outpatient consultation & laboratory charges', ['required' => true]) ?>
            </div>
            <div class="form-grid three-col">
                <?= field('Charge item 1 (Description)', 'item_desc[]', 'e.g. Specialist Consultation Fee', 'text', 'Specialist Consultation Fee') ?>
                <?= field('Qty', 'item_qty[]', '1', 'number', '1') ?>
                <?= field('Unit price (₱)', 'item_rate[]', '800.00', 'number', '800.00') ?>
            </div>
            <div class="form-grid three-col">
                <?= field('Charge item 2 (Optional)', 'item_desc[]', 'e.g. Complete blood count', 'text', 'Complete blood count') ?>
                <?= field('Qty', 'item_qty[]', '1', 'number', '1') ?>
                <?= field('Unit price (₱)', 'item_rate[]', '350.00', 'number', '350.00') ?>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <?= button('Cancel', ['variant' => 'secondary', 'href' => url(['page' => 'unpaid-bills'])]) ?>
                <?= button('Create bill', ['type' => 'submit', 'icon' => 'plus']) ?>
            </div>
        </form>
    </div>
<?php endif; ?>

<?= table_open(['Bill No.', 'Patient & MRN', 'Issued Date', 'Total', 'Paid So Far', 'Balance', 'Status', 'Actions'], $toolbar) ?>
<?php foreach ($paged['rows'] as $b): ?>
    <tr>
        <td><code><?= e($b['bill_no']) ?></code></td>
        <td>
            <div class="person-cell">
                <?= mini_avatar($b['patient']) ?>
                <div>
                    <strong><?= e($b['patient']) ?></strong>
                    <span>MRN: <code><?= e($b['mrn'] ?: '—') ?></code> · <?= count($b['items']) ?> item<?= count($b['items']) === 1 ? '' : 's' ?></span>
                </div>
            </div>
        </td>
        <td><?= e($b['issued']) ?></td>
        <td><strong><?= e(format_money($b['total'])) ?></strong></td>
        <td style="color:var(--green); font-weight:600;"><?= e(format_money($b['paid'])) ?></td>
        <td><strong style="color:<?= $b['balance'] > 0 ? 'var(--red)' : 'var(--gray-700)' ?>; font-size:13px;"><?= e(format_money($b['balance'])) ?></strong></td>
        <td><?= badge($b['status'], status_tone($b['status'])) ?></td>
        <td>
            <div class="row-actions" style="gap:6px; flex-wrap:wrap;">
                <a class="btn btn-secondary" style="height:32px; padding:0 10px; font-size:11px;"
                   href="<?= e(url(['page' => 'unpaid-bills', 'adjust_bill' => $b['bill_no']])) ?>">
                    <?= icon('plus', 13) ?><span>Add Charge</span>
                </a>
                <?php if ($b['status'] !== 'Paid'): ?>
                    <a class="btn btn-primary" style="height:32px; padding:0 12px; font-size:11px;"
                       href="<?= e(url(['page' => 'process-payment', 'bill' => $b['bill_no']])) ?>">
                        <?= icon('card', 14) ?><span>Collect</span>
                    </a>
                <?php else: ?>
                    <a class="btn btn-secondary" style="height:32px; padding:0 12px; font-size:11px;"
                       href="<?= e(url(['page' => 'process-payment', 'bill' => $b['bill_no']])) ?>">
                        <span>View</span>
                    </a>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close('bill', true, $paged['total'], $paged['page'], $paged['pages']) ?>

<?php if (!$paged['rows']): ?>
    <?= filtered_empty('No bills match your search.') ?>
<?php endif; ?>
