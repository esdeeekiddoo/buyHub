<?php
/**
 * includes/page-shim.php
 * ---------------------------------------------------------------------
 * Shared by the small .php redirect stubs at the site root.
 *
 * When the pages moved into pages/, every old URL (browse.php, cart.php,
 * ...) stopped existing. Each old name now has a stub at the root that
 * requires THIS file, which forwards to the matching page in pages/.
 *
 * The current file name is read from the URL, so one file covers them
 * all, and the query string is carried over so things like
 * item.php?id=3 still work.
 */

require_once __DIR__ . '/helpers.php';

$page  = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$query = $_SERVER['QUERY_STRING'] ?? '';

$target = page_url($page) . ($query === '' ? '' : '?' . $query);

header('Location: ' . $target, true, 302);
exit;
