<?php
declare(strict_types=1);

/**
 * Merges duplicate branch rows into three: Colombo, Kilinochchi (main hub), Mullaitivu.
 * Reassigns "Main Branch" to Kilinochchi, updates FKs, then deletes extras.
 *
 * Open in browser while logged in as admin, review the plan, then submit the form to run.
 */
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/BranchMergeService.php';

$config = Helpers::config();
$allowed = (($config['env'] ?? '') === 'local')
    || (Auth::check() && Auth::hasRole('admin'));
if (!$allowed) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden. Log in as admin or set config env to local.';
    exit;
}

$pdo = Database::pdo();
$csrf = Helpers::csrfToken();
$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_cleanup'])) {
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        $error = 'Invalid CSRF token.';
    } else {
        $result = BranchMergeService::execute($pdo);
    }
}

$preview = BranchMergeService::preview($pdo);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cleanup duplicate branches</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-4">
  <div class="container" style="max-width: 720px;">
    <h1 class="h4 mb-3">Cleanup duplicate branches</h1>
    <p class="text-muted small">Keeps one row each for <strong>Colombo</strong>, <strong>Kilinochchi</strong>, and <strong>Mullaitivu</strong>. Moves all links from duplicates and from <strong>Main Branch</strong> into the correct keeper, then deletes the extra branch rows. Kilinochchi becomes the only <strong>main</strong> hub (codes COL / KIL / MUL).</p>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($result !== null): ?>
      <?php if (!empty($result['ok'])): ?>
        <div class="alert alert-success">
          <ul class="mb-0 small">
            <?php foreach ($result['messages'] ?? [] as $m): ?>
              <li><?php echo htmlspecialchars((string)$m); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <a class="btn btn-primary" href="<?php echo htmlspecialchars(Helpers::baseUrl('index.php?page=settings&tab=branches#pane-branches')); ?>">Back to Settings</a>
      <?php else: ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars((string)($result['error'] ?? 'Failed')); ?></div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (($result === null || empty($result['ok'])) && !empty($preview['ok'])): ?>
      <div class="card shadow-sm mb-3">
        <div class="card-header fw-semibold">Planned actions</div>
        <div class="card-body small">
          <ul class="mb-0">
            <?php foreach ($preview['messages'] ?? [] as $m): ?>
              <li><?php echo htmlspecialchars((string)$m); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <form method="post" class="d-flex flex-wrap gap-2 align-items-center" onsubmit="return confirm('Run cleanup? This updates many database rows.');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
        <button type="submit" name="run_cleanup" value="1" class="btn btn-danger">Run cleanup now</button>
        <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars(Helpers::baseUrl('index.php?page=settings')); ?>">Cancel</a>
      </form>
    <?php elseif ($result === null || empty($result['ok'])): ?>
      <div class="alert alert-warning"><?php echo htmlspecialchars((string)($preview['error'] ?? 'Cannot build plan')); ?></div>
    <?php endif; ?>
  </div>
</body>
</html>
