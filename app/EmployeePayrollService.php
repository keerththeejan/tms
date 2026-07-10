<?php

declare(strict_types=1);

/**
 * Salary calculations for Sri Lanka payroll (EPF / ETF / tax).
 */
class EmployeePayrollService
{
    public const EPF_EMPLOYEE_RATE = 0.08;
    public const EPF_EMPLOYER_RATE = 0.12;
    public const ETF_RATE = 0.03;

    /** @param array<string,mixed> $data */
    public static function calculate(array $data): array
    {
        $basic = round((float) ($data['basic_salary'] ?? 0), 2);
        $allowance = round((float) ($data['allowance_amount'] ?? $data['allowance'] ?? 0), 2);
        $overtime = round((float) ($data['overtime_amount'] ?? 0), 2);
        $gross = $basic + $allowance + $overtime;

        $epfEmployee = array_key_exists('epf_employee', $data)
            ? round((float) $data['epf_employee'], 2)
            : round($basic * self::EPF_EMPLOYEE_RATE, 2);

        $epfEmployer = array_key_exists('epf_employer', $data)
            ? round((float) $data['epf_employer'], 2)
            : round($basic * self::EPF_EMPLOYER_RATE, 2);

        $etf = array_key_exists('etf_amount', $data) || array_key_exists('etf', $data)
            ? round((float) ($data['etf_amount'] ?? $data['etf'] ?? 0), 2)
            : round($basic * self::ETF_RATE, 2);

        $tax = round((float) ($data['tax_amount'] ?? $data['tax'] ?? 0), 2);
        $deductions = round((float) ($data['deductions'] ?? 0), 2);

        $net = round($gross - $epfEmployee - $tax - $deductions, 2);

        return [
            'basic_salary' => $basic,
            'allowance_amount' => $allowance,
            'overtime_amount' => $overtime,
            'gross_salary' => $gross,
            'epf_employee' => $epfEmployee,
            'epf_employer' => $epfEmployer,
            'etf_amount' => $etf,
            'tax_amount' => $tax,
            'deductions' => $deductions,
            'net_salary' => max(0, $net),
        ];
    }

    public static function syncMonthlyPayroll(PDO $pdo, int $employeeId, array $salary, string $monthYear): void
    {
        try {
            $pdo->query('SELECT 1 FROM employee_payroll LIMIT 1');
        } catch (Throwable $e) {
            return;
        }

        $st = $pdo->prepare('SELECT id FROM employee_payroll WHERE employee_id = ? AND month_year = ? LIMIT 1');
        $st->execute([$employeeId, $monthYear]);
        $existingId = (int) ($st->fetchColumn() ?: 0);

        if ($existingId > 0) {
            $pdo->prepare(
                'UPDATE employee_payroll SET basic_salary=?, epf_employee=?, epf_employer=?, etf=?, allowance=?, deductions=?
                 WHERE id=?'
            )->execute([
                $salary['basic_salary'],
                $salary['epf_employee'],
                $salary['epf_employer'],
                $salary['etf_amount'],
                $salary['allowance_amount'],
                $salary['deductions'] ?? 0,
                $existingId,
            ]);
        } else {
            $pdo->prepare(
                'INSERT INTO employee_payroll (employee_id, month_year, basic_salary, epf_employee, epf_employer, etf, allowance, deductions)
                 VALUES (?,?,?,?,?,?,?,?)'
            )->execute([
                $employeeId,
                $monthYear,
                $salary['basic_salary'],
                $salary['epf_employee'],
                $salary['epf_employer'],
                $salary['etf_amount'],
                $salary['allowance_amount'],
                $salary['deductions'] ?? 0,
            ]);
        }
    }
}
