<?php

declare(strict_types=1);

/**
 * Reports data layer — single source for TMS operational & accounting reports.
 */
class ReportsRepository
{
    private static function parcelRevenueSql(): string
    {
        return "(p.status IS NULL OR p.status NOT IN ('cancelled', 'returned', 'failed'))";
    }

    /** @return array{from:string,to:string,branch_id:int,supplier_id:int} */
    public static function normalizeFilters(array $input): array
    {
        $from = Helpers::parseDateOr((string) ($input['from'] ?? ''), date('Y-m-01'));
        $to = Helpers::parseDateOr((string) ($input['to'] ?? ''), date('Y-m-d'));
        [$from, $to] = Helpers::orderDateRange($from, $to);

        return [
            'from' => $from,
            'to' => $to,
            'branch_id' => max(0, (int) ($input['branch_id'] ?? 0)),
            'supplier_id' => max(0, (int) ($input['supplier_id'] ?? 0)),
        ];
    }

    /** @param array{from:string,to:string,branch_id:int,supplier_id:int} $filters */
    public static function dashboard(PDO $pdo, array $filters): array
    {
        ExpenseSchemaRepository::ensureSchema($pdo);
        AccountingVoucherRepository::ensureSchema($pdo);

        $revenueByBranch = self::revenueByBranch($pdo, $filters);
        $parcelsBySupplier = self::parcelsBySupplier($pdo, $filters);
        $expenseSummary = self::expenseSummary($pdo, $filters);
        $summary = self::summaryCards($pdo, $filters, $revenueByBranch, $expenseSummary);
        $monthlyRevenue = self::monthlyRevenueTrend($pdo, $filters);
        $expenseBuckets = self::expenseBuckets($expenseSummary);
        $parcelRows = self::parcelReportRows($pdo, $filters, 100);
        $revenueRows = self::revenueDetailRows($pdo, $filters, 100);
        $expenseRows = self::expenseDetailRows($pdo, $filters, 100);

        return [
            'filters' => $filters,
            'summary' => $summary,
            'revenue_by_branch' => $revenueByBranch,
            'parcels_by_supplier' => $parcelsBySupplier,
            'expense_summary' => $expenseSummary,
            'expense_buckets' => $expenseBuckets,
            'monthly_revenue' => $monthlyRevenue,
            'tables' => [
                'revenue' => $revenueRows,
                'expenses' => $expenseRows,
                'suppliers' => $parcelsBySupplier,
                'parcels' => $parcelRows,
            ],
            'generated_at' => date('c'),
        ];
    }

