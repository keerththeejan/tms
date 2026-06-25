<?php
$openingMode = (($accAction ?? '') === 'opening_balances');
$accAccountGroupsBoot = [];
$accGroupsBootError = null;
$accLoadAccountsJs = true;
try {
    $accAccountGroupsBoot = AccountGroupRepository::listForAccountForm(Database::pdo());
} catch (Throwable $e) {
    $accGroupsBootError = $e->getMessage();
}
$accGroupOptionsHtml = AccountGroupRepository::renderSelectOptionsHtml($accAccountGroupsBoot);
$colCount = $openingMode ? 9 : 7;
?>
<div class="acc-accounts-master">
  <div class="acc-toolbar">
    <div>
      <label class="form-label" for="accCoaSearch"><i class="bi bi-search"></i> Search</label>
      <input type="search" class="form-control form-control-sm" id="accCoaSearch" placeholder="Code, name, or group…" autocomplete="off">
    </div>
    <div>
      <label class="form-label" for="accCoaGroupFilter">Group Type</label>
      <select class="form-select form-select-sm" id="accCoaGroupFilter" data-enhance="false">
        <option value="">All Types</option>
        <option value="ASSETS">Assets</option>
        <option value="LIABILITIES">Liabilities</option>
        <option value="CAPITAL">Equity</option>
        <option value="INCOME">Income</option>
        <option value="EXPENSES">Expenses</option>
      </select>
    </div>
    <div>
      <label class="form-label" for="accCoaStatusFilter">Status</label>
      <select class="form-select form-select-sm" id="accCoaStatusFilter" data-enhance="false">
        <option value="">All</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>
    <div class="ms-auto d-flex gap-2 flex-wrap">
      <button type="button" class="btn btn-outline-primary btn-sm" id="accCoaNewGroupBtn"><i class="bi bi-diagram-3"></i> Add Group</button>
      <button type="button" class="btn btn-primary btn-sm" id="accCoaNewBtn"><i class="bi bi-plus-lg"></i> New Account</button>
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
  </div>

  <div class="acc-table-wrap acc-coa-table-wrap">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 acc-coa-table" id="accCoaTable" data-col-count="<?php echo (int) $colCount; ?>">
        <thead class="acc-coa-thead">
          <tr>
            <th data-sort="account_code" class="acc-sortable">Code <i class="bi bi-arrow-down-up small opacity-50"></i></th>
            <th data-sort="account_name" class="acc-sortable">Account Name <i class="bi bi-arrow-down-up small opacity-50"></i></th>
            <th data-sort="group_name" class="acc-sortable">Group <i class="bi bi-arrow-down-up small opacity-50"></i></th>
            <th data-sort="group_type" class="acc-sortable">Type <i class="bi bi-arrow-down-up small opacity-50"></i></th>
            <?php if ($openingMode): ?>
              <th class="text-end">Opening Balance</th>
              <th>Dr/Cr</th>
            <?php endif; ?>
            <th data-sort="current_balance" class="text-end acc-sortable">Current Balance <i class="bi bi-arrow-down-up small opacity-50"></i></th>
            <th data-sort="is_active" class="acc-sortable">Status <i class="bi bi-arrow-down-up small opacity-50"></i></th>
            <th class="text-end" style="width:120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="<?php echo (int) $colCount; ?>" class="text-center text-muted py-4">
              <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Loading accounts…
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="acc-coa-pagination d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 border-top">
      <div class="small text-muted" id="accCoaPageInfo">—</div>
      <div class="btn-group btn-group-sm" role="group" aria-label="Pagination">
        <button type="button" class="btn btn-outline-secondary" id="accCoaPrevBtn" disabled><i class="bi bi-chevron-left"></i></button>
        <button type="button" class="btn btn-outline-secondary" id="accCoaNextBtn" disabled><i class="bi bi-chevron-right"></i></button>
      </div>
    </div>
  </div>
</div>
