(function () {
  "use strict";
  var app = document.getElementById("advancesApp");
  if (!app) return;

  function esc(s) { return String(s == null ? "" : s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;"); }
  function counter() {
    document.querySelectorAll("[data-avm-count]").forEach(function (el) {
      var target = parseFloat(el.getAttribute("data-avm-count")) || 0, s = performance.now();
      (function f(t) { var p = Math.min(1, (t - s) / 650); el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString(); if (p < 1) requestAnimationFrame(f); })(s);
    });
  }
  function highlight(q) {
    q = String(q || "").trim(); if (!q) return;
    var re; try { re = new RegExp("(" + q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + ")", "ig"); } catch (_) { return; }
    document.querySelectorAll("td[data-hl]").forEach(function (td) {
      var raw = td.getAttribute("data-raw") || td.textContent;
      if (!re.test(raw)) { td.innerHTML = esc(raw); return; }
      re.lastIndex = 0; td.innerHTML = esc(raw).replace(re, '<mark class="avm-mark">$1</mark>');
    });
  }
  function rows() { var t = document.getElementById("avmTable"); return t && t.tBodies[0] ? Array.from(t.tBodies[0].rows) : []; }
  function exportCsv(list) {
    var h = ["Advance No","Employee","Employee ID","Department","Advance Date","Advance Amount","Recovered Amount","Outstanding","Payment Method","Status","Created Date"], out = [h.join(",")];
    list.forEach(function (tr) {
      var vals = ["no","employee","empid","department","adate","amount","recovered","outstanding","payment","status","created"].map(function (k) { return String(tr.getAttribute("data-" + k) || ""); });
      out.push(vals.map(function (v) { return /[",\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v; }).join(","));
    });
    var b = new Blob([out.join("\n")], { type: "text/csv;charset=utf-8;" }), a = document.createElement("a");
    a.href = URL.createObjectURL(b); a.download = "employee_advances.csv"; a.click(); URL.revokeObjectURL(a.href);
  }
  function initTable() {
    var t = document.getElementById("avmTable"); if (!t || typeof DataTable === "undefined") return null;
    var dt = new DataTable(t, { paging: true, searching: true, order: [[10, "desc"]], pageLength: 25, lengthChange: false, scrollX: true, columnDefs: [{ orderable: false, targets: [12] }] });
    var q = document.getElementById("avmQuickSearch");
    if (q) q.addEventListener("input", function () { dt.search(q.value).draw(); highlight(q.value); });
    var sz = document.getElementById("avmPageSize");
    if (sz) sz.addEventListener("change", function () { dt.page.len(parseInt(sz.value, 10) || 25).draw(); });
    return dt;
  }
  function toolbar(dt) {
    app.addEventListener("click", function (e) {
      var b = e.target.closest("[data-avm-action]"); if (!b) return;
      var a = b.getAttribute("data-avm-action");
      if (a === "refresh") { e.preventDefault(); location.reload(); }
      else if (a === "print") { e.preventDefault(); print(); }
      else if (a === "export-all") { e.preventDefault(); exportCsv(rows()); }
      else if (a === "export-filtered" && dt) { e.preventDefault(); var n = []; dt.rows({ search: "applied" }).every(function () { n.push(this.node()); }); exportCsv(n); }
    });
  }
  function summaryForm() {
    var f = document.getElementById("avmForm"); if (!f) return;
    var emp = f.querySelector('[name="employee_id"]');
    var amt = f.querySelector('[name="amount"]');
    var date = f.querySelector('[name="advance_date"]');
    var purpose = f.querySelector('[name="purpose"]');
    var no = document.getElementById("avmNo");
    function selText(sel) { var o = sel && sel.options ? sel.options[sel.selectedIndex] : null; return o ? o.textContent.trim() : "—"; }
    function upd() {
      var amount = parseFloat((amt && amt.value) || 0) || 0;
      var recovered = Math.max(0, amount * 0.35);
      var out = Math.max(0, amount - recovered);
      var pct = amount > 0 ? Math.min(100, Math.round((recovered / amount) * 100)) : 0;
      var set = function (id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
      set("avmSumEmployee", selText(emp));
      set("avmSumNo", (no && no.textContent) || "—");
      set("avmSumAmount", "LKR " + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      set("avmSumRecovered", "LKR " + recovered.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      set("avmSumOutstanding", "LKR " + out.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      set("avmSumDate", (date && date.value) || "—");
      set("avmSumPurpose", (purpose && purpose.value.trim()) || "—");
      var bar = document.getElementById("avmProgressBar");
      if (bar) { bar.style.width = pct + "%"; bar.textContent = pct + "%"; }
    }
    [emp, amt, date, purpose].forEach(function (el) { if (el) { el.addEventListener("input", upd); el.addEventListener("change", upd); } });
    upd();
  }

  counter();
  var dt = initTable();
  toolbar(dt);
  summaryForm();
})();
