<?php
/**
 * includes/header.php
 * ---------------------------------------------------------------------
 * The top of every page: the navigation bar, then opening <main>.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/icons.php';

$page_title = $page_title ?? $config['site_name'];
$flash = show_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($config['site_name']) ?></title>
    <meta name="theme-color" content="#f5f5f5">

    <!-- Nunito carries the display voice and every price.
         Inter carries the interface. If these never arrive, the
         system rounded / system sans stack below takes over. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Nunito:wght@600;700;800&display=swap">

    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" type="image/png" href="assets/images/favicon.png">

    <script>document.documentElement.classList.add('js');</script>

    <?php
    /* Randomise the faded gradient's centre on each page load, so the
       soft orange glow never covers the exact same spot twice.
       The body rule reads these via CSS variables. */
    $glow_x = random_int(15, 85);
    $glow_y = random_int(0, 40);
    ?>
    <style>
        :root {
            --glow-x: <?= $glow_x ?>%;
            --glow-y: <?= $glow_y ?>%;
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="wrap topbar__inner">

        <a class="wordmark" href="index.php"><?= e($config['site_name']) ?></a>

        <button type="button" class="nav-toggle" data-nav-toggle
                aria-expanded="false" aria-controls="main-nav" aria-label="Menu">
            <?= icon('menu', ['size' => 20]) ?>
        </button>

        <nav class="nav" id="main-nav" aria-label="Main">
            <!-- Centre zone: the browsing and shopping links. -->
            <div class="nav__group nav__group--center">
                <a class="nav__link" href="browse.php">
                    <?= icon('layout-grid', ['size' => 17]) ?>
                    <span>Browse</span>
                </a>

                <?php if (is_logged_in()): ?>
                    <a class="nav__link" href="add-item.php">
                        <?= icon('shopping-bag', ['size' => 17]) ?>
                        <span>Sell an item</span>
                    </a>
                    <a class="nav__link" href="my-items.php">
                        <?= icon('package', ['size' => 17]) ?>
                        <span>My items</span>
                    </a>
                    <a class="nav__link" href="cart.php">
                        <?= icon('shopping-cart', ['size' => 17]) ?>
                        <span>Cart</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Right zone: orders, who you are, and logging out. -->
            <div class="nav__group nav__group--end">
                <?php if (is_logged_in()): ?>
                    <a class="nav__link" href="my-orders.php">
                        <?= icon('package', ['size' => 17]) ?>
                        <span>My orders</span>
                    </a>

                    <span class="nav__who">
                        <?= icon('user', ['size' => 15]) ?>
                        <span>Hi, <?= e(first_name_of(current_user())) ?></span>
                    </span>

                    <form action="process/logout.php" method="post" class="inline-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--quiet" title="Log out">
                            <?= icon('log-out', ['size' => 17]) ?>
                            <span class="sr-only">Log out</span>
                        </button>
                    </form>
                <?php else: ?>
                    <a class="nav__link" href="login.php">
                        <?= icon('log-in', ['size' => 17]) ?>
                        <span>Log in</span>
                    </a>
                    <a class="btn btn--small" href="register.php">
                        <?= icon('user-plus', ['size' => 16]) ?>
                        <span>Create an account</span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>

    </div>
</header>

<main class="wrap">

    <?php if ($flash !== null): ?>
        <div class="toast <?= $flash['type'] === 'error' ? 'toast--error' : '' ?>"
             role="status"><?= e($flash['message']) ?></div>
    <?php endif; ?>
