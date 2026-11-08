<?php
declare(strict_types=1);

$q         = trim((string) ($_GET['q'] ?? ''));
$reqBillNo = trim((string) ($_GET['bill'] ?? ''));
$reqPt     = trim((string) ($_GET['patient'] ?? ''));

$allBills = [];
foreach ($state['bills'] ?? [] as $idx => $b) {
    $norm        = normalize_bill_row($b, $idx);
    $ptRec       = find_patient_record($state, $norm['patient']);
    $norm['mrn'] = $ptRec ? patient_mrn_display($ptRec) : ($ptRec[8] ?? '');
    $allBills[]  = $norm;
}

// Group patients with bills
// Only patients with at least one billing record are shown in the cashier.
// Patients with no billing records and provisional patients without bills are strictly hidden.
$patientsMap = [];
foreach ($allBills as $b) {
    $pName = trim((string) ($b['patient'] ?? ''));
    if ($pName === '' || $pName === '—') {
        continue;
    }
    if (!isset($patientsMap[$pName])) {
        $ptRec = find_patient_record($state, $pName);
        $patientsMap[$pName] = [
            'patient'     => $pName,
            'mrn'         => $ptRec ? patient_mrn_display($ptRec) : ($b['mrn'] ?: '—'),
            'bills'       => [],
            'total_bills' => 0,
            'total_billed'=> 0.0,
            'total_paid'  => 0.0,
            'balance'     => 0.0,
            'unpaid_bills'=> [],
        ];
    }
    $patientsMap[$pName]['bills'][] = $b;
    $patientsMap[$pName]['total_bills']++;
    $patientsMap[$pName]['total_billed'] += $b['total'];
    $patientsMap[$pName]['total_paid']   += $b['paid'];
    $patientsMap[$pName]['balance']      += $b['balance'];
    if ($b['status'] !== 'Paid' && $b['balance'] > 0.009) {
        $patientsMap[$pName]['unpaid_bills'][] = $b;
    }
}

// Determine if we are on Step 1 (Patient Selection) or Step 2 (Process Payment)
$selectedBill = null;
if ($reqBillNo !== '') {
    foreach ($allBills as $b) {
        if (strcasecmp($b['bill_no'], $reqBillNo) === 0) {
            $selectedBill = $b;
            if ($reqPt === '') {
                $reqPt = $b['patient'];
            }
            break;
        }
    }
} elseif ($reqPt !== '') {
    $ptInfo = $patientsMap[$reqPt] ?? null;
    if ($ptInfo && !empty($ptInfo['unpaid_bills'])) {
        $selectedBill = $ptInfo['unpaid_bills'][0];
    } elseif ($ptInfo && !empty($ptInfo['bills'])) {
        $selectedBill = $ptInfo['bills'][0];
    }
}

$isProcessingStep = ($selectedBill !== null && $reqPt !== '');
?>

