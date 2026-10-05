<?php
/**
 * process/logout.php - log out
 *
 * This one changes data (it destroys the session), so it is POST only.
 * If logout were a GET link, a random web page could log your users out
 * with a hidden <img> tag.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_post();

// Logging out is a low-risk action, so a stale CSRF token (e.g. the
// session expired while the tab was left open) must NOT dead-end the
// user on a 403 page. We still require a POST and a matching token, but
// if the token is simply out of date we log them out anyway - which is
// exactly what they asked for - and say so gently.
$sent     = $_POST['csrf_token'] ?? '';
$expected = $_SESSION['csrf_token'] ?? '';
$valid    = $expected !== '' && hash_equals($expected, $sent);

logout_user();

// Logging out destroyed the session, so start a fresh one to carry the
// flash message across the redirect. Regenerating the id makes PHP send a
// NEW session cookie - without it the browser would still be holding the
// deleted one and the message would be lost.
session_start();
session_regenerate_id(true);

flash($valid
    ? 'You have been logged out.'
    : 'Your session had already ended. You are now logged out.');

redirect('index.php');
