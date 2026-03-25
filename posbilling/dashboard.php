<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

// Basic Stats
$today = date('Y-m-d');
$sales_today_res = $conn->query("SELECT SUM(net_amount) as total FROM sales WHERE DATE(sale_date) = '$today'");
$sales_today = ($sales_today_res && $row = $sales_today_res->fetch_assoc()) ? ($row['total'] ?? 0) : 0;

$total_customers_res = $conn->query("SELECT COUNT(*) as count FROM customers");
$total_customers = ($total_customers_res && $row = $total_customers_res->fetch_assoc()) ? $row['count'] : 0;

// Fix Low Stock query to handle products with NO batches (total stock = 0)
$low_stock_res = $conn->query("SELECT p.id FROM products p LEFT JOIN batches b ON p.id = b.product_id GROUP BY p.id, p.reorder_level HAVING IFNULL(SUM(b.current_qty), 0) <= p.reorder_level");
$low_stock = ($low_stock_res) ? $low_stock_res->num_rows : 0;

$expired_soon_res = $conn->query("SELECT COUNT(*) as count FROM batches WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)");
$expired_soon = ($expired_soon_res && $row = $expired_soon_res->fetch_assoc()) ? $row['count'] : 0;

?>
<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="fw-bold">Welcome, <?php echo $_SESSION['full_name']; ?>!</h2>
        <p class="text-muted">Here's a quick overview of your pharmacy's performance today.</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Today's Sales</h6>
                        <h3 class="fw-bold mb-0"><?php echo formatCurrency($sales_today); ?></h3>
                    </div>
                    <i class="fas fa-chart-line fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Customers</h6>
                        <h3 class="fw-bold mb-0"><?php echo $total_customers; ?></h3>
                    </div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning mb-3 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Low Stock Items</h6>
                        <h3 class="fw-bold mb-0"><?php echo $low_stock; ?></h3>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger mb-3 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Near Expiry</h6>
                        <h3 class="fw-bold mb-0"><?php echo $expired_soon; ?></h3>
                    </div>
                    <i class="fas fa-calendar-times fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold">Recent Sales</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_sales = $conn->query("SELECT s.*, c.name as customer_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id ORDER BY s.created_at DESC LIMIT 5");
                            while ($sale = $recent_sales->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $sale['invoice_num']; ?></td>
                                <td><?php echo $sale['customer_name'] ?? 'Walk-in'; ?></td>
                                <td><?php echo formatCurrency($sale['net_amount']); ?></td>
                                <td><span class="badge bg-success">Completed</span></td>
                                <td><a href="view_sale.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="pos.php" class="btn btn-primary py-2"><i class="fas fa-plus-circle me-1"></i> New Sale</a>
                    <a href="products.php" class="btn btn-outline-primary py-2"><i class="fas fa-capsules me-1"></i> Add Product</a>
                    <a href="purchases.php" class="btn btn-outline-primary py-2"><i class="fas fa-truck-loading me-1"></i> New Purchase</a>
                    <a href="reports.php" class="btn btn-outline-secondary py-2"><i class="fas fa-file-alt me-1"></i> Generate Report</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
