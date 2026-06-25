(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    if (!window.AccModule) return;

    AccModule.fetchJson({ acc_action: 'dashboard' }).then(function (res) {
      if (!res.ok || !res.data) {
        AccModule.toast(res.error || 'Could not load dashboard', 'error');
        return;
      }
      var d = res.data;
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
      renderRevExpChart(d.revenue_mtd, d.expenses_mtd);
    }).catch(function () {
      AccModule.toast('Dashboard load failed', 'error');
    });
  });

  function setKpi(key, val, signed, plain) {
    var el = document.querySelector('[data-kpi="' + key + '"]');
    if (!el) return;
    if (plain) {
      el.textContent = String(val || 0);
      return;
    }
    el.textContent = AccModule.money(val);
    if (signed) {
      el.classList.toggle('positive', (parseFloat(val) || 0) >= 0);
      el.classList.toggle('negative', (parseFloat(val) || 0) < 0);
    }
  }

  function renderRecent(rows) {
    var tbody = document.querySelector('#accRecentTxTable tbody');
    if (!tbody) return;
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No vouchers yet</td></tr>';
      return;
    }
    tbody.innerHTML = rows.map(function (r) {
      var amt = parseFloat(r.total_debit || r.total_credit || 0);
      return '<tr>' +
        '<td>' + escapeHtml(r.voucher_date || '') + '</td>' +
        '<td>' + escapeHtml(r.voucher_number || '') + '</td>' +
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
    new Chart(canvas, {
      type: 'line',
      data: {
        labels: points.map(function (p) { return p.label; }),
        datasets: [
          { label: 'Revenue', data: points.map(function (p) { return p.revenue; }), borderColor: '#059669', tension: 0.3 },
          { label: 'Expenses', data: points.map(function (p) { return p.expenses; }), borderColor: '#dc2626', tension: 0.3 },
        ],
      },
      options: { responsive: true, maintainAspectRatio: false },
    });
  }

  function renderRevExpChart(rev, exp) {
    var canvas = document.getElementById('accRevExpChart');
    if (!canvas || typeof Chart === 'undefined') return;
    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: ['Revenue', 'Expenses'],
        datasets: [{ data: [Math.max(0, parseFloat(rev) || 0), Math.max(0, parseFloat(exp) || 0)], backgroundColor: ['#059669', '#dc2626'] }],
      },
      options: { responsive: true, maintainAspectRatio: false },
    });
  }

  function escapeHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
})();
