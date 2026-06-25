<?php

declare(strict_types=1);

/**
 * JSON API for HRMS Employees (emp_action parameter).
 */
class EmployeeApi
{
    public static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function dispatch(PDO $pdo, array $user, bool $isAdmin): void
    {
        $action = $_GET['emp_action'] ?? $_POST['emp_action'] ?? '';
        if ($action === '') {
            self::json(['success' => false, 'message' => 'Missing emp_action'], 400);

            return;
        }

        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        if ($isPost && !Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
            self::json(['success' => false, 'message' => 'Invalid CSRF token.'], 400);

            return;
        }

        EmployeeSchemaRepository::ensureSchema($pdo);
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
                    self::delete($pdo, $userId);
                    break;
                case 'restore':
                    if (!$isPost) {
                        self::json(['success' => false, 'message' => 'POST required'], 405);
                        break;
                    }
                    self::restore($pdo, $userId);
                    break;
                case 'duplicate_check':
                    self::duplicateCheck($pdo);
                    break;
                case 'next_code':
                    self::json(['success' => true, 'data' => ['emp_code' => EmployeeRepository::generateEmpCode($pdo)]]);
                    break;
                case 'calc_salary':
                    self::calcSalary();
                    break;
                case 'departments':
                    self::json(['success' => true, 'data' => EmployeeRepository::listDepartments($pdo)]);
                    break;
                case 'designations':
                    self::json([
                        'success' => true,
                        'data' => EmployeeRepository::listDesignations($pdo, (int) ($_GET['department_id'] ?? 0) ?: null),
                    ]);
                    break;
                case 'export_csv':
                    self::exportCsv($pdo);
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

    /** @return array<string,mixed> */
    private static function filtersFromRequest(): array
    {
        $src = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

        return [
            'emp_code' => trim((string) ($src['emp_code'] ?? '')),
            'name' => trim((string) ($src['name'] ?? '')),
            'nic_passport' => trim((string) ($src['nic_passport'] ?? '')),
            'phone' => trim((string) ($src['phone'] ?? '')),
            'email' => trim((string) ($src['email'] ?? '')),
            'department_id' => (int) ($src['department_id'] ?? 0) ?: null,
            'designation_id' => (int) ($src['designation_id'] ?? 0) ?: null,
            'branch_id' => (int) ($src['branch_id'] ?? 0) ?: null,
            'employment_type' => trim((string) ($src['employment_type'] ?? '')),
            'status' => trim((string) ($src['status'] ?? '')),
            'gender' => trim((string) ($src['gender'] ?? '')),
            'join_from' => trim((string) ($src['join_from'] ?? '')),
            'join_to' => trim((string) ($src['join_to'] ?? '')),
            'join_date' => trim((string) ($src['join_date'] ?? '')),
            'q' => trim((string) ($src['q'] ?? '')),
        ];
    }

    private static function boot(PDO $pdo, bool $isAdmin): void
    {
        self::json([
            'success' => true,
            'data' => [
                'branches' => BranchRepository::forFilters($pdo),
                'departments' => EmployeeRepository::listDepartments($pdo),
                'designations' => EmployeeRepository::listDesignations($pdo),
                'supervisors' => EmployeeRepository::listSupervisors($pdo),
                'employment_types' => EmployeeRepository::employmentTypes(),
                'statuses' => EmployeeRepository::statuses(),
                'genders' => ['male', 'female', 'other'],
                'is_admin' => $isAdmin,
                'csrf_token' => Helpers::csrfToken(),
                'currency' => Helpers::currencyJsConfig(),
            ],
        ]);
    }

    private static function list(PDO $pdo): void
    {
        $page = max(1, (int) ($_GET['page_num'] ?? 1));
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 25)));
        $result = EmployeeRepository::list($pdo, self::filtersFromRequest(), $page, $limit);
        self::json(['success' => true, 'data' => $result, 'employees' => $result['rows'], 'count' => $result['total'], 'ok' => true]);
    }

    private static function stats(PDO $pdo): void
    {
        self::json(['success' => true, 'data' => EmployeeRepository::stats($pdo, self::filtersFromRequest())]);
    }

    private static function get(PDO $pdo): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $row = EmployeeRepository::getById($pdo, $id, !empty($_GET['include_deleted']));
        if (!$row) {
            self::json(['success' => false, 'message' => 'Not found'], 404);

            return;
        }
        self::json(['success' => true, 'data' => $row]);
    }

    private static function save(PDO $pdo, int $userId): void
    {
        $row = EmployeeRepository::save($pdo, $_POST, $userId);
        self::json(['success' => true, 'message' => 'Employee saved.', 'data' => $row, 'ok' => true]);
    }

    private static function delete(PDO $pdo, int $userId): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if (!EmployeeRepository::softDelete($pdo, $id, $userId)) {
            self::json(['success' => false, 'message' => 'Employee not found.'], 404);

            return;
        }
        self::json(['success' => true, 'message' => 'Employee archived.', 'ok' => true]);
    }

    private static function restore(PDO $pdo, int $userId): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if (!EmployeeRepository::restore($pdo, $id, $userId)) {
            self::json(['success' => false, 'message' => 'Employee not found.'], 404);

            return;
        }
        self::json(['success' => true, 'message' => 'Employee restored.', 'ok' => true]);
    }

    private static function duplicateCheck(PDO $pdo): void
    {
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $dup = EmployeeRepository::isDuplicate($pdo, array_merge($_GET, $_POST), $id);
        self::json(['success' => true, 'duplicate' => $dup !== [], 'errors' => $dup]);
    }

    private static function calcSalary(): void
    {
        $salary = EmployeePayrollService::calculate($_POST ?: $_GET);
        self::json(['success' => true, 'data' => $salary]);
    }

    private static function exportCsv(PDO $pdo): void
    {
        $result = EmployeeRepository::list($pdo, self::filtersFromRequest(), 1, 5000);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="employees_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fputcsv($out, ['Employee ID', 'Name', 'NIC', 'Phone', 'Email', 'Department', 'Designation', 'Branch', 'Salary', 'Type', 'Join Date', 'Status']);
        foreach ($result['rows'] as $row) {
            fputcsv($out, [
                $row['emp_code'] ?? '',
                $row['name'] ?? '',
                $row['nic_passport'] ?? '',
                $row['phone'] ?? $row['mobile'] ?? '',
                $row['email'] ?? '',
                $row['department_name'] ?? '',
                $row['designation_name'] ?? $row['position'] ?? '',
                $row['branch_name'] ?? '',
                $row['salary_display'] ?? '',
                $row['employment_type'] ?? '',
                $row['join_date'] ?? '',
                $row['status'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }
}
