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

                case 'get_account_balance':
                    self::getAccountBalance($pdo);
                    return;

                case 'list_account_groups':
                    self::listAccountGroups($pdo);
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

                default:
                    self::json(['ok' => false, 'error' => 'Unknown action'], 400);
                    return;
            }
        } catch (Throwable $e) {
            self::json(['ok' => false, 'error' => $e->getMessage()], 500);
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
        $details = $payload['details'] ?? [];

        if (empty($details)) {
            self::json(['ok' => false, 'error' => 'At least one line item is required'], 400);
            return;
        }

        if (count($details) < 2) {
            self::json(['ok' => false, 'error' => 'At least two line items are required for double-entry'], 400);
            return;
        }

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($details as $detail) {
            $debit = (float) ($detail['debit_amount'] ?? 0);
            $credit = (float) ($detail['credit_amount'] ?? 0);
            if ($debit > 0 && $credit > 0) {
                self::json(['ok' => false, 'error' => 'Each line must have either debit or credit, not both'], 400);
                return;
            }
            if ($debit <= 0 && $credit <= 0) {
                self::json(['ok' => false, 'error' => 'Each line must have a debit or credit amount'], 400);
                return;
            }
            if (empty($detail['account_id'])) {
                self::json(['ok' => false, 'error' => 'Each line must have a valid account'], 400);
                return;
            }
            $totalDebit += $debit;
            $totalCredit += $credit;
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

        if (($payload['payment_mode'] ?? '') === 'PETTY_CASH') {
            $payload['payment_mode'] = 'CASH';
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            self::json(['ok' => false, 'error' => 'Voucher must be balanced (Debit = Credit)'], 400);
            return;
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

        // Validate balance
        $totals = VoucherDetailRepository::getTotals($pdo, $id);
        if (abs($totals['difference']) > 0.01) {
            self::json(['ok' => false, 'error' => 'Voucher must be balanced (Debit = Credit)'], 400);
            return;
        }

        $pdo->beginTransaction();
        try {
            // Create ledger entries
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

            LedgerEntryRepository::createBatch($pdo, $id, $ledgerEntries);

            // Update voucher status
            $st = $pdo->prepare(
                'UPDATE vouchers SET status = ?, posted_at = CURRENT_TIMESTAMP, posted_by = ? WHERE id = ?'
            );
            $st->execute(['POSTED', Auth::user()['id'] ?? null, $id]);

            $pdo->commit();
            self::json(['ok' => true, 'data' => AccountingVoucherRepository::getById($pdo, $id)]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
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
        $accounts = AccountRepository::listAccounts($pdo);
        self::json(['ok' => true, 'data' => $accounts]);
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
        $groups = AccountGroupRepository::listGroups($pdo);
        self::json(['ok' => true, 'data' => $groups]);
    }

    private static function dayBook(PDO $pdo): void
    {
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-t');
        $voucherType = $_GET['voucher_type'] ?? null;

        $entries = LedgerEntryRepository::getDayBook($pdo, $fromDate, $toDate, $voucherType);
        self::json(['ok' => true, 'data' => $entries]);
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

        $cashBalance = self::accountCodeBalance($pdo, 'CASH_MAIN', $today);
        $bankBalance = self::accountCodeBalance($pdo, 'BANK_MAIN', $today);
        $receivable = self::groupTypeBalanceSum($pdo, 'SUNDRY_DEBTORS', $today);
        $payable = self::groupTypeBalanceSum($pdo, 'SUNDRY_CREDITORS', $today);

        $profitLoss = LedgerEntryRepository::getProfitLoss($pdo, $monthStart, $monthEnd);

        $pendingSt = $pdo->query("SELECT COUNT(*) FROM vouchers WHERE status = 'DRAFT' AND deleted_at IS NULL");
        $pendingDrafts = (int) ($pendingSt ? $pendingSt->fetchColumn() : 0);

        $recent = AccountingVoucherRepository::listVouchers($pdo, [], 1, 8);

        self::json([
            'ok' => true,
            'data' => [
                'cash_balance' => $cashBalance,
                'bank_balance' => $bankBalance,
                'accounts_receivable' => $receivable,
                'accounts_payable' => abs($payable),
                'revenue_mtd' => (float) ($profitLoss['total_income'] ?? 0),
                'expenses_mtd' => (float) ($profitLoss['total_expenses'] ?? 0),
                'net_profit_mtd' => (float) ($profitLoss['net_profit'] ?? 0),
                'pending_drafts' => $pendingDrafts,
                'recent_vouchers' => $recent['rows'] ?? [],
                'monthly_trend' => self::monthlyTrend($pdo),
            ],
        ]);
    }

    /** @return list<array{label:string,revenue:float,expenses:float}> */
    private static function monthlyTrend(PDO $pdo): array
    {
        $points = [];
        for ($i = 5; $i >= 0; $i--) {
            $ts = strtotime(date('Y-m-01') . " -{$i} months");
            $from = date('Y-m-01', $ts);
            $to = date('Y-m-t', $ts);
            $pl = LedgerEntryRepository::getProfitLoss($pdo, $from, $to);
            $points[] = [
                'label' => date('M Y', $ts),
                'revenue' => (float) ($pl['total_income'] ?? 0),
                'expenses' => (float) ($pl['total_expenses'] ?? 0),
            ];
        }

        return $points;
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
            self::json(['ok' => false, 'error' => 'POST required'], 405);
            return;
        }

        $payload = $_POST;
        $id = (int) ($payload['id'] ?? 0);
        $userId = Auth::user()['id'] ?? null;

        if ($id > 0) {
            $account = AccountRepository::update($pdo, $id, [
                'account_name' => $payload['account_name'] ?? '',
                'account_group_id' => (int) ($payload['account_group_id'] ?? 0),
                'opening_balance' => (float) ($payload['opening_balance'] ?? 0),
                'opening_balance_type' => $payload['opening_balance_type'] ?? 'DEBIT',
                'is_active' => (int) ($payload['is_active'] ?? 1),
            ]);
        } else {
            $account = AccountRepository::create($pdo, [
                'account_code' => (string) ($payload['account_code'] ?? ''),
                'account_name' => (string) ($payload['account_name'] ?? ''),
                'account_group_id' => (int) ($payload['account_group_id'] ?? 0),
                'opening_balance' => (float) ($payload['opening_balance'] ?? 0),
                'opening_balance_type' => $payload['opening_balance_type'] ?? 'DEBIT',
                'is_active' => (int) ($payload['is_active'] ?? 1),
                'created_by' => $userId,
            ]);
        }

        self::json(['ok' => true, 'data' => $account]);
    }

    private static function deleteAccount(PDO $pdo, bool $isPost): void
    {
        if (!$isPost) {
            self::json(['ok' => false, 'error' => 'POST required'], 405);
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            self::json(['ok' => false, 'error' => 'Invalid account id'], 400);
            return;
        }

        AccountRepository::delete($pdo, $id, Auth::user()['id'] ?? null);
        self::json(['ok' => true]);
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
}
