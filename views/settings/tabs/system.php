<?php $config = $config ?? []; ?>
<div class="settings-card mb-3">
  <div class="card-header py-2 px-3">
    <div class="settings-title mb-0"><i class="bi bi-sliders me-1"></i> System</div>
    <div class="settings-subtitle">Integrations and account security.</div>
  </div>
  <div class="card-body py-3">
    <form method="post" action="<?php echo htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? Helpers::baseUrl('index.php?page=settings'))); ?>" id="systemSettingsForm">
      <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
      <input type="hidden" name="settings_section" value="system">
      <input type="hidden" name="settings_return_tab" value="system">

      <div class="settings-section-label">Google Maps</div>
      <div class="mb-3">
        <label class="form-label">API key (optional)</label>
        <input type="text" name="google_maps_api_key" class="form-control form-control-sm" value="<?php echo htmlspecialchars($config['google_maps_api_key'] ?? ''); ?>" placeholder="AIza…" autocomplete="off">
        <div class="settings-helper">Places autocomplete on customer / delivery forms when enabled.</div>
      </div>

      <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i> Save system settings</button>
    </form>

    <hr class="my-4 text-muted opacity-25">

    <div class="settings-section-label">Security</div>
    <p class="small text-muted mb-2">Update the password for the account you are logged in with.</p>
    <a href="<?php echo Helpers::baseUrl('index.php?page=change_password'); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-lock me-1"></i> Change password</a>
  </div>
</div>
