<?php /** @var array $supplier */ ?>
<?php
  $supplier = is_array($supplier ?? null) ? $supplier : [];
  $supplier += ['id' => 0, 'name' => '', 'phone' => '', 'branch_id' => 0, 'supplier_code' => ''];
?>
<style>
  .sup-form-page { --sf-radius: 14px; --sf-border: rgba(17,24,39,.08); }
  .sup-form-page .sf-head { margin-bottom: 1rem; }
  .sup-form-page .sf-title { font-size: 1.25rem; font-weight: 800; letter-spacing: -.02em; margin: 0; color: #0f172a; }
  .sup-form-page .sf-card {
    background: #fff;
    border: 1px solid var(--sf-border);
    border-radius: var(--sf-radius);
    box-shadow: 0 1px 3px rgba(16,24,40,.06), 0 8px 24px rgba(15,23,42,.06);
    overflow: hidden;
  }
  .sup-form-page .sf-card .card-body { padding: 1.15rem 1.25rem; }
  .sup-form-page .sf-card .card-footer {
    background: #f8fafc;
    border-top: 1px solid var(--sf-border);
    padding: 0.85rem 1.25rem;
  }
  .sup-form-page .form-label { font-size: 0.8rem; font-weight: 600; color: #475569; }
  .sup-form-page .form-control, .sup-form-page .form-select { border-radius: 10px; border-color: rgba(15,23,42,.12); }
  .sup-form-page .form-control:focus, .sup-form-page .form-select:focus {
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
  }
</style>

<div class="sup-form-page container-fluid px-0">
  <div class="sf-head d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1 class="sf-title"><?php echo $supplier['id'] ? 'Edit supplier' : 'New supplier'; ?></h1>
    <a href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>" class="btn btn-outline-secondary rounded-pill"><i class="bi bi-arrow-left me-1"></i> Back to list</a>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible py-2 px-3 mb-3" role="alert">
      <?php echo htmlspecialchars($error); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=suppliers&action=save'); ?>">
    <div class="sf-card">
      <div class="card-body">
        <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
        <input type="hidden" name="id" value="<?php echo (int)$supplier['id']; ?>">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="supName">Supplier name</label>
            <input type="text" name="name" id="supName" class="form-control" required value="<?php echo htmlspecialchars($supplier['name']); ?>" autocomplete="organization">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="supPhone">Phone</label>
            <input type="text" name="phone" id="supPhone" class="form-control" value="<?php echo htmlspecialchars($supplier['phone']); ?>" inputmode="tel" autocomplete="tel">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="supBranch">Branch</label>
            <select name="branch_id" id="supBranch" class="form-select" data-enhance="false" required>
              <option value="">— Select branch —</option>
              <?php foreach (($branchesAll ?? []) as $b): ?>
                <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($supplier['branch_id'] ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="supCode">Supplier code <span class="text-muted fw-normal">(optional)</span></label>
            <input type="text" name="supplier_code" id="supCode" class="form-control" value="<?php echo htmlspecialchars($supplier['supplier_code']); ?>" maxlength="64">
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="<?php echo Helpers::baseUrl('index.php?page=suppliers'); ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i> Save supplier</button>
      </div>
    </div>
  </form>
</div>
