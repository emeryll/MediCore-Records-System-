<?php
declare(strict_types=1);

$q         = trim((string) ($_GET['q'] ?? ''));
$status_   = (string) ($_GET['status'] ?? 'All');
$adjBillNo = trim((string) ($_GET['adjust_bill'] ?? ''));
$targetPt  = trim((string) ($_GET['patient'] ?? ''));
$isHistory = ($targetPt !== '' || ($_GET['view'] ?? '') === 'history');

// Load all normalized bills
$allBills = [];
foreach ($state['bills'] ?? [] as $idx => $b) {
    $norm        = normalize_bill_row($b, $idx);
    $ptRec       = find_patient_record($state, $norm['patient']);
    $norm['mrn'] = $ptRec ? patient_mrn_display($ptRec) : ($ptRec[8] ?? '');
    $allBills[]  = $norm;
}

// Group bills by patient for the main Billing view
// Only patients with at least one billing record are shown in the cashier.
// Patients with no billing records (and provisional patients without bills) are strictly hidden.
$patientsMap = [];
foreach ($allBills as $b) {
    $pName = trim((string) ($b['patient'] ?? ''));
    if ($pName === '' || $pName === '—') {
        continue;
    }
    if (!isset($patientsMap[$pName])) {
        $ptRec = find_patient_record($state, $pName);
        $patientsMap[$pName] = [
            'patient'      => $pName,
            'mrn'          => $ptRec ? patient_mrn_display($ptRec) : ($b['mrn'] ?: '—'),
            'bills'        => [],
            'total_bills'  => 0,
            'total_billed' => 0.0,
            'total_paid'   => 0.0,
            'balance'      => 0.0,
            'unpaid_count' => 0,
        ];
    }
    $patientsMap[$pName]['bills'][] = $b;
    $patientsMap[$pName]['total_bills']++;
    $patientsMap[$pName]['total_billed'] += $b['total'];
    $patientsMap[$pName]['total_paid']   += $b['paid'];
    $patientsMap[$pName]['balance']      += $b['balance'];
    if ($b['status'] !== 'Paid') {
        $patientsMap[$pName]['unpaid_count']++;
    }
}

// Quick select price list EXCLUDING MEDICINES as requested
$priceItems = array_values(array_filter(
    $state['price_list'] ?? [],
    static fn ($pl) => !empty($pl['active']) && stripos((string) ($pl['category'] ?? ''), 'med') === false
));
?>

