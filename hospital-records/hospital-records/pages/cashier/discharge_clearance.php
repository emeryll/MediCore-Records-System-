<?php
declare(strict_types=1);

$q = trim((string) ($_GET['q'] ?? ''));

$admissions = array_values(array_filter(
    $state['admissions'] ?? [],
    static fn ($r) => row_matches($r, $q)
));
?>
<?= page_title(
    'Discharge Billing Clearance',
    'Discharge billing clearance can only be marked Cleared after processing full payment.'
) ?>

<?= table_open(['Patient & MRN', 'Room / Bed & Ward', 'Admission Status', 'Current Bill Balance', 'Billing Clearance Status', 'Clearance Actions']) ?>
<?php foreach ($admissions as $adm):
    $ptName = (string) ($adm[0] ?? '');
    $ptRec  = find_patient_record($state, $ptName);
    $mrnStr = $ptRec ? patient_mrn_display($ptRec) : ($adm[8] ?? '');
    $aStat  = (string) ($adm[6] ?? 'Admitted');
    $bClear = (string) ($adm[7] ?? 'Pending');
    $cNotes = (string) ($adm[10] ?? '');

    // Find unpaid balance for this patient
    $ptBal    = 0.0;
    $ptBillNo = '';
    foreach ($state['bills'] ?? [] as $idx => $b) {
        $nb = normalize_bill_row($b, $idx);
        if (strcasecmp($nb['patient'], $ptName) === 0) {
            $ptBal += $nb['balance'];
            if ($ptBillNo === '' || $nb['balance'] > 0) {
                $ptBillNo = $nb['bill_no'];
            }
        }
    }
?>
    <tr>
        <td>
            <strong><?= e($ptName) ?></strong>
            <div style="font-size:11px; color:var(--teal-700); font-weight:700;"><code><?= e($mrnStr ?: '—') ?></code></div>
        </td>
        <td>
            <?= badge($adm[1], 'info') ?>
            <div class="muted" style="font-size:11px;"><?= e($adm[2]) ?> · Admitted <?= e($adm[3]) ?></div>
        </td>
        <td><?= badge($aStat, status_tone($aStat)) ?></td>
        <td>
            <strong style="color:<?= $ptBal > 0 ? 'var(--red)' : 'var(--green)' ?>;"><?= e(format_money($ptBal)) ?></strong>
            <?php if ($ptBillNo !== ''): ?>
                <div class="muted" style="font-size:11px;">Bill <code><?= e($ptBillNo) ?></code></div>
            <?php endif; ?>
        </td>
        <td>
            <?= badge($bClear, status_tone($bClear)) ?>
            <?php if ($cNotes !== ''): ?>
                <div class="muted" style="font-size:11px; margin-top:3px;"><?= e($cNotes) ?></div>
            <?php endif; ?>
        </td>
        <td style="white-space:nowrap;">
            <div class="appt-action-cell" style="display:inline-flex; align-items:center; gap:6px;">
                <?php if ($ptBal > 0.009): ?>
                    <!-- Patient has outstanding balance: MUST process payment first! -->
                    <a class="btn btn-primary btn-sm btn-appt-action" href="<?= e(url(['page' => 'process-payment', 'bill' => $ptBillNo, 'patient' => $ptName])) ?>" title="Process payment before marking cleared">
                        <?= icon('card', 14) ?><span>Process Payment (<?= e(format_money($ptBal)) ?>)</span>
                    </a>
                <?php elseif ($bClear !== 'Cleared'): ?>
                    <!-- Payment has been processed and balance is zero: can now be marked cleared -->
                    <?= post_button('Mark Cleared', 'clear_discharge_billing', ['patient' => $ptName, 'clearance_status' => 'Cleared', 'reason' => 'All inpatient charges verified and settled'], ['variant' => 'primary', 'class' => 'btn-sm btn-appt-action', 'icon' => 'check']) ?>
                <?php else: ?>
                    <span style="font-size:12px; color:var(--teal-800); font-weight:700; display:inline-flex; align-items:center; gap:4px;">
                        <?= icon('check', 14) ?> Cleared
                    </span>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close('inpatient admission', false) ?>

<?php if (!$admissions): ?>
    <?= filtered_empty('No current admissions found.') ?>
<?php endif; ?>
