<?php
declare(strict_types=1);

$reqBillNo = trim((string) ($_GET['bill'] ?? ''));
$successOr = trim((string) ($_GET['success_or'] ?? ''));

$allBills = [];
foreach ($state['bills'] ?? [] as $idx => $b) {
    $allBills[] = normalize_bill_row($b, $idx);
}

$unpaidBills = array_values(array_filter(
    $allBills,
    static fn ($b) => $b['status'] !== 'Paid' && $b['balance'] > 0
));

$selectedBill = null;
if ($reqBillNo !== '') {
    foreach ($allBills as $b) {
        if (strcasecmp($b['bill_no'], $reqBillNo) === 0) {
            $selectedBill = $b;
            break;
        }
    }
}
if ($selectedBill === null) {
    $selectedBill = $unpaidBills[0] ?? ($allBills[0] ?? null);
}
?>
<?= page_title(
    'Process Payment',
    'Review itemized charges on the left and record payment collection (Cash, Card, HMO, or PhilHealth) on the right.',
    button('Back to Billing', ['variant' => 'secondary', 'href' => url(['page' => 'unpaid-bills'])])
) ?>

<?php if ($allBills): ?>
    <div class="panel" style="padding: 14px 20px; margin-bottom: 20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:10px;">
            <span style="font-size:12px; font-weight:700; color:var(--navy-900);">Select Invoice:</span>
            <span style="font-size:12px; color:var(--gray-500);">Switch between patient bills</span>
        </div>
        <form method="get" action="index.php" style="display:flex; align-items:center; gap:10px; min-width:340px;">
            <input type="hidden" name="page" value="process-payment">
            <span class="input-wrap" style="flex:1;">
                <select name="bill" onchange="this.form.submit()">
                    <?php foreach ($allBills as $bOpt): ?>
                        <option value="<?= e($bOpt['bill_no']) ?>"<?= ($selectedBill && $selectedBill['bill_no'] === $bOpt['bill_no']) ? ' selected' : '' ?>>
                            <?= e($bOpt['bill_no'] . ' · ' . $bOpt['patient'] . ' · Balance: ' . format_money($bOpt['balance']) . ' (' . $bOpt['status'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= icon('down', 16) ?>
            </span>
            <button type="submit" class="btn btn-secondary"><span>Load</span></button>
        </form>
    </div>
<?php endif; ?>

<?php if ($selectedBill === null): ?>
    <?= filtered_empty('No bills available to process.') ?>
<?php else:
    $ptRec   = find_patient_record($state, $selectedBill['patient']);
    $mrnStr  = $ptRec ? patient_mrn_display($ptRec) : ($ptRec[8] ?? 'MRN-1001');
    $ptHin   = $ptRec[3] ?? '—';
    $ptPhone = $ptRec[2] ?? '—';
    $balNum  = round($selectedBill['balance'], 2);
    $isPaid  = $selectedBill['status'] === 'Paid' || $balNum <= 0;
?>
    <div class="content-grid" style="grid-template-columns: 1.15fr 0.85fr; align-items:start;">
        <!-- LEFT SIDE: Bill Details -->
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2 style="display:flex; align-items:center; gap:10px;">
                        <span>Invoice <?= e($selectedBill['bill_no']) ?></span>
                        <?= badge($selectedBill['status'], status_tone($selectedBill['status'])) ?>
                    </h2>
                    <p>Issued <?= e($selectedBill['issued']) ?> · West Metro Medical Center statement</p>
                </div>
                <div style="text-align:right;">
                    <span style="font-size:10px; text-transform:uppercase; color:var(--gray-500); letter-spacing:.6px;">Balance Due</span>
                    <div style="font-size:20px; font-weight:800; color:<?= $isPaid ? 'var(--green)' : 'var(--red)' ?>;">
                        <?= e(format_money($balNum)) ?>
                    </div>
                </div>
            </div>

            <div style="padding: 18px 22px; border-bottom: 1px solid var(--gray-200); background: var(--gray-50); display:grid; grid-template-columns: repeat(3, 1fr); gap: 14px;">
                <div>
                    <span style="font-size:10px; text-transform:uppercase; color:var(--gray-500); letter-spacing:.5px;">Patient</span>
                    <div style="font-weight:700; color:var(--navy-900); margin-top:3px;"><?= e($selectedBill['patient']) ?></div>
                    <div style="font-size:11px; color:var(--gray-500);">MRN: <code><?= e($mrnStr ?: '—') ?></code></div>
                </div>
                <div>
                    <span style="font-size:10px; text-transform:uppercase; color:var(--gray-500); letter-spacing:.5px;">PhilHealth / HMO</span>
                    <div style="font-weight:600; color:var(--gray-800); margin-top:3px;"><?= e($ptHin) ?></div>
                    <div style="font-size:11px; color:var(--gray-500);">Phone: <?= e($ptPhone) ?></div>
                </div>
                <div>
                    <span style="font-size:10px; text-transform:uppercase; color:var(--gray-500); letter-spacing:.5px;">Cashier on Duty</span>
                    <div style="font-weight:600; color:var(--gray-800); margin-top:3px;"><?= e(current_user_name($role)) ?></div>
                    <div style="font-size:11px; color:var(--gray-500);"><?= e(date('M d, Y')) ?></div>
                </div>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Itemized Charge Description</th>
                            <th style="width:70px; text-align:center;">Qty</th>
                            <th style="width:110px; text-align:right;">Rate</th>
                            <th style="width:115px; text-align:right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($selectedBill['items'] as $line): ?>
                            <tr>
                                <td><strong><?= e((string) ($line[0] ?? 'Service')) ?></strong></td>
                                <td style="text-align:center;"><?= e((string) ($line[1] ?? '1')) ?></td>
                                <td style="text-align:right;"><?= e(format_money(parse_money((string) ($line[2] ?? '0')))) ?></td>
                                <td style="text-align:right;"><strong><?= e(format_money(parse_money((string) ($line[3] ?? '0')))) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="padding: 18px 22px; background: var(--gray-50); border-top: 1px solid var(--gray-200); display:grid; gap: 10px;">
                <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--gray-600);">
                    <span>Total Charges</span>
                    <strong style="color:var(--navy-900); font-size:13px;"><?= e(format_money($selectedBill['total'])) ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--gray-600);">
                    <span>Amount Paid So Far</span>
                    <strong style="color:var(--green); font-size:13px;">− <?= e(format_money($selectedBill['paid'])) ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-top:10px; border-top: 1px solid var(--gray-200);">
                    <span style="font-size:13px; font-weight:700; color:var(--navy-900);">Balance Due</span>
                    <strong style="font-size:20px; color:<?= $isPaid ? 'var(--green)' : 'var(--navy-900)' ?>;">
                        <?= e(format_money($balNum)) ?>
                    </strong>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE: Payment Form -->
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2>Collect Payment</h2>
                    <p>Record payment by Cash, Card, HMO, or PhilHealth and issue printable Official Receipt</p>
                </div>
                <?= icon('card', 20) ?>
            </div>

            <?php if ($isPaid): ?>
                <div style="padding: 32px 22px; text-align:center; display:grid; gap:12px; place-items:center;">
                    <span style="width:46px; height:46px; border-radius:50%; background:var(--green-bg); color:var(--green); display:grid; place-items:center;">
                        <?= icon('shield', 24) ?>
                    </span>
                    <strong style="font-size:15px; color:var(--navy-900);">This invoice is paid in full</strong>
                    <p style="font-size:12px; color:var(--gray-500); max-width:300px;">
                        All charges for <b><?= e($selectedBill['bill_no']) ?></b> have been settled.
                    </p>
                    <div class="button-pair" style="margin-top:6px;">
                        <?= button('Billing List', ['variant' => 'secondary', 'href' => url(['page' => 'unpaid-bills'])]) ?>
                        <?= button('Payment History', ['icon' => 'history', 'href' => url(['page' => 'payment-history', 'q' => $selectedBill['bill_no']])]) ?>
                    </div>
                </div>
            <?php else: ?>
                <form method="post" action="index.php" id="cashier-pay-form" style="padding: 20px 22px; display:grid; gap: 16px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="process_payment">
                    <input type="hidden" name="bill_no" value="<?= e($selectedBill['bill_no']) ?>">
                    <input type="hidden" name="return" value="<?= e(url(['page' => 'process-payment', 'bill' => $selectedBill['bill_no']])) ?>">

                    <label class="field">
                        <span class="field-label">Amount to Apply to Bill (₱)</span>
                        <span class="input-wrap">
                            <input type="number" name="amount_to_pay" id="pay-apply-amount"
                                   step="0.01" min="0.01" max="<?= e(number_format($balNum, 2, '.', '')) ?>"
                                   value="<?= e(number_format($balNum, 2, '.', '')) ?>" required>
                        </span>
                        <small class="field-hint">Defaults to full balance due (<?= e(format_money($balNum)) ?>). Lower to record a partial payment or PhilHealth/HMO deduction.</small>
                    </label>

                    <label class="field">
                        <span class="field-label">Payment Method</span>
                        <span class="input-wrap">
                            <select name="payment_method" id="pay-method-select">
                                <option value="Cash">Cash</option>
                                <option value="Card">Card (Credit / Debit)</option>
                                <option value="HMO">HMO (LOA / Health Card)</option>
                                <option value="PhilHealth">PhilHealth Benefit Claim</option>
                            </select>
                            <?= icon('down', 16) ?>
                        </span>
                    </label>

                    <label class="field" id="pay-ref-wrap" style="display:none;">
                        <span class="field-label" id="pay-ref-label">Reference / Approval / Case Rate No.</span>
                        <span class="input-wrap">
                            <input type="text" name="reference_no" id="pay-ref-input" placeholder="e.g. LOA-948201 / PhilHealth Case No.">
                        </span>
                        <small class="field-hint">Required for Card, HMO, and PhilHealth transactions.</small>
                    </label>

                    <label class="field">
                        <span class="field-label">Amount Received / Tendered (₱)</span>
                        <span class="input-wrap">
                            <input type="number" name="amount_received" id="pay-received-amount"
                                   step="0.01" min="0.01"
                                   value="<?= e(number_format($balNum, 2, '.', '')) ?>" required>
                        </span>
                    </label>

                    <div id="pay-change-card" style="padding: 16px; border-radius: var(--radius); background: var(--teal-50); border: 1px solid #ccece6; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <span style="font-size:11px; font-weight:700; color:var(--teal-900); text-transform:uppercase; letter-spacing:.5px;">Change Due</span>
                            <p id="pay-change-hint" style="font-size:11px; color:var(--gray-600); margin-top:2px;">Calculated live from amount received</p>
                        </div>
                        <strong id="pay-change-value" style="font-size:22px; color:var(--teal-800);">₱0.00</strong>
                    </div>

                    <button type="submit" id="pay-submit-btn" class="btn btn-primary" style="width:100%; height:44px; font-size:13px;">
                        <?= icon('shield', 18) ?><span>Confirm Payment &amp; Issue Receipt</span>
                    </button>
                </form>

                <script>
                (function () {
                    var applyInput = document.getElementById('pay-apply-amount');
                    var recvInput  = document.getElementById('pay-received-amount');
                    var methodSel  = document.getElementById('pay-method-select');
                    var refWrap    = document.getElementById('pay-ref-wrap');
                    var refInput   = document.getElementById('pay-ref-input');
                    var changeVal  = document.getElementById('pay-change-value');

                    function updateCalc() {
                        var m = methodSel.value;
                        if (m === 'Cash') {
                            refWrap.style.display = 'none';
                            refInput.required = false;
                        } else {
                            refWrap.style.display = '';
                            refInput.required = true;
                        }
                        var app = parseFloat(applyInput.value || '0');
                        var rcv = parseFloat(recvInput.value || '0');
                        var chg = Math.max(0, rcv - app);
                        changeVal.textContent = '₱' + chg.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                    methodSel.addEventListener('change', updateCalc);
                    applyInput.addEventListener('input', updateCalc);
                    recvInput.addEventListener('input', updateCalc);
                    updateCalc();
                })();
                </script>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
