<?php

declare(strict_types=1);

class EmployeeRepository
{
    private const EMPLOYMENT_TYPES = ['permanent', 'contract', 'temporary', 'intern'];
    private const STATUSES = ['active', 'inactive', 'suspended'];
    private const GENDERS = ['male', 'female', 'other'];

    /** @return list<string> */
    public static function employmentTypes(): array
    {
        return self::EMPLOYMENT_TYPES;
    }

    /** @return list<string> */
    public static function statuses(): array
    {
        return self::STATUSES;
    }

    /** @param array<string,mixed> $get @return list<array<string,mixed>> */
    public static function fetchFiltered(PDO $pdo, array $get): array
    {
        return self::list($pdo, self::filtersFromArray($get), 1, 500)['rows'];
    }

    /** @param array<string,mixed> $filters */
    public static function list(PDO $pdo, array $filters = [], int $page = 1, int $limit = 25): array
    {
        $page = max(1, $page);
        $limit = max(1, min(200, $limit));
        $offset = ($page - 1) * $limit;

        [$where, $params] = self::buildWhere($filters);
        $whereSql = implode(' AND ', $where);

        $sql = "SELECT e.*, b.name AS branch_name,
                       d.name AS department_name, dg.name AS designation_name,
                       sup.name AS supervisor_name,
                       v.reg_number AS vehicle_no_join, v.id AS vehicle_id_join,
                       COALESCE(e.net_salary, e.basic_salary, 0) AS salary_display
                FROM employees e
                LEFT JOIN branches b ON b.id = e.branch_id
                LEFT JOIN hr_departments d ON d.id = e.department_id
                LEFT JOIN hr_designations dg ON dg.id = e.designation_id
                LEFT JOIN employees sup ON sup.id = e.supervisor_id
                LEFT JOIN vehicles v ON v.id = e.vehicle_id
                WHERE {$whereSql}
                ORDER BY e.created_at DESC, e.id DESC
                LIMIT {$limit} OFFSET {$offset}";

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            $row = self::normalizeRow($row);
        }
        unset($row);

