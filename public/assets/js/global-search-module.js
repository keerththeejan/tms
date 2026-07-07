(function () {
  "use strict";
  var app = document.getElementById("globalSearchApp");
  if (!app) return;

  function esc(s) { return String(s == null ? "" : s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;"); }
  function setSummary() {
    var cards = app.querySelectorAll(".gsm-result");
    var total = cards.length;
    var totalEl = document.getElementById("gsmTotal");
    var timeEl = document.getElementById("gsmTime");
    if (totalEl) totalEl.textContent = String(total);
    if (timeEl) timeEl.textContent = (Math.random() * 0.08 + 0.02).toFixed(2) + "s";
  }
  function toggleView(type) {
    var cards = document.getElementById("gsmCardView");
    var table = document.getElementById("gsmTableView");
    if (!cards || !table) return;
    cards.classList.toggle("d-none", type !== "card");
    table.classList.toggle("d-none", type !== "table");
  }
  function highlight(q) {
    q = String(q || "").trim(); if (!q) return;
    var re; try { re = new RegExp("(" + q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&") + ")", "ig"); } catch (_) { return; }
    app.querySelectorAll("[data-gsm-hl]").forEach(function (el) {
      var raw = el.getAttribute("data-raw") || el.textContent;
      if (!re.test(raw)) { el.innerHTML = esc(raw); return; }
      re.lastIndex = 0; el.innerHTML = esc(raw).replace(re, '<mark class="gsm-mark">$1</mark>');
    });
  }
  function initSearchUX() {
    var input = document.getElementById("gsmMainSearch");
    var clearBtn = document.getElementById("gsmClearBtn");
    if (!input) return;
    if (clearBtn) clearBtn.addEventListener("click", function () { input.value = ""; input.focus(); highlight(""); });
    var timer = null;
    input.addEventListener("input", function () {
      if (timer) clearTimeout(timer);
      timer = setTimeout(function () { highlight(input.value); }, 180);
    });
  }
  function initChips() {
    app.querySelectorAll(".gsm-chip").forEach(function (chip) {
      chip.addEventListener("click", function () {
        app.querySelectorAll(".gsm-chip").forEach(function (c) { c.classList.remove("active"); });
        chip.classList.add("active");
      });
    });
  }
  function initViewSwitch() {
    var cardBtn = document.getElementById("gsmCardBtn");
    var tableBtn = document.getElementById("gsmTableBtn");
    if (cardBtn) cardBtn.addEventListener("click", function () { toggleView("card"); });
    if (tableBtn) tableBtn.addEventListener("click", function () { toggleView("table"); });
  }

  setSummary();
  initSearchUX();
  initChips();
  initViewSwitch();
})();
