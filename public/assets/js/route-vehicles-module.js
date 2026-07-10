(function () {
  "use strict";
  var app = document.getElementById("routeVehiclesApp");
  if (!app) return;

  function esc(s) { return String(s == null ? "" : s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;"); }
  function counter() {
    document.querySelectorAll("[data-rvm-count]").forEach(function (el) {
      var target = parseFloat(el.getAttribute("data-rvm-count")) || 0, s = performance.now();
      (function f(t) { var p = Math.min(1, (t - s) / 650); el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString(); if (p < 1) requestAnimationFrame(f); })(s);
    });
  }
  function highlight(q) {
    q = String(q || "").trim(); if (!q) return;
    var re; try { re = new RegExp("(" + q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + ")", "ig"); } catch (_) { return; }
    document.querySelectorAll("td[data-hl]").forEach(function (td) {
      var raw = td.getAttribute("data-raw") || td.textContent;
      if (!re.test(raw)) { td.innerHTML = esc(raw); return; }
      re.lastIndex = 0; td.innerHTML = esc(raw).replace(re, '<mark class="rvm-mark">$1</mark>');
    });
  }
  function rows() { var t = document.getElementById("rvmTable"); return t && t.tBodies[0] ? Array.from(t.tBodies[0].rows) : []; }
  function exportCsv(list) {
    var h = ["Assignment ID","Route Name","Vehicle Number","Vehicle Type","Driver","Helper","Branch","Capacity","Today's Trips","Status","Last Updated"], out = [h.join(",")];
    list.forEach(function (tr) {
      var vals = ["assignment","route","vehicle","type","driver","helper","branch","capacity","trips","status","updated"].map(function (k) { return String(tr.getAttribute("data-" + k) || ""); });
      out.push(vals.map(function (v) { return /[",\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v; }).join(","));
    });
    var b = new Blob([out.join("\n")], { type: "text/csv;charset=utf-8;" }), a = document.createElement("a");
    a.href = URL.createObjectURL(b); a.download = "route_vehicle_assignments.csv"; a.click(); URL.revokeObjectURL(a.href);
  }
  function initTable() {
    var t = document.getElementById("rvmTable"); if (!t || typeof DataTable === "undefined") return null;
    var dt = new DataTable(t, { paging: true, searching: true, order: [[10, "desc"]], pageLength: 25, lengthChange: false, scrollX: true, columnDefs: [{ orderable: false, targets: [11] }] });
    var q = document.getElementById("rvmQuickSearch");
    if (q) q.addEventListener("input", function () { dt.search(q.value).draw(); highlight(q.value); });
    var sz = document.getElementById("rvmPageSize");
    if (sz) sz.addEventListener("change", function () { dt.page.len(parseInt(sz.value, 10) || 25).draw(); });
    return dt;
  }
  function initToolbar(dt) {
    app.addEventListener("click", function (e) {
      var b = e.target.closest("[data-rvm-action]"); if (!b) return;
      var a = b.getAttribute("data-rvm-action");
      if (a === "refresh") { e.preventDefault(); location.reload(); }
      else if (a === "print") { e.preventDefault(); print(); }
      else if (a === "export-all") { e.preventDefault(); exportCsv(rows()); }
      else if (a === "export-filtered" && dt) { e.preventDefault(); var n = []; dt.rows({ search: "applied" }).every(function () { n.push(this.node()); }); exportCsv(n); }
    });
  }
  function initModal() {
    var m = document.getElementById("rvmDetailModal"), b = document.getElementById("rvmDetailBody");
    if (!m || !b || !window.bootstrap) return;
    app.addEventListener("click", function (e) {
      var btn = e.target.closest("[data-rvm-view]"); if (!btn) return;
      var tr = btn.closest("tr"); if (!tr) return;
      b.innerHTML = ""
        + '<div class="rvm-stat"><span>Assignment</span><strong>' + esc(tr.getAttribute("data-assignment")) + "</strong></div>"
        + '<div class="rvm-stat"><span>Route</span><strong>' + esc(tr.getAttribute("data-route")) + "</strong></div>"
        + '<div class="rvm-stat"><span>Vehicle</span><strong>' + esc(tr.getAttribute("data-vehicle")) + "</strong></div>"
        + '<div class="rvm-stat"><span>Vehicle Type</span><strong>' + esc(tr.getAttribute("data-type")) + "</strong></div>"
        + '<div class="rvm-stat"><span>Driver</span><strong>' + esc(tr.getAttribute("data-driver")) + "</strong></div>"
        + '<div class="rvm-stat"><span>Helper</span><strong>' + esc(tr.getAttribute("data-helper")) + "</strong></div>"
        + '<div class="rvm-stat"><span>Branch</span><strong>' + esc(tr.getAttribute("data-branch")) + "</strong></div>"
        + '<div class="rvm-stat"><span>Capacity</span><strong>' + esc(tr.getAttribute("data-capacity")) + "</strong></div>"
        + '<div class="rvm-stat"><span>Today Trips</span><strong>' + esc(tr.getAttribute("data-trips")) + "</strong></div>"
        + '<div class="rvm-stat"><span>Status</span><strong>' + esc(tr.getAttribute("data-status")) + "</strong></div>";
      bootstrap.Modal.getOrCreateInstance(m).show();
    });
  }

  counter();
  var dt = initTable();
  initToolbar(dt);
  initModal();
})();
