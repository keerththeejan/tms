</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script>
  (function(){
    if (typeof DataTable !== 'undefined') {
      document.querySelectorAll('table.datatable').forEach(function(tbl){
        new DataTable(tbl, { paging: true, searching: true, order: [] });
      });
    }
  })();
</script>
</body>
</html>
