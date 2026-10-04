<?php
/**
 * includes/db.php
 * ---------------------------------------------------------------------
 * Creates the database connection ONE time, then hands the same
 * connection to every file that needs it.
 *
 * PDO is used with prepared statements. That means user input is sent
 * separately from the SQL, so nobody can break into your database by
 * typing funny things into a form. Never build SQL like this:
 *
 *     $sql = "SELECT * FROM items WHERE title = '$title'";   // DANGEROUS
 *
 * Always do this instead:
 *
 *     $sql = "SELECT * FROM items WHERE title = ?";
 *     $row = db()->fetchOne($sql, [$title]);                 // SAFE
 */

require_once __DIR__ . '/../config.php';

/** Returns the shared PDO connection. */
function db(): PDO
{
    // A function cannot see variables from the page that called it unless
    // we say "global". Without this line $config would be undefined here.
    global $config;

    // static = remember this value between calls, so we only connect once.
    static $pdo = null;

    if ($pdo === null) {
        $host = $config['db']['host'];
        $name = $config['db']['name'];
        $user = $config['db']['user'];
        $pass = $config['db']['pass'];

        try {
            $dsn = "mysql:host=$host;port=" . $config['db']['port'] . ";dbname=$name;charset=utf8mb4";

            // Hosted MySQL (like Aiven) REFUSES plain connections, so add
            // sslmode=require when DB_SSL=true.
            if (isset($config['db']['ssl']) && $config['db']['ssl'] === true) {
                $dsn .= ';sslmode=require';
            }

            $pdo = new PDO(
                $dsn,
                $user,
                $pass,
                [
                    // Throw an error instead of silently failing. Silent
                    // failures are the hardest kind of bug to track down.
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    // Fetch rows as named arrays: $row['title']
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Use real prepared statements (stronger injection defence)
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            if ($config['debug']) {
                // While developing you WANT to see what went wrong.
                die('Database error: ' . $e->getMessage());
            }
            // When live, the visitor must never see your database details.
            die('Sorry, the site is having problems. Please try again later.');
        }
    }

    return $pdo;
}

/** Turns on PHP error reporting while you are developing. */
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
