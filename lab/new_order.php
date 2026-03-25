<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) die("CSRF failed");

    $customer_id = (int)$_POST['customer_id'];
    $pet_name = sanitizeInput($_POST['pet_name']);
    $species = $_POST['species'];
    $age_y = (int)$_POST['age_years'];
    $age_m = (int)$_POST['age_months'];
    $test_id = (int)$_POST['test_id'];
    $tech_id = (int)$_POST['technician_id'];

    // Get test price
    $t_stmt = $conn->prepare("SELECT price, gst_rate FROM test_types WHERE id = ?");
    $t_stmt->bind_param("i", $test_id);
    $t_stmt->execute();
    $test_data = $t_stmt->get_result()->fetch_assoc();

    $tax = $test_data['price'] * ($test_data['gst_rate'] / 100);
    $net = $test_data['price'] + $tax;

    $stmt = $conn->prepare("INSERT INTO lab_orders (customer_id, pet_name, species, age_years, age_months, test_id, technician_id, total_amount, tax_amount, net_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("isssiiiddd", $customer_id, $pet_name, $species, $age_y, $age_m, $test_id, $tech_id, $test_data['price'], $tax, $net);
    $stmt->execute();
    $order_id = $conn->insert_id;

    flashMessage('success', "Order #$order_id created successfully.");
    header("Location: lab_orders.php");
    exit();
}

$customers = $conn->query("SELECT id, name FROM customers");
$tests = $conn->query("SELECT id, name, price FROM test_types");
$techs = $conn->query("SELECT id, name FROM technicians");
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h4 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>New Lab Order</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="mb-3">
                        <label class="form-label">Customer / Owner</label>
                        <select name="customer_id" class="form-select select2" required>
                            <option value="">Select Owner</option>
                            <?php while($c = $customers->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Pet Name</label>
                            <input type="text" name="pet_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Species</label>
                            <select name="species" class="form-select" required>
                                <option value="Canine">Canine (Dog)</option>
                                <option value="Feline">Feline (Cat)</option>
                                <option value="Bovine">Bovine</option>
                                <option value="Equine">Equine</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Age (Years)</label>
                            <input type="number" name="age_years" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Age (Months)</label>
                            <input type="number" name="age_months" class="form-control" value="0">
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label">Select Test</label>
                        <select name="test_id" class="form-select select2" required>
                            <option value="">Choose Test...</option>
                            <?php while($t = $tests->fetch_assoc()) echo "<option value='{$t['id']}'>{$t['name']} (" . formatCurrency($t['price']) . ")</option>"; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Assigned Technician</label>
                        <select name="technician_id" class="form-select" required>
                            <option value="">Select Tech</option>
                            <?php while($tc = $techs->fetch_assoc()) echo "<option value='{$tc['id']}'>{$tc['name']}</option>"; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold">CREATE ORDER & COLLECT SAMPLE</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
