<?php
$q    = trim((string) ($_GET['q'] ?? ''));
$rows = array_values(array_filter($state['admissions'], static fn ($r) => row_matches($r, $q)));

$toolbar = toolbar_open('admissions') . search_box('Search current admissions...', 'q', $q) . toolbar_close();
?>
<?= page_title(
    'Admissions',
    'Manage current inpatient admissions and room assignments.',
    button('Admit patient', ['icon' => 'plus', 'href' => url(['page' => 'admissions', 'modal' => 'Admit patient'])])
) ?>

<?= table_open(['Patient', 'Room', 'Ward', 'Admitted', 'Attending', 'Actions'], $toolbar) ?>
<?php foreach ($rows as $r): ?>
    <tr>
        <td><strong><?= e($r[0]) ?></strong></td>
        <td><?= badge($r[1], 'info') ?></td>
        <td><?= e($r[2]) ?></td>
        <td><?= e($r[3]) ?></td>
        <td><?= e($r[4]) ?></td>
        <td><?= post_button('Discharge', 'discharge', ['key' => $r[0]], ['variant' => 'secondary']) ?></td>
    </tr>
<?php endforeach; ?>
<?= table_close('1–' . count($rows) . ' of ' . count($rows), false) ?>

<?php if (!$rows): ?>
    <?= filtered_empty('No current admissions match this search.') ?>
<?php endif; ?>