    /**
     * Revenue by branch: GL income ledger + operational parcel freight (non-cancelled).
     *
     * @param array{from:string,to:string,branch_id:int,supplier_id:int} $filters
     * @return list<array<string,mixed>>
     */
    public static function revenueByBranch(PDO $pdo, array $filters): array
    {
        $branches = self::branchesForReport($pdo, $filters['branch_id']);
        if (!$branches) {
            return [];
        }

        $acctParams = [$filters['from'], $filters['to']];
        $acctBranchSql = '';
        if ($filters['branch_id'] > 0) {
            $acctBranchSql = ' AND COALESCE(NULLIF(le.branch_id, 0), NULLIF(v.branch_id, 0)) = ?';
            $acctParams[] = $filters['branch_id'];
        }

        $acctSql = "SELECT COALESCE(NULLIF(le.branch_id, 0), NULLIF(v.branch_id, 0)) AS branch_id,
                           SUM(le.credit_amount - le.debit_amount) AS accounting_revenue,
                           COUNT(DISTINCT v.id) AS txn_count
                    FROM ledger_entries le
                    INNER JOIN vouchers v ON v.id = le.voucher_id AND v.deleted_at IS NULL
                    INNER JOIN accounts a ON a.id = le.account_id AND a.deleted_at IS NULL
                    INNER JOIN account_groups ag ON ag.id = a.account_group_id AND ag.group_type = 'INCOME'
                    WHERE le.entry_date BETWEEN ? AND ?{$acctBranchSql}
                    GROUP BY COALESCE(NULLIF(le.branch_id, 0), NULLIF(v.branch_id, 0))";

        $acctSt = $pdo->prepare($acctSql);
        $acctSt->execute($acctParams);
        $acctMap = [];
        foreach ($acctSt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $bid = (int) ($row['branch_id'] ?? 0);
            if ($bid > 0) {
                $acctMap[$bid] = $row;
            }
        }

        $freightParams = [$filters['from'], $filters['to']];
        $freightWhere = [
            'DATE(p.created_at) BETWEEN ? AND ?',
            self::parcelRevenueSql(),
        ];
        if ($filters['branch_id'] > 0) {
            $freightWhere[] = 'p.to_branch_id = ?';
            $freightParams[] = $filters['branch_id'];
        }
        if ($filters['supplier_id'] > 0) {
            $freightWhere[] = 'p.supplier_id = ?';
            $freightParams[] = $filters['supplier_id'];
        }

        $freightSql = 'SELECT p.to_branch_id AS branch_id,
                              SUM(COALESCE(p.price, 0)) AS freight_revenue,
                              COUNT(*) AS parcel_count
                       FROM parcels p
                       WHERE ' . implode(' AND ', $freightWhere) . '
                       GROUP BY p.to_branch_id';
        $freightSt = $pdo->prepare($freightSql);
        $freightSt->execute($freightParams);
        $freightMap = [];
        foreach ($freightSt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $freightMap[(int) $row['branch_id']] = $row;
        }

        $dnParams = [$filters['from'], $filters['to']];
        $dnWhere = ['dn.delivery_date BETWEEN ? AND ?'];
        if ($filters['branch_id'] > 0) {
            $dnWhere[] = 'dn.branch_id = ?';
            $dnParams[] = $filters['branch_id'];
        }
        $dnSql = 'SELECT dn.branch_id,
                         SUM(COALESCE(dn.total_amount, 0) - COALESCE(dn.discount, 0)) AS dn_revenue,
                         COUNT(*) AS dn_count
                  FROM delivery_notes dn
                  WHERE ' . implode(' AND ', $dnWhere) . '
                  GROUP BY dn.branch_id';
        $dnSt = $pdo->prepare($dnSql);
        $dnSt->execute($dnParams);
        $dnMap = [];
        foreach ($dnSt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $dnMap[(int) $row['branch_id']] = $row;
        }

        $rows = [];
        $grandTotal = 0.0;
        foreach ($branches as $branch) {
            $bid = (int) $branch['id'];
            $acctRev = (float) ($acctMap[$bid]['accounting_revenue'] ?? 0);
            $freightRev = (float) ($freightMap[$bid]['freight_revenue'] ?? 0);
            if ($freightRev <= 0) {
                $freightRev = (float) ($dnMap[$bid]['dn_revenue'] ?? 0);
            }
            $revenue = $acctRev + $freightRev;
            $txnCount = (int) ($acctMap[$bid]['txn_count'] ?? 0)
                + (int) ($freightMap[$bid]['parcel_count'] ?? 0)
                + (int) ($dnMap[$bid]['dn_count'] ?? 0);
            $grandTotal += $revenue;
            $rows[] = [
                'branch_id' => $bid,
                'branch_name' => $branch['name'],
                'accounting_revenue' => round($acctRev, 2),
                'freight_revenue' => round($freightRev, 2),
                'revenue' => round($revenue, 2),
                'txn_count' => $txnCount,
                'percentage' => 0.0,
            ];
        }

        $knownIds = array_map(static fn (array $b): int => (int) $b['id'], $branches);
        $extraBranchIds = array_unique(array_merge(
            array_keys($acctMap),
            array_keys($freightMap),
            array_keys($dnMap)
        ));
        foreach ($extraBranchIds as $rawId) {
            $bid = (int) $rawId;
            if ($bid <= 0) {
                continue;
            }
            if (in_array($bid, $knownIds, true)) {
                continue;
            }
            if ($filters['branch_id'] > 0 && $filters['branch_id'] !== $bid) {
                continue;
            }
            $nameSt = $pdo->prepare('SELECT name FROM branches WHERE id = ? LIMIT 1');
            $nameSt->execute([$bid]);
            $branchName = (string) ($nameSt->fetchColumn() ?: ('Branch #' . $bid));

            $acctRev = (float) ($acctMap[$bid]['accounting_revenue'] ?? 0);
            $freightRev = (float) ($freightMap[$bid]['freight_revenue'] ?? 0);
            if ($freightRev <= 0) {
                $freightRev = (float) ($dnMap[$bid]['dn_revenue'] ?? 0);
            }
            $revenue = $acctRev + $freightRev;
            if ($revenue <= 0 && ($acctMap[$bid]['txn_count'] ?? 0) <= 0) {
                continue;
            }
            $txnCount = (int) ($acctMap[$bid]['txn_count'] ?? 0)
                + (int) ($freightMap[$bid]['parcel_count'] ?? 0)
                + (int) ($dnMap[$bid]['dn_count'] ?? 0);
            $grandTotal += $revenue;
            $rows[] = [
                'branch_id' => $bid,
                'branch_name' => $branchName,
                'accounting_revenue' => round($acctRev, 2),
                'freight_revenue' => round($freightRev, 2),
                'revenue' => round($revenue, 2),
                'txn_count' => $txnCount,
                'percentage' => 0.0,
            ];
        }

        $unassignedAcct = (float) ($acctMap[0]['accounting_revenue'] ?? 0);
        $unassignedFreight = (float) ($freightMap[0]['freight_revenue'] ?? 0);
        if ($unassignedFreight <= 0) {
            $unassignedFreight = (float) ($dnMap[0]['dn_revenue'] ?? 0);
        }
        $unassignedTotal = $unassignedAcct + $unassignedFreight;
        if ($unassignedTotal > 0 && $filters['branch_id'] === 0) {
            $grandTotal += $unassignedTotal;
            $rows[] = [
                'branch_id' => 0,
                'branch_name' => 'Unassigned',
                'accounting_revenue' => round($unassignedAcct, 2),
                'freight_revenue' => round($unassignedFreight, 2),
                'revenue' => round($unassignedTotal, 2),
                'txn_count' => (int) ($acctMap[0]['txn_count'] ?? 0)
                    + (int) ($freightMap[0]['parcel_count'] ?? 0)
                    + (int) ($dnMap[0]['dn_count'] ?? 0),
                'percentage' => 0.0,
            ];
        }

        if ($grandTotal > 0) {
            foreach ($rows as &$row) {
                $row['percentage'] = round(((float) $row['revenue'] / $grandTotal) * 100, 1);
            }
            unset($row);
        }

        usort($rows, static fn ($a, $b) => ((float) $b['revenue']) <=> ((float) $a['revenue']));

        return $rows;
    }

