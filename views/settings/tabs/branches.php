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

$fixedBranchLabels = [
    0 => ['title' => 'Branch 1 — Colombo', 'code' => 'COL'],
    1 => ['title' => 'Branch 2 — Kilinochchi', 'code' => 'KIL'],
    2 => ['title' => 'Branch 3 — Mullaitivu', 'code' => 'MUL'],
];
?>

<div class="alert alert-info py-2 small mb-3" role="status">
  This system uses exactly <strong>three fixed branches</strong>: Colombo, Kilinochchi, and Mullaitivu.
  Branch names cannot be added or removed. Update addresses and phone numbers below; they appear on invoices, receipts, letterheads, and all branch dropdowns.
</div>

<div class="settings-section-label mb-2">Fixed branches (letterhead &amp; billing)</div>
<div class="settings-card mb-3">
  <div class="card-body py-3">
    <form method="post" action="<?php echo htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? Helpers::baseUrl('index.php?page=settings'))); ?>" id="branchLetterheadForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
      <input type="hidden" name="settings_section" value="branch_letterhead">
      <input type="hidden" name="settings_return_tab" value="branches">

      <div id="branchLetterheadClientError" class="alert alert-danger d-none py-2" role="alert"></div>

      <p class="small text-muted mb-3">Layout on prints: <strong>Colombo | Kilinochchi | Mullaitivu</strong>. Tamil and English addresses and phones sync to the database and <code>company.json</code>.</p>

      <?php for ($i = 0; $i < 3; $i++):
          $b = $settingsBranchSlots[$i] ?? ['name' => '', 'address_ta' => '', 'address_en' => '', 'phones' => ''];
          $branchId = $i + 1;
          $label = $fixedBranchLabels[$i];
          $canonicalName = (string)($label['title']);
          ?>
        <div class="branch-card branch-letterhead-card mb-3<?php echo ($defaultBranchSlotIndex === $i) ? ' default' : ''; ?>" data-branch-index="<?php echo (int)$i; ?>">
          <input type="hidden" name="branch_db_id[]" value="<?php echo (int)$branchId; ?>">
          <input type="hidden" name="branch_name[]" value="<?php echo htmlspecialchars(trim(explode('—', $canonicalName, 2)[1] ?? ($b['name'] ?? ''))); ?>">
          <div class="branch-top mb-2">
            <div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <h6 class="branch-name mb-0"><?php echo htmlspecialchars($label['title']); ?></h6>
                <span class="badge text-bg-secondary"><?php echo htmlspecialchars($label['code']); ?></span>
                <?php if ($defaultBranchSlotIndex === $i): ?>
                  <span class="badge badge-soft default-badge">Default header &amp; billing</span>
                <?php endif; ?>
              </div>
              <div class="settings-helper mb-0 small">ID <?php echo (int)$branchId; ?> · Name is fixed</div>
            </div>
            <div class="text-end">
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="radio" name="default_branch_idx" value="<?php echo (int)$i; ?>" id="default_branch_<?php echo (int)$i; ?>" <?php echo ($defaultBranchSlotIndex === $i) ? 'checked' : ''; ?>>
                <label class="form-check-label small" for="default_branch_<?php echo (int)$i; ?>">Default header &amp; print</label>
              </div>
            </div>
          </div>
          <div class="addr-grid mt-2">
            <div>
              <label class="form-label">Address (Tamil) <span class="text-danger">*</span></label>
              <input type="text" name="branch_address_ta[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($b['address_ta'] ?? '')); ?>" placeholder="தமிழ் முகவரி" required>
              <div class="error-text" data-error-for="branch_address_ta_<?php echo (int)$i; ?>">Tamil address is required.</div>
            </div>
            <div>
              <label class="form-label">Address (English) <span class="text-danger">*</span></label>
              <input type="text" name="branch_address_en[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($b['address_en'] ?? '')); ?>" placeholder="English address" required>
              <div class="error-text" data-error-for="branch_address_en_<?php echo (int)$i; ?>">English address is required.</div>
            </div>
          </div>
          <div class="row g-2 mt-1">
            <div class="col-12">
              <label class="form-label">Phones <span class="text-danger">*</span></label>
              <input type="text" name="branch_phones[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($b['phones'] ?? '')); ?>" placeholder="077 … | 077 …" required>
              <div class="settings-helper small">Separate with <code>|</code>.</div>
              <div class="error-text" data-error-for="branch_phones_<?php echo (int)$i; ?>">Phone numbers are required.</div>
            </div>
          </div>
        </div>
      <?php endfor; ?>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <button type="submit" class="btn btn-primary btn-sm" id="saveBranchLetterheadBtn"><span class="save-label"><i class="bi bi-check-lg me-1"></i> Save branch details</span><span class="save-loading d-none"><span class="spinner-border spinner-border-sm me-1"></span>Saving…</span></button>
        <span class="text-muted small">Updates all letterheads, prints, and dropdowns system-wide.</span>
      </div>
    </form>
  </div>
</div>

<?php
  $branchesMaster = $branchesMaster ?? [];
  $branches = $branchesMaster;
  $branchListEmbed = true;
  $branchListFixedMode = true;
  $branchListError = '';
  if (!empty($_SESSION['branch_list_flash_err'])) {
      $branchListError = (string)$_SESSION['branch_list_flash_err'];
      unset($_SESSION['branch_list_flash_err']);
  }
  include dirname(__DIR__, 2) . '/branches/index.php';
?>
