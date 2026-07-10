<?php /** @var array $customer */ ?>
<?php
  $deliveryLocationOptions = $deliveryLocationOptions ?? [];
  $currentDl = trim((string)($customer['delivery_location'] ?? ''));
  $dlInList = in_array($currentDl, $deliveryLocationOptions, true);
  $showOtherInput = ($currentDl !== '' && !$dlInList);
  $customerLedger = $customerLedger ?? null;
  $cid = (int)($customer['id'] ?? 0);
  $customerCode = !empty($customerLedger['ledger_code'])
    ? (string)$customerLedger['ledger_code']
    : ($cid > 0 ? 'CUS-' . str_pad((string)$cid, 5, '0', STR_PAD_LEFT) : 'CUS-NEW');
  $outstanding = isset($customer['outstanding_amount'])
    ? number_format((float)$customer['outstanding_amount'], 2)
    : '0.00';
  $createdAt = !empty($customer['created_at']) ? substr((string)$customer['created_at'], 0, 10) : '';

  $crmCssPath = dirname(__DIR__, 2) . '/public/assets/css/customers-module.css';
  $crmCssVer = is_file($crmCssPath) ? (string) filemtime($crmCssPath) : '1';

  $cfgMaps = (require __DIR__ . '/../../config/config.php');
  $gmKey = $cfgMaps['google_maps_api_key'] ?? '';
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/customers-module.css?v=' . rawurlencode($crmCssVer)); ?>">

<div class="crm-page crm-form-page container-fluid px-0">
  <section class="crm-hero mb-3">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="crm-hero-icon" aria-hidden="true"><i class="bi bi-person-plus-fill"></i></div>
        <div>
          <h1 class="crm-hero-title h4 mb-0"><?php echo $cid ? 'Edit Customer' : 'New Customer'; ?></h1>
          <p class="crm-hero-subtitle mb-0">Complete customer profile with contact, address, and business details.</p>
        </div>
      </div>
      <a href="<?php echo Helpers::baseUrl('index.php?page=customers'); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Customers
      </a>
    </div>
  </section>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars((string)($error ?? '')); ?></div>
<?php endif; ?>

