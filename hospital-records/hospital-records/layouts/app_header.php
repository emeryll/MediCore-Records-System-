<?php
/** @var string $role @var array $nav @var string $page @var string $title */
if (!empty($_GET['clear_sim'])) {
    unset($_SESSION['sim_messages']);
}
$name   = current_user_name($role);
$notes  = $state['notifications'][$role] ?? [];
$unread = count(array_filter($notes, static fn ($n) => is_array($n) && empty($n[3])));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(APP_NAME) ?> (Zamboanga City)</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <script src="assets/js/ph_address.js?v=<?= filemtime(__DIR__ . '/../assets/js/ph_address.js') ?>"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function colorizeAsterisks(context) {
            (context || document).querySelectorAll('.field-label, label').forEach(function(el) {
                if (el.querySelector('.req-star')) return;
                el.childNodes.forEach(function(node) {
                    if (node.nodeType === 3 && node.nodeValue && node.nodeValue.indexOf('*') !== -1) {
                        var wrap = document.createElement('span');
                        wrap.innerHTML = node.nodeValue.replace(/\*/g, '<span class="req-star" style="color:#dc2626; font-weight:700;">*</span>');
                        el.replaceChild(wrap, node);
                    }
                });
            });
        }
        colorizeAsterisks();
        window.addEventListener('load', function() { colorizeAsterisks(); });
        var obs = new MutationObserver(function() { colorizeAsterisks(); });
        obs.observe(document.body, { childList: true, subtree: true });
    });
    </script>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <?= logo(true) ?>
        <div class="role-chip"><span><?= e($role === 'Staff' ? 'Front Desk' : $role) ?></span> Portal</div>
        <nav>
            <?php foreach ($nav as [$label, $iconName, $slug]): $active = $slug === ($navActive ?? $page); ?>
                <a class="nav-item <?= $active ? 'active' : '' ?>" href="<?= e(url(['page' => $slug])) ?>">
                    <?= icon($iconName) ?>
                    <span><?= e($label) ?></span>
                    <?= $active ? icon('chevron', 15) : '' ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="support-card">
            <span class="support-icon"><?= icon('shield') ?></span>
            <strong>West Metro Medical Center</strong>
            <p>Veterans Ave. Ext., Zamboanga City · Data Privacy Act Compliant.</p>
        </div>
        <p class="sidebar-footer">WMMC Records System · Zamboanga City</p>
    </aside>

    <div class="main-shell">
        <header class="topbar">
            <div class="mobile-brand"><?= logo() ?></div>
            <?php if (in_array($role, ['Doctor', 'Staff'], true)): ?>
            <form class="top-search" method="get" action="index.php">
                <input type="hidden" name="page" value="patients">
                <?= search_box('Search patient by name, birthdate, phone, or MRN...') ?>
            </form>
            <?php else: ?>
            <div style="font-size:13px; color:var(--gray-500); font-weight:500;">
                <?= e(APP_NAME) ?> · <span style="color:var(--teal-700);">Zamboanga City</span>
            </div>
            <?php endif; ?>
            <div class="top-actions">
                <details class="notif">
                    <summary class="icon-btn" aria-label="Notifications">
                        <?= icon('bell') ?><?= $unread ? '<span class="notification"></span>' : '' ?>
                    </summary>
                    <div class="notif-panel">
                        <div class="notif-head">
                            <strong>Notifications</strong>
                            <span><?= $unread ? e((string) $unread) . ' unread' : 'All caught up' ?></span>
                        </div>
                        <?php foreach ($notes as $n): if (!is_array($n) || !isset($n[0])) continue; ?>
                            <div class="notif-item<?= !empty($n[3]) ? '' : ' unread' ?>">
                                <strong><?= e((string) $n[0]) ?></strong>
                                <p><?= e((string) ($n[1] ?? '')) ?></p>
                                <small><?= e((string) ($n[2] ?? 'Just now')) ?></small>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$notes): ?><p class="notif-empty">No notifications.</p><?php endif; ?>
                        <?php if ($unread): ?>
                            <form method="post" action="index.php" class="notif-foot">
                                <input type="hidden" name="action" value="read_notifications">
                                <input type="hidden" name="return" value="<?= e(url($_GET)) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-link">Mark all as read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </details>
                <div class="top-divider"></div>
                <a href="<?= e(url(['page' => 'profile'])) ?>" class="btn btn-secondary btn-sm" style="display:flex; align-items:center; gap:6px; height:34px; padding:0 12px; font-size:12.5px; font-weight:600; text-decoration:none; margin-right:4px;">
                    <?= icon('user', 14) ?>
                    <span>Profile</span>
                </a>
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
            <?= render_sim_banners() ?>
