/**
 * Settings → Branches tab: letterhead form validation (3 fixed branches).
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
    var branchEns = form.querySelectorAll('input[name="branch_address_en[]"]');
    var branchTas = form.querySelectorAll('input[name="branch_address_ta[]"]');
    var branchPhones = form.querySelectorAll('input[name="branch_phones[]"]');

    for (var i = 0; i < 3; i++) {
      var be = branchEns[i];
      var bt = branchTas[i];
      var bp = branchPhones[i];
      var beV = be ? be.value.trim() : '';
      var btV = bt ? bt.value.trim() : '';
      var bpV = bp ? bp.value.trim() : '';
      if (be && beV === '') { ok = false; setErr(be, true); } else setErr(be, false);
      if (bt && btV === '') { ok = false; setErr(bt, true); } else setErr(bt, false);
      if (bp && bpV === '') { ok = false; setErr(bp, true); } else setErr(bp, false);
    }
    return ok;
  }

  function showClientError(msg) {
    var box = document.getElementById('branchLetterheadClientError');
    if (!box) return;
    box.textContent = msg || 'Please fill all three branch addresses and phone numbers.';
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
      try { first.focus(); } catch (e3) { /* ignore */ }
    }
  }

  var saveBtn = document.getElementById('saveBranchLetterheadBtn');
  form.addEventListener('submit', function (e) {
    if (!validate()) {
      e.preventDefault();
      e.stopPropagation();
      showClientError('All three branches require Tamil address, English address, and phone numbers.');
      scrollToFirstError();
      return;
    }
    var errBox = document.getElementById('branchLetterheadClientError');
    if (errBox) errBox.classList.add('d-none');
    if (saveBtn) {
      saveBtn.disabled = true;
      var lbl = saveBtn.querySelector('.save-label');
      var loading = saveBtn.querySelector('.save-loading');
      if (lbl) lbl.classList.add('d-none');
      if (loading) loading.classList.remove('d-none');
    }
  });

  form.querySelectorAll('input[name="default_branch_idx"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      form.querySelectorAll('.branch-letterhead-card').forEach(function (card) {
        card.classList.remove('default');
      });
      var idx = radio.value;
      var card = form.querySelector('.branch-letterhead-card[data-branch-index="' + idx + '"]');
      if (card) card.classList.add('default');
    });
  });
})();
