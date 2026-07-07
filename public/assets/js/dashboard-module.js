(function () {
  "use strict";
  var app = document.getElementById("mainDashboardApp");
  if (!app) return;

  function pad(n) { return String(n).padStart(2, "0"); }
  function tickClock() {
    var el = document.getElementById("dashLiveClock");
    if (!el) return;
    var d = new Date();
    el.innerHTML = '<i class="bi bi-clock me-1"></i>' + pad(d.getHours()) + ":" + pad(d.getMinutes()) + ":" + pad(d.getSeconds());
  }
  tickClock();
  setInterval(tickClock, 1000);
})();
