<?php
$settingsBranchSlots = $settingsBranchSlots ?? [];
$defaultBranchSlotIndex = isset($defaultBranchSlotIndex) ? (int)$defaultBranchSlotIndex : 0;
if ($defaultBranchSlotIndex < 0) {
    $defaultBranchSlotIndex = 0;
}
if ($defaultBranchSlotIndex > 2) {
    $defaultBranchSlotIndex = 2;
}
while (count($settingsBranchSlots) < 3) {
    $settingsBranchSlots[] = ['id' => 0, 'name' => '', 'address_ta' => '', 'address_en' => '', 'phones' => ''];
}
$letterheadBranches = [];
foreach ($settingsBranchSlots as $sb) {
    $letterheadBranches[] = [
        'name' => (string)($sb['name'] ?? ''),
        'address_ta' => (string)($sb['address_ta'] ?? ''),
        'address_en' => (string)($sb['address_en'] ?? ''),
        'phones' => (string)($sb['phones'] ?? ''),
    ];
}
?>

<div class="settings-section-label mb-2">A · Operational branches</div>
<p class="small text-muted mb-2">Master list for parcels, users, delivery notes, and reports. Use <strong>Set default</strong> for header / billing when that branch is not tied to a letterhead slot.</p>
<?php
  $branchesMaster = $branchesMaster ?? [];
  $branches = $branchesMaster;
  $branchListEmbed = true;
  $branchListError = '';
  if (!empty($_SESSION['branch_list_flash_err'])) {
      $branchListError = (string)$_SESSION['branch_list_flash_err'];
      unset($_SESSION['branch_list_flash_err']);
  }
  include dirname(__DIR__, 2) . '/branches/index.php';
?>

