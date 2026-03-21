<?php /** @var array $routes */ /** @var string|null $success */ /** @var string|null $error */ ?>
<style>
  .dr-page {
    --dr-border: rgba(17, 24, 39, 0.08);
    --dr-shadow: 0 1px 3px rgba(16, 24, 40, 0.06), 0 8px 24px rgba(15, 23, 42, 0.06);
    --dr-radius: 16px;
    --dr-accent: #2563eb;
    --dr-accent-soft: rgba(37, 99, 235, 0.12);
  }
  .dr-page .dr-hero {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.09) 0%, rgba(99, 102, 241, 0.06) 50%, rgba(14, 165, 233, 0.05) 100%);
    border: 1px solid var(--dr-border);
    border-radius: var(--dr-radius);
    box-shadow: var(--dr-shadow);
    position: relative;
    overflow: hidden;
  }
  .dr-page .dr-hero::before {
    content: "";
    position: absolute;
    top: -40%;
    right: -15%;
    width: 55%;
    height: 140%;
    background: radial-gradient(ellipse, rgba(37, 99, 235, 0.14), transparent 65%);
    pointer-events: none;
  }
  .dr-page .dr-hero-inner { position: relative; z-index: 1; }
  .dr-page .dr-head { margin-bottom: 1.25rem; }
  .dr-page .dr-title {
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  .dr-page .dr-title i { color: var(--dr-accent); }
  .dr-page .dr-subtitle { font-size: 0.9rem; color: #64748b; margin: 0.35rem 0 0; max-width: 52rem; line-height: 1.45; }
  .dr-page .dr-badge-count {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    background: var(--dr-accent-soft);
    color: #1d4ed8;
    border: 1px solid rgba(37, 99, 235, 0.2);
  }
  .dr-page .dr-card {
    background: #fff;
    border: 1px solid var(--dr-border);
    border-radius: var(--dr-radius);
    box-shadow: var(--dr-shadow);
    overflow: hidden;
  }
  .dr-page .dr-card-h {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    padding: 0.65rem 1rem;
    background: linear-gradient(180deg, #f8fafc, #fff);
    border-bottom: 1px solid var(--dr-border);
  }
  .dr-page .dr-add-form .form-control {
    border-radius: 12px;
    padding: 0.6rem 0.85rem;
    border-color: rgba(15, 23, 42, 0.12);
  }
  .dr-page .dr-add-form .form-control:focus {
    border-color: var(--dr-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
  }
  .dr-page .dr-add-form .btn-primary {
    border-radius: 12px;
    padding: 0.55rem 1.15rem;
    font-weight: 600;
  }
  .dr-page .dr-table-wrap { border-radius: 0 0 var(--dr-radius) var(--dr-radius); }
  .dr-page .dr-table thead th {
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 700;
    color: #64748b;
    border-bottom-width: 1px;
    padding: 0.65rem 1rem;
    background: #f8fafc;
  }
  .dr-page .dr-table tbody td { padding: 0.65rem 1rem; vertical-align: middle; border-color: rgba(15, 23, 42, 0.06); }
  .dr-page .dr-table tbody tr:hover { background: rgba(37, 99, 235, 0.03); }
  .dr-page .dr-route-name { font-weight: 600; color: #0f172a; }
  .dr-page .dr-id { font-variant-numeric: tabular-nums; color: #94a3b8; font-size: 0.85rem; }
  .dr-page .dr-empty {
    text-align: center;
    padding: 2.5rem 1.5rem;
    color: #64748b;
  }
  .dr-page .dr-empty .bi { font-size: 2.75rem; opacity: 0.35; color: var(--dr-accent); display: block; margin-bottom: 0.75rem; }
  .dr-page .dr-tips {
    font-size: 0.78rem;
    color: #64748b;
    padding: 0.65rem 0.85rem;
    background: rgba(248, 250, 252, 0.9);
    border-top: 1px solid var(--dr-border);
    border-radius: 0 0 var(--dr-radius) var(--dr-radius);
  }
  .dr-page .dr-tips i { color: #0ea5e9; }
  /* Mobile: stacked route cards */
  @media (max-width: 767.98px) {
    .dr-page .dr-table thead { display: none; }
    .dr-page .dr-table tbody tr {
      display: block;
      border-bottom: 1px solid rgba(15, 23, 42, 0.08);
      padding: 0.65rem 0;
    }
    .dr-page .dr-table tbody tr:last-child { border-bottom: none; }
    .dr-page .dr-table tbody td {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
      border: none;
      padding: 0.35rem 1rem;
    }
    .dr-page .dr-table tbody td::before {
      content: attr(data-label);
      font-size: 0.62rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #94a3b8;
      flex-shrink: 0;
    }
    .dr-page .dr-table tbody td.dr-td-actions::before { content: none; }
    .dr-page .dr-table tbody td.dr-td-actions { justify-content: flex-end; padding-top: 0.5rem; }
  }
</style>

<div class="dr-page">
  <div class="dr-head">
    <h1 class="dr-title">
      <i class="bi bi-signpost-2" aria-hidden="true"></i>
      Delivery routes
      <span class="dr-badge-count"><?php echo count($routes ?? []); ?> saved</span>
    </h1>
    <p class="dr-subtitle">
      Maintain a shared list of route names (areas, towns, corridors). They appear in <strong>parcel</strong> and <strong>customer</strong> screens so teams pick consistent labels.
    </p>
  </div>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 py-2 px-3 mb-3" role="alert">
      <i class="bi bi-check-circle-fill flex-shrink-0"></i>
      <span><?php echo htmlspecialchars($success); ?></span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3" role="alert">
      <?php echo htmlspecialchars($error); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row g-3 g-lg-4 align-items-start">
    <div class="col-12 col-lg-4 col-xl-3">
      <div class="dr-hero p-3 p-md-4 h-100">
        <div class="dr-hero-inner">
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary" style="width:40px;height:40px;">
              <i class="bi bi-plus-lg fs-5" aria-hidden="true"></i>
            </span>
            <div>
              <div class="fw-bold text-dark small text-uppercase letter-spacing-wide" style="letter-spacing:0.06em;">Quick add</div>
              <div class="text-muted" style="font-size:0.8rem;">New route name</div>
            </div>
          </div>
          <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_routes&action=save'); ?>" class="dr-add-form">
            <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
            <label class="form-label small fw-semibold text-secondary mb-1" for="drRouteName">Route name</label>
            <input type="text" name="name" id="drRouteName" class="form-control mb-3" placeholder="e.g. Kilinochchi — Jaffna" required autocomplete="off" maxlength="255">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-lightning-charge-fill me-1"></i> Add route
            </button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-8 col-xl-9">
      <div class="dr-card">
        <div class="dr-card-h d-flex justify-content-between align-items-center flex-wrap gap-2">
          <span><i class="bi bi-list-ul me-1 text-primary"></i> Common routes</span>
          <?php if (!empty($routes)): ?>
            <span class="badge rounded-pill text-bg-light border text-secondary fw-normal" style="font-size:0.75rem;">A–Z sorted</span>
          <?php endif; ?>
        </div>
        <?php if (empty($routes)): ?>
          <div class="dr-empty">
            <i class="bi bi-map" aria-hidden="true"></i>
            <div class="fw-semibold text-dark mb-1">No routes yet</div>
            <div class="small">Use the panel on the left to add your first delivery route name.</div>
          </div>
          <div class="dr-tips mb-0">
            <i class="bi bi-lightbulb me-1"></i>
            <strong>Tip:</strong> Use the same spelling your team uses on invoices and delivery notes.
          </div>
        <?php else: ?>
          <div class="table-responsive dr-table-wrap">
            <table class="table table-hover align-middle mb-0 dr-table">
              <thead>
                <tr>
                  <th style="width:88px;">ID</th>
                  <th>Route name</th>
                  <th class="text-end" style="width:120px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($routes as $r): ?>
                  <tr>
                    <td class="dr-id" data-label="ID">#<?php echo (int)$r['id']; ?></td>
                    <td class="dr-route-name" data-label="Route"><?php echo htmlspecialchars($r['name'] ?? ''); ?></td>
                    <td class="text-end dr-td-actions" data-label="">
                      <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=delivery_routes&action=delete'); ?>" class="d-inline" onsubmit="return confirm('Remove this route from the shared list? It will no longer appear in dropdowns.');">
                        <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                        <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Remove route">
                          <i class="bi bi-trash"></i><span class="d-none d-md-inline ms-1">Remove</span>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="dr-tips mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Removing a route only affects this list — existing parcels keep their stored text.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
