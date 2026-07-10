<div class="acc-dashboard">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="small text-muted" id="accDashUpdated">Loading dashboard…</div>
    <button type="button" class="btn btn-sm btn-outline-primary" id="accDashRefresh"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</button>
  </div>
  <div class="acc-kpi-grid mb-3" id="accDashKpis">
    <div class="acc-kpi"><div class="acc-kpi-label">Cash Balance</div><div class="acc-kpi-value" data-kpi="cash">—</div></div>
    <div class="acc-kpi"><div class="acc-kpi-label">Bank Balance</div><div class="acc-kpi-value" data-kpi="bank">—</div></div>
    <div class="acc-kpi"><div class="acc-kpi-label">Accounts Receivable</div><div class="acc-kpi-value" data-kpi="receivable">—</div></div>
    <div class="acc-kpi"><div class="acc-kpi-label">Accounts Payable</div><div class="acc-kpi-value" data-kpi="payable">—</div></div>
    <div class="acc-kpi"><div class="acc-kpi-label">Revenue (MTD)</div><div class="acc-kpi-value positive" data-kpi="revenue">—</div></div>
    <div class="acc-kpi"><div class="acc-kpi-label">Expenses (MTD)</div><div class="acc-kpi-value negative" data-kpi="expenses">—</div></div>
    <div class="acc-kpi"><div class="acc-kpi-label">Net Profit (MTD)</div><div class="acc-kpi-value" data-kpi="net_profit">—</div></div>
    <div class="acc-kpi"><div class="acc-kpi-label">Pending Drafts</div><div class="acc-kpi-value" data-kpi="pending_drafts">—</div></div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-8">
      <div class="acc-card h-100">
        <div class="acc-card-header">Monthly Financial Trends</div>
        <div class="acc-card-body"><canvas id="accTrendChart" height="120"></canvas></div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="acc-card h-100">
        <div class="acc-card-header">Revenue vs Expenses (MTD vs Last Month)</div>
        <div class="acc-card-body"><canvas id="accRevExpChart" height="180"></canvas></div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="acc-card">
        <div class="acc-card-header d-flex justify-content-between align-items-center">
          <span>Recent Transactions</span>
          <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars(AccountingModule::url('vouchers')); ?>">View all</a>
        </div>
        <div class="acc-card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" id="accRecentTxTable">
              <thead><tr><th>Date</th><th>Voucher</th><th>Type</th><th>Status</th><th class="text-end">Amount</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="acc-card mb-3">
        <div class="acc-card-header">Quick Actions</div>
        <div class="acc-card-body d-grid gap-2">
          <a class="btn btn-outline-primary btn-sm text-start" href="<?php echo htmlspecialchars(AccountingModule::url('entry', ['voucher_type' => 'PAYMENT', 'payment_mode' => 'CASH'])); ?>"><i class="bi bi-cash me-2"></i>Cash Payment Voucher</a>
          <a class="btn btn-outline-primary btn-sm text-start" href="<?php echo htmlspecialchars(AccountingModule::url('entry', ['voucher_type' => 'JOURNAL'])); ?>"><i class="bi bi-journal-text me-2"></i>Journal Entry</a>
          <a class="btn btn-outline-secondary btn-sm text-start" href="<?php echo htmlspecialchars(AccountingModule::url('chart')); ?>"><i class="bi bi-list-nested me-2"></i>Chart of Accounts</a>
          <a class="btn btn-outline-secondary btn-sm text-start" href="<?php echo htmlspecialchars(AccountingModule::url('trial_balance')); ?>"><i class="bi bi-balance-scale me-2"></i>Trial Balance</a>
        </div>
      </div>
      <div class="acc-card">
        <div class="acc-card-header">TMS Integration Status</div>
        <div class="acc-card-body small" id="accIntegrationStatus">
          <div class="d-flex justify-content-between py-1 border-bottom"><span>Payments Module</span><span class="text-success">Linked</span></div>
          <div class="d-flex justify-content-between py-1 border-bottom"><span>Expenses Module</span><span class="text-success">Linked</span></div>
          <div class="d-flex justify-content-between py-1 border-bottom"><span>Customers / Cashbook</span><span class="text-success">Active</span></div>
          <div class="d-flex justify-content-between py-1"><span>Transfer Vouchers API</span><span class="text-success">Active</span></div>
        </div>
      </div>
    </div>
  </div>
</div>
