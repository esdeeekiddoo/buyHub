<?php
/**
 * process/add-cart.php - adds an item to the shopper's cart
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_post();
check_csrf();
require_login();

$item_id  = (int) ($_POST['item_id'] ?? 0);
$quantity = max(1, (int) ($_POST['quantity'] ?? 1));

$stmt = db()->prepare('SELECT id, user_id, title, stock FROM items WHERE id = ? LIMIT 1');
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    flash('That item no longer exists.', 'error');
    redirect('index.php');
}

if ((int) $item['user_id'] === (int) $_SESSION['user_id']) {
    flash('You cannot add your own listing to your cart.', 'error');
    redirect('item.php?id=' . $item_id);
}

if ((int) $item['stock'] < 1) {
    flash('That item is sold out.', 'error');
    redirect('item.php?id=' . $item_id);
}

$quantity = min($quantity, (int) $item['stock']);

// INSERT ... ON DUPLICATE KEY UPDATE merges into the existing row instead
// of making a second line. The LEAST cap means adding two this week and
// three next week can never hold more than what is actually in stock.
$stmt = db()->prepare(
    'INSERT INTO cart_items (user_id, item_id, quantity)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)'
);
$stmt->execute([$_SESSION['user_id'], $item_id, $quantity, $item['stock']]);

flash('Added to your cart.');
redirect('cart.php');
