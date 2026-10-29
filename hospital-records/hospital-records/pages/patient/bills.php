<?php
$idInfo  = resolve_patient_identity($state);
$ptName  = $idInfo['name'];
$mob     = $_GET['mobile'] ?? null;

$myBills = [];
foreach ($state['bills'] as $idx => $b) {
    if (matches_patient_identity($idInfo, $b[0] ?? '', null)) {
        $myBills[$idx] = $b;
    }
}
?>
<?= page_title(
    'Bills & Invoices',
    'View your hospital billing statements and download or print receipts.'
) ?>

<?= table_open(['Bill No.', 'Date issued', 'Total', 'Paid So Far', 'Balance', 'Status', 'Actions']) ?>
<?php foreach ($myBills as $idx => $b):
    $nb = normalize_bill_row($b, $idx);
?>
    <tr>
        <td><code><?= e($nb['bill_no']) ?></code></td>
        <td><?= e($nb['issued']) ?></td>
        <td><strong><?= e(format_money($nb['total'])) ?></strong></td>
        <td style="color:var(--green); font-weight:600;"><?= e(format_money($nb['paid'])) ?></td>
        <td><strong style="color:<?= $nb['balance'] > 0 ? 'var(--red)' : 'var(--gray-700)' ?>;"><?= e(format_money($nb['balance'])) ?></strong></td>
        <td><?= badge($nb['status'], status_tone($nb['status'])) ?></td>
        <td>
            <div class="row-actions">
                <a class="text-link" href="<?= e(url(['page' => 'bills', 'detail' => 'bill', 'bill' => $idx, 'mobile' => $mob])) ?>">
                    View statement <?= icon('arrow', 14) ?>
                </a>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close(count($myBills) . ' invoice' . (count($myBills) !== 1 ? 's' : ''), false) ?>

<?php if (!$myBills): ?>
    <?= filtered_empty('No billing statements found for your account.') ?>
<?php endif; ?>
