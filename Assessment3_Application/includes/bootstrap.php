<?php
/**
 * bootstrap.php - included at the top of every page.
 * Starts a hardened session and provides small helper functions for
 * escaping, links, flash messages, access control and CSRF protection.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,   // JavaScript cannot read the session cookie
        'samesite' => 'Lax',  // cookie not sent on cross-site POSTs
    ]);
    session_start();
}

define('APP_ROOT', dirname(__DIR__));

foreach (['Database', 'User', 'Show', 'Performance', 'Booking', 'Ticket', 'Report'] as $class) {
    require_once APP_ROOT . "/classes/$class.php";
}

/* ---------- output + navigation helpers ---------- */

/** Escape text for safe output in HTML (prevents XSS). */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Build a link that works from the root folder and from staff/ or manager/. */
function url(string $path): string
{
    $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
    $prefix = ($scriptDir === realpath(APP_ROOT)) ? '' : '../';
    return $prefix . $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function money(float|string $amount): string
{
    return '$' . number_format((float)$amount, 2);
}

/* ---------- authentication + role checks ---------- */

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentUserId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function hasRole(string ...$roles): bool
{
    return isLoggedIn() && in_array($_SESSION['role'], $roles, true);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

/** Allow only the listed roles; everyone else is sent to their dashboard. */
function requireRole(string ...$roles): void
{
    requireLogin();
    if (!hasRole(...$roles)) {
        http_response_code(403);
        flash('error', 'You do not have permission to open that page.');
        redirect('dashboard.php');
    }
}

/* ---------- CSRF protection ---------- */

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Hidden input to place inside every POST form. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}

/** Reject any POST that does not carry this session's token. */
function verifyCsrf(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrfToken(), $sent)) {
        http_response_code(400);
        exit('Invalid or expired form. Please go back, refresh the page and try again.');
    }
}

// Every POST request in the application is CSRF-checked automatically.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
}
