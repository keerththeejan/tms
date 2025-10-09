</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
  (function(){
    if (typeof DataTable !== 'undefined') {
      document.querySelectorAll('table.datatable').forEach(function(tbl){
        // Ensure responsive container
        if (!tbl.closest('.table-responsive')) {
          var wrap = document.createElement('div');
          wrap.className = 'table-responsive';
          tbl.parentNode.insertBefore(wrap, tbl);
          wrap.appendChild(tbl);
        }
        // Ensure Bootstrap table classes for better mobile readability
        tbl.classList.add('table','table-striped','table-hover','align-middle');
        // Initialize DataTable with horizontal scroll support
        new DataTable(tbl, { paging: true, searching: true, order: [], scrollX: true });
      });
    }
  })();
</script>
<script>
  (function(){
    var body = document.body;
    var openBtn = document.querySelector('[data-role="sidebar-open"]');
    var overlay = document.querySelector('[data-role="sidebar-overlay"]');

    function openSidebar(){ body.classList.add('sidebar-open'); }
    function closeSidebar(){ body.classList.remove('sidebar-open'); }

    if (openBtn) openBtn.addEventListener('click', function(e){ e.preventDefault(); openSidebar(); });
    if (overlay) overlay.addEventListener('click', function(){ closeSidebar(); });

    // Close on ESC
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeSidebar(); });

    // Auto-close when switching to large screens
    function handleResize(){ if (window.innerWidth >= 992) { body.classList.remove('sidebar-open'); } }
    window.addEventListener('resize', handleResize);
  })();
</script>
<script>
  // Enhance all Bootstrap selects with search using Choices.js
  (function(){
    if (typeof Choices === 'undefined') return;
    // Avoid double-initialization
    document.querySelectorAll('select.form-select').forEach(function(sel){
      if (sel.dataset.enhanced === '1') return;
      // Skip if developer opted out
      if (sel.dataset.enhance === 'false') return;
      var optionCount = sel.options ? sel.options.length : 0;
      var searchEnabled = optionCount >= 5; // auto-disable search for very small lists
      var cfg = {
        searchEnabled: searchEnabled,
        searchResultLimit: 50,
        shouldSort: true,
        itemSelectText: '',
        allowHTML: false,
        removeItemButton: sel.multiple === true
      };
      // Keep placeholder at top for supplier select by disabling sort
      if ((sel.getAttribute('name')||'').toLowerCase() === 'supplier_id') {
        cfg.shouldSort = false;
      }
      var instance = new Choices(sel, cfg);
      sel.dataset.enhanced = '1';
      // Hide placeholder option in dropdown for Supplier select so '-- None --' won't appear in the middle
      try {
        if ((sel.getAttribute('name')||'').toLowerCase() === 'supplier_id') {
          var container = sel.closest('.choices');
          var toHide = container && (container.querySelector('.choices__list--dropdown [data-value="0"]') || container.querySelector('.choices__list--dropdown [data-value=""]'));
          if (toHide) { toHide.style.display = 'none'; }
        }
      } catch (e) { /* ignore */ }
    });
  })();
</script>
<script>
  // Make non-DataTables tables responsive as well
  (function(){
    document.querySelectorAll('table:not(.datatable)').forEach(function(tbl){
      if (!tbl.closest('.table-responsive')) {
        var wrap = document.createElement('div');
        wrap.className = 'table-responsive';
        tbl.parentNode.insertBefore(wrap, tbl);
        wrap.appendChild(tbl);
      }
      tbl.classList.add('table','table-striped','align-middle');
    });
  })();
</script>
</body>
</html>
