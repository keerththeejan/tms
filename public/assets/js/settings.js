/**
 * Settings page: Bootstrap 5 tab activation from URL (?tab=) or hash (#pane-*).
 */
(function () {
  var allowed = ['general', 'branches', 'users', 'system'];
  var tabList = document.getElementById('settingsMainTabs');
  if (!tabList || typeof bootstrap === 'undefined' || !bootstrap.Tab) return;

  function paneId(name) {
    return 'pane-' + name;
  }

  function activate(name) {
    if (allowed.indexOf(name) === -1) return;
    var trigger = tabList.querySelector('[data-bs-target="#' + paneId(name) + '"]');
    if (!trigger) return;
    try {
      bootstrap.Tab.getOrCreateInstance(trigger).show();
    } catch (e) {
      trigger.click();
    }
  }

  function readTabFromLocation() {
    var params = new URLSearchParams(window.location.search);
    var q = (params.get('tab') || '').toLowerCase();
    if (allowed.indexOf(q) !== -1) return q;
    var h = (window.location.hash || '').replace(/^#/, '');
    if (h === 'settings-operational-branches' || h === 'settings-branch-crud') return 'branches';
    if (h.indexOf('pane-') === 0) {
      var n = h.slice('pane-'.length);
      if (allowed.indexOf(n) !== -1) return n;
    }
    return null;
  }

  var fromLoc = readTabFromLocation();
  if (fromLoc) {
    activate(fromLoc);
  }

  tabList.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (btn) {
    btn.addEventListener('shown.bs.tab', function (e) {
      var target = e.target && e.target.getAttribute('data-bs-target');
      if (!target || target.indexOf('#pane-') !== 0) return;
      var name = target.slice('#pane-'.length);
      if (allowed.indexOf(name) === -1) return;
      try {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', name);
        window.history.replaceState(null, '', url.pathname + '?' + url.searchParams.toString() + '#pane-' + name);
      } catch (err) {
        /* ignore */
      }
    });
  });
})();
