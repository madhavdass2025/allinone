<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

// 1. Test Volume by Category
$cat_vol_query = "SELECT tc.name as cat_name, COUNT(lo.id) as total_tests
                  FROM test_categories tc
                  LEFT JOIN test_types tt ON tc.id = tt.category_id
                  LEFT JOIN lab_orders lo ON tt.id = lo.test_id
                  GROUP BY tc.id";
$cat_vol_res = $conn->query($cat_vol_query);

// 2. Revenue by Test Type
$rev_query = "SELECT tt.name as test_name, SUM(lo.net_amount) as total_revenue
              FROM test_types tt
              JOIN lab_orders lo ON tt.id = lo.test_id
              GROUP BY tt.id
              ORDER BY total_revenue DESC";
$rev_res = $conn->query($rev_query);

// 3. Technician Performance
$tech_perf_query = "SELECT t.name as tech_name, COUNT(lo.id) as completed_tests
                    FROM technicians t
                    LEFT JOIN lab_orders lo ON t.id = lo.technician_id
                    WHERE lo.status = 'Finalized'
                    GROUP BY t.id";
$tech_perf_res = $conn->query($tech_perf_query);

// 4. Species Distribution
$species_query = "SELECT species, COUNT(*) as count FROM lab_orders GROUP BY species";
$species_res = $conn->query($species_query);
?>

<div class="row no-print">
    <div class="col-md-12 mb-4 d-flex justify-content-between align-items-center">
        <h3 class="fw-bold text-info"><i class="fas fa-chart-pie me-2"></i>Lab Analytics & Reports</h3>
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i> Print All Analytics</button>
    </div>
</div>

<div class="row">
    <!-- Test Volume -->
    <div class="col-md-6 mb-4 print-section">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary">Test Volume by Category</h5>
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
                <h5 class="mb-0 fw-bold text-success">Species Distribution</h5>
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
                <h5 class="mb-0 fw-bold text-info">Revenue by Test Type</h5>
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
                <h5 class="mb-0 fw-bold text-warning">Technician Performance (Finalized)</h5>
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
