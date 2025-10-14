<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Simple standalone email form using global Mailer
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$error = '';
$success = '';
// Load current mail config (if available) for display and optional overrides
$currentCfg = [];
$mailCfgPath = __DIR__ . '/../config/mail.php';
if (file_exists($mailCfgPath)) { $currentCfg = require $mailCfgPath; }

// Default values
$to = 'yathunila2001@gmail.com';
$toName = 'TMS Recipient';
$subject = 'TMS Test Email';
$message = "Hello,\nThis is a test email from TMS.";
// Build initial transport info summary (without password)
$usingSmtp = (bool)($currentCfg['use_smtp'] ?? false);
$transportInfo = ($usingSmtp ? 'SMTP' : 'PHP mail')
  . ($usingSmtp ? (' | Host: ' . (string)($currentCfg['host'] ?? '')
  . ' | Port: ' . (string)($currentCfg['port'] ?? '')
  . ' | Enc: ' . (string)($currentCfg['encryption'] ?? '')) : '');
if (!empty($currentCfg['from_email'])) { $transportInfo .= ' | From: ' . (string)$currentCfg['from_email']; }

if ($method === 'POST') {
    // CSRF check
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $to = trim((string)($_POST['to'] ?? $to));
        $toName = trim((string)($_POST['to_name'] ?? $toName));
        $subject = trim((string)($_POST['subject'] ?? $subject));
        $message = (string)($_POST['message'] ?? $message);
        // Optional overrides
        $useOverride = isset($_POST['use_override']) && $_POST['use_override'] === '1';
        $activeMailer = $GLOBALS['mailer'] ?? null;
        if ($useOverride) {
            $override = $currentCfg;
            $override['use_smtp'] = isset($_POST['ov_use_smtp']) ? true : false;
            $override['host'] = trim((string)($_POST['ov_host'] ?? ($override['host'] ?? '')));
            $override['port'] = (int)($_POST['ov_port'] ?? ($override['port'] ?? 0));
            $enc = strtolower(trim((string)($_POST['ov_encryption'] ?? ($override['encryption'] ?? ''))));
            if (!in_array($enc, ['tls','ssl',''], true)) { $enc = 'tls'; }
            $override['encryption'] = $enc === '' ? null : $enc;
            $u = trim((string)($_POST['ov_username'] ?? ''));
            if ($u !== '') { $override['username'] = $u; }
            $p = (string)($_POST['ov_password'] ?? '');
            if ($p !== '') { $override['password'] = $p; }
            $fe = trim((string)($_POST['ov_from_email'] ?? ''));
            if ($fe !== '') { $override['from_email'] = $fe; }
            $fn = trim((string)($_POST['ov_from_name'] ?? ''));
            if ($fn !== '') { $override['from_name'] = $fn; }
            $rt = trim((string)($_POST['ov_reply_to'] ?? ''));
            if ($rt !== '') { $override['reply_to'] = $rt; }
            $activeMailer = new Mailer($override);
            $usingSmtp = (bool)($override['use_smtp'] ?? false);
            $transportInfo = ($usingSmtp ? 'SMTP' : 'PHP mail')
              . ($usingSmtp ? (' | Host: ' . (string)($override['host'] ?? '')
              . ' | Port: ' . (string)($override['port'] ?? '')
              . ' | Enc: ' . (string)($override['encryption'] ?? '')) : '');
            if (!empty($override['from_email'])) { $transportInfo .= ' | From: ' . (string)$override['from_email']; }
        }
        if ($to === '' || $subject === '' || $message === '') {
            $error = 'To, Subject and Message are required.';
        } else {
            // HTML body with simple nl2br
            $htmlBody = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
            $ok = false;
            if ($activeMailer instanceof Mailer) {
                try {
                    $ok = $activeMailer->send($to, ($toName !== '' ? $toName : $to), $subject, $htmlBody, $message);
                } catch (Throwable $e) {
                    $ok = false;
                }
            }
            if ($ok) {
                $success = 'Email sent successfully to ' . htmlspecialchars($to);
            } else {
                $detail = '';
                $candidate = $activeMailer instanceof Mailer ? $activeMailer : ($GLOBALS['mailer'] ?? null);
                if ($candidate && method_exists($candidate, 'getLastError')) { $detail = trim((string)$candidate->getLastError()); }
                if ($detail !== '') {
                    $error = 'Failed to send email. Reason: ' . htmlspecialchars($detail) . ' (Check config/mail.php)';
                } else {
                    $error = 'Failed to send email. Please verify SMTP settings in config/mail.php.';
                }
            }
        }
    }
}

