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
    $where .= " AND (p.invoice_num LIKE '%$search%' OR s.name LIKE '%$search%')";
}

$count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM purchase_returns pr JOIN purchases p ON pr.purchase_id = p.id LEFT JOIN suppliers s ON p.supplier_id = s.id $where");
if ($search) $count_stmt->bind_param("ss", $searchTerm, $searchTerm);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT pr.*, p.invoice_num, s.name as supplier_name FROM purchase_returns pr JOIN purchases p ON pr.purchase_id = p.id LEFT JOIN suppliers s ON p.supplier_id = s.id $where ORDER BY pr.return_date DESC LIMIT ?, ?");
if ($search) {
    $stmt->bind_param("ssii", $searchTerm, $searchTerm, $start, $limit);
} else {
    $stmt->bind_param("ii", $start, $limit);
}
$stmt->execute();
$returns = $stmt->get_result();
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-undo-alt me-2"></i>Purchase Return List</h3>
        <button onclick="window.print()" class="btn btn-outline-secondary no-print"><i class="fas fa-print me-1"></i> Print List</button>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 no-print">
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
                        <th>Return ID</th>
                        <th>Invoice #</th>
                        <th>Supplier</th>
                        <th>Return Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($returns && $returns->num_rows > 0): ?>
                        <?php while ($r = $returns->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatDate($r['return_date']); ?></td>
                            <td>#<?php echo $r['id']; ?></td>
                            <td><?php echo $r['invoice_num']; ?></td>
                            <td><?php echo $r['supplier_name']; ?></td>
                            <td class="fw-bold text-danger"><?php echo formatCurrency($r['total_return_amount']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4">No purchase returns found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_records > $limit): ?>
    <div class="card-footer bg-white">
        <?php echo getPagination($total_records, $limit, $page, "purchase_return_list.php?search=$search"); ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
