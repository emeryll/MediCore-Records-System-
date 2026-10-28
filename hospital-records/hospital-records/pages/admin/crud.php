<?php
/** Shared page for Departments / Rooms / Medications ($page is the slug). */
$type     = $page;
$typeName = ucfirst($type);                       // "Departments"
$singular = strtolower(rtrim($typeName, 's'));    // "department"
$q        = trim((string) ($_GET['q'] ?? ''));

$records = array_values(array_filter($state['crud'][$type] ?? [], static fn ($r) => row_matches($r, $q)));

$headings = match ($type) {
    'departments' => ['Department', 'Head', 'Staff', 'Location', 'Actions'],
    'rooms'       => ['Room', 'Ward', 'Status', 'Patient', 'Actions'],
    default       => ['Medication', 'Category', 'Strength', 'Stock', 'Actions'],
};

$toolbar = toolbar_open($type) . search_box('Search ' . $type . '...', 'q', $q) . toolbar_close();
?>
<?= page_title(
    $typeName,
    'Manage hospital ' . $type . ' and keep records up to date.',
    button('Add ' . $singular, ['icon' => 'plus', 'href' => url(['page' => $type, 'modal' => 'Add ' . $singular])])
) ?>

<?= table_open($headings, $toolbar) ?>
<?php foreach ($records as $r): ?>
    <tr>
        <?php foreach ($r as $i => $cell): ?>
            <td>
                <?php if ($i === 0): ?>
                    <strong><?= e($cell) ?></strong>
                <?php elseif ($type === 'rooms' && $i === 2): ?>
                    <?= badge($cell, $cell === 'Available' ? 'success' : ($cell === 'Cleaning' ? 'warning' : 'info')) ?>
                <?php else: ?>
                    <?= e($cell) ?>
                <?php endif; ?>
            </td>
        <?php endforeach; ?>
        <td>
            <div class="row-actions">
                <a href="<?= e(url(['page' => $type, 'modal' => 'Edit ' . $singular])) ?>"><?= icon('edit', 15) ?> Edit</a>
                <?= post_button('', 'delete_crud', ['type' => $type, 'key' => $r[0]], [
                    'plain' => true, 'class' => 'danger-link', 'html' => 'Delete',
                ]) ?>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close('1–4 of 12') ?>

<?php if (!$records): ?>
    <?= filtered_empty('No ' . $type . ' match this search.') ?>
<?php endif; ?>
