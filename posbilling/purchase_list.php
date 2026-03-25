<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

// Search Functionality
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
if ($search) {
    $searchTerm = "%$search%";
    $stmt = $conn->prepare("SELECT p.*, s.name as supplier_name FROM purchases p JOIN suppliers s ON p.supplier_id = s.id WHERE p.invoice_num LIKE ? OR s.name LIKE ? ORDER BY p.purchase_date DESC");
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $purchases = $stmt->get_result();
} else {
    $purchases = $conn->query("SELECT p.*, s.name as supplier_name FROM purchases p JOIN suppliers s ON p.supplier_id = s.id ORDER BY p.purchase_date DESC");
}
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-list me-2"></i>Purchase List</h3>
        <a href="purchases.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Purchase</a>
    </div>
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

<?php include 'includes/footer.php'; ?>
