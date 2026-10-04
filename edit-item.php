<?php
/**
 * edit-item.php - change one of my own items
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';

require_login();

$item_id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM items WHERE id = ? LIMIT 1');
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    flash('That item does not exist any more.', 'error');
    redirect('my-items.php');
}

// OWNERSHIP CHECK - the most important line in this file.
//
// Without "AND user_id = ?", any logged-in user could change
// edit-item.php?id=5 in the URL and edit somebody else's item.
if ((int) $item['user_id'] !== (int) $_SESSION['user_id']) {
    flash('You can only edit items you listed yourself.', 'error');
    redirect('my-items.php');
}

$page_title = 'Edit item';
$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

require_once __DIR__ . '/includes/icons.php';

$allow_no_photo = true;      // tells the shared fields a photo is optional

include __DIR__ . '/includes/header.php';
?>

<div class="page-head rise-1">
    <h1>Edit item</h1>
    <p>Changes go live immediately. Buyers will see the new details straight away.</p>
</div>

<div class="panel panel--wide rise-3">

    <?php if (!empty($errors)): ?>
        <div class="problems">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="process/edit-item.php" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
        <?php include __DIR__ . '/includes/item-fields.php'; ?>
    </form>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
