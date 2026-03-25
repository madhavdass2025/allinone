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
    $where .= " AND (p.name LIKE ? OR p.generic_name LIKE ?)";
}

$count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM products p $where");
if ($search) $count_stmt->bind_param("ss", $searchTerm, $searchTerm);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT p.*, IFNULL(SUM(b.current_qty), 0) as total_stock
          FROM products p
          LEFT JOIN batches b ON p.id = b.product_id
          $where
          GROUP BY p.id
          ORDER BY p.name ASC
          LIMIT ?, ?");
if ($search) {
    $stmt->bind_param("ssii", $searchTerm, $searchTerm, $start, $limit);
} else {
    $stmt->bind_param("ii", $start, $limit);
}
$stmt->execute();
$products = $stmt->get_result();
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-boxes me-2"></i>Product Wise Stock</h3>
        <button onclick="window.print()" class="btn btn-outline-secondary no-print"><i class="fas fa-print me-1"></i> Print Stock List</button>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" placeholder="Search by product or generic name..." value="<?php echo $search; ?>">
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
                        <th>Product Name</th>
                        <th>Generic Name</th>
                        <th>Category</th>
                        <th>Reorder Level</th>
                        <th>Total Available Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products && $products->num_rows > 0): ?>
                        <?php while ($p = $products->fetch_assoc()):
                            $is_low = ($p['total_stock'] <= $p['reorder_level']);
                        ?>
                        <tr>
                            <td class="fw-bold"><?php echo $p['name']; ?></td>
                            <td><?php echo $p['generic_name']; ?></td>
                            <td><?php echo $p['category']; ?></td>
                            <td><?php echo $p['reorder_level']; ?></td>
                            <td class="fw-bold <?php echo $is_low ? 'text-danger' : 'text-success'; ?>">
                                <?php echo $p['total_stock']; ?>
                            </td>
                            <td>
                                <?php if ($is_low): ?>
                                    <span class="badge bg-danger">Low Stock</span>
                                <?php else: ?>
                                    <span class="badge bg-success">In Stock</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-4">No products found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_records > $limit): ?>
    <div class="card-footer bg-white">
        <?php echo getPagination($total_records, $limit, $page, "product_stock_list.php?search=" . urlencode($search) . ""); ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
