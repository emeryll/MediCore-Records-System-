<?php
$q    = trim((string) ($_GET['q'] ?? ''));
$rows = array_values(array_filter(doctor_visits(), static fn ($r) => row_matches([$r[1]], $q)));

$toolbar = toolbar_open('my-appointments')
    . field('', 'date', '', 'date', (string) ($_GET['date'] ?? ''))
    . search_box('Search patient...', 'q', $q)
    . filter_button()
    . toolbar_close();
?>
<?= page_title('My appointments', 'Review today’s schedule and begin patient consultations.') ?>

<?= table_open(['Time', 'Patient', 'Visit type', 'Status', 'Actions'], $toolbar) ?>
<?php foreach ($rows as $r): ?>
    <tr>
        <td><strong><?= e($r[0]) ?></strong></td>
        <td>
            <div class="person-cell"><?= mini_avatar($r[1]) ?><strong><?= e($r[1]) ?></strong></div>
        </td>
        <td><?= e($r[2]) ?></td>
        <td><?= badge($r[3], $r[3] === 'Completed' ? 'success' : ($r[3] === 'Waiting' ? 'warning' : 'info')) ?></td>
        <td>
            <?php if ($r[3] === 'Completed'): ?>
                <a class="text-link" href="<?= e(url(['page' => 'my-appointments', 'detail' => 'history'])) ?>">View record</a>
            <?php else: ?>
                <?= button($r[3] === 'In progress' ? 'Continue' : 'Start consultation', [
                    'href' => url(['page' => 'my-appointments', 'detail' => 'new-record']),
                ]) ?>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close('1–5 of 48') ?>

<?php if (!$rows): ?>
    <?= filtered_empty('No appointments match this search.') ?>
<?php endif; ?>
