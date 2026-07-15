<?php
/** @var array $stats */
/** @var list<array> $history */
/** @var list<array> $legacyFiles */
/** @var string $csrf */
/** @var string $apiBase */

$last = $stats['last_backup'] ?? null;
$drive = $stats['google_drive'] ?? [];
$driveOk = !empty($stats['google_drive_connected']) || (($drive['status'] ?? '') === 'Connected');
$driveDiag = $drive['diagnostics'] ?? [];
$lastDrive = $stats['last_drive_upload'] ?? null;

if (!function_exists('backup_h')) {
    function backup_h(?string $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('backup_status_badge')) {
    function backup_status_badge(string $status): string
    {
        $map = [
            'SUCCESS' => 'success',
            'PARTIAL' => 'warning',
            'FAILED' => 'danger',
            'RUNNING' => 'info',
            'PENDING' => 'secondary',
        ];
        $cls = $map[$status] ?? 'secondary';

        return '<span class="badge text-bg-' . $cls . '">' . backup_h($status) . '</span>';
    }
}
?>
<link rel="stylesheet" href="<?php echo Helpers::baseUrl('assets/css/backup-module.css?v=1'); ?>">

<div class="backup-module container-fluid px-0" id="backupModule"
     data-api-base="<?php echo backup_h($apiBase); ?>"
     data-csrf="<?php echo backup_h($csrf); ?>">

  <div class="row g-3 mb-3 align-items-stretch">
    <div class="col-12">
      <div class="backup-hero card border-0 shadow-sm">
        <div class="card-body p-3 p-md-4 d-flex flex-column flex-lg-row justify-content-between gap-3">
          <div>
            <div class="text-uppercase small text-muted fw-semibold mb-1">Enterprise Backup Management</div>
            <h1 class="h4 mb-1 fw-bold">Database Backup Control Center</h1>
            <p class="text-muted mb-0 small">
              Automatic daily MySQL backups at 02:00 · ZIP archives · Google Drive sync · Full audit trail
            </p>
          </div>
          <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-primary btn-lg d-inline-flex align-items-center gap-2" id="btnBackupNow">
              <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
              <span>Backup Now</span>
            </button>
            <a class="btn btn-outline-secondary" href="#backupHistory">
              <i class="bi bi-clock-history" aria-hidden="true"></i> History
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="backupAlert" class="alert d-none" role="alert"></div>

  <div class="card border-0 shadow-sm mb-3 d-none" id="backupProgressCard">
    <div class="card-body p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-semibold" id="backupProgressLabel">Running backup…</div>
        <div class="small text-muted" id="backupProgressPct">0%</div>
      </div>
      <div class="progress backup-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar progress-bar-striped progress-bar-animated" id="backupProgressBar" style="width:0%"></div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3" id="backupKpis">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="backup-kpi card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="backup-kpi-label">Last Backup</div>
          <div class="backup-kpi-value" id="kpiLastBackup">
            <?php echo $last ? backup_h((string) ($last['completed_at'] ?? $last['created_at'])) : 'Never'; ?>
          </div>
          <div class="small text-muted" id="kpiLastFile"><?php echo $last ? backup_h((string) ($last['filename'] ?? '')) : '—'; ?></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="backup-kpi card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="backup-kpi-label">Next Scheduled</div>
          <div class="backup-kpi-value" id="kpiNext"><?php echo backup_h((string) ($stats['next_scheduled'] ?? '—')); ?></div>
          <div class="small text-muted"><?php echo backup_h((string) ($stats['schedule_label'] ?? '')); ?></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="backup-kpi card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="backup-kpi-label">Backup Status</div>
          <div class="backup-kpi-value" id="kpiStatus"><?php echo backup_h((string) ($stats['backup_status'] ?? 'NONE')); ?></div>
          <div class="small text-muted">Duration:
            <span id="kpiDuration"><?php
              echo isset($last['duration_seconds']) ? backup_h((string) $last['duration_seconds']) . 's' : '—';
            ?></span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="backup-kpi card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="backup-kpi-label">Total Backups</div>
          <div class="backup-kpi-value" id="kpiTotal"><?php echo (int) ($stats['total_backups'] ?? 0); ?></div>
          <div class="small text-muted">Successful: <span id="kpiSuccess"><?php echo (int) ($stats['successful_backups'] ?? 0); ?></span></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="backup-kpi card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="backup-kpi-label">Google Drive Status</div>
          <div class="backup-kpi-value <?php echo $driveOk ? 'text-success' : 'text-warning'; ?>" id="kpiDrive">
            <?php echo backup_h((string) ($drive['status'] ?? 'Unknown')); ?>
          </div>
          <div class="small text-muted"><?php echo backup_h((string) ($drive['folder'] ?? 'TMS Database Backups')); ?></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="backup-kpi card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="backup-kpi-label">Drive Sync</div>
          <div class="backup-kpi-value" id="kpiSync"><?php echo backup_h((string) ($stats['google_drive_sync'] ?? '—')); ?></div>
          <div class="small text-muted">Offsite copy of each ZIP</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="backup-kpi card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="backup-kpi-label">Local Storage Used</div>
          <div class="backup-kpi-value" id="kpiLocal"><?php echo backup_h((string) ($stats['local_storage_human'] ?? '0 B')); ?></div>
          <div class="small text-muted">Keep 30 daily · 12 monthly</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="backup-kpi card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="backup-kpi-label">Last Backup Size</div>
          <div class="backup-kpi-value" id="kpiSize">
            <?php
              if ($last) {
                  $bytes = (int) ($last['size_bytes'] ?? 0);
                  if ($bytes < 1024) {
                      echo $bytes . ' B';
                  } else {
                      echo number_format($bytes / 1024, 1) . ' KB';
                  }
              } else {
                  echo '—';
              }
            ?>
          </div>
          <div class="small text-muted">Compressed ZIP</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3" id="googleDrivePanel">
    <div class="card-header bg-white border-0 py-3 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="fw-semibold">Google Drive Integration</div>
      <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnRetryDriveUploads">
          <i class="bi bi-cloud-upload" aria-hidden="true"></i> Retry Upload
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshDriveStatus">
          <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Refresh Status
        </button>
      </div>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <div class="small text-muted text-uppercase fw-semibold">Connected</div>
          <div class="fs-5 fw-bold" id="driveConnectedLabel"><?php echo $driveOk ? 'Yes' : 'No'; ?></div>
        </div>
        <div class="col-md-3">
          <div class="small text-muted text-uppercase fw-semibold">Folder Name</div>
          <div class="fw-semibold" id="driveFolderName"><?php echo backup_h((string) ($stats['google_drive_folder_name'] ?? $drive['folder'] ?? 'TMS Database Backups')); ?></div>
        </div>
        <div class="col-md-3">
          <div class="small text-muted text-uppercase fw-semibold">Folder ID</div>
          <div class="small text-break" id="driveFolderId"><?php echo backup_h((string) ($stats['google_drive_folder_id'] ?? $drive['folder_id'] ?? '—') ?: '—'); ?></div>
        </div>
        <div class="col-md-3">
          <div class="small text-muted text-uppercase fw-semibold">Last Upload</div>
          <div class="fw-semibold" id="driveLastUpload">
            <?php
              echo $lastDrive
                ? backup_h((string) ($lastDrive['google_drive_uploaded_at'] ?? $lastDrive['completed_at'] ?? '—'))
                : '—';
            ?>
          </div>
          <div class="small text-muted" id="driveLastStatus">
            Upload Status:
            <?php echo backup_h((string) ($lastDrive['google_drive_status'] ?? ($last['google_drive_status'] ?? 'NONE'))); ?>
          </div>
        </div>
      </div>

      <?php if (!empty($lastDrive['google_drive_link']) || !empty($last['google_drive_link'])): ?>
        <?php $gLink = (string) ($lastDrive['google_drive_link'] ?? $last['google_drive_link'] ?? ''); ?>
        <div class="mt-3">
          <div class="small text-muted text-uppercase fw-semibold">Google Drive Link</div>
          <a href="<?php echo backup_h($gLink); ?>" target="_blank" rel="noopener" id="driveLastLink"><?php echo backup_h($gLink); ?></a>
        </div>
      <?php else: ?>
        <div class="mt-3 d-none" id="driveLastLinkWrap">
          <div class="small text-muted text-uppercase fw-semibold">Google Drive Link</div>
          <a href="#" target="_blank" rel="noopener" id="driveLastLink"></a>
        </div>
      <?php endif; ?>

      <?php if (!$driveOk): ?>
        <div class="alert alert-warning mt-3 mb-0" id="driveSetupAlert">
          <div class="fw-semibold mb-1">Google Drive is not ready</div>
          <div class="small mb-2" id="driveSetupReason">
            <?php echo backup_h((string) ($drive['error'] ?? $driveDiag['blocking_reason'] ?? 'Service account JSON is missing.')); ?>
          </div>
          <ol class="small mb-0 ps-3">
            <li>Enable <strong>Google Drive API</strong> in Google Cloud Console.</li>
            <li>Create a <strong>Service Account</strong> and download the JSON key.</li>
            <li>Save it as <code>config/google-drive-service-account.json</code>.</li>
            <li>Create folder <strong>TMS Database Backups</strong> in your Drive, share it with the service account email as <strong>Editor</strong>.</li>
            <li>Paste the folder ID into <code>config/google-drive.php</code> → <code>folder_id</code>.</li>
            <li>Click <strong>Backup Now</strong> or <strong>Retry Upload</strong>.</li>
          </ol>
          <div class="small mt-2 mb-0">Full guide: <code>docs/GOOGLE_DRIVE_SETUP.md</code></div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3" id="backupHistory">
    <div class="card-header bg-white border-0 py-3 px-3 d-flex justify-content-between align-items-center">
      <div class="fw-semibold">Backup History</div>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshHistory">
        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Refresh
      </button>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0 backup-history-table">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Time</th>
              <th>Filename</th>
              <th class="text-end">Size</th>
              <th class="text-end">Duration</th>
              <th>Destination</th>
              <th>Status</th>
              <th>Created By</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody id="backupHistoryBody">
            <?php if (empty($history)): ?>
              <tr class="backup-empty-row"><td colspan="9" class="text-center text-muted py-4">No backups yet. Click <strong>Backup Now</strong> to create the first archive.</td></tr>
            <?php else: ?>
              <?php foreach ($history as $row): ?>
                <?php
                  $created = strtotime((string) ($row['created_at'] ?? '')) ?: time();
                  $sizeKb = number_format(((int) ($row['size_bytes'] ?? 0)) / 1024, 1);
                ?>
                <tr data-id="<?php echo (int) $row['id']; ?>">
                  <td><?php echo backup_h(date('Y-m-d', $created)); ?></td>
                  <td><?php echo backup_h(date('H:i:s', $created)); ?></td>
                  <td class="text-wrap"><code class="small"><?php echo backup_h((string) $row['filename']); ?></code></td>
                  <td class="text-end"><?php echo $sizeKb; ?> KB</td>
                  <td class="text-end"><?php echo isset($row['duration_seconds']) ? backup_h((string) $row['duration_seconds']) . 's' : '—'; ?></td>
                  <td><?php echo backup_h((string) ($row['destination'] ?? 'LOCAL')); ?></td>
                  <td><?php echo backup_status_badge((string) ($row['status'] ?? '')); ?></td>
                  <td><?php echo backup_h((string) ($row['created_by_name'] ?? '—')); ?></td>
                  <td class="text-end text-nowrap">
                    <div class="btn-group btn-group-sm">
                      <a class="btn btn-outline-secondary" title="Download"
                         href="<?php echo Helpers::baseUrl('index.php?page=backup&action=download&id=' . (int) $row['id']); ?>">
                        <i class="bi bi-download"></i>
                      </a>
                      <?php if (in_array((string) ($row['google_drive_status'] ?? ''), ['FAILED', 'RETRY', 'PENDING', 'SKIPPED'], true)
                          && in_array((string) ($row['status'] ?? ''), ['SUCCESS', 'PARTIAL'], true)
                          && empty($row['google_drive_file_id'])): ?>
                      <button type="button" class="btn btn-outline-info btn-retry-upload" title="Retry Google Drive Upload" data-id="<?php echo (int) $row['id']; ?>">
                        <i class="bi bi-cloud-arrow-up"></i>
                      </button>
                      <?php endif; ?>
                      <button type="button" class="btn btn-outline-primary btn-restore" title="Restore" data-id="<?php echo (int) $row['id']; ?>">
                        <i class="bi bi-arrow-counterclockwise"></i>
                      </button>
                      <button type="button" class="btn btn-outline-secondary btn-log" title="View Log" data-id="<?php echo (int) $row['id']; ?>">
                        <i class="bi bi-journal-text"></i>
                      </button>
                      <button type="button" class="btn btn-outline-danger btn-delete" title="Delete" data-id="<?php echo (int) $row['id']; ?>">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php if (!empty($legacyFiles)): ?>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 px-3 fw-semibold">Legacy SQL backups (pre-enterprise)</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>File</th><th class="text-end">Size</th><th>Created</th><th class="text-end">Download</th></tr></thead>
          <tbody>
          <?php foreach ($legacyFiles as $f): ?>
            <tr>
              <td><?php echo backup_h((string) $f['name']); ?></td>
              <td class="text-end"><?php echo number_format(((int) $f['size']) / 1024, 1); ?> KB</td>
              <td><?php echo backup_h(date('Y-m-d H:i:s', (int) $f['mtime'])); ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary"
                   href="<?php echo Helpers::baseUrl('index.php?page=backup&action=download&file=' . urlencode((string) $f['name'])); ?>">
                  Download
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="card border-danger shadow-sm">
    <div class="card-header bg-danger text-white d-flex align-items-center gap-2">
      <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
      <span>Reset All Data</span>
    </div>
    <div class="card-body">
      <p class="text-muted mb-3">Permanently delete all business and accounting data. Create a backup first if you may need to restore.</p>
      <button type="button" class="btn btn-danger" id="openDbResetModalBtn" data-bs-toggle="modal" data-bs-target="#dbResetModal">
        <i class="bi bi-trash3 me-1" aria-hidden="true"></i> Delete All Data
      </button>
    </div>
  </div>
</div>

<!-- Restore modal -->
<div class="modal fade" id="backupRestoreModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-warning">
      <div class="modal-header">
        <h5 class="modal-title fw-bold text-warning">Confirm Database Restore</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Restoring will overwrite the current database. A <strong>restore-point backup</strong> is created automatically first.</p>
        <input type="hidden" id="restoreBackupId" value="">
        <label class="form-label fw-semibold" for="restoreConfirmInput">Type <code>RESTORE</code> to continue</label>
        <input type="text" class="form-control" id="restoreConfirmInput" autocomplete="off" placeholder="RESTORE">
        <div id="restoreError" class="alert alert-danger py-2 mt-3 d-none"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning" id="btnConfirmRestore" disabled>Restore Database</button>
      </div>
    </div>
  </div>
</div>

<!-- Log modal -->
<div class="modal fade" id="backupLogModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Backup Log</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <pre class="backup-log-pre small mb-0" id="backupLogBody">Loading…</pre>
      </div>
    </div>
  </div>
</div>

<!-- Reset modal (preserved) -->
<div class="modal fade" id="dbResetModal" tabindex="-1" aria-labelledby="dbResetModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger bg-opacity-10 border-danger">
        <h5 class="modal-title text-danger fw-bold" id="dbResetModalTitle">
          <i class="bi bi-exclamation-octagon-fill me-2" aria-hidden="true"></i>Permanent Database Reset
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="dbResetModalClose"></button>
      </div>
      <div class="modal-body">
        <form id="dbResetForm" method="post" action="<?php echo Helpers::baseUrl('index.php?page=backup&action=reset_data'); ?>" onsubmit="return false;">
          <input type="hidden" name="csrf_token" value="<?php echo backup_h($csrf); ?>">
          <p class="mb-2">This operation will permanently delete <strong>ALL business data</strong> from the system.</p>
          <div class="alert alert-warning py-2 small mb-3">
            <strong>This action CANNOT be undone.</strong> Create a backup before continuing.
          </div>
          <label class="form-label fw-semibold" for="dbResetConfirmInput">Type <code>DELETE</code> to continue</label>
          <input type="text" class="form-control" id="dbResetConfirmInput" name="confirm_reset" autocomplete="off" placeholder="DELETE" spellcheck="false">
          <div id="dbResetError" class="alert alert-danger py-2 mt-3 d-none" role="alert"></div>
          <div id="dbResetProgress" class="d-none mt-4 text-center">
            <div class="spinner-border text-danger mb-3" role="status" aria-hidden="true"></div>
            <div class="fw-semibold text-danger" id="dbResetProgressLabel">Preparing...</div>
          </div>
          <div id="dbResetSuccess" class="d-none mt-4">
            <div class="alert alert-success mb-0">
              <div class="fw-bold mb-1">Database Reset Completed Successfully</div>
              <div>You will now be logged out.</div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-top">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="dbResetCancelBtn">Cancel</button>
        <button type="button" class="btn btn-danger" id="dbResetDeleteBtn" disabled>
          <i class="bi bi-trash3 me-1" aria-hidden="true"></i> Delete Everything
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  window.TMS_BACKUP = {
    apiBase: <?php echo json_encode($apiBase, JSON_UNESCAPED_UNICODE); ?>,
    csrf: <?php echo json_encode($csrf, JSON_UNESCAPED_UNICODE); ?>,
    downloadBase: <?php echo json_encode(Helpers::baseUrl('index.php?page=backup&action=download'), JSON_UNESCAPED_UNICODE); ?>
  };
  window.TMS_BACKUP_RESET = {
    loginUrl: <?php echo json_encode(Helpers::baseUrl('index.php?page=login&reset=1'), JSON_UNESCAPED_UNICODE); ?>
  };
</script>
<script src="<?php echo Helpers::baseUrl('assets/js/backup-module.js?v=1'); ?>"></script>
<script src="<?php echo Helpers::baseUrl('assets/js/backup-reset.js?v=1'); ?>"></script>