<?php if (!empty($customerLedger['ledger_code'])): ?>
  <div class="alert alert-light border d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
      <span class="text-muted small me-2">Ledger Code</span>
      <code><?php echo htmlspecialchars((string)$customerLedger['ledger_code']); ?></code>
      <span class="badge bg-primary-subtle text-primary ms-2"><?php echo htmlspecialchars((string)($customerLedger['ledger_type'] ?? 'Accounts Receivable')); ?></span>
      <?php if (isset($customerLedger['is_active']) && (int)$customerLedger['is_active'] !== 1): ?>
        <span class="badge bg-secondary ms-1">Inactive</span>
      <?php endif; ?>
    </div>
    <?php if (!empty($customerLedger['account_id'])): ?>
    <a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=accounting&action=customer_ledger&customer_id=' . $cid); ?>">
      <i class="bi bi-journal-text me-1"></i>View Ledger
    </a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <form id="crmCustomerForm" method="post" action="<?php echo Helpers::baseUrl('index.php?page=customers&action=save'); ?>" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
      <input type="hidden" name="id" value="<?php echo $cid; ?>">

      <section class="crm-form-card" aria-labelledby="crmSecBasic">
        <div class="crm-form-card-header">
          <div class="crm-form-card-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></div>
          <div>
            <h2 id="crmSecBasic" class="h6 mb-0 fw-bold">Basic Information</h2>
            <div class="small text-muted">Primary identity details</div>
          </div>
        </div>
        <div class="crm-form-card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <div class="form-floating crm-input-group">
                <input type="text" name="name" id="crmName" class="form-control crm-form-control" required value="<?php echo htmlspecialchars((string)($customer['name'] ?? '')); ?>" placeholder="Customer Name">
                <label for="crmName">Customer Name <span class="text-danger">*</span></label>
              </div>
              <div id="crmNameHint" class="form-text crm-ui-hint"></div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Customer Code</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-upc-scan" aria-hidden="true"></i></span>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($customerCode); ?>" readonly aria-readonly="true">
              </div>
              <div class="crm-ui-hint">Auto-generated from ledger / ID</div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="crmNicPreview">NIC / Passport</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-card-text" aria-hidden="true"></i></span>
                <input type="text" id="crmNicPreview" class="form-control" disabled placeholder="Not stored in current schema">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="crmDobPreview">Date of Birth</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar-event" aria-hidden="true"></i></span>
                <input type="date" id="crmDobPreview" class="form-control" disabled>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="crmGenderPreview">Gender</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-gender-ambiguous" aria-hidden="true"></i></span>
                <select id="crmGenderPreview" class="form-select" disabled><option>—</option></select>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="crm-form-card" aria-labelledby="crmSecContact">
        <div class="crm-form-card-header">
          <div class="crm-form-card-icon"><i class="bi bi-telephone" aria-hidden="true"></i></div>
          <div>
            <h2 id="crmSecContact" class="h6 mb-0 fw-bold">Contact Details</h2>
            <div class="small text-muted">Phone, email and messaging</div>
          </div>
        </div>
        <div class="crm-form-card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="crmPhone">Phone</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-phone" aria-hidden="true"></i></span>
                <input type="text" name="phone" id="crmPhone" class="form-control crm-form-control" value="<?php echo htmlspecialchars((string)($customer['phone'] ?? '')); ?>" placeholder="077 123 4567" inputmode="tel">
              </div>
              <div id="crmPhoneHint" class="form-text crm-ui-hint"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="crmMobilePreview">Mobile</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-phone-vibrate" aria-hidden="true"></i></span>
                <input type="text" id="crmMobilePreview" class="form-control" disabled placeholder="Use phone field">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="crmWhatsappPreview">WhatsApp</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-whatsapp" aria-hidden="true"></i></span>
                <input type="text" id="crmWhatsappPreview" class="form-control" disabled placeholder="Preview only">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="crmEmail">Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                <input type="email" name="email" id="crmEmail" class="form-control crm-form-control" value="<?php echo htmlspecialchars((string)($customer['email'] ?? '')); ?>" placeholder="name@example.com">
              </div>
              <div id="crmEmailHint" class="form-text crm-ui-hint"></div>
            </div>
            <div class="col-12">
              <label class="form-label" for="crmWebsitePreview">Website</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-globe2" aria-hidden="true"></i></span>
                <input type="url" id="crmWebsitePreview" class="form-control" disabled placeholder="https://">
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="crm-form-card" aria-labelledby="crmSecAddress">
        <div class="crm-form-card-header">
          <div class="crm-form-card-icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></div>
          <div>
            <h2 id="crmSecAddress" class="h6 mb-0 fw-bold">Address</h2>
            <div class="small text-muted">Physical address and delivery location</div>
          </div>
        </div>
        <div class="crm-form-card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="crmAddress">Address Line 1</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-house" aria-hidden="true"></i></span>
                <input type="text" name="address" id="crmAddress" class="form-control" value="<?php echo htmlspecialchars((string)($customer['address'] ?? '')); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="crmAddr2Preview">Address Line 2</label>
              <input type="text" id="crmAddr2Preview" class="form-control" disabled placeholder="Preview only">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="crmCityPreview">City</label>
              <input type="text" id="crmCityPreview" class="form-control" disabled placeholder="Derived from delivery location">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="crmDistrictPreview">District</label>
              <input type="text" id="crmDistrictPreview" class="form-control" disabled>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="crmProvincePreview">Province</label>
              <input type="text" id="crmProvincePreview" class="form-control" disabled>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="crmPostalPreview">Postal Code</label>
              <input type="text" id="crmPostalPreview" class="form-control" disabled>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="crmCountryPreview">Country</label>
              <input type="text" id="crmCountryPreview" class="form-control" disabled value="Sri Lanka">
            </div>
            <div class="col-12">
              <label class="form-label" for="delivery_location_select">Delivery Location</label>
              <input type="hidden" name="delivery_location" id="delivery_location_value" value="<?php echo htmlspecialchars($currentDl); ?>">
              <select id="delivery_location_select" class="form-select" data-choices-search="true" aria-label="Delivery route">
                <option value="">— Select or search —</option>
                <option value="__other__" <?php echo $showOtherInput ? ' selected' : ''; ?>>— Other (type below) —</option>
                <?php foreach ($deliveryLocationOptions as $opt): ?>
                  <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($currentDl === $opt && $dlInList) ? ' selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                <?php endforeach; ?>
              </select>
              <div id="delivery_location_other_wrap" class="mt-2" style="display:<?php echo $showOtherInput ? 'block' : 'none'; ?>;">
                <input type="text" id="delivery_location_other" class="form-control" placeholder="Type delivery location" value="<?php echo $showOtherInput ? htmlspecialchars($currentDl) : ''; ?>">
              </div>
              <input type="hidden" name="place_id" value="<?php echo htmlspecialchars((string)($customer['place_id'] ?? '')); ?>">
              <input type="hidden" name="lat" value="<?php echo htmlspecialchars((string)($customer['lat'] ?? '')); ?>">
              <input type="hidden" name="lng" value="<?php echo htmlspecialchars((string)($customer['lng'] ?? '')); ?>">
              <div id="mapPreview" class="crm-map-preview mt-2" role="img" aria-label="Map preview"></div>
            </div>
          </div>
        </div>
      </section>

      <section class="crm-form-card" aria-labelledby="crmSecBusiness">
        <div class="crm-form-card-header">
          <div class="crm-form-card-icon"><i class="bi bi-briefcase" aria-hidden="true"></i></div>
          <div>
            <h2 id="crmSecBusiness" class="h6 mb-0 fw-bold">Business Details</h2>
            <div class="small text-muted">Group, terms and status</div>
          </div>
        </div>
        <div class="crm-form-card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="crmCustomerType">Customer Group</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-diagram-3" aria-hidden="true"></i></span>
                <select name="customer_type" id="crmCustomerType" class="form-select">
                  <option value="">-- Select --</option>
                  <option value="regular" <?php echo ($customer['customer_type'] ?? '') === 'regular' ? 'selected' : ''; ?>>Regular</option>
                  <option value="corporate" <?php echo ($customer['customer_type'] ?? '') === 'corporate' ? 'selected' : ''; ?>>Corporate (VIP)</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="crmCreditPreview">Credit Limit</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-credit-card-2-front" aria-hidden="true"></i></span>
                <input type="text" id="crmCreditPreview" class="form-control" disabled placeholder="Not in schema">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="crmOpeningPreview">Opening Balance</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-cash" aria-hidden="true"></i></span>
                <input type="text" id="crmOpeningPreview" class="form-control" disabled placeholder="Managed in ledger">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="crmTermsPreview">Payment Terms</label>
              <select id="crmTermsPreview" class="form-select" disabled><option>Standard</option></select>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="crmBranchPreview">Branch</label>
              <select id="crmBranchPreview" class="form-select" disabled><option>Current branch</option></select>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="crmStatusPreview">Status</label>
              <select id="crmStatusPreview" class="form-select" disabled><option>Active</option></select>
            </div>
            <div class="col-12">
              <label class="form-label" for="crmNotesPreview">Notes</label>
              <textarea id="crmNotesPreview" class="form-control" rows="3" disabled placeholder="Internal notes (preview only)"></textarea>
            </div>
          </div>
        </div>
      </section>

      <div class="d-flex flex-wrap gap-2 justify-content-end mb-4">
        <a href="<?php echo Helpers::baseUrl('index.php?page=customers'); ?>" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i>Save Customer</button>
      </div>
    </form>
  </div>

  <div class="col-lg-4">
    <aside class="crm-summary-card" aria-label="Live customer preview">
      <div class="crm-summary-header"><i class="bi bi-eye me-2"></i>Live Preview</div>
      <div class="crm-summary-body text-center">
        <div id="crmSumAvatar" class="crm-drawer-avatar mb-2" aria-hidden="true">?</div>
        <div class="crm-summary-row"><span class="text-muted">Name</span><strong id="crmSumName" class="text-end">—</strong></div>
        <div class="crm-summary-row"><span class="text-muted">Code</span><strong id="crmSumCode" class="text-end"><?php echo htmlspecialchars($customerCode); ?></strong></div>
        <div class="crm-summary-row"><span class="text-muted">Phone</span><strong id="crmSumPhone" class="text-end">—</strong></div>
        <div class="crm-summary-row"><span class="text-muted">Group</span><strong id="crmSumGroup" class="text-end">—</strong></div>
        <div class="crm-summary-row"><span class="text-muted">Balance</span><strong id="crmSumBalance" class="text-end text-warning-emphasis">LKR <?php echo htmlspecialchars($outstanding); ?></strong></div>
        <div class="crm-summary-row"><span class="text-muted">Status</span><span id="crmSumStatus" class="text-end"><span class="badge bg-success-subtle text-success">Active</span></span></div>
        <div class="crm-summary-row border-0"><span class="text-muted">Registered</span><strong id="crmSumCreated" class="text-end"><?php echo htmlspecialchars($createdAt ?: 'On save'); ?></strong></div>
      </div>
    </aside>
  </div>
