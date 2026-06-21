<?php if ($user !== null): ?>
</div>
</main>
</div>
<?php else: ?>
</main>
<?php endif; ?>
 <!-- jQuery is required by some DataTables builds and third-party scripts -->
 <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
 <!-- Load DataTables only if jQuery is available, to avoid 'jQuery is not defined' halting other scripts -->
 <script>
 (function(){
   function initDataTables(){
     if (typeof DataTable === 'undefined') return;
     document.querySelectorAll('table.datatable').forEach(function(tbl){
       if (tbl.dataset.dtInit === '1') return;
       var body = tbl.tBodies[0];
       if (body) {
         var colCount = tbl.tHead && tbl.tHead.rows[0] ? tbl.tHead.rows[0].cells.length : 0;
         var badRow = Array.from(body.rows).some(function(tr){
           if (colCount > 0 && tr.cells.length !== colCount) return true;
           return Array.from(tr.cells).some(function(td){ return (td.colSpan || 1) > 1; });
         });
         if (badRow) return;
       }
       if (!tbl.closest('.table-responsive')) {
         var wrap = document.createElement('div');
         wrap.className = 'table-responsive';
         tbl.parentNode.insertBefore(wrap, tbl);
         wrap.appendChild(tbl);
       }
       tbl.classList.add('table','table-striped','table-hover','align-middle');
       var dtOpts = { paging: true, searching: true, order: [], scrollX: tbl.dataset.dtScrollX !== 'false' };
       var actionsCols = parseInt(tbl.dataset.dtActionsCol || '0', 10);
       if (actionsCols > 0) {
         var lastIdx = (tbl.tHead && tbl.tHead.rows[0] ? tbl.tHead.rows[0].cells.length : 0) - 1;
         if (lastIdx >= 0) {
           dtOpts.columnDefs = [{ orderable: false, searchable: false, targets: lastIdx }];
         }
       }
       new DataTable(tbl, dtOpts);
       tbl.dataset.dtInit = '1';
     });
   }
   window.TMS_initDataTables = initDataTables;
   function load(src, cb){ var s=document.createElement('script'); s.src=src; s.async=false; s.onload=function(){ try{ cb&&cb(); }catch(e){} }; document.head.appendChild(s); }
   if (window.jQuery) {
     load('https://cdn.jsdelivr.net/npm/datatables.net@2.1.8/js/dataTables.min.js', function(){
       load('https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.1.8/js/dataTables.bootstrap5.min.js', initDataTables);
     });
   }
 })();
 </script>
<script>
  (function(){
    var body = document.body;
    var openBtns = Array.prototype.slice.call(document.querySelectorAll('[data-role="sidebar-open"]'));
    var overlay = document.querySelector('[data-role="sidebar-overlay"]');
    var sidebar = document.getElementById('sidebar');

    function openSidebar(){
      body.classList.add('sidebar-open');
      openBtns.forEach(function(btn){ btn.setAttribute('aria-expanded', 'true'); });
      if (overlay) overlay.setAttribute('aria-hidden', 'false');
    }
    function closeSidebar(){
      body.classList.remove('sidebar-open');
      openBtns.forEach(function(btn){ btn.setAttribute('aria-expanded', 'false'); });
      if (overlay) overlay.setAttribute('aria-hidden', 'true');
    }

    openBtns.forEach(function(btn){
      btn.addEventListener('click', function(e){ e.preventDefault(); openSidebar(); });
    });
    if (overlay) overlay.addEventListener('click', function(){ closeSidebar(); });
    var closeBtn = document.querySelector('[data-role="sidebar-close"]');
    if (closeBtn) closeBtn.addEventListener('click', function(e){ e.preventDefault(); closeSidebar(); });

    if (sidebar) {
      sidebar.addEventListener('click', function(e){
        var a = e.target && e.target.closest ? e.target.closest('a.nav-link[href]') : null;
        if (!a || window.innerWidth >= 992) return;
        closeSidebar();
      });
    }

    // Close on ESC
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeSidebar(); });

    // Auto-close when switching to large screens
    function handleResize(){
      if (window.innerWidth >= 992) { body.classList.remove('sidebar-open'); }
    }
    window.addEventListener('resize', handleResize);
  })();
</script>
<script src="<?php echo Helpers::baseUrl('assets/js/tms-ui.js?v=1'); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
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
      var searchEnabled = sel.dataset.choicesSearch === 'true' || optionCount >= 5; // force search e.g. delivery route
      var cfg = {
        searchEnabled: searchEnabled,
        searchResultLimit: 50,
        shouldSort: true,
        itemSelectText: '',
        allowHTML: false,
        removeItemButton: sel.multiple === true
      };
      var selName = (sel.getAttribute('name')||'').toLowerCase();
      // Keep placeholder at top for supplier select by disabling sort
      if (selName === 'supplier_id') {
        cfg.shouldSort = false;
      }
      var instance = new Choices(sel, cfg);
      // Expose instance for programmatic updates (e.g., after Quick Add)
      try { sel._choices = instance; } catch(e) { /* ignore */ }
      sel.dataset.enhanced = '1';
      // Hide placeholder option in dropdown for Supplier select so '-- None --' won't appear in the middle
      try {
        if ((sel.getAttribute('name')||'').toLowerCase() === 'supplier_id') {
          var container = sel.closest('.choices');
          var toHide = container && (container.querySelector('.choices__list--dropdown [data-value="0"]') || container.querySelector('.choices__list--dropdown [data-value=""]'));
          if (toHide) { toHide.style.display = 'none'; }
        }
      } catch (e) { /* ignore */ }
      // Listen for custom refresh event to sync newly added <option>s
      sel.addEventListener('refresh-choices', function(){
        try {
          var inst = sel._choices; if (!inst) return;
          // Rebuild from current <option>s
          var choices = Array.from(sel.options).map(function(o){
            return { value: o.value, label: o.textContent, selected: o.selected, disabled: o.disabled };
          });
          inst.setChoices(choices, 'value', 'label', true);
        } catch(e) { /* ignore */ }
      });
    });
  })();
</script>
<script>
  // Make non-DataTables tables responsive as well
  (function(){
    document.querySelectorAll('table:not(.datatable):not(#itemsTable)').forEach(function(tbl){
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
