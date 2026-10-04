-- =====================================================================
--  Mini Marketplace - database setup
--  ---------------------------------------------------------------------
--  HOW TO RUN THIS
--    Option A (easiest): open phpMyAdmin at http://localhost/phpmyadmin,
--        click the "Import" tab, choose this file, press Go.
--    Option B (command line):
--        mysql -u root < database.sql
--
--  Demo logins after importing (password for all three: password123)
--    aisyah@example.com
--    weiming@example.com
--    siti@example.com
-- =====================================================================

DROP DATABASE IF EXISTS marketplace;
CREATE DATABASE marketplace
  DEFAULT CHARACTER SET utf8mb4          -- utf8mb4 allows emoji
  COLLATE utf8mb4_unicode_ci;
USE marketplace;


-- ---------------------------------------------------------------------
--  users - everyone who registers or logs in
--
--  first_name + last_name instead of one "name" column, because that is
--  how people actually think about their own name, and it lets you sort
--  or search by last name later.
--
--  birth_date is a DATE, not an age. Age goes stale the day after you
--  write it down - somebody stores "18" and it is wrong a year later.
--  Store the birthday, then work the age out when you need it.
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,

    first_name    VARCHAR(40)  NOT NULL,
    last_name     VARCHAR(40)  NOT NULL,

    email         VARCHAR(190) NOT NULL,

    -- We store the HASH, never the real password. If someone gets into
    -- your database they get gibberish, not everyone's passwords.
    password_hash VARCHAR(255) NOT NULL,

    -- YYYY-MM-DD. An age check belongs at signup, not in the database.
    birth_date    DATE         NOT NULL,

    -- Optional, so these are nullable. Requiring a phone number that the
    -- app never uses is how you teach people to type rubbish.
    phone         VARCHAR(20)   DEFAULT NULL,
    gender        ENUM('female','male','other','prefer_not_to_say')
                               DEFAULT 'prefer_not_to_say',

    -- Where they are, so "near you" sorting has something to read.
    city          VARCHAR(80)   DEFAULT NULL,

    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Nobody can have two accounts with the same email.
    UNIQUE KEY uq_users_email (email),

    -- For sorting search results by surname.
    KEY idx_users_last_name (last_name)
) ENGINE = InnoDB;


-- ---------------------------------------------------------------------
--  items - everything posted for sale
-- ---------------------------------------------------------------------
CREATE TABLE items (
    id          INT AUTO_INCREMENT PRIMARY KEY,

    -- Whoever posted this item. Needed so only THEY can edit or delete it.
    user_id     INT NOT NULL,

    title       VARCHAR(120)   NOT NULL,
    description TEXT           NOT NULL,
    price       DECIMAL(10,2)  NOT NULL,

    -- How many are available. This is what lets one seller list 50 chairs
    -- instead of posting the same advert 50 times. An item with stock 0 is
    -- "sold out", and the home page query filters those out automatically.
    stock       INT            NOT NULL DEFAULT 1,

    -- Filename only, e.g. "a3f9c1b2....jpg". The file lives in /uploads.
    -- NULL means the item was posted without a photo.
    photo       VARCHAR(255)   DEFAULT NULL,

    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- if you delete this user, their items go too
    CONSTRAINT fk_items_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE = InnoDB;

-- These indexes make the home page and "My items" fast as data grows.
CREATE INDEX idx_items_user    ON items (user_id);
CREATE INDEX idx_items_created ON items (created_at);


-- ---------------------------------------------------------------------
--  cart_items - a shopper's pending basket
--
--  One row per (user, item). quantity is the amount they plan to buy;
--  the final check happens again at checkout, so a stale cart can never
--  oversell someone.
-- ---------------------------------------------------------------------
CREATE TABLE cart_items (
    id        INT AUTO_INCREMENT PRIMARY KEY,

    user_id   INT NOT NULL,
    item_id   INT NOT NULL,
    quantity  INT NOT NULL DEFAULT 1,

    CONSTRAINT fk_cart_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_cart_item
        FOREIGN KEY (item_id) REFERENCES items (id)
        ON DELETE CASCADE,

    -- Nobody keeps the same item in their cart twice - "adding" it again
    -- should add to the quantity, not make a second row.
    UNIQUE KEY uq_cart_user_item (user_id, item_id)
) ENGINE = InnoDB;


-- ---------------------------------------------------------------------
--  purchases - a completed order, "the things you purchased"
--
--  This is the receipt. title + unit_price are SNAPSHOTS copied from the
--  item at the moment of purchase, so the record is still accurate even
--  if the item is edited or deleted later (item_id goes NULL then).
-- ---------------------------------------------------------------------
CREATE TABLE purchases (
    id           INT AUTO_INCREMENT PRIMARY KEY,

    user_id      INT NOT NULL,
    item_id      INT          DEFAULT NULL,

    title        VARCHAR(120) NOT NULL,
    unit_price   DECIMAL(10,2) NOT NULL,
    quantity     INT          NOT NULL,

    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_purchases_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_purchases_item
        FOREIGN KEY (item_id) REFERENCES items (id)
        ON DELETE SET NULL
) ENGINE = InnoDB;

CREATE INDEX idx_purchases_user ON purchases (user_id);


-- =====================================================================
--  SAMPLE DATA - so the site is not empty on your first run
-- =====================================================================

-- The password for all three is password123. That hash was produced by
-- password_hash('password123', PASSWORD_DEFAULT) - you cannot work it out
-- by hand, which is the whole point of storing a hash.
INSERT INTO users
  (first_name, last_name, email, password_hash, birth_date, phone, gender, city) VALUES
('Aisyah', 'Rahman', 'aisyah@example.com',
 '$2y$10$UZyJ9aBvO5hP76aBJb.9puKDBlad.3vzZqde1qyCyEKA6xVNKLwCG',
 '2001-04-12', '0917 123 4567', 'female', 'Quezon City'),

('Wei', 'Ming', 'weiming@example.com',
 '$2y$10$UZyJ9aBvO5hP76aBJb.9puKDBlad.3vzZqde1qyCyEKA6xVNKLwCG',
 '1999-11-03', '0918 555 0142', 'male', 'Cebu City'),

('Siti', 'Nur', 'siti@example.com',
 '$2y$10$UZyJ9aBvO5hP76aBJb.9puKDBlad.3vzZqde1qyCyEKA6xVNKLwCG',
 '2003-07-25', '0995 321 7788', 'female', 'Davao City');

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
