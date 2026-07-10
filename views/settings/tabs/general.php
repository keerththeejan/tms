<?php
$company = $company ?? [];
$routeParts = $company['route_tamil_parts'] ?? ['கொழும்பு', 'கிளிநொச்சி', 'முல்லைத்தீவு'];
$routeParts = array_values($routeParts);
while (count($routeParts) < 3) {
    $routeParts[] = '';
}
?>
<div class="settings-card mb-3">
  <div class="card-header py-2 px-3">
    <div class="settings-title mb-0"><i class="bi bi-building me-1"></i> Company &amp; print options</div>
    <div class="settings-subtitle">Name, logo, route banner, and invoice footer. Branch letterhead is under the <strong>Branches</strong> tab.</div>
  </div>
  <div class="card-body py-3">
    <?php $baseUrlForLogo = rtrim(Helpers::baseUrl(''), '/'); ?>
    <form method="post" action="<?php echo htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? Helpers::baseUrl('index.php?page=settings'))); ?>" enctype="multipart/form-data" id="companyGeneralForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
      <input type="hidden" name="settings_section" value="general">
      <input type="hidden" name="settings_return_tab" value="general">
      <input type="hidden" id="baseUrlForLogo" value="<?php echo htmlspecialchars($baseUrlForLogo); ?>">

      <div id="generalClientError" class="alert alert-danger d-none py-2" role="alert"></div>

      <input type="hidden" name="logo_display" value="image">
      <input type="hidden" name="logo_initials" value="<?php echo htmlspecialchars($company['logo_initials'] ?? 'TS'); ?>">
      <input type="hidden" name="logo_arch_color" value="<?php echo htmlspecialchars($company['logo_arch_color'] ?? 'c00'); ?>">
      <input type="hidden" name="logo_bar_bg" value="<?php echo htmlspecialchars($company['logo_bar_bg'] ?? '000'); ?>">
      <input type="hidden" name="logo_bar_color" value="<?php echo htmlspecialchars($company['logo_bar_color'] ?? 'fff'); ?>">
      <input type="hidden" name="logo_title_color" value="<?php echo htmlspecialchars($company['logo_title_color'] ?? 'c00'); ?>">
      <input type="hidden" name="remove_logo" id="removeLogo" value="0">

      <div class="settings-section-label">Company</div>
      <div class="row g-2 mb-3">
        <div class="col-12 col-md-6">
          <label class="form-label">Company name</label>
          <input type="text" name="company_name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($company['name'] ?? 'TS Transport'); ?>" required>
          <div class="settings-helper">App header and print titles.</div>
          <div class="error-text" data-error-for="company_name">Company name is required.</div>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label">Reg No</label>
          <input type="text" name="reg_no" class="form-control form-control-sm" value="<?php echo htmlspecialchars($company['reg_no'] ?? ''); ?>" placeholder="e.g. KN/KR/1443">
        </div>
      </div>

      <div class="settings-section-label">Logo</div>
      <?php
        $currentLogoUrl = $company['logo_url'] ?? '';
        $previewSrc = '';
        if ($currentLogoUrl !== '') {
            $previewSrc = (strpos($currentLogoUrl, 'http') === 0 || strpos($currentLogoUrl, '//') === 0) ? $currentLogoUrl : Helpers::baseUrl($currentLogoUrl);
        }
      ?>
      <div class="row g-2 mb-3">
        <div class="col-12">
          <div id="logoDrop" class="logo-drop py-2 px-3" role="button" tabindex="0" aria-label="Upload company logo">
            <div class="d-flex flex-column">
              <div class="fw-semibold small">Drag &amp; drop logo</div>
              <div class="settings-helper mb-0 small">PNG, JPG, GIF, WebP — invoices &amp; PDFs.</div>
            </div>
            <div class="logo-preview" style="width:100px;height:52px;">
              <img id="previewMainLogo" src="<?php echo $previewSrc ? htmlspecialchars($previewSrc) : ''; ?>" alt="" style="<?php echo $previewSrc ? '' : 'display:none;'; ?>" onerror="this.style.display='none'; document.getElementById('previewPlaceholder')&&(document.getElementById('previewPlaceholder').style.display='');">
              <span id="previewPlaceholder" class="text-muted small" style="<?php echo $previewSrc ? 'display:none' : ''; ?>">No logo</span>
            </div>
          </div>
          <input type="file" name="logo_file" id="logoFileInput" class="d-none" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp">
          <div class="row g-2 mt-2">
            <div class="col-12 col-md-8">
              <label class="form-label">Logo URL (optional)</label>
              <input type="text" name="logo_url" id="logoUrlInput" class="form-control form-control-sm" value="<?php echo htmlspecialchars($company['logo_url'] ?? ''); ?>" placeholder="uploads/logo.png or https://…">
            </div>
            <div class="col-12 col-md-4 d-flex align-items-end gap-1">
              <button type="button" class="btn btn-sm btn-outline-primary flex-fill" id="logoReplaceBtn"><i class="bi bi-upload"></i></button>
              <button type="button" class="btn btn-sm btn-outline-danger flex-fill" id="logoRemoveBtn"><i class="bi bi-trash"></i></button>
            </div>
          </div>
        </div>
      </div>

      <div class="settings-section-label">Route bar (Tamil)</div>
      <div class="row g-2 mb-3">
        <div class="col-4 col-md-4"><label class="form-label">Part 1</label><input type="text" name="route_1" class="form-control form-control-sm" value="<?php echo htmlspecialchars($routeParts[0] ?? ''); ?>"></div>
        <div class="col-4 col-md-4"><label class="form-label">Part 2</label><input type="text" name="route_2" class="form-control form-control-sm" value="<?php echo htmlspecialchars($routeParts[1] ?? ''); ?>"></div>
        <div class="col-4 col-md-4"><label class="form-label">Part 3</label><input type="text" name="route_3" class="form-control form-control-sm" value="<?php echo htmlspecialchars($routeParts[2] ?? ''); ?>"></div>
      </div>

      <div class="settings-section-label">Print footer</div>
      <div class="mb-3">
        <label class="form-label">Footer note</label>
        <textarea name="footer_note" class="form-control form-control-sm" rows="2"><?php echo htmlspecialchars($company['footer_note'] ?? ''); ?></textarea>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center">
        <button type="submit" class="btn btn-primary btn-sm" id="saveGeneralBtn"><span class="save-label"><i class="bi bi-check-lg me-1"></i> Save</span><span class="save-loading d-none"><span class="spinner-border spinner-border-sm me-1"></span>Saving…</span></button>
        <span class="text-muted small">Branch addresses are saved separately under <strong>Branches</strong>.</span>
      </div>
    </form>
  </div>
</div>
