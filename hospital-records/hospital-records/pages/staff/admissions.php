<?php
declare(strict_types=1);

$view        = ($_GET['view'] ?? 'Current admissions') === 'Discharged' ? 'Discharged' : 'Current admissions';
[$from, $to] = range_params();
$q           = trim((string) ($_GET['q'] ?? ''));
$wardFilter  = (string) ($_GET['ward'] ?? 'All wards');
$statusFilt  = (string) ($_GET['adm_status'] ?? 'All statuses');

$wards = ['All wards', 'Cardiology Ward', 'Medical Ward', 'Pediatrics Ward', 'Maternity Ward', 'Orthopedics Ward', 'ICU'];

if ($view === 'Current admissions') {
    $toolbar = toolbar_open('admissions', ['view' => $view])
        . search_box('Search patient name, MRN, room/bed, or attending doctor...', 'q', $q)
        . '<div class="toolbar-right">'
        . select_field('', 'ward', $wards, $wardFilter)
        . select_field('', 'adm_status', ['All statuses', 'Admitted', 'For discharge'], $statusFilt)
        . filter_button()
        . '</div>'
        . toolbar_close();
} else {
    $toolbar = toolbar_open('admissions', ['view' => $view])
        . search_box('Search discharged patient, MRN, room/bed, or diagnosis...', 'q', $q)
        . '<div class="toolbar-right">'
        . select_field('', 'ward', $wards, $wardFilter)
        . date_range_fields($from, $to)
        . filter_button()
        . '</div>'
        . toolbar_close();
}
?>
<?= page_title(
    'Inpatient Admissions & Discharges',
    'Manage inpatient admissions, assign vacant hospital beds, and process discharges after doctor order and billing clearance.',
    button('Admit patient', ['icon' => 'plus', 'href' => url(['page' => 'admissions', 'modal' => 'Admit patient'])])
) ?>

<?= tabs(['Current admissions', 'Discharged'], $view, 'view') ?>

<?php if ($view === 'Current admissions'): ?>
    <?php
    $filtered = [];
    foreach ($state['admissions'] as $idx => $r) {
        $wMatch = ($wardFilter === 'All wards' || strcasecmp((string) ($r[2] ?? ''), $wardFilter) === 0);
        $aStat  = (string) ($r[6] ?? 'Admitted');
        $sMatch = ($statusFilt === 'All statuses' || strcasecmp($aStat, $statusFilt) === 0);
        if ($wMatch && $sMatch && row_matches($r, $q)) {
            $filtered[] = ['idx' => $idx, 'row' => $r];
        }
    }
    $paged = paginate_rows($filtered, 10, 'p');
    ?>
    <?= table_open(['Patient & MRN', 'Room / Bed', 'Ward', 'Admitted Date', 'Attending Doctor', 'Admission Diagnosis', 'Status', 'Billing Clearance', 'Actions'], $toolbar) ?>
    <?php foreach ($paged['rows'] as $entry):
        $r      = $entry['row'];
        $ptRec  = find_patient_record($state, (string) ($r[0] ?? ''));
        $mrnStr = $ptRec ? patient_mrn_display($ptRec) : (string) ($r[8] ?? '—');
        $aStat  = (string) ($r[6] ?? 'Admitted');
        $bClear = (string) ($r[7] ?? 'Pending');
    ?>
        <tr>
            <td>
                <strong><?= e($r[0]) ?></strong>
                <div style="font-size:11px; color:var(--teal-700); font-weight:700;"><code><?= e($mrnStr ?: '—') ?></code></div>
            </td>
            <td><?= badge($r[1], 'info') ?></td>
            <td><?= e($r[2]) ?></td>
            <td><?= e($r[3]) ?></td>
            <td><?= e($r[4]) ?></td>
            <td><span class="complaint-text"><?= e($r[5] ?? 'Inpatient observation') ?></span></td>
            <td><?= badge($aStat, status_tone($aStat)) ?></td>
            <td>
                <?= badge($bClear, status_tone($bClear)) ?>
                <?php if (!empty($r[10])): ?>
                    <div class="muted" style="font-size:10.5px;"><?= e($r[10]) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <div class="row-actions">
                    <?= button('Discharge', [
                        'variant' => $aStat === 'For discharge' ? 'primary' : 'secondary',
                        'href'    => url(['page' => 'admissions', 'modal' => 'Discharge patient', 'patient' => $r[0]]),
                    ]) ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?= table_close('current admission', true, $paged['total'], $paged['page'], $paged['pages']) ?>
    <?php if (!$paged['rows']): ?>
        <?= filtered_empty('No current admissions match this search.') ?>
    <?php endif; ?>
<?php else: ?>
    <?php
    $allDis = array_reverse($state['discharges']);
    $filtered = array_values(array_filter(
        $allDis,
        static function ($d) use ($from, $to, $q, $wardFilter): bool {
            $wMatch = ($wardFilter === 'All wards' || strcasecmp((string) ($d[2] ?? ''), $wardFilter) === 0);
            return $wMatch && in_range((int) ($d[5] ?? time()), $from, $to) && row_matches([$d[0], $d[1], $d[2], $d[3], $d[6] ?? '', $d[7] ?? '', $d[8] ?? '', $d[9] ?? ''], $q);
        }
    ));
    $paged = paginate_rows($filtered, 10, 'p');
    ?>
    <?= table_open(['Patient & MRN', 'Room / Bed & Ward', 'Stay Dates & Length', 'Attending Doctor', 'Discharge Type', 'Final Diagnosis', 'Clearance / Proof'], $toolbar) ?>
    <?php foreach ($paged['rows'] as $d):
        $ptRec  = find_patient_record($state, (string) ($d[0] ?? ''));
        $mrnStr = $ptRec ? patient_mrn_display($ptRec) : (string) ($d[6] ?? '—');
    ?>
        <tr>
            <td>
                <strong><?= e($d[0]) ?></strong>
                <div style="font-size:11px; color:var(--teal-700); font-weight:700;"><code><?= e($mrnStr ?: '—') ?></code></div>
            </td>
            <td>
                <?= badge($d[1], 'info') ?>
                <div class="muted" style="font-size:11px; margin-top:3px;"><?= e($d[2]) ?></div>
            </td>
            <td>
                <strong>Discharged: <?= e($d[3]) ?></strong>
                <div class="muted" style="font-size:11px;">Admitted: <?= e($d[10] ?? '—') ?> · <?= e((string) ($d[11] ?? '1')) ?> day(s)</div>
            </td>
            <td><?= e($d[7] ?? 'Attending Physician') ?></td>
            <td><?= badge($d[8] ?? 'Recovered / Routine', 'neutral') ?></td>
            <td><span class="complaint-text"><?= e($d[9] ?? 'Resolved') ?></span></td>
            <td>
                <?= $d[4] ? proof_thumb($d[4], 'Proof of discharge for ' . $d[0]) : badge('Cleared', 'success') ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?= table_close('discharged record', true, $paged['total'], $paged['page'], $paged['pages']) ?>
    <?php if (!$paged['rows']): ?>
        <?= filtered_empty('No discharged patients in this date range.') ?>
    <?php endif; ?>
<?php endif; ?>
