<?php

declare(strict_types=1);

/**
 * JSON API for Expenses module (exp_action parameter).
 */
class ExpenseApi
{
    public static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function dispatch(PDO $pdo, array $user, bool $isAdmin): void
    {
        $action = $_GET['exp_action'] ?? $_POST['exp_action'] ?? '';
        if ($action === '') {
            self::json(['success' => false, 'message' => 'Missing exp_action'], 400);

            return;
        }

        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        if ($isPost && !Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
            self::json(['success' => false, 'message' => 'Invalid CSRF token.'], 400);

            return;
        }

        ExpenseSchemaRepository::ensureSchema($pdo);
        $userId = (int) ($user['id'] ?? 0);

        try {
            switch ($action) {
                case 'boot':
                    self::boot($pdo, $isAdmin);
                    break;
                case 'list':
                    self::list($pdo);
                    break;
                case 'stats':
                    self::stats($pdo);
                    break;
                case 'get':
                    self::get($pdo);
                    break;
                case 'save':
                    if (!$isPost) {
                        self::json(['success' => false, 'message' => 'POST required'], 405);
                        break;
                    }
                    self::save($pdo, $userId);
                    break;
                case 'delete':
                    if (!$isPost) {
                        self::json(['success' => false, 'message' => 'POST required'], 405);
                        break;
                    }
                    self::delete($pdo);
                    break;
                case 'approve':
                    if (!$isPost) {
                        self::json(['success' => false, 'message' => 'POST required'], 405);
                        break;
                    }
                    if (!$isAdmin) {
                        self::json(['success' => false, 'message' => 'Forbidden'], 403);
                        break;
                    }
                    self::approve($pdo, $userId);
                    break;
                case 'reject':
                    if (!$isPost) {
                        self::json(['success' => false, 'message' => 'POST required'], 405);
                        break;
                    }
                    if (!$isAdmin) {
                        self::json(['success' => false, 'message' => 'Forbidden'], 403);
                        break;
                    }
                    self::reject($pdo, $userId);
                    break;
                case 'cancel':
                    if (!$isPost) {
                        self::json(['success' => false, 'message' => 'POST required'], 405);
                        break;
                    }
                    self::cancel($pdo, $userId);
                    break;
                case 'settle':
                    if (!$isPost) {
                        self::json(['success' => false, 'message' => 'POST required'], 405);
                        break;
                    }
                    self::settle($pdo, $userId);
                    break;
                case 'categories':
                    self::categories($pdo);
                    break;
                case 'category_save':
                    if (!$isPost) {
                        self::json(['success' => false, 'message' => 'POST required'], 405);
                        break;
                    }
                    self::categorySave($pdo);
                    break;
                case 'category_delete':
                    if (!$isPost) {
                        self::json(['success' => false, 'message' => 'POST required'], 405);
                        break;
                    }
                    self::categoryDelete($pdo);
                    break;
                case 'suppliers':
                    self::suppliers($pdo);
                    break;
                case 'accounts':
                    self::accounts($pdo);
                    break;
                case 'export_csv':
                    self::exportCsv($pdo);
                    break;
                case 'next_number':
                    self::json(['success' => true, 'data' => ['expense_number' => ExpenseRepository::generateExpenseNumber($pdo)]]);
                    break;
                default:
                    self::json(['success' => false, 'message' => 'Unknown action'], 400);
            }
        } catch (InvalidArgumentException $e) {
            self::json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            self::json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private static function filtersFromRequest(): array
    {
        $src = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

        return [
            'from' => trim((string) ($src['from'] ?? date('Y-m-01'))),
            'to' => trim((string) ($src['to'] ?? date('Y-m-d'))),
            'branch_id' => (int) ($src['branch_id'] ?? 0) ?: null,
            'category_id' => (int) ($src['category_id'] ?? 0) ?: null,
            'supplier_id' => (int) ($src['supplier_id'] ?? 0) ?: null,
            'payment_method' => trim((string) ($src['payment_method'] ?? $src['mode'] ?? '')),
            'status' => trim((string) ($src['status'] ?? '')),
            'approval' => trim((string) ($src['approval'] ?? $src['approved'] ?? '')),
            'credit_status' => trim((string) ($src['credit_status'] ?? '')),
            'q' => trim((string) ($src['q'] ?? $src['notes'] ?? '')),
            'amount_min' => $src['amount_min'] ?? null,
            'amount_max' => $src['amount_max'] ?? null,
        ];
    }

    private static function boot(PDO $pdo, bool $isAdmin): void
    {
        self::json([
            'success' => true,
            'data' => [
                'branches' => BranchRepository::forFilters($pdo),
                'categories' => ExpenseCategoryRepository::listActive($pdo),
                'suppliers' => ExpenseRepository::listSuppliers($pdo),
                'expense_accounts' => ExpenseRepository::expenseAccounts($pdo),
                'payment_methods' => ExpenseRepository::paymentMethods(),
                'statuses' => ExpenseRepository::statuses(),
                'is_admin' => $isAdmin,
                'csrf_token' => Helpers::csrfToken(),
                'currency' => Helpers::currencyJsConfig(),
            ],
        ]);
    }

    private static function list(PDO $pdo): void
    {
        $page = max(1, (int) ($_GET['page_num'] ?? $_GET['page'] ?? 1));
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 25)));
        $sort = trim((string) ($_GET['sort'] ?? 'expense_date'));
        $dir = strtolower((string) ($_GET['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $result = ExpenseRepository::list($pdo, self::filtersFromRequest(), $page, $limit);
        self::json(['success' => true, 'data' => $result, 'sort' => $sort, 'dir' => $dir]);
    }

    private static function stats(PDO $pdo): void
    {
        $stats = ExpenseRepository::stats($pdo, self::filtersFromRequest());
        self::json(['success' => true, 'data' => $stats]);
    }

    private static function get(PDO $pdo): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            self::json(['success' => false, 'message' => 'Invalid id'], 400);

            return;
        }
        $row = ExpenseRepository::getById($pdo, $id);
        if (!$row) {
            self::json(['success' => false, 'message' => 'Not found'], 404);

            return;
        }
        self::json(['success' => true, 'data' => $row]);
    }

