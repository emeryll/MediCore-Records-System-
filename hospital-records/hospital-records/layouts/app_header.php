<?php
/** @var string $role @var array $nav @var string $page @var string $title */
$name = current_user_name($role);
$notificationFlash = null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(APP_NAME) ?> Hospital Records System</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <?= logo(true) ?>
        <div class="role-chip"><span><?= e($role === 'Staff' ? 'Front desk' : $role) ?></span> workspace</div>
        <nav>
            <?php foreach ($nav as [$label, $iconName, $slug]): $active = $slug === $page; ?>
                <a class="nav-item <?= $active ? 'active' : '' ?>" href="<?= e(url(['page' => $slug])) ?>">
                    <?= icon($iconName) ?>
                    <span><?= e($label) ?></span>
                    <?= $active ? icon('chevron', 15) : '' ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="support-card">
            <span class="support-icon"><?= icon('shield') ?></span>
            <strong>Secure workspace</strong>
            <p>Patient data is encrypted and monitored.</p>
        </div>
        <p class="sidebar-footer">MediCore Health · v2.4.0</p>
    </aside>

    <div class="main-shell">
        <header class="topbar">
            <div class="mobile-brand"><?= logo() ?></div>
            <form class="top-search" method="get" action="index.php">
                <input type="hidden" name="page" value="<?= e($page === 'dashboard' ? 'patients' : $page) ?>">
                <?= search_box('Search patient, record, or action...') ?>
            </form>
            <div class="top-actions">
                <form method="post" action="index.php" class="inline-form">
                    <input type="hidden" name="action" value="notify">
                    <input type="hidden" name="message" value="You have 3 new system notifications">
                    <input type="hidden" name="return" value="<?= e(url($_GET)) ?>">
                    <?= csrf_field() ?>
                    <button class="icon-btn" aria-label="Notifications" type="submit">
                        <?= icon('bell') ?><span class="notification"></span>
                    </button>
                </form>
                <div class="top-divider"></div>
                <div class="avatar"><?= e(initials($name, 2)) ?></div>
                <div class="user-copy">
                    <strong><?= e($name) ?></strong>
                    <span><?= e(current_user_title($role)) ?></span>
                </div>
                <form method="post" action="index.php" class="inline-form">
                    <input type="hidden" name="action" value="logout">
                    <?= csrf_field() ?>
                    <button class="icon-btn" aria-label="Log out" type="submit"><?= icon('logout') ?></button>
                </form>
            </div>
        </header>
        <main class="page-content">
