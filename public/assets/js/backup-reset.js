(function () {
  'use strict';

  var modalEl = document.getElementById('dbResetModal');
  var progressEl = document.getElementById('dbResetProgress');
  var successEl = document.getElementById('dbResetSuccess');
  var confirmInput = document.getElementById('dbResetConfirmInput');
  var deleteBtn = document.getElementById('dbResetDeleteBtn');
  var openBtn = document.getElementById('openDbResetModalBtn');

  if (!modalEl || !confirmInput || !deleteBtn) {
    return;
  }

  var steps = [
    'Preparing...',
    'Deleting Records...',
    'Resetting Accounting...',
    'Resetting Auto Increment...',
    'Clearing Cache...',
    'Logging Out...'
  ];

  var stepTimer = null;
  var currentStep = 0;

  function setDeleteEnabled() {
    deleteBtn.disabled = confirmInput.value !== 'DELETE';
  }

  function showProgress() {
    if (progressEl) {
      progressEl.classList.remove('d-none');
    }
    if (successEl) {
      successEl.classList.add('d-none');
    }
    var closeBtn = document.getElementById('dbResetModalClose');
    var cancelBtn = document.getElementById('dbResetCancelBtn');
    if (closeBtn) {
      closeBtn.disabled = true;
      closeBtn.classList.add('d-none');
    }
    if (cancelBtn) {
      cancelBtn.disabled = true;
    }
    if (modalEl && window.bootstrap && window.bootstrap.Modal) {
      var instance = window.bootstrap.Modal.getInstance(modalEl);
      if (instance) {
        modalEl.setAttribute('data-bs-keyboard', 'false');
      }
    }
    currentStep = 0;
    updateProgressLabel();
    stepTimer = window.setInterval(function () {
      if (currentStep < steps.length - 1) {
        currentStep += 1;
        updateProgressLabel();
      }
    }, 900);
  }

  function resetModalControls() {
    var closeBtn = document.getElementById('dbResetModalClose');
    var cancelBtn = document.getElementById('dbResetCancelBtn');
    if (closeBtn) {
      closeBtn.disabled = false;
      closeBtn.classList.remove('d-none');
    }
    if (cancelBtn) {
      cancelBtn.disabled = false;
    }
  }

  function stopProgress() {
    if (stepTimer !== null) {
      window.clearInterval(stepTimer);
      stepTimer = null;
    }
  }

  function updateProgressLabel() {
    var label = document.getElementById('dbResetProgressLabel');
    if (label) {
      label.textContent = steps[currentStep] || steps[steps.length - 1];
    }
  }

  function showSuccess() {
    stopProgress();
    if (progressEl) {
      progressEl.classList.add('d-none');
    }
    if (successEl) {
      successEl.classList.remove('d-none');
    }
    var label = document.getElementById('dbResetProgressLabel');
    if (label) {
      label.textContent = 'Logging Out...';
    }
  }

  function showError(message) {
    stopProgress();
    resetModalControls();
    if (progressEl) {
      progressEl.classList.add('d-none');
    }
    var err = document.getElementById('dbResetError');
    if (err) {
      err.textContent = message;
      err.classList.remove('d-none');
    }
    deleteBtn.disabled = false;
  }

  confirmInput.addEventListener('input', setDeleteEnabled);
  setDeleteEnabled();

  if (openBtn) {
    openBtn.addEventListener('click', function () {
      confirmInput.value = '';
      setDeleteEnabled();
      resetModalControls();
      var err = document.getElementById('dbResetError');
      if (err) {
        err.classList.add('d-none');
        err.textContent = '';
      }
      if (progressEl) {
        progressEl.classList.add('d-none');
      }
      if (successEl) {
        successEl.classList.add('d-none');
      }
    });
  }

  deleteBtn.addEventListener('click', function () {
    if (confirmInput.value !== 'DELETE') {
      return;
    }

    var form = document.getElementById('dbResetForm');
    if (!form) {
      return;
    }

    var csrf = form.querySelector('input[name="csrf_token"]');
    var err = document.getElementById('dbResetError');
    if (err) {
      err.classList.add('d-none');
      err.textContent = '';
    }

    deleteBtn.disabled = true;
    showProgress();

    var body = new FormData();
    body.append('csrf_token', csrf ? csrf.value : '');
    body.append('confirm_reset', 'DELETE');

    fetch(form.getAttribute('action'), {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: body,
      credentials: 'same-origin'
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { ok: res.ok, data: data };
        });
      })
      .then(function (result) {
        if (!result.ok || !result.data || !result.data.success) {
          var msg = (result.data && result.data.message)
            ? result.data.message
            : 'Database reset failed. Please try again or check server logs.';
          if (result.data && result.data.errors && result.data.errors.length) {
            msg += ' ' + result.data.errors.join('; ');
          }
          showError(msg);
          return;
        }

        showSuccess();
        window.setTimeout(function () {
          window.location.href = result.data.redirect || (window.TMS_BACKUP_RESET && window.TMS_BACKUP_RESET.loginUrl) || 'index.php?page=login&reset=1';
        }, 2800);
      })
      .catch(function () {
        showError('Network error during database reset. Please check your connection and try again.');
      });
  });
})();
