<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$query = "SELECT b.*, p.name as product_name FROM batches b JOIN products p ON b.product_id = p.id";
if ($search) {
    $query .= " WHERE p.name LIKE '%$search%' OR b.batch_num LIKE '%$search%'";
}
$batches = $conn->query($query);
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-layer-group me-2"></i>Batch Inventory</h3>
    </div>
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
