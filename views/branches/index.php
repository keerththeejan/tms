<?php
/** @var array $branches */
$branchListEmbed = $branchListEmbed ?? false;
$error = $error ?? '';
$branchListError = $branchListError ?? null;
$brErr = $branchListError !== null ? (string)$branchListError : (string)$error;
$newUrl = Helpers::baseUrl('index.php?page=branches&action=new');
$deleteAction = Helpers::baseUrl('index.php?page=branches&action=delete');
$csrf = Helpers::csrfToken();
?>
<style>
  .branches-page {
    --br-border: rgba(15, 23, 42, 0.1);
    --br-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    --br-radius: 14px;
    --br-sticky-top: 56px;
    --br-surface: rgba(255, 255, 255, 0.92);
  }
  .branches-page .branches-toolbar-sticky {
    position: sticky;
    top: var(--br-sticky-top);
    z-index: 1010;
    margin: -0.25rem -0.25rem 0.75rem;
    padding: 0.65rem 0.75rem 0.85rem;
    background: var(--br-surface);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid var(--br-border);
    border-radius: var(--br-radius);
    box-shadow: var(--br-shadow);
  }
  .branches-page .breadcrumb {
    font-size: 0.8125rem;
    margin-bottom: 0.35rem;
  }
  .branches-page .page-title-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
  }
  .branches-page .page-title-row h1 {
    font-size: clamp(1.15rem, 2.5vw, 1.35rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    margin: 0;
    line-height: 1.2;
  }
  .branches-page .btn-new-branch {
    border-radius: 12px;
    font-weight: 600;
    padding: 0.5rem 1.1rem;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.28);
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 55%, #1e40af 100%);
    border: none;
    transition: transform 0.12s ease, box-shadow 0.12s ease;
  }
  .branches-page .btn-new-branch:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.35);
    color: #fff;
  }
  .branches-page .btn-new-branch:focus-visible {
    outline: 3px solid rgba(37, 99, 235, 0.45);
    outline-offset: 2px;
  }
  .branches-page .controls-card {
    background: var(--br-surface);
    border: 1px solid var(--br-border);
    border-radius: var(--br-radius);
    box-shadow: var(--br-shadow);
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
  }
  .branches-page .controls-card .form-control,
  .branches-page .controls-card .form-select {
    font-size: 0.875rem;
  }
  .branches-page .table-panel {
    border: 1px solid var(--br-border);
    border-radius: var(--br-radius);
    background: #fff;
    box-shadow: var(--br-shadow);
    overflow: hidden;
  }
  .branches-page .branches-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .branches-page .branches-table {
    margin-bottom: 0;
    font-size: 0.8125rem;
    --bs-table-striped-bg: rgba(15, 23, 42, 0.035);
  }
  .branches-page .branches-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 700;
    color: #475569;
    background: rgba(248, 250, 252, 0.98);
    border-bottom: 1px solid var(--br-border);
    padding: 0.45rem 0.65rem !important;
    white-space: nowrap;
  }
  .branches-page .branches-table tbody td {
    padding: 0.4rem 0.65rem !important;
    vertical-align: middle;
    border-color: rgba(15, 23, 42, 0.06);
  }
  .branches-page .branches-table tbody tr {
    transition: background 0.12s ease;
  }
  .branches-page .branches-table tbody tr:hover {
    background: rgba(37, 99, 235, 0.045) !important;
  }
  .branches-page .cell-ellipsis {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
    max-width: 100%;
  }
  .branches-page .col-id { width: 64px; }
  .branches-page .col-code { width: 110px; }
  .branches-page .col-main { width: 100px; }
  .branches-page .col-actions { width: 96px; }
  .branches-page .badge-main {
    background: rgba(22, 163, 74, 0.12);
    color: #15803d;
    border: 1px solid rgba(22, 163, 74, 0.22);
    font-weight: 700;
    font-size: 0.7rem;
    padding: 0.2em 0.55em;
  }
  .branches-page .badge-branch {
    background: rgba(100, 116, 139, 0.1);
    color: #475569;
    border: 1px solid rgba(100, 116, 139, 0.2);
    font-weight: 600;
    font-size: 0.7rem;
    padding: 0.2em 0.55em;
  }
  .branches-page .btn-icon-action {
    width: 34px;
    height: 34px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    transition: transform 0.1s ease, background 0.12s ease;
  }
  .branches-page .btn-icon-action:hover {
    transform: scale(1.05);
  }
  .branches-page .btn-icon-action:focus-visible {
    outline: 2px solid #2563eb;
    outline-offset: 2px;
  }
  .branches-page .actions-gap {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.35rem;
  }
  /* Mobile cards */
  .branches-page .branch-cards {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
  }
  .branches-page .branch-card {
    border: 1px solid var(--br-border);
    border-radius: var(--br-radius);
    background: #fff;
    box-shadow: var(--br-shadow);
    overflow: hidden;
    transition: box-shadow 0.15s ease, transform 0.12s ease;
  }
  .branches-page .branch-card:hover {
    box-shadow: 0 6px 16px rgba(16, 24, 40, 0.08);
  }
  .branches-page .branch-card summary {
    list-style: none;
    cursor: pointer;
    padding: 0.65rem 0.85rem;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
  }
  .branches-page .branch-card summary::-webkit-details-marker { display: none; }
  .branches-page .branch-card summary::after {
    content: "";
    width: 0.5rem;
    height: 0.5rem;
    border-right: 2px solid #94a3b8;
    border-bottom: 2px solid #94a3b8;
    transform: rotate(45deg);
    margin-top: 0.35rem;
    flex-shrink: 0;
    transition: transform 0.2s ease;
  }
  .branches-page .branch-card[open] summary::after {
    transform: rotate(-135deg);
    margin-top: 0.5rem;
  }
  .branches-page .branch-card-title {
    font-weight: 700;
    font-size: 0.95rem;
    color: #0f172a;
    line-height: 1.25;
  }
  .branches-page .branch-card-meta {
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 0.2rem;
  }
  .branches-page .branch-card-body {
    padding: 0 0.85rem 0.75rem;
    border-top: 1px dashed rgba(15, 23, 42, 0.08);
  }
  .branches-page .branch-card-body dl {
    margin: 0;
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 0.35rem 0.75rem;
    font-size: 0.8125rem;
  }
  .branches-page .branch-card-body dt {
    color: #64748b;
    font-weight: 600;
  }
  .branches-page .branch-card-body dd {
    margin: 0;
    font-weight: 500;
  }
  .branches-page .branch-card-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.35rem;
    margin-top: 0.65rem;
    padding-top: 0.65rem;
    border-top: 1px solid rgba(15, 23, 42, 0.06);
  }
  .branches-page .empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: #64748b;
    font-size: 0.9rem;
  }
  @media (max-width: 767.98px) {
    .branches-page .branches-toolbar-sticky {
      margin-left: 0;
      margin-right: 0;
    }
  }
  @media (prefers-reduced-motion: reduce) {
    .branches-page .btn-new-branch,
    .branches-page .branch-card,
    .branches-page .branches-table tbody tr {
      transition: none;
    }
  }
