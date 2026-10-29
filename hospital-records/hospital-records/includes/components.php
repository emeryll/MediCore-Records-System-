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
 * opts: variant (primary|secondary|ghost|danger), icon, href, type
 */
function button(string $label, array $opts = []): string
{
    $variant = $opts['variant'] ?? 'primary';
    $icon    = isset($opts['icon']) ? icon($opts['icon'], 17) : '';
    $inner   = $icon . '<span>' . e($label) . '</span>';
    $class   = 'btn btn-' . e($variant);

    if (isset($opts['href'])) {
        return '<a class="' . $class . '" href="' . e($opts['href']) . '">' . $inner . '</a>';
    }
    return '<button class="' . $class . '" type="' . e($opts['type'] ?? 'button') . '">' . $inner . '</button>';
}

/** A POST form wrapping one button (used for actions that change state). */
function post_button(string $label, string $action, array $fields = [], array $opts = []): string
{
    $hidden = '<input type="hidden" name="action" value="' . e($action) . '">'
        . '<input type="hidden" name="return" value="' . e(url($_GET)) . '">'
        . csrf_field();
    foreach ($fields as $k => $v) {
        $hidden .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }

    $class = $opts['class'] ?? '';
    if (!empty($opts['plain'])) {
        $btn = '<button type="submit" class="' . e($class) . '">' . ($opts['html'] ?? e($label)) . '</button>';
    } else {
        $btn = '<button type="submit" class="btn btn-' . e($opts['variant'] ?? 'primary') . ' ' . e($class) . '">'
            . (isset($opts['icon']) ? icon($opts['icon'], 17) : '') . '<span>' . e($label) . '</span></button>';
    }
    $confirmAttr = (($opts['confirm'] ?? true) !== false && $action !== 'read_notifications')
        ? ' onsubmit="return confirm(\'Are you sure about this action?\');"'
        : '';
    return '<form method="post" action="index.php" class="inline-form"' . $confirmAttr . '>' . $hidden . $btn . '</form>';
}

/** Button that only shows a confirmation toast (matches the prototype's demo-only buttons). */
function notify_button(string $label, string $message, array $opts = []): string
{
    return post_button($label, 'notify', ['message' => $message], $opts);
}

function field(string $label, string $name, string $placeholder = '', string $type = 'text', string $value = ''): string
{
    return '<label class="field"><span class="field-label">' . e($label) . '</span><span class="input-wrap">'
        . '<input type="' . e($type) . '" name="' . e($name) . '" placeholder="' . e($placeholder) . '" value="' . e($value) . '">'
        . '</span></label>';
}

/** Image file input (JPG/PNG/GIF/WEBP). Camera capture is offered on mobile. */
function file_field(string $label, string $name): string
{
    return '<label class="field"><span class="field-label">' . e($label) . '</span><span class="input-wrap">'
        . '<input type="file" class="file-input" name="' . e($name) . '" accept="image/jpeg,image/png,image/gif,image/webp" required>'
        . '</span><small class="field-hint">JPG, PNG, GIF or WEBP · max 5 MB</small></label>';
}

/** Thumbnail link to a stored proof image. */
function proof_thumb(string $path, string $alt): string
{
    return '<a class="proof-thumb" href="' . e($path) . '" target="_blank" rel="noopener">'
        . '<img src="' . e($path) . '" alt="' . e($alt) . '"><span>View</span></a>';
}


function select_field(string $label, string $name, array $options, string $selected = ''): string
{
    $html = '<label class="field"><span class="field-label">' . e($label) . '</span><span class="input-wrap">'
        . '<select name="' . e($name) . '">';
    foreach ($options as $opt) {
        $html .= '<option' . ($opt === $selected ? ' selected' : '') . '>' . e($opt) . '</option>';
    }
    return $html . '</select>' . icon('down', 16) . '</span></label>';
}

function textarea_field(string $label, string $name, string $placeholder = ''): string
{
    return '<label class="field"><span class="field-label">' . e($label) . '</span>'
        . '<textarea name="' . e($name) . '" placeholder="' . e($placeholder) . '"></textarea></label>';
}

