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
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">TMS Settings</h3>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="row">
  <div class="col-lg-8">
    <div class="card shadow-sm mb-4">
      <div class="card-header"><h5 class="mb-0"><i class="bi bi-building me-1"></i> Company / Logo & Address</h5></div>
      <div class="card-body">
        <?php $baseUrlForLogo = rtrim(Helpers::baseUrl(''), '/'); ?>
          <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=settings'); ?>" enctype="multipart/form-data" id="companySettingsForm">
          <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
          <input type="hidden" name="settings_section" value="company">
          <input type="hidden" id="baseUrlForLogo" value="<?php echo htmlspecialchars($baseUrlForLogo); ?>">

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Company Name</label>
              <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($company['name'] ?? 'TS Transport'); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Reg No</label>
              <input type="text" name="reg_no" class="form-control" value="<?php echo htmlspecialchars($company['reg_no'] ?? ''); ?>" placeholder="e.g. KN/KR/1443">
            </div>
          </div>

          <input type="hidden" name="logo_display" value="image">
          <input type="hidden" name="logo_initials" value="<?php echo htmlspecialchars($company['logo_initials'] ?? 'TS'); ?>">
          <input type="hidden" name="logo_arch_color" value="<?php echo htmlspecialchars($company['logo_arch_color'] ?? 'c00'); ?>">
          <input type="hidden" name="logo_bar_bg" value="<?php echo htmlspecialchars($company['logo_bar_bg'] ?? '000'); ?>">
          <input type="hidden" name="logo_bar_color" value="<?php echo htmlspecialchars($company['logo_bar_color'] ?? 'fff'); ?>">
          <input type="hidden" name="logo_title_color" value="<?php echo htmlspecialchars($company['logo_title_color'] ?? 'c00'); ?>">
            <div class="col-12">
              <div class="card border bg-light">
                <div class="card-body py-3">
                  <h6 class="card-title mb-2"><i class="bi bi-image me-1"></i> Company logo</h6>
                  <p class="text-muted small mb-3">Enter a logo URL manually <strong>or</strong> upload a file. Shown on receipts and print views.</p>
                  <?php
                  $currentLogoUrl = $company['logo_url'] ?? '';
                  $previewSrc = '';
                  if ($currentLogoUrl !== '') {
                    $previewSrc = (strpos($currentLogoUrl, 'http') === 0 || strpos($currentLogoUrl, '//') === 0) ? $currentLogoUrl : Helpers::baseUrl($currentLogoUrl);
                  }
                  ?>
                  <div class="mb-3">
                    <label class="form-label small">Preview</label>
                    <div class="d-inline-flex align-items-center p-2 border rounded bg-white">
                      <img id="previewMainLogo" src="<?php echo $previewSrc ? htmlspecialchars($previewSrc) : ''; ?>" alt="Company logo" style="max-height: 56px; max-width: 200px; object-fit: contain;<?php echo $previewSrc ? '' : ' display:none;'; ?>" onerror="this.style.display='none'; document.getElementById('previewPlaceholder')&&(document.getElementById('previewPlaceholder').style.display='');">
                      <span id="previewPlaceholder" class="text-muted small" style="<?php echo $previewSrc ? 'display:none' : ''; ?>">No logo — add URL or upload</span>
                    </div>
                  </div>
                  <div class="row g-3">
                    <div class="col-12 col-md-6">
                      <label class="form-label"><i class="bi bi-link-45deg me-1"></i> Manual — Logo URL</label>
                      <input type="text" name="logo_url" id="logoUrlInput" class="form-control" value="<?php echo htmlspecialchars($company['logo_url'] ?? ''); ?>" placeholder="e.g. uploads/logo.png or https://...">
                      <small class="text-muted">Enter image path (e.g. uploads/logo.png) or full URL.</small>
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label"><i class="bi bi-upload me-1"></i> Upload — Logo file</label>
                      <input type="file" name="logo_file" id="logoFileInput" class="form-control" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp">
                      <small class="text-muted">PNG, JPG, GIF or WebP. Upload overwrites current logo.</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <script>
            (function(){
              var baseUrl = (document.getElementById('baseUrlForLogo') && document.getElementById('baseUrlForLogo').value) || '';
              var mainLogo = document.getElementById('previewMainLogo');
              var placeholder = document.getElementById('previewPlaceholder');
              var urlInput = document.getElementById('logoUrlInput');
              var fileInput = document.getElementById('logoFileInput');
              function showPreview(src){
                if (mainLogo) { mainLogo.src = src || ''; mainLogo.style.display = src ? '' : 'none'; }
                if (placeholder) placeholder.style.display = src ? 'none' : '';
              }
              function updateFromUrl(){
                var v = (urlInput && urlInput.value || '').trim();
                if (v) {
                  var src = (v.indexOf('http') === 0 || v.indexOf('//') === 0) ? v : (baseUrl + '/' + v.replace(/^\//, ''));
                  showPreview(src);
                } else { showPreview(''); }
              }
              if (urlInput) urlInput.addEventListener('input', updateFromUrl);
              if (fileInput) {
                fileInput.addEventListener('change', function(){
                  var f = this.files && this.files[0];
                  if (f && /^image\//.test(f.type)) {
                    var r = new FileReader();
                    r.onload = function(e){ showPreview(e.target.result); };
                    r.readAsDataURL(f);
                  }
                });
              }
            })();
          </script>

          <div class="mb-3">
            <label class="form-label">Route Bar (Tamil) — 3 parts separated by arrows</label>
            <div class="row g-2">
              <div class="col-md-4"><input type="text" name="route_1" class="form-control" value="<?php echo htmlspecialchars($routeParts[0] ?? ''); ?>" placeholder="கொழும்பு"></div>
              <div class="col-md-4"><input type="text" name="route_2" class="form-control" value="<?php echo htmlspecialchars($routeParts[1] ?? ''); ?>" placeholder="கிளிநொச்சி"></div>
              <div class="col-md-4"><input type="text" name="route_3" class="form-control" value="<?php echo htmlspecialchars($routeParts[2] ?? ''); ?>" placeholder="முல்லைத்தீவு"></div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Branch Addresses (for bill header)</label>
            <?php for ($i = 0; $i < 3; $i++): $b = $branches[$i] ?? ['name'=>'','address_ta'=>'','address_en'=>'','phones'=>'']; ?>
              <div class="border rounded p-3 mb-3">
                <div class="row g-2">
                  <div class="col-12"><input type="text" name="branch_name[]" class="form-control" value="<?php echo htmlspecialchars($b['name']); ?>" placeholder="Branch name (e.g. Colombo)"></div>
                  <div class="col-12"><input type="text" name="branch_address_ta[]" class="form-control" value="<?php echo htmlspecialchars($b['address_ta']); ?>" placeholder="Address (Tamil)"></div>
                  <div class="col-12"><input type="text" name="branch_address_en[]" class="form-control" value="<?php echo htmlspecialchars($b['address_en']); ?>" placeholder="Address (English)"></div>
                  <div class="col-12"><input type="text" name="branch_phones[]" class="form-control" value="<?php echo htmlspecialchars($b['phones']); ?>" placeholder="Phones (e.g. 077 2474 905 | 077 2474 177)"></div>
                </div>
              </div>
            <?php endfor; ?>
          </div>

          <div class="mb-3">
            <label class="form-label">Footer Note (on print)</label>
            <textarea name="footer_note" class="form-control" rows="2"><?php echo htmlspecialchars($company['footer_note'] ?? ''); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Google Maps API Key (optional)</label>
            <input type="text" name="google_maps_api_key" class="form-control" value="<?php echo htmlspecialchars($config['google_maps_api_key'] ?? ''); ?>" placeholder="AIza...">
          </div>

          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Company Settings</button>
        </form>
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
