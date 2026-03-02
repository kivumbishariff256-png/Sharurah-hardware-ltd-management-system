-- Sharurah Hardware Ltd Management System Database Schema
-- Created for: Sharurah Hardware Ltd Uganda
-- Database: sharurah_hardware_db

-- Create Database
CREATE DATABASE IF NOT EXISTS sharurah_hardware_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sharurah_hardware_db;

-- ============================================
-- 1. USER AUTHENTICATION TABLES
-- ============================================

-- Users table for login system
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20),
    role ENUM('admin', 'manager', 'cashier', 'accountant', 'inventory_manager') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- User sessions for login tracking
CREATE TABLE user_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id)
);

-- ============================================
-- 2. INVENTORY MANAGEMENT TABLES
-- ============================================

-- Product categories
CREATE TABLE product_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_category_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_category_id) REFERENCES product_categories(category_id) ON DELETE SET NULL,
    INDEX idx_category_name (category_name)
);

-- Products table
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    category_id INT NOT NULL,
    description TEXT,
    unit_price DECIMAL(15, 2) NOT NULL,
    cost_price DECIMAL(15, 2) NOT NULL,
    quantity_in_stock INT DEFAULT 0,
    reorder_level INT DEFAULT 10,
    unit_of_measure VARCHAR(20) DEFAULT 'pcs',
    brand VARCHAR(100),
    supplier_id INT NULL,
    barcode VARCHAR(100) UNIQUE,
    image_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(category_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL,
    INDEX idx_product_code (product_code),
    INDEX idx_product_name (product_name),
    INDEX idx_category (category_id),
    INDEX idx_barcode (barcode)
);

-- Suppliers table
CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    phone_number VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Uganda',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supplier_name (supplier_name)
);

-- Stock movements (in/out)
CREATE TABLE stock_movements (
    movement_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    movement_type ENUM('purchase', 'sale', 'return', 'adjustment', 'transfer') NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(15, 2),
    reference_number VARCHAR(100),
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_product_id (product_id),
    INDEX idx_movement_type (movement_type),
    INDEX idx_created_at (created_at)
);

-- ============================================
-- 3. CUSTOMER MANAGEMENT TABLES
-- ============================================

-- Customers table
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Uganda',
    customer_type ENUM('retail', 'wholesale', 'corporate') DEFAULT 'retail',
    credit_limit DECIMAL(15, 2) DEFAULT 0,
    current_balance DECIMAL(15, 2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_code (customer_code),
    INDEX idx_phone_number (phone_number),
    INDEX idx_customer_type (customer_type)
);

-- ============================================
-- 4. SALES MANAGEMENT TABLES
-- ============================================

-- Sales orders
CREATE TABLE sales_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(15, 2) NOT NULL,
    discount_amount DECIMAL(15, 2) DEFAULT 0,
    tax_amount DECIMAL(15, 2) DEFAULT 0,
    total_amount DECIMAL(15, 2) NOT NULL,
    payment_status ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
    order_status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'credit') DEFAULT 'cash',
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_order_number (order_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_order_date (order_date),
    INDEX idx_payment_status (payment_status)
);

-- Sales order items
CREATE TABLE sales_order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(15, 2) NOT NULL,
    discount_percent DECIMAL(5, 2) DEFAULT 0,
    total_price DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES sales_orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id)
);

-- ============================================
-- 5. PAYMENT PROCESSING TABLES
-- ============================================

-- Payments table
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(50) UNIQUE NOT NULL,
    order_id INT NULL,
    customer_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    payment_method ENUM('cash', 'mtn_mobile_money', 'airtel_money', 'bank_transfer') NOT NULL,
    payment_status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_reference VARCHAR(100),
    phone_number VARCHAR(20),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES sales_orders(order_id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (processed_by) REFERENCES users(user_id),
    INDEX idx_payment_number (payment_number),
    INDEX idx_order_id (order_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_payment_date (payment_date)
);

-- Mobile money transactions
CREATE TABLE mobile_money_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    provider ENUM('mtn', 'airtel') NOT NULL,
    transaction_reference VARCHAR(100) UNIQUE NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    transaction_status ENUM('initiated', 'pending', 'successful', 'failed', 'cancelled') DEFAULT 'initiated',
    response_code VARCHAR(50),
    response_message TEXT,
    callback_received BOOLEAN DEFAULT FALSE,
    callback_data TEXT,
    initiated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE CASCADE,
    INDEX idx_transaction_reference (transaction_reference),
    INDEX idx_provider (provider),
    INDEX idx_phone_number (phone_number),
    INDEX idx_transaction_status (transaction_status)
);

-- ============================================
-- 6. ACCOUNTING TABLES
-- ============================================

-- Chart of accounts
CREATE TABLE chart_of_accounts (
    account_id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(200) NOT NULL,
    account_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
    parent_account_id INT NULL,
    opening_balance DECIMAL(15, 2) DEFAULT 0,
    current_balance DECIMAL(15, 2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_account_id) REFERENCES chart_of_accounts(account_id) ON DELETE SET NULL,
    INDEX idx_account_code (account_code),
    INDEX idx_account_type (account_type)
);

