<?php
$roles = $state['roles'];
?>
<?= page_title('Roles & permissions', 'Control what each team can view and manage.', button('Create role', ['icon' => 'plus', 'href' => url(['page' => 'roles', 'modal' => 'Create role'])])) ?>

<div class="role-grid">
    <?php foreach ($roles as $i => $r): ?>
        <div class="role-card">
            <span class="role-icon r<?= $i % 4 ?>"><?= icon($r[4]) ?></span>
            <h3><?= e($r[0]) ?></h3>
            <p><?= e($r[1]) ?></p>
            <div><span><?= e((string) $r[2]) ?> users</span><span><?= count($r[3]) ?> permissions</span></div>
            <div class="role-actions">
                <?= button('Manage role', ['variant' => 'secondary', 'icon' => 'edit', 'href' => url(['page' => 'roles', 'modal' => 'Manage role', 'role' => $r[0]])]) ?>
                <?php if ($r[2] === 0): ?>
                    <?= post_button('Delete', 'delete_role', ['key' => $r[0]], ['variant' => 'secondary']) ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
