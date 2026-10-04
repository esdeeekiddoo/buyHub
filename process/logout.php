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
check_csrf();
logout_user();

// Logging out destroyed the session, so start a fresh one to carry the
// flash message across the redirect.
session_start();
flash('You have been logged out.');
redirect('index.php');
