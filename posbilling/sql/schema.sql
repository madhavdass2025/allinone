CREATE DATABASE IF NOT EXISTS pharmacy_erp;
USE pharmacy_erp;

-- Products Table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    generic_name VARCHAR(255),
    category VARCHAR(100),
    hsn_code VARCHAR(20),
    gst_rate DECIMAL(5,2) DEFAULT 0.00,
    igst_rate DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (hsn_code),
    INDEX (name)
);

-- Suppliers Table
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    gstin VARCHAR(20),
    state VARCHAR(100),
    balance DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Batches Table (Inventory)
CREATE TABLE batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    batch_num VARCHAR(50) NOT NULL,
    expiry_date DATE NOT NULL,
    mrp DECIMAL(15,2) NOT NULL,
    cost_price DECIMAL(15,2) NOT NULL,
    selling_price DECIMAL(15,2) NOT NULL,
    current_qty INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX (expiry_date),
    INDEX (batch_num)
);

-- Customers Table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    state VARCHAR(100),
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    current_due DECIMAL(15,2) DEFAULT 0.00,
    advance_balance DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (phone)
);

-- Purchases (Header)
CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    invoice_num VARCHAR(50),
    purchase_date DATE NOT NULL,
    total_taxable_amount DECIMAL(15,2),
    total_tax_amount DECIMAL(15,2),
    total_amount DECIMAL(15,2),
    payment_status ENUM('Paid', 'Partial', 'Unpaid') DEFAULT 'Unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Purchase Items (Rows)
CREATE TABLE purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    product_id INT NOT NULL,
    batch_num VARCHAR(50),
    expiry_date DATE,
    qty INT NOT NULL,
    cost_price DECIMAL(15,2),
    tax_rate DECIMAL(5,2),
    tax_amount DECIMAL(15,2),
    total_amount DECIMAL(15,2),
    FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Sales (Header)
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    invoice_num VARCHAR(50) UNIQUE,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_taxable_amount DECIMAL(15,2),
    total_tax_amount DECIMAL(15,2),
    total_amount DECIMAL(15,2),
    discount_amount DECIMAL(15,2) DEFAULT 0.00,
    net_amount DECIMAL(15,2),
    payment_mode ENUM('Cash', 'Card', 'UPI', 'Credit', 'Mixed') DEFAULT 'Cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    INDEX (sale_date)
);

-- Sale Items (Rows)
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    batch_id INT NOT NULL,
    qty INT NOT NULL,
    unit_price DECIMAL(15,2),
    tax_rate DECIMAL(5,2),
    tax_amount DECIMAL(15,2),
    total_amount DECIMAL(15,2),
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id)
);

-- Sale Returns
CREATE TABLE sale_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    return_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_refund_amount DECIMAL(15,2),
    FOREIGN KEY (sale_id) REFERENCES sales(id)
);

-- Sale Return Items
CREATE TABLE sale_return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_return_id INT NOT NULL,
    sale_item_id INT NOT NULL,
    qty INT NOT NULL,
    refund_amount DECIMAL(15,2),
    FOREIGN KEY (sale_return_id) REFERENCES sale_returns(id),
    FOREIGN KEY (sale_item_id) REFERENCES sale_items(id)
);

-- Purchase Returns
CREATE TABLE purchase_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    return_date DATE NOT NULL,
    total_return_amount DECIMAL(15,2),
    FOREIGN KEY (purchase_id) REFERENCES purchases(id)
);

-- Purchase Return Items
CREATE TABLE purchase_return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_return_id INT NOT NULL,
    purchase_item_id INT NOT NULL,
    qty INT NOT NULL,
    return_amount DECIMAL(15,2),
    FOREIGN KEY (purchase_return_id) REFERENCES purchase_returns(id),
    FOREIGN KEY (purchase_item_id) REFERENCES purchase_items(id)
);

-- Payments (Supports Split-Mode)
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    supplier_id INT,
    sale_id INT,
    purchase_id INT,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(15,2) NOT NULL,
    payment_mode ENUM('Cash', 'Card', 'UPI', 'Credit', 'Advance') NOT NULL,
    reference_num VARCHAR(100),
    notes TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (purchase_id) REFERENCES purchases(id)
);

-- Ledger for Auditing
CREATE TABLE ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_type ENUM('Customer', 'Supplier', 'Cash', 'Bank') NOT NULL,
    account_id INT, -- Refers to customer_id or supplier_id
    transaction_type ENUM('Debit', 'Credit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    reference_type ENUM('Sale', 'Purchase', 'Return', 'Payment') NOT NULL,
    reference_id INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (account_type, account_id)
);

-- Users Table for Login
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('Admin', 'Staff') DEFAULT 'Staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Admin
INSERT INTO users (username, password, full_name, role) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'Admin'); -- password: password
