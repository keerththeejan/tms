(function () {
  "use strict";
  var app = document.getElementById("remindersApp");
  if (!app) return;

  function esc(s) { return String(s == null ? "" : s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;"); }
  function counter() {
    document.querySelectorAll("[data-rmd-count]").forEach(function (el) {
      var target = parseFloat(el.getAttribute("data-rmd-count")) || 0, s = performance.now();
      (function f(t) { var p = Math.min(1, (t - s) / 650); el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString(); if (p < 1) requestAnimationFrame(f); })(s);
    });
  }
  function highlight(q) {
    q = String(q || "").trim(); if (!q) return;
    var re; try { re = new RegExp("(" + q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + ")", "ig"); } catch (_) { return; }
    document.querySelectorAll("td[data-hl]").forEach(function (td) {
      var raw = td.getAttribute("data-raw") || td.textContent;
      if (!re.test(raw)) { td.innerHTML = esc(raw); return; }
      re.lastIndex = 0; td.innerHTML = esc(raw).replace(re, '<mark class="rmd-mark">$1</mark>');
    });
  }
  function rows() { var t = document.getElementById("rmdTable"); return t && t.tBodies[0] ? Array.from(t.tBodies[0].rows) : []; }
  function exportCsv(list) {
    var h = ["Reminder ID","Title","Category","Assigned To","Reminder Date","Due Date","Priority","Status","Created By","Created Date"], out = [h.join(",")];
    list.forEach(function (tr) {
      var vals = ["id","title","category","assigned","rdate","ddate","priority","status","creator","created"].map(function (k) { return String(tr.getAttribute("data-" + k) || ""); });
      out.push(vals.map(function (v) { return /[",\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v; }).join(","));
    });
    var b = new Blob([out.join("\n")], { type: "text/csv;charset=utf-8;" }), a = document.createElement("a");
    a.href = URL.createObjectURL(b); a.download = "reminders_export.csv"; a.click(); URL.revokeObjectURL(a.href);
  }
  function initTable() {
    var t = document.getElementById("rmdTable"); if (!t || typeof DataTable === "undefined") return null;
    var dt = new DataTable(t, { paging: true, searching: true, order: [[9, "desc"]], pageLength: 25, lengthChange: false, scrollX: true, columnDefs: [{ orderable: false, targets: [10] }] });
    var q = document.getElementById("rmdQuickSearch");
    if (q) q.addEventListener("input", function () { dt.search(q.value).draw(); highlight(q.value); });
    var sz = document.getElementById("rmdPageSize");
    if (sz) sz.addEventListener("change", function () { dt.page.len(parseInt(sz.value, 10) || 25).draw(); });
    return dt;
  }
  function toolbar(dt) {
    app.addEventListener("click", function (e) {
      var b = e.target.closest("[data-rmd-action]"); if (!b) return;
      var a = b.getAttribute("data-rmd-action");
      if (a === "refresh") { e.preventDefault(); location.reload(); }
      else if (a === "export-all") { e.preventDefault(); exportCsv(rows()); }
      else if (a === "export-filtered" && dt) { e.preventDefault(); var n = []; dt.rows({ search: "applied" }).every(function () { n.push(this.node()); }); exportCsv(n); }
    });
  }
  function summaryForm() {
    var f = document.getElementById("rmdForm"); if (!f) return;
    var fields = {
      title: f.querySelector('[name="title"]'),
      category: f.querySelector('[name="category"]'),
      due: f.querySelector('[name="due_date"]'),
      repeat: f.querySelector('[name="repeat_interval"]'),
      notes: f.querySelector('[name="notes"]')
    };
    function set(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; }
    function update() {
      set("rmdSumTitle", (fields.title && fields.title.value.trim()) || "—");
      set("rmdSumCategory", (fields.category && fields.category.value.trim()) || "—");
      set("rmdSumDue", (fields.due && fields.due.value) || "—");
      set("rmdSumRepeat", (fields.repeat && fields.repeat.value) || "none");
      set("rmdSumStatus", "Pending");
      set("rmdSumNotes", (fields.notes && fields.notes.value.trim()) || "—");
      var due = fields.due && fields.due.value ? new Date(fields.due.value + "T00:00:00") : null;
      var now = new Date();
      var days = due ? Math.ceil((due - now) / 86400000) : null;
      set("rmdSumCountdown", days == null ? "—" : (days >= 0 ? (days + " day(s)") : ("Overdue by " + Math.abs(days) + " day(s)")));
    }
    Object.keys(fields).forEach(function (k) { var el = fields[k]; if (el) { el.addEventListener("input", update); el.addEventListener("change", update); } });
    update();
  }

  counter();
  var dt = initTable();
  toolbar(dt);
  summaryForm();
})();
