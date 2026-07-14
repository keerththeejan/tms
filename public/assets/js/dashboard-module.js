/**
 * TMS Executive Dashboard UI behaviours.
 * Presentation only — does not alter business calculations.
 */
(function () {
  "use strict";

  var REFRESH_MS = 60000;
  var app = document.getElementById("mainDashboardApp");
  if (!app) return;

  var currencySymbol =
    (app.getAttribute("data-currency-symbol") ||
      (window.TMS_CURRENCY && window.TMS_CURRENCY.symbol) ||
      "LKR").trim() || "LKR";

  function pad(n) {
    return String(n).padStart(2, "0");
  }

  function tickClock() {
    var el = document.getElementById("dashLiveClock");
    if (!el) return;
    var d = new Date();
    var label = pad(d.getHours()) + ":" + pad(d.getMinutes()) + ":" + pad(d.getSeconds());
    var icon = el.querySelector("i");
    var span = el.querySelector("span");
    if (span) {
      span.textContent = label;
    } else {
      el.innerHTML =
        (icon ? icon.outerHTML : '<i class="bi bi-clock" aria-hidden="true"></i>') +
        "<span>" +
        label +
        "</span>";
    }
  }

  function formatCount(n) {
    return Math.round(n).toLocaleString("en-US");
  }

  function formatMoney(n) {
    var abs = Math.abs(Number(n) || 0);
    var body = abs.toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
    return currencySymbol + " " + body;
  }

  function formatValue(format, n) {
    return format === "money" ? formatMoney(n) : formatCount(n);
  }

  function easeOutCubic(t) {
    return 1 - Math.pow(1 - t, 3);
  }

  function animateNumber(el, toValue, format, durationMs) {
    if (!el) return;
    var reduce =
      window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    var from = Number(el.getAttribute("data-displayed") || 0);
    var to = Number(toValue) || 0;
    if (reduce || durationMs <= 0) {
      el.textContent = formatValue(format, to);
      el.setAttribute("data-displayed", String(to));
      return;
    }
    var start = null;
    function frame(ts) {
      if (start === null) start = ts;
      var p = Math.min(1, (ts - start) / durationMs);
      var eased = easeOutCubic(p);
      var current = from + (to - from) * eased;
      el.textContent = formatValue(format, current);
      if (p < 1) {
        window.requestAnimationFrame(frame);
      } else {
        el.textContent = formatValue(format, to);
        el.setAttribute("data-displayed", String(to));
      }
    }
    window.requestAnimationFrame(frame);
  }

  function hydrateCard(card, value, animate) {
    if (!card) return;
    var format = card.getAttribute("data-format") === "money" ? "money" : "count";
    var target = Number(value);
    if (!isFinite(target)) target = 0;
    card.setAttribute("data-value", String(target));
    var valueEl = card.querySelector("[data-kpi-value]");
    if (!valueEl) return;
    card.classList.add("is-ready");
    animateNumber(valueEl, target, format, animate ? 900 : 0);
  }

  function hydrateAll(animate) {
    var cards = app.querySelectorAll(".erp-kpi-card[data-kpi-id]");
    cards.forEach(function (card) {
      hydrateCard(card, card.getAttribute("data-value"), !!animate);
    });
  }

  function setRefreshHint(text) {
    var hint = document.getElementById("dashKpiRefreshHint");
    if (hint) hint.textContent = text || "";
  }

  function parseKpisFromHtml(html) {
    var doc = new DOMParser().parseFromString(html, "text/html");
    var map = {};
    doc.querySelectorAll(".erp-kpi-card[data-kpi-id]").forEach(function (card) {
      var id = card.getAttribute("data-kpi-id");
      if (!id) return;
      map[id] = {
        value: card.getAttribute("data-value"),
        format: card.getAttribute("data-format") || "count",
      };
    });
    return map;
  }

  function refreshKpis() {
    var url = app.getAttribute("data-dash-refresh-url");
    if (!url) return;
    setRefreshHint("Refreshing…");
    app.querySelectorAll(".erp-kpi-card").forEach(function (c) {
      c.classList.add("is-updating");
    });
    fetch(url, {
      method: "GET",
      credentials: "same-origin",
      headers: { "X-Requested-With": "XMLHttpRequest" },
    })
      .then(function (res) {
        if (!res.ok) throw new Error("refresh failed");
        return res.text();
      })
      .then(function (html) {
        var map = parseKpisFromHtml(html);
        Object.keys(map).forEach(function (id) {
          var card = app.querySelector('.erp-kpi-card[data-kpi-id="' + id + '"]');
          if (!card) return;
          hydrateCard(card, map[id].value, true);
        });
        var now = new Date();
        setRefreshHint(
          "Last refreshed " +
            pad(now.getHours()) +
            ":" +
            pad(now.getMinutes()) +
            ":" +
            pad(now.getSeconds())
        );
      })
      .catch(function () {
        setRefreshHint("Refresh deferred");
      })
      .finally(function () {
        app.querySelectorAll(".erp-kpi-card").forEach(function (c) {
          c.classList.remove("is-updating");
        });
      });
  }

  // Initial skeleton → animated values
  window.requestAnimationFrame(function () {
    hydrateAll(true);
  });

  tickClock();
  window.setInterval(tickClock, 1000);
  window.setInterval(refreshKpis, REFRESH_MS);
})();
