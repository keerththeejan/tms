/**
 * Settings → General tab: logo drop zone, preview, and form validation.
 */
(function () {
  var form = document.getElementById('companyGeneralForm');
  if (!form) return;

  var baseUrl = (document.getElementById('baseUrlForLogo') && document.getElementById('baseUrlForLogo').value) || '';
  var drop = document.getElementById('logoDrop');
  var fileInput = document.getElementById('logoFileInput');
  var urlInput = document.getElementById('logoUrlInput');
  var mainLogo = document.getElementById('previewMainLogo');
  var placeholder = document.getElementById('previewPlaceholder');
  var removeLogo = document.getElementById('removeLogo');
  var replaceBtn = document.getElementById('logoReplaceBtn');
  var removeBtn = document.getElementById('logoRemoveBtn');

  function showPreview(src) {
    if (mainLogo) {
      mainLogo.src = src || '';
      mainLogo.style.display = src ? '' : 'none';
    }
    if (placeholder) placeholder.style.display = src ? 'none' : '';
  }

  function resolveUrl(v) {
    v = (v || '').trim();
    if (!v) return '';
    return v.indexOf('http') === 0 || v.indexOf('//') === 0 ? v : baseUrl + '/' + v.replace(/^\//, '');
  }

  function updateFromUrl() {
    var v = (urlInput && urlInput.value) || '';
    v = v.trim();
    if (!v) {
      showPreview('');
      return;
    }
    showPreview(resolveUrl(v));
  }

  if (urlInput) {
    urlInput.addEventListener('input', function () {
      if (removeLogo) removeLogo.value = '0';
      updateFromUrl();
    });
  }

  var dirty = false;
  var saveBtn = document.getElementById('saveGeneralBtn');
  function setDirty(on) {
    dirty = !!on;
  }
  form.addEventListener('input', function () {
    setDirty(true);
  });
  window.addEventListener('beforeunload', function (e) {
    if (!dirty) return;
    e.preventDefault();
    e.returnValue = '';
  });

  function pickFile() {
    if (fileInput) fileInput.click();
  }

  if (drop) {
    drop.addEventListener('click', pickFile);
    drop.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        pickFile();
      }
    });
    drop.addEventListener('dragover', function (e) {
      e.preventDefault();
      drop.classList.add('is-dragover');
    });
    drop.addEventListener('dragleave', function () {
      drop.classList.remove('is-dragover');
    });
    drop.addEventListener('drop', function (e) {
      e.preventDefault();
      drop.classList.remove('is-dragover');
      if (!fileInput) return;
      var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
      if (!f || !/^image\//.test(f.type)) return;
      fileInput.files = e.dataTransfer.files;
      if (removeLogo) removeLogo.value = '0';
      var r = new FileReader();
      r.onload = function (ev) {
        showPreview(ev.target.result);
      };
      r.readAsDataURL(f);
    });
  }

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      var f = this.files && this.files[0];
      if (f && /^image\//.test(f.type)) {
        if (removeLogo) removeLogo.value = '0';
        var r = new FileReader();
        r.onload = function (e) {
          showPreview(e.target.result);
        };
        r.readAsDataURL(f);
      }
    });
  }
  if (replaceBtn) replaceBtn.addEventListener('click', pickFile);
  if (removeBtn) {
    removeBtn.addEventListener('click', function () {
      if (removeLogo) removeLogo.value = '1';
      if (urlInput) urlInput.value = '';
      if (fileInput) fileInput.value = '';
      showPreview('');
    });
  }

  function setErr(input, on) {
    if (!input) return;
    if (on) input.classList.add('field-error');
    else input.classList.remove('field-error');
  }

  function validateGeneral() {
    var ok = true;
    var companyName = form.querySelector('input[name="company_name"]');
    if (companyName && companyName.value.trim() === '') {
      ok = false;
      setErr(companyName, true);
      var e1 = form.querySelector('[data-error-for="company_name"]');
      if (e1) e1.style.display = '';
    } else {
      setErr(companyName, false);
      var e1b = form.querySelector('[data-error-for="company_name"]');
      if (e1b) e1b.style.display = 'none';
    }
    return ok;
  }

  function showClientError(msg) {
    var box = document.getElementById('generalClientError');
    if (!box) return;
    box.textContent = msg || 'Please fix validation errors and try again.';
    box.classList.remove('d-none');
  }

  function scrollToFirstError() {
    var first = form.querySelector('.field-error');
    if (!first) return;
    try {
      first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } catch (e) {
      first.scrollIntoView(true);
    }
    try {
      first.focus({ preventScroll: true });
    } catch (e2) {
      try {
        first.focus();
      } catch (e3) { /* ignore */ }
    }
  }

  form.addEventListener('submit', function (e) {
    if (!validateGeneral()) {
      e.preventDefault();
      e.stopPropagation();
      showClientError('Company name is required.');
      scrollToFirstError();
      return;
    }
    if (saveBtn) {
      saveBtn.disabled = true;
      var a = saveBtn.querySelector('.save-label');
      var b = saveBtn.querySelector('.save-loading');
      if (a) a.classList.add('d-none');
      if (b) b.classList.remove('d-none');
    }
    setDirty(false);
  });

  form.addEventListener('input', function () {
    validateGeneral();
  });

  updateFromUrl();
})();
