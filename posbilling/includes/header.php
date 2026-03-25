<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy ERP - POS Billing System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">
<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
if (isLoggedIn()): ?>
<div id="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar" class="no-print">
        <div class="sidebar-header border-bottom">
            <h4 class="mb-0 fw-bold"><i class="fas fa-prescription-bottle-alt me-2"></i>Pharmacy</h4>
        </div>

        <ul class="list-unstyled components">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
            <li><a href="pos.php"><i class="fas fa-shopping-cart me-2"></i> POS / New Sale</a></li>
            <li><a href="sale_list.php"><i class="fas fa-file-invoice-dollar me-2"></i> Sale History</a></li>

            <li class="px-3 py-2 small text-uppercase opacity-50 mt-3 fw-bold">Inventory</li>
            <li><a href="products.php"><i class="fas fa-capsules me-2"></i> Products</a></li>
            <li><a href="product_stock_list.php"><i class="fas fa-boxes me-2"></i> Stock List</a></li>
            <li><a href="batches.php"><i class="fas fa-layer-group me-2"></i> Batch Stock</a></li>
            <li><a href="expiry_alerts.php"><i class="fas fa-calendar-times me-2"></i> Expiry Alerts</a></li>

            <li class="px-3 py-2 small text-uppercase opacity-50 mt-3 fw-bold">Procurement</li>
            <li><a href="suppliers.php"><i class="fas fa-truck me-2"></i> Suppliers</a></li>
            <li><a href="purchase_list.php"><i class="fas fa-history me-2"></i> Purchase History</a></li>
            <li><a href="purchase_return_list.php"><i class="fas fa-undo-alt me-2"></i> Purchase Returns</a></li>
            <li><a href="purchases.php"><i class="fas fa-plus-circle me-2"></i> New Purchase</a></li>

            <li class="px-3 py-2 small text-uppercase opacity-50 mt-3 fw-bold">Management</li>
            <li><a href="customers.php"><i class="fas fa-users me-2"></i> Customers</a></li>
            <li><a href="payments.php"><i class="fas fa-money-bill-wave me-2"></i> Payments</a></li>
            <li><a href="sale_return_list.php"><i class="fas fa-undo me-2"></i> Sale Returns</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-line me-2"></i> Reports</a></li>

            <li class="px-3 py-2 small text-uppercase opacity-50 mt-3 fw-bold text-info">Clinical Services</li>
            <li><a href="../lab/dashboard.php"><i class="fas fa-microscope me-2"></i> Laboratory (LIS)</a></li>
        </ul>

        <div class="mt-auto p-3 border-top bg-dark text-white">
            <div class="small mb-2"><i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['full_name']; ?></div>
            <a href="logout.php" class="btn btn-danger btn-sm w-100"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
        </div>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <div class="row">
            <div class="col-12">
                <?php flashMessage('success'); ?>
                <?php flashMessage('error', '', 'alert alert-danger'); ?>
            </div>
        </div>
<?php endif; ?>
