<?php

declare(strict_types=1);

final class TransferVoucherApi
{
    public static function dispatch(PDO $pdo): void
    {
        $action = (string) ($_GET['tv_action'] ?? $_POST['tv_action'] ?? '');
        $isPost = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';

        try {
            switch ($action) {
                case 'list_vouchers':
                    self::requireRole();
                    $page = max(1, (int) ($_GET['page_no'] ?? 1));
                    $limit = max(1, (int) ($_GET['limit'] ?? 20));
                    $filters = [
                        'status' => (string) ($_GET['status'] ?? ''),
                        'from_date' => (string) ($_GET['from_date'] ?? ''),
                        'to_date' => (string) ($_GET['to_date'] ?? ''),
                        'q' => trim((string) ($_GET['q'] ?? '')),
                    ];
                    self::json(['ok' => true, 'data' => TransferVoucherRepository::listVouchers($pdo, $filters, $page, $limit)]);
                    return;

                case 'get_voucher':
                    self::requireRole();
                    $id = (int) ($_GET['id'] ?? 0);
                    if ($id <= 0) {
                        self::json(['ok' => false, 'error' => 'Invalid voucher id'], 400);
                        return;
                    }
                    $voucher = TransferVoucherRepository::getVoucher($pdo, $id);
                    if (!$voucher) {
                        self::json(['ok' => false, 'error' => 'Voucher not found'], 404);
                        return;
                    }
                    self::json(['ok' => true, 'data' => $voucher]);
                    return;

                case 'save_voucher':
                    self::requireRole();
                    self::requirePost($isPost);
                    self::requireCsrf();
                    $u = Auth::user();
                    $uid = (int) ($u['id'] ?? 0) ?: null;
                    $voucher = TransferVoucherRepository::upsertDraftWithItems($pdo, $_POST, $uid);
                    CashbookRepository::audit($pdo, 'transfer_voucher', (string) ($voucher['id'] ?? ''), 'save', $uid, [
                        'voucher_no' => $voucher['voucher_no'] ?? null,
                    ]);
                    self::json(['ok' => true, 'data' => $voucher]);
                    return;

                case 'post_voucher':
                    self::requireRole();
                    self::requirePost($isPost);
                    self::requireCsrf();
                    $u = Auth::user();
                    $uid = (int) ($u['id'] ?? 0) ?: null;
                    $voucher = TransferVoucherRepository::postVoucherWithItems($pdo, $_POST, $uid);
                    self::json(['ok' => true, 'data' => $voucher]);
                    return;

                case 'cancel_voucher':
                    self::requireRole();
                    self::requirePost($isPost);
                    self::requireCsrf();
                    $id = (int) ($_POST['id'] ?? 0);
                    if ($id <= 0) {
                        self::json(['ok' => false, 'error' => 'Invalid voucher id'], 400);
                        return;
                    }
                    $u = Auth::user();
                    $uid = (int) ($u['id'] ?? 0) ?: null;
                    $voucher = TransferVoucherRepository::cancelVoucher($pdo, $id, $uid, trim((string) ($_POST['reason'] ?? '')) ?: null);
                    self::json(['ok' => true, 'data' => $voucher]);
                    return;

                case 'dashboard_summary':
                    self::requireRole();
                    $from = (string) ($_GET['from_date'] ?? date('Y-m-01'));
                    $to = (string) ($_GET['to_date'] ?? date('Y-m-d'));
                    self::json(['ok' => true, 'data' => TransferVoucherRepository::summary($pdo, $from, $to)]);
                    return;

                case 'audit_logs':
                    self::requireRole();
                    $limit = max(1, (int) ($_GET['limit'] ?? 20));
                    self::json(['ok' => true, 'data' => TransferVoucherRepository::recentAuditLogs($pdo, $limit)]);
                    return;

                case 'export_csv':
                    self::requireRole();
                    $filters = [
                        'status' => (string) ($_GET['status'] ?? ''),
                        'from_date' => (string) ($_GET['from_date'] ?? ''),
                        'to_date' => (string) ($_GET['to_date'] ?? ''),
                        'q' => trim((string) ($_GET['q'] ?? '')),
                    ];
                    $result = TransferVoucherRepository::listVouchers($pdo, $filters, 1, 1000);
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="transfer_vouchers_' . date('Ymd_His') . '.csv"');
                    $out = fopen('php://output', 'w');
                    fputcsv($out, ['Voucher No', 'Date', 'From Account', 'To Account', 'Amount', 'Status', 'Reference', 'Narration', 'Posted At']);
                    foreach ($result['rows'] as $row) {
                        fputcsv($out, [
                            $row['voucher_no'] ?? '',
                            $row['voucher_date'] ?? '',
                            $row['from_account_name'] ?? '',
                            $row['to_account_name'] ?? '',
                            $row['amount'] ?? '',
                            $row['status'] ?? '',
                            $row['reference_number'] ?? '',
                            $row['narration'] ?? '',
                            $row['posted_at'] ?? '',
                        ]);
                    }
                    fclose($out);
                    return;

                default:
                    self::json(['ok' => false, 'error' => 'Unknown action'], 400);
                    return;
            }
        } catch (Throwable $e) {
            self::json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private static function requireRole(): void
    {
        if (!Auth::hasAnyRole(['admin', 'accountant'])) {
            self::json(['ok' => false, 'error' => 'Forbidden'], 403);
            exit;
        }
    }

    private static function requirePost(bool $isPost): void
    {
        if (!$isPost) {
            self::json(['ok' => false, 'error' => 'POST required'], 405);
            exit;
        }
    }

    private static function requireCsrf(): void
    {
        if (!Helpers::verifyCsrf((string) ($_POST['csrf_token'] ?? ''))) {
            self::json(['ok' => false, 'error' => 'Invalid CSRF token'], 400);
            exit;
        }
    }

    private static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}