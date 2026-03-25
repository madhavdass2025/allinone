<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$where = "WHERE 1=1";
if ($search) {
    $searchTerm = "%$search%";
    $where .= " AND (p.name LIKE ? OR b.batch_num LIKE ?)";
}

$count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM batches b JOIN products p ON b.product_id = p.id $where");
if ($search) $count_stmt->bind_param("ss", $searchTerm, $searchTerm);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT b.*, p.name as product_name FROM batches b JOIN products p ON b.product_id = p.id $where ORDER BY b.expiry_date ASC LIMIT ?, ?");
if ($search) {
    $stmt->bind_param("ssii", $searchTerm, $searchTerm, $start, $limit);
} else {
    $stmt->bind_param("ii", $start, $limit);
}
$stmt->execute();
$batches = $stmt->get_result();
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-layer-group me-2"></i>Batch Inventory</h3>
        <button onclick="window.print()" class="btn btn-outline-secondary no-print"><i class="fas fa-print me-1"></i> Print List</button>
    </div>
    <?php if ($total_records > $limit): ?>
    <div class="card-footer bg-white">
        <?php echo getPagination($total_records, $limit, $page, "batches.php?search=" . urlencode($search) . ""); ?>
    </div>
    <?php endif; ?>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" placeholder="Search by product name or batch number..." value="<?php echo $search; ?>">
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
                        <th>ID</th>
                        <th>Product</th>
                        <th>Batch #</th>
                        <th>Expiry</th>
                        <th>Cost</th>
                        <th>Selling</th>
                        <th>Current Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($b = $batches->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $b['id']; ?></td>
                        <td><?php echo $b['product_name']; ?></td>
                        <td><?php echo $b['batch_num']; ?></td>
                        <td><?php echo formatDate($b['expiry_date']); ?></td>
                        <td><?php echo formatCurrency($b['cost_price']); ?></td>
                        <td><?php echo formatCurrency($b['selling_price']); ?></td>
                        <td class="<?php echo ($b['current_qty'] < 10) ? 'text-danger fw-bold' : ''; ?>">
                            <?php echo $b['current_qty']; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
