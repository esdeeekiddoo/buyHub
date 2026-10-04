<?php
/**
 * process/remove-cart.php - drops one line from the cart
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_post();
check_csrf();
require_login();

$cart_id = (int) ($_POST['cart_id'] ?? 0);

// The WHERE clause carries user_id too: you cannot delete a line that
// belongs to someone else just by guessing its id.
db()->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?')
    ->execute([$cart_id, $_SESSION['user_id']]);

flash('Removed from your cart.');
redirect('cart.php');
