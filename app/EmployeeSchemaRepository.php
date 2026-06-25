<?php

declare(strict_types=1);

/**
 * Idempotent schema upgrades for HRMS Employees module.
 */
class EmployeeSchemaRepository
{
    public static function ensureSchema(PDO $pdo): void
    {
        self::ensureLookupTables($pdo);
        self::ensureEmployeeColumns($pdo);
        self::ensureDocumentsTable($pdo);
        self::seedLookups($pdo);
        self::backfillEmployees($pdo);
    }

    private static function ensureLookupTables(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS hr_departments (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              code VARCHAR(40) NOT NULL,
              name VARCHAR(120) NOT NULL,
              branch_id BIGINT UNSIGNED NULL,
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              sort_order INT NOT NULL DEFAULT 0,
              created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              UNIQUE KEY uq_hr_departments_code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS hr_designations (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              code VARCHAR(40) NOT NULL,
              name VARCHAR(120) NOT NULL,
              department_id INT UNSIGNED NULL,
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              sort_order INT NOT NULL DEFAULT 0,
              created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              UNIQUE KEY uq_hr_designations_code (code),
              KEY idx_hr_designations_dept (department_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function ensureDocumentsTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS employee_documents (
              id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              employee_id BIGINT UNSIGNED NOT NULL,
              doc_type VARCHAR(40) NOT NULL DEFAULT \'other\',
              file_path VARCHAR(255) NOT NULL,
              original_name VARCHAR(255) NULL,
              uploaded_by BIGINT UNSIGNED NULL,
              created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              KEY idx_emp_docs_employee (employee_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function ensureEmployeeColumns(PDO $pdo): void
    {
        $columns = [
            'nic_passport' => 'VARCHAR(30) NULL AFTER last_name',
            'mobile' => 'VARCHAR(25) NULL AFTER phone',
            'date_of_birth' => 'DATE NULL AFTER nic_passport',
            'gender' => "VARCHAR(20) NULL AFTER date_of_birth",
            'marital_status' => 'VARCHAR(20) NULL AFTER gender',
            'nationality' => 'VARCHAR(60) NULL DEFAULT \'Sri Lankan\' AFTER marital_status',
            'blood_group' => 'VARCHAR(10) NULL AFTER nationality',
            'religion' => 'VARCHAR(50) NULL AFTER blood_group',
            'district' => 'VARCHAR(80) NULL AFTER address',
            'province' => 'VARCHAR(80) NULL AFTER district',
            'postal_code' => 'VARCHAR(20) NULL AFTER province',
            'emergency_contact' => 'VARCHAR(120) NULL AFTER email',
            'emergency_phone' => 'VARCHAR(25) NULL AFTER emergency_contact',
            'department_id' => 'INT UNSIGNED NULL AFTER position',
            'designation_id' => 'INT UNSIGNED NULL AFTER department_id',
            'job_title' => 'VARCHAR(100) NULL AFTER designation_id',
            'employment_type' => "VARCHAR(20) NOT NULL DEFAULT 'permanent' AFTER job_title",
            'supervisor_id' => 'BIGINT UNSIGNED NULL AFTER employment_type',
            'confirmation_date' => 'DATE NULL AFTER join_date',
            'photo_path' => 'VARCHAR(255) NULL AFTER status',
            'basic_salary' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER photo_path',
            'allowance_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER basic_salary',
            'overtime_rate' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER allowance_amount',
            'epf_employee' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER overtime_rate',
            'epf_employer' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER epf_employee',
            'etf_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER epf_employer',
            'tax_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER etf_amount',
            'net_salary' => 'DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER tax_amount',
            'bank_name' => 'VARCHAR(120) NULL AFTER net_salary',
            'bank_branch' => 'VARCHAR(120) NULL AFTER bank_name',
            'bank_account_no' => 'VARCHAR(40) NULL AFTER bank_branch',
            'bank_account_holder' => 'VARCHAR(120) NULL AFTER bank_account_no',
            'system_username' => 'VARCHAR(50) NULL AFTER bank_account_holder',
            'system_user_id' => 'BIGINT UNSIGNED NULL AFTER system_username',
            'remarks' => 'TEXT NULL AFTER system_user_id',
            'deleted_at' => 'DATETIME NULL AFTER updated_at',
            'created_by' => 'BIGINT UNSIGNED NULL AFTER deleted_at',
            'updated_by' => 'BIGINT UNSIGNED NULL AFTER created_by',
            'code_mode' => "VARCHAR(10) NOT NULL DEFAULT 'auto' AFTER emp_code",
        ];

        foreach ($columns as $name => $definition) {
            if (!self::columnExists($pdo, 'employees', $name)) {
                try {
                    $pdo->exec("ALTER TABLE employees ADD COLUMN {$name} {$definition}");
                } catch (Throwable $e) {
                    /* ignore */
                }
            }
        }

        self::ensureIndex($pdo, 'employees', 'idx_employees_nic', 'nic_passport', false);
        self::ensureIndex($pdo, 'employees', 'idx_employees_department', 'department_id', false);
        self::ensureIndex($pdo, 'employees', 'idx_employees_designation', 'designation_id', false);
        self::ensureIndex($pdo, 'employees', 'idx_employees_employment_type', 'employment_type', false);
        self::ensureIndex($pdo, 'employees', 'idx_employees_deleted', 'deleted_at', false);
        self::ensureIndex($pdo, 'employees', 'idx_employees_gender', 'gender', false);
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $st->execute([$table, $column]);

        return (int) $st->fetchColumn() > 0;
    }

    private static function ensureIndex(PDO $pdo, string $table, string $indexName, string $column, bool $unique): void
    {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
        );
        $st->execute([$table, $indexName]);
        if ((int) $st->fetchColumn() > 0) {
            return;
        }
        try {
            $type = $unique ? 'UNIQUE INDEX' : 'INDEX';
            $pdo->exec("CREATE {$type} {$indexName} ON {$table} ({$column})");
        } catch (Throwable $e) {
            /* ignore */
        }
    }

    private static function seedLookups(PDO $pdo): void
    {
        $depts = [
            ['operations', 'Operations', 10],
            ['finance', 'Finance', 20],
            ['hr', 'Human Resources', 30],
            ['maintenance', 'Maintenance', 40],
            ['admin', 'Administration', 50],
            ['logistics', 'Logistics', 60],
        ];
        $st = $pdo->prepare('INSERT IGNORE INTO hr_departments (code, name, sort_order) VALUES (?,?,?)');
        foreach ($depts as $d) {
            $st->execute($d);
        }

        $desigs = [
            ['driver', 'Driver', 10],
            ['manager', 'Manager', 20],
            ['clerk', 'Clerk', 30],
            ['mechanic', 'Mechanic', 40],
            ['accountant', 'Accountant', 50],
            ['supervisor', 'Supervisor', 60],
        ];
        $st2 = $pdo->prepare('INSERT IGNORE INTO hr_designations (code, name, sort_order) VALUES (?,?,?)');
        foreach ($desigs as $d) {
            $st2->execute($d);
        }
    }

    private static function backfillEmployees(PDO $pdo): void
    {
        try {
            $pdo->exec(
                "UPDATE employees SET employment_type = 'permanent'
                 WHERE employment_type IS NULL OR employment_type = ''"
            );
            $pdo->exec(
                'UPDATE employees e
                 INNER JOIN hr_designations d ON d.code = e.role
                 SET e.designation_id = d.id
                 WHERE e.designation_id IS NULL AND e.role IS NOT NULL AND e.role <> \'\''
            );
            $pdo->exec(
                "UPDATE employees SET name = TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')))
                 WHERE (name IS NULL OR TRIM(name) = '')
                   AND (COALESCE(first_name,'') <> '' OR COALESCE(last_name,'') <> '')"
            );
        } catch (Throwable $e) {
            /* optional */
        }
    }
}