</div>
</div>

<?php if (!$gmKey): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function(){
  function bootOsmDeliveryLocation(){
  const input = document.getElementById('delivery_location_other');
  const placeIdEl = document.querySelector('input[name="place_id"]');
  const latEl = document.querySelector('input[name="lat"]');
  const lngEl = document.querySelector('input[name="lng"]');
  const mapEl = document.getElementById('mapPreview');
  if (!input) return;

  const wrap = document.createElement('div');
  wrap.className = 'osm-suggest';
  input.parentNode.insertBefore(wrap, input);
  wrap.appendChild(input);
  const list = document.createElement('div');
  list.className = 'osm-suggest-list';
  list.style.display = 'none';
  wrap.appendChild(list);

  let timer;
  input.addEventListener('input', function(){
    const q = input.value.trim();
    if (timer) clearTimeout(timer);
    if (q.length < 3) { list.style.display='none'; list.innerHTML=''; return; }
    timer = setTimeout(async ()=>{
      try {
        const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=8&q=' + encodeURIComponent(q);
        const res = await fetch(url, { headers: { 'Accept':'application/json' } });
        const data = await res.json();
        list.innerHTML = '';
        if (!Array.isArray(data) || data.length === 0) { list.style.display='none'; return; }
        data.forEach(item => {
          const div = document.createElement('div');
          div.className = 'osm-suggest-item';
          div.textContent = item.display_name;
          div.dataset.lat = item.lat;
          div.dataset.lon = item.lon;
          div.dataset.id = (item.osm_type || '') + ':' + (item.osm_id || '');
          list.appendChild(div);
        });
        list.style.display = 'block';
      } catch(e) { list.style.display='none'; }
    }, 300);
  });

  document.addEventListener('click', function(e){
    if (!wrap.contains(e.target)) { list.style.display='none'; }
  });

  let map, marker;
  function ensureMap(){
    if (!mapEl || typeof L === 'undefined') return;
    if (!map) { map = L.map(mapEl).setView([7.8731, 80.7718], 6); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OSM' }).addTo(map); }
    if (!marker) { marker = L.marker([7.8731,80.7718]).addTo(map); }
  }

  list.addEventListener('click', function(e){
    const item = e.target.closest('.osm-suggest-item');
    if (!item) return;
    input.value = item.textContent;
    list.style.display = 'none';
    var hidden = document.getElementById('delivery_location_value');
    if (hidden) hidden.value = item.textContent;
    if (placeIdEl) placeIdEl.value = item.dataset.id || '';
    const lat = parseFloat(item.dataset.lat || '');
    const lon = parseFloat(item.dataset.lon || '');
    if (!isNaN(lat) && !isNaN(lon)) {
      if (latEl) latEl.value = lat;
      if (lngEl) lngEl.value = lon;
      ensureMap();
      if (map && marker) {
        marker.setLatLng([lat, lon]);
        map.setView([lat, lon], 14);
      }
    }
  });
  input.addEventListener('input', function(){ var h = document.getElementById('delivery_location_value'); if (h) h.value = input.value; });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootOsmDeliveryLocation);
  } else {
    bootOsmDeliveryLocation();
  }
})();
</script>
<?php endif; ?>

