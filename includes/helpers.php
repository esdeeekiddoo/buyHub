<?php
/**
 * includes/helpers.php
 * ---------------------------------------------------------------------
 * A few tiny functions used across the pages. Nothing clever.
 */

/**
 * Escape text before printing it in HTML.
 *
 * If a user types  <script>alert('hi')</script>  as an item title and you
 * print it raw, their code runs in every visitor's browser. Running it
 * through e() prints it as harmless text instead.
 *
 * Rule: every <?php echo ?> in a page goes through e().
 */
function e(?string $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

/**
 * Full name for display: "Aisyah Rahman".
 *
 * These three take ?array rather than array on purpose. They are called
 * with whatever a query returned, and a nullable parameter means a missing
 * row prints something sensible instead of throwing a TypeError and taking
 * the whole page down.
 */
function full_name(?array $user): string
{
    if ($user === null) {
        return 'Someone';
    }

    $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

    return $name !== '' ? $name : 'Someone';
}

/** First name only, for a friendly "Hi, Aisyah". */
function first_name_of(?array $user): string
{
    $name = $user['first_name'] ?? '';

    return $name !== '' ? (string) $name : 'there';
}

/** Initials for an avatar placeholder: "Aisyah Rahman" -> "AR". */
function initials_of(?array $user): string
{
    if ($user === null) {
        return '?';
    }

    $a = mb_substr((string) ($user['first_name'] ?? ''), 0, 1);
    $b = mb_substr((string) ($user['last_name'] ?? ''), 0, 1);

    return mb_strtoupper($a . $b) ?: '?';
}

/**
 * Age in whole years from a YYYY-MM-DD birthday.
 *
 * Returns null when the date is missing or unreadable, so the caller can
 * decide what to show rather than printing "0" by accident.
 */
function age_from(?string $birthDate): ?int
{
    if ($birthDate === null || $birthDate === '') {
        return null;
    }

    try {
        $born = new DateTimeImmutable($birthDate);
    } catch (Exception) {
        return null;
    }

    $now = new DateTimeImmutable('today');

    // Subtracting years then correcting is how you avoid the classic bug
    // where someone born on 29 Feb is "one year off" for most of a year.
    $age = $born->diff($now)->y;

    return ($born->format('m-d') > $now->format('m-d')) ? $age - 1 : $age;
}

/**
 * Format a number as a price: 12.5 becomes "₱12.50".
 *
 * The symbol is the ISO 4217 code for the Philippine peso, U+20B1. It is
 * written into this file as a literal because PHP source is UTF-8, which
 * is declared at the top of config.php's output.
 */
function money(float|int|string|null $amount): string
{
    return currency_symbol() . number_format((float) $amount, 2);
}

/**
 * The symbol that goes in front of a price.
 *
 * Kept separate from money() so a template can print a bare "from
 * ₱35.00" without duplicating the number formatting.
 *
 * The symbol is written as a \u{...} escape rather than a literal
 * character, so this file survives being opened and saved by an editor
 * that guesses the wrong encoding.
 */
function currency_symbol(): string
{
    global $config;

    // An explicit currency_symbol in config.php wins, because that is the
    // one a custom symbol cannot be reached through a lookup table.
    if (!empty($config['currency_symbol'])) {
        return (string) $config['currency_symbol'];
    }

    // Otherwise fall back to the ISO 4217 code for the currency named.
    $symbols = [
        'PHP' => "\u{20B1}",   // Philippine peso
        'MYR' => 'RM',         // Malaysian ringgit
        'USD' => '$',           // US dollar
        'EUR' => "\u{20AC}",   // euro
        'GBP' => "\u{00A3}",   // pound sterling
        'SGD' => 'S$',          // Singapore dollar
        'IDR' => 'Rp',          // Indonesian rupiah
        'THB' => "\u{0E3F}",   // Thai baht
        'JPY' => "\u{00A5}",   // Japanese yen
        'INR' => "\u{20B9}",   // Indian rupee
        'AUD' => 'A$',          // Australian dollar
        'CAD' => 'C$',          // Canadian dollar
    ];

    $code = strtoupper((string) ($config['currency'] ?? 'PHP'));

    // An unknown code still prints something readable rather than nothing.
    return $symbols[$code] ?? $code . ' ';
}

/**
 * Show a message on the NEXT page only.
 *
 * This is how a process file tells the page it redirects to what happened,
 * because a redirect throws away $_POST and there is no other way to pass
 * a message along.
 */
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/** Read and clear the stored message. */
function show_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);      // delete it so it only shows once
    return $flash;
}

