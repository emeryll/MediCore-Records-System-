<?php
$q       = trim((string) ($_GET['q'] ?? ''));
$status_ = (string) ($_GET['status'] ?? 'All statuses');

$rows = array_values(array_filter(
    $state['crud']['rooms'],
    static fn ($r) => ($status_ === 'All statuses' || $r[2] === $status_) && row_matches($r, $q)
));

$all   = $state['crud']['rooms'];
$count = static fn (string $s) => count(array_filter($all, static fn ($r) => $r[2] === $s));

$toolbar = toolbar_open('staff-rooms')
    . search_box('Search rooms or patients...', 'q', $q)
    . '<div class="toolbar-right">'
    . select_field('', 'status', ['All statuses', 'Available', 'Occupied'], $status_)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title(
    'Rooms',
    'See room availability and current patient assignments.',
    button('Add room', ['icon' => 'plus', 'href' => url(['page' => 'staff-rooms', 'modal' => 'Add room'])])
) ?>

<div class="stats-grid two">
    <?= stat_card('Available', (string) $count('Available'), 'Ready for admission', 'bed', 'green') ?>
    <?= stat_card('Occupied', (string) $count('Occupied'), 'Patients in room', 'patient') ?>
</div>

<?= table_open(['Room', 'Ward', 'Status', 'Patient', 'Actions'], $toolbar) ?>
<?php foreach ($rows as $r): ?>
    <tr>
        <td><strong><?= e($r[0]) ?></strong></td>
        <td><?= e($r[1]) ?></td>
        <td><?= badge($r[2], status_tone($r[2])) ?></td>
        <td><?= e($r[3]) ?></td>
        <td>
            <div class="row-actions">
                <a href="<?= e(url(['page' => 'staff-rooms', 'modal' => 'Edit room', 'room' => $r[0]])) ?>"><?= icon('edit', 15) ?> Edit</a>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close(count($rows) . ' of ' . count($all) . ' rooms', false) ?>

<?php if (!$rows): ?>
    <?= filtered_empty('No rooms match these filters.') ?>
<?php endif; ?>