-- Journal entries
CREATE TABLE journal_entries (
    entry_id INT AUTO_INCREMENT PRIMARY KEY,
    entry_number VARCHAR(50) UNIQUE NOT NULL,
    entry_date DATE NOT NULL,
    reference_number VARCHAR(100),
    description TEXT,
    entry_type ENUM('sales', 'purchase', 'payment', 'receipt', 'adjustment', 'general') NOT NULL,
    status ENUM('draft', 'posted', 'reversed') DEFAULT 'draft',
    total_debit DECIMAL(15, 2) DEFAULT 0,
    total_credit DECIMAL(15, 2) DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    posted_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_entry_number (entry_number),
    INDEX idx_entry_date (entry_date),
    INDEX idx_entry_type (entry_type),
    INDEX idx_status (status)
);

-- Journal entry lines
CREATE TABLE journal_entry_lines (
    line_id INT AUTO_INCREMENT PRIMARY KEY,
    entry_id INT NOT NULL,
    account_id INT NOT NULL,
    description TEXT,
    debit_amount DECIMAL(15, 2) DEFAULT 0,
    credit_amount DECIMAL(15, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (entry_id) REFERENCES journal_entries(entry_id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(account_id),
    INDEX idx_entry_id (entry_id),
    INDEX idx_account_id (account_id)
);

-- Expenses
CREATE TABLE expenses (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    expense_number VARCHAR(50) UNIQUE NOT NULL,
    expense_date DATE NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    amount DECIMAL(15, 2) NOT NULL,
    payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'credit') NOT NULL,
    reference_number VARCHAR(100),
    receipt_image_url VARCHAR(500),
    approved_by INT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_expense_number (expense_number),
    INDEX idx_expense_date (expense_date),
    INDEX idx_category (category),
    INDEX idx_status (status)
);

-- ============================================
-- 7. SYSTEM SETTINGS & LOGS
-- ============================================

-- System settings
CREATE TABLE system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Activity logs
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_module (module),
    INDEX idx_created_at (created_at)
);

-- ============================================
-- INITIAL DATA INSERTION
-- ============================================

