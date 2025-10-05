<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Simple standalone email form using global Mailer
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$error = '';
$success = '';

// Default values
$to = 'yathunila2001@gmail.com';
$toName = 'TMS Recipient';
$subject = 'TMS Test Email';
$message = "Hello,\nThis is a test email from TMS.";

if ($method === 'POST') {
    // CSRF check
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $to = trim((string)($_POST['to'] ?? $to));
        $toName = trim((string)($_POST['to_name'] ?? $toName));
        $subject = trim((string)($_POST['subject'] ?? $subject));
        $message = (string)($_POST['message'] ?? $message);
        if ($to === '' || $subject === '' || $message === '') {
            $error = 'To, Subject and Message are required.';
        } else {
            // HTML body with simple nl2br
            $htmlBody = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
            $ok = false;
            if (isset($GLOBALS['mailer']) && $GLOBALS['mailer'] instanceof Mailer) {
                $ok = $GLOBALS['mailer']->send($to, ($toName !== '' ? $toName : $to), $subject, $htmlBody, $message);
            }
            if ($ok) {
                $success = 'Email sent successfully to ' . htmlspecialchars($to);
            } else {
                $detail = '';
                if (isset($GLOBALS['mailer']) && method_exists($GLOBALS['mailer'], 'getLastError')) {
                    $detail = trim((string)$GLOBALS['mailer']->getLastError());
                }
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

</div>
</body>
</html>