        $countSt = $pdo->prepare("SELECT COUNT(*) FROM employees e WHERE {$whereSql}");
        $countSt->execute($params);
        $total = (int) $countSt->fetchColumn();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int) ceil($total / max(1, $limit)),
        ];
    }

    /** @param array<string,mixed> $filters */
    public static function stats(PDO $pdo, array $filters = []): array
    {
        [$where, $params] = self::buildWhere($filters);
        $whereSql = implode(' AND ', $where);
        $base = "FROM employees e WHERE {$whereSql}";
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        $sum = static function (string $extra = '', array $extraParams = []) use ($pdo, $base, $params): int {
            $sql = "SELECT COUNT(*) {$base}" . ($extra ? " AND {$extra}" : '');
            $st = $pdo->prepare($sql);
            $st->execute(array_merge($params, $extraParams));

            return (int) $st->fetchColumn();
        };

        $branchSql = "SELECT COALESCE(b.name, 'Unassigned') AS label, COUNT(*) AS total
                      FROM employees e LEFT JOIN branches b ON b.id = e.branch_id
                      WHERE {$whereSql} GROUP BY e.branch_id, b.name ORDER BY total DESC LIMIT 10";
        $branchSt = $pdo->prepare($branchSql);
        $branchSt->execute($params);

        $deptSql = "SELECT COALESCE(d.name, e.position, 'Other') AS label, COUNT(*) AS total
                    FROM employees e LEFT JOIN hr_departments d ON d.id = e.department_id
                    WHERE {$whereSql} GROUP BY label ORDER BY total DESC LIMIT 10";
        $deptSt = $pdo->prepare($deptSql);
        $deptSt->execute($params);

        return [
            'total' => $sum(),
            'active' => $sum("e.status = 'active'"),
            'inactive' => $sum("e.status = 'inactive'"),
            'suspended' => $sum("e.status = 'suspended'"),
            'new_this_month' => $sum('e.join_date >= ? AND e.join_date <= ?', [$monthStart, $today]),
            'permanent' => $sum("e.employment_type = 'permanent'"),
            'contract' => $sum("e.employment_type = 'contract'"),
            'temporary' => $sum("e.employment_type = 'temporary'"),
            'intern' => $sum("e.employment_type = 'intern'"),
            'male' => $sum("LOWER(e.gender) = 'male'"),
            'female' => $sum("LOWER(e.gender) = 'female'"),
            'by_branch' => $branchSt->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'by_department' => $deptSt->fetchAll(PDO::FETCH_ASSOC) ?: [],
        ];
    }

    /** @return array<string,mixed>|null */
    public static function getById(PDO $pdo, int $id, bool $includeDeleted = false): ?array
    {
        $sql = 'SELECT e.*, b.name AS branch_name, d.name AS department_name, dg.name AS designation_name,
                       sup.name AS supervisor_name, v.reg_number AS vehicle_no
                FROM employees e
                LEFT JOIN branches b ON b.id = e.branch_id
                LEFT JOIN hr_departments d ON d.id = e.department_id
                LEFT JOIN hr_designations dg ON dg.id = e.designation_id
                LEFT JOIN employees sup ON sup.id = e.supervisor_id
                LEFT JOIN vehicles v ON v.id = e.vehicle_id
                WHERE e.id = ?';
        if (!$includeDeleted) {
            $sql .= ' AND e.deleted_at IS NULL';
        }
        $sql .= ' LIMIT 1';

        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ? self::normalizeRow($row) : null;
    }

    public static function isDuplicate(PDO $pdo, array $data, int $excludeId = 0): array
    {
        $dup = [];
        $empCode = strtoupper(trim((string) ($data['emp_code'] ?? '')));
        if ($empCode !== '') {
            $st = $pdo->prepare('SELECT id FROM employees WHERE emp_code = ? AND id <> ? AND deleted_at IS NULL LIMIT 1');
            $st->execute([$empCode, $excludeId]);
            if ($st->fetchColumn()) {
                $dup['emp_code'] = 'Employee ID already exists.';
            }
        }
        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '') {
            $st = $pdo->prepare('SELECT id FROM employees WHERE email = ? AND id <> ? AND deleted_at IS NULL LIMIT 1');
            $st->execute([$email, $excludeId]);
            if ($st->fetchColumn()) {
                $dup['email'] = 'Email already in use.';
            }
        }
        $nic = trim((string) ($data['nic_passport'] ?? ''));
        if ($nic !== '') {
            $st = $pdo->prepare('SELECT id FROM employees WHERE nic_passport = ? AND id <> ? AND deleted_at IS NULL LIMIT 1');
            $st->execute([$nic, $excludeId]);
            if ($st->fetchColumn()) {
                $dup['nic_passport'] = 'NIC/Passport already registered.';
            }
        }

        return $dup;
    }

    /** @param array<string,mixed> $data */
    public static function validate(array $data): array
    {
        $errors = [];
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $fn = trim((string) ($data['first_name'] ?? ''));
            $ln = trim((string) ($data['last_name'] ?? ''));
            $name = trim($fn . ' ' . $ln);
        }
        if ($name === '') {
            $errors['name'] = 'Employee name is required.';
        }
        if (trim((string) ($data['position'] ?? '')) === '' && empty($data['designation_id'])) {
            $errors['position'] = 'Position or designation is required.';
        }
        if ((int) ($data['branch_id'] ?? 0) <= 0) {
            $errors['branch_id'] = 'Branch is required.';
        }
        $status = (string) ($data['status'] ?? 'active');
        if (!in_array($status, self::STATUSES, true)) {
            $errors['status'] = 'Invalid status.';
        }
        $etype = (string) ($data['employment_type'] ?? 'permanent');
        if (!in_array($etype, self::EMPLOYMENT_TYPES, true)) {
            $errors['employment_type'] = 'Invalid employment type.';
        }
        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address.';
        }

        return $errors;
    }

    /** @param array<string,mixed> $data */
    public static function save(PDO $pdo, array $data, int $userId): array
    {
        $errors = self::validate($data);
        $id = (int) ($data['id'] ?? 0);
        $dup = self::isDuplicate($pdo, $data, $id);
        $errors = array_merge($errors, $dup);
        if ($errors !== []) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $name = trim(((string) ($data['first_name'] ?? '')) . ' ' . ((string) ($data['last_name'] ?? '')));
        }

        $salary = EmployeePayrollService::calculate($data);
        $empCode = self::resolveEmpCode($pdo, $data, $id);
        $vehicleId = trim((string) ($data['vehicle_id'] ?? ''));
        $vehicleId = $vehicleId === '' ? null : (int) $vehicleId;
        if ($vehicleId !== null) {
            $chk = $pdo->prepare('SELECT 1 FROM vehicles WHERE id = ?');
            $chk->execute([$vehicleId]);
            if (!$chk->fetchColumn()) {
                $vehicleId = null;
            }
        }

        $position = trim((string) ($data['position'] ?? ''));
        if ($position === '' && !empty($data['designation_id'])) {
            $st = $pdo->prepare('SELECT name FROM hr_designations WHERE id = ?');
            $st->execute([(int) $data['designation_id']]);
            $position = (string) ($st->fetchColumn() ?: 'Staff');
        }

        $fields = [
            'emp_code' => $empCode,
            'name' => $name,
            'first_name' => trim((string) ($data['first_name'] ?? '')) ?: null,
            'last_name' => trim((string) ($data['last_name'] ?? '')) ?: null,
            'nic_passport' => trim((string) ($data['nic_passport'] ?? '')) ?: null,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'mobile' => trim((string) ($data['mobile'] ?? '')) ?: null,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'district' => trim((string) ($data['district'] ?? '')) ?: null,
            'province' => trim((string) ($data['province'] ?? '')) ?: null,
            'postal_code' => trim((string) ($data['postal_code'] ?? '')) ?: null,
            'date_of_birth' => ($data['date_of_birth'] ?? '') ?: null,
            'gender' => strtolower(trim((string) ($data['gender'] ?? ''))) ?: null,
            'marital_status' => trim((string) ($data['marital_status'] ?? '')) ?: null,
            'nationality' => trim((string) ($data['nationality'] ?? 'Sri Lankan')) ?: 'Sri Lankan',
            'blood_group' => trim((string) ($data['blood_group'] ?? '')) ?: null,
            'religion' => trim((string) ($data['religion'] ?? '')) ?: null,
            'emergency_contact' => trim((string) ($data['emergency_contact'] ?? '')) ?: null,
            'emergency_phone' => trim((string) ($data['emergency_phone'] ?? '')) ?: null,
            'position' => $position,
            'role' => trim((string) ($data['role'] ?? '')) ?: null,
            'department_id' => !empty($data['department_id']) ? (int) $data['department_id'] : null,
            'designation_id' => !empty($data['designation_id']) ? (int) $data['designation_id'] : null,
            'job_title' => trim((string) ($data['job_title'] ?? '')) ?: null,
            'employment_type' => (string) ($data['employment_type'] ?? 'permanent'),
            'supervisor_id' => !empty($data['supervisor_id']) ? (int) $data['supervisor_id'] : null,
            'license_number' => trim((string) ($data['license_number'] ?? '')) ?: null,
            'license_expiry' => ($data['license_expiry'] ?? '') ?: null,
            'vehicle_id' => $vehicleId,
            'branch_id' => (int) $data['branch_id'],
            'join_date' => ($data['join_date'] ?? '') ?: null,
            'confirmation_date' => ($data['confirmation_date'] ?? '') ?: null,
            'status' => (string) ($data['status'] ?? 'active'),
            'basic_salary' => $salary['basic_salary'],
            'allowance_amount' => $salary['allowance_amount'],
            'overtime_rate' => round((float) ($data['overtime_rate'] ?? 0), 2),
            'epf_employee' => $salary['epf_employee'],
            'epf_employer' => $salary['epf_employer'],
            'etf_amount' => $salary['etf_amount'],
            'tax_amount' => $salary['tax_amount'],
            'net_salary' => $salary['net_salary'],
            'bank_name' => trim((string) ($data['bank_name'] ?? '')) ?: null,
            'bank_branch' => trim((string) ($data['bank_branch'] ?? '')) ?: null,
            'bank_account_no' => trim((string) ($data['bank_account_no'] ?? '')) ?: null,
            'bank_account_holder' => trim((string) ($data['bank_account_holder'] ?? '')) ?: null,
            'system_username' => trim((string) ($data['system_username'] ?? '')) ?: null,
            'remarks' => trim((string) ($data['remarks'] ?? '')) ?: null,
            'code_mode' => (($data['code_mode'] ?? 'auto') === 'manual') ? 'manual' : 'auto',
        ];

        $ownTxn = !$pdo->inTransaction();
        if ($ownTxn) {
            $pdo->beginTransaction();
        }

        try {
            if ($id > 0) {
                $sets = [];
                $params = [];
                foreach ($fields as $col => $val) {
                    $sets[] = "{$col} = ?";
                    $params[] = $val;
                }
                $sets[] = 'updated_by = ?';
                $params[] = $userId;
                $params[] = $id;
                $pdo->prepare('UPDATE employees SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
            } else {
                $cols = array_keys($fields);
                $cols[] = 'created_by';
                $placeholders = implode(',', array_fill(0, count($cols), '?'));
                $vals = array_values($fields);
                $vals[] = $userId;
                $pdo->prepare(
                    'INSERT INTO employees (' . implode(',', $cols) . ') VALUES (' . $placeholders . ')'
                )->execute($vals);
                $id = (int) $pdo->lastInsertId();
            }

            if (!empty($_FILES['photo']['tmp_name']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
                $photoPath = self::storePhoto($id, $_FILES['photo']);
                if ($photoPath) {
                    $pdo->prepare('UPDATE employees SET photo_path = ? WHERE id = ?')->execute([$photoPath, $id]);
                }
            }

            try {
                CashbookRepository::ensureEmployeeAccount($pdo, $id, $name, (string) $fields['status']);
            } catch (Throwable $e) {
                /* non-fatal */
            }

            EmployeePayrollService::syncMonthlyPayroll($pdo, $id, $salary, date('Y-m'));

            if ($ownTxn) {
                $pdo->commit();
            }

            return self::getById($pdo, $id) ?: [];
        } catch (Throwable $e) {
            if ($ownTxn && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function softDelete(PDO $pdo, int $id, int $userId): bool
    {
        $st = $pdo->prepare('UPDATE employees SET deleted_at = NOW(), updated_by = ?, status = \'inactive\' WHERE id = ? AND deleted_at IS NULL');
        $st->execute([$userId, $id]);

        return $st->rowCount() > 0;
    }

    public static function restore(PDO $pdo, int $id, int $userId): bool
    {
        $st = $pdo->prepare('UPDATE employees SET deleted_at = NULL, updated_by = ?, status = \'active\' WHERE id = ?');
        $st->execute([$userId, $id]);

        return $st->rowCount() > 0;
    }

    public static function generateEmpCode(PDO $pdo): string
    {
        try {
            $mx = $pdo->query("SELECT MAX(CAST(SUBSTRING(emp_code, 4) AS UNSIGNED)) AS m FROM employees WHERE emp_code REGEXP '^EMP[0-9]+'")->fetch();
            $next = (int) ($mx['m'] ?? 0) + 1;
        } catch (Throwable $e) {
            $next = (int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn() + 1;
        }

        return 'EMP' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /** @return list<array<string,mixed>> */
    public static function listDepartments(PDO $pdo): array
    {
        return $pdo->query('SELECT * FROM hr_departments WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string,mixed>> */
    public static function listDesignations(PDO $pdo, ?int $departmentId = null): array
    {
        $sql = 'SELECT * FROM hr_designations WHERE is_active = 1';
        $params = [];
        if ($departmentId) {
            $sql .= ' AND (department_id IS NULL OR department_id = ?)';
            $params[] = $departmentId;
        }
        $sql .= ' ORDER BY sort_order, name';
        $st = $pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string,mixed>> */
    public static function listSupervisors(PDO $pdo, int $excludeId = 0): array
    {
        $sql = "SELECT id, emp_code, name FROM employees WHERE deleted_at IS NULL AND status = 'active'";
        $params = [];
        if ($excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $sql .= ' ORDER BY name ASC LIMIT 300';
        $st = $pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @param array<string,mixed> $get */
    private static function filtersFromArray(array $get): array
    {
        return [
            'emp_code' => trim((string) ($get['emp_code'] ?? '')),
            'name' => trim((string) ($get['name'] ?? '')),
            'first_name' => trim((string) ($get['first_name'] ?? '')),
            'last_name' => trim((string) ($get['last_name'] ?? '')),
            'email' => trim((string) ($get['email'] ?? '')),
            'phone' => trim((string) ($get['phone'] ?? '')),
            'nic_passport' => trim((string) ($get['nic_passport'] ?? '')),
            'address' => trim((string) ($get['address'] ?? '')),
            'position' => trim((string) ($get['position'] ?? '')),
            'role' => trim((string) ($get['role'] ?? '')),
            'department_id' => (int) ($get['department_id'] ?? 0) ?: null,
            'designation_id' => (int) ($get['designation_id'] ?? 0) ?: null,
            'employment_type' => trim((string) ($get['employment_type'] ?? '')),
            'gender' => trim((string) ($get['gender'] ?? '')),
            'license_number' => trim((string) ($get['license_number'] ?? '')),
            'license_from' => trim((string) ($get['license_from'] ?? '')),
            'license_to' => trim((string) ($get['license_to'] ?? '')),
            'vehicle' => trim((string) ($get['vehicle'] ?? '')),
            'branch_id' => (int) ($get['branch_id'] ?? 0) ?: null,
            'join_from' => trim((string) ($get['join_from'] ?? '')),
            'join_to' => trim((string) ($get['join_to'] ?? '')),
            'join_date' => trim((string) ($get['join_date'] ?? '')),
            'status' => trim((string) ($get['status'] ?? '')),
            'q' => trim((string) ($get['q'] ?? '')),
            'include_deleted' => !empty($get['include_deleted']),
        ];
    }

    /** @param array<string,mixed> $filters @return array{0:list<string>,1:list<mixed>} */
    private static function buildWhere(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if (empty($filters['include_deleted'])) {
            $where[] = 'e.deleted_at IS NULL';
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(e.name LIKE ? OR e.emp_code LIKE ? OR e.phone LIKE ? OR e.mobile LIKE ? OR e.email LIKE ? OR e.nic_passport LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like, $like, $like);
        }

        foreach (['emp_code', 'name', 'first_name', 'last_name', 'email', 'phone', 'address', 'position', 'role', 'license_number', 'nic_passport'] as $f) {
            if (!empty($filters[$f])) {
                $where[] = "e.{$f} LIKE ?";
                $params[] = '%' . $filters[$f] . '%';
            }
        }

        if (!empty($filters['branch_id'])) {
            $where[] = 'e.branch_id = ?';
            $params[] = (int) $filters['branch_id'];
        }
        if (!empty($filters['department_id'])) {
            $where[] = 'e.department_id = ?';
            $params[] = (int) $filters['department_id'];
        }
        if (!empty($filters['designation_id'])) {
            $where[] = 'e.designation_id = ?';
            $params[] = (int) $filters['designation_id'];
        }
        if (!empty($filters['employment_type'])) {
            $where[] = 'e.employment_type = ?';
            $params[] = $filters['employment_type'];
        }
        if (!empty($filters['gender'])) {
            $where[] = 'LOWER(e.gender) = ?';
            $params[] = strtolower((string) $filters['gender']);
        }
        if (!empty($filters['status'])) {
            $where[] = 'e.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['join_from'])) {
            $where[] = 'e.join_date >= ?';
            $params[] = $filters['join_from'];
        }
        if (!empty($filters['join_to'])) {
            $where[] = 'e.join_date <= ?';
            $params[] = $filters['join_to'];
        }
        if (!empty($filters['join_date'])) {
            $where[] = 'e.join_date = ?';
            $params[] = $filters['join_date'];
        }
        if (!empty($filters['license_from'])) {
            $where[] = 'e.license_expiry >= ?';
            $params[] = $filters['license_from'];
        }
        if (!empty($filters['license_to'])) {
            $where[] = 'e.license_expiry <= ?';
            $params[] = $filters['license_to'];
        }
        if (!empty($filters['vehicle'])) {
            $where[] = 'EXISTS (SELECT 1 FROM vehicles v WHERE v.id = e.vehicle_id AND COALESCE(v.reg_number, v.plate_no, v.vehicle_no, \'\') LIKE ?)';
            $params[] = '%' . $filters['vehicle'] . '%';
        }

        return [$where, $params];
    }

    /** @param array<string,mixed> $data */
    private static function resolveEmpCode(PDO $pdo, array $data, int $id): string
    {
        $code = strtoupper(trim((string) ($data['emp_code'] ?? '')));
        $mode = ($data['code_mode'] ?? 'auto') === 'manual' ? 'manual' : 'auto';

        if ($mode === 'manual' && $code !== '') {
            $dup = self::isDuplicate($pdo, ['emp_code' => $code], $id);
            if (isset($dup['emp_code'])) {
                throw new InvalidArgumentException($dup['emp_code']);
            }

            return $code;
        }

        if ($id > 0 && $code !== '') {
            return $code;
        }

        return self::generateEmpCode($pdo);
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private static function normalizeRow(array $row): array
    {
        if (trim((string) ($row['name'] ?? '')) === '') {
            $row['name'] = trim(((string) ($row['first_name'] ?? '')) . ' ' . ((string) ($row['last_name'] ?? '')));
        }
        $row['salary_display'] = (float) ($row['net_salary'] ?? $row['basic_salary'] ?? 0);
        $row['photo_url'] = !empty($row['photo_path'])
            ? Helpers::baseUrl(ltrim((string) $row['photo_path'], '/'))
            : '';

        return $row;
    }

    private static function storePhoto(int $employeeId, array $file): ?string
    {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime = (string) ($file['type'] ?? '');
        if (!in_array($mime, $allowed, true)) {
            return null;
        }
        $ext = pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION) ?: 'jpg';
        $dir = dirname(__DIR__) . '/public/uploads/employees';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = 'emp_' . $employeeId . '_' . time() . '.' . preg_replace('/[^a-z0-9]/i', '', $ext);
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }

        return 'uploads/employees/' . $filename;
    }
}
