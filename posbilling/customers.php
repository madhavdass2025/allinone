<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }
    $name = sanitizeInput($_POST['name']);
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $address = sanitizeInput($_POST['address']);
    $state = sanitizeInput($_POST['state']);
    $credit_limit = (float)$_POST['credit_limit'];

    if (isset($_POST['customer_id']) && !empty($_POST['customer_id'])) {
        $id = (int)$_POST['customer_id'];
        $stmt = $conn->prepare("UPDATE customers SET name = ?, phone = ?, email = ?, address = ?, state = ?, credit_limit = ? WHERE id = ?");
        $stmt->bind_param("sssssdi", $name, $phone, $email, $address, $state, $credit_limit, $id);
        $stmt->execute();
        flashMessage('success', 'Customer updated successfully.');
    } else {
        $stmt = $conn->prepare("INSERT INTO customers (name, phone, email, address, state, credit_limit) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssd", $name, $phone, $email, $address, $state, $credit_limit);
        $stmt->execute();
        flashMessage('success', 'Customer added successfully.');
    }
}

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$query = "SELECT * FROM customers";
if ($search) {
    $query .= " WHERE name LIKE '%$search%' OR phone LIKE '%$search%'";
}
$customers = $conn->query($query);
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-users me-2"></i>Customer Management</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
            <i class="fas fa-plus me-1"></i> Add Customer
        </button>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" placeholder="Search by name or phone..." value="<?php echo $search; ?>">
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
                        <th>Phone</th>
                        <th>State</th>
                        <th>Due Amount</th>
                        <th>Credit Limit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $customers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $c['id']; ?></td>
                        <td><?php echo $c['name']; ?></td>
                        <td><?php echo $c['phone']; ?></td>
                        <td><?php echo $c['state']; ?></td>
                        <td class="text-danger fw-bold"><?php echo formatCurrency($c['current_due']); ?></td>
                        <td><?php echo formatCurrency($c['credit_limit']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-info edit-customer" data-customer='<?php echo json_encode($c); ?>'><i class="fas fa-edit"></i></button>
                            <a href="customer_ledger.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-invoice"></i> Ledger</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="customers.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="customer_id" id="customer_id">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="cust_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="cust_phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="cust_email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="cust_address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">State</label>
                            <input type="text" name="state" id="cust_state" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Credit Limit</label>
                            <input type="number" step="0.01" name="credit_limit" id="cust_credit_limit" class="form-control" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Customer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-customer');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.getAttribute('data-customer'));
            document.getElementById('customer_id').value = data.id;
            document.getElementById('cust_name').value = data.name;
            document.getElementById('cust_phone').value = data.phone;
            document.getElementById('cust_email').value = data.email;
            document.getElementById('cust_address').value = data.address;
            document.getElementById('cust_state').value = data.state;
            document.getElementById('cust_credit_limit').value = data.credit_limit;

            new bootstrap.Modal(document.getElementById('addCustomerModal')).show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
