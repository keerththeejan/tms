<?php
require_once __DIR__ . '/../app/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$error = '';
$success = '';

$dnId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$toEmail = '';
$subject = 'Delivery Note';

if ($method === 'POST') {
    if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $dnId = (int)($_POST['dn_id'] ?? 0);
        $toEmail = trim((string)($_POST['to_email'] ?? ''));
        $subject = trim((string)($_POST['subject'] ?? 'Delivery Note'));
        if ($dnId <= 0 || $toEmail === '' || $subject === '') {
            $error = 'Delivery Note ID, To Email and Subject are required.';
        } else {
            try {
                $pdo = Database::pdo();
                // Main DN row with totals and paid
                $sql = 'SELECT dn.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
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
                        WHERE dn.id = ? LIMIT 1';
                $st = $pdo->prepare($sql);
                $st->execute([$dnId]);
                $dn = $st->fetch();
                if (!$dn) { throw new RuntimeException('Delivery Note not found.'); }

                // Items (parcels joined via delivery_note_parcels)
                $itSql = 'SELECT dnp.amount, p.id AS parcel_id, p.tracking_number, p.status, p.vehicle_no
                          FROM delivery_note_parcels dnp
                          JOIN parcels p ON p.id = dnp.parcel_id
                          WHERE dnp.delivery_note_id = ?
                          ORDER BY p.id';
                $its = $pdo->prepare($itSql);
                $its->execute([$dnId]);
                $items = $its->fetchAll();

                // Build HTML
                ob_start();
                ?>
                <div style="font-family: Arial, Helvetica, sans-serif; font-size: 14px;">
                  <h3 style="margin:0 0 8px;">Delivery Note #<?php echo (int)$dn['id']; ?></h3>
                  <div style="margin:0 0 6px; color:#555;">Date: <?php echo htmlspecialchars((string)$dn['delivery_date']); ?></div>
                  <div style="margin:0 0 6px; color:#555;">Branch: <?php echo htmlspecialchars((string)($dn['branch_name'] ?? '')); ?></div>
                  <div style="margin:0 0 12px; color:#555;">Customer: <?php echo htmlspecialchars((string)($dn['customer_name'] ?? '')); ?> (<?php echo htmlspecialchars((string)($dn['customer_phone'] ?? '')); ?>)</div>

                  <table cellspacing="0" cellpadding="6" border="1" style="border-collapse:collapse; width:100%;">
                    <thead style="background:#f1f1f1;">
                      <tr>
                        <th align="left">Parcel ID</th>
                        <th align="left">Tracking</th>
                        <th align="left">Status</th>
                        <th align="left">Vehicle</th>
                        <th align="right">Amount</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!$items): ?>
                        <tr><td colspan="5" align="center">No items</td></tr>
                      <?php else: foreach ($items as $it): ?>
                        <tr>
                          <td>#<?php echo (int)$it['parcel_id']; ?></td>
                          <td><?php echo htmlspecialchars((string)($it['tracking_number'] ?? '')); ?></td>
                          <td><?php echo htmlspecialchars((string)($it['status'] ?? '')); ?></td>
                          <td><?php echo htmlspecialchars((string)($it['vehicle_no'] ?? '')); ?></td>
                          <td align="right"><?php echo number_format((float)($it['amount'] ?? 0), 2); ?></td>
                        </tr>
                      <?php endforeach; endif; ?>
                    </tbody>
                    <tfoot>
                      <tr style="background:#fafafa;">
                        <td colspan="4" align="right"><strong>Total</strong></td>
                        <td align="right"><strong><?php echo number_format((float)$dn['total_amount'], 2); ?></strong></td>
                      </tr>
                      <tr>
                        <td colspan="4" align="right">Paid</td>
                        <td align="right"><?php echo number_format((float)($dn['paid'] ?? 0), 2); ?></td>
                      </tr>
                      <tr>
                        <td colspan="4" align="right">Due</td>
                        <td align="right"><?php echo number_format((float)($dn['due'] ?? 0), 2); ?></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
                <?php
                $html = ob_get_clean();

                // Text fallback
                $text = sprintf("Delivery Note #%d\nDate: %s\nBranch: %s\nCustomer: %s (%s)\nTotal: %.2f Paid: %.2f Due: %.2f\n",
                    (int)$dn['id'], (string)$dn['delivery_date'], (string)($dn['branch_name'] ?? ''),
                    (string)($dn['customer_name'] ?? ''), (string)($dn['customer_phone'] ?? ''),
                    (float)$dn['total_amount'], (float)($dn['paid'] ?? 0), (float)($dn['due'] ?? 0)
                );
                foreach ($items as $it) {
                    $text .= sprintf("Parcel #%d | %s | %s | %s | Amount: %.2f\n",
                        (int)$it['parcel_id'], (string)($it['tracking_number'] ?? ''), (string)($it['status'] ?? ''), (string)($it['vehicle_no'] ?? ''), (float)($it['amount'] ?? 0)
                    );
                }

                $sent = false;
                if (isset($GLOBALS['mailer']) && $GLOBALS['mailer'] instanceof Mailer) {
                    $sent = $GLOBALS['mailer']->send($toEmail, $toEmail, $subject, $html, $text);
                }
                if ($sent) {
                    $success = 'Delivery Note emailed to ' . htmlspecialchars($toEmail);
                } else {
                    $detail = '';
                    if (isset($GLOBALS['mailer']) && method_exists($GLOBALS['mailer'], 'getLastError')) {
                        $detail = trim((string)$GLOBALS['mailer']->getLastError());
                    }
                    $error = 'Failed to send email.' . ($detail !== '' ? (' Reason: ' . htmlspecialchars($detail)) : '');
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
  <title>Email Delivery Note</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="mb-3">Email Delivery Note</h3>
  <?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
  <?php elseif ($success !== ''): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
  <?php endif; ?>

  <form method="post" class="card p-3 shadow-sm bg-white">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Helpers::csrfToken()); ?>">

    <div class="row g-3">
      <div class="col-sm-3">
        <label class="form-label">Delivery Note ID</label>
        <input type="number" name="dn_id" class="form-control" value="<?php echo (int)$dnId; ?>" required>
      </div>
      <div class="col-sm-6">
        <label class="form-label">To Email</label>
        <input type="email" name="to_email" class="form-control" value="<?php echo htmlspecialchars($toEmail); ?>" required>
      </div>
      <div class="col-sm-3">
        <label class="form-label">Subject</label>
        <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($subject); ?>" required>
      </div>
    </div>

    <div class="d-flex gap-2 mt-3">
      <button type="submit" class="btn btn-primary">Send Delivery Note</button>
      <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars(Helpers::baseUrl('index.php?page=dashboard')); ?>">Back to App</a>
      <a class="btn btn-outline-dark" href="<?php echo htmlspecialchars(Helpers::baseUrl('email_delivery_notes.php')); ?>">Range Report</a>
    </div>
  </form>
</div>
</body>
</html>
