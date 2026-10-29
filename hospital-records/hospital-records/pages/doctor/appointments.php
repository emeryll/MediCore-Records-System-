<?php
// Categories: All · Upcoming (Confirmed/Waiting) · In Progress · Completed
$cat  = in_array($_GET['cat'] ?? '', ['Upcoming', 'In progress', 'Completed'], true)
      ? $_GET['cat'] : 'All';
$q    = trim((string) ($_GET['q'] ?? ''));
[$from, $to] = range_params();

$catMap = [
    'All'         => fn ($s) => true,
    'Upcoming'    => fn ($s) => in_array($s, ['Confirmed', 'Waiting'], true),
    'In progress' => fn ($s) => $s === 'In progress',
    'Completed'   => fn ($s) => $s === 'Completed',
];

$allAppts = $state['appointments'];
$rows = array_values(array_filter(
    $allAppts,
    static fn ($r) => $catMap[$cat]($r[3]) && row_matches([$r[1], $r[2], $r[4]], $q)
));

// Counts per category for badge labels
$counts = [
    'All'         => count($allAppts),
    'Upcoming'    => count(array_filter($allAppts, fn ($r) => in_array($r[3], ['Confirmed', 'Waiting'], true))),
    'In progress' => count(array_filter($allAppts, fn ($r) => $r[3] === 'In progress')),
    'Completed'   => count(array_filter($allAppts, fn ($r) => $r[3] === 'Completed')),
];

$toolbar = toolbar_open('my-appointments', ['cat' => $cat])
    . search_box('Search patient or complaint...', 'q', $q)
    . '<div class="toolbar-right">'
    . date_range_fields($from, $to)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title('My appointments', 'Review today\'s schedule and begin patient consultations.') ?>

<div class="appt-cats">
    <?php foreach (['All', 'Upcoming', 'In progress', 'Completed'] as $c): ?>
        <a class="appt-cat <?= $cat === $c ? 'active' : '' ?>"
           href="<?= e(url(['page' => 'my-appointments', 'cat' => $c === 'All' ? null : $c])) ?>">
            <?= e($c) ?>
            <span class="appt-cat-count"><?= $counts[$c] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<?= table_open(['Time', 'Patient', 'Type', 'Complaint', 'Status', 'Actions'], $toolbar) ?>
<?php foreach ($rows as $r): ?>
    <tr>
        <td><strong><?= e($r[0]) ?></strong></td>
        <td>
            <div class="person-cell"><?= mini_avatar($r[1]) ?><strong><?= e($r[1]) ?></strong></div>
        </td>
        <td><?= e($r[2]) ?></td>
        <td><span class="complaint-text"><?= e($r[4]) ?></span></td>
        <td><?= badge($r[3], match($r[3]) {
            'Completed'   => 'success',
            'In progress' => 'info',
            'Waiting'     => 'warning',
            default       => 'neutral',
        }) ?></td>
        <td>
            <?php if ($r[3] === 'Completed'): ?>
                <a class="text-link" href="<?= e(url(['page' => 'my-appointments', 'detail' => 'history', 'patient' => $r[1]])) ?>">View record <?= icon('arrow', 14) ?></a>
            <?php elseif ($r[3] === 'In progress'): ?>
                <?= button('Continue', ['href' => url(['page' => 'my-appointments', 'detail' => 'new-record', 'patient' => $r[1]])]) ?>
            <?php else: ?>
                <?= post_button('Start consultation', 'start_consultation', ['patient' => $r[1]]) ?>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close(count($rows) . ' of ' . $counts[$cat] . ' appointment' . ($counts[$cat] !== 1 ? 's' : ''), false) ?>

<?php if (!$rows): ?>
    <?= filtered_empty('No ' . strtolower($cat === 'All' ? '' : $cat . ' ') . 'appointments match this search.') ?>
<?php endif; ?>
