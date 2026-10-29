<?php
$orNo      = trim((string) ($_GET['or'] ?? ''));
$autoPrint = isset($_GET['print']);

$payment = null;
foreach ($state['payments'] ?? [] as $py) {
    if ($orNo === '' || strcasecmp((string) ($py['receipt_no'] ?? ''), $orNo) === 0) {
        $payment = $py;
        break;
    }
}

if ($payment === null): ?>
    <a class="back-link" href="<?= e(url(['page' => 'payment-history'])) ?>">← Back to payment history</a>
    <?= filtered_empty('Official receipt not found.') ?>
<?php return; endif;

$ptRec   = find_patient_record($state, (string) ($payment['patient_name'] ?? ''));
$ptId    = $ptRec[8] ?? 'P-1001';
$ptHin   = $ptRec[3] ?? '—';
$items   = (!empty($payment['items']) && is_array($payment['items']))
    ? $payment['items']
    : default_bill_items(parse_money($payment['amount_paid'] ?? 0));

$actions = '<div class="button-pair">'
    . button('Back to History', ['variant' => 'secondary', 'href' => url(['page' => 'payment-history'])])
    . '<button type="button" class="btn btn-primary" onclick="window.print()">' . icon('printer', 17) . '<span>Print Receipt</span></button>'
    . '</div>';
?>
<a class="back-link" href="<?= e(url(['page' => 'payment-history'])) ?>">← Back to payment history</a>

<?= page_title(
    'Official Receipt ' . ($payment['receipt_no'] ?? 'OR-1001'),
    'Bill ' . ($payment['bill_no'] ?? '—') . ' · Issued ' . ($payment['paid_at'] ?? ''),
    $actions
) ?>

<div class="invoice" id="printable-receipt">
    <div class="invoice-head">
        <div>
            <?= logo() ?>
            <p style="font-size:11px; color:var(--gray-500); margin-top:6px;">
                1450 Harbor Avenue, San Francisco, CA 94107 · Official Hospital Payment Receipt
            </p>
        </div>
        <div style="text-align:right;">
            <?= badge('OFFICIAL RECEIPT', 'success') ?>
            <div style="margin-top:8px; font-size:16px; font-weight:800; color:var(--navy-900);">
                <?= e($payment['receipt_no'] ?? 'OR-1001') ?>
            </div>
            <div style="font-size:11px; color:var(--gray-500);">
                Date: <?= e($payment['paid_at'] ?? date('M d, Y · H:i')) ?>
            </div>
        </div>
    </div>

    <div class="invoice-parties" style="grid-template-columns: repeat(3, 1fr); gap: 24px;">
        <div>
            <span>Received From (Patient)</span>
            <strong><?= e($payment['patient_name'] ?? '—') ?></strong>
            <p>
                Patient ID: <code><?= e($ptId) ?></code><br>
                Insurance: <?= e($ptHin) ?>
            </p>
        </div>
        <div>
            <span>Invoice &amp; Payment Details</span>
            <strong>Bill No. <?= e($payment['bill_no'] ?? '—') ?></strong>
            <p>
                Payment Method: <b><?= e($payment['payment_method'] ?? 'Cash') ?></b><br>
                Reference No.: <?= e(($payment['reference_no'] ?? '—') !== '' ? $payment['reference_no'] : '—') ?>
            </p>
        </div>
        <div>
            <span>Hospital &amp; Cashier</span>
            <strong><?= e(APP_NAME) ?> Health Hospital</strong>
            <p>
                Cashier: <b><?= e($payment['cashier_name'] ?? current_user_name($role)) ?></b><br>
                Status: Paid / Verified
            </p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Itemized Charge Description</th>
                <th style="width:75px; text-align:center;">Qty</th>
                <th style="width:120px; text-align:right;">Rate</th>
                <th style="width:120px; text-align:right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $line): ?>
                <tr>
                    <td><?= e((string) ($line[0] ?? 'Hospital service')) ?></td>
                    <td style="text-align:center;"><?= e((string) ($line[1] ?? '1')) ?></td>
                    <td style="text-align:right;"><?= e((string) ($line[2] ?? '$0.00')) ?></td>
                    <td style="text-align:right;"><strong><?= e((string) ($line[3] ?? '$0.00')) ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="width: 320px; margin-left: auto; padding-top: 18px; display: grid; gap: 8px; font-size: 12px;">
        <div style="display:flex; justify-content:space-between; color:var(--gray-600);">
            <span>Amount Received</span>
            <strong><?= e(format_money($payment['amount_received'] ?? ($payment['amount_paid'] ?? 0))) ?></strong>
        </div>
        <div style="display:flex; justify-content:space-between; color:var(--gray-600);">
            <span>Change Due</span>
            <strong><?= e(format_money($payment['change_due'] ?? 0)) ?></strong>
        </div>
        <div class="invoice-total" style="width:100%; padding:14px 0 6px; border-top:2px solid var(--gray-200);">
            <span style="font-weight:700; color:var(--navy-900);">Amount Paid</span>
            <strong><?= e(format_money($payment['amount_paid'] ?? 0)) ?></strong>
        </div>
    </div>

    <div style="margin-top:28px; padding-top:18px; border-top:1px dashed var(--gray-200); display:flex; justify-content:space-between; align-items:end; font-size:11px; color:var(--gray-500);">
        <div>
            Thank you for choosing <strong><?= e(APP_NAME) ?> Health</strong>.<br>
            Keep this official receipt for your medical and insurance records.
        </div>
        <div style="text-align:right;">
            <div style="border-top:1px solid var(--gray-400); padding-top:5px; min-width:180px; color:var(--navy-900); font-weight:600;">
                <?= e($payment['cashier_name'] ?? current_user_name($role)) ?>
            </div>
            <span>Authorized Cashier Signature</span>
        </div>
    </div>
</div>

<?php if ($autoPrint): ?>
<script>
window.addEventListener('load', function () {
    window.print();
});
</script>
<?php endif; ?>
