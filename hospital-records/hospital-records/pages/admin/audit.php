<?php
declare(strict_types=1);

$q     = trim((string) ($_GET['q'] ?? ''));
$user_ = (string) ($_GET['user'] ?? 'All users');
[$from, $to] = range_params();

$adminUid = (string) ($_SESSION['user_id'] ?? '');
$linkedPts = function_exists('db_get_account_linked_patient_ids') ? db_get_account_linked_patient_ids($adminUid) : [];
$isFilteredToOwn = false;
$matchedQuery = '';

if ($q !== '' && !empty($linkedPts)) {
    foreach ($linkedPts as $lid) {
        if (stripos($q, $lid) !== false || (function_exists('db_is_patient_conflict_of_interest') && db_is_patient_conflict_of_interest($adminUid, $q))) {
            $isFilteredToOwn = true;
            $matchedQuery = $q;
            break;
        }
    }
}

if ($isFilteredToOwn && empty($_SESSION['flagged_audit_view_' . md5($q)])) {
    $_SESSION['flagged_audit_view_' . md5($q)] = true;
    $actingActor = current_user_name('Admin') . ' acting as Admin';
    try {
        $pdo = db();
        $ins = $pdo->prepare('INSERT INTO audit_logs (actor_name, action, table_name, record_id, logged_at, actor_role) VALUES (:an, :ac, :tb, :ri, :la, :ar)');
        $ins->execute([
            ':an' => $actingActor,
            ':ac' => 'Viewed audit log filtered to own/dependent patient record [FLAGGED FOR REVIEW]',
            ':tb' => 'audit_logs',
            ':ri' => $matchedQuery,
            ':la' => date('M d, Y · H:i'),
            ':ar' => 'Administrator',
        ]);
        if (isset($state['audit']) && is_array($state['audit'])) {
            array_unshift($state['audit'], [
                $actingActor,
                'Viewed audit log filtered to own/dependent patient record [FLAGGED FOR REVIEW]',
                'audit_logs',
                $matchedQuery,
                date('M d, Y · H:i'),
                'Administrator',
            ]);
        }
    } catch (Throwable) {}
}

$auditSource = $state['audit'] ?? audit_rows();
$rows = array_values(array_filter(
    $auditSource,
    static fn ($r) => (
        $user_ === 'All users'
        || strcasecmp((string) $r[5], $user_) === 0
        || ($user_ === 'Staff' && strcasecmp((string) $r[5], 'Front desk') === 0)
    ) && in_range($r[4], $from, $to)
        && row_matches($r, $q)
));
$paged = paginate_rows($rows, 12, 'p');

$toolbar = toolbar_open('audit-logs')
    . search_box('Search user, action, table, or record ID...', 'q', $q)
    . '<div class="toolbar-right">'
    . date_range_fields($from, $to)
    . select_field('', 'user', ['All users', 'Administrator', 'Doctor', 'Staff', 'Cashier', 'Patient'], $user_)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title('Audit Log', 'Read-only log of important actions (logins, record edits, discharges, payments, user changes).') ?>

<?php if ($isFilteredToOwn): ?>
    <div class="panel" style="padding:14px 18px; background:#fff1f2; border:1px solid #fecdd3; border-radius:8px; margin-bottom:16px;">
        <strong style="color:#b91c1c; font-size:13px;">Compliance Notice: This query filters for your personal or dependent patient records.</strong>
        <p style="margin:4px 0 0; font-size:12px; color:#9f1239;">Under hospital conflict-of-interest policy, administrative queries concerning your own patient records are logged and flagged for compliance review.</p>
    </div>
<?php endif; ?>

<?= table_open(['Actor & Role', 'Action', 'Module / Table', 'Record ID', 'Timestamp'], $toolbar) ?>
<?php foreach ($paged['rows'] as $r):
    $isFlagged = str_contains((string) $r[1], '[FLAGGED FOR REVIEW]') || (!empty($linkedPts) && in_array(strtoupper(trim((string) $r[3])), $linkedPts, true));
?>
    <tr style="<?= $isFlagged ? 'background:#fff7ed;' : '' ?>">
        <td><div class="person-cell"><?= mini_avatar($r[0]) ?><div><strong><?= e($r[0]) ?></strong><span><?= e($r[5]) ?></span></div></div></td>
        <td>
            <?= badge($r[1], status_tone($r[1])) ?>
            <?php if ($isFlagged): ?>
                <span style="margin-left:6px;"><?= badge('Flagged for Review', 'danger') ?></span>
            <?php endif; ?>
        </td>
        <td><code><?= e($r[2]) ?></code></td>
        <td><?= e($r[3]) ?></td>
        <td><?= e($r[4]) ?></td>
    </tr>
<?php endforeach; ?>
<?= table_close('audit entry', true, $paged['total'], $paged['page'], $paged['pages']) ?>

<?php if (!$paged['rows']): ?>
    <?= filtered_empty('No activity matches these filters.') ?>
<?php endif; ?>
