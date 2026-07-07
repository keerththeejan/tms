<?php /** @var array $userRow */ ?>
<?php
  $rolesCatalog = $rolesCatalog ?? [];
  $rolesDynamic = $rolesDynamic ?? [];
  $currentUserId = (int)($currentUserId ?? 0);
  $isSelf = ((int)($userRow['id'] ?? 0) > 0) && ((int)($userRow['id'] ?? 0) === $currentUserId);
  $isNew = !((int)($userRow['id'] ?? 0));
  $pageTitle = $isNew ? 'Create New User' : 'Edit User';
  $pageSubtitle = $isNew
    ? 'Create a new system user with secure access permissions.'
    : 'Update user profile, credentials, and access settings.';
  $existingUsernames = $existingUsernames ?? [];
?>
<div class="usr-form-page">
  <header class="usr-form-hero">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-stretch align-items-lg-center gap-3">
      <div class="d-flex align-items-start gap-3 min-w-0">
        <div class="usr-form-hero-icon" aria-hidden="true">
          <i class="bi bi-person-plus-fill"></i>
        </div>
        <div class="min-w-0">
          <h1 class="usr-form-hero-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
          <p class="usr-form-hero-sub"><?php echo htmlspecialchars($pageSubtitle); ?></p>
        </div>
      </div>
      <div class="usr-form-hero-actions d-flex flex-wrap gap-2 justify-content-lg-end">
        <a href="<?php echo Helpers::baseUrl('index.php?page=users'); ?>" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back
        </a>
        <button type="submit" form="userFormMain" class="btn btn-primary usr-form-submit-btn">
          <span class="usr-btn-text"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Save</span>
          <span class="usr-btn-spinner spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        </button>
      </div>
    </div>
  </header>

  <?php if (!empty($error)): ?>
    <div id="usrFormAlert" class="alert alert-danger usr-alert-premium mb-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
      <?php echo htmlspecialchars($error); ?>
      <?php if (!empty($suggestedUsername)): ?>
        <div class="mt-2 small">Suggested: <strong><?php echo htmlspecialchars($suggestedUsername); ?></strong></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-8">
      <form id="userFormMain" method="post" action="<?php echo Helpers::baseUrl('index.php?page=users&action=save'); ?>" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
        <input type="hidden" name="id" value="<?php echo (int)$userRow['id']; ?>">

        <!-- Card 1: Personal Information -->
        <section class="usr-form-card" aria-labelledby="usrCardPersonalTitle">
          <div class="usr-form-card-header">
            <div class="usr-form-card-header-icon" aria-hidden="true"><i class="bi bi-person-vcard"></i></div>
            <h2 class="usr-form-card-title" id="usrCardPersonalTitle">Personal Information</h2>
          </div>
          <div class="usr-form-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="usr-form-label" for="usrFullName">Full Name <span class="text-danger">*</span></label>
                <div class="input-group usr-input-group">
                  <span class="input-group-text" aria-hidden="true"><i class="bi bi-person"></i></span>
                  <input type="text" id="usrFullName" name="full_name" class="form-control usr-form-control" required
                    value="<?php echo htmlspecialchars($userRow['full_name']); ?>" autocomplete="name"
                    aria-required="true" placeholder="Enter full name">
                </div>
              </div>
              <div class="col-md-6">
                <label class="usr-form-label" for="usrUsername">Username <span class="text-danger">*</span></label>
                <div class="input-group usr-input-group">
                  <span class="input-group-text" aria-hidden="true"><i class="bi bi-at"></i></span>
                  <input type="text" id="usrUsername" name="username" class="form-control usr-form-control" required
                    value="<?php echo htmlspecialchars($userRow['username']); ?>" autocomplete="username"
                    aria-required="true" placeholder="Unique login name" aria-describedby="usrUsernameHint">
                </div>
                <div id="usrUsernameHint" class="usr-field-hint" role="status"></div>
              </div>
              <div class="col-md-6">
                <label class="usr-form-label" for="usrEmail">Email</label>
                <div class="input-group usr-input-group">
                  <span class="input-group-text" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                  <input type="email" id="usrEmail" class="form-control usr-form-control" autocomplete="email"
                    placeholder="name@company.com" aria-describedby="usrEmailHint usrEmailUiHint">
                </div>
                <div id="usrEmailHint" class="usr-field-hint" role="status"></div>
                <div id="usrEmailUiHint" class="usr-ui-only-hint">Optional — for your reference (not stored)</div>
              </div>
              <div class="col-md-6">
                <label class="usr-form-label" for="usrMobile">Mobile Number</label>
                <div class="input-group usr-input-group">
                  <span class="input-group-text" aria-hidden="true"><i class="bi bi-phone"></i></span>
                  <input type="tel" id="usrMobile" class="form-control usr-form-control" inputmode="tel"
                    autocomplete="tel" placeholder="+94 77 000 0000" aria-describedby="usrMobileHint usrMobileUiHint">
                </div>
                <div id="usrMobileHint" class="usr-field-hint" role="status"></div>
                <div id="usrMobileUiHint" class="usr-ui-only-hint">Optional — for your reference (not stored)</div>
              </div>
            </div>
          </div>
        </section>

        <!-- Card 2: Account Security -->
        <section class="usr-form-card" aria-labelledby="usrCardSecurityTitle">
          <div class="usr-form-card-header">
            <div class="usr-form-card-header-icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></div>
            <h2 class="usr-form-card-title" id="usrCardSecurityTitle">Account Security</h2>
          </div>
          <div class="usr-form-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="usr-form-label" for="usrPassword">
                  Password <?php echo $isNew ? '<span class="text-danger">*</span>' : '<span class="text-muted fw-normal">(leave blank to keep)</span>'; ?>
                </label>
                <div class="input-group usr-input-group">
                  <span class="input-group-text" aria-hidden="true"><i class="bi bi-key"></i></span>
                  <input type="password" id="usrPassword" name="password" class="form-control usr-form-control"
                    <?php echo $isNew ? 'required' : ''; ?> autocomplete="new-password"
                    aria-describedby="usrPwdHint usrStrengthLabel">
                  <button type="button" class="btn usr-pwd-toggle" data-target="usrPassword"
                    aria-label="Show password"><i class="bi bi-eye" aria-hidden="true"></i></button>
                </div>
                <div id="usrPwdHint" class="usr-field-hint" role="status"></div>
                <div class="usr-strength-track" aria-hidden="true"><div id="usrStrengthBar" class="usr-strength-bar"></div></div>
                <div id="usrStrengthLabel" class="usr-strength-label text-muted" role="status">Enter a password</div>
                <ul class="usr-req-list" aria-label="Password requirements">
                  <li data-pwd-rule="length"><i class="bi bi-circle" aria-hidden="true"></i> Minimum 8 characters</li>
                  <li data-pwd-rule="upper"><i class="bi bi-circle" aria-hidden="true"></i> Uppercase</li>
                  <li data-pwd-rule="lower"><i class="bi bi-circle" aria-hidden="true"></i> Lowercase</li>
                  <li data-pwd-rule="number"><i class="bi bi-circle" aria-hidden="true"></i> Number</li>
                  <li data-pwd-rule="special"><i class="bi bi-circle" aria-hidden="true"></i> Special character</li>
                </ul>
              </div>
              <div class="col-md-6">
                <label class="usr-form-label" for="usrPasswordConfirm">Confirm Password</label>
                <div class="input-group usr-input-group">
                  <span class="input-group-text" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                  <input type="password" id="usrPasswordConfirm" class="form-control usr-form-control"
                    autocomplete="new-password" aria-describedby="usrPwdConfirmHint">
                  <button type="button" class="btn usr-pwd-toggle" data-target="usrPasswordConfirm"
                    aria-label="Show confirm password"><i class="bi bi-eye" aria-hidden="true"></i></button>
                </div>
                <div id="usrPwdConfirmHint" class="usr-field-hint" role="status"></div>
              </div>
            </div>
          </div>
        </section>

        <!-- Card 3: Organization -->
        <section class="usr-form-card" aria-labelledby="usrCardOrgTitle">
          <div class="usr-form-card-header">
            <div class="usr-form-card-header-icon" aria-hidden="true"><i class="bi bi-building"></i></div>
            <h2 class="usr-form-card-title" id="usrCardOrgTitle">Organization</h2>
          </div>
          <div class="usr-form-card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="usr-form-label" for="usrBranch">Branch</label>
                <div class="input-group usr-input-group">
                  <span class="input-group-text" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                  <select name="branch_id" id="usrBranch" class="form-select usr-form-control">
                    <option value="0">— None —</option>
                    <?php foreach (($branchesAll ?? []) as $b): ?>
                      <?php
                        $bid = (int)$b['id'];
                        $inactive = isset($b['is_active']) && (int)$b['is_active'] === 0;
                        $selected = ((int)($userRow['branch_id'] ?? 0) === $bid);
                        $label = htmlspecialchars((string)($b['name'] ?? '')) . ($inactive ? ' (inactive)' : '');
                      ?>
                      <option value="<?php echo $bid; ?>" <?php echo $selected ? 'selected' : ''; ?><?php echo ($inactive && !$selected) ? ' disabled' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-text">Colombo, Kilinochchi, or Mullaitivu only.</div>
              </div>
              <div class="col-md-6">
                <label class="usr-form-label" for="usrDepartment">Department</label>
                <div class="input-group usr-input-group">
                  <span class="input-group-text" aria-hidden="true"><i class="bi bi-diagram-3"></i></span>
                  <select id="usrDepartment" class="form-select usr-form-control" aria-describedby="usrDeptUiHint">
                    <option value="operations">Operations</option>
                    <option value="finance">Finance</option>
                    <option value="hr">Human Resources</option>
                    <option value="administration">Administration</option>
                    <option value="logistics">Logistics</option>
                    <option value="it">Information Technology</option>
                  </select>
                </div>
                <div id="usrDeptUiHint" class="usr-ui-only-hint">Visual reference — linked to role</div>
              </div>
              <div class="col-md-6">
                <label class="usr-form-label" for="usrDesignation">Designation</label>
                <div class="input-group usr-input-group">
                  <span class="input-group-text" aria-hidden="true"><i class="bi bi-award"></i></span>
                  <select id="usrDesignation" class="form-select usr-form-control" aria-describedby="usrDesigUiHint">
                    <option value="manager">Manager</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="officer">Officer</option>
                    <option value="executive">Executive</option>
                    <option value="administrator">Administrator</option>
                    <option value="clerk">Clerk</option>
                  </select>
                </div>
                <div id="usrDesigUiHint" class="usr-ui-only-hint">Visual reference — linked to role</div>
              </div>
              <div class="col-md-6">
                <label class="usr-form-label d-flex justify-content-between align-items-center" for="userRoleSelect">
                  <span>User Role <span class="text-danger">*</span></span>
                  <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse"
                    data-bs-target="#quickAddRole" aria-expanded="false" aria-controls="quickAddRole">
                    <i class="bi bi-person-gear" aria-hidden="true"></i> Quick Add
                  </button>
                </label>
                <div class="input-group usr-input-group">
                  <span class="input-group-text" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                  <select name="role" class="form-select usr-form-control" id="userRoleSelect" required aria-describedby="usrRoleHint">
                    <?php
                      $currentRole = (string)($userRow['role'] ?? 'staff');
                      $renderedKeys = [];
                      foreach ($rolesCatalog as $k => $label): $renderedKeys[$k] = true; ?>
                        <option value="<?php echo htmlspecialchars($k); ?>" <?php echo $currentRole===$k?'selected':''; ?>><?php echo htmlspecialchars($label); ?></option>
                      <?php endforeach;
                      if (!empty($rolesDynamic) && is_array($rolesDynamic)) {
                        foreach ($rolesDynamic as $r) {
                          $rk = trim((string)($r['role'] ?? ''));
                          if ($rk === '' || isset($renderedKeys[$rk])) { continue; }
                          $renderedKeys[$rk] = true;
                          $label = ucwords(str_replace('_',' ', $rk));
                    ?>
                          <option value="<?php echo htmlspecialchars($rk); ?>" <?php echo $currentRole===$rk?'selected':''; ?>><?php echo htmlspecialchars($label); ?></option>
                    <?php
                        }
                      }
                      if ($currentRole !== '' && !isset($renderedKeys[$currentRole])) {
                        $label = ucwords(str_replace('_',' ', $currentRole)); ?>
                        <option value="<?php echo htmlspecialchars($currentRole); ?>" selected><?php echo htmlspecialchars($label); ?></option>
                      <?php } ?>
                  </select>
                </div>
                <div id="usrRoleHint" class="usr-field-hint" role="status"></div>
                <div class="collapse mt-2" id="quickAddRole">
                  <div class="usr-quick-role p-3">
                    <div class="row g-2 align-items-end">
                      <div class="col-sm-8">
                        <label class="form-label small mb-1" for="ur_name">New role name</label>
                        <input type="text" id="ur_name" class="form-control form-control-sm" placeholder="e.g., Supervisor or hub_manager">
                      </div>
                      <div class="col-sm-4">
                        <button type="button" id="ur_submit" class="btn btn-sm btn-primary w-100">
                          <i class="bi bi-plus-lg" aria-hidden="true"></i> Add &amp; Select
                        </button>
                      </div>
                    </div>
                    <div class="form-text mb-0">Tip: Use simple words or snake_case for the role key.</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Card 4: Access Permissions (UI preview from role) -->
        <section class="usr-form-card" aria-labelledby="usrCardPermTitle">
          <div class="usr-form-card-header">
            <div class="usr-form-card-header-icon" aria-hidden="true"><i class="bi bi-grid-3x3-gap"></i></div>
            <div>
              <h2 class="usr-form-card-title" id="usrCardPermTitle">Access Permissions</h2>
              <p class="small text-muted mb-0">Preview based on selected role — permissions are managed by role assignment.</p>
            </div>
          </div>
          <div class="usr-form-card-body">
            <div class="usr-perm-grid" role="group" aria-label="Access permissions preview">
              <?php
                $permDefs = [
                  ['dashboard', 'Dashboard', 'Overview & KPIs', 'bi-speedometer2'],
                  ['accounting', 'Accounting', 'Ledgers & vouchers', 'bi-calculator'],
                  ['sales', 'Sales', 'Parcels & billing', 'bi-cart3'],
                  ['hr', 'HR', 'Employees & payroll', 'bi-people'],
                  ['inventory', 'Inventory', 'Fleet & routes', 'bi-truck'],
                  ['reports', 'Reports', 'Analytics & exports', 'bi-bar-chart-line'],
                  ['administration', 'Administration', 'Users & branches', 'bi-shield-check'],
                  ['settings', 'Settings', 'System configuration', 'bi-gear'],
                ];
                foreach ($permDefs as [$key, $title, $desc, $icon]):
              ?>
              <div class="usr-perm-card" data-perm-key="<?php echo htmlspecialchars($key); ?>" tabindex="0">
                <div class="usr-perm-card-head">
                  <div class="d-flex align-items-center gap-2">
                    <div class="usr-perm-icon" aria-hidden="true"><i class="bi <?php echo $icon; ?>"></i></div>
                    <div>
                      <p class="usr-perm-title"><?php echo htmlspecialchars($title); ?></p>
                    </div>
                  </div>
                  <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" tabindex="-1"
                      aria-label="<?php echo htmlspecialchars($title); ?> permission" disabled>
                  </div>
                </div>
                <p class="usr-perm-desc"><?php echo htmlspecialchars($desc); ?></p>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </section>

        <!-- Card 5: Status -->
        <section class="usr-form-card" aria-labelledby="usrCardStatusTitle">
          <div class="usr-form-card-header">
            <div class="usr-form-card-header-icon" aria-hidden="true"><i class="bi bi-toggle-on"></i></div>
            <h2 class="usr-form-card-title" id="usrCardStatusTitle">Status</h2>
          </div>
          <div class="usr-form-card-body">
            <div class="usr-status-panel">
              <div>
                <p class="mb-1 fw-semibold">Account status</p>
                <p class="small text-muted mb-0">Inactive users cannot sign in to the system.</p>
              </div>
              <div class="usr-status-toggle-wrap">
                <span id="usrStatusBadge" class="badge usr-status-badge <?php echo ((int)($userRow['active'] ?? 1) === 1) ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                  <?php echo ((int)($userRow['active'] ?? 1) === 1) ? 'Active' : 'Inactive'; ?>
                </span>
                <div class="form-check form-switch usr-status-switch">
                  <input class="form-check-input" type="checkbox" name="active" id="activeChk" value="1"
                    <?php echo ((int)($userRow['active'] ?? 1) === 1) ? 'checked' : ''; ?>
                    <?php echo $isSelf ? 'disabled' : ''; ?>
                    aria-label="User active status">
                  <label class="form-check-label fw-semibold" for="activeChk">
                    <?php echo ((int)($userRow['active'] ?? 1) === 1) ? 'Active' : 'Inactive'; ?>
                  </label>
                </div>
              </div>
            </div>
            <?php if ($isSelf): ?>
              <input type="hidden" name="active" value="1">
              <div class="form-text mt-2">Your account stays active while you are logged in.</div>
            <?php endif; ?>
          </div>
        </section>

        <!-- Mobile / bottom actions -->
        <div class="usr-form-actions d-lg-none">
          <button type="submit" class="btn btn-primary usr-form-submit-btn">
            <span class="usr-btn-text"><i class="bi bi-person-check me-1" aria-hidden="true"></i> Save User</span>
            <span class="usr-btn-spinner spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          </button>
          <button type="button" class="btn btn-outline-secondary" id="usrFormReset">
            <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> Reset
          </button>
          <a href="<?php echo Helpers::baseUrl('index.php?page=users'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-lg me-1" aria-hidden="true"></i> Cancel
          </a>
        </div>
      </form>
    </div>

    <!-- Right sidebar summary -->
    <div class="col-lg-4">
      <aside class="usr-summary-card" aria-label="User summary preview">
        <div class="usr-summary-header">
          <i class="bi bi-card-list me-2" aria-hidden="true"></i> Summary
        </div>
        <div class="usr-summary-body">
          <div id="usrSumAvatar" class="usr-summary-avatar" aria-hidden="true">U</div>
          <div class="usr-summary-row">
            <span class="usr-summary-label">Username</span>
            <span id="usrSumUsername" class="usr-summary-value">—</span>
          </div>
          <div class="usr-summary-row">
            <span class="usr-summary-label">Role</span>
            <span id="usrSumRole" class="usr-summary-value">—</span>
          </div>
          <div class="usr-summary-row">
            <span class="usr-summary-label">Branch</span>
            <span id="usrSumBranch" class="usr-summary-value">—</span>
          </div>
          <div class="usr-summary-row">
            <span class="usr-summary-label">Status</span>
            <span id="usrSumStatus" class="usr-summary-value">—</span>
          </div>
          <div class="usr-summary-row">
            <span class="usr-summary-label">User ID</span>
            <span id="usrSumUserId" class="usr-summary-value"><?php echo $isNew ? 'Auto-assigned on save' : 'USR-' . str_pad((string)(int)$userRow['id'], 5, '0', STR_PAD_LEFT); ?></span>
          </div>
        </div>
        <div class="p-3 pt-0 d-none d-lg-block">
          <div class="usr-form-actions flex-column">
            <button type="submit" form="userFormMain" class="btn btn-primary w-100 usr-form-submit-btn">
              <span class="usr-btn-text"><i class="bi bi-person-check me-1" aria-hidden="true"></i> Save User</span>
              <span class="usr-btn-spinner spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            </button>
            <button type="button" class="btn btn-outline-secondary w-100" id="usrFormResetSidebar">
              <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> Reset
            </button>
            <a href="<?php echo Helpers::baseUrl('index.php?page=users'); ?>" class="btn btn-outline-secondary w-100">
              <i class="bi bi-x-lg me-1" aria-hidden="true"></i> Cancel
            </a>
          </div>
        </div>
      </aside>
    </div>
  </div>
</div>

<script>
  window.TMS_USER_FORM = <?php echo json_encode([
    'isNew' => $isNew,
    'userId' => (int)($userRow['id'] ?? 0),
    'currentUsername' => (string)($userRow['username'] ?? ''),
    'existingUsernames' => array_values($existingUsernames),
  ], JSON_UNESCAPED_UNICODE); ?>;
</script>