function search_box(string $placeholder = 'Search...', string $name = 'q', string $value = ''): string
{
    return '<div class="search">' . icon('search', 17)
        . '<input type="search" aria-label="Search" name="' . e($name) . '" placeholder="' . e($placeholder) . '" value="' . e($value) . '">'
        . '<kbd>⌘ K</kbd></div>';
}

function page_title(string $title, string $description, string $action = ''): string
{
    return '<div class="page-heading"><div><p class="eyebrow">Hospital Records System</p>'
        . '<h1>' . e($title) . '</h1><p>' . e($description) . '</p></div>'
        . ($action !== '' ? '<div class="heading-action">' . $action . '</div>' : '') . '</div>';
}

function logo(bool $light = false): string
{
    return '<div class="logo' . ($light ? ' logo-light' : '') . '"><span class="logo-mark"><span></span><span></span></span>'
        . '<span><strong>' . e(APP_NAME) . '</strong><small>Records system</small></span></div>';
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

/** Tone for a status word. */
function status_tone(string $status): string
{
    return match ($status) {
        'Paid', 'Completed', 'Done', 'Active', 'Checked in', 'Available', 'Cash' => 'success',
        'Pending', 'Waiting', 'Partial', 'Insurance' => 'warning',
        'Unpaid', 'Disabled', 'Cancelled' => 'danger',
        'Disabled-neutral' => 'neutral',
        default => 'info',
    };
}

/* --------------------------- tables --------------------------- */

/** Opens a GET form carrying the params that must survive a filter submit. */
function toolbar_open(string $page, array $keep = []): string
{
    $html = '<form method="get" action="index.php" class="table-toolbar">'
        . '<input type="hidden" name="page" value="' . e($page) . '">';
    foreach ($keep as $k => $v) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return $html;
}

function toolbar_close(): string
{
    return '</form>';
}

/** "Apply" button for toolbars (there is no JS to auto-submit on change). */
function filter_button(): string
{
    return '<button class="btn btn-secondary" type="submit"><span>Apply</span></button>';
}

function table_open(array $headers, string $toolbar = ''): string
{
    $html = '<div class="table-card">' . $toolbar . '<div class="table-scroll"><table><thead><tr>';
    foreach ($headers as $h) {
        $html .= '<th>' . e($h) . '</th>';
    }
    return $html . '</tr></thead><tbody>';
}

function table_close(string $count = '1–5 of 48', bool $pager = true): string
{
    $html = '</tbody></table></div><div class="pagination"><span>Showing ' . e($count) . '</span>';
    if ($pager) {
        $p = max(1, min(3, (int) ($_GET['p'] ?? 1)));
        $html .= '<div>';
        $html .= $p === 1
            ? '<button disabled>Previous</button>'
            : '<a href="' . e(url_with(['p' => $p - 1])) . '">Previous</a>';
        foreach ([1, 2, 3] as $n) {
            $html .= '<a class="' . ($n === $p ? 'current' : '') . '" href="' . e(url_with(['p' => $n])) . '">' . $n . '</a>';
        }
        $html .= $p === 3
            ? '<button disabled>Next</button>'
            : '<a href="' . e(url_with(['p' => $p + 1])) . '">Next</a>';
        $html .= '</div>';
    }
    return $html . '</div></div>';
}

function filtered_empty(string $message): string
{
    return '<div class="filtered-empty">' . e($message) . '</div>';
}

/** Case-insensitive "row contains query" test. */
function row_matches(array $row, string $query): bool
{
    return $query === '' || mb_stripos(implode(' ', $row), $query) !== false;
}

/** Tabs rendered as links (server-side). */
function tabs(array $labels, string $active, string $param = 'tab'): string
{
    $html = '<div class="tabs">';
    foreach ($labels as $label) {
        $html .= '<a class="' . ($label === $active ? 'active' : '') . '" href="' . e(url_with([$param => $label])) . '">' . e($label) . '</a>';
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

/** From/To date range inputs — no label wrappers, flush 42px height to match toolbar controls. */
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