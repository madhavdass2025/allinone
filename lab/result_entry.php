<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

if (!isset($_GET['order_id'])) {
    header("Location: lab_orders.php");
    exit();
}

$order_id = (int)$_GET['order_id'];
$order = $conn->query("SELECT o.*, c.name as owner_name FROM lab_orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = $order_id")->fetch_assoc();

if (!$order) die("Order not found");

// Get the test type for this order
$test_id_res = $conn->query("SELECT test_id FROM lab_orders WHERE id = $order_id");
$test_id = $test_id_res->fetch_assoc()['test_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) die("CSRF failed");

    $conn->begin_transaction();
    try {
        foreach ($_POST['results'] as $param_id => $value) {
            $param_id = (int)$param_id;

            // Calculate Flag (Strategy Logic)
            $p_stmt = $conn->prepare("SELECT reference_ranges FROM test_parameters WHERE id = ?");
            $p_stmt->bind_param("i", $param_id);
            $p_stmt->execute();
            $param = $p_stmt->get_result()->fetch_assoc();
            $ranges = json_decode($param['reference_ranges'], true);
            $species = $order['species'];

            $flag = 'Normal';
            if (isset($ranges[$species]) && is_numeric($value)) {
                $min = $ranges[$species]['min'];
                $max = $ranges[$species]['max'];
                if ($value < $min) $flag = 'L';
                if ($value > $max) $flag = 'H';
            }

            // Upsert result
            $res_check = $conn->query("SELECT id FROM lab_results WHERE order_id = $order_id AND parameter_id = $param_id");
            if ($res_check->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE lab_results SET result_value = ?, flag = ?, recorded_at = NOW() WHERE order_id = ? AND parameter_id = ?");
                $stmt->bind_param("ssii", $value, $flag, $order_id, $param_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO lab_results (order_id, parameter_id, result_value, flag) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $order_id, $param_id, $value, $flag);
            }
            $stmt->execute();
        }

        // Update Order Status
        $status = isset($_POST['finalize']) ? 'Finalized' : 'In Progress';
        $conn->query("UPDATE lab_orders SET status = '$status' WHERE id = $order_id");

        $conn->commit();
        flashMessage('success', 'Results saved successfully.');
        if ($status == 'Finalized') {
            header("Location: lab_orders.php");
            exit();
        }
    } catch (Exception $e) {
        $conn->rollback();
        flashMessage('error', 'Error: ' . $e->getMessage());
    }
}

$parameters = $conn->query("SELECT * FROM test_parameters WHERE test_id = $test_id ORDER BY sort_order ASC");
?>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between">
                <h4 class="mb-0 fw-bold">Result Entry: Order #<?php echo $order_id; ?></h4>
                <span class="badge bg-primary"><?php echo $order['pet_name']; ?> (<?php echo $order['species']; ?>)</span>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Parameter</th>
                                    <th>Reference Range (<?php echo $order['species']; ?>)</th>
                                    <th width="300">Result Value</th>
                                    <th>Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($p = $parameters->fetch_assoc()):
                                    $ranges = json_decode($p['reference_ranges'], true);
                                    $ref = $ranges[$order['species']] ?? ['min' => '-', 'max' => '-'];

                                    // Fetch existing result
                                    $res_stmt = $conn->prepare("SELECT result_value FROM lab_results WHERE order_id = ? AND parameter_id = ?");
                                    $res_stmt->bind_param("ii", $order_id, $p['id']);
                                    $res_stmt->execute();
                                    $existing = $res_stmt->get_result()->fetch_assoc()['result_value'] ?? '';
                                ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $p['parameter_name']; ?></td>
                                    <td class="text-muted small">
                                        <?php echo $ref['min'] . ' - ' . $ref['max']; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['input_type'] == 'numeric'): ?>
                                            <input type="number" step="0.001" name="results[<?php echo $p['id']; ?>]" class="form-control" value="<?php echo $existing; ?>">
                                        <?php elseif ($p['input_type'] == 'textarea'): ?>
                                            <textarea name="results[<?php echo $p['id']; ?>]" class="form-control" rows="2"><?php echo $existing; ?></textarea>
                                        <?php else: ?>
                                            <input type="text" name="results[<?php echo $p['id']; ?>]" class="form-control" value="<?php echo $existing; ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $p['unit']; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" name="save" class="btn btn-outline-primary px-5">Save Draft</button>
                        <button type="submit" name="finalize" class="btn btn-success px-5" onclick="return confirm('Finalize report? No more edits allowed.')">Finalize & Generate Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
