<?php
/**
 * add-item.php - form to post a new item for sale
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';
require_once __DIR__ . '/includes/icons.php';

// Without this line anyone could type add-item.php in the URL and post an
// item without an account. It must come before any HTML output.
require_login();

$page_title = 'Sell an item';

$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

include __DIR__ . '/includes/header.php';
?>

<div class="page-head rise-1">
    <h1>Sell an item</h1>
    <p>A clear photo and an honest description are what actually sell things.
       Keep the title short and specific.</p>
</div>

<div class="panel panel--wide rise-3">

    <?php if (!empty($errors)): ?>
        <div class="problems">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="process/add-item.php" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php include __DIR__ . '/includes/item-fields.php'; ?>
    </form>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
