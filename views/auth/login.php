<style>
  .login-page {
    min-height: calc(100vh - 2rem);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem 0;
    padding-left: max(1rem, env(safe-area-inset-left));
    padding-right: max(1rem, env(safe-area-inset-right));
    padding-bottom: max(1rem, env(safe-area-inset-bottom));
    box-sizing: border-box;
  }
  .login-page .login-card { width: 100%; max-width: 400px; }
  .login-page .card-body { padding: 1.5rem; }
  .login-page .form-control { min-height: 2.75rem; font-size: 1rem; }
  .login-page .btn-login { min-height: 2.75rem; font-size: 1rem; padding: 0.5rem 1rem; }
  .login-page .alert { word-wrap: break-word; }
  @media (max-width: 576px) {
    .login-page {
      min-height: calc(100vh - 1rem);
      padding: 0.75rem;
      padding-left: max(0.75rem, env(safe-area-inset-left));
      padding-right: max(0.75rem, env(safe-area-inset-right));
      align-items: flex-start;
      padding-top: max(0.75rem, min(2rem, 10vh), env(safe-area-inset-top));
    }
    .login-page .card-body { padding: 1.25rem; }
    .login-page .form-control, .login-page .btn-login { min-height: 2.875rem; }
    .login-page .alert br + a { display: inline-block; margin-top: 0.25rem; }
  }
  @media (min-width: 577px) and (max-width: 767px) {
    .login-page { padding: 1.5rem; }
  }
</style>
<div class="login-page">
  <div class="row justify-content-center w-100 g-0">
    <div class="col-12 col-sm-11 col-md-8 col-lg-5 col-xl-4 px-2 px-sm-3">
      <div class="card shadow-sm login-card">
        <div class="card-body">
          <h5 class="card-title mb-3">Login</h5>
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 mb-3"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>
          <?php if (!empty($success)): ?>
            <div class="alert alert-info py-2 mb-3">
              <?php echo htmlspecialchars($success); ?>
              <?php if (!empty($seedUrl)): ?>
                <br><a href="<?php echo htmlspecialchars($seedUrl); ?>">Create admin user (run seed)</a> — then use <strong>admin</strong> / <strong>admin123</strong> to log in.
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <form method="post" action="<?php echo Helpers::baseUrl('index.php?page=login'); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo Helpers::csrfToken(); ?>">
            <div class="mb-3">
              <label for="username" class="form-label">Username</label>
              <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-login">Login</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
