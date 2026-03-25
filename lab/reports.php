<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// 1. Test Volume by Category
$stmt1 = $conn->prepare("SELECT tc.name as cat_name, COUNT(lo.id) as total_tests
                  FROM test_categories tc
                  LEFT JOIN test_types tt ON tc.id = tt.category_id
                  LEFT JOIN lab_orders lo ON tt.id = lo.test_id AND DATE(lo.order_date) BETWEEN ? AND ?
                  GROUP BY tc.id");
$stmt1->bind_param("ss", $from_date, $to_date);
$stmt1->execute();
$cat_vol_res = $stmt1->get_result();

// 2. Revenue by Test Type
$stmt2 = $conn->prepare("SELECT tt.name as test_name, SUM(lo.net_amount) as total_revenue
              FROM test_types tt
              JOIN lab_orders lo ON tt.id = lo.test_id
              WHERE DATE(lo.order_date) BETWEEN ? AND ?
              GROUP BY tt.id
              ORDER BY total_revenue DESC");
$stmt2->bind_param("ss", $from_date, $to_date);
$stmt2->execute();
$rev_res = $stmt2->get_result();

// 3. Technician Performance
$stmt3 = $conn->prepare("SELECT t.name as tech_name, COUNT(lo.id) as completed_tests
                    FROM technicians t
                    LEFT JOIN lab_orders lo ON t.id = lo.technician_id AND lo.status = 'Finalized' AND DATE(lo.order_date) BETWEEN ? AND ?
                    GROUP BY t.id");
$stmt3->bind_param("ss", $from_date, $to_date);
$stmt3->execute();
$tech_perf_res = $stmt3->get_result();

// 4. Species Distribution
$stmt4 = $conn->prepare("SELECT species, COUNT(*) as count FROM lab_orders WHERE DATE(order_date) BETWEEN ? AND ? GROUP BY species");
$stmt4->bind_param("ss", $from_date, $to_date);
$stmt4->execute();
$species_res = $stmt4->get_result();
?>

<div class="row no-print">
    <div class="col-md-12 mb-4 d-flex justify-content-between align-items-center">
        <h3 class="fw-bold text-info"><i class="fas fa-chart-pie me-2"></i>Lab Analytics & Reports</h3>
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i> Print All</button>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-info w-100 text-white"><i class="fas fa-filter me-1"></i> Filter Lab Reports</button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <!-- Test Volume -->
    <div class="col-md-6 mb-4 print-section">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary">Test Volume (<?php echo formatDate($from_date); ?> to <?php echo formatDate($to_date); ?>)</h5>
                <button onclick="printSection(this)" class="btn btn-sm btn-outline-primary no-print"><i class="fas fa-print"></i></button>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Category</th><th>Total Tests</th></tr></thead>
                    <tbody>
                        <?php while($row = $cat_vol_res->fetch_assoc()): ?>
                        <tr><td><?php echo $row['cat_name']; ?></td><td><?php echo $row['total_tests']; ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Species distribution -->
    <div class="col-md-6 mb-4 print-section">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-success">Species Mix (<?php echo formatDate($from_date); ?> to <?php echo formatDate($to_date); ?>)</h5>
                <button onclick="printSection(this)" class="btn btn-sm btn-outline-success no-print"><i class="fas fa-print"></i></button>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Species</th><th>Total Orders</th></tr></thead>
                    <tbody>
                        <?php while($row = $species_res->fetch_assoc()): ?>
                        <tr><td><?php echo $row['species']; ?></td><td><?php echo $row['count']; ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Revenue Report -->
    <div class="col-md-6 mb-4 print-section">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-info">Lab Revenue (<?php echo formatDate($from_date); ?> to <?php echo formatDate($to_date); ?>)</h5>
                <button onclick="printSection(this)" class="btn btn-sm btn-outline-info no-print"><i class="fas fa-print"></i></button>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Test Type</th><th>Total Revenue</th></tr></thead>
                    <tbody>
                        <?php while($row = $rev_res->fetch_assoc()): ?>
                        <tr><td><?php echo $row['test_name']; ?></td><td class="fw-bold"><?php echo formatCurrency($row['total_revenue']); ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Technician Performance -->
    <div class="col-md-6 mb-4 print-section">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-warning">Tech Output (<?php echo formatDate($from_date); ?> to <?php echo formatDate($to_date); ?>)</h5>
                <button onclick="printSection(this)" class="btn btn-sm btn-outline-warning no-print"><i class="fas fa-print"></i></button>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Technician Name</th><th>Finalized Reports</th></tr></thead>
                    <tbody>
                        <?php while($row = $tech_perf_res->fetch_assoc()): ?>
                        <tr><td><?php echo $row['tech_name']; ?></td><td><?php echo $row['completed_tests']; ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function printSection(btn) {
    const section = btn.closest('.print-section');
    const originalContent = document.body.innerHTML;
    const title = section.querySelector('h5').innerText;
    const printHeader = `<h2 class='text-center mb-4'>Lab Report: ${title}</h2>`;

    document.body.innerHTML = printHeader + section.innerHTML;
    window.print();
    document.body.innerHTML = originalContent;
    window.location.reload();
}
</script>

<?php include 'includes/footer.php'; ?>
