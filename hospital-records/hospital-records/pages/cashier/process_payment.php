<?php
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

$confirmedPayment = null;
if ($successOr !== '') {
    foreach ($state['payments'] ?? [] as $py) {
        if (strcasecmp((string) ($py['receipt_no'] ?? ''), $successOr) === 0) {
            $confirmedPayment = $py;
            break;
        }
    }
}
?>
<?= page_title(
    'Process Payment',
    'Review itemized charges on the left and record payment collection on the right.',
    button('Back to Unpaid Bills', ['variant' => 'secondary', 'href' => url(['page' => 'unpaid-bills'])])
) ?>

<?php if ($allBills): ?>
    <div class="panel" style="padding: 14px 20px; margin-bottom: 20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:10px;">
            <span style="font-size:12px; font-weight:700; color:var(--navy-900);">Select Invoice:</span>
            <span style="font-size:12px; color:var(--gray-500);">Switch between active patient bills</span>
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
    <?= filtered_empty('No bills available to process. Create a bill under Unpaid Bills first.') ?>
<?php else:
    $ptRec   = find_patient_record($state, $selectedBill['patient']);
    $ptId    = $ptRec[8] ?? 'P-1001';
    $ptHin   = $ptRec[3] ?? '—';
    $ptPhone = $ptRec[2] ?? '—';
    $balNum  = round($selectedBill['balance'], 2);
    $isPaid  = $selectedBill['status'] === 'Paid' || $balNum <= 0;
