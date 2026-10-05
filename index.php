<?php
/**
 * index.php - THE HOME PAGE
 * ---------------------------------------------------------------------
 * Shows every item that is still available (stock > 0), newest first.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/icons.php';
require_once __DIR__ . '/includes/search.php';

$page_number = max(1, (int) ($_GET['page'] ?? 1));
$per_page    = 12;

$result = search_items(search_filters(), $per_page, ($page_number - 1) * $per_page);

$items = $result['items'];
$total = $result['total'];
$pages = (int) ceil($total / $per_page);

include __DIR__ . '/includes/header.php';
?>

<section class="masthead">
    <h1 class="rise-1">Second-hand things,<br>from people near you.</h1>
    <p class="rise-2">Your neighbours list what they no longer need. Buy it outright and arrange
       a handover yourself. No bidding, no waiting for a seller to reply.</p>
</section>

<!-- ------------------------------------------------------------------
     Search. A GET form, so a search is a shareable URL and the browser's
     Back button behaves. The full filter panel lives on browse.php.
     ------------------------------------------------------------------ -->
<form class="searchbar rise-3" action="browse.php" method="get" role="search">
    <div class="searchbar__field control">
        <?= icon('search', ['size' => 19, 'class' => 'control__icon']) ?>
        <label class="sr-only" for="q">Search items</label>
        <input type="search" id="q" name="q" autocomplete="off"
               placeholder="Search for a phone, a chair, a textbook">
    </div>
    <button type="submit" class="btn">Search</button>
    <a class="searchbar__filters" href="browse.php">
        <?= icon('sliders-horizontal', ['size' => 17]) ?>
        <span>Filters</span>
    </a>
</form>

<div class="tally rise-3">
    <?php if ($total === 0): ?>
        Nothing available yet.
    <?php elseif ($total === 1): ?>
        1 item available now.
    <?php else: ?>
        <?= $total ?> items available now.
    <?php endif; ?>
</div>

<?php if ($items === []): ?>

    <div class="empty rise-4">
        <div class="empty__icon"><?= icon('store', ['size' => 34]) ?></div>
        <h2>Nothing listed yet</h2>
        <p>Be the first to put something up. It takes a photo and a price.</p>
        <?php if (is_logged_in()): ?>
            <a class="btn" href="add-item.php">
                <?= icon('camera', ['size' => 18]) ?>
                <span>List an item</span>
            </a>
        <?php else: ?>
            <a class="btn" href="register.php">
                <?= icon('user-plus', ['size' => 18]) ?>
                <span>Create an account</span>
            </a>
        <?php endif; ?>
    </div>

<?php else: ?>

    <div class="rise-4">
        <div class="grid">
            <?php foreach ($items as $item): ?>
                <article class="tile">

                    <a class="tile__media" href="item.php?id=<?= (int) $item['id'] ?>"
                       tabindex="-1" aria-hidden="true">
                        <?php if (item_photo_url($item) !== null): ?>
                            <img src="<?= e(item_photo_url($item)) ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <span class="tile__photo-missing">No photo</span>
                        <?php endif; ?>
                    </a>

                    <div class="tile__body">
                        <h2 class="tile__title"><?= e($item['title']) ?></h2>

                        <?php if ($item['stock'] == 1): ?>
                            <span class="tile__note tile__note--low">Last one</span>
                        <?php elseif ($item['stock'] <= 5): ?>
                            <span class="tile__note tile__note--low">
                                <?= (int) $item['stock'] ?> left
                            </span>
                        <?php endif; ?>

                        <p class="tile__seller">
                            <?= icon('user', ['size' => 13]) ?>
                            <span><?= e(first_name_of($item)) ?></span>
                            <?php if (!empty($item['city'])): ?>
                                <span class="tile__city">&middot; <?= e($item['city']) ?></span>
                            <?php endif; ?>
                        </p>

                        <div class="tile__foot">
                            <span class="price"><?= money($item['price']) ?></span>

                            <?php if (is_logged_in()): ?>
                                <form action="process/buy.php" method="post" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn--small">
                                        <?= icon('shopping-cart', ['size' => 15]) ?>
                                        <span>Buy</span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <a class="btn btn--small btn--soft" href="login.php">
                                    <?= icon('log-in', ['size' => 15]) ?>
                                    <span>Log in</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="pager" aria-label="Pages">
            <?php if ($page_number > 1): ?>
                <a class="pager__step" href="?<?= search_query(['page' => $page_number - 1]) ?>">
                    <?= icon('chevron-left', ['size' => 16]) ?>
                    <span>Previous</span>
                </a>
            <?php else: ?>
                <span class="pager__step is-off" aria-hidden="true">
                    <?= icon('chevron-left', ['size' => 16]) ?>
                    <span>Previous</span>
                </span>
            <?php endif; ?>

            <span class="pager__status">Page <?= $page_number ?> of <?= $pages ?></span>

            <?php if ($page_number < $pages): ?>
                <a class="pager__step" href="?<?= search_query(['page' => $page_number + 1]) ?>">
                    <span>Next</span>
                    <?= icon('chevron-right', ['size' => 16]) ?>
                </a>
            <?php else: ?>
                <span class="pager__step is-off" aria-hidden="true">
                    <span>Next</span>
                    <?= icon('chevron-right', ['size' => 16]) ?>
                </span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