<script>
(function(){
  function readSelectValue(sel) {
    if (!sel) return '';
    try {
      if (sel._choices) {
        const v = sel._choices.getValue(true);
        if (Array.isArray(v)) {
          const first = v[0];
          return String((first && first.value !== undefined) ? first.value : (first || ''));
        }
        return String(v || '');
      }
    } catch (_) { /* ignore */ }
    return String((sel.options[sel.selectedIndex] && sel.options[sel.selectedIndex].value) || sel.value || '');
  }
  function syncDeliveryLocation() {
    var sel = document.getElementById('delivery_location_select');
    var hidden = document.getElementById('delivery_location_value');
    var wrap = document.getElementById('delivery_location_other_wrap');
    var otherInput = document.getElementById('delivery_location_other');
    if (!sel || !hidden) return;
    var val = readSelectValue(sel);
    if (val === '__other__') {
      if (wrap) wrap.style.display = 'block';
      if (otherInput) hidden.value = otherInput.value;
    } else {
      if (wrap) wrap.style.display = 'none';
      hidden.value = val;
    }
  }
  function initDlSync() {
    var sel = document.getElementById('delivery_location_select');
    var hidden = document.getElementById('delivery_location_value');
    var otherInput = document.getElementById('delivery_location_other');
    var form = sel && sel.closest('form');
    if (!sel || !hidden) return;
    sel.addEventListener('change', syncDeliveryLocation);
    if (otherInput) otherInput.addEventListener('input', syncDeliveryLocation);
    if (form) form.addEventListener('submit', function() {
      var val = readSelectValue(sel);
      if (val === '__other__' && otherInput) {
        hidden.value = otherInput.value;
      } else if (val !== '__other__') {
        hidden.value = val;
      }
    });
    syncDeliveryLocation();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() { setTimeout(initDlSync, 150); });
  } else {
    setTimeout(initDlSync, 150);
  }
})();
</script>

