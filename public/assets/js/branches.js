/**
 * Settings → Branches tab: letterhead form validation and default-slot visuals.
 */
(function () {
  var form = document.getElementById('branchLetterheadForm');
  if (!form) return;

  function setErr(input, on) {
    if (!input) return;
    if (on) input.classList.add('field-error');
    else input.classList.remove('field-error');
  }

  function validate() {
    var ok = true;
    var branchNames = form.querySelectorAll('input[name="branch_name[]"]');
    var branchEns = form.querySelectorAll('input[name="branch_address_en[]"]');
    var branchTas = form.querySelectorAll('input[name="branch_address_ta[]"]');
    var branchPhones = form.querySelectorAll('input[name="branch_phones[]"]');

    for (var i = 0; i < branchNames.length; i++) {
      var bn = branchNames[i];
      var be = branchEns[i];
      var bt = branchTas[i];
      var bp = branchPhones[i];
      var bnV = bn ? bn.value.trim() : '';
      var beV = be ? be.value.trim() : '';
      var btV = bt ? bt.value.trim() : '';
      var bpV = bp ? bp.value.trim() : '';
      var hasAny = bnV !== '' || beV !== '' || btV !== '' || bpV !== '';
      var required = i === 0 || hasAny;
      if (required) {
        if (bn && bnV === '') {
          ok = false;
          setErr(bn, true);
        } else setErr(bn, false);
        if (be && beV === '') {
          ok = false;
          setErr(be, true);
        } else setErr(be, false);
      } else {
        if (bn) setErr(bn, false);
        if (be) setErr(be, false);
      }
    }
    return ok;
  }

  function showClientError(msg) {
    var box = document.getElementById('branchLetterheadClientError');
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

  var saveBtn = document.getElementById('saveBranchLetterheadBtn');
  form.addEventListener('submit', function (e) {
    if (!validate()) {
      e.preventDefault();
      e.stopPropagation();
      showClientError('Please fill primary branch and English address; optional slots need name + English if any field is filled.');
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
  });

  form.addEventListener('input', function () {
    validate();
    var box = document.getElementById('branchLetterheadClientError');
    if (box) box.classList.add('d-none');
  });

  var radios = form.querySelectorAll('input[name="default_branch_idx"]');
  function refreshDefault() {
    var selected = 0;
    for (var i = 0; i < radios.length; i++) {
      if (radios[i].checked) {
        selected = parseInt(radios[i].value || '0', 10) || 0;
        break;
      }
    }
    var cards = form.querySelectorAll('.branch-letterhead-card');
    for (var c = 0; c < cards.length; c++) {
      var idx = parseInt(cards[c].getAttribute('data-branch-index') || '0', 10) || 0;
      if (idx === selected) cards[c].classList.add('default');
      else cards[c].classList.remove('default');
    }
  }
  for (var r = 0; r < radios.length; r++) {
    radios[r].addEventListener('change', refreshDefault);
  }
  refreshDefault();
})();
