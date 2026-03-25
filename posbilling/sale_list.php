<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search Functionality
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$where = "WHERE 1=1";
if ($search) {
    $searchTerm = "%$search%";
    $where .= " AND (s.invoice_num LIKE ? OR c.name LIKE ?)";
}

// Count for pagination
$count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM sales s LEFT JOIN customers c ON s.customer_id = c.id $where");
if ($search) {
    $count_stmt->bind_param("ss", $searchTerm, $searchTerm);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['count'];

// Fetch data
$stmt = $conn->prepare("SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id $where ORDER BY s.sale_date DESC LIMIT ?, ?");
if ($search) {
    $stmt->bind_param("ssii", $searchTerm, $searchTerm, $start, $limit);
} else {
    $stmt->bind_param("ii", $start, $limit);
}
$stmt->execute();
$sales = $stmt->get_result();
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i>Sale List / History</h3>
        <div>
            <button onclick="window.print()" class="btn btn-outline-secondary no-print me-2"><i class="fas fa-print me-1"></i> Print List</button>
            <a href="pos.php" class="btn btn-primary no-print"><i class="fas fa-plus me-1"></i> New Sale</a>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" placeholder="Search by invoice number or customer name..." value="<?php echo $search; ?>">
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
                        <th>Customer</th>
                        <th>Net Amount</th>
                        <th>Mode</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sales && $sales->num_rows > 0): ?>
                        <?php while ($s = $sales->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatDate($s['sale_date']); ?></td>
                            <td><?php echo $s['invoice_num']; ?></td>
                            <td><?php echo $s['customer_name'] ?? 'Walk-in'; ?></td>
                            <td class="fw-bold text-primary"><?php echo formatCurrency($s['net_amount']); ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo $s['payment_mode']; ?></span></td>
                            <td class="no-print">
                                <a href="view_sale.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-info" title="View"><i class="fas fa-eye"></i></a>
                                <a href="sale_returns.php?sale_id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-danger" title="Return Items"><i class="fas fa-undo"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-4">No sales found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_records > $limit): ?>
    <div class="card-footer bg-white">
        <?php echo getPagination($total_records, $limit, $page, "sale_list.php?search=$search"); ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
