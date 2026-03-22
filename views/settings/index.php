<?php
$company = $company ?? [];
$config = $config ?? [];
$settingsActiveTab = $settingsActiveTab ?? 'general';
$allowedSettingsTabs = ['general', 'branches', 'users', 'system'];
if (!in_array($settingsActiveTab, $allowedSettingsTabs, true)) {
    $settingsActiveTab = 'general';
}
?>
<style>
  :root { --ui-bg: #f8f9fb; --ui-border: rgba(17,24,39,.10); --ui-shadow: 0 1px 2px rgba(16,24,40,.06); --ui-shadow-hover: 0 6px 18px rgba(16,24,40,.10); --ui-radius: 14px; }
  .settings-page { background: var(--ui-bg); border-radius: var(--ui-radius); padding: 12px; border: 1px solid rgba(17,24,39,.06); }
  .settings-card { border: 1px solid var(--ui-border); border-radius: var(--ui-radius); box-shadow: var(--ui-shadow); overflow: hidden; background: #fff; }
  .settings-card .card-header { background: #fff; border-bottom: 1px solid var(--ui-border); padding: 10px 14px; }
  .settings-card .card-body { padding: 14px; }
  .settings-title { font-weight: 800; font-size: 1rem; margin: 0; letter-spacing: -.01em; }
  .settings-subtitle { color: #6b7280; font-size: .8125rem; margin-top: 2px; }
  .settings-section-label { font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; color: #6c757d; font-weight: 700; margin-bottom: 8px; }
  .settings-page .form-label { font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: 4px; }
  .settings-page .form-control-sm, .settings-page .form-select-sm { border-radius: 10px; }
  .settings-helper { color: #6c757d; font-size: .78rem; margin-top: 4px; }
  .field-error { border-color: rgba(220,53,69,.65) !important; }
  .error-text { color: #dc3545; font-size: .78rem; margin-top: 4px; display: none; }
  .logo-drop { border: 1.5px dashed rgba(17,24,39,.22); border-radius: 12px; background: #fbfcfe; cursor: pointer; transition: background .15s ease, border-color .15s ease; }
  .logo-drop:hover { background: #f4f8ff; border-color: rgba(13,110,253,.55); }
  .logo-drop.is-dragover { background: rgba(13,110,253,.08); border-color: rgba(13,110,253,.65); }
  .logo-preview { border-radius: 8px; border: 1px solid rgba(16,24,40,.12); background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; flex: 0 0 auto; }
  .logo-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
  .badge-soft { background: rgba(13,110,253,.1); color: #0d6efd; border: 1px solid rgba(13,110,253,.18); font-weight: 700; }
  .branch-card { border: 1px solid var(--ui-border); border-radius: var(--ui-radius); padding: 12px; background: #fff; box-shadow: var(--ui-shadow); }
  .branch-card.default { border-color: rgba(13,110,253,.35); box-shadow: 0 0 0 .2rem rgba(13,110,253,.08), var(--ui-shadow); }
  .branch-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
  .branch-name { font-weight: 700; margin: 0; font-size: .95rem; }
  .addr-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  @media (max-width: 992px) { .addr-grid { grid-template-columns: 1fr; } }
  .branch-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  @media (max-width: 992px) { .branch-grid { grid-template-columns: 1fr; } }
  .default-badge { display: none; }
  .branch-card.default .default-badge { display: inline-flex; }
  #settingsMainTabs.nav-tabs { flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; scrollbar-width: thin; gap: 2px; border-bottom: 1px solid var(--ui-border); }
  #settingsMainTabs .nav-item { flex-shrink: 0; }
  #settingsMainTabs .nav-link { border-radius: 10px 10px 0 0; padding: 0.45rem 0.9rem; font-size: 0.875rem; font-weight: 600; color: #64748b; border: 1px solid transparent; }
  #settingsMainTabs .nav-link:hover { color: #0d6efd; }
  #settingsMainTabs .nav-link.active { color: #0d6efd; background: #fff; border-color: var(--ui-border) var(--ui-border) #fff; }
  .settings-tabs-sticky { position: sticky; top: 0; z-index: 1010; background: linear-gradient(180deg, var(--ui-bg) 70%, rgba(248,249,251,0.92)); padding-top: 4px; margin-bottom: 0 !important; }
</style>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h3 class="mb-0 h5 fw-bold">TMS Settings</h3>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success py-2"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000;">
  <div id="settingsSuccessToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="polite" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="settingsToastBody"><?php echo !empty($success) ? htmlspecialchars($success) : 'Saved.'; ?></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<div class="settings-page">
  <ul class="nav nav-tabs settings-tabs-sticky mb-3" id="settingsMainTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $settingsActiveTab === 'general' ? 'active' : ''; ?>" id="tab-general" data-bs-toggle="tab" data-bs-target="#pane-general" type="button" role="tab" aria-controls="pane-general" aria-selected="<?php echo $settingsActiveTab === 'general' ? 'true' : 'false'; ?>">General</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $settingsActiveTab === 'branches' ? 'active' : ''; ?>" id="tab-branches" data-bs-toggle="tab" data-bs-target="#pane-branches" type="button" role="tab" aria-controls="pane-branches" aria-selected="<?php echo $settingsActiveTab === 'branches' ? 'true' : 'false'; ?>">Branches</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $settingsActiveTab === 'users' ? 'active' : ''; ?>" id="tab-users" data-bs-toggle="tab" data-bs-target="#pane-users" type="button" role="tab" aria-controls="pane-users" aria-selected="<?php echo $settingsActiveTab === 'users' ? 'true' : 'false'; ?>">Users</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $settingsActiveTab === 'system' ? 'active' : ''; ?>" id="tab-system" data-bs-toggle="tab" data-bs-target="#pane-system" type="button" role="tab" aria-controls="pane-system" aria-selected="<?php echo $settingsActiveTab === 'system' ? 'true' : 'false'; ?>">System</button>
    </li>
  </ul>

  <div class="tab-content" id="settingsTabContent">
    <div class="tab-pane fade <?php echo $settingsActiveTab === 'general' ? 'show active' : ''; ?>" id="pane-general" role="tabpanel" aria-labelledby="tab-general" tabindex="0">
      <?php include __DIR__ . '/tabs/general.php'; ?>
    </div>
    <div class="tab-pane fade <?php echo $settingsActiveTab === 'branches' ? 'show active' : ''; ?>" id="pane-branches" role="tabpanel" aria-labelledby="tab-branches" tabindex="0">
      <?php include __DIR__ . '/tabs/branches.php'; ?>
    </div>
    <div class="tab-pane fade <?php echo $settingsActiveTab === 'users' ? 'show active' : ''; ?>" id="pane-users" role="tabpanel" aria-labelledby="tab-users" tabindex="0">
      <?php include __DIR__ . '/tabs/users.php'; ?>
    </div>
    <div class="tab-pane fade <?php echo $settingsActiveTab === 'system' ? 'show active' : ''; ?>" id="pane-system" role="tabpanel" aria-labelledby="tab-system" tabindex="0">
      <?php include __DIR__ . '/tabs/system.php'; ?>
    </div>
  </div>
</div>

<script src="<?php echo Helpers::baseUrl('assets/js/settings.js'); ?>"></script>
<script src="<?php echo Helpers::baseUrl('assets/js/settings-general.js'); ?>"></script>
<script src="<?php echo Helpers::baseUrl('assets/js/branches.js'); ?>"></script>
<script>
(function () {
  var msg = <?php echo json_encode((string)($success ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE); ?>;
  if (!msg || typeof bootstrap === 'undefined' || !bootstrap.Toast) return;
  var el = document.getElementById('settingsSuccessToast');
  var body = document.getElementById('settingsToastBody');
  if (body) body.textContent = msg;
  if (el) new bootstrap.Toast(el, { delay: 2800 }).show();
})();
</script>