<?php if ($isHistory): ?>
    <?php
    $ptData = $patientsMap[$targetPt] ?? null;
    $ptBills = $ptData ? $ptData['bills'] : array_values(array_filter($allBills, static fn ($b) => strcasecmp($b['patient'], $targetPt) === 0));
    $ptRec   = find_patient_record($state, $targetPt);
    $ptMrn   = $ptRec ? patient_mrn_display($ptRec) : ($ptData['mrn'] ?? '—');

    $filteredPtBills = array_values(array_filter($ptBills, static function ($b) use ($q): bool {
        return row_matches([$b['bill_no'], $b['issued'], format_money($b['total']), $b['status']], $q);
    }));
    $paged = paginate_rows($filteredPtBills, 10, 'p');
    ?>

    <?= page_title(
        'Billing History: ' . e($targetPt),
        'Patient MRN: ' . e($ptMrn) . ' · Complete billing statements, charges, and payment records.',
        button('← Back to Patient Billing', ['variant' => 'secondary', 'href' => url(['page' => 'unpaid-bills'])])
    ) ?>

    <!-- Patient Statement Summary Banner -->
    <div class="archive-summary" style="margin-bottom:20px;">
        <div>
            <span>Total Invoices</span>
            <strong><?= count($ptBills) ?></strong>
            <small>Bills generated</small>
        </div>
        <div>
            <span>Total Invoiced</span>
            <strong style="color:var(--navy-900);"><?= e(format_money($ptData['total_billed'] ?? 0.0)) ?></strong>
            <small>Total amount billed</small>
        </div>
        <div>
            <span>Total Paid</span>
            <strong style="color:var(--green);"><?= e(format_money($ptData['total_paid'] ?? 0.0)) ?></strong>
            <small>Settled so far</small>
        </div>
        <div>
            <span>Outstanding Balance</span>
            <strong style="color:<?= ($ptData['balance'] ?? 0.0) > 0 ? 'var(--red)' : 'var(--teal-800)' ?>;">
                <?= e(format_money($ptData['balance'] ?? 0.0)) ?>
            </strong>
            <small><?= ($ptData['balance'] ?? 0.0) > 0 ? 'Payment pending' : 'Cleared / Paid in Full' ?></small>
        </div>
    </div>

    <?php if ($adjBillNo !== ''): ?>
        <div class="panel" style="padding: 20px 22px; margin-bottom: 20px; border-color: var(--teal-700); background: var(--teal-50);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                <div>
                    <h2 style="font-size:15px; color:var(--navy-900);">Add Manual Charge / Adjustment to <?= e($adjBillNo) ?></h2>
                    <p style="font-size:11.5px; color:var(--gray-600);">Select an item from the Price List (medicines excluded) or enter a custom adjustment with a mandatory documented reason.</p>
                </div>
                <a class="icon-btn" href="<?= e(url(['page' => 'unpaid-bills', 'view' => 'history', 'patient' => $targetPt])) ?>" aria-label="Close"><?= icon('close') ?></a>
            </div>
            <form method="post" action="index.php" style="display:grid; gap:14px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_manual_charge">
                <input type="hidden" name="bill_no" value="<?= e($adjBillNo) ?>">
                <input type="hidden" name="return" value="<?= e(url(['page' => 'unpaid-bills', 'view' => 'history', 'patient' => $targetPt])) ?>">
                <div class="form-grid three">
                    <label class="field">
                        <span class="field-label">Quick Select from Price List (Non-medication)</span>
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
                        <span class="input-wrap"><input type="text" name="item_desc" id="adj-desc" placeholder="e.g. Medical supplies / Consultation fee adjustment" required></span>
                    </label>
                    <div style="display:grid; grid-template-columns:80px 1fr; gap:8px;">
                        <?= field('Qty *', 'item_qty', '1', 'number', '1', ['required' => true]) ?>
                        <label class="field">
                            <span class="field-label">Unit Price (₱) *</span>
                            <span class="input-wrap"><input type="number" step="0.01" name="item_rate" id="adj-rate" placeholder="350.00" required></span>
                        </label>
                    </div>
                </div>
                <?= field('Required Reason for Manual Charge / Adjustment *', 'reason', 'e.g. Clinic supplies used during procedure / Approved billing adjustment', 'text', '', ['required' => true]) ?>
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <?= button('Cancel', ['variant' => 'secondary', 'href' => url(['page' => 'unpaid-bills', 'view' => 'history', 'patient' => $targetPt])]) ?>
                    <?= button('Save Charge to Bill', ['type' => 'submit', 'icon' => 'check']) ?>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php
    $tbHistory = toolbar_open('unpaid-bills', ['view' => 'history', 'patient' => $targetPt])
        . search_box('Search bill number, date, or amount...', 'q', $q)
        . filter_button()
        . toolbar_close();
    ?>

    <?= table_open(['Bill No.', 'Issued Date', 'Charge Items', 'Total Amount', 'Paid So Far', 'Balance Due', 'Status', 'Actions'], $tbHistory) ?>
    <?php foreach ($paged['rows'] as $b):
        $isPaid = ($b['status'] === 'Paid' || $b['balance'] <= 0.009);
    ?>
        <tr>
            <td><code><?= e($b['bill_no']) ?></code></td>
            <td><?= e($b['issued']) ?></td>
            <td>
                <strong><?= count($b['items']) ?> item<?= count($b['items']) === 1 ? '' : 's' ?></strong>
                <div class="muted" style="font-size:11px; max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    <?= e(implode(', ', array_map(static fn ($it) => (string) ($it[0] ?? ''), array_slice($b['items'], 0, 2)))) ?>
                </div>
            </td>
            <td><strong><?= e(format_money($b['total'])) ?></strong></td>
            <td style="color:var(--green); font-weight:600;"><?= e(format_money($b['paid'])) ?></td>
            <td><strong style="color:<?= $b['balance'] > 0 ? 'var(--red)' : 'var(--gray-600)' ?>; font-size:13px;"><?= e(format_money($b['balance'])) ?></strong></td>
            <td><?= badge($b['status'], status_tone($b['status'])) ?></td>
            <td style="white-space:nowrap;">
                <div class="appt-action-cell" style="display:inline-flex; align-items:center; gap:6px;">
                    <?php if ($isPaid): ?>
                        <!-- Paid bills are locked. Show only View and Print receipt. Strictly NO Add Charge! -->
                        <a class="btn btn-secondary btn-sm btn-appt-action"
                           href="<?= e(url(['page' => 'receipt', 'bill' => $b['bill_no']])) ?>" title="View official receipt and details">
                            <span>View</span>
                        </a>
                        <a class="btn btn-secondary btn-sm btn-appt-action"
                           href="<?= e(url(['page' => 'receipt', 'bill' => $b['bill_no'], 'print' => 1])) ?>" target="_blank" title="Print Official Receipt">
                            <?= icon('print', 13) ?><span>Print Receipt</span>
                        </a>
                    <?php else: ?>
                        <!-- Add Charge appears only on Unpaid and Partial bills -->
                        <a class="btn btn-secondary btn-sm btn-appt-action"
                           href="<?= e(url(['page' => 'unpaid-bills', 'view' => 'history', 'patient' => $targetPt, 'adjust_bill' => $b['bill_no']])) ?>" title="Add manual charge or adjustment">
                            <?= icon('plus', 13) ?><span>Add Charge</span>
                        </a>
                        <a class="btn btn-primary btn-sm btn-appt-action"
                           href="<?= e(url(['page' => 'process-payment', 'bill' => $b['bill_no'], 'patient' => $targetPt])) ?>" title="Process payment for this bill">
                            <?= icon('card', 14) ?><span>Collect Payment</span>
                        </a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?= table_close('bill', true, $paged['total'], $paged['page'], $paged['pages']) ?>

    <?php if (!$paged['rows']): ?>
        <?= filtered_empty('No bills found for this patient.') ?>
    <?php endif; ?>

