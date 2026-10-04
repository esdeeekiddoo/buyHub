<?php
/**
 * includes/upload.php
 * ---------------------------------------------------------------------
 * Handles the one photo that goes with each item.
 *
 * Three rules make uploading safe:
 *
 * 1. NEVER trust the filename the visitor sent. If you keep their name,
 *    someone can upload a file called "shell.php" and run their own code
 *    on your server. We generate a random name instead.
 *
 * 2. NEVER trust the file type the browser claims. $_FILES['type'] is just
 *    text the visitor controls. We check the REAL type of the file bytes.
 *
 * 3. Check is_uploaded_file() so PHP only accepts genuine uploads, not
 *    someone guessing the temp file path.
 */

/**
 * Saves an uploaded photo.
 *
 * @param  array|null $file  One entry from $_FILES, e.g. $_FILES['photo']
 * @return array{0:?string, 1:?string} [saved filename, or error message]
 */
function save_photo(?array $file): array
{
    global $config;

    // No file chosen, or the browser sent an empty box.
    if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, 'Please choose a photo.'];
    }

    // Step 1: did PHP even receive the file properly?
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return [null, 'That photo is too large (max 3 MB).'];
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
            return [null, 'Server could not save the photo.'];
        default:
            return [null, 'Photo upload failed.'];
    }

    // Step 2: is it really an upload, and is it small enough?
    if (!is_uploaded_file($file['tmp_name'])) {
        return [null, 'That was not a valid upload.'];
    }

    if ($file['size'] <= 0 || $file['size'] > $config['max_photo_bytes']) {
        return [null, 'Photo must be smaller than 3 MB.'];
    }

    // Step 3: check the REAL contents of the file.
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    // finfo reads the file's own header, which the visitor cannot fake
    // without actually providing an image.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realType = $finfo->file($file['tmp_name']);

    if (!isset($allowed[$realType])) {
        return [null, 'Only JPG, PNG or WEBP photos are allowed.'];
    }

    // Step 4: give it a safe random name and move it into /uploads
    $folder = __DIR__ . '/../uploads';
    if (!is_dir($folder)) {
        mkdir($folder, 0775, true);
    }

    // 32 random hex characters: unguessable, and never ends in .php
    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$realType];

    if (!move_uploaded_file($file['tmp_name'], $folder . '/' . $filename)) {
        return [null, 'Could not save the photo on the server.'];
    }

    return [$filename, null];
}

/** Delete a saved photo. Called when an item or a user is deleted. */
function delete_photo(?string $filename): void
{
    if ($filename === null || $filename === '') {
        return;
    }

    // Strip anything that looks like a path escape (../../config.php)
    $filename = basename($filename);
    $path = __DIR__ . '/../uploads/' . $filename;

    if (is_file($path)) {
        unlink($path);
    }
}
