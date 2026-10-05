<?php
/**
 * my-items.php - every item I posted, with Edit and Delete
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';

require_login();

$page_title = 'My items';

// "WHERE user_id = ?" is what stops you seeing anybody else's items.
// It is built from the session, not from a form field, so it cannot be
// tricked by editing the HTML.
$stmt = db()->prepare(
    'SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC, id DESC'
);
$stmt->execute([$_SESSION['user_id']]);
$my_items = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-head rise-1">
    <h1>My items</h1>
    <p><?= count($my_items) ?> <?= count($my_items) === 1 ? 'item' : 'items' ?> listed under your account.</p>
</div>

<?php if ($my_items === []): ?>

    <div class="empty rise-2">
        <div class="empty__icon"><?= icon('package', ['size' => 34]) ?></div>
        <h2>You have not listed anything yet</h2>
        <p>Put up something you no longer need. Add a photo, name a price, and it
           shows up on the home page straight away.</p>
        <a class="btn" href="<?= e(page_url('add-item.php')) ?>">
            <?= icon('camera', ['size' => 18]) ?>
            <span>List your first item</span>
        </a>
    </div>

<?php else: ?>

    <div class="sheet rise-2">
        <div class="rows">
            <?php foreach ($my_items as $item): ?>
                <div class="row">

                    <?php if ($item['photo'] !== null && $item['photo'] !== ''): ?>
                        <img class="row__thumb" src="<?= e(base_url('image.php?id=' . (int) $item['id'])) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <div class="row__thumb"></div>
                    <?php endif; ?>

                    <div class="row__main">
                        <a class="row__title" href="<?= e(page_url('item.php?id=' . (int) $item['id'])) ?>">
                            <?= e($item['title']) ?>
                        </a>
                        <p class="row__meta">
                            <?php if ($item['stock'] == 0): ?>
                                <?= icon('circle-alert', ['size' => 13, 'class' => 'row__meta-icon']) ?>
                                <span>Sold out</span>
                            <?php elseif ($item['stock'] == 1): ?>
                                <?= icon('info', ['size' => 13, 'class' => 'row__meta-icon']) ?>
                                <span>1 left</span>
                            <?php else: ?>
                                <?= icon('badge-check', ['size' => 13, 'class' => 'row__meta-icon']) ?>
                                <span><?= (int) $item['stock'] ?> available</span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="row__end">
                        <span class="price"><?= money($item['price']) ?></span>

                        <div class="row__actions">
                            <!-- Opening a form only reads, so a link is right.
                                 Deleting changes data, so it must be a POST. -->
                            <a class="btn btn--small btn--quiet"
                               href="<?= e(page_url('edit-item.php?id=' . (int) $item['id'])) ?>"
                               title="Edit this item">
                                <?= icon('pencil', ['size' => 15]) ?>
                                <span>Edit</span>
                            </a>

                            <form action="<?= e(base_url('process/delete-item.php')) ?>" method="post" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <button type="submit" class="btn btn--small btn--danger"
                                        onclick="return confirm('Delete this item? This cannot be undone.')">
                                    <?= icon('trash-2', ['size' => 15]) ?>
                                    <span>Delete</span>
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <p style="margin-top:24px;">
        <a class="btn btn--soft" href="<?= e(page_url('add-item.php')) ?>">List another item</a>
    </p>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
