<?php
$ptName  = current_user_name('Patient');
$billIdx = isset($_GET['bill']) ? (int) $_GET['bill'] : 0;
$mob     = $_GET['mobile'] ?? null;

$bill = $state['bills'][$billIdx] ?? null;
if (!$bill || $bill[0] !== $ptName) {
    foreach ($state['bills'] as $idx => $b) {
        if ($b[0] === $ptName) {
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
</div>
