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
      <button type="button" class="btn btn-outline-primary btn-sm" id="accCoaNewGroupBtn"><i class="bi bi-diagram-3"></i> Add Group</button>
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
          <label class="form-label">Account Group <span class="text-danger">*</span></label>
          <div class="d-flex gap-2 align-items-start">
            <div class="flex-grow-1">
              <select class="form-select acc-select2" name="account_group_id" id="accAccountGroup" required data-placeholder="Select account group…"></select>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm text-nowrap" id="accAddGroupInlineBtn" title="Add account group">+ Group</button>
          </div>
          <div id="accGroupEmptyState" class="alert alert-warning py-2 px-3 mt-2 mb-0 d-none small">
            <div class="fw-semibold mb-1">No Account Groups Available</div>
            <div class="mb-2">Default groups (Assets, Liabilities, Equity, Income, Expenses) can be created automatically.</div>
            <button type="button" class="btn btn-sm btn-warning" id="accSeedGroupsBtn">Create Default Groups</button>
          </div>
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

<div class="modal fade" id="accGroupModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="accGroupForm">
      <div class="modal-header">
        <h5 class="modal-title">New Account Group</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-2">
        <div class="col-md-5">
          <label class="form-label">Group Code</label>
          <input type="text" class="form-control" name="group_code" id="accGroupCode" maxlength="40" placeholder="Auto if empty">
        </div>
        <div class="col-md-7">
          <label class="form-label">Group Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="group_name" id="accGroupName" required>
        </div>
        <div class="col-12">
          <label class="form-label">Parent Group</label>
          <select class="form-select" name="parent_id" id="accGroupParent">
            <option value="">— None (top level) —</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Group Type</label>
          <select class="form-select" name="group_type" id="accGroupType">
            <option value="ASSETS">Assets</option>
            <option value="LIABILITIES">Liabilities</option>
            <option value="CAPITAL">Equity</option>
            <option value="INCOME">Income</option>
            <option value="EXPENSES" selected>Expenses</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Nature</label>
          <select class="form-select" name="nature" id="accGroupNature">
            <option value="DEBIT">Debit</option>
            <option value="CREDIT">Credit</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Group</button>
      </div>
    </form>
  </div>
</div>

<script>
window.TMS_ACCOUNTS_MASTER = { openingMode: <?php echo $openingMode ? 'true' : 'false'; ?> };
</script>
<script src="<?php echo Helpers::baseUrl('assets/js/accounting-accounts.js?v=3'); ?>"></script>
