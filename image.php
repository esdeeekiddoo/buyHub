<?php
/**
 * image.php - serves one item's photo
 * ---------------------------------------------------------------------
 * Photos are stored in the database (see includes/upload.php), so this
 * script is what turns an item id back into image bytes:
 *
 *     <img src="image.php?id=12">
 *
 * It also serves the old file-based photos in /uploads, so the sample
 * data keeps working without anything special.
 */

require_once __DIR__ . '/includes/db.php';

$item_id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT i.photo, p.mime, p.data
       FROM items i
       LEFT JOIN item_photos p ON p.item_id = i.id
      WHERE i.id = ?
      LIMIT 1'
);
$stmt->execute([$item_id]);
$item = $stmt->fetch();

// A long cache is safe: the URL is per item, and replacing the photo
// changes what this same URL returns only on that one item.
$cache = 'public, max-age=604800';

if (!$item) {
    http_response_code(404);
    exit;
}

// --- Database-stored photo ---
if (!empty($item['data'])) {
    $mime = $item['mime'] ?: 'application/octet-stream';

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . strlen($item['data']));
    header('Cache-Control: ' . $cache);
    echo $item['data'];
    exit;
}

// --- Legacy file-based photo (sample data) ---
if (!empty($item['photo']) && $item['photo'] !== 'db') {
    $file = basename($item['photo']);                  // no path escapes
    $path = __DIR__ . '/uploads/' . $file;

    if (is_file($path)) {
        header('Content-Type: ' . (mime_content_type($path) ?: 'image/png'));
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: ' . $cache);
        readfile($path);
        exit;
    }
}

// Nothing to show.
http_response_code(404);
exit;
