<?php
/**
 * process/buy.php - the "Buy" button
 * ---------------------------------------------------------------------
 * This is the most important file in the project, because it is the only
 * place where the stock number changes.
 *
 * THE TRICK: the WHERE clause contains "AND stock >= ?".
 *
 *   UPDATE items SET stock = stock - ? WHERE id = ? AND stock >= ?
 *
 * The database does the check and the subtraction in ONE step. There is
 * no gap between "check there is enough" and "take it away", so two
 * people clicking Buy at the same instant can never buy the same last
 * item. rowCount() then tells us if we actually got it.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_post();
check_csrf();
require_login();     // nobody buys without logging in

$item_id = (int) ($_POST['item_id'] ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 1);

// A hidden field is easy to edit by hand, so never trust it blindly.
if ($quantity < 1) {
    $quantity = 1;
}

// Do we even know this item exists?
$stmt = db()->prepare('SELECT id, user_id, title, price, stock FROM items WHERE id = ? LIMIT 1');
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    flash('That item no longer exists.', 'error');
    redirect('index.php');
}

// Nobody buys their own listing. Buying it would only decrement your own
// stock and record a sale to yourself, so it is nonsense data either way.
//
// The UI already hides the Buy form for your own items, but a hidden form
// is not a rule: this check belongs here, where the request is verified.
if ((int) $item['user_id'] === (int) ($_SESSION['user_id'] ?? 0)) {
    flash('You cannot buy your own listing.', 'error');
    redirect('item.php?id=' . $item_id);
}

// If this failed, some other user already took the last one.
//
// NOTE: the same value is used twice here, so it needs TWO placeholder
// names (:take and :need). With real prepared statements MySQL refuses to
// reuse one name like ":quantity >= :quantity" and throws
// "SQLSTATE[HY093] Invalid parameter number". Two names, one value.
$stmt = db()->prepare(
    'UPDATE items
        SET stock = stock - :take
      WHERE id = :id
        AND stock >= :need'
);
$stmt->execute([
    'take' => $quantity,
    'id'   => $item_id,
    'need' => $quantity,
]);

if ($stmt->rowCount() === 0) {
    // Either it sold out, or someone tried to order more than exists.
    flash(
        'Sorry, only ' . (int) $item['stock'] . ' left for "' . $item['title'] . '".',
        'error'
    );
    redirect('item.php?id=' . $item_id);
}

// rowCount() === 1 means the update worked.
$total = (float) $item['stock'] - $quantity;   // only used for the message

// Also record the actual purchase, so "My orders" has it. The title and
// price are SNAPSHOTS: if the seller later edits or deletes the listing,
// the record of this sale keeps the values that were real at buy time.
$stmt = db()->prepare(
    'INSERT INTO purchases (order_id, user_id, item_id, title, unit_price, quantity)
     VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->execute([new_order_id(), $_SESSION['user_id'], $item_id, $item['title'], $item['price'], $quantity]);

flash(
    'Bought ' . $quantity . ' x "' . $item['title'] . '". '
    . ($total > 0 ? $total . ' still available.' : 'That was the last one!')
);

redirect('index.php');
