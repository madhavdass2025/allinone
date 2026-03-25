<?php
include 'includes/header.php';
requireLogin();
require_once 'services/ReportGenerator.php';

$conn = get_db_conn();

if (!isset($_GET['order_id'])) {
    header("Location: lab_orders.php");
    exit();
}

$order_id = (int)$_GET['order_id'];
$order = $conn->query("SELECT o.*, c.name as owner_name FROM lab_orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = $order_id")->fetch_assoc();

if (!$order) die("Order not found");

// Fetch Results with Parameter Details
$res_query = "SELECT lr.*, tp.parameter_name, tp.unit, tp.reference_ranges
              FROM lab_results lr
              JOIN test_parameters tp ON lr.parameter_id = tp.id
              WHERE lr.order_id = $order_id";
$results_res = $conn->query($res_query);
$results = [];
while($row = $results_res->fetch_assoc()) $results[] = $row;

$generator = new ReportGenerator(new DefaultReportStrategy());
$report_html = $generator->generate($order, $results);
?>

<div class="row no-print mb-4">
    <div class="col-12 text-end">
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-1"></i> Print Report</button>
        <a href="lab_orders.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Orders</a>
    </div>
</div>

<div class="card shadow-sm border-0 report-card p-5 bg-white">
    <?php echo $report_html; ?>
</div>

<style>
@media print {
    body { background: white !important; }
    .report-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
    .report-container { width: 100% !important; margin: 0 !important; }
}
</style>

<?php include 'includes/footer.php'; ?>
