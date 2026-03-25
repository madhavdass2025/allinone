USE pharmacy_erp;

-- Lab Technicians
CREATE TABLE IF NOT EXISTS technicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    qualification VARCHAR(255),
    signature_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Test Categories (e.g., Hematology, Biochemistry, Imaging)
CREATE TABLE IF NOT EXISTS test_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    template_file VARCHAR(100), -- Template file in lab/templates/
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Test Types (e.g., Complete Blood Count, Liver Function Test, X-Ray Chest)
CREATE TABLE IF NOT EXISTS test_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    hsn_code VARCHAR(20),
    price DECIMAL(15,2) DEFAULT 0.00,
    gst_rate DECIMAL(5,2) DEFAULT 0.00,
    FOREIGN KEY (category_id) REFERENCES test_categories(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Test Parameters (The "Engine")
CREATE TABLE IF NOT EXISTS test_parameters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    parameter_name VARCHAR(255) NOT NULL,
    unit VARCHAR(50),
    -- Reference Ranges stored as JSON: [{"species": "Canine", "min": 70, "max": 110}, {"species": "Feline", "min": 60, "max": 120}]
    reference_ranges JSON,
    input_type ENUM('numeric', 'text', 'textarea', 'file') DEFAULT 'numeric',
    sort_order INT DEFAULT 0,
    FOREIGN KEY (test_id) REFERENCES test_types(id)
);

-- Lab Orders (Header)
CREATE TABLE IF NOT EXISTS lab_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT, -- Linked to customers table
    pet_name VARCHAR(255),
    species ENUM('Canine', 'Feline', 'Bovine', 'Equine', 'Other'),
    age_years INT,
    age_months INT,
    test_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    technician_id INT,
    sample_collected_at DATETIME,
    status ENUM('Pending', 'In Progress', 'Completed', 'Finalized') DEFAULT 'Pending',
    total_amount DECIMAL(15,2),
    tax_amount DECIMAL(15,2),
    net_amount DECIMAL(15,2),
    invoice_num VARCHAR(50), -- Linked to billing if needed
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (test_id) REFERENCES test_types(id),
    FOREIGN KEY (technician_id) REFERENCES technicians(id)
);

-- Lab Results (EAV Model)
CREATE TABLE IF NOT EXISTS lab_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    parameter_id INT NOT NULL,
    result_value TEXT,
    flag ENUM('Normal', 'H', 'L') DEFAULT 'Normal',
    technician_id INT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES lab_orders(id),
    FOREIGN KEY (parameter_id) REFERENCES test_parameters(id),
    FOREIGN KEY (technician_id) REFERENCES technicians(id)
);

-- Insert Sample Categories
INSERT INTO test_categories (name, template_file) VALUES
('Hematology', 'hematology.php'),
('Biochemistry', 'biochemistry.php'),
('Imaging', 'imaging.php');
