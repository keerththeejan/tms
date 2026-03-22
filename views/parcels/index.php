<?php /** @var array $parcels */ 
  $filter_type = $filter_type ?? '';
  $today = date('Y-m-d');
  $parcelItemsById = $parcelItemsById ?? [];
  $parcelsFiltersActive = ($filter_type !== '')
    || (int)($customer_filter_id ?? 0) > 0
    || (int)($from_branch_filter_id ?? 0) > 0
    || (int)($to_branch_filter_id ?? 0) > 0
    || (int)($supplier_filter_id ?? 0) > 0
    || trim((string)($q ?? '')) !== ''
    || trim((string)($tracking_filter ?? '')) !== ''
    || trim((string)($invoice_no_filter ?? '')) !== ''
    || trim((string)($vehicle_no ?? '')) !== ''
    || trim((string)($delivery_location_filter ?? '')) !== ''
    || trim((string)($delivery_route_filter ?? '')) !== ''
    || trim((string)($status ?? '')) !== ''
    || trim((string)($route_date ?? '')) !== ''
    || trim((string)($_GET['ids'] ?? '')) !== ''
    || isset($_GET['from'])
    || isset($_GET['to']);
?>
<style>
  /* Parcels list: one-viewport fit — tight filters + scrollable table (Bootstrap 5) */
  .parcels-page { --p-gap: 6px; max-width: none !important; width: 100%; margin: 0; padding-bottom: .35rem; }
  .parcels-page .table-wrap { border: 1px solid rgba(17,24,39,.10); border-radius: 12px; background:#fff; }
  /* Vertical scroll only — table fits viewport width (table-layout: fixed, no horizontal scroll) */
  .parcels-page .parcels-table-scroll {
    max-height: min(640px, calc(100vh - 240px));
    overflow-x: hidden;
    overflow-y: auto;
    width: 100%;
    max-width: 100%;
    -webkit-overflow-scrolling: touch;
  }
  @media (max-width: 991.98px) {
    .parcels-page .parcels-table-scroll { max-height: none; }
  }
  /* Actions dropdown above sticky header & scroll layers */
  .parcels-page .parcels-actions-dd .dropdown-menu {
    z-index: 1060;
    min-width: 12rem;
  }
  .parcels-page .parcels-table { table-layout: fixed; font-size: 12.5px; }
  .parcels-page .parcels-table th,
  .parcels-page .parcels-table td { padding: 4px 6px !important; vertical-align: middle; }
  .parcels-page .parcels-table th { padding: 5px 6px !important; vertical-align: middle; }
  .parcels-page .parcels-table thead th { font-size: 11.5px; letter-spacing: .02em; }
  .parcels-page .parcels-table tbody tr:hover { background: rgba(2,6,23,.035); }
  /* Sticky table header: desktop only */
  @media (min-width: 768px) {
    .parcels-page .parcels-table thead th { position: sticky; top: 0; z-index: 10; background: #f8f9fa; }
  }
  /* Skeleton loader */
  .parcels-page .skeleton { background: linear-gradient(90deg, rgba(200,200,200,0.3) 25%, rgba(220,220,220,0.5) 50%, rgba(200,200,200,0.3) 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 4px; }
  @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
  .parcels-page .skeleton-row td { padding: 10px 8px !important; }
  .parcels-page .skeleton-cell { height: 16px; width: 80%; }
  .parcels-page .parcels-table .cell-ellipsis { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; max-width: 100%; }
  .parcels-page .parcels-table .cell-tight { white-space: nowrap; }
  /* Fixed layout: allow ellipsis in flexible columns */
  .parcels-page .parcels-table td.col-min-0,
  .parcels-page .parcels-table th.col-min-0 { min-width: 0; }
  .parcels-page .parcels-table .col-actions {
    width: 4.75rem;
    min-width: 4.75rem;
    max-width: 5.25rem;
    text-align: right;
    vertical-align: middle;
    white-space: nowrap;
  }
  .parcels-page .parcels-table .col-actions .parcel-actions-cell {
    justify-content: flex-end;
  }
  .parcels-page .parcels-table .col-actions .btn-icon {
    width: 28px;
    height: 28px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
  }
  .parcels-page .parcels-table .col-num { width: 2.5rem; min-width: 2.25rem; max-width: 2.75rem; }
  .parcels-page .parcels-table .col-parcel { width: 7%; min-width: 0; }
  .parcels-page .parcels-table .col-date { width: 6.5rem; min-width: 0; max-width: 7rem; }
  .parcels-page .parcels-table .col-customer { width: 14%; min-width: 0; }
  .parcels-page .parcels-table .col-supplier { width: 9%; min-width: 0; }
  .parcels-page .parcels-table .col-branch { width: 6%; min-width: 0; }
  .parcels-page .parcels-table .col-veh { width: 5%; min-width: 0; }
  .parcels-page .parcels-table .col-route { width: 9%; min-width: 0; }
  .parcels-page .parcels-table .col-ln { width: 2.25rem; min-width: 0; }
  .parcels-page .parcels-table .col-status { width: 7.5rem; min-width: 0; max-width: 9rem; }
  .parcels-page .parcels-table .col-status .badge { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; vertical-align: middle; }
  .parcels-page .parcels-table .col-email { width: 8%; min-width: 0; max-width: 9rem; }
  .parcels-page .parcels-table .col-weight { width: 3.25rem; min-width: 0; }
  .parcels-page .parcels-table .col-price { width: 4.25rem; min-width: 0; }
  .parcels-page .badge.badge-soft { font-weight: 700; border: 1px solid rgba(17,24,39,.10); }
  .parcels-page .badge-soft-success { background: rgba(25,135,84,.12); color: #146c43; }
  .parcels-page .badge-soft-warning { background: rgba(255,193,7,.16); color: #8a6d00; }
  .parcels-page .badge-soft-info { background: rgba(13, 110, 253, 0.14); color: #084298; }
  .parcels-page .badge-soft-secondary { background: rgba(108,117,125,.14); color: #495057; }
  .parcels-page .badge-soft-danger { background: rgba(220,53,69,.14); color: #b02a37; }
  .parcels-page .btn-icon { width: 30px; height: 30px; padding: 0; display:inline-flex; align-items:center; justify-content:center; }
  .parcels-page .cards-wrap .card { border: 1px solid rgba(17,24,39,.10); border-radius: 12px; }
  .parcels-page .cards-wrap .card .card-body { padding: .7rem .85rem; }
  .parcels-page .cards-wrap .meta { color: #64748b; font-size: .85rem; }
  .parcels-page .cards-wrap .kv { display:flex; justify-content:space-between; gap:.75rem; }
  .parcels-page .cards-wrap .kv .k { color:#64748b; }
  .parcels-page .cards-wrap .kv .v { font-weight:600; text-align:right; }
  .parcels-page .cards-wrap .title { font-weight:800; }
  .parcels-page .cards-wrap .actions .btn { border-radius: 10px; }
  @media (max-width: 992px) {
    /* Tablet: keep width tighter */
    .parcels-page .parcels-table th,
    .parcels-page .parcels-table td { padding: 6px 8px !important; }
  }
  @media (max-width: 576px) {
    /* Mobile: still allow scroll, but tighter */
    .parcels-page .parcels-table { font-size: 12.5px; }
  }

  /* Compact page spacing */
  .parcels-page .page-title-row { margin-bottom: .5rem !important; }
  .parcels-page .filters-card { margin-bottom: .5rem !important; }
  .parcels-page .filters-card .card-header:not(.parcels-toolbar-header) { padding: .35rem .65rem !important; }
  .parcels-page .filters-card .card-body { padding: .5rem .65rem !important; }
  .parcels-page .filters-card .form-label { margin-bottom: .1rem !important; font-size: .72rem; font-weight: 600; color: #64748b; }
  .parcels-page .filters-card { border-radius: 12px; }
  .parcels-page .filters-card .form-control,
  .parcels-page .filters-card .form-select { padding-top: .15rem; padding-bottom: .15rem; font-size: .8rem; min-height: calc(1.45em + .35rem + 2px); }
  .parcels-page .filters-card .btn { border-radius: 8px; }
  /* Filters toggle: ▼ collapsed / ▲ expanded (smooth) */
  .parcels-page .filters-card .parcel-filter-chevron {
    transition: transform 0.25s ease;
    display: inline-block;
  }
  .parcels-page .filters-card [data-bs-toggle="collapse"].collapsed .parcel-filter-chevron,
  .parcels-page .filters-card [data-bs-toggle="collapse"][aria-expanded="false"] .parcel-filter-chevron {
    transform: rotate(0deg);
  }
  .parcels-page .filters-card [data-bs-toggle="collapse"]:not(.collapsed) .parcel-filter-chevron,
  .parcels-page .filters-card [data-bs-toggle="collapse"][aria-expanded="true"] .parcel-filter-chevron {
    transform: rotate(180deg);
  }
  /* Toolbar: flex, wrap, gap — scroll container prevents page overflow */
  .parcels-page .filters-card .card-header.parcels-toolbar-header {
    padding: .35rem .5rem !important;
    max-width: 100%;
    min-width: 0;
  }
  .parcels-page .parcels-toolbar-scroll {
    max-width: 100%;
    min-width: 0;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
    touch-action: pan-x pan-y;
  }
  .parcels-page .parcels-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    max-width: 100%;
    min-width: 0;
  }
  .parcels-page .parcels-toolbar-main {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
    flex: 1 1 auto;
    min-width: 0;
  }
  .parcels-page .parcels-toolbar-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
    min-width: 0;
  }
  .parcels-page .parcels-toolbar-label {
    font-size: .7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #64748b;
    white-space: nowrap;
    flex-shrink: 0;
    padding: 0 .15rem;
  }
  .parcels-page .parcels-toolbar-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
    justify-content: flex-end;
    flex: 0 1 auto;
    min-width: 0;
    margin-left: auto;
  }
  .parcels-page .parcels-toolbar-plan {
    gap: 6px !important;
  }
  .parcels-page .filters-toolbar .btn {
    white-space: nowrap;
    max-width: 100%;
    border-radius: 50rem;
    padding: .25rem .55rem;
    font-size: .8125rem;
    line-height: 1.25;
  }
  .parcels-page .filters-toolbar .btn i { flex-shrink: 0; }
  .parcels-page .filters-toolbar .filters-toggle-btn:not(.collapsed) {
    color: #fff !important;
    background-color: var(--bs-primary) !important;
    border-color: var(--bs-primary) !important;
  }
  .parcels-page .filters-toolbar .filters-toggle-btn.collapsed {
    color: inherit;
  }
  .parcels-page .filters-toolbar .filters-toggle-btn.collapsed .parcels-filter-active {
    color: var(--bs-primary) !important;
  }
  .parcels-page .filters-toolbar .filters-toggle-btn:not(.collapsed) .parcels-filter-active {
    color: rgba(255, 255, 255, 0.95) !important;
  }
  .parcels-page .filters-actions-row { border-top: 1px solid rgba(0,0,0,.06); margin-top: .25rem; padding-top: .35rem !important; }
  .parcels-page .parcels-toolbar { margin-bottom: .35rem !important; }
  /* Pull list slightly closer to topbar (less dead space under global header) */
  main.content-wrapper > .container-fluid .parcels-page { margin-top: -0.35rem; }
  .parcels-page .pf-qe { cursor: pointer; }
  .parcels-page .pf-qe.pf-qe-busy { opacity: 0.65; pointer-events: none; }
  .parcels-page .col-chk { width: 36px; text-align: center; }
  .parcels-page .col-exp { width: 28px; vertical-align: middle !important; }
  .parcels-page .parcel-expand-btn { line-height: 1; color: #64748b !important; }
  .parcels-page .parcel-expand-btn .parcel-exp-ico { transition: transform 0.2s ease; display: inline-block; }
  .parcels-page .parcel-expand-btn[aria-expanded="true"] .parcel-exp-ico { transform: rotate(90deg); }
  .parcels-page .parcel-expand-btn .parcel-exp-ico-m { transition: transform 0.2s ease; display: inline-block; }
  .parcels-page .parcel-expand-btn[aria-expanded="true"] .parcel-exp-ico-m { transform: rotate(180deg); }
  .parcels-page .parcel-items-panel { border-radius: 0 0 8px 8px; }
  .parcels-page .parcel-items-nested { font-size: 11.5px; margin-bottom: 0; background: #fff; border: 1px solid rgba(17,24,39,.08); border-radius: 8px; }
  .parcels-page .parcel-items-nested thead th { background: #f1f5f9; font-size: 10.5px; text-transform: uppercase; letter-spacing: .03em; color: #64748b; }
  .parcels-page .parcel-items-nested td, .parcels-page .parcel-items-nested th { padding: 4px 8px !important; }
  .parcels-page .parcel-items-nested .pf-add-tag { font-size: 10px; font-weight: 600; }
  .parcels-page .parcel-items-nested tfoot td { font-weight: 700; background: #f8fafc; }
  .parcels-page .parcel-items-loading { min-height: 2rem; }
  .parcels-page .parcel-items-panel .table-responsive {
    overflow-x: hidden;
  }
  @media (max-width: 767.98px) {
    .parcels-page .parcel-items-nested { font-size: 11px; }
  }
  /* Print-only document (screen hidden) */
  .parcels-print-document { display: none !important; }
  .ppd-head { margin: 0 0 8px; padding: 0; }
  .ppd-title { font-size: 16px; font-weight: 800; margin: 0 0 4px; letter-spacing: 0.02em; }
  .ppd-sub { font-size: 10px; margin: 0; color: #333; line-height: 1.3; }
  .ppd-block {
    margin: 0 0 10px;
    padding: 0 0 8px;
    border-bottom: 1px solid #bbb;
    page-break-inside: avoid;
    break-inside: avoid;
  }
  .ppd-block:last-child { border-bottom: none; }
  .ppd-sum-table, .ppd-items-table {
    width: 100%;
    max-width: 100%;
    border-collapse: collapse;
    font-size: 10.5px;
    line-height: 1.2;
    table-layout: fixed;
  }
  .ppd-sum-table th, .ppd-sum-table td,
  .ppd-items-table th, .ppd-items-table td {
    border: 1px solid #000;
    padding: 2px 4px;
    vertical-align: top;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: anywhere;
  }
  .ppd-sum-table th, .ppd-items-table th {
    background: #eee;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 9px;
  }
  .ppd-items-caption { font-size: 10px; font-weight: 700; margin: 4px 0 2px; }
  .ppd-num { text-align: right; }
  .ppd-empty { font-size: 10px; color: #555; margin: 8px 0; }
  .ppd-empty-tight { margin: 2px 0 0 !important; }
  .ppd-foot-sub td { font-weight: 700; }
  .ppd-foot-total td { font-weight: 800; font-size: 9px; }
  /* Column share (table-layout: fixed); percentages only — fits A4 */
  .ppd-s-id { width: 5%; }
  .ppd-s-serial { width: 8%; }
  .ppd-s-date { width: 9%; }
  .ppd-s-customer { width: 15%; }
  .ppd-s-branches { width: 13%; }
  .ppd-s-route { width: 19%; }
  .ppd-s-weight { width: 8%; }
  .ppd-s-total { width: 8%; }
  .ppd-s-status { width: 15%; }
  .ppd-i-no { width: 6%; }
  .ppd-i-desc { width: 40%; }
  .ppd-i-qty { width: 10%; }
  .ppd-i-rate { width: 12%; }
  .ppd-i-amt { width: 12%; }
  .ppd-i-add { width: 20%; }
  @media print {
    @page { size: A4; margin: 8mm; }
    /* Shell: no flex scrollbars, full usable width */
    html {
      width: 100% !important;
      max-width: 100% !important;
      overflow-x: hidden !important;
      overflow-y: visible !important;
      height: auto !important;
    }
    body {
      width: 100% !important;
      max-width: 100% !important;
      overflow-x: hidden !important;
      overflow-y: visible !important;
      min-width: 0 !important;
      height: auto !important;
      max-height: none !important;
      background: #fff !important;
      background-image: none !important;
      margin: 0 !important;
      padding: 0 !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .app-shell.d-flex,
    .app-shell {
      display: block !important;
      width: 100% !important;
      max-width: 100% !important;
      min-width: 0 !important;
      min-height: 0 !important;
      overflow: visible !important;
      flex: none !important;
    }
    #sidebar,
    .sidebar-overlay,
    .sidebar-toggle-floating,
    .topbar,
    .skip-link,
    .no-print,
    .no-print-parcels,
    .modal,
    .modal-backdrop,
    .offcanvas,
    .toast,
    .toast-container {
      display: none !important;
    }
    main.content-wrapper,
    #main-content {
      display: block !important;
      flex: none !important;
      flex-grow: 0 !important;
      margin: 0 !important;
      padding: 0 !important;
      margin-left: 0 !important;
      width: 100% !important;
      max-width: 100% !important;
      min-width: 0 !important;
      min-height: 0 !important;
      overflow: visible !important;
    }
    .container-fluid,
    .container,
    .container-sm,
    .container-md,
    .container-lg,
    .container-xl,
    .container-xxl {
      width: 100% !important;
      max-width: 100% !important;
      min-width: 0 !important;
      margin: 0 !important;
      padding-left: 0 !important;
      padding-right: 0 !important;
      overflow: visible !important;
    }
    .row {
      margin-left: 0 !important;
      margin-right: 0 !important;
      --bs-gutter-x: 0 !important;
      --bs-gutter-y: 0 !important;
    }
    .table-responsive,
    .table-wrap,
    .parcels-table-scroll,
    .parcels-page .table-responsive,
    .parcels-page .table-wrap,
    .parcels-page .parcels-table-scroll {
      display: block !important;
      width: 100% !important;
      max-width: 100% !important;
      min-width: 0 !important;
      overflow: visible !important;
      overflow-x: visible !important;
      overflow-y: visible !important;
      max-height: none !important;
      -webkit-overflow-scrolling: auto !important;
    }
    .parcels-page .card,
    .parcels-page .card-body,
    .parcels-page .collapse,
    .wrapper {
      overflow: visible !important;
      max-height: none !important;
      min-width: 0 !important;
    }
    .parcels-page .parcels-table,
    .parcels-page .parcel-items-nested {
      min-width: 0 !important;
      width: 100% !important;
      max-width: 100% !important;
    }
    * {
      box-shadow: none !important;
      text-shadow: none !important;
    }
    body * {
      background-image: none !important;
    }
    .parcels-print-document th {
      background: #fff !important;
      color: #000 !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .parcels-page {
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      max-width: 100% !important;
      min-width: 0 !important;
      overflow: visible !important;
    }
    .parcels-print-document {
      display: block !important;
      width: 100% !important;
      max-width: 100% !important;
      min-width: 0 !important;
      margin: 0 !important;
      padding: 0 !important;
      overflow-x: hidden !important;
      overflow-y: visible !important;
      font-family: "Courier New", Courier, "Liberation Mono", monospace, "Noto Sans Tamil", "Latha", Tahoma, sans-serif !important;
      color: #000 !important;
      -webkit-font-smoothing: auto !important;
    }
    .ppd-print-hide {
      display: none !important;
    }
    .ppd-head {
      margin: 0 0 5px !important;
      padding: 0 !important;
    }
    .ppd-title {
      font-size: 16px !important;
      font-weight: 800 !important;
      margin: 0 0 2px !important;
    }
    .ppd-sub {
      font-size: 11px !important;
      margin: 0 !important;
      line-height: 1.35 !important;
      color: #000 !important;
    }
    .ppd-block {
      margin: 0 0 5px !important;
      padding: 0 0 5px !important;
      page-break-inside: avoid !important;
      break-inside: avoid !important;
    }
    .ppd-block:last-child {
      margin-bottom: 0 !important;
      padding-bottom: 0 !important;
      border-bottom: none !important;
    }
    .ppd-items-caption {
      margin: 3px 0 1px !important;
      font-size: 11px !important;
      font-weight: 700 !important;
    }
    .ppd-sum-table,
    .ppd-items-table {
      width: 100% !important;
      max-width: 100% !important;
      min-width: 0 !important;
      table-layout: fixed !important;
      font-size: 12px !important;
    }
    .ppd-sum-table th,
    .ppd-sum-table td,
    .ppd-items-table th,
    .ppd-items-table td {
      min-width: 0 !important;
      padding: 3px 4px !important;
      font-size: 12px !important;
      border-color: #000 !important;
      color: #000 !important;
      word-break: break-word !important;
      overflow-wrap: anywhere !important;
      white-space: normal !important;
    }
    .ppd-sum-table th,
    .ppd-items-table th {
      font-size: 11px !important;
      font-weight: 800 !important;
      background: #fff !important;
      border-bottom: 2px solid #000 !important;
    }
    .ppd-empty,
    .ppd-empty-tight {
      margin: 2px 0 !important;
      font-size: 11px !important;
    }
    .ppd-foot-sub td,
    .ppd-foot-total td {
      font-size: 11px !important;
      color: #000 !important;
    }
  }
</style>

<div class="parcels-page">

<div class="no-print">

<div class="card border shadow-sm mb-1 filters-card">
  <div class="card-header bg-light parcels-toolbar-header">
    <div class="parcels-toolbar-scroll">
      <div class="parcels-toolbar filters-toolbar" role="toolbar" aria-label="Parcel filters and presets">
        <div class="parcels-toolbar-main">
          <button class="btn btn-sm rounded-pill btn-outline-secondary filters-toggle-btn collapsed px-2 py-1 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#parcelsFiltersBody" aria-expanded="false" aria-controls="parcelsFiltersBody" title="Show / hide filters">
            <i class="bi bi-chevron-down parcel-filter-chevron" aria-hidden="true"></i><span class="ms-1">Filters<span id="parcelsFilterActiveLabel" class="<?php echo $parcelsFiltersActive ? 'fw-semibold parcels-filter-active' : 'd-none'; ?>"> (Active)</span></span>
          </button>
          <div class="parcels-toolbar-presets" role="group" aria-label="Presets">
            <span class="parcels-toolbar-label d-none d-sm-inline">Presets</span>
            <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&filter_type=route_planning&from='.$today.'&to='.$today); ?>" class="btn btn-sm rounded-pill <?php echo $filter_type==='route_planning'?'btn-primary':'btn-outline-primary'; ?>"><i class="bi bi-geo-alt me-1"></i><span class="d-none d-md-inline">Route planning</span><span class="d-md-none">Route</span></a>
            <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&filter_type=vehicle_routes'); ?>" class="btn btn-sm rounded-pill <?php echo $filter_type==='vehicle_routes'?'btn-primary':'btn-outline-primary'; ?>"><i class="bi bi-truck-front me-1"></i><span class="d-none d-md-inline">Vehicles</span><span class="d-md-none">Veh</span></a>
            <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&filter_type=customers'); ?>" class="btn btn-sm rounded-pill <?php echo $filter_type==='customers'?'btn-primary':'btn-outline-primary'; ?>"><i class="bi bi-people me-1"></i><span class="d-none d-md-inline">Customers</span><span class="d-md-none">Cust</span></a>
            <a href="<?php echo Helpers::baseUrl('index.php?page=parcels'); ?>" class="btn btn-sm rounded-pill btn-outline-secondary" title="Reset all filters" aria-label="Refresh list and clear filters"><i class="bi bi-arrow-counterclockwise me-sm-1"></i><span class="d-none d-sm-inline">Refresh</span></a>
          </div>
        </div>
        <div class="parcels-toolbar-actions">
          <div class="parcels-toolbar-plan d-none d-md-flex flex-wrap align-items-center" role="group" aria-label="Planning tools">
            <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm rounded-pill btn-outline-secondary"><i class="bi bi-map me-1"></i><span class="d-none d-xl-inline">Plan routes</span><span class="d-xl-none">Plan</span></a>
            <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm rounded-pill btn-outline-secondary"><i class="bi bi-truck-front me-1"></i><span class="d-none d-xl-inline">Vehicle routes</span><span class="d-xl-none">Veh routes</span></a>
          </div>
          <div class="dropdown d-md-none">
            <button class="btn btn-sm rounded-pill btn-outline-secondary px-2 py-1" type="button" id="parcelsToolbarMore" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions"><i class="bi bi-three-dots-vertical" aria-hidden="true"></i></button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="parcelsToolbarMore">
              <li><a class="dropdown-item" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route'); ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-map me-2 text-muted"></i>Plan routes</a></li>
              <li><a class="dropdown-item" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_vehicles'); ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-truck-front me-2 text-muted"></i>Vehicle routes</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="parcelsFiltersBody" class="card-body collapse py-1 px-2 border-top border-light">
    <?php if ($filter_type === 'route_planning'): ?>
    <div class="alert alert-info py-1 px-2 mb-1 small"><i class="bi bi-info-circle me-1"></i> <strong>Route planning</strong> preset: pending / in transit, today.</div>
    <?php elseif ($filter_type === 'vehicle_routes'): ?>
    <div class="alert alert-info py-1 px-2 mb-1 small"><i class="bi bi-info-circle me-1"></i> Showing parcels with a <strong>vehicle</strong> assigned.</div>
    <?php elseif ($filter_type === 'customers'): ?>
    <div class="alert alert-info py-1 px-2 mb-1 small"><i class="bi bi-info-circle me-1"></i> Pick a <strong>customer</strong> below, then Apply.</div>
    <?php endif; ?>
    <form id="parcelsFilterForm" method="get" action="<?php echo Helpers::baseUrl('index.php'); ?>" class="parcel-filters-form">
      <input type="hidden" name="page" value="parcels">
      <?php if ($filter_type !== ''): ?><input type="hidden" name="filter_type" value="<?php echo htmlspecialchars($filter_type); ?>"><?php endif; ?>

      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-2 align-items-end">
        <div class="col">
          <label class="form-label" for="filter_customer_id">Customer</label>
          <select class="form-select form-select-sm" id="filter_customer_id" name="customer_id">
            <option value="0">All Customers</option>
            <?php foreach (($customersList ?? []) as $c): ?>
              <?php $nm = (string)($c['name'] ?? ''); $ph = trim((string)($c['phone'] ?? '')); $isPH = preg_match('/^NA\d{10}-\d{3}$/', $ph) === 1; $label = $nm . (!$isPH && $ph !== '' ? ' (' . $ph . ')' : ''); ?>
              <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)($customer_filter_id ?? 0) === (int)$c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col">
          <label class="form-label" for="filter_from_branch_id">From branch</label>
          <select class="form-select form-select-sm" id="filter_from_branch_id" name="from_branch_id">
            <option value="0">All From Branches</option>
            <?php foreach (($branchesFilterList ?? []) as $b): ?>
              <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($from_branch_filter_id ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col">
          <label class="form-label" for="filter_to_branch_id">To branch</label>
          <select class="form-select form-select-sm" id="filter_to_branch_id" name="to_branch_id">
            <option value="0">All To Branches</option>
            <?php foreach (($branchesFilterList ?? []) as $b): ?>
              <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($to_branch_filter_id ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col">
          <label class="form-label" for="filter_from">Date from</label>
          <input type="date" class="form-control form-control-sm" id="filter_from" name="from" value="<?php echo htmlspecialchars($from ?? ''); ?>" title="From date">
        </div>
        <div class="col">
          <label class="form-label" for="filter_to">Date to</label>
          <input type="date" class="form-control form-control-sm" id="filter_to" name="to" value="<?php echo htmlspecialchars($to ?? ''); ?>" title="To date">
        </div>
      </div>

      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-2 align-items-end">
        <div class="col">
          <label class="form-label" for="filter_q">Search</label>
          <input type="text" class="form-control form-control-sm" id="filter_q" name="q" placeholder="Name, phone, tracking" value="<?php echo htmlspecialchars($q ?? ''); ?>" title="Customer name, phone, or tracking">
        </div>
        <div class="col">
          <label class="form-label" for="filter_tracking_number">Tracking / serial</label>
          <input type="text" class="form-control form-control-sm" id="filter_tracking_number" name="tracking_number" placeholder="Serial" value="<?php echo htmlspecialchars($tracking_filter ?? ''); ?>">
        </div>
        <div class="col">
          <label class="form-label" for="filter_invoice_no">Invoice no.</label>
          <input type="text" class="form-control form-control-sm" id="filter_invoice_no" name="invoice_no" placeholder="Invoice" value="<?php echo htmlspecialchars($invoice_no_filter ?? ''); ?>">
        </div>
        <div class="col">
          <label class="form-label" for="filter_supplier_id">Supplier</label>
          <select class="form-select form-select-sm" id="filter_supplier_id" name="supplier_id">
            <option value="0">All Suppliers</option>
            <?php foreach (($suppliersFilterList ?? []) as $s): ?>
              <option value="<?php echo (int)$s['id']; ?>" <?php echo ((int)($supplier_filter_id ?? 0) === (int)$s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name'] ?? ''); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col">
          <label class="form-label" for="filter_vehicle_no">Vehicle no.</label>
          <input type="text" class="form-control form-control-sm" id="filter_vehicle_no" name="vehicle_no" placeholder="Vehicle" value="<?php echo htmlspecialchars($vehicle_no ?? ''); ?>">
        </div>
      </div>

      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-2 align-items-end">
        <div class="col">
          <label class="form-label" for="filter_delivery_location">Delivery location</label>
          <input type="text" class="form-control form-control-sm" id="filter_delivery_location" name="delivery_location" placeholder="Location" value="<?php echo htmlspecialchars($delivery_location_filter ?? ''); ?>">
        </div>
        <div class="col">
          <label class="form-label" for="filter_delivery_route">Delivery route</label>
          <?php if (!empty($deliveryRoutesFilterList) && is_array($deliveryRoutesFilterList)): ?>
            <select class="form-select form-select-sm" id="filter_delivery_route" name="delivery_route">
              <option value="">All Routes</option>
              <?php foreach ($deliveryRoutesFilterList as $r): ?>
                <?php $rName = (string)($r['name'] ?? ''); if (trim($rName) === '') continue; ?>
                <option value="<?php echo htmlspecialchars($rName); ?>" <?php echo ((string)($delivery_route_filter ?? '') === (string)$rName) ? 'selected' : ''; ?>><?php echo htmlspecialchars($rName); ?></option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input type="text" class="form-control form-control-sm" id="filter_delivery_route" name="delivery_route" placeholder="Route" value="<?php echo htmlspecialchars($delivery_route_filter ?? ''); ?>">
          <?php endif; ?>
        </div>
        <div class="col">
          <label class="form-label" for="filter_status">Status</label>
          <select class="form-select form-select-sm" id="filter_status" name="status">
            <option value="">All Status</option>
            <?php foreach (Helpers::parcelStatusMap() as $stVal => $stLabel): ?>
            <option value="<?php echo htmlspecialchars($stVal); ?>" <?php echo (($status ?? '') === $stVal) ? 'selected' : ''; ?>><?php echo htmlspecialchars($stLabel); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col">
          <label class="form-label" for="filter_route_date">Route date</label>
          <input type="date" class="form-control form-control-sm" id="filter_route_date" name="route_date" value="<?php echo htmlspecialchars($route_date ?? ''); ?>" title="Parcels with delivery route on this date">
        </div>
      </div>
      <div class="filters-actions-row d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="form-check form-check-inline small mb-0">
          <input class="form-check-input" type="checkbox" id="parcelsAutoApplyToggle" value="1">
          <label class="form-check-label text-muted" for="parcelsAutoApplyToggle" title="Submit immediately when any filter changes">Auto-apply on change</label>
        </div>
        <div class="d-flex flex-wrap justify-content-end align-items-center gap-2 ms-auto">
          <a href="<?php echo Helpers::baseUrl('index.php?page=parcels'); ?>" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Clear all filters"><i class="bi bi-x-lg me-1"></i>Clear all</a>
          <button type="submit" class="btn btn-primary btn-sm py-1 px-2"><i class="bi bi-funnel me-1"></i>Apply</button>
          <?php if (isset($_SESSION['parcels_filter_from']) || isset($_SESSION['parcels_filter_to'])): ?>
            <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&clear_dates=1'); ?>" class="btn btn-outline-danger btn-sm py-1 px-2" title="Clear saved date filter"><i class="bi bi-calendar-x"></i></a>
          <?php endif; ?>
        </div>
      </div>
      <datalist id="pfParcelRouteDatalist">
        <?php foreach (($deliveryRoutesFilterList ?? []) as $r): ?>
          <?php $rn = trim((string)($r['name'] ?? '')); if ($rn === '') continue; ?>
          <option value="<?php echo htmlspecialchars($rn); ?>"></option>
        <?php endforeach; ?>
      </datalist>
      <datalist id="pfParcelVehicleDatalist">
        <?php foreach (($vehiclesQuickList ?? []) as $v): ?>
          <?php $vn = trim((string)($v['vehicle_no'] ?? '')); if ($vn === '') continue; ?>
          <option value="<?php echo htmlspecialchars($vn); ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </form>
  </div>
</div>
<?php if (isset($_SESSION['parcels_filter_from']) || isset($_SESSION['parcels_filter_to'])): ?>
  <div class="alert alert-info alert-dismissible fade show mb-1 py-1 small" role="alert">
    <i class="bi bi-info-circle"></i> Date filter is saved: 
    <strong><?php echo htmlspecialchars($from ?? ''); ?></strong> to <strong><?php echo htmlspecialchars($to ?? ''); ?></strong>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<div class="cards-wrap d-md-none">
  <?php if (empty($parcels)): ?>
    <div class="alert alert-light border mb-3">No parcels found.</div>
  <?php else: ?>
    <div class="d-flex flex-column gap-2">
      <?php $rowNum = (int)($parcelRowStart ?? 0); foreach ($parcels as $p): $rowNum++; ?>
        <?php
          $st = (string)($p['status'] ?? '');
          $stClass = Helpers::parcelStatusBadgeClass($st);
          $cid = (int)($p['customer_id'] ?? 0);
          $nm = (string)($p['customer_name'] ?? '');
          $ph = trim((string)($p['customer_phone'] ?? ''));
          $isPH = preg_match('/^NA\d{10}-\d{3}$/', $ph) === 1;
          $custLabel = $nm . (!$isPH && $ph !== '' ? ' (' . $ph . ')' : '');
          $veh = trim((string)($p['vehicle_no'] ?? ''));
          $fromBr = (string)($p['from_branch'] ?? '—');
          $toBr = (string)($p['to_branch'] ?? '—');
          $priceText = is_null($p['price']) ? '-' : number_format((float)$p['price'], 2);
          $weightText = number_format((float)($p['weight'] ?? 0), 2);
          $mPid = (int)$p['id'];
          $mTrack = trim((string)($p['tracking_number'] ?? ''));
          $mItemLines = (int)($p['item_line_count'] ?? 0);
          $mSavedRoute = trim((string)($p['delivery_route'] ?? ''));
          $mRouteDisp = $mSavedRoute !== '' ? $mSavedRoute : ($fromBr . ' → ' . $toBr);
        ?>
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="title">
                  <a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=edit&id='.$mPid); ?>">
                    #<?php echo $mPid; ?>
                  </a>
                  <?php if ($mTrack !== ''): ?><span class="small text-muted ms-1"><?php echo htmlspecialchars($mTrack); ?></span><?php endif; ?>
                  <span class="badge badge-soft <?php echo $stClass; ?> ms-1"><?php echo htmlspecialchars(Helpers::parcelStatusLabel($st)); ?></span>
                </div>
                <div class="meta">
                  <?php echo htmlspecialchars(substr((string)($p['created_at'] ?? ''), 0, 16)); ?>
                </div>
              </div>
              <div class="text-end">
                <div class="kv"><span class="k">Total</span><span class="v fw-bold"><?php echo $priceText; ?></span></div>
                <div class="kv"><span class="k">Weight</span><span class="v"><?php echo $weightText; ?></span></div>
              </div>
            </div>

            <div class="mt-2">
              <div class="kv"><span class="k">Customer</span><span class="v text-truncate" style="max-width: 65%"><a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&customer_id=' . $cid); ?>"><?php echo htmlspecialchars($custLabel); ?></a></span></div>
              <div class="kv"><span class="k">Branches</span><span class="v text-truncate" style="max-width: 65%"><span><?php echo htmlspecialchars($fromBr); ?></span> <span class="text-muted">→</span> <span><?php echo htmlspecialchars($toBr); ?></span></span></div>
              <div class="kv"><span class="k">Delivery route</span><span class="v text-truncate" style="max-width: 65%"><?php echo htmlspecialchars($mRouteDisp); ?></span></div>
              <div class="kv"><span class="k">Vehicle</span><span class="v text-truncate" style="max-width: 65%">
                <?php if ($veh !== ''): ?>
                  <a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&vehicle_no=' . urlencode($veh)); ?>"><?php echo htmlspecialchars($veh); ?></a>
                <?php else: ?>—<?php endif; ?>
              </span></div>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-sm w-100 mt-2 d-flex justify-content-between align-items-center parcel-expand-btn" data-bs-toggle="collapse" data-bs-target="#parcel-items-m-<?php echo $mPid; ?>" aria-expanded="false" data-parcel-id="<?php echo $mPid; ?>">
              <span><i class="bi bi-list-ul me-1"></i>Line items<?php if ($mItemLines > 0): ?> <span class="badge bg-light text-dark border"><?php echo $mItemLines; ?></span><?php endif; ?></span>
              <i class="bi bi-chevron-down small parcel-exp-ico-m"></i>
            </button>
            <div class="collapse parcel-items-collapse" id="parcel-items-m-<?php echo $mPid; ?>" data-parcel-id="<?php echo $mPid; ?>">
              <div class="parcel-items-host mt-2 border rounded bg-light p-2" data-parcel-id="<?php echo $mPid; ?>"></div>
            </div>

            <div class="actions d-flex gap-2 flex-wrap mt-3">
              <button type="button" class="btn btn-outline-primary btn-sm btn-parcel-print" data-parcel-id="<?php echo (int)$p['id']; ?>"><i class="bi bi-printer me-1"></i>Print</button>
              <a class="btn btn-outline-primary btn-sm" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=edit&id='.(int)$p['id']); ?>"><i class="bi bi-pencil-square me-1"></i>Edit</a>
              <a class="btn btn-outline-primary btn-sm" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=email_form&id='.(int)$p['id']); ?>"><i class="bi bi-envelope me-1"></i>Email</a>
              <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=parcels&action=delete'); ?>" onsubmit="return confirm('Delete this parcel?');" class="ms-auto">
                <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="d-flex flex-wrap align-items-center gap-2 parcels-toolbar no-print-parcels">
  <button type="button" class="btn btn-outline-primary btn-sm py-1" id="parcelsPrintSelectedBtn" title="Print selected rows (current page, browser print)"><i class="bi bi-printer me-1"></i>Print selected</button>
  <button type="button" class="btn btn-outline-primary btn-sm py-1" id="parcelsPrintListBtn" title="Print parcels list (this page, browser print)"><i class="bi bi-files me-1"></i>Print list</button>
  <?php if (!empty($vehicle_no)): ?>
    <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route_detail&vehicle_no=' . urlencode($vehicle_no) . '&from=' . urlencode($from ?? date('Y-m-d')) . '&to=' . urlencode($to ?? date('Y-m-d')) . '&direction=from'); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm py-1" title="Route print"><i class="bi bi-signpost me-1"></i>Route print</a>
  <?php endif; ?>
</div>

<?php $parcelTableColspan = 17; ?>
<div class="table-wrap parcels-table-scroll d-none d-md-block">
  <table class="table table-sm table-striped table-hover align-middle parcels-table mb-0 w-100">
    <thead class="table-light">
      <tr>
        <th class="col-exp"><span class="visually-hidden">Expand</span></th>
        <th class="col-chk"><input type="checkbox" class="form-check-input" id="parcelsSelectAll" title="Select all on page" aria-label="Select all"></th>
        <th class="col-num">#</th>
        <th class="col-parcel">Parcel</th>
        <th class="cell-tight col-date">Date</th>
        <th class="col-customer">Customer</th>
        <th class="col-supplier d-none d-xl-table-cell">Supplier</th>
        <th class="col-branch d-none d-lg-table-cell">From</th>
        <th class="col-branch d-none d-lg-table-cell">To</th>
        <th class="col-veh d-none d-lg-table-cell" title="Vehicle"><i class="bi bi-truck-front"></i></th>
        <th class="col-route d-none d-lg-table-cell" title="Delivery route"><i class="bi bi-signpost"></i></th>
        <th class="col-ln d-none d-xl-table-cell text-center" title="Line count">Ln</th>
        <th class="col-weight d-none d-lg-table-cell">Wt.</th>
        <th class="col-price">Total</th>
        <th class="col-status">Status</th>
        <th class="col-email d-none d-xl-table-cell">Email</th>
        <th class="text-end col-actions" scope="col"><span class="small text-muted fw-semibold" title="Print (always) and more actions"><i class="bi bi-printer" aria-hidden="true"></i><span class="mx-1">·</span>⋮</span><span class="visually-hidden">Print and row actions</span></th>
      </tr>
    </thead>
    <tbody>
      <?php $rowNum = (int)($parcelRowStart ?? 0); foreach ($parcels as $p): $rowNum++;
        $pid = (int)$p['id'];
        $isBilled = ($p['price'] !== null && (float)$p['price'] > 0);
        $savedRoute = trim((string)($p['delivery_route'] ?? ''));
        $custLoc = trim((string)($p['customer_delivery_location'] ?? ''));
        $rdTo = trim((string)($p['route_date_to'] ?? ''));
        $rdFrom = trim((string)($p['route_date_from'] ?? ''));
        $veh = trim((string)($p['vehicle_no'] ?? ''));
        $vehDb = trim((string)($p['vehicle_no_db'] ?? ''));
        ob_start();
        if ($savedRoute !== '') {
          echo '<span class="cell-ellipsis" title="' . htmlspecialchars($savedRoute) . '">' . htmlspecialchars($savedRoute) . '</span>';
        } elseif ($custLoc !== '') {
          echo '<span class="cell-ellipsis" title="' . htmlspecialchars($custLoc) . '">' . htmlspecialchars($custLoc) . '</span>';
        } elseif ($rdTo !== '' || $rdFrom !== '') {
          $parts = [];
          if ($rdTo !== '') {
            $parts[] = 'To: ' . $rdTo;
          }
          if ($rdFrom !== '') {
            $parts[] = 'From: ' . $rdFrom;
          }
          if ($veh !== '') {
            array_unshift($parts, $veh);
          }
          $routeLabel = implode(' · ', $parts);
          echo '<span class="cell-ellipsis" title="' . htmlspecialchars($routeLabel) . '">' . htmlspecialchars($routeLabel) . '</span>';
        } elseif ($veh !== '') {
          echo '<span class="cell-ellipsis" title="' . htmlspecialchars($veh) . '">' . htmlspecialchars($veh) . '</span>';
        } else {
          echo '—';
        }
        $routeCellInner = ob_get_clean();
        $st = (string)($p['status'] ?? '');
        $stClass = Helpers::parcelStatusBadgeClass($st);
        $track = trim((string)($p['tracking_number'] ?? ''));
        $itemLineCount = (int)($p['item_line_count'] ?? 0);
        $createdShort = substr((string)($p['created_at'] ?? ''), 0, 16);
        $cid = (int)$p['customer_id'];
        $nm = (string)($p['customer_name'] ?? '');
        $ph = trim((string)($p['customer_phone'] ?? ''));
        $isPH = preg_match('/^NA\d{10}-\d{3}$/', $ph) === 1;
        $custLabel = $nm . (!$isPH && $ph !== '' ? ' (' . $ph . ')' : '');
      ?>
        <tr class="parcel-main-row" data-parcel-id="<?php echo $pid; ?>">
          <td class="col-exp">
            <button type="button" class="btn btn-link btn-sm text-muted parcel-expand-btn py-0 px-1" data-bs-toggle="collapse" data-bs-target="#parcel-items-d-<?php echo $pid; ?>" aria-expanded="false" aria-controls="parcel-items-d-<?php echo $pid; ?>" data-parcel-id="<?php echo $pid; ?>" title="Show line items">
              <i class="bi bi-chevron-right parcel-exp-ico" aria-hidden="true"></i><span class="visually-hidden">Expand</span>
            </button>
          </td>
          <td class="col-chk"><input type="checkbox" class="form-check-input parcel-row-check" name="parcel_ids[]" value="<?php echo $pid; ?>" aria-label="Select parcel <?php echo $pid; ?>"></td>
          <td class="cell-tight col-min-0"><a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=edit&id=' . $pid); ?>" title="Edit"><?php echo $rowNum; ?></a></td>
          <td class="cell-tight col-parcel col-min-0">
            <a class="text-decoration-none fw-semibold" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=edit&id=' . $pid); ?>">#<?php echo $pid; ?></a>
            <?php if ($track !== ''): ?><div class="small text-muted text-truncate" title="<?php echo htmlspecialchars($track); ?>"><?php echo htmlspecialchars($track); ?></div><?php endif; ?>
          </td>
          <td class="cell-tight small text-muted col-date col-min-0"><?php echo htmlspecialchars($createdShort); ?></td>
          <td class="col-customer col-min-0">
            <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&customer_id=' . $cid); ?>" class="text-decoration-none">
              <span class="cell-ellipsis" title="<?php echo htmlspecialchars($custLabel); ?>"><?php echo htmlspecialchars($custLabel); ?></span>
            </a>
          </td>
          <td class="d-none d-xl-table-cell col-supplier col-min-0"><span class="cell-ellipsis" title="<?php echo htmlspecialchars((string)($p['supplier_name'] ?? '')); ?>"><?php echo htmlspecialchars($p['supplier_name'] ?? ''); ?></span></td>
          <td class="d-none d-lg-table-cell col-branch col-min-0"><span class="cell-ellipsis" title="<?php echo htmlspecialchars((string)($p['from_branch'] ?? '')); ?>"><?php echo htmlspecialchars($p['from_branch'] ?? ''); ?></span></td>
          <td class="d-none d-lg-table-cell col-branch col-min-0"><span class="cell-ellipsis" title="<?php echo htmlspecialchars((string)($p['to_branch'] ?? '')); ?>"><?php echo htmlspecialchars($p['to_branch'] ?? ''); ?></span></td>
          <td class="d-none d-lg-table-cell col-veh col-min-0 pf-qe" data-parcel-id="<?php echo $pid; ?>" data-qe-field="vehicle_no" data-qe-value="<?php echo htmlspecialchars($vehDb); ?>" title="Click to edit vehicle">
            <?php if ($veh !== ''): ?>
              <span class="cell-ellipsis" title="<?php echo htmlspecialchars($veh); ?>"><?php echo htmlspecialchars($veh); ?></span>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td class="small d-none d-lg-table-cell col-route col-min-0 pf-qe" data-parcel-id="<?php echo $pid; ?>" data-qe-field="delivery_route" data-qe-value="<?php echo htmlspecialchars($savedRoute); ?>" title="Click to edit route">
            <?php echo $routeCellInner; ?>
          </td>
          <td class="text-center cell-tight d-none d-xl-table-cell col-ln col-min-0">
            <?php if ($itemLineCount > 0): ?><span class="badge rounded-pill bg-light text-dark border"><?php echo $itemLineCount; ?></span><?php else: ?><span class="text-muted">0</span><?php endif; ?>
          </td>
          <td class="text-end cell-tight d-none d-lg-table-cell col-weight col-min-0"><?php echo number_format((float)$p['weight'], 2); ?></td>
          <td class="text-end cell-tight col-price col-min-0 <?php echo $p['price'] !== null ? 'fw-bold' : ''; ?>"><?php echo is_null($p['price']) ? '-' : number_format((float)$p['price'], 2); ?></td>
          <td class="col-status col-min-0 <?php echo $isBilled ? '' : 'pf-qe'; ?>" data-parcel-id="<?php echo $pid; ?>" data-qe-field="status" data-qe-value="<?php echo htmlspecialchars($st); ?>" title="<?php echo $isBilled ? '' : 'Click to edit status'; ?>">
            <span class="badge badge-soft <?php echo $stClass; ?>"><?php echo htmlspecialchars(Helpers::parcelStatusLabel($st)); ?></span>
          </td>
          <td class="d-none d-xl-table-cell col-email col-min-0">
            <?php if (!empty($p['email_status'])): ?>
              <?php if ($p['email_status'] === 'sent'): ?>
                <span class="badge badge-soft badge-soft-success">Sent</span>
              <?php else: ?>
                <span class="badge badge-soft badge-soft-danger">Failed</span>
              <?php endif; ?>
              <small class="text-muted d-block text-truncate"><?php echo htmlspecialchars($p['emailed_at'] ?? ''); ?></small>
            <?php else: ?>
              <span class="badge badge-soft badge-soft-secondary">Not sent</span>
            <?php endif; ?>
            <div>
              <a class="small text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=email_log&id=' . $pid); ?>">Log</a>
            </div>
          </td>
          <td class="text-end position-relative col-actions">
            <div class="d-inline-flex align-items-center gap-1 parcel-actions-cell">
              <button type="button" class="btn btn-outline-primary btn-sm btn-icon btn-parcel-print" data-parcel-id="<?php echo (int)$pid; ?>" title="Print invoice in preview" aria-label="Print parcel <?php echo (int)$pid; ?>">
                <i class="bi bi-printer" aria-hidden="true"></i>
              </button>
              <div class="dropdown parcels-actions-dd">
              <button class="btn btn-outline-secondary btn-sm btn-icon" type="button" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-popper-config='{"strategy":"fixed","modifiers":[{"name":"preventOverflow","options":{"boundary":"viewport"}}]}' aria-expanded="false" title="More actions" aria-label="More actions for parcel <?php echo (int)$pid; ?>">
                <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><a class="dropdown-item" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=edit&id=' . $pid); ?>"><i class="bi bi-pencil-square me-2"></i>Edit</a></li>
                <li><a class="dropdown-item" href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=email_form&id=' . $pid); ?>"><i class="bi bi-envelope me-2"></i>Email</a></li>
                <li><a class="dropdown-item" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=route&customer_id=' . $cid); ?>"><i class="bi bi-signpost me-2"></i>Delivery route</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=parcels&action=delete'); ?>" class="px-3 mb-0" onsubmit="return confirm('Delete this parcel?');">
                    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
                    <input type="hidden" name="id" value="<?php echo $pid; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash me-2"></i>Delete</button>
                  </form>
                </li>
              </ul>
              </div>
            </div>
          </td>
        </tr>
        <tr class="parcel-items-tr parcel-items-tr-<?php echo $pid; ?>">
          <td colspan="<?php echo (int)$parcelTableColspan; ?>" class="p-0 border-0">
            <div id="parcel-items-d-<?php echo $pid; ?>" class="collapse parcel-items-collapse" data-parcel-id="<?php echo $pid; ?>">
              <div class="parcel-items-panel px-2 py-2 border-top border-light bg-light bg-opacity-50">
                <div class="table-responsive">
                  <div class="parcel-items-host" data-parcel-id="<?php echo $pid; ?>"></div>
                </div>
              </div>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <!-- Skeleton loader (hidden by default, shown via JS when loading) -->
  <table id="parcelsSkeleton" class="table table-sm parcels-table" style="display:none;">
    <thead class="table-light"><tr><th class="col-chk"></th><th class="col-num">#</th><th>Customer</th><th>Supplier</th><th>From</th><th>To</th><th>Veh</th><th>Route</th><th>Items</th><th>Weight</th><th>Price</th><th>Status</th><th>Email</th><th>Act</th></tr></thead>
    <tbody>
      <tr class="skeleton-row"><td></td><td><div class="skeleton skeleton-cell" style="width:40%"></div></td><td><div class="skeleton skeleton-cell"></div></td><td><div class="skeleton skeleton-cell"></div></td><td><div class="skeleton skeleton-cell" style="width:60%"></div></td><td><div class="skeleton skeleton-cell" style="width:60%"></div></td><td><div class="skeleton skeleton-cell" style="width:50%"></div></td><td><div class="skeleton skeleton-cell"></div></td><td><div class="skeleton skeleton-cell"></div></td><td><div class="skeleton skeleton-cell" style="width:50%"></div></td><td><div class="skeleton skeleton-cell" style="width:50%"></div></td><td><div class="skeleton skeleton-cell" style="width:60%"></div></td><td><div class="skeleton skeleton-cell" style="width:50%"></div></td><td><div class="skeleton skeleton-cell" style="width:30%"></div></td></tr>
      <tr class="skeleton-row"><td></td><td><div class="skeleton skeleton-cell" style="width:40%"></div></td><td><div class="skeleton skeleton-cell"></div></td><td><div class="skeleton skeleton-cell"></div></td><td><div class="skeleton skeleton-cell" style="width:60%"></div></td><td><div class="skeleton skeleton-cell" style="width:60%"></div></td><td><div class="skeleton skeleton-cell" style="width:50%"></div></td><td><div class="skeleton skeleton-cell"></div></td><td><div class="skeleton skeleton-cell"></div></td><td><div class="skeleton skeleton-cell" style="width:50%"></div></td><td><div class="skeleton skeleton-cell" style="width:50%"></div></td><td><div class="skeleton skeleton-cell" style="width:60%"></div></td><td><div class="skeleton skeleton-cell" style="width:50%"></div></td><td><div class="skeleton skeleton-cell" style="width:30%"></div></td></tr>
      <tr class="skeleton-row"><td></td><td><div class="skeleton skeleton-cell" style="width:40%"></div></td><td><div class="skeleton skeleton-cell"></div></td><td><div class="skeleton skeleton-cell"></div></td><td><div class="skeleton skeleton-cell" style="width:60%"></div></td><td><div class="skeleton skeleton-cell" style="width:60%"></div></td><td><div class="skeleton skeleton-cell" style="width:50%"></div></td><td><div class="skeleton skeleton-cell"></div></td><td><div class="skeleton skeleton-cell"></div></td><td><div class="skeleton skeleton-cell" style="width:50%"></div></td><td><div class="skeleton skeleton-cell" style="width:50%"></div></td><td><div class="skeleton skeleton-cell" style="width:60%"></div></td><td><div class="skeleton skeleton-cell" style="width:50%"></div></td><td><div class="skeleton skeleton-cell" style="width:30%"></div></td></tr>
    </tbody>
  </table>
</div>

<?php if (($totalPages ?? 1) > 1): ?>
<div class="d-flex justify-content-between align-items-center mt-1 flex-wrap gap-2 small">
  <div class="text-muted">
    Showing <?php echo count($parcels); ?> of <?php echo (int)($totalCount ?? 0); ?> parcels
  </div>
  <nav>
    <ul class="pagination pagination-sm mb-0">
      <?php if (($page ?? 1) > 1): ?>
        <li class="page-item">
          <a class="page-link" href="<?php 
            $query = $_GET;
            $query['page_num'] = ($page ?? 1) - 1;
            echo Helpers::baseUrl('index.php?' . http_build_query($query));
          ?>">Previous</a>
        </li>
      <?php else: ?>
        <li class="page-item disabled">
          <span class="page-link">Previous</span>
        </li>
      <?php endif; ?>
      
      <?php
      $currentPage = $page ?? 1;
      $totalPages = $totalPages ?? 1;
      $startPage = max(1, $currentPage - 2);
      $endPage = min($totalPages, $currentPage + 2);
      
      if ($startPage > 1): ?>
        <li class="page-item">
          <a class="page-link" href="<?php 
            $query = $_GET;
            $query['page_num'] = 1;
            echo Helpers::baseUrl('index.php?' . http_build_query($query));
          ?>">1</a>
        </li>
        <?php if ($startPage > 2): ?>
          <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
      <?php endif; ?>
      
      <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
        <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
          <a class="page-link" href="<?php 
            $query = $_GET;
            $query['page_num'] = $i;
            echo Helpers::baseUrl('index.php?' . http_build_query($query));
          ?>"><?php echo $i; ?></a>
        </li>
      <?php endfor; ?>
      
      <?php if ($endPage < $totalPages): ?>
        <?php if ($endPage < $totalPages - 1): ?>
          <li class="page-item disabled"><span class="page-link">...</span></li>
        <?php endif; ?>
        <li class="page-item">
          <a class="page-link" href="<?php 
            $query = $_GET;
            $query['page_num'] = $totalPages;
            echo Helpers::baseUrl('index.php?' . http_build_query($query));
          ?>"><?php echo $totalPages; ?></a>
        </li>
      <?php endif; ?>
      
      <?php if (($page ?? 1) < ($totalPages ?? 1)): ?>
        <li class="page-item">
          <a class="page-link" href="<?php 
            $query = $_GET;
            $query['page_num'] = ($page ?? 1) + 1;
            echo Helpers::baseUrl('index.php?' . http_build_query($query));
          ?>">Next</a>
        </li>
      <?php else: ?>
        <li class="page-item disabled">
          <span class="page-link">Next</span>
        </li>
      <?php endif; ?>
    </ul>
  </nav>
</div>
<?php endif; ?>

</div><!-- /.no-print -->

<div class="parcels-print-document">
  <header class="ppd-head">
    <h1 class="ppd-title">Parcels List</h1>
    <p class="ppd-sub">Date range: <?php echo htmlspecialchars($from ?? '—'); ?> to <?php echo htmlspecialchars($to ?? '—'); ?> — Total: <?php echo (int)($totalCount ?? 0); ?> parcel(s) — Page <?php echo (int)($page ?? 1); ?> of <?php echo (int)($totalPages ?? 1); ?> (<?php echo count($parcels); ?> on this page)</p>
  </header>
  <?php if (empty($parcels)): ?>
    <p class="ppd-empty">No parcels on this page.</p>
  <?php else: ?>
    <?php foreach ($parcels as $p): ?>
      <?php
        $ppid = (int)($p['id'] ?? 0);
        $pst = (string)($p['status'] ?? '');
        $ptrack = trim((string)($p['tracking_number'] ?? ''));
        $pdate = htmlspecialchars(substr((string)($p['created_at'] ?? ''), 0, 16));
        $pnm = (string)($p['customer_name'] ?? '');
        $pph = trim((string)($p['customer_phone'] ?? ''));
        $pisPH = preg_match('/^NA\d{10}-\d{3}$/', $pph) === 1;
        $pcust = htmlspecialchars($pnm . (!$pisPH && $pph !== '' ? ' (' . $pph . ')' : ''));
        $pfrom = htmlspecialchars((string)($p['from_branch'] ?? ''));
        $pto = htmlspecialchars((string)($p['to_branch'] ?? ''));
        $pwt = number_format((float)($p['weight'] ?? 0), 2);
        $pprice = $p['price'] === null ? '—' : number_format((float)$p['price'], 2);
        $pstLabel = htmlspecialchars(Helpers::parcelStatusLabel($pst));
        $savedRoute = trim((string)($p['delivery_route'] ?? ''));
        $custLoc = trim((string)($p['customer_delivery_location'] ?? ''));
        $rdTo = trim((string)($p['route_date_to'] ?? ''));
        $rdFrom = trim((string)($p['route_date_from'] ?? ''));
        $pveh = trim((string)($p['vehicle_no'] ?? ''));
        if ($savedRoute !== '') {
          $proute = htmlspecialchars($savedRoute);
        } elseif ($custLoc !== '') {
          $proute = htmlspecialchars($custLoc);
        } elseif ($rdTo !== '' || $rdFrom !== '') {
          $rparts = [];
          if ($rdTo !== '') {
            $rparts[] = 'To: ' . $rdTo;
          }
          if ($rdFrom !== '') {
            $rparts[] = 'From: ' . $rdFrom;
          }
          if ($pveh !== '') {
            array_unshift($rparts, $pveh);
          }
          $proute = htmlspecialchars(implode(' · ', $rparts));
        } elseif ($pveh !== '') {
          $proute = htmlspecialchars($pveh);
        } else {
          $proute = '—';
        }
        $pitems = $parcelItemsById[$ppid] ?? [];
      ?>
      <section class="ppd-block" data-parcel-id="<?php echo $ppid; ?>">
        <table class="ppd-sum-table">
          <colgroup>
            <col class="ppd-s-id">
            <col class="ppd-s-serial">
            <col class="ppd-s-date">
            <col class="ppd-s-customer">
            <col class="ppd-s-branches">
            <col class="ppd-s-route">
            <col class="ppd-s-weight">
            <col class="ppd-s-total">
            <col class="ppd-s-status">
          </colgroup>
          <thead>
            <tr>
              <th>ID</th>
              <th>Serial</th>
              <th>Date</th>
              <th>Customer</th>
              <th>From / To</th>
              <th>Route</th>
              <th class="ppd-num">Weight</th>
              <th class="ppd-num">Total</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><?php echo $ppid; ?></td>
              <td><?php echo $ptrack !== '' ? htmlspecialchars($ptrack) : '—'; ?></td>
              <td><?php echo $pdate; ?></td>
              <td><?php echo $pcust; ?></td>
              <td><?php echo $pfrom; ?> → <?php echo $pto; ?></td>
              <td><?php echo $proute; ?></td>
              <td class="ppd-num"><?php echo htmlspecialchars($pwt); ?></td>
              <td class="ppd-num"><?php echo htmlspecialchars($pprice); ?></td>
              <td><?php echo $pstLabel; ?></td>
            </tr>
          </tbody>
        </table>
        <div class="ppd-items-caption">Line items</div>
        <?php if (empty($pitems)): ?>
          <p class="ppd-empty ppd-empty-tight">No line items.</p>
        <?php else: ?>
          <table class="ppd-items-table">
            <colgroup>
              <col class="ppd-i-no">
              <col class="ppd-i-desc">
              <col class="ppd-i-qty">
              <col class="ppd-i-rate">
              <col class="ppd-i-amt">
              <col class="ppd-i-add">
            </colgroup>
            <thead>
              <tr>
                <th>No</th>
                <th>Description</th>
                <th class="ppd-num">Qty</th>
                <th class="ppd-num">Rate</th>
                <th class="ppd-num">Amount</th>
                <th>Additional</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $pln = 0;
              $sumAmt = 0.0;
              $sumAdd = 0.0;
              foreach ($pitems as $ir):
                $pln++;
                $pq = (float)($ir['qty'] ?? 0);
                $pr = array_key_exists('rate', $ir) && $ir['rate'] !== null && $ir['rate'] !== '' ? (float)$ir['rate'] : null;
                $pamt = ($pr !== null && $pq > 0) ? round($pq * $pr, 2) : 0.0;
                $sumAmt += $pamt;
                $addStored = array_key_exists('additional_amount', $ir) && $ir['additional_amount'] !== null && $ir['additional_amount'] !== ''
                  ? (float)$ir['additional_amount'] : 0.0;
                $tagVals = [];
                if (!empty($ir['additional_amounts'])) {
                  $dec = json_decode((string)$ir['additional_amounts'], true);
                  if (is_array($dec)) {
                    foreach ($dec as $tv) {
                      $tagVals[] = round((float)$tv, 2);
                    }
                  }
                }
                $add = $addStored > 0 ? $addStored : ($tagVals ? round(array_sum($tagVals), 2) : 0.0);
                $sumAdd += $add;
              ?>
              <tr>
                <td class="ppd-num"><?php echo $pln; ?></td>
                <td><?php echo htmlspecialchars((string)($ir['description'] ?? '')); ?></td>
                <td class="ppd-num"><?php echo htmlspecialchars(number_format($pq, 2)); ?></td>
                <td class="ppd-num"><?php echo $pr !== null ? htmlspecialchars(number_format($pr, 2)) : '—'; ?></td>
                <td class="ppd-num"><?php echo $pamt > 0 ? htmlspecialchars(number_format($pamt, 2)) : '—'; ?></td>
                <td>
                  <?php if ($tagVals): ?>
                    <?php
                    $addParts = [];
                    foreach ($tagVals as $tv) {
                      $addParts[] = '+' . number_format($tv, 2);
                    }
                    echo htmlspecialchars(implode(' ', $addParts));
                    ?>
                  <?php elseif ($add > 0): ?>
                    +<?php echo htmlspecialchars(number_format($add, 2)); ?>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="ppd-foot-sub">
                <td colspan="4" class="ppd-num">Subtotals</td>
                <td class="ppd-num"><?php echo htmlspecialchars(number_format($sumAmt, 2)); ?></td>
                <td class="ppd-num"><?php echo $sumAdd > 0 ? '+' . htmlspecialchars(number_format($sumAdd, 2)) : '—'; ?></td>
              </tr>
              <tr class="ppd-foot-total">
                <td colspan="6" class="ppd-num">Lines + additional: <?php echo htmlspecialchars(number_format($sumAmt + $sumAdd, 2)); ?></td>
              </tr>
            </tfoot>
          </table>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="modal fade no-print no-print-parcels" id="parcelPrintModal" tabindex="-1" aria-labelledby="parcelPrintModalLabel" aria-hidden="true" data-bs-backdrop="true">
  <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-lg-down modal-xl modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header py-2 px-3">
        <h5 class="modal-title fs-6" id="parcelPrintModalLabel">Invoice preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0 position-relative bg-secondary bg-opacity-10" style="min-height: 50vh;">
        <div id="parcelPrintLoading" class="position-absolute top-50 start-50 translate-middle text-muted small">Loading invoice…</div>
        <iframe id="parcelPrintFrame" class="w-100 border-0 d-none" style="min-height: 65vh; height: 65vh; background: #fff;" title="Invoice"></iframe>
      </div>
      <div class="modal-footer py-2 px-3 gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary btn-sm" id="parcelPrintModalPrintBtn" disabled><i class="bi bi-printer me-1"></i>Print</button>
      </div>
    </div>
  </div>
</div>

<div id="parcelsToastHost" class="toast-container position-fixed bottom-0 end-0 p-3 no-print no-print-parcels" style="z-index:1090"></div>
<script>
window.TMS_PARCELS = <?php echo json_encode([
  'indexUrl' => Helpers::baseUrl('index.php'),
  'csrf' => Helpers::csrfToken(),
  'quickUpdateUrl' => Helpers::baseUrl('index.php?page=parcels&action=quick_update'),
  'statusOptions' => Helpers::parcelStatusMap(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="<?php echo Helpers::baseUrl('assets/js/parcels.js'); ?>"></script>

</div><!-- /.parcels-page -->
