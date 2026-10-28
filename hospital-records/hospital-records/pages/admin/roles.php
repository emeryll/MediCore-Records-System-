<?php
$roles = [
    ['Administrator', 'Full system access', '4 users', '12 permissions', 'shield'],
    ['Doctor', 'Clinical records and appointments', '42 users', '8 permissions', 'patient'],
    ['Nurse', 'Patient care and admissions', '68 users', '6 permissions', 'clipboard'],
    ['Front desk', 'Registration and scheduling', '24 users', '5 permissions', 'users'],
];
?>
<?= page_title('Roles & permissions', 'Control what each team can view and manage.', notify_button('Create role', 'Role creation is ready', ['icon' => 'plus'])) ?>

<div class="role-grid">
    <?php foreach ($roles as $i => $r): ?>
        <div class="role-card">
            <span class="role-icon r<?= $i ?>"><?= icon($r[4]) ?></span>
            <h3><?= e($r[0]) ?></h3>
            <p><?= e($r[1]) ?></p>
            <div><span><?= e($r[2]) ?></span><span><?= e($r[3]) ?></span></div>
            <?= notify_button('Manage role', 'Role permissions opened', ['variant' => 'secondary', 'icon' => 'edit']) ?>
        </div>
    <?php endforeach; ?>
</div>
