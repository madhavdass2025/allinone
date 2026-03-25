<?php
include 'includes/header.php';
requireLogin();

$conn = get_db_conn();

if (!isset($_GET['id'])) {
    header("Location: customers.php");
    exit();
}

$customer_id = (int)$_GET['id'];
$customer = $conn->query("SELECT * FROM customers WHERE id = $customer_id")->fetch_assoc();

if (!$customer) {
    die("Customer not found.");
}

$ledger = $conn->query("SELECT * FROM ledger WHERE account_type = 'Customer' AND account_id = $customer_id ORDER BY created_at ASC");
?>

<div class="row">
    <div class="col-md-12 d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-file-invoice me-2"></i>Ledger: <?php echo $customer['name']; ?></h3>
        <div class="text-end">
            <p class="mb-0 text-muted">Current Balance</p>
            <h4 class="fw-bold text-danger"><?php echo formatCurrency($customer['current_due']); ?></h4>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Ref ID</th>
                        <th>Description</th>
                        <th>Debit (Due)</th>
                        <th>Credit (Paid)</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $running_balance = 0;
                    while ($l = $ledger->fetch_assoc()):
                        if ($l['transaction_type'] == 'Debit') {
                            $debit = $l['amount'];
                            $credit = 0;
                            $running_balance += $debit;
                        } else {
                            $debit = 0;
                            $credit = $l['amount'];
                            $running_balance -= $credit;
                        }
                    ?>
                    <tr>
                        <td><?php echo formatDate($l['created_at']); ?></td>
                        <td><?php echo $l['reference_type']; ?></td>
                        <td>#<?php echo $l['reference_id']; ?></td>
                        <td><?php echo $l['description']; ?></td>
                        <td class="text-danger"><?php echo $debit > 0 ? formatCurrency($debit) : '-'; ?></td>
                        <td class="text-success"><?php echo $credit > 0 ? formatCurrency($credit) : '-'; ?></td>
                        <td class="fw-bold"><?php echo formatCurrency($running_balance); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
