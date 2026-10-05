<?php
/**
 * cart.php - the shopper's basket
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';

require_login();

$page_title = 'Your cart';

$stmt = db()->prepare(
    'SELECT ci.id AS cart_id, ci.quantity,
            i.id AS item_id, i.title, i.price, i.stock, i.photo
       FROM cart_items ci
       JOIN items i ON i.id = ci.item_id
      WHERE ci.user_id = ?
      ORDER BY ci.id DESC'
);
$stmt->execute([$_SESSION['user_id']]);
$rows = $stmt->fetchAll();

$total = 0.0;
foreach ($rows as $row) {
    $total += (float) $row['price'] * (int) $row['quantity'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-head rise-1">
    <h1>Your cart</h1>
    <p><?= count($rows) ?> <?= count($rows) === 1 ? 'item' : 'items' ?> ready to check out.</p>
</div>

<?php if ($rows === []): ?>

    <div class="empty rise-2">
        <div class="empty__icon"><?= icon('shopping-cart', ['size' => 34]) ?></div>
        <h2>Your cart is empty</h2>
        <p>When you add something you like, it waits here until you check out.</p>
        <a class="btn" href="<?= e(page_url('browse.php')) ?>">
            <?= icon('search', ['size' => 17]) ?>
            <span>Browse items</span>
        </a>
    </div>

<?php else: ?>

    <div class="sheet rise-2">
        <div class="rows">
            <?php foreach ($rows as $row): ?>
                <div class="row">

                    <?php if ($row['photo'] !== null && $row['photo'] !== ''): ?>
                        <img class="row__thumb" src="<?= e(base_url('image.php?id=' . (int) $row['item_id'])) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <div class="row__thumb"></div>
                    <?php endif; ?>

                    <div class="row__main">
                        <a class="row__title" href="<?= e(page_url('item.php?id=' . (int) $row['item_id'])) ?>">
                            <?= e($row['title']) ?>
                        </a>
                        <p class="row__meta">
                            <?php if ((int) $row['stock'] === 0): ?>
                                <?= icon('circle-alert', ['size' => 13, 'class' => 'row__meta-icon']) ?>
                                <span>Sold out</span>
                            <?php elseif ((int) $row['stock'] === 1): ?>
                                <?= icon('info', ['size' => 13, 'class' => 'row__meta-icon']) ?>
                                <span>1 left</span>
                            <?php else: ?>
                                <?= icon('badge-check', ['size' => 13, 'class' => 'row__meta-icon']) ?>
                                <span><?= (int) $row['stock'] ?> available</span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="row__end">
                        <span class="price"><?= money($row['price'] * $row['quantity']) ?></span>

                        <div class="row__actions">
                            <form action="<?= e(base_url('process/update-cart.php')) ?>" method="post" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="cart_id" value="<?= (int) $row['cart_id'] ?>">
                                <input type="number" name="quantity" min="1" max="<?= (int) $row['stock'] ?>"
                                       value="<?= (int) $row['quantity'] ?>" class="qty" aria-label="Quantity">
                                <button type="submit" class="btn btn--small btn--quiet">Update</button>
                            </form>

                            <form action="<?= e(base_url('process/remove-cart.php')) ?>" method="post" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="cart_id" value="<?= (int) $row['cart_id'] ?>">
                                <button type="submit" class="btn btn--small btn--danger">
                                    <?= icon('trash-2', ['size' => 14]) ?>
                                    <span>Remove</span>
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel" style="margin-top:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <p style="font-size:15px;"><strong>Total</strong></p>
            <p class="price" style="font-size:20px;"><?= money($total) ?></p>
        </div>
        <form action="<?= e(base_url('process/checkout.php')) ?>" method="post" style="margin-top:16px;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--block">
                <?= icon('check', ['size' => 18]) ?>
                <span>Checkout</span>
            </button>
        </form>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
