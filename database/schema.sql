-- =========================================================
-- FRESH SCENTS - Mfumo wa Kuuzia Manukato (Perfume Shop System)
-- Database Schema (MySQL / MariaDB)
-- =========================================================

CREATE DATABASE IF NOT EXISTS fresh_scents_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE fresh_scents_db;

-- ---------------------------------------------------------
-- 1. WATUMISHI WA MFUMO (ADMINS)
-- ---------------------------------------------------------
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(120) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    security_question VARCHAR(255) NOT NULL,
    security_answer_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 2. WATEJA (CUSTOMERS) - hiari kuwa na akaunti
-- ---------------------------------------------------------
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(120) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    security_question VARCHAR(255) NOT NULL,
    security_answer_hash VARCHAR(255) NOT NULL,
    address VARCHAR(255) DEFAULT NULL,
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 3. AINA ZA BIDHAA (CATEGORIES)
-- ---------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO categories (name) VALUES ('Wanaume'),('Wanawake'),('Unisex'),('Body Spray'),('Mafuta ya Kunukia');

-- ---------------------------------------------------------
-- 4. BIDHAA (PRODUCTS / PERFUMES)
-- ---------------------------------------------------------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    brand VARCHAR(100) DEFAULT NULL,
    description TEXT,
    category_id INT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 4b. UKUBWA (ML) NA BEI KWA KILA BIDHAA (PRODUCT VARIANTS)
--     Bidhaa moja (mfano "Fresh Scents Royal Oud") inaweza kuwa
--     na ML na bei nyingi tofauti (mfano 30ml, 50ml, 100ml).
-- ---------------------------------------------------------
CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    ml INT NOT NULL COMMENT 'Ujazo kwa mililita',
    price DECIMAL(12,2) NOT NULL,
    cost_price DECIMAL(12,2) DEFAULT 0,
    stock_qty INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 5. MAAGIZO / MAOMBI YA WATEJA (ORDERS / REQUESTS)
-- ---------------------------------------------------------
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(20) NOT NULL UNIQUE,
    customer_id INT DEFAULT NULL,
    guest_name VARCHAR(120) DEFAULT NULL,
    guest_phone VARCHAR(30) DEFAULT NULL,
    guest_address VARCHAR(255) DEFAULT NULL,
    delivery_type ENUM('pickup','delivery') NOT NULL DEFAULT 'pickup',
    source ENUM('online','duka') NOT NULL DEFAULT 'online' COMMENT 'online = kupitia tovuti, duka = mteja alikuja dukani moja kwa moja',
    served_by INT DEFAULT NULL COMMENT 'Admin aliyemuuza mteja wa dukani',
    status ENUM('pending','confirmed','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
    payment_status ENUM('not_required','unpaid','pending_confirmation','paid') NOT NULL DEFAULT 'not_required',
    payment_name VARCHAR(120) DEFAULT NULL COMMENT 'Jina lililotumika kulipia',
    payment_transaction_id VARCHAR(100) DEFAULT NULL COMMENT 'Namba ya muamala',
    payment_phone VARCHAR(30) DEFAULT NULL,
    payment_submitted_at DATETIME DEFAULT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes TEXT,
    admin_notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (served_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    variant_id INT DEFAULT NULL,
    product_name VARCHAR(150) NOT NULL,
    ml INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 6. WAFANYAKAZI (EMPLOYEES) NA MALIPO (PAYROLL)
-- ---------------------------------------------------------
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    position VARCHAR(80) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    salary DECIMAL(12,2) NOT NULL DEFAULT 0,
    hired_date DATE DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE salary_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    pay_month VARCHAR(20) NOT NULL COMMENT 'Mfano: Agosti 2026',
    pay_date DATE NOT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    paid_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (paid_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 7. MATUMIZI (EXPENSES)
-- ---------------------------------------------------------
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(80) DEFAULT NULL,
    amount DECIMAL(12,2) NOT NULL,
    expense_date DATE NOT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 8. BIDHAA ZINAZOINGIA STOO (STOCK IN)
-- ---------------------------------------------------------
CREATE TABLE stock_ins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variant_id INT NOT NULL,
    quantity INT NOT NULL,
    cost_price DECIMAL(12,2) DEFAULT 0,
    supplier VARCHAR(150) DEFAULT NULL,
    stock_date DATE NOT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    added_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 9. MIPANGILIO YA DUKA (SETTINGS)
-- ---------------------------------------------------------
CREATE TABLE settings (
    setting_key VARCHAR(60) PRIMARY KEY,
    setting_value TEXT
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
('shop_name', 'FRESH SCENTS'),
('shop_tagline', 'Harufu Nzuri, Hadhi Ya Kifalme'),
('payment_phone', '0621002091'),
('payment_name', 'YAHYA JUMA IS-HAKA'),
('shop_phone', '0621002091'),
('shop_address', 'Mbeya, Tanzania'),
('currency', 'TZS'),
('installed', '0');

-- ---------------------------------------------------------
-- 10. LOGS ZA KUINGIA (LOGIN AUDIT - hiari, kwa usalama)
-- ---------------------------------------------------------
CREATE TABLE login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin','customer') NOT NULL,
    user_id INT NOT NULL,
    ip_address VARCHAR(60) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SAMPLE PRODUCTS (mifano ya bidhaa - unaweza kufuta)
-- Kila bidhaa ina ukubwa (ML) na bei zaidi ya moja kama mfano.
-- ---------------------------------------------------------
INSERT INTO products (name, brand, description, category_id, image, status, is_featured) VALUES
('Fresh Scents Royal Oud', 'Fresh Scents', 'Harufu ya kifalme yenye mchanganyiko wa oud na vanilla, hukaa muda mrefu.', 1, NULL, 'active', 1),
('Fresh Scents Blue Ice', 'Fresh Scents', 'Harufu safi na yenye baridi, nzuri kwa matumizi ya kila siku.', 1, NULL, 'active', 1),
('Fresh Scents Rose Elegance', 'Fresh Scents', 'Harufu ya kike yenye mchanganyiko wa rose na jasmine.', 2, NULL, 'active', 1),
('Fresh Scents Golden Amber', 'Fresh Scents', 'Harufu ya kudumu yenye amber na musk, kwa wanaume na wanawake.', 3, NULL, 'active', 0),
('Fresh Scents Ocean Breeze', 'Fresh Scents', 'Harufu nyepesi ya baharini, inayofaa kwa mchana.', 1, NULL, 'active', 0),
('Fresh Scents Diamond Noir', 'Fresh Scents', 'Harufu nzito na ya kuvutia kwa jioni.', 1, NULL, 'active', 1);

INSERT INTO product_variants (product_id, ml, price, stock_qty) VALUES
(1, 30, 22000, 20), (1, 50, 32000, 18), (1, 100, 45000, 25),
(2, 50, 28000, 30), (2, 100, 38000, 14),
(3, 30, 24000, 22), (3, 75, 38000, 20),
(4, 100, 42000, 15),
(5, 60, 25000, 40),
(6, 50, 34000, 12), (6, 100, 50000, 10);
