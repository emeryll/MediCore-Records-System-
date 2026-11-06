<?php
declare(strict_types=1);

$reportTab = in_array($_GET['rtab'] ?? '', ['Revenue', 'Appointments', 'Admissions'], true)
    ? $_GET['rtab']
    : 'Revenue';
[$from, $to] = range_params();
$q = trim((string) ($_GET['q'] ?? ''));

$allPayments = $state['payments'] ?? [];
$allAppts    = $state['appointments'] ?? [];
$allAdms     = $state['admissions'] ?? [];
$allDis      = $state['discharges'] ?? [];

$totalRev = 0.0;
foreach ($allPayments as $py) {
    $totalRev += parse_money($py['amount_paid'] ?? 0);
}

$actions = '<div class="button-pair">'
    . '<button type="button" class="btn btn-primary" onclick="window.print()">' . icon('printer', 17) . '<span>Print Report</span></button>'
    . '</div>';
?>
<?= page_title(
    'Hospital Reports (Appointments, Admissions & Revenue)',
    'Summary reports for West Metro Medical Center appointments, inpatient admissions, and revenue collections by date range.',
    $actions
) ?>

<div class="stats-grid four">
    <?= stat_card('Total Revenue', format_money($totalRev), count($allPayments) . ' official receipts collected', 'card', 'green') ?>
    <?= stat_card('Total Appointments', (string) count($allAppts), count(array_filter($allAppts, static fn ($a) => ($a[3] ?? '') === 'Completed')) . ' completed consultations', 'calendar', 'teal') ?>
    <?= stat_card('Current Admissions', (string) count($allAdms), 'Active inpatients in wards', 'bed', 'navy') ?>
    <?= stat_card('Total Discharges', (string) count($allDis), 'Completed inpatient stays', 'check', 'amber') ?>
</div>

<?= tabs(['Revenue', 'Appointments', 'Admissions'], $reportTab, 'rtab') ?>

<?php if ($reportTab === 'Revenue'): ?>
    <?php
    $filtered = array_values(array_filter($allPayments, static fn ($py) => in_range((string) ($py['paid_date'] ?? ($py['paid_at'] ?? '')), $from, $to) && row_matches([$py['receipt_no'] ?? '', $py['bill_no'] ?? '', $py['patient_name'] ?? '', $py['payment_method'] ?? ''], $q)));
    $paged    = paginate_rows($filtered, 10, 'p');
    $toolbar  = toolbar_open('sales-records', ['rtab' => 'Revenue'])
        . search_box('Search receipt, bill, patient, or payment method...', 'q', $q)
        . '<div class="toolbar-right">' . date_range_fields($from, $to) . filter_button() . '</div>'
        . toolbar_close();
    ?>
    <?= table_open(['Receipt No.', 'Bill No.', 'Date & Time', 'Patient', 'Payment Method', 'Cashier', 'Amount Paid', 'Action'], $toolbar) ?>
    <?php foreach ($paged['rows'] as $py): ?>
        <tr>
            <td><code><?= e($py['receipt_no'] ?? '') ?></code></td>
            <td><code><?= e($py['bill_no'] ?? '') ?></code></td>
            <td><?= e($py['paid_at'] ?? '') ?></td>
            <td><strong><?= e($py['patient_name'] ?? '') ?></strong></td>
            <td><?= badge($py['payment_method'] ?? 'Cash', status_tone($py['payment_method'] ?? 'Cash')) ?></td>
            <td><?= e($py['cashier_name'] ?? 'Cashier') ?></td>
            <td><strong><?= e(format_money($py['amount_paid'] ?? 0)) ?></strong></td>
            <td><a class="text-link" href="<?= e(url(['page' => 'sales-records', 'detail' => 'receipt', 'or' => $py['receipt_no'] ?? '', 'from_page' => 'sales-records'])) ?>">Receipt</a></td>
        </tr>
    <?php endforeach; ?>
    <?= table_close('revenue transaction', true, $paged['total'], $paged['page'], $paged['pages']) ?>

<?php elseif ($reportTab === 'Appointments'): ?>
    <?php
    $filtered = array_values(array_filter($allAppts, static fn ($a) => row_matches($a, $q)));
    $paged    = paginate_rows($filtered, 10, 'p');
    $toolbar  = toolbar_open('sales-records', ['rtab' => 'Appointments'])
        . search_box('Search patient, doctor, department, or status...', 'q', $q)
        . toolbar_close();
    ?>
    <?= table_open(['Date & Time', 'Patient', 'Doctor', 'Department', 'Visit Type', 'Status'], $toolbar) ?>
    <?php foreach ($paged['rows'] as $a): ?>
        <tr>
            <td><strong><?= e($a[7] ?? 'Today') ?> · <?= e($a[0]) ?></strong></td>
            <td><strong><?= e($a[1]) ?></strong></td>
            <td><?= e($a[5] ?? '—') ?></td>
            <td><?= e($a[6] ?? 'Outpatient') ?></td>
            <td><?= e($a[2]) ?></td>
            <td><?= badge($a[3], status_tone($a[3])) ?></td>
        </tr>
    <?php endforeach; ?>
    <?= table_close('appointment', true, $paged['total'], $paged['page'], $paged['pages']) ?>

<?php else: ?>
    <?php
    $combined = [];
    foreach ($allAdms as $adm) {
        $combined[] = ['patient' => $adm[0], 'bed' => $adm[1], 'ward' => $adm[2], 'date' => $adm[3], 'doctor' => $adm[4], 'diag' => $adm[5] ?? 'Inpatient care', 'status' => $adm[6] ?? 'Admitted'];
    }
    foreach ($allDis as $dis) {
        $combined[] = ['patient' => $dis[0], 'bed' => $dis[1], 'ward' => $dis[2], 'date' => $dis[3], 'doctor' => $dis[7] ?? 'Attending', 'diag' => $dis[9] ?? 'Resolved', 'status' => 'Discharged'];
    }
    $filtered = array_values(array_filter($combined, static fn ($c) => row_matches(array_values($c), $q)));
    $paged    = paginate_rows($filtered, 10, 'p');
    $toolbar  = toolbar_open('sales-records', ['rtab' => 'Admissions'])
        . search_box('Search patient, ward, room/bed, or diagnosis...', 'q', $q)
        . toolbar_close();
    ?>
    <?= table_open(['Patient', 'Room / Bed', 'Ward', 'Date', 'Attending Doctor', 'Diagnosis', 'Status'], $toolbar) ?>
    <?php foreach ($paged['rows'] as $c): ?>
        <tr>
            <td><strong><?= e($c['patient']) ?></strong></td>
            <td><?= badge($c['bed'], 'info') ?></td>
            <td><?= e($c['ward']) ?></td>
            <td><?= e($c['date']) ?></td>
            <td><?= e($c['doctor']) ?></td>
            <td><?= e($c['diag']) ?></td>
            <td><?= badge($c['status'], status_tone($c['status'])) ?></td>
        </tr>
    <?php endforeach; ?>
    <?= table_close('admission record', true, $paged['total'], $paged['page'], $paged['pages']) ?>
<?php endif; ?>
