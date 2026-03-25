<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }
    $name = sanitizeInput($_POST['name']);
    $contact = sanitizeInput($_POST['contact_person']);
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $address = sanitizeInput($_POST['address']);
    $gstin = sanitizeInput($_POST['gstin']);
    $state = sanitizeInput($_POST['state']);

    if (isset($_POST['supplier_id']) && !empty($_POST['supplier_id'])) {
        $id = (int)$_POST['supplier_id'];
        $stmt = $conn->prepare("UPDATE suppliers SET name = ?, contact_person = ?, phone = ?, email = ?, address = ?, gstin = ?, state = ? WHERE id = ?");
        $stmt->bind_param("sssssssi", $name, $contact, $phone, $email, $address, $gstin, $state, $id);
        $stmt->execute();
        flashMessage('success', 'Supplier updated successfully.');
    } else {
        $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address, gstin, state) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $name, $contact, $phone, $email, $address, $gstin, $state);
        $stmt->execute();
        flashMessage('success', 'Supplier added successfully.');
    }
}

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$where = "WHERE 1=1";
if ($search) {
    $searchTerm = "%$search%";
    $where .= " AND (name LIKE ? OR contact_person LIKE ?)";
}

$count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM suppliers $where");
if ($search) $count_stmt->bind_param("ss", $searchTerm, $searchTerm);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT * FROM suppliers $where LIMIT ?, ?");
if ($search) {
    $stmt->bind_param("ssii", $searchTerm, $searchTerm, $start, $limit);
} else {
    $stmt->bind_param("ii", $start, $limit);
}
$stmt->execute();
$suppliers = $stmt->get_result();
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-truck me-2"></i>Supplier Management</h3>
        <div>
            <button onclick="window.print()" class="btn btn-outline-secondary no-print me-2"><i class="fas fa-print me-1"></i> Print List</button>
            <button class="btn btn-primary no-print" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                <i class="fas fa-plus me-1"></i> Add Supplier
            </button>
        </div>
    </div>
    <?php if ($total_records > $limit): ?>
    <div class="card-footer bg-white">
        <?php echo getPagination($total_records, $limit, $page, "suppliers.php?search=$search"); ?>
    </div>
    <?php endif; ?>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" placeholder="Search by name or contact person..." value="<?php echo $search; ?>">
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
                        <th>Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>GSTIN</th>
                        <th>State</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($s = $suppliers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $s['id']; ?></td>
                        <td><?php echo $s['name']; ?></td>
                        <td><?php echo $s['contact_person']; ?></td>
                        <td><?php echo $s['phone']; ?></td>
                        <td><?php echo $s['gstin']; ?></td>
                        <td><?php echo $s['state']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-info edit-supplier" data-supplier='<?php echo json_encode($s); ?>'><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="suppliers.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Supplier Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="supplier_id" id="supplier_id">
                    <div class="mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="name" id="supp_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" id="supp_contact" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="supp_phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="supp_email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="supp_address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">GSTIN</label>
                            <input type="text" name="gstin" id="supp_gstin" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">State</label>
                            <input type="text" name="state" id="supp_state" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Supplier</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-supplier');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.getAttribute('data-supplier'));
            document.getElementById('supplier_id').value = data.id;
            document.getElementById('supp_name').value = data.name;
            document.getElementById('supp_contact').value = data.contact_person;
            document.getElementById('supp_phone').value = data.phone;
            document.getElementById('supp_email').value = data.email;
            document.getElementById('supp_address').value = data.address;
            document.getElementById('supp_gstin').value = data.gstin;
            document.getElementById('supp_state').value = data.state;

            new bootstrap.Modal(document.getElementById('addSupplierModal')).show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
