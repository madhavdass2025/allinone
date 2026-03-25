<?php
include 'includes/header.php';
requireLogin();
require_once 'services/TaxService.php';
require_once 'services/InventoryService.php';
require_once 'services/SalesService.php';

$conn = get_db_conn();
$inventoryService = new InventoryService($conn);
$salesService = new SalesService($conn, $inventoryService);

// Process Sale via AJAX or form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }
    $customer_id = (int)$_POST['customer_id'];
    if ($customer_id === 0) $customer_id = null;
    $items = json_decode($_POST['items'], true);
    $discount = (float)$_POST['discount'];
    $payment_mode = $_POST['payment_mode'];
    $payments = [];
    if ($payment_mode === 'Mixed') {
        if ((float)$_POST['split_cash'] > 0) $payments[] = ['mode' => 'Cash', 'amount' => (float)$_POST['split_cash'], 'ref' => 'Split'];
        if ((float)$_POST['split_card'] > 0) $payments[] = ['mode' => 'Card', 'amount' => (float)$_POST['split_card'], 'ref' => 'Split'];
        if ((float)$_POST['split_upi'] > 0) $payments[] = ['mode' => 'UPI', 'amount' => (float)$_POST['split_upi'], 'ref' => 'Split'];
        if ((float)$_POST['split_credit'] > 0) $payments[] = ['mode' => 'Credit', 'amount' => (float)$_POST['split_credit'], 'ref' => 'Split'];
    } else {
        $amount_paid = (float)$_POST['amount_paid'];
        $payments[] = ['mode' => $payment_mode, 'amount' => $amount_paid, 'ref' => ''];
    }

    $sale_id = $salesService->processSale($customer_id, $items, $discount, $payment_mode, $payments);
    if ($sale_id) {
        flashMessage('success', 'Sale processed successfully! Invoice #' . $sale_id);
    } else {
        flashMessage('error', 'Error processing sale.');
    }
}

// Fetch products for autocomplete
$all_products = $conn->query("SELECT p.*, SUM(b.current_qty) as stock FROM products p LEFT JOIN batches b ON p.id = b.product_id WHERE b.current_qty > 0 GROUP BY p.id");
?>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0 fw-bold">Billing Items</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-9">
                        <select id="product_search" class="form-select select2">
                            <option value="">Search Product...</option>
                            <?php while ($p = $all_products->fetch_assoc()): ?>
                            <option value="<?php echo $p['id']; ?>" data-name="<?php echo $p['name']; ?>" data-gst="<?php echo $p['gst_rate']; ?>" data-stock="<?php echo $p['stock']; ?>">
                                <?php echo $p['name']; ?> (Stock: <?php echo $p['stock']; ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" id="add_item" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i> Add</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="pos_table">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th width="100">Qty</th>
                                <th width="120">Price</th>
                                <th width="80">GST%</th>
                                <th width="120">Total</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Items added dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <form action="pos.php" method="POST" id="sale_form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="items" id="items_input">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold">Invoice Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="0">Walk-in Customer</option>
                            <?php
                            $customers = $conn->query("SELECT id, name FROM customers");
                            while($c = $customers->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['name']}</option>";
                            ?>
                        </select>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span id="subtotal">₹0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Tax:</span>
                        <span id="tax_amount">₹0.00</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Discount:</label>
                        <input type="number" name="discount" id="discount" class="form-control" value="0">
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <h4 class="fw-bold text-primary">Total:</h4>
                        <h4 class="fw-bold text-primary" id="grand_total">₹0.00</h4>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Payment Mode</label>
                        <select name="payment_mode" id="payment_mode" class="form-select" onchange="toggleMixedPayment(this.value)">
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                            <option value="UPI">UPI</option>
                            <option value="Credit">Credit</option>
                            <option value="Mixed">Mixed (Split Payment)</option>
                        </select>
                    </div>
                    <div id="single_payment_div">
                        <div class="mb-3">
                            <label class="form-label">Amount Paid</label>
                            <input type="number" step="0.01" name="amount_paid" class="form-control">
                        </div>
                    </div>
                    <div id="mixed_payment_div" style="display:none;">
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="small">Cash</label><input type="number" name="split_cash" class="form-control form-control-sm split-input" value="0"></div>
                            <div class="col-6"><label class="small">Card</label><input type="number" name="split_card" class="form-control form-control-sm split-input" value="0"></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="small">UPI</label><input type="number" name="split_upi" class="form-control form-control-sm split-input" value="0"></div>
                            <div class="col-6"><label class="small">Credit</label><input type="number" name="split_credit" class="form-control form-control-sm split-input" value="0"></div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-5">PROCESS SALE</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let items = [];

document.getElementById('add_item').addEventListener('click', function() {
    const select = document.getElementById('product_search');
    const product_id = select.value;
    if (!product_id) return;

    const option = select.options[select.selectedIndex];
    const name = option.getAttribute('data-name');
    const gst = parseFloat(option.getAttribute('data-gst'));
    const stock = parseInt(option.getAttribute('data-stock'));

    const existing = items.find(i => i.product_id === product_id);
    if (existing) {
        if (existing.qty < stock) existing.qty++;
    } else {
        items.push({
            product_id: product_id,
            name: name,
            qty: 1,
            unit_price: 0,
            gst_rate: gst
        });
    }
    renderTable();
});

function renderTable() {
    const tbody = document.querySelector('#pos_table tbody');
    tbody.innerHTML = '';
    let subtotal = 0;
    let totalTax = 0;

    items.forEach((item, index) => {
        const itemTotal = item.qty * item.unit_price;
        const itemTax = itemTotal * (item.gst_rate / 100);
        subtotal += itemTotal;
        totalTax += itemTax;

        tbody.innerHTML += `
            <tr>
                <td>${item.name}</td>
                <td><input type="number" class="form-control form-control-sm" value="${item.qty}" onchange="updateQty(${index}, this.value)"></td>
                <td><input type="number" class="form-control form-control-sm" value="${item.unit_price}" onchange="updatePrice(${index}, this.value)"></td>
                <td>${item.gst_rate}%</td>
                <td>₹${(itemTotal + itemTax).toFixed(2)}</td>
                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${index})"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
    });

    const discount = parseFloat(document.getElementById('discount').value) || 0;
    document.getElementById('subtotal').innerText = '₹' + subtotal.toFixed(2);
    document.getElementById('tax_amount').innerText = '₹' + totalTax.toFixed(2);
    document.getElementById('grand_total').innerText = '₹' + (subtotal + totalTax - discount).toFixed(2);
    document.getElementById('items_input').value = JSON.stringify(items);
}

function updateQty(index, val) {
    items[index].qty = parseInt(val);
    renderTable();
}

function updatePrice(index, val) {
    items[index].unit_price = parseFloat(val);
    renderTable();
}

function removeItem(index) {
    items.splice(index, 1);
    renderTable();
}

function toggleMixedPayment(val) {
    if (val === 'Mixed') {
        document.getElementById('mixed_payment_div').style.display = 'block';
        document.getElementById('single_payment_div').style.display = 'none';
    } else {
        document.getElementById('mixed_payment_div').style.display = 'none';
        document.getElementById('single_payment_div').style.display = 'block';
    }
}

document.getElementById('discount').addEventListener('input', renderTable);
</script>

<?php include 'includes/footer.php'; ?>
