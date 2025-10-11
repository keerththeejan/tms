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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
  <style>
    html, body { height: 100%; }
    body { padding-top: 0; margin: 0; }
    .quick-actions .card { cursor: pointer; transition: transform .05s ease-in; }
    .quick-actions .card:hover { transform: scale(1.01); }
    /* Slightly smaller, neater navbar font sizes */
    .navbar .navbar-brand { font-size: 1rem; }
    /* Sidebar brand (TMS) larger and colored */
    #sidebar .navbar-brand { font-size: 1.35rem; color: #4fbcff; letter-spacing: .5px; }
    #sidebar .navbar-brand:hover { color: #86d6ff; }
    .navbar .nav-link, .navbar .navbar-text { font-size: 0.9rem; }
    /* Hide caret arrow for specific dropdowns */
    .navbar .dropdown-toggle.no-caret::after { display: none; }
    /* Sidebar tweaks */
    /* place logout as a normal item at the end */
    #sidebar { box-shadow: inset -1px 0 0 rgba(255,255,255,.08); transition: transform .2s ease-in-out; z-index: 1045; }
    .content-wrapper { transition: margin-left .2s ease-in-out, width .2s ease-in-out; }
    body.sidebar-open { overflow: hidden; }
    /* Small screens: sidebar hidden by default, content full width */
    @media (max-width: 992px) {
      #sidebar { transform: translateX(-240px); }
      body.sidebar-open #sidebar { transform: translateX(0); }
      .content-wrapper { margin-left: 0 !important; width: 100% !important; }
      .sidebar-toggle-floating { display: inline-flex !important; }
      .sidebar-overlay { display: none; }
      body.sidebar-open .sidebar-overlay { display: block; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 1030; }
    }
    /* Floating open button (shown only on small screens) */
    .sidebar-toggle-floating { display: none; position: fixed; top: 12px; left: 12px; z-index: 1035; border-radius: 50%; width: 44px; height: 44px; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,.2); }
    body.sidebar-open .sidebar-toggle-floating { display: none !important; }
    /* Close button inside sidebar (mobile only) */
    .sidebar-close-btn { display: none; }
    @media (max-width: 992px) { .sidebar-close-btn { display: inline-flex; } }
    /* Choices.js tweaks to match Bootstrap sizing */
    .choices__inner { min-height: calc(2.25rem + 2px); padding-top: .375rem; padding-bottom: .375rem; }
    .choices[data-type*=select-one] .choices__inner { padding-bottom: .375rem; }
    .choices__list--dropdown { z-index: 1050; }
    /* Darker text for select and dropdown */
    .form-select,
    .choices__inner,
    .choices__input,
    .choices__list--dropdown .choices__item { color: #212529; }
    .choices__placeholder { color: #495057 !important; opacity: 1 !important; }
    .choices[data-type*=select-one] .choices__button { color: #212529; }
    /* Tables: prevent layout breaks on mobile */
    .table td, .table th { white-space: nowrap; }
    .table .text-wrap, .text-wrap { white-space: normal; word-break: break-word; }
    /* General container and typography tuning for small screens */
    @media (max-width: 576px) {
      .container-fluid { padding-left: .75rem; padding-right: .75rem; }
      .navbar .navbar-brand { font-size: .95rem; }
      .navbar .nav-link, .navbar .navbar-text { font-size: .85rem; }
      .table { font-size: .9rem; }
      .btn { padding: .35rem .6rem; font-size: .9rem; }
      .form-control, .form-select { font-size: .95rem; }
    }
    /* Form rows: stack labels/inputs nicely on narrow screens */
    @media (max-width: 768px) {
      .form-label { margin-bottom: .25rem; }
      .row.g-3 > [class^="col-"], .row.g-2 > [class^="col-"], .row.g-1 > [class^="col-"] { margin-bottom: .5rem; }
      .btn-group { flex-wrap: wrap; }
      .btn-toolbar { gap: .5rem; flex-wrap: wrap; }
    }
    /* DataTables wrapper adjustments */
    .dataTables_wrapper .dataTables_filter input { max-width: 160px; }
    @media (max-width: 576px) {
      .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter { float: none; text-align: left; }
      .dataTables_wrapper .dataTables_filter input { width: 100%; max-width: none; }
    }
    /* Utility helpers */
    .w-100-sm { width: auto; }
    @media (max-width: 576px) { .w-100-sm { width: 100% !important; } }
  </style>
</head>
<body>
<?php 
  // Common active state logic
  $currentPage = $_GET['page'] ?? '';
  $action = $_GET['action'] ?? '';
  $canCreateParcels = Auth::canCreateParcels();
  $parcelsCreateUrl = $canCreateParcels
    ? Helpers::baseUrl('index.php?page=parcels&action=new')
    : Helpers::baseUrl('index.php?page=parcels');
  $parcelsListUrl = Helpers::baseUrl('index.php?page=parcels');
  $isParcelsCreateActive = ($currentPage === 'parcels' && in_array($action, ['new','edit'], true));
  $isParcelsListActive = ($currentPage === 'parcels' && !in_array($action, ['new','edit'], true));
  $isDN = ($currentPage === 'delivery_notes');
  $dnAction = $action;
  $isRouteActive = $isDN && ($dnAction === 'route');
  $isRouteVehiclesActive = $isDN && ($dnAction === 'route_vehicles' || $dnAction === 'route_detail');
  $isDNIndexActive = $isDN && ($dnAction === '' || $dnAction === 'index');
?>
<?php if ($user): ?>
<div class="d-flex">
  <!-- Sidebar -->
  <nav id="sidebar" class="bg-dark text-white position-fixed vh-100" style="width: 240px; top:0; left:0; overflow-y:auto;">
    <div class="p-3 border-bottom border-secondary d-flex align-items-center justify-content-between">
      <a class="navbar-brand text-white text-decoration-none fw-semibold" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>">TMS</a>
      <button class="btn btn-sm btn-outline-light sidebar-close-btn" type="button" title="Close" data-role="sidebar-close"><i class="bi bi-x"></i></button>
    </div>
    <?php if ($user): ?>
    <ul class="nav nav-pills flex-column p-2 small">
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='dashboard'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
      <?php if (($user['role'] ?? '') === 'admin'): ?>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='branches'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=branches'); ?>"><i class="bi bi-diagram-3 me-1"></i> Branches</a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='users'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=users'); ?>"><i class="bi bi-people me-1"></i> Users</a></li>
      <?php endif; ?>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isParcelsCreateActive?'active':''; ?>" href="<?php echo $parcelsCreateUrl; ?>"><i class="bi bi-box-seam me-1"></i> Parcels</a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isParcelsListActive?'active':''; ?>" href="<?php echo $parcelsListUrl; ?>"><i class="bi bi-card-list me-1"></i> Parcel Details</a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='customers'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=customers'); ?>"><i class="bi bi-person-lines-fill me-1"></i> Customers</a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='suppliers'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>"><i class="bi bi-truck me-1"></i> Suppliers</a></li>
      <li class="nav-item mt-2 text-uppercase text-secondary fw-semibold px-2">Delivery</li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isRouteActive?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>"><i class="bi bi-geo-alt me-1"></i> Route Planning</a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isRouteVehiclesActive?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles'); ?>"><i class="bi bi-truck-front me-1"></i> Vehicle Routes</a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isDNIndexActive?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>"><i class="bi bi-receipt me-1"></i> Delivery Notes</a></li>
      <?php if (Auth::canCollectPayments()): ?>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='payments'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=payments'); ?>"><i class="bi bi-currency-dollar me-1"></i> Payments</a></li>
      <?php endif; ?>
      <?php if (Auth::canManageExpenses()): ?>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='expenses'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=expenses'); ?>"><i class="bi bi-wallet2 me-1"></i> Expenses</a></li>
      <?php endif; ?>
      <?php if (Auth::hasAnyRole(['admin','accountant'])): ?>
        <li class="nav-item mt-2 text-uppercase text-secondary fw-semibold px-2">HR</li>
        <li class="nav-item"><a class="nav-link text-white <?php echo ($currentPage==='employees' && $action!=='payroll')?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=employees'); ?>"><i class="bi bi-person-badge me-1"></i> Employee Details</a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo ($currentPage==='employees' && $action==='payroll')?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=employees&action=payroll'); ?>"><i class="bi bi-clipboard-data me-1"></i> Salary Report</a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='salaries'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=salaries'); ?>"><i class="bi bi-cash-coin me-1"></i> Salaries</a></li>
        <li class="nav-item mt-2 text-uppercase text-secondary fw-semibold px-2">Accounts</li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='accounts'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=accounts'); ?>"><i class="bi bi-journal-richtext me-1"></i> Accounts</a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='daybook'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=daybook'); ?>"><i class="bi bi-journal-text me-1"></i> Daybook</a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='ledger'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=ledger'); ?>"><i class="bi bi-journal-check me-1"></i> Account Ledger</a></li>
      <?php endif; ?>
      <li class="nav-item mt-2 text-uppercase text-secondary fw-semibold px-2">Tools</li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='search'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=search'); ?>"><i class="bi bi-search me-1"></i> Search</a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='reports'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=reports'); ?>"><i class="bi bi-bar-chart-line me-1"></i> Reports</a></li>
      <?php if ($user): ?>
        <li class="nav-item mt-2 px-2"><hr class="border-secondary opacity-50"></li>
        <li class="nav-item px-2 mb-2 text-secondary small">Branch: <?php echo htmlspecialchars($user['branch_name'] ?? ''); ?> (<?php echo $user['is_main_branch'] ? 'Main' : 'Branch'; ?>)</li>
        <li class="nav-item px-2 mb-3"><a class="btn btn-sm btn-outline-warning w-100" href="<?php echo Helpers::baseUrl('index.php?page=logout'); ?>"><i class="bi bi-box-arrow-right me-1"></i> Logout</a></li>
      <?php endif; ?>
    </ul>
    <?php endif; ?>
  </nav>

  <!-- Content wrapper -->
  <div class="content-wrapper" style="margin-left: 240px; width: calc(100% - 240px); min-height: 100vh;">
    <div class="sidebar-overlay" data-role="sidebar-overlay"></div>
    <div class="container-fluid mt-3">
      <button class="btn btn-primary sidebar-toggle-floating d-lg-none" type="button" title="Menu" data-role="sidebar-open"><i class="bi bi-list"></i></button>
<?php else: ?>
<div class="container-fluid mt-3">
<?php endif; ?>