/**
 * The folder the app lives in, as a URL prefix.
 *
 * It is worked out from the URL rather than hard-coded, so the app keeps
 * working whether you reach it at:
 *     http://marketplace.test/          -> prefix ""
 *     http://localhost/marketplace/     -> prefix "/marketplace"
 *
 * basename() alone is not enough, because files inside process/ are one
 * level deeper than the site root, so that folder is stripped off.
 */
function base_url(string $path = ''): string
{
    static $prefix = null;

    if ($prefix === null) {
        // SCRIPT_NAME is the URL path of the running script:
        //   /marketplace/index.php       -> dirname gives "/marketplace"
        //   /marketplace/process/buy.php  -> dirname gives "/marketplace/process"
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));

        $prefix = (string) preg_replace('#/(process|includes|assets|uploads)$#', '', $dir);
        $prefix = rtrim($prefix, '/');
    }

    return $prefix . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

/**
 * Send the visitor to another page and stop running this file.
 *
 * "login.php"       -> turned into /marketplace/login.php for you
 * "/marketplace/x"  -> treated as already absolute and left alone
 *
 * The second form matters because $_SESSION['redirect_to'] stores the full
 * REQUEST_URI, which is already an absolute path.
 */
function redirect(string $path): never
{
    $target = str_starts_with($path, '/') ? $path : base_url($path);
    header('Location: ' . $target);
    exit;
}

/**
 * True when the visitor has a session that points at a real account.
 *
 * This deliberately does NOT mean "isset($_SESSION['user_id'])". A session
 * can outlive its account: re-importing database.sql recreates the users
 * table with fresh ids, deleting an account removes the row, and a cookie
 * can survive a browser restart. In every one of those cases the session
 * key is still set while the user no longer exists.
 *
 * Trusting the session key alone is what let a stale session through
 * require_login() and straight into pages that filter on
 * $_SESSION['user_id'] - an id that matches nothing.
 *
 * So the check is: the key is set AND the row is really there. The row is
 * fetched once and cached, so this costs at most one query per request.
 */
function is_logged_in(): bool
{
    return current_user() !== null;
}

/**
 * The logged-in user's row from the database, or null.
 *
 * A session whose user_id no longer exists is cleaned up on the spot. The
 * "this session was stale" fact is recorded in session_stale_was(), which
 * is per-request rather than stored in the session - see the note there.
 */
function current_user(): ?array
{
    // null means "not looked up yet", which is different from "looked up
    // and there is no such user". $checked distinguishes the two so the
    // query runs once per request at most.
    static $user = null;
    static $checked = false;

    if ($checked) {
        return $user;
    }
    $checked = true;

    $id = $_SESSION['user_id'] ?? null;

    if ($id === null) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $id]);
    $user = $stmt->fetch() ?: null;

    if ($user === null) {
        // The account is gone but the session claims otherwise. Drop the
        // claim, or every later check repeats the same failed lookup and
        // any page reading $_SESSION['user_id'] acts on a dead id.
        unset($_SESSION['user_id'], $_SESSION['redirect_to']);

        session_mark_stale();
    }

    return $user;
}

/**
 * Records that this session pointed at an account that no longer exists.
 *
 * This lives in $_SESSION deliberately, not in a per-request static. The
 * detection and the explanation happen on different page loads: the home
 * page is what notices the account is gone (it clears user_id), but the
 * visitor only sees the message when they later click something that
 * calls require_login(). A per-request flag would already be gone by
 * then, and they would get a bare "Please log in first" for a session
 * that was perfectly valid a moment ago.
 *
 * session_was_stale() consumes it, so the message is shown exactly once.
 */
function session_mark_stale(): void
{
    $_SESSION['_was_stale'] = true;
}

/**
 * True if this session was found to be stale, then clears the flag.
 *
 * Reading and clearing together is what stops the message repeating on
 * every subsequent protected page.
 */
function session_was_stale(): bool
{
    if (empty($_SESSION['_was_stale'])) {
        return false;
    }
    unset($_SESSION['_was_stale']);
    return true;
}
