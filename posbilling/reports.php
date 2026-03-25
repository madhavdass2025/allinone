<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

// Near Expiry (next 90 days)
$expiry_query = "SELECT b.*, p.name as product_name FROM batches b JOIN products p ON b.product_id = p.id WHERE b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND b.current_qty > 0 ORDER BY b.expiry_date ASC";
$expiry_results = $conn->query($expiry_query);

// Low Stock (< 10 units)
$low_stock_query = "SELECT b.*, p.name as product_name FROM batches b JOIN products p ON b.product_id = p.id WHERE b.current_qty < 10 AND b.current_qty > 0";
$low_stock_results = $conn->query($low_stock_query);

// Sales Report (current month)
$sales_report_query = "SELECT SUM(net_amount) as total_sales, COUNT(*) as sale_count, DATE(sale_date) as date FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE()) GROUP BY DATE(sale_date)";
$sales_report = $conn->query($sales_report_query);
?>

<div class="row">
    <div class="col-md-12 mb-4 d-flex justify-content-between align-items-center">
        <h3 class="fw-bold"><i class="fas fa-chart-bar me-2"></i>Reporting & Analytics</h3>
        <button onclick="window.print()" class="btn btn-outline-secondary no-print"><i class="fas fa-print me-1"></i> Print Report</button>
    </div>
</div>

<div class="row">
    <!-- Near Expiry Items -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold text-danger"><i class="fas fa-calendar-times me-2"></i>Near Expiry Items (90 Days)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Expiry</th>
                                <th>Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($expiry_results->num_rows > 0): ?>
                                <?php while($row = $expiry_results->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['product_name']; ?></td>
                                    <td><?php echo $row['batch_num']; ?></td>
                                    <td><span class="badge bg-danger"><?php echo formatDate($row['expiry_date']); ?></span></td>
                                    <td><?php echo $row['current_qty']; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4">No near-expiry items found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Items -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold text-warning"><i class="fas fa-exclamation-triangle me-2"></i>Low Stock Items (< 10)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Current Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($low_stock_results->num_rows > 0): ?>
                                <?php while($row = $low_stock_results->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['product_name']; ?></td>
                                    <td><?php echo $row['batch_num']; ?></td>
                                    <td class="fw-bold text-warning"><?php echo $row['current_qty']; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4">No low-stock items found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Daily Sales Report -->
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-chart-line me-2"></i>Daily Sales Report (Current Month)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Invoices</th>
                                <th>Total Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($sales_report->num_rows > 0): ?>
                                <?php while($row = $sales_report->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo formatDate($row['date']); ?></td>
                                    <td><?php echo $row['sale_count']; ?></td>
                                    <td class="fw-bold"><?php echo formatCurrency($row['total_sales']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4">No sales recorded this month.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
