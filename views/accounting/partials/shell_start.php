<?php
/** @var string $accAction */
/** @var string $accTitle */
/** @var array $accNav */
/** @var string $accBaseUrl */
/** @var string $accCsrf */
/** @var bool $accFullBleed */
$accCssPath = __DIR__ . '/../../../public/assets/css/accounting-module.css';
$accJsPath = __DIR__ . '/../../../public/assets/js/accounting-module.js';
$accCssVer = is_file($accCssPath) ? (string) filemtime($accCssPath) : '1';
$accJsVer = is_file($accJsPath) ? (string) filemtime($accJsPath) : '1';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/accounting-module.css?v=' . rawurlencode($accCssVer)); ?>">

<div class="acc-module<?php echo !empty($accFullBleed) ? ' acc-module--full-bleed' : ''; ?>" id="accModule" data-theme="light">
  <aside class="acc-sidebar d-none d-lg-flex flex-column" aria-label="Accounting navigation">
    <div class="acc-sidebar-brand">
      <i class="bi bi-calculator" aria-hidden="true"></i>
      <div>
        <div class="acc-sidebar-title">Accounting</div>
        <div class="acc-sidebar-sub">Enterprise ERP</div>
      </div>
    </div>
    <nav class="acc-sidebar-nav flex-grow-1 overflow-auto">
      <?php foreach ($accNav as $section): ?>
        <div class="acc-nav-section">
          <div class="acc-nav-section-label">
            <i class="bi <?php echo htmlspecialchars($section['icon']); ?>" aria-hidden="true"></i>
            <?php echo htmlspecialchars($section['label']); ?>
          </div>
          <ul class="nav flex-column acc-nav-list">
            <?php foreach ($section['items'] as $item):
              $itemAction = (string) ($item['action'] ?? '');
              $itemParams = (array) ($item['params'] ?? []);
              $isActive = ($accAction === $itemAction);
              if ($itemAction === 'entry' && $accAction === 'entry') {
                  $isActive = (($itemParams['voucher_type'] ?? '') === ($_GET['voucher_type'] ?? 'PAYMENT'))
                      && (($itemParams['payment_mode'] ?? '') === ($_GET['payment_mode'] ?? ($itemParams['voucher_type'] === 'JOURNAL' ? '' : 'CASH')));
              }
              if ($itemAction === 'chart' && $accAction === 'opening_balances') {
                  $isActive = false;
              }
              if ($itemAction === 'opening_balances' && $accAction === 'opening_balances') {
                  $isActive = true;
              }
            ?>
              <li class="nav-item">
                <a class="nav-link<?php echo $isActive ? ' active' : ''; ?>"
                   href="<?php echo htmlspecialchars(AccountingModule::url($itemAction, $itemParams)); ?>">
                  <i class="bi <?php echo htmlspecialchars($item['icon']); ?>" aria-hidden="true"></i>
                  <?php echo htmlspecialchars($item['label']); ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </nav>
    <div class="acc-sidebar-footer">
      <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="accThemeToggle" title="Toggle dark mode">
        <i class="bi bi-moon-stars" aria-hidden="true"></i> Dark mode
      </button>
    </div>
  </aside>

  <div class="acc-main">
    <header class="acc-topbar">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="dropdown d-lg-none">
          <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">Menu</button>
          <ul class="dropdown-menu">
            <?php foreach ($accNav as $section): ?>
              <li><h6 class="dropdown-header"><?php echo htmlspecialchars($section['label']); ?></h6></li>
              <?php foreach ($section['items'] as $item): ?>
                <li>
                  <a class="dropdown-item" href="<?php echo htmlspecialchars(AccountingModule::url((string) $item['action'], (array) ($item['params'] ?? []))); ?>">
                    <?php echo htmlspecialchars($item['label']); ?>
                  </a>
                </li>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </ul>
        </div>
        <h1 class="acc-page-title mb-0"><?php echo htmlspecialchars($accTitle); ?></h1>
      </div>
      <div class="acc-topbar-actions d-flex align-items-center gap-2">
        <a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars(AccountingModule::url('entry', ['voucher_type' => 'PAYMENT', 'payment_mode' => 'CASH'])); ?>">
          <i class="bi bi-plus-lg" aria-hidden="true"></i> New Voucher
        </a>
      </div>
    </header>
    <div class="acc-content">
    <?php
      if (!isset($reportTitle) || trim((string)$reportTitle) === '') {
          $reportTitle = $accTitle ?? 'Accounting Report';
      }
      include __DIR__ . '/../../partials/report/embed_block.php';
    ?>
