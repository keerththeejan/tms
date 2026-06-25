    </div>
  </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3 acc-toast-host" id="accToastHost"></div>

<script>
window.TMS_ACCOUNTING = {
  baseUrl: <?php echo json_encode($accBaseUrl, JSON_UNESCAPED_SLASHES); ?>,
  csrf: <?php echo json_encode($accCsrf, JSON_UNESCAPED_UNICODE); ?>,
  action: <?php echo json_encode($accAction, JSON_UNESCAPED_UNICODE); ?>
};
<?php if (!empty($accLoadAccountsJs)): ?>
window.TMS_ACCOUNTS_MASTER = {
  openingMode: <?php echo !empty($openingMode) ? 'true' : 'false'; ?>,
  groupsBoot: <?php echo json_encode($accAccountGroupsBoot ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
};
<?php endif; ?>
</script>
