<?php
$view   = ($_GET['view'] ?? 'Current') === 'Discharged' ? 'Discharged' : 'Current';
[$from, $to] = range_params();
$q      = trim((string) ($_GET['q'] ?? ''));

$toolbar = toolbar_open('admissions', ['view' => $view])
    . search_box($view === 'Current' ? 'Search current admissions...' : 'Search discharged patients...', 'q', $q)
    . ($view === 'Discharged' ? '<div class="toolbar-right">' . date_range_fields($from, $to) . filter_button() . '</div>' : '')
    . toolbar_close();
?>
<?= page_title(
    'Admissions',
    'Manage current inpatient admissions and review discharged patients.',
    button('Admit patient', ['icon' => 'plus', 'href' => url(['page' => 'admissions', 'modal' => 'Admit patient'])])
) ?>

<?= tabs(['Current', 'Discharged'], $view, 'view') ?>

<?php if ($view === 'Current'): ?>
    <?php $rows = array_values(array_filter($state['admissions'], static fn ($r) => row_matches($r, $q))); ?>
    <?= table_open(['Patient', 'Room', 'Ward', 'Admitted', 'Attending', 'Actions'], $toolbar) ?>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><strong><?= e($r[0]) ?></strong></td>
            <td><?= badge($r[1], 'info') ?></td>
            <td><?= e($r[2]) ?></td>
            <td><?= e($r[3]) ?></td>
            <td><?= e($r[4]) ?></td>
            <td><?= button('Discharge', ['variant' => 'secondary', 'href' => url(['page' => 'admissions', 'modal' => 'Discharge patient', 'patient' => $r[0]])]) ?></td>
        </tr>
    <?php endforeach; ?>
    <?= table_close(count($rows) . ' of ' . count($state['admissions']), false) ?>
    <?php if (!$rows): ?>
        <?= filtered_empty('No current admissions match this search.') ?>
    <?php endif; ?>
<?php else: ?>
    <?php
    $rows = array_values(array_filter(
        array_reverse($state['discharges']),
        static fn ($d) => in_range((int) $d[5], $from, $to) && row_matches([$d[0], $d[1], $d[2], $d[3]], $q)
    ));
    ?>
    <?= table_open(['Patient', 'Room', 'Ward', 'Discharged', 'Proof of discharge'], $toolbar) ?>
    <?php foreach ($rows as $d): ?>
        <tr>
            <td><strong><?= e($d[0]) ?></strong></td>
            <td><?= badge($d[1], 'info') ?></td>
            <td><?= e($d[2]) ?></td>
            <td><?= e($d[3]) ?></td>
            <td><?= $d[4] ? proof_thumb($d[4], 'Proof of discharge for ' . $d[0]) : '—' ?></td>
        </tr>
    <?php endforeach; ?>
    <?= table_close(count($rows) . ' discharged', false) ?>
    <?php if (!$rows): ?>
        <?= filtered_empty('No discharged patients in this date range.') ?>
    <?php endif; ?>
<?php endif; ?>
