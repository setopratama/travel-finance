-- TRAVEL-FINANCE PRO - Database Schema
-- Last Updated: 2026-02-24

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. Table: users
-- --------------------------------------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('superadmin', 'admin') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Table: customers
-- --------------------------------------------------------
DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Table: transactions
-- --------------------------------------------------------
DROP TABLE IF EXISTS transactions;
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('INCOME', 'EXPENSE', 'REFUND') NOT NULL,
    category VARCHAR(50) NOT NULL,
    ref_no VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    customer_name VARCHAR(100),
    total_amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    status ENUM('PAID', 'PENDING', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    payment_type ENUM('FULL', 'DP', 'REMAINDER') NOT NULL DEFAULT 'FULL',
    dp_id INT DEFAULT NULL,
    contract_amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    cancel_reason TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (dp_id) REFERENCES transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Table: transaction_items
-- --------------------------------------------------------
DROP TABLE IF EXISTS transaction_items;
CREATE TABLE transaction_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    description TEXT NOT NULL,
    qty DECIMAL(15, 2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Table: categories
-- --------------------------------------------------------
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    type ENUM('INCOME', 'EXPENSE', 'REFUND') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Table: settings
-- --------------------------------------------------------
DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
    id INT PRIMARY KEY DEFAULT 1,
    company_name VARCHAR(100),
    company_address TEXT,
    logo VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- INITIAL DATA
-- --------------------------------------------------------

-- Default Categories
INSERT INTO categories (name, type) VALUES 
('TICKET', 'INCOME'), ('HOTEL', 'INCOME'), ('TOUR', 'INCOME'),
('TICKET', 'EXPENSE'), ('HOTEL', 'EXPENSE'), ('OPERATIONAL', 'EXPENSE'), ('MARKETING', 'EXPENSE'),
('CUSTOMER REFUND', 'REFUND'), ('VENDOR REFUND', 'REFUND'), ('AIRLINE REFUND', 'REFUND'), ('HOTEL REFUND', 'REFUND');

-- Default Settings
INSERT INTO settings (id, company_name) VALUES (1, 'Travel Finance Pro');

SET FOREIGN_KEY_CHECKS = 1;
