<div class="settings-card mb-3">
  <div class="card-header py-2 px-3">
    <div class="settings-title mb-0"><i class="bi bi-people me-1"></i> Users</div>
    <div class="settings-subtitle">Accounts, roles, and access to TMS modules.</div>
  </div>
  <div class="card-body py-3">
    <p class="small text-muted mb-3">Create staff logins and assign branches where needed.</p>
    <div class="d-grid gap-2 col-md-8 col-lg-6">
      <a href="<?php echo Helpers::baseUrl('index.php?page=users'); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list me-1"></i> View all users</a>
      <a href="<?php echo Helpers::baseUrl('index.php?page=users&action=new'); ?>" class="btn btn-sm btn-primary"><i class="bi bi-person-plus me-1"></i> Create user</a>
    </div>
  </div>
</div>
