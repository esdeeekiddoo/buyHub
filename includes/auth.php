<?php
/**
 * includes/auth.php
 * ---------------------------------------------------------------------
 * Logging in, logging out, and blocking pages that need a login.
 *
 * Every page that requires a login starts with:
 *
 *     require_once __DIR__ . '/includes/auth.php';
 *     require_login();
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// The session is what remembers who is logged in between pages.
// It must start before any output, which is why every page includes
// this file before printing any HTML.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Only let this file be reached by submitting its own form (a POST).
 *
 * check_csrf() already rejects a bare visit to a process file, but
 * refusing GET makes the rule obvious in the code: these files react,
 * they never just display.
 */
function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        redirect('index.php');
    }
}

/**
 * Send anyone who is not logged in to the login page.
 * Without this, a visitor could just type /add-item.php in the address
 * bar and skip the login form completely.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        // current_user() may have just noticed the session points at a
        // deleted account. session_was_stale() reports and clears that,
        // so the visitor gets told what actually happened instead of a
        // bare "please log in" for a session that worked moments ago.
        $stale = session_was_stale();

        // Remember where they were going so login can send them back.
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'] ?? 'index.php';

        flash(
            $stale
                ? 'Your session had expired. Please log in again.'
                : 'Please log in first.',
            'error'
        );

        redirect('login.php');
    }
}

/** Start a session as this user. */
function login_user(int $userId): void
{
    // A new session id after login stops "session fixation" attacks,
    // where someone hijacks your session id before you log in.
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;

    // The stale flag described the PREVIOUS session. Leaving it behind
    // would show "Your session had expired" on an unrelated later visit.
    unset($_SESSION['_was_stale']);
}

/** Destroy the session completely. */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain']);
    }
    session_destroy();
}

/**
 * Reject any form submission that did not come from one of our pages.
 *
 * Without CSRF protection, any website on the internet could secretly
 * submit a form to your process/delete-item.php while a user is logged
 * in, and delete their items. A hidden token proves the request came
 * from a page we rendered.
 */
function check_csrf(): void
{
    // Create a token the first time it is needed.
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $sent = $_POST['csrf_token'] ?? '';

    // hash_equals compares in constant time, so nobody can guess the
    // token one character at a time by measuring how long it takes.
    if (!hash_equals($_SESSION['csrf_token'], $sent)) {
        http_response_code(403);
        die('403 - Request rejected. Please go back, refresh the page and try again.');
    }
}

/** The hidden input you paste inside every <form>. */
function csrf_field(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}
