<?php
include 'includes/header.php';
requireLogin();
require_once 'services/InventoryService.php';

$conn = get_db_conn();
$inventoryService = new InventoryService($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $sale_id = (int)$_POST['sale_id'];
    $items_to_return = $_POST['items']; // Array of sale_item_id => qty

    $conn->begin_transaction();
    try {
        $total_refund = 0;
        $stmt = $conn->prepare("INSERT INTO sale_returns (sale_id, total_refund_amount) VALUES (?, ?)");
        $stmt->bind_param("id", $sale_id, $total_refund);
        $stmt->execute();
        $return_id = $conn->insert_id;

        foreach ($items_to_return as $si_id => $qty) {
            if ($qty <= 0) continue;

            // Get sale item details
            $si_stmt = $conn->prepare("SELECT product_id, batch_id, unit_price, tax_rate FROM sale_items WHERE id = ?");
            $si_stmt->bind_param("i", $si_id);
            $si_stmt->execute();
            $si = $si_stmt->get_result()->fetch_assoc();

            $refund_per_unit = $si['unit_price'] * (1 + $si['tax_rate'] / 100);
            $refund_amount = $qty * $refund_per_unit;

            // Restore stock
            $inventoryService->handleReturn($si['product_id'], $si['batch_id'], $qty, 'sale');

            // Record return item
            $ri_stmt = $conn->prepare("INSERT INTO sale_return_items (sale_return_id, sale_item_id, qty, refund_amount) VALUES (?, ?, ?, ?)");
            $ri_stmt->bind_param("iiid", $return_id, $si_id, $qty, $refund_amount);
            $ri_stmt->execute();

            $total_refund += $refund_amount;
        }

        // Update total refund
        $upd_sr_stmt = $conn->prepare("UPDATE sale_returns SET total_refund_amount = ? WHERE id = ?");
        $upd_sr_stmt->bind_param("di", $total_refund, $return_id);
        $upd_sr_stmt->execute();

        // Update customer balance if applicable
        $si_stmt_info = $conn->prepare("SELECT customer_id FROM sales WHERE id = ?");
        $si_stmt_info->bind_param("i", $sale_id);
        $si_stmt_info->execute();
        $sale_info = $si_stmt_info->get_result()->fetch_assoc();

        if ($sale_info['customer_id']) {
            $upd_cust_due = $conn->prepare("UPDATE customers SET current_due = current_due - ? WHERE id = ?");
            $upd_cust_due->bind_param("di", $total_refund, $sale_info['customer_id']);
            $upd_cust_due->execute();
        }

        $conn->commit();
        flashMessage('success', 'Sale return processed successfully. Refund: ' . formatCurrency($total_refund));
    } catch (Exception $e) {
        $conn->rollback();
        flashMessage('error', 'Error: ' . $e->getMessage());
    }
}

$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;
$sale_items = [];
if ($sale_id) {
    $si_list_stmt = $conn->prepare("SELECT si.*, p.name as product_name, b.batch_num FROM sale_items si JOIN products p ON si.product_id = p.id JOIN batches b ON si.batch_id = b.id WHERE si.sale_id = ?");
    $si_list_stmt->bind_param("i", $sale_id);
    $si_list_stmt->execute();
    $sale_items = $si_list_stmt->get_result();
}
?>

<div class="row">
    <div class="col-md-12">
        <h3 class="fw-bold"><i class="fas fa-undo me-2"></i>Sale Returns</h3>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="number" name="sale_id" class="form-control" placeholder="Enter Sale ID / Invoice ID..." value="<?php echo $sale_id; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Find Sale</button>
            </div>
        </form>
    </div>
</div>

<?php if ($sale_id && $sale_items && $sale_items->num_rows > 0): ?>
<div class="card shadow-sm border-0">
    <form action="sale_returns.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="sale_id" value="<?php echo $sale_id; ?>">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Items in Sale #<?php echo $sale_id; ?></h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Batch</th>
                            <th>Purchased Qty</th>
                            <th>Return Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = $sale_items->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $item['product_name']; ?></td>
                            <td><?php echo $item['batch_num']; ?></td>
                            <td><?php echo $item['qty']; ?></td>
                            <td>
                                <input type="number" name="items[<?php echo $item['id']; ?>]" class="form-control" min="0" max="<?php echo $item['qty']; ?>" value="0">
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-danger">Process Return</button>
        </div>
    </form>
</div>
<?php elseif($sale_id): ?>
    <div class="alert alert-warning">No items found for Sale ID #<?php echo $sale_id; ?>.</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
