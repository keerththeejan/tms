(function () {
  'use strict';

  var trendChart = null;
  var revExpChart = null;

  function loadDashboard() {
    if (!window.AccModule) {
      console.error('[Accounting Dashboard] AccModule is not loaded. Ensure accounting-module.js is included.');
      return;
    }

    var updatedEl = document.getElementById('accDashUpdated');
    var refreshBtn = document.getElementById('accDashRefresh');
    if (updatedEl) updatedEl.textContent = 'Loading…';
    if (refreshBtn) refreshBtn.disabled = true;
    document.querySelectorAll('[data-kpi]').forEach(function (el) {
      el.classList.add('acc-kpi-loading');
      el.textContent = '…';
    });

    AccModule.fetchJson({ acc_action: 'dashboard', _t: String(Date.now()) })
      .then(function (res) {
        if (!res.ok || !res.data) {
          throw new Error(res.error || res.message || 'Could not load dashboard');
        }
        renderDashboard(res.data);
        if (updatedEl) {
          var ts = res.data.generated_at ? new Date(res.data.generated_at) : new Date();
          updatedEl.textContent = 'Last updated: ' + ts.toLocaleString();
        }
      })
      .catch(function (err) {
        if (updatedEl) updatedEl.textContent = 'Failed to load dashboard data';
        AccModule.toast(err.message || 'Dashboard load failed', 'error');
      })
      .finally(function () {
        if (refreshBtn) refreshBtn.disabled = false;
      });
  }

  function renderDashboard(d) {
    setKpi('cash', d.cash_balance);
    setKpi('bank', d.bank_balance);
    setKpi('receivable', d.accounts_receivable);
    setKpi('payable', d.accounts_payable);
    setKpi('revenue', d.revenue_mtd);
    setKpi('expenses', d.expenses_mtd);
    setKpi('net_profit', d.net_profit_mtd, true);
    setKpi('pending_drafts', d.pending_drafts, false, true);

    renderRecent(d.recent_vouchers || []);
    renderTrendChart(d.monthly_trend || []);
    renderRevExpChart(d);
  }

  function setKpi(key, val, signed, plain) {
    var el = document.querySelector('[data-kpi="' + key + '"]');
    if (!el) return;
    el.classList.remove('acc-kpi-loading');
    if (plain) {
      el.textContent = String(val != null ? val : 0);
      return;
    }
    el.textContent = AccModule.money(val);
    if (signed) {
      var n = parseFloat(val) || 0;
      el.classList.toggle('positive', n >= 0);
      el.classList.toggle('negative', n < 0);
    }
  }

  function renderRecent(rows) {
    var tbody = document.querySelector('#accRecentTxTable tbody');
    if (!tbody) return;
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No vouchers yet. Post a voucher to see transactions here.</td></tr>';
      return;
    }
    tbody.innerHTML = rows.map(function (r) {
      var amt = parseFloat(r.total_debit || r.total_credit || 0);
      var ref = r.reference_number || r.narration || '';
      return '<tr>' +
        '<td>' + escapeHtml(r.voucher_date || '') + '</td>' +
        '<td><code class="small">' + escapeHtml(r.voucher_number || '') + '</code></td>' +
        '<td>' + escapeHtml(r.voucher_type || '') + '</td>' +
        '<td><span class="badge text-bg-' + statusClass(r.status) + '">' + escapeHtml(r.status || '') + '</span></td>' +
        '<td class="text-end">' + AccModule.money(amt) + '</td></tr>';
    }).join('');
  }

  function statusClass(s) {
    if (s === 'POSTED') return 'success';
    if (s === 'CANCELLED') return 'secondary';
    return 'warning';
  }

  function renderTrendChart(points) {
    var canvas = document.getElementById('accTrendChart');
    if (!canvas || typeof Chart === 'undefined') return;
    if (trendChart) {
      trendChart.destroy();
      trendChart = null;
    }
    trendChart = new Chart(canvas, {
      type: 'line',
      data: {
        labels: points.map(function (p) { return p.label; }),
        datasets: [
          {
            label: 'Revenue',
            data: points.map(function (p) { return p.revenue; }),
            borderColor: '#059669',
            backgroundColor: 'rgba(5, 150, 105, 0.1)',
            fill: true,
            tension: 0.3,
          },
          {
            label: 'Expenses',
            data: points.map(function (p) { return p.expenses; }),
            borderColor: '#dc2626',
            backgroundColor: 'rgba(220, 38, 38, 0.05)',
            fill: true,
            tension: 0.3,
          },
          {
            label: 'Profit',
            data: points.map(function (p) { return p.profit != null ? p.profit : (p.revenue - p.expenses); }),
            borderColor: '#2563eb',
            borderDash: [4, 4],
            tension: 0.3,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position: 'bottom' },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                return ctx.dataset.label + ': ' + AccModule.money(ctx.parsed.y);
              },
            },
          },
        },
        scales: {
          y: {
            ticks: {
              callback: function (v) { return AccModule.money(v); },
            },
          },
        },
      },
    });
  }

  function renderRevExpChart(d) {
    var canvas = document.getElementById('accRevExpChart');
    if (!canvas || typeof Chart === 'undefined') return;
    if (revExpChart) {
      revExpChart.destroy();
      revExpChart = null;
    }
    revExpChart = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: ['Revenue', 'Expenses'],
        datasets: [
          {
            label: 'This Month',
            data: [Math.max(0, parseFloat(d.revenue_mtd) || 0), Math.max(0, parseFloat(d.expenses_mtd) || 0)],
            backgroundColor: ['rgba(5, 150, 105, 0.75)', 'rgba(220, 38, 38, 0.75)'],
          },
          {
            label: 'Last Month',
            data: [Math.max(0, parseFloat(d.revenue_prev_month) || 0), Math.max(0, parseFloat(d.expenses_prev_month) || 0)],
            backgroundColor: ['rgba(5, 150, 105, 0.35)', 'rgba(220, 38, 38, 0.35)'],
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                return ctx.dataset.label + ': ' + AccModule.money(ctx.parsed.y);
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (v) { return AccModule.money(v); },
            },
          },
        },
      },
    });
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('accTrendChart')) return;

    loadDashboard();

    var refreshBtn = document.getElementById('accDashRefresh');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', loadDashboard);
    }

    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'visible') {
        loadDashboard();
      }
    });
  });
})();
