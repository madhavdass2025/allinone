USE pharmacy_erp;

-- 1. Products
INSERT INTO products (name, generic_name, category, hsn_code, gst_rate, igst_rate) VALUES
('Paracetamol 500mg', 'Paracetamol', 'Analgesic', '3004', 12.00, 12.00),
('Amoxicillin 250mg', 'Amoxicillin', 'Antibiotic', '3004', 12.00, 12.00),
('Cetirizine 10mg', 'Cetirizine', 'Antihistamine', '3004', 12.00, 12.00),
('Metformin 500mg', 'Metformin', 'Anti-Diabetic', '3004', 12.00, 12.00),
('Atorvastatin 10mg', 'Atorvastatin', 'Cholesterol', '3004', 12.00, 12.00);

-- 2. Suppliers
INSERT INTO suppliers (name, contact_person, phone, email, address, gstin, state, balance) VALUES
('Global Pharma Dist', 'John Doe', '9876543210', 'info@globalpharma.com', '123 Supply Lane, Mumbai', '27AAAAA0000A1Z5', 'Maharashtra', 0.00),
('HealthPlus Wholesalers', 'Jane Smith', '9876543211', 'sales@healthplus.com', '456 Med Street, Delhi', '07BBBBB1111B1Z6', 'Delhi', 0.00),
('Generic Meds Inc', 'Mike Ross', '9876543212', 'mike@genericmeds.com', '789 Bulk Road, Bangalore', '29CCCCC2222C1Z7', 'Karnataka', 0.00),
('Elite Pharma', 'Sarah Connor', '9876543213', 'sarah@elitepharma.com', '101 Premium Blvd, Chennai', '33DDDDD3333D1Z8', 'Tamil Nadu', 0.00),
('QuickSupply Pharm', 'Tom Hardy', '9876543214', 'tom@quicksupply.com', '202 Fast Ave, Hyderabad', '36EEEEE4444E1Z9', 'Telangana', 0.00);

-- 3. Batches
INSERT INTO batches (product_id, batch_num, expiry_date, mrp, cost_price, selling_price, current_qty) VALUES
(1, 'BN001', '2026-12-31', 50.00, 30.00, 45.00, 100),
(2, 'BN002', '2026-06-30', 120.00, 80.00, 110.00, 50),
(3, 'BN003', '2025-05-15', 30.00, 15.00, 25.00, 200),
(4, 'BN004', '2026-03-20', 80.00, 50.00, 75.00, 150),
(5, 'BN005', '2026-09-10', 150.00, 100.00, 140.00, 80);

-- 4. Customers
INSERT INTO customers (name, phone, email, address, state, credit_limit, current_due) VALUES
('Amit Kumar', '9123456780', 'amit@email.com', 'Sector 5, Dwarka, Delhi', 'Delhi', 5000.00, 0.00),
('Priya Sharma', '9123456781', 'priya@email.com', 'Vasant Kunj, Delhi', 'Delhi', 2000.00, 0.00),
('Rohan Mehra', '9123456782', 'rohan@email.com', 'Gurgaon, Haryana', 'Haryana', 10000.00, 0.00),
('Sneha Gupta', '9123456783', 'sneha@email.com', 'Noida, UP', 'Uttar Pradesh', 3000.00, 0.00),
('Vikram Singh', '9123456784', 'vikram@email.com', 'Chandigarh', 'Punjab', 15000.00, 0.00);

-- 5. Purchases
INSERT INTO purchases (supplier_id, invoice_num, purchase_date, total_taxable_amount, total_tax_amount, total_amount) VALUES
(1, 'PUR-1001', '2026-03-20', 3000.00, 360.00, 3360.00),
(2, 'PUR-1002', '2026-03-21', 4000.00, 480.00, 4480.00),
(3, 'PUR-1003', '2026-03-22', 1500.00, 180.00, 1680.00),
(4, 'PUR-1004', '2026-03-23', 5000.00, 600.00, 5600.00),
(5, 'PUR-1005', '2026-03-24', 8000.00, 960.00, 8960.00);

-- 6. Sales
INSERT INTO sales (customer_id, invoice_num, total_taxable_amount, total_tax_amount, total_amount, discount_amount, net_amount, payment_mode) VALUES
(1, 'INV-2001', 450.00, 54.00, 504.00, 0.00, 504.00, 'Cash'),
(2, 'INV-2002', 250.00, 30.00, 280.00, 10.00, 270.00, 'UPI'),
(3, 'INV-2003', 1100.00, 132.00, 1232.00, 0.00, 1232.00, 'Credit'),
(4, 'INV-2004', 750.00, 90.00, 840.00, 40.00, 800.00, 'Card'),
(5, 'INV-2005', 1400.00, 168.00, 1568.00, 0.00, 1568.00, 'Mixed');

-- 7. Sale Items
INSERT INTO sale_items (sale_id, product_id, batch_id, qty, unit_price, tax_rate, tax_amount, total_amount) VALUES
(1, 1, 1, 10, 45.00, 12.00, 54.00, 504.00),
(2, 3, 3, 10, 25.00, 12.00, 30.00, 280.00),
(3, 2, 2, 10, 110.00, 12.00, 132.00, 1232.00),
(4, 4, 4, 10, 75.00, 12.00, 90.00, 840.00),
(5, 5, 5, 10, 140.00, 12.00, 168.00, 1568.00);

-- 8. Payments
INSERT INTO payments (customer_id, sale_id, amount, payment_mode, reference_num) VALUES
(1, 1, 504.00, 'Cash', 'CASH-001'),
(2, 2, 270.00, 'UPI', 'TXN987654'),
(4, 4, 800.00, 'Card', 'CRD-1122'),
(5, 5, 1000.00, 'Cash', 'SPLIT-1'),
(5, 5, 568.00, 'UPI', 'SPLIT-2');

-- 9. Ledger
INSERT INTO ledger (account_type, account_id, transaction_type, amount, balance_after, reference_type, reference_id, description) VALUES
('Customer', 1, 'Debit', 504.00, 0.00, 'Sale', 1, 'Sale Invoice: INV-2001'),
('Customer', 1, 'Credit', 504.00, 0.00, 'Payment', 1, 'Payment received - Cash'),
('Customer', 3, 'Debit', 1232.00, 1232.00, 'Sale', 3, 'Sale Invoice: INV-2003'),
('Customer', 5, 'Debit', 1568.00, 0.00, 'Sale', 5, 'Sale Invoice: INV-2005'),
('Customer', 5, 'Credit', 1568.00, 0.00, 'Payment', 4, 'Split Payment - Cash/UPI');
