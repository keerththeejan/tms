<?php /** @var array $parcel */ ?>
<style>
  .receipt-box { border: 2px solid #0d6efd; border-radius: 6px; }
  .receipt-header { background: #e7f1ff; border-bottom: 2px solid #0d6efd; }
  .receipt-grid th, .receipt-grid td { border: 1px solid #0d6efd; vertical-align: middle; }
  .receipt-grid th { background: #f8fbff; text-transform: uppercase; font-size: 0.85rem; }
  .receipt-total { background: #e7f1ff; border-top: 2px solid #0d6efd; font-weight: 700; }
  .serial-badge { border: 2px solid #0d6efd; padding: .25rem .5rem; border-radius: .25rem; font-weight: 600; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?php echo $parcel['id'] ? 'Edit Parcel' : 'New Parcel'; ?></h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=parcels'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=parcels&action=save'); ?>">
  <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
  <input type="hidden" name="id" value="<?php echo (int)$parcel['id']; ?>">

  <!-- Top controls (data fields required by system) -->
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <label class="form-label d-flex justify-content-between align-items-center">
        <span>Customer</span>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#quickAddCustomer" aria-expanded="false"><i class="bi bi-person-plus"></i> Quick Add</button>
      </label>
      <select name="customer_id" class="form-select" required>
        <option value="">-- Select Customer --</option>
        <?php foreach (($customersAll ?? []) as $c): ?>
          <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)($parcel['customer_id'] ?? 0) === (int)$c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name'].' ('.$c['phone'].')'); ?></option>
        <?php endforeach; ?>
      </select>
      <div class="collapse mt-2" id="quickAddCustomer">
        <div class="border rounded p-2 bg-light">
          <div class="row g-2">
            <div class="col-6">
              <input type="text" id="qa_name" class="form-control form-control-sm" placeholder="Name">
            </div>
            <div class="col-6">
              <input type="text" id="qa_phone" class="form-control form-control-sm" placeholder="Phone (unique)">
            </div>
            <div class="col-6">
              <input type="text" id="qa_address" class="form-control form-control-sm" placeholder="Address">
            </div>
            <div class="col-6">
              <input type="text" id="qa_delivery_location" class="form-control form-control-sm" placeholder="Delivery Location" list="dl_locations">
              <datalist id="dl_locations">
                <?php 
                  $locs = [];
                  foreach (($customersAll ?? []) as $c) { 
                    $dl = trim((string)($c['delivery_location'] ?? '')); 
                    if ($dl !== '') { $locs[$dl] = true; }
                  }
                  foreach (array_keys($locs) as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt); ?>"></option>
                <?php endforeach; ?>
              </datalist>
            </div>
            <div class="col-6">
              <select id="qa_type" class="form-select form-select-sm">
                <option value="">Type (optional)</option>
                <option value="regular">Regular</option>
                <option value="corporate">Corporate</option>
              </select>
            </div>
            <div class="col-6 text-end">
              <button type="button" id="qa_submit" class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save & Use</button>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#findByLocation" aria-expanded="false"><i class="bi bi-geo"></i> Find by Delivery Location</button>
        <div class="collapse border rounded p-2 bg-light mt-2" id="findByLocation">
          <div class="mb-2">
            <input type="text" id="locQuery" class="form-control form-control-sm" placeholder="Type delivery location area (e.g., Kilinochchi)">
          </div>
          <div id="locResults" class="small" style="max-height: 180px; overflow:auto"></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <label class="form-label">Supplier (Optional)</label>
      <select name="supplier_id" class="form-select">
        <option value="0">-- None --</option>
        <?php foreach (($suppliersAll ?? []) as $s): ?>
          <option value="<?php echo (int)$s['id']; ?>" <?php echo ((int)($parcel['supplier_id'] ?? 0) === (int)$s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Date</label>
      <input type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" disabled>
    </div>
    <div class="col-md-4">
      <label class="form-label">From Branch</label>
      <select name="from_branch_id" class="form-select" required>
        <option value="">-- Select Branch --</option>
        <?php foreach (($branchesAll ?? []) as $b): ?>
          <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($parcel['from_branch_id'] ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">To Branch</label>
      <select name="to_branch_id" class="form-select" required>
        <option value="">-- Select Branch --</option>
        <?php foreach (($branchesAll ?? []) as $b): ?>
          <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($parcel['to_branch_id'] ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
        <?php endforeach; ?>
      </select>
      <div id="toBranchSuggest" class="form-text"></div>
    </div>
    <div class="col-md-4">
      <label class="form-label">Vehicle No. (Optional)</label>
      <?php if (!empty($vehiclesAll)): ?>
        <select name="vehicle_no" class="form-select">
          <option value="">-- None --</option>
          <?php 
            $vehCurrent = trim((string)($parcel['vehicle_no'] ?? ''));
            foreach ($vehiclesAll as $v): 
              $vno = trim((string)($v['vehicle_no'] ?? ''));
              if ($vno === '') continue; 
          ?>
            <option value="<?php echo htmlspecialchars($vno); ?>" <?php echo (strcasecmp($vehCurrent, $vno) === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars($vno); ?></option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <input type="text" name="vehicle_no" class="form-control" placeholder="e.g., AB-1234" value="<?php echo htmlspecialchars($parcel['vehicle_no'] ?? ''); ?>">
      <?php endif; ?>
      <?php 
        $lorryChecked = 0; 
        $pid = (int)($parcel['id'] ?? 0);
        if ($pid > 0 && !empty($_SESSION['lorry_full_saved'][$pid])) { 
          $lorryChecked = 1; 
        } elseif (!empty($_SESSION['lorry_full_pref'])) { 
          $lorryChecked = 1; 
        }
      ?>
      <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" value="1" id="lorry_full" name="lorry_full" <?php echo $lorryChecked ? 'checked' : ''; ?>>
        <label class="form-check-label" for="lorry_full">
          Lorry Full (start next lorry after saving)
        </label>
      </div>
    </div>
  </div>

  <!-- Receipt-like box -->
  <div class="receipt-box mb-3">
    <div class="receipt-header p-3 d-flex justify-content-between align-items-center">
      <div>
        <div class="fw-bold">TS Transport</div>
        <small class="text-muted">Consignment Entry</small>
      </div>
      <div class="serial-badge">Serial: <?php echo $parcel['id'] ? (int)$parcel['id'] : '—'; ?></div>
    </div>

    <div class="p-3">
      <div class="row g-3 mb-2">
        <div class="col-sm-4">
          <div><strong>Customer:</strong> <span id="customerDisplay" class="text-muted">selected above</span></div>
          <div class="small text-muted"><strong>Delivery Location:</strong> <span id="customerLocDisplay" class="text-muted">—</span></div>
        </div>
        <div class="col-sm-2">
          <div><strong>Date:</strong> <?php echo date('Y-m-d'); ?></div>
        </div>
        <div class="col-sm-3">
          <div><strong>From:</strong> <span id="fromBranchDisplay" class="text-muted">selected above</span></div>
        </div>
        <div class="col-sm-3">
          <div><strong>To:</strong> <span id="toBranchDisplay" class="text-muted">selected above</span></div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table receipt-grid mb-2" id="itemsTable">
          <thead>
            <tr>
              <th style="width:8%">No</th>
              <th>Description</th>
              <th style="width:12%">Qty</th>
              <th style="width:10%">Rs</th>
              <th style="width:10%">Cts</th>
              <th style="width:6%"></th>
            </tr>
          </thead>
          <tbody>
            <?php 
              $rowIndex = 0; 
              foreach (($items ?? []) as $it): 
                $rowIndex++; 
                $q = (float)($it['qty'] ?? 0);
                // derive rs/cts from qty*rate if present
                $r = (float)($it['rate'] ?? 0);
                $amt = $q > 0 && $r > 0 ? ($q * $r) : 0; 
                $rs = floor($amt); 
                $ct = (int)round(($amt - $rs) * 100);
            ?>
            <tr>
              <td class="text-center align-middle"><?php echo $rowIndex; ?></td>
              <td><input type="text" name="items[<?php echo $rowIndex; ?>][description]" class="form-control item-desc" value="<?php echo htmlspecialchars($it['description'] ?? ''); ?>" placeholder="Description"></td>
              <td><input type="number" step="0.01" name="items[<?php echo $rowIndex; ?>][qty]" class="form-control item-qty" value="<?php echo htmlspecialchars((string)$q); ?>" placeholder="Qty"></td>
              <td><input type="number" step="1" min="0" name="items[<?php echo $rowIndex; ?>][rs]" class="form-control item-rs" value="<?php echo $rs>0?(string)$rs:''; ?>" <?php echo !($isMain ?? false) ? 'disabled' : ''; ?> placeholder="Rs"></td>
              <td><input type="number" step="1" min="0" max="99" name="items[<?php echo $rowIndex; ?>][cts]" class="form-control item-cts" value="<?php echo $amt>0?str_pad((string)$ct,2,'0',STR_PAD_LEFT):''; ?>" <?php echo !($isMain ?? false) ? 'disabled' : ''; ?> placeholder="Cts"></td>
              <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="bi bi-x"></i></button></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
            <tr>
              <td class="text-center align-middle">1</td>
              <td><input type="text" name="items[1][description]" class="form-control item-desc" placeholder="Description"></td>
              <td><input type="number" step="0.01" name="items[1][qty]" class="form-control item-qty" placeholder="Qty"></td>
              <td><input type="number" step="1" min="0" name="items[1][rs]" class="form-control item-rs" <?php echo !($isMain ?? false) ? 'disabled' : ''; ?> placeholder="Rs"></td>
              <td><input type="number" step="1" min="0" max="99" name="items[1][cts]" class="form-control item-cts" <?php echo !($isMain ?? false) ? 'disabled' : ''; ?> placeholder="Cts"></td>
              <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="bi bi-x"></i></button></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
        <div class="text-end mb-2">
          <button type="button" class="btn btn-sm btn-outline-primary" id="addRow"><i class="bi bi-plus-lg"></i> Add Row</button>
        </div>
      </div>

      <div class="receipt-total p-2 d-flex justify-content-end">
        <div>
          Total: <span class="fs-5" id="totalDisplay"><?php echo $parcel['price']===null ? '—' : number_format((float)$parcel['price'],2); ?></span>
          <input type="hidden" name="price" id="totalPrice" value="<?php echo htmlspecialchars((string)($parcel['price'] ?? '')); ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Status and actions -->
  <div class="row g-3 align-items-end">
    <div class="col-sm-4">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="pending" <?php echo ($parcel['status'] ?? '')==='pending'?'selected':''; ?>>Pending</option>
        <option value="in_transit" <?php echo ($parcel['status'] ?? '')==='in_transit'?'selected':''; ?>>In Transit</option>
        <option value="delivered" <?php echo ($parcel['status'] ?? '')==='delivered'?'selected':''; ?>>Delivered</option>
      </select>
    </div>
    <div class="col-sm-8 text-end">
      <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
    </div>
  </div>
</form>

<script>
(function(){
  const isMain = <?php echo $isMain ? 'true' : 'false'; ?>;
  const table = document.getElementById('itemsTable');
  const addBtn = document.getElementById('addRow');
  const totalDisplay = document.getElementById('totalDisplay');
  const totalPrice = document.getElementById('totalPrice');

  function recalc(){
    let total = 0;
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
      const qty = parseFloat(row.querySelector('.item-qty')?.value || '0');
      const rsInput = row.querySelector('.item-rs');
      const ctsInput = row.querySelector('.item-cts');
      const rs = parseFloat(rsInput?.value || '0');
      const cts = parseFloat(ctsInput?.value || '0');
      let amt = rs + (cts/100);
      // If non-main branch, amounts should be ignored for total (cannot set price). Keep display only.
      if (!isMain) { amt = 0; }
      total += (amt>0 && qty>=0) ? amt : 0;
    });
    if (total > 0) {
      totalDisplay.textContent = total.toFixed(2);
      totalPrice.value = total.toFixed(2);
    } else {
      totalDisplay.textContent = '—';
      totalPrice.value = '';
    }
  }

  // Quick Add submit without nested form
  const qaBtn = document.getElementById('qa_submit');
  if (qaBtn) {
    qaBtn.addEventListener('click', function(){
      var nameEl = document.getElementById('qa_name');
      var phoneEl = document.getElementById('qa_phone');
      var addressEl = document.getElementById('qa_address');
      var dlEl = document.getElementById('qa_delivery_location');
      var typeEl = document.getElementById('qa_type');
      var name = nameEl ? nameEl.value.trim() : '';
      var phone = phoneEl ? phoneEl.value.trim() : '';
      var address = addressEl ? addressEl.value.trim() : '';
      var delivery_location = dlEl ? dlEl.value.trim() : '';
      var type = typeEl ? typeEl.value : '';
      if (!name || !phone) { alert('Name and Phone are required'); return; }
      var tmp = document.createElement('form');
      tmp.method = 'POST';
      tmp.action = '<?php echo Helpers::baseUrl('index.php?page=quick_add_customer'); ?>';
      var csrf = document.createElement('input'); csrf.type='hidden'; csrf.name='csrf_token'; csrf.value='<?php echo Helpers::csrfToken(); ?>'; tmp.appendChild(csrf);
      function addField(n, v){ var i=document.createElement('input'); i.type='hidden'; i.name=n; i.value=v; tmp.appendChild(i);}    
      addField('name', name); addField('phone', phone); addField('address', address); addField('delivery_location', delivery_location); addField('customer_type', type);
      document.body.appendChild(tmp);
      tmp.submit();
    });
  }

  table.addEventListener('input', function(e){
    const target = e.target;
    if (target.classList.contains('item-qty') || target.classList.contains('item-rate')) {
      recalc();
    }
  });

  table.addEventListener('click', function(e){
    if (e.target.closest('.remove-row')) {
      const tr = e.target.closest('tr');
      tr.parentNode.removeChild(tr);
      recalc();
    }
  });

  addBtn?.addEventListener('click', function(){
    const tbody = table.querySelector('tbody');
    const idx = tbody.querySelectorAll('tr').length + 1;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="text-center align-middle">${idx}</td>
      <td><input type="text" name="items[${idx}][description]" class="form-control item-desc" placeholder="Description"></td>
      <td><input type="number" step="0.01" name="items[${idx}][qty]" class="form-control item-qty" placeholder="Qty"></td>
      <td><input type="number" step="1" min="0" name="items[${idx}][rs]" class="form-control item-rs" ${isMain? '' : 'disabled'} placeholder="Rs"></td>
      <td><input type="number" step="1" min="0" max="99" name="items[${idx}][cts]" class="form-control item-cts" ${isMain? '' : 'disabled'} placeholder="Cts"></td>
      <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(tr);
  });

  recalc();

  // Auto update Customer and To labels
  const customerSelect = document.querySelector('select[name="customer_id"]');
  const fromBranchSelect = document.querySelector('select[name="from_branch_id"]');
  const toBranchSelect = document.querySelector('select[name="to_branch_id"]');
  const customerDisplay = document.getElementById('customerDisplay');
  const fromBranchDisplay = document.getElementById('fromBranchDisplay');
  const toBranchDisplay = document.getElementById('toBranchDisplay');
  const customerLocDisplay = document.getElementById('customerLocDisplay');
  const toBranchSuggest = document.getElementById('toBranchSuggest');
  const branchesData = <?php echo json_encode(array_map(function($b){ return ['id'=>(int)$b['id'],'name'=>$b['name']]; }, $branchesAll ?? []), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  const customersData = <?php echo json_encode(array_map(function($c){ return ['id'=>(int)$c['id'],'delivery_location'=>$c['delivery_location'] ?? '']; }, $customersAll ?? []), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  function updateMeta(){
    const selIdx = customerSelect?.selectedIndex ?? -1;
    const custText = customerSelect?.options[selIdx]?.text || 'selected above';
    const fromText = fromBranchSelect?.options[fromBranchSelect.selectedIndex]?.text || 'selected above';
    const toText = toBranchSelect?.options[toBranchSelect.selectedIndex]?.text || 'selected above';
    if (customerDisplay) customerDisplay.textContent = custText;
    if (fromBranchDisplay) fromBranchDisplay.textContent = fromText;
    if (toBranchDisplay) toBranchDisplay.textContent = toText;
    // Customer delivery location
    const custId = parseInt(customerSelect?.value || '0');
    const cRow = customersData.find(c => c.id === custId);
    const loc = (cRow?.delivery_location || '').trim();
    if (customerLocDisplay) customerLocDisplay.textContent = loc || '—';
    // Suggest To Branch by matching branch name in location
    if (toBranchSuggest) toBranchSuggest.innerHTML = '';
    if (loc) {
      const lcf = loc.toLowerCase();
      const matches = branchesData.filter(b => lcf.includes(String(b.name).toLowerCase()));
      if (matches.length === 1) {
        // Auto-select
        if (toBranchSelect) {
          toBranchSelect.value = String(matches[0].id);
          toBranchSelect.dispatchEvent(new Event('change'));
        }
      } else if (matches.length > 1 && toBranchSuggest) {
        const html = 'Suggested: ' + matches.map(m => `<a href="#" data-pick-branch="${m.id}">${m.name}</a>`).join(' | ');
        toBranchSuggest.innerHTML = html;
      }
    }
  }
  customerSelect?.addEventListener('change', updateMeta);
  fromBranchSelect?.addEventListener('change', updateMeta);
  toBranchSelect?.addEventListener('change', updateMeta);
  updateMeta();

  // Handle clicking suggestion links
  toBranchSuggest?.addEventListener('click', function(e){
    const a = e.target.closest('[data-pick-branch]');
    if (a) {
      e.preventDefault();
      const id = a.getAttribute('data-pick-branch');
      if (toBranchSelect) {
        toBranchSelect.value = id;
        toBranchSelect.dispatchEvent(new Event('change'));
      }
    }
  });

  // Delivery Location finder (suggest customers by location)
  const allCustomers = <?php echo json_encode(array_map(function($c){ return [
    'id'=>(int)$c['id'],
    'name'=>$c['name'],
    'phone'=>$c['phone'],
    'delivery_location'=>$c['delivery_location'] ?? ''
  ]; }, $customersAll ?? []), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
  const locQ = document.getElementById('locQuery');
  const locRes = document.getElementById('locResults');
  const customerSelect2 = document.querySelector('select[name="customer_id"]');
  function renderLoc(matches){
    if (!locRes) return;
    if (!matches || matches.length===0) { locRes.innerHTML = '<div class="text-muted">No matches.</div>'; return; }
    locRes.innerHTML = matches.slice(0,50).map(c => `
      <div class="d-flex justify-content-between align-items-center border-bottom py-1">
        <div>
          <div><strong>${c.name}</strong> <span class="text-muted">(${c.phone})</span></div>
          <div class="text-muted">${c.delivery_location || ''}</div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" data-pick="${c.id}">Use</button>
      </div>
    `).join('');
  }
  function doSearch(){
    const q = (locQ?.value || '').toLowerCase().trim();
    if (!q) { renderLoc([]); return; }
    const m = allCustomers.filter(c => (c.delivery_location||'').toLowerCase().includes(q));
    // boost by To Branch name if selected
    const toText = toBranchSelect?.options[toBranchSelect.selectedIndex]?.text?.toLowerCase() || '';
    if (toText) {
      const withBranch = m.filter(c => (c.delivery_location||'').toLowerCase().includes(toText));
      const withoutBranch = m.filter(c => !(c.delivery_location||'').toLowerCase().includes(toText));
      renderLoc([...withBranch, ...withoutBranch]);
    } else {
      renderLoc(m);
    }
  }
  locQ?.addEventListener('input', doSearch);
  toBranchSelect?.addEventListener('change', doSearch);
  locRes?.addEventListener('click', function(e){
    const btn = e.target.closest('[data-pick]');
    if (btn) {
      const id = btn.getAttribute('data-pick');
      if (customerSelect2) {
        customerSelect2.value = id;
        customerSelect2.dispatchEvent(new Event('change'));
      }
    }
  });
})();
</script>
<?php $cfgMaps = (require __DIR__ . '/../../config/config.php'); $gmKey = $cfgMaps['google_maps_api_key'] ?? ''; if ($gmKey): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($gmKey); ?>&libraries=places"></script>
<script>
  (function(){
    function attachAutocomplete(selector){
      var input = document.querySelector(selector);
      if (!input || !(window.google && google.maps && google.maps.places)) return;
      var ac = new google.maps.places.Autocomplete(input, { types: ['geocode'] });
      ac.addListener('place_changed', function(){
        var place = ac.getPlace();
        if (place && place.formatted_address) {
          input.value = place.formatted_address;
        }
      });
    }
    attachAutocomplete('#quickAddCustomer input[name="delivery_location"]');
  })();
  </script>
<?php endif; ?>
