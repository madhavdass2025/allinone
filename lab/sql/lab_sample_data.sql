USE pharmacy_erp;

-- 1. Technicians
INSERT INTO technicians (name, qualification) VALUES
('Dr. Robert Paws', 'DVM, Pathologist'),
('Sarah Jenkins', 'Senior Lab Tech'),
('Mike Woof', 'B.Sc Lab Technology'),
('Emily Meow', 'Veterinary Technician'),
('Kevin Claw', 'Junior Pathologist');

-- 2. Test Types
INSERT INTO test_types (category_id, name, hsn_code, price, gst_rate) VALUES
(1, 'Complete Blood Count (CBC)', '9993', 800.00, 12.00),
(2, 'Liver Function Test (LFT)', '9993', 1200.00, 12.00),
(2, 'Blood Glucose', '9993', 300.00, 12.00),
(1, 'Hemoglobin Test', '9993', 200.00, 12.00),
(3, 'Chest X-Ray', '9993', 1500.00, 12.00);

-- 3. Test Parameters
INSERT INTO test_parameters (test_id, parameter_name, unit, reference_ranges, input_type) VALUES
(1, 'WBC Count', '10^3/µL', '{"Canine": {"min": 6.0, "max": 17.0}, "Feline": {"min": 5.5, "max": 19.5}}', 'numeric'),
(1, 'RBC Count', '10^6/µL', '{"Canine": {"min": 5.5, "max": 8.5}, "Feline": {"min": 5.0, "max": 10.0}}', 'numeric'),
(2, 'ALT (SGPT)', 'U/L', '{"Canine": {"min": 10, "max": 100}, "Feline": {"min": 10, "max": 80}}', 'numeric'),
(3, 'Glucose Fasting', 'mg/dL', '{"Canine": {"min": 70, "max": 110}, "Feline": {"min": 60, "max": 120}}', 'numeric'),
(5, 'Radiology Observation', '', '{}', 'textarea');

-- 4. Lab Orders
INSERT INTO lab_orders (customer_id, pet_name, species, age_years, age_months, test_id, technician_id, total_amount, tax_amount, net_amount, status) VALUES
(1, 'Buddy', 'Canine', 3, 0, 1, 1, 800.00, 96.00, 896.00, 'Finalized'),
(2, 'Misty', 'Feline', 2, 5, 2, 2, 1200.00, 144.00, 1344.00, 'Finalized'),
(3, 'Rocky', 'Canine', 5, 2, 3, 3, 300.00, 36.00, 336.00, 'In Progress'),
(4, 'Luna', 'Feline', 1, 0, 4, 4, 200.00, 24.00, 224.00, 'Pending'),
(5, 'Max', 'Canine', 7, 8, 5, 5, 1500.00, 180.00, 1680.00, 'Finalized');

-- 5. Lab Results
INSERT INTO lab_results (order_id, parameter_id, result_value, flag, technician_id) VALUES
(1, 1, '12.5', 'Normal', 1),
(1, 2, '7.2', 'Normal', 1),
(2, 3, '125', 'H', 2),
(5, 5, 'Lungs clear. No signs of congestion.', 'Normal', 5);
