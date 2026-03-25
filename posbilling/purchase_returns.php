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

    $purchase_id = (int)$_POST['purchase_id'];
    $items_to_return = $_POST['items'];

    $conn->begin_transaction();
    try {
        $total_return = 0;
        $stmt = $conn->prepare("INSERT INTO purchase_returns (purchase_id, return_date, total_return_amount) VALUES (?, CURDATE(), ?)");
        $stmt->bind_param("id", $purchase_id, $total_return);
        $stmt->execute();
        $return_id = $conn->insert_id;

        foreach ($items_to_return as $pi_id => $qty) {
            if ($qty <= 0) continue;

            $pi_stmt = $conn->prepare("SELECT pi.*, b.id as batch_id FROM purchase_items pi JOIN batches b ON pi.product_id = b.product_id AND pi.batch_num = b.batch_num WHERE pi.id = ?");
            $pi_stmt->bind_param("i", $pi_id);
            $pi_stmt->execute();
            $pi = $pi_stmt->get_result()->fetch_assoc();

            $return_amount = $qty * $pi['cost_price'];

            // Deduct stock
            $inventoryService->handleReturn($pi['product_id'], $pi['batch_id'], $qty, 'purchase');

            // Record return item
            $ri_stmt = $conn->prepare("INSERT INTO purchase_return_items (purchase_return_id, purchase_item_id, qty, return_amount) VALUES (?, ?, ?, ?)");
            $ri_stmt->bind_param("iiid", $return_id, $pi_id, $qty, $return_amount);
            $ri_stmt->execute();

            $total_return += $return_amount;
        }

        $conn->query("UPDATE purchase_returns SET total_return_amount = $total_return WHERE id = $return_id");

        $purchase_info = $conn->query("SELECT supplier_id FROM purchases WHERE id = $purchase_id")->fetch_assoc();
        if ($purchase_info['supplier_id']) {
            $conn->query("UPDATE suppliers SET balance = balance - $total_return WHERE id = " . $purchase_info['supplier_id']);
        }

        $conn->commit();
        flashMessage('success', 'Purchase return processed successfully. Refund: ' . formatCurrency($total_return));
    } catch (Exception $e) {
        $conn->rollback();
        flashMessage('error', 'Error: ' . $e->getMessage());
    }
}

$purchase_id = isset($_GET['purchase_id']) ? (int)$_GET['purchase_id'] : 0;
$purchase_items = [];
if ($purchase_id) {
    $purchase_items = $conn->query("SELECT pi.*, p.name as product_name FROM purchase_items pi JOIN products p ON pi.product_id = p.id WHERE pi.purchase_id = $purchase_id");
}
?>

<div class="row">
    <div class="col-md-12">
        <h3 class="fw-bold"><i class="fas fa-undo-alt me-2"></i>Purchase Returns</h3>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="number" name="purchase_id" class="form-control" placeholder="Enter Purchase ID / Invoice ID..." value="<?php echo $purchase_id; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Find Purchase</button>
            </div>
        </form>
    </div>
</div>

<?php if ($purchase_id && $purchase_items && $purchase_items->num_rows > 0): ?>
<div class="card shadow-sm border-0">
    <form action="purchase_returns.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="purchase_id" value="<?php echo $purchase_id; ?>">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Items in Purchase #<?php echo $purchase_id; ?></h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Batch</th>
                            <th>Expiry</th>
                            <th>Purchased Qty</th>
                            <th>Return Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = $purchase_items->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $item['product_name']; ?></td>
                            <td><?php echo $item['batch_num']; ?></td>
                            <td><?php echo formatDate($item['expiry_date']); ?></td>
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
<?php elseif($purchase_id): ?>
    <div class="alert alert-warning">No items found for Purchase ID #<?php echo $purchase_id; ?>.</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
