<?php
/**
 * process/edit-item.php - saves the changes to an item
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/upload.php';

require_post();
check_csrf();
require_login();

$item_id     = (int) ($_POST['item_id'] ?? 0);
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$price       = $_POST['price'] ?? '';
$stock       = $_POST['stock'] ?? '';

// ---- Load the item ----
$stmt = db()->prepare('SELECT * FROM items WHERE id = ? LIMIT 1');
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    flash('That item does not exist.', 'error');
    redirect('my-items.php');
}

// ---- Ownership check again ----
// edit-item.php already checked, but this is the file that actually writes.
// Checking only in the form is like locking the front door and leaving the
// back door open. Check in BOTH places.
if ((int) $item['user_id'] !== (int) $_SESSION['user_id']) {
    http_response_code(403);
    flash('You can only edit your own items.', 'error');
    redirect('my-items.php');
}

// ---- Validate ----
$errors = [];
$old    = ['title' => $title, 'description' => $description, 'price' => $price, 'stock' => $stock];

if ($title === '') {
    $errors[] = 'Please give the item a title.';
}

if ($description === '') {
    $errors[] = 'Please write a short description.';
}

if (!is_numeric($price) || (float) $price < 0) {
    $errors[] = 'Please enter a valid price, like 45.00';
}

if (!filter_var($stock, FILTER_VALIDATE_INT) || (int) $stock < 0) {
    $errors[] = 'Stock must be 0 or more.';
}

// ---- Optional new photo ----
// Only touch the photo when a file was actually sent. Leaving the box
// empty keeps whatever is already there - no re-upload needed.
$new_data = null;
$new_mime = null;

if (has_upload($_FILES['photo'] ?? null)) {
    [$new_data, $new_mime, $photo_error] = save_photo($_FILES['photo'] ?? null);

    if ($photo_error !== null) {
        $errors[] = $photo_error;
    }
}

if ($errors !== []) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $old;
    flash('Please fix the problems below.', 'error');
    redirect('edit-item.php?id=' . $item_id);
}

// ---- Save ----
$data = [
    'title'       => $title,
    'description' => $description,
    'price'       => number_format((float) $price, 2, '.', ''),
    'stock'       => (int) $stock,
];

if ($new_data !== null) {
    // Mark the row as having a photo even if it never had one before.
    $data['photo'] = 'db';
}

$set = implode(', ', array_map(static fn (string $col): string => "$col = ?", array_keys($data)));

$stmt = db()->prepare("UPDATE items SET $set WHERE id = ? AND user_id = ?");
$stmt->execute(array_merge(array_values($data), [$item_id, $_SESSION['user_id']]));

// Store/replace the bytes. INSERT ... ON DUPLICATE KEY means this works
// whether the item had a photo before or not.
if ($new_data !== null && $new_mime !== null) {
    $stmt = db()->prepare(
        'INSERT INTO item_photos (item_id, mime, data)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE mime = VALUES(mime), data = VALUES(data)'
    );
    $stmt->execute([$item_id, $new_mime, $new_data]);

    // If the old photo was a file on disk (legacy sample data), remove it.
    if (!empty($item['photo']) && $item['photo'] !== 'db') {
        delete_photo($item['photo']);
    }
}

flash('Item updated.');
redirect('my-items.php');
