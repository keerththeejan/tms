<?php /** @var array $customer */ ?>
<?php // Maps config needed early (OSM/Google blocks may check $gmKey)
  $cfgMaps = (require __DIR__ . '/../../config/config.php');
  $gmKey = $cfgMaps['google_maps_api_key'] ?? '';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?php echo $customer['id'] ? 'Edit Customer' : 'New Customer'; ?></h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=customers'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if (!$gmKey): ?>
<!-- OSM Fallback: Nominatim + Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<style>
  .osm-suggest { position: relative; }
  .osm-suggest-list { position:absolute; z-index:1000; background:#fff; border:1px solid #ddd; width:100%; max-height:220px; overflow:auto; border-radius:4px; }
  .osm-suggest-item { padding:6px 8px; cursor:pointer; }
  .osm-suggest-item:hover { background:#f1f3f5; }
</style>
<script>
(function(){
  const input = document.querySelector('input[name="delivery_location"]');
  const placeIdEl = document.querySelector('input[name="place_id"]');
  const latEl = document.querySelector('input[name="lat"]');
  const lngEl = document.querySelector('input[name="lng"]');
  const mapEl = document.getElementById('mapPreview');
  if (!input) return;

  // Suggestion container
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
})();
</script>
<?php endif; ?>
<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=customers&action=save'); ?>" class="card shadow-sm">
  <div class="card-body">
    <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo (int)$customer['id']; ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($customer['name']); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone']); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($customer['address']); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Delivery Location</label>
        <input type="text" name="delivery_location" class="form-control" list="dl_locations" value="<?php echo htmlspecialchars($customer['delivery_location']); ?>">
        <input type="hidden" name="place_id" value="<?php echo htmlspecialchars($customer['place_id'] ?? ''); ?>">
        <input type="hidden" name="lat" value="<?php echo htmlspecialchars((string)($customer['lat'] ?? '')); ?>">
        <input type="hidden" name="lng" value="<?php echo htmlspecialchars((string)($customer['lng'] ?? '')); ?>">
        <datalist id="dl_locations">
          <?php if (!empty($customer['delivery_location'])): ?>
            <option value="<?php echo htmlspecialchars($customer['delivery_location']); ?>"></option>
          <?php endif; ?>
        </datalist>
        <div id="mapPreview" class="mt-2" style="height: 180px; border-radius: 6px; border: 1px solid #ddd;"></div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Customer Type</label>
        <select name="customer_type" class="form-select">
          <option value="">-- Select --</option>
          <option value="regular" <?php echo ($customer['customer_type'] ?? '') === 'regular' ? 'selected' : ''; ?>>Regular</option>
          <option value="corporate" <?php echo ($customer['customer_type'] ?? '') === 'corporate' ? 'selected' : ''; ?>>Corporate</option>
        </select>
      </div>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
  </div>
</form>
<?php $cfgMaps = (require __DIR__ . '/../../config/config.php'); $gmKey = $cfgMaps['google_maps_api_key'] ?? ''; if ($gmKey): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars($gmKey); ?>&libraries=places"></script>
<script>
(function(){
  var input = document.querySelector('input[name="delivery_location"]');
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
      if (place.formatted_address) input.value = place.formatted_address;
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

  // Initialize preview if we have saved lat/lng (edit case)
  var initLat = parseFloat(latEl?.value || '');
  var initLng = parseFloat(lngEl?.value || '');
  if (!isNaN(initLat) && !isNaN(initLng)) {
    setMarker(initLat, initLng);
  }
})();
</script>
<?php endif; ?>
