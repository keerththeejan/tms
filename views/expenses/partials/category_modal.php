<div class="modal fade" id="expCategoryModal" tabindex="-1" aria-labelledby="expCategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="expCategoryModalLabel">Expense Categories</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="expCategoryForm" class="border rounded p-3 mb-3 bg-light">
          <input type="hidden" name="csrf_token" id="expCatCsrf">
          <input type="hidden" name="exp_action" value="category_save">
          <input type="hidden" name="id" id="expCatId" value="0">
          <div class="row g-2 align-items-end">
            <div class="col-md-5">
              <label class="form-label" for="expCatName">Name</label>
              <input type="text" class="form-control" id="expCatName" name="name" required placeholder="e.g. Parking">
            </div>
            <div class="col-md-5">
              <label class="form-label" for="expCatAccount">GL Expense Account</label>
              <select class="form-select" id="expCatAccount" name="account_id">
                <option value="">— None —</option>
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary w-100">Save</button>
            </div>
          </div>
        </form>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Name</th><th>Code</th><th>Account</th><th>Status</th><th></th></tr></thead>
            <tbody id="expCategoryList"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
