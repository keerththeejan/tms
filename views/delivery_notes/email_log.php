<?php /** @var array $dn */ /** @var array $logs */ ?>
<div class="container-fluid px-0">
  <div class="row g-2">
    <div class="col-12">
      <h4 class="h5 mb-0 fw-bold">Email log</h4>
    </div>
    <div class="col-12">
      <div class="card shadow-sm rounded-3 border-0">
        <div class="card-body">
          <div class="mb-2"><strong>Delivery Note:</strong> #<?php echo (int)($dn['id'] ?? 0); ?></div>
          <div class="mb-2"><strong>Customer:</strong> <?php echo htmlspecialchars((string)($dn['customer_name'] ?? '')); ?></div>
          <div class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars((string)($dn['customer_email'] ?? '')); ?></div>
          <div class="mb-2"><strong>Status:</strong>
            <?php $st = strtolower(trim((string)($dn['last_email_status'] ?? ''))); ?>
            <?php if ($st === 'sent'): ?>
              <span class="badge bg-success">Sent</span>
            <?php elseif ($st === 'failed'): ?>
              <span class="badge bg-danger">Failed</span>
            <?php else: ?>
              <span class="badge bg-secondary">Not sent</span>
            <?php endif; ?>
          </div>
          <div class="mb-2"><strong>Time:</strong> <?php echo htmlspecialchars((string)($dn['last_emailed_at'] ?? '')); ?></div>
          <a href="<?php echo Helpers::baseUrl('index.php?page=delivery_notes'); ?>" class="btn btn-outline-secondary">Back</a>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card shadow-sm rounded-3 border-0 overflow-hidden">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>To</th>
                  <th>Subject</th>
                  <th>Message</th>
                  <th>Status</th>
                  <th>Error</th>
                  <th>Time</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($logs)): ?>
                  <?php foreach ($logs as $r): ?>
                    <?php $tb = (string)($r['text_body'] ?? ''); $hb = (string)($r['html_body'] ?? ''); $msg = trim($tb !== '' ? $tb : strip_tags($hb)); ?>
                    <tr>
                      <td><?php echo (int)$r['id']; ?></td>
                      <td><?php echo htmlspecialchars((string)($r['to_email'] ?? '')); ?></td>
                      <td><?php echo htmlspecialchars((string)($r['subject'] ?? '')); ?></td>
                      <td style="max-width:360px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($msg); ?>"><?php echo htmlspecialchars($msg); ?></td>
                      <td>
                        <?php if (($r['status'] ?? '') === 'sent'): ?>
                          <span class="badge bg-success">Sent</span>
                        <?php else: ?>
                          <span class="badge bg-danger">Failed</span>
                        <?php endif; ?>
                      </td>
                      <td style="max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars((string)($r['error'] ?? '')); ?>"><?php echo htmlspecialchars((string)($r['error'] ?? '')); ?></td>
                      <td><?php echo htmlspecialchars((string)($r['created_at'] ?? '')); ?></td>
                      <td>
                        <?php $mid = 'dnLogModal-' . (int)$r['id']; ?>
                        <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#<?php echo $mid; ?>"><i class="bi bi-eye" aria-hidden="true"></i><span>View</span></button>
                        <div class="modal fade" id="<?php echo $mid; ?>" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title">Email to <?php echo htmlspecialchars((string)($r['to_email'] ?? '')); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                <div class="mb-2"><strong>Subject:</strong> <?php echo htmlspecialchars((string)($r['subject'] ?? '')); ?></div>
                                <div class="mb-2"><strong>Sent at:</strong> <?php echo htmlspecialchars((string)($r['created_at'] ?? '')); ?></div>
                                <hr>
                                <?php if (!empty($hb)): ?>
                                  <div style="border:1px solid #eee;padding:10px;background:#fafafa"><?php echo (string)$hb; ?></div>
                                <?php elseif (!empty($tb)): ?>
                                  <pre style="white-space:pre-wrap;background:#fafafa;border:1px solid #eee;padding:10px;"><?php echo htmlspecialchars($tb); ?></pre>
                                <?php else: ?>
                                  <div class="text-muted">No message content recorded.</div>
                                <?php endif; ?>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="8">No email logs for this delivery note.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
