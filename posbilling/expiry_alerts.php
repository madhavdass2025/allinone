<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

// Near Expiry (next 90 days)
$expiry_query = "SELECT b.*, p.name as product_name FROM batches b JOIN products p ON b.product_id = p.id WHERE b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND b.current_qty > 0 ORDER BY b.expiry_date ASC";
$expiry_results = $conn->query($expiry_query);
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <h3 class="fw-bold text-danger"><i class="fas fa-calendar-times me-2"></i>Detailed Expiry Alerts</h3>
        <p class="text-muted">Showing all items expiring within the next 90 days.</p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product Name</th>
                        <th>Batch Number</th>
                        <th>Expiry Date</th>
                        <th>Current Quantity</th>
                        <th>Cost Price</th>
                        <th>Estimated Loss</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($expiry_results->num_rows > 0): ?>
                        <?php while($row = $expiry_results->fetch_assoc()):
                            $loss = $row['current_qty'] * $row['cost_price'];
                        ?>
                        <tr>
                            <td class="fw-bold"><?php echo $row['product_name']; ?></td>
                            <td><?php echo $row['batch_num']; ?></td>
                            <td><span class="badge bg-danger"><?php echo formatDate($row['expiry_date']); ?></span></td>
                            <td class="fw-bold text-danger"><?php echo $row['current_qty']; ?></td>
                            <td><?php echo formatCurrency($row['cost_price']); ?></td>
                            <td class="fw-bold text-danger"><?php echo formatCurrency($loss); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5">No items expiring within the next 90 days. Excellent inventory management!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
