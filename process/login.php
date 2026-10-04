<?php
/**
 * process/login.php - checks the email and password
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_post();
check_csrf();

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// One message for BOTH "no such email" and "wrong password". If they were
// different, someone could use the login form to discover which emails
// are registered on your site.
$fail = function (string $message) use ($email) {
    $_SESSION['error'] = $message;
    $_SESSION['old_email'] = $email;
    redirect('login.php');
};

if ($email === '' || $password === '') {
    $fail('Please fill in both fields.');
}

$stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $fail('Wrong email or password.');
}

// password_verify takes the plain text the visitor typed, hashes it the
// same way, and compares it against the stored hash.
if (!password_verify($password, $user['password_hash'])) {
    $fail('Wrong email or password.');
}

login_user((int) $user['id']);

// Send them back to whatever page they were trying to reach before login.
$destination = $_SESSION['redirect_to'] ?? 'index.php';
unset($_SESSION['redirect_to']);

flash('Welcome back, ' . first_name_of($user) . '!');
redirect($destination);