    /**
     * @param array{from:string,to:string,branch_id:int,supplier_id:int} $filters
     * @return list<array<string,mixed>>
     */
    public static function parcelsBySupplier(PDO $pdo, array $filters): array
    {
        $where = [
            'DATE(p.created_at) BETWEEN ? AND ?',
        ];
        $params = [$filters['from'], $filters['to']];

        if ($filters['supplier_id'] > 0) {
            $where[] = 'p.supplier_id = ?';
            $params[] = $filters['supplier_id'];
        }
        if ($filters['branch_id'] > 0) {
            $where[] = 'p.to_branch_id = ?';
            $params[] = $filters['branch_id'];
        }

        $sql = "SELECT COALESCE(s.id, 0) AS supplier_id,
                       COALESCE(NULLIF(TRIM(s.name), ''), 'Unassigned') AS supplier_name,
                       COUNT(*) AS parcels_count,
                       SUM(COALESCE(p.price, 0)) AS revenue,
                       SUM(CASE WHEN COALESCE(p.status, 'pending') IN ('pending', '', 'on_hold', 'in_transit', 'out_for_delivery') THEN 1 ELSE 0 END) AS pending_parcels,
                       SUM(CASE WHEN p.status = 'delivered' THEN 1 ELSE 0 END) AS delivered_parcels,
                       SUM(CASE WHEN p.status IN ('cancelled', 'returned', 'failed') THEN 1 ELSE 0 END) AS cancelled_parcels
                FROM parcels p
                LEFT JOIN suppliers s ON s.id = p.supplier_id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY COALESCE(s.id, 0), COALESCE(NULLIF(TRIM(s.name), ''), 'Unassigned')
                ORDER BY parcels_count DESC, supplier_name ASC";

        $st = $pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array{from:string,to:string,branch_id:int,supplier_id:int} $filters
     * @return list<array<string,mixed>>
     */
    public static function expenseSummary(PDO $pdo, array $filters): array
    {
        $where = [
            'e.expense_date BETWEEN ? AND ?',
            "e.status NOT IN ('cancelled', 'rejected')",
        ];
        $params = [$filters['from'], $filters['to']];

        if ($filters['branch_id'] > 0) {
            $where[] = 'e.branch_id = ?';
            $params[] = $filters['branch_id'];
        }
        if ($filters['supplier_id'] > 0) {
            $where[] = 'e.supplier_id = ?';
            $params[] = $filters['supplier_id'];
        }

        $sql = "SELECT COALESCE(c.id, 0) AS category_id,
                       COALESCE(c.code, LOWER(REPLACE(COALESCE(e.expense_type, 'miscellaneous'), ' ', '_'))) AS category_code,
                       COALESCE(c.name, COALESCE(e.expense_type, 'Miscellaneous')) AS category_name,
                       SUM(COALESCE(e.total_amount, e.amount, 0)) AS total,
                       COUNT(*) AS expense_count
                FROM expenses e
                LEFT JOIN expense_categories c ON c.id = e.category_id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY COALESCE(c.id, 0),
                         COALESCE(c.code, LOWER(REPLACE(COALESCE(e.expense_type, 'miscellaneous'), ' ', '_'))),
                         COALESCE(c.name, COALESCE(e.expense_type, 'Miscellaneous'))
                ORDER BY total DESC";

        $st = $pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param list<array<string,mixed>> $expenseSummary
     * @return list<array{label:string,total:float}>
     */
    public static function expenseBuckets(array $expenseSummary): array
    {
        $bucketMap = [
            'Fuel' => ['fuel'],
            'Transport' => ['transport'],
            'Salary' => ['staff_salary', 'salary', 'driver_salary_wages'],
            'Office' => ['office', 'stationery', 'printing', 'marketing'],
            'Maintenance' => ['maintenance', 'vehicle_repairs', 'vehicle_maintenance', 'tyres', 'vehicle_insurance'],
            'Utilities' => ['electricity', 'water', 'internet', 'telephone', 'utilities'],
            'Miscellaneous' => ['miscellaneous', 'meals', 'accommodation', 'cleaning', 'other'],
        ];

        $totals = [];
        foreach (array_keys($bucketMap) as $label) {
            $totals[$label] = 0.0;
        }

        foreach ($expenseSummary as $row) {
            $code = strtolower((string) ($row['category_code'] ?? ''));
            $amount = (float) ($row['total'] ?? 0);
            $matched = false;
            foreach ($bucketMap as $label => $codes) {
                if (in_array($code, $codes, true) || in_array(str_replace('_', '', $code), $codes, true)) {
                    $totals[$label] += $amount;
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $totals['Miscellaneous'] += $amount;
            }
        }

        $out = [];
        foreach ($totals as $label => $total) {
            if ($total > 0) {
                $out[] = ['label' => $label, 'total' => round($total, 2)];
            }
        }

        return $out;
    }

    /**
     * @param array{from:string,to:string,branch_id:int,supplier_id:int} $filters
     * @return list<array{label:string,revenue:float}>
     */
    public static function monthlyRevenueTrend(PDO $pdo, array $filters): array
    {
        $points = [];
        $rangeStart = date('Y-m-01', strtotime('-11 months'));

        $acctParams = [$rangeStart, $filters['to']];
        $acctBranchSql = '';
        if ($filters['branch_id'] > 0) {
            $acctBranchSql = ' AND COALESCE(NULLIF(le.branch_id, 0), NULLIF(v.branch_id, 0)) = ?';
            $acctParams[] = $filters['branch_id'];
        }

        $acctSql = "SELECT DATE_FORMAT(le.entry_date, '%Y-%m') AS ym,
                           SUM(le.credit_amount - le.debit_amount) AS revenue
                    FROM ledger_entries le
                    INNER JOIN vouchers v ON v.id = le.voucher_id AND v.deleted_at IS NULL
                    INNER JOIN accounts a ON a.id = le.account_id AND a.deleted_at IS NULL
                    INNER JOIN account_groups ag ON ag.id = a.account_group_id AND ag.group_type = 'INCOME'
                    WHERE le.entry_date BETWEEN ? AND ?{$acctBranchSql}
                    GROUP BY DATE_FORMAT(le.entry_date, '%Y-%m')";
        $acctSt = $pdo->prepare($acctSql);
        $acctSt->execute($acctParams);
        $acctMap = [];
        foreach ($acctSt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $acctMap[$row['ym']] = (float) ($row['revenue'] ?? 0);
        }

        $freightParams = [$rangeStart, $filters['to']];
        $freightWhere = [
            'DATE(p.created_at) BETWEEN ? AND ?',
            self::parcelRevenueSql(),
        ];
        if ($filters['branch_id'] > 0) {
            $freightWhere[] = 'p.to_branch_id = ?';
            $freightParams[] = $filters['branch_id'];
        }
        if ($filters['supplier_id'] > 0) {
            $freightWhere[] = 'p.supplier_id = ?';
            $freightParams[] = $filters['supplier_id'];
        }
        $freightSql = "SELECT DATE_FORMAT(p.created_at, '%Y-%m') AS ym,
                              SUM(COALESCE(p.price, 0)) AS revenue
                       FROM parcels p
                       WHERE " . implode(' AND ', $freightWhere) . '
                       GROUP BY DATE_FORMAT(p.created_at, \'%Y-%m\')';
        $freightSt = $pdo->prepare($freightSql);
        $freightSt->execute($freightParams);
        $freightMap = [];
        foreach ($freightSt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $freightMap[$row['ym']] = (float) ($row['revenue'] ?? 0);
        }

        $dnParams = [$rangeStart, $filters['to']];
        $dnBranchSql = '';
        if ($filters['branch_id'] > 0) {
            $dnBranchSql = ' AND dn.branch_id = ?';
            $dnParams[] = $filters['branch_id'];
        }
        $dnSql = "SELECT DATE_FORMAT(dn.delivery_date, '%Y-%m') AS ym,
                         SUM(COALESCE(dn.total_amount, 0) - COALESCE(dn.discount, 0)) AS revenue
                  FROM delivery_notes dn
                  WHERE dn.delivery_date BETWEEN ? AND ?{$dnBranchSql}
                  GROUP BY DATE_FORMAT(dn.delivery_date, '%Y-%m')";
        $dnSt = $pdo->prepare($dnSql);
        $dnSt->execute($dnParams);
        $dnMap = [];
        foreach ($dnSt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $dnMap[$row['ym']] = (float) ($row['revenue'] ?? 0);
        }

        for ($i = 11; $i >= 0; $i--) {
            $ts = strtotime(date('Y-m-01') . " -{$i} months");
            $ym = date('Y-m', $ts);
            $freight = $freightMap[$ym] ?? 0;
            if ($freight <= 0) {
                $freight = $dnMap[$ym] ?? 0;
            }
            $total = ($acctMap[$ym] ?? 0) + $freight;
            $points[] = [
                'label' => date('M Y', $ts),
                'revenue' => round($total, 2),
            ];
        }

        return $points;
    }

    /**
     * @param array{from:string,to:string,branch_id:int,supplier_id:int} $filters
     * @param list<array<string,mixed>> $revenueByBranch
     * @param list<array<string,mixed>> $expenseSummary
     * @return array<string,mixed>
     */
    public static function summaryCards(
        PDO $pdo,
        array $filters,
        array $revenueByBranch,
        array $expenseSummary
    ): array {
        $totalRevenue = 0.0;
        foreach ($revenueByBranch as $row) {
            $totalRevenue += (float) ($row['revenue'] ?? 0);
        }

        $totalExpenses = 0.0;
        foreach ($expenseSummary as $row) {
            $totalExpenses += (float) ($row['total'] ?? 0);
        }

        $acctExpParams = [$filters['from'], $filters['to']];
        $acctExpSql = "SELECT COALESCE(SUM(le.debit_amount - le.credit_amount), 0)
                       FROM ledger_entries le
                       INNER JOIN vouchers v ON v.id = le.voucher_id AND v.deleted_at IS NULL
                       INNER JOIN accounts a ON a.id = le.account_id AND a.deleted_at IS NULL
                       INNER JOIN account_groups ag ON ag.id = a.account_group_id AND ag.group_type = 'EXPENSES'
                       WHERE le.entry_date BETWEEN ? AND ?";
        if ($filters['branch_id'] > 0) {
            $acctExpSql .= ' AND COALESCE(NULLIF(le.branch_id, 0), NULLIF(v.branch_id, 0)) = ?';
            $acctExpParams[] = $filters['branch_id'];
        }
        $acctExpSt = $pdo->prepare($acctExpSql);
        $acctExpSt->execute($acctExpParams);
        $glExpenses = (float) $acctExpSt->fetchColumn();
        if ($totalExpenses <= 0 && $glExpenses > 0) {
            $totalExpenses = $glExpenses;
        }

        $parcelWhere = ['DATE(p.created_at) BETWEEN ? AND ?'];
        $parcelParams = [$filters['from'], $filters['to']];
        if ($filters['branch_id'] > 0) {
            $parcelWhere[] = 'p.to_branch_id = ?';
            $parcelParams[] = $filters['branch_id'];
        }
        if ($filters['supplier_id'] > 0) {
            $parcelWhere[] = 'p.supplier_id = ?';
            $parcelParams[] = $filters['supplier_id'];
        }

        $parcelSql = 'SELECT
            COUNT(*) AS total_parcels,
            SUM(CASE WHEN p.status = \'delivered\' THEN 1 ELSE 0 END) AS delivered,
            SUM(CASE WHEN COALESCE(p.status, \'pending\') IN (\'pending\', \'\', \'on_hold\', \'in_transit\', \'out_for_delivery\') THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN p.status IN (\'cancelled\', \'returned\', \'failed\') THEN 1 ELSE 0 END) AS cancelled
            FROM parcels p WHERE ' . implode(' AND ', $parcelWhere);
        $parcelSt = $pdo->prepare($parcelSql);
        $parcelSt->execute($parcelParams);
        $parcelStats = $parcelSt->fetch(PDO::FETCH_ASSOC) ?: [];

        $yearStart = date('Y-01-01');
        $yearFilters = array_merge($filters, ['from' => $yearStart, 'to' => $filters['to']]);
        $yearExpenses = 0.0;
        foreach (self::expenseSummary($pdo, $yearFilters) as $row) {
            $yearExpenses += (float) ($row['total'] ?? 0);
        }

        $monthStart = date('Y-m-01');
        $monthFilters = array_merge($filters, ['from' => $monthStart, 'to' => $filters['to']]);
        $monthExpenses = 0.0;
        foreach (self::expenseSummary($pdo, $monthFilters) as $row) {
            $monthExpenses += (float) ($row['total'] ?? 0);
        }

        $activeSql = 'SELECT COUNT(DISTINCT p.supplier_id) FROM parcels p
             WHERE p.supplier_id IS NOT NULL AND p.supplier_id > 0
             AND DATE(p.created_at) BETWEEN ? AND ?';
        $activeParams = [$filters['from'], $filters['to']];
        if ($filters['branch_id'] > 0) {
            $activeSql .= ' AND p.to_branch_id = ?';
            $activeParams[] = $filters['branch_id'];
        }
        $st = $pdo->prepare($activeSql);
        $st->execute($activeParams);
        $activeSuppliers = (int) $st->fetchColumn();

        $custSql = 'SELECT COUNT(DISTINCT p.customer_id) FROM parcels p
             WHERE p.customer_id IS NOT NULL AND p.customer_id > 0
             AND DATE(p.created_at) BETWEEN ? AND ?';
        $custParams = [$filters['from'], $filters['to']];
        if ($filters['branch_id'] > 0) {
            $custSql .= ' AND p.to_branch_id = ?';
            $custParams[] = $filters['branch_id'];
        }
        $st = $pdo->prepare($custSql);
        $st->execute($custParams);
        $activeCustomers = (int) $st->fetchColumn();

        return [
            'total_revenue' => round($totalRevenue, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_profit' => round($totalRevenue - $totalExpenses, 2),
            'monthly_expenses' => round($monthExpenses, 2),
            'yearly_expenses' => round($yearExpenses, 2),
            'total_parcels' => (int) ($parcelStats['total_parcels'] ?? 0),
            'delivered_parcels' => (int) ($parcelStats['delivered'] ?? 0),
            'pending_parcels' => (int) ($parcelStats['pending'] ?? 0),
            'cancelled_parcels' => (int) ($parcelStats['cancelled'] ?? 0),
            'active_suppliers' => $activeSuppliers,
            'active_customers' => $activeCustomers,
        ];
    }

    /** @param array{from:string,to:string,branch_id:int,supplier_id:int} $filters */
    public static function parcelReportRows(PDO $pdo, array $filters, int $limit = 100): array
    {
        $where = ['DATE(p.created_at) BETWEEN ? AND ?'];
        $params = [$filters['from'], $filters['to']];
        if ($filters['branch_id'] > 0) {
            $where[] = 'p.to_branch_id = ?';
            $params[] = $filters['branch_id'];
        }
        if ($filters['supplier_id'] > 0) {
            $where[] = 'p.supplier_id = ?';
            $params[] = $filters['supplier_id'];
        }

        $sql = "SELECT p.id, p.tracking_number, p.invoice_no, DATE(p.created_at) AS parcel_date,
                       COALESCE(p.status, 'pending') AS status, COALESCE(p.price, 0) AS amount,
                       c.name AS customer_name, COALESCE(s.name, '—') AS supplier_name,
                       bf.name AS from_branch, bt.name AS to_branch
                FROM parcels p
                LEFT JOIN customers c ON c.id = p.customer_id
                LEFT JOIN suppliers s ON s.id = p.supplier_id
                LEFT JOIN branches bf ON bf.id = p.from_branch_id
                LEFT JOIN branches bt ON bt.id = p.to_branch_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT " . max(1, min(500, $limit));

        $st = $pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @param array{from:string,to:string,branch_id:int,supplier_id:int} $filters */
    public static function revenueDetailRows(PDO $pdo, array $filters, int $limit = 100): array
    {
        $params = [$filters['from'], $filters['to']];
        $branchSql = '';
        if ($filters['branch_id'] > 0) {
            $branchSql = ' AND COALESCE(NULLIF(le.branch_id, 0), NULLIF(v.branch_id, 0)) = ?';
            $params[] = $filters['branch_id'];
        }

        $sql = "SELECT le.entry_date, v.voucher_number, v.voucher_type, v.reference_number,
                       a.account_name, ag.group_name,
                       (le.credit_amount - le.debit_amount) AS amount,
                       COALESCE(b.name, 'Unassigned') AS branch_name, v.status
                FROM ledger_entries le
                INNER JOIN vouchers v ON v.id = le.voucher_id AND v.deleted_at IS NULL
                INNER JOIN accounts a ON a.id = le.account_id AND a.deleted_at IS NULL
                INNER JOIN account_groups ag ON ag.id = a.account_group_id AND ag.group_type = 'INCOME'
                LEFT JOIN branches b ON b.id = COALESCE(NULLIF(le.branch_id, 0), NULLIF(v.branch_id, 0))
                WHERE le.entry_date BETWEEN ? AND ?{$branchSql}
                ORDER BY le.entry_date DESC, le.id DESC
                LIMIT " . max(1, min(500, $limit));

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $ledgerRows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $freightParams = [$filters['from'], $filters['to']];
        $freightWhere = ['DATE(p.created_at) BETWEEN ? AND ?', self::parcelRevenueSql()];
        if ($filters['branch_id'] > 0) {
            $freightWhere[] = 'p.to_branch_id = ?';
            $freightParams[] = $filters['branch_id'];
        }
        if ($filters['supplier_id'] > 0) {
            $freightWhere[] = 'p.supplier_id = ?';
            $freightParams[] = $filters['supplier_id'];
        }

        $freightSql = "SELECT DATE(p.created_at) AS entry_date,
                              CONCAT('PRC-', p.id) AS voucher_number,
                              'FREIGHT' AS voucher_type,
                              p.tracking_number AS reference_number,
                              'Parcel Freight' AS account_name,
                              'Sales Income' AS group_name,
                              COALESCE(p.price, 0) AS amount,
                              bt.name AS branch_name,
                              COALESCE(p.status, 'pending') AS status
                       FROM parcels p
                       LEFT JOIN branches bt ON bt.id = p.to_branch_id
                       WHERE " . implode(' AND ', $freightWhere) . "
                       ORDER BY p.created_at DESC
                       LIMIT " . max(1, min(500, $limit));
        $freightSt = $pdo->prepare($freightSql);
        $freightSt->execute($freightParams);
        $freightRows = $freightSt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $merged = array_merge($ledgerRows, $freightRows);
        usort($merged, static fn ($a, $b) => strcmp((string) ($b['entry_date'] ?? ''), (string) ($a['entry_date'] ?? '')));

        return array_slice($merged, 0, $limit);
    }

    /** @param array{from:string,to:string,branch_id:int,supplier_id:int} $filters */
    public static function expenseDetailRows(PDO $pdo, array $filters, int $limit = 100): array
    {
        $where = [
            'e.expense_date BETWEEN ? AND ?',
            "e.status NOT IN ('cancelled', 'rejected')",
        ];
        $params = [$filters['from'], $filters['to']];
        if ($filters['branch_id'] > 0) {
            $where[] = 'e.branch_id = ?';
            $params[] = $filters['branch_id'];
        }
        if ($filters['supplier_id'] > 0) {
            $where[] = 'e.supplier_id = ?';
            $params[] = $filters['supplier_id'];
        }

        $sql = "SELECT e.expense_date, e.expense_number, COALESCE(c.name, e.expense_type) AS category_name,
                       COALESCE(e.total_amount, e.amount) AS amount, e.status, b.name AS branch_name,
                       COALESCE(s.name, '—') AS supplier_name, e.reference_number
                FROM expenses e
                LEFT JOIN expense_categories c ON c.id = e.category_id
                LEFT JOIN branches b ON b.id = e.branch_id
                LEFT JOIN suppliers s ON s.id = e.supplier_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.expense_date DESC, e.id DESC
                LIMIT " . max(1, min(500, $limit));

        $st = $pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string,mixed>> */
    public static function bootMeta(PDO $pdo): array
    {
        ExpenseSchemaRepository::ensureSchema($pdo);

        return [
            'branches' => BranchRepository::forFilters($pdo),
            'suppliers' => $pdo->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'default_from' => date('Y-m-01'),
            'default_to' => date('Y-m-d'),
        ];
    }

    /** @return list<array{id:int,name:string}> */
    private static function branchesForReport(PDO $pdo, int $branchId): array
    {
        if ($branchId > 0) {
            $st = $pdo->prepare('SELECT id, name FROM branches WHERE id = ? AND COALESCE(is_active, 1) = 1 LIMIT 1');
            $st->execute([$branchId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);

            return $row ? [$row] : [];
        }

        return BranchRepository::forFilters($pdo);
    }

    /** @param array{from:string,to:string,branch_id:int,supplier_id:int} $filters */
    public static function exportCsv(PDO $pdo, array $filters, string $type): void
    {
        $data = self::dashboard($pdo, $filters);
        $filename = 'tms_report_' . preg_replace('/[^a-z0-9_]/', '', strtolower($type)) . '_' . $filters['from'] . '_to_' . $filters['to'] . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }

        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        switch ($type) {
            case 'revenue':
                fputcsv($out, ['Branch', 'Accounting Revenue', 'Freight Revenue', 'Total Revenue', 'Percentage', 'Transactions']);
                foreach ($data['revenue_by_branch'] as $row) {
                    fputcsv($out, [
                        $row['branch_name'],
                        $row['accounting_revenue'],
                        $row['freight_revenue'],
                        $row['revenue'],
                        $row['percentage'] . '%',
                        $row['txn_count'],
                    ]);
                }
                break;
            case 'suppliers':
                fputcsv($out, ['Supplier', 'Parcels', 'Revenue', 'Pending', 'Delivered', 'Cancelled']);
                foreach ($data['parcels_by_supplier'] as $row) {
                    fputcsv($out, [
                        $row['supplier_name'],
                        $row['parcels_count'],
                        $row['revenue'],
                        $row['pending_parcels'],
                        $row['delivered_parcels'],
                        $row['cancelled_parcels'],
                    ]);
                }
                break;
            case 'expenses':
                fputcsv($out, ['Category', 'Total', 'Count']);
                foreach ($data['expense_summary'] as $row) {
                    fputcsv($out, [$row['category_name'], $row['total'], $row['expense_count']]);
                }
                break;
            case 'parcels':
                fputcsv($out, ['Date', 'Tracking', 'Customer', 'Supplier', 'From', 'To', 'Status', 'Amount']);
                foreach ($data['tables']['parcels'] as $row) {
                    fputcsv($out, [
                        $row['parcel_date'],
                        $row['tracking_number'],
                        $row['customer_name'],
                        $row['supplier_name'],
                        $row['from_branch'],
                        $row['to_branch'],
                        $row['status'],
                        $row['amount'],
                    ]);
                }
                break;
            case 'summary':
                fputcsv($out, ['Metric', 'Value']);
                foreach ($data['summary'] as $key => $val) {
                    fputcsv($out, [ucwords(str_replace('_', ' ', $key)), $val]);
                }
                break;
            default:
                fputcsv($out, ['Error', 'Unknown export type']);
        }

        fclose($out);
    }
}
