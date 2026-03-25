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
    $where .= " AND (s.invoice_num LIKE '%$search%' OR c.name LIKE '%$search%')";
}

$total_res = $conn->query("SELECT COUNT(*) as count FROM sale_returns sr JOIN sales s ON sr.sale_id = s.id LEFT JOIN customers c ON s.customer_id = c.id $where");
$total_records = $total_res->fetch_assoc()['count'];

$query = "SELECT sr.*, s.invoice_num, c.name as customer_name FROM sale_returns sr JOIN sales s ON sr.sale_id = s.id LEFT JOIN customers c ON s.customer_id = c.id $where ORDER BY sr.return_date DESC LIMIT $start, $limit";
$returns = $conn->query($query);
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-undo me-2"></i>Sale Return List</h3>
        <button onclick="window.print()" class="btn btn-outline-secondary no-print"><i class="fas fa-print me-1"></i> Print List</button>
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
                        <th>Return ID</th>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Refund Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($returns && $returns->num_rows > 0): ?>
                        <?php while ($r = $returns->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatDate($r['return_date']); ?></td>
                            <td>#<?php echo $r['id']; ?></td>
                            <td><?php echo $r['invoice_num']; ?></td>
                            <td><?php echo $r['customer_name'] ?? 'Walk-in'; ?></td>
                            <td class="fw-bold text-danger"><?php echo formatCurrency($r['total_refund_amount']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4">No sale returns found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_records > $limit): ?>
    <div class="card-footer bg-white">
        <?php echo getPagination($total_records, $limit, $page, "sale_return_list.php?search=$search"); ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
