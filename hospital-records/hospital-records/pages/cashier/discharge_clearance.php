<?php
declare(strict_types=1);

$q      = trim((string) ($_GET['q'] ?? ''));
$excPt  = trim((string) ($_GET['exception_patient'] ?? ''));

$admissions = array_values(array_filter(
    $state['admissions'] ?? [],
    static fn ($r) => row_matches($r, $q)
));
?>
<?= page_title(
    'Discharge Billing Clearance',
    'Mark an inpatient admission’s billing status as Cleared (or Exception with a documented reason) so Front Desk Staff can complete the discharge.'
) ?>

<?php if ($excPt !== ''): ?>
    <div class="panel" style="padding:20px 22px; margin-bottom:20px; border-color:var(--amber-border); background:var(--amber-bg);">
        <h3 style="margin:0 0 6px; font-size:15px;">Grant Exception Clearance for <?= e($excPt) ?></h3>
        <p style="font-size:12px; color:var(--gray-700); margin:0 0 12px;">Document the approved reason for releasing the patient prior to full cash settlement (e.g., signed promissory note, DSWD/MAIP guarantee letter, or corporate HMO guarantee).</p>
        <form method="post" action="index.php" style="display:grid; gap:12px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="clear_discharge_billing">
            <input type="hidden" name="patient" value="<?= e($excPt) ?>">
            <input type="hidden" name="clearance_status" value="Exception">
            <?= field('Documented Exception Reason *', 'reason', 'e.g. Approved Promissory Note #PN-2026-04 / DSWD Guarantee Letter', 'text', '', ['required' => true]) ?>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <?= button('Cancel', ['variant' => 'secondary', 'href' => url(['page' => 'discharge-clearance'])]) ?>
                <?= button('Confirm Exception Clearance', ['type' => 'submit', 'icon' => 'check']) ?>
            </div>
        </form>
    </div>
<?php endif; ?>

<?= table_open(['Patient & MRN', 'Room / Bed & Ward', 'Admission Status', 'Current Bill Balance', 'Billing Clearance Status', 'Clearance Actions']) ?>
<?php foreach ($admissions as $adm):
    $ptName = (string) ($adm[0] ?? '');
    $ptRec  = find_patient_record($state, $ptName);
    $mrnStr = $ptRec ? patient_mrn_display($ptRec) : ($adm[8] ?? '');
    $aStat  = (string) ($adm[6] ?? 'Admitted');
    $bClear = (string) ($adm[7] ?? 'Pending');
    $cNotes = (string) ($adm[10] ?? '');

    // Find unpaid balance for this patient
    $ptBal   = 0.0;
    $ptBillNo = '';
    foreach ($state['bills'] ?? [] as $idx => $b) {
        $nb = normalize_bill_row($b, $idx);
        if (strcasecmp($nb['patient'], $ptName) === 0) {
            $ptBal   += $nb['balance'];
            $ptBillNo = $nb['bill_no'];
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
        <td>
            <div class="row-actions" style="gap:6px; flex-wrap:wrap;">
                <?= post_button('Mark Cleared', 'clear_discharge_billing', ['patient' => $ptName, 'clearance_status' => 'Cleared', 'reason' => 'All inpatient charges verified and settled'], ['variant' => 'primary']) ?>
                <?= button('Grant Exception', ['variant' => 'secondary', 'href' => url(['page' => 'discharge-clearance', 'exception_patient' => $ptName])]) ?>
                <?php if ($ptBillNo !== '' && $ptBal > 0): ?>
                    <?= button('Collect Payment', ['variant' => 'secondary', 'href' => url(['page' => 'process-payment', 'bill' => $ptBillNo])]) ?>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close('inpatient admission', false) ?>

<?php if (!$admissions): ?>
    <?= filtered_empty('No current admissions found.') ?>
<?php endif; ?>
