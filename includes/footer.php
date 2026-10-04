<?php
/**
 * includes/footer.php
 * ---------------------------------------------------------------------
 * Closes the page.
 */
?>
</main>

<footer class="footer">
    <div class="wrap footer__inner">

        <div class="footer__brand">
            <a class="wordmark" href="index.php"><?= e($config['site_name']) ?></a>
            <p>Second-hand things, from people near you.</p>
        </div>

        <nav class="footer__col" aria-label="Explore">
            <h3>Explore</h3>
            <a href="index.php">Home</a>
            <a href="browse.php">Browse</a>
            <a href="sell.php">Sell an item</a>
        </nav>

        <nav class="footer__col" aria-label="Account">
            <h3>Account</h3>
            <?php if (is_logged_in()): ?>
                <a href="add-item.php">List an item</a>
                <a href="my-items.php">My items</a>
            <?php else: ?>
                <a href="login.php">Log in</a>
                <a href="register.php">Create an account</a>
            <?php endif; ?>
        </nav>

    </div>

    <div class="wrap footer__bar">
        <p>&copy; <?= date('Y') ?> <?= e($config['site_name']) ?> &middot; Group Project PHP &middot; Built for a school project</p>
    </div>
</footer>

<script src="assets/app.js" defer></script>

</body>
</html>
