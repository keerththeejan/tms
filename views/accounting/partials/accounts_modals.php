<?php
/** Chart of Accounts modals — rendered at body level (outside .acc-module overflow). */
?>
<div class="modal fade acc-coa-modal" id="accAccountModal" tabindex="-1" aria-labelledby="accAccountModalTitle" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
  <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
    <form class="modal-content" id="accAccountForm" novalidate>
      <div class="modal-header">
        <h5 class="modal-title" id="accAccountModalTitle"><i class="bi bi-journal-bookmark"></i> Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body row g-3">
        <input type="hidden" name="id" id="accAccountId">
        <div class="col-12" id="accCodeModeWrap">
          <label class="form-label d-block mb-2">Account Code Mode</label>
          <div class="btn-group btn-group-sm w-100 acc-code-mode" role="group" aria-label="Account code mode">
            <input type="radio" class="btn-check" name="accCodeMode" id="accCodeModeAuto" value="auto" checked autocomplete="off">
            <label class="btn btn-outline-primary" for="accCodeModeAuto"><i class="bi bi-magic"></i> Auto Generate</label>
            <input type="radio" class="btn-check" name="accCodeMode" id="accCodeModeManual" value="manual" autocomplete="off">
            <label class="btn btn-outline-primary" for="accCodeModeManual"><i class="bi bi-pencil"></i> Manual Entry</label>
          </div>
          <div class="form-text">Auto Generate fills the next available code. Manual Entry lets you type a unique numeric code.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="accAccountCode">Code <span class="text-danger">*</span></label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-hash"></i></span>
            <input type="text" class="form-control" name="account_code" id="accAccountCode" required maxlength="30" autocomplete="off" readonly inputmode="numeric" pattern="[0-9]*">
          </div>
          <div class="invalid-feedback d-block" id="accAccountCodeError"></div>
        </div>
        <div class="col-md-8">
          <label class="form-label" for="accAccountName">Account Name <span class="text-danger">*</span></label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-card-text"></i></span>
            <input type="text" class="form-control" name="account_name" id="accAccountName" required minlength="3" maxlength="150" autocomplete="off">
          </div>
          <div class="invalid-feedback d-block" id="accAccountNameError"></div>
        </div>
        <div class="col-12">
          <label class="form-label" for="accAccountGroup">Account Group <span class="text-danger">*</span></label>
          <div class="d-flex gap-2 align-items-start">
            <div class="flex-grow-1">
              <select class="form-select form-select-sm" name="account_group_id" id="accAccountGroup" required data-enhance="false" data-placeholder="— Select account group —">
                <?php echo $accGroupOptionsHtml ?? ''; ?>
              </select>
              <div class="invalid-feedback d-block" id="accAccountGroupError"></div>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm text-nowrap" id="accAddGroupInlineBtn" title="Add account group"><i class="bi bi-plus-lg"></i> Group</button>
          </div>
          <?php if (!empty($accGroupsBootError)): ?>
          <div class="alert alert-danger py-2 px-3 mt-2 mb-0 small"><?php echo htmlspecialchars($accGroupsBootError); ?></div>
          <?php endif; ?>
          <div id="accGroupEmptyState" class="alert alert-warning py-2 px-3 mt-2 mb-0 <?php echo empty($accAccountGroupsBoot) ? '' : 'd-none'; ?> small">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle"></i> No Account Groups Found</div>
            <div class="mb-2">Create default groups or add a new group before saving an account.</div>
            <button type="button" class="btn btn-sm btn-warning" id="accSeedGroupsBtn">Create Default Groups</button>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="accOpeningBalance">Opening Balance</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-cash-coin"></i></span>
            <input type="number" step="0.01" class="form-control" name="opening_balance" id="accOpeningBalance" value="0.00" inputmode="decimal">
          </div>
          <div class="invalid-feedback d-block" id="accOpeningBalanceError"></div>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="accOpeningType">Balance Type</label>
          <select class="form-select form-select-sm" name="opening_balance_type" id="accOpeningType" data-enhance="false">
            <option value="DEBIT">Debit</option>
            <option value="CREDIT">Credit</option>
          </select>
          <div class="form-text">Auto-selected from group; you may override.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="accNormalBalance">Normal Balance</label>
          <select class="form-select form-select-sm" name="normal_balance" id="accNormalBalance" data-enhance="false">
            <option value="DEBIT">Debit</option>
            <option value="CREDIT">Credit</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="accAccountType">Account Type</label>
          <select class="form-select form-select-sm" name="account_type" id="accAccountType" data-enhance="false">
            <option value="ASSET">Asset</option>
            <option value="LIABILITY">Liability</option>
            <option value="CAPITAL">Capital</option>
            <option value="INCOME">Income</option>
            <option value="EXPENSE">Expense</option>
            <option value="GENERAL">General</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="accLedgerType">Ledger Type</label>
          <select class="form-select form-select-sm" name="ledger_type" id="accLedgerType" data-enhance="false">
            <option value="GENERAL">General</option>
            <option value="CASH">Cash</option>
            <option value="BANK">Bank</option>
            <option value="CUSTOMER">Customer</option>
            <option value="SUPPLIER">Supplier</option>
            <option value="EXPENSE">Expense</option>
            <option value="INCOME">Income</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="accParentAccount">Parent Account</label>
          <select class="form-select form-select-sm" name="parent_account_id" id="accParentAccount" data-enhance="false">
            <option value="">— None —</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label" for="accAccountActive">Status</label>
          <select class="form-select form-select-sm" name="is_active" id="accAccountActive" data-enhance="false">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
        <div class="col-12 d-none" id="accAccountDeleteWrap">
          <button type="button" class="btn btn-outline-danger btn-sm w-100" id="accAccountDeleteBtn">
            <i class="bi bi-trash"></i> Delete Account
          </button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="accAccountSaveBtn" disabled>
          <span class="acc-save-label"><i class="bi bi-check-lg"></i> Save Account</span>
          <span class="acc-save-spinner spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
        </button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade acc-coa-modal" id="accGroupModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" id="accGroupForm" novalidate>
      <div class="modal-header">
        <h5 class="modal-title">New Account Group</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body row g-3">
        <div class="col-md-5">
          <label class="form-label" for="accGroupCode">Group Code</label>
          <input type="text" class="form-control form-control-sm" name="group_code" id="accGroupCode" maxlength="40" placeholder="Auto if empty">
        </div>
        <div class="col-md-7">
          <label class="form-label" for="accGroupName">Group Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control form-control-sm" name="group_name" id="accGroupName" required maxlength="100">
          <div class="invalid-feedback d-block" id="accGroupNameError"></div>
        </div>
        <div class="col-12">
          <label class="form-label" for="accGroupParent">Parent Group</label>
          <select class="form-select form-select-sm" name="parent_id" id="accGroupParent" data-enhance="false">
            <option value="">— None (top level) —</option>
            <?php
            foreach (AccountGroupRepository::sortGroupsForSelect($accAccountGroupsBoot ?? []) as $g):
                $depth = (int) ($g['_depth'] ?? 0);
                $indent = $depth > 0 ? str_repeat('  ', $depth) . '└ ' : '';
            ?>
            <option value="<?php echo (int) $g['id']; ?>"><?php echo htmlspecialchars($indent . ($g['group_name'] ?? '')); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="accGroupType">Group Type</label>
          <select class="form-select form-select-sm" name="group_type" id="accGroupType" data-enhance="false">
            <option value="ASSETS">Assets</option>
            <option value="LIABILITIES">Liabilities</option>
            <option value="CAPITAL">Equity</option>
            <option value="INCOME">Income</option>
            <option value="EXPENSES" selected>Expenses</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="accGroupNature">Balance Type</label>
          <select class="form-select form-select-sm" name="nature" id="accGroupNature" data-enhance="false">
            <option value="DEBIT">Debit</option>
            <option value="CREDIT">Credit</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label" for="accGroupDescription">Description</label>
          <textarea class="form-control form-control-sm" name="description" id="accGroupDescription" rows="2" maxlength="500" placeholder="Optional notes about this group"></textarea>
        </div>
        <div class="col-12">
          <label class="form-label" for="accGroupStatus">Status</label>
          <select class="form-select form-select-sm" name="is_active" id="accGroupStatus" data-enhance="false">
            <option value="1" selected>Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="accGroupSaveBtn">
          <span class="acc-save-label">Save Group</span>
          <span class="acc-save-spinner spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
        </button>
      </div>
    </form>
  </div>
</div>
