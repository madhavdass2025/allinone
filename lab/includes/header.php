<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Information System (LIS) - Pet Clinical Lab</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../posbilling/css/style.css">
    <link rel="stylesheet" href="css/lab_style.css">
</head>
<body class="bg-light">
<?php
require_once '../posbilling/includes/db.php';
require_once '../posbilling/includes/functions.php';
if (isLoggedIn()): ?>
<div id="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar" class="no-print bg-dark">
        <div class="sidebar-header border-bottom bg-black">
            <h4 class="mb-0 fw-bold text-info"><i class="fas fa-microscope me-2"></i>Pet Lab</h4>
        </div>

        <ul class="list-unstyled components">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
            <li><a href="lab_orders.php"><i class="fas fa-clipboard-list me-2"></i> Lab Orders</a></li>
            <li><a href="new_order.php"><i class="fas fa-plus-circle me-2"></i> New Test Order</a></li>

            <li class="px-3 py-2 small text-uppercase opacity-50 mt-3 fw-bold text-info">Master Data</li>
            <li><a href="test_types.php"><i class="fas fa-vial me-2"></i> Test Types</a></li>
            <li><a href="technicians.php"><i class="fas fa-user-md me-2"></i> Technicians</a></li>

            <li class="px-3 py-2 small text-uppercase opacity-50 mt-3 fw-bold text-info">Reports</li>
            <li><a href="reports.php"><i class="fas fa-chart-pie me-2"></i> Analytics</a></li>
            <li><a href="../posbilling/dashboard.php"><i class="fas fa-arrow-left me-2"></i> Back to POS</a></li>
        </ul>
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
