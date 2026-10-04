<?php
/**
 * process/add-item.php - saves the new item
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/upload.php';

require_post();
check_csrf();
require_login();

// ---- 1. Read the input ----
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$price       = $_POST['price'] ?? '';
$stock       = $_POST['stock'] ?? '';

$errors = [];
$old    = ['title' => $title, 'description' => $description, 'price' => $price, 'stock' => $stock];

// ---- 2. Validate ----
if ($title === '') {
    $errors[] = 'Please give the item a title.';
} elseif (mb_strlen($title) > 120) {
    $errors[] = 'That title is too long (120 characters max).';
}

if ($description === '') {
    $errors[] = 'Please write a short description.';
}

// is_numeric rejects "abc" but allows "12.50"
if (!is_numeric($price) || (float) $price < 0) {
    $errors[] = 'Please enter a valid price, like 45.00';
}

if (!filter_var($stock, FILTER_VALIDATE_INT) || (int) $stock < 1) {
    $errors[] = 'Stock must be a whole number of 1 or more.';
}

// ---- 3. Handle the photo ----
$photo = null;

[$photo, $photo_error] = save_photo($_FILES['photo'] ?? null);

if ($photo_error !== null) {
    $errors[] = $photo_error;
}

// ---- 4. Anything wrong? Back to the form ----
if ($errors !== []) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $old;
    flash('Please fix the problems below.', 'error');
    redirect('add-item.php');
}

// ---- 5. Save ----
// user_id comes from the SESSION, never from the form. Otherwise anyone
// could type someone else's id into a hidden field and post items as them.
$stmt = db()->prepare(
    'INSERT INTO items (user_id, title, description, price, stock, photo)
     VALUES (?, ?, ?, ?, ?, ?)'
);

$stmt->execute([
    $_SESSION['user_id'],
    $title,
    $description,
    number_format((float) $price, 2, '.', ''),   // store as 45.00, not 45
    (int) $stock,
    $photo,
]);

flash('Your item "' . $title . '" is now live!');
redirect('my-items.php');
