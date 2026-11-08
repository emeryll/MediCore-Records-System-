<?php
declare(strict_types=1);

$q      = trim((string) ($_GET['q'] ?? ''));
$cat    = (string) ($_GET['cat'] ?? 'All categories');
$editId = trim((string) ($_GET['edit'] ?? ''));
$showAdd = isset($_GET['add']);

$allItems = $state['price_list'] ?? [];
$cats     = ['All categories', 'Consultation', 'Lab & Diagnostics', 'Room Rate'];

$filtered = array_values(array_filter($allItems, static function (array $it) use ($q, $cat): bool {
    if ($cat !== 'All categories' && strcasecmp($it['category'] ?? '', $cat) !== 0) {
        return false;
    }
    return row_matches([$it['id'] ?? '', $it['category'] ?? '', $it['name'] ?? '', (string) ($it['price'] ?? '')], $q);
}));
$paged = paginate_rows($filtered, 12, 'p');

$editItem = null;
if ($editId !== '') {
    foreach ($allItems as $it) {
        if (($it['id'] ?? '') === $editId) {
            $editItem = $it;
            break;
        }
    }
}

$toolbar = toolbar_open('price-list')
    . search_box('Search service, lab test, or room rate...', 'q', $q)
    . '<div class="toolbar-right">'
    . select_field('', 'cat', $cats, $cat)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title(
    'Hospital Price List',
    'Manage standard Philippine Peso (₱) prices for consultation fees, lab tests, and room rates so billing stays consistent across Doctor, Staff, and Cashier portals.',
    button('Add price item', ['icon' => 'plus', 'href' => url(['page' => 'price-list', 'add' => 1])])
) ?>

<?php if ($showAdd || $editItem): ?>
    <div class="panel" style="padding:20px 22px; margin-bottom:20px; border-color:var(--teal-600);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <div>
                <h2 style="font-size:15px;"><?= $editItem ? 'Edit Price Item ' . e($editItem['id']) : 'Add New Price List Item' ?></h2>
                <p style="font-size:11.5px; color:var(--gray-500);">Updates here immediately apply to new doctor consultations and cashier billing</p>
            </div>
            <a class="icon-btn" href="<?= e(url(['page' => 'price-list'])) ?>"><?= icon('close') ?></a>
        </div>
        <form method="post" action="index.php" style="display:grid; gap:14px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_price_item">
            <input type="hidden" name="id" value="<?= e($editItem['id'] ?? '') ?>">
            <div class="form-grid three">
                <?= select_field('Category *', 'category', ['Consultation', 'Lab & Diagnostics', 'Room Rate'], $editItem['category'] ?? 'Consultation') ?>
                <?= field('Item / Service Name *', 'name', 'e.g. Complete blood count (CBC)', 'text', $editItem['name'] ?? '', ['required' => true]) ?>
                <?= field('Standard Price (₱) *', 'price', '500.00', 'number', (string) ($editItem['price'] ?? '500'), ['required' => true]) ?>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <?= button('Cancel', ['variant' => 'secondary', 'href' => url(['page' => 'price-list'])]) ?>
                <?= button('Save Price Item', ['type' => 'submit', 'icon' => 'check']) ?>
            </div>
        </form>
    </div>
<?php endif; ?>

<?= table_open(['Code', 'Category', 'Item / Service Name', 'Standard Price (₱)', 'Status', 'Actions'], $toolbar) ?>
<?php foreach ($paged['rows'] as $it):
    $itId  = (string) ($it['id'] ?? ($it['code'] ?? ''));
    $isAct = !empty($it['active']) || ($it['status'] ?? '') === 'Active';
?>
    <tr>
        <td><code><?= e($itId) ?></code></td>
        <td><?= badge($it['category'] ?? 'General', 'info') ?></td>
        <td><strong><?= e($it['name'] ?? '—') ?></strong></td>
        <td><strong style="color:var(--navy-900);"><?= e(format_money((float) ($it['price'] ?? 0))) ?></strong></td>
        <td><?= badge($isAct ? 'Active' : 'Deactivated', $isAct ? 'success' : 'neutral') ?></td>
        <td>
            <div class="row-actions" style="gap:8px;">
                <a href="<?= e(url(['page' => 'price-list', 'edit' => $itId])) ?>"><?= icon('edit', 15) ?> Edit</a>
                <?= post_button('', 'toggle_price_item', ['id' => $itId], [
                    'plain' => true,
                    'class' => $isAct ? 'danger-link' : 'text-link',
                    'html'  => $isAct ? 'Deactivate' : 'Activate',
                ]) ?>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
<?= table_close('price item', true, $paged['total'], $paged['page'], $paged['pages']) ?>
