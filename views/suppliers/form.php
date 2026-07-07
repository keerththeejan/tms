<?php /** @var array $supplier */ ?>
<?php
  $supplier = is_array($supplier ?? null) ? $supplier : [];
  $supplier += ['id' => 0, 'name' => '', 'phone' => '', 'branch_id' => 0, 'supplier_code' => ''];
  $supCssPath = dirname(__DIR__, 2) . '/public/assets/css/suppliers-module.css';
  $supCssVer = is_file($supCssPath) ? (string) filemtime($supCssPath) : '1';
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/suppliers-module.css?v=' . rawurlencode($supCssVer)); ?>">

<div id="suppliersApp" class="supm-app container-fluid px-0">
  <section class="supm-hero mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <h1 class="supm-title mb-0"><?php echo $supplier['id'] ? 'Edit Supplier' : 'New Supplier'; ?></h1>
        <p class="supm-subtitle">Premium vendor profile setup with business, contact, and financial sections.</p>
      </div>
      <a href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to list</a>
    </div>
  </section>

  <div class="row g-3">
    <div class="col-lg-8">
      <form id="supmForm" method="post" action="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=save'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
        <input type="hidden" name="id" value="<?php echo (int)$supplier['id']; ?>">

        <div class="supm-card">
          <div class="supm-form-section">
            <div class="supm-form-title">Section 1 - Basic Information</div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Supplier Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($supplier['name']); ?>"></div>
              <div class="col-md-6"><label class="form-label">Company Name</label><input type="text" id="supmCompanyPreview" class="form-control" placeholder="Preview only"></div>
              <div class="col-md-6"><label class="form-label">Supplier Code</label><input type="text" name="supplier_code" class="form-control" value="<?php echo htmlspecialchars($supplier['supplier_code']); ?>" maxlength="64"></div>
              <div class="col-md-6"><label class="form-label">Business Registration No.</label><input type="text" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Tax Number</label><input type="text" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Supplier Category</label><input type="text" id="supmCategoryPreview" class="form-control" placeholder="General"></div>
              <div class="col-md-6"><label class="form-label">Status</label><input type="text" id="supmStatusPreview" class="form-control" value="Active"></div>
            </div>
          </div>
          <div class="supm-form-section">
            <div class="supm-form-title">Section 2 - Contact Information</div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Contact Person</label><input type="text" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($supplier['phone']); ?>"></div>
              <div class="col-md-6"><label class="form-label">Mobile</label><input type="text" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">WhatsApp</label><input type="text" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Website</label><input type="url" class="form-control"></div>
            </div>
          </div>
          <div class="supm-form-section">
            <div class="supm-form-title">Section 3 - Address</div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Address Line 1</label><input type="text" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Address Line 2</label><input type="text" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">City</label><input type="text" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">District</label><input type="text" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">Province</label><input type="text" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">Postal Code</label><input type="text" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">Country</label><input type="text" class="form-control" value="Sri Lanka"></div>
              <div class="col-md-4"><label class="form-label">Google Maps Link</label><input type="url" class="form-control"></div>
            </div>
          </div>
          <div class="supm-form-section">
            <div class="supm-form-title">Section 4 - Financial Information</div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Opening Balance</label><input type="text" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Credit Limit</label><input type="text" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Payment Terms</label><input type="text" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Bank Name</label><input type="text" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Account Number</label><input type="text" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Currency</label><input type="text" class="form-control" value="LKR"></div>
              <div class="col-md-6"><label class="form-label">Branch <span class="text-danger">*</span></label>
                <select name="branch_id" class="form-select" data-enhance="false" required>
                  <option value="">— Select branch —</option>
                  <?php foreach (($branchesAll ?? []) as $b): ?>
                    <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($supplier['branch_id'] ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="supm-form-section">
            <div class="supm-form-title">Section 5 - Additional Information</div>
            <div class="row g-3">
              <div class="col-12"><label class="form-label">Remarks</label><textarea class="form-control" rows="2"></textarea></div>
              <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" rows="2"></textarea></div>
            </div>
          </div>
          <div class="supm-form-section d-flex justify-content-end gap-2">
            <a href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save Supplier</button>
          </div>
        </div>
      </form>
    </div>
    <div class="col-lg-4">
      <aside class="supm-card supm-summary p-3">
        <h2 class="h6 fw-bold mb-2"><i class="bi bi-eye me-1 text-success"></i>Supplier Summary</h2>
        <div class="supm-summary-row"><span>Supplier Name</span><strong id="supmSumName">—</strong></div>
        <div class="supm-summary-row"><span>Supplier Code</span><strong id="supmSumCode">—</strong></div>
        <div class="supm-summary-row"><span>Company</span><strong id="supmSumCompany">—</strong></div>
        <div class="supm-summary-row"><span>Category</span><strong id="supmSumCategory">—</strong></div>
        <div class="supm-summary-row"><span>Phone</span><strong id="supmSumPhone">—</strong></div>
        <div class="supm-summary-row"><span>Status</span><strong id="supmSumStatus">Active</strong></div>
        <div class="supm-summary-row"><span>Branch</span><strong id="supmSumBranch">—</strong></div>
      </aside>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible py-2 px-3 mb-3" role="alert">
      <?php echo htmlspecialchars($error); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

</div>
