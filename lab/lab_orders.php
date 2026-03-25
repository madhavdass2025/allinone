<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

$query = "SELECT o.*, c.name as customer_name FROM lab_orders o JOIN customers c ON o.customer_id = c.id ORDER BY o.order_date DESC";
$orders = $conn->query($query);
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-microscope me-2"></i>Lab Orders</h3>
        <a href="new_order.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Order</a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Order ID</th>
                        <th>Pet / Owner</th>
                        <th>Species</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($o = $orders->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo formatDate($o['order_date']); ?></td>
                        <td>#<?php echo $o['id']; ?></td>
                        <td>
                            <span class="fw-bold"><?php echo $o['pet_name']; ?></span><br>
                            <small class="text-muted"><?php echo $o['customer_name']; ?></small>
                        </td>
                        <td><?php echo $o['species']; ?></td>
                        <td><?php echo formatCurrency($o['net_amount']); ?></td>
                        <td>
                            <span class="badge <?php echo ($o['status'] == 'Finalized' ? 'bg-success' : ($o['status'] == 'Pending' ? 'bg-danger' : 'bg-warning')); ?>">
                                <?php echo $o['status']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($o['status'] != 'Finalized'): ?>
                                <a href="result_entry.php?order_id=<?php echo $o['id']; ?>" class="btn btn-sm btn-info text-white"><i class="fas fa-edit me-1"></i> Enter Results</a>
                            <?php else: ?>
                                <a href="report_view.php?order_id=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-file-pdf me-1"></i> View Report</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
