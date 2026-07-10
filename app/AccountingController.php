<?php

declare(strict_types=1);

/**
 * Accounting Controller
 * Handles voucher entry and accounting operations (BUSY/Tally style)
 */
class AccountingController
{
    public static function json(array $data, int $code = 200): void
    {
        if (isset($data['ok']) && !isset($data['success'])) {
            $data['success'] = (bool) $data['ok'];
        }
        if (!isset($data['ok']) && isset($data['success'])) {
            $data['ok'] = (bool) $data['success'];
        }
        if (isset($data['error']) && !isset($data['message'])) {
            $data['message'] = $data['error'];
        }
        if (!isset($data['errors'])) {
            $data['errors'] = [];
        }

        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function dispatch(PDO $pdo): void
    {
        $action = $_GET['acc_action'] ?? $_POST['acc_action'] ?? '';
        if ($action === '') {
            self::json(['ok' => false, 'error' => 'Missing acc_action'], 400);
            return;
        }

        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        if ($isPost) {
            $csrfToken = (string) ($_POST['csrf_token'] ?? '');
            if ($csrfToken === '' && str_contains((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
                $jsonBody = json_decode((string) file_get_contents('php://input'), true);
                if (is_array($jsonBody)) {
                    $csrfToken = (string) ($jsonBody['csrf_token'] ?? '');
                    $_POST = array_merge($_POST, $jsonBody);
                }
            }
            if (!Helpers::verifyCsrf($csrfToken)) {
                self::json(['ok' => false, 'error' => 'Invalid CSRF token.'], 400);
                return;
            }
        }

        try {
            AccountingVoucherRepository::ensureSchema($pdo);
            AccountRepository::ensureSchema($pdo);

            switch ($action) {
                case 'list_vouchers':
                    self::listVouchers($pdo);
                    return;

                case 'get_voucher':
                    self::getVoucher($pdo);
                    return;

                case 'save_voucher':
                    self::saveVoucher($pdo, $isPost);
                    return;

                case 'post_voucher':
                    self::postVoucher($pdo, $isPost);
                    return;

                case 'cancel_voucher':
                    self::cancelVoucher($pdo, $isPost);
                    return;

                case 'delete_voucher':
                    self::deleteVoucher($pdo, $isPost);
                    return;

                case 'list_accounts':
                    self::listAccounts($pdo);
                    return;

                case 'search_accounts':
                    self::searchAccounts($pdo);
                    return;

                case 'chart_accounts':
                    self::chartAccounts($pdo);
                    return;

                case 'next_account_code':
                    self::nextAccountCode($pdo);
                    return;

                case 'get_account_balance':
                    self::getAccountBalance($pdo);
                    return;

                case 'list_account_groups':
                    self::listAccountGroups($pdo);
                    return;

                case 'seed_default_account_groups':
                    self::seedDefaultAccountGroups($pdo, $isPost);
                    return;

                case 'save_account_group':
                    self::saveAccountGroup($pdo, $isPost);
                    return;

                case 'day_book':
                    self::dayBook($pdo);
                    return;

                case 'ledger':
                    self::ledger($pdo);
                    return;

                case 'list_customer_ledgers':
                    self::listCustomerLedgers($pdo);
                    return;

                case 'get_customer_ledger':
                    self::getCustomerLedger($pdo);
                    return;

                case 'trial_balance':
                    self::trialBalance($pdo);
                    return;

                case 'profit_loss':
                    self::profitLoss($pdo);
                    return;

                case 'balance_sheet':
                    self::balanceSheet($pdo);
                    return;

                case 'dashboard':
                    self::dashboard($pdo);
                    return;

                case 'get_account':
                    self::getAccount($pdo);
                    return;

                case 'save_account':
                    self::saveAccount($pdo, $isPost);
                    return;

                case 'delete_account':
                    self::deleteAccount($pdo, $isPost);
                    return;

                case 'cash_book':
                    self::cashBook($pdo);
                    return;

                case 'bank_book':
                    self::bankBook($pdo);
                    return;

                case 'group_tree':
                    self::groupTree($pdo);
                    return;

                case 'payment_mode_settings':
                    self::paymentModeSettings($pdo);
                    return;

                case 'save_payment_mode_settings':
                    self::savePaymentModeSettings($pdo, $isPost);
                    return;

                default:
                    self::json(['ok' => false, 'error' => 'Unknown action'], 400);
                    return;
            }
        } catch (Throwable $e) {
            error_log('[AccountingController] ' . ($action ?? 'unknown') . ': ' . $e->getMessage());
            self::json([
                'ok' => false,
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
                'errors' => [],
            ], 500);
        }
    }

    private static function listVouchers(PDO $pdo): void
    {
        $voucherType = $_GET['voucher_type'] ?? '';
        $fromDate = $_GET['from_date'] ?? '';
        $toDate = $_GET['to_date'] ?? '';
        $status = $_GET['status'] ?? '';
        $query = $_GET['q'] ?? '';
        $page = max(1, (int) ($_GET['page_no'] ?? $_GET['acc_page'] ?? 1));
        $limit = max(1, min(500, (int) ($_GET['limit'] ?? 20)));

        $result = AccountingVoucherRepository::listVouchers($pdo, [
            'voucher_type' => $voucherType,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'status' => $status,
            'q' => $query,
        ], $page, $limit);

        self::json(['ok' => true, 'data' => $result]);
    }

    private static function getVoucher(PDO $pdo): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher id'], 400);
            return;
        }

        $voucher = AccountingVoucherRepository::getById($pdo, $id);
        if (!$voucher) {
            self::json(['ok' => false, 'error' => 'Voucher not found'], 404);
            return;
        }

        $voucher['details'] = VoucherDetailRepository::getByVoucherId($pdo, $id);
        self::json(['ok' => true, 'data' => $voucher]);
    }

    private static function saveVoucher(PDO $pdo, bool $isPost): void
    {
        if (!$isPost) {
            self::json(['ok' => false, 'error' => 'POST required'], 405);
            return;
        }

        $payload = $_POST;
        $rawDetails = $payload['details'] ?? [];
        $voucherType = strtoupper((string) ($payload['voucher_type'] ?? 'JOURNAL'));
        $paymentMode = (string) ($payload['payment_mode'] ?? 'CASH');

        if (($paymentMode) === 'PETTY_CASH') {
            $paymentMode = 'CASH';
            $payload['payment_mode'] = 'CASH';
        }

        if (empty($rawDetails)) {
            self::json(['ok' => false, 'error' => 'At least one line item is required'], 400);
            return;
        }

        $normalizedDetails = [];
        foreach ((array) $rawDetails as $line) {
            if (!is_array($line)) {
                continue;
            }
            $accountId = (int) ($line['account_id'] ?? 0);
            $debit = (float) ($line['debit_amount'] ?? 0);
            $credit = (float) ($line['credit_amount'] ?? 0);
            if ($accountId <= 0 && $debit <= 0 && $credit <= 0) {
                continue;
            }
            $normalizedDetails[] = [
                'account_id' => $accountId,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'narration' => $line['narration'] ?? null,
                'cost_center_id' => $line['cost_center_id'] ?? null,
            ];
        }

        try {
            VoucherAutoLedgerService::validateSimpleLines($normalizedDetails);
        } catch (InvalidArgumentException $e) {
            self::json(['ok' => false, 'error' => $e->getMessage()], 400);
            return;
        }

        $details = VoucherAutoLedgerService::detailsForStorage($normalizedDetails);

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($details as $detail) {
            $totalDebit += (float) ($detail['debit_amount'] ?? 0);
            $totalCredit += (float) ($detail['credit_amount'] ?? 0);
        }

        $voucherDate = (string) ($payload['voucher_date'] ?? date('Y-m-d'));
        $payload['total_debit'] = $totalDebit;
        $payload['total_credit'] = $totalCredit;
        $payload['fiscal_year'] = substr($voucherDate, 0, 4);
        $user = Auth::user();
        if (!isset($payload['id']) || (int) $payload['id'] <= 0) {
            $payload['created_by'] = $user['id'] ?? null;
            $payload['branch_id'] = $user['branch_id'] ?? null;
        }

        $pdo->beginTransaction();
        try {
            if (isset($payload['id']) && $payload['id'] > 0) {
                $voucher = AccountingVoucherRepository::update($pdo, (int) $payload['id'], $payload);
                $voucherId = (int) $payload['id'];
            } else {
                $voucher = AccountingVoucherRepository::create($pdo, $payload);
                $voucherId = (int) $voucher['id'];
            }

            // Save details
            VoucherDetailRepository::createBatch($pdo, $voucherId, $details);

            self::postVoucherLedger($pdo, $voucherId);

            $pdo->commit();
            self::json(['ok' => true, 'data' => AccountingVoucherRepository::getById($pdo, $voucherId)]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function postVoucher(PDO $pdo, bool $isPost): void
    {
        if (!$isPost) {
            self::json(['ok' => false, 'error' => 'POST required'], 405);
            return;
        }

        $payload = $_POST;
        $id = (int) ($payload['id'] ?? 0);

        if ($id <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher id'], 400);
            return;
        }

        $voucher = AccountingVoucherRepository::getById($pdo, $id);
        if (!$voucher) {
            self::json(['ok' => false, 'error' => 'Voucher not found'], 404);
            return;
        }

        if (($voucher['status'] ?? '') !== 'DRAFT') {
            self::json(['ok' => false, 'error' => 'Only draft vouchers can be posted'], 400);
            return;
        }

        $details = VoucherDetailRepository::getByVoucherId($pdo, $id);
        if (empty($details)) {
            self::json(['ok' => false, 'error' => 'No line items found'], 400);
            return;
        }

        $pdo->beginTransaction();
        try {
            self::postVoucherLedger($pdo, $id);

            $pdo->commit();
            self::json(['ok' => true, 'data' => AccountingVoucherRepository::getById($pdo, $id)]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function postVoucherLedger(PDO $pdo, int $voucherId): void
    {
        $voucher = AccountingVoucherRepository::getById($pdo, $voucherId);
        if (!$voucher) {
            throw new RuntimeException('Voucher not found');
        }

        if (($voucher['status'] ?? '') === 'POSTED') {
            return;
        }

        $details = VoucherDetailRepository::getByVoucherId($pdo, $voucherId);
        if ($details === []) {
            throw new RuntimeException('No line items found');
        }

        $ledgerEntries = [];
        foreach ($details as $detail) {
            $ledgerEntries[] = [
                'voucher_detail_id' => $detail['id'],
                'account_id' => $detail['account_id'],
                'entry_date' => $voucher['voucher_date'],
                'voucher_type' => $voucher['voucher_type'],
                'voucher_number' => $voucher['voucher_number'],
                'debit_amount' => $detail['debit_amount'],
                'credit_amount' => $detail['credit_amount'],
                'balance_type' => ((float) ($detail['debit_amount'] ?? 0) > 0) ? 'DEBIT' : 'CREDIT',
                'narration' => $detail['narration'] ?? $voucher['narration'],
                'branch_id' => $voucher['branch_id'],
            ];
        }

        LedgerEntryRepository::createBatch($pdo, $voucherId, $ledgerEntries);

        $st = $pdo->prepare(
            'UPDATE vouchers SET status = ?, posted_at = CURRENT_TIMESTAMP, posted_by = ? WHERE id = ?'
        );
        $st->execute(['POSTED', Auth::user()['id'] ?? null, $voucherId]);
    }

    private static function cancelVoucher(PDO $pdo, bool $isPost): void
    {
        if (!$isPost) {
            self::json(['ok' => false, 'error' => 'POST required'], 405);
            return;
        }

        $payload = $_POST;
        $id = (int) ($payload['id'] ?? 0);
        $reason = $payload['reason'] ?? '';

        if ($id <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher id'], 400);
            return;
        }

        AccountingVoucherRepository::cancel($pdo, $id, $reason, Auth::user()['id'] ?? null);
        self::json(['ok' => true]);
    }

    private static function deleteVoucher(PDO $pdo, bool $isPost): void
    {
        if (!$isPost) {
            self::json(['ok' => false, 'error' => 'POST required'], 405);
            return;
        }

        $payload = $_POST;
        $id = (int) ($payload['id'] ?? 0);

        if ($id <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid voucher id'], 400);
            return;
        }

        AccountingVoucherRepository::delete($pdo, $id, Auth::user()['id'] ?? null);
        self::json(['ok' => true]);
    }

    private static function listAccounts(PDO $pdo): void
    {
        $accounts = AccountRepository::listAccounts($pdo, ($_GET['include_inactive'] ?? '') === '1');
        self::json(['ok' => true, 'success' => true, 'data' => $accounts]);
    }

    private static function chartAccounts(PDO $pdo): void
    {
        $page = max(1, (int) ($_GET['page_no'] ?? 1));
        $limit = max(1, min(500, (int) ($_GET['limit'] ?? 50)));
        $result = AccountRepository::listForChart($pdo, [
            'q' => $_GET['q'] ?? '',
            'group_type' => $_GET['group_type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'sort' => $_GET['sort'] ?? 'account_code',
            'order' => $_GET['order'] ?? 'ASC',
        ], $page, $limit);

        self::json([
            'ok' => true,
            'success' => true,
            'data' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    private static function nextAccountCode(PDO $pdo): void
    {
        $groupId = (int) ($_GET['account_group_id'] ?? 0);
        $code = AccountRepository::generateNextCode($pdo, $groupId > 0 ? $groupId : null);
        self::json(['ok' => true, 'success' => true, 'data' => ['account_code' => $code]]);
    }

    private static function searchAccounts(PDO $pdo): void
    {
        $query = $_GET['q'] ?? '';
        $limit = (int) ($_GET['limit'] ?? 50);
        $accounts = AccountRepository::searchAccounts($pdo, $query, $limit);
        self::json(['ok' => true, 'data' => $accounts]);
    }

    private static function getAccountBalance(PDO $pdo): void
    {
        $accountId = (int) ($_GET['account_id'] ?? 0);
        $asOfDate = $_GET['as_of_date'] ?? null;

        if ($accountId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid account id'], 400);
            return;
        }

        $balance = AccountRepository::getBalance($pdo, $accountId, $asOfDate);
        self::json(['ok' => true, 'data' => ['balance' => $balance]]);
    }

    private static function listAccountGroups(PDO $pdo): void
    {
        $groups = AccountGroupRepository::listForAccountForm($pdo);
        self::json(['ok' => true, 'success' => true, 'data' => $groups]);
    }

    private static function saveAccountGroup(PDO $pdo, bool $isPost): void
    {
        if (!$isPost) {
            self::json(['ok' => false, 'error' => 'POST required'], 405);
            return;
        }

        $payload = $_POST;
        $name = trim((string) ($payload['group_name'] ?? ''));
        $code = strtoupper(trim((string) ($payload['group_code'] ?? '')));
        $parentId = !empty($payload['parent_id']) ? (int) $payload['parent_id'] : null;
        $groupType = strtoupper(trim((string) ($payload['group_type'] ?? 'EXPENSES')));
        $nature = strtoupper(trim((string) ($payload['nature'] ?? '')));
        $description = trim((string) ($payload['description'] ?? ''));
        $isActive = (int) ($payload['is_active'] ?? 1);

        if ($name === '') {
            self::json(['ok' => false, 'error' => 'Group name is required.'], 400);
            return;
        }

        if (!in_array($groupType, ['ASSETS', 'LIABILITIES', 'CAPITAL', 'INCOME', 'EXPENSES'], true)) {
            self::json(['ok' => false, 'error' => 'Invalid group type.'], 400);
            return;
        }

        if ($code === '') {
            $code = strtoupper(preg_replace('/[^A-Z0-9]+/', '_', $name));
            $code = trim($code, '_');
            if (strlen($code) > 40) {
                $code = substr($code, 0, 40);
            }
        }

        if (strlen($code) > 40) {
            self::json(['ok' => false, 'error' => 'Group code must be at most 40 characters.'], 400);
            return;
        }

        if (AccountGroupRepository::getByCode($pdo, $code)) {
            self::json(['ok' => false, 'error' => 'Group code already exists.'], 400);
            return;
        }

        if ($parentId !== null && $parentId > 0) {
            $parent = AccountGroupRepository::getById($pdo, $parentId);
            if (!$parent) {
                self::json(['ok' => false, 'error' => 'Parent group not found.'], 400);
                return;
            }
            $groupType = (string) ($parent['group_type'] ?? $groupType);
        }

        if ($nature === '') {
            $nature = in_array($groupType, ['LIABILITIES', 'CAPITAL', 'INCOME'], true) ? 'CREDIT' : 'DEBIT';
        }

        if (!in_array($nature, ['DEBIT', 'CREDIT'], true)) {
            self::json(['ok' => false, 'error' => 'Invalid nature.'], 400);
            return;
        }

        $group = AccountGroupRepository::create($pdo, [
            'group_code' => $code,
            'group_name' => $name,
            'parent_id' => $parentId,
            'group_type' => $groupType,
            'nature' => $nature,
            'description' => $description !== '' ? $description : null,
            'is_active' => $isActive === 1 ? 1 : 0,
            'is_primary' => $parentId === null ? 1 : 0,
            'is_system' => 0,
            'sort_order' => (int) ($payload['sort_order'] ?? 500),
        ]);

        if (empty($group['id'])) {
            self::json(['ok' => false, 'error' => 'Group was saved but could not be loaded.'], 500);
            return;
        }

        self::json(['ok' => true, 'success' => true, 'message' => 'Account group created successfully.', 'data' => $group]);
    }

    private static function seedDefaultAccountGroups(PDO $pdo, bool $isPost): void
    {
        if (!$isPost) {
            self::json(['ok' => false, 'error' => 'POST required'], 405);
            return;
        }

        $created = AccountGroupRepository::ensureDefaultGroups($pdo);
        try {
            AccountingSchemaRepository::syncStandardAccountGroups($pdo);
        } catch (Throwable $e) {
            /* non-fatal */
        }
        $groups = AccountGroupRepository::listGroups($pdo);
        if ($groups === []) {
            self::json(['ok' => false, 'error' => 'Unable to create default account groups.'], 500);
            return;
        }

        self::json([
            'ok' => true,
            'data' => $groups,
            'created' => $created,
            'message' => $created > 0 ? 'Default account groups created.' : 'Account groups are already available.',
        ]);
    }

    private static function dayBook(PDO $pdo): void
    {
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-t');
        $voucherType = $_GET['voucher_type'] ?? null;

        $entries = LedgerEntryRepository::getDayBook($pdo, $fromDate, $toDate, $voucherType);
<<<<<<< HEAD
        $summary = LedgerEntryRepository::getDayBookSummary($pdo, $fromDate, $toDate, $voucherType);
        self::json(['ok' => true, 'data' => $entries, 'summary' => $summary]);
=======
        self::json(['ok' => true, 'data' => $entries]);
>>>>>>> dc21f8bb723e0a4ca5ce083e8c8d33eaaf2af947
    }

    private static function ledger(PDO $pdo): void
    {
        $accountId = (int) ($_GET['account_id'] ?? 0);
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        if ($accountId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid account id'], 400);
            return;
        }

        $ledger = AccountRepository::getLedger($pdo, $accountId, $fromDate, $toDate);
        $customerLink = CustomerLedgerRepository::getByAccountId($pdo, $accountId);
        self::json(['ok' => true, 'data' => $ledger, 'customer' => $customerLink]);
    }

    private static function listCustomerLedgers(PDO $pdo): void
    {
        $filters = [
            'q' => $_GET['q'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];
        $rows = CustomerLedgerRepository::listWithStats($pdo, $filters);
        self::json(['ok' => true, 'data' => $rows]);
    }

    private static function getCustomerLedger(PDO $pdo): void
    {
        $customerId = (int) ($_GET['customer_id'] ?? 0);
        if ($customerId <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid customer id'], 400);
            return;
        }

        $row = CustomerLedgerRepository::getByCustomerId($pdo, $customerId);
        if (!$row) {
            self::json(['ok' => false, 'error' => 'Customer ledger not found'], 404);
            return;
        }

        $stats = CustomerLedgerRepository::getCustomerStats($pdo, $customerId, (int) $row['account_id']);
        self::json(['ok' => true, 'data' => array_merge($row, $stats)]);
    }

    private static function trialBalance(PDO $pdo): void
    {
        $asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
        $trialBalance = LedgerEntryRepository::getTrialBalance($pdo, $asOfDate);
        self::json(['ok' => true, 'data' => $trialBalance]);
    }

    private static function profitLoss(PDO $pdo): void
    {
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-t');
        $profitLoss = LedgerEntryRepository::getProfitLoss($pdo, $fromDate, $toDate);
        self::json(['ok' => true, 'data' => $profitLoss]);
    }

    private static function balanceSheet(PDO $pdo): void
    {
        $asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
        $balanceSheet = LedgerEntryRepository::getBalanceSheet($pdo, $asOfDate);
        self::json(['ok' => true, 'data' => $balanceSheet]);
    }

    private static function dashboard(PDO $pdo): void
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $prevMonthStart = date('Y-m-01', strtotime('first day of last month'));
        $prevMonthEnd = date('Y-m-t', strtotime('last day of last month'));

        $cashBalance = self::groupCodeBalanceSum($pdo, 'CASH', $today);
        if (abs($cashBalance) < 0.0001) {
            $cashBalance = self::accountCodeBalance($pdo, 'CASH_MAIN', $today);
        }

        $bankBalance = self::groupCodeBalanceSum($pdo, 'BANK', $today);
        if (abs($bankBalance) < 0.0001) {
            $bankBalance = self::accountCodeBalance($pdo, 'BANK_MAIN', $today);
        }

        $receivable = self::groupCodeBalanceSum($pdo, 'SUNDRY_DEBTORS', $today);
        $payable = self::groupCodeBalanceSum($pdo, 'SUNDRY_CREDITORS', $today);

        $profitLoss = LedgerEntryRepository::getProfitLoss($pdo, $monthStart, $monthEnd);
        $profitLossPrev = LedgerEntryRepository::getProfitLoss($pdo, $prevMonthStart, $prevMonthEnd);

        $pendingSt = $pdo->prepare("SELECT COUNT(*) FROM vouchers WHERE status = 'DRAFT' AND deleted_at IS NULL");
        $pendingSt->execute();
        $pendingDrafts = (int) ($pendingSt->fetchColumn() ?: 0);

        $recent = AccountingVoucherRepository::listVouchers($pdo, [], 1, 20);

        self::json([
            'ok' => true,
            'success' => true,
            'data' => [
                'cash_balance' => $cashBalance,
                'bank_balance' => $bankBalance,
                'accounts_receivable' => max(0, $receivable),
                'accounts_payable' => max(0, abs($payable)),
                'revenue_mtd' => (float) ($profitLoss['total_income'] ?? 0),
                'expenses_mtd' => (float) ($profitLoss['total_expenses'] ?? 0),
                'net_profit_mtd' => (float) ($profitLoss['net_profit'] ?? 0),
                'revenue_prev_month' => (float) ($profitLossPrev['total_income'] ?? 0),
                'expenses_prev_month' => (float) ($profitLossPrev['total_expenses'] ?? 0),
                'pending_drafts' => $pendingDrafts,
                'recent_vouchers' => $recent['rows'] ?? [],
                'monthly_trend' => self::monthlyTrend($pdo, 12),
                'generated_at' => date('c'),
            ],
        ]);
    }

    /** @return list<array{label:string,revenue:float,expenses:float,profit:float}> */
    private static function monthlyTrend(PDO $pdo, int $months = 12): array
    {
        $points = [];
        $months = max(1, min(24, $months));
        for ($i = $months - 1; $i >= 0; $i--) {
            $ts = strtotime(date('Y-m-01') . " -{$i} months");
            $from = date('Y-m-01', $ts);
            $to = date('Y-m-t', $ts);
            $pl = LedgerEntryRepository::getProfitLoss($pdo, $from, $to);
            $revenue = (float) ($pl['total_income'] ?? 0);
            $expenses = (float) ($pl['total_expenses'] ?? 0);
            $points[] = [
                'label' => date('M Y', $ts),
                'revenue' => $revenue,
                'expenses' => $expenses,
                'profit' => $revenue - $expenses,
            ];
        }

        return $points;
    }

    private static function groupCodeBalanceSum(PDO $pdo, string $groupCode, string $asOfDate): float
    {
        $group = AccountGroupRepository::getByCode($pdo, $groupCode);
        if (!$group) {
            return 0.0;
        }

        $accounts = AccountRepository::listByGroup($pdo, (int) $group['id']);
        $sum = 0.0;
        foreach ($accounts as $account) {
            $sum += AccountRepository::getBalance($pdo, (int) $account['id'], $asOfDate);
        }

        return $sum;
    }

    private static function accountCodeBalance(PDO $pdo, string $code, string $asOfDate): float
    {
        $account = AccountRepository::getByCode($pdo, $code);
        if (!$account) {
            return 0.0;
        }

        return AccountRepository::getBalance($pdo, (int) $account['id'], $asOfDate);
    }

    private static function groupTypeBalanceSum(PDO $pdo, string $groupCode, string $asOfDate): float
    {
        return self::groupCodeBalanceSum($pdo, $groupCode, $asOfDate);
    }

    private static function getAccount(PDO $pdo): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid account id'], 400);
            return;
        }

        $account = AccountRepository::getById($pdo, $id);
        if (!$account) {
            self::json(['ok' => false, 'error' => 'Account not found'], 404);
            return;
        }

        self::json(['ok' => true, 'data' => $account]);
    }

    private static function saveAccount(PDO $pdo, bool $isPost): void
    {
        if (!$isPost) {
            self::json(['ok' => false, 'success' => false, 'message' => 'POST required', 'errors' => []], 405);
            return;
        }

        $payload = $_POST;
        $id = (int) ($payload['id'] ?? 0);
        $userId = Auth::user()['id'] ?? null;
        $groupId = (int) ($payload['account_group_id'] ?? 0);

        $errors = AccountRepository::validateAccountData($pdo, $payload, $id > 0 ? $id : null);
        if ($errors !== []) {
            self::json([
                'ok' => false,
                'success' => false,
                'message' => reset($errors) ?: 'Validation failed.',
                'errors' => $errors,
            ], 400);
            return;
        }

        $group = AccountGroupRepository::getById($pdo, $groupId);
        $balanceType = strtoupper(trim((string) ($payload['opening_balance_type'] ?? '')));
        if ($balanceType === '' && $group) {
            $balanceType = (string) ($group['nature'] ?? 'DEBIT');
        }
        if (!in_array($balanceType, ['DEBIT', 'CREDIT'], true)) {
            $balanceType = 'DEBIT';
        }

        $accountData = [
            'account_name' => trim((string) ($payload['account_name'] ?? '')),
            'account_group_id' => $groupId,
            'opening_balance' => (float) ($payload['opening_balance'] ?? 0),
            'opening_balance_type' => $balanceType,
            'is_active' => (int) ($payload['is_active'] ?? 1),
        ];

        try {
            if ($id > 0) {
                $existing = AccountRepository::getById($pdo, $id);
                if (!$existing) {
                    self::json(['ok' => false, 'success' => false, 'message' => 'Account not found.', 'errors' => []], 404);
                    return;
                }
                $account = AccountRepository::update($pdo, $id, $accountData);
                $message = 'Account updated successfully.';
            } else {
                $codeMode = strtolower(trim((string) ($payload['code_mode'] ?? 'auto')));
                if (!in_array($codeMode, ['auto', 'manual'], true)) {
                    $codeMode = 'auto';
                }
                $submittedCode = trim((string) ($payload['account_code'] ?? ''));
                if ($codeMode === 'auto' || $submittedCode === '') {
                    $accountData['account_code'] = AccountRepository::generateNextCode($pdo, $groupId);
                } else {
                    $accountData['account_code'] = $submittedCode;
                }
                $accountData['created_by'] = $userId;
                $account = AccountRepository::create($pdo, $accountData);
                $message = 'Account created successfully.';
            }

            self::json(['ok' => true, 'success' => true, 'message' => $message, 'data' => $account, 'errors' => []]);
        } catch (PDOException $e) {
            error_log('[AccountingController] save_account SQL: ' . $e->getMessage());
            self::json([
                'ok' => false,
                'success' => false,
                'message' => 'Database error.',
                'error' => 'Database error.',
                'errors' => [],
            ], 500);
        }
    }

    private static function deleteAccount(PDO $pdo, bool $isPost): void
    {
        if (!$isPost) {
            self::json(['ok' => false, 'success' => false, 'message' => 'POST required', 'errors' => []], 405);
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            self::json(['ok' => false, 'success' => false, 'message' => 'Invalid account id.', 'errors' => []], 400);
            return;
        }

        try {
            AccountRepository::delete($pdo, $id, Auth::user()['id'] ?? null);
        } catch (RuntimeException $e) {
            self::json(['ok' => false, 'success' => false, 'message' => $e->getMessage(), 'errors' => []], 400);
            return;
        }

        self::json(['ok' => true, 'success' => true, 'message' => 'Account deleted successfully.', 'errors' => []]);
    }

    private static function cashBook(PDO $pdo): void
    {
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-t');
        $account = AccountRepository::getByCode($pdo, 'CASH_MAIN');
        if (!$account) {
            self::json(['ok' => true, 'data' => []]);
            return;
        }

        $entries = LedgerEntryRepository::getCashBook($pdo, $fromDate, $toDate, (int) $account['id']);
        self::json(['ok' => true, 'data' => $entries]);
    }

    private static function bankBook(PDO $pdo): void
    {
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-t');
        $accountId = (int) ($_GET['account_id'] ?? 0);
        if ($accountId <= 0) {
            $account = AccountRepository::getByCode($pdo, 'BANK_MAIN');
            $accountId = $account ? (int) $account['id'] : 0;
        }
        if ($accountId <= 0) {
            self::json(['ok' => true, 'data' => []]);
            return;
        }

        $entries = LedgerEntryRepository::getCashBook($pdo, $fromDate, $toDate, $accountId);
        self::json(['ok' => true, 'data' => $entries]);
    }

    private static function groupTree(PDO $pdo): void
    {
        self::json([
            'ok' => true,
            'data' => [
                'groups' => AccountGroupRepository::getTree($pdo),
                'accounts' => AccountRepository::listAccounts($pdo, true),
            ],
        ]);
    }

    private static function paymentModeSettings(PDO $pdo): void
    {
        self::json([
            'ok' => true,
            'data' => AccountingPaymentModeSettingsRepository::getAll($pdo),
        ]);
    }

    private static function savePaymentModeSettings(PDO $pdo, bool $isPost): void
    {
        if (!$isPost) {
            self::json(['ok' => false, 'error' => 'POST required'], 405);
            return;
        }

        $mappings = $_POST['mappings'] ?? [];
        if (!is_array($mappings) || empty($mappings)) {
            self::json(['ok' => false, 'error' => 'Payment mode mappings are required'], 400);
            return;
        }

        try {
            $saved = AccountingPaymentModeSettingsRepository::saveMappings($pdo, $mappings);
            self::json(['ok' => true, 'data' => $saved, 'message' => 'Payment mode accounts saved.']);
        } catch (InvalidArgumentException $e) {
            self::json(['ok' => false, 'error' => $e->getMessage()], 400);
        }
    }
}
