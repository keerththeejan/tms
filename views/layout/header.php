<?php
$user = Auth::user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Transport Management System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.1.8/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
  <link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/report-master.css?v=1'); ?>">
  <link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/tms-design-system.css'); ?>">
  <link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/app-layout-rebuild.css'); ?>">
  <?php if (in_array($_GET['page'] ?? '', ['employees', 'advances'], true)): ?>
  <link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/hr-responsive.css'); ?>">
  <?php endif; ?>
  <?php
  $hdrPage = $_GET['page'] ?? '';
  $hdrEmpAction = $_GET['action'] ?? '';
  if ($hdrPage === 'employees'): ?>
  <link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/employees-page.css'); ?>">
  <?php endif; ?>
  <?php if ($hdrPage === 'users' && in_array($hdrEmpAction, ['new', 'edit'], true)): ?>
  <link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/users-form.css'); ?>">
  <?php endif; ?>
  <script>
    window.TMS_CURRENCY = <?php echo json_encode(Helpers::currencyJsConfig(), JSON_UNESCAPED_UNICODE); ?>;
  </script>
  <script src="<?php echo Helpers::baseUrl('assets/js/tms-currency.js?v=1'); ?>"></script>
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
  $isItemsActive = ($currentPage === 'items');
  $isDN = ($currentPage === 'delivery_notes');
  $dnAction = $action;
  $isRouteActive = $isDN && ($dnAction === 'route');
  $isRouteVehiclesActive = $isDN && ($dnAction === 'route_vehicles' || $dnAction === 'route_detail');
  $isDNIndexActive = $isDN && ($dnAction === '' || $dnAction === 'index');
  $navCurrent = static function (bool $active): string {
    return $active ? ' aria-current="page"' : '';
  };
