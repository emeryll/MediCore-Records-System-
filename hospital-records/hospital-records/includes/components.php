<?php
declare(strict_types=1);

/* ---------------------------------------------------------------
 * Small UI building blocks. Every function RETURNS an HTML string.
 * ------------------------------------------------------------- */

function badge(string $text, string $tone = 'neutral'): string
{
    return '<span class="badge badge-' . e($tone) . '"><span class="badge-dot"></span>' . e($text) . '</span>';
}

/**
 * Button / link-button.
 * opts: variant (primary|secondary|ghost|danger), icon, href, type, class, attrs
 */
function button(string $label, array $opts = []): string
{
    $variant = $opts['variant'] ?? 'primary';
    $icon    = isset($opts['icon']) ? icon($opts['icon'], 17) : '';
    $inner   = $icon . '<span>' . e($label) . '</span>';
    $class   = 'btn btn-' . e($variant) . (!empty($opts['class']) ? ' ' . e($opts['class']) : '');
    $attrs   = !empty($opts['attrs']) ? ' ' . $opts['attrs'] : '';

    if (isset($opts['href'])) {
        return '<a class="' . $class . '" href="' . e($opts['href']) . '"' . $attrs . '>' . $inner . '</a>';
    }
    return '<button class="' . $class . '" type="' . e($opts['type'] ?? 'button') . '"' . $attrs . '>' . $inner . '</button>';
}

/** A POST form wrapping one button (used for actions that change state). */
function post_button(string $label, string $action, array $fields = [], array $opts = []): string
{
    $hidden = '<input type="hidden" name="action" value="' . e($action) . '">'
        . '<input type="hidden" name="return" value="' . e($opts['return'] ?? url($_GET)) . '">'
        . csrf_field();
    foreach ($fields as $k => $v) {
        $hidden .= '<input type="hidden" name="' . e((string) $k) . '" value="' . e((string) $v) . '">';
    }

    $class = $opts['class'] ?? '';
    if (!empty($opts['plain'])) {
        $btn = '<button type="submit" class="' . e($class) . '">' . ($opts['html'] ?? e($label)) . '</button>';
    } else {
        $btn = '<button type="submit" class="btn btn-' . e($opts['variant'] ?? 'primary') . ' ' . e($class) . '">'
            . (isset($opts['icon']) ? icon($opts['icon'], 17) : '') . '<span>' . e($label) . '</span></button>';
    }
    $confirmMsg = $opts['confirm_msg'] ?? 'Are you sure about this action?';
    $confirmAttr = (($opts['confirm'] ?? true) !== false && $action !== 'read_notifications')
        ? ' onsubmit="return confirm(' . htmlspecialchars(json_encode($confirmMsg), ENT_QUOTES, 'UTF-8') . ');"'
        : '';
    return '<form method="post" action="index.php" class="inline-form"' . $confirmAttr . '>' . $hidden . $btn . '</form>';
}

/** Button that only shows a confirmation toast. */
function notify_button(string $label, string $message, array $opts = []): string
{
    return post_button($label, 'notify', ['message' => $message], $opts);
}

function format_label(string $label): string
{
    if (str_contains($label, '*')) {
        $escaped = e($label);
        return preg_replace('/(\*+)/', '<span class="req-star" style="color:#dc2626; font-weight:700;">$1</span>', $escaped);
    }
    return e($label);
}

function field(string $label, string $name, string $placeholder = '', string $type = 'text', string $value = '', array $opts = []): string
{
    $req = !empty($opts['required']) ? ' required' : '';
    $ro  = !empty($opts['readonly']) ? ' readonly' : '';
    $id  = !empty($opts['id']) ? ' id="' . e($opts['id']) . '"' : '';
    $hint = !empty($opts['hint']) ? '<small class="field-hint">' . e($opts['hint']) . '</small>' : '';
    return '<label class="field"><span class="field-label">' . format_label($label) . '</span><span class="input-wrap">'
        . '<input type="' . e($type) . '" name="' . e($name) . '"' . $id . ' placeholder="' . e($placeholder) . '" value="' . e($value) . '"' . $req . $ro . '>'
        . '</span>' . $hint . '</label>';
}

/** Image file input (JPG/PNG/GIF/WEBP). */
function file_field(string $label, string $name, bool $required = true): string
{
    return '<label class="field"><span class="field-label">' . format_label($label) . '</span><span class="input-wrap">'
        . '<input type="file" class="file-input" name="' . e($name) . '" accept="image/jpeg,image/png,image/gif,image/webp"' . ($required ? ' required' : '') . '>'
        . '</span><small class="field-hint">JPG, PNG, GIF or WEBP · max 5 MB</small></label>';
}