?>
    <div class="content-grid" style="grid-template-columns: 1.15fr 0.85fr; align-items:start;">
        <!-- LEFT SIDE: Bill Details with Itemized Charges, Total, Paid So Far, and Balance Due -->
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2 style="display:flex; align-items:center; gap:10px;">
                        <span>Invoice <?= e($selectedBill['bill_no']) ?></span>
                        <?= badge($selectedBill['status'], status_tone($selectedBill['status'])) ?>
                    </h2>
                    <p>Issued <?= e($selectedBill['issued']) ?> · Itemized hospital statement</p>
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
                    <div style="font-size:11px; color:var(--gray-500);">ID: <code><?= e($ptId) ?></code></div>
                </div>
                <div>
                    <span style="font-size:10px; text-transform:uppercase; color:var(--gray-500); letter-spacing:.5px;">Insurance No.</span>
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
                                <td style="text-align:right;"><?= e((string) ($line[2] ?? '$0.00')) ?></td>
                                <td style="text-align:right;"><strong><?= e((string) ($line[3] ?? '$0.00')) ?></strong></td>
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

        <!-- RIGHT SIDE: Payment Form with Method, Ref No. (for non-cash), Amount Received, Live Change Due -->
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2>Collect Payment</h2>
                    <p>Enter amount received and select payment method</p>
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
                        All charges for <b><?= e($selectedBill['bill_no']) ?></b> have been settled. You can view or print the receipt from Payment History.
                    </p>
                    <div class="button-pair" style="margin-top:6px;">
                        <?= button('Unpaid Bills', ['variant' => 'secondary', 'href' => url(['page' => 'unpaid-bills'])]) ?>
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
                        <span class="field-label">Amount to Apply to Bill ($)</span>
                        <span class="input-wrap">
                            <input type="number" name="amount_to_pay" id="pay-apply-amount"
                                   step="0.01" min="0.01" max="<?= e(number_format($balNum, 2, '.', '')) ?>"
                                   value="<?= e(number_format($balNum, 2, '.', '')) ?>" required>
                        </span>
                        <small class="field-hint">Defaults to full balance due (<?= e(format_money($balNum)) ?>). Lower to record a partial payment.</small>
                    </label>

                    <label class="field">
                        <span class="field-label">Payment Method</span>
                        <span class="input-wrap">
                            <select name="payment_method" id="pay-method-select">
                                <option value="Cash">Cash</option>
                                <option value="Card">Card (Credit / Debit)</option>
                                <option value="GCash">GCash (E-Wallet)</option>
                                <option value="Insurance">Insurance</option>
                            </select>
                            <?= icon('down', 16) ?>
                        </span>
                    </label>

                    <label class="field" id="pay-ref-wrap" style="display:none;">
                        <span class="field-label" id="pay-ref-label">Reference / Approval Number</span>
                        <span class="input-wrap">
                            <input type="text" name="reference_no" id="pay-ref-input" placeholder="e.g. REF-948201 / Approval Code">
                        </span>
                        <small class="field-hint">Required for Card, GCash, and Insurance transactions.</small>
                    </label>

                    <label class="field">
                        <span class="field-label">Amount Received / Tendered ($)</span>
                        <span class="input-wrap">
                            <input type="number" name="amount_received" id="pay-received-amount"
                                   step="0.01" min="0.01"
                                   value="<?= e(number_format($balNum, 2, '.', '')) ?>" required>
                        </span>
                    </label>

                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <button type="button" class="btn btn-secondary" style="height:30px; padding:0 10px; font-size:11px;" data-quick-exact="1">Exact Amount</button>
                        <button type="button" class="btn btn-secondary" style="height:30px; padding:0 10px; font-size:11px;" data-quick-add="10">+$10</button>
                        <button type="button" class="btn btn-secondary" style="height:30px; padding:0 10px; font-size:11px;" data-quick-add="50">+$50</button>
                        <button type="button" class="btn btn-secondary" style="height:30px; padding:0 10px; font-size:11px;" data-quick-add="100">+$100</button>
                    </div>

                    <div id="pay-change-card" style="padding: 16px; border-radius: var(--radius); background: var(--teal-50); border: 1px solid #ccece6; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <span style="font-size:11px; font-weight:700; color:var(--teal-900); text-transform:uppercase; letter-spacing:.5px;">Change Due</span>
                            <p id="pay-change-hint" style="font-size:11px; color:var(--gray-600); margin-top:2px;">Calculated live from amount received</p>
                        </div>
                        <strong id="pay-change-value" style="font-size:22px; color:var(--teal-800);">$0.00</strong>
                    </div>

                    <button type="submit" id="pay-submit-btn" class="btn btn-primary" style="width:100%; height:44px; font-size:13px;">
                        <?= icon('shield', 18) ?><span>Confirm Payment</span>
                    </button>
                </form>

                <script>
                (function () {
                    var applyInput = document.getElementById('pay-apply-amount');
                    var recvInput  = document.getElementById('pay-received-amount');
                    var methodSel  = document.getElementById('pay-method-select');
                    var refWrap    = document.getElementById('pay-ref-wrap');
                    var refInput   = document.getElementById('pay-ref-input');
                    var refLabel   = document.getElementById('pay-ref-label');
                    var changeVal  = document.getElementById('pay-change-value');
                    var changeHint = document.getElementById('pay-change-hint');
                    var changeCard = document.getElementById('pay-change-card');
                    var submitBtn  = document.getElementById('pay-submit-btn');

                    function fmt(n) {
                        return '$' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    }

                    function updateCalc() {
                        if (!applyInput || !recvInput || !changeVal) return;
                        var apply = parseFloat(applyInput.value) || 0;
                        var recv  = parseFloat(recvInput.value) || 0;
                        var diff  = recv - apply;

                        if (diff < -0.005) {
                            changeVal.textContent = fmt(0);
                            changeVal.style.color = 'var(--red)';
                            changeHint.textContent = 'Short by ' + fmt(Math.abs(diff)) + ' — increase amount received';
                            changeCard.style.background = 'var(--red-bg)';
                            changeCard.style.borderColor = '#f5c2c7';
                            if (submitBtn) submitBtn.disabled = true;
                        } else {
                            var chg = Math.max(0, diff);
                            changeVal.textContent = fmt(chg);
                            changeVal.style.color = 'var(--teal-800)';
                            changeHint.textContent = chg > 0 ? 'Return to patient' : 'Exact amount tendered';
                            changeCard.style.background = 'var(--teal-50)';
                            changeCard.style.borderColor = '#ccece6';
                            if (submitBtn) submitBtn.disabled = false;
                        }
                    }

                    function updateMethod() {
                        if (!methodSel || !refWrap || !refInput) return;
                        var m = methodSel.value;
                        var isNonCash = (m !== 'Cash');
                        refWrap.style.display = isNonCash ? '' : 'none';
                        refInput.required = isNonCash;
                        if (refLabel) {
                            refLabel.textContent = m === 'Card'
                                ? 'Card Approval / Terminal Reference No.'
                                : (m === 'GCash' ? 'GCash Reference No.' : 'Insurance Policy / LOA Approval No.');
                        }
                        if (isNonCash && applyInput && recvInput) {
                            recvInput.value = (parseFloat(applyInput.value) || 0).toFixed(2);
                            updateCalc();
                        }
                    }

                    if (applyInput) {
                        applyInput.addEventListener('input', function () {
                            if (methodSel && methodSel.value !== 'Cash') {
                                recvInput.value = applyInput.value;
                            }
                            updateCalc();
                        });
                    }
                    if (recvInput) {
                        recvInput.addEventListener('input', updateCalc);
                    }
                    if (methodSel) {
                        methodSel.addEventListener('change', updateMethod);
                        updateMethod();
                    }

                    document.querySelectorAll('[data-quick-exact]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            if (applyInput && recvInput) {
                                recvInput.value = (parseFloat(applyInput.value) || 0).toFixed(2);
                                updateCalc();
                            }
                        });
                    });
                    document.querySelectorAll('[data-quick-add]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            if (recvInput) {
                                var add = parseFloat(btn.getAttribute('data-quick-add')) || 0;
                                var cur = parseFloat(recvInput.value) || 0;
                                recvInput.value = (cur + add).toFixed(2);
                                updateCalc();
                            }
                        });
                    });

                    updateCalc();
                })();
                </script>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($confirmedPayment !== null):
    $closeSuccessUrl = url(['page' => 'process-payment', 'bill' => $confirmedPayment['bill_no']]);
    $receiptPrintUrl = url(['page' => 'payment-history', 'detail' => 'receipt', 'or' => $confirmedPayment['receipt_no'], 'print' => 1]);
    $receiptViewUrl  = url(['page' => 'payment-history', 'detail' => 'receipt', 'or' => $confirmedPayment['receipt_no']]);
