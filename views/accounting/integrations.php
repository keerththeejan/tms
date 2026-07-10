<p class="text-muted mb-3">Accounting integrates with existing TMS operational modules. Operational data continues through current business logic; use vouchers and ledgers here for formal double-entry records.</p>

<div class="acc-integration-grid">
  <a class="acc-integration-card" href="<?php echo Helpers::baseUrl('index.php?page=accounting&action=entry&voucher_type=PAYMENT&payment_mode=CASH'); ?>">
    <i class="bi bi-currency-dollar"></i>
    <div class="fw-semibold mt-2">Customer Receipts</div>
    <div class="small text-muted">Record customer receipts via accounting vouchers</div>
  </a>
  <a class="acc-integration-card" href="<?php echo Helpers::baseUrl('index.php?page=expenses'); ?>">
    <i class="bi bi-wallet2"></i>
    <div class="fw-semibold mt-2">Expenses & Purchases</div>
    <div class="small text-muted">Operational expense posting</div>
  </a>
  <a class="acc-integration-card" href="<?php echo Helpers::baseUrl('index.php?page=customers'); ?>">
    <i class="bi bi-person-lines-fill"></i>
    <div class="fw-semibold mt-2">Customers & AR</div>
    <div class="small text-muted">Customer master with linked cashbook accounts</div>
  </a>
  <a class="acc-integration-card" href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>">
    <i class="bi bi-truck"></i>
    <div class="fw-semibold mt-2">Suppliers & AP</div>
    <div class="small text-muted">Supplier master records</div>
  </a>
  <a class="acc-integration-card" href="<?php echo Helpers::baseUrl('index.php?page=employees&action=payroll'); ?>">
    <i class="bi bi-cash-coin"></i>
    <div class="fw-semibold mt-2">HR / Payroll</div>
    <div class="small text-muted">Salaries and employee accounts</div>
  </a>
  <a class="acc-integration-card" href="<?php echo Helpers::baseUrl('index.php?page=parcels'); ?>">
    <i class="bi bi-box-seam"></i>
    <div class="fw-semibold mt-2">Sales / Freight</div>
    <div class="small text-muted">Parcel billing and freight operations</div>
  </a>
  <a class="acc-integration-card" href="<?php echo Helpers::baseUrl('index.php?page=accounting&action=entry&voucher_type=TRANSFER'); ?>">
    <i class="bi bi-shuffle"></i>
    <div class="fw-semibold mt-2">Transfer Vouchers</div>
    <div class="small text-muted">Unified transfer voucher entry (API-backed posting preserved)</div>
  </a>
  <a class="acc-integration-card" href="<?php echo Helpers::baseUrl('index.php?page=reports'); ?>">
    <i class="bi bi-bar-chart-line"></i>
    <div class="fw-semibold mt-2">TMS Operational Reports</div>
    <div class="small text-muted">Transport and operations reporting</div>
  </a>
</div>
