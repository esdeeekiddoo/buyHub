<?php
/**
 * process/update-cart.php - changes the quantity of one cart line
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_post();
check_csrf();
require_login();

$cart_id  = (int) ($_POST['cart_id']  ?? 0);
$quantity = max(1, (int) ($_POST['quantity'] ?? 1));

// Cap the new quantity at what is actually in stock, in the same step.
$stmt = db()->prepare(
    'UPDATE cart_items c
       JOIN items i ON i.id = c.item_id
      SET c.quantity = LEAST(?, i.stock)
     WHERE c.id = ? AND c.user_id = ?'
);
$stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);

flash('Cart updated.');
redirect('cart.php');
