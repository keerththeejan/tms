<div class="acc-card">
  <div class="acc-card-header">Accounting Configuration</div>
  <div class="acc-card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Fiscal Year</label>
        <input type="text" class="form-control" value="<?php echo date('Y'); ?>" readonly>
        <div class="form-text">Derived from voucher fiscal year field in existing posting logic.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Default Voucher Series</label>
        <input type="text" class="form-control" value="Main" readonly>
      </div>
      <div class="col-12">
        <div class="alert alert-info mb-0">
          <strong>Preserved backend services</strong>
          <ul class="mb-0 mt-2">
            <li>Double-entry posting via <code>AccountingController::postVoucher()</code></li>
            <li>Cashbook API at <code>page=api_cashbook</code></li>
            <li>Transfer voucher API at <code>page=transfer_voucher&amp;tv_action=...</code></li>
            <li>Customer/employee cashbook account provisioning unchanged</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