<div class="settings-section-label mt-4 mb-2">B · Primary letterhead &amp; billing</div>
<div class="settings-card mb-3">
  <div class="card-body py-3">
    <form method="post" action="<?php echo htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? Helpers::baseUrl('index.php?page=settings'))); ?>" id="branchLetterheadForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
      <input type="hidden" name="settings_section" value="branch_letterhead">
      <input type="hidden" name="settings_return_tab" value="branches">

      <div id="branchLetterheadClientError" class="alert alert-danger d-none py-2" role="alert"></div>

      <p class="small text-muted mb-3">Up to three addresses sync to the <code>branches</code> table and <code>company.json</code>. Tamil and English lines are used on bilingual prints; phones support multiple values separated by <code>|</code>.</p>

      <?php
        $b0 = $letterheadBranches[0] ?? ['name' => '', 'address_ta' => '', 'address_en' => '', 'phones' => ''];
        $id0 = (int)($settingsBranchSlots[0]['id'] ?? 0);
      ?>
      <div class="branch-card branch-letterhead-card mb-3<?php echo ($defaultBranchSlotIndex === 0) ? ' default' : ''; ?>" data-branch-index="0">
        <input type="hidden" name="branch_db_id[]" value="<?php echo (int)$id0; ?>">
        <div class="branch-top mb-2">
          <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <h6 class="branch-name mb-0">Primary branch</h6>
              <span class="badge badge-soft default-badge">Default header &amp; billing</span>
            </div>
            <div class="settings-helper mb-0 small">Required for valid letterhead output.</div>
          </div>
          <div class="text-end">
            <div class="form-check form-switch m-0">
              <input class="form-check-input" type="radio" name="default_branch_idx" value="0" id="default_branch_0" <?php echo ($defaultBranchSlotIndex === 0) ? 'checked' : ''; ?>>
              <label class="form-check-label small" for="default_branch_0">Default header &amp; print</label>
            </div>
          </div>
        </div>
        <div class="row g-2">
          <div class="col-12">
            <label class="form-label">Branch name</label>
            <input type="text" name="branch_name[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b0['name']); ?>" placeholder="e.g. Colombo" required>
            <div class="error-text" data-error-for="branch_name_0">Branch name is required.</div>
          </div>
        </div>
        <div class="addr-grid mt-2">
          <div>
            <label class="form-label">Address (Tamil)</label>
            <input type="text" name="branch_address_ta[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b0['address_ta']); ?>" placeholder="தமிழ் முகவரி">
          </div>
          <div>
            <label class="form-label">Address (English)</label>
            <input type="text" name="branch_address_en[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b0['address_en']); ?>" placeholder="English address" required>
            <div class="error-text" data-error-for="branch_address_en_0">English address is required.</div>
          </div>
        </div>
        <div class="row g-2 mt-1">
          <div class="col-12">
            <label class="form-label">Phones</label>
            <input type="text" name="branch_phones[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b0['phones']); ?>" placeholder="077 … | 077 …">
            <div class="settings-helper small">Separate with <code>|</code>.</div>
          </div>
        </div>
      </div>

      <div class="settings-section-label mt-3 mb-2">C · Additional branches (max 2 cards + primary = 3)</div>
      <div class="branch-grid mb-2">
        <?php for ($i = 1; $i < 3; $i++):
            $b = $letterheadBranches[$i] ?? ['name' => '', 'address_ta' => '', 'address_en' => '', 'phones' => ''];
            $slotId = (int)($settingsBranchSlots[$i]['id'] ?? 0);
            ?>
          <div class="branch-card branch-letterhead-card<?php echo ($defaultBranchSlotIndex === $i) ? ' default' : ''; ?>" data-branch-index="<?php echo (int)$i; ?>">
            <input type="hidden" name="branch_db_id[]" value="<?php echo (int)$slotId; ?>">
            <div class="branch-top mb-2">
              <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <h6 class="branch-name mb-0">Branch <?php echo (int)($i + 1); ?></h6>
                  <span class="badge badge-soft default-badge">Default header &amp; billing</span>
                </div>
              </div>
              <div class="text-end">
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="radio" name="default_branch_idx" value="<?php echo (int)$i; ?>" id="default_branch_<?php echo (int)$i; ?>" <?php echo ($defaultBranchSlotIndex === $i) ? 'checked' : ''; ?>>
                  <label class="form-check-label small" for="default_branch_<?php echo (int)$i; ?>">Default header &amp; print</label>
                </div>
              </div>
            </div>
            <div class="row g-2">
              <div class="col-12">
                <label class="form-label">Branch name</label>
                <input type="text" name="branch_name[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b['name']); ?>" placeholder="e.g. Kilinochchi">
                <div class="error-text" data-error-for="branch_name_<?php echo (int)$i; ?>">Branch name is required.</div>
              </div>
            </div>
            <div class="addr-grid mt-2">
              <div>
                <label class="form-label">Address (Tamil)</label>
                <input type="text" name="branch_address_ta[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b['address_ta']); ?>" placeholder="தமிழ்">
              </div>
              <div>
                <label class="form-label">Address (English)</label>
                <input type="text" name="branch_address_en[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b['address_en']); ?>" placeholder="English">
                <div class="error-text" data-error-for="branch_address_en_<?php echo (int)$i; ?>">English address is required.</div>
              </div>
            </div>
            <div class="row g-2 mt-1">
              <div class="col-12">
                <label class="form-label">Phones</label>
                <input type="text" name="branch_phones[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($b['phones']); ?>" placeholder="077 … | 077 …">
              </div>
            </div>
          </div>
        <?php endfor; ?>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <button type="submit" class="btn btn-primary btn-sm" id="saveBranchLetterheadBtn"><span class="save-label"><i class="bi bi-check-lg me-1"></i> Save letterhead</span><span class="save-loading d-none"><span class="spinner-border spinner-border-sm me-1"></span>Saving…</span></button>
        <span class="text-muted small">Updates DB and JSON mirror used across TMS.</span>
      </div>
    </form>
  </div>
</div>
