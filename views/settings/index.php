<?php
$company = $company ?? [];
$branches = $company['branches'] ?? [
    ['name' => 'Colombo', 'address_ta' => '', 'address_en' => '', 'phones' => ''],
    ['name' => 'Kilinochchi', 'address_ta' => '', 'address_en' => '', 'phones' => ''],
    ['name' => 'Mullaitivu', 'address_ta' => '', 'address_en' => '', 'phones' => ''],
];
$routeParts = $company['route_tamil_parts'] ?? ['கொழும்பு', 'கிளிநொச்சி', 'முல்லைத்தீவு'];
$routeParts = array_values($routeParts);
while (count($routeParts) < 3) { $routeParts[] = ''; }
$config = $config ?? [];
?>
<style>
  :root { --ui-bg: #f8f9fb; --ui-border: rgba(17,24,39,.10); --ui-shadow: 0 1px 2px rgba(16,24,40,.06); --ui-shadow-hover: 0 6px 18px rgba(16,24,40,.10); --ui-radius: 14px; }
  .settings-page { background: var(--ui-bg); border-radius: var(--ui-radius); padding: 16px; border: 1px solid rgba(17,24,39,.06); }
  .settings-card { border: 1px solid var(--ui-border); border-radius: var(--ui-radius); box-shadow: var(--ui-shadow); overflow: hidden; background: #fff; }
  .settings-card .card-header { background: #fff; border-bottom: 1px solid var(--ui-border); padding: 14px 16px; }
  .settings-card .card-body { padding: 16px; }
  .settings-card .card-footer { background: #fff; border-top: 1px solid var(--ui-border); padding: 14px 16px; }
  .settings-title { font-weight: 800; font-size: 1.05rem; margin: 0; letter-spacing: -.01em; }
  .settings-subtitle { color: #6b7280; font-size: .875rem; margin-top: 2px; }
  .settings-section-label { font-size: .78rem; letter-spacing: .08em; text-transform: uppercase; color: #6c757d; font-weight: 700; margin-bottom: 10px; }
  .settings-page .form-label { font-size: .85rem; font-weight: 600; color: #374151; }
  .settings-page .form-control, .settings-page .form-select { border-radius: 12px; border-color: rgba(17,24,39,.16); }
  .settings-page .form-control:focus, .settings-page .form-select:focus { box-shadow: 0 0 0 .25rem rgba(13,110,253,.12); border-color: rgba(13,110,253,.45); }
  .settings-helper { color: #6c757d; font-size: .85rem; margin-top: 6px; }
  .field-error { border-color: rgba(220,53,69,.65) !important; }
  .error-text { color: #dc3545; font-size: .85rem; margin-top: 6px; display:none; }
  .logo-drop { border: 1.5px dashed rgba(17,24,39,.22); border-radius: var(--ui-radius); background: #fbfcfe; padding: 14px; display:flex; align-items:center; justify-content:space-between; gap: 12px; cursor: pointer; transition: background .15s ease, border-color .15s ease, box-shadow .15s ease; }
  .logo-drop:hover { background: #f4f8ff; border-color: rgba(13,110,253,.55); }
  .logo-drop.is-dragover { background: rgba(13,110,253,.08); border-color: rgba(13,110,253,.65); box-shadow: 0 0 0 .25rem rgba(13,110,253,.10); }
  .logo-preview { width: 120px; height: 64px; border-radius: 10px; border: 1px solid rgba(16,24,40,.12); background:#fff; display:flex; align-items:center; justify-content:center; overflow:hidden; flex: 0 0 auto; }
  .logo-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
  .badge-soft { background: rgba(13,110,253,.1); color: #0d6efd; border: 1px solid rgba(13,110,253,.18); font-weight: 700; }
  .branch-card { border: 1px solid var(--ui-border); border-radius: var(--ui-radius); padding: 14px; background: #fff; box-shadow: var(--ui-shadow); }
  .branch-card.default { border-color: rgba(13,110,253,.35); box-shadow: 0 0 0 .25rem rgba(13,110,253,.08), var(--ui-shadow); }
  .branch-top { display:flex; align-items:flex-start; justify-content:space-between; gap: 10px; }
  .branch-name { font-weight: 700; margin: 0; }
  .addr-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  @media (max-width: 992px) { .addr-grid { grid-template-columns: 1fr; } }
  .print-hint { font-size: .85rem; color:#6c757d; }

  .branch-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  @media (max-width: 992px) { .branch-grid { grid-template-columns: 1fr; } }
  .default-badge { display:none; }
  .branch-card.default .default-badge { display:inline-flex; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">TMS Settings</h3>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000;">
  <div id="companyToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="polite" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">Company details updated successfully.</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<div class="row settings-page">
  <div class="col-lg-8">
    <div class="settings-card mb-4">
      <div class="card-header">
        <div class="d-flex align-items-start justify-content-between gap-3">
          <div>
            <div class="settings-title"><i class="bi bi-buildings me-1"></i> Company Address &amp; Branch Management</div>
            <div class="settings-subtitle">Centralized company settings. The default branch address is used across website header, invoices, print views, and exports.</div>
          </div>
        </div>
      </div>
      <div class="card-body">
        <?php $baseUrlForLogo = rtrim(Helpers::baseUrl(''), '/'); ?>
        <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=settings'); ?>" enctype="multipart/form-data" id="companySettingsForm" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
          <input type="hidden" name="settings_section" value="company">
          <input type="hidden" id="baseUrlForLogo" value="<?php echo htmlspecialchars($baseUrlForLogo); ?>">

          <input type="hidden" name="logo_display" value="image">
          <input type="hidden" name="logo_initials" value="<?php echo htmlspecialchars($company['logo_initials'] ?? 'TS'); ?>">
          <input type="hidden" name="logo_arch_color" value="<?php echo htmlspecialchars($company['logo_arch_color'] ?? 'c00'); ?>">
          <input type="hidden" name="logo_bar_bg" value="<?php echo htmlspecialchars($company['logo_bar_bg'] ?? '000'); ?>">
          <input type="hidden" name="logo_bar_color" value="<?php echo htmlspecialchars($company['logo_bar_color'] ?? 'fff'); ?>">
          <input type="hidden" name="logo_title_color" value="<?php echo htmlspecialchars($company['logo_title_color'] ?? 'c00'); ?>">
          <input type="hidden" name="remove_logo" id="removeLogo" value="0">

          <div class="settings-section-label">Company Information</div>
          <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
              <label class="form-label">Company Name</label>
              <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($company['name'] ?? 'TS Transport'); ?>" required>
              <div class="settings-helper">Displayed in the app header and print headers.</div>
              <div class="error-text" data-error-for="company_name">Company name is required.</div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Reg No</label>
              <input type="text" name="reg_no" class="form-control" value="<?php echo htmlspecialchars($company['reg_no'] ?? ''); ?>" placeholder="e.g. KN/KR/1443">
              <div class="settings-helper">Shown on receipts/invoices where applicable.</div>
            </div>
          </div>

          <div class="settings-section-label">Company Logo</div>
          <?php
            $currentLogoUrl = $company['logo_url'] ?? '';
            $previewSrc = '';
            if ($currentLogoUrl !== '') {
              $previewSrc = (strpos($currentLogoUrl, 'http') === 0 || strpos($currentLogoUrl, '//') === 0) ? $currentLogoUrl : Helpers::baseUrl($currentLogoUrl);
            }
          ?>
          <div class="row g-3 mb-4">
            <div class="col-12">
              <div id="logoDrop" class="logo-drop" role="button" tabindex="0" aria-label="Upload company logo">
                <div class="d-flex flex-column">
                  <div class="fw-semibold">Drag &amp; drop your logo here</div>
                  <div class="settings-helper mb-0">PNG, JPG, GIF or WebP. Used in invoice/print headers and PDF exports.</div>
                  <div class="print-hint mt-1">Tip: Use a transparent PNG for best results on A4 invoices.</div>
                </div>
                <div class="logo-preview">
                  <img id="previewMainLogo" src="<?php echo $previewSrc ? htmlspecialchars($previewSrc) : ''; ?>" alt="Company logo" style="<?php echo $previewSrc ? '' : 'display:none;'; ?>" onerror="this.style.display='none'; document.getElementById('previewPlaceholder')&&(document.getElementById('previewPlaceholder').style.display='');">
                  <span id="previewPlaceholder" class="text-muted small" style="<?php echo $previewSrc ? 'display:none' : ''; ?>">No logo</span>
                </div>
              </div>
              <input type="file" name="logo_file" id="logoFileInput" class="d-none" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp">
              <div class="row g-2 mt-2">
                <div class="col-12 col-md-8">
                  <label class="form-label">Logo URL (optional)</label>
                  <input type="text" name="logo_url" id="logoUrlInput" class="form-control" value="<?php echo htmlspecialchars($company['logo_url'] ?? ''); ?>" placeholder="e.g. uploads/logo.png or https://...">
                  <div class="settings-helper">If you upload a file, this will be updated automatically.</div>
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                  <button type="button" class="btn btn-outline-primary w-100" id="logoReplaceBtn"><i class="bi bi-upload me-1"></i> Replace</button>
                  <button type="button" class="btn btn-outline-danger w-100" id="logoRemoveBtn"><i class="bi bi-trash me-1"></i> Remove</button>
                </div>
              </div>
            </div>
          </div>

          <div class="settings-section-label">Primary Branch - Header &amp; Billing Address</div>
          <div class="settings-helper mb-3">Displayed on invoices and official documents. The selected default is used in the website header, print views, and exports.</div>

          <input type="hidden" name="default_branch_idx" id="defaultBranchIdx" value="0">

          <?php $b0 = $branches[0] ?? ['name'=>'','address_ta'=>'','address_en'=>'','phones'=>'']; ?>
          <div class="branch-card mb-3 default" data-branch-index="0">
            <div class="branch-top mb-2">
              <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <h6 class="branch-name mb-0">Primary Branch</h6>
                  <span class="badge badge-soft default-badge">Default for Header &amp; Billing</span>
                </div>
                <div class="settings-helper mb-0">Keep Tamil &amp; English addresses accurate for billing and customer clarity.</div>
              </div>
              <div class="text-end">
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="radio" name="default_branch_idx" value="0" id="default_branch_0" checked>
                  <label class="form-check-label small" for="default_branch_0">Use as Default Header &amp; Print Address</label>
                </div>
              </div>
            </div>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Branch Name</label>
                <input type="text" name="branch_name[]" class="form-control" value="<?php echo htmlspecialchars($b0['name']); ?>" placeholder="e.g. Colombo" required>
                <div class="error-text" data-error-for="branch_name_0">Branch name is required.</div>
              </div>
            </div>
            <div class="addr-grid mt-3">
              <div>
                <label class="form-label">Address (Tamil)</label>
                <input type="text" name="branch_address_ta[]" class="form-control" value="<?php echo htmlspecialchars($b0['address_ta']); ?>" placeholder="தமிழ் முகவரி">
                <div class="settings-helper">Used on bilingual headers where applicable.</div>
              </div>
              <div>
                <label class="form-label">Address (English)</label>
                <input type="text" name="branch_address_en[]" class="form-control" value="<?php echo htmlspecialchars($b0['address_en']); ?>" placeholder="English address" required>
                <div class="error-text" data-error-for="branch_address_en_0">English address is required.</div>
              </div>
            </div>
            <div class="row g-3 mt-1">
              <div class="col-12">
                <label class="form-label">Phones</label>
                <input type="text" name="branch_phones[]" class="form-control" value="<?php echo htmlspecialchars($b0['phones']); ?>" placeholder="e.g. 077 2474 905 | 077 2474 177">
                <div class="settings-helper">Separate multiple numbers using <code>|</code>.</div>
              </div>
            </div>
          </div>

          <div class="settings-section-label mt-4">Additional Branches</div>
          <div class="settings-helper mb-3">You can store up to 3 branch addresses. Choose which one is used as the default header &amp; print address.</div>
          <div class="branch-grid mb-4">
            <?php for ($i = 1; $i < 3; $i++): $b = $branches[$i] ?? ['name'=>'','address_ta'=>'','address_en'=>'','phones'=>'']; ?>
              <div class="branch-card" data-branch-index="<?php echo (int)$i; ?>">
                <div class="branch-top mb-2">
                  <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <h6 class="branch-name mb-0">Branch <?php echo (int)($i + 1); ?></h6>
                      <span class="badge badge-soft default-badge">Default for Header &amp; Billing</span>
                    </div>
                    <div class="settings-helper mb-0">This branch will also appear in header/print when configured.</div>
                  </div>
                  <div class="text-end">
                    <div class="form-check form-switch m-0">
                      <input class="form-check-input" type="radio" name="default_branch_idx" value="<?php echo (int)$i; ?>" id="default_branch_<?php echo (int)$i; ?>">
                      <label class="form-check-label small" for="default_branch_<?php echo (int)$i; ?>">Use as Default Header &amp; Print Address</label>
                    </div>
                  </div>
                </div>
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label">Branch Name</label>
                    <input type="text" name="branch_name[]" class="form-control" value="<?php echo htmlspecialchars($b['name']); ?>" placeholder="e.g. Kilinochchi" required>
                    <div class="error-text" data-error-for="branch_name_<?php echo (int)$i; ?>">Branch name is required.</div>
                  </div>
                </div>
                <div class="addr-grid mt-3">
                  <div>
                    <label class="form-label">Address (Tamil)</label>
                    <input type="text" name="branch_address_ta[]" class="form-control" value="<?php echo htmlspecialchars($b['address_ta']); ?>" placeholder="தமிழ் முகவரி">
                  </div>
                  <div>
                    <label class="form-label">Address (English)</label>
                    <input type="text" name="branch_address_en[]" class="form-control" value="<?php echo htmlspecialchars($b['address_en']); ?>" placeholder="English address" required>
                    <div class="error-text" data-error-for="branch_address_en_<?php echo (int)$i; ?>">English address is required.</div>
                  </div>
                </div>
                <div class="row g-3 mt-1">
                  <div class="col-12">
                    <label class="form-label">Phones</label>
                    <input type="text" name="branch_phones[]" class="form-control" value="<?php echo htmlspecialchars($b['phones']); ?>" placeholder="e.g. 077 2474 905 | 077 2474 177">
                    <div class="settings-helper">Separate multiple numbers using <code>|</code>.</div>
                  </div>
                </div>
              </div>
            <?php endfor; ?>
          </div>

          <div class="settings-section-label">Route Bar (Tamil)</div>
          <div class="row g-2 mb-4">
            <div class="col-12 col-md-4"><label class="form-label">Part 1</label><input type="text" name="route_1" class="form-control" value="<?php echo htmlspecialchars($routeParts[0] ?? ''); ?>" placeholder="கொழும்பு"></div>
            <div class="col-12 col-md-4"><label class="form-label">Part 2</label><input type="text" name="route_2" class="form-control" value="<?php echo htmlspecialchars($routeParts[1] ?? ''); ?>" placeholder="கிளிநொச்சி"></div>
            <div class="col-12 col-md-4"><label class="form-label">Part 3</label><input type="text" name="route_3" class="form-control" value="<?php echo htmlspecialchars($routeParts[2] ?? ''); ?>" placeholder="முல்லைத்தீவு"></div>
            <div class="col-12"><div class="settings-helper">Displayed on some print templates as a route banner.</div></div>
          </div>

          <div class="settings-section-label">Print Footer</div>
          <div class="mb-4">
            <label class="form-label">Footer Note (on print)</label>
            <textarea name="footer_note" class="form-control" rows="2"><?php echo htmlspecialchars($company['footer_note'] ?? ''); ?></textarea>
            <div class="settings-helper">Appears at the bottom of receipts/invoices.</div>
          </div>

          <div class="settings-section-label">Integrations</div>
          <div class="mb-4">
            <label class="form-label">Google Maps API Key (optional)</label>
            <input type="text" name="google_maps_api_key" class="form-control" value="<?php echo htmlspecialchars($config['google_maps_api_key'] ?? ''); ?>" placeholder="AIza...">
            <div class="settings-helper">Used for Places Autocomplete (if enabled) in customer/delivery location forms.</div>
          </div>

          <div class="d-flex flex-wrap gap-2 align-items-center">
            <button type="submit" class="btn btn-primary" id="saveCompanyBtn"><span class="save-label"><i class="bi bi-check-lg me-1"></i> Save Changes</span><span class="save-loading d-none"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...</span></button>
            <span class="text-muted small">Changes reflect everywhere automatically (single source of truth).</span>
          </div>
        </form>

        <script>
          (function(){
            var form = document.getElementById('companySettingsForm');
            if (!form) return;
            var baseUrl = (document.getElementById('baseUrlForLogo') && document.getElementById('baseUrlForLogo').value) || '';
            var drop = document.getElementById('logoDrop');
            var fileInput = document.getElementById('logoFileInput');
            var urlInput = document.getElementById('logoUrlInput');
            var mainLogo = document.getElementById('previewMainLogo');
            var placeholder = document.getElementById('previewPlaceholder');
            var removeLogo = document.getElementById('removeLogo');
            var replaceBtn = document.getElementById('logoReplaceBtn');
            var removeBtn = document.getElementById('logoRemoveBtn');

            function showPreview(src){
              if (mainLogo) { mainLogo.src = src || ''; mainLogo.style.display = src ? '' : 'none'; }
              if (placeholder) placeholder.style.display = src ? 'none' : '';
            }
            function resolveUrl(v){
              v = (v || '').trim();
              if (!v) return '';
              return (v.indexOf('http') === 0 || v.indexOf('//') === 0) ? v : (baseUrl + '/' + v.replace(/^\//, ''));
            }
            function updateFromUrl(){
              var v = (urlInput && urlInput.value || '').trim();
              if (!v) { showPreview(''); return; }
              showPreview(resolveUrl(v));
            }

            if (urlInput) urlInput.addEventListener('input', function(){ if (removeLogo) removeLogo.value = '0'; updateFromUrl(); });

            function pickFile(){ if (fileInput) fileInput.click(); }
            var dirty = false;
            var saveBtn = document.getElementById('saveCompanyBtn');
            function setDirty(on){ dirty = !!on; }
            form.addEventListener('input', function(){ setDirty(true); });
            window.addEventListener('beforeunload', function(e){ if (!dirty) return; e.preventDefault(); e.returnValue = ''; });

            if (drop) {
              drop.addEventListener('click', pickFile);
              drop.addEventListener('keydown', function(e){ if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pickFile(); } });
              drop.addEventListener('dragover', function(e){ e.preventDefault(); drop.classList.add('is-dragover'); });
              drop.addEventListener('dragleave', function(){ drop.classList.remove('is-dragover'); });
              drop.addEventListener('drop', function(e){
                e.preventDefault();
                drop.classList.remove('is-dragover');
                if (!fileInput) return;
                var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
                if (!f) return;
                if (!/^image\//.test(f.type)) return;
                fileInput.files = e.dataTransfer.files;
                if (removeLogo) removeLogo.value = '0';
                var r = new FileReader();
                r.onload = function(ev){ showPreview(ev.target.result); };
                r.readAsDataURL(f);
              });
            }

            if (fileInput) {
              fileInput.addEventListener('change', function(){
                var f = this.files && this.files[0];
                if (f && /^image\//.test(f.type)) {
                  if (removeLogo) removeLogo.value = '0';
                  var r = new FileReader();
                  r.onload = function(e){ showPreview(e.target.result); };
                  r.readAsDataURL(f);
                }
              });
            }
            if (replaceBtn) replaceBtn.addEventListener('click', pickFile);
            if (removeBtn) {
              removeBtn.addEventListener('click', function(){
                if (removeLogo) removeLogo.value = '1';
                if (urlInput) urlInput.value = '';
                if (fileInput) fileInput.value = '';
                showPreview('');
              });
            }

            // Inline validation (clean states)
            function setErr(input, on, msg){
              if (!input) return;
              if (on) input.classList.add('field-error'); else input.classList.remove('field-error');
              if (!msg) return;
            }
            function validate(){
              var ok = true;
              var companyName = form.querySelector('input[name="company_name"]');
              if (companyName && companyName.value.trim() === '') { ok = false; setErr(companyName, true); var e1 = form.querySelector('[data-error-for="company_name"]'); if (e1) e1.style.display = ''; }
              else { setErr(companyName, false); var e1b = form.querySelector('[data-error-for="company_name"]'); if (e1b) e1b.style.display = 'none'; }

              var branchNames = form.querySelectorAll('input[name="branch_name[]"]');
              var branchEns = form.querySelectorAll('input[name="branch_address_en[]"]');
              for (var i=0; i<branchNames.length; i++) {
                var bn = branchNames[i];
                if (bn && bn.value.trim() === '') { ok = false; setErr(bn, true); } else { setErr(bn, false); }
              }
              for (var j=0; j<branchEns.length; j++) {
                var be = branchEns[j];
                if (be && be.value.trim() === '') { ok = false; setErr(be, true); } else { setErr(be, false); }
              }
              return ok;
            }
            form.addEventListener('submit', function(e){
              if (!validate()) { e.preventDefault(); e.stopPropagation(); return; }
              if (saveBtn) {
                saveBtn.disabled = true;
                var a = saveBtn.querySelector('.save-label');
                var b = saveBtn.querySelector('.save-loading');
                if (a) a.classList.add('d-none');
                if (b) b.classList.remove('d-none');
              }
            });
            form.addEventListener('input', function(){ validate(); });

            // Default branch selection: keep in sync with visual default state
            var radios = form.querySelectorAll('input[name="default_branch_idx"]');
            function refreshDefault(){
              var selected = 0;
              for (var i=0;i<radios.length;i++){ if (radios[i].checked) { selected = parseInt(radios[i].value || '0',10) || 0; break; } }
              var cards = form.querySelectorAll('.branch-card');
              for (var c=0;c<cards.length;c++) {
                var idx = parseInt(cards[c].getAttribute('data-branch-index') || '0', 10) || 0;
                if (idx === selected) { cards[c].classList.add('default'); }
                else { cards[c].classList.remove('default'); }
              }
            }
            for (var r=0;r<radios.length;r++){ radios[r].addEventListener('change', refreshDefault); }
            refreshDefault();
            updateFromUrl();

            try {
              var success = <?php echo !empty($success) ? 'true' : 'false'; ?>;
              if (success && window.bootstrap && bootstrap.Toast) {
                var el = document.getElementById('companyToast');
                if (el) { new bootstrap.Toast(el, { delay: 2500 }).show(); }
              }
            } catch(e) {}
          })();
        </script>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="bi bi-key me-1"></i> Password</h5></div>
      <div class="card-body">
        <p class="text-muted small">Change your login password.</p>
        <a href="<?php echo Helpers::baseUrl('index.php?page=change_password'); ?>" class="btn btn-outline-primary w-100"><i class="bi bi-lock me-1"></i> Change Password</a>
      </div>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="bi bi-people me-1"></i> Users</h5></div>
      <div class="card-body">
        <p class="text-muted small">Create and manage user accounts.</p>
        <a href="<?php echo Helpers::baseUrl('index.php?page=users'); ?>" class="btn btn-outline-secondary w-100 mb-2"><i class="bi bi-list me-1"></i> View Users</a>
        <a href="<?php echo Helpers::baseUrl('index.php?page=users&action=new'); ?>" class="btn btn-primary w-100"><i class="bi bi-person-plus me-1"></i> Create New User</a>
      </div>
    </div>
  </div>
</div>
