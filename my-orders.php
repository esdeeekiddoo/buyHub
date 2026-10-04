<?php
/**
 * my-orders.php - the things you have purchased
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/icons.php';

require_login();

$page_title = 'My orders';

$stmt = db()->prepare(
    'SELECT * FROM purchases
      WHERE user_id = ?
      ORDER BY created_at DESC, id DESC'
);
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-head rise-1">
    <h1>My orders</h1>
    <p><?= count($orders) ?> <?= count($orders) === 1 ? 'thing' : 'things' ?> you have bought.</p>
</div>

<?php if ($orders === []): ?>

    <div class="empty rise-2">
        <div class="empty__icon"><?= icon('package', ['size' => 34]) ?></div>
        <h2>No orders yet</h2>
        <p>When you buy or check out, your purchases show up here with the total paid.</p>
        <a class="btn" href="browse.php">
            <?= icon('search', ['size' => 17]) ?>
            <span>Browse items</span>
        </a>
    </div>

<?php else: ?>

    <div class="sheet rise-2">
        <div class="rows">
            <?php foreach ($orders as $order): ?>
                <div class="row">

                    <div class="row__thumb row__thumb--icon"><?= icon('package', ['size' => 22]) ?></div>

                    <div class="row__main">
                        <?php if ($order['item_id'] !== null): ?>
                            <a class="row__title" href="item.php?id=<?= (int) $order['item_id'] ?>">
                                <?= e($order['title']) ?>
                            </a>
                        <?php else: ?>
                            <span class="row__title"><?= e($order['title']) ?></span>
                        <?php endif; ?>
                        <p class="row__meta">
                            <span><?= (int) $order['quantity'] ?> × <?= money($order['unit_price']) ?> each</span>
                            <span>&middot; <?= date('M j, Y', strtotime($order['created_at'])) ?></span>
                        </p>
                    </div>
                    <div class="row__end">
                        <span class="price"><?= money($order['unit_price'] * $order['quantity']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
