<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

// Handle Add/Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }
    $name = sanitizeInput($_POST['name']);
    $generic = sanitizeInput($_POST['generic_name']);
    $category = sanitizeInput($_POST['category']);
    $hsn = sanitizeInput($_POST['hsn_code']);
    $gst = (float)$_POST['gst_rate'];
    $igst = (float)$_POST['igst_rate'];

    if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
        $id = (int)$_POST['product_id'];
        $stmt = $conn->prepare("UPDATE products SET name = ?, generic_name = ?, category = ?, hsn_code = ?, gst_rate = ?, igst_rate = ? WHERE id = ?");
        $stmt->bind_param("ssssddi", $name, $generic, $category, $hsn, $gst, $igst, $id);
        $stmt->execute();
        flashMessage('success', 'Product updated successfully.');
    } else {
        $stmt = $conn->prepare("INSERT INTO products (name, generic_name, category, hsn_code, gst_rate, igst_rate) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdd", $name, $generic, $category, $hsn, $gst, $igst);
        $stmt->execute();
        flashMessage('success', 'Product added successfully.');
    }
}

// Search Functionality
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$query = "SELECT * FROM products";
if ($search) {
    $query .= " WHERE name LIKE '%$search%' OR generic_name LIKE '%$search%' OR hsn_code LIKE '%$search%'";
}
$products = $conn->query($query);
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-capsules me-2"></i>Product Management</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus me-1"></i> Add Product
        </button>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" placeholder="Search by name, generic name, or HSN code..." value="<?php echo $search; ?>">
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
                        <th>Product Name</th>
                        <th>Generic Name</th>
                        <th>Category</th>
                        <th>HSN Code</th>
                        <th>GST %</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = $products->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td><?php echo $p['name']; ?></td>
                        <td><?php echo $p['generic_name']; ?></td>
                        <td><?php echo $p['category']; ?></td>
                        <td><?php echo $p['hsn_code']; ?></td>
                        <td><?php echo $p['gst_rate']; ?>%</td>
                        <td>
                            <button class="btn btn-sm btn-outline-info edit-product" data-product='<?php echo json_encode($p); ?>'><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="products.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="product_id">
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Generic Name</label>
                        <input type="text" name="generic_name" id="generic_name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" id="category" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">HSN Code</label>
                            <input type="text" name="hsn_code" id="hsn_code" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">GST %</label>
                            <input type="number" step="0.01" name="gst_rate" id="gst_rate" class="form-control" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">IGST %</label>
                            <input type="number" step="0.01" name="igst_rate" id="igst_rate" class="form-control" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-product');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.getAttribute('data-product'));
            document.getElementById('product_id').value = data.id;
            document.getElementById('name').value = data.name;
            document.getElementById('generic_name').value = data.generic_name;
            document.getElementById('category').value = data.category;
            document.getElementById('hsn_code').value = data.hsn_code;
            document.getElementById('gst_rate').value = data.gst_rate;
            document.getElementById('igst_rate').value = data.igst_rate;

            new bootstrap.Modal(document.getElementById('addProductModal')).show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
