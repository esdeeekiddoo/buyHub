<?php
/**
 * my-orders.php - the things you have purchased
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';

require_login();

$page_title = 'My orders';

$stmt = db()->prepare(
    'SELECT * FROM purchases
      WHERE user_id = ?
      ORDER BY created_at DESC, id DESC'
);
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Group lines into orders. Everything bought in one checkout shares an
// order_id; older rows (or a single Buy) fall back to their own id.
$groups = [];
foreach ($orders as $order) {
    $key = $order['order_id'] ?: 'single-' . $order['id'];
    if (!isset($groups[$key])) {
        $groups[$key] = [
            'id'    => $order['order_id'] ?: null,
            'date'  => $order['created_at'],
            'lines' => [],
            'total' => 0.0,
        ];
    }
    $groups[$key]['lines'][] = $order;
    $groups[$key]['total']  += (float) $order['unit_price'] * (int) $order['quantity'];
}

$grand_total = 0.0;
foreach ($groups as $group) {
    $grand_total += $group['total'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-head rise-1">
    <h1>My orders</h1>
    <p><?= count($groups) ?> <?= count($groups) === 1 ? 'order' : 'orders' ?>
       &middot; <?= count($orders) ?> <?= count($orders) === 1 ? 'item' : 'items' ?> bought.</p>
</div>

<?php if ($orders === []): ?>

    <div class="empty rise-2">
        <div class="empty__icon"><?= icon('package', ['size' => 34]) ?></div>
        <h2>No orders yet</h2>
        <p>When you buy or check out, your purchases show up here with the total paid.</p>
        <a class="btn" href="<?= e(page_url('browse.php')) ?>">
            <?= icon('search', ['size' => 17]) ?>
            <span>Browse items</span>
        </a>
    </div>

<?php else: ?>

    <div class="sheet rise-2">
        <div class="rows">
            <?php $receipt_index = 0; ?>
            <?php foreach ($groups as $group): ?>
                <?php $receipt_index++; ?>
                <div class="row">

                    <div class="row__thumb row__thumb--icon"><?= icon('package', ['size' => 22]) ?></div>

                    <div class="row__main">
                        <span class="row__title">
                            <?= $group['id'] !== null ? e($group['id']) : 'Order #' . $receipt_index ?>
                        </span>
                        <p class="row__meta">
                            <span><?= count($group['lines']) ?>
                                  <?= count($group['lines']) === 1 ? 'item' : 'items' ?></span>
                            <span>&middot; <?= date('M j, Y', strtotime($group['date'])) ?></span>
                        </p>
                    </div>
                    <div class="row__end">
                        <span class="price"><?= money($group['total']) ?></span>
                        <button type="button" class="btn btn--small btn--quiet"
                                data-receipt="receipt-<?= $receipt_index ?>">
                            <?= icon('receipt', ['size' => 15]) ?>
                            <span>Receipt</span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel" style="margin-top:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <p style="font-size:15px;"><strong>Total spent</strong></p>
            <p class="price" style="font-size:20px;"><?= money($grand_total) ?></p>
        </div>
    </div>

    <!-- ---------- receipts: one hidden dialog per order ---------- -->
    <?php $receipt_index = 0; ?>
    <?php foreach ($groups as $group): ?>
        <?php $receipt_index++; ?>
        <dialog class="receipt" id="receipt-<?= $receipt_index ?>">
            <div class="receipt__head">
                <div>
                    <p class="receipt__label">Receipt</p>
                    <p class="receipt__no"><?= $group['id'] !== null ? e($group['id']) : 'Order #' . $receipt_index ?></p>
                </div>
                <button type="button" class="receipt__close" aria-label="Close"
                        data-receipt-close>&times;</button>
            </div>

            <p class="receipt__date"><?= date('F j, Y g:i A', strtotime($group['date'])) ?></p>

            <table class="receipt__table">
                <thead>
                    <tr><th>Item</th><th>Qty</th><th>Price</th><th>Amount</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($group['lines'] as $line): ?>
                        <tr>
                            <td><?= e($line['title']) ?></td>
                            <td><?= (int) $line['quantity'] ?></td>
                            <td><?= money($line['unit_price']) ?></td>
                            <td><?= money((float) $line['unit_price'] * (int) $line['quantity']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Total</td>
                        <td class="price"><?= money($group['total']) ?></td>
                    </tr>
                </tfoot>
            </table>

            <p class="receipt__note">Thanks for shopping with <?= e($config['site_name']) ?>.</p>
        </dialog>
    <?php endforeach; ?>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
