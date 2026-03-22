<?php
declare(strict_types=1);
/** @var array $employees */
/** @var array $branchesAll */
/** @var array $rolesForFilter */

if (!function_exists('emp_index_display_name')) {
    function emp_index_display_name(array $e): string
    {
        $n = trim((string)($e['name'] ?? ''));
        if ($n !== '') {
            return $n;
        }
        $fn = trim((string)($e['first_name'] ?? ''));
        $ln = trim((string)($e['last_name'] ?? ''));
        $x = trim($fn . ' ' . $ln);

        return $x !== '' ? $x : '—';
    }
}

if (!function_exists('emp_index_vehicle_label')) {
    function emp_index_vehicle_label(array $e): string
    {
        $v = trim((string)($e['vehicle_no_join'] ?? ''));
        if ($v !== '') {
            return $v;
        }
        $id = (string)($e['vehicle_id_join'] ?? $e['vehicle_id'] ?? '');

        return $id !== '' ? $id : '—';
    }
}

if (!function_exists('emp_index_status_badge')) {
    function emp_index_status_badge(string $status): string
    {
        if ($status === 'active') {
            return '<span class="badge badge-soft badge-soft-success">Active</span>';
        }
        if ($status === 'suspended') {
            return '<span class="badge badge-soft badge-soft-warning">Suspended</span>';
        }

        return '<span class="badge badge-soft badge-soft-secondary">Inactive</span>';
    }
}

$filterActive = 0;
foreach (['emp_code', 'name', 'phone', 'first_name', 'last_name', 'email', 'address', 'position', 'role', 'license_number', 'license_from', 'license_to', 'vehicle_like', 'join_from', 'join_to', 'status', 'q'] as $fk) {
    if (trim((string)(${$fk} ?? '')) !== '') {
        $filterActive++;
    }
}
if ((int)($branch_id ?? 0) > 0) {
    $filterActive++;
}

$csrf = Helpers::csrfToken();
$listJsonUrl = Helpers::baseUrl('index.php?page=employees&action=list_json');
$deleteUrl = Helpers::baseUrl('index.php?page=employees&action=delete');
$editBase = Helpers::baseUrl('index.php?page=employees&action=edit&id=');
$newUrl = Helpers::baseUrl('index.php?page=employees&action=new');
$clearUrl = Helpers::baseUrl('index.php?page=employees');
$payrollUrl = Helpers::baseUrl('index.php?page=employees&action=payroll');
$rolesForFilter = $rolesForFilter ?? [];
$q = $q ?? '';
$stCur = (string)($status ?? '');
?>
<div
  class="hr-page emp-page"
  id="empEmployeesRoot"
  data-list-json="<?php echo htmlspecialchars($listJsonUrl, ENT_QUOTES, 'UTF-8'); ?>"
  data-csrf="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>"
  data-delete-url="<?php echo htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8'); ?>"
  data-edit-base="<?php echo htmlspecialchars($editBase, ENT_QUOTES, 'UTF-8'); ?>"
