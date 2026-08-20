-- =========================================================
-- FRESH SCENTS — MIGRATION: Ongeza Ukubwa (ML) na Bei Nyingi kwa Bidhaa Moja
-- Endesha SQL hii MARA MOJA TU kwenye database yako iliyopo (fresh_scents_db)
-- kupitia phpMyAdmin -> SQL tab -> bandika yote -> Go.
-- =========================================================

USE fresh_scents_db;

-- 1. Jedwali jipya la "variants" — kila bidhaa inaweza kuwa na ML/Bei nyingi
CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    ml INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    cost_price DECIMAL(12,2) DEFAULT 0,
    stock_qty INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 2. Hamisha ML/Bei/Stock zilizopo za kila bidhaa kuwa "variant" yake ya kwanza
INSERT INTO product_variants (product_id, ml, price, cost_price, stock_qty)
SELECT id, ml, price, COALESCE(cost_price,0), stock_qty FROM products;

-- 3. Ongeza variant_id kwenye order_items (kujua ni ukubwa upi ulinunuliwa)
ALTER TABLE order_items ADD COLUMN variant_id INT NULL AFTER product_id;

-- 4. Ongeza variant_id kwenye stock_ins (stock sasa inaongezwa kwa variant maalum)
ALTER TABLE stock_ins ADD COLUMN variant_id INT NULL AFTER product_id;
UPDATE stock_ins si
JOIN product_variants pv ON pv.product_id = si.product_id
SET si.variant_id = pv.id
WHERE si.variant_id IS NULL;

-- 5. Ondoa safu za zamani kwenye products (sasa zinasimamiwa na product_variants)
ALTER TABLE products DROP COLUMN ml;
ALTER TABLE products DROP COLUMN price;
ALTER TABLE products DROP COLUMN cost_price;
ALTER TABLE products DROP COLUMN stock_qty;

-- IMEKAMILIKA! Sasa fungua Admin Panel -> Bidhaa, utaona kila bidhaa
-- inaweza kuwa na ML na Bei nyingi (mfano 30ml, 50ml, 100ml, kila moja na bei yake).
