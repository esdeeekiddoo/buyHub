<?php
/**
 * config.php
 * ---------------------------------------------------------------------
 * All the settings live here, so if the database password changes you
 * only edit this one file and not 20 other files.
 *
 * Laragon's default MySQL settings are used below (root / no password).
 */

$config = [
    // In production (Render) these come from environment variables.
    // Locally they fall back to Laragon's defaults, so nothing changes
    // for local dev.
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'marketplace',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        // Hosted MySQL (Aiven, Railway, ...) usually requires SSL on.
        //   DB_SSL=true  -> append ;sslmode=require to the DSN
        'ssl'  => filter_var(getenv('DB_SSL') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    ],

    // When true, errors show on screen while you are developing.
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'true', FILTER_VALIDATE_BOOLEAN),

    'site_name' => 'BuyHub',

    // Currency. The three-letter code is ISO 4217, so it is safe in a form
    // label and needs no HTML entity. money() in helpers.php prints the
    // matching symbol, so changing this line changes both the label and
    // every price on the site.
    //   PHP = Philippine peso  (symbol ₱, written as \u{20B1} in money())
    //   MYR = Malaysian ringgit (symbol RM)
    'currency' => 'PHP',
    'currency_symbol' => "\u{20B1}",   // ₱ Philippine peso

    // One photo per item, so this is the only upload limit you need.
    'max_photo_bytes' => 3 * 1024 * 1024,   // 3 MB
];
