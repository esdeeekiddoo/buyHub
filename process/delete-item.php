<?php
/**
 * process/delete-item.php - remove one of my items
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/upload.php';

require_post();
check_csrf();
require_login();

$item_id = (int) ($_POST['item_id'] ?? 0);

// ---- Load it first, so we know the photo filename before it is gone ----
$stmt = db()->prepare('SELECT * FROM items WHERE id = ? LIMIT 1');
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    flash('That item was already deleted.', 'error');
    redirect('my-items.php');
}

// ---- Ownership check ----
if ((int) $item['user_id'] !== (int) $_SESSION['user_id']) {
    http_response_code(403);
    flash('You can only delete your own items.', 'error');
    redirect('my-items.php');
}

// ---- Delete the row ----
// user_id is in the WHERE clause as well, so the delete is safe even if
// somebody changed the id between the SELECT and here.
$stmt = db()->prepare('DELETE FROM items WHERE id = ? AND user_id = ?');
$stmt->execute([$item_id, $_SESSION['user_id']]);

// ---- Then clean up the photo file ----
// The database stores only the filename, so the picture on disk is left
// behind unless we remove it here. That is what "orphaned files" are.
delete_photo($item['photo']);

flash('Item deleted.');
redirect('my-items.php');