?>
<?php if ($user): ?>
<a href="#main-content" class="skip-link visually-hidden-focusable">Skip to main content</a>
<div class="app app-layout">
  <!-- Sidebar -->
  <nav id="sidebar" class="tms-sidebar app-sidebar bg-dark text-white" aria-label="Primary navigation">
    <div class="p-2 border-bottom border-secondary d-flex align-items-center justify-content-between gap-2">
      <a class="navbar-brand text-white text-decoration-none fw-semibold mb-0" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>">TMS</a>
      <button class="btn btn-sm btn-outline-light sidebar-close-btn" type="button" title="Close menu" data-role="sidebar-close" aria-label="Close navigation menu"><i class="bi bi-x-lg" aria-hidden="true"></i><span class="d-none d-sm-inline ms-1 small">Close</span></button>
    </div>
    <?php if ($user): ?>
    <ul class="nav nav-pills flex-column p-2 small" role="list">
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='dashboard'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>"<?php echo $navCurrent($currentPage==='dashboard'); ?>><i class="bi bi-speedometer2" aria-hidden="true"></i><span>Dashboard</span></a></li>
      <?php if (($user['role'] ?? '') === 'admin'): ?>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='users'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=users'); ?>"<?php echo $navCurrent($currentPage==='users'); ?>><i class="bi bi-people" aria-hidden="true"></i><span>Users</span></a></li>
      <?php endif; ?>
      <li class="nav-item">
        <span class="nav-link text-white fw-semibold"><i class="bi bi-box-seam" aria-hidden="true"></i><span>Parcels</span></span>
        <ul class="nav flex-column ms-3 mb-1" aria-label="Parcels menu">
          <li class="nav-item"><a class="nav-link text-white py-1 <?php echo $isParcelsCreateActive?'active':''; ?>" href="<?php echo $parcelsCreateUrl; ?>"<?php echo $navCurrent($isParcelsCreateActive); ?>><i class="bi bi-plus-circle" aria-hidden="true"></i><span>New Parcel</span></a></li>
          <li class="nav-item"><a class="nav-link text-white py-1 <?php echo $isParcelsListActive?'active':''; ?>" href="<?php echo $parcelsListUrl; ?>"<?php echo $navCurrent($isParcelsListActive); ?>><i class="bi bi-card-list" aria-hidden="true"></i><span>Parcel Details</span></a></li>
          <?php if (Auth::isAdmin()): ?>
            <li class="nav-item"><a class="nav-link text-white py-1 <?php echo $isItemsActive?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=items'); ?>"<?php echo $navCurrent($isItemsActive); ?>><i class="bi bi-box2-heart" aria-hidden="true"></i><span>Add Items</span></a></li>
          <?php endif; ?>
        </ul>
      </li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='customers'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=customers'); ?>"<?php echo $navCurrent($currentPage==='customers'); ?>><i class="bi bi-person-lines-fill" aria-hidden="true"></i><span>Customers</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='delivery_routes'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_routes'); ?>"<?php echo $navCurrent($currentPage==='delivery_routes'); ?>><i class="bi bi-signpost" aria-hidden="true"></i><span>Delivery Routes</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='suppliers'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>"<?php echo $navCurrent($currentPage==='suppliers'); ?>><i class="bi bi-truck" aria-hidden="true"></i><span>Suppliers</span></a></li>
      <li class="nav-item nav-section">Delivery</li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isRouteActive?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>"<?php echo $navCurrent($isRouteActive); ?>><i class="bi bi-geo-alt" aria-hidden="true"></i><span>Route Planning</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isRouteVehiclesActive?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles'); ?>"<?php echo $navCurrent($isRouteVehiclesActive); ?>><i class="bi bi-truck-front" aria-hidden="true"></i><span>Vehicle Routes</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isDNIndexActive?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>"<?php echo $navCurrent($isDNIndexActive); ?>><i class="bi bi-receipt" aria-hidden="true"></i><span>Delivery Notes</span></a></li>
      <?php if (Auth::canManageExpenses()): ?>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='expenses'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=expenses'); ?>"<?php echo $navCurrent($currentPage==='expenses'); ?>><i class="bi bi-wallet2" aria-hidden="true"></i><span>Expenses</span></a></li>
      <?php endif; ?>
      <?php if (Auth::hasAnyRole(['admin','accountant'])): ?>
        <li class="nav-item nav-section">HR</li>
        <li class="nav-item"><a class="nav-link text-white <?php echo ($currentPage==='employees' && $action!=='payroll')?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=employees'); ?>"<?php echo $navCurrent($currentPage==='employees' && $action!=='payroll'); ?>><i class="bi bi-person-badge" aria-hidden="true"></i><span>Employee Details</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo ($currentPage==='employees' && $action==='payroll')?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=employees&action=payroll'); ?>"<?php echo $navCurrent($currentPage==='employees' && $action==='payroll'); ?>><i class="bi bi-clipboard-data" aria-hidden="true"></i><span>Salary Report</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='advances'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=advances'); ?>"<?php echo $navCurrent($currentPage==='advances'); ?>><i class="bi bi-cash-stack" aria-hidden="true"></i><span>Advances</span></a></li>
        <li class="nav-item nav-section">Accounting</li>
        <li class="nav-item"><a class="nav-link text-white <?php echo ($currentPage==='accounting' && ($_GET['action'] ?? 'dashboard')==='dashboard')?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=accounting&action=dashboard'); ?>"<?php echo $navCurrent($currentPage==='accounting' && ($_GET['action'] ?? 'dashboard')==='dashboard'); ?>><i class="bi bi-calculator" aria-hidden="true"></i><span>Accounting</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo ($currentPage==='accounting' && ($_GET['action'] ?? '')==='entry')?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=accounting&action=entry&voucher_type=PAYMENT&payment_mode=CASH'); ?>"<?php echo $navCurrent($currentPage==='accounting' && ($_GET['action'] ?? '')==='entry'); ?>><i class="bi bi-receipt-cutoff" aria-hidden="true"></i><span>Voucher Entry</span></a></li>
      <?php endif; ?>
      <li class="nav-item nav-section">Tools</li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='search'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=search'); ?>"<?php echo $navCurrent($currentPage==='search'); ?>><i class="bi bi-search" aria-hidden="true"></i><span>Search</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='reminders'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=reminders'); ?>"<?php echo $navCurrent($currentPage==='reminders'); ?>><i class="bi bi-bell" aria-hidden="true"></i><span>Reminders</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='reports'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=reports'); ?>"<?php echo $navCurrent($currentPage==='reports'); ?>><i class="bi bi-bar-chart-line" aria-hidden="true"></i><span>Reports</span></a></li>
      <?php if (($user['role'] ?? '') === 'admin'): ?>
        <li class="nav-item nav-section">Admin</li>
        <li class="nav-item"><a class="nav-link text-white <?php echo ($currentPage==='settings' || $currentPage==='branches')?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=settings'); ?>"<?php echo $navCurrent($currentPage==='settings' || $currentPage==='branches'); ?>><i class="bi bi-gear" aria-hidden="true"></i><span>Settings</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='backup'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=backup'); ?>"<?php echo $navCurrent($currentPage==='backup'); ?>><i class="bi bi-hdd" aria-hidden="true"></i><span>Backups</span></a></li>
      <?php endif; ?>
      <?php if ($user): ?>
        <li class="nav-item mt-2 px-2"><hr class="border-secondary opacity-50"></li>
        <li class="nav-item px-2 mb-2 text-secondary small branch-meta">Branch: <?php echo htmlspecialchars($user['branch_name'] ?? ''); ?> (<?php echo $user['is_main_branch'] ? 'Main' : 'Branch'; ?>)</li>
        <li class="nav-item px-2 mb-3"><a class="btn btn-sm btn-outline-warning w-100 btn-logout" href="<?php echo Helpers::baseUrl('index.php?page=logout'); ?>"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a></li>
      <?php endif; ?>
    </ul>
    <?php endif; ?>
  </nav>

  <!-- Main content -->
  <main id="main-content" class="content-wrapper app-content flex-grow-1" role="main" tabindex="-1">
    <button class="btn btn-outline-secondary sidebar-toggle-floating" type="button" data-role="sidebar-open" aria-label="Open navigation menu" aria-controls="sidebar" aria-expanded="false">
      <i class="bi bi-list" aria-hidden="true"></i><span class="visually-hidden">Open menu</span>
    </button>
    <div class="sidebar-overlay" data-role="sidebar-overlay" role="presentation" aria-hidden="true"></div>
    <?php
      $uiCompany = Helpers::company();
      $uiHeaderAddr = Helpers::companyHeaderAddressLines('', 3);
      $pageTitle = 'Dashboard';
      if ($currentPage && $currentPage !== 'dashboard') {
        $pageTitle = ucwords(str_replace('_', ' ', (string)$currentPage));
      }
    ?>
    <div class="topbar" role="banner">
      <div class="container-fluid px-2">
        <div class="topbar-inner">
          <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1 topbar-title-block">
            <button class="btn btn-outline-secondary d-flex align-items-center gap-1 flex-shrink-0" type="button" title="Open navigation menu" data-role="sidebar-open" aria-label="Open navigation menu" aria-controls="sidebar" aria-expanded="false"><i class="bi bi-list" aria-hidden="true"></i><span class="d-none d-sm-inline small">Menu</span></button>
            <div class="min-w-0 flex-grow-1">
              <p class="page-title text-truncate mb-0"><?php echo htmlspecialchars($pageTitle); ?></p>
              <?php /* subtitle hidden (address/phone) */ ?>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-shrink-0 topbar-actions">
            <button class="btn btn-outline-secondary d-flex align-items-center gap-1" type="button" id="themeToggleBtn" aria-label="Toggle theme" title="Toggle light and dark mode">
              <i class="bi bi-moon-stars" aria-hidden="true"></i><span class="d-none d-md-inline small">Theme</span>
            </button>
            <button class="btn btn-outline-secondary d-flex align-items-center gap-1" type="button" title="Notifications (coming soon)" aria-label="Notifications"><i class="bi bi-bell" aria-hidden="true"></i><span class="d-none d-md-inline small">Alerts</span></button>
            <div class="dropdown">
              <button class="btn btn-outline-secondary d-flex align-items-center gap-1 dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Account menu" aria-label="Account menu">
                <i class="bi bi-person-circle" aria-hidden="true"></i><span class="d-none d-lg-inline small text-truncate" style="max-width:8rem"><?php echo htmlspecialchars((string)($user['username'] ?? ($user['name'] ?? 'Account'))); ?></span>
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

    <div class="container-fluid px-2 py-2 flex-grow-1 d-flex flex-column min-vh-0">
      
<?php else: ?>
<a href="#main-content" class="skip-link visually-hidden-focusable">Skip to main content</a>
<main id="main-content" class="container-fluid px-2 py-3" tabindex="-1" role="main">
<?php endif; ?>
