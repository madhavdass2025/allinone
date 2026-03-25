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
    $supplier_id = (int)$_POST['supplier_id'];
    $invoice_num = sanitizeInput($_POST['invoice_num']);
    $purchase_date = $_POST['purchase_date'];
    $items = json_decode($_POST['items'], true);

    $conn->begin_transaction();
    try {
        $total_amount = 0;
        $stmt = $conn->prepare("INSERT INTO purchases (supplier_id, invoice_num, purchase_date, total_amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $supplier_id, $invoice_num, $purchase_date, $total_amount);
        $stmt->execute();
        $purchase_id = $conn->insert_id;

        foreach ($items as $item) {
            $product_id = $item['product_id'];
            $batch_num = $item['batch_num'];
            $expiry = $item['expiry_date'];
            $qty = $item['qty'];
            $cost = $item['cost_price'];
            $selling = $item['selling_price'];
            $mrp = $item['mrp'];

            $inventoryService->addStock($product_id, $batch_num, $expiry, $qty, $cost, $selling, $mrp);

            $item_stmt = $conn->prepare("INSERT INTO purchase_items (purchase_id, product_id, batch_num, expiry_date, qty, cost_price, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $item_total = $qty * $cost;
            $item_stmt->bind_param("iissddd", $purchase_id, $product_id, $batch_num, $expiry, $qty, $cost, $item_total);
            $item_stmt->execute();
            $total_amount += $item_total;
        }

        $conn->prepare("UPDATE purchases SET total_amount = ? WHERE id = ?")->bind_param("di", $total_amount, $purchase_id)->execute();
        $conn->commit();
        flashMessage('success', 'Purchase recorded successfully.');
    } catch (Exception $e) {
        $conn->rollback();
        flashMessage('error', 'Error: ' . $e->getMessage());
    }
}

$suppliers = $conn->query("SELECT id, name FROM suppliers");
$products = $conn->query("SELECT id, name FROM products");
?>

<div class="row">
    <div class="col-md-12">
        <h3 class="fw-bold"><i class="fas fa-truck-loading me-2"></i>New Purchase</h3>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form action="purchases.php" method="POST" id="purchase_form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="items" id="items_input">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">Select Supplier</option>
                        <?php while($s = $suppliers->fetch_assoc()) echo "<option value='{$s['id']}'>{$s['name']}</option>"; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Invoice Number</label>
                    <input type="text" name="invoice_num" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purchase Date</label>
                    <input type="date" name="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered" id="purchase_table">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Batch #</th>
                            <th>Expiry</th>
                            <th>Qty</th>
                            <th>Cost</th>
                            <th>MRP</th>
                            <th>Selling</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="btn btn-outline-primary" id="add_purchase_item"><i class="fas fa-plus"></i> Add Item</button>
            </div>

            <div class="row">
                <div class="col-md-8"></div>
                <div class="col-md-4 text-end">
                    <h4>Grand Total: <span id="purchase_grand_total">₹0.00</span></h4>
                    <button type="submit" class="btn btn-success btn-lg mt-3">Record Purchase</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let p_items = [];
const product_list = <?php
    $products->data_seek(0);
    $arr = [];
    while($p = $products->fetch_assoc()) $arr[] = $p;
    echo json_encode($arr);
?>;

document.getElementById('add_purchase_item').addEventListener('click', function() {
    p_items.push({
        product_id: '',
        batch_num: '',
        expiry_date: '',
        qty: 0,
        cost_price: 0,
        mrp: 0,
        selling_price: 0
    });
    renderPTable();
});

function renderPTable() {
    const tbody = document.querySelector('#purchase_table tbody');
    tbody.innerHTML = '';
    let grand = 0;

    p_items.forEach((item, idx) => {
        let options = '<option value="">Select Product</option>';
        product_list.forEach(p => {
            options += `<option value="${p.id}" ${item.product_id == p.id ? 'selected' : ''}>${p.name}</option>`;
        });

        const total = item.qty * item.cost_price;
        grand += total;

        tbody.innerHTML += `
            <tr>
                <td><select class="form-select" onchange="updatePItem(${idx}, 'product_id', this.value)">${options}</select></td>
                <td><input type="text" class="form-control" value="${item.batch_num}" onchange="updatePItem(${idx}, 'batch_num', this.value)"></td>
                <td><input type="date" class="form-control" value="${item.expiry_date}" onchange="updatePItem(${idx}, 'expiry_date', this.value)"></td>
                <td><input type="number" class="form-control" value="${item.qty}" onchange="updatePItem(${idx}, 'qty', this.value)"></td>
                <td><input type="number" step="0.01" class="form-control" value="${item.cost_price}" onchange="updatePItem(${idx}, 'cost_price', this.value)"></td>
                <td><input type="number" step="0.01" class="form-control" value="${item.mrp}" onchange="updatePItem(${idx}, 'mrp', this.value)"></td>
                <td><input type="number" step="0.01" class="form-control" value="${item.selling_price}" onchange="updatePItem(${idx}, 'selling_price', this.value)"></td>
                <td>₹${total.toFixed(2)}</td>
                <td><button type="button" class="btn btn-sm btn-danger" onclick="removePItem(${idx})"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
    });

    document.getElementById('purchase_grand_total').innerText = '₹' + grand.toFixed(2);
    document.getElementById('items_input').value = JSON.stringify(p_items);
}

function updatePItem(idx, key, val) {
    p_items[idx][key] = val;
    renderPTable();
}

function removePItem(idx) {
    p_items.splice(idx, 1);
    renderPTable();
}
</script>

<?php include 'includes/footer.php'; ?>
