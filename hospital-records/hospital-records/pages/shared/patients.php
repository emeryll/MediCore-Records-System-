<?php
/** Patients list — used by Front desk (→ profile) and Doctor (→ clinical history). */
$q = trim((string) ($_GET['q'] ?? ''));
$patients = array_values(array_filter(patients_data(), static fn ($p) => row_matches($p, $q)));

$isDoctor   = $role === 'Doctor';
$detailName = $isDoctor ? 'history' : 'profile';
$linkLabel  = $isDoctor ? 'View history' : 'View profile';

$toolbar = toolbar_open('patients')
    . search_box('Search by name, phone, or insurance...', 'q', $q)
    . '<div class="toolbar-right">'
    . select_field('', 'filter', ['All patients', 'Recently added'], (string) ($_GET['filter'] ?? 'All patients'))
    . filter_button()
    . '</div>'
    . toolbar_close();

$action = $isDoctor ? '' : button('Register patient', [
    'icon' => 'plus',
    'href' => url(['page' => 'patients', 'modal' => 'Register patient']),
]);
?>
<?= page_title('Patients', 'Find patient records and register new patients.', $action) ?>

<?= table_open(['Patient', 'Birthdate', 'Phone', 'Insurance no.', 'Actions'], $toolbar) ?>
<?php foreach ($patients as $p): $link = url(['page' => 'patients', 'detail' => $detailName]); ?>
    <tr>
        <td>
            <a class="person-cell" href="<?= e($link) ?>">
                <span class="mini-avatar"><?= e($p[4]) ?></span>
                <strong><?= e($p[0]) ?></strong>
            </a>
        </td>
        <td><?= e($p[1]) ?></td>
        <td><?= e($p[2]) ?></td>
        <td><code><?= e($p[3]) ?></code></td>
        <td><a class="text-link" href="<?= e($link) ?>"><?= e($linkLabel) ?> <?= icon('arrow', 14) ?></a></td>
    </tr>
<?php endforeach; ?>
<?= table_close('1–5 of 48') ?>

<?php if (!$patients): ?>
    <?= filtered_empty('No patients match this search.') ?>
<?php endif; ?>
