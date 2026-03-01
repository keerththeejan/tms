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
    body { padding-top: 0; margin: 0; background: #f8f9fb; }
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
    #sidebar { box-shadow: inset -1px 0 0 rgba(255,255,255,.08); transition: transform .2s ease-in-out, width .15s ease; z-index: 1045; }
    .content-wrapper { transition: margin-left .2s ease-in-out, width .2s ease-in-out; }
    body.sidebar-open { overflow: hidden; }

    /* 2026 SaaS shell styling */
    :root {
      --shell-radius: 14px;
      --shell-border: rgba(17,24,39,.10);
      --shell-shadow: 0 1px 2px rgba(16,24,40,.06);
    }
    #sidebar {
      width: 220px !important;
      background: #0b1220 !important;
    }
    #sidebar .nav-link {
      border-radius: 10px;
      padding: .5rem .65rem;
      transition: background .15s ease, color .15s ease;
      display: flex;
      align-items: center;
      gap: .5rem;
    }
    #sidebar .nav-link:hover { background: rgba(255,255,255,.06); }
    #sidebar .nav-link.active { background: rgba(79,188,255,.16) !important; color: #e8f7ff !important; }
    #sidebar .nav-link i { width: 18px; text-align: center; }
    #sidebar .nav-section {
      font-size: .72rem;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: rgba(255,255,255,.55);
      font-weight: 700;
      margin-top: .75rem;
      padding: .35rem .65rem;
    }
    .content-wrapper {
      margin-left: 220px !important;
      width: calc(100% - 220px) !important;
    }

    /* Collapsed (icon-only) sidebar on desktop */
    body.sidebar-collapsed #sidebar { width: 72px !important; }
    body.sidebar-collapsed .content-wrapper { margin-left: 72px !important; width: calc(100% - 72px) !important; }
    body.sidebar-collapsed #sidebar .nav-link { justify-content: center; padding: .55rem .5rem; }
    body.sidebar-collapsed #sidebar .nav-link span,
    body.sidebar-collapsed #sidebar .nav-section,
    body.sidebar-collapsed #sidebar .branch-meta,
    body.sidebar-collapsed #sidebar .btn-logout { display: none !important; }
    body.sidebar-collapsed #sidebar .navbar-brand { font-size: 1.1rem; }

    /* Sticky top bar */
    .topbar {
      position: sticky;
      top: 0;
      z-index: 1020;
      background: rgba(248,249,251,.92);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid var(--shell-border);
    }
    .topbar-inner {
      min-height: 56px;
      padding: 10px 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .page-title { font-size: 1.05rem; font-weight: 700; margin: 0; }
    .page-subtitle { font-size: .82rem; color: #6b7280; margin: 0; }
    .icon-btn { width: 36px; height: 36px; padding: 0; display: inline-flex; align-items:center; justify-content:center; border-radius: 10px; }
    .shell-card { background: #fff; border: 1px solid var(--shell-border); border-radius: var(--shell-radius); box-shadow: var(--shell-shadow); }
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
      .table td, .table th { white-space: normal; word-break: break-word; }
      .btn { white-space: normal; }
      .form-control, .form-select { min-width: 0; }
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
  <nav id="sidebar" class="bg-dark text-white position-fixed vh-100" style="top:0; left:0; overflow-y:auto;">
    <div class="p-3 border-bottom border-secondary d-flex align-items-center justify-content-between gap-2">
      <a class="navbar-brand text-white text-decoration-none fw-semibold" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>">TMS</a>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-outline-light d-none d-lg-inline-flex" type="button" title="Collapse" data-role="sidebar-collapse"><i class="bi bi-layout-sidebar-inset"></i></button>
        <button class="btn btn-sm btn-outline-light sidebar-close-btn" type="button" title="Close" data-role="sidebar-close"><i class="bi bi-x"></i></button>
      </div>
    </div>
    <?php if ($user): ?>
    <ul class="nav nav-pills flex-column p-2 small">
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='dashboard'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
      <?php if (($user['role'] ?? '') === 'admin'): ?>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='branches'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=branches'); ?>"><i class="bi bi-diagram-3"></i><span>Branches</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='users'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=users'); ?>"><i class="bi bi-people"></i><span>Users</span></a></li>
      <?php endif; ?>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isParcelsCreateActive?'active':''; ?>" href="<?php echo $parcelsCreateUrl; ?>"><i class="bi bi-box-seam"></i><span>Parcels</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isParcelsListActive?'active':''; ?>" href="<?php echo $parcelsListUrl; ?>"><i class="bi bi-card-list"></i><span>Parcel Details</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='customers'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=customers'); ?>"><i class="bi bi-person-lines-fill"></i><span>Customers</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='delivery_routes'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_routes'); ?>"><i class="bi bi-signpost"></i><span>Delivery Routes</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='suppliers'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>"><i class="bi bi-truck"></i><span>Suppliers</span></a></li>
      <li class="nav-item nav-section">Delivery</li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isRouteActive?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>"><i class="bi bi-geo-alt"></i><span>Route Planning</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isRouteVehiclesActive?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles'); ?>"><i class="bi bi-truck-front"></i><span>Vehicle Routes</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isDNIndexActive?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>"><i class="bi bi-receipt"></i><span>Delivery Notes</span></a></li>
      <?php if (Auth::canCollectPayments()): ?>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='payments'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=payments'); ?>"><i class="bi bi-currency-dollar"></i><span>Payments</span></a></li>
      <?php endif; ?>
      <?php if (Auth::canManageExpenses()): ?>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='expenses'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=expenses'); ?>"><i class="bi bi-wallet2"></i><span>Expenses</span></a></li>
      <?php endif; ?>
      <?php if (Auth::hasAnyRole(['admin','accountant'])): ?>
        <li class="nav-item nav-section">HR</li>
        <li class="nav-item"><a class="nav-link text-white <?php echo ($currentPage==='employees' && $action!=='payroll')?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=employees'); ?>"><i class="bi bi-person-badge"></i><span>Employee Details</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo ($currentPage==='employees' && $action==='payroll')?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=employees&action=payroll'); ?>"><i class="bi bi-clipboard-data"></i><span>Salary Report</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='salaries'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=salaries'); ?>"><i class="bi bi-cash-coin"></i><span>Salaries</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='advances'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=advances'); ?>"><i class="bi bi-cash-stack"></i><span>Advances</span></a></li>
        <li class="nav-item nav-section">Accounts</li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='accounts'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=accounts'); ?>"><i class="bi bi-journal-richtext"></i><span>Accounts</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='daybook'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=daybook'); ?>"><i class="bi bi-journal-text"></i><span>Daybook</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='ledger'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=ledger'); ?>"><i class="bi bi-journal-check"></i><span>Account Ledger</span></a></li>
      <?php endif; ?>
      <li class="nav-item nav-section">Tools</li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='search'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=search'); ?>"><i class="bi bi-search"></i><span>Search</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='reminders'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=reminders'); ?>"><i class="bi bi-bell"></i><span>Reminders</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='reports'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=reports'); ?>"><i class="bi bi-bar-chart-line"></i><span>Reports</span></a></li>
      <?php if (($user['role'] ?? '') === 'admin'): ?>
        <li class="nav-item nav-section">Admin</li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='settings'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=settings'); ?>"><i class="bi bi-gear"></i><span>Settings</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='backup'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=backup'); ?>"><i class="bi bi-hdd"></i><span>Backups</span></a></li>
      <?php endif; ?>
      <?php if ($user): ?>
        <li class="nav-item mt-2 px-2"><hr class="border-secondary opacity-50"></li>
        <li class="nav-item px-2 mb-2 text-secondary small branch-meta">Branch: <?php echo htmlspecialchars($user['branch_name'] ?? ''); ?> (<?php echo $user['is_main_branch'] ? 'Main' : 'Branch'; ?>)</li>
        <li class="nav-item px-2 mb-3"><a class="btn btn-sm btn-outline-warning w-100 btn-logout" href="<?php echo Helpers::baseUrl('index.php?page=logout'); ?>"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a></li>
      <?php endif; ?>
    </ul>
    <?php endif; ?>
  </nav>

  <!-- Content wrapper -->
  <div class="content-wrapper" style="min-height: 100vh;">
    <div class="sidebar-overlay" data-role="sidebar-overlay"></div>
    <?php
      $uiCompany = Helpers::company();
      $uiHeaderAddr = Helpers::companyHeaderAddressLines('', 3);
      $pageTitle = 'Dashboard';
      if ($currentPage && $currentPage !== 'dashboard') {
        $pageTitle = ucwords(str_replace('_', ' ', (string)$currentPage));
      }
    ?>
    <div class="topbar">
      <div class="container-fluid">
        <div class="topbar-inner">
          <div class="d-flex align-items-center gap-2 min-w-0">
            <button class="btn btn-outline-secondary icon-btn d-none d-lg-inline-flex" type="button" title="Toggle sidebar" data-role="sidebar-collapse" aria-label="Toggle sidebar"><i class="bi bi-layout-sidebar-inset"></i></button>
            <button class="btn btn-outline-secondary icon-btn d-lg-none" type="button" title="Menu" data-role="sidebar-open"><i class="bi bi-list"></i></button>
            <div class="min-w-0">
              <p class="page-title text-truncate mb-0"><?php echo htmlspecialchars($pageTitle); ?></p>
              <?php if (!empty($uiHeaderAddr)): ?>
                <p class="page-subtitle text-truncate mb-0"><?php echo htmlspecialchars(implode(' | ', $uiHeaderAddr)); ?></p>
              <?php endif; ?>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <?php if (Auth::canCreateParcels()): ?>
              <a class="btn btn-primary btn-sm" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=new'); ?>"><i class="bi bi-plus-lg me-1"></i> Parcel</a>
            <?php endif; ?>
            <button class="btn btn-outline-secondary icon-btn" type="button" title="Notifications" aria-label="Notifications"><i class="bi bi-bell"></i></button>
            <div class="dropdown">
              <button class="btn btn-outline-secondary icon-btn dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Profile">
                <i class="bi bi-person-circle"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header"><?php echo htmlspecialchars((string)($user['username'] ?? ($user['name'] ?? 'User'))); ?></h6></li>
                <li><span class="dropdown-item-text small text-muted"><?php echo htmlspecialchars((string)($user['role'] ?? '')); ?></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?php echo Helpers::baseUrl('index.php?page=settings'); ?>"><i class="bi bi-gear me-2"></i>Settings</a></li>
                <li><a class="dropdown-item" href="<?php echo Helpers::baseUrl('index.php?page=logout'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container-fluid mt-3">
      
<?php else: ?>
<div class="container-fluid mt-3">
<?php endif; ?>
