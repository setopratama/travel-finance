-- TRAVEL-FINANCE PRO - Database Schema

-- Clean start (Optional, but since user said install from 0)
-- DROP TABLE IF EXISTS transaction_items;
-- DROP TABLE IF EXISTS transactions;
-- DROP TABLE IF EXISTS settings;
-- DROP TABLE IF EXISTS users;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('superadmin', 'admin') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('INCOME', 'EXPENSE') NOT NULL,
    category VARCHAR(50) NOT NULL,
    ref_no VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    customer_name VARCHAR(100),
    total_amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    status ENUM('PAID', 'PENDING', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    cancel_reason TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS transaction_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    description TEXT NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY DEFAULT 1,
    company_name VARCHAR(100),
    company_address TEXT,
    logo VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    type ENUM('INCOME', 'EXPENSE') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO categories (name, type) VALUES 
('TICKET', 'INCOME'), ('HOTEL', 'INCOME'), ('TOUR', 'INCOME'),
('TICKET', 'EXPENSE'), ('HOTEL', 'EXPENSE'), ('OPERATIONAL', 'EXPENSE'), ('MARKETING', 'EXPENSE');

INSERT IGNORE INTO settings (id, company_name) VALUES (1, 'Travel Finance Pro');

-- ==========================================
-- MIGRATIONS / UPDATES AREA
-- ==========================================
-- Tambahkan perintah ALTER TABLE atau CREATE TABLE baru di bawah ini.
-- Jalankan melalui menu Settings > One-Click DB Sync.

-- Add role to users
ALTER TABLE users ADD COLUMN role ENUM('superadmin', 'admin') NOT NULL DEFAULT 'admin';

-- Add created_by to transactions
ALTER TABLE transactions ADD COLUMN created_by INT;
ALTER TABLE transactions ADD FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- Ensure first user is superadmin
UPDATE users SET role = 'superadmin' WHERE id = 1;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    type ENUM('INCOME', 'EXPENSE') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO categories (name, type) VALUES 
('TICKET', 'INCOME'), ('HOTEL', 'INCOME'), ('TOUR', 'INCOME'),
('TICKET', 'EXPENSE'), ('HOTEL', 'EXPENSE'), ('OPERATIONAL', 'EXPENSE'), ('MARKETING', 'EXPENSE');