// Basic HTML (no dependency on views) for easy drop-in
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TMS Email Sender</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="mb-3">Send Email (Gmail SMTP supported)</h3>
  <p class="text-muted">Configure SMTP in <code>config/mail.php</code>. For Gmail, enable App Passwords and set <code>use_smtp=true</code>, host <code>smtp.gmail.com</code>, port <code>587</code>, encryption <code>tls</code>.</p>
  <div class="alert alert-info py-2">Using: <?php echo htmlspecialchars($transportInfo); ?></div>

  <?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php elseif ($success !== ''): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <form method="post" class="card p-3 shadow-sm bg-white">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Helpers::csrfToken()); ?>">

    <div class="mb-3">
      <label class="form-label">To Email</label>
      <input type="email" name="to" class="form-control" value="<?php echo htmlspecialchars($to); ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">To Name</label>
      <input type="text" name="to_name" class="form-control" value="<?php echo htmlspecialchars($toName); ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Subject</label>
      <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($subject); ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Message</label>
      <textarea name="message" rows="8" class="form-control" required><?php echo htmlspecialchars($message); ?></textarea>
    </div>

    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary">Send Email</button>
      <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars(Helpers::baseUrl('index.php?page=dashboard')); ?>">Back to App</a>
    </div>
  </form>

  <div class="card p-3 mt-3">
    <div class="form-check mb-2">
      <input class="form-check-input" type="checkbox" id="use_override" name="use_override" value="1" form="ovForm">
      <label class="form-check-label" for="use_override">Use override SMTP settings for this test</label>
    </div>
    <form id="ovForm" method="post" class="row g-2">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Helpers::csrfToken()); ?>">
      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="ov_use_smtp" name="ov_use_smtp" <?php echo $usingSmtp?'checked':''; ?>>
          <label class="form-check-label" for="ov_use_smtp">Use SMTP</label>
        </div>
      </div>
      <div class="col-md-4"><input type="text" class="form-control" name="ov_host" placeholder="Host" value="<?php echo htmlspecialchars((string)($currentCfg['host'] ?? 'smtp.gmail.com')); ?>"></div>
      <div class="col-md-2"><input type="number" class="form-control" name="ov_port" placeholder="Port" value="<?php echo htmlspecialchars((string)($currentCfg['port'] ?? '587')); ?>"></div>
      <div class="col-md-2">
        <select class="form-select" name="ov_encryption">
          <?php $encSel = strtolower((string)($currentCfg['encryption'] ?? 'tls')); ?>
          <option value="tls" <?php echo $encSel==='tls'?'selected':''; ?>>tls</option>
          <option value="ssl" <?php echo $encSel==='ssl'?'selected':''; ?>>ssl</option>
          <option value="" <?php echo ($encSel===''||$encSel==='none')?'selected':''; ?>>none</option>
        </select>
      </div>
      <div class="col-md-4"><input type="email" class="form-control" name="ov_from_email" placeholder="From email" value="<?php echo htmlspecialchars((string)($currentCfg['from_email'] ?? '')); ?>"></div>
      <div class="col-md-4"><input type="text" class="form-control" name="ov_from_name" placeholder="From name" value="<?php echo htmlspecialchars((string)($currentCfg['from_name'] ?? 'TMS')); ?>"></div>
      <div class="col-md-4"><input type="email" class="form-control" name="ov_reply_to" placeholder="Reply-To (optional)" value="<?php echo htmlspecialchars((string)($currentCfg['reply_to'] ?? '')); ?>"></div>
      <div class="col-md-6"><input type="email" class="form-control" name="ov_username" placeholder="SMTP username (email)" value="<?php echo htmlspecialchars((string)($currentCfg['username'] ?? '')); ?>"></div>
      <div class="col-md-6"><input type="password" class="form-control" name="ov_password" placeholder="SMTP password (App Password)"></div>
      <div class="col-12 d-flex gap-2 mt-2">
        <button type="submit" class="btn btn-secondary">Send using overrides</button>
        <a target="_blank" rel="noreferrer" class="btn btn-outline-info" href="https://accounts.google.com/DisplayUnlockCaptcha">Google Unlock Captcha</a>
      </div>
      <input type="hidden" name="to" value="<?php echo htmlspecialchars($to); ?>">
      <input type="hidden" name="to_name" value="<?php echo htmlspecialchars($toName); ?>">
      <input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
      <input type="hidden" name="message" value="<?php echo htmlspecialchars($message); ?>">
    </form>
  </div>

</div>
</body>
</html>