<?php if ($gmKey): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($gmKey); ?>&libraries=places"></script>
<script>
(function(){
  var input = document.getElementById('delivery_location_other');
  var placeIdEl = document.querySelector('input[name="place_id"]');
  var latEl = document.querySelector('input[name="lat"]');
  var lngEl = document.querySelector('input[name="lng"]');
  var mapEl = document.getElementById('mapPreview');
  var map, marker;

  function initMapIfNeeded(){
    if (!mapEl || !window.google || !google.maps) return;
    if (!map) {
      map = new google.maps.Map(mapEl, { center: {lat: 7.8731, lng: 80.7718}, zoom: 6 });
      marker = new google.maps.Marker({ map: map });
    }
  }

  function setMarker(lat, lng){
    initMapIfNeeded();
    if (!map || !marker) return;
    var pos = {lat: lat, lng: lng};
    marker.setPosition(pos);
    map.setCenter(pos);
    map.setZoom(14);
  }

  if (input && window.google && google.maps && google.maps.places) {
    var ac = new google.maps.places.Autocomplete(input, { types: ['geocode'] });
    ac.addListener('place_changed', function(){
      var place = ac.getPlace();
      if (!place) return;
      if (place.formatted_address) {
        input.value = place.formatted_address;
        var hidden = document.getElementById('delivery_location_value');
        if (hidden) hidden.value = place.formatted_address;
      }
      if (place.place_id && placeIdEl) placeIdEl.value = place.place_id;
      var loc = place.geometry && place.geometry.location ? place.geometry.location : null;
      if (loc) {
        var lat = loc.lat();
        var lng = loc.lng();
        if (latEl) latEl.value = lat;
        if (lngEl) lngEl.value = lng;
        setMarker(lat, lng);
      }
    });
  }

  var initLat = parseFloat(latEl?.value || '');
  var initLng = parseFloat(lngEl?.value || '');
  if (!isNaN(initLat) && !isNaN(initLng)) {
    setMarker(initLat, initLng);
  }
})();
</script>
<?php endif; ?>

<script>
window.TMS_CUSTOMER_FORM = <?php echo json_encode([
  'currentId' => $cid,
  'customerCode' => $customerCode,
  'createdAt' => $createdAt,
  'outstanding' => 'LKR ' . $outstanding,
  'existingPhones' => [],
  'existingEmails' => [],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
</script>
