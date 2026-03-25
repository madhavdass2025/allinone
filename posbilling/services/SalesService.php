<?php
class SalesService {
    private $conn;
    private $inventoryService;

    public function __construct($conn, $inventoryService) {
        $this->conn = $conn;
        $this->inventoryService = $inventoryService;
    }

    public function processSale($customer_id, $items, $discount = 0, $payment_mode = 'Cash', $payments = []) {
        $this->conn->begin_transaction();

        try {
            $total_taxable_amount = 0;
            $total_tax_amount = 0;
            $total_net_amount = 0;

            // Fetch state information for GST
            $config = include __DIR__ . '/../includes/config.php';
            $shop_state = $config['shop_state'] ?? 'Delhi';
            $cust_state = '';
            if ($customer_id) {
                $cs_stmt = $this->conn->prepare("SELECT state FROM customers WHERE id = ?");
                $cs_stmt->bind_param("i", $customer_id);
                $cs_stmt->execute();
                $cs_res = $cs_stmt->get_result();
                if ($cs_row = $cs_res->fetch_assoc()) {
                    $cust_state = $cs_row['state'];
                }
            }

            // Generate Invoice Num
            $invoice_num = 'INV-' . time();

            // Insert Sale Header (placeholder values)
            $stmt = $this->conn->prepare("INSERT INTO sales (customer_id, invoice_num, total_taxable_amount, total_tax_amount, total_amount, discount_amount, net_amount, payment_mode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isddddds", $customer_id, $invoice_num, $total_taxable_amount, $total_tax_amount, $total_net_amount, $discount, $total_net_amount, $payment_mode);
            $stmt->execute();
            $sale_id = $this->conn->insert_id;

            foreach ($items as $item) {
                // Deduct from batches using FEFO
                $product_id = $item['product_id'];
                $qty = $item['qty'];
                $unit_price = $item['unit_price'];
                $gstRate = $item['gst_rate'];

                $deducted_batches = $this->inventoryService->deductFEFO($product_id, $qty);
                if (!$deducted_batches) {
                    throw new Exception("Insufficient stock for product ID: " . $product_id);
                }

                foreach ($deducted_batches as $batch) {
                    $item_qty = $batch['qty'];
                    $tax_calc = TaxService::calculateTax($item_qty, $unit_price, 0, $gstRate, $shop_state, $cust_state);

                    $item_taxable = $tax_calc['taxable_amount'];
                    $item_tax = $tax_calc['total_tax'];
                    $item_total = $tax_calc['total_amount'];

                    $item_stmt = $this->conn->prepare("INSERT INTO sale_items (sale_id, product_id, batch_id, qty, unit_price, tax_rate, tax_amount, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $item_stmt->bind_param("iiiddddd", $sale_id, $product_id, $batch['batch_id'], $item_qty, $unit_price, $gstRate, $item_tax, $item_total);
                    $item_stmt->execute();

                    $total_taxable_amount += $item_taxable;
                    $total_tax_amount += $item_tax;
                    $total_net_amount += $item_total;
                }
            }

            // Update Sale Header with final totals
            $net_amount = $total_net_amount - $discount;
            $update_stmt = $this->conn->prepare("UPDATE sales SET total_taxable_amount = ?, total_tax_amount = ?, total_amount = ?, net_amount = ? WHERE id = ?");
            $update_stmt->bind_param("ddddd", $total_taxable_amount, $total_tax_amount, $total_net_amount, $net_amount, $sale_id);
            $update_stmt->execute();

            // Financial Ledger: Record total invoice as a single DEBIT
            if ($customer_id) {
                // Update customer due with full net amount first
                $cust_stmt = $this->conn->prepare("UPDATE customers SET current_due = current_due + ? WHERE id = ?");
                $cust_stmt->bind_param("di", $net_amount, $customer_id);
                $cust_stmt->execute();

                $this->addToLedger('Customer', $customer_id, 'Debit', $net_amount, $sale_id, 'Sale', "Sale Invoice: " . $invoice_num);
            }

            // Process Payments
            foreach ($payments as $payment) {
                $pay_stmt = $this->conn->prepare("INSERT INTO payments (customer_id, sale_id, amount, payment_mode, reference_num) VALUES (?, ?, ?, ?, ?)");
                $pay_stmt->bind_param("iidss", $customer_id, $sale_id, $payment['amount'], $payment['mode'], $payment['ref']);
                $pay_stmt->execute();

                // If payment is made (not Credit), update customer due (record as CREDIT)
                if ($payment['mode'] !== 'Credit' && $customer_id) {
                    $cust_stmt = $this->conn->prepare("UPDATE customers SET current_due = current_due - ? WHERE id = ?");
                    $cust_stmt->bind_param("di", $payment['amount'], $customer_id);
                    $cust_stmt->execute();

                    $this->addToLedger('Customer', $customer_id, 'Credit', $payment['amount'], $sale_id, 'Payment', "Payment for INV: " . $invoice_num . " via " . $payment['mode']);
                }
            }

            $this->conn->commit();
            return $sale_id;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    private function addToLedger($account_type, $account_id, $transaction_type, $amount, $reference_id, $reference_type, $description) {
        if ($account_type === 'Customer' && (!$account_id || $account_id == 0)) {
            return; // No ledger for walk-in customers
        }

        // Fetch current balance
        $balance = 0;
        if ($account_type === 'Customer') {
            $stmt = $this->conn->prepare("SELECT current_due FROM customers WHERE id = ?");
            $stmt->bind_param("i", $account_id);
            $stmt->execute();
            $balance = $stmt->get_result()->fetch_assoc()['current_due'];
        }

        $stmt = $this->conn->prepare("INSERT INTO ledger (account_type, account_id, transaction_type, amount, balance_after, reference_type, reference_id, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisddsis", $account_type, $account_id, $transaction_type, $amount, $balance, $reference_type, $reference_id, $description);
        $stmt->execute();
    }
}
?>
