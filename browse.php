<?php
/**
 * browse.php - search and filter the catalogue
 * ---------------------------------------------------------------------
 * A GET form, because every control here is a link-friendly state: the
 * URL can be bookmarked, shared or sent back with the browser's Back
 * button, and nothing on this page changes data.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/icons.php';
require_once __DIR__ . '/includes/search.php';

$page_title = 'Browse';

$filters = search_filters();
$page    = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 12;

$result = search_items($filters, $per_page, ($page - 1) * $per_page);
$items  = $result['items'];
$total  = $result['total'];
$pages  = (int) ceil($total / $per_page);

[$lowest, $highest] = price_bounds();
$sort_options = search_sort_options();

// The number shown on each price filter, so the visitor knows what the
// slider is about before they touch it.
$fmt = static fn ($n) => money((float) $n);

include __DIR__ . '/includes/header.php';
?>

<div class="page-head rise-1">
    <h1><?= $filters['q'] !== ''
            ? 'Results for &ldquo;' . e($filters['q']) . '&rdquo;'
            : 'Browse everything' ?></h1>
    <p class="page-head__desc">Second-hand things from people near you. Filter by price or seller, or sort to find what you are after.</p>
    <p>
        <?php if ($total === 0): ?>
            Nothing matched.
        <?php elseif ($total === 1): ?>
            1 item.
        <?php else: ?>
            <?= $total ?> items<?= search_has_filters($filters) ? ' matched' : '' ?>.
        <?php endif; ?>
    </p>
</div>

<!-- ==================================================================
     The form. method="get" so every filter is a shareable URL.

     The search row is literally the dashboard's .searchbar, so the two
     pages read as the same control. The filters sit underneath in a
     borderless strip: no boxed panel, no labels stacked above each
     field, no second full-size button.
     ================================================================== -->
<form class="filters rise-2" action="browse.php" method="get" role="search">

    <div class="searchbar">
        <div class="searchbar__field control">
            <?= icon('search', ['size' => 19, 'class' => 'control__icon']) ?>
            <label class="sr-only" for="q">Search items</label>
            <input type="search" id="q" name="q" placeholder="Search for a phone, a chair, a textbook"
                   value="<?= e($filters['q']) ?>" autocomplete="off">
        </div>

        <button type="submit" class="btn">Search</button>
    </div>

    <div class="filters__row">

        <div class="control control--select filters__ctrl">
            <label class="sr-only" for="sort">Sort by</label>
            <select id="sort" name="sort">
                <?php foreach ($sort_options as $value => $label): ?>
                    <option value="<?= e($value) ?>"
                        <?= $filters['sort'] === $value ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= icon('chevron-down', ['size' => 15, 'class' => 'control__caret']) ?>
        </div>

        <div class="control filters__ctrl">
            <label class="sr-only" for="min">Minimum price</label>
            <input type="number" id="min" name="min" min="0" step="1"
                   placeholder="Min &#8369;<?= number_format($lowest, 0) ?>"
                   value="<?= $filters['min'] > 0 ? $filters['min'] : '' ?>">
        </div>

        <div class="control filters__ctrl">
            <label class="sr-only" for="max">Maximum price</label>
            <input type="number" id="max" name="max" min="0" step="1"
                   placeholder="Max &#8369;<?= number_format($highest, 0) ?>"
                   value="<?= $filters['max'] > 0 ? $filters['max'] : '' ?>">
        </div>

        <div class="control filters__ctrl">
            <label class="sr-only" for="seller">Seller</label>
            <input type="text" id="seller" name="seller" placeholder="Seller"
                   value="<?= e($filters['seller']) ?>" autocomplete="off">
        </div>

        <div class="filters__actions">
            <button type="submit" class="btn btn--small filters__apply">Apply</button>

            <?php if (search_has_filters($filters)): ?>
                <a class="filters__clear" href="browse.php">
                    <?= icon('x', ['size' => 14]) ?>
                    <span>Clear</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</form>

<?php if ($items === []): ?>

    <div class="empty rise-3">
        <div class="empty__icon"><?= icon('search-x', ['size' => 34]) ?></div>
        <h2>Nothing matched</h2>
        <p>Try a shorter search, or widen the price range.</p>
        <?php if (search_has_filters($filters)): ?>
            <a class="btn" href="browse.php">
                <?= icon('x', ['size' => 17]) ?>
                <span>Clear all filters</span>
            </a>
        <?php else: ?>
            <a class="btn" href="index.php">Back to the home page</a>
        <?php endif; ?>
    </div>

<?php else: ?>

    <div class="rise-3">
        <div class="grid">
            <?php foreach ($items as $item): ?>
                <article class="tile">

                    <a class="tile__media" href="item.php?id=<?= (int) $item['id'] ?>"
                       tabindex="-1" aria-hidden="true">
                        <?php if (!empty($item['photo'])): ?>
                            <img src="uploads/<?= e($item['photo']) ?>" alt="" loading="lazy">
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

                            <a class="btn btn--small btn--soft"
                               href="item.php?id=<?= (int) $item['id'] ?>">View</a>
                        </div>
                    </div>

                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ---------- pagination ---------- -->
    <?php if ($pages > 1): ?>
        <nav class="pager rise-4" aria-label="Pages">
            <?php if ($page > 1): ?>
                <a class="pager__step" href="browse.php<?= search_query(['page' => $page - 1]) ?>">
                    <?= icon('chevron-left', ['size' => 16]) ?>
                    <span>Previous</span>
                </a>
            <?php else: ?>
                <span class="pager__step is-off" aria-hidden="true">
                    <?= icon('chevron-left', ['size' => 16]) ?>
                    <span>Previous</span>
                </span>
            <?php endif; ?>

            <span class="pager__status">Page <?= $page ?> of <?= $pages ?></span>

            <?php if ($page < $pages): ?>
                <a class="pager__step" href="browse.php<?= search_query(['page' => $page + 1]) ?>">
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