<?php else: ?>
    <!-- MAIN BILLING VIEW: DISPLAY BY PATIENT (NOT BY BILLING NO) -->
    <?php
    $patientRows = array_values($patientsMap);
    $filteredPatients = array_values(array_filter($patientRows, static function ($p) use ($q, $status_): bool {
        if ($status_ === 'With Balance' && $p['balance'] <= 0.009) {
            return false;
        }
        if ($status_ === 'Paid in Full' && ($p['balance'] > 0.009 || $p['total_bills'] === 0)) {
            return false;
        }
        return row_matches([$p['patient'], $p['mrn'], format_money($p['total_billed']), format_money($p['balance'])], $q);
    }));

    // Sort patients with outstanding balance to the top
    usort($filteredPatients, static function ($a, $b) {
        if ($a['balance'] > 0 && $b['balance'] <= 0) return -1;
        if ($a['balance'] <= 0 && $b['balance'] > 0) return 1;
        return strcmp($a['patient'], $b['patient']);
    });

    $paged = paginate_rows($filteredPatients, 10, 'p');

    $totalOutstanding = 0.0;
    foreach ($patientsMap as $p) {
        $totalOutstanding += $p['balance'];
    }

    $toolbar = toolbar_open('unpaid-bills')
        . search_box('Search by patient name or MRN...', 'q', $q)
        . '<div class="toolbar-right">'
        . select_field('', 'status', ['All', 'With Balance', 'Paid in Full'], $status_)
        . filter_button()
        . '</div>'
        . toolbar_close();
    ?>

    <?= page_title(
        'Billing & Patient Statements',
        'Displaying statements grouped by Patient. Click Billing History to inspect individual bills, charges, and payment locks.',
        button('Process Payments', [
            'variant' => 'primary',
            'icon'    => 'card',
            'href'    => url(['page' => 'process-payment']),
        ])
    ) ?>

    <!-- Summary cards -->
    <div class="archive-summary" style="margin-bottom:20px;">
        <div>
            <span>Patients with Statements</span>
            <strong><?= count($patientsMap) ?></strong>
            <small>Active billing accounts</small>
        </div>
        <div>
            <span>Total Outstanding Balance</span>
            <strong style="color:var(--red);"><?= e(format_money($totalOutstanding)) ?></strong>
            <small>Hospital receivables pending</small>
        </div>
        <div style="display:flex; flex-direction:column; justify-content:center; gap:6px;">
            <span style="font-size:11px; color:var(--gray-600); font-weight:600; text-transform:uppercase; letter-spacing:.5px;">Quick Action</span>
            <div>
                <a class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; height:38px; padding:0 16px; font-size:13px; font-weight:600; color:#fff; text-decoration:none; border-radius:6px; background:var(--teal-800);" href="<?= e(url(['page' => 'process-payment'])) ?>">
                    <?= icon('card', 16) ?><span>Process Payments</span>
                </a>
            </div>
            <small style="color:var(--gray-500); font-size:10px;">Collect payment for patients</small>
        </div>
    </div>

    <?= table_open(['Patient Name', 'MRN', 'Total Invoices', 'Total Billed', 'Total Paid', 'Outstanding Balance', 'Status', 'Actions'], $toolbar) ?>
    <?php foreach ($paged['rows'] as $p):
        $hasBal  = $p['balance'] > 0.009;
        $isPaid  = !$hasBal && $p['total_bills'] > 0;
        $stLabel = $hasBal ? ($p['total_paid'] > 0 ? 'Partial' : 'Unpaid') : ($p['total_bills'] > 0 ? 'Paid' : 'No Bills');
        $stTone  = $hasBal ? ($p['total_paid'] > 0 ? 'info' : 'danger') : ($p['total_bills'] > 0 ? 'success' : 'neutral');
    ?>
        <tr>
            <td>
                <div class="person-cell">
                    <?= mini_avatar($p['patient']) ?>
                    <div>
                        <strong><?= e($p['patient']) ?></strong>
                        <span style="font-size:11px; color:var(--gray-500);"><?= (int) $p['total_bills'] ?> statement<?= $p['total_bills'] === 1 ? '' : 's' ?></span>
                    </div>
                </div>
            </td>
            <td>
                <?php if ($p['mrn'] !== '—' && $p['mrn'] !== ''): ?>
                    <code style="font-weight:700; color:var(--teal-800); background:var(--teal-50); padding:3px 8px; border-radius:6px;"><?= e($p['mrn']) ?></code>
                <?php else: ?>
                    <span class="muted" style="font-size:12px;">— (Provisional)</span>
                <?php endif; ?>
            </td>
            <td><strong><?= (int) $p['total_bills'] ?></strong></td>
            <td><strong><?= e(format_money($p['total_billed'])) ?></strong></td>
            <td style="color:var(--green); font-weight:600;"><?= e(format_money($p['total_paid'])) ?></td>
            <td>
                <strong style="color:<?= $hasBal ? 'var(--red)' : 'var(--gray-600)' ?>; font-size:13.5px;">
                    <?= e(format_money($p['balance'])) ?>
                </strong>
            </td>
            <td><?= badge($stLabel, $stTone) ?></td>
            <td style="white-space:nowrap;">
                <div class="appt-action-cell" style="display:inline-flex; align-items:center; gap:6px;">
                    <a class="btn btn-secondary btn-sm btn-appt-action"
                       href="<?= e(url(['page' => 'unpaid-bills', 'view' => 'history', 'patient' => $p['patient']])) ?>"
                       title="View billing history and all invoices for <?= e($p['patient']) ?>">
                        <?= icon('history', 13) ?><span>Billing History</span>
                    </a>
                    <?php if ($hasBal): ?>
                        <a class="btn btn-primary btn-sm btn-appt-action"
                           href="<?= e(url(['page' => 'process-payment', 'patient' => $p['patient']])) ?>"
                           title="Process payment for <?= e($p['patient']) ?>">
                            <?= icon('card', 14) ?><span>Pay</span>
                        </a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?= table_close('patient', true, $paged['total'], $paged['page'], $paged['pages']) ?>

    <?php if (!$paged['rows']): ?>
        <?= filtered_empty('No patient billing records match your search.') ?>
    <?php endif; ?>

<?php endif; ?>
