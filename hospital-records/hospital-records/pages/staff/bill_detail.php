<?php
$lines = [
    ['Cardiology consultation', '1', '$280.00', '$280.00'],
    ['Echocardiogram', '1', '$650.00', '$650.00'],
    ['Laboratory panel', '1', '$350.00', '$350.00'],
];
$actions = '<div class="button-pair">'
    . notify_button('Print', 'Press Ctrl/Cmd + P to print this invoice', ['variant' => 'secondary', 'icon' => 'printer'])
    . notify_button('Mark as paid', 'Bill marked as paid')
    . '</div>';
?>
<a class="back-link" href="<?= e(url(['page' => 'billing'])) ?>">← Back to billing</a>

<?= page_title('Bill #INV-2025-0842', 'Issued March 18, 2025 · Due April 1, 2025', $actions) ?>

<div class="invoice">
    <div class="invoice-head">
        <?= logo() ?>
        <?= badge('Unpaid', 'danger') ?>
    </div>

    <div class="invoice-parties">
        <div>
            <span>Billed to</span>
            <strong>Michael Torres</strong>
            <p>Patient #PT-002913<br>HIN-293-771</p>
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
        <span>Total due</span>
        <strong>$1,280.00</strong>
    </div>
</div>
