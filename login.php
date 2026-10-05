<?php
/**
 * login.php - log in form
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/icons.php';

if (is_logged_in()) {
    redirect('index.php');
}

$page_title = 'Log in';
$error  = $_SESSION['error'] ?? null;
$old_email = $_SESSION['old_email'] ?? '';
unset($_SESSION['error'], $_SESSION['old_email']);

include __DIR__ . '/includes/header.php';
?>

<div class="auth">
    <div class="auth__pitch rise-1">
        <h1 class="auth__title">Log in</h1>
        <p class="auth__lede">Buy something, or list an item of your own.</p>
        <ul class="auth__points">
            <li><?= icon('circle-check', ['size' => 18, 'class' => 'tick']) ?>
                <span>Buy an item outright, no bidding</span></li>
            <li><?= icon('circle-check', ['size' => 18, 'class' => 'tick']) ?>
                <span>List something in about a minute</span></li>
            <li><?= icon('circle-check', ['size' => 18, 'class' => 'tick']) ?>
                <span>Arrange the handover yourself</span></li>
        </ul>
    </div>

    <div class="panel rise-2">

        <?php if ($error !== null): ?>
            <div class="problems">
                <p><?= icon('circle-alert', ['size' => 16, 'class' => 'problems__icon']) ?>
                   <span><?= e($error) ?></span></p>
            </div>
        <?php endif; ?>

        <form action="process/login.php" method="post">
            <?= csrf_field() ?>

            <div class="field">
                <label for="email">Email</label>
                <div class="control">
                    <?= icon('mail', ['size' => 18, 'class' => 'control__icon']) ?>
                    <input type="email" id="email" name="email" maxlength="190" required autofocus
                           autocomplete="email" placeholder="you@example.com"
                           value="<?= e($old_email) ?>">
                </div>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="control">
                    <?= icon('lock', ['size' => 18, 'class' => 'control__icon']) ?>
                    <input type="password" id="password" name="password" required
                           autocomplete="current-password" placeholder="Your password">
                    <button type="button" class="control__reveal"
                            data-reveal="password" aria-label="Show password"
                            aria-pressed="false">
                        <?= icon('eye', ['size' => 18, 'class' => 'reveal-on']) ?>
                        <?= icon('eye-off', ['size' => 18, 'class' => 'reveal-off']) ?>
                    </button>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--block">
                    <?= icon('log-in', ['size' => 18]) ?>
                    <span>Log in</span>
                </button>
            </div>
        </form>

        <p class="auth__switch">
            No account yet? <a href="register.php">Create one free</a>
        </p>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
