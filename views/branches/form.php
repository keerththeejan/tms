<?php /** @var array $branch */ ?>
<style>
  .branches-form-page { --ui-border: rgba(17,24,39,.10); --ui-shadow: 0 1px 2px rgba(16,24,40,.06); --ui-radius: 14px; }
  .branches-form-page .page-header { flex-wrap: wrap; gap: 0.5rem; }
  .branches-form-page .page-header h3 { font-size: 1.25rem; font-weight: 800; letter-spacing: -.01em; }
  .branches-form-page .card-soft { background:#fff; border: 1px solid var(--ui-border); border-radius: var(--ui-radius); box-shadow: var(--ui-shadow); }
  .branches-form-page .form-control { height: 38px; border-radius: 12px; }
  .branches-form-page .form-label { font-size: .9rem; font-weight: 600; color:#374151; }
  @media (max-width: 576px) {
    .branches-form-page .page-header { flex-direction: column; align-items: stretch; }
    .branches-form-page .page-header .btn { width: 100%; }
    .branches-form-page .card-footer .btn { width: 100%; }
  }
</style>
<div class="branches-form-page">
<div class="d-flex justify-content-between align-items-center mb-3 page-header">
  <h3 class="mb-0"><?php echo $branch['id'] ? 'Edit Branch' : 'New Branch'; ?></h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=branches'); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=branches&action=save'); ?>" class="card card-soft">
  <div class="card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo (int)$branch['id']; ?>">
    <div class="row g-2 g-md-3">
      <div class="col-12 col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($branch['name']); ?>">
      </div>
      <div class="col-12 col-sm-6 col-md-3">
        <label class="form-label">Code</label>
        <input type="text" name="code" class="form-control" required value="<?php echo htmlspecialchars($branch['code']); ?>">
      </div>
      <div class="col-12 col-sm-6 col-md-3 d-flex align-items-end pb-1">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="is_main" name="is_main" <?php echo !empty($branch['is_main']) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="is_main">Main Branch</label>
        </div>
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-end">
    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
  </div>
</form>
</div>
