<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) die("CSRF failed");
    $name = sanitizeInput($_POST['name']);
    $qual = sanitizeInput($_POST['qualification']);
    $stmt = $conn->prepare("INSERT INTO technicians (name, qualification) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $qual);
    $stmt->execute();
    flashMessage('success', 'Technician added.');
}

$techs = $conn->query("SELECT * FROM technicians");
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-user-md me-2"></i>Lab Technicians</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTechModal"><i class="fas fa-plus me-1"></i> Add Technician</button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr><th>ID</th><th>Name</th><th>Qualification</th></tr>
            </thead>
            <tbody>
                <?php while($t = $techs->fetch_assoc()): ?>
                <tr><td><?php echo $t['id']; ?></td><td><?php echo $t['name']; ?></td><td><?php echo $t['qualification']; ?></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addTechModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">New Technician</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" required></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Save</button></div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
