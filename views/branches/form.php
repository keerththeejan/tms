<?php /** @var array $branch */ ?>
<?php
$id = (int)($branch['id'] ?? 0);
$canonical = BranchFixedMaster::CANONICAL[$id] ?? null;
if (!$canonical) {
    echo '<div class="alert alert-warning">Only the three fixed branches can be edited from Settings → Branches.</div>';
    return;
}
?>
<style>
  .branches-form-page { --ui-border: rgba(17,24,39,.10); --ui-shadow: 0 1px 2px rgba(16,24,40,.06); --ui-radius: 14px; }
  .branches-form-page .page-header { flex-wrap: wrap; gap: 0.5rem; }
  .branches-form-page .page-header h3 { font-size: 1.25rem; font-weight: 800; letter-spacing: -.01em; }
  .branches-form-page .card-soft { background:#fff; border: 1px solid var(--ui-border); border-radius: var(--ui-radius); box-shadow: var(--ui-shadow); }
  .branches-form-page .form-control { height: 38px; border-radius: 12px; }
  .branches-form-page .form-label { font-size: .9rem; font-weight: 600; color:#374151; }
</style>
<div class="branches-form-page">
<div class="d-flex justify-content-between align-items-center mb-3 page-header">
  <h3 class="mb-0">Edit <?php echo htmlspecialchars($canonical['name']); ?></h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=settings&tab=branches#pane-branches'); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Settings</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<div class="alert alert-info py-2 small">Branch name and code are fixed. Update letterhead addresses under <a href="<?php echo Helpers::baseUrl('index.php?page=settings&tab=branches#pane-branches'); ?>">Settings → Branches</a>.</div>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=branches&action=save'); ?>" class="card card-soft">
  <div class="card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <div class="row g-2 g-md-3">
      <div class="col-12 col-md-6">
        <label class="form-label">Name</label>
        <input type="text" class="form-control" readonly disabled value="<?php echo htmlspecialchars($canonical['name']); ?>">
      </div>
      <div class="col-12 col-sm-6 col-md-3">
        <label class="form-label">Code</label>
        <input type="text" class="form-control" readonly disabled value="<?php echo htmlspecialchars($canonical['code']); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Location (optional)</label>
        <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars((string)($branch['location'] ?? '')); ?>" placeholder="City / area">
      </div>
      <div class="col-12 col-sm-6 col-md-3 d-flex align-items-end pb-1">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="is_main" disabled <?php echo !empty($branch['is_main']) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="is_main">Main hub (Kilinochchi only)</label>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-md-3 d-flex align-items-end pb-1">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo !isset($branch['is_active']) || !empty($branch['is_active']) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="is_active">Active</label>
        </div>
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-end">
    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
  </div>
</form>
</div>
