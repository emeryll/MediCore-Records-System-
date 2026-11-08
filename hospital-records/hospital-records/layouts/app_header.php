<?php
/** @var string $role @var array $nav @var string $page @var string $title */
if (!empty($_GET['clear_sim'])) {
    unset($_SESSION['sim_messages']);
}
if (!empty($_SESSION['sim_messages']) && is_array($_SESSION['sim_messages'])) {
    $_SESSION['sim_messages'] = array_values(array_filter($_SESSION['sim_messages'], static fn ($m) => ($m['channel'] ?? '') === 'SMS'));
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
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>
<div class="app-shell">
    <aside class="sidebar" id="app-sidebar">
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
            <button type="button" class="icon-btn nav-toggle" id="nav-toggle" aria-label="Open menu" aria-controls="app-sidebar" aria-expanded="false"><?= icon('menu', 22) ?></button>
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
                <?php
                if ($role === 'Patient' && function_exists('db_get_account_linked_patients')) {
                    $linkedProfiles = db_get_account_linked_patients((string) ($_SESSION['user_id'] ?? ''));
                    $activePid      = (string) ($_SESSION['active_patient_id'] ?? ($_SESSION['user_id'] ?? ''));
                    if (!empty($linkedProfiles)) {
                ?>
                    <div class="patient-profile-dropdown" style="display:inline-flex; align-items:center; gap:6px; background:#f1f5f9; padding:3px 8px; border-radius:20px; border:1px solid var(--gray-200); margin-right:8px;">
                        <label for="header-profile-select" style="font-size:10.5px; font-weight:800; color:var(--gray-500); text-transform:uppercase; letter-spacing:0.5px; padding-left:4px; margin:0; cursor:pointer;">Profile:</label>
                        <div style="position:relative; display:inline-flex; align-items:center;">
                            <select id="header-profile-select"
                                    aria-label="Switch active patient profile"
                                    onchange="if (this.value) window.location.href = this.value;"
                                    style="height:26px; padding:0 24px 0 10px; font-size:11.5px; font-weight:700; color:var(--teal-800); background:#fff; border:1px solid var(--gray-300); border-radius:14px; outline:none; cursor:pointer; appearance:none; -webkit-appearance:none; -moz-appearance:none; max-width:240px;">
                                <?php foreach ($linkedProfiles as $lp):
                                    $isCur = strcasecmp((string) $lp['patient_id'], $activePid) === 0;
                                    $pTag = $lp['is_primary'] ? 'Me' : ($lp['relationship_label'] ?: 'Dependent');
                                    $lpMrn = (string) ($lp['mrn'] ?: $lp['patient_id']);
                                    if (preg_match('/^(?:MRN|P)-?0*(\d+)$/i', $lpMrn, $m)) {
                                        $lpMrn = 'MRN-' . str_pad($m[1], 4, '0', STR_PAD_LEFT);
                                    }
                                    $shortName = explode(' ', (string) $lp['patient_name'])[0];
                                ?>
                                    <option value="<?= e(url_with(['switch_patient' => $lp['patient_id']])) ?>" <?= $isCur ? 'selected' : '' ?>>
                                        <?= e($shortName) ?> (<?= e($lpMrn) ?>) · <?= e($pTag) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span style="position:absolute; right:7px; pointer-events:none; color:var(--teal-700); display:flex; align-items:center;">
                                <?= icon('down', 12) ?>
                            </span>
                        </div>
                    </div>
                <?php
                    }
                }
                $headerDisplayName = ($role === 'Patient' && !empty($_SESSION['active_patient_name'])) ? (string) $_SESSION['active_patient_name'] : $name;
                $activeMrn = (string) ($_SESSION['active_patient_mrn'] ?? '');
                if (preg_match('/^(?:MRN|P)-?0*(\d+)$/i', $activeMrn, $m)) {
                    $activeMrn = 'MRN-' . str_pad($m[1], 4, '0', STR_PAD_LEFT);
                }
                $headerDisplaySub  = ($role === 'Patient' && $activeMrn !== '') ? (str_starts_with($activeMrn, 'MRN-') ? $activeMrn : 'MRN ' . $activeMrn) : current_user_title($role);
                ?>
                <a href="<?= e(url(['page' => 'profile'])) ?>" class="btn btn-secondary btn-sm" style="display:flex; align-items:center; gap:6px; height:34px; padding:0 12px; font-size:12.5px; font-weight:600; text-decoration:none; margin-right:4px;">
                    <?= icon('user', 14) ?>
                    <span>Profile</span>
                </a>
                <div class="avatar"><?= e(initials($headerDisplayName, 2)) ?></div>
                <div class="user-copy">
                    <strong><?= e($headerDisplayName) ?></strong>
                    <span><?= e($headerDisplaySub) ?></span>
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
