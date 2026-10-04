<?php
/**
 * item.php - one item's full page
 * ---------------------------------------------------------------------
 * The id arrives in the URL:  item.php?id=3
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/icons.php';

// Cast to (int): item.php?id=abc becomes 0, which matches nothing,
// instead of throwing a database error.
$item_id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT items.*, users.first_name, users.last_name, users.city, users.birth_date
       FROM items
       JOIN users ON users.id = items.user_id
      WHERE items.id = ?
      LIMIT 1'
);
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    $page_title = 'Item not found';
    include __DIR__ . '/includes/header.php';
    echo '<div class="empty" style="margin-block:40px;">'
       . '<h2>That item is gone</h2>'
       . '<p>It may have been sold or taken down by its owner.</p>'
       . '<a class="btn" href="index.php">See what else is available</a>'
       . '</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = $item['title'];
include __DIR__ . '/includes/header.php';
?>

<div class="sheet">
    <div class="detail">

        <div class="detail__media">
            <?php if (!empty($item['photo'])): ?>
                <img src="uploads/<?= e($item['photo']) ?>" alt="<?= e($item['title']) ?>">
            <?php else: ?>
                <span class="tile__photo-missing">No photo</span>
            <?php endif; ?>
        </div>

        <div>
            <h1 class="detail__title"><?= e($item['title']) ?></h1>

            <p class="price price--large price--shimmer detail__price"><?= money($item['price']) ?></p>

            <?php if ($item['stock'] == 0): ?>
                <p class="status status--gone">Sold out. This one is no longer available.</p>
            <?php elseif ($item['stock'] == 1): ?>
                <p class="status status--low">Only 1 left.</p>
            <?php else: ?>
                <p class="status"><?= (int) $item['stock'] ?> available.</p>
            <?php endif; ?>

            <div class="seller">
                <div class="seller__avatar" aria-hidden="true">
                    <?= e(initials_of($item)) ?>
                </div>
                <div>
                    <p class="seller__name"><?= e(full_name($item)) ?></p>
                    <p class="seller__meta">
                        <?php if (!empty($item['city'])): ?>
                            Listed in <?= e($item['city']) ?> on
                        <?php else: ?>
                            Listed on
                        <?php endif; ?>
                        <?= e(date('j M Y', strtotime($item['created_at']))) ?>
                        <?php $sellerAge = age_from($item['birth_date'] ?? null); ?>
                        <?php if ($sellerAge !== null): ?>
                            &middot; age <?= (int) $sellerAge ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <p class="detail__desc"><?= e($item['description']) ?></p>

            <div class="detail__action">
                <?php if ($item['stock'] == 0): ?>

                    <a class="btn btn--block btn--quiet" href="index.php">See other items</a>

                <?php elseif (is_logged_in() && (int) $item['user_id'] === (int) $_SESSION['user_id']): ?>

                    <a class="btn btn--block btn--quiet" href="edit-item.php?id=<?= (int) $item['id'] ?>">
                        <?= icon('pencil', ['size' => 18]) ?>
                        <span>This is your listing</span>
                    </a>

                <?php elseif (is_logged_in()): ?>

                    <form action="process/buy.php" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">

                        <div class="stepper" role="group" aria-label="How many">
                            <button type="button" data-step="-1" aria-label="One fewer">&minus;</button>
                            <input type="number" id="quantity" name="quantity" value="1"
                                   min="1" max="<?= (int) $item['stock'] ?>"
                                   aria-label="Quantity">
                            <button type="button" data-step="1" aria-label="One more">+</button>
                        </div>
                        <p class="stepper__max">Up to <?= (int) $item['stock'] ?> available.</p>

                        <button type="submit" class="btn btn--block">
                            <?= icon('shopping-cart', ['size' => 18]) ?>
                            <span>Buy now</span>
                        </button>

                        <!-- Same quantity, a different destination: formaction
                             on the button overrides the form's action. -->
                        <button type="submit" class="btn btn--block btn--quiet"
                                formaction="process/add-cart.php">
                            <?= icon('shopping-bag', ['size' => 18]) ?>
                            <span>Add to cart</span>
                        </button>
                    </form>

                <?php else: ?>

                    <a class="btn btn--block" href="login.php">
                        <?= icon('log-in', ['size' => 18]) ?>
                        <span>Log in to buy this</span>
                    </a>

                <?php endif; ?>
            </div>

            <a class="backlink" href="index.php">Back to all items</a>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
