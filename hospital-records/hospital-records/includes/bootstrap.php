<?php
declare(strict_types=1);

session_start();

const APP_NAME = 'MediCore';

/** Escape output. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build an app URL from query params (empty values are dropped). */
function url(array $params = []): string
{
    $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
    return 'index.php' . ($params ? '?' . http_build_query($params) : '');
}

/** Current URL with some query params overridden. */
function url_with(array $override): string
{
    return url(array_merge($_GET, $override));
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function take_flash(): ?string
{
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

/** Initials for avatars: "Dr. James Wilson" -> "DJ" (limit 2) or "ER". */
function initials(string $name, int $limit = 2): string
{
    $out = '';
    foreach (preg_split('/\s+/', trim($name)) ?: [] as $word) {
        $out .= mb_substr($word, 0, 1);
    }
    return mb_strtoupper(mb_substr($out, 0, $limit));
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf']) && hash_equals(csrf_token(), (string) $_POST['csrf']);
}

/** Only allow redirects back into this app. */
function safe_return(?string $to): string
{
    return ($to && str_starts_with($to, 'index.php')) ? $to : 'index.php';
}

/** Mutable per-session copy of the sample data. */
function &state(): array
{
    if (!isset($_SESSION['state'])) {
        $_SESSION['state'] = [
            'users'      => seed_users(),
            'crud'       => seed_crud(),
            'admissions' => seed_admissions(),
        ];
    }
    return $_SESSION['state'];
}
