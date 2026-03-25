<?php
include 'includes/header.php';
requireLogin();

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$conn = get_db_conn();

// Process Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_payment') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }
    $purchase_id = (int)$_POST['purchase_id'];
    $amount = (float)$_POST['amount'];
    $mode = $_POST['payment_mode'];
    $ref = sanitizeInput($_POST['reference_num']);

    $conn->begin_transaction();
    try {
        $supp_stmt = $conn->prepare("SELECT supplier_id, total_amount FROM purchases WHERE id = ?");
        $supp_stmt->bind_param("i", $purchase_id);
        $supp_stmt->execute();
        $pur_data = $supp_stmt->get_result()->fetch_assoc();
        $supplier_id = $pur_data['supplier_id'];
        $total_amount = $pur_data['total_amount'];

        $stmt = $conn->prepare("INSERT INTO payments (supplier_id, purchase_id, amount, payment_mode, reference_num) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iidss", $supplier_id, $purchase_id, $amount, $mode, $ref);
        $stmt->execute();

        // Update purchase status
        $pay_total_stmt = $conn->prepare("SELECT SUM(amount) as paid FROM payments WHERE purchase_id = ?");
        $pay_total_stmt->bind_param("i", $purchase_id);
        $pay_total_stmt->execute();
        $paid_total = $pay_total_stmt->get_result()->fetch_assoc()['paid'];

        $status = 'Partial';
        if ($paid_total >= $total_amount) $status = 'Paid';
        elseif ($paid_total <= 0) $status = 'Unpaid';

        $upd_stmt = $conn->prepare("UPDATE purchases SET payment_status = ? WHERE id = ?");
        $upd_stmt->bind_param("si", $status, $purchase_id);
        $upd_stmt->execute();

        // Update supplier balance
        $upd_supp = $conn->prepare("UPDATE suppliers SET balance = balance - ? WHERE id = ?");
        $upd_supp->bind_param("di", $amount, $supplier_id);
        $upd_supp->execute();

        // Ledger Entry for Payment to Supplier
        $new_bal = 0;
        $bal_res = $conn->query("SELECT balance FROM suppliers WHERE id = $supplier_id");
        if ($bal_row = $bal_res->fetch_assoc()) $new_bal = $bal_row['balance'];

        $ledger_stmt = $conn->prepare("INSERT INTO ledger (account_type, account_id, transaction_type, amount, balance_after, reference_type, reference_id, description) VALUES ('Supplier', ?, 'Debit', ?, ?, 'Payment', ?, ?)");
        $desc = "Payment for Purchase ID: $purchase_id via $mode";
        $ledger_stmt->bind_param("iddis", $supplier_id, $amount, $new_bal, $purchase_id, $desc);
        $ledger_stmt->execute();

        $conn->commit();
        flashMessage('success', 'Payment recorded successfully.');
    } catch (Exception $e) {
        $conn->rollback();
        flashMessage('error', 'Error: ' . $e->getMessage());
    }
}

// Search Functionality
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$where = "WHERE 1=1";
if ($search) {
    $searchTerm = "%$search%";
    $where .= " AND (p.invoice_num LIKE ? OR s.name LIKE ?)";
}

$count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM purchases p JOIN suppliers s ON p.supplier_id = s.id $where");
if ($search) $count_stmt->bind_param("ss", $searchTerm, $searchTerm);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT p.*, s.name as supplier_name FROM purchases p JOIN suppliers s ON p.supplier_id = s.id $where ORDER BY p.purchase_date DESC LIMIT ?, ?");
if ($search) {
    $stmt->bind_param("ssii", $searchTerm, $searchTerm, $start, $limit);
} else {
    $stmt->bind_param("ii", $start, $limit);
}
$stmt->execute();
$purchases = $stmt->get_result();
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-list me-2"></i>Purchase List</h3>
        <div>
            <button onclick="window.print()" class="btn btn-outline-secondary no-print me-2"><i class="fas fa-print me-1"></i> Print List</button>
            <a href="purchases.php" class="btn btn-primary no-print"><i class="fas fa-plus me-1"></i> New Purchase</a>
        </div>
    </div>
    <?php if ($total_records > $limit): ?>
    <div class="card-footer bg-white">
        <?php echo getPagination($total_records, $limit, $page, "purchase_list.php?search=$search"); ?>
    </div>
    <?php endif; ?>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" placeholder="Search by invoice number or supplier name..." value="<?php echo $search; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="fas fa-search me-1"></i> Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Invoice #</th>
                        <th>Supplier</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($purchases && $purchases->num_rows > 0): ?>
                        <?php while ($p = $purchases->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatDate($p['purchase_date']); ?></td>
                            <td><?php echo $p['invoice_num']; ?></td>
                            <td><?php echo $p['supplier_name']; ?></td>
                            <td><?php echo formatCurrency($p['total_amount']); ?></td>
                            <td>
                                <span class="badge <?php echo ($p['payment_status'] == 'Paid' ? 'bg-success' : ($p['payment_status'] == 'Partial' ? 'bg-warning' : 'bg-danger')); ?>">
                                    <?php echo $p['payment_status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_purchase.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-info" title="View"><i class="fas fa-eye"></i></a>
                                <?php if ($p['payment_status'] != 'Paid'): ?>
                                <button class="btn btn-sm btn-outline-success add-payment-btn" data-id="<?php echo $p['id']; ?>" data-invoice="<?php echo $p['invoice_num']; ?>" title="Add Payment"><i class="fas fa-money-bill"></i></button>
                                <?php endif; ?>
                                <a href="purchase_returns.php?purchase_id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger" title="Return Items"><i class="fas fa-undo-alt"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-4">No purchases found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="purchase_list.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="add_payment">
            <input type="hidden" name="purchase_id" id="modal_purchase_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment for <span id="modal_invoice_num"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference #</label>
                        <input type="text" name="reference_num" class="form-control">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentBtns = document.querySelectorAll('.add-payment-btn');
    paymentBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modal_purchase_id').value = this.getAttribute('data-id');
            document.getElementById('modal_invoice_num').innerText = this.getAttribute('data-invoice');
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
