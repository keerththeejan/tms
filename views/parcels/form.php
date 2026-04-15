<?php /** @var array $parcel */ ?>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap');
  /* —— SaaS dashboard tokens (8px grid, 12–16px cards) —— */
  /* Mobile-first: fluid layout, no page-level horizontal scroll */
  .parcel-form-page.pf-saas .pf-page-wrap {
    max-width: 100%;
    overflow-x: clip;
  }
  .parcel-form-page .pf-main-columns > [class*="col-"] {
    min-width: 0;
  }
  .parcel-form-page .pf-form-sections .row {
    --bs-gutter-x: 0.75rem;
    --bs-gutter-y: 0.5rem;
  }
  .parcel-form-page .pf-input-group {
    flex-wrap: nowrap;
    max-width: 100%;
  }
  .parcel-form-page .pf-input-group > .form-control,
  .parcel-form-page .pf-input-group > .form-select,
  .parcel-form-page .pf-input-group > .choices {
    min-width: 0;
    flex: 1 1 auto;
  }
  .parcel-form-page .pf-input-group .choices {
    max-width: 100%;
  }
  .parcel-form-page.pf-saas {
    --pf-space-1: 8px;
    --pf-space-2: 16px;
    --pf-space-3: 24px;
    --pf-glass-bg: rgba(255, 255, 255, 0.78);
    --pf-glass-border: rgba(255, 255, 255, 0.55);
    --pf-soft-shadow: 0 4px 24px rgba(15, 23, 42, 0.06), 0 1px 3px rgba(15, 23, 42, 0.04);
    --pf-card-radius: clamp(12px, 1.2vw, 16px);
    --pf-card-pad: var(--pf-space-2);
    --pf-input-h: 38px;
    --pf-accent: #2563eb;
    --pf-accent-ring: rgba(37, 99, 235, 0.35);
    --pf-accent-soft: rgba(37, 99, 235, 0.08);
    --pf-muted: #64748b;
    --pf-border-subtle: rgba(15, 23, 42, 0.08);
    --pf-transition: 0.2s ease;
    font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    color: #0f172a;
    background: linear-gradient(165deg, #eef2f7 0%, #e8ecf4 40%, #f4f6fa 100%);
    min-height: 100%;
    overflow-x: clip;
  }
  .parcel-form-page { --pf-radius: 0.5rem; --pf-border: 1px solid var(--bs-border-color-translucent); }
  /* Full width inside main content (matches parcels list — no side gutters) */
  .parcel-form-page.pf-saas .pf-page-wrap { max-width: none; width: 100%; margin-left: 0; margin-right: 0; }
  /* Nested .container-fluid inherits design-system max-width:1560px — force parcel form to true full width of parent */
  .parcel-form-page .pf-page-wrap.pf-form-inner-fluid,
  .parcel-form-page #parcelForm > .container-fluid.pf-form-inner-fluid {
    max-width: none !important;
    width: 100% !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    box-sizing: border-box;
  }
  .parcel-form-page.pf-saas .page-header {
    margin-bottom: 0.65rem;
    padding: 0.75rem 0.9rem;
    border: 1px solid rgba(255,255,255,0.6);
    border-radius: var(--pf-card-radius);
    background: var(--pf-glass-bg);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    box-shadow: var(--pf-soft-shadow);
    transition: box-shadow var(--pf-transition), transform var(--pf-transition);
  }
  .parcel-form-page.pf-saas .page-header:hover { box-shadow: 0 12px 40px rgba(15, 23, 42, 0.1); }
  .parcel-form-page.pf-saas .pf-breadcrumb {
    padding: 0.35rem 0;
  }
  @keyframes pfFadeSlideIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .parcel-form-page .pf-animate-in {
    animation: pfFadeSlideIn 0.45s ease forwards;
  }
  .parcel-form-page .pf-animate-delay-2 { animation-delay: 0.08s; opacity: 0; animation-fill-mode: forwards; }
  .parcel-form-page .pf-sticky-head {
    position: sticky;
    top: 0;
    z-index: 40;
    background: rgba(248,249,251,.92);
    backdrop-filter: blur(10px);
  }
  .parcel-form-page .section-card {
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: var(--pf-card-radius, clamp(16px, 2vw, 20px));
    box-shadow: var(--pf-soft-shadow, 0 8px 32px rgba(15, 23, 42, 0.08));
    margin-bottom: 0.85rem;
    overflow: hidden;
    background: var(--pf-glass-bg, #fff);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    transition: box-shadow 0.3s ease, transform 0.3s ease;
  }
  .parcel-form-page.pf-saas .section-card:hover { box-shadow: 0 14px 44px rgba(15, 23, 42, 0.1); }
  .parcel-form-page .section-card .section-title {
    font-size: 0.88rem;
    font-weight: 700;
    padding: 0.65rem 1rem;
    background: linear-gradient(180deg, rgba(248,250,252,0.95), rgba(255,255,255,0.6));
    border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    color: #334155;
    letter-spacing: 0.02em;
  }
  .parcel-form-page .section-card .section-body { padding: var(--pf-card-pad, 1rem); }
  .parcel-form-page .pf-details-grid { --pf-gutter: 0.5rem; }
  .parcel-form-page .pf-details-grid > [class*="col-"] { margin-bottom: 0; }
  .parcel-form-page .pf-field-group-title { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--bs-secondary-color); margin: 0.35rem 0 0.25rem; }
  .parcel-form-page .pf-field-group-title:first-child { margin-top: 0; }
  .parcel-form-page .receipt-box {
    border: 1px solid rgba(79, 70, 229, 0.25);
    border-radius: var(--pf-card-radius, 18px);
    overflow: hidden;
    background: rgba(255, 255, 255, 0.95);
    box-shadow: var(--pf-soft-shadow, 0 8px 32px rgba(15, 23, 42, 0.08));
    transition: box-shadow 0.3s ease;
  }
  .parcel-form-page.pf-saas .receipt-box:hover { box-shadow: 0 14px 44px rgba(79, 70, 229, 0.12); }
  .parcel-form-page .receipt-header {
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(99, 102, 241, 0.06));
    border-bottom: 1px solid rgba(79, 70, 229, 0.2);
    padding: 0.65rem 1rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
  }
  .parcel-form-page .pf-serial-input {
    width: 100%;
    max-width: min(100%, 12rem);
    min-width: 0;
    min-height: 44px;
  }
  @media (min-width: 768px) {
    .parcel-form-page .pf-serial-input {
      max-width: 11rem;
    }
  }
  .parcel-form-page .receipt-grid { border-collapse: separate; border-spacing: 0; font-size: 0.8125rem; }
  /* Receipt strip: responsive grid — stacked on phone, multi-column on tablet/desktop */
  .parcel-form-page .pf-receipt-summary {
    display: grid;
    gap: 0.65rem 1rem;
    grid-template-columns: 1fr;
    align-items: start;
  }
  .parcel-form-page .pf-receipt-summary .pf-rs-block {
    min-width: 0;
  }
  @media (min-width: 576px) and (max-width: 991.98px) {
    .parcel-form-page .pf-receipt-summary {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .parcel-form-page .pf-receipt-summary .pf-rs-customer {
      grid-column: 1 / -1;
    }
  }
  @media (min-width: 992px) {
    .parcel-form-page .pf-receipt-summary {
      grid-template-columns: minmax(0, 2fr) repeat(4, minmax(0, 1fr));
      gap: 0.5rem 0.75rem;
    }
  }
  .parcel-form-page .receipt-grid th, .parcel-form-page .receipt-grid td { border: 1px solid rgba(15, 23, 42, 0.08); vertical-align: middle; padding: 0.45rem 0.55rem; }
  .parcel-form-page .receipt-grid thead th {
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    text-transform: uppercase; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.04em; color: #475569;
    border-bottom: 1px solid rgba(15, 23, 42, 0.1);
    white-space: nowrap;
  }
  /* Sticky thead only on large screens (table layout); mobile uses stacked cards */
  .parcel-form-page .receipt-grid tbody tr { transition: background-color 0.15s ease; }
  .parcel-form-page .receipt-grid tbody tr:hover { background: rgba(37, 99, 235, 0.04); }
  .parcel-form-page .receipt-grid .form-control, .parcel-form-page .receipt-grid .form-control-sm { min-height: 2rem; border-radius: 8px; }
  .parcel-form-page .receipt-total {
    background: linear-gradient(180deg, rgba(249, 250, 251, 0.95) 0%, rgba(255, 255, 255, 0.98) 100%);
    border-top: 1px solid #e5e7eb;
    font-weight: 700;
    padding: 0.65rem 0.85rem;
    position: sticky;
    bottom: 0;
    z-index: 5;
    box-shadow: 0 -4px 16px rgba(15, 23, 42, 0.05);
  }
  .parcel-form-page .pf-items-section .receipt-total .row {
    align-items: center;
  }
  .parcel-form-page .serial-badge { border: 2px solid var(--bs-primary); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-weight: 600; }
  .parcel-form-page .table-responsive { -webkit-overflow-scrolling: touch; }
  .parcel-form-page .pf-col-items {
    min-width: 0;
    max-width: 100%;
  }
  .parcel-form-page .section-card.pf-items-section {
    overflow: visible !important;
  }
  /* Branches: Choices.js dropdown is position:absolute — allow it to extend past card edges */
  .parcel-form-page .section-card.pf-branches-section {
    overflow: visible !important;
  }
  .parcel-form-page .pf-branches-section .section-body {
    overflow: visible;
  }
  .parcel-form-page .pf-items-section .receipt-box {
    max-width: 100%;
    overflow: visible;
  }
  /* Line items: same table layout on all viewports — pan horizontally on narrow screens */
  .parcel-form-page .pf-items-scroll {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    display: block;
    box-sizing: border-box;
    max-height: min(380px, 48vh);
    overflow-x: auto;
    overflow-y: auto;
    overscroll-behavior: contain;
    overscroll-behavior-x: contain;
    touch-action: pan-x pan-y;
    border: 1px solid rgba(15, 23, 42, 0.1);
    border-radius: 10px;
    background: #fff;
    -webkit-overflow-scrolling: touch;
  }
  .parcel-form-page .pf-items-scroll .table { margin-bottom: 0; }
  /* Items grid: fixed min width — scroll container shows full desktop-style columns */
  .parcel-form-page #itemsTable {
    --pf-grid-line: #e5e7eb;
    --pf-items-table-min: 880px;
    width: 100%;
    min-width: var(--pf-items-table-min);
    max-width: none;
    table-layout: fixed;
    border-collapse: collapse;
    border: 1px solid var(--pf-grid-line);
    font-size: clamp(0.7rem, 0.12vw + 0.66rem, 0.8125rem);
    background: #fff;
  }
  .parcel-form-page #itemsTable.receipt-grid th,
  .parcel-form-page #itemsTable.receipt-grid td {
    border: 1px solid var(--pf-grid-line);
    vertical-align: middle;
    padding: clamp(0.12rem, 0.35vw, 0.28rem) clamp(0.2rem, 0.45vw, 0.38rem);
    white-space: nowrap;
  }
  .parcel-form-page #itemsTable.receipt-grid td.item-amount-cell {
    white-space: normal;
    min-width: 12.5rem;
  }
  .parcel-form-page #itemsTable.receipt-grid thead th {
    background: #f9fafb;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748b;
    white-space: nowrap;
    border-bottom: 1px solid #d1d5db;
  }
  .parcel-form-page #itemsTable.receipt-grid tbody tr {
    transition: background-color 0.12s ease;
    background: #fff;
  }
  .parcel-form-page #itemsTable.receipt-grid tbody tr:hover {
    background: #f0f7ff;
  }
  .parcel-form-page #itemsTable .form-control,
  .parcel-form-page #itemsTable .form-control-sm {
    border: none;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    min-height: 1.35rem;
    padding: 0.12rem 0.28rem;
    font-size: inherit;
    line-height: 1.3;
  }
  .parcel-form-page #itemsTable .form-control:focus,
  .parcel-form-page #itemsTable .form-control-sm:focus {
    background: #fff;
    outline: none;
    box-shadow: inset 0 0 0 2px #2563eb;
    border-radius: 1px;
  }
  .parcel-form-page #itemsTable .form-control::placeholder {
    color: #94a3b8;
    font-size: 0.85em;
  }
  .parcel-form-page #itemsTable .form-control:disabled {
    background: rgba(241, 245, 249, 0.65);
    color: #64748b;
    opacity: 1;
  }
  .parcel-form-page #itemsTable .pf-amount-readout {
    display: block;
    font-variant-numeric: tabular-nums;
    font-weight: 600;
    color: #0f172a;
    padding: 0.14rem 0.28rem;
    min-height: 1.35rem;
    line-height: 1.3;
    background: #f9fafb;
    border: 1px solid var(--pf-grid-line, #e5e7eb);
    border-radius: 0;
    text-align: center;
  }
  .parcel-form-page #itemsTable .pf-item-amt-cell {
    min-width: 5rem;
    vertical-align: middle;
  }
  .parcel-form-page #itemsTable .pf-item-amt-cell .item-amount.pf-amount-readout {
    margin: 0;
  }
  .parcel-form-page #itemsTable .item-add-sum.pf-amount-readout {
    font-size: 0.72rem;
    padding: 0.1rem 0.22rem;
    min-height: 1.25rem;
    margin-bottom: 0.1rem;
  }
  .parcel-form-page #itemsTable .pf-item-add-block {
    gap: 0.25rem !important;
  }
  /* Excel-style additional amounts: single visible cell; hidden native rows submit as before */
  .parcel-form-page #itemsTable .pf-item-add-block.pf-item-add-excel .item-add-list {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
    pointer-events: none;
  }
  .parcel-form-page #itemsTable .pf-item-add-block.pf-item-add-excel .add-amount-btn {
    display: none !important;
  }
  .parcel-form-page #itemsTable .pf-item-add-excel-wrap {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.2rem 0.35rem;
    min-width: 0;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    min-height: 1.42rem;
    padding: 0.12rem 0.35rem;
    border: 1px solid #e5e7eb;
    border-radius: 0;
    background: #fff;
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
  }
  .parcel-form-page #itemsTable .pf-item-add-excel-wrap:focus-within {
    border-color: rgba(37, 99, 235, 0.55);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    outline: 0;
  }
  .parcel-form-page #itemsTable .pf-item-add-excel-tokens {
    display: contents;
  }
  .parcel-form-page #itemsTable .pf-item-add-excel-token {
    display: inline-flex;
    align-items: center;
    gap: 0.12rem;
    flex: 0 0 auto;
    max-width: 100%;
    padding: 0.06rem 0.28rem 0.06rem 0.38rem;
    font-size: 0.72rem;
    font-variant-numeric: tabular-nums;
    line-height: 1.25;
    color: #334155;
    background: rgba(241, 245, 249, 0.95);
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 4px;
  }
  .parcel-form-page #itemsTable .pf-item-add-excel-token + .pf-item-add-excel-token::before {
    content: '+';
    display: inline;
    margin-inline-end: 0.35rem;
    color: #94a3b8;
    font-size: 0.72rem;
    font-weight: 500;
  }
  .parcel-form-page #itemsTable .pf-item-add-excel-remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    padding: 0 0.12rem;
    border: 0;
    background: transparent;
    color: #94a3b8;
    font-size: 0.85rem;
    line-height: 1;
    cursor: pointer;
    border-radius: 2px;
  }
  .parcel-form-page #itemsTable .pf-item-add-excel-remove:hover,
  .parcel-form-page #itemsTable .pf-item-add-excel-remove:focus-visible {
    color: #b91c1c;
    background: rgba(254, 226, 226, 0.45);
    outline: 0;
  }
  .parcel-form-page #itemsTable .pf-item-add-excel-field {
    flex: 1 1 3.25rem;
    min-width: 2.5rem;
    max-width: 100%;
    border: 0;
    background: transparent;
    padding: 0.06rem 0.1rem;
    font-size: 0.78rem;
    font-variant-numeric: tabular-nums;
    line-height: 1.3;
    color: #0f172a;
    outline: 0;
    box-shadow: none;
  }
  .parcel-form-page #itemsTable .pf-item-add-excel-field::placeholder {
    color: #94a3b8;
    font-size: 0.72rem;
  }
  .parcel-form-page #itemsTable .pf-item-add-excel-field:disabled {
    cursor: not-allowed;
    opacity: 0.75;
  }
  .parcel-form-page #itemsTable .pf-item-add-block.pf-item-add-excel {
    position: relative;
    min-width: 0;
  }
  .parcel-form-page #itemsTable .item-add-list {
    gap: 0.2rem !important;
  }
  .parcel-form-page #itemsTable .item-add-row .form-control-sm {
    min-height: 1.4rem;
    padding: 0.12rem 0.28rem;
    font-size: 0.78rem;
  }
  .parcel-form-page #itemsTable .add-amount-btn {
    align-self: flex-start;
    padding: 0.1rem 0.35rem !important;
    font-size: 0.65rem !important;
    line-height: 1.2;
    border-radius: 6px;
    border-color: rgba(15, 23, 42, 0.14);
    color: #64748b;
    opacity: 0.92;
  }
  .parcel-form-page #itemsTable .add-amount-btn:hover {
    opacity: 1;
    border-color: rgba(37, 99, 235, 0.35);
    color: var(--pf-accent, #2563eb);
    background: rgba(37, 99, 235, 0.06);
  }
  .parcel-form-page #itemsTable .btn.remove-row,
  .parcel-form-page #itemsTable .remove-row {
    padding: 0.2rem 0.35rem !important;
    min-height: 0;
    line-height: 1;
    border-radius: 8px;
    border-color: rgba(15, 23, 42, 0.12);
    color: #64748b;
    background: transparent;
  }
  .parcel-form-page #itemsTable .btn.remove-row:hover,
  .parcel-form-page #itemsTable .remove-row:hover {
    border-color: rgba(220, 38, 38, 0.45);
    color: #b91c1c;
    background: rgba(254, 226, 226, 0.35);
  }
  .parcel-form-page #itemsTable .pf-item-remove-cell {
    width: 2.5rem;
    text-align: center;
    vertical-align: middle;
  }
  /* Column alignment — same as desktop at every breakpoint (table scrolls horizontally on small screens) */
  .parcel-form-page #itemsTable thead th:nth-child(1),
  .parcel-form-page #itemsTable thead th:nth-child(3),
  .parcel-form-page #itemsTable thead th:nth-child(4),
  .parcel-form-page #itemsTable thead th:nth-child(5),
  .parcel-form-page #itemsTable thead th:nth-child(6),
  .parcel-form-page #itemsTable thead th:nth-child(7) {
    text-align: center;
  }
  .parcel-form-page #itemsTable thead th:nth-child(2) {
    text-align: left;
  }
  .parcel-form-page #itemsTable .item-desc {
    text-align: left;
  }
  .parcel-form-page #itemsTable .item-qty,
  .parcel-form-page #itemsTable .item-rate {
    text-align: center;
  }
  .parcel-form-page #itemsTable .pf-item-amt-cell .pf-amount-readout,
  .parcel-form-page #itemsTable .item-amount-cell .pf-amount-readout,
  .parcel-form-page #itemsTable .item-amount-cell .item-add {
    text-align: center;
  }
  .parcel-form-page .pf-items-scroll thead th {
    position: sticky;
    top: 0;
    z-index: 6;
    box-shadow: 0 1px 0 #e5e7eb;
  }
  .parcel-form-page .pf-items-scroll #itemsTable thead th {
    background: #f3f4f6;
  }
  .parcel-form-page .pf-btn-icon-touch {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    min-height: 2.5rem;
    min-width: 2.5rem;
    padding: 0.35rem 0.65rem;
  }
  @media (min-width: 768px) {
    .parcel-form-page .pf-btn-icon-touch {
      min-height: 2rem;
      min-width: auto;
      padding: 0.25rem 0.5rem;
    }
  }
  .parcel-form-page .form-label { font-weight: 600; margin-bottom: 0.15rem; font-size: 0.8125rem; }
  .parcel-form-page .pf-customer-stack .form-control,
  .parcel-form-page .pf-customer-stack .form-select { margin-bottom: 0; }
  .parcel-form-page .pf-customer-stack .customer-search-results input { margin-bottom: 0.25rem; }
  .parcel-form-page .btn-quick { font-size: 0.8rem; }
  /* Parcel details — grouped cards, dense inputs */
  .parcel-form-page .pf-details-shell {
    overflow-x: hidden;
    max-width: 100%;
    background: linear-gradient(180deg, rgba(255,255,255,0.95) 0%, rgba(248,250,252,0.92) 100%);
    border: 1px solid var(--pf-border-subtle, rgba(15, 23, 42, 0.08));
    border-radius: var(--pf-card-radius, 14px);
    padding: var(--pf-card-pad, 16px);
    box-shadow: var(--pf-soft-shadow, 0 8px 32px rgba(15, 23, 42, 0.08));
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    transition: box-shadow 0.3s ease;
  }
  .parcel-form-page.pf-saas .pf-details-shell:hover { box-shadow: 0 12px 40px rgba(15, 23, 42, 0.1); }
  /* Explicit field order (Bootstrap order-*) + smooth layout rendering */
  .parcel-form-page .pf-details-field-row {
    --bs-gutter-x: var(--pf-space-2, 16px);
    --bs-gutter-y: var(--pf-space-2, 16px);
  }
  .parcel-form-page .pf-details-field-row > [class*="col-"] {
    min-width: 0;
    transition: box-shadow 0.25s ease, border-color 0.25s ease, transform 0.2s ease;
  }
  @media (prefers-reduced-motion: reduce) {
    .parcel-form-page .pf-details-field-row > [class*="col-"],
    .parcel-form-page .pf-animate-in { transition: none !important; animation: none !important; }
  }
  .parcel-form-page.pf-saas {
    scroll-behavior: smooth;
  }
  .parcel-form-page .pf-details-shell > .pf-details-heading {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.65rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(15, 23, 42, 0.07);
  }
  .parcel-form-page .pf-field-card {
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid var(--pf-border-subtle, rgba(15, 23, 42, 0.08));
    border-radius: var(--pf-card-radius, 14px);
    padding: var(--pf-space-2, 16px);
    height: 100%;
    box-shadow: var(--pf-soft-shadow);
    transition: border-color var(--pf-transition), box-shadow var(--pf-transition), transform var(--pf-transition);
  }
  .parcel-form-page.pf-saas .pf-field-card:hover {
    border-color: rgba(37, 99, 235, 0.22);
    box-shadow: 0 8px 28px rgba(15, 23, 42, 0.08);
  }
  .parcel-form-page .pf-field-card-title {
    font-family: Poppins, Inter, system-ui, sans-serif;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: var(--pf-muted, #64748b);
    margin: 0 0 var(--pf-space-1, 8px);
    display: flex;
    align-items: center;
    gap: 0.35rem;
  }
  .parcel-form-page .pf-field-card-title i { opacity: 0.85; font-size: 0.85rem; }
  .parcel-form-page .pf-label {
    display: block;
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 0.2rem;
    line-height: 1.2;
  }
  .parcel-form-page .pf-dense .form-control-sm,
  .parcel-form-page .pf-dense .form-select-sm {
    border-radius: 10px;
    border-color: rgba(15, 23, 42, 0.12);
    min-height: var(--pf-input-h, 38px);
    padding: 0.375rem 0.625rem;
    font-size: 0.8125rem;
    transition: border-color var(--pf-transition), box-shadow var(--pf-transition);
  }
  .parcel-form-page.pf-saas .pf-dense .form-control-sm:focus,
  .parcel-form-page.pf-saas .pf-dense .form-select-sm:focus,
  .parcel-form-page.pf-saas .pf-input-group .form-control:focus,
  .parcel-form-page.pf-saas .pf-input-group .form-select:focus {
    border-color: var(--pf-accent);
    box-shadow: 0 0 0 3px var(--pf-accent-ring);
    outline: 0;
  }
  .parcel-form-page.pf-saas .form-select.is-valid,
  .parcel-form-page.pf-saas .form-control.is-valid {
    border-color: var(--bs-success);
  }
  .parcel-form-page.pf-saas .form-select.is-invalid,
  .parcel-form-page.pf-saas .form-control.is-invalid {
    border-color: var(--bs-danger);
  }
  /* Branch row: keep both columns usable on narrow screens */
  .parcel-form-page .pf-branches-row > [class*="col-"] {
    flex: 1 1 0;
    min-width: min(100%, 11rem);
  }
  .parcel-form-page .pf-branches-row .pf-branch-input-group {
    width: 100%;
  }
  .parcel-form-page .pf-branches-row .pf-branch-input-group .form-select {
    width: 100%;
  }
  /* Invoice & date: prevent overlap in narrow columns */
  .parcel-form-page .pf-invoice-date-row > [class*="col-"] {
    min-width: 0;
  }
  /* Floating labels (Bootstrap 5) */
  .parcel-form-page .pf-floating .form-floating > .form-control,
  .parcel-form-page .pf-floating .form-floating > .form-select {
    min-height: 2.5rem;
    height: calc(2.5rem + 2px);
    padding-top: 1rem;
    padding-bottom: 0.35rem;
    border-radius: 12px;
    border-color: rgba(15, 23, 42, 0.12);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
  }
  .parcel-form-page .pf-floating .form-floating > label {
    padding: 0.65rem 0.75rem;
    color: #64748b;
    font-size: 0.8rem;
  }
  .parcel-form-page .pf-floating .form-floating > .form-control:focus ~ label,
  .parcel-form-page .pf-floating .form-floating > .form-control:not(:placeholder-shown) ~ label,
  .parcel-form-page .pf-floating .form-floating > .form-select ~ label {
    color: var(--pf-accent, #4f46e5);
  }
  /* Icon inside input */
  .parcel-form-page .pf-input-icon {
    position: relative;
  }
  .parcel-form-page .pf-input-icon > .bi {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1rem;
    z-index: 1;
    pointer-events: none;
  }
  .parcel-form-page .pf-input-icon > .form-control {
    padding-left: 2.35rem;
    border-radius: 12px;
    min-height: var(--pf-input-h, 2.4rem);
  }
  .parcel-form-page .pf-main-grid { min-width: 0; }
  .parcel-form-page .pf-main-grid > .row > [class*="col-"] { min-width: 0; }
  @media (min-width: 992px) {
    .parcel-form-page .pf-main-grid > .row.pf-main-columns {
      --bs-gutter-x: 0.75rem;
      --bs-gutter-y: 0.75rem;
    }
  }
  .parcel-form-page .pf-col-items .section-card,
  .parcel-form-page .pf-col-items .receipt-box {
    width: 100%;
  }
  /* Status row: use full width, no stray right margin */
  .parcel-form-page .pf-status-row {
    width: 100%;
    margin-left: 0;
    margin-right: 0;
  }
  .parcel-form-page .pf-status-row .col-status { flex: 1 1 0; min-width: 0; max-width: 100%; }
  /* Delivery location: value stays inside field — ellipsis, no overflow misalignment */
  .parcel-form-page .pf-form-sections [class*="col-"] .section-card,
  .parcel-form-page .pf-floating-tight.form-floating {
    min-width: 0;
  }
  .parcel-form-page .pf-floating-tight.form-floating > #deliveryLocationInput.form-control {
    width: 100%;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.35;
  }
  .parcel-form-page #deliveryLocationInput.form-control-sm {
    width: 100%;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.35;
    min-height: var(--pf-input-h, 34px);
    padding: 0.32rem 0.55rem;
    box-sizing: border-box;
  }
  /* Status + Save row: compact native select aligned with other sm inputs */
  .parcel-form-page .pf-status-row .form-label[for="parcelStatusSelect"] {
    margin-bottom: 0.2rem;
    font-size: 0.75rem;
    line-height: 1.2;
  }
  .parcel-form-page .pf-status-row .form-label.opacity-0 {
    margin-bottom: 0.2rem;
    min-height: 0;
    line-height: 1.2;
  }
  .parcel-form-page #parcelStatusSelect.form-select {
    min-height: var(--pf-input-h, 34px);
    padding: 0.22rem 2rem 0.22rem 0.5rem;
    font-size: 0.8125rem;
    line-height: 1.25;
    background-position: right 0.45rem center;
    background-size: 14px 10px;
  }
  @media (min-width: 992px) {
    .parcel-form-page .pf-status-row #parcelStatusSelect.form-select {
      width: auto;
      min-width: 10rem;
      max-width: min(260px, 100%);
    }
    .parcel-form-page .pf-status-row .col-status {
      flex: 0 1 auto;
    }
    .parcel-form-page .pf-status-row > .col-lg-auto.flex-shrink-0 {
      margin-left: auto;
    }
  }
  @media (max-width: 991.98px) {
    .parcel-form-page #parcelStatusSelect.form-select {
      max-width: 100%;
    }
  }
  .parcel-form-page .pf-accordion-soft .accordion-item {
    background: transparent;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 14px !important;
    margin-bottom: 0.5rem;
    overflow: hidden;
  }
  .parcel-form-page .pf-accordion-soft .accordion-button {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #475569;
    padding: 0.5rem 0.85rem;
    background: rgba(248, 250, 252, 0.9);
    box-shadow: none;
  }
  .parcel-form-page .pf-accordion-soft .accordion-button:not(.collapsed) {
    background: rgba(79, 70, 229, 0.08);
    color: var(--pf-accent, #4f46e5);
  }
  .parcel-form-page .pf-accordion-soft .accordion-button::after { filter: opacity(0.65); }
  .parcel-form-page .pf-accordion-soft .accordion-body { padding: 0.65rem 0.85rem 0.85rem; }
  .parcel-form-page .pf-invalid-hint {
    font-size: 0.72rem;
    color: var(--bs-danger);
    margin-top: 0.2rem;
    display: none;
  }
  .parcel-form-page .was-validated .form-control:invalid ~ .pf-invalid-hint,
  .parcel-form-page .was-validated .form-select:invalid ~ .pf-invalid-hint { display: block; }
  .parcel-form-page .pf-input-group .form-control,
  .parcel-form-page .pf-input-group .form-select {
    min-height: 2rem;
    font-size: 0.8125rem;
    padding: 0.22rem 0.5rem;
    border-radius: 8px 0 0 8px;
    border-color: rgba(15, 23, 42, 0.14);
  }
  .parcel-form-page .pf-input-group .form-select {
    border-radius: 8px 0 0 8px;
  }
  .parcel-form-page .pf-input-group > .btn {
    min-width: 2.35rem;
    min-height: 2rem;
    padding: 0.2rem 0.45rem;
    border-radius: 0 8px 8px 0;
    border-color: rgba(15, 23, 42, 0.18);
    color: #475569;
  }
  .parcel-form-page .pf-input-group > .btn:hover {
    background: rgba(37, 99, 235, 0.08);
    border-color: rgba(37, 99, 235, 0.35);
    color: #1d4ed8;
  }
  .parcel-form-page .pf-input-group > .btn:focus-visible {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    z-index: 3;
  }
  .parcel-form-page.pf-saas .pf-input-group .input-group-text {
    border-radius: 12px 0 0 12px;
    border-color: rgba(15, 23, 42, 0.12);
    min-height: var(--pf-input-h, 2.4rem);
    transition: border-color 0.3s ease;
  }
  .parcel-form-page.pf-saas .pf-input-group .form-control.border-start-0 {
    border-radius: 0;
  }
  .parcel-form-page.pf-saas .btn-primary {
    background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
    border: none;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
    transition: transform 0.2s ease, box-shadow 0.3s ease;
  }
  .parcel-form-page.pf-saas .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.45);
  }
  .parcel-form-page .pf-dense .form-control-sm:focus,
  .parcel-form-page .pf-dense .form-select-sm:focus {
    border-color: rgba(37, 99, 235, 0.55);
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.12);
  }
  .parcel-form-page .pf-form-text { font-size: 0.7rem; margin-top: 0.2rem; }
  /* Last bill / same-day — full-width taps on phone; compact inline buttons on desktop */
  .parcel-form-page .pf-lastbill-actions .btn { flex: 0 0 auto; }
  @media (min-width: 768px) {
    .parcel-form-page .pf-lastbill-actions {
      flex-direction: row !important;
      flex-wrap: wrap;
      align-items: center !important;
      gap: 0.5rem 0.75rem !important;
    }
    .parcel-form-page .pf-lastbill-actions .btn { width: auto !important; }
  }
  @media (max-width: 767.98px) {
    .parcel-form-page .pf-lastbill-actions { align-items: stretch !important; }
    .parcel-form-page .pf-lastbill-actions .btn { width: 100% !important; }
    .parcel-form-page .pf-input-group .form-control,
    .parcel-form-page .pf-input-group .form-select { min-height: 2.5rem; }
    .parcel-form-page .pf-input-group > .btn { min-height: 2.5rem; min-width: 2.75rem; }
  }
  .parcel-form-page .pf-visually-hidden-select { position:absolute !important; left:-9999px !important; width:1px !important; height:1px !important; opacity:0 !important; }
  .parcel-form-page .customer-search-results { position: relative; }
  .parcel-form-page .customer-search-results .list-group { position:absolute; z-index: 1050; width:100%; max-height: 260px; overflow:auto; }
  .parcel-form-page .pf-breadcrumb { font-size: 0.9rem; }
  /* Structured sections (left column) */
  .parcel-form-page .pf-form-sections { min-width: 0; }
  .parcel-form-page .pf-form-sections > .section-card { margin-bottom: 0; }
  .parcel-form-page .pf-page-hero h1 {
    font-family: Poppins, Inter, system-ui, sans-serif;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: #0f172a;
  }
  .parcel-form-page .pf-aux-banner {
    border: 1px solid rgba(37, 99, 235, 0.22);
    border-radius: var(--pf-card-radius);
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.06), rgba(99, 102, 241, 0.04));
    box-shadow: var(--pf-soft-shadow);
  }
  .parcel-form-page .pf-aux-banner .card-body { padding: 0.65rem 1rem; }
  .parcel-form-page .pf-btn-save {
    border-radius: 12px !important;
    font-weight: 600;
    padding: 0.4rem 1rem !important;
    min-height: 2.5rem;
  }
  .parcel-form-page .pf-btn-add-row {
    border-radius: 12px !important;
    font-weight: 600;
    padding: 0.45rem 1.1rem !important;
    box-shadow: 0 4px 14px rgba(79, 70, 229, 0.28);
    transition: transform 0.2s ease, box-shadow 0.25s ease, filter 0.2s ease;
  }
  /* Items section: compact primary “Add Row” (same #addRow / classes) */
  .parcel-form-page.pf-saas .pf-items-section .pf-btn-add-row {
    border-radius: 8px !important;
    padding: 0.32rem 0.85rem !important;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 2px 8px rgba(79, 70, 229, 0.2);
  }
  .parcel-form-page.pf-saas .pf-items-section .pf-items-scroll + .text-end {
    padding-left: 0.25rem;
    padding-right: 0.25rem;
  }
  .parcel-form-page.pf-saas .pf-btn-save:hover,
  .parcel-form-page.pf-saas .pf-btn-add-row:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(79, 70, 229, 0.38);
  }
  @media (prefers-reduced-motion: reduce) {
    .parcel-form-page.pf-saas .pf-btn-save:hover,
    .parcel-form-page.pf-saas .pf-btn-add-row:hover {
      transform: none;
    }
  }
  .parcel-form-page .pf-floating-tight.form-floating > .form-control,
  .parcel-form-page .pf-floating-tight.form-floating > .form-select {
    min-height: calc(var(--pf-input-h) + 2px);
    height: calc(var(--pf-input-h) + 2px);
    padding-top: 1.1rem;
    padding-bottom: 0.2rem;
    font-size: 0.8125rem;
  }
  .parcel-form-page .pf-floating-tight.form-floating > label {
    font-size: 0.75rem;
    padding: 0.55rem 0.65rem;
  }
  /* Choices.js — match compact parcel inputs (global script enhances selects) */
  .parcel-form-page .choices { margin-bottom: 0; font-size: 0.8125rem; }
  .parcel-form-page .choices__inner {
    min-height: var(--pf-input-h, 38px);
    padding: 0.25rem 0.5rem;
    border-radius: 10px;
    border-color: rgba(15, 23, 42, 0.12);
    background-color: #fff;
    transition: border-color var(--pf-transition), box-shadow var(--pf-transition);
  }
  .parcel-form-page .choices.is-focused .choices__inner,
  .parcel-form-page .choices:focus-within .choices__inner {
    border-color: var(--pf-accent);
    box-shadow: 0 0 0 3px var(--pf-accent-ring);
  }
  .parcel-form-page .choices[data-type*="select-one"] .choices__button { display: none; }
  /* Branch selects: Choices.js search for compact, mobile-friendly selection */
  .parcel-form-page .pf-branch-input-group {
    overflow: visible;
    width: 100%;
  }
  .parcel-form-page .pf-branches-section .pf-branch-input-group .form-select {
    width: 100%;
    min-height: var(--pf-input-h, 38px);
  }
  .parcel-form-page .pf-branches-section .form-select.is-invalid {
    border-color: var(--bs-danger);
  }
  @media (max-width: 575.98px) {
    .parcel-form-page .pf-branches-section .pf-branch-input-group .form-select {
      min-height: 44px;
    }
  }
  .parcel-form-page .pf-card { border: var(--pf-border); border-radius: 12px; box-shadow: 0 8px 20px rgba(2,6,23,.06); background: #fff; overflow:hidden; }
  .parcel-form-page .pf-card-h { padding: 0.85rem 1rem; background: linear-gradient(180deg, rgba(248,250,252,1), rgba(255,255,255,1)); border-bottom: var(--pf-border); font-weight: 700; }
  .parcel-form-page .pf-card-b { padding: 1rem; }
  .parcel-form-page .pf-field .form-text { margin-top: .25rem; }
  .parcel-form-page .pf-actions-desktop { position: sticky; top: .75rem; z-index: 20; }
  .parcel-form-page .pf-sticky-actions {
    position: fixed; left: 0; right: 0; bottom: 0; z-index: 1055;
    background: rgba(255,255,255,.88); backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-top: 1px solid rgba(17,24,39,.08);
    box-shadow: 0 -8px 32px rgba(15, 23, 42, 0.08);
    padding: .65rem .75rem;
    padding-bottom: max(0.65rem, env(safe-area-inset-bottom));
    transition: background 0.3s ease;
  }
  .parcel-form-page .pf-sticky-actions-inner {
    width: 100%;
    max-width: 100%;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: stretch;
  }
  .parcel-form-page .pf-sticky-actions .btn {
    border-radius: 12px;
    min-height: 2.75rem;
    width: 100%;
  }
  /* Sticky bar only renders below lg (d-lg-none); tablet can use side-by-side, phone stacked */
  @media (min-width: 768px) and (max-width: 991.98px) {
    .parcel-form-page .pf-sticky-actions-inner {
      flex-direction: row;
    }
    .parcel-form-page .pf-sticky-actions .btn {
      flex: 1 1 50%;
    }
  }
  .parcel-form-page .pf-toast-wrap { position: fixed; top: 1rem; right: 1rem; z-index: 1100; width: min(420px, calc(100vw - 2rem)); }
  /* Last-bill modal: full screen on phones, comfortable on desktop */
  .parcel-form-page .clb-modal .modal-content { border: 0; border-radius: 1rem; box-shadow: 0 18px 50px rgba(2,6,23,.12); overflow: hidden; }
  .parcel-form-page .clb-modal .modal-header { border-bottom: 1px solid var(--bs-border-color-translucent); }
  .parcel-form-page .clb-modal .modal-footer { border-top: 1px solid var(--bs-border-color-translucent); }
  .parcel-form-page .clb-iframe-wrap { min-height: clamp(220px, 52vh, 640px); }
  /* One-screen dense layout */
  .parcel-form-page.pf-compact-view { font-size: 0.8125rem; line-height: 1.35; }
  .parcel-form-page.pf-compact-view .pf-breadcrumb { font-size: 0.78rem; margin-bottom: 0.25rem !important; }
  .parcel-form-page.pf-compact-view .page-header { margin-bottom: 0.35rem !important; padding-bottom: 0.35rem !important; }
  .parcel-form-page.pf-compact-view .page-header .h4 { font-size: 1.05rem; }
  .parcel-form-page.pf-compact-view .page-header .text-muted.small { display: none; }
  .parcel-form-page.pf-compact-view .section-card { margin-bottom: 0.45rem; }
  .parcel-form-page.pf-compact-view .section-card .section-title { padding: 0.3rem 0.55rem; font-size: 0.82rem; }
  .parcel-form-page.pf-compact-view .section-card .section-body { padding: 0.4rem 0.5rem; }
  .parcel-form-page.pf-compact-view .pf-details-grid { --bs-gutter-y: 0.35rem; --bs-gutter-x: 0.35rem; }
  .parcel-form-page.pf-compact-view .form-label { font-size: 0.75rem; margin-bottom: 0.08rem; }
  .parcel-form-page.pf-compact-view .form-control-sm,
  .parcel-form-page.pf-compact-view .form-select-sm { min-height: 1.55rem; padding-top: 0.2rem; padding-bottom: 0.2rem; font-size: 0.78rem; }
  .parcel-form-page.pf-compact-view .btn-sm { padding: 0.15rem 0.45rem; font-size: 0.75rem; }
  .parcel-form-page.pf-compact-view .btn-quick { padding: 0.1rem 0.35rem; font-size: 0.72rem; }
  .parcel-form-page.pf-compact-view .receipt-header { padding: 0.35rem 0.5rem; }
  .parcel-form-page.pf-compact-view .serial-badge { padding: 0.15rem 0.35rem; }
  .parcel-form-page.pf-compact-view .receipt-grid th,
  .parcel-form-page.pf-compact-view .receipt-grid td { padding: 0.25rem 0.35rem; font-size: 0.75rem; }
  .parcel-form-page.pf-compact-view .receipt-grid .form-control { min-height: 1.45rem; font-size: 0.78rem; padding: 0.15rem 0.35rem; }
  .parcel-form-page.pf-compact-view .receipt-total { padding: 0.4rem 0.55rem !important; }
  .parcel-form-page.pf-compact-view .receipt-total .fs-5 { font-size: 1rem !important; }
  .parcel-form-page.pf-compact-view .pf-items-scroll {
    max-height: min(220px, 32vh);
    overflow-y: auto;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: 0.25rem;
  }
  .parcel-form-page.pf-compact-view .pf-items-scroll .table { margin-bottom: 0; }
  @media (min-width: 1200px) {
    .parcel-form-page.pf-compact-view .pf-items-scroll { max-height: min(260px, 36vh); }
  }
  /* Small screens: line items stay a real table — pan .pf-items-scroll horizontally (no card/stack transforms) */
  @media (max-width: 767.98px) {
    .parcel-form-page .row.pf-main-columns {
      margin-left: 0 !important;
      margin-right: 0 !important;
      --bs-gutter-x: 0.65rem;
    }
    .parcel-form-page .pf-col-items,
    .parcel-form-page .pf-items-section,
    .parcel-form-page .pf-items-section .receipt-box,
    .parcel-form-page .pf-items-section .section-card {
      width: 100% !important;
      max-width: 100% !important;
      margin-left: 0 !important;
      margin-right: 0 !important;
    }
    .parcel-form-page .pf-page-wrap.pf-form-inner-fluid,
    .parcel-form-page #parcelForm > .container-fluid.pf-form-inner-fluid {
      padding-left: max(0.5rem, env(safe-area-inset-left)) !important;
      padding-right: max(0.5rem, env(safe-area-inset-right)) !important;
    }
    .parcel-form-page .pf-receipt-summary {
      grid-template-columns: 1fr !important;
    }
    .parcel-form-page .pf-items-section .text-end.mb-1 {
      text-align: center !important;
    }
    .parcel-form-page .pf-items-section #addRow {
      width: 100% !important;
    }
    .parcel-form-page .pf-form-sections .form-control-sm,
    .parcel-form-page .pf-form-sections .form-select-sm,
    .parcel-form-page .pf-form-sections .pf-input-group .form-control,
    .parcel-form-page .pf-form-sections .pf-input-group .form-select {
      min-height: 44px;
    }
    .parcel-form-page #parcelStatusSelect.form-select {
      min-height: 44px;
    }
    .parcel-form-page .pf-form-sections .section-card .section-body {
      padding: 0.65rem !important;
    }
  }
  @media (max-width: 576px) {
    .parcel-form-page .clb-modal .modal-body { padding: 0.75rem; }
    .parcel-form-page .page-header { flex-direction: column; align-items: stretch !important; gap: 0.75rem; }
    .parcel-form-page .page-header .d-flex.gap-2 { width: 100%; }
    .parcel-form-page .page-header .d-flex.gap-2 .btn { flex: 1 1 auto; }
    .parcel-form-page .section-card .section-body { padding: 0.65rem; }
    .parcel-form-page .receipt-header { flex-direction: column; align-items: stretch; }
    .parcel-form-page .receipt-header .serial-badge { width: 100%; justify-content: space-between; }
    .parcel-form-page #serialInput { max-width: 100% !important; width: 100%; }
    .parcel-form-page .receipt-grid th, .parcel-form-page .receipt-grid td { padding: 0.35rem 0.4rem; font-size: 0.85rem; }
    .parcel-form-page .receipt-grid .form-control, .parcel-form-page .receipt-grid .form-control-sm { min-height: 1.85rem; font-size: 0.9rem; }
  }

  /* —— Parcel form: dense dashboard layout (CSS only; no input/JS/PHP logic changes) —— */
  .parcel-form-page.pf-saas.pf-layout-optimized {
    --pf-space-1: 6px;
    --pf-space-2: 10px;
    --pf-space-3: 18px;
    --pf-card-pad: 10px;
    --pf-input-h: 34px;
    --pf-soft-shadow: 0 2px 16px rgba(15, 23, 42, 0.05), 0 1px 2px rgba(15, 23, 42, 0.04);
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-page-wrap {
    padding-left: max(0.35rem, env(safe-area-inset-left));
    padding-right: max(0.35rem, env(safe-area-inset-right));
  }
  @media (max-width: 991.98px) {
    .parcel-form-page.pf-saas.pf-layout-optimized .pf-page-wrap {
      padding-bottom: max(4.5rem, calc(0.5rem + env(safe-area-inset-bottom)));
    }
  }
  @media (min-width: 992px) {
    .parcel-form-page.pf-saas.pf-layout-optimized .pf-page-wrap {
      padding-bottom: 0.25rem;
    }
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-breadcrumb {
    margin-bottom: 0.3rem !important;
    padding-top: 0.15rem;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .page-header {
    margin-bottom: 0.45rem !important;
    padding: 0.5rem 0.65rem !important;
    gap: 0.5rem !important;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .page-header .h4,
  .parcel-form-page.pf-saas.pf-layout-optimized .page-header h1.h4 {
    font-size: 1.05rem;
    line-height: 1.25;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .page-header .text-muted.small {
    margin-top: 0.15rem !important;
    font-size: 0.72rem;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-aux-banner {
    margin-bottom: 0.45rem !important;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-aux-banner .card-body {
    padding: 0.45rem 0.65rem !important;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .section-card {
    margin-bottom: 0.45rem !important;
    border-radius: clamp(10px, 1vw, 14px);
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .section-card .section-title {
    font-size: 0.8rem;
    padding: 0.4rem 0.65rem;
    line-height: 1.2;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .section-card .section-body {
    padding: var(--pf-card-pad) !important;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .section-card .section-body.pt-2,
  .parcel-form-page.pf-saas.pf-layout-optimized .section-card .section-body.pt-lg-3 {
    padding-top: 0.5rem !important;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-form-sections.d-flex.flex-column {
    gap: 0.45rem !important;
  }
  @media (min-width: 992px) {
    .parcel-form-page.pf-saas.pf-layout-optimized .pf-form-sections.d-flex.flex-column {
      gap: 0.55rem !important;
    }
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-form-sections > .row {
    --bs-gutter-x: 0.4rem;
    --bs-gutter-y: 0.4rem;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-label {
    margin-bottom: 0.12rem;
    font-size: 0.68rem;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .form-label {
    margin-bottom: 0.12rem;
    font-size: 0.78rem;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-form-sections .form-check.mt-1 {
    margin-top: 0.25rem !important;
  }
  /* Desktop / tablet lg+: balanced two-column grid — details left, items right */
  @media (min-width: 992px) {
    .parcel-form-page.pf-saas.pf-layout-optimized .row.pf-main-columns {
      display: grid !important;
      grid-template-columns: minmax(0, 0.96fr) minmax(0, 1.08fr);
      gap: 0.5rem;
      align-items: start;
      margin-left: 0 !important;
      margin-right: 0 !important;
      --bs-gutter-x: 0;
      --bs-gutter-y: 0;
    }
    .parcel-form-page.pf-saas.pf-layout-optimized .row.pf-main-columns > [class*="col-"] {
      width: 100% !important;
      max-width: none !important;
      flex: none !important;
      padding-left: 0 !important;
      padding-right: 0 !important;
    }
  }
  @media (min-width: 1200px) {
    .parcel-form-page.pf-saas.pf-layout-optimized .row.pf-main-columns {
      grid-template-columns: minmax(0, 0.94fr) minmax(0, 1.12fr);
      gap: 0.65rem;
    }
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-col-items {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized #billPreview {
    margin-bottom: 0.45rem !important;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-items-section {
    margin-top: 0 !important;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .receipt-header {
    padding: 0.45rem 0.65rem !important;
    gap: 0.35rem !important;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .receipt-header .small {
    font-size: 0.75rem;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-receipt-summary {
    font-size: 0.72rem;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-receipt-summary .mt-1 {
    margin-top: 0.15rem !important;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .receipt-box > .pf-receipt-summary-wrap {
    padding: 0.35rem 0.5rem !important;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-items-section .p-2.pt-1 {
    padding: 0.45rem !important;
    padding-top: 0.35rem !important;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .receipt-total {
    padding: 0.45rem 0.65rem !important;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .receipt-total .fs-5 {
    font-size: 1.1rem !important;
  }
  /* Items table: use remaining viewport height on large screens */
  @media (min-width: 992px) {
    .parcel-form-page.pf-saas.pf-layout-optimized .pf-items-scroll {
      max-height: clamp(160px, calc(100vh - 300px), 480px);
    }
  }
  @media (min-width: 1200px) {
    .parcel-form-page.pf-saas.pf-layout-optimized .pf-items-scroll {
      max-height: clamp(180px, calc(100vh - 280px), 520px);
    }
  }
  /* Sticky status + Save on desktop (direct child .container-fluid is the status/actions strip only) */
  @media (min-width: 992px) {
    .parcel-form-page.pf-saas.pf-layout-optimized #parcelForm > .container-fluid.px-0 {
      position: sticky;
      bottom: 0;
      z-index: 30;
      padding-top: 0.25rem;
      margin-top: 0.25rem;
      background: linear-gradient(180deg, rgba(238, 242, 247, 0), rgba(238, 242, 247, 0.88) 18%);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }
    .parcel-form-page.pf-saas.pf-layout-optimized #parcelForm > .container-fluid.px-0 .section-card {
      margin-bottom: 0 !important;
    }
    .parcel-form-page.pf-saas.pf-layout-optimized #parcelForm > .container-fluid.px-0 .section-body {
      padding-top: 0.45rem !important;
      padding-bottom: 0.45rem !important;
    }
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-btn-save {
    min-height: 2.15rem !important;
    padding: 0.3rem 0.75rem !important;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-sticky-actions {
    padding: 0.5rem 0.65rem;
  }
  .parcel-form-page.pf-saas.pf-layout-optimized .pf-sticky-actions .btn {
    min-height: 2.35rem;
  }
  /* Tablet: slightly smaller type */
  @media (min-width: 576px) and (max-width: 991.98px) {
    .parcel-form-page.pf-saas.pf-layout-optimized {
      font-size: 0.8125rem;
    }
    .parcel-form-page.pf-saas.pf-layout-optimized .section-card .section-title {
      font-size: 0.78rem;
    }
  }
  /* Mobile: tighter stacks */
  @media (max-width: 575.98px) {
    .parcel-form-page.pf-saas.pf-layout-optimized .page-header {
      gap: 0.45rem !important;
    }
    .parcel-form-page.pf-saas.pf-layout-optimized .section-card .section-body {
      padding: 0.5rem 0.55rem !important;
    }
    .parcel-form-page.pf-saas.pf-layout-optimized .pf-form-sections .section-card .section-body {
      padding: 0.5rem !important;
    }
  }

  /* ---------------------------------------------------------------------------
     Mobile one-screen: fill viewport below topbar; single scroll region; fixed Save/Reset
     --------------------------------------------------------------------------- */
  @media (max-width: 767.98px) {
    :root {
      --pf-one-topbar-h: 52px;
      --pf-one-sticky-h: 112px;
    }
    html:has(.parcel-form-page.pf-one-screen) {
      height: 100%;
      overflow: hidden;
    }
    body:has(.parcel-form-page.pf-one-screen) {
      overflow: hidden !important;
      height: 100%;
    }
    .app-shell:has(.parcel-form-page.pf-one-screen) {
      min-height: 100dvh;
    }
    main.content-wrapper:has(.parcel-form-page.pf-one-screen) {
      display: flex;
      flex-direction: column;
      height: 100dvh;
      max-height: 100dvh;
      min-height: 0;
      overflow: hidden;
    }
    main.content-wrapper:has(.parcel-form-page.pf-one-screen) > .container-fluid.parcels-main-fluid {
      flex: 1 1 auto;
      min-height: 0;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      margin-top: 0 !important;
      padding-top: 0.25rem !important;
      padding-bottom: 0 !important;
    }
    .parcel-form-page.pf-one-screen {
      flex: 1 1 auto;
      min-height: 0;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      background: linear-gradient(165deg, #eef2f7 0%, #e8ecf4 40%, #f4f6fa 100%);
      font-size: 13px;
      --pf-card-radius: 10px;
      --pf-space-1: 8px;
      --pf-space-2: 10px;
      --pf-input-h: 36px;
    }
    .parcel-form-page.pf-one-screen .pf-page-wrap {
      flex: 1 1 auto;
      min-height: 0;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      padding-bottom: 0 !important;
    }
    .parcel-form-page.pf-one-screen .pf-breadcrumb {
      padding: 0.15rem 0;
      margin-bottom: 0.25rem !important;
      font-size: 0.75rem;
    }
    .parcel-form-page.pf-one-screen .page-header {
      margin-bottom: 0.35rem !important;
      padding: 0.45rem 0.55rem !important;
      flex-shrink: 0;
    }
    .parcel-form-page.pf-one-screen .page-header .h4,
    .parcel-form-page.pf-one-screen .page-header h1.h4 {
      font-size: 1rem;
    }
    .parcel-form-page.pf-one-screen .pf-aux-banner {
      margin-bottom: 0.35rem !important;
    }
    .parcel-form-page.pf-one-screen .pf-aux-banner .card-body {
      padding: 0.35rem 0.5rem !important;
    }
    .parcel-form-page.pf-one-screen .alert {
      padding: 0.35rem 0.5rem !important;
      margin-bottom: 0.35rem !important;
      font-size: 0.8rem;
    }
    .parcel-form-page.pf-one-screen .pf-one-screen-fill {
      flex: 1 1 auto;
      min-height: 0;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .parcel-form-page.pf-one-screen .pf-one-screen-form {
      flex: 1 1 auto;
      min-height: 0;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .parcel-form-page.pf-one-screen .pf-one-screen-form > .d-none {
      min-height: 0;
    }
    .parcel-form-page.pf-one-screen .pf-form-scroll-region {
      flex: 1 1 auto;
      min-height: 0;
      overflow-y: auto;
      overflow-x: hidden;
      -webkit-overflow-scrolling: touch;
      overscroll-behavior-y: contain;
      padding-bottom: calc(var(--pf-one-sticky-h) + env(safe-area-inset-bottom, 0px));
    }
    .parcel-form-page.pf-one-screen .page-header #pfSaveBtnTop {
      display: none;
    }
    .parcel-form-page.pf-one-screen .pf-sticky-actions {
      position: fixed;
      left: 0;
      right: 0;
      bottom: 0;
      z-index: 1055;
      padding-left: max(0.5rem, env(safe-area-inset-left));
      padding-right: max(0.5rem, env(safe-area-inset-right));
      padding-bottom: max(0.45rem, env(safe-area-inset-bottom));
      box-shadow: 0 -4px 20px rgba(15, 23, 42, 0.1);
    }
    .parcel-form-page.pf-one-screen .section-card {
      margin-bottom: 0.4rem !important;
    }
    .parcel-form-page.pf-one-screen .section-card .section-title {
      padding: 0.4rem 0.6rem !important;
      font-size: 0.72rem !important;
    }
    .parcel-form-page.pf-one-screen .section-card .section-body {
      padding: 0.45rem 0.55rem !important;
    }
    .parcel-form-page.pf-one-screen .pf-dense .form-control-sm,
    .parcel-form-page.pf-one-screen .pf-dense .form-select-sm {
      min-height: 36px !important;
      padding: 0.25rem 0.45rem !important;
      font-size: 13px !important;
      border-radius: 8px !important;
    }
    .parcel-form-page.pf-one-screen .pf-label {
      font-size: 0.65rem !important;
      margin-bottom: 0.1rem !important;
    }
    .parcel-form-page.pf-one-screen .pf-form-sections.d-flex.flex-column {
      gap: 0.4rem !important;
    }
    .parcel-form-page.pf-one-screen .row.g-2,
    .parcel-form-page.pf-one-screen .row.g-2.g-lg-3 {
      --bs-gutter-y: 0.4rem;
      --bs-gutter-x: 0.45rem;
    }
    /* Items: cap height so Add Row + total stay reachable; inner scroll for table */
    .parcel-form-page.pf-one-screen .pf-items-scroll {
      max-height: min(28dvh, 200px) !important;
      flex: 0 0 auto;
      overflow-x: auto;
      overflow-y: auto;
    }
    .parcel-form-page.pf-one-screen .pf-items-section .text-end.mb-1 {
      flex-shrink: 0;
    }
    .parcel-form-page.pf-one-screen .receipt-total {
      padding: 0.4rem 0.5rem !important;
    }
    .parcel-form-page.pf-one-screen .receipt-total .fs-5 {
      font-size: 1rem !important;
    }
    .parcel-form-page.pf-one-screen #parcelStatusSelect.form-select {
      min-height: 36px;
      font-size: 13px;
    }
    .parcel-form-page.pf-one-screen.pf-saas.pf-layout-optimized .pf-page-wrap {
      padding-bottom: 0 !important;
    }
  }
  @media (min-width: 768px) {
    .parcel-form-page .pf-form-scroll-region {
      display: contents;
    }
    html:has(.parcel-form-page.pf-one-screen),
    body:has(.parcel-form-page.pf-one-screen) {
      height: auto !important;
      overflow: auto !important;
    }
    main.content-wrapper:has(.parcel-form-page.pf-one-screen) {
      height: auto !important;
      max-height: none !important;
      overflow: visible !important;
    }
    main.content-wrapper:has(.parcel-form-page.pf-one-screen) > .container-fluid.parcels-main-fluid {
      overflow: visible !important;
    }
    .parcel-form-page.pf-one-screen {
      flex: initial !important;
      min-height: 0 !important;
      overflow: visible !important;
    }
    .parcel-form-page.pf-one-screen .pf-page-wrap,
    .parcel-form-page.pf-one-screen .pf-one-screen-fill,
    .parcel-form-page.pf-one-screen .pf-one-screen-form {
      flex: initial !important;
      min-height: 0 !important;
      overflow: visible !important;
    }
    .parcel-form-page.pf-one-screen .page-header #pfSaveBtnTop {
      display: inline-flex;
    }
  }
</style>
<div class="parcel-form-page pf-saas pf-layout-optimized pf-one-screen">
<div class="container-fluid px-1 px-sm-2 px-lg-2 pf-page-wrap pf-form-inner-fluid">

<?php 
  $isEdit = (int)($parcel['id'] ?? 0) > 0; 
  $policy = $policy ?? ['priceOnly'=>false,'lockAll'=>false,'canEnterItemAmounts'=>false,'statusOnlyEdit'=>false];
  $priceOnly = !empty($policy['priceOnly']);
  $lockAll = !empty($policy['lockAll']);
  $canEnterItemAmounts = !empty($policy['canEnterItemAmounts']);
  $statusOnlyEdit = !empty($policy['statusOnlyEdit']);
  // Safety: ensure the status select never renders with an empty value.
  $parcelStatus = trim((string)($parcel['status'] ?? ''));
  $parcelStatusMap = Helpers::parcelStatusMap();
  if ($parcelStatus === '' || !isset($parcelStatusMap[$parcelStatus])) {
    $parcel['status'] = 'pending';
  }
  $branchesList = array_values($branchesAll ?? []);
?>
<nav class="pf-breadcrumb mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb mb-0">
    <li class="breadcrumb-item"><a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=dashboard'); ?>">Dashboard</a></li>
    <li class="breadcrumb-item"><a class="text-decoration-none" href="<?php echo Helpers::baseUrl('index.php?page=parcels'); ?>">Parcels</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?php echo $parcel['id'] ? 'Edit' : 'New'; ?></li>
  </ol>
</nav>
<header class="page-header pf-page-hero d-flex flex-wrap justify-content-between align-items-center gap-2">
  <div>
    <h1 class="h4 mb-0"><?php echo $parcel['id'] ? 'Edit Parcel' : 'New Parcel'; ?></h1>
    <div class="text-muted small d-none d-sm-block mt-1">Fast entry • consistent billing • logistics workflow</div>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a href="<?php echo Helpers::baseUrl('index.php?page=parcels'); ?>" class="btn btn-outline-secondary btn-sm rounded-3"><i class="bi bi-arrow-left" aria-hidden="true"></i><span class="d-none d-md-inline ms-1">Back to Parcels</span></a>
    <button type="submit" form="parcelForm" class="btn btn-primary btn-sm pf-btn-save" id="pfSaveBtnTop"><i class="bi bi-save" aria-hidden="true"></i><span class="d-none d-md-inline ms-1">Save Parcel</span></button>
  </div>
</header>

<?php
  // New parcel: show add options when we have prefilled data (same-day / add more) or when we have a last bill
  $isNewPrefilled = empty($parcel['id']) && (int)($parcel['customer_id'] ?? 0) > 0 && ((int)($parcel['from_branch_id'] ?? 0) > 0 || (int)($parcel['to_branch_id'] ?? 0) > 0 || !empty($parcel['created_at']));
  $sameDayDateNew = $isNewPrefilled ? substr((string)($parcel['created_at'] ?? date('Y-m-d')), 0, 10) : '';
  $sameDayUrlNew = $isNewPrefilled ? Helpers::baseUrl('index.php?page=parcels&action=new'
    . '&customer_id='.(int)($parcel['customer_id'] ?? 0)
    . '&vehicle_no='.urlencode((string)($parcel['vehicle_no'] ?? ''))
    . '&from_branch_id='.(int)($parcel['from_branch_id'] ?? 0)
    . '&to_branch_id='.(int)($parcel['to_branch_id'] ?? 0)
    . '&date='.urlencode($sameDayDateNew)) : '';
?>
<?php if (empty($parcel['id']) && ($isNewPrefilled || !empty($lastParcel))): ?>
<div class="card mb-2 pf-aux-banner shadow-sm">
  <div class="card-body py-2 px-3">
    <div class="d-flex flex-column flex-md-row flex-wrap align-items-stretch align-items-md-center gap-2 pf-lastbill-actions">
      <?php if ($isNewPrefilled && $sameDayUrlNew !== ''): ?>
      <span class="fw-semibold text-secondary small">Same day bill:</span>
      <a href="<?php echo $sameDayUrlNew; ?>" class="btn btn-sm btn-primary w-100 w-md-auto"><i class="bi bi-plus-circle me-1"></i> Add new parcel (same bill)</a>
      <?php endif; ?>
      <?php if (!empty($lastParcel)): ?>
      <?php if ($isNewPrefilled && $sameDayUrlNew !== ''): ?><span class="text-secondary d-none d-md-inline mx-1">|</span><?php endif; ?>
      <span class="fw-semibold text-secondary small">Last bill:</span>
      <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=edit&id='.(int)$lastParcel['id']); ?>" class="btn btn-sm btn-outline-primary w-100 w-md-auto"><i class="bi bi-pencil-square me-1"></i> Open last bill #<?php echo (int)$lastParcel['id']; ?></a>
      <a href="<?php echo Helpers::baseUrl('index.php?page=parcels&action=new&customer_id='.(int)$lastParcel['customer_id'].'&vehicle_no='.urlencode((string)($lastParcel['vehicle_no'] ?? '')).'&from_branch_id='.(int)$lastParcel['from_branch_id'].'&to_branch_id='.(int)$lastParcel['to_branch_id'].'&date='.urlencode(substr((string)($lastParcel['created_at'] ?? date('Y-m-d')),0,10))); ?>" class="btn btn-sm btn-outline-primary w-100 w-md-auto"><i class="bi bi-plus-circle me-1"></i> Add more parcel</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($isEdit && !empty($parcel['id']) && !$statusOnlyEdit): ?>
<?php
  $sameDayDate = substr((string)($parcel['created_at'] ?? date('Y-m-d')), 0, 10);
  $sameDayUrl = Helpers::baseUrl('index.php?page=parcels&action=new'
    . '&customer_id='.(int)($parcel['customer_id'] ?? 0)
    . '&vehicle_no='.urlencode((string)($parcel['vehicle_no'] ?? ''))
    . '&from_branch_id='.(int)($parcel['from_branch_id'] ?? 0)
    . '&to_branch_id='.(int)($parcel['to_branch_id'] ?? 0)
    . '&date='.urlencode($sameDayDate));
?>
<div class="card mb-2 pf-aux-banner shadow-sm">
  <div class="card-body py-2 px-3">
    <div class="d-flex flex-column flex-md-row flex-wrap align-items-stretch align-items-md-center gap-2 pf-lastbill-actions">
      <span class="fw-semibold text-secondary small">Same day bill:</span>
      <a href="<?php echo $sameDayUrl; ?>" class="btn btn-sm btn-primary w-100 w-md-auto"><i class="bi bi-plus-circle me-1"></i> Add new parcel (same bill)</a>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($statusOnlyEdit): ?>
  <div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i> Parcel is In Transit. Only status can be changed.</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_parcel_saved'])): ?>
  <?php 
    $flash = $_SESSION['flash_parcel_saved']; 
    // Resolve customer name/phone from provided list
    $custName = 'Customer #'.(int)($flash['customer_id'] ?? 0);
    $custPhone = '';
    if (!empty($customersAll)) {
      foreach ($customersAll as $c) {
        if ((int)$c['id'] === (int)$flash['customer_id']) { $custName = (string)$c['name']; $custPhone = (string)($c['phone'] ?? ''); break; }
      }
    }
    $veh = trim((string)($flash['vehicle_no'] ?? ''));
    $msg = 'Saved Parcel #'.(int)($flash['id'] ?? 0).' for ' . htmlspecialchars($custName);
    if ($custPhone !== '') { $msg .= ' ('.htmlspecialchars($custPhone).')'; }
    if ($veh !== '') { $msg .= ' — Vehicle: '.htmlspecialchars($veh); }
  ?>
  <div class="alert alert-success py-2">
    <?php echo $msg; ?>
  </div>
  <?php unset($_SESSION['flash_parcel_saved']); ?>
<?php endif; ?>

<?php
  $showBillPrompt = isset($_GET['prompt_bill']) && (int)($_GET['prompt_bill'] ?? 0) === 1 && !empty($_SESSION['flash_bill_prompt']);
  $billPrompt = $showBillPrompt ? $_SESSION['flash_bill_prompt'] : null;
  if ($showBillPrompt) { unset($_SESSION['flash_bill_prompt']); }
?>
<?php if ($showBillPrompt && !empty($billPrompt['customer_id'])): ?>
  <?php
    $dnUrl = Helpers::baseUrl('index.php?page=delivery_notes&action=generate'
      . '&customer_id='.(int)$billPrompt['customer_id']
      . '&delivery_date='.urlencode((string)($billPrompt['date'] ?? date('Y-m-d'))));
  ?>
  <div class="modal fade" id="billPromptModal" tabindex="-1" aria-labelledby="billPromptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="billPromptModalLabel">Create new bill?</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Parcel status is now <strong>In Transit</strong>. Do you want to generate a new delivery note (bill) for this customer?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Not now</button>
          <a class="btn btn-primary" href="<?php echo $dnUrl; ?>">Create Bill</a>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if (isset($_GET['duplicate']) && (int)$_GET['duplicate'] > 0): ?>
  <div class="alert alert-warning alert-dismissible fade show mb-2 py-2" role="alert">
    <i class="bi bi-exclamation-triangle"></i> Duplicate entry prevented. A similar parcel (#<?php echo (int)$_GET['duplicate']; ?>) was created recently. Please check if this is the same parcel.
  </div>
<?php endif; ?>

<div class="pf-toast-wrap" id="pfToastWrap" aria-live="polite" aria-atomic="true"></div>

<div class="pf-one-screen-fill">
<form method="post" id="parcelForm" class="needs-validation pf-one-screen-form" novalidate action="<?php echo Helpers::baseUrl('index.php?page=parcels&action=save'); ?>" autocomplete="off">
  <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
  <input type="hidden" name="id" value="<?php echo (int)$parcel['id']; ?>">
  <?php if ($statusOnlyEdit): ?><input type="hidden" name="status_only_edit" value="1"><?php endif; ?>
  <input type="hidden" name="idempotency_key" value="<?php echo bin2hex(random_bytes(16)); ?>">

  <div class="pf-form-scroll-region">
  <div class="<?php echo $statusOnlyEdit ? 'd-none' : ''; ?>">
  <div class="container-fluid px-0 pf-form-inner-fluid">
    <div class="row g-2 g-lg-3 align-items-start pf-main-columns">
      <div class="col-12 col-lg-6 col-xl-6 pf-animate pf-animate-in">
  <div class="pf-form-sections d-flex flex-column gap-2 gap-lg-3 mb-2 mb-lg-0 pf-dense pf-floating">
    <!-- Customer -->
    <section class="section-card pf-customer-stack" role="region" aria-labelledby="pf-h-customer">
      <div class="section-title" id="pf-h-customer"><i class="bi bi-person-badge me-2 text-primary" aria-hidden="true"></i>Customer</div>
      <div class="section-body pt-2 pt-lg-3">
          <div class="row g-2 align-items-start pf-customer-row">
            <div class="col-12 col-lg-4">
              <label class="pf-label" for="customerSelectHidden">Select customer</label>
              <select name="customer_id" id="customerSelectHidden" class="form-select form-select-sm" required <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> data-enhance="false" aria-invalid="false" aria-describedby="customerInvalidFeedback">
        <option value="" disabled hidden <?php echo ((int)($parcel['customer_id'] ?? 0) <= 0) ? 'selected' : ''; ?>>-- Select Customer --</option>
        <?php foreach (($customersAll ?? []) as $c): ?>
          <?php 
            $nm = (string)($c['name'] ?? '');
            $phRaw = trim((string)($c['phone'] ?? ''));
            // Hide internal placeholder phones like NA<epoch>-<3digits>
            $isPlaceholder = preg_match('/^NA\d{10}-\d{3}$/', $phRaw) === 1;
            $label = $nm . (!$isPlaceholder && $phRaw !== '' ? ' (' . $phRaw . ')' : '');
          ?>
          <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)($parcel['customer_id'] ?? 0) === (int)$c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
        <?php endforeach; ?>
      </select>
              <div class="invalid-feedback" id="customerInvalidFeedback">Please choose a customer.</div>
            </div>
            <div class="col-12 col-lg-8">
              <label class="pf-label" for="customerSearch">Search by name or phone</label>
              <div class="customer-search-results">
                <div class="input-group input-group-sm pf-input-group">
                  <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-search" aria-hidden="true"></i></span>
                  <input type="text" id="customerSearch" class="form-control form-control-sm border-start-0" placeholder="Type to search…" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> aria-label="Search customer by name or phone" aria-describedby="customerSummary customerInvalidFeedback">
                  <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#quickAddCustomer" title="Quick add customer" aria-label="Quick add customer"><i class="bi bi-person-plus" aria-hidden="true"></i></button>
                </div>
                <div id="customerSearchResults" class="list-group list-group-flush shadow-sm rounded mt-1 border" style="display:none"></div>
              </div>
            </div>
          </div>
      <div class="mt-1">
        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" data-bs-toggle="collapse" data-bs-target="#findByLocation" aria-expanded="false"><i class="bi bi-geo"></i> Find by Delivery Location</button>
        <div class="collapse border rounded px-2 py-1 bg-light mt-1" id="findByLocation">
          <div class="mb-1">
            <input type="text" id="locQuery" class="form-control form-control-sm" placeholder="Type delivery location area (e.g., Kilinochchi)">
          </div>
          <div id="locResults" class="small" style="max-height: 140px; overflow:auto"></div>
        </div>
      </div>
      <div id="customerSummary" class="mt-1 small pf-form-text" role="status" aria-live="polite"></div>
      </div>
    </section>

    <div class="row g-2 g-lg-3 align-items-stretch pf-loc-sup-inv-row">
      <!-- Location (after invoice on narrow screens — see order-* classes) -->
      <div class="col-12 col-lg-4 order-2 order-lg-1">
        <section class="section-card h-100" role="region" aria-labelledby="pf-h-location">
          <div class="section-title" id="pf-h-location"><i class="bi bi-geo-alt me-2 text-primary" aria-hidden="true"></i>Location</div>
          <div class="section-body pt-2 pt-lg-3">
          <?php if ($lockAll || $priceOnly): ?>
          <label class="pf-label" for="deliveryLocationInput">Delivery location</label>
          <input type="text" name="delivery_location" class="form-control form-control-sm" id="deliveryLocationInput" placeholder="Customer delivery location" value="<?php echo htmlspecialchars((string)($parcel['delivery_location'] ?? '')); ?>" disabled aria-label="Delivery location">
          <?php else: ?>
          <div class="form-floating pf-floating-tight">
            <input type="text" name="delivery_location" class="form-control" id="deliveryLocationInput" placeholder=" " value="<?php echo htmlspecialchars((string)($parcel['delivery_location'] ?? '')); ?>" autocomplete="off" aria-label="Delivery location" aria-required="true">
            <label for="deliveryLocationInput">Delivery location</label>
          </div>
          <?php endif; ?>
          </div>
        </section>
      </div>
      <!-- Supplier -->
      <div class="col-12 col-lg-4 order-3 order-lg-2">
        <section class="section-card h-100" role="region" aria-labelledby="pf-h-supplier">
          <div class="section-title" id="pf-h-supplier"><i class="bi bi-people me-2 text-primary" aria-hidden="true"></i>Supplier</div>
          <div class="section-body pt-2 pt-lg-3">
          <label class="pf-label" for="supplierSelect">Supplier (optional)</label>
          <div class="input-group input-group-sm pf-input-group">
            <select name="supplier_id" id="supplierSelect" class="form-select form-select-sm" <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> data-choices-search="true" aria-label="Supplier">
              <option value="0">-- None --</option>
              <?php foreach (($suppliersAll ?? []) as $s): ?>
                <?php 
                  $raw = (string)($s['name'] ?? '');
                  $nm = trim($raw);
                  $norm = strtolower(preg_replace('/[^a-z0-9]+/i','', $nm));
                  if ($nm === '' || $norm === 'none' || $norm === 'nonenone') { continue; }
                  $ph = trim((string)($s['phone'] ?? ''));
                  $label = $nm . ($ph !== '' ? ' (' . htmlspecialchars($ph) . ')' : '');
                ?>
                <option data-phone="<?php echo htmlspecialchars($ph); ?>" value="<?php echo (int)$s['id']; ?>" <?php echo ((int)($parcel['supplier_id'] ?? 0) === (int)$s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#quickAddSupplier" aria-label="Quick add supplier"><i class="bi bi-person-plus" aria-hidden="true"></i></button>
          </div>
          <div id="supplierPhoneHint" class="pf-form-text mb-0"></div>
          </div>
        </section>
      </div>
      <!-- Invoice & date (first on mobile: parcel date next to customer flow) -->
      <div class="col-12 col-lg-4 order-1 order-lg-3">
        <section class="section-card h-100" role="region" aria-labelledby="pf-h-invoice">
          <div class="section-title" id="pf-h-invoice"><i class="bi bi-file-earmark-text me-2 text-primary" aria-hidden="true"></i>Invoice &amp; date</div>
          <div class="section-body pt-2 pt-lg-3">
          <div class="row g-2 pf-invoice-date-row">
            <div class="col-12 col-sm-6">
              <?php if ($lockAll && $isEdit): ?>
              <input type="hidden" name="invoice_no" value="<?php echo (int)($parcel['invoice_no'] ?? $parcel['id']); ?>">
              <label class="pf-label" for="pfInvoiceNoRo">Invoice no.</label>
              <input type="number" id="pfInvoiceNoRo" class="form-control form-control-sm" min="1" value="<?php echo (int)($parcel['invoice_no'] ?? $parcel['id']); ?>" disabled readonly aria-label="Invoice number">
              <?php else: ?>
              <label class="pf-label" for="pfInvoiceNo">Invoice no. <?php echo $isEdit ? '' : '(optional)'; ?></label>
              <input type="number" name="invoice_no" id="pfInvoiceNo" class="form-control form-control-sm" min="1" value="<?php echo (int)($parcel['invoice_no'] ?? 0) ?: ''; ?>" placeholder="—" aria-label="Invoice number">
              <?php endif; ?>
            </div>
            <div class="col-12 col-sm-6">
              <label class="pf-label" for="parcelDate">Parcel date</label>
              <input type="date" class="form-control form-control-sm" id="parcelDate" name="created_date" value="<?php echo htmlspecialchars(substr((string)($parcel['created_at'] ?? date('Y-m-d')),0,10)); ?>" aria-label="Parcel date">
            </div>
          </div>
          </div>
        </section>
      </div>
    </div>

      <!-- Branches -->
      <section class="section-card pf-branches-section" role="region" aria-labelledby="pf-h-branches">
          <div class="section-title" id="pf-h-branches"><i class="bi bi-diagram-3 me-2 text-primary" aria-hidden="true"></i>Branches</div>
          <div class="section-body pt-2 pt-lg-3">
          <?php if (empty($branchesList)): ?>
          <div class="alert alert-warning py-2 mb-2 small" role="alert">
            <strong>No active branches on first load.</strong> Branches are loaded from the server automatically. If the list stays empty, add or re-activate branches under <a href="<?php echo Helpers::baseUrl('index.php?page=settings&tab=branches#pane-branches'); ?>">Settings → Branches</a>.
          </div>
          <?php endif; ?>
          <div id="pfBranchesDynamicMsg" class="small py-1 d-none" role="status" aria-live="polite"></div>
          <div class="row g-2 pf-branches-row">
            <div class="col-12 col-md-6 col-lg-6">
              <label class="pf-label" for="fromBranchSelect">From branch</label>
              <div class="pf-branch-input-group">
                <select name="from_branch_id" id="fromBranchSelect" class="form-select form-select-sm" required <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> data-enhance="false" aria-label="From branch">
        <option value="">Select Branch</option>
        <?php foreach (($branchesList ?? []) as $b):
            $bid = (int)$b['id'];
            ?>
          <option value="<?php echo $bid; ?>" <?php echo ((int)($parcel['from_branch_id'] ?? 0) === $bid) ? 'selected' : ''; ?>
            data-branch-name="<?php echo htmlspecialchars((string)($b['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            data-address-ta="<?php echo htmlspecialchars((string)($b['address_tamil'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            data-address-en="<?php echo htmlspecialchars((string)($b['address_english'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            data-phones="<?php echo htmlspecialchars((string)($b['phones'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)($b['name'] ?? '')); ?></option>
        <?php endforeach; ?>
      </select>
              </div>
            </div>
            <div class="col-12 col-md-6 col-lg-6">
              <label class="pf-label" for="toBranchSelect">To branch</label>
              <div class="pf-branch-input-group">
                <select name="to_branch_id" id="toBranchSelect" class="form-select form-select-sm" required <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> data-enhance="false" aria-label="To branch">
        <option value="">Select Branch</option>
        <?php foreach (($branchesList ?? []) as $b):
            $bid = (int)$b['id'];
            ?>
          <option value="<?php echo $bid; ?>" <?php echo ((int)($parcel['to_branch_id'] ?? 0) === $bid) ? 'selected' : ''; ?>
            data-branch-name="<?php echo htmlspecialchars((string)($b['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            data-address-ta="<?php echo htmlspecialchars((string)($b['address_tamil'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            data-address-en="<?php echo htmlspecialchars((string)($b['address_english'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            data-phones="<?php echo htmlspecialchars((string)($b['phones'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)($b['name'] ?? '')); ?></option>
        <?php endforeach; ?>
      </select>
              </div>
      <div id="toBranchSuggest" class="pf-form-text text-primary"></div>
            </div>
          </div>
          </div>
      </section>

    <div class="row g-2 g-lg-3 align-items-stretch">
      <!-- Vehicle -->
      <div class="col-12 col-md-6">
        <section class="section-card h-100" role="region" aria-labelledby="pf-h-vehicle">
          <div class="section-title" id="pf-h-vehicle"><i class="bi bi-truck me-2 text-primary" aria-hidden="true"></i>Vehicle</div>
          <div class="section-body pt-2 pt-lg-3">
      <?php if (!empty($vehiclesAll)): ?>
        <label class="pf-label" for="vehicleSelect">Vehicle</label>
        <div class="input-group input-group-sm pf-input-group">
        <select name="vehicle_no" class="form-select form-select-sm" id="vehicleSelect" <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> data-choices-search="true" aria-label="Vehicle number">
          <option value="">-- None --</option>
          <?php 
            $vehCurrent = trim((string)($parcel['vehicle_no'] ?? ''));
            foreach ($vehiclesAll as $v): 
              $vno = trim((string)($v['vehicle_no'] ?? ''));
              if ($vno === '') continue; 
          ?>
            <option value="<?php echo htmlspecialchars($vno); ?>" <?php echo (strcasecmp($vehCurrent, $vno) === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars($vno); ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#quickAddVehicle" aria-label="Quick add vehicle"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
        </div>
      <?php else: ?>
        <label class="pf-label" for="vehicleInput">Vehicle</label>
        <div class="input-group input-group-sm pf-input-group">
        <input type="text" name="vehicle_no" class="form-control form-control-sm" id="vehicleInput" placeholder="e.g., AB-1234" value="<?php echo htmlspecialchars($parcel['vehicle_no'] ?? ''); ?>" <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> aria-label="Vehicle number">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#quickAddVehicle" aria-label="Quick add vehicle"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
        </div>
      <?php endif; ?>
      <div id="deliveryRouteHint" class="pf-form-text small text-success d-none" aria-live="polite"></div>
      <?php 
        $lorryChecked = 0; 
        $pid = (int)($parcel['id'] ?? 0);
        if ($pid > 0 && !empty($_SESSION['lorry_full_saved'][$pid])) { 
          $lorryChecked = 1; 
        } elseif (!empty($_SESSION['lorry_full_pref'])) { 
          $lorryChecked = 1; 
        }
      ?>
      <div class="form-check mt-1 small">
        <input class="form-check-input" type="checkbox" value="1" id="lorry_full" name="lorry_full" <?php echo $lorryChecked ? 'checked' : ''; ?> <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> >
        <label class="form-check-label" for="lorry_full">
          Lorry Full (start next lorry after saving)
        </label>
      </div>
          </div>
        </section>
      </div>

      <!-- Route -->
      <div class="col-12 col-md-6">
        <section class="section-card h-100" role="region" aria-labelledby="pf-h-route">
          <div class="section-title" id="pf-h-route"><i class="bi bi-signpost me-2 text-primary" aria-hidden="true"></i>Route</div>
          <div class="section-body pt-2 pt-lg-3">
      <?php $drVal = trim((string)($parcel['delivery_route'] ?? '')); ?>
      <?php if (!empty($deliveryRoutesAll) && is_array($deliveryRoutesAll)): ?>
        <label class="pf-label" for="deliveryRouteField">Delivery route</label>
        <select name="delivery_route" id="deliveryRouteField" class="form-select form-select-sm" <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> data-choices-search="true" aria-label="Delivery route">
          <option value="">-- Select Route --</option>
          <?php foreach ($deliveryRoutesAll as $r): ?>
            <?php $rName = trim((string)($r['name'] ?? '')); if ($rName === '') continue; ?>
            <option value="<?php echo htmlspecialchars($rName); ?>" <?php echo ($drVal !== '' && strcasecmp($drVal, $rName) === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars($rName); ?></option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <label class="pf-label" for="deliveryRouteField">Delivery route</label>
        <input type="text" name="delivery_route" id="deliveryRouteField" class="form-control form-control-sm" placeholder="Route" value="<?php echo htmlspecialchars($drVal); ?>" <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> aria-label="Delivery route">
      <?php endif; ?>
          </div>
        </section>
      </div>
    </div>
  </div>
      </div>
      <div class="col-12 col-lg-6 col-xl-6 pf-animate pf-animate-in pf-animate-delay-2 pf-col-items">

  <!-- Full-width Previous Bill Preview (moved outside left column) -->
  <div id="billPreview" class="mb-3 pf-bill-preview" style="display:none;">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background: rgba(255,255,255,0.9); backdrop-filter: blur(12px);">
      <div class="card-header d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold">Previous Bill</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="billPreviewClose">Close</button>
      </div>
      <div class="card-body p-0" style="height:700px; max-height:75vh;">
        <iframe id="billPreviewFrame" src="about:blank" style="border:0; width:100%; height:100%;"></iframe>
      </div>
    </div>
  </div>

  <!-- Items & receipt section -->
  <div class="section-card pf-items-section mt-1" role="region" aria-labelledby="pf-h-items-total">
    <div class="section-title" id="pf-h-items-total"><i class="bi bi-list-ul me-2 text-primary" aria-hidden="true"></i> Items &amp; Total</div>
    <div class="section-body p-0">
  <div class="receipt-box">
    <div class="receipt-header d-flex justify-content-between align-items-center">
      <div class="fw-semibold small">TS Transport</div>
      <div class="serial-badge d-flex align-items-center gap-1 flex-wrap justify-content-end">
        <label for="serialInput" class="mb-0 small">Serial:</label>
        <input type="text" id="serialInput" name="tracking_number" class="form-control form-control-sm pf-serial-input" placeholder="Auto" value="<?php echo htmlspecialchars((string)($parcel['tracking_number'] ?? '')); ?>" aria-label="Parcel serial or tracking number" autocomplete="off" />
      </div>
    </div>
    <div class="px-2 py-2 border-bottom bg-body-secondary bg-opacity-25 pf-receipt-summary-wrap">
      <div class="pf-receipt-summary small">
        <div class="pf-rs-block pf-rs-customer text-break">
          <strong>Customer:</strong> <span id="customerDisplay">—</span>
          <div class="text-muted mt-1"><strong>Location:</strong> <span id="customerLocDisplay">—</span></div>
        </div>
        <div class="pf-rs-block"><strong>Date:</strong> <span id="recDateSummary"><?php echo htmlspecialchars(substr((string)($parcel['created_at'] ?? date('Y-m-d')),0,10)); ?></span></div>
        <div class="pf-rs-block"><strong>From:</strong> <span id="fromBranchDisplay">—</span></div>
        <div class="pf-rs-block"><strong>To:</strong> <span id="toBranchDisplay">—</span></div>
        <div class="pf-rs-block"><strong>Vehicle:</strong> <span id="recVehicle">—</span></div>
      </div>
    </div>

    <div class="p-2 pt-1">
      <div class="pf-items-scroll mb-1">
        <table class="table table-sm receipt-grid mb-0 align-middle" id="itemsTable" aria-describedby="pf-h-items-total">
          <colgroup>
            <col class="pf-col-no" style="width:4.5%" />
            <col class="pf-col-desc" style="width:30%" />
            <col class="pf-col-qty" style="width:8%" />
            <col class="pf-col-rate" style="width:9%" />
            <col class="pf-col-amt" style="width:10%" />
            <col class="pf-col-addl" style="width:12%" />
            <col class="pf-col-act" style="width:3.5rem" />
          </colgroup>
          <thead>
            <tr>
              <th scope="col">No</th>
              <th scope="col">Description</th>
              <th scope="col">Qty</th>
              <th scope="col">Rate</th>
              <th scope="col">Amount</th>
              <th scope="col" title="Additional amounts">Additional</th>
              <th scope="col"><span class="visually-hidden">Remove row</span></th>
            </tr>
          </thead>
          <tbody>
            <?php
              $itemsList = $items ?? [];
              $parcelPrice = (float)($parcel['price'] ?? 0);
              $sumWithRate = 0.0;
              $noRateCount = 0;
              foreach ($itemsList as $it) {
                $r = (float)($it['rate'] ?? 0);
                $q = (float)($it['qty'] ?? 0);
                if ($q > 0 && $r > 0) {
                  $sumWithRate += $q * $r;
                } elseif ($q > 0 || trim((string)($it['description'] ?? '')) !== '') {
                  $noRateCount++;
                }
              }
              $remainderForNoRate = $noRateCount > 0 && $parcelPrice > $sumWithRate ? ($parcelPrice - $sumWithRate) : 0;
              $rowIndex = 0;
              $noRateIdx = 0;
            ?>
            <?php foreach ($itemsList as $it): ?>
            <?php
              $rowIndex++;
              $q = (float)($it['qty'] ?? 0);
              $r = (float)($it['rate'] ?? 0);
              $amt = $q > 0 && $r > 0 ? ($q * $r) : 0;
              if ($amt <= 0 && $noRateCount > 0 && $parcelPrice > 0 && ($q > 0 || trim((string)($it['description'] ?? '')) !== '')) {
                $noRateIdx++;
                $amt = ($remainderForNoRate / $noRateCount);
              }
            ?>
            <tr>
              <td class="text-center align-middle pf-item-no-cell" data-label=""><?php echo $rowIndex; ?></td>
              <td data-label="Description"><input type="text" name="items[<?php echo $rowIndex; ?>][description]" class="form-control item-desc" value="<?php echo htmlspecialchars($it['description'] ?? ''); ?>" placeholder="Description" <?php echo ($lockAll || ($isEdit && $priceOnly)) ? 'readonly' : ''; ?>></td>
              <td data-label="Qty"><input type="number" step="0.01" name="items[<?php echo $rowIndex; ?>][qty]" class="form-control item-qty" value="<?php echo htmlspecialchars((string)$q); ?>" placeholder="Qty" <?php echo ($lockAll || ($isEdit && $priceOnly)) ? 'readonly' : ''; ?>></td>
              <td data-label="Rate"><input type="number" step="0.01" min="0" name="items[<?php echo $rowIndex; ?>][rate]" class="form-control item-rate" value="<?php echo $r > 0 ? number_format($r, 2, '.', '') : ''; ?>" <?php echo ($lockAll || !$canEnterItemAmounts) ? 'disabled' : ''; ?> placeholder="Rate"></td>
              <?php
                $addAmounts = [];
                if (!empty($it['additional_amounts'])) {
                  $raw = $it['additional_amounts'];
                  $addAmounts = is_string($raw) ? (json_decode($raw, true) ?: []) : (array)$raw;
                } elseif ((float)($it['additional_amount'] ?? 0) > 0) {
                  $addAmounts = [(float)$it['additional_amount']];
                }
                if (empty($addAmounts)) { $addAmounts = ['']; }
                $addSumPhp = array_sum(array_map('floatval', $addAmounts));
              ?>
              <td class="align-middle pf-item-amt-cell" data-label="Amount">
                <span class="item-amount fw-semibold pf-amount-readout"><?php echo $amt > 0 ? number_format($amt, 2) : '—'; ?></span>
              </td>
              <td class="align-middle item-amount-cell" data-label="Additional">
                <div class="d-flex flex-column gap-1 pf-item-add-block pf-item-add-excel">
                  <span class="item-add-sum fw-semibold pf-amount-readout"><?php echo $addSumPhp > 0 ? number_format($addSumPhp, 2) : '—'; ?></span>
                  <span class="visually-hidden">Additional line amounts</span>
                  <div class="pf-item-add-excel-wrap">
                    <div class="pf-item-add-excel-tokens" aria-hidden="true"></div>
                    <input type="text" class="pf-item-add-excel-field" autocomplete="off" inputmode="decimal" placeholder="Amount, +, Enter" <?php echo ($lockAll || !$canEnterItemAmounts) ? 'disabled' : ''; ?> aria-label="Additional amounts">
                  </div>
                  <div class="item-add-list d-flex flex-column gap-1">
                    <?php foreach ($addAmounts as $addVal): ?>
                    <div class="d-flex gap-1 align-items-center item-add-row">
                      <input type="number" step="0.01" min="0" name="items[<?php echo $rowIndex; ?>][additional_amounts][]" class="form-control form-control-sm item-add" value="<?php echo ($addVal !== '' && (float)$addVal > 0) ? number_format((float)$addVal, 2, '.', '') : ''; ?>" placeholder="0" <?php echo ($lockAll || !$canEnterItemAmounts) ? 'disabled' : ''; ?>>
                      <?php if (!$lockAll && $canEnterItemAmounts): ?><button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 remove-add" title="Remove"><i class="bi bi-x"></i></button><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                  </div>
                  <?php if (!$lockAll && $canEnterItemAmounts): ?><button type="button" class="btn btn-sm btn-outline-secondary py-0 add-amount-btn" title="Add another amount line"><i class="bi bi-plus-lg" aria-hidden="true"></i><span class="visually-hidden">Add amount</span></button><?php endif; ?>
                </div>
              </td>
              <td class="text-center pf-item-remove-cell"><?php if (!$isEdit && !$lockAll): ?><button type="button" class="btn btn-outline-danger btn-sm remove-row pf-btn-icon-touch rounded-3" aria-label="Delete line"><i class="bi bi-trash3" aria-hidden="true"></i></button><?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
            <tr>
              <td class="text-center align-middle pf-item-no-cell" data-label="">1</td>
              <td data-label="Description"><input type="text" name="items[1][description]" class="form-control item-desc" placeholder="Description" <?php echo ($lockAll || ($isEdit && $priceOnly)) ? 'readonly' : ''; ?>></td>
              <td data-label="Qty"><input type="number" step="0.01" name="items[1][qty]" class="form-control item-qty" placeholder="Qty" <?php echo ($lockAll || ($isEdit && $priceOnly)) ? 'readonly' : ''; ?>></td>
              <td data-label="Rate"><input type="number" step="0.01" min="0" name="items[1][rate]" class="form-control item-rate" <?php echo ($lockAll || !$canEnterItemAmounts) ? 'disabled' : ''; ?> placeholder="Rate"></td>
              <td class="align-middle pf-item-amt-cell" data-label="Amount">
                <span class="item-amount fw-semibold pf-amount-readout">—</span>
              </td>
              <td class="align-middle item-amount-cell" data-label="Additional">
                <div class="d-flex flex-column gap-1 pf-item-add-block pf-item-add-excel">
                  <span class="item-add-sum fw-semibold pf-amount-readout">—</span>
                  <span class="visually-hidden">Additional line amounts</span>
                  <div class="pf-item-add-excel-wrap">
                    <div class="pf-item-add-excel-tokens" aria-hidden="true"></div>
                    <input type="text" class="pf-item-add-excel-field" autocomplete="off" inputmode="decimal" placeholder="Amount, +, Enter" <?php echo ($lockAll || !$canEnterItemAmounts) ? 'disabled' : ''; ?> aria-label="Additional amounts">
                  </div>
                  <div class="item-add-list d-flex flex-column gap-1">
                    <div class="d-flex gap-1 align-items-center item-add-row">
                      <input type="number" step="0.01" min="0" name="items[1][additional_amounts][]" class="form-control form-control-sm item-add" placeholder="0" <?php echo ($lockAll || !$canEnterItemAmounts) ? 'disabled' : ''; ?>>
                      <?php if (!$lockAll && $canEnterItemAmounts): ?><button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 remove-add" title="Remove"><i class="bi bi-x"></i></button><?php endif; ?>
                    </div>
                  </div>
                  <?php if (!$lockAll && $canEnterItemAmounts): ?><button type="button" class="btn btn-sm btn-outline-secondary py-0 add-amount-btn" title="Add another amount line"><i class="bi bi-plus-lg" aria-hidden="true"></i><span class="visually-hidden">Add amount</span></button><?php endif; ?>
                </div>
              </td>
              <td class="text-center pf-item-remove-cell"><?php if (!$isEdit && !$lockAll): ?><button type="button" class="btn btn-outline-danger btn-sm remove-row pf-btn-icon-touch rounded-3" aria-label="Delete line"><i class="bi bi-trash3" aria-hidden="true"></i></button><?php endif; ?></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="text-end mb-1 px-2">
          <?php if (!$lockAll): ?>
            <button type="button" class="btn btn-sm btn-primary pf-btn-add-row w-100 w-md-auto" id="addRow" aria-label="Add line item"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add Row</button>
          <?php endif; ?>
      </div>

      <?php $currPrice = (float)($parcel['price'] ?? 0); ?>
      <div class="receipt-total p-2 p-md-3">
        <div class="row g-2 g-md-3 align-items-center justify-content-lg-end">
          <div class="col-12 col-sm-6 col-lg-auto">
            <label class="col-form-label mb-0" for="totalPrice"><strong>Total</strong></label>
          </div>
          <div class="col-12 col-sm-6 col-lg-auto">
            <input type="number" step="0.01" min="0" class="form-control form-control-sm w-100" name="price" id="totalPrice" value="<?php echo $currPrice>0? number_format($currPrice,2,'.','') : ''; ?>" <?php 
              echo ($lockAll || !$priceOnly) ? 'disabled' : '';
            ?> placeholder="0.00">
          </div>
          <?php if ($priceOnly && !$lockAll): ?>
            <div class="col-12 col-sm-6 col-lg-auto">
              <label class="col-form-label mb-0" for="discountInput"><strong>Discount</strong></label>
            </div>
            <div class="col-12 col-sm-6 col-lg-auto">
              <input type="number" step="0.01" min="0" class="form-control form-control-sm w-100" name="discount" id="discountInput" value="" placeholder="0.00">
            </div>
          <?php endif; ?>
          <div class="col-12 col-lg-auto text-lg-end">
            <span class="fs-5 fw-bold" id="totalDisplay"><?php echo $parcel['price']===null ? '—' : number_format((float)$parcel['price'],2); ?></span>
          </div>
        </div>
      </div>
      </div>
    </div>
  </div>
  </div>
      </div><!-- /.pf-col-items -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid main grid -->
  </div><!-- /.statusOnly hide block -->

  <!-- Status and actions (native select: do not use Choices.js — keeps all 8 statuses visible) -->
  <div class="container-fluid px-0 pf-form-inner-fluid">
  <div class="section-card mt-2">
    <div class="section-body py-2 px-2 px-sm-3">
      <div class="row g-2 align-items-end pf-status-row">
        <div class="col-12 col-lg col-status min-w-0">
          <label class="form-label mb-1" for="parcelStatusSelect">Status</label>
          <select name="status" id="parcelStatusSelect" class="form-select form-select-sm w-100" data-enhance="false" <?php echo ($lockAll && !$statusOnlyEdit) ? 'disabled' : ''; ?> aria-label="Parcel status">
            <?php foreach (Helpers::parcelStatusMap() as $stVal => $stLabel): ?>
            <option value="<?php echo htmlspecialchars($stVal); ?>" <?php echo (($parcel['status'] ?? '') === $stVal) ? 'selected' : ''; ?>><?php echo htmlspecialchars($stLabel); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-lg-auto d-none d-lg-block flex-shrink-0">
          <label class="form-label mb-1 opacity-0 user-select-none" aria-hidden="true">&nbsp;</label>
          <button type="submit" id="parcelSubmitBtn" class="btn btn-primary btn-sm text-nowrap pf-btn-save"><i class="bi bi-save me-1" aria-hidden="true"></i> Save Parcel</button>
        </div>
      </div>
    </div>
  </div>
  </div>

  </div><!-- /.pf-form-scroll-region -->

  <!-- Mobile sticky action bar (stacked ≤767px; side-by-side tablet when bar visible) -->
  <div class="pf-sticky-actions d-lg-none">
    <div class="pf-sticky-actions-inner">
      <button type="submit" class="btn btn-primary pf-btn-save" id="pfSaveBtnMobile"><i class="bi bi-save me-1" aria-hidden="true"></i> Save</button>
      <button type="reset" class="btn btn-outline-secondary rounded-3" id="pfResetBtnMobile"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> Reset</button>
    </div>
  </div>
</form>
</div><!-- /.pf-one-screen-fill -->

  <!-- Quick Add Customer: OUTSIDE #parcelForm (Enter must not submit parcel) -->
  <div class="modal fade" id="quickAddCustomer" tabindex="-1" aria-labelledby="quickAddCustomerTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="quickAddCustomerTitle">Quick Add Customer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-12 col-sm-6">
              <label class="pf-label" for="qa_name">Name</label>
              <input type="text" id="qa_name" class="form-control form-control-sm" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" aria-required="true">
            </div>
            <div class="col-12 col-sm-6">
              <label class="pf-label" for="qa_phone_input">Phone</label>
              <input type="text" id="qa_phone_input" class="form-control form-control-sm" autocomplete="new-password" inputmode="tel" autocapitalize="off" autocorrect="off" spellcheck="false">
            </div>
            <div class="col-12 col-sm-6">
              <label class="pf-label" for="qa_email">Email</label>
              <input type="email" id="qa_email" class="form-control form-control-sm" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
            </div>
            <div class="col-12 col-sm-6">
              <label class="pf-label" for="qa_address">Address</label>
              <input type="text" id="qa_address" class="form-control form-control-sm" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
            </div>
            <div class="col-12 col-sm-6">
              <label class="pf-label" for="qa_delivery_location">Delivery location</label>
              <input type="text" id="qa_delivery_location" class="form-control form-control-sm" list="dl_locations" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
              <datalist id="dl_locations">
                <?php 
                  $locs = [];
                  foreach (($customersAll ?? []) as $c) { 
                    $dl = trim((string)($c['delivery_location'] ?? '')); 
                    if ($dl !== '') { $locs[$dl] = true; }
                  }
                  foreach (array_keys($locs) as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt); ?>"></option>
                <?php endforeach; ?>
              </datalist>
            </div>
            <div class="col-12 col-sm-6">
              <label class="pf-label" for="qa_type">Type</label>
              <select id="qa_type" class="form-select form-select-sm" aria-label="Customer type">
                <option value="">— Select —</option>
                <option value="regular">Regular</option>
                <option value="corporate">Corporate</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="qa_submit" class="btn btn-primary"><i class="bi bi-save"></i> Save &amp; Use</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Add Supplier -->
  <div class="modal fade" id="quickAddSupplier" tabindex="-1" aria-labelledby="quickAddSupplierTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="quickAddSupplierTitle">Quick Add Supplier</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-12 col-sm-6">
              <label class="pf-label" for="qs_name">Supplier name</label>
              <input type="text" id="qs_name" class="form-control form-control-sm" autocomplete="off" aria-required="true">
            </div>
            <div class="col-12 col-sm-6">
              <label class="pf-label" for="qs_phone">Phone</label>
              <input type="text" id="qs_phone" class="form-control form-control-sm" autocomplete="off" inputmode="tel">
            </div>
            <div class="col-12 col-sm-6">
              <label class="pf-label" for="qs_branch">Branch</label>
              <select id="qs_branch" class="form-select form-select-sm" aria-label="Supplier branch" data-enhance="false">
                <option value="0">Select Branch</option>
                <?php foreach (($branchesList ?? []) as $b): ?>
                  <option value="<?php echo (int)$b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-sm-6">
              <label class="pf-label" for="qs_code">Code <span class="text-muted fw-normal">(optional)</span></label>
              <input type="text" id="qs_code" class="form-control form-control-sm" autocomplete="off">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="qs_submit" class="btn btn-primary"><i class="bi bi-save"></i> Save &amp; Use</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Add Vehicle: outside parcel form -->
  <div class="modal fade" id="quickAddVehicle" tabindex="-1" aria-labelledby="quickAddVehicleTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="quickAddVehicleTitle">Quick Add Vehicle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-12 col-md-8">
              <label class="pf-label" for="qv_no">Vehicle number</label>
              <input type="text" id="qv_no" class="form-control form-control-sm" placeholder="e.g. REG011 or AB-1234" autocomplete="off" aria-label="Vehicle number">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="qv_submit" class="btn btn-primary"><i class="bi bi-save"></i> Save &amp; Use</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Customer pick: last bill preview (outside parcel form) -->
  <div class="modal fade" id="customerLastBillModal" tabindex="-1" aria-labelledby="customerLastBillModalLabel" aria-hidden="true">
    <div class="modal-dialog clb-modal modal-fullscreen-sm-down modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-0" id="customerLastBillModalLabel"><i class="bi bi-receipt-cutoff me-2"></i>Last bill</h5>
            <div class="small text-muted" id="clbHint">Review the bill, then tap <strong>Select customer</strong> to continue.</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-2 small">
            <span class="badge text-bg-light border"><span class="text-muted">Customer</span> <span id="clbCustomerName" class="fw-semibold text-dark">—</span></span>
            <span class="badge text-bg-light border"><span class="text-muted">Bill</span> <span id="clbBillId" class="fw-semibold text-dark">—</span></span>
            <span class="badge text-bg-light border"><span class="text-muted">Balance</span> <span id="clbBalance" class="fw-semibold text-dark">—</span></span>
          </div>
          <div id="clbNoBill" class="alert alert-info py-2 d-none mb-2">
            <i class="bi bi-info-circle me-1"></i>No previous delivery note found. You can still select this customer.
          </div>
          <div class="clb-iframe-wrap border rounded overflow-hidden bg-light">
            <iframe id="clbFrame" title="Last bill preview" src="about:blank" class="w-100 h-100" style="border:0; min-height: inherit;"></iframe>
          </div>
        </div>
        <div class="modal-footer flex-wrap gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Cancel</button>
          <button type="button" class="btn btn-primary px-4" id="clbSelectBtn"><i class="bi bi-check2-circle me-1"></i>Select customer</button>
        </div>
      </div>
    </div>
  </div>

</div><!-- .pf-page-wrap -->
</div><!-- .parcel-form-page -->
<script>
(function(){
  const isEdit = <?php echo $isEdit ? 'true' : 'false'; ?>;
  /** New Parcel: always preview last bill before customer is applied */
  const forceLastBillFlow = <?php echo empty($parcel['id']) ? 'true' : 'false'; ?>;
  const priceOnly = <?php echo $priceOnly ? 'true' : 'false'; ?>;
  const lockAll = <?php echo $lockAll ? 'true' : 'false'; ?>;
  const canEnterItemAmounts = <?php echo $canEnterItemAmounts ? 'true' : 'false'; ?>;
  const customersSearchData = <?php echo json_encode(array_map(function($c){ return ['id'=>(int)($c['id'] ?? 0),'name'=>(string)($c['name'] ?? ''),'phone'=>(string)($c['phone'] ?? '')]; }, $customersAll ?? []), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  const table = document.getElementById('itemsTable');
  const addBtn = document.getElementById('addRow');
  const totalDisplay = document.getElementById('totalDisplay');
  const totalPrice = document.getElementById('totalPrice');
  const discountInput = document.getElementById('discountInput');

  /** Parse JSON from AJAX responses (strip BOM; tolerate HTML error pages) */
  function parseJsonResponse(text) {
    if (!text || !String(text).trim()) return null;
    const t = String(text).replace(/^\uFEFF/, '').trim();
    try { return JSON.parse(t); } catch (_) { return null; }
  }

  /** Keep native branch <select> and Choices.js UI in sync after programmatic value changes */
  function syncBranchSelect(sel, value) {
    if (!sel) return;
    const v = value != null ? String(value) : '';
    sel.value = v;
    try {
      if (sel._choices) {
        const key = (v === '' || v === '0') ? '' : v;
        sel._choices.setChoiceByValue(key);
      }
    } catch (_) { /* ignore */ }
    sel.dispatchEvent(new Event('change', { bubbles: true }));
  }

  const parcelBranchJsonUrl = <?php echo json_encode(Helpers::baseUrl('index.php?page=branches&action=json'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES); ?>;
  const parcelBranchPreserveFrom = <?php echo (int)($parcel['from_branch_id'] ?? 0); ?>;
  const parcelBranchPreserveTo = <?php echo (int)($parcel['to_branch_id'] ?? 0); ?>;
  let branchesData = <?php echo json_encode(array_map(function ($b) { return ['id' => (int)$b['id'], 'name' => $b['name']]; }, $branchesAll ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

  async function loadParcelBranchesFromApi() {
    const fromSel = document.querySelector('select[name="from_branch_id"]');
    const toSel = document.querySelector('select[name="to_branch_id"]');
    const msgEl = document.getElementById('pfBranchesDynamicMsg');
    if (!fromSel || !toSel || fromSel.disabled || toSel.disabled) {
      return;
    }
    const prevFrom = String(fromSel.value || '').trim();
    const prevTo = String(toSel.value || '').trim();
    const preserveFromId = parseInt(String(prevFrom || String(parcelBranchPreserveFrom || 0)), 10) || 0;
    const preserveToId = parseInt(String(prevTo || String(parcelBranchPreserveTo || 0)), 10) || 0;
    const url = parcelBranchJsonUrl
      + (String(parcelBranchJsonUrl).indexOf('?') === -1 ? '?' : '&')
      + 'preserve_from=' + encodeURIComponent(String(preserveFromId))
      + '&preserve_to=' + encodeURIComponent(String(preserveToId));
    if (msgEl) {
      msgEl.classList.remove('d-none', 'text-danger', 'text-warning');
      msgEl.classList.add('text-muted');
      msgEl.textContent = 'Loading branches…';
    }
    try {
      const res = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      const payload = parseJsonResponse(await res.text());
      if (!res.ok || !payload || payload.ok !== true || !Array.isArray(payload.branches)) {
        if (msgEl) {
          msgEl.classList.remove('d-none', 'text-muted');
          msgEl.classList.add('text-warning');
          msgEl.textContent = 'Could not load branches from server. The list above is from when the page loaded.';
        }
        return;
      }
      const preserveIds = new Set([preserveFromId, preserveToId].filter(function (x) { return x > 0; }));
      const active = payload.branches.filter(function (b) {
        const id = parseInt(String(b.id), 10);
        const isOn = parseInt(String(b.is_active != null ? b.is_active : 1), 10) === 1;
        return isOn || preserveIds.has(id);
      });
      active.sort(function (a, b) {
        const ma = parseInt(String(a.is_main ?? 0), 10) === 1;
        const mb = parseInt(String(b.is_main ?? 0), 10) === 1;
        if (ma !== mb) {
          return ma ? -1 : 1;
        }
        return String(a.name || '').localeCompare(String(b.name || ''));
      });
      branchesData = active.map(function (b) {
        return { id: parseInt(String(b.id), 10), name: String(b.name || '') };
      });
      function fillSelect(sel, selected) {
        const keep = String(selected || '').trim();
        sel.innerHTML = '';
        const ph = document.createElement('option');
        ph.value = '';
        ph.textContent = 'Select Branch';
        sel.appendChild(ph);
        active.forEach(function (b) {
          const o = document.createElement('option');
          const id = parseInt(String(b.id), 10);
          o.value = String(id);
          o.textContent = (b.name && String(b.name).trim()) ? String(b.name) : ('Branch #' + id);
          o.dataset.branchName = String(b.name || '');
          o.dataset.addressTa = String(b.address_tamil || '');
          o.dataset.addressEn = String(b.address_english || '');
          o.dataset.phones = String(b.phones || '');
          sel.appendChild(o);
        });
        if (keep && Array.from(sel.options).some(function (o) { return o.value === keep; })) {
          sel.value = keep;
        } else {
          sel.value = '';
        }
      }
      fillSelect(fromSel, prevFrom);
      fillSelect(toSel, prevTo);
      const qsBranch = document.getElementById('qs_branch');
      if (qsBranch) {
        const prevQs = String(qsBranch.value || '0');
        const supplierOnly = active.filter(function (b) {
          return parseInt(String(b.is_active != null ? b.is_active : 1), 10) === 1;
        });
        qsBranch.innerHTML = '';
        const z = document.createElement('option');
        z.value = '0';
        z.textContent = 'Select Branch';
        qsBranch.appendChild(z);
        supplierOnly.forEach(function (b) {
          const o = document.createElement('option');
          const id = parseInt(String(b.id), 10);
          o.value = String(id);
          o.textContent = (b.name && String(b.name).trim()) ? String(b.name) : ('Branch #' + id);
          qsBranch.appendChild(o);
        });
        if (prevQs !== '0' && Array.from(qsBranch.options).some(function (opt) { return opt.value === prevQs; })) {
          qsBranch.value = prevQs;
        } else {
          qsBranch.value = '0';
        }
      }
      if (active.length === 0) {
        if (msgEl) {
          msgEl.classList.remove('d-none', 'text-muted');
          msgEl.classList.add('text-warning');
          msgEl.textContent = 'No branches available. An administrator can add active branches under Settings → Branches.';
        }
      } else if (msgEl) {
        msgEl.classList.add('d-none');
        msgEl.textContent = '';
      }
      fromSel.dispatchEvent(new Event('change', { bubbles: true }));
      toSel.dispatchEvent(new Event('change', { bubbles: true }));
    } catch (err) {
      if (msgEl) {
        msgEl.classList.remove('d-none', 'text-muted');
        msgEl.classList.add('text-danger');
        msgEl.textContent = 'Could not load branches. Check your connection and try again.';
      }
    }
  }

  window.TMS_refreshParcelBranches = function () {
    return loadParcelBranchesFromApi();
  };

  // Prevent double submission — all visible Save buttons (desktop status row, sticky mobile, header)
  const form = document.querySelector('form[action*="parcels&action=save"]');
  const saveBtns = [
    document.getElementById('parcelSubmitBtn'),
    document.getElementById('pfSaveBtnMobile'),
    document.getElementById('pfSaveBtnTop'),
  ].filter(function (el) { return el && el.tagName === 'BUTTON'; });
  if (form) {
    let isSubmitting = false;
    form.addEventListener('submit', function(e) {
      // AJAX submit (no reload)
      e.preventDefault();
      if (isSubmitting) {
        return false;
      }
      // Delivery location: validate with a clear message (no duplicate HTML required + custom)
      const deliveryLoc = document.getElementById('deliveryLocationInput');
      if (deliveryLoc && !deliveryLoc.disabled) {
        if (!String(deliveryLoc.value || '').trim()) {
          deliveryLoc.setCustomValidity('Please enter a delivery location.');
        } else {
          deliveryLoc.setCustomValidity('');
        }
      }
      const fromBranchEl = form.querySelector('select[name="from_branch_id"]');
      const toBranchEl = form.querySelector('select[name="to_branch_id"]');
      let fromBranchErr = '';
      let toBranchErr = '';
      if (fromBranchEl && !fromBranchEl.disabled) {
        const fv = String(fromBranchEl.value || '').trim();
        if (!fv || fv === '0') {
          fromBranchErr = 'Please select a from branch.';
        }
      }
      if (toBranchEl && !toBranchEl.disabled) {
        const tv = String(toBranchEl.value || '').trim();
        if (!tv || tv === '0') {
          toBranchErr = 'Please select a to branch.';
        }
      }
      if (fromBranchEl && toBranchEl && !fromBranchEl.disabled && !toBranchEl.disabled && !fromBranchErr && !toBranchErr) {
        const fv = String(fromBranchEl.value || '').trim();
        const tv = String(toBranchEl.value || '').trim();
        if (fv === tv) {
          fromBranchErr = 'From and To branch must be different.';
          toBranchErr = 'From and To branch must be different.';
        }
      }
      if (fromBranchEl && !fromBranchEl.disabled) {
        fromBranchEl.setCustomValidity(fromBranchErr);
      }
      if (toBranchEl && !toBranchEl.disabled) {
        toBranchEl.setCustomValidity(toBranchErr);
      }
      // Scroll to first invalid field (HTML5 validation)
      if (!form.checkValidity()) {
        form.classList.add('was-validated');
        const firstInvalid = form.querySelector(':invalid');
        if (firstInvalid) {
          const scrollTarget = firstInvalid.closest('.choices') || firstInvalid;
          scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
          try { firstInvalid.focus({ preventScroll: true }); } catch (_) { try { firstInvalid.focus(); } catch (_) {} }
        }
        return false;
      }
      form.classList.remove('was-validated');

      const toastWrap = document.getElementById('pfToastWrap');
      function showToast(type, title, message) {
        if (!toastWrap) return;
        const cls = type === 'success' ? 'text-bg-success' : (type === 'warning' ? 'text-bg-warning' : 'text-bg-danger');
        const id = 't' + Math.random().toString(16).slice(2);
        const html = `
          <div class="toast ${cls} border-0 mb-2" id="${id}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
              <div class="toast-body">
                <div class="fw-semibold">${title}</div>
                <div class="small">${message || ''}</div>
              </div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
          </div>`;
        toastWrap.insertAdjacentHTML('beforeend', html);
        const el = document.getElementById(id);
        if (window.bootstrap && el) {
          const t = new bootstrap.Toast(el, { delay: 3500 });
          t.show();
          el.addEventListener('hidden.bs.toast', () => { try { el.remove(); } catch(_) {} });
        }
      }

      async function ajaxSubmit() {
        isSubmitting = true;
        const snap = saveBtns.map(function (b) { return { el: b, html: b.innerHTML }; });
        const loadingHtml = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving…';
        saveBtns.forEach(function (b) { b.disabled = true; b.innerHTML = loadingHtml; });
        try {
          const fd = new FormData(form);
          const res = await fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd
          });
          let data = null;
          try {
            const raw = await res.text();
            data = raw ? JSON.parse(raw) : null;
          } catch (_) {
            data = null;
          }
          if (!res.ok || !data || data.ok !== true) {
            const msg = (data && data.error) ? String(data.error) : (res.status >= 500 ? 'Server error while saving. Please try again.' : 'Could not save. Check highlighted fields and try again.');
            showToast('error', 'Could not save', msg);
            // focus first invalid again
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) { firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' }); firstInvalid.focus(); }
            return;
          }

          showToast('success', 'Saved', `Parcel saved successfully (ID: ${data.id}).`);

          // If backend suggests redirect, try to apply changes without full page reload
          // New parcel flow: keep customer/branches/vehicle, clear items
          const redirect = String(data.redirect || '');
          if (redirect.includes('page=parcels') && redirect.includes('action=new')) {
            try {
              const u = new URL(redirect, window.location.origin);
              const qs = u.searchParams;
              const cid = qs.get('customer_id') || '';
              const fromId = qs.get('from_branch_id') || '';
              const toId = qs.get('to_branch_id') || '';
              const veh = qs.get('vehicle_no') || '';
              if (cid && customerSel) { customerSel.value = cid; customerSel.dispatchEvent(new Event('change')); }
              if (fromId && fromBranchSel) { syncBranchSelect(fromBranchSel, fromId); }
              if (toId && toBranchSel) { syncBranchSelect(toBranchSel, toId); }
              if (veh) {
                if (vehicleSelect) { vehicleSelect.value = veh; vehicleSelect.dispatchEvent(new Event('change')); }
                if (vehicleInput) { vehicleInput.value = veh; vehicleInput.dispatchEvent(new Event('input')); }
                updateVehicle();
              }
              // clear item rows to a single blank row
              const tbody = table?.querySelector('tbody');
              if (tbody) {
                tbody.innerHTML = `
                  <tr>
                    <td class="text-center align-middle pf-item-no-cell" data-label="">1</td>
                    <td data-label="Description"><input type="text" name="items[1][description]" class="form-control item-desc" placeholder="Description"></td>
                    <td data-label="Qty"><input type="number" step="0.01" name="items[1][qty]" class="form-control item-qty" placeholder="Qty"></td>
                    <td data-label="Rate"><input type="number" step="0.01" min="0" name="items[1][rate]" class="form-control item-rate" ${canEnterItemAmounts ? '' : 'disabled'} placeholder="Rate"></td>
                    <td class="align-middle pf-item-amt-cell" data-label="Amount">
                      <span class="item-amount fw-semibold pf-amount-readout">—</span>
                    </td>
                    <td class="align-middle item-amount-cell" data-label="Additional">
                      <div class="d-flex flex-column gap-1 pf-item-add-block pf-item-add-excel">
                        <span class="item-add-sum fw-semibold pf-amount-readout">—</span>
                        <span class="visually-hidden">Additional line amounts</span>
                        <div class="pf-item-add-excel-wrap">
                          <div class="pf-item-add-excel-tokens" aria-hidden="true"></div>
                          <input type="text" class="pf-item-add-excel-field" autocomplete="off" inputmode="decimal" placeholder="Amount, +, Enter" ${canEnterItemAmounts ? '' : 'disabled'} aria-label="Additional amounts">
                        </div>
                        <div class="item-add-list d-flex flex-column gap-1">
                          <div class="d-flex gap-1 align-items-center item-add-row">
                            <input type="number" step="0.01" min="0" name="items[1][additional_amounts][]" class="form-control form-control-sm item-add" placeholder="0" ${canEnterItemAmounts ? '' : 'disabled'}>
                            ${canEnterItemAmounts ? '<button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 remove-add" title="Remove"><i class="bi bi-x"></i></button>' : ''}
                          </div>
                        </div>
                        ${canEnterItemAmounts ? '<button type="button" class="btn btn-sm btn-outline-secondary py-0 add-amount-btn" title="Add another amount line"><i class="bi bi-plus-lg" aria-hidden="true"></i><span class="visually-hidden">Add amount</span></button>' : ''}
                      </div>
                    </td>
                    <td class="text-center pf-item-remove-cell"><button type="button" class="btn btn-outline-danger btn-sm remove-row" aria-label="Delete line"><i class="bi bi-trash3" aria-hidden="true"></i></button></td>
                  </tr>`;
                syncAddRemoveButtons(tbody);
                recalc();
              }
              // clear tracking/serial input
              const serial = document.getElementById('serialInput');
              if (serial) serial.value = '';
              // update URL quietly
              try { window.history.replaceState({}, '', u.pathname + '?' + u.searchParams.toString()); } catch(_) {}
            } catch(_) {
              // fallback to navigation if URL parsing fails
              window.location.href = redirect;
            }
          } else if (data.prompt_bill && redirect) {
            // Status change to in_transit: navigate to show bill prompt modal
            window.location.href = redirect;
          }
        } catch (err) {
          showToast('error', 'Network error', 'Could not save right now. Please try again.');
        } finally {
          isSubmitting = false;
          snap.forEach(function (s) {
            s.el.disabled = false;
            s.el.innerHTML = s.html;
          });
        }
      }

      ajaxSubmit();
    });
  }

  // Sync recDateSummary with parcel date
  const parcelDateEl = document.getElementById('parcelDate');
  const recDateSummaryEl = document.getElementById('recDateSummary');
  if (parcelDateEl && recDateSummaryEl) {
    function syncRecDate() { recDateSummaryEl.textContent = parcelDateEl.value || '—'; }
    parcelDateEl.addEventListener('change', syncRecDate);
    parcelDateEl.addEventListener('input', syncRecDate);
    syncRecDate();
  }

  function recalc(){
    if (lockAll) { return; }
    // When item amounts are allowed, derive from Qty × Rate and show Amount per row
    // EXCEPT during price-only edit (user types price manually)
    if (canEnterItemAmounts && !(isEdit && priceOnly)) {
      let total = 0;
      const rows = table.querySelectorAll('tbody tr');
      rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value || '0');
        const rateInput = row.querySelector('.item-rate');
        const rate = parseFloat(rateInput?.value || '0') || 0;
        const addInputs = row.querySelectorAll('.item-add');
        let addSum = 0;
        addInputs.forEach(inp => { addSum += parseFloat(inp?.value || '0') || 0; });
        const base = (qty > 0 && rate > 0) ? (qty * rate) : 0;
        const line = base + addSum;
        total += line;
        const amountEl = row.querySelector('.item-amount');
        const addSumEl = row.querySelector('.item-add-sum');
        if (amountEl) amountEl.textContent = base > 0 ? base.toFixed(2) : '—';
        if (addSumEl) addSumEl.textContent = addSum > 0 ? addSum.toFixed(2) : '—';
      });
      if (totalDisplay) totalDisplay.textContent = total > 0 ? total.toFixed(2) : '—';
      if (totalPrice && !totalPrice.disabled) totalPrice.value = total > 0 ? total.toFixed(2) : '';
      return;
    }





    // Otherwise (other branches), show price minus discount on edit
    if (isEdit) {
      const p = parseFloat(totalPrice?.value || '0') || 0;
      const d = parseFloat(discountInput?.value || '0') || 0;
      const v = Math.max(0, p - Math.max(0, d));
      totalDisplay.textContent = v > 0 ? v.toFixed(2) : '—';
      return;
    }
    // Default fallback for create in other branches
    totalDisplay.textContent = totalPrice?.value ? String(totalPrice.value) : '—';
  }

  // Supplier phone hint + Vehicle display
  const supplierSelect = document.getElementById('supplierSelect');
  const supplierPhoneHint = document.getElementById('supplierPhoneHint');
  function updateSupplierHint(){
    if (!supplierSelect || !supplierPhoneHint) return;
    const opt = supplierSelect.options[supplierSelect.selectedIndex];
    const ph = opt ? (opt.getAttribute('data-phone') || '') : '';
    supplierPhoneHint.textContent = ph ? ('Supplier Phone: ' + ph) : '';
  }
  supplierSelect?.addEventListener('change', updateSupplierHint);
  updateSupplierHint();

  const vehicleSelect = document.getElementById('vehicleSelect');
  const vehicleInput = document.getElementById('vehicleInput');
  const recVehicle = document.getElementById('recVehicle');
  function updateVehicle(){
    if (!recVehicle) return;
    const v = vehicleSelect ? (vehicleSelect.value || '') : (vehicleInput?.value || '');
    recVehicle.textContent = v && v.trim() !== '' ? v : '—';
  }
  vehicleSelect?.addEventListener('change', updateVehicle);
  vehicleInput?.addEventListener('input', updateVehicle);
  updateVehicle();

  const customerSearchInput = document.getElementById('customerSearch');
  const customerSelectMain = document.getElementById('customerSelectHidden') || document.querySelector('select[name="customer_id"]');
  const customerSearchResults = document.getElementById('customerSearchResults');
  function normalize(s){ return String(s || '').toLowerCase().replace(/\s+/g,' ').trim(); }
  function digits(s){ return String(s || '').replace(/\D+/g,''); }
  function formatCustomerLabel(c){
    const nm = String(c.name || '').trim();
    const ph = String(c.phone || '').trim();
    if (nm && ph) return nm + ' (' + ph + ')';
    return nm || ph || ('Customer #' + String(c.id || ''));
  }
  function setCustomer(id){
    if (!customerSelectMain) return;
    customerSelectMain.value = String(id || '');
    customerSelectMain.dispatchEvent(new Event('change'));
    customerSelectMain.dispatchEvent(new Event('input'));
  }
  function hideCustomerResults(){
    if (customerSearchResults) customerSearchResults.style.display = 'none';
    if (customerSearchResults) customerSearchResults.innerHTML = '';
  }
  function showCustomerResults(items){
    if (!customerSearchResults) return;
    if (!items || items.length === 0) { hideCustomerResults(); return; }
    customerSearchResults.innerHTML = items.map(function(c){
      const label = formatCustomerLabel(c);
      return `<button type="button" class="list-group-item list-group-item-action" data-customer-id="${c.id}">${label}</button>`;
    }).join('');
    customerSearchResults.style.display = '';
  }
  function findMatches(qRaw){
    const q = normalize(qRaw);
    const qd = digits(qRaw);
    if (!q && !qd) return [];
    const scored = [];
    customersSearchData.forEach(function(c){
      if (!c || !c.id) return;
      const n = normalize(c.name);
      const p = String(c.phone || '').trim();
      const pd = digits(p);
      let score = 0;
      if (qd && pd && (pd === qd)) score += 100;
      if (qd && pd && (pd.endsWith(qd) || qd.endsWith(pd))) score += 60;
      if (q && n === q) score += 80;
      if (q && n.startsWith(q)) score += 40;
      if (q && n.includes(q)) score += 20;
      if (q && normalize(p).includes(q)) score += 10;
      if (score > 0) scored.push({c, score});
    });
    scored.sort(function(a,b){ return b.score - a.score; });
    return scored.map(x=>x.c);
  }
  function onCustomerSearch(){
    if (lockAll || priceOnly) return;
    const qRaw = String(customerSearchInput?.value || '').trim();
    const matches = findMatches(qRaw).slice(0, 15);
    showCustomerResults(matches);
  }
  customerSearchInput?.addEventListener('input', onCustomerSearch);
  customerSearchInput?.addEventListener('focus', onCustomerSearch);
  customerSearchInput?.addEventListener('keydown', function(e){
    if (e.key === 'Escape') { hideCustomerResults(); }
    if (e.key === 'Enter') {
      const qRaw = String(customerSearchInput?.value || '').trim();
      const matches = findMatches(qRaw);
      if (matches.length === 1) {
        e.preventDefault();
        hideCustomerResults();
        if (forceLastBillFlow && !isEdit) {
          showLastBillThenSelect(matches[0]);
          customerSearchInput.value = formatCustomerLabel(matches[0]);
        } else {
          setCustomer(matches[0].id);
          customerSearchInput.value = formatCustomerLabel(matches[0]);
        }
      }
    }
  });
  // Customer selection should happen AFTER showing last bill preview
  let pendingCustomerPick = null;
  const clbModalEl = document.getElementById('customerLastBillModal');
  const clbFrame = document.getElementById('clbFrame');
  const clbNoBill = document.getElementById('clbNoBill');
  const clbCustomerName = document.getElementById('clbCustomerName');
  const clbBillId = document.getElementById('clbBillId');
  const clbBalance = document.getElementById('clbBalance');
  const clbSelectBtn = document.getElementById('clbSelectBtn');
  function formatMoneyClb(n) {
    const x = Number(n);
    if (!isFinite(x)) return '—';
    return x.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  async function showLastBillThenSelect(customer) {
    if (!customer || !customer.id) return;
    pendingCustomerPick = customer;
    if (clbCustomerName) clbCustomerName.textContent = formatCustomerLabel(customer);
    if (clbBillId) clbBillId.textContent = '—';
    if (clbBalance) {
      clbBalance.textContent = '—';
      clbBalance.classList.remove('text-danger', 'text-success');
    }
    if (clbNoBill) clbNoBill.classList.add('d-none');
    if (clbFrame) clbFrame.src = 'about:blank';

    // Fetch customer summary to get last_delivery_note_id + outstanding due (balance)
    try {
      const url = '<?php echo Helpers::baseUrl('index.php?page=customers&action=summary'); ?>' + '&id=' + encodeURIComponent(String(customer.id));
      const res = await fetch(url, { headers: { 'Accept':'application/json' } });
      const data = res.ok ? await res.json() : null;
      if (data && !data.error && clbBalance && data.due !== undefined && data.due !== null) {
        clbBalance.textContent = formatMoneyClb(data.due);
        clbBalance.classList.toggle('text-danger', Number(data.due) > 0.0001);
        clbBalance.classList.toggle('text-success', Number(data.due) <= 0.0001);
      }
      const dnId = data && data.last_delivery_note_id ? parseInt(data.last_delivery_note_id, 10) : 0;
      if (dnId > 0) {
        if (clbBillId) clbBillId.textContent = '#' + String(dnId);
        let href = '<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=print&id='); ?>' + dnId + '&embed=1';
        if (clbFrame) clbFrame.src = href;
      } else {
        // Fallback: query last delivery note directly from delivery_notes
        try {
          const url2 = '<?php echo Helpers::baseUrl('index.php?page=parcels&action=last_delivery_note_id_for_customer'); ?>' + '&customer_id=' + encodeURIComponent(String(customer.id));
          const res2 = await fetch(url2, { headers: { 'Accept':'application/json' } });
          const data2 = res2.ok ? await res2.json() : null;
          const dnId2 = data2 && data2.data && data2.data.delivery_note_id ? parseInt(data2.data.delivery_note_id, 10) : 0;
          if (dnId2 > 0) {
            if (clbBillId) clbBillId.textContent = '#' + String(dnId2);
            let href = '<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=print&id='); ?>' + dnId2 + '&embed=1';
            if (clbFrame) clbFrame.src = href;
          } else {
            if (clbNoBill) clbNoBill.classList.remove('d-none');
          }
        } catch(_) {
          if (clbNoBill) clbNoBill.classList.remove('d-none');
        }
      }
    } catch(_) {
      if (clbNoBill) clbNoBill.classList.remove('d-none');
    }

    if (clbModalEl && window.bootstrap) {
      const modal = bootstrap.Modal.getOrCreateInstance(clbModalEl);
      modal.show();
    } else {
      // Fallback: directly select if modal unavailable
      setCustomer(customer.id);
      if (customerSearchInput) customerSearchInput.value = formatCustomerLabel(customer);
      hideCustomerResults();
    }
  }

  customerSearchResults?.addEventListener('click', function(e){
    const btn = e.target.closest('[data-customer-id]');
    if (!btn) return;
    const id = parseInt(btn.getAttribute('data-customer-id') || '0');
    const c = customersSearchData.find(x => parseInt(x.id||0) === id) || { id };
    hideCustomerResults();
    if (forceLastBillFlow && !isEdit) {
      showLastBillThenSelect(c);
    } else {
      setCustomer(id);
      if (customerSearchInput) customerSearchInput.value = formatCustomerLabel(c);
    }
  });

  clbSelectBtn?.addEventListener('click', function(){
    if (!pendingCustomerPick) return;
    setCustomer(pendingCustomerPick.id);
    if (customerSearchInput) customerSearchInput.value = formatCustomerLabel(pendingCustomerPick);
    pendingCustomerPick = null;
    try {
      if (clbModalEl && window.bootstrap) bootstrap.Modal.getOrCreateInstance(clbModalEl).hide();
    } catch(_) {}
  });
  document.addEventListener('click', function(e){
    if (!customerSearchResults || !customerSearchInput) return;
    if (e.target === customerSearchInput) return;
    if (customerSearchResults.contains(e.target)) return;
    hideCustomerResults();
  });

  // Auto-pick vehicle + delivery route name when customer / branches / date change
  const customerSel = document.querySelector('select[name="customer_id"]');
  const toBranchSel = document.querySelector('select[name="to_branch_id"]');
  const fromBranchSel = document.querySelector('select[name="from_branch_id"]');
  const parcelDateEl2 = document.getElementById('parcelDate');
  const routeHintEl = document.getElementById('deliveryRouteHint');
  function applyDeliveryRouteName(drName) {
    if (lockAll) return;
    const dr = String(drName || '').trim();
    if (!dr) return;
    const drSel = document.querySelector('select[name="delivery_route"]');
    const drInp = document.querySelector('input[name="delivery_route"]');
    if (drSel) {
      const wasDisabled = drSel.disabled;
      if (wasDisabled) drSel.disabled = false;
      let matchVal = '';
      Array.from(drSel.options).forEach(function(o) {
        if (String(o.value).toLowerCase() === dr.toLowerCase()) matchVal = o.value;
      });
      if (matchVal) {
        drSel.value = matchVal;
      } else {
        const opt = document.createElement('option');
        opt.value = dr;
        opt.textContent = dr;
        drSel.appendChild(opt);
        drSel.value = dr;
      }
      // If enhanced by Choices.js, sync visible UI too
      try {
        if (drSel._choices) {
          const choices = Array.from(drSel.options).map(function(o){
            return { value: o.value, label: o.textContent, selected: o.selected, disabled: o.disabled };
          });
          drSel._choices.setChoices(choices, 'value', 'label', true);
          drSel._choices.setChoiceByValue(drSel.value || dr);
        }
      } catch(_) { /* ignore */ }
      drSel.dispatchEvent(new Event('change'));
      drSel.dispatchEvent(new Event('input'));
      drSel.dispatchEvent(new Event('refresh-choices'));
      if (wasDisabled) drSel.disabled = true;
    } else if (drInp) {
      drInp.value = dr;
      drInp.dispatchEvent(new Event('input'));
    }
  }
  function applyDeliveryRouteFromCustomer() {
    if (lockAll) return;
    const cid = customerSel ? (customerSel.value || '').trim() : '';
    const toId = toBranchSel ? (toBranchSel.value || '').trim() : '';
    const fromId = fromBranchSel ? (fromBranchSel.value || '').trim() : '';
    const dateVal = parcelDateEl2 ? (parcelDateEl2.value || '').trim() : '';
    if (!cid || cid === '0') return;
    const date = dateVal || '<?php echo date('Y-m-d'); ?>';
    const url = '<?php echo Helpers::baseUrl('index.php?page=parcels&action=route_for_customer'); ?>'
      + '&customer_id=' + encodeURIComponent(cid)
      + '&to_branch_id=' + encodeURIComponent(toId)
      + '&from_branch_id=' + encodeURIComponent(fromId)
      + '&date=' + encodeURIComponent(date);
    fetch(url, { method: 'GET', headers: { 'Accept': 'application/json' } })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        const v = data && data.vehicle_no ? String(data.vehicle_no).trim() : '';
        if (v !== '') {
          if (vehicleSelect) {
            let exists = false;
            Array.from(vehicleSelect.options).forEach(function(o) { if ((o.value || '') === v) exists = true; });
            if (!exists) { const opt = document.createElement('option'); opt.value = v; opt.textContent = v; vehicleSelect.appendChild(opt); }
            const wasDisabled = vehicleSelect.disabled; if (wasDisabled) vehicleSelect.disabled = false;
            vehicleSelect.value = v;
            vehicleSelect.dispatchEvent(new Event('change')); vehicleSelect.dispatchEvent(new Event('input'));
            if (wasDisabled) vehicleSelect.disabled = true;
          } else if (vehicleInput) {
            vehicleInput.value = v;
            vehicleInput.dispatchEvent(new Event('input'));
          }
          updateVehicle();
        }
        let routeNm = data && data.delivery_route ? String(data.delivery_route).trim() : '';
        // Fallback: if API has no saved route, use selected branch label so route field is not left blank.
        if (routeNm === '') {
          const toLabel = toBranchSel?.options[toBranchSel?.selectedIndex]?.text?.trim() || '';
          const fromLabel = fromBranchSel?.options[fromBranchSel?.selectedIndex]?.text?.trim() || '';
          if (toLabel && toLabel !== '-- Select Branch --') routeNm = toLabel;
          else if (fromLabel && fromLabel !== '-- Select Branch --') routeNm = fromLabel;
        }
        if (routeNm !== '') applyDeliveryRouteName(routeNm);
        if (routeHintEl) {
          const parts = [];
          if (routeNm) parts.push('Route: ' + routeNm);
          if (v) parts.push('Vehicle: ' + v);
          if (data && data.delivery_date) parts.push(String(data.delivery_date));
          if (parts.length) {
            routeHintEl.textContent = parts.join(' · ');
            routeHintEl.classList.remove('d-none');
          } else {
            routeHintEl.classList.add('d-none');
          }
        }
      })
      .catch(function() { if (routeHintEl) routeHintEl.classList.add('d-none'); });
  }
  if (customerSel) customerSel.addEventListener('change', applyDeliveryRouteFromCustomer);
  if (toBranchSel) toBranchSel.addEventListener('change', applyDeliveryRouteFromCustomer);
  if (fromBranchSel) fromBranchSel.addEventListener('change', applyDeliveryRouteFromCustomer);
  if (parcelDateEl2) parcelDateEl2.addEventListener('change', applyDeliveryRouteFromCustomer);
  if (parcelDateEl2) parcelDateEl2.addEventListener('input', applyDeliveryRouteFromCustomer);

  // Quick Add Vehicle
  document.getElementById('qv_submit')?.addEventListener('click', async function(){
    const vInput = document.getElementById('qv_no');
    const v = (vInput?.value || '').trim();
    if (!v) { alert('Enter a vehicle number'); return; }
    const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
    if (!csrf) { alert('Session expired. Please refresh the page and try again.'); return; }
    const qvBtn = document.getElementById('qv_submit');
    if (qvBtn) { qvBtn.disabled = true; qvBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'; }
    try {
      const fd = new FormData();
      fd.append('csrf_token', csrf);
      fd.append('vehicle_no', v);
      const res = await fetch('<?php echo Helpers::baseUrl('index.php?page=vehicles&action=save'); ?>', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: fd
      });
      const raw = await res.text();
      const data = parseJsonResponse(raw);
      if (!res.ok || !data || typeof data.vehicle_no === 'undefined') {
        const msg = (data && data.error) ? data.error : (raw && raw.length < 220 ? raw.trim() : 'Could not save vehicle.');
        alert(msg || 'Could not save vehicle.');
        return;
      }
      if (vehicleSelect) {
        const idLabel = String(data.vehicle_no || v);
        let exists = false;
        Array.from(vehicleSelect.options).forEach(o=>{ if ((o.value||'') === idLabel) exists = true; });
        if (!exists) { const opt = document.createElement('option'); opt.value = idLabel; opt.textContent = idLabel; vehicleSelect.appendChild(opt); }
        const wasDisabled = vehicleSelect.disabled; if (wasDisabled) vehicleSelect.disabled = false;
        vehicleSelect.value = idLabel;
        vehicleSelect.dispatchEvent(new Event('change'));
        vehicleSelect.dispatchEvent(new Event('input'));
        if (wasDisabled) vehicleSelect.disabled = true;
      } else if (vehicleInput) {
        vehicleInput.value = String(data.vehicle_no || v);
        vehicleInput.dispatchEvent(new Event('input'));
      }
      if (vInput) vInput.value = '';
      const m = document.getElementById('quickAddVehicle');
      if (m && window.bootstrap && window.bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(m).hide();
      }
    } catch (e) {
      console.error('Quick Add Vehicle', e);
      alert('Could not add vehicle. ' + (e && e.message ? e.message : ''));
    } finally {
      if (qvBtn) { qvBtn.disabled = false; qvBtn.innerHTML = '<i class="bi bi-save"></i> Save &amp; Use'; }
    }
  });
  // Quick Add Supplier handlers
  const qsBtn = document.getElementById('qs_submit');
  qsBtn?.addEventListener('click', async function(){
    const name = (document.getElementById('qs_name')?.value || '').trim();
    const phone = (document.getElementById('qs_phone')?.value || '').trim();
    let branchId = parseInt(document.getElementById('qs_branch')?.value || '0', 10);
    const code = (document.getElementById('qs_code')?.value || '').trim();
    if (!name) { alert('Enter supplier name'); return; }
    const norm = name.toLowerCase().replace(/[^a-z0-9]+/g,'');
    if (norm === 'none' || norm === 'nonenone') { alert('Invalid supplier name'); return; }
    if (!branchId || branchId <= 0) {
      const fb = parseInt(document.querySelector('select[name="from_branch_id"]')?.value || '0', 10);
      branchId = fb > 0 ? fb : (userBranchId || 0);
    }
    if (!branchId || branchId <= 0) { alert('Select a branch'); return; }
    const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
    if (!csrf) { alert('Session expired. Please refresh the page and try again.'); return; }
    if (qsBtn) { qsBtn.disabled = true; qsBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'; }
    try {
      const fd = new FormData();
      fd.append('csrf_token', csrf);
      fd.append('ajax', '1');
      fd.append('id', '0');
      fd.append('name', name);
      fd.append('phone', phone);
      fd.append('branch_id', String(branchId));
      fd.append('supplier_code', code);
      const res = await fetch('<?php echo Helpers::baseUrl('index.php?page=suppliers&action=save'); ?>', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: fd
      });
      const raw = await res.text();
      const data = parseJsonResponse(raw);
      if (data && data.error) { alert(data.error); return; }
      if (!res.ok || !data || !data.id) {
        alert((raw && raw.length < 220 ? raw.trim() : null) || 'Could not save supplier.');
        return;
      }
      const sel = document.querySelector('select[name="supplier_id"]');
      if (sel) {
        const idStr = String(data.id);
        const label = String(data.name||name) + (data.phone ? ' (' + String(data.phone).trim() + ')' : (phone ? ' (' + phone + ')' : ''));
        let exists = false;
        Array.from(sel.options).forEach(o=>{ if (String(o.value) === idStr) { o.textContent = label; o.setAttribute('data-phone', data.phone || phone || ''); exists = true; } });
        if (!exists) { const opt=document.createElement('option'); opt.value=idStr; opt.textContent=label; opt.setAttribute('data-phone', data.phone || phone || ''); sel.appendChild(opt); }
        const wasDisabled = sel.disabled; if (wasDisabled) sel.disabled = false;
        sel.value = idStr;
        sel.dispatchEvent(new Event('change'));
        sel.dispatchEvent(new Event('input'));
        if (wasDisabled) sel.disabled = true;
      }
      ['qs_name','qs_phone','qs_code'].forEach(id=>{ const el=document.getElementById(id); if (el) el.value=''; });
      const qsBranch = document.getElementById('qs_branch'); if (qsBranch) qsBranch.value='0';
      const m = document.getElementById('quickAddSupplier');
      if (m && window.bootstrap && window.bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(m).hide();
      }
    } catch (e) {
      console.error('Quick Add Supplier', e);
      alert('Could not add supplier. ' + (e && e.message ? e.message : ''));
    } finally {
      if (qsBtn) { qsBtn.disabled = false; qsBtn.innerHTML = '<i class="bi bi-save"></i> Save &amp; Use'; }
    }
  });

  // Keep total display in sync while typing manual price in price-only mode
  totalPrice?.addEventListener('input', function(){
    if (priceOnly) {
      const v = parseFloat(totalPrice.value || '0');
      totalDisplay.textContent = v > 0 ? v.toFixed(2) : '—';
    }
  });

  table.addEventListener('input', function(e){
    const target = e.target;
    if (lockAll) return;
    if (!canEnterItemAmounts) return;
    if (target.classList.contains('item-qty') || target.classList.contains('item-rate') || target.classList.contains('item-add')) {
      recalc();
    }
  });

  function syncAddRemoveButtons(root){
    if (!root) return;
    let lists = [];
    if (root.querySelectorAll) {
      if (root.classList && root.classList.contains('item-add-list')) {
        lists = [root];
      } else {
        lists = root.querySelectorAll('.item-add-list');
      }
    }
    lists.forEach(function(list){
      const rows = list.querySelectorAll('.item-add-row');
      const hide = rows.length <= 1;
      rows.forEach(function(r){
        const btn = r.querySelector('.remove-add');
        if (btn) btn.style.display = hide ? 'none' : '';
      });
    });
  }

  // Initial state: hide unwanted X when only one add-amount row exists
  syncAddRemoveButtons(table);

  table.addEventListener('click', function(e){
    if (e.target.closest('.add-amount-btn')) {
      const cell = e.target.closest('.item-amount-cell');
      const list = cell?.querySelector('.item-add-list');
      if (!list) return;
      const nameMatch = list.querySelector('input[name]');
      const baseName = (nameMatch && nameMatch.name) ? nameMatch.name : 'items[1][additional_amounts][]';
      const div = document.createElement('div');
      div.className = 'd-flex gap-1 align-items-center item-add-row';
      div.innerHTML = `<input type="number" step="0.01" min="0" name="${baseName}" class="form-control form-control-sm item-add" placeholder="0"><button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 remove-add" title="Remove"><i class="bi bi-x"></i></button>`;
      list.appendChild(div);
      syncAddRemoveButtons(cell);
      recalc();
      return;
    }
    if (e.target.closest('.remove-add')) {
      const addRow = e.target.closest('.item-add-row');
      const list = addRow?.parentElement;
      if (!list || !addRow) return;
      const rows = list.querySelectorAll('.item-add-row');
      if (rows.length > 1) {
        addRow.remove();
        syncAddRemoveButtons(list);
        recalc();
        return;
      }
      // If this is the only remaining add-amount row, clear its value instead of no-op
      const inp = addRow.querySelector('input.item-add');
      if (inp) {
        inp.value = '';
        inp.dispatchEvent(new Event('input', { bubbles: true }));
      }
      syncAddRemoveButtons(list);
      recalc();
      return;
    }
    if (e.target.closest('.remove-row')) {
      const tr = e.target.closest('tr');
      tr.parentNode.removeChild(tr);
      recalc();
    }
  });

  addBtn?.addEventListener('click', function(){
    const tbody = table.querySelector('tbody');
    const idx = tbody.querySelectorAll('tr').length + 1;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="text-center align-middle pf-item-no-cell" data-label="">${idx}</td>
      <td data-label="Description"><input type="text" name="items[${idx}][description]" class="form-control item-desc" placeholder="Description"></td>
      <td data-label="Qty"><input type="number" step="0.01" name="items[${idx}][qty]" class="form-control item-qty" placeholder="Qty"></td>
      <td data-label="Rate"><input type="number" step="0.01" min="0" name="items[${idx}][rate]" class="form-control item-rate" ${canEnterItemAmounts ? '' : 'disabled'} placeholder="Rate"></td>
      <td class="align-middle pf-item-amt-cell" data-label="Amount">
        <span class="item-amount fw-semibold pf-amount-readout">—</span>
      </td>
      <td class="align-middle item-amount-cell" data-label="Additional">
        <div class="d-flex flex-column gap-1 pf-item-add-block pf-item-add-excel">
          <span class="item-add-sum fw-semibold pf-amount-readout">—</span>
          <span class="visually-hidden">Additional line amounts</span>
          <div class="pf-item-add-excel-wrap">
            <div class="pf-item-add-excel-tokens" aria-hidden="true"></div>
            <input type="text" class="pf-item-add-excel-field" autocomplete="off" inputmode="decimal" placeholder="Amount, +, Enter" ${canEnterItemAmounts ? '' : 'disabled'} aria-label="Additional amounts">
          </div>
          <div class="item-add-list d-flex flex-column gap-1">
            <div class="d-flex gap-1 align-items-center item-add-row">
              <input type="number" step="0.01" min="0" name="items[${idx}][additional_amounts][]" class="form-control form-control-sm item-add" placeholder="0" ${canEnterItemAmounts ? '' : 'disabled'}>
              <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 remove-add" title="Remove"><i class="bi bi-x"></i></button>
            </div>
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary py-0 add-amount-btn" title="Add another amount line"><i class="bi bi-plus-lg" aria-hidden="true"></i><span class="visually-hidden">Add amount</span></button>
        </div>
      </td>
      <td class="text-center pf-item-remove-cell"><button type="button" class="btn btn-outline-danger btn-sm remove-row pf-btn-icon-touch rounded-3" aria-label="Delete line"><i class="bi bi-trash3" aria-hidden="true"></i></button></td>
    `;
    tbody.appendChild(tr);
    syncAddRemoveButtons(tr);
    // Focus Description of the newly added row for quick typing
    const desc = tr.querySelector(`input[name="items[${idx}][description]"]`);
    if (desc) { desc.focus(); desc.select(); }
  });

  recalc();

  /* Excel-style additional amounts: visual layer over existing item-add[] inputs (no changes to recalc / names / classes on those inputs). */
  (function pfItemAddExcelLayer() {
    if (!table) return;
    function parseNumsFromText(s) {
      if (!s || !String(s).trim()) return [];
      return String(s).split(/\s*\+\s*/).map(function (p) { return p.trim(); })
        .filter(Boolean)
        .map(function (p) { return parseFloat(String(p).replace(',', '.')); })
        .filter(function (n) { return !isNaN(n) && n >= 0; });
    }
    function readPositiveValues(list) {
      var out = [];
      if (!list) return out;
      list.querySelectorAll('input.item-add').forEach(function (inp) {
        var v = parseFloat(inp && inp.value);
        if (!isNaN(v) && v > 0) out.push(v);
      });
      return out;
    }
    function ensureRowMarkup(baseName) {
      var div = document.createElement('div');
      div.className = 'd-flex gap-1 align-items-center item-add-row';
      div.innerHTML = '<input type="number" step="0.01" min="0" name="' + baseName + '" class="form-control form-control-sm item-add" placeholder="0"><button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 remove-add" title="Remove"><i class="bi bi-x"></i></button>';
      return div;
    }
    function applyValues(list, values) {
      var baseName = 'items[1][additional_amounts][]';
      var nm = list.querySelector('input[name]');
      if (nm && nm.name) baseName = nm.name;
      var need = Math.max(1, values.length);
      var rows = list.querySelectorAll('.item-add-row');
      while (rows.length < need) {
        list.appendChild(ensureRowMarkup(baseName));
        rows = list.querySelectorAll('.item-add-row');
      }
      while (rows.length > need) {
        rows[rows.length - 1].remove();
        rows = list.querySelectorAll('.item-add-row');
      }
      rows = list.querySelectorAll('.item-add-row');
      for (var i = 0; i < rows.length; i++) {
        var inp = rows[i].querySelector('input.item-add');
        if (!inp) continue;
        if (i < values.length) {
          inp.value = Number(values[i]).toFixed(2);
        } else {
          inp.value = '';
        }
      }
      syncAddRemoveButtons(list);
      var first = list.querySelector('input.item-add');
      if (first) first.dispatchEvent(new Event('input', { bubbles: true }));
    }
    function initBlock(block) {
      var wrap = block.querySelector('.pf-item-add-excel-wrap');
      if (!wrap || wrap.dataset.pfExcelInit) return;
      var list = block.querySelector('.item-add-list');
      var field = wrap.querySelector('.pf-item-add-excel-field');
      var tokensEl = wrap.querySelector('.pf-item-add-excel-tokens');
      if (!list || !tokensEl) return;
      wrap.dataset.pfExcelInit = '1';
      try { list.setAttribute('inert', ''); } catch (_) {}
      function render() {
        tokensEl.innerHTML = '';
        var vals = readPositiveValues(list);
        vals.forEach(function (v, idx) {
          var tok = document.createElement('span');
          tok.className = 'pf-item-add-excel-token';
          tok.appendChild(document.createTextNode(Number(v).toFixed(2)));
          var rm = document.createElement('button');
          rm.type = 'button';
          rm.className = 'pf-item-add-excel-remove';
          rm.setAttribute('aria-label', 'Remove');
          rm.appendChild(document.createTextNode('\u00D7'));
          rm.addEventListener('click', function (e) {
            e.preventDefault();
            if (lockAll || !canEnterItemAmounts) return;
            var cur = readPositiveValues(list);
            cur.splice(idx, 1);
            applyValues(list, cur);
            render();
          });
          tok.appendChild(rm);
          tokensEl.appendChild(tok);
        });
      }
      function commitField() {
        if (!field || field.disabled) return;
        var t = field.value.trim();
        if (t === '') return;
        if (t.indexOf('+') !== -1) {
          applyValues(list, parseNumsFromText(t));
        } else {
          var n = parseFloat(String(t).replace(',', '.'));
          if (isNaN(n) || n < 0) {
            field.value = '';
            return;
          }
          var cur = readPositiveValues(list);
          cur.push(n);
          applyValues(list, cur);
        }
        field.value = '';
        render();
      }
      render();
      if (field && canEnterItemAmounts && !lockAll) {
        field.addEventListener('keydown', function (e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            commitField();
          }
        });
        field.addEventListener('blur', function (e) {
          if (wrap.contains(e.relatedTarget)) return;
          commitField();
        });
      }
    }
    table.querySelectorAll('.pf-item-add-block.pf-item-add-excel').forEach(initBlock);
    var tbody = table.querySelector('tbody');
    if (tbody) {
      var mo = new MutationObserver(function () {
        tbody.querySelectorAll('.pf-item-add-block.pf-item-add-excel').forEach(initBlock);
      });
      mo.observe(tbody, { childList: true, subtree: true });
    }
  })();

  // Auto update Customer and To labels
  const customerSelect = document.querySelector('select[name="customer_id"]');
  const fromBranchSelect = document.querySelector('select[name="from_branch_id"]');
  const toBranchSelect = document.querySelector('select[name="to_branch_id"]');
  const customerDisplay = document.getElementById('customerDisplay');
  const fromBranchDisplay = document.getElementById('fromBranchDisplay');
  const toBranchDisplay = document.getElementById('toBranchDisplay');
  const customerLocDisplay = document.getElementById('customerLocDisplay');
  const deliveryLocationInput = document.getElementById('deliveryLocationInput');
  let deliveryLocationTouched = false;
  deliveryLocationInput?.addEventListener('input', function(){ deliveryLocationTouched = true; });
  const toBranchSuggest = document.getElementById('toBranchSuggest');
  const customersData = <?php echo json_encode(array_map(function($c){ return ['id'=>(int)$c['id'],'delivery_location'=>$c['delivery_location'] ?? '']; }, $customersAll ?? []), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  /** Label for receipt summary — use value lookup (Choices.js can leave selectedIndex on placeholder while value is set) */
  function branchSelectLabel(sel) {
    if (!sel) return '—';
    let v = String(sel.value || '').trim();
    if (!v || v === '0') return '—';
    try {
      if (sel._choices && typeof sel._choices.getValue === 'function') {
        var gv = sel._choices.getValue(true);
        var raw = Array.isArray(gv) ? gv[0] : gv;
        if (raw !== undefined && raw !== null && String(raw) !== '') v = String(raw);
      }
    } catch (_) { /* ignore */ }
    var opt = Array.from(sel.options).find(function (o) { return String(o.value) === v; });
    if (opt) {
      var t = (opt.textContent || opt.text || '').trim();
      if (t) return t;
    }
    var bd = branchesData.find(function (b) { return String(b.id) === v; });
    return bd && bd.name ? String(bd.name) : '—';
  }
  function updateMeta(){
    const selIdx = customerSelect?.selectedIndex ?? -1;
    const custText = customerSelect?.options[selIdx]?.text || '-- Select Customer --';
    const fromText = branchSelectLabel(fromBranchSelect);
    const toText = branchSelectLabel(toBranchSelect);
    if (customerDisplay) customerDisplay.textContent = custText;
    if (fromBranchDisplay) fromBranchDisplay.textContent = fromText;
    if (toBranchDisplay) toBranchDisplay.textContent = toText;
    // Receipt summary uses customerDisplay / fromBranchDisplay / toBranchDisplay (single row)
    // Customer delivery location
    const custId = parseInt(customerSelect?.value || '0');
    const cRow = customersData.find(c => c.id === custId);
    const loc = (cRow?.delivery_location || '').trim();
    if (customerLocDisplay) customerLocDisplay.textContent = loc || '—';
    if (deliveryLocationInput && !deliveryLocationTouched) {
      deliveryLocationInput.value = loc || '';
    }
    // Suggest To Branch by matching branch name in location
    if (toBranchSuggest) toBranchSuggest.innerHTML = '';
    if (loc) {
      const locLow = loc.toLowerCase();
      const matches = branchesData.filter(b => String(b.name||'').toLowerCase() !== '' && locLow.includes(String(b.name||'').toLowerCase()));
      if (matches.length === 1) {
        // Auto-select
        if (toBranchSelect) {
          syncBranchSelect(toBranchSelect, String(matches[0].id));
        }
      } else if (matches.length > 1 && toBranchSuggest) {
        const html = 'Suggested: ' + matches.map(m => `<a href="#" data-pick-branch="${m.id}">${m.name}</a>`).join(' | ');
        toBranchSuggest.innerHTML = html;
      }
    }
  }
  async function fetchCustomerSummary(){
    const custId = parseInt(customerSelect?.value || '0');
    const box = document.getElementById('customerSummary');
    if (!box) return;
    box.innerHTML = '';
    if (!custId) return;
    try {
      const url = '<?php echo Helpers::baseUrl('index.php?page=customers&action=summary'); ?>' + '&id=' + custId;
      const res = await fetch(url, { headers: { 'Accept':'application/json' } });
      if (!res.ok) return;
      const data = await res.json();
      if (data && !data.error && ((data.total_delivery_notes||0) > 0 || (data.total_parcels||0) > 0)) {
        const due = (data.due||0);
        const cls = due > 0 ? 'alert-warning' : 'alert-info';
        const dueHtml = due > 0 ? `<div><strong>Due:</strong> <span class="text-danger">${due.toFixed(2)}</span></div>` : '';
        const links = [];
        if (data.today_delivery_note_id) {
          links.push(`<a class="btn btn-sm btn-primary me-1" target="_blank" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=view&id='); ?>${data.today_delivery_note_id}">Open Today's Bill</a>`);
        }
        if (data.last_delivery_note_id && data.last_delivery_note_id !== data.today_delivery_note_id) {
          links.push(`<a class=\"btn btn-sm btn-outline-primary\" target=\"_blank\" href=\"<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=view&id='); ?>${data.last_delivery_note_id}\">Open Last Bill</a>`);
        }
        // View Details link: prefer phone when available, else fall back to name
        const baseSearch = '<?php echo Helpers::baseUrl('index.php?page=search'); ?>';
        const qPhone = (data.phone || '').trim();
        const qName = (data.name || '').trim();
        let detailsUrl = baseSearch;
        if (qPhone) {
          detailsUrl += '&phone=' + encodeURIComponent(qPhone);
        } else if (qName) {
          detailsUrl += '&name=' + encodeURIComponent(qName);
        }
        links.unshift(`<a class=\"btn btn-sm btn-outline-primary me-1\" href=\"${detailsUrl}\" target=\"_blank\">View Details</a>`);
        box.innerHTML = `
          <div class="alert ${cls} py-2">
            <div class="fw-semibold">Previous activity found for ${data.name} (${data.phone})</div>
            <div class="small text-muted">Delivery Notes: ${data.total_delivery_notes}, Parcels: ${data.total_parcels}${data.last_delivery_date ? ', Last: ' + data.last_delivery_date : ''}</div>
            ${dueHtml}
            <div class="mt-1 d-flex flex-wrap gap-1">
              ${links.join('')}
            </div>
          </div>`;
      }
    } catch (e) { /* ignore */ }
  }
  customerSelect?.addEventListener('change', updateMeta);
  customerSelect?.addEventListener('change', fetchCustomerSummary);
  // Auto-populate previous billing info when customer changes (only if fields are empty/unselected)
  async function applyPreviousBillingInfo(){
    if (lockAll || priceOnly) return;
    const custId = parseInt(customerSelect?.value || '0');
    if (!custId) return;
    try {
      const url = '<?php echo Helpers::baseUrl('index.php?page=parcels&action=last_billing_for_customer'); ?>' + '&customer_id=' + encodeURIComponent(String(custId));
      const res = await fetch(url, { headers: { 'Accept':'application/json' } });
      if (!res.ok) return;
      const payload = await res.json();
      if (!payload || payload.ok !== true || !payload.data) return;
      const d = payload.data;
      // Only set if current value is empty/zero
      if (fromBranchSelect && (!fromBranchSelect.value || fromBranchSelect.value === '0') && parseInt(d.from_branch_id||0) > 0) {
        syncBranchSelect(fromBranchSelect, String(d.from_branch_id));
      }
      if (toBranchSelect && (!toBranchSelect.value || toBranchSelect.value === '0') && parseInt(d.to_branch_id||0) > 0) {
        syncBranchSelect(toBranchSelect, String(d.to_branch_id));
      }
      const suppSel = document.getElementById('supplierSelect');
      if (suppSel && (!suppSel.value || suppSel.value === '0') && parseInt(d.supplier_id||0) > 0) {
        suppSel.value = String(d.supplier_id);
        suppSel.dispatchEvent(new Event('change'));
      }
      const drSel = document.querySelector('select[name="delivery_route"]');
      const drInp = document.querySelector('input[name="delivery_route"]');
      if (d.delivery_route && ((drSel && (!drSel.value || drSel.value.trim() === '')) || (drInp && (!drInp.value || drInp.value.trim() === '')))) {
        applyDeliveryRouteName(d.delivery_route);
      }
      const v = String(d.vehicle_no || '').trim();
      if (v) {
        const currentV = vehicleSelect ? (vehicleSelect.value || '') : (vehicleInput?.value || '');
        if (!currentV || currentV.trim() === '') {
          if (vehicleSelect) {
            let exists = false;
            Array.from(vehicleSelect.options).forEach(function(o){ if ((o.value||'') === v) exists = true; });
            if (!exists) { const opt = document.createElement('option'); opt.value = v; opt.textContent = v; vehicleSelect.appendChild(opt); }
            const wasDisabled = vehicleSelect.disabled; if (wasDisabled) vehicleSelect.disabled = false;
            vehicleSelect.value = v;
            vehicleSelect.dispatchEvent(new Event('change'));
            vehicleSelect.dispatchEvent(new Event('input'));
            if (wasDisabled) vehicleSelect.disabled = true;
          } else if (vehicleInput) {
            vehicleInput.value = v;
            vehicleInput.dispatchEvent(new Event('input'));
          }
          updateVehicle();
        }
      }
    } catch(_) { /* ignore */ }
    finally {
      try { applyDeliveryRouteFromCustomer(); } catch(_) {}
    }
  }
  customerSelect?.addEventListener('change', applyPreviousBillingInfo);
  fromBranchSelect?.addEventListener('change', updateMeta);
  toBranchSelect?.addEventListener('change', updateMeta);
  fromBranchSelect?.addEventListener('input', updateMeta);
  toBranchSelect?.addEventListener('input', updateMeta);
  document.getElementById('parcelForm')?.addEventListener('click', function (e) {
    if (!e.target.closest('.pf-branches-section select.form-select')) return;
    window.requestAnimationFrame(function () { try { updateMeta(); } catch (_) {} });
  });
  function clearBranchFieldInvalid(sel) {
    if (!sel) return;
    sel.setCustomValidity('');
    sel.classList.remove('is-invalid');
    const wrap = sel.closest('.choices');
    if (wrap) wrap.classList.remove('is-invalid');
  }
  fromBranchSelect?.addEventListener('change', function () { clearBranchFieldInvalid(this); });
  toBranchSelect?.addEventListener('change', function () { clearBranchFieldInvalid(this); });
  function bootParcelBranchUi() {
    const userBranchId = <?php echo (int)((Auth::user()['branch_id'] ?? 0)); ?>;
    if (fromBranchSelect && (!fromBranchSelect.value || fromBranchSelect.value === '0') && userBranchId > 0) {
      const opt = Array.from(fromBranchSelect.options).find(function (o) { return parseInt(o.value || '0', 10) === userBranchId; });
      if (opt) { syncBranchSelect(fromBranchSelect, String(userBranchId)); }
    }
    updateMeta();
    fetchCustomerSummary();
    setTimeout(function () { applyPreviousBillingInfo(); }, 120);
  }
  loadParcelBranchesFromApi().then(bootParcelBranchUi).catch(bootParcelBranchUi);

  let __pfBranchReloadTimer = null;
  window.addEventListener('focus', function () {
    if (lockAll || priceOnly) return;
    if (__pfBranchReloadTimer) clearTimeout(__pfBranchReloadTimer);
    __pfBranchReloadTimer = setTimeout(function () {
      loadParcelBranchesFromApi().then(function () {
        try { updateMeta(); } catch (_) {}
      }).catch(function () {});
    }, 400);
  });

  // Show bill prompt modal when redirected after setting in_transit
  (function(){
    const m = document.getElementById('billPromptModal');
    if (!m || !window.bootstrap) return;
    const modal = new bootstrap.Modal(m);
    modal.show();
  })();

  // Intercept 'Open ... Bill' links to show inline preview instead of navigating
  (function(){
    const summary = document.getElementById('customerSummary');
    const wrap = document.getElementById('billPreview');
    const frame = document.getElementById('billPreviewFrame');
    const closeBtn = document.getElementById('billPreviewClose');
    if (!summary || !wrap || !frame) return;
    summary.addEventListener('click', function(e){
      const a = e.target.closest('a[href*="page=delivery_notes"][href*="action="]');
      if (!a) return;
      e.preventDefault();
      let href = a.getAttribute('href');
      try {
        const u = new URL(href, window.location.origin);
        // Use print layout for clean embed
        u.searchParams.set('action','print');
        u.searchParams.set('embed','1');
        href = u.toString();
      } catch(_) { /* keep href */ }
      frame.src = href;
      wrap.style.display = '';
      // Scroll into view for convenience
      wrap.scrollIntoView({behavior:'smooth', block:'start'});
    });
    closeBtn?.addEventListener('click', function(){
      wrap.style.display = 'none';
      frame.src = 'about:blank';
    });
  })();

  // Handle clicking suggestion links
  toBranchSuggest?.addEventListener('click', function(e){
    const a = e.target.closest('[data-pick-branch]');
    if (a) {
      e.preventDefault();
      const id = a.getAttribute('data-pick-branch');
      if (toBranchSelect) {
        syncBranchSelect(toBranchSelect, id);
      }
    }
  });

  // Delivery Location finder (suggest customers by location)
  const allCustomers = <?php echo json_encode(array_map(function($c){ return [
    'id'=>(int)$c['id'],
    'name'=>$c['name'],
    'phone'=>$c['phone'],
    'delivery_location'=>$c['delivery_location'] ?? ''
  ]; }, $customersAll ?? []), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  const locQ = document.getElementById('locQuery');
  const locRes = document.getElementById('locResults');
  const customerSelect2 = document.querySelector('select[name="customer_id"]');
  function renderLoc(matches){
    if (!locRes) return;
    if (!matches || matches.length===0) { locRes.innerHTML = '<div class="text-muted">No matches.</div>'; return; }
    locRes.innerHTML = matches.slice(0,50).map(c => `
      <div class="d-flex justify-content-between align-items-center border-bottom py-1">
        <div>
          <div><strong>${c.name}</strong> <span class="text-muted">(${c.phone})</span></div>
          <div class="text-muted">${c.delivery_location || ''}</div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" data-pick="${c.id}">Use</button>
      </div>
    `).join('');
  }
  function doSearch(){
    const q = (locQ?.value || '').toLowerCase().trim();
    if (!q) { renderLoc([]); return; }
    const m = allCustomers.filter(c => (c.delivery_location||'').toLowerCase().includes(q));
    // boost by To Branch name if selected
    const toText = toBranchSelect?.options[toBranchSelect.selectedIndex]?.text?.toLowerCase() || '';
    if (toText) {
      const withBranch = m.filter(c => (c.delivery_location||'').toLowerCase().includes(toText));
      const withoutBranch = m.filter(c => !(c.delivery_location||'').toLowerCase().includes(toText));
      renderLoc([...withBranch, ...withoutBranch]);
    } else {
      renderLoc(m);
    }
  }
  locQ?.addEventListener('input', doSearch);
  toBranchSelect?.addEventListener('change', doSearch);
  locRes?.addEventListener('click', function(e){
    const btn = e.target.closest('[data-pick]');
    if (btn) {
      const id = parseInt(btn.getAttribute('data-pick') || '0', 10);
      const c = (allCustomers || []).find(x => parseInt(x.id||0) === id) || { id };
      if (forceLastBillFlow && !isEdit) {
        if (customerSearchInput) customerSearchInput.value = formatCustomerLabel(c);
        showLastBillThenSelect({ id: c.id, name: c.name || '', phone: c.phone || '' });
      } else if (customerSelect2) {
        customerSelect2.value = String(id);
        customerSelect2.dispatchEvent(new Event('change'));
      }
    }
  });

  const fromSel = document.querySelector('select[name="from_branch_id"]');
  const toSel = document.querySelector('select[name="to_branch_id"]');
  // Quick Add Customer via AJAX (try dedicated endpoint first, then full save)
  document.getElementById('qa_submit')?.addEventListener('click', async function(e){
    e.preventDefault();
    const name = (document.getElementById('qa_name')?.value || '').trim();
    const phone = (document.getElementById('qa_phone_input')?.value || '').trim();
    const email = (document.getElementById('qa_email')?.value || '').trim();
    const address = (document.getElementById('qa_address')?.value || '').trim();
    const delivery_location = (document.getElementById('qa_delivery_location')?.value || '').trim();
    const type = (document.getElementById('qa_type')?.value || '').trim();
    // Only require Name; others optional
    if (!name) { alert('Please enter Name before saving.'); return; }
    // Basic email pattern check only if email provided
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { alert('Enter a valid email'); return; }
    const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
    if (!csrf) { alert('Session expired. Please refresh the page and try again.'); return; }
    const btn = document.getElementById('qa_submit');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'; }
    const fetchOpts = { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } };
    try {
      const fdQuick = new FormData();
      fdQuick.append('csrf_token', csrf);
      fdQuick.append('ajax', '1');
      fdQuick.append('name', name);
      fdQuick.append('phone', phone);
      fdQuick.append('email', email);
      fdQuick.append('address', address);
      fdQuick.append('delivery_location', delivery_location);
      fdQuick.append('customer_type', type);
      let res = await fetch('<?php echo Helpers::baseUrl('index.php?page=quick_add_customer'); ?>', Object.assign({}, fetchOpts, { body: fdQuick }));
      let data = parseJsonResponse(await res.text());
      if (data && data.error) {
        alert(data.error);
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-save"></i> Save & Use'; }
        return;
      }
      if (!res.ok || !data || !parseInt(String(data.id || '0'), 10)) {
        const fdFull = new FormData();
        fdFull.append('csrf_token', csrf);
        fdFull.append('ajax', '1');
        fdFull.append('id', '0');
        fdFull.append('name', name);
        fdFull.append('phone', phone);
        fdFull.append('email', email);
        fdFull.append('address', address);
        fdFull.append('delivery_location', delivery_location);
        fdFull.append('customer_type', type);
        res = await fetch('<?php echo Helpers::baseUrl('index.php?page=customers&action=save'); ?>', Object.assign({}, fetchOpts, { body: fdFull }));
        data = parseJsonResponse(await res.text());
        if (data && data.error) { alert(data.error); if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-save"></i> Save & Use'; } return; }
        if (!res.ok || !data || !parseInt(String(data.id || '0'), 10)) {
          alert(data && data.error ? data.error : ('Request failed. ' + (res.status ? 'Status: ' + res.status : '')));
          if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-save"></i> Save & Use'; }
          return;
        }
      }
      // Ensure it shows up in the Customer dropdown immediately
      const sel = document.querySelector('select[name="customer_id"]');
      if (sel) {
        const idStr = String(data.id);
        const phonePart = (data.phone ? ' (' + String(data.phone).trim() + ')' : '');
        const emailPart = (data.email ? ' [' + String(data.email).trim() + ']' : '');
        const label = String(data.name || '').trim() + phonePart + emailPart;
        // Avoid duplicate options; update text if exists
        let found = false;
        Array.from(sel.options).forEach(o => {
          if (String(o.value) === idStr) { o.textContent = label; found = true; }
        });
        if (!found) {
          const opt = document.createElement('option');
          opt.value = idStr;
          opt.textContent = label;
          sel.appendChild(opt);
        }
        if (Array.isArray(customersSearchData)) {
          const cid = parseInt(idStr, 10);
          if (!customersSearchData.find(x => parseInt(x.id||0) === cid)) {
            customersSearchData.push({ id: cid, name: String(data.name||'').trim(), phone: String(data.phone||'').trim() });
          }
        }
        if (forceLastBillFlow && !isEdit) {
          if (customerSearchInput) customerSearchInput.value = label;
          showLastBillThenSelect({ id: parseInt(idStr,10), name: String(data.name||'').trim(), phone: String(data.phone||'').trim() });
        } else {
          // If select is disabled, temporarily enable to set value so UI reflects change
          const wasDisabled = sel.disabled;
          if (wasDisabled) sel.disabled = false;
          sel.value = idStr;
          // If enhanced by Choices.js, sync and select via API
          try {
            if (sel._choices) {
              const choices = Array.from(sel.options).map(o => ({ value: o.value, label: o.textContent, selected: o.selected, disabled: o.disabled }));
              sel._choices.setChoices(choices, 'value', 'label', true);
              sel._choices.setChoiceByValue(idStr);
            }
          } catch(_) { /* ignore */ }
          sel.dispatchEvent(new Event('change'));
          sel.dispatchEvent(new Event('input'));
          sel.dispatchEvent(new Event('refresh-choices'));
          if (wasDisabled) sel.disabled = true;
          try { if (typeof updateMeta === 'function') updateMeta(); } catch(_) {}
          try { if (typeof fetchCustomerSummary === 'function') fetchCustomerSummary(); } catch(_) {}
        }
        // Update in-memory customersData so delivery location and suggestions work without refresh
        try {
          if (Array.isArray(customersData)) {
            const cid = parseInt(idStr, 10);
            const existing = customersData.find(c => c.id === cid);
            if (!existing) customersData.push({ id: cid, delivery_location: (data.delivery_location || '').trim() });
          }
        } catch(_) { /* ignore */ }
      }
      // Close the modal
      const m = document.getElementById('quickAddCustomer');
      if (m && window.bootstrap && window.bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(m).hide();
      }
      // Clear inputs
      ['qa_name','qa_phone_input','qa_email','qa_address','qa_delivery_location'].forEach(id=>{ const el=document.getElementById(id); if (el) el.value=''; });
      const qaType = document.getElementById('qa_type'); if (qaType) qaType.value = '';
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-save me-1"></i> Save & Use'; }
    } catch (e) {
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-save me-1"></i> Save & Use'; }
      console.error('Quick Add Customer error', e);
      alert('Could not add customer. ' + (e && e.message ? e.message : 'Please try again or refresh the page.'));
    }
  });

  const customerSelPf = document.getElementById('customerSelectHidden');
  if (customerSelPf) {
    customerSelPf.addEventListener('change', function() {
      this.classList.remove('is-invalid');
      this.setAttribute('aria-invalid', 'false');
    });
  }
})();
</script>
<?php $cfgMaps = (require __DIR__ . '/../../config/config.php'); $gmKey = $cfgMaps['google_maps_api_key'] ?? ''; if ($gmKey): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($gmKey); ?>&libraries=places"></script>
<script>
  (function(){
    function attachAutocomplete(selector){
      var input = document.querySelector(selector);
      if (!input || !(window.google && google.maps && google.maps.places)) return;
      var ac = new google.maps.places.Autocomplete(input, { types: ['geocode'] });
      ac.addListener('place_changed', function(){
        try {
          var place = ac.getPlace();
          if (place && place.formatted_address) {
            input.value = place.formatted_address;
          }
        } catch(_) {}
      });
    }
    attachAutocomplete('#qa_delivery_location');
    attachAutocomplete('#deliveryLocationInput');
  })();
  </script>
<?php endif; ?>
