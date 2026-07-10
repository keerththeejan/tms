<?php
/** @var array $branchesAll */
/** @var bool $isAdmin */
/** @var int $editId */
/** @var bool $openNew */
$empCssPath = dirname(__DIR__, 2) . '/public/assets/css/employees-hrms.css';
$empJsPath = dirname(__DIR__, 2) . '/public/assets/js/employees-hrms.js';
$empCssVer = is_file($empCssPath) ? (string) filemtime($empCssPath) : '1';
$empJsVer = is_file($empJsPath) ? (string) filemtime($empJsPath) : '1';
$base = Helpers::baseUrl('');
$apiBase = $base . 'index.php?page=employees';
$payrollUrl = $base . 'index.php?page=employees&action=payroll';
$csrf = Helpers::csrfToken();
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/employees-hrms.css?v=' . rawurlencode($empCssVer)); ?>">

<div class="hrms-page container-fluid px-0 px-sm-1" id="hrmsApp"
     data-api-base="<?php echo htmlspecialchars($apiBase); ?>"
     data-csrf="<?php echo htmlspecialchars($csrf); ?>"
     data-payroll-url="<?php echo htmlspecialchars($payrollUrl); ?>"
     data-edit-id="<?php echo (int) ($editId ?? 0); ?>"
     data-open-new="<?php echo !empty($openNew) ? '1' : '0'; ?>">

  <header class="hrms-head d-flex flex-column flex-lg-row justify-content-between align-items-stretch align-items-lg-start gap-3 mb-3">
    <div>
      <h1 class="hrms-title"><i class="bi bi-people-fill"></i> Employees</h1>
      <p class="text-muted mb-0 small">Human Resource Management — profiles, payroll, and workforce analytics.</p>
    </div>
    <div class="d-flex flex-column flex-sm-row flex-wrap gap-2">
      <button type="button" class="btn btn-primary" id="hrmsBtnNew" data-bs-toggle="modal" data-bs-target="#employeeModal">
        <i class="bi bi-person-plus me-1"></i> New Employee
      </button>
      <a href="<?php echo htmlspecialchars($payrollUrl); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-clipboard-data me-1"></i> Salary Report
      </a>
      <button type="button" class="btn btn-outline-success" id="hrmsBtnExport"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel</button>
      <button type="button" class="btn btn-outline-danger" id="hrmsBtnPrint"><i class="bi bi-printer me-1"></i> Print</button>
    </div>
  </header>

  <div id="hrmsAlert" class="alert d-none"></div>

  <section class="card hrms-filters-card mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
      <button class="btn btn-link text-decoration-none text-dark p-0 fw-semibold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#hrmsFilters" aria-expanded="false">
        <i class="bi bi-funnel me-1"></i> Filters
      </button>
      <span class="badge bg-primary-subtle text-primary d-none" id="hrmsFilterBadge">Active</span>
    </div>
    <div id="hrmsFilters" class="collapse">
      <div class="card-body">
        <form id="hrmsFilterForm" class="row g-3 align-items-end">
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label" for="fEmpCode">Employee ID</label>
            <input type="text" class="form-control" id="fEmpCode" name="emp_code">
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label" for="fName">Employee Name</label>
            <input type="text" class="form-control" id="fName" name="name">
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label" for="fNic">NIC / Passport</label>
            <input type="text" class="form-control" id="fNic" name="nic_passport">
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label" for="fPhone">Phone</label>
            <input type="text" class="form-control" id="fPhone" name="phone">
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label" for="fEmail">Email</label>
            <input type="email" class="form-control" id="fEmail" name="email">
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label" for="fDept">Department</label>
            <select class="form-select" id="fDept" name="department_id"><option value="">All</option></select>
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label" for="fDesig">Designation</label>
            <select class="form-select" id="fDesig" name="designation_id"><option value="">All</option></select>
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label" for="fBranch">Branch</label>
            <select class="form-select" id="fBranch" name="branch_id">
              <option value="">All Branches</option>
              <?php foreach ($branchesAll as $b): ?>
              <option value="<?php echo (int) $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label" for="fEmpType">Employment Type</label>
            <select class="form-select" id="fEmpType" name="employment_type">
              <option value="">All</option>
              <option value="permanent">Permanent</option>
              <option value="contract">Contract</option>
              <option value="temporary">Temporary</option>
              <option value="intern">Intern</option>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label" for="fStatus">Status</label>
            <select class="form-select" id="fStatus" name="status">
              <option value="">Any</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label" for="fGender">Gender</label>
            <select class="form-select" id="fGender" name="gender">
              <option value="">Any</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label" for="fJoinFrom">Join From</label>
            <input type="date" class="form-control" id="fJoinFrom" name="join_from">
          </div>
          <div class="col-12 col-sm-6 col-lg-3">
            <label class="form-label" for="fJoinTo">Join To</label>
            <input type="date" class="form-control" id="fJoinTo" name="join_to">
          </div>
          <div class="col-12 col-lg-6">
            <label class="form-label" for="fSearch">Search</label>
            <input type="search" class="form-control" id="fSearch" name="q" placeholder="Quick search across name, ID, NIC, phone…">
          </div>
          <div class="col-12 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary flex-fill flex-sm-grow-0">Search</button>
            <button type="button" class="btn btn-outline-secondary flex-fill flex-sm-grow-0" id="hrmsBtnReset">Reset</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <section class="row g-2 g-md-3 mb-3" id="hrmsStats">
    <?php
    $statCards = [
      ['total', 'Total Employees', 'bi-people'],
      ['active', 'Active', 'bi-person-check'],
      ['inactive', 'Inactive', 'bi-person-dash'],
      ['new_this_month', 'New This Month', 'bi-calendar-plus'],
      ['permanent', 'Permanent', 'bi-briefcase'],
      ['contract', 'Contract', 'bi-file-earmark-text'],
      ['temporary', 'Temporary', 'bi-clock'],
      ['intern', 'Intern', 'bi-mortarboard'],
      ['male', 'Male', 'bi-gender-male'],
      ['female', 'Female', 'bi-gender-female'],
    ];
    foreach ($statCards as [$key, $label, $icon]):
    ?>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="hrms-stat-card">
        <i class="bi <?php echo $icon; ?> hrms-stat-icon"></i>
        <div>
          <div class="hrms-stat-label"><?php echo htmlspecialchars($label); ?></div>
          <div class="hrms-stat-value" data-stat="<?php echo $key; ?>">—</div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </section>

  <section class="card hrms-table-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
      <span class="fw-semibold">Employee Register</span>
      <select class="form-select form-select-sm w-auto" id="hrmsPageSize">
        <option value="10">10</option>
        <option value="25" selected>25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
    </div>
    <div class="card-body p-0">
      <div id="hrmsLoading" class="text-center py-5 d-none"><span class="spinner-border spinner-border-sm"></span> Loading…</div>
      <div id="hrmsEmpty" class="hrms-empty d-none"><i class="bi bi-inbox"></i><p>No employees found.</p></div>

      <div class="d-none d-lg-block table-responsive hrms-table-wrap">
        <table class="table table-hover table-sm align-middle mb-0" id="hrmsTable">
          <thead class="table-light sticky-top">
            <tr>
              <th>ID</th>
              <th>Photo</th>
              <th>Full Name</th>
              <th>NIC</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Department</th>
              <th>Designation</th>
              <th>Branch</th>
              <th class="text-end">Salary</th>
              <th>Type</th>
              <th>Joined</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody id="hrmsTableBody"></tbody>
        </table>
      </div>
      <div class="d-lg-none" id="hrmsCards"></div>
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 p-3 border-top">
        <div class="small text-muted" id="hrmsPageInfo"></div>
        <ul class="pagination pagination-sm mb-0" id="hrmsPagination"></ul>
      </div>
    </div>
  </section>
</div>

<?php require __DIR__ . '/partials/employee_modal.php'; ?>
<?php require __DIR__ . '/partials/employee_profile_modal.php'; ?>

<script src="<?php echo Helpers::baseUrl('assets/js/employees-hrms.js?v=' . rawurlencode($empJsVer)); ?>"></script>