/** Thumbnail link to a stored proof image. */
function proof_thumb(string $path, string $alt): string
{
    return '<a class="proof-thumb" href="' . e($path) . '" target="_blank" rel="noopener">'
        . '<img src="' . e($path) . '" alt="' . e($alt) . '"><span>View</span></a>';
}

function select_field(string $label, string $name, array $options, string $selected = '', array $opts = []): string
{
    $id  = !empty($opts['id']) ? ' id="' . e($opts['id']) . '"' : '';
    $req = !empty($opts['required']) ? ' required' : '';
    $html = '<label class="field"><span class="field-label">' . format_label($label) . '</span><span class="input-wrap">'
        . '<select name="' . e($name) . '"' . $id . $req . '>';
    foreach ($options as $k => $opt) {
        $val = is_int($k) ? (string) $opt : (string) $k;
        $lbl = (string) $opt;
        $html .= '<option value="' . e($val) . '"' . ($val === $selected || $lbl === $selected ? ' selected' : '') . '>' . e($lbl) . '</option>';
    }
    return $html . '</select>' . icon('down', 16) . '</span></label>';
}

function textarea_field(string $label, string $name, string $placeholder = '', string $value = ''): string
{
    return '<label class="field"><span class="field-label">' . format_label($label) . '</span>'
        . '<textarea name="' . e($name) . '" placeholder="' . e($placeholder) . '">' . e($value) . '</textarea></label>';
}

function search_box(string $placeholder = 'Search...', string $name = 'q', string $value = ''): string
{
    return '<div class="search">' . icon('search', 17)
        . '<input type="search" aria-label="Search" name="' . e($name) . '" placeholder="' . e($placeholder) . '" value="' . e($value) . '">'
        . '<kbd>⌘ K</kbd></div>';
}

function page_title(string $title, string $description, string $action = ''): string
{
    return '<div class="page-heading"><div><p class="eyebrow">' . e(APP_NAME) . ' · Zamboanga City</p>'
        . '<h1>' . e($title) . '</h1><p>' . e($description) . '</p></div>'
        . ($action !== '' ? '<div class="heading-action">' . $action . '</div>' : '') . '</div>';
}

function logo(bool $light = false): string
{
    return '<div class="logo' . ($light ? ' logo-light' : '') . '"><span class="logo-mark"><span></span><span></span></span>'
        . '<span><strong>West Metro</strong><small>Medical Center · ZC</small></span></div>';
}

function stat_card(string $label, string $value, string $trend, string $icon, string $tone = 'teal'): string
{
    return '<div class="stat-card"><div class="stat-icon ' . e($tone) . '">' . icon($icon, 21) . '</div>'
        . '<div class="stat-copy"><p>' . e($label) . '</p><strong>' . e($value) . '</strong><span>' . e($trend) . '</span></div></div>';
}

function mini_avatar(string $name): string
{
    return '<span class="mini-avatar">' . e(initials($name, 3)) . '</span>';
}

/** Tone for a status word across all portals. */
function status_tone(string $status): string
{
    return match ($status) {
        'Paid', 'Completed', 'Done', 'Active', 'Registered', 'Checked in', 'Checked-in', 'Available', 'Vacant', 'Cleared', 'Cash', 'Recovered / Improved' => 'success',
        'Pending', 'Waiting', 'Partial', 'Provisional', 'For discharge', 'In consultation', 'In progress', 'Exception', 'HMO', 'PhilHealth', 'Insurance', 'Urgent' => 'warning',
        'Unpaid', 'Disabled', 'Deactivated', 'Cancelled', 'No-show', 'Maintenance', 'Out of service', 'Stale', 'STAT', 'Deceased' => 'danger',
        'Disabled-neutral', 'Discharged', 'Archived' => 'neutral',
        default => 'info',
    };
}