    private static function save(PDO $pdo, int $userId): void
    {
        $data = $_POST;
        $row = ExpenseRepository::save($pdo, $data, $userId);
        self::json(['success' => true, 'message' => 'Expense saved.', 'data' => $row]);
    }

    private static function delete(PDO $pdo): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            self::json(['success' => false, 'message' => 'Invalid id'], 400);

            return;
        }
        ExpenseRepository::delete($pdo, $id);
        self::json(['success' => true, 'message' => 'Expense deleted.']);
    }

    private static function approve(PDO $pdo, int $userId): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $row = ExpenseRepository::approve($pdo, $id, $userId);
        self::json(['success' => true, 'message' => 'Expense approved and posted to accounts.', 'data' => $row]);
    }

    private static function reject(PDO $pdo, int $userId): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $row = ExpenseRepository::reject($pdo, $id, $userId);
        self::json(['success' => true, 'message' => 'Expense rejected.', 'data' => $row]);
    }

    private static function cancel(PDO $pdo, int $userId): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $row = ExpenseRepository::cancel($pdo, $id, $userId);
        self::json(['success' => true, 'message' => 'Expense cancelled.', 'data' => $row]);
    }

    private static function settle(PDO $pdo, int $userId): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $amount = (float) ($_POST['pay_amount'] ?? $_POST['amount'] ?? 0);
        $notes = trim((string) ($_POST['pay_notes'] ?? $_POST['notes'] ?? ''));
        $row = ExpenseRepository::settle($pdo, $id, $amount, $userId, $notes);
        self::json(['success' => true, 'message' => 'Payment recorded.', 'data' => $row]);
    }

    private static function categories(PDO $pdo): void
    {
        self::json(['success' => true, 'data' => ExpenseCategoryRepository::listActive($pdo, true)]);
    }

    private static function categorySave(PDO $pdo): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $payload = [
            'name' => $_POST['name'] ?? '',
            'account_id' => $_POST['account_id'] ?? null,
            'is_active' => $_POST['is_active'] ?? 1,
            'sort_order' => $_POST['sort_order'] ?? 500,
        ];
        if ($id > 0) {
            $row = ExpenseCategoryRepository::update($pdo, $id, $payload);
        } else {
            $payload['code'] = $_POST['code'] ?? $_POST['name'] ?? '';
            $row = ExpenseCategoryRepository::create($pdo, $payload);
        }
        self::json(['success' => true, 'message' => 'Category saved.', 'data' => $row]);
    }

    private static function categoryDelete(PDO $pdo): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        ExpenseCategoryRepository::delete($pdo, $id);
        self::json(['success' => true, 'message' => 'Category deleted.']);
    }

    private static function suppliers(PDO $pdo): void
    {
        $branchId = (int) ($_GET['branch_id'] ?? 0) ?: null;
        self::json(['success' => true, 'data' => ExpenseRepository::listSuppliers($pdo, $branchId)]);
    }

    private static function accounts(PDO $pdo): void
    {
        self::json(['success' => true, 'data' => ExpenseRepository::expenseAccounts($pdo)]);
    }

    private static function exportCsv(PDO $pdo): void
    {
        $result = ExpenseRepository::list($pdo, self::filtersFromRequest(), 1, 5000);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="expenses_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fputcsv($out, ['Expense No', 'Date', 'Category', 'Supplier', 'Branch', 'Amount', 'Paid', 'Balance', 'Payment', 'Status', 'Reference', 'Notes']);
        foreach ($result['rows'] as $row) {
            fputcsv($out, [
                $row['expense_number'] ?? '',
                $row['expense_date'] ?? '',
                $row['category_name'] ?? '',
                $row['supplier_name'] ?? $row['credit_party'] ?? '',
                $row['branch_name'] ?? '',
                $row['total_amount'] ?? '',
                $row['paid_amount'] ?? '',
                $row['balance_amount'] ?? '',
                $row['payment_method'] ?? '',
                $row['status'] ?? '',
                $row['reference_number'] ?? '',
                $row['notes'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }
}
