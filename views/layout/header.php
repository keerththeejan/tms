<?php
$user = Auth::user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Transport Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.1.8/css/dataTables.bootstrap5.min.css">
  <style>
    body { padding-top: 4.5rem; }
    .quick-actions .card { cursor: pointer; transition: transform .05s ease-in; }
    .quick-actions .card:hover { transform: scale(1.01); }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>">TMS</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if ($user): ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>">Dashboard</a></li>
          <?php if (($user['role'] ?? '') === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo Helpers::baseUrl('index.php?page=branches'); ?>">Branches</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo Helpers::baseUrl('index.php?page=users'); ?>">Users</a></li>
          <?php endif; ?>
          <?php 
            $canCreateParcels = Auth::canCreateParcels();
            $parcelsUrl = $canCreateParcels ? Helpers::baseUrl('index.php?page=parcels&action=new') : Helpers::baseUrl('index.php?page=parcels');
          ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo $parcelsUrl; ?>">Parcels</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo Helpers::baseUrl('index.php?page=customers'); ?>">Customers</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>">Suppliers</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>">Delivery Notes</a></li>
          <?php if (Auth::canCollectPayments()): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo Helpers::baseUrl('index.php?page=payments'); ?>">Payments</a></li>
          <?php endif; ?>
          <?php if (Auth::canManageExpenses()): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo Helpers::baseUrl('index.php?page=expenses'); ?>">Expenses</a></li>
          <?php endif; ?>
          <?php if (Auth::hasAnyRole(['admin','accountant'])): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo Helpers::baseUrl('index.php?page=employees'); ?>">Employees</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo Helpers::baseUrl('index.php?page=salaries'); ?>">Salaries</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo Helpers::baseUrl('index.php?page=search'); ?>">Search</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo Helpers::baseUrl('index.php?page=reports'); ?>">Reports</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <?php if ($user): ?>
          <li class="nav-item"><span class="navbar-text me-3">Branch: <?php echo htmlspecialchars($user['branch_name'] ?? ''); ?> (<?php echo $user['is_main_branch'] ? 'Main' : 'Branch'; ?>)</span></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo Helpers::baseUrl('index.php?page=logout'); ?>">Logout</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container-fluid mt-3">
