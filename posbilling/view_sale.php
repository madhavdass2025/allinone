<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$sale_id = (int)$_GET['id'];
$sale = $conn->query("SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address FROM sales s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = $sale_id")->fetch_assoc();

if (!$sale) {
    die("Sale not found.");
}

$items = $conn->query("SELECT si.*, p.name as product_name, b.batch_num FROM sale_items si JOIN products p ON si.product_id = p.id JOIN batches b ON si.batch_id = b.id WHERE si.sale_id = $sale_id");
?>

<div class="row">
    <div class="col-md-12 text-end mb-4">
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i> Print Invoice</button>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 invoice-print">
    <div class="card-body p-5">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2 class="fw-bold text-primary">PHARMACY ERP</h2>
                <p>123 Health Avenue, Medical City<br>Phone: +91 9876543210<br>GSTIN: 22AAAAA0000A1Z5</p>
            </div>
            <div class="col-md-6 text-end">
                <h3 class="text-muted">INVOICE</h3>
                <p class="mb-0"><strong>Invoice #:</strong> <?php echo $sale['invoice_num']; ?></p>
                <p class="mb-0"><strong>Date:</strong> <?php echo formatDate($sale['sale_date']); ?></p>
            </div>
        </div>

        <hr>

        <div class="row mb-4">
            <div class="col-md-12">
                <h6 class="text-muted text-uppercase fw-bold">Bill To:</h6>
                <h5 class="fw-bold"><?php echo $sale['customer_name'] ?? 'Walk-in Customer'; ?></h5>
                <p><?php echo $sale['customer_phone']; ?><br><?php echo $sale['customer_address']; ?></p>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Product Description</th>
                        <th>Batch</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>GST %</th>
                        <th>Tax Amount</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    while($item = $items->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo $item['product_name']; ?></td>
                        <td><?php echo $item['batch_num']; ?></td>
                        <td><?php echo $item['qty']; ?></td>
                        <td><?php echo formatCurrency($item['unit_price']); ?></td>
                        <td><?php echo $item['tax_rate']; ?>%</td>
                        <td><?php echo formatCurrency($item['tax_amount']); ?></td>
                        <td><?php echo formatCurrency($item['total_amount']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="row">
            <div class="col-md-7">
                <p class="text-muted"><em>Note: Medicines once sold will not be taken back unless expired or damaged.</em></p>
            </div>
            <div class="col-md-5">
                <table class="table table-borderless">
                    <tr>
                        <td>Subtotal:</td>
                        <td class="text-end"><?php echo formatCurrency($sale['total_taxable_amount']); ?></td>
                    </tr>
                    <tr>
                        <td>Total Tax:</td>
                        <td class="text-end"><?php echo formatCurrency($sale['total_tax_amount']); ?></td>
                    </tr>
                    <?php if ($sale['discount_amount'] > 0): ?>
                    <tr>
                        <td>Discount:</td>
                        <td class="text-end text-danger">- <?php echo formatCurrency($sale['discount_amount']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="border-top">
                        <td class="fw-bold fs-5">Grand Total:</td>
                        <td class="text-end fw-bold fs-5 text-primary"><?php echo formatCurrency($sale['net_amount']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .btn, .footer, .no-print { display: none !important; }
    .invoice-print { border: none !important; box-shadow: none !important; }
    body { background: white !important; }
}
</style>

<?php include 'includes/footer.php'; ?>
