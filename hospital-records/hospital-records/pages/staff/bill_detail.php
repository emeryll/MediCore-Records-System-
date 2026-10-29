<?php
$patient = (string) ($_GET['patient'] ?? '');
$bill    = null;
foreach ($state['bills'] as $b) {
    if ($b[0] === $patient) {
        $bill = $b;
    }
}
$bill ??= $state['bills'][0] ?? null;
if ($bill === null): ?>
    <a class="back-link" href="<?= e(url(['page' => 'billing'])) ?>">← Back to billing</a>
    <?= filtered_empty('Bill record not found.') ?>
<?php return; endif;

$patient = $bill[0];
$receipt = $state['receipts'][$patient] ?? null;
$lines = [
    ['Inpatient / consultation services', '1', $bill[1], $bill[1]],
];
$actions = notify_button('Print', 'Press Ctrl/Cmd + P to print this invoice', ['variant' => 'secondary', 'icon' => 'printer']);
?>
<a class="back-link" href="<?= e(url(['page' => 'billing'])) ?>">← Back to billing</a>

<?= page_title('Invoice Statement', 'Issued ' . $bill[3], $actions) ?>

<div class="invoice">
    <div class="invoice-head">
        <?= logo() ?>
        <?= badge($bill[2], status_tone($bill[2])) ?>
    </div>

    <div class="invoice-parties">
        <?php
        $billPtRec = find_patient_record($state, $patient);
        $billPtId  = $billPtRec[8] ?? 'P-1001';
        $billPtDob = format_birthdate($billPtRec[1] ?? '');
        $billPtAge = patient_age_label($billPtDob, true, '—');
        ?>
        <div>
            <span>Billed to</span>
            <strong><?= e($patient) ?></strong>
            <p>Patient ID <?= e($billPtId) ?><?= $billPtDob !== '—' ? ' · Birthday: ' . e($billPtDob) . ' (' . e($billPtAge) . ')' : '' ?></p>
        </div>
        <div>
            <span>Hospital</span>
            <strong>MediCore Health</strong>
            <p>1450 Harbor Avenue<br>San Francisco, CA 94107</p>
        </div>
    </div>

    <table>
        <thead><tr><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($lines as $l): ?>
            <tr><td><?= e($l[0]) ?></td><td><?= e($l[1]) ?></td><td><?= e($l[2]) ?></td><td><?= e($l[3]) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="invoice-total">
        <span><?= $bill[2] === 'Paid' ? 'Total paid' : 'Total due' ?></span>
        <strong><?= e($bill[1]) ?></strong>
    </div>

    <div class="receipt-box">
        <h3>Proof of payment</h3>
        <?php if ($receipt): ?>
            <?= proof_thumb($receipt, 'Receipt for ' . $patient) ?>
        <?php else: ?>
            <form method="post" action="index.php" enctype="multipart/form-data" class="receipt-form">
                <input type="hidden" name="action" value="upload_receipt">
                <input type="hidden" name="key" value="<?= e($patient) ?>">
                <input type="hidden" name="return" value="<?= e(url($_GET)) ?>">
                <?= csrf_field() ?>
                <?= file_field('Upload receipt (image)', 'receipt') ?>
                <?= button('Upload receipt & mark as paid', ['type' => 'submit']) ?>
            </form>
        <?php endif; ?>
    </div>
</div>