?>
    <div class="modal-backdrop">
        <a class="modal-dismiss" href="<?= e($closeSuccessUrl) ?>" aria-label="Close dialog"></a>
        <div class="modal" style="max-width:460px;">
            <div class="modal-head">
                <div>
                    <h2>Payment Confirmed</h2>
                    <p>Official Receipt <?= e($confirmedPayment['receipt_no']) ?> has been recorded</p>
                </div>
                <a class="icon-btn" href="<?= e($closeSuccessUrl) ?>" aria-label="Close"><?= icon('close') ?></a>
            </div>
            <div class="modal-body" style="text-align:center; padding: 26px 22px;">
                <div style="width:52px; height:52px; border-radius:50%; background:var(--green-bg); color:var(--green); display:grid; place-items:center; margin:0 auto 8px;">
                    <?= icon('shield', 26) ?>
                </div>
                <h3 style="font-size:18px; color:var(--navy-900); margin:0;">
                    <?= e(format_money($confirmedPayment['amount_paid'])) ?> Collected
                </h3>
                <p style="font-size:12px; color:var(--gray-500); margin-bottom:12px;">
                    Patient: <strong><?= e($confirmedPayment['patient_name']) ?></strong> · Bill <code><?= e($confirmedPayment['bill_no']) ?></code>
                </p>

                <div class="panel" style="padding:14px 16px; background:var(--gray-50); text-align:left; display:grid; gap:8px; font-size:12px;">
                    <div style="display:flex; justify-content:space-between;">
                        <span class="muted">Official Receipt No.</span>
                        <code><?= e($confirmedPayment['receipt_no']) ?></code>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span class="muted">Payment Method</span>
                        <strong><?= e($confirmedPayment['payment_method']) ?><?= ($confirmedPayment['reference_no'] ?? '—') !== '—' && ($confirmedPayment['reference_no'] ?? '') !== '' ? ' (' . e($confirmedPayment['reference_no']) . ')' : '' ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span class="muted">Amount Received</span>
                        <strong><?= e(format_money($confirmedPayment['amount_received'])) ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; border-top:1px solid var(--gray-200); padding-top:8px;">
                        <span style="font-weight:700; color:var(--teal-800);">Change Due</span>
                        <strong style="font-size:14px; color:var(--teal-800);"><?= e(format_money($confirmedPayment['change_due'])) ?></strong>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <?= button('Done', ['variant' => 'secondary', 'href' => $closeSuccessUrl]) ?>
                <?= button('View Receipt', ['variant' => 'secondary', 'href' => $receiptViewUrl]) ?>
                <?= button('Print Receipt', ['icon' => 'printer', 'href' => $receiptPrintUrl]) ?>
            </div>
        </div>
    </div>
<?php endif; ?>
