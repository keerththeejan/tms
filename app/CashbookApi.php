<?php

declare(strict_types=1);

/**
 * JSON API for Cash Book (called from public/index.php).
 */
class CashbookApi
{
    public static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function dispatch(\PDO $pdo): void
    {
        $action = $_GET['cb_action'] ?? $_POST['cb_action'] ?? '';
        if ($action === '') {
            self::json(['ok' => false, 'error' => 'Missing cb_action'], 400);

            return;
        }

        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        if ($isPost && !Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
            self::json(['ok' => false, 'error' => 'Invalid CSRF token.'], 400);

            return;
        }

        try {
            switch ($action) {
                case 'get_accounts':
                case 'accounts':
                    /* Pagination must use cb_page — plain "page" is the app route (e.g. page=cashbook) and would be overwritten by URLSearchParams in the client. */
                    $page = (int) ($_GET['cb_page'] ?? 0);
                    $forOps = ($_GET['for_ops'] ?? '') === '1' || ($_GET['for_ops'] ?? '') === 'true';
                    $mapAccountsShape = static function (array $acc): array {
                        // Keep legacy keys (`name`, `type`, `account_kind`, etc.) but also expose
                        // the requested API shape for the UI.
                        $type = (string) ($acc['type'] ?? '');
                        if ($type === 'customer') {
                            $acc['account_type'] = 'Customer';
                        } elseif ($type === 'bank') {
                            $acc['account_type'] = 'Bank';
                        } else {
                            $acc['account_type'] = 'Main';
                        }

                        $acc['account_name'] = (string) ($acc['name'] ?? '');
                        $acc['customer_name'] = isset($acc['customer_name']) && $acc['customer_name'] !== '' ? (string) $acc['customer_name'] : null;
                        return $acc;
                    };
                    if ($forOps) {
                        $accs = CashbookRepository::listAccounts($pdo, true);
                        $accs = array_map($mapAccountsShape, $accs);
                        self::json(['ok' => true, 'accounts' => $accs]);

                        return;
                    }
                    if ($page > 0) {
                        $perPage = (int) ($_GET['per_page'] ?? 15);
                        $q = trim((string) ($_GET['q'] ?? ''));
                        $typeF = trim((string) ($_GET['type'] ?? ''));
                        $statusF = trim((string) ($_GET['status'] ?? ''));
                        $typeFilter = $typeF !== '' ? $typeF : null;
                        $statusFilter = $statusF !== '' ? $statusF : null;
                        $sort = trim((string) ($_GET['sort'] ?? 'default'));
                        if (!in_array($sort, ['default', 'name_asc', 'name_desc', 'balance_asc', 'balance_desc'], true)) {
                            $sort = 'default';
                        }
                        $paged = CashbookRepository::listAccountsPaged($pdo, $q, $typeFilter, $statusFilter, $page, $perPage, $sort);
                        $items = $paged['items'] ?? [];
                        $items = array_map($mapAccountsShape, $items);
                        self::json([
                            'ok' => true,
                            'accounts' => $items,
                            'total' => $paged['total'],
                            'page' => $paged['page'],
                            'per_page' => $paged['per_page'],
                        ]);

                        return;
                    }
                    $accs = CashbookRepository::listAccounts($pdo);
                    $accs = array_map($mapAccountsShape, $accs);
                    self::json(['ok' => true, 'accounts' => $accs]);

                    return;
                case 'mgmt_dashboard':
                    $anchor = trim((string) ($_GET['anchor'] ?? date('Y-m-d')));
                    if ($anchor === '') {
                        $anchor = date('Y-m-d');
                    }
                    $d = CashbookRepository::managementDashboardTotals($pdo, $anchor);
                    self::json(['ok' => true] + $d);

                    return;
                case 'customer_accounts_sync':
                    if (!$isPost) {
                        self::json(['ok' => false, 'error' => 'POST required'], 405);

                        return;
                    }
                    if (!Auth::hasAnyRole(['admin', 'accountant'])) {
                        self::json(['ok' => false, 'error' => 'Forbidden'], 403);

                        return;
                    }
                    $n = CashbookRepository::syncMissingCustomerAccounts($pdo);
                    self::json(['ok' => true, 'linked' => $n]);

                    return;
                case 'account_get':
                    $id = (int) ($_GET['id'] ?? 0);
                    if ($id <= 0) {
                        self::json(['ok' => false, 'error' => 'id required'], 400);

                        return;
                    }
                    $acc = CashbookRepository::getAccount($pdo, $id);
                    if (!$acc) {
                        self::json(['ok' => false, 'error' => 'Account not found'], 404);

                        return;
                    }
                    self::json(['ok' => true, 'account' => $acc]);

                    return;
                case 'account_save':
                    if (!$isPost) {
                        self::json(['ok' => false, 'error' => 'POST required'], 405);

                        return;
                    }
                    $id = (int) ($_POST['id'] ?? 0);
                    $name = trim((string) ($_POST['name'] ?? ''));
                    $type = (string) ($_POST['type'] ?? 'cash');
                    if (!in_array($type, ['cash', 'bank', 'branch', 'customer'], true)) {
                        $type = 'cash';
                    }
                    $status = (string) ($_POST['status'] ?? 'active');
                    if (!in_array($status, ['active', 'inactive'], true)) {
                        $status = 'active';
                    }
                    $branchId = ($_POST['branch_id'] ?? '') !== '' ? (int) $_POST['branch_id'] : null;
                    if ($name === '') {
                        self::json(['ok' => false, 'error' => 'Name is required'], 400);

                        return;
                    }
                    if (CashbookRepository::accountNameExists($pdo, $name, $id)) {
                        self::json(['ok' => false, 'error' => 'An account with this name already exists.'], 400);

                        return;
                    }
                    if ($id > 0) {
                        $existing = CashbookRepository::getAccount($pdo, $id);
                        if ($existing && !empty($existing['customer_id'])) {
                            $type = 'customer';
                        }
                        CashbookRepository::updateAccount($pdo, $id, $name, $type, $branchId, $status);
                    } else {
                        $customerId = null;
                        if ($type === 'customer') {
                            $rawCid = $_POST['customer_id'] ?? '';
                            $cid = $rawCid !== '' ? (int) $rawCid : 0;
                            if ($cid <= 0) {
                                self::json(['ok' => false, 'error' => 'Select a customer for customer accounts.'], 400);

                                return;
                            }
                            $dup = $pdo->prepare('SELECT id FROM cashbook_accounts WHERE customer_id = ? LIMIT 1');
                            $dup->execute([$cid]);
                            if ($dup->fetchColumn()) {
                                self::json(['ok' => false, 'error' => 'This customer already has a Cash Book account.'], 400);

                                return;
                            }
                            $customerId = $cid;
                        }
                        $id = CashbookAccountService::createAccount($pdo, $name, $type, $branchId, $customerId, $status);
                    }
                    $accounts = [];
                    try {
                        $accounts = CashbookRepository::listAccounts($pdo);
                    } catch (\Throwable $e) {
                        /* Account saved; listing is optional for the client */
                    }
                    self::json(['ok' => true, 'id' => $id, 'accounts' => $accounts]);

                    return;
                case 'account_delete':
                    if (!$isPost) {
                        self::json(['ok' => false, 'error' => 'POST required'], 405);

                        return;
                    }
                    $id = (int) ($_POST['id'] ?? 0);
                    if ($id <= 0) {
                        self::json(['ok' => false, 'error' => 'Invalid id'], 400);

                        return;
                    }
                    $acc = CashbookRepository::getAccount($pdo, $id);
                    if (!$acc) {
                        self::json(['ok' => false, 'error' => 'Account not found'], 404);

                        return;
                    }
                    if ((int) ($acc['is_system'] ?? 0) === 1) {
                        self::json(['ok' => false, 'error' => 'System accounts cannot be deleted.'], 400);

                        return;
                    }
                    if (!empty($acc['customer_id'])) {
                        self::json(['ok' => false, 'error' => 'Customer-linked accounts cannot be deleted from here.'], 400);

                        return;
                    }
                    if (abs((float) ($acc['balance'] ?? 0)) > 0.00001) {
                        self::json(['ok' => false, 'error' => 'Balance must be zero before this account can be removed.'], 400);

                        return;
                    }
                    if (!CashbookRepository::deleteAccount($pdo, $id)) {
                        self::json(['ok' => false, 'error' => 'Account has transactions or transfers; cannot delete.'], 400);

                        return;
                    }
                    $accounts = [];
                    try {
                        $accounts = CashbookRepository::listAccounts($pdo);
                    } catch (\Throwable $e) {
                    }
                    self::json(['ok' => true, 'accounts' => $accounts]);

                    return;
                case 'totals':
                    $accountId = (int) ($_GET['account_id'] ?? 0);
                    $period = (string) ($_GET['period'] ?? 'monthly');
                    $anchor = (string) ($_GET['anchor'] ?? date('Y-m-d'));
                    if ($accountId <= 0) {
                        self::json(['ok' => false, 'error' => 'account_id required'], 400);

                        return;
                    }
                    [$from, $to] = CashbookRepository::periodBounds($period, $anchor);
                    $t = CashbookRepository::totalsForAccount($pdo, $accountId, $from, $to);
                    self::json(['ok' => true, 'from' => $from, 'to' => $to, 'totals' => $t]);

                    return;
                case 'get_transactions':
                case 'entries':
                    $accountId = (int) ($_GET['account_id'] ?? 0);
                    $period = (string) ($_GET['period'] ?? 'monthly');
                    $anchor = (string) ($_GET['anchor'] ?? date('Y-m-d'));
                    $q = trim((string) ($_GET['q'] ?? ''));
                    if ($accountId <= 0) {
                        self::json(['ok' => false, 'error' => 'account_id required'], 400);

                        return;
                    }
                    [$from, $to] = CashbookRepository::periodBounds($period, $anchor);
                    $entries = CashbookRepository::listMergedEntries($pdo, $accountId, $from, $to, $q);
                    self::json(['ok' => true, 'from' => $from, 'to' => $to, 'entries' => $entries]);

                    return;
                case 'transaction_save':
                    if (!$isPost) {
                        self::json(['ok' => false, 'error' => 'POST required'], 405);

                        return;
                    }
                    $id = (int) ($_POST['id'] ?? 0);
                    $accountId = (int) ($_POST['account_id'] ?? 0);
                    $txnType = (string) ($_POST['txn_type'] ?? 'income');
                    if (!in_array($txnType, ['income', 'expense'], true)) {
                        self::json(['ok' => false, 'error' => 'Invalid type'], 400);

                        return;
                    }
                    $amount = (float) str_replace(',', '', (string) ($_POST['amount'] ?? '0'));
                    $occurredAt = trim((string) ($_POST['occurred_at'] ?? ''));
                    if ($occurredAt === '') {
                        $occurredAt = date('Y-m-d H:i:s');
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $occurredAt)) {
                        $occurredAt .= ' 12:00:00';
                    }
                    $notes = trim((string) ($_POST['notes'] ?? '')) ?: null;
                    $parcelId = ($_POST['parcel_id'] ?? '') !== '' ? (int) $_POST['parcel_id'] : null;
                    if ($parcelId !== null && $parcelId <= 0) {
                        $parcelId = null;
                    }
                    $itemsJson = null;
                    $items = $_POST['items_json'] ?? '';
                    if (is_string($items) && $items !== '') {
                        $itemsJson = $items;
                    }
                    $attachmentPath = null;
                    if (!empty($_FILES['attachment']) && is_array($_FILES['attachment']) && ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                        $attachmentPath = self::storeUpload($_FILES['attachment']);
                    } elseif ($id > 0) {
                        $st = $pdo->prepare('SELECT attachment_path FROM cashbook_transactions WHERE id=?');
                        $st->execute([$id]);
                        $attachmentPath = $st->fetchColumn() ?: null;
                    }
                    if ($accountId <= 0 || $amount <= 0) {
                        self::json(['ok' => false, 'error' => 'Account and positive amount required'], 400);

                        return;
                    }
                    if ($id > 0) {
                        CashbookRepository::updateTransaction($pdo, $id, $accountId, $txnType, $amount, $occurredAt, $notes, $parcelId, $itemsJson, $attachmentPath);
                    } else {
                        CashbookRepository::addTransaction($pdo, $accountId, $txnType, $amount, $occurredAt, $notes, $parcelId, $itemsJson, $attachmentPath);
                    }
                    self::json(['ok' => true]);

                    return;
                case 'transaction_delete':
                    if (!$isPost) {
                        self::json(['ok' => false, 'error' => 'POST required'], 405);

                        return;
                    }
                    $id = (int) ($_POST['id'] ?? 0);
                    if ($id <= 0) {
                        self::json(['ok' => false, 'error' => 'Invalid id'], 400);

                        return;
                    }
                    CashbookRepository::deleteTransaction($pdo, $id);
                    self::json(['ok' => true]);

                    return;
                case 'transfer_save':
                    if (!$isPost) {
                        self::json(['ok' => false, 'error' => 'POST required'], 405);

                        return;
                    }
                    $fromId = (int) ($_POST['from_account_id'] ?? 0);
                    $toId = (int) ($_POST['to_account_id'] ?? 0);
                    $amount = (float) str_replace(',', '', (string) ($_POST['amount'] ?? '0'));
                    $occurredAt = trim((string) ($_POST['occurred_at'] ?? ''));
                    if ($occurredAt === '') {
                        $occurredAt = date('Y-m-d H:i:s');
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $occurredAt)) {
                        $occurredAt .= ' 12:00:00';
                    }
                    $notes = trim((string) ($_POST['notes'] ?? '')) ?: null;
                    if ($fromId <= 0 || $toId <= 0 || $amount <= 0) {
                        self::json(['ok' => false, 'error' => 'Accounts and amount required'], 400);

                        return;
                    }
                    /* Default: block overdrafts unless client explicitly sends prevent_negative=false */
                    $preventNeg = !(
                        (string) ($_POST['prevent_negative'] ?? '') === '0'
                        || strtolower((string) ($_POST['prevent_negative'] ?? '')) === 'false'
                    );
                    $u = Auth::user();
                    $createdBy = ($u && isset($u['id'])) ? (int) $u['id'] : null;
                    if ($createdBy <= 0) {
                        $createdBy = null;
                    }
                    CashbookRepository::addTransfer($pdo, $fromId, $toId, $amount, $occurredAt, $notes, $preventNeg, $createdBy);
                    self::json(['ok' => true]);

                    return;
                case 'transfer_delete':
                    if (!$isPost) {
                        self::json(['ok' => false, 'error' => 'POST required'], 405);

                        return;
                    }
                    $tid = (int) ($_POST['transfer_id'] ?? 0);
                    if ($tid <= 0) {
                        self::json(['ok' => false, 'error' => 'Invalid transfer id'], 400);

                        return;
                    }
                    CashbookRepository::deleteTransfer($pdo, $tid);
                    self::json(['ok' => true]);

                    return;
                case 'parcel_customer_account':
                    $pid = (int) ($_GET['parcel_id'] ?? 0);
                    if ($pid <= 0) {
                        self::json(['ok' => false, 'error' => 'parcel_id required'], 400);

                        return;
                    }
                    $row = CashbookRepository::parcelCustomerCashbookAccount($pdo, $pid);
                    if ($row === null) {
                        self::json(['ok' => false, 'error' => 'Parcel not found'], 404);

                        return;
                    }
                    self::json([
                        'ok' => true,
                        'customer_id' => $row['customer_id'],
                        'customer_name' => $row['customer_name'],
                        'cashbook_account_id' => $row['cashbook_account_id'],
                    ]);

                    return;
                case 'parcel_search':
                    $q = trim((string) ($_GET['q'] ?? ''));
                    self::json(['ok' => true, 'parcels' => CashbookRepository::searchParcels($pdo, $q)]);

                    return;
                case 'report_months':
                    $accountId = (int) ($_GET['account_id'] ?? 0);
                    $from = (string) ($_GET['from'] ?? date('Y-m-01'));
                    $to = (string) ($_GET['to'] ?? date('Y-m-d'));
                    if ($accountId <= 0) {
                        self::json(['ok' => false, 'error' => 'account_id required'], 400);

                        return;
                    }
                    $rows = CashbookRepository::reportByMonth($pdo, $accountId, $from, $to);
                    self::json(['ok' => true, 'rows' => $rows]);

                    return;
                default:
                    self::json(['ok' => false, 'error' => 'Unknown action'], 400);
            }
        } catch (\Throwable $e) {
            self::json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
     */
    private static function storeUpload(array $file): ?string
    {
        $max = 4 * 1024 * 1024;
        if (($file['size'] ?? 0) > $max) {
            throw new \RuntimeException('File too large (max 4MB).');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return null;
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
        if (!in_array($ext, $allowed, true)) {
            throw new \RuntimeException('Allowed: images or PDF.');
        }
        $dir = dirname(__DIR__) . '/public/uploads/cashbook';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new \RuntimeException('Cannot create upload directory.');
        }
        $base = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $base;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new \RuntimeException('Upload failed.');
        }

        return 'uploads/cashbook/' . $base;
    }
}
