<?php
/**
 * process/checkout.php - turns the whole cart into purchases
 *
 * This is where cart rows become real orders. The rule that matters:
 * every cart line is checked against CURRENT stock before it ships. If a
 * seller took something back or two buyers race for the last one, the
 * loser does not silently oversell - they get a clear message.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_post();
check_csrf();
require_login();

$stmt = db()->prepare(
    'SELECT ci.id AS cart_id, ci.quantity,
            i.id  AS item_id, i.title, i.price, i.stock
       FROM cart_items ci
       JOIN items i ON i.id = ci.item_id
      WHERE ci.user_id = ?
      ORDER BY ci.id'
);
$stmt->execute([$_SESSION['user_id']]);
$rows = $stmt->fetchAll();

if ($rows === []) {
    flash('Your cart is empty.');
    redirect('browse.php');
}

$purchased = 0;
$blocked   = [];
$order_id  = new_order_id();   // one receipt for this whole checkout

foreach ($rows as $row) {
    $quantity = min((int) $row['quantity'], (int) $row['stock']);

    if ($quantity < 1) {
        $blocked[] = $row['title'];
        continue;
    }

    // The same UPDATE ... WHERE stock >= pattern as process/buy.php: the
    // database does the stock check and the decrement in ONE step, so two
    // people checking out at once cannot buy the same last item.
    $stmt = db()->prepare(
        'UPDATE items SET stock = stock - ? WHERE id = ? AND stock >= ?'
    );
    $stmt->execute([$quantity, $row['item_id'], $quantity]);

    if ($stmt->rowCount() === 0) {
        $blocked[] = $row['title'];
        continue;
    }

    // Record the sale as a purchase, then clear it from the cart.
    $stmt = db()->prepare(
        'INSERT INTO purchases (order_id, user_id, item_id, title, unit_price, quantity)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$order_id, $_SESSION['user_id'], $row['item_id'], $row['title'], $row['price'], $quantity]);

    $stmt = db()->prepare('DELETE FROM cart_items WHERE id = ?');
    $stmt->execute([$row['cart_id']]);

    $purchased++;
}

if ($purchased > 0 && $blocked === []) {
    flash('Order placed! ' . $purchased . ($purchased === 1 ? ' item' : ' items') . ' checked out.');
    redirect('my-orders.php');
}

if ($purchased > 0) {
    flash('Checkout finished, but these could not be filled: ' . implode(', ', $blocked) . '.', 'error');
    redirect('my-orders.php');
}

// Nothing shipped.
flash('Nothing could be checked out. These are out of stock: ' . implode(', ', $blocked) . '.', 'error');
redirect('cart.php');