</style>

<section class="branches-page" aria-labelledby="branches-heading"<?php echo !empty($branchListEmbed) ? ' id="settings-operational-branches"' : ''; ?>>
  <?php if (!empty($branchListEmbed)): ?>
  <header class="mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
      <div>
        <h1 id="branches-heading" class="h5 fw-bold mb-1">Operational branches</h1>
        <p class="small text-muted mb-0">Parcels, users, delivery notes, and reports use this master list. Updates apply everywhere immediately.</p>
      </div>
      <a href="<?php echo htmlspecialchars($newUrl); ?>" class="btn btn-sm btn-primary flex-shrink-0"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i><span class="d-none d-sm-inline">New branch</span><span class="d-sm-none">New</span></a>
    </div>
  </header>
  <?php else: ?>
  <header class="branches-toolbar-sticky">
    <nav aria-label="Breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo Helpers::baseUrl('index.php?page=settings'); ?>">Settings</a></li>
        <li class="breadcrumb-item active" aria-current="page">Branches</li>
      </ol>
    </nav>
    <div class="page-title-row">
      <div>
        <h1 id="branches-heading">Branches</h1>
        <p class="text-muted small mb-0 mt-1">Manage hub and branch offices for your logistics network.</p>
      </div>
      <a href="<?php echo htmlspecialchars($newUrl); ?>" class="btn btn-primary btn-new-branch">
        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i><span class="d-none d-sm-inline">New Branch</span><span class="d-sm-none">New</span>
      </a>
    </div>
  </header>
  <?php endif; ?>

  <?php if (!empty($brErr)): ?>
    <div class="alert alert-danger py-2 mb-3" role="alert"><?php echo htmlspecialchars($brErr); ?></div>
  <?php endif; ?>

  <div class="controls-card" id="settings-branch-crud">
    <div class="row g-2 g-md-3 align-items-end">
      <div class="col-12 col-md-4 col-lg-3">
        <label class="form-label small fw-semibold mb-1" for="branchSearch">Search</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white border-end-0" aria-hidden="true"><i class="bi bi-search text-muted"></i></span>
          <input type="search" id="branchSearch" class="form-control border-start-0" placeholder="Name or code…" autocomplete="off" aria-describedby="branchSearchHint">
        </div>
        <span id="branchSearchHint" class="visually-hidden">Filters the list below as you type.</span>
      </div>
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label small fw-semibold mb-1" for="branchFilterType">Branch type</label>
        <select id="branchFilterType" class="form-select form-select-sm" aria-label="Filter by branch type">
          <option value="all">All</option>
          <option value="main">Main hub</option>
          <option value="branch">Branch office</option>
        </select>
      </div>
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label small fw-semibold mb-1" for="branchSort">Sort by</label>
        <select id="branchSort" class="form-select form-select-sm" aria-label="Sort branches">
          <option value="main_first">Main first</option>
          <option value="name_asc">Name (A–Z)</option>
          <option value="name_desc">Name (Z–A)</option>
          <option value="code_asc">Code (A–Z)</option>
          <option value="id_asc">Database ID (low–high)</option>
        </select>
      </div>
      <div class="col-12 col-md-2 col-lg-auto ms-md-auto">
        <p class="small text-muted mb-0 text-md-end" id="branchCountLabel" aria-live="polite"></p>
      </div>
    </div>
  </div>

  <?php if (empty($branches)): ?>
    <div class="empty-state card border-0 shadow-sm">
      <div class="card-body py-5">
        <i class="bi bi-diagram-3 fs-1 text-muted d-block mb-2" aria-hidden="true"></i>
        <p class="mb-2 fw-semibold text-dark">No branches yet</p>
        <p class="small mb-3">Create your first branch to start routing parcels.</p>
        <a href="<?php echo htmlspecialchars($newUrl); ?>" class="btn btn-primary btn-new-branch btn-sm">Add branch</a>
      </div>
    </div>
  <?php else: ?>

  <!-- Desktop / tablet table -->
  <div class="d-none d-md-block table-panel mb-3">
    <div class="branches-table-wrap table-responsive">
      <table class="table table-sm table-striped table-hover align-middle branches-table mb-0" id="branchesTable" data-branches-table aria-describedby="branchCountLabel">
        <caption class="visually-hidden">Branches list with row number, name, code, type, and actions</caption>
        <thead>
          <tr>
            <th scope="col" class="col-id">#</th>
            <th scope="col">Name</th>
            <th scope="col" class="col-code d-none d-lg-table-cell">Code</th>
            <th scope="col" class="col-main">Type</th>
            <th scope="col" class="d-none d-xl-table-cell">Status</th>
            <th scope="col" class="text-end col-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($branches as $index => $b):
            $bid = (int)$b['id'];
            $rowNum = (int)$index + 1;
            $isMain = (int)($b['is_main'] ?? 0) === 1;
            $isActive = !isset($b['is_active']) || (int)($b['is_active'] ?? 1) === 1;
            $name = (string)($b['name'] ?? '');
            $code = (string)($b['code'] ?? '');
            $editUrl = Helpers::baseUrl('index.php?page=branches&action=edit&id=' . $bid);
          ?>
          <tr
            data-branch-id="<?php echo $bid; ?>"
            data-name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"
            data-code="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>"
            data-main="<?php echo $isMain ? '1' : '0'; ?>"
          >
            <td class="text-muted branch-row-num"><?php echo $rowNum; ?></td>
            <td>
              <span class="cell-ellipsis fw-medium" title="<?php echo htmlspecialchars($name); ?>"><?php echo htmlspecialchars($name); ?></span>
              <span class="d-lg-none small text-muted d-block text-truncate"><?php echo htmlspecialchars($code); ?></span>
            </td>
            <td class="d-none d-lg-table-cell"><span class="cell-ellipsis" title="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($code); ?></span></td>
            <td>
              <?php if ($isMain): ?>
                <span class="badge rounded-pill badge-main">Main</span>
              <?php else: ?>
                <span class="badge rounded-pill badge-branch">Branch</span>
              <?php endif; ?>
            </td>
            <td class="d-none d-xl-table-cell">
              <?php if ($isActive): ?>
                <span class="badge text-bg-success">Active</span>
              <?php else: ?>
                <span class="badge text-bg-secondary">Inactive</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <div class="actions-gap">
                <a class="btn btn-sm btn-outline-secondary btn-icon-action" href="<?php echo htmlspecialchars($editUrl); ?>" title="Edit branch" aria-label="Edit <?php echo htmlspecialchars($name); ?>">
                  <i class="bi bi-pencil-square" aria-hidden="true"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger btn-icon-action branch-delete-open" title="Delete branch"
                  aria-label="Delete <?php echo htmlspecialchars($name); ?>"
                  data-branch-id="<?php echo $bid; ?>"
                  data-branch-name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                  <i class="bi bi-trash" aria-hidden="true"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Mobile cards -->
  <div class="d-md-none branch-cards mb-4" id="branchCards" data-branch-cards aria-label="Branch list">
    <?php foreach ($branches as $index => $b):
      $bid = (int)$b['id'];
      $rowNum = (int)$index + 1;
      $isMain = (int)($b['is_main'] ?? 0) === 1;
      $name = (string)($b['name'] ?? '');
      $code = (string)($b['code'] ?? '');
      $editUrl = Helpers::baseUrl('index.php?page=branches&action=edit&id=' . $bid);
    ?>
    <details class="branch-card" data-branch-id="<?php echo $bid; ?>" data-name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" data-code="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>" data-main="<?php echo $isMain ? '1' : '0'; ?>">
      <summary>
        <div class="min-w-0">
          <div class="branch-card-title text-truncate"><?php echo htmlspecialchars($name); ?></div>
          <div class="branch-card-meta text-muted"><?php echo htmlspecialchars($code); ?> · #<span class="branch-row-num"><?php echo $rowNum; ?></span></div>
        </div>
        <div class="flex-shrink-0">
          <?php if ($isMain): ?>
            <span class="badge rounded-pill badge-main">Main</span>
          <?php else: ?>
            <span class="badge rounded-pill badge-branch">Branch</span>
          <?php endif; ?>
        </div>
      </summary>
      <div class="branch-card-body">
        <dl>
          <dt>Code</dt><dd><?php echo htmlspecialchars($code); ?></dd>
          <dt>#</dt><dd class="branch-row-num"><?php echo $rowNum; ?></dd>
          <dt>Record ID</dt><dd class="text-muted small"><?php echo $bid; ?></dd>
          <dt>Type</dt><dd><?php echo $isMain ? 'Main hub' : 'Branch office'; ?></dd>
        </dl>
        <div class="branch-card-actions">
          <a class="btn btn-sm btn-outline-secondary btn-icon-action" href="<?php echo htmlspecialchars($editUrl); ?>" title="Edit" aria-label="Edit <?php echo htmlspecialchars($name); ?>">
            <i class="bi bi-pencil-square" aria-hidden="true"></i>
          </a>
          <button type="button" class="btn btn-sm btn-outline-danger btn-icon-action branch-delete-open" title="Delete"
            aria-label="Delete <?php echo htmlspecialchars($name); ?>"
            data-branch-id="<?php echo $bid; ?>"
            data-branch-name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
            <i class="bi bi-trash" aria-hidden="true"></i>
          </button>
        </div>
      </div>
    </details>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>
