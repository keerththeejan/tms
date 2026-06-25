<div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form id="expenseForm" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="expenseModalLabel">New Expense</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" id="expFormCsrf" value="">
          <input type="hidden" name="exp_action" value="save">
          <input type="hidden" name="id" id="expFormId" value="0">

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="expFormNumber">Expense Number</label>
              <div class="input-group">
                <input type="text" class="form-control" id="expFormNumber" name="expense_number" readonly placeholder="Auto generate">
                <span class="input-group-text text-muted small">Auto</span>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="expFormDate">Expense Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="expFormDate" name="expense_date" required>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="expFormBranch">Branch <span class="text-danger">*</span></label>
              <select class="form-select" id="expFormBranch" name="branch_id" required></select>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="expFormCategory">Expense Category</label>
              <select class="form-select" id="expFormCategory" name="category_id"></select>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="expFormAccount">Expense Account</label>
              <select class="form-select" id="expFormAccount" name="account_id">
                <option value="">— From category default —</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="expFormSupplier">Supplier</label>
              <select class="form-select" id="expFormSupplier" name="supplier_id">
                <option value="">— None —</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="expFormRef">Reference Number</label>
              <input type="text" class="form-control" id="expFormRef" name="reference_number" maxlength="64">
            </div>
            <div class="col-md-8">
              <label class="form-label" for="expFormDesc">Description</label>
              <input type="text" class="form-control" id="expFormDesc" name="description">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="expFormAmount">Amount <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0.01" class="form-control" id="expFormAmount" name="amount" required>
            </div>
            <div class="col-md-3">
              <label class="form-label" for="expFormTax">Tax</label>
              <input type="number" step="0.01" min="0" class="form-control" id="expFormTax" name="tax_amount" value="0">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="expFormDiscount">Discount</label>
              <input type="number" step="0.01" min="0" class="form-control" id="expFormDiscount" name="discount_amount" value="0">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="expFormTotal">Total</label>
              <input type="number" step="0.01" class="form-control fw-semibold" id="expFormTotal" name="total_amount" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="expFormPayMethod">Payment Method</label>
              <select class="form-select" id="expFormPayMethod" name="payment_method">
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
                <option value="cheque">Cheque</option>
                <option value="credit">Credit</option>
                <option value="transfer">Transfer</option>
              </select>
            </div>
            <div class="col-md-4" id="expFormPayAccountWrap">
              <label class="form-label" for="expFormPayAccount">Payment Account</label>
              <select class="form-select" id="expFormPayAccount" name="payment_account_id">
                <option value="">— Default cash/bank —</option>
              </select>
            </div>
            <div class="col-md-4" id="expFormPaidWrap">
              <label class="form-label" for="expFormPaid">Paid Amount</label>
              <input type="number" step="0.01" min="0" class="form-control" id="expFormPaid" name="paid_amount" value="0">
            </div>
            <div class="col-md-4" id="expFormBalanceWrap">
              <label class="form-label" for="expFormBalance">Balance</label>
              <input type="number" step="0.01" class="form-control" id="expFormBalance" readonly>
            </div>
            <div class="col-md-4 d-none" id="expFormDueWrap">
              <label class="form-label" for="expFormDue">Due Date</label>
              <input type="date" class="form-control" id="expFormDue" name="credit_due_date">
            </div>
            <div class="col-md-4 d-none" id="expFormPartyWrap">
              <label class="form-label" for="expFormParty">Credit Party</label>
              <input type="text" class="form-control" id="expFormParty" name="credit_party">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="expFormStatus">Status</label>
              <select class="form-select" id="expFormStatus" name="status">
                <option value="draft">Draft</option>
                <option value="pending" selected>Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label" for="expFormNotes">Notes</label>
              <textarea class="form-control" id="expFormNotes" name="notes" rows="2"></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="expFormAttachment">Attachment</label>
              <input type="file" class="form-control" id="expFormAttachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp">
            </div>
          </div>
          <div id="expFormErrors" class="alert alert-danger mt-3 d-none"></div>
        </div>
        <div class="modal-footer flex-wrap gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="expFormSubmit">
            <span class="spinner-border spinner-border-sm d-none me-1" id="expFormSpinner"></span>
            Save Expense
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Settle payment mini modal -->
<div class="modal fade" id="expSettleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="expSettleForm">
        <div class="modal-header">
          <h5 class="modal-title">Record Payment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" id="expSettleCsrf">
          <input type="hidden" name="exp_action" value="settle">
          <input type="hidden" name="id" id="expSettleId">
          <p class="small text-muted mb-2" id="expSettleSummary"></p>
          <div class="mb-3">
            <label class="form-label" for="expSettleAmount">Amount</label>
            <input type="number" step="0.01" min="0.01" class="form-control" id="expSettleAmount" name="pay_amount" required>
          </div>
          <div class="mb-0">
            <label class="form-label" for="expSettleNotes">Notes</label>
            <input type="text" class="form-control" id="expSettleNotes" name="pay_notes">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Record Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>