/** Render simulated SMS notification banner when present in session. */
function render_sim_banners(): string
{
    if (isset($_SESSION['sim_messages']) && is_array($_SESSION['sim_messages'])) {
        // Strip out mockup emails; emails are sent directly to the inbox
        $_SESSION['sim_messages'] = array_values(array_filter($_SESSION['sim_messages'], static fn ($m) => ($m['channel'] ?? '') === 'SMS'));
    }
    $msgs = $_SESSION['sim_messages'] ?? [];
    if (empty($msgs)) {
        return '';
    }
    $smsMsgs = $_SESSION['sim_messages'];
    $html = '<div style="display:flex; flex-direction:column; gap:8px; margin-bottom:16px;">';
    foreach ($smsMsgs as $idx => $m) {
        $html .= '<div style="background:#f0fdfa; border:1px solid #99f6e4; border-radius:10px; padding:12px 16px; display:flex; align-items:center; justify-content:space-between; gap:12px; font-size:13px; color:#0f766e;">';
        $html .= '<div><strong>📱 Simulated SMS to ' . e((string) ($m['recipient'] ?? '')) . ':</strong> ' . e((string) ($m['message'] ?? ''));
        if (!empty($m['link_url'])) {
            $html .= ' — <a href="' . e((string) $m['link_url']) . '" style="font-weight:700; text-decoration:underline; color:#0f766e;">' . e((string) ($m['link_label'] ?: 'Open Link')) . '</a>';
        }
        $html .= '</div>';
        $html .= '<a href="' . e(url_with(['clear_sim' => 1])) . '" style="font-size:12px; color:#0f766e; opacity:0.75; text-decoration:none; white-space:nowrap;">Dismiss ×</a>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

/* --------------------------- tables & pagination --------------------------- */

/** Slice an array of rows for the current page ?p= and return ['rows' => array, 'label' => '1–10 of 24', 'page' => int, 'pages' => int, 'total' => int]. */
function paginate_rows(array $allRows, int $perPage = 10, string $param = 'p'): array
{
    $total = count($allRows);
    $pages = max(1, (int) ceil($total / max(1, $perPage)));
    $page  = max(1, min($pages, (int) ($_GET[$param] ?? 1)));
    $offset = ($page - 1) * $perPage;
    $slice  = array_slice($allRows, $offset, $perPage, true);
    $start  = $total > 0 ? ($offset + 1) : 0;
    $end    = min($total, $offset + count($slice));
    $label  = $total > 0 ? ($start . '–' . $end . ' of ' . $total) : '0 of 0';

    return [
        'rows'   => $slice,
        'label'  => $label,
        'page'   => $page,
        'pages'  => $pages,
        'total'  => $total,
        'offset' => $offset,
    ];
}

/** Opens a GET form carrying the params that must survive a filter submit. */
function toolbar_open(string $page, array $keep = []): string
{
    $html = '<form method="get" action="index.php" class="table-toolbar">'
        . '<input type="hidden" name="page" value="' . e($page) . '">';
    foreach ($keep as $k => $v) {
        if ($v !== null && $v !== '') {
            $html .= '<input type="hidden" name="' . e((string) $k) . '" value="' . e((string) $v) . '">';
        }
    }
    return $html;
}

function toolbar_close(): string
{
    return '</form>';
}

/** "Apply" button for toolbars. */
function filter_button(string $label = 'Apply'): string
{
    return '<button class="btn btn-secondary" type="submit"><span>' . e($label) . '</span></button>';
}

function table_open(array $headers, string $toolbar = ''): string
{
    $html = '<div class="table-card">' . $toolbar . '<div class="table-scroll"><table><thead><tr>';
    foreach ($headers as $h) {
        $html .= '<th>' . e($h) . '</th>';
    }
    return $html . '</tr></thead><tbody>';
}

function table_close(string $count = '1–10', bool $pager = true, mixed $totalPages = 3, mixed $currentPage = null, mixed $extraParam = null): string
{
    $html = '</tbody></table></div><div class="pagination"><span>Showing ' . e($count) . '</span>';
    if ($pager) {
        $param = 'p';
        $numPages = 1;

        // Support various calling styles:
        // 1. table_close('label', true, 5, 'p') -> $totalPages=5, $currentPage='p'
        // 2. table_close('label', true, $totalCount, $page, $pagesCount) -> $totalPages=$totalCount, $currentPage=$page, $extraParam=$pagesCount
        // 3. table_close('label', true, $pagesCount, $page) -> $totalPages=$pagesCount
        if (is_int($extraParam) || is_numeric($extraParam)) {
            $numPages = max(1, (int) $extraParam);
            $param = is_string($currentPage) ? $currentPage : 'p';
        } elseif (is_string($currentPage)) {
            $param = $currentPage;
            $numPages = max(1, (int) $totalPages);
        } else {
            $numPages = max(1, (int) $totalPages);
        }

        $p = max(1, min($numPages, (int) ($_GET[$param] ?? 1)));
        $html .= '<div>';
        $html .= $p <= 1
            ? '<button disabled>Previous</button>'
            : '<a href="' . e(url_with([$param => $p - 1])) . '">Previous</a>';
        for ($n = 1; $n <= $numPages; $n++) {
            $html .= '<a class="' . ($n === $p ? 'current' : '') . '" href="' . e(url_with([$param => $n])) . '">' . $n . '</a>';
        }
        $html .= $p >= $numPages
            ? '<button disabled>Next</button>'
            : '<a href="' . e(url_with([$param => $p + 1])) . '">Next</a>';
        $html .= '</div>';
    }
    return $html . '</div></div>';
}

/** Standalone pagination bar (for card grids / list timelines outside standard table_open/table_close). */
function pagination_bar(string $itemSingular, int $total, int $page, int $pages, string $param = 'p'): string
{
    $label = $total . ' ' . $itemSingular . ($total === 1 ? '' : 's');
    $p = max(1, min(max(1, $pages), $page));
    $html = '<div class="pagination"><span>Showing ' . e($label) . '</span>';
    if ($pages > 1) {
        $html .= '<div>';
        $html .= $p <= 1
            ? '<button disabled>Previous</button>'
            : '<a href="' . e(url_with([$param => $p - 1])) . '">Previous</a>';
        for ($n = 1; $n <= $pages; $n++) {
            $html .= '<a class="' . ($n === $p ? 'current' : '') . '" href="' . e(url_with([$param => $n])) . '">' . $n . '</a>';
        }
        $html .= $p >= $pages
            ? '<button disabled>Next</button>'
            : '<a href="' . e(url_with([$param => $p + 1])) . '">Next</a>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

function filtered_empty(string $message): string
{
    return '<div class="filtered-empty">' . e($message) . '</div>';
}

/** Case-insensitive "row contains query" test. */
function row_matches(array $row, string $query): bool
{
    if ($query === '') {
        return true;
    }
    $flat = [];
    foreach ($row as $v) {
        if (is_scalar($v)) {
            $flat[] = (string) $v;
        }
    }
    return mb_stripos(implode(' ', $flat), $query) !== false;
}

/** Tabs rendered as links (server-side). */
function tabs(array $labels, string $active, string $param = 'tab'): string
{
    $html = '<div class="tabs">';
    foreach ($labels as $label) {
        $html .= '<a class="' . ($label === $active ? 'active' : '') . '" href="' . e(url_with([$param => $label, 'p' => null])) . '">' . e($label) . '</a>';
    }
    return $html . '</div>';
}

/* ------------------------- date range filter ------------------------- */

/** Validated [from, to] (Y-m-d or '') from ?from= / ?to=, swapped if reversed. */
function range_params(): array
{
    $clean = static fn ($v) => (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false) ? $v : '';
    $from = $clean($_GET['from'] ?? '');
    $to   = $clean($_GET['to'] ?? '');
    if ($from !== '' && $to !== '' && $from > $to) {
        [$from, $to] = [$to, $from];
    }
    return [$from, $to];
}

/** Is a date (string or timestamp) inside the inclusive range? Empty bound = open-ended. */
function in_range(string|int $date, string $from, string $to): bool
{
    if ($from === '' && $to === '') {
        return true;
    }
    $ts = is_int($date) ? $date : strtotime($date);
    if ($ts === false) {
        return false;
    }
    $day = date('Y-m-d', $ts);
    return ($from === '' || $day >= $from) && ($to === '' || $day <= $to);
}

/** From/To date range inputs. */
function date_range_fields(string $from, string $to): string
{
    $fAttr = ' value="' . e($from) . '"' . ($to   !== '' ? ' max="' . e($to)   . '"' : '');
    $tAttr = ' value="' . e($to)   . '"' . ($from !== '' ? ' min="' . e($from) . '"' : '');
    $clear = ($from !== '' || $to !== '')
        ? '<a class="range-clear" href="' . e(url_with(['from' => null, 'to' => null, 'p' => null])) . '">&times;&nbsp;Clear</a>'
        : '';
    return '<div class="range-filter">'
        . '<input type="date" name="from" aria-label="From date"' . $fAttr . '>'
        . '<span class="range-sep">&ndash;</span>'
        . '<input type="date" name="to" aria-label="To date"' . $tAttr . '>'
        . $clear
        . '</div>';
}