-- Insert default admin user (password: admin123 - should be changed)
INSERT INTO users (username, password_hash, email, full_name, phone_number, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@sharurahhardware.ug', 'System Administrator', '0773586844', 'admin'),
('manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager@sharurahhardware.ug', 'Store Manager', '0704467880', 'manager');

-- Insert product categories
INSERT INTO product_categories (category_name, description) VALUES
('Laps', 'Various types of laps'),
('Mixer', 'Kitchen and construction mixers'),
('Toilet', 'Toilet fixtures and accessories'),
('Basins', 'Wash basins and sinks'),
('Bathroom Cabinet', 'Storage cabinets for bathrooms'),
('Sinks', 'Kitchen and bathroom sinks'),
('Pedestal Basins', 'Basins with pedestal stands'),
('Shataf', 'Muslim shower fixtures'),
('Shower Mixer', 'Shower mixing valves'),
('Squatting Toilet Pan', 'Squatting type toilets'),
('Soap Dish', 'Soap holders and dishes'),
('Tooth Brush Holder', 'Tooth brush storage'),
('TP Holder', 'Toilet paper holders'),
('Instant Water Heater', 'Instant water heating systems'),
('Concealed Toilet', 'Concealed cistern toilets'),
('Angle Value', 'Plumbing angle valves'),
('Mirror', 'Bathroom and vanity mirrors'),
('PPR Machine', 'PPR pipe welding machines'),
('Toilet Seat', 'Replacement toilet seats'),
('Wall Mount', 'Wall mounting fixtures'),
('Water Meter', 'Water measurement devices'),
('Other', 'Miscellaneous hardware items');

-- Insert chart of accounts
INSERT INTO chart_of_accounts (account_code, account_name, account_type, opening_balance) VALUES
('1000', 'Cash and Cash Equivalents', 'asset', 0),
('1001', 'Cash on Hand', 'asset', 0),
('1002', 'Bank Account', 'asset', 0),
('1100', 'Accounts Receivable', 'asset', 0),
('1200', 'Inventory', 'asset', 0),
('2000', 'Accounts Payable', 'liability', 0),
('2100', 'Loans Payable', 'liability', 0),
('3000', 'Owner Equity', 'equity', 0),
('4000', 'Sales Revenue', 'revenue', 0),
('4100', 'Service Revenue', 'revenue', 0),
('5000', 'Cost of Goods Sold', 'expense', 0),
('5100', 'Operating Expenses', 'expense', 0),
('5200', 'Salary Expenses', 'expense', 0),
('5300', 'Utility Expenses', 'expense', 0);

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('company_name', 'Sharurah Hardware Ltd', 'Company name'),
('company_address', 'Sharurah, Uganda', 'Company address'),
('company_phone', '0773586844', 'Company phone number'),
('company_email', 'info@sharurahhardware.ug', 'Company email'),
('mtn_api_key', '', 'MTN Mobile Money API Key'),
('airtel_api_key', '', 'Airtel Money API Key'),
('currency', 'UGX', 'Default currency'),
('tax_rate', '18', 'VAT tax rate percentage'),
('receipt_footer', 'Thank you for shopping with Sharurah Hardware Ltd!', 'Receipt footer message');

-- ============================================
-- VIEWS FOR REPORTING
-- ============================================

-- View for inventory summary
CREATE VIEW inventory_summary AS
SELECT 
    p.product_id,
    p.product_code,
    p.product_name,
    c.category_name,
    p.unit_price,
    p.cost_price,
    p.quantity_in_stock,
    p.reorder_level,
    (p.quantity_in_stock * p.cost_price) as total_value,
    CASE 
        WHEN p.quantity_in_stock <= p.reorder_level THEN 'Low Stock'
        WHEN p.quantity_in_stock = 0 THEN 'Out of Stock'
        ELSE 'In Stock'
    END as stock_status
FROM products p
LEFT JOIN product_categories c ON p.category_id = c.category_id
WHERE p.is_active = TRUE;

-- View for sales summary
CREATE VIEW sales_summary AS
SELECT 
    so.order_id,
    so.order_number,
    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
    c.phone_number,
    so.order_date,
    so.total_amount,
    so.payment_status,
    so.order_status,
    so.payment_method,
    u.full_name as created_by
FROM sales_orders so
JOIN customers c ON so.customer_id = c.customer_id
JOIN users u ON so.created_by = u.user_id;

-- View for payment summary
CREATE VIEW payment_summary AS
SELECT 
    p.payment_id,
    p.payment_number,
    p.amount,
    p.payment_method,
    p.payment_status,
    p.payment_date,
    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
    c.phone_number,
    CASE 
        WHEN p.payment_method = 'mtn_mobile_money' THEN mmt.transaction_reference
        WHEN p.payment_method = 'airtel_money' THEN mmt.transaction_reference
        ELSE p.transaction_reference
    END as transaction_reference
FROM payments p
JOIN customers c ON p.customer_id = c.customer_id
LEFT JOIN mobile_money_transactions mmt ON p.payment_id = mmt.payment_id;

-- ============================================
-- STORED PROCEDURES
-- ============================================

DELIMITER //

-- Procedure to update stock
CREATE PROCEDURE update_stock(IN p_product_id INT, IN p_quantity INT, IN p_movement_type VARCHAR(20), IN p_reference VARCHAR(100), IN p_user_id INT)
BEGIN
    DECLARE current_stock INT;
    
    -- Get current stock
    SELECT quantity_in_stock INTO current_stock FROM products WHERE product_id = p_product_id;
    
    -- Update stock based on movement type
    IF p_movement_type IN ('purchase', 'return') THEN
        UPDATE products SET quantity_in_stock = current_stock + p_quantity WHERE product_id = p_product_id;
    ELSEIF p_movement_type IN ('sale', 'adjustment') THEN
        UPDATE products SET quantity_in_stock = current_stock - p_quantity WHERE product_id = p_product_id;
    END IF;
    
    -- Record stock movement
    INSERT INTO stock_movements (product_id, movement_type, quantity, reference_number, created_by)
    VALUES (p_product_id, p_movement_type, p_quantity, p_reference, p_user_id);
END //

-- Procedure to create journal entry
CREATE PROCEDURE create_journal_entry(
    IN p_entry_number VARCHAR(50),
    IN p_entry_date DATE,
    IN p_reference VARCHAR(100),
    IN p_description TEXT,
    IN p_entry_type VARCHAR(20),
    IN p_user_id INT
)
BEGIN
    INSERT INTO journal_entries (entry_number, entry_date, reference_number, description, entry_type, created_by)
    VALUES (p_entry_number, p_entry_date, p_reference, p_description, p_entry_type, p_user_id);
END //

DELIMITER ;

-- ============================================
-- TRIGGERS
-- ============================================

DELIMITER //

-- Trigger to update customer balance after payment
CREATE TRIGGER after_payment_insert
AFTER INSERT ON payments
FOR EACH ROW
BEGIN
    IF NEW.payment_status = 'completed' THEN
        UPDATE customers 
        SET current_balance = current_balance - NEW.amount
        WHERE customer_id = NEW.customer_id;
    END IF;
END //

-- Trigger to log user activity
CREATE TRIGGER after_user_login
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.last_login IS NOT NULL AND OLD.last_login IS NULL OR NEW.last_login != OLD.last_login THEN
        INSERT INTO activity_logs (user_id, action, module, description)
        VALUES (NEW.user_id, 'login', 'authentication', 'User logged in');
    END IF;
END //

DELIMITER ;

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

-- Additional indexes for frequently queried columns
CREATE INDEX idx_products_stock ON products(quantity_in_stock);
CREATE INDEX idx_sales_orders_date_status ON sales_orders(order_date, order_status);
CREATE INDEX idx_payments_date_status ON payments(payment_date, payment_status);
CREATE INDEX idx_stock_movements_date ON stock_movements(created_at);
CREATE INDEX idx_journal_entries_date_type ON journal_entries(entry_date, entry_type);

-- ============================================
-- END OF SCHEMA
-- ============================================