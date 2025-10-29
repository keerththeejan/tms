<?php /** @var array $parcel */ ?>
<style>
  .receipt-box { border: 2px solid #0d6efd; border-radius: 6px; }
  .receipt-header { background: #e7f1ff; border-bottom: 2px solid #0d6efd; }
  .receipt-grid th, .receipt-grid td { border: 1px solid #0d6efd; vertical-align: middle; }
  .receipt-grid th { background: #f8fbff; text-transform: uppercase; font-size: 0.85rem; }
  .receipt-total { background: #e7f1ff; border-top: 2px solid #0d6efd; font-weight: 700; }
  .serial-badge { border: 2px solid #0d6efd; padding: .25rem .5rem; border-radius: .25rem; font-weight: 600; }
</style>

<?php 
  $isEdit = (int)($parcel['id'] ?? 0) > 0; 
  $policy = $policy ?? ['priceOnly'=>false,'lockAll'=>false,'canEnterItemAmounts'=>false];
  $priceOnly = !empty($policy['priceOnly']);
  $lockAll = !empty($policy['lockAll']);
  $canEnterItemAmounts = !empty($policy['canEnterItemAmounts']);
  // Build unique branch list by case-insensitive name, prefer main branch
  $branchesUnique = [];
  foreach (($branchesAll ?? []) as $b) {
    $nm = trim((string)($b['name'] ?? ''));
    if ($nm === '') { continue; }
    $key = strtolower($nm);
    if (!isset($branchesUnique[$key])) { $branchesUnique[$key] = $b; continue; }
    $curr = $branchesUnique[$key];
    $isMainNew = !empty($b['is_main']);
    $isMainCurr = !empty($curr['is_main']);
    if ($isMainNew && !$isMainCurr) { $branchesUnique[$key] = $b; }
  }
  $branchesList = array_values($branchesUnique);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><?php echo $parcel['id'] ? 'Edit Parcel' : 'New Parcel'; ?></h3>
  <a href="<?php echo Helpers::baseUrl('index.php?page=parcels'); ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> Next</a>
</div>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_parcel_saved'])): ?>
  <?php 
    $flash = $_SESSION['flash_parcel_saved']; 
    // Resolve customer name/phone from provided list
    $custName = 'Customer #'.(int)($flash['customer_id'] ?? 0);
    $custPhone = '';
    if (!empty($customersAll)) {
      foreach ($customersAll as $c) {
        if ((int)$c['id'] === (int)$flash['customer_id']) { $custName = (string)$c['name']; $custPhone = (string)($c['phone'] ?? ''); break; }
      }
    }
    $veh = trim((string)($flash['vehicle_no'] ?? ''));
    $msg = 'Saved Parcel #'.(int)($flash['id'] ?? 0).' for ' . htmlspecialchars($custName);
    if ($custPhone !== '') { $msg .= ' ('.htmlspecialchars($custPhone).')'; }
    if ($veh !== '') { $msg .= ' — Vehicle: '.htmlspecialchars($veh); }
  ?>
  <div class="alert alert-success py-2">
    <?php echo $msg; ?>
  </div>
  <?php unset($_SESSION['flash_parcel_saved']); ?>
<?php endif; ?>

<form method="post" action="<?php echo Helpers::baseUrl('index.php?page=parcels&action=save'); ?>" autocomplete="off">
  <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
  <input type="hidden" name="id" value="<?php echo (int)$parcel['id']; ?>">

  <!-- Top controls (data fields required by system) -->
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <label class="form-label d-flex justify-content-between align-items-center">
        <span>Customer</span>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#quickAddCustomer" aria-expanded="false"><i class="bi bi-person-plus"></i> Quick Add</button>
      </label>
      <select name="customer_id" class="form-select" required <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> >
        <option value="">-- Select Customer --</option>
        <?php foreach (($customersAll ?? []) as $c): ?>
          <?php 
            $nm = (string)($c['name'] ?? '');
            $phRaw = trim((string)($c['phone'] ?? ''));
            // Hide internal placeholder phones like NA<epoch>-<3digits>
            $isPlaceholder = preg_match('/^NA\d{10}-\d{3}$/', $phRaw) === 1;
            $label = $nm . (!$isPlaceholder && $phRaw !== '' ? ' (' . $phRaw . ')' : '');
          ?>
          <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)($parcel['customer_id'] ?? 0) === (int)$c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
        <?php endforeach; ?>
      </select>
      <div class="collapse mt-2" id="quickAddCustomer">
        <div class="border rounded p-2 bg-light">
          <div class="row g-2">
            <div class="col-6">
              <input type="text" id="qa_name" class="form-control form-control-sm" placeholder="Name" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
            </div>
            <div class="col-6">
              <input type="text" id="qa_phone_input" class="form-control form-control-sm" placeholder="Phone" autocomplete="new-password" inputmode="tel" autocapitalize="off" autocorrect="off" spellcheck="false">
            </div>
            <div class="col-6">
              <input type="email" id="qa_email" class="form-control form-control-sm" placeholder="Email" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
            </div>
            <div class="col-6">
              <input type="text" id="qa_address" class="form-control form-control-sm" placeholder="Address" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
            </div>
            <div class="col-6">
              <input type="text" id="qa_delivery_location" name="delivery_location" class="form-control form-control-sm" placeholder="Delivery Location" list="dl_locations" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
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
                <option value="">Type</option>
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
      <div id="customerSummary" class="mt-2"></div>
    </div>
    <div class="col-md-4">
      <label class="form-label d-flex justify-content-between align-items-center">
        <span>Supplier</span>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#quickAddSupplier" aria-expanded="false"><i class="bi bi-person-plus"></i> Quick Add</button>
      </label>
      <select name="supplier_id" id="supplierSelect" class="form-select" <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> >
        <option value="0">-- None --</option>
        <?php foreach (($suppliersAll ?? []) as $s): ?>
          <?php 
            $raw = (string)($s['name'] ?? '');
            $nm = trim($raw);
            // Build a normalized token to detect placeholder-like entries such as '-- None --', 'none', ' - none - '
            $norm = strtolower(preg_replace('/[^a-z0-9]+/i','', $nm)); // remove non-alnum
            if ($nm === '' || $norm === 'none' || $norm === 'nonenone') { continue; }
            $ph = trim((string)($s['phone'] ?? ''));
            $label = $nm . ($ph !== '' ? ' (' . htmlspecialchars($ph) . ')' : '');
          ?>
          <option data-phone="<?php echo htmlspecialchars($ph); ?>" value="<?php echo (int)$s['id']; ?>" <?php echo ((int)($parcel['supplier_id'] ?? 0) === (int)$s['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
        <?php endforeach; ?>
      </select>
      <div id="supplierPhoneHint" class="form-text"></div>
      <div class="collapse mt-2" id="quickAddSupplier">
        <div class="border rounded p-2 bg-light">
          <div class="row g-2">
            <div class="col-6"><input type="text" id="qs_name" class="form-control form-control-sm" placeholder="Supplier name"></div>
            <div class="col-6"><input type="text" id="qs_phone" class="form-control form-control-sm" placeholder="Phone"></div>
            <div class="col-6">
              <select id="qs_branch" class="form-select form-select-sm">
                <option value="0">-- Select Branch --</option>
                <?php foreach (($branchesList ?? []) as $b): ?>
                  <option value="<?php echo (int)$b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6"><input type="text" id="qs_code" class="form-control form-control-sm" placeholder="Code (optional)"></div>
            <div class="col-12 text-end"><button type="button" id="qs_submit" class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save & Use</button></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <label class="form-label">Date</label>
      <input type="date" class="form-control" id="parcelDate" name="created_date" value="<?php echo htmlspecialchars(substr((string)($parcel['created_at'] ?? date('Y-m-d')),0,10)); ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label d-flex justify-content-between align-items-center">
        <span>From Branch</span>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#quickAddFromBranch" aria-expanded="false"><i class="bi bi-building-add"></i> Quick Add</button>
      </label>
      <select name="from_branch_id" class="form-select" required <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> >
        <option value="">-- Select Branch --</option>
        <?php foreach (($branchesList ?? []) as $b): ?>
          <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($parcel['from_branch_id'] ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
        <?php endforeach; ?>
      </select>
      <div class="collapse mt-2" id="quickAddFromBranch">
        <div class="border rounded p-2 bg-light">
          <div class="row g-2">
            <div class="col-6"><input type="text" id="fab_name" class="form-control form-control-sm" placeholder="Branch name"></div>
            <div class="col-4"><input type="text" id="fab_code" class="form-control form-control-sm" placeholder="Code"></div>
            <div class="col-2 d-flex align-items-center"><div class="form-check"><input id="fab_main" class="form-check-input" type="checkbox"> <label class="form-check-label" for="fab_main">Main</label></div></div>
            <div class="col-12 text-end"><button type="button" id="fab_submit" class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save & Use</button></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <label class="form-label d-flex justify-content-between align-items-center">
        <span>To Branch</span>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#quickAddToBranch" aria-expanded="false"><i class="bi bi-building-add"></i> Quick Add</button>
      </label>
      <select name="to_branch_id" class="form-select" required <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> >
        <option value="">-- Select Branch --</option>
        <?php foreach (($branchesList ?? []) as $b): ?>
          <option value="<?php echo (int)$b['id']; ?>" <?php echo ((int)($parcel['to_branch_id'] ?? 0) === (int)$b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
        <?php endforeach; ?>
      </select>
      <div id="toBranchSuggest" class="form-text"></div>
      <div class="collapse mt-2" id="quickAddToBranch">
        <div class="border rounded p-2 bg-light">
          <div class="row g-2">
            <div class="col-6"><input type="text" id="tab_name" class="form-control form-control-sm" placeholder="Branch name"></div>
            <div class="col-4"><input type="text" id="tab_code" class="form-control form-control-sm" placeholder="Code"></div>
            <div class="col-2 d-flex align-items-center"><div class="form-check"><input id="tab_main" class="form-check-input" type="checkbox"> <label class="form-check-label" for="tab_main">Main</label></div></div>
            <div class="col-12 text-end"><button type="button" id="tab_submit" class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save & Use</button></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <label class="form-label d-flex justify-content-between align-items-center">
        <span>Vehicle No.</span>
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#quickAddVehicle" aria-expanded="false"><i class="bi bi-truck"></i> Quick Add</button>
      </label>
      <?php if (!empty($vehiclesAll)): ?>
        <select name="vehicle_no" class="form-select" id="vehicleSelect" <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> >
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
        <input type="text" name="vehicle_no" class="form-control" id="vehicleInput" placeholder="e.g., AB-1234" value="<?php echo htmlspecialchars($parcel['vehicle_no'] ?? ''); ?>" <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> >
      <?php endif; ?>
      <div class="collapse mt-2" id="quickAddVehicle">
        <div class="border rounded p-2 bg-light">
          <div class="row g-2">
            <div class="col-8"><input type="text" id="qv_no" class="form-control form-control-sm" placeholder="e.g., REG011 or AB-1234"></div>
            <div class="col-4 text-end"><button type="button" id="qv_submit" class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save & Use</button></div>
          </div>
        </div>
      </div>
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
        <input class="form-check-input" type="checkbox" value="1" id="lorry_full" name="lorry_full" <?php echo $lorryChecked ? 'checked' : ''; ?> <?php echo ($lockAll || $priceOnly) ? 'disabled' : ''; ?> >
        <label class="form-check-label" for="lorry_full">
          Lorry Full (start next lorry after saving)
        </label>
      </div>
    </div>
  </div>

  <!-- Full-width Previous Bill Preview (moved outside left column) -->
  <div id="billPreview" class="mb-3" style="display:none;">
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold">Previous Bill</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="billPreviewClose">Close</button>
      </div>
      <div class="card-body p-0" style="height:700px; max-height:75vh;">
        <iframe id="billPreviewFrame" src="about:blank" style="border:0; width:100%; height:100%;"></iframe>
      </div>
    </div>
  </div>

  <!-- Receipt-like box -->
  <div class="receipt-box mt-4">
    <div class="receipt-header p-2 d-flex justify-content-between align-items-center">
      <div>TS Transport</div>
      <div class="serial-badge d-flex align-items-center gap-2">
        <label for="serialInput" class="mb-0 me-1">Serial:</label>
        <input type="text" id="serialInput" name="tracking_number" class="form-control form-control-sm" style="max-width: 180px;" placeholder="Auto" value="<?php echo htmlspecialchars((string)($parcel['tracking_number'] ?? '')); ?>" />
      </div>
    </div>
    <div class="p-2">
      <div><strong>Customer:</strong> <span id="recCustomer">-- Select Customer --</span></div>
      <div><strong>Date:</strong> <span id="recDate"><?php echo htmlspecialchars(substr((string)($parcel['created_at'] ?? date('Y-m-d')),0,10)); ?></span></div>
      <div><strong>From:</strong> <span id="recFrom">—</span>&nbsp;&nbsp; <strong>To:</strong> <span id="recTo">—</span></div>
      <div><strong>Vehicle:</strong> <span id="recVehicle">—</span></div>
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
              <td><input type="text" name="items[<?php echo $rowIndex; ?>][description]" class="form-control item-desc" value="<?php echo htmlspecialchars($it['description'] ?? ''); ?>" placeholder="Description" <?php echo ($lockAll || ($isEdit && $priceOnly)) ? 'readonly' : ''; ?>></td>
              <td><input type="number" step="0.01" name="items[<?php echo $rowIndex; ?>][qty]" class="form-control item-qty" value="<?php echo htmlspecialchars((string)$q); ?>" placeholder="Qty" <?php echo ($lockAll || ($isEdit && $priceOnly)) ? 'readonly' : ''; ?>></td>
              <td><input type="number" step="1" min="0" name="items[<?php echo $rowIndex; ?>][rs]" class="form-control item-rs" value="<?php echo $rs>0?(string)$rs:''; ?>" <?php echo ($lockAll || !$canEnterItemAmounts) ? 'disabled' : ''; ?> placeholder="Rs"></td>
              <td><input type="number" step="1" min="0" max="99" name="items[<?php echo $rowIndex; ?>][cts]" class="form-control item-cts" value="<?php echo $amt>0?str_pad((string)$ct,2,'0',STR_PAD_LEFT):''; ?>" <?php echo ($lockAll || !$canEnterItemAmounts) ? 'disabled' : ''; ?> placeholder="Cts"></td>
              <td class="text-center"><?php if (!$isEdit && !$lockAll): ?><button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="bi bi-x"></i></button><?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
            <tr>
              <td class="text-center align-middle">1</td>
              <td><input type="text" name="items[1][description]" class="form-control item-desc" placeholder="Description" <?php echo ($lockAll || ($isEdit && $priceOnly)) ? 'readonly' : ''; ?>></td>
              <td><input type="number" step="0.01" name="items[1][qty]" class="form-control item-qty" placeholder="Qty" <?php echo ($lockAll || ($isEdit && $priceOnly)) ? 'readonly' : ''; ?>></td>
              <td><input type="number" step="1" min="0" name="items[1][rs]" class="form-control item-rs" <?php echo ($lockAll || !$canEnterItemAmounts) ? 'disabled' : ''; ?> placeholder="Rs"></td>
              <td><input type="number" step="1" min="0" max="99" name="items[1][cts]" class="form-control item-cts" <?php echo ($lockAll || !$canEnterItemAmounts) ? 'disabled' : ''; ?> placeholder="Cts"></td>
              <td class="text-center"><?php if (!$isEdit && !$lockAll): ?><button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="bi bi-x"></i></button><?php endif; ?></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
        <div class="text-end mb-2">
          <?php if (!$isEdit && !$lockAll): ?>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addRow"><i class="bi bi-plus-lg"></i> Add Row</button>
          <?php endif; ?>
        </div>
      </div>

      <div class="receipt-total p-2 d-flex justify-content-end">
        <div class="text-end" style="min-width: 360px;">
          <?php $currPrice = (float)($parcel['price'] ?? 0); ?>
          <div class="row g-2 align-items-center">
            <div class="col-auto">
              <label class="col-form-label"><strong>Total</strong></label>
            </div>
            <div class="col">
              <input type="number" step="0.01" min="0" class="form-control" name="price" id="totalPrice" value="<?php echo $currPrice>0? number_format($currPrice,2,'.','') : ''; ?>" <?php 
                echo ($lockAll || !$priceOnly) ? 'disabled' : '';
              ?> placeholder="0.00">
            </div>
            <?php if ($priceOnly && !$lockAll): ?>
              <div class="col-auto">
                <label class="col-form-label"><strong>Discount</strong></label>
              </div>
              <div class="col">
                <input type="number" step="0.01" min="0" class="form-control" name="discount" id="discountInput" value="" placeholder="0.00">
              </div>
            <?php endif; ?>
            <div class="col-auto">
              <span class="fs-5" id="totalDisplay"><?php echo $parcel['price']===null ? '—' : number_format((float)$parcel['price'],2); ?></span>
            </div>
          </div>
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
  const isEdit = <?php echo $isEdit ? 'true' : 'false'; ?>;
  const priceOnly = <?php echo $priceOnly ? 'true' : 'false'; ?>;
  const lockAll = <?php echo $lockAll ? 'true' : 'false'; ?>;
  const canEnterItemAmounts = <?php echo $canEnterItemAmounts ? 'true' : 'false'; ?>;
  const table = document.getElementById('itemsTable');
  const addBtn = document.getElementById('addRow');
  const totalDisplay = document.getElementById('totalDisplay');
  const totalPrice = document.getElementById('totalPrice');
  const discountInput = document.getElementById('discountInput');

  function recalc(){
    if (lockAll) { return; }
    // When item amounts are allowed (Kilinochchi), derive from RS/CTS
    // EXCEPT during price-only edit (user types price manually)
    if (canEnterItemAmounts && !(isEdit && priceOnly)) {
      let total = 0;
      const rows = table.querySelectorAll('tbody tr');
      rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value || '0');
        const rsInput = row.querySelector('.item-rs');
        const ctsInput = row.querySelector('.item-cts');
        const rs = parseFloat(rsInput?.value || '0');
        const cts = parseFloat(ctsInput?.value || '0');
        const perUnit = rs + (cts/100);
        const line = (qty > 0 && perUnit > 0) ? (qty * perUnit) : 0;
        total += line;
      });
  // Supplier phone hint + Vehicle display
  const supplierSelect = document.getElementById('supplierSelect');
  const supplierPhoneHint = document.getElementById('supplierPhoneHint');
  function updateSupplierHint(){
    if (!supplierSelect || !supplierPhoneHint) return;
    const opt = supplierSelect.options[supplierSelect.selectedIndex];
    const ph = opt ? (opt.getAttribute('data-phone') || '') : '';
    supplierPhoneHint.textContent = ph ? ('Supplier Phone: ' + ph) : '';
  }
  supplierSelect?.addEventListener('change', updateSupplierHint);
  updateSupplierHint();

  const vehicleSelect = document.getElementById('vehicleSelect');
  const vehicleInput = document.getElementById('vehicleInput');
  const recVehicle = document.getElementById('recVehicle');
  function updateVehicle(){
    if (!recVehicle) return;
    const v = vehicleSelect ? (vehicleSelect.value || '') : (vehicleInput?.value || '');
    recVehicle.textContent = v && v.trim() !== '' ? v : '—';
  }
  vehicleSelect?.addEventListener('change', updateVehicle);
  vehicleInput?.addEventListener('input', updateVehicle);
  updateVehicle();

  // Quick Add Vehicle in parcel form
  document.getElementById('qv_submit')?.addEventListener('click', async function(){
    const vInput = document.getElementById('qv_no');
    const v = (vInput?.value || '').trim();
    if (!v) { alert('Enter a vehicle number'); return; }
    try {
      const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
      const fd = new FormData();
      fd.append('csrf_token', csrf);
      fd.append('vehicle_no', v);
      const res = await fetch('<?php echo Helpers::baseUrl('index.php?page=vehicles&action=save'); ?>', {
        method: 'POST', headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' }, body: fd
      });
      if (!res.ok) throw new Error('Failed');
      const data = await res.json();
      if (vehicleSelect) {
        const idLabel = String(data.vehicle_no || v);
        let exists = false;
        Array.from(vehicleSelect.options).forEach(o=>{ if ((o.value||'') === idLabel) exists = true; });
        if (!exists) { const opt = document.createElement('option'); opt.value = idLabel; opt.textContent = idLabel; vehicleSelect.appendChild(opt); }
        const wasDisabled = vehicleSelect.disabled; if (wasDisabled) vehicleSelect.disabled = false;
        vehicleSelect.value = idLabel;
        vehicleSelect.dispatchEvent(new Event('change'));
        vehicleSelect.dispatchEvent(new Event('input'));
        if (wasDisabled) vehicleSelect.disabled = true;
      } else if (vehicleInput) {
        vehicleInput.value = String(data.vehicle_no || v);
        vehicleInput.dispatchEvent(new Event('input'));
      }
      if (vInput) vInput.value = '';
      const c = document.getElementById('quickAddVehicle'); if (c && window.bootstrap) new bootstrap.Collapse(c, {toggle:true});
    } catch(e) {
      alert('Failed to add vehicle');
    }
  });
  // Quick Add Branch: From Branch (fab_*)
  const fabBtn = document.getElementById('fab_submit');
  fabBtn?.addEventListener('click', async function(){
    const name = (document.getElementById('fab_name')?.value || '').trim();
    const code = (document.getElementById('fab_code')?.value || '').trim();
    const isMain = !!document.getElementById('fab_main')?.checked;
    if (!name || !code) { alert('Enter branch name and code'); return; }
    try {
      const created = await quickAdd(name, code, isMain);
      const sel = document.querySelector('select[name="from_branch_id"]');
      if (sel) {
        // Avoid duplicate option and update label if present
        const idStr = String(created.id);
        const label = String(created.name||name);
        let exists = false;
        Array.from(sel.options).forEach(o=>{ if (String(o.value) === idStr) { o.textContent = label; exists = true; } });
        if (!exists) { const opt=document.createElement('option'); opt.value=idStr; opt.textContent=label; sel.appendChild(opt); }
        const wasDisabled = sel.disabled; if (wasDisabled) sel.disabled = false;
        sel.value = idStr;
        sel.dispatchEvent(new Event('change'));
        sel.dispatchEvent(new Event('input'));
        if (wasDisabled) sel.disabled = true;
      }
      // Clear inputs and close collapse
      ['fab_name','fab_code'].forEach(id=>{ const el=document.getElementById(id); if (el) el.value=''; });
      const fabMain = document.getElementById('fab_main'); if (fabMain) fabMain.checked = false;
      const collapseEl = document.getElementById('quickAddFromBranch'); if (collapseEl && window.bootstrap) new bootstrap.Collapse(collapseEl, {toggle:true});
    } catch (e) {
      alert('Failed to add branch');
    }
  });

  // Quick Add Branch: To Branch (tab_*)
  const tabBtn = document.getElementById('tab_submit');
  tabBtn?.addEventListener('click', async function(){
    const name = (document.getElementById('tab_name')?.value || '').trim();
    const code = (document.getElementById('tab_code')?.value || '').trim();
    const isMain = !!document.getElementById('tab_main')?.checked;
    if (!name || !code) { alert('Enter branch name and code'); return; }
    try {
      const created = await quickAdd(name, code, isMain);
      const sel = document.querySelector('select[name="to_branch_id"]');
      if (sel) {
        const idStr = String(created.id);
        const label = String(created.name||name);
        let exists = false;
        Array.from(sel.options).forEach(o=>{ if (String(o.value) === idStr) { o.textContent = label; exists = true; } });
        if (!exists) { const opt=document.createElement('option'); opt.value=idStr; opt.textContent=label; sel.appendChild(opt); }
        const wasDisabled = sel.disabled; if (wasDisabled) sel.disabled = false;
        sel.value = idStr;
        sel.dispatchEvent(new Event('change'));
        sel.dispatchEvent(new Event('input'));
        if (wasDisabled) sel.disabled = true;
      }
      ['tab_name','tab_code'].forEach(id=>{ const el=document.getElementById(id); if (el) el.value=''; });
      const tabMain = document.getElementById('tab_main'); if (tabMain) tabMain.checked = false;
      const collapseEl = document.getElementById('quickAddToBranch'); if (collapseEl && window.bootstrap) new bootstrap.Collapse(collapseEl, {toggle:true});
    } catch (e) {
      alert('Failed to add branch');
    }
  });
  // Quick Add Supplier handlers
  const qsBtn = document.getElementById('qs_submit');
  qsBtn?.addEventListener('click', async function(){
    const name = (document.getElementById('qs_name')?.value || '').trim();
    const phone = (document.getElementById('qs_phone')?.value || '').trim();
    let branchId = parseInt(document.getElementById('qs_branch')?.value || '0');
    const code = (document.getElementById('qs_code')?.value || '').trim();
    if (!name) { alert('Enter supplier name'); return; }
    const norm = name.toLowerCase().replace(/[^a-z0-9]+/g,'');
    if (norm === 'none' || norm === 'nonenone') { alert('Invalid supplier name'); return; }
    if (!branchId || branchId <= 0) {
      // Try to fallback to selected From Branch, else to user's branch
      const fb = parseInt(document.querySelector('select[name="from_branch_id"]')?.value || '0');
      branchId = fb > 0 ? fb : (userBranchId || 0);
    }
    if (!branchId || branchId <= 0) { alert('Select a branch'); return; }
    try {
      const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
      const fd = new FormData();
      fd.append('csrf_token', csrf);
      fd.append('ajax', '1');
      fd.append('id', '0');
      fd.append('name', name);
      fd.append('phone', phone);
      fd.append('branch_id', String(branchId));
      fd.append('supplier_code', code);
      const res = await fetch('<?php echo Helpers::baseUrl('index.php?page=suppliers&action=save'); ?>', {
        method: 'POST',
        headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' },
        body: fd
      });
      if (!res.ok) throw new Error('Failed');
      const data = await res.json();
      if (data && data.error) { alert(data.error); return; }
      if (!data || !data.id) throw new Error('Invalid response');
      const sel = document.querySelector('select[name="supplier_id"]');
      if (sel) {
        const idStr = String(data.id);
        const label = String(data.name||name) + (data.phone ? ' (' + String(data.phone).trim() + ')' : (phone ? ' (' + phone + ')' : ''));
        let exists = false;
        Array.from(sel.options).forEach(o=>{ if (String(o.value) === idStr) { o.textContent = label; o.setAttribute('data-phone', data.phone || phone || ''); exists = true; } });
        if (!exists) { const opt=document.createElement('option'); opt.value=idStr; opt.textContent=label; opt.setAttribute('data-phone', data.phone || phone || ''); sel.appendChild(opt); }
        const wasDisabled = sel.disabled; if (wasDisabled) sel.disabled = false;
        sel.value = idStr;
        sel.dispatchEvent(new Event('change'));
        sel.dispatchEvent(new Event('input'));
        if (wasDisabled) sel.disabled = true;
      }
      // Clear inputs
      const ids = ['qs_name','qs_phone','qs_code']; ids.forEach(id=>{ const el=document.getElementById(id); if (el) el.value=''; });
      const qsBranch = document.getElementById('qs_branch'); if (qsBranch) qsBranch.value='0';
      // Close collapse
      const collapseEl = document.getElementById('quickAddSupplier'); if (collapseEl && window.bootstrap) new bootstrap.Collapse(collapseEl, {toggle:true});
    } catch (e) {
      alert('Failed to add supplier');
    }
  });
      if (total > 0) {
        totalDisplay.textContent = total.toFixed(2);
        if (totalPrice) totalPrice.value = total.toFixed(2);
      } else {
        totalDisplay.textContent = '—';
        // Do not clear manual price during price-only edit
        if (!(isEdit && priceOnly)) {
          if (totalPrice) totalPrice.value = '';
        }
      }
      return;
    }
    // Otherwise (other branches), show price minus discount on edit
    if (isEdit) {
      const p = parseFloat(totalPrice?.value || '0') || 0;
      const d = parseFloat(discountInput?.value || '0') || 0;
      const v = Math.max(0, p - Math.max(0, d));
      totalDisplay.textContent = v > 0 ? v.toFixed(2) : '—';
      return;
    }
    // Default fallback for create in other branches
    totalDisplay.textContent = totalPrice?.value ? String(totalPrice.value) : '—';
  }

  // Keep total display in sync while typing manual price in price-only mode
  totalPrice?.addEventListener('input', function(){
    if (priceOnly) {
      const v = parseFloat(totalPrice.value || '0');
      totalDisplay.textContent = v > 0 ? v.toFixed(2) : '—';
    }
  });

  async function quickAdd(name, code, isMain){
    const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
    const form = new FormData();
    form.append('csrf_token', csrf);
    form.append('ajax', '1');
    form.append('id', '0');
    form.append('name', name);
    form.append('code', code);
    if (isMain) form.append('is_main', '1');
    const res = await fetch('<?php echo Helpers::baseUrl('index.php?page=branches&action=save'); ?>', {
      method: 'POST',
      headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' },
      body: form
    });
    if (!res.ok) throw new Error('Failed to save');
    const data = await res.json();
    if (!data || !data.id) throw new Error('Invalid response');
    return { id: data.id, name: data.name || name };
  }

  table.addEventListener('input', function(e){
    const target = e.target;
    if (lockAll) return;
    if (!canEnterItemAmounts) return;
    if (target.classList.contains('item-qty') || target.classList.contains('item-rs') || target.classList.contains('item-cts')) {
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
      <td><input type="number" step="1" min="0" name="items[${idx}][rs]" class="form-control item-rs" ${canEnterItemAmounts ? '' : 'disabled'} placeholder="Rs"></td>
      <td><input type="number" step="1" min="0" max="99" name="items[${idx}][cts]" class="form-control item-cts" ${canEnterItemAmounts ? '' : 'disabled'} placeholder="Cts"></td>
      <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(tr);
    // Focus Description of the newly added row for quick typing
    const desc = tr.querySelector(`input[name="items[${idx}][description]"]`);
    if (desc) { desc.focus(); desc.select(); }
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
  // Quick Add Vehicle handlers
  const qvBtn = document.getElementById('qv_submit');
  qvBtn?.addEventListener('click', async function(){
    const v = (document.getElementById('qv_no')?.value || '').trim();
    if (!v) { alert('Enter a vehicle number'); return; }
    try {
      const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
      const fd = new FormData();
      fd.append('csrf_token', csrf);
      fd.append('vehicle_no', v);
      const res = await fetch('<?php echo Helpers::baseUrl('index.php?page=vehicles&action=save'); ?>', {
        method: 'POST',
        headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' },
        body: fd
      });
      if (!res.ok) throw new Error('Failed');
      const data = await res.json();
      // If we have a select, append option; else fill input
      const sel = document.getElementById('vehicleSelect');
      const inp = document.getElementById('vehicleInput');
      if (sel) {
        // Avoid duplicate option
        let exists = false;
        Array.from(sel.options).forEach(o=>{ if ((o.value||'').toLowerCase() === v.toLowerCase()) exists = true; });
        if (!exists) { const opt=document.createElement('option'); opt.value=v; opt.textContent=v; sel.appendChild(opt); }
        sel.value = v;
      } else if (inp) {
        inp.value = v;
      }
      document.getElementById('qv_no').value='';
      const collapseEl = document.getElementById('quickAddVehicle'); if (collapseEl && window.bootstrap) new bootstrap.Collapse(collapseEl, {toggle:true});
    } catch (e) {
      alert('Failed to add vehicle');
    }
  });
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
  async function fetchCustomerSummary(){
    const custId = parseInt(customerSelect?.value || '0');
    const box = document.getElementById('customerSummary');
    if (!box) return;
    box.innerHTML = '';
    if (!custId) return;
    try {
      const url = '<?php echo Helpers::baseUrl('index.php?page=customers&action=summary'); ?>' + '&id=' + custId;
      const res = await fetch(url, { headers: { 'Accept':'application/json' } });
      if (!res.ok) return;
      const data = await res.json();
      if (data && !data.error && ((data.total_delivery_notes||0) > 0 || (data.total_parcels||0) > 0)) {
        const due = (data.due||0);
        const cls = due > 0 ? 'alert-warning' : 'alert-info';
        const dueHtml = due > 0 ? `<div><strong>Due:</strong> <span class="text-danger">${due.toFixed(2)}</span></div>` : '';
        const links = [];
        if (data.today_delivery_note_id) {
          links.push(`<a class="btn btn-sm btn-primary me-1" target="_blank" href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=view&id='); ?>${data.today_delivery_note_id}">Open Today's Bill</a>`);
        }
        if (data.last_delivery_note_id && data.last_delivery_note_id !== data.today_delivery_note_id) {
          links.push(`<a class=\"btn btn-sm btn-outline-primary\" target=\"_blank\" href=\"<?php echo Helpers::baseUrl('index.php?page=delivery_notes&action=view&id='); ?>${data.last_delivery_note_id}\">Open Last Bill</a>`);
        }
        box.innerHTML = `
          <div class="alert ${cls} py-2">
            <div class="fw-semibold">Previous activity found for ${data.name} (${data.phone})</div>
            <div class="small text-muted">Delivery Notes: ${data.total_delivery_notes}, Parcels: ${data.total_parcels}${data.last_delivery_date ? ', Last: ' + data.last_delivery_date : ''}</div>
            ${dueHtml}
            <div class="mt-1 d-flex flex-wrap gap-1">
              <a class="btn btn-sm btn-outline-primary" href="<?php echo Helpers::baseUrl('index.php?page=search'); ?>&phone=${encodeURIComponent(data.phone)}" target="_blank">View Details</a>
              ${links.join('')}
            </div>
          </div>`;
      }
    } catch (e) { /* ignore */ }
  }
  customerSelect?.addEventListener('change', updateMeta);
  customerSelect?.addEventListener('change', fetchCustomerSummary);
  fromBranchSelect?.addEventListener('change', updateMeta);
  toBranchSelect?.addEventListener('change', updateMeta);
  // If From Branch is empty on load, default to current user's branch
  const userBranchId = <?php echo (int)((Auth::user()['branch_id'] ?? 0)); ?>;
  if (fromBranchSelect && (!fromBranchSelect.value || fromBranchSelect.value === '0') && userBranchId > 0) {
    const opt = Array.from(fromBranchSelect.options).find(o => parseInt(o.value||'0') === userBranchId);
    if (opt) { fromBranchSelect.value = String(userBranchId); fromBranchSelect.dispatchEvent(new Event('change')); }
  }
  updateMeta();
  fetchCustomerSummary();

  // Intercept 'Open ... Bill' links to show inline preview instead of navigating
  (function(){
    const summary = document.getElementById('customerSummary');
    const wrap = document.getElementById('billPreview');
    const frame = document.getElementById('billPreviewFrame');
    const closeBtn = document.getElementById('billPreviewClose');
    if (!summary || !wrap || !frame) return;
    summary.addEventListener('click', function(e){
      const a = e.target.closest('a[href*="page=delivery_notes"][href*="action="]');
      if (!a) return;
      e.preventDefault();
      let href = a.getAttribute('href');
      try {
        const u = new URL(href, window.location.origin);
        // Use print layout for clean embed
        u.searchParams.set('action','print');
        u.searchParams.set('embed','1');
        href = u.toString();
      } catch(_) { /* keep href */ }
      frame.src = href;
      wrap.style.display = '';
      // Scroll into view for convenience
      wrap.scrollIntoView({behavior:'smooth', block:'start'});
    });
    closeBtn?.addEventListener('click', function(){
      wrap.style.display = 'none';
      frame.src = 'about:blank';
    });
  })();

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

  const fromSel = document.querySelector('select[name="from_branch_id"]');
  const toSel = document.querySelector('select[name="to_branch_id"]');
  // Quick Add Customer via AJAX
  document.getElementById('qa_submit')?.addEventListener('click', async function(){
    const name = (document.getElementById('qa_name')?.value || '').trim();
    const phone = (document.getElementById('qa_phone_input')?.value || '').trim();
    const email = (document.getElementById('qa_email')?.value || '').trim();
    const address = (document.getElementById('qa_address')?.value || '').trim();
    const delivery_location = (document.getElementById('qa_delivery_location')?.value || '').trim();
    const type = (document.getElementById('qa_type')?.value || '').trim();
    // Only require Name; others optional
    if (!name) { alert('Please enter Name before saving.'); return; }
    // Basic email pattern check only if email provided
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { alert('Enter a valid email'); return; }
    try {
      const csrf = document.querySelector('input[name="csrf_token"]')?.value || '';
      const fd = new FormData();
      fd.append('csrf_token', csrf);
      fd.append('ajax', '1');
      fd.append('id', '0');
      fd.append('name', name);
      fd.append('phone', phone);
      fd.append('email', email);
      fd.append('address', address);
      fd.append('delivery_location', delivery_location);
      fd.append('customer_type', type);
      const res = await fetch('<?php echo Helpers::baseUrl('index.php?page=customers&action=save'); ?>', {
        method: 'POST',
        headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' },
        body: fd
      });
      if (!res.ok) throw new Error('Failed');
      const data = await res.json();
      if (!data || !data.id) throw new Error('Invalid response');
      // Ensure it shows up in the Customer dropdown immediately
      const sel = document.querySelector('select[name="customer_id"]');
      if (sel) {
        const idStr = String(data.id);
        const phonePart = (data.phone ? ' (' + String(data.phone).trim() + ')' : '');
        const emailPart = (data.email ? ' [' + String(data.email).trim() + ']' : '');
        const label = String(data.name || '').trim() + phonePart + emailPart;
        // Avoid duplicate options; update text if exists
        let found = false;
        Array.from(sel.options).forEach(o => {
          if (String(o.value) === idStr) { o.textContent = label; found = true; }
        });
        if (!found) {
          const opt = document.createElement('option');
          opt.value = idStr;
          opt.textContent = label;
          sel.appendChild(opt);
        }
        // If select is disabled, temporarily enable to set value so UI reflects change
        const wasDisabled = sel.disabled;
        if (wasDisabled) sel.disabled = false;
        sel.value = idStr;
        // Trigger common events so any enhancers/listeners refresh
        sel.dispatchEvent(new Event('change'));
        sel.dispatchEvent(new Event('input'));
        if (wasDisabled) sel.disabled = true;
        // Update in-memory customersData so delivery location and suggestions work without refresh
        try {
          if (Array.isArray(customersData)) {
            const cid = parseInt(idStr, 10);
            const existing = customersData.find(c => c.id === cid);
            if (!existing) customersData.push({ id: cid, delivery_location: (data.delivery_location || '').trim() });
          }
        } catch(_) { /* ignore */ }
        // Ensure header labels and summary update immediately
        try { if (typeof updateMeta === 'function') updateMeta(); } catch(_) {}
        try { if (typeof fetchCustomerSummary === 'function') fetchCustomerSummary(); } catch(_) {}
      }
      // Close the collapse
      const collapseEl = document.getElementById('quickAddCustomer'); if (collapseEl && window.bootstrap) new bootstrap.Collapse(collapseEl, {toggle:true});
      // Clear inputs
      ['qa_name','qa_phone_input','qa_email','qa_address','qa_delivery_location'].forEach(id=>{ const el=document.getElementById(id); if (el) el.value=''; });
    } catch (e) {
      alert('Failed to add customer');
    }
  });
  document.getElementById('fab_submit')?.addEventListener('click', async function(){
    const name = document.getElementById('fab_name')?.value.trim() || '';
    const code = document.getElementById('fab_code')?.value.trim() || '';
    const isMain = document.getElementById('fab_main')?.checked || false;
    if (!name || !code) { alert('Name and Code are required'); return; }
    const b = await quickAdd(name, code, isMain);
    if (fromSel) {
      const opt = document.createElement('option'); opt.value = String(b.id); opt.textContent = b.name || name; fromSel.appendChild(opt); fromSel.value = String(b.id);
      fromSel.dispatchEvent(new Event('change'));
    }
  });
  document.getElementById('tab_submit')?.addEventListener('click', async function(){
    const name = document.getElementById('tab_name')?.value.trim() || '';
    const code = document.getElementById('tab_code')?.value.trim() || '';
    const isMain = document.getElementById('tab_main')?.checked || false;
    if (!name || !code) { alert('Name and Code are required'); return; }
    const b = await quickAdd(name, code, isMain);
    if (toSel) {
      const opt = document.createElement('option'); opt.value = String(b.id); opt.textContent = b.name || name; toSel.appendChild(opt); toSel.value = String(b.id);
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
