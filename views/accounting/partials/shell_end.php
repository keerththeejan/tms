    </div>
  </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3 acc-toast-host" id="accToastHost"></div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
window.TMS_ACCOUNTING = {
  baseUrl: <?php echo json_encode($accBaseUrl, JSON_UNESCAPED_SLASHES); ?>,
  csrf: <?php echo json_encode($accCsrf, JSON_UNESCAPED_UNICODE); ?>,
  action: <?php echo json_encode($accAction, JSON_UNESCAPED_UNICODE); ?>
};
</script>
<script src="<?php echo Helpers::baseUrl('assets/js/accounting-module.js?v=' . rawurlencode($accJsVer ?? '1')); ?>"></script>
