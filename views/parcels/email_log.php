<?php /** @var array $parcelHdr */ /** @var array $logs */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Email Log — Parcel #<?php echo (int)($parcelHdr['id'] ?? 0); ?> (<?php echo htmlspecialchars($parcelHdr['customer_name'] ?? ''); ?>)</h3>
  <a class="btn btn-sm btn-outline-secondary" href="<?php echo Helpers::baseUrl('index.php?page=parcels'); ?>">Back to Parcels</a>
</div>
<div class="table-responsive">
  <table class="table table-sm table-striped align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>To</th>
        <th>Subject</th>
        <th>Status</th>
        <th>Error</th>
        <th>Time</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($logs)): ?>
        <?php foreach ($logs as $r): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['to_email']); ?></td>
            <td><?php echo htmlspecialchars($r['subject']); ?></td>
            <td>
              <?php if ($r['status'] === 'sent'): ?>
                <span class="badge bg-success">Sent</span>
              <?php else: ?>
                <span class="badge bg-danger">Failed</span>
              <?php endif; ?>
            </td>
            <td style="max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($r['error'] ?? ''); ?>">
              <?php echo htmlspecialchars($r['error'] ?? ''); ?>
            </td>
            <td><?php echo htmlspecialchars($r['created_at']); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="6">No email logs for this parcel.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
