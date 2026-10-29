<?php
$idInfo  = resolve_patient_identity($state);
$ptName  = $idInfo['name'];
$billIdx = isset($_GET['bill']) ? (int) $_GET['bill'] : 0;
$mob     = $_GET['mobile'] ?? null;

$bill = $state['bills'][$billIdx] ?? null;
if (!$bill || !matches_patient_identity($idInfo, $bill[0] ?? '', null)) {
    foreach ($state['bills'] as $idx => $b) {
        if (matches_patient_identity($idInfo, $b[0] ?? '', null)) {
            $bill    = $b;
            $billIdx = $idx;
            break;
        }
    }
}
if ($bill === null): ?>
    <a class="back-link" href="<?= e(url(['page' => 'bills', 'mobile' => $mob])) ?>">← Back to bills</a>
    <?= filtered_empty('No billing statement found.') ?>
<?php return; endif;
$nb      = normalize_bill_row($bill, $billIdx);
$invCode = $nb['bill_no'];
$lines   = $nb['items'];

$actions = '<div class="button-pair">'
    . '<button type="button" class="btn btn-secondary" onclick="window.print()">' . icon('printer', 17) . '<span>Print / Download PDF</span></button>'
    . '</div>';
?>
<a class="back-link" href="<?= e(url(['page' => 'bills', 'mobile' => $mob])) ?>">← Back to bills</a>

<?= page_title('Statement ' . $invCode, 'Issued ' . $nb['issued'] . ' · Patient copy', $actions) ?>

<div class="invoice">
    <div class="invoice-head">
        <?= logo() ?>
        <?= badge($nb['status'], status_tone($nb['status'])) ?>
    </div>

    <div class="invoice-parties">
        <?php
        $billPtRec = find_patient_record($state, $ptName);
        $billPtId  = $billPtRec[8] ?? ($_SESSION['user_id'] ?? 'P-1001');
        $billPtDob = format_birthdate($billPtRec[1] ?? '');
        $billPtAge = patient_age_label($billPtDob, true, '—');
        $billPtHin = $billPtRec[3] ?? '—';
        ?>
        <div>
            <span>Billed to</span>
            <strong><?= e($ptName) ?></strong>
            <p>Patient ID <?= e($billPtId) ?> · Birthday: <?= e($billPtDob) ?> (<?= e($billPtAge) ?>)<br>Insurance <?= e($billPtHin) ?></p>
        </div>
        <div>
            <span>Facility</span>
            <strong>MediCore Health</strong>
            <p>1450 Harbor Avenue<br>San Francisco, CA 94107</p>
        </div>
    </div>

    <table>
        <thead><tr><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($lines as $l): ?>
            <tr><td><?= e((string) ($l[0] ?? '')) ?></td><td><?= e((string) ($l[1] ?? '1')) ?></td><td><?= e((string) ($l[2] ?? '$0.00')) ?></td><td><?= e((string) ($l[3] ?? '$0.00')) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div style="width: 290px; margin-left: auto; padding-top: 16px; display: grid; gap: 6px; font-size: 12px;">
        <div style="display:flex; justify-content:space-between; color:var(--gray-600);">
            <span>Total Charges</span>
            <strong><?= e(format_money($nb['total'])) ?></strong>
        </div>
        <div style="display:flex; justify-content:space-between; color:var(--green);">
            <span>Paid So Far</span>
            <strong><?= e(format_money($nb['paid'])) ?></strong>
        </div>
        <div class="invoice-total" style="width:100%; padding:12px 0 0; border-top:1px solid var(--gray-200);">
            <span><?= $nb['status'] === 'Paid' ? 'Balance Due (Settled)' : 'Balance Due' ?></span>
            <strong><?= e(format_money($nb['balance'])) ?></strong>
        </div>
    </div>

    <?php
    $relatedPayments = [];
    foreach ($state['payments'] ?? [] as $py) {
        if (strcasecmp((string) ($py['bill_no'] ?? ''), $invCode) === 0) {
            $relatedPayments[] = $py;
        }
    }
    ?>

    <?php if ($relatedPayments): ?>
        <div style="margin-top: 28px; padding-top: 20px; border-top: 1px dashed var(--gray-300);">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                <span style="color:var(--green);"><?= icon('card', 18) ?></span>
                <strong style="font-size:13px; color:var(--navy-900);">Official Payment Receipts &amp; Collections</strong>
            </div>
            <div style="display:grid; gap:10px;">
                <?php foreach ($relatedPayments as $py): ?>
                    <div style="background:var(--gray-50); border:1px solid var(--gray-200); border-radius:8px; padding:12px 16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <code style="font-weight:700; color:var(--teal-800); font-size:12px;"><?= e($py['receipt_no']) ?></code>
                                <?= badge($py['payment_method'], 'success') ?>
                                <span style="font-size:11px; color:var(--gray-500);"><?= e($py['paid_at']) ?></span>
                            </div>
                            <p style="font-size:11px; color:var(--gray-600); margin:4px 0 0;">
                                Collected by <strong><?= e($py['cashier_name'] ?? 'Cashier') ?></strong>
                                <?= !empty($py['reference_no']) && $py['reference_no'] !== '—' ? ' · Ref: <code>' . e($py['reference_no']) . '</code>' : '' ?>
                            </p>
                        </div>
                        <div style="display:flex; align-items:center; gap:16px;">
                            <strong style="font-size:14px; color:var(--green);"><?= e(format_money($py['amount_paid'])) ?></strong>
                            <a class="btn btn-secondary" style="padding:6px 12px; font-size:11px;" href="<?= e(url(['page' => 'bills', 'detail' => 'receipt', 'or' => $py['receipt_no'], 'from_page' => 'bills'])) ?>">
                                <?= icon('printer', 14) ?> <span style="margin-left:4px;">View Official Receipt</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif ($nb['balance'] > 0): ?>
        <div style="margin-top: 24px; padding: 14px 18px; background: var(--amber-bg); border: 1px solid var(--amber-border); border-radius: 8px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div>
                <strong style="color: var(--amber-text); font-size: 12px;">Outstanding balance of <?= e(format_money($nb['balance'])) ?></strong>
                <p style="color: var(--gray-600); font-size: 11px; margin: 2px 0 0;">Please present your Bill No. <code><?= e($invCode) ?></code> to the Hospital Cashier to pay via Cash, Card, GCash, or Insurance.</p>
            </div>
            <?= badge('Payment Pending', 'warning') ?>
        </div>
    <?php endif; ?>
</div>
