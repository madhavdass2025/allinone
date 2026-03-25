<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) die("CSRF validation failed");

    $name = sanitizeInput($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $hsn = sanitizeInput($_POST['hsn_code']);
    $price = (float)$_POST['price'];
    $gst = (float)$_POST['gst_rate'];

    $stmt = $conn->prepare("INSERT INTO test_types (category_id, name, hsn_code, price, gst_rate) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssd", $category_id, $name, $hsn, $price, $gst);
    $stmt->execute();
    $test_id = $conn->insert_id;

    // Handle Parameters
    if (isset($_POST['params'])) {
        foreach ($_POST['params'] as $p) {
            if (empty($p['name'])) continue;
            $p_name = sanitizeInput($p['name']);
            $unit = sanitizeInput($p['unit']);
            $ranges = json_encode($p['ranges']);
            $type = $p['type'];

            $p_stmt = $conn->prepare("INSERT INTO test_parameters (test_id, parameter_name, unit, reference_ranges, input_type) VALUES (?, ?, ?, ?, ?)");
            $p_stmt->bind_param("issss", $test_id, $p_name, $unit, $ranges, $type);
            $p_stmt->execute();
        }
    }
    flashMessage('success', 'Test type defined successfully.');
}

$categories = $conn->query("SELECT * FROM test_categories");
$tests = $conn->query("SELECT t.*, c.name as cat_name FROM test_types t JOIN test_categories c ON t.category_id = c.id");
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-vial me-2"></i>Test Configuration</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTestModal">
            <i class="fas fa-plus me-1"></i> Define New Test
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Category</th>
                        <th>Test Name</th>
                        <th>HSN</th>
                        <th>Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($t = $tests->fetch_assoc()): ?>
                    <tr>
                        <td><span class="badge bg-info text-dark"><?php echo $t['cat_name']; ?></span></td>
                        <td><?php echo $t['name']; ?></td>
                        <td><?php echo $t['hsn_code']; ?></td>
                        <td><?php echo formatCurrency($t['price']); ?></td>
                        <td><a href="edit_test.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-secondary">Edit</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Test Modal -->
<div class="modal fade" id="addTestModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Test Definition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <?php while($c = $categories->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Test Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">HSN</label>
                            <input type="text" name="hsn_code" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="0">
                        </div>
                    </div>

                    <h6 class="fw-bold border-bottom pb-2">Parameters & Reference Ranges</h6>
                    <div id="param_container">
                        <!-- Dynamic Params -->
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addParam()"><i class="fas fa-plus"></i> Add Parameter</button>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save Configuration</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let pIdx = 0;
function addParam() {
    const div = document.createElement('div');
    div.className = 'card bg-light mb-3 p-3';
    div.innerHTML = `
        <div class="row g-2 mb-2">
            <div class="col-md-4">
                <input type="text" name="params[${pIdx}][name]" class="form-control form-control-sm" placeholder="Parameter Name" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="params[${pIdx}][unit]" class="form-control form-control-sm" placeholder="Unit (e.g. mg/dL)">
            </div>
            <div class="col-md-3">
                <select name="params[${pIdx}][type]" class="form-select form-select-sm">
                    <option value="numeric">Numeric</option>
                    <option value="text">Text</option>
                    <option value="textarea">Observation</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-danger w-100" onclick="this.closest('.card').remove()"><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">Dog (Min-Max)</span>
                    <input type="number" step="0.01" name="params[${pIdx}][ranges][Canine][min]" class="form-control" placeholder="Min">
                    <input type="number" step="0.01" name="params[${pIdx}][ranges][Canine][max]" class="form-control" placeholder="Max">
                </div>
            </div>
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">Cat (Min-Max)</span>
                    <input type="number" step="0.01" name="params[${pIdx}][ranges][Feline][min]" class="form-control" placeholder="Min">
                    <input type="number" step="0.01" name="params[${pIdx}][ranges][Feline][max]" class="form-control" placeholder="Max">
                </div>
            </div>
        </div>
    `;
    document.getElementById('param_container').appendChild(div);
    pIdx++;
}
</script>

<?php include 'includes/footer.php'; ?>
