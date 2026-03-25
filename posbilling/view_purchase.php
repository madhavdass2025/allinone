<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

if (!isset($_GET['id'])) {
    header("Location: purchase_list.php");
    exit();
}

$purchase_id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT p.*, s.name as supplier_name, s.phone as supplier_phone, s.address as supplier_address, s.gstin as supplier_gstin FROM purchases p JOIN suppliers s ON p.supplier_id = s.id WHERE p.id = ?");
$stmt->bind_param("i", $purchase_id);
$stmt->execute();
$purchase = $stmt->get_result()->fetch_assoc();

if (!$purchase) {
    die("Purchase not found.");
}

$item_stmt = $conn->prepare("SELECT pi.*, pr.name as product_name FROM purchase_items pi JOIN products pr ON pi.product_id = pr.id WHERE pi.purchase_id = ?");
$item_stmt->bind_param("i", $purchase_id);
$item_stmt->execute();
$items = $item_stmt->get_result();
?>

<div class="row">
    <div class="col-md-12 text-end mb-4">
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i> Print Purchase Voucher</button>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 invoice-print">
    <div class="card-body p-5">
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase fw-bold">Supplier Information:</h6>
                <h5 class="fw-bold"><?php echo $purchase['supplier_name']; ?></h5>
                <p><?php echo $purchase['supplier_phone']; ?><br><?php echo $purchase['supplier_address']; ?><br><strong>GSTIN:</strong> <?php echo $purchase['supplier_gstin']; ?></p>
            </div>
            <div class="col-md-6 text-end">
                <h3 class="text-muted">PURCHASE VOUCHER</h3>
                <p class="mb-0"><strong>Invoice #:</strong> <?php echo $purchase['invoice_num']; ?></p>
                <p class="mb-0"><strong>Purchase Date:</strong> <?php echo formatDate($purchase['purchase_date']); ?></p>
            </div>
        </div>

        <hr>

        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Product Description</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                        <th>Qty</th>
                        <th>Cost Price</th>
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
                        <td><?php echo formatDate($item['expiry_date']); ?></td>
                        <td><?php echo $item['qty']; ?></td>
                        <td><?php echo formatCurrency($item['cost_price']); ?></td>
                        <td><?php echo formatCurrency($item['total_amount']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="row">
            <div class="col-md-8"></div>
            <div class="col-md-4">
                <table class="table table-borderless">
                    <tr class="border-top">
                        <td class="fw-bold fs-5">Grand Total:</td>
                        <td class="text-end fw-bold fs-5 text-primary"><?php echo formatCurrency($purchase['total_amount']); ?></td>
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