>
  <div class="card border shadow-sm mb-1 filters-card">
    <div class="card-header bg-light d-flex flex-wrap align-items-center justify-content-between gap-1 py-1 px-2">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <button
          class="btn btn-link btn-sm text-decoration-none text-dark p-0 fw-semibold small"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#empFiltersBody"
          aria-expanded="true"
          aria-controls="empFiltersBody"
          title="Show / hide filters"
        >
          <i class="bi bi-chevron-down emp-filter-chevron" aria-hidden="true"></i><span class="ms-1">Filters</span>
          <span class="badge <?php echo $filterActive > 0 ? 'bg-primary' : 'bg-secondary'; ?> ms-1 align-middle" id="empFilterBadge"><?php echo (int)$filterActive; ?></span>
        </button>
        <div class="btn-group btn-group-sm filters-presets flex-wrap" role="group" aria-label="Quick filters">
          <span class="input-group-text bg-light border-0 text-muted py-0 px-1 small d-none d-sm-inline">Presets</span>
          <a href="<?php echo htmlspecialchars(Helpers::baseUrl('index.php?page=employees&status=active'), ENT_QUOTES, 'UTF-8'); ?>" class="btn <?php echo $stCur === 'active' ? 'btn-primary' : 'btn-outline-primary'; ?>"><i class="bi bi-check-circle me-1" aria-hidden="true"></i><span class="d-none d-md-inline">Active</span><span class="d-md-none">Act</span></a>
          <a href="<?php echo htmlspecialchars(Helpers::baseUrl('index.php?page=employees&status=inactive'), ENT_QUOTES, 'UTF-8'); ?>" class="btn <?php echo $stCur === 'inactive' ? 'btn-primary' : 'btn-outline-primary'; ?>"><i class="bi bi-pause-circle me-1" aria-hidden="true"></i><span class="d-none d-md-inline">Inactive</span><span class="d-md-none">Off</span></a>
          <a href="<?php echo htmlspecialchars($clearUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary" title="All employees"><i class="bi bi-people me-1" aria-hidden="true"></i><span class="d-none d-md-inline">All</span></a>
          <a href="<?php echo htmlspecialchars($clearUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary" title="Reset filters"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></a>
        </div>
      </div>
      <div class="btn-group btn-group-sm filters-tools" role="group" aria-label="Actions">
        <a href="<?php echo htmlspecialchars($newUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i><span class="d-none d-lg-inline">New Employee</span><span class="d-lg-none">New</span></a>
        <a href="<?php echo htmlspecialchars($payrollUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary" title="Salary report"><i class="bi bi-clipboard-data me-1" aria-hidden="true"></i><span class="d-none d-xl-inline">Salary report</span></a>
      </div>
    </div>
    <div id="empFiltersBody" class="card-body collapse show py-1 px-2 border-top border-light">
      <form id="empFilterForm" method="get" action="<?php echo htmlspecialchars(Helpers::baseUrl('index.php'), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="page" value="employees">

        <div class="row g-2 mb-1">
          <div class="col-12">
            <label class="form-label" for="empLiveSearch">Quick search</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-search" aria-hidden="true"></i></span>
              <input
                type="search"
                name="q"
                id="empLiveSearch"
                class="form-control form-control-sm"
                placeholder="Name, code, phone, email…"
                value="<?php echo htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8'); ?>"
                autocomplete="off"
              >
            </div>
          </div>
        </div>

          <div class="small text-uppercase text-muted fw-semibold mb-1">Basic</div>
          <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-2 align-items-end mb-2">
            <div class="col">
              <label class="form-label" for="filter_emp_code">Employee code</label>
              <input id="filter_emp_code" type="text" name="emp_code" class="form-control form-control-sm" placeholder="Code" value="<?php echo htmlspecialchars((string)($emp_code ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col">
              <label class="form-label" for="filter_emp_name">Name</label>
              <input id="filter_emp_name" type="text" name="name" class="form-control form-control-sm" placeholder="Search name" value="<?php echo htmlspecialchars((string)($name ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col">
              <label class="form-label" for="filter_emp_phone">Phone</label>
              <input id="filter_emp_phone" type="text" name="phone" class="form-control form-control-sm" placeholder="Phone" value="<?php echo htmlspecialchars((string)($phone ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <div class="small text-uppercase text-muted fw-semibold mt-2 mb-1">Advanced</div>
          <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-2 align-items-end mb-1">
            <div class="col">
              <label class="form-label" for="filter_emp_branch">Branch</label>
              <select id="filter_emp_branch" name="branch_id" class="form-select form-select-sm">
                <option value="0" <?php echo ((int)($branch_id ?? 0) === 0) ? 'selected' : ''; ?>>All branches</option>
                <?php foreach (($branchesAll ?? []) as $b): ?>
                  <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($branch_id ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)($b['name'] ?? '')); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col">
              <label class="form-label" for="filter_emp_role">Role</label>
              <input id="filter_emp_role" type="text" name="role" class="form-control form-control-sm" list="empRoleOptions" placeholder="Role" value="<?php echo htmlspecialchars((string)($role ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              <datalist id="empRoleOptions">
                <?php foreach ($rolesForFilter as $rv): ?>
                  <option value="<?php echo htmlspecialchars((string)$rv, ENT_QUOTES, 'UTF-8'); ?>"></option>
                <?php endforeach; ?>
              </datalist>
            </div>
            <div class="col">
              <label class="form-label" for="filter_emp_status">Status</label>
              <select id="filter_emp_status" name="status" class="form-select form-select-sm">
                <?php $st = (string)($status ?? ''); ?>
                <option value="" <?php echo $st === '' ? 'selected' : ''; ?>>Any</option>
                <option value="active" <?php echo $st === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $st === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                <option value="suspended" <?php echo $st === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
              </select>
            </div>
            <div class="col">
              <label class="form-label" for="filter_join_from">Join from</label>
              <input id="filter_join_from" type="date" name="join_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($join_from ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col">
              <label class="form-label" for="filter_join_to">Join to</label>
              <input id="filter_join_to" type="date" name="join_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($join_to ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col">
              <label class="form-label" for="filter_license_from">License expiry from</label>
              <input id="filter_license_from" type="date" name="license_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($license_from ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col">
              <label class="form-label" for="filter_license_to">License expiry to</label>
              <input id="filter_license_to" type="date" name="license_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($license_to ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <button class="btn btn-sm btn-outline-secondary mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#empMoreFilters" aria-expanded="false" aria-controls="empMoreFilters">
            More criteria
          </button>
          <div id="empMoreFilters" class="collapse mt-2">
            <div class="row g-2">
              <div class="col-6 col-md-4">
                <label class="form-label small mb-0 text-muted">First name</label>
                <input type="text" name="first_name" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($first_name ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </div>
              <div class="col-6 col-md-4">
                <label class="form-label small mb-0 text-muted">Last name</label>
                <input type="text" name="last_name" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($last_name ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label small mb-0 text-muted">Email</label>
                <input type="text" name="email" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($email ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label small mb-0 text-muted">Address</label>
                <input type="text" name="address" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($address ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </div>
              <div class="col-6 col-md-3">
                <label class="form-label small mb-0 text-muted">Position</label>
                <input type="text" name="position" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($position ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </div>
              <div class="col-6 col-md-3">
                <label class="form-label small mb-0 text-muted">License #</label>
                <input type="text" name="license_number" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)($license_number ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label small mb-0 text-muted">Vehicle</label>
                <input type="text" name="vehicle" class="form-control form-control-sm" placeholder="Plate / reg" value="<?php echo htmlspecialchars((string)($vehicle_like ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </div>
            </div>
          </div>

        <div class="filters-actions-row d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div class="form-check form-check-inline small mb-0">
            <input class="form-check-input" type="checkbox" id="empAutoApplyToggle" value="1">
            <label class="form-check-label text-muted" for="empAutoApplyToggle" title="Apply filters when any field changes">Auto-apply on change</label>
          </div>
          <div class="d-flex flex-wrap justify-content-end align-items-center gap-2 ms-auto">
            <a href="<?php echo htmlspecialchars($clearUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Clear all filters"><i class="bi bi-x-lg me-1" aria-hidden="true"></i>Clear all</a>
            <button type="submit" class="btn btn-primary btn-sm py-1 px-2" id="empFilterApply"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Apply</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div id="empEmptyState" class="emp-empty border-bottom <?php echo empty($employees) ? '' : 'd-none'; ?>">
    <i class="bi bi-inbox display-6 d-block mb-2 opacity-50" aria-hidden="true"></i>
    No employees match your criteria.
  </div>

  <div class="cards-wrap d-md-none mb-2">
    <div class="d-flex flex-column gap-2 px-1" id="empCards">
        <?php foreach ($employees as $e): ?>
          <?php
            $eid = (int)$e['id'];
            $payload = rawurlencode(json_encode($e, JSON_UNESCAPED_UNICODE));
            $dn = emp_index_display_name($e);
            $code = trim((string)($e['emp_code'] ?? ''));
          ?>
          <div class="card shadow-sm emp-card" data-emp-payload="<?php echo htmlspecialchars($payload, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="card-body py-3 px-3">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="min-w-0">
                  <div class="emp-card-title emp-truncate"><?php echo htmlspecialchars($dn, ENT_QUOTES, 'UTF-8'); ?></div>
                  <?php if ($code !== ''): ?>
                    <div class="meta"><?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?></div>
                  <?php endif; ?>
                  <div class="mt-2 small">
                    <div><i class="bi bi-telephone me-1" aria-hidden="true"></i><?php echo htmlspecialchars((string)($e['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div><i class="bi bi-person-badge me-1" aria-hidden="true"></i><?php echo htmlspecialchars((string)($e['role'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div><i class="bi bi-building me-1" aria-hidden="true"></i><?php echo htmlspecialchars((string)($e['branch_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                  </div>
                </div>
                <div class="flex-shrink-0 text-end">
                  <?php echo emp_index_status_badge((string)($e['status'] ?? '')); ?>
                  <div class="dropdown emp-actions-dd mt-2">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <li>
                        <button type="button" class="dropdown-item emp-act-view" data-id="<?php echo $eid; ?>"><i class="bi bi-eye me-2"></i>View</button>
                      </li>
                      <li>
                        <a class="dropdown-item" href="<?php echo htmlspecialchars($editBase . $eid, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-pencil-square me-2"></i>Edit</a>
                      </li>
                      <li><hr class="dropdown-divider"></li>
                      <li>
                        <form method="post" action="<?php echo htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('Delete this employee?');">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                          <input type="hidden" name="id" value="<?php echo $eid; ?>">
                          <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                        </form>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
    </div>
  </div>

  <div class="table-responsive table-wrap emp-table-scroll d-none d-md-block mb-1">
    <table class="table table-sm table-striped table-hover align-middle mb-0 emp-table" id="employeesTable">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Branch</th>
          <th>Status</th>
          <th class="text-end"><span class="small text-muted fw-semibold">Actions</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($employees as $e): ?>
          <?php
            $eid = (int)$e['id'];
            $payload = rawurlencode(json_encode($e, JSON_UNESCAPED_UNICODE));
            $dn = emp_index_display_name($e);
            $code = trim((string)($e['emp_code'] ?? ''));
          ?>
          <tr data-emp-payload="<?php echo htmlspecialchars($payload, ENT_QUOTES, 'UTF-8'); ?>">
            <td class="text-muted small"><?php echo $eid; ?></td>
            <td class="emp-name-cell">
              <div class="emp-name-main emp-truncate" title="<?php echo htmlspecialchars($dn, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($dn, ENT_QUOTES, 'UTF-8'); ?></div>
              <?php if ($code !== ''): ?>
                <div class="emp-code-sub emp-truncate"><?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?></div>
              <?php endif; ?>
            </td>
            <td class="emp-truncate"><?php echo htmlspecialchars((string)($e['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="emp-truncate"><?php echo htmlspecialchars((string)($e['role'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td class="emp-truncate"><?php echo htmlspecialchars((string)($e['branch_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo emp_index_status_badge((string)($e['status'] ?? '')); ?></td>
            <td class="text-end">
              <div class="dropdown emp-actions-dd d-inline-block">
                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <button type="button" class="dropdown-item emp-act-view" data-id="<?php echo $eid; ?>">
                      <i class="bi bi-eye me-2"></i>View
                    </button>
                  </li>
                  <li>
                    <a class="dropdown-item" href="<?php echo htmlspecialchars($editBase . $eid, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-pencil-square me-2"></i>Edit</a>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <form method="post" action="<?php echo htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8'); ?>" class="px-0 mb-0" onsubmit="return confirm('Delete this employee?');">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="id" value="<?php echo $eid; ?>">
                      <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                    </form>
                  </li>
                </ul>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="modal fade" id="empViewModal" tabindex="-1" aria-labelledby="empViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content rounded-3 border-0 shadow">
        <div class="modal-header border-0 pb-0">
          <h2 class="modal-title h5" id="empViewModalLabel">Employee details</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body pt-0" id="empViewModalBody"></div>
        <div class="modal-footer border-0 pt-0">
          <a class="btn btn-primary" id="empViewModalEdit" href="#">Edit</a>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="<?php echo Helpers::baseUrl('assets/js/employees-index.js'); ?>"></script>
