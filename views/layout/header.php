<?php
$user = Auth::user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Transport Management System</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.1.8/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
  <link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/tms-design-system.css'); ?>">
  <?php if (in_array($_GET['page'] ?? '', ['employees', 'salaries', 'advances'], true)): ?>
  <link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/hr-responsive.css'); ?>">
  <?php endif; ?>
  <?php
  $hdrPage = $_GET['page'] ?? '';
  $hdrEmpAction = $_GET['action'] ?? '';
  if ($hdrPage === 'employees' && ($hdrEmpAction === '' || $hdrEmpAction === 'index')): ?>
  <link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/employees-page.css'); ?>">
  <?php endif; ?>
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
  $navCurrent = static function (bool $active): string {
    return $active ? ' aria-current="page"' : '';
  };
  /** Full-width main + topbar (no max-width gutter) for Parcels list / new / edit, Cash Book, Employees list */
  $parcelsFullWidth = ($currentPage === 'parcels' || $currentPage === 'cashbook' || ($currentPage === 'employees' && $action !== 'payroll'));
?>
<?php if ($user): ?>
<a href="#main-content" class="skip-link visually-hidden-focusable">Skip to main content</a>
<div class="d-flex app-shell">
  <!-- Sidebar -->
  <nav id="sidebar" class="bg-dark text-white position-fixed vh-100" style="top:0; left:0; overflow-y:auto;" aria-label="Primary navigation">
    <div class="p-3 border-bottom border-secondary d-flex align-items-center justify-content-between gap-2">
      <a class="navbar-brand text-white text-decoration-none fw-semibold" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>">TMS</a>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-outline-light d-none d-lg-inline-flex" type="button" title="Collapse sidebar" data-role="sidebar-collapse" aria-label="Collapse sidebar"><i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i></button>
        <button class="btn btn-sm btn-outline-light sidebar-close-btn" type="button" title="Close menu" data-role="sidebar-close" aria-label="Close menu"><i class="bi bi-x" aria-hidden="true"></i></button>
      </div>
    </div>
    <?php if ($user): ?>
    <ul class="nav nav-pills flex-column p-2 small" role="list">
      <?php if ($currentPage !== 'cashbook'): ?>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='dashboard'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>"<?php echo $navCurrent($currentPage==='dashboard'); ?>><i class="bi bi-speedometer2" aria-hidden="true"></i><span>Dashboard</span></a></li>
      <?php endif; ?>
      <?php if (($user['role'] ?? '') === 'admin'): ?>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='users'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=users'); ?>"<?php echo $navCurrent($currentPage==='users'); ?>><i class="bi bi-people" aria-hidden="true"></i><span>Users</span></a></li>
      <?php endif; ?>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isParcelsCreateActive?'active':''; ?>" href="<?php echo $parcelsCreateUrl; ?>"<?php echo $navCurrent($isParcelsCreateActive); ?>><i class="bi bi-box-seam" aria-hidden="true"></i><span>Parcels</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isParcelsListActive?'active':''; ?>" href="<?php echo $parcelsListUrl; ?>"<?php echo $navCurrent($isParcelsListActive); ?>><i class="bi bi-card-list" aria-hidden="true"></i><span>Parcel Details</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='customers'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=customers'); ?>"<?php echo $navCurrent($currentPage==='customers'); ?>><i class="bi bi-person-lines-fill" aria-hidden="true"></i><span>Customers</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='delivery_routes'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_routes'); ?>"<?php echo $navCurrent($currentPage==='delivery_routes'); ?>><i class="bi bi-signpost" aria-hidden="true"></i><span>Delivery Routes</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='suppliers'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>"<?php echo $navCurrent($currentPage==='suppliers'); ?>><i class="bi bi-truck" aria-hidden="true"></i><span>Suppliers</span></a></li>
      <li class="nav-item nav-section">Delivery</li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isRouteActive?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>"<?php echo $navCurrent($isRouteActive); ?>><i class="bi bi-geo-alt" aria-hidden="true"></i><span>Route Planning</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isRouteVehiclesActive?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles'); ?>"<?php echo $navCurrent($isRouteVehiclesActive); ?>><i class="bi bi-truck-front" aria-hidden="true"></i><span>Vehicle Routes</span></a></li>
      <li class="nav-item"><a class="nav-link text-white <?php echo $isDNIndexActive?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>"<?php echo $navCurrent($isDNIndexActive); ?>><i class="bi bi-receipt" aria-hidden="true"></i><span>Delivery Notes</span></a></li>
      <?php if (Auth::canCollectPayments()): ?>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='payments'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=payments'); ?>"<?php echo $navCurrent($currentPage==='payments'); ?>><i class="bi bi-currency-dollar" aria-hidden="true"></i><span>Payments</span></a></li>
      <?php endif; ?>
      <?php if (Auth::canManageExpenses()): ?>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='expenses'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=expenses'); ?>"<?php echo $navCurrent($currentPage==='expenses'); ?>><i class="bi bi-wallet2" aria-hidden="true"></i><span>Expenses</span></a></li>
      <?php endif; ?>
      <?php if (Auth::hasAnyRole(['admin','accountant'])): ?>
        <li class="nav-item nav-section">HR</li>
        <li class="nav-item"><a class="nav-link text-white <?php echo ($currentPage==='employees' && $action!=='payroll')?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=employees'); ?>"<?php echo $navCurrent($currentPage==='employees' && $action!=='payroll'); ?>><i class="bi bi-person-badge" aria-hidden="true"></i><span>Employee Details</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo ($currentPage==='employees' && $action==='payroll')?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=employees&action=payroll'); ?>"<?php echo $navCurrent($currentPage==='employees' && $action==='payroll'); ?>><i class="bi bi-clipboard-data" aria-hidden="true"></i><span>Salary Report</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='salaries'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=salaries'); ?>"<?php echo $navCurrent($currentPage==='salaries'); ?>><i class="bi bi-cash-coin" aria-hidden="true"></i><span>Salaries</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='advances'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=advances'); ?>"<?php echo $navCurrent($currentPage==='advances'); ?>><i class="bi bi-cash-stack" aria-hidden="true"></i><span>Advances</span></a></li>
        <li class="nav-item nav-section">Accounts</li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='cashbook'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=cashbook'); ?>"<?php echo $navCurrent($currentPage==='cashbook'); ?>><i class="bi bi-cash-stack" aria-hidden="true"></i><span>Cash Book</span></a></li>
        <?php
          $accTab = $_GET['tab'] ?? 'all';
          $accActive = ($currentPage === 'accounts');
          $accOpen = $accActive ? ' show' : '';
        ?>
        <li class="nav-item">
          <button class="nav-link text-white w-100 text-start d-flex align-items-center justify-content-between <?php echo $accActive?'active':''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarAccountsSub" aria-expanded="<?php echo $accActive ? 'true' : 'false'; ?>" aria-controls="sidebarAccountsSub"<?php echo $navCurrent($accActive); ?>>
            <span class="d-inline-flex align-items-center gap-2"><i class="bi bi-journal-richtext" aria-hidden="true"></i><span>Accounts</span></span>
            <i class="bi bi-chevron-down small opacity-75" aria-hidden="true"></i>
          </button>
          <div class="collapse<?php echo $accOpen; ?>" id="sidebarAccountsSub">
            <ul class="nav nav-pills flex-column ms-4 my-1 gap-1" role="list">
              <li class="nav-item">
                <a class="nav-link text-white py-1 <?php echo $accActive && ($accTab==='all' || $accTab==='') ? 'active' : ''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=accounts&tab=all'); ?>">
                  <span>All Accounts</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link text-white py-1 <?php echo $accActive && $accTab==='add' ? 'active' : ''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=accounts&tab=add'); ?>">
                  <span>Add Account</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link text-white py-1 <?php echo $accActive && $accTab==='statement' ? 'active' : ''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=accounts&tab=statement'); ?>">
                  <span>Account Statement</span>
                </a>
              </li>
            </ul>
          </div>
        </li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='daybook'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=daybook'); ?>"<?php echo $navCurrent($currentPage==='daybook'); ?>><i class="bi bi-journal-text" aria-hidden="true"></i><span>Daybook</span></a></li>
        <li class="nav-item"><a class="nav-link text-white <?php echo $currentPage==='ledger'?'active':''; ?>" href="<?php echo Helpers::baseUrl('index.php?page=ledger'); ?>"<?php echo $navCurrent($currentPage==='ledger'); ?>><i class="bi bi-journal-check" aria-hidden="true"></i><span>Account Ledger</span></a></li>
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
  <main id="main-content" class="content-wrapper flex-grow-1" style="min-height: 100vh;" role="main" tabindex="-1">
    <div class="sidebar-overlay" data-role="sidebar-overlay" role="presentation" aria-hidden="true"></div>
    <?php
      $uiCompany = Helpers::company();
      $uiHeaderAddr = Helpers::companyHeaderAddressLines('', 3);
      $pageTitle = 'Dashboard';
      if ($currentPage && $currentPage !== 'dashboard') {
        $pageTitle = $currentPage === 'cashbook' ? 'Cash Book' : ucwords(str_replace('_', ' ', (string)$currentPage));
      }
    ?>
    <div class="topbar" role="banner">
      <div class="container-fluid <?php echo $parcelsFullWidth ? 'parcels-topbar-fluid' : ''; ?>">
        <div class="topbar-inner">
          <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1 topbar-title-block">
            <button class="btn btn-outline-secondary icon-btn d-none d-lg-inline-flex flex-shrink-0" type="button" title="Toggle sidebar" data-role="sidebar-collapse" aria-label="Toggle sidebar"><i class="bi bi-layout-sidebar-inset"></i></button>
            <button class="btn btn-outline-secondary icon-btn d-lg-none flex-shrink-0" type="button" title="Open menu" data-role="sidebar-open" aria-label="Open navigation menu" aria-controls="sidebar" aria-expanded="false"><i class="bi bi-list" aria-hidden="true"></i></button>
            <div class="min-w-0 flex-grow-1">
              <p class="page-title text-truncate mb-0"><?php echo htmlspecialchars($pageTitle); ?></p>
              <?php if (!empty($uiHeaderAddr)): ?>
                <?php $addrLine = implode(' | ', $uiHeaderAddr); ?>
                <p class="page-subtitle mb-0 d-none d-md-block text-truncate" title="<?php echo htmlspecialchars($addrLine); ?>"><?php echo htmlspecialchars($addrLine); ?></p>
              <?php endif; ?>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-shrink-0 topbar-actions">
            <?php if (Auth::canCreateParcels()): ?>
              <a class="btn btn-primary btn-sm" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=new'); ?>" title="New parcel">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span class="d-none d-md-inline ms-1">Parcel</span>
              </a>
            <?php endif; ?>
            <button class="btn btn-outline-secondary icon-btn" type="button" title="Notifications" aria-label="Notifications"><i class="bi bi-bell" aria-hidden="true"></i></button>
            <div class="dropdown">
              <button class="btn btn-outline-secondary icon-btn dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Profile" aria-label="User account menu">
                <i class="bi bi-person-circle" aria-hidden="true"></i>
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

    <div class="container-fluid mt-3 py-1 <?php echo $parcelsFullWidth ? 'parcels-main-fluid' : ''; ?>">
      
<?php else: ?>
<a href="#main-content" class="skip-link visually-hidden-focusable">Skip to main content</a>
<main id="main-content" class="container-fluid mt-3" tabindex="-1" role="main">
<?php endif; ?>
