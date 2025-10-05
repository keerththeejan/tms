</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
  (function(){
    if (typeof DataTable !== 'undefined') {
      document.querySelectorAll('table.datatable').forEach(function(tbl){
        new DataTable(tbl, { paging: true, searching: true, order: [] });
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
      var instance = new Choices(sel, {
        searchEnabled: searchEnabled,
        searchResultLimit: 50,
        shouldSort: true,
        itemSelectText: '',
        allowHTML: false,
        removeItemButton: sel.multiple === true
      });
      sel.dataset.enhanced = '1';
    });
  })();
</script>
</body>
</html>
