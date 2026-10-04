-- reset-items.sql
-- ---------------------------------------------------------------------
-- Restores the sample listings WITHOUT touching any registered accounts.
-- Use this after you (or someone) has clicked around, bought things,
-- deleted listings, etc. Your logins survive this.
--
-- Run:
--   mysql -u root < reset-items.sql
--
-- Compare database.sql, which DELETEs everything including users.
-- ---------------------------------------------------------------------

USE marketplace;

SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM purchases;
DELETE FROM cart_items;
DELETE FROM items;
ALTER TABLE items AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO items (user_id, title, description, price, stock, photo) VALUES
(2, 'iPhone 12 128GB Blue',
    'No scratches on the screen, battery health 89%. Original box and cable included.',
    1899.00, 1, 'sample-1.svg'),
(2, 'MacBook Air M1 2020',
    '8GB RAM, 256GB SSD. Used lightly for study. Charger included, no scratches at all.',
    2450.00, 1, 'sample-2.svg'),
(1, 'Uniqlo fleece jacket, size M',
    'Worn twice, still very warm. Grey colour, size M.',
    45.00, 5, 'sample-3.svg'),
(3, 'Cambridge IELTS 17 with CD',
    'Brand new set with the CD included. Selling because I retook the test.',
    60.00, 1, 'sample-4.svg'),
(2, 'Office chairs - wholesale, 50 available',
    'I have 50 of these left over from an office clearance. Selling individually at 35 each, or all 50 for 1500. This is where the stock column earns its keep.',
    35.00, 50, 'sample-5.svg'),
(1, 'AirPods Pro 2nd generation',
    'Used for about 6 months, works perfectly, includes the charging case.',
    750.00, 2, 'sample-6.svg');
