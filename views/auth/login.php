<div class="row justify-content-center">
  <div class="col-12 col-sm-10 col-md-6 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title mb-3">Login</h5>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
          <div class="alert alert-info py-2">
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
            <input type="text" class="form-control" id="username" name="username" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
      </div>
    </div>
  </div>
</div>
