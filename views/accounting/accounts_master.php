<?php
$openingMode = (($accAction ?? '') === 'opening_balances');
?>
<div class="acc-accounts-master">
  <div class="acc-toolbar">
    <div>
      <label class="form-label" for="accCoaSearch">Search</label>
      <input type="search" class="form-control form-control-sm" id="accCoaSearch" placeholder="Code or name…">
    </div>
    <div>
      <label class="form-label" for="accCoaGroupFilter">Group Type</label>
      <select class="form-select form-select-sm" id="accCoaGroupFilter">
        <option value="">All</option>
        <option value="ASSETS">Assets</option>
        <option value="LIABILITIES">Liabilities</option>
        <option value="CAPITAL">Equity</option>
        <option value="INCOME">Income</option>
        <option value="EXPENSES">Expenses</option>
      </select>
    </div>
    <div class="ms-auto d-flex gap-2">
      <button type="button" class="btn btn-primary btn-sm" id="accCoaNewBtn"><i class="bi bi-plus-lg"></i> New Account</button>
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
  </div>

  <div class="acc-table-wrap">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 datatable" id="accCoaTable">
        <thead>
          <tr>
            <th>Code</th>
            <th>Account Name</th>
            <th>Group</th>
            <th>Type</th>
            <?php if ($openingMode): ?>
              <th class="text-end">Opening Balance</th>
              <th>Dr/Cr</th>
            <?php endif; ?>
            <th class="text-end">Current Balance</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="accAccountModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="accAccountForm">
      <div class="modal-header">
        <h5 class="modal-title" id="accAccountModalTitle">Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-2">
        <input type="hidden" name="id" id="accAccountId">
        <div class="col-md-4">
          <label class="form-label">Code</label>
          <input type="text" class="form-control" name="account_code" id="accAccountCode" required>
        </div>
        <div class="col-md-8">
          <label class="form-label">Account Name</label>
          <input type="text" class="form-control" name="account_name" id="accAccountName" required>
        </div>
        <div class="col-12">
          <label class="form-label">Account Group</label>
          <select class="form-select acc-select2" name="account_group_id" id="accAccountGroup" required></select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Opening Balance</label>
          <input type="number" step="0.01" class="form-control" name="opening_balance" id="accOpeningBalance" value="0">
        </div>
        <div class="col-md-6">
          <label class="form-label">Balance Type</label>
          <select class="form-select" name="opening_balance_type" id="accOpeningType">
            <option value="DEBIT">Debit</option>
            <option value="CREDIT">Credit</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Status</label>
          <select class="form-select" name="is_active" id="accAccountActive">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Account</button>
      </div>
    </form>
  </div>
</div>

<script>
window.TMS_ACCOUNTS_MASTER = { openingMode: <?php echo $openingMode ? 'true' : 'false'; ?> };
</script>
<script src="<?php echo Helpers::baseUrl('assets/js/accounting-accounts.js?v=1'); ?>"></script>
