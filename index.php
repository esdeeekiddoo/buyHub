<?php
/**
 * index.php (site root) - entry point
 * ---------------------------------------------------------------------
 * The real home page lives in pages/index.php. This shim exists so that
 * opening the folder URL (http://localhost/marketplace/) still lands on
 * the home page, instead of showing a directory listing.
 *
 * It sends the browser to pages/, where every page lives.
 */

require_once __DIR__ . '/includes/helpers.php';

header('Location: ' . base_url('pages/index.php'));
exit;
