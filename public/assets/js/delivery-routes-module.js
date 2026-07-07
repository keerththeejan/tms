(function () {
  "use strict";

  var app = document.getElementById("deliveryRoutesApp");
  if (!app) return;

  function escapeHtml(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function animateCounters() {
    document.querySelectorAll("[data-drm-count]").forEach(function (el) {
      var target = parseFloat(el.getAttribute("data-drm-count")) || 0;
      var startTime = performance.now();
      var duration = 650;
      function tick(t) {
        var p = Math.min(1, (t - startTime) / duration);
        var val = Math.round(target * (1 - Math.pow(1 - p, 3)));
        el.textContent = val.toLocaleString();
        if (p < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    });
  }

  function highlight(term) {
    var q = String(term || "").trim();
    if (!q) return;
    var re;
    try {
      re = new RegExp("(" + q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + ")", "ig");
    } catch (_) {
      return;
    }
    document.querySelectorAll("td[data-hl]").forEach(function (td) {
      var raw = td.getAttribute("data-raw") || td.textContent;
      if (!re.test(raw)) {
        td.innerHTML = escapeHtml(raw);
        return;
      }
      re.lastIndex = 0;
      td.innerHTML = escapeHtml(raw).replace(re, '<mark class="drm-mark">$1</mark>');
    });
  }

  function collectRows() {
    var t = document.getElementById("drmTable");
    if (!t || !t.tBodies[0]) return [];
    return Array.from(t.tBodies[0].rows);
  }

  function exportRows(rows) {
    var headers = ["Route Code", "Route Name", "Branch", "Driver", "Vehicle", "Delivery Area", "Distance", "Estimated Time", "Capacity", "Status", "Created Date"];
    var csv = [headers.join(",")];
    rows.forEach(function (tr) {
      var vals = [
        tr.getAttribute("data-code"),
        tr.getAttribute("data-name"),
        tr.getAttribute("data-branch"),
        tr.getAttribute("data-driver"),
        tr.getAttribute("data-vehicle"),
        tr.getAttribute("data-area"),
        tr.getAttribute("data-distance"),
        tr.getAttribute("data-time"),
        tr.getAttribute("data-capacity"),
        tr.getAttribute("data-status"),
        tr.getAttribute("data-created")
      ];
      csv.push(vals.map(function (v) {
        var s = String(v || "");
        return /[",\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
      }).join(","));
    });
    var blob = new Blob([csv.join("\n")], { type: "text/csv;charset=utf-8;" });
    var a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "delivery_routes_export.csv";
    a.click();
    URL.revokeObjectURL(a.href);
  }

  function bindModal() {
    var modalEl = document.getElementById("drmDetailModal");
    if (!modalEl || !window.bootstrap) return;
    var body = document.getElementById("drmDetailBody");
    app.addEventListener("click", function (e) {
      var btn = e.target.closest("[data-drm-view]");
      if (!btn) return;
      var tr = btn.closest("tr");
      if (!tr) return;
      body.innerHTML =
        '<div class="drm-modal-stat"><span>Route Name</span><strong>' + escapeHtml(tr.getAttribute("data-name")) + "</strong></div>" +
        '<div class="drm-modal-stat"><span>Route Code</span><strong>' + escapeHtml(tr.getAttribute("data-code")) + "</strong></div>" +
        '<div class="drm-modal-stat"><span>Branch</span><strong>' + escapeHtml(tr.getAttribute("data-branch")) + "</strong></div>" +
        '<div class="drm-modal-stat"><span>Assigned Driver</span><strong>' + escapeHtml(tr.getAttribute("data-driver")) + "</strong></div>" +
        '<div class="drm-modal-stat"><span>Assigned Vehicle</span><strong>' + escapeHtml(tr.getAttribute("data-vehicle")) + "</strong></div>" +
        '<div class="drm-modal-stat"><span>Coverage Area</span><strong>' + escapeHtml(tr.getAttribute("data-area")) + "</strong></div>" +
        '<div class="drm-modal-stat"><span>Distance</span><strong>' + escapeHtml(tr.getAttribute("data-distance")) + "</strong></div>" +
        '<div class="drm-modal-stat"><span>Estimated Time</span><strong>' + escapeHtml(tr.getAttribute("data-time")) + "</strong></div>" +
        '<div class="drm-modal-stat"><span>Daily Capacity</span><strong>' + escapeHtml(tr.getAttribute("data-capacity")) + "</strong></div>" +
        '<div class="drm-modal-stat"><span>Status</span><strong>' + escapeHtml(tr.getAttribute("data-status")) + "</strong></div>" +
        '<div class="drm-modal-stat"><span>Created Date</span><strong>' + escapeHtml(tr.getAttribute("data-created")) + "</strong></div>";
      bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
  }

  function initTable() {
    var table = document.getElementById("drmTable");
    if (!table || typeof DataTable === "undefined") return null;
    var dt = new DataTable(table, {
      paging: true,
      searching: true,
      order: [[10, "desc"]],
      lengthChange: false,
      pageLength: 25,
      scrollX: true,
      columnDefs: [{ orderable: false, targets: [11] }]
    });

    var search = document.getElementById("drmSearch");
    if (search) {
      search.addEventListener("input", function () {
        dt.search(search.value).draw();
        highlight(search.value);
      });
    }

    var pageSize = document.getElementById("drmPageSize");
    if (pageSize) {
      pageSize.addEventListener("change", function () {
        dt.page.len(parseInt(pageSize.value, 10) || 25).draw();
      });
    }

    return dt;
  }

  function bindToolbar(dt) {
    app.addEventListener("click", function (e) {
      var act = e.target.closest("[data-drm-action]");
      if (!act) return;
      var type = act.getAttribute("data-drm-action");
      if (type === "refresh") {
        e.preventDefault();
        window.location.reload();
      } else if (type === "print") {
        e.preventDefault();
        window.print();
      } else if (type === "export-all") {
        e.preventDefault();
        exportRows(collectRows());
      } else if (type === "export-filtered" && dt) {
        e.preventDefault();
        var nodes = [];
        dt.rows({ search: "applied" }).every(function () { nodes.push(this.node()); });
        exportRows(nodes);
      }
    });
  }

  function bindAdvancedToggle() {
    var btn = document.getElementById("drmAdvToggle");
    var panel = document.getElementById("drmAdvanced");
    if (!btn || !panel) return;
    btn.addEventListener("click", function () {
      panel.classList.toggle("d-none");
      btn.setAttribute("aria-expanded", panel.classList.contains("d-none") ? "false" : "true");
    });
  }

  animateCounters();
  bindModal();
  var dt = initTable();
  bindToolbar(dt);
  bindAdvancedToggle();
})();