</section>

<!-- Delete confirmation modal -->
<div class="modal fade" id="branchDeleteModal" tabindex="-1" aria-labelledby="branchDeleteModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: 14px;">
      <div class="modal-header border-0 pb-0">
        <h2 class="modal-title h5 fw-bold" id="branchDeleteModalTitle">Delete branch</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
      </div>
      <div class="modal-body pt-2">
        <p class="mb-0 text-muted small">This action cannot be undone. If this branch is linked to users, parcels, or expenses, deletion will be blocked.</p>
        <p class="mt-3 mb-0 fw-semibold text-dark" id="branchDeleteModalName"></p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
        <form method="post" action="<?php echo htmlspecialchars($deleteAction); ?>" id="branchDeleteForm" class="d-inline">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
          <input type="hidden" name="id" id="branchDeleteFormId" value="">
          <button type="submit" class="btn btn-danger rounded-pill px-3">Delete branch</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($branches)): ?>
<script>
(function () {
  var table = document.getElementById('branchesTable');
  var cardsRoot = document.getElementById('branchCards');
  var search = document.getElementById('branchSearch');
  var filterType = document.getElementById('branchFilterType');
  var sortSel = document.getElementById('branchSort');
  var countLabel = document.getElementById('branchCountLabel');
  var modalEl = document.getElementById('branchDeleteModal');
  var formId = document.getElementById('branchDeleteFormId');
  var modalName = document.getElementById('branchDeleteModalName');
  var deleteModal = modalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(modalEl) : null;

  function rowMatchesFilter(el) {
    var q = (search && search.value || '').trim().toLowerCase();
    var name = (el.getAttribute('data-name') || '').toLowerCase();
    var code = (el.getAttribute('data-code') || '').toLowerCase();
    var main = el.getAttribute('data-main') === '1';
    var type = filterType ? filterType.value : 'all';
    if (q && name.indexOf(q) === -1 && code.indexOf(q) === -1) return false;
    if (type === 'main' && !main) return false;
    if (type === 'branch' && main) return false;
    return true;
  }

  function sortElements(rows, mode) {
    var arr = rows.slice();
    arr.sort(function (a, b) {
      var na = (a.getAttribute('data-name') || '').toLowerCase();
      var nb = (b.getAttribute('data-name') || '').toLowerCase();
      var ca = (a.getAttribute('data-code') || '').toLowerCase();
      var cb = (b.getAttribute('data-code') || '').toLowerCase();
      var ida = parseInt(a.getAttribute('data-branch-id') || '0', 10);
      var idb = parseInt(b.getAttribute('data-branch-id') || '0', 10);
      var ma = a.getAttribute('data-main') === '1';
      var mb = b.getAttribute('data-main') === '1';
      switch (mode) {
        case 'name_asc': return na.localeCompare(nb);
        case 'name_desc': return nb.localeCompare(na);
        case 'code_asc': return ca.localeCompare(cb);
        case 'id_asc': return ida - idb;
        case 'main_first':
        default:
          if (ma !== mb) return ma ? -1 : 1;
          return na.localeCompare(nb);
      }
    });
    return arr;
  }

  function applyTable() {
    if (!table) return;
    var tbody = table.tBodies[0];
    if (!tbody) return;
    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
    var order = sortSel ? sortSel.value : 'main_first';
    rows.forEach(function (tr) {
      tr.hidden = !rowMatchesFilter(tr);
    });
    var visible = rows.filter(function (r) { return !r.hidden; });
    var hidden = rows.filter(function (r) { return r.hidden; });
    var sorted = sortElements(visible, order);
    var frag = document.createDocumentFragment();
    sorted.forEach(function (tr) { frag.appendChild(tr); });
    hidden.forEach(function (tr) { frag.appendChild(tr); });
    tbody.appendChild(frag);
    renumberTable();
    updateCount();
  }

  function renumberTable() {
    if (!table || !table.tBodies[0]) return;
    var n = 0;
    Array.prototype.forEach.call(table.tBodies[0].querySelectorAll('tr'), function (tr) {
      if (tr.hidden) return;
      n++;
      var cell = tr.querySelector('td.branch-row-num');
      if (cell) cell.textContent = String(n);
    });
  }

  function applyCards() {
    if (!cardsRoot) return;
    var details = Array.prototype.slice.call(cardsRoot.querySelectorAll('details.branch-card'));
    var order = sortSel ? sortSel.value : 'main_first';
    details.forEach(function (el) {
      var show = rowMatchesFilter(el);
      el.hidden = !show;
      if (!show) el.open = false;
    });
    var visible = details.filter(function (d) { return !d.hidden; });
    var hidden = details.filter(function (d) { return d.hidden; });
    var sorted = sortElements(visible, order);
    var frag = document.createDocumentFragment();
    sorted.forEach(function (d) { frag.appendChild(d); });
    hidden.forEach(function (d) { frag.appendChild(d); });
    cardsRoot.appendChild(frag);
    renumberCards();
    updateCount();
  }

  function renumberCards() {
    if (!cardsRoot) return;
    var n = 0;
    Array.prototype.forEach.call(cardsRoot.querySelectorAll('details.branch-card'), function (d) {
      if (d.hidden) return;
      n++;
      d.querySelectorAll('.branch-row-num').forEach(function (el) {
        el.textContent = String(n);
      });
    });
  }

  function updateCount() {
    if (!countLabel) return;
    var total = 0;
    var n = 0;
    if (table && table.tBodies[0]) {
      total = table.tBodies[0].rows.length;
      n = Array.prototype.slice.call(table.tBodies[0].rows).filter(function (r) { return !r.hidden; }).length;
    } else if (cardsRoot) {
      total = cardsRoot.querySelectorAll('details.branch-card').length;
      n = cardsRoot.querySelectorAll('details.branch-card:not([hidden])').length;
    }
    countLabel.textContent = n === total ? (total + ' branch' + (total !== 1 ? 'es' : '')) : (n + ' of ' + total + ' shown');
  }

  function refresh() {
    applyTable();
    applyCards();
  }

  if (search) search.addEventListener('input', refresh);
  if (filterType) filterType.addEventListener('change', refresh);
  if (sortSel) sortSel.addEventListener('change', refresh);

  document.querySelectorAll('.branch-delete-open').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-branch-id');
      var nm = btn.getAttribute('data-branch-name') || '';
      if (formId) formId.value = id;
      if (modalName) modalName.textContent = nm ? '"' + nm + '"' : 'This branch';
      if (deleteModal) deleteModal.show();
      else if (window.confirm('Delete this branch?')) {
        var f = document.getElementById('branchDeleteForm');
        if (f) f.submit();
      }
    });
  });

  refresh();
})();
</script>
<?php endif; ?>
