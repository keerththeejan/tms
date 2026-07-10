<?php
$csrf = Helpers::csrfToken();
$accBaseUrl = Helpers::baseUrl('');
?>
<div class="acc-card mb-3">
  <div class="acc-card-header">Default Payment Mode Accounts</div>
  <div class="acc-card-body">
    <p class="text-muted small mb-3">
      Map each payment mode to the main ledger account used for automatic opposite entries in Receipt, Payment, and Contra vouchers.
    </p>
    <form id="accPaymentModeSettingsForm">
      <div class="row g-3" id="accPaymentModeSettingsRows">
        <div class="col-12 text-muted py-2">
          <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Loading settings…
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary btn-sm" id="accPaymentModeSaveBtn">
          <i class="bi bi-check-lg me-1"></i> Save Mappings
        </button>
      </div>
    </form>
  </div>
</div>

<div class="acc-card">
  <div class="acc-card-header">Accounting Configuration</div>
  <div class="acc-card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Fiscal Year</label>
        <input type="text" class="form-control" value="<?php echo date('Y'); ?>" readonly>
        <div class="form-text">Derived from voucher fiscal year field in existing posting logic.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Default Voucher Series</label>
        <input type="text" class="form-control" value="Main" readonly>
      </div>
      <div class="col-12">
        <div class="alert alert-info mb-0">
          <strong>Automatic opposite ledger</strong>
          <ul class="mb-0 mt-2">
            <li>Receipt/Payment vouchers post the configured main account automatically on save.</li>
            <li>Contra vouchers use Cash and Bank main accounts based on payment mode direction.</li>
            <li>Journal vouchers require manual ledger lines (no automatic account).</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';

  const baseUrl = <?php echo json_encode($accBaseUrl, JSON_UNESCAPED_SLASHES); ?>;
  const csrf = <?php echo json_encode($csrf); ?>;
  const modeLabels = { CASH: 'Cash', BANK: 'Bank', CHEQUE: 'Cheque' };
  let accounts = [];

  function apiUrl(params) {
    const q = new URLSearchParams({ page: 'api_accounting', ...params });
    return baseUrl + 'index.php?' + q.toString();
  }

  async function loadData() {
    const [settingsRes, accountsRes] = await Promise.all([
      fetch(apiUrl({ acc_action: 'payment_mode_settings' })),
      fetch(apiUrl({ acc_action: 'list_accounts' })),
    ]);
    const settingsData = await settingsRes.json();
    const accountsData = await accountsRes.json();
    if (accountsData.ok && Array.isArray(accountsData.data)) {
      accounts = accountsData.data;
    }
    if (settingsData.ok) {
      renderRows(settingsData.data || []);
    }
  }

  function renderRows(settings) {
    const container = document.getElementById('accPaymentModeSettingsRows');
    if (!container) return;

    const map = {};
    settings.forEach(function (row) {
      map[row.payment_mode] = row;
    });

    const modes = ['CASH', 'BANK', 'CHEQUE'];
    container.innerHTML = modes.map(function (mode) {
      const current = map[mode] || {};
      const options = accounts.map(function (a) {
        const selected = String(a.id) === String(current.account_id) ? ' selected' : '';
        return '<option value="' + a.id + '"' + selected + '>' +
          escapeHtml(a.account_code + ' — ' + a.account_name) + '</option>';
      }).join('');

      return '<div class="col-md-4">' +
        '<label class="form-label" for="accPma_' + mode + '">' + (modeLabels[mode] || mode) + '</label>' +
        '<select class="form-select form-select-sm" id="accPma_' + mode + '" name="mappings[' + mode + ']" required>' +
        '<option value="">Select account…</option>' + options +
        '</select></div>';
    }).join('');
  }

  document.getElementById('accPaymentModeSettingsForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const mappings = {};
    ['CASH', 'BANK', 'CHEQUE'].forEach(function (mode) {
      const el = document.getElementById('accPma_' + mode);
      if (el && el.value) {
        mappings[mode] = parseInt(el.value, 10);
      }
    });

    try {
      const res = await fetch(apiUrl({ acc_action: 'save_payment_mode_settings' }), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          acc_action: 'save_payment_mode_settings',
          csrf_token: csrf,
          mappings: mappings,
        }),
      });
      const data = await res.json();
      if (data.ok) {
        alert('Payment mode account mappings saved.');
        renderRows(data.data || []);
      } else {
        alert('Error: ' + (data.error || 'Save failed'));
      }
    } catch (err) {
      alert('Error saving settings: ' + err.message);
    }
  });

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  loadData().catch(function (err) {
    console.error(err);
    const container = document.getElementById('accPaymentModeSettingsRows');
    if (container) {
      container.innerHTML = '<div class="col-12 text-danger">Failed to load payment mode settings.</div>';
    }
  });
})();
</script>
