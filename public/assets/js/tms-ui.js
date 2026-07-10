(function () {
  var storageKey = 'tms-theme';

  function getPreferredTheme() {
    try {
      var stored = window.localStorage.getItem(storageKey);
      if (stored === 'dark' || stored === 'light') {
        return stored;
      }
    } catch (e) {
      /* ignore */
    }
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function applyTheme(theme) {
    var next = theme === 'dark' ? 'dark' : 'light';
    document.body.setAttribute('data-theme', next);
    try {
      window.localStorage.setItem(storageKey, next);
    } catch (e) {
      /* ignore */
    }
    var btn = document.getElementById('themeToggleBtn');
    if (btn) {
      var icon = btn.querySelector('i');
      var label = btn.querySelector('span');
      if (icon) {
        icon.className = next === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
      }
      if (label) {
        label.textContent = next === 'dark' ? 'Light' : 'Theme';
      }
      btn.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
      btn.title = next === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
    }
  }

  function initThemeToggle() {
    applyTheme(getPreferredTheme());
    var btn = document.getElementById('themeToggleBtn');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var current = document.body.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
      applyTheme(current === 'dark' ? 'light' : 'dark');
    });
  }

  function money(value) {
    if (window.TMS && typeof window.TMS.formatMoney === 'function') {
      return window.TMS.formatMoney(value);
    }
    var n = Number(value) || 0;
    return 'LKR ' + n.toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function initDashboardChart() {
    var canvas = document.getElementById('dashboardBranchChart');
    var data = window.TMS_DASHBOARD_DATA || null;
    if (!canvas || typeof Chart === 'undefined' || !data) return;
    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    var existing = window.TMS_DASHBOARD_CHART;
    if (existing && typeof existing.destroy === 'function') {
      existing.destroy();
    }

    window.TMS_DASHBOARD_CHART = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: data.labels || [],
        datasets: [
          {
            label: 'Pending Parcels',
            data: data.pending || [],
            backgroundColor: 'rgba(13, 110, 253, 0.72)',
            borderRadius: 10,
          },
          {
            label: 'Collections',
            data: data.collections || [],
            backgroundColor: 'rgba(25, 135, 84, 0.72)',
            borderRadius: 10,
          },
          {
            label: 'Expenses',
            data: data.expenses || [],
            backgroundColor: 'rgba(220, 53, 69, 0.72)',
            borderRadius: 10,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10 } },
          tooltip: {
            callbacks: {
              label: function (context) {
                return context.dataset.label + ': ' + money(context.raw);
              },
            },
          },
        },
        scales: {
          x: { grid: { display: false } },
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return money(value);
              },
            },
          },
        },
      },
    });
  }

  function boot() {
    initThemeToggle();
    initDashboardChart();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
