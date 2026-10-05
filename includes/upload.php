<?php
/**
 * includes/upload.php
 * ---------------------------------------------------------------------
 * Handles the one photo that goes with each item.
 *
 * Photos are stored as BYTES IN THE DATABASE (in item_photos), not as
 * files in /uploads. A file on disk can disappear while the database row
 * survives (for example when a host restarts with an ephemeral
 * filesystem), which leaves a broken image. Keeping the bytes beside the
 * row they belong to means they survive together.
 *
 * Three rules make uploading safe:
 *
 * 1. NEVER trust the filename the visitor sent. We do not keep it at all.
 *
 * 2. NEVER trust the file type the browser claims. $_FILES['type'] is just
 *    text the visitor controls. We check the REAL type of the file bytes.
 *
 * 3. Check is_uploaded_file() so PHP only accepts genuine uploads, not
 *    someone guessing the temp file path.
 */

/**
 * Did the visitor actually attach a file?
 *
 * This is what lets "edit item" keep the current photo when the file box
 * is left empty, instead of demanding a re-upload.
 */
function has_upload(?array $file): bool
{
    return $file !== null
        && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

/**
 * Saves an uploaded photo, re-encoded and shrunk so listing pages stay
 * light.
 *
 * @param  array|null $file  One entry from $_FILES, e.g. $_FILES['photo']
 * @return array{0:?string, 1:?string, 2:?string} [bytes, mime, error]
 */
function save_photo(?array $file): array
{
    global $config;

    // No file chosen, or the browser sent an empty box.
    if (!has_upload($file)) {
        return [null, null, 'Please choose a photo.'];
    }

    // Step 1: did PHP even receive the file properly?
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return [null, null, 'That photo is too large (max 3 MB).'];
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
            return [null, null, 'Server could not save the photo.'];
        default:
            return [null, null, 'Photo upload failed.'];
    }

    // Step 2: is it really an upload, and is it small enough?
    if (!is_uploaded_file($file['tmp_name'])) {
        return [null, null, 'That was not a valid upload.'];
    }

    if ($file['size'] <= 0 || $file['size'] > $config['max_photo_bytes']) {
        return [null, null, 'Photo must be smaller than 3 MB.'];
    }

    // Step 3: check the REAL contents of the file.
    $allowed = [
        'image/jpeg' => true,
        'image/png'  => true,
        'image/webp' => true,
    ];

    // finfo reads the file's own header, which the visitor cannot fake
    // without actually providing an image.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realType = $finfo->file($file['tmp_name']);

    if (!isset($allowed[$realType])) {
        return [null, null, 'Only JPG, PNG or WEBP photos are allowed.'];
    }

    // Step 4: re-encode and shrink, then keep the bytes.
    [$bytes, $mime] = compress_photo($file['tmp_name'], $realType);

    if ($bytes === null || $bytes === '') {
        return [null, null, 'Could not read that photo. Please try another one.'];
    }

    return [$bytes, $mime, null];
}

/**
 * Re-encodes an uploaded image down to a sane size for the web.
 *
 * Full-resolution phone photos (4000px+) are what made the listing pages
 * slow. Capping the long edge at 1200px and re-encoding at quality 82
 * cuts a several-MB photo to a few tens of KB, with no visible difference
 * at the sizes they are actually shown.
 *
 * GD is used when available. If it is not compiled in, the original bytes
 * are stored untouched rather than failing the upload.
 *
 * @return array{0:string, 1:string} [bytes, mime]
 */
function compress_photo(string $path, string $mime): array
{
    if (!function_exists('imagecreatetruecolor')) {
        return [(string) file_get_contents($path), $mime];
    }

    $source = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png'  => @imagecreatefrompng($path),
        'image/webp' => @imagecreatefromwebp($path),
        default      => false,
    };

    if ($source === false) {
        return [(string) file_get_contents($path), $mime];
    }

    $width  = imagesx($source);
    $height = imagesy($source);

    $maxEdge = 1200;
    $scale   = min(1.0, $maxEdge / max($width, $height));
    $newW    = max(1, (int) round($width * $scale));
    $newH    = max(1, (int) round($height * $scale));

    $canvas = imagecreatetruecolor($newW, $newH);

    // Keep transparency for PNG and WEBP instead of flattening it black.
    if ($mime !== 'image/jpeg') {
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
    }

    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);

    ob_start();
    match ($mime) {
        'image/png'  => imagepng($canvas, null, 6),
        'image/webp' => imagewebp($canvas, null, 82),
        default      => imagejpeg($canvas, null, 82),
    };
    $bytes = (string) ob_get_clean();

    imagedestroy($source);
    imagedestroy($canvas);

    return [$bytes, $mime];
}

/**
 * Delete a legacy photo file left over from when photos were stored on
 * disk. New photos live in the database and are removed with the row.
 */
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