<?php if (!$isProcessingStep): ?>
    <!-- STEP 1: PATIENT LIST & SEARCH BAR FIRST -->
    <?= page_title(
        'Payments & Cashiering',
        'Search and select a patient first to proceed with billing settlement, HMO/PhilHealth processing, and receipt issuance.',
        button('Billing Statements', ['variant' => 'secondary', 'icon' => 'bill', 'href' => url(['page' => 'unpaid-bills'])])
    ) ?>

    <?php
    $patientRows = array_values(array_filter(
        $patientsMap,
        static fn ($p) => ($p['total_bills'] ?? 0) >= 1 && !empty($p['bills'])
    ));
    $filteredPatients = array_values(array_filter($patientRows, static function ($p) use ($q): bool {
        return row_matches([$p['patient'], $p['mrn'], format_money($p['balance']), format_money($p['total_billed'])], $q);
    }));

    // Prioritize patients with active balances
    usort($filteredPatients, static function ($a, $b) {
        if ($a['balance'] > 0 && $b['balance'] <= 0) return -1;
        if ($a['balance'] <= 0 && $b['balance'] > 0) return 1;
        return strcmp($a['patient'], $b['patient']);
    });

    $paged = paginate_rows($filteredPatients, 10, 'p');

    $tb = toolbar_open('process-payment')
        . search_box('Search patient by name or MRN to collect payment...', 'q', $q)
        . filter_button('Search Patient')
        . toolbar_close();
    ?>

    <?= table_open(['Patient Name', 'MRN', 'Unpaid Invoices', 'Total Balance Due', 'Payment Status', 'Action'], $tb) ?>
    <?php foreach ($paged['rows'] as $p):
        $hasBal    = $p['balance'] > 0.009;
        $unpaidCnt = count($p['unpaid_bills']);
        $stLabel   = $hasBal ? ($unpaidCnt > 0 ? 'Pending Payment' : 'Partial') : ($p['total_bills'] > 0 ? 'Fully Paid' : 'No Invoices');
        $stTone    = $hasBal ? 'danger' : ($p['total_bills'] > 0 ? 'success' : 'neutral');
    ?>
        <tr>
            <td>
                <div class="person-cell">
                    <?= mini_avatar($p['patient']) ?>
                    <div>
                        <strong><?= e($p['patient']) ?></strong>
                        <span style="font-size:11px; color:var(--gray-500);"><?= (int) $p['total_bills'] ?> total statement<?= $p['total_bills'] === 1 ? '' : 's' ?></span>
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
            <td>
                <strong><?= (int) $unpaidCnt ?></strong>
                <span class="muted" style="font-size:11px;">unsettled</span>
            </td>
            <td>
                <strong style="color:<?= $hasBal ? 'var(--red)' : 'var(--gray-600)' ?>; font-size:13.5px;">
                    <?= e(format_money($p['balance'])) ?>
                </strong>
            </td>
            <td><?= badge($stLabel, $stTone) ?></td>
            <td style="white-space:nowrap;">
                <div class="appt-action-cell" style="display:inline-flex; align-items:center; gap:6px;">
                    <?php if ($hasBal): ?>
                        <a class="btn btn-primary btn-sm btn-appt-action"
                           href="<?= e(url(['page' => 'process-payment', 'patient' => $p['patient']])) ?>"
                           title="Proceed to collect payment for <?= e($p['patient']) ?>">
                            <?= icon('card', 14) ?><span>Process Payment</span>
                        </a>
                    <?php else: ?>
                        <a class="btn btn-secondary btn-sm btn-appt-action"
                           href="<?= e(url(['page' => 'process-payment', 'patient' => $p['patient']])) ?>"
                           title="View invoices for <?= e($p['patient']) ?>">
                            <span>View Invoices</span>
                        </a>
                    <?php endif; ?>
                    <a class="btn btn-secondary btn-sm btn-appt-action"
                       href="<?= e(url(['page' => 'unpaid-bills', 'view' => 'history', 'patient' => $p['patient']])) ?>"
                       title="View full billing statement history">
                        <?= icon('history', 13) ?><span>History</span>
                    </a>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?= table_close('patient', true, $paged['total'], $paged['page'], $paged['pages']) ?>

    <?php if (!$paged['rows']): ?>
        <?= filtered_empty('No matching patient records found.') ?>
    <?php endif; ?>

