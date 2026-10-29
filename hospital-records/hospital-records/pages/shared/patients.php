<?php
/** Patients list — used by Front desk (→ profile) and Doctor (→ clinical history). */
$q      = trim((string) ($_GET['q'] ?? ''));
$filter = (string) ($_GET['filter'] ?? 'All patients');
$allPatients = $state['patients'] ?? patients_data();

$patients = array_values(array_filter(
    $allPatients,
    static fn ($p) => row_matches(array_merge($p, [patient_age_label($p[1] ?? '', true), patient_age_label($p[1] ?? '', false)]), $q)
));
if ($filter === 'Recently added') {
    $patients = array_slice($patients, 0, 3);
}

$isDoctor   = $role === 'Doctor';
$detailName = $isDoctor ? 'history' : 'profile';
$linkLabel  = $isDoctor ? 'View history' : 'View profile';

$toolbar = toolbar_open('patients')
    . search_box('Search by ID, name, birthday, age, phone, or insurance...', 'q', $q)
    . '<div class="toolbar-right">'
    . select_field('', 'filter', ['All patients', 'Recently added'], $filter)
    . filter_button()
    . '</div>'
    . toolbar_close();

$action = $isDoctor ? '' : button('Register patient', [
    'icon' => 'plus',
    'href' => url(['page' => 'patients', 'modal' => 'Register patient']),
]);
?>
<?= page_title('Patients', 'Find patient records, birthday, age, and register new patients.', $action) ?>

<?= table_open(['Patient ID', 'Patient', 'Birthday', 'Age', 'Phone', 'Blood type', 'Insurance no.', 'Actions'], $toolbar) ?>
<?php foreach ($patients as $idx => $p):
    $link  = url(['page' => 'patients', 'detail' => $detailName, 'patient' => $p[0]]);
    $ptId  = $p[8] ?? ('P-' . str_pad((string) (1001 + $idx), 4, '0', STR_PAD_LEFT));
    $bdate = format_birthdate($p[1] ?? '');
    $age   = patient_age_label($bdate, true);
?>
    <tr>
        <td><code><?= e($ptId) ?></code></td>
        <td>
            <a class="person-cell" href="<?= e($link) ?>">
                <span class="mini-avatar"><?= e($p[4]) ?></span>
                <strong><?= e($p[0]) ?></strong>
            </a>
        </td>
        <td><?= e($bdate) ?></td>
        <td><strong><?= e($age) ?></strong></td>
        <td><?= e($p[2]) ?></td>
        <td><?= isset($p[6]) && $p[6] !== 'Not specified' ? badge($p[6], 'neutral') : '<span class="muted">Not specified</span>' ?></td>
        <td><code><?= e($p[3]) ?></code></td>
        <td><a class="text-link" href="<?= e($link) ?>"><?= e($linkLabel) ?> <?= icon('arrow', 14) ?></a></td>
    </tr>
<?php endforeach; ?>
<?= table_close(count($patients) . ' of ' . count($allPatients) . ' patients', false) ?>

<?php if (!$patients): ?>
    <?= filtered_empty('No patients match this search.') ?>
<?php endif; ?>
