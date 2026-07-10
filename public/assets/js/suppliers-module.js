(function () {
  "use strict";
  var app = document.getElementById("suppliersApp");
  if (!app) return;

  function esc(s) { return String(s == null ? "" : s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;"); }
  function counter() {
    document.querySelectorAll("[data-supm-count]").forEach(function (el) {
      var t = parseFloat(el.getAttribute("data-supm-count")) || 0, s = performance.now();
      (function f(n) { var p = Math.min(1, (n - s) / 650); el.textContent = Math.round(t * (1 - Math.pow(1 - p, 3))).toLocaleString(); if (p < 1) requestAnimationFrame(f); })(s);
    });
  }
  function highlight(q) {
    q = String(q || "").trim(); if (!q) return;
    var re; try { re = new RegExp("(" + q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + ")", "ig"); } catch (_) { return; }
    document.querySelectorAll("td[data-hl]").forEach(function (td) {
      var raw = td.getAttribute("data-raw") || td.textContent;
      if (!re.test(raw)) { td.innerHTML = esc(raw); return; }
      re.lastIndex = 0; td.innerHTML = esc(raw).replace(re, '<mark class="supm-mark">$1</mark>');
    });
  }
  function rows() { var t = document.getElementById("supmTable"); return t && t.tBodies[0] ? Array.from(t.tBodies[0].rows) : []; }
  function exportCsv(list) {
    var h = ["Supplier Code","Supplier Name","Company Name","Phone","Email","City","Category","Outstanding","Status","Created Date"], out = [h.join(",")];
    list.forEach(function (tr) {
      var vals = ["code","name","company","phone","email","city","category","outstanding","status","created"].map(function (k) { return String(tr.getAttribute("data-" + k) || ""); });
      out.push(vals.map(function (v) { return /[",\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v; }).join(","));
    });
    var b = new Blob([out.join("\n")], { type: "text/csv;charset=utf-8;" }), a = document.createElement("a");
    a.href = URL.createObjectURL(b); a.download = "suppliers_export.csv"; a.click(); URL.revokeObjectURL(a.href);
  }

  function initTable() {
    var t = document.getElementById("supmTable"); if (!t || typeof DataTable === "undefined") return null;
    var dt = new DataTable(t, { paging: true, searching: true, order: [[10, "desc"]], pageLength: 25, lengthChange: false, scrollX: true, columnDefs: [{ orderable: false, targets: [0, 11] }] });
    var q = document.getElementById("supmQuickSearch");
    if (q) q.addEventListener("input", function () { dt.search(q.value).draw(); highlight(q.value); });
    var sz = document.getElementById("supmPageSize");
    if (sz) sz.addEventListener("change", function () { dt.page.len(parseInt(sz.value, 10) || 25).draw(); });
    return dt;
  }

  function initModal() {
    var m = document.getElementById("supmProfileModal"), b = document.getElementById("supmProfileBody");
    if (!m || !b || !window.bootstrap) return;
    app.addEventListener("click", function (e) {
      var btn = e.target.closest("[data-supm-view]"); if (!btn) return;
      var tr = btn.closest("tr"); if (!tr) return;
      b.innerHTML = ""
        + '<div class="supm-summary-row"><span>Supplier</span><strong>' + esc(tr.getAttribute("data-name")) + "</strong></div>"
        + '<div class="supm-summary-row"><span>Company</span><strong>' + esc(tr.getAttribute("data-company")) + "</strong></div>"
        + '<div class="supm-summary-row"><span>Code</span><strong>' + esc(tr.getAttribute("data-code")) + "</strong></div>"
        + '<div class="supm-summary-row"><span>Phone</span><strong>' + esc(tr.getAttribute("data-phone")) + "</strong></div>"
        + '<div class="supm-summary-row"><span>Email</span><strong>' + esc(tr.getAttribute("data-email")) + "</strong></div>"
        + '<div class="supm-summary-row"><span>City</span><strong>' + esc(tr.getAttribute("data-city")) + "</strong></div>"
        + '<div class="supm-summary-row"><span>Category</span><strong>' + esc(tr.getAttribute("data-category")) + "</strong></div>"
        + '<div class="supm-summary-row"><span>Outstanding</span><strong>' + esc(tr.getAttribute("data-outstanding")) + "</strong></div>"
        + '<div class="supm-summary-row"><span>Status</span><strong>' + esc(tr.getAttribute("data-status")) + "</strong></div>"
        + '<div class="supm-summary-row"><span>Created</span><strong>' + esc(tr.getAttribute("data-created")) + "</strong></div>";
      bootstrap.Modal.getOrCreateInstance(m).show();
    });
  }

  function initToolbar(dt) {
    app.addEventListener("click", function (e) {
      var b = e.target.closest("[data-supm-action]"); if (!b) return;
      var a = b.getAttribute("data-supm-action");
      if (a === "refresh") { e.preventDefault(); location.reload(); }
      else if (a === "print") { e.preventDefault(); print(); }
      else if (a === "export-all") { e.preventDefault(); exportCsv(rows()); }
      else if (a === "export-filtered" && dt) { e.preventDefault(); var n = []; dt.rows({ search: "applied" }).every(function () { n.push(this.node()); }); exportCsv(n); }
    });
  }

  function initFormSummary() {
    var f = document.getElementById("supmForm"); if (!f) return;
    var fields = {
      name: f.querySelector('[name="name"]'),
      phone: f.querySelector('[name="phone"]'),
      code: f.querySelector('[name="supplier_code"]'),
      branch: f.querySelector('[name="branch_id"]'),
      company: document.getElementById("supmCompanyPreview"),
      category: document.getElementById("supmCategoryPreview"),
      status: document.getElementById("supmStatusPreview")
    };
    var out = {
      name: document.getElementById("supmSumName"),
      code: document.getElementById("supmSumCode"),
      phone: document.getElementById("supmSumPhone"),
      company: document.getElementById("supmSumCompany"),
      category: document.getElementById("supmSumCategory"),
      status: document.getElementById("supmSumStatus"),
      branch: document.getElementById("supmSumBranch")
    };
    function selText(sel) { var o = sel && sel.options ? sel.options[sel.selectedIndex] : null; return o ? o.textContent.trim() : "—"; }
    function refresh() {
      if (out.name) out.name.textContent = (fields.name && fields.name.value.trim()) || "—";
      if (out.code) out.code.textContent = (fields.code && fields.code.value.trim()) || "—";
      if (out.phone) out.phone.textContent = (fields.phone && fields.phone.value.trim()) || "—";
      if (out.company) out.company.textContent = (fields.company && fields.company.value.trim()) || "—";
      if (out.category) out.category.textContent = (fields.category && fields.category.value.trim()) || "—";
      if (out.status) out.status.textContent = (fields.status && fields.status.value.trim()) || "Active";
      if (out.branch) out.branch.textContent = selText(fields.branch);
    }
    Object.keys(fields).forEach(function (k) { var el = fields[k]; if (el) el.addEventListener("input", refresh); if (el) el.addEventListener("change", refresh); });
    refresh();
  }

  counter();
  var dt = initTable();
  initToolbar(dt);
  initModal();
  initFormSummary();
})();
