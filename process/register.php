<?php
/**
 * process/register.php - handles the register form
 * ---------------------------------------------------------------------
 * A "process" file never shows HTML. Its whole job is:
 *   1. check the input
 *   2. either save something to the database, or
 *   3. bounce back to the form with an explanation
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_post();
check_csrf();

// ---- 1. Read and tidy the input ----
$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = $_POST['password'] ?? '';
$password2  = $_POST['password_confirm'] ?? '';
$birth_date = trim($_POST['birth_date'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$city       = trim($_POST['city'] ?? '');

// A <select> only ever sends one of its own options, but never trust that
// - if someone posts garbage, fall back to the safe default rather than
// letting it near the database.
$gender = $_POST['gender'] ?? 'prefer_not_to_say';
if (!in_array($gender, ['female', 'male', 'other', 'prefer_not_to_say'], true)) {
    $gender = 'prefer_not_to_say';
}

// Phone: keep digits, spaces and dashes only, so nobody can paste markup
// or a script into this field. Storing clean data beats escaping it later.
$phone = preg_replace('/[^0-9 +\-]/', '', $phone) ?? '';

$errors = [];

// ---- 2. Validate ----

// Names: a single letter is not a real name, and 40 characters is the
// column width, so anything longer is a paste accident.
if ($first_name === '') {
    $errors[] = 'Please enter your first name.';
} elseif (mb_strlen($first_name) < 2) {
    $errors[] = 'First name must be at least 2 characters.';
} elseif (mb_strlen($first_name) > 40) {
    $errors[] = 'First name must be 40 characters or fewer.';
} elseif (!preg_match("/^[\p{L}\p{M}'\- ]+$/u", $first_name)) {
    // Letters, marks (accents), apostrophes, hyphens and spaces only.
    $errors[] = 'First name can only contain letters, spaces, hyphens and apostrophes.';
}

if ($last_name === '') {
    $errors[] = 'Please enter your last name.';
} elseif (mb_strlen($last_name) < 2) {
    $errors[] = 'Last name must be at least 2 characters.';
} elseif (mb_strlen($last_name) > 40) {
    $errors[] = 'Last name must be 40 characters or fewer.';
} elseif (!preg_match("/^[\p{L}\p{M}'\- ]+$/u", $last_name)) {
    $errors[] = 'Last name can only contain letters, spaces, hyphens and apostrophes.';
}

if ($email === '') {
    $errors[] = 'Please enter your email.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Rejects things like "abc@" or "abc.com" (missing the @)
    $errors[] = 'That does not look like a valid email address.';
} elseif (strlen($email) > 190) {
    $errors[] = 'That email address is too long.';
}

if (strlen($password) < 6) {
    $errors[] = 'Your password must be at least 6 characters.';
}

if ($password !== $password2) {
    // Gives away nothing about the real password to someone guessing.
    $errors[] = 'The two passwords do not match.';
}

/* ---- birthday ----
   Two separate things to check, and they need different messages:
     - is it a real date at all?  (30 February is not)
     - does it make you old enough? */
$age = null;

if ($birth_date === '') {
    $errors[] = 'Please enter your date of birth.';
} else {
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $birth_date);

    // createFromFormat returns false for impossible dates, but happily
    // accepts 2026-02-31 and rolls it over to March. Comparing the
    // formatted result back to the input catches that.
    if ($parsed === false || $parsed->format('Y-m-d') !== $birth_date) {
        $errors[] = 'That date of birth is not a real date.';
    } else {
        $today = new DateTimeImmutable('today');
        if ($parsed > $today) {
            $errors[] = 'Date of birth cannot be in the future.';
        } else {
            $age = age_from($birth_date);
            if ($age === null || $age < 0) {
                $errors[] = 'Please check your date of birth.';
            } elseif ($age < 18) {
                // Never reveal whether the specific birthday exists -
                // this one reveals the child is under 18, which the person
                // submitting already knows, so nothing is leaked.
                $errors[] = 'You must be 18 or over to create an account.';
            } elseif ($age > 120) {
                $errors[] = 'Please check your date of birth.';
            }
        }
    }
}

if ($phone !== '' && !preg_match('/^[0-9 +\-]{7,20}$/', $phone)) {
    $errors[] = 'Phone number should be 7 to 20 digits, spaces or dashes only.';
}

if ($city !== '' && mb_strlen($city) > 80) {
    $errors[] = 'City name must be 80 characters or fewer.';
}

// ---- 3. Is that email already used? ----
// Only worth checking once the obvious problems are out of the way.
if ($errors === []) {
    $check = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->execute([$email]);

    if ($check->fetch()) {
        $errors[] = 'An account with that email already exists. Try logging in.';
    }
}

// ---- 4. Anything wrong? Go back to the form ----
if ($errors !== []) {
    $_SESSION['errors'] = $errors;
    // Keep what they typed so the form is not blank again.
    // Never keep passwords - that would put them in the HTML as plain text.
    $_SESSION['old'] = [
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'email'      => $email,
        'birth_date' => $birth_date,
        'phone'      => $phone,
        'city'       => $city,
        'gender'     => $gender,
    ];
    flash('Please fix the problems below.', 'error');
    redirect('register.php');
}

// ---- 5. All good, save the account ----
// password_hash turns "hello123" into something like
// "$2y$10$UZyJ9aBvO5hP76aBJb.9..." - a hash, not the password.
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = db()->prepare(
    'INSERT INTO users
        (first_name, last_name, email, password_hash, birth_date, phone, gender, city)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);

$stmt->execute([
    $first_name,
    $last_name,
    $email,
    $hash,
    $birth_date,
    $phone === '' ? null : $phone,     // empty string means "not given"
    $gender,
    $city === '' ? null : $city,
]);

$new_user_id = (int) db()->lastInsertId();

// Log the new user straight in so they do not have to type it all again.
login_user($new_user_id);

flash('Welcome, ' . $first_name . '! Your account is ready.');
redirect('index.php');
