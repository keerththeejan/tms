(function () {
  'use strict';

  const cfg = window.TMS_BACKUP || {};
  const root = document.getElementById('backupModule');
  if (!root) return;

  const apiBase = cfg.apiBase || root.dataset.apiBase || '';
  const csrf = cfg.csrf || root.dataset.csrf || '';
  const downloadBase = cfg.downloadBase || (apiBase + '&action=download');

  const alertEl = document.getElementById('backupAlert');
  const progressCard = document.getElementById('backupProgressCard');
  const progressBar = document.getElementById('backupProgressBar');
  const progressLabel = document.getElementById('backupProgressLabel');
  const progressPct = document.getElementById('backupProgressPct');
  const historyBody = document.getElementById('backupHistoryBody');
  const btnBackupNow = document.getElementById('btnBackupNow');

  let progressTimer = null;

  function showAlert(type, message) {
    if (!alertEl) return;
    alertEl.className = 'alert alert-' + type;
    alertEl.textContent = message;
    alertEl.classList.remove('d-none');
    window.setTimeout(function () {
      alertEl.classList.add('d-none');
    }, 8000);
  }

  function setProgress(pct, label) {
    const p = Math.max(0, Math.min(100, pct));
    if (progressCard) progressCard.classList.remove('d-none');
    if (progressBar) progressBar.style.width = p + '%';
    if (progressPct) progressPct.textContent = p + '%';
    if (progressLabel) progressLabel.textContent = label || 'Working…';
  }

  function startFakeProgress() {
    let p = 5;
    const steps = [
      [15, 'Dumping MySQL database…'],
      [35, 'Compressing ZIP archive…'],
      [55, 'Saving local backup…'],
      [75, 'Uploading to Google Drive…'],
      [88, 'Applying retention policy…'],
      [92, 'Finalizing…'],
    ];
    let i = 0;
    setProgress(p, 'Starting backup…');
    progressTimer = window.setInterval(function () {
      if (i < steps.length) {
        setProgress(steps[i][0], steps[i][1]);
        i += 1;
      } else if (p < 95) {
        p += 1;
        setProgress(p, 'Almost done…');
      }
    }, 900);
  }

  function stopProgress(finalPct, label) {
    if (progressTimer) {
      window.clearInterval(progressTimer);
      progressTimer = null;
    }
    setProgress(finalPct, label);
    window.setTimeout(function () {
      if (progressCard && finalPct >= 100) {
        progressCard.classList.add('d-none');
      }
    }, 1200);
  }

  function formatBytes(bytes) {
    bytes = Number(bytes || 0);
    if (bytes < 1024) return bytes + ' B';
    const kb = bytes / 1024;
    if (kb < 1024) return kb.toFixed(1) + ' KB';
    return (kb / 1024).toFixed(2) + ' MB';
  }

  function statusBadge(status) {
    const map = {
      SUCCESS: 'success',
      PARTIAL: 'warning',
      FAILED: 'danger',
      RUNNING: 'info',
      PENDING: 'secondary',
    };
    const cls = map[status] || 'secondary';
    return '<span class="badge text-bg-' + cls + '">' + escapeHtml(status || '') + '</span>';
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function renderHistory(rows) {
    if (!historyBody) return;
    if (!rows || !rows.length) {
      historyBody.innerHTML =
        '<tr class="backup-empty-row"><td colspan="9" class="text-center text-muted py-4">No backups yet.</td></tr>';
      return;
    }
    historyBody.innerHTML = rows
      .map(function (row) {
        const created = new Date(String(row.created_at || '').replace(' ', 'T'));
        const valid = !isNaN(created.getTime());
        const date = valid ? created.toISOString().slice(0, 10) : '—';
        const time = valid
          ? String(row.created_at).slice(11, 19)
          : '—';
        const size = formatBytes(row.size_bytes);
        const dur = row.duration_seconds != null ? row.duration_seconds + 's' : '—';
        const id = Number(row.id);
        const canRetryDrive =
          ['FAILED', 'RETRY', 'PENDING', 'SKIPPED'].indexOf(String(row.google_drive_status || '')) >= 0 &&
          ['SUCCESS', 'PARTIAL'].indexOf(String(row.status || '')) >= 0 &&
          !row.google_drive_file_id;
        return (
          '<tr data-id="' +
          id +
          '">' +
          '<td>' +
          escapeHtml(date) +
          '</td>' +
          '<td>' +
          escapeHtml(time) +
          '</td>' +
          '<td class="text-wrap"><code class="small">' +
          escapeHtml(row.filename || '') +
          '</code></td>' +
          '<td class="text-end">' +
          escapeHtml(size) +
          '</td>' +
          '<td class="text-end">' +
          escapeHtml(dur) +
          '</td>' +
          '<td>' +
          escapeHtml(row.destination || 'LOCAL') +
          '</td>' +
          '<td>' +
          statusBadge(row.status) +
          '</td>' +
          '<td>' +
          escapeHtml(row.created_by_name || '—') +
          '</td>' +
          '<td class="text-end text-nowrap"><div class="btn-group btn-group-sm">' +
          '<a class="btn btn-outline-secondary" title="Download" href="' +
          escapeHtml(downloadBase + '&id=' + id) +
          '"><i class="bi bi-download"></i></a>' +
          (canRetryDrive
            ? '<button type="button" class="btn btn-outline-info btn-retry-upload" title="Retry Google Drive Upload" data-id="' +
              id +
              '"><i class="bi bi-cloud-arrow-up"></i></button>'
            : '') +
          '<button type="button" class="btn btn-outline-primary btn-restore" title="Restore" data-id="' +
          id +
          '"><i class="bi bi-arrow-counterclockwise"></i></button>' +
          '<button type="button" class="btn btn-outline-secondary btn-log" title="View Log" data-id="' +
          id +
          '"><i class="bi bi-journal-text"></i></button>' +
          '<button type="button" class="btn btn-outline-danger btn-delete" title="Delete" data-id="' +
          id +
          '"><i class="bi bi-trash"></i></button>' +
          '</div></td></tr>'
        );
      })
      .join('');
  }

  function applyStats(stats) {
    if (!stats) return;
    const last = stats.last_backup || null;
    const setText = function (id, value) {
      const el = document.getElementById(id);
      if (el) el.textContent = value;
    };
    setText('kpiLastBackup', last ? last.completed_at || last.created_at || '—' : 'Never');
    setText('kpiLastFile', last ? last.filename || '—' : '—');
    setText('kpiNext', stats.next_scheduled || '—');
    setText('kpiStatus', stats.backup_status || 'NONE');
    setText(
      'kpiDuration',
      last && last.duration_seconds != null ? last.duration_seconds + 's' : '—'
    );
    setText('kpiTotal', String(stats.total_backups != null ? stats.total_backups : '0'));
    setText('kpiSuccess', String(stats.successful_backups != null ? stats.successful_backups : '0'));
    setText('kpiDrive', (stats.google_drive && stats.google_drive.status) || '—');
    setText('kpiSync', stats.google_drive_sync || '—');
    setText('kpiLocal', stats.local_storage_human || '0 B');
    setText('kpiSize', last ? formatBytes(last.size_bytes) : '—');

    const connected = !!(stats.google_drive_connected || (stats.google_drive && stats.google_drive.connected));
    setText('driveConnectedLabel', connected ? 'Yes' : 'No');
    setText('driveFolderName', stats.google_drive_folder_name || (stats.google_drive && stats.google_drive.folder) || 'TMS Database Backups');
    setText('driveFolderId', stats.google_drive_folder_id || (stats.google_drive && stats.google_drive.folder_id) || '—');
    const lastUp = stats.last_drive_upload || null;
    setText(
      'driveLastUpload',
      lastUp ? lastUp.google_drive_uploaded_at || lastUp.completed_at || '—' : '—'
    );
    setText(
      'driveLastStatus',
      'Upload Status: ' +
        (lastUp
          ? lastUp.google_drive_status || '—'
          : last
            ? last.google_drive_status || 'NONE'
            : 'NONE')
    );
    const link = (lastUp && lastUp.google_drive_link) || (last && last.google_drive_link) || '';
    const linkEl = document.getElementById('driveLastLink');
    const linkWrap = document.getElementById('driveLastLinkWrap');
    if (linkEl && link) {
      linkEl.href = link;
      linkEl.textContent = link;
      if (linkWrap) linkWrap.classList.remove('d-none');
    }
    const reason = document.getElementById('driveSetupReason');
    if (reason && stats.google_drive && stats.google_drive.error) {
      reason.textContent = stats.google_drive.error;
    }
  }

  async function postForm(action, fields) {
    const body = new URLSearchParams();
    body.set('csrf_token', csrf);
    Object.keys(fields || {}).forEach(function (k) {
      body.set(k, fields[k]);
    });
    const res = await fetch(apiBase + '&action=' + encodeURIComponent(action), {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: body.toString(),
      credentials: 'same-origin',
    });
    const data = await res.json().catch(function () {
      return { ok: false, error: 'Invalid server response' };
    });
    return { res: res, data: data };
  }

  async function getJson(action, query) {
    let url = apiBase + '&action=' + encodeURIComponent(action);
    if (query) {
      Object.keys(query).forEach(function (k) {
        url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(query[k]);
      });
    }
    const res = await fetch(url, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });
    return res.json();
  }

  async function refreshDashboard() {
    const data = await getJson('dashboard');
    if (data && data.ok) {
      applyStats(data.stats);
      renderHistory(data.history || []);
    }
  }

  if (btnBackupNow) {
    btnBackupNow.addEventListener('click', async function () {
      btnBackupNow.disabled = true;
      startFakeProgress();
      try {
        const result = await postForm('backup_now', {});
        if (result.data && result.data.ok) {
          stopProgress(100, 'Backup completed');
          showAlert('success', result.data.message || 'Backup completed successfully.');
          applyStats(result.data.stats);
          renderHistory(result.data.history || []);
        } else {
          stopProgress(100, 'Backup failed');
          showAlert('danger', (result.data && result.data.error) || 'Backup failed.');
          await refreshDashboard();
        }
      } catch (e) {
        stopProgress(100, 'Backup failed');
        showAlert('danger', e.message || 'Network error while creating backup.');
      } finally {
        btnBackupNow.disabled = false;
      }
    });
  }

  const btnRefresh = document.getElementById('btnRefreshHistory');
  if (btnRefresh) {
    btnRefresh.addEventListener('click', function () {
      refreshDashboard().catch(function () {});
    });
  }

  // Restore / delete / log delegation
  document.addEventListener('click', function (ev) {
    const restoreBtn = ev.target.closest('.btn-restore');
    const deleteBtn = ev.target.closest('.btn-delete');
    const logBtn = ev.target.closest('.btn-log');
    const retryBtn = ev.target.closest('.btn-retry-upload');

    if (retryBtn) {
      const id = retryBtn.getAttribute('data-id');
      postForm('retry_upload', { id: id })
        .then(function (result) {
          if (result.data && result.data.ok) {
            showAlert('success', result.data.message || 'Uploaded to Google Drive.');
            applyStats(result.data.stats);
            renderHistory(result.data.history || []);
          } else {
            showAlert('danger', (result.data && result.data.error) || 'Retry upload failed.');
          }
        })
        .catch(function (e) {
          showAlert('danger', e.message || 'Retry upload failed.');
        });
      return;
    }

    if (restoreBtn) {
      const id = restoreBtn.getAttribute('data-id');
      document.getElementById('restoreBackupId').value = id;
      document.getElementById('restoreConfirmInput').value = '';
      document.getElementById('btnConfirmRestore').disabled = true;
      document.getElementById('restoreError').classList.add('d-none');
      const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('backupRestoreModal'));
      modal.show();
    }

    if (deleteBtn) {
      const id = deleteBtn.getAttribute('data-id');
      if (!window.confirm('Delete this backup from local storage, Google Drive, and audit logs?')) {
        return;
      }
      postForm('delete', { id: id })
        .then(function (result) {
          if (result.data && result.data.ok) {
            showAlert('success', result.data.message || 'Backup deleted.');
            applyStats(result.data.stats);
            renderHistory(result.data.history || []);
          } else {
            showAlert('danger', (result.data && result.data.error) || 'Delete failed.');
          }
        })
        .catch(function (e) {
          showAlert('danger', e.message || 'Delete failed.');
        });
    }

    if (logBtn) {
      const id = logBtn.getAttribute('data-id');
      const body = document.getElementById('backupLogBody');
      body.textContent = 'Loading…';
      const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('backupLogModal'));
      modal.show();
      getJson('log', { id: id }).then(function (data) {
        if (!data || !data.ok) {
          body.textContent = (data && data.error) || 'Unable to load log.';
          return;
        }
        const lines = [];
        lines.push('ID: ' + data.backup.id);
        lines.push('File: ' + data.backup.filename);
        lines.push('Status: ' + data.backup.status);
        lines.push('Drive: ' + (data.backup.google_drive_status || '—'));
        if (data.backup.error_message) lines.push('Error: ' + data.backup.error_message);
        lines.push('');
        (data.log || []).forEach(function (entry) {
          lines.push(
            '[' +
              (entry.at || '') +
              '] ' +
              (entry.message || '') +
              (entry.context ? ' ' + JSON.stringify(entry.context) : '')
          );
        });
        body.textContent = lines.join('\n');
      });
    }
  });

  const btnRetryAll = document.getElementById('btnRetryDriveUploads');
  if (btnRetryAll) {
    btnRetryAll.addEventListener('click', function () {
      btnRetryAll.disabled = true;
      postForm('retry_upload', {})
        .then(function (result) {
          if (result.data && result.data.ok) {
            showAlert('success', result.data.message || 'Pending uploads retried.');
            applyStats(result.data.stats);
            renderHistory(result.data.history || []);
          } else {
            showAlert('danger', (result.data && result.data.error) || 'Retry failed. Complete Google Drive setup first.');
            if (result.data && result.data.stats) applyStats(result.data.stats);
          }
        })
        .catch(function (e) {
          showAlert('danger', e.message || 'Retry failed.');
        })
        .finally(function () {
          btnRetryAll.disabled = false;
        });
    });
  }

  const btnRefreshDrive = document.getElementById('btnRefreshDriveStatus');
  if (btnRefreshDrive) {
    btnRefreshDrive.addEventListener('click', function () {
      refreshDashboard().catch(function () {});
    });
  }

  const restoreInput = document.getElementById('restoreConfirmInput');
  const btnConfirmRestore = document.getElementById('btnConfirmRestore');
  if (restoreInput && btnConfirmRestore) {
    restoreInput.addEventListener('input', function () {
      btnConfirmRestore.disabled = restoreInput.value.trim() !== 'RESTORE';
    });
    btnConfirmRestore.addEventListener('click', async function () {
      const id = document.getElementById('restoreBackupId').value;
      const err = document.getElementById('restoreError');
      btnConfirmRestore.disabled = true;
      err.classList.add('d-none');
      try {
        const result = await postForm('restore', {
          id: id,
          confirm: restoreInput.value.trim(),
        });
        if (result.data && result.data.ok) {
          bootstrap.Modal.getOrCreateInstance(document.getElementById('backupRestoreModal')).hide();
          showAlert('success', result.data.message || 'Restore completed.');
          await refreshDashboard();
        } else {
          err.textContent = (result.data && result.data.error) || 'Restore failed.';
          err.classList.remove('d-none');
          btnConfirmRestore.disabled = false;
        }
      } catch (e) {
        err.textContent = e.message || 'Restore failed.';
        err.classList.remove('d-none');
        btnConfirmRestore.disabled = false;
      }
    });
  }
})();
