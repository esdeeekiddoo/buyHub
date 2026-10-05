-- =====================================================================
--  migrate-2026.sql - upgrade an EXISTING database in place
--  ---------------------------------------------------------------------
--  This is for a database that already has your live users and items.
--  It adds the new columns and table WITHOUT dropping anything.
--
--  Run it once:
--    mysql -h HOST -P PORT -u USER -p DBNAME < migrate-2026.sql
--
--  A fresh install does NOT need this - database.sql already has it.
-- =====================================================================

USE marketplace;

-- 1. Photos are now stored as bytes, in their own table.
CREATE TABLE IF NOT EXISTS item_photos (
    item_id   INT PRIMARY KEY,
    mime      VARCHAR(50)  NOT NULL,
    data      MEDIUMBLOB   NOT NULL,
    CONSTRAINT fk_photos_item
        FOREIGN KEY (item_id) REFERENCES items (id)
        ON DELETE CASCADE
) ENGINE = InnoDB;

-- 2. Purchases are grouped into receipts by a shared order_id.
ALTER TABLE purchases
    ADD COLUMN order_id CHAR(33) DEFAULT NULL AFTER id;

CREATE INDEX idx_purchases_order ON purchases (order_id);
