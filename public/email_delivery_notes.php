<?php
require_once __DIR__ . '/../app/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$error = '';
$success = '';

// Defaults
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$toEmail = $_GET['to_email'] ?? '';
$subject = 'Delivery Notes Report';

if ($method === 'POST') {
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $from = trim((string)($_POST['from'] ?? $from));
        $to = trim((string)($_POST['to'] ?? $to));
        $toEmail = trim((string)($_POST['to_email'] ?? $toEmail));
        $subject = trim((string)($_POST['subject'] ?? $subject));
        if ($from === '' || $to === '' || $toEmail === '' || $subject === '') {
            $error = 'From, To, To Email and Subject are required.';
        } else {
            try {
                $pdo = Database::pdo();
                // Fetch Delivery Notes with paid and due within date range
                $sql = 'SELECT dn.id, dn.delivery_date, dn.total_amount,
                               c.name AS customer_name, c.phone AS customer_phone,
                               b.name AS branch_name,
                               COALESCE(paid.total_paid,0) AS paid,
                               (dn.total_amount - COALESCE(paid.total_paid,0)) AS due
                        FROM delivery_notes dn
                        LEFT JOIN customers c ON c.id = dn.customer_id
                        LEFT JOIN branches b ON b.id = dn.branch_id
                        LEFT JOIN (
                          SELECT delivery_note_id, SUM(amount) AS total_paid
                          FROM payments GROUP BY delivery_note_id
                        ) paid ON paid.delivery_note_id = dn.id
                        WHERE dn.delivery_date BETWEEN ? AND ?
                        ORDER BY dn.delivery_date DESC, dn.id DESC';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$from, $to]);
                $rows = $stmt->fetchAll();

                // Build HTML report
                $count = 0; $sumTotal = 0.0; $sumPaid = 0.0; $sumDue = 0.0;
                ob_start();
                ?>
                <div style="font-family: Arial, Helvetica, sans-serif; font-size: 14px;">
                  <h3 style="margin:0 0 8px;">Delivery Notes Report</h3>
                  <div style="margin:0 0 12px; color:#555;">Range: <?php echo htmlspecialchars($from); ?> to <?php echo htmlspecialchars($to); ?></div>
                  <table cellspacing="0" cellpadding="6" border="1" style="border-collapse:collapse; width:100%;">
                    <thead style="background:#f1f1f1;">
                      <tr>
                        <th align="left">DN ID</th>
                        <th align="left">Date</th>
                        <th align="left">Branch</th>
                        <th align="left">Customer</th>
                        <th align="left">Phone</th>
                        <th align="right">Total</th>
                        <th align="right">Paid</th>
                        <th align="right">Due</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!$rows) : ?>
                        <tr><td colspan="8" align="center">No records found</td></tr>
                      <?php else: ?>
                        <?php foreach ($rows as $r):
                            $count++;
                            $t = (float)($r['total_amount'] ?? 0);
                            $p = (float)($r['paid'] ?? 0);
                            $d = (float)($r['due'] ?? 0);
                            $sumTotal += $t; $sumPaid += $p; $sumDue += $d; ?>
                          <tr>
                            <td>#<?php echo (int)$r['id']; ?></td>
                            <td><?php echo htmlspecialchars((string)$r['delivery_date']); ?></td>
                            <td><?php echo htmlspecialchars((string)($r['branch_name'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string)($r['customer_name'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string)($r['customer_phone'] ?? '')); ?></td>
                            <td align="right"><?php echo number_format($t, 2); ?></td>
                            <td align="right"><?php echo number_format($p, 2); ?></td>
                            <td align="right"><?php echo number_format($d, 2); ?></td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                    <tfoot>
                      <tr style="background:#fafafa; font-weight:bold;">
                        <td colspan="5" align="right">Totals (<?php echo (int)$count; ?>)</td>
                        <td align="right"><?php echo number_format($sumTotal, 2); ?></td>
                        <td align="right"><?php echo number_format($sumPaid, 2); ?></td>
                        <td align="right"><?php echo number_format($sumDue, 2); ?></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
                <?php
                $htmlReport = ob_get_clean();

                // Plain text fallback
                $text = "Delivery Notes Report\nRange: $from to $to\n";
                foreach ($rows as $r) {
                    $text .= sprintf(
                        "DN #%d | %s | %s | %s | %s | Total: %.2f | Paid: %.2f | Due: %.2f\n",
                        (int)$r['id'], (string)$r['delivery_date'], (string)($r['branch_name'] ?? ''),
                        (string)($r['customer_name'] ?? ''), (string)($r['customer_phone'] ?? ''),
                        (float)$r['total_amount'], (float)$r['paid'], (float)$r['due']
                    );
                }

                // Send email
                $sent = false;
                if (isset($GLOBALS['mailer']) && $GLOBALS['mailer'] instanceof Mailer) {
                    $sent = $GLOBALS['mailer']->send($toEmail, $toEmail, $subject, $htmlReport, $text);
                }
                if ($sent) {
                    $success = 'Report sent successfully to ' . htmlspecialchars($toEmail);
                } else {
                    $detail = '';
                    if (isset($GLOBALS['mailer']) && method_exists($GLOBALS['mailer'], 'getLastError')) {
                        $detail = trim((string)$GLOBALS['mailer']->getLastError());
                    }
                    $error = 'Failed to send report. ' . ($detail !== '' ? ('Reason: ' . htmlspecialchars($detail)) : 'Check mail settings.');
                }
            } catch (Throwable $e) {
                $error = 'Error: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Email Delivery Notes Report</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="mb-3">Email Delivery Notes Report</h3>
  <p class="text-muted">Select a date range and recipient. SMTP config is in <code>config/mail.php</code>.</p>

  <?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
  <?php elseif ($success !== ''): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
  <?php endif; ?>

  <form method="post" class="card p-3 shadow-sm bg-white">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Helpers::csrfToken()); ?>">

    <div class="row g-3">
      <div class="col-sm-6">
        <label class="form-label">From Date</label>
        <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($from); ?>" required>
      </div>
      <div class="col-sm-6">
        <label class="form-label">To Date</label>
        <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($to); ?>" required>
      </div>
    </div>

    <div class="row g-3 mt-1">
      <div class="col-sm-8">
        <label class="form-label">To Email</label>
        <input type="email" name="to_email" class="form-control" value="<?php echo htmlspecialchars($toEmail); ?>" required>
      </div>
      <div class="col-sm-4">
        <label class="form-label">Subject</label>
        <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($subject); ?>" required>
      </div>
    </div>

    <div class="d-flex gap-2 mt-3">
      <button type="submit" class="btn btn-primary">Send Report</button>
      <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars(Helpers::baseUrl('index.php?page=dashboard')); ?>">Back to App</a>
      <a class="btn btn-outline-dark" href="<?php echo htmlspecialchars(Helpers::baseUrl('email.php')); ?>">Email Tester</a>
    </div>
  </form>
</div>
</body>
</html>
