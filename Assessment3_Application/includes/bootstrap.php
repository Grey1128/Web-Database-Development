<?php
/**
 * bootstrap.php - included at the top of every page.
 * Starts a hardened session and provides helper functions for
 * escaping, links, flash messages, access control, CSRF protection
 * and automatic session timeout.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,   // JavaScript cannot read the session cookie
        'samesite' => 'Lax',  // cookie not sent on cross-site POSTs
    ]);

    session_start();
}

/* ---------- automatic session timeout ---------- */

// Log out a user after 5 minutes of inactivity.
$sessionTimeout = 300;

if (isset($_SESSION['user_id'])) {

    if (
        isset($_SESSION['LAST_ACTIVITY']) &&
        (time() - $_SESSION['LAST_ACTIVITY']) > $sessionTimeout
    ) {
        // Clear all session data.
        $_SESSION = [];

        // Delete the session cookie.
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy the old session.
        session_destroy();

        // Start a new session for the flash message.
        session_start();

        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Your session expired after 5 minutes of inactivity. Please log in again.'
        ];

        header('Location: /Assessment3_Application/login.php');
        exit;
    }

    // Update activity time whenever the logged-in user loads a page.
    $_SESSION['LAST_ACTIVITY'] = time();
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

/** Redirect the user to another page. */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/** Store a temporary message in the session. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/** Format a number as currency. */
function money(float|string $amount): string
{
    return '$' . number_format((float)$amount, 2);
}

/* ---------- authentication + role checks ---------- */

/** Check whether a user is logged in. */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/** Return the current user's ID. */
function currentUserId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

/** Check whether the current user has one of the required roles. */
function hasRole(string ...$roles): bool
{
    return isLoggedIn()
        && isset($_SESSION['role'])
        && in_array($_SESSION['role'], $roles, true);
}

/** Require the user to be logged in. */
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

        flash(
            'error',
            'You do not have permission to open that page.'
        );

        redirect('dashboard.php');
    }
}

/* ---------- CSRF protection ---------- */

/** Generate and return the CSRF token for the current session. */
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
    return '<input type="hidden" name="csrf" value="'
        . e(csrfToken())
        . '">';
}

/** Reject any POST that does not carry this session's token. */
function verifyCsrf(): void
{
    $sent = $_POST['csrf'] ?? '';

    if (
        !is_string($sent) ||
        !hash_equals(csrfToken(), $sent)
    ) {
        http_response_code(400);

        exit(
            'Invalid or expired form. '
            . 'Please go back, refresh the page and try again.'
        );
    }
}

// Every POST request in the application is CSRF-checked automatically.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
}