<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

// Dashboard Stats
$total_orders = $conn->query("SELECT COUNT(*) as count FROM lab_orders")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM lab_orders WHERE status = 'Pending'")->fetch_assoc()['count'];
$completed_today = $conn->query("SELECT COUNT(*) as count FROM lab_orders WHERE status = 'Finalized' AND DATE(order_date) = CURDATE()")->fetch_assoc()['count'];
$revenue = $conn->query("SELECT SUM(net_amount) as total FROM lab_orders")->fetch_assoc()['total'] ?? 0;

?>
<div class="row mb-4">
    <div class="col-md-12">
        <h2 class="fw-bold">Lab Dashboard</h2>
        <p class="text-muted">Clinical Laboratory Information System Overview</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white border-0 shadow-sm">
            <div class="card-body">
                <h6>Total Orders</h6>
                <h2 class="fw-bold"><?php echo $total_orders; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white border-0 shadow-sm">
            <div class="card-body">
                <h6>Pending Tests</h6>
                <h2 class="fw-bold"><?php echo $pending_orders; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white border-0 shadow-sm">
            <div class="card-body">
                <h6>Finalized Today</h6>
                <h2 class="fw-bold"><?php echo $completed_today; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white border-0 shadow-sm">
            <div class="card-body">
                <h6>Total Revenue</h6>
                <h2 class="fw-bold"><?php echo formatCurrency($revenue); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0 fw-bold">Recent Lab Requests</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Pet Name</th><th>Species</th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php
                            $recent = $conn->query("SELECT * FROM lab_orders ORDER BY order_date DESC LIMIT 5");
                            while($r = $recent->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $r['pet_name']; ?></td>
                                <td><?php echo $r['species']; ?></td>
                                <td><?php echo formatDate($r['order_date']); ?></td>
                                <td><span class="badge <?php echo $r['status']=='Finalized'?'bg-success':'bg-warning'; ?>"><?php echo $r['status']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