<?php else: ?>
    <!-- STEP 2: PROCEED TO PAYMENT PROCESSING VIEW -->
    <?php
    $ptRec   = find_patient_record($state, $selectedBill['patient']);
    $mrnStr  = $ptRec ? patient_mrn_display($ptRec) : ($ptRec[8] ?? 'MRN-1001');
    $ptHin   = $ptRec[3] ?? '—';
    $ptPhone = $ptRec[2] ?? '—';
    $balNum  = round($selectedBill['balance'], 2);
    $isPaid  = $selectedBill['status'] === 'Paid' || $balNum <= 0.009;
    $ptBills = array_values(array_filter($allBills, static fn ($b) => strcasecmp($b['patient'], $reqPt) === 0));
    ?>

    <?= page_title(
        'Process Payment: ' . e($reqPt),
        'MRN: ' . e($mrnStr) . ' · Invoice ' . e($selectedBill['bill_no']) . ' · Record collection with required proof upload for HMO and PhilHealth claims.',
        button('← Back to Patient List', ['variant' => 'secondary', 'href' => url(['page' => 'process-payment'])])
    ) ?>

    <!-- Bill Selector Tab if Patient has multiple bills -->
    <?php if (count($ptBills) > 1): ?>
        <div class="panel" style="padding: 12px 18px; margin-bottom: 20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; background:#f8fafc;">
            <div style="display:flex; align-items:center; gap:8px;">
                <span style="font-size:12.5px; font-weight:700; color:var(--navy-900);">Patient Invoices:</span>
                <span style="font-size:12px; color:var(--gray-500);"><?= count($ptBills) ?> invoices on file</span>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <?php foreach ($ptBills as $bTab):
                    $isTabActive = ($bTab['bill_no'] === $selectedBill['bill_no']);
                    $bTabBal = round($bTab['balance'], 2);
                ?>
                    <a class="btn <?= $isTabActive ? 'btn-primary' : 'btn-secondary' ?> btn-sm btn-appt-action"
                       href="<?= e(url(['page' => 'process-payment', 'patient' => $reqPt, 'bill' => $bTab['bill_no']])) ?>">
                        <span><?= e($bTab['bill_no']) ?> (<?= e(format_money($bTabBal)) ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="content-grid" style="align-items:start;">
        <!-- LEFT SIDE: Itemized Charges & Patient Information -->
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
                    <span style="font-size:11px; text-transform:uppercase; color:var(--gray-500); font-weight:700;">Balance Due</span>
                    <strong style="display:block; font-size:18px; color:<?= $isPaid ? 'var(--green)' : 'var(--red)' ?>;">
                        <?= e(format_money($balNum)) ?>
                    </strong>
                </div>
            </div>

            <!-- Patient Details Card -->
            <div class="bill-pt-meta">
                <div>
                    <span style="color:var(--gray-500); display:block; font-size:11px;">PATIENT</span>
                    <strong><?= e($selectedBill['patient']) ?></strong>
                </div>
                <div>
                    <span style="color:var(--gray-500); display:block; font-size:11px;">MRN</span>
                    <code><?= e($mrnStr) ?></code>
                </div>
                <div>
                    <span style="color:var(--gray-500); display:block; font-size:11px;">CONTACT / HIN</span>
                    <span><?= e($ptPhone) ?> / <?= e($ptHin) ?></span>
                </div>
            </div>

            <!-- Itemized Table -->
            <div style="padding: 0 18px;">
                <table style="width:100%; border-collapse:collapse; margin-top:10px;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--gray-200); font-size:11px; text-transform:uppercase; color:var(--gray-500);">
                            <th style="padding:10px 0; text-align:left;">Charge Description</th>
                            <th style="padding:10px 0; text-align:center;">Qty</th>
                            <th style="padding:10px 0; text-align:right;">Rate</th>
                            <th style="padding:10px 0; text-align:right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($selectedBill['items'] as $item): ?>
                            <tr style="border-bottom:1px solid var(--gray-100); font-size:12.5px;">
                                <td style="padding:11px 0;"><strong><?= e((string) $item[0]) ?></strong></td>
                                <td style="padding:11px 0; text-align:center; color:var(--gray-600);"><?= e((string) $item[1]) ?></td>
                                <td style="padding:11px 0; text-align:right; color:var(--gray-600);"><?= e((string) $item[2]) ?></td>
                                <td style="padding:11px 0; text-align:right; font-weight:600;"><?= e((string) $item[3]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Invoice Totals Summary -->
            <div style="padding: 16px 18px; border-top: 1px solid var(--gray-200); margin-top:12px; display:grid; gap:6px; font-size:13px;">
                <div style="display:flex; justify-content:space-between; color:var(--gray-600);">
                    <span>Total Invoiced</span>
                    <strong><?= e(format_money($selectedBill['total'])) ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; color:var(--green);">
                    <span>Total Paid So Far</span>
                    <strong>- <?= e(format_money($selectedBill['paid'])) ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; padding-top:8px; border-top:1px dashed var(--gray-300); font-size:15px;">
                    <strong>Net Balance Due</strong>
                    <strong style="color:<?= $isPaid ? 'var(--green)' : 'var(--navy-900)' ?>;">
                        <?= e(format_money($balNum)) ?>
                    </strong>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE: Payment Form with Proof Upload for HMO & PhilHealth -->
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2>Collect Payment</h2>
                    <p>Record payment by Cash, Card, HMO, or PhilHealth and issue Official Receipt.</p>
                </div>
                <?= icon('card', 20) ?>
            </div>

            <?php if ($isPaid): ?>
                <div style="padding: 36px 22px; text-align:center; display:grid; gap:12px; place-items:center;">
                    <span style="width:48px; height:48px; border-radius:50%; background:var(--green-bg); color:var(--green); display:grid; place-items:center;">
                        <?= icon('shield', 26) ?>
                    </span>
                    <strong style="font-size:16px; color:var(--navy-900);">This Invoice is Paid in Full</strong>
                    <p style="font-size:12px; color:var(--gray-500); max-width:320px;">
                        All charges for <b><?= e($selectedBill['bill_no']) ?></b> have been completely settled.
                    </p>
                    <div class="button-pair" style="margin-top:8px;">
                        <?= button('Official Receipt', ['icon' => 'receipt', 'href' => url(['page' => 'receipt', 'bill' => $selectedBill['bill_no']])]) ?>
                        <?= button('Patient List', ['variant' => 'secondary', 'href' => url(['page' => 'process-payment'])]) ?>
                    </div>
                </div>
            <?php else: ?>
                <form method="post" action="index.php" id="cashier-pay-form" enctype="multipart/form-data" style="padding: 20px 22px; display:grid; gap: 16px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="process_payment">
                    <input type="hidden" name="bill_no" value="<?= e($selectedBill['bill_no']) ?>">
                    <input type="hidden" name="return" value="<?= e(url(['page' => 'process-payment', 'patient' => $reqPt, 'bill' => $selectedBill['bill_no']])) ?>">

                    <label class="field">
                        <span class="field-label">Amount to Apply to Bill (₱)</span>
                        <span class="input-wrap">
                            <input type="number" name="amount_to_pay" id="pay-apply-amount"
                                   step="0.01" min="0.01" max="<?= e(number_format($balNum, 2, '.', '')) ?>"
                                   value="<?= e(number_format($balNum, 2, '.', '')) ?>" required>
                        </span>
                        <small class="field-hint">Defaults to balance due (<?= e(format_money($balNum)) ?>). Adjust for partial payments.</small>
                    </label>

                    <label class="field">
                        <span class="field-label">Payment Method *</span>
                        <span class="input-wrap">
                            <select name="payment_method" id="pay-method-select" required>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card (Credit / Debit)</option>
                                <option value="HMO">HMO (Letter of Authorization / Health Card)</option>
                                <option value="PhilHealth">PhilHealth Benefit Claim / Voucher</option>
                            </select>
                            <?= icon('down', 16) ?>
                        </span>
                    </label>

                    <!-- Reference Number (Card, HMO, PhilHealth) -->
                    <label class="field" id="pay-ref-wrap" style="display:none;">
                        <span class="field-label" id="pay-ref-label">Approval Code / Reference No. *</span>
                        <span class="input-wrap">
                            <input type="text" name="reference_no" id="pay-ref-input" placeholder="e.g. LOA-948201 / Case Rate No. / Auth Code">
                        </span>
                    </label>

                    <!-- Document Upload Proof for HMO and PhilHealth -->
                    <div id="pay-proof-wrap" style="display:none; background:#fffbeb; border:1px solid #fcd34d; border-radius:8px; padding:12px 14px;">
                        <label class="field" style="margin-bottom:0;">
                            <span class="field-label" style="color:#92400e; font-weight:700;">
                                📄 Upload Proof of Papers (LOA / PhilHealth Claim Documents) *
                            </span>
                            <span class="input-wrap">
                                <input type="file" name="proof_doc" id="pay-proof-input" accept="image/jpeg,image/png,image/webp,application/pdf" style="padding-top:6px;">
                            </span>
                            <small class="field-hint" style="color:#b45309;">
                                Mandatory: Upload photo or PDF of the patient's approved HMO LOA, guarantee paper, or PhilHealth claim voucher to store in hospital records.
                            </small>
                        </label>
                    </div>

                    <label class="field" id="pay-recv-wrap">
                        <span class="field-label">Amount Tendered / Received (₱)</span>
                        <span class="input-wrap">
                            <input type="number" name="amount_received" id="pay-received-amount"
                                   step="0.01" min="0.01"
                                   value="<?= e(number_format($balNum, 2, '.', '')) ?>" required>
                        </span>
                    </label>

                    <div id="pay-change-card" style="padding: 14px 16px; border-radius: var(--radius); background: var(--teal-50); border: 1px solid #ccece6; display:flex; justify-content:space-between; align-items:center;">
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
                    var refLabel   = document.getElementById('pay-ref-label');
                    var refInput   = document.getElementById('pay-ref-input');
                    var proofWrap  = document.getElementById('pay-proof-wrap');
                    var proofInput = document.getElementById('pay-proof-input');
                    var recvWrap   = document.getElementById('pay-recv-wrap');
                    var changeCard = document.getElementById('pay-change-card');
                    var changeVal  = document.getElementById('pay-change-value');

                    function updateCalc() {
                        var m = methodSel.value;
                        if (m === 'Cash') {
                            refWrap.style.display = 'none';
                            refInput.required = false;
                            proofWrap.style.display = 'none';
                            proofInput.required = false;
                            recvWrap.style.display = '';
                            changeCard.style.display = 'flex';
                        } else if (m === 'Card') {
                            refWrap.style.display = '';
                            refLabel.textContent = 'Card Approval Code / Trace No. *';
                            refInput.required = true;
                            proofWrap.style.display = 'none';
                            proofInput.required = false;
                            recvWrap.style.display = '';
                            changeCard.style.display = 'none';
                        } else if (m === 'HMO') {
                            refWrap.style.display = '';
                            refLabel.textContent = 'HMO Approval / LOA Reference No. *';
                            refInput.required = true;
                            proofWrap.style.display = 'block';
                            proofInput.required = true;
                            recvWrap.style.display = 'none';
                            recvInput.value = applyInput.value;
                            changeCard.style.display = 'none';
                        } else if (m === 'PhilHealth') {
                            refWrap.style.display = '';
                            refLabel.textContent = 'PhilHealth Claim / Case Rate Reference No. *';
                            refInput.required = true;
                            proofWrap.style.display = 'block';
                            proofInput.required = true;
                            recvWrap.style.display = 'none';
                            recvInput.value = applyInput.value;
                            changeCard.style.display = 'none';
                        }

                        var app = parseFloat(applyInput.value || '0');
                        var rcv = parseFloat(recvInput.value || '0');
                        var chg = Math.max(0, rcv - app);
                        changeVal.textContent = '₱' + chg.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }

                    methodSel.addEventListener('change', updateCalc);
                    applyInput.addEventListener('input', function() {
                        if (methodSel.value === 'HMO' || methodSel.value === 'PhilHealth') {
                            recvInput.value = applyInput.value;
                        }
                        updateCalc();
                    });
                    recvInput.addEventListener('input', updateCalc);
                    updateCalc();
                })();
                </script>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
