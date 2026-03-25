<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $customer_id = (int)$_POST['customer_id'];
    $amount = (float)$_POST['amount'];
    $mode = $_POST['payment_mode'];
    $ref = sanitizeInput($_POST['reference_num']);
    $notes = sanitizeInput($_POST['notes']);

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO payments (customer_id, amount, payment_mode, reference_num, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("idsss", $customer_id, $amount, $mode, $ref, $notes);
        $stmt->execute();
        $payment_id = $conn->insert_id;

        // Update customer due
        $update_stmt = $conn->prepare("UPDATE customers SET current_due = current_due - ? WHERE id = ?");
        $update_stmt->bind_param("di", $amount, $customer_id);
        $update_stmt->execute();

        // Get new balance for ledger
        $bal_stmt = $conn->prepare("SELECT current_due FROM customers WHERE id = ?");
        $bal_stmt->bind_param("i", $customer_id);
        $bal_stmt->execute();
        $new_balance = $bal_stmt->get_result()->fetch_assoc()['current_due'];

        // Add to ledger
        $ledger_stmt = $conn->prepare("INSERT INTO ledger (account_type, account_id, transaction_type, amount, balance_after, reference_type, reference_id, description) VALUES ('Customer', ?, 'Credit', ?, ?, 'Payment', ?, ?)");
        $desc = "Payment received - $mode ($ref)";
        $ledger_stmt->bind_param("iddis", $customer_id, $amount, $new_balance, $payment_id, $desc);
        $ledger_stmt->execute();

        $conn->commit();
        flashMessage('success', 'Payment recorded successfully.');
    } catch (Exception $e) {
        $conn->rollback();
        flashMessage('error', 'Error: ' . $e->getMessage());
    }
}

$payments = $conn->query("SELECT p.*, c.name as customer_name FROM payments p LEFT JOIN customers c ON p.customer_id = c.id ORDER BY p.payment_date DESC");
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-money-bill-wave me-2"></i>Payments Management</h3>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
            <i class="fas fa-plus me-1"></i> Receive Payment
        </button>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Mode</th>
                        <th>Reference</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = $payments->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo formatDate($p['payment_date']); ?></td>
                        <td><?php echo $p['customer_name'] ?? 'Walk-in'; ?></td>
                        <td><?php echo formatCurrency($p['amount']); ?></td>
                        <td><span class="badge bg-info text-dark"><?php echo $p['payment_mode']; ?></span></td>
                        <td><?php echo $p['reference_num']; ?></td>
                        <td><?php echo $p['notes']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="payments.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Receive Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            <?php
                            $customers = $conn->query("SELECT id, name, current_due FROM customers WHERE current_due > 0");
                            while($c = $customers->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']} (Due: " . formatCurrency($c['current_due']) . ")</option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Mode</label>
                        <select name="payment_mode" class="form-select" required>
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                            <option value="UPI">UPI</option>
                            <option value="Advance">Advance</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference #</label>
                        <input type="text" name="reference_num" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
