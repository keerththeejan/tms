<?php
require_once __DIR__ . '/../app/bootstrap.php';
date_default_timezone_set('Asia/Colombo');

$page = $_GET['page'] ?? 'dashboard';

function _merge_delivery_location_options(PDO $pdo) {
    $opts = [];
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS delivery_routes (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    } catch (Throwable $e) { /* ignore */ }
    try {
        $st = $pdo->query("SELECT name FROM delivery_routes ORDER BY name");
        if ($st) { while (($v = $st->fetchColumn()) !== false) $opts[] = $v; }
    } catch (Throwable $e) { /* ignore */ }
    $stmtDl = $pdo->query("SELECT DISTINCT delivery_location FROM customers WHERE delivery_location IS NOT NULL AND TRIM(delivery_location) != '' ORDER BY delivery_location");
    if ($stmtDl) { while (($v = $stmtDl->fetchColumn()) !== false) $opts[] = $v; }
    $opts = array_values(array_unique($opts));
    sort($opts);
    return $opts;
}

/** Built-in TMS user roles (key => label). */
function _users_role_catalog(): array
{
    return [
        'admin' => 'Admin',
        'accountant' => 'Accountant',
        'cashier' => 'Cashier',
        'collector' => 'Due Collector',
        'parcel_user' => 'Parcel User',
        'staff' => 'Staff',
    ];
}

/** Roles for dropdowns: built-ins plus any custom roles already in the DB. */
function _users_roles_for_forms(PDO $pdo): array
{
    $catalog = _users_role_catalog();
    $dynamic = [];
    try {
        $rows = $pdo->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL AND role<>'' ORDER BY role")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $rk = trim((string)($r['role'] ?? ''));
            if ($rk !== '' && !isset($catalog[$rk])) {
                $dynamic[] = ['role' => $rk];
            }
        }
    } catch (Throwable $e) { /* ignore */ }
    return ['catalog' => $catalog, 'dynamic' => $dynamic];
}

function _users_count_active_admins(PDO $pdo, ?int $excludeId = null): int
{
    $sql = "SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1";
    $params = [];
    if ($excludeId !== null && $excludeId > 0) {
        $sql .= ' AND id <> ?';
        $params[] = $excludeId;
    }
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return (int)$st->fetchColumn();
}

function _users_is_last_active_admin(PDO $pdo, int $userId): bool
{
    if ($userId <= 0) {
        return false;
    }
    $st = $pdo->prepare('SELECT role, active FROM users WHERE id = ? LIMIT 1');
    $st->execute([$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row || (string)($row['role'] ?? '') !== 'admin' || (int)($row['active'] ?? 0) !== 1) {
        return false;
    }
    return _users_count_active_admins($pdo) <= 1;
}

function _users_sanitize_row_for_form(?array $userRow): array
{
    $row = is_array($userRow) ? $userRow : [];
    unset($row['password_hash']);
    return $row;
}

/** Link cash book account to new customer; failures do not block customer save. */
function _cashbook_ensure_customer_account(PDO $pdo, int $customerId, string $name): void
{
    CashbookRepository::ensureCustomerAccount($pdo, $customerId, trim($name));
}

function _cashbook_sync_customer_account_name(PDO $pdo, int $customerId, string $name): void
{
    try {
        CashbookRepository::syncCustomerAccountName($pdo, $customerId, trim($name));
    } catch (Throwable $e) {
        /* non-fatal */
    }
}

/** Link customer ledger (AR account) to new customer; failures do not block customer save. */
function _customer_ledger_ensure(PDO $pdo, int $customerId, string $name): void
{
    try {
        $userId = null;
        if (class_exists('Auth') && Auth::check()) {
            $user = Auth::user();
            $userId = isset($user['id']) ? (int) $user['id'] : null;
        }
        CustomerLedgerRepository::ensureForCustomer($pdo, $customerId, trim($name), true, $userId);
    } catch (Throwable $e) {
        /* non-fatal */
    }
}

function _customer_ledger_sync_name(PDO $pdo, int $customerId, string $name): void
{
    try {
        CustomerLedgerRepository::syncCustomerName($pdo, $customerId, trim($name));
    } catch (Throwable $e) {
        /* non-fatal */
    }
}

action_router:

// Public routes
if ($page === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid CSRF token.';
            Helpers::view('auth/login', compact('error'));
            return;
        }

        
      $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (Auth::attempt($username, $password)) {
            Helpers::redirect('index.php?page=dashboard');
        } else {
            $error = 'Invalid credentials.';
            Helpers::view('auth/login', compact('error'));
        }
    } else {
        $success = ($_GET['reset'] ?? '') === '1'
            ? 'Database reset completed successfully. All application records have been permanently removed. Run the seed script to create an admin user, then log in.'
            : null;
$seedUrl = ($_GET['reset'] ?? '') === '1' ? Helpers::baseUrl('seed_admin.php') : null;
        Helpers::view('auth/login', array_filter(compact('success', 'seedUrl')));
    }
    return;
}

if ($page === 'logout') {
    Auth::logout();
    Helpers::redirect('index.php?page=login');
}

// Protected routes
if (!Auth::check()) {
    Helpers::redirect('index.php?page=login');
}

switch ($page) {
    case 'backup':
        // Admin only
        if (!Auth::hasRole('admin')) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';
        $config = Helpers::config();
        $db = $config['db'] ?? [];
        $backupDir = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0775, true);
        }
        $error = null;
        $success = null;
        if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $ts = date('Ymd_His');
            $filename = "tms_backup_{$ts}.sql";
            $outfile = $backupDir . DIRECTORY_SEPARATOR . $filename;
            $host = (string)($db['host'] ?? '127.0.0.1');
            $port = (int)($db['port'] ?? 3306);
            $user = (string)($db['username'] ?? 'root');
            $pass = (string)($db['password'] ?? '');
            $dbname = (string)($db['database'] ?? '');
            // Optional override path in config: $config['mysqldump_path']
            $mysqldump = $config['mysqldump_path'] ?? 'mysqldump';
            // Build command
            $cmd = '"' . $mysqldump . '"'
                 . ' --host=' . escapeshellarg($host)
                 . ' --port=' . escapeshellarg((string)$port)
                 . ' --user=' . escapeshellarg($user)
                 . ' --password=' . escapeshellarg($pass)
                 . ' --routines --triggers --events '
                 . escapeshellarg($dbname)
                 . ' > ' . escapeshellarg($outfile);
            $ok = false; $output = []; $ret = 0;
            @exec($cmd, $output, $ret);
            if ($ret === 0 && file_exists($outfile) && filesize($outfile) > 0) {
                $success = 'Backup created: ' . htmlspecialchars($filename);
            } else {
                $error = 'Backup failed. Ensure mysqldump is installed and configure mysqldump_path in config.php if needed.';
            }
            // List files for view
            $files = [];
            foreach (glob($backupDir . DIRECTORY_SEPARATOR . 'tms_backup_*.sql') as $f) {
                $files[] = [
                    'name' => basename($f),
                    'size' => filesize($f),
                    'mtime' => filemtime($f),
                ];
            }
            usort($files, fn($a,$b)=>$b['mtime']<=>$a['mtime']);
            Helpers::view('admin/backup', compact('files','error','success'));
            break;
        }
        if ($action === 'download') {
            // Admin-only secure download
            $file = basename((string)($_GET['file'] ?? ''));
            if (!preg_match('/^tms_backup_\d{8}_\d{6}\.sql$/', $file)) { http_response_code(400); echo 'Invalid file.'; break; }
            $path = $backupDir . DIRECTORY_SEPARATOR . $file;
            if (!is_file($path)) { http_response_code(404); echo 'Not found'; break; }
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $file . '"');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            return;
        }
        if ($action === 'reset_data' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $wantsJson = (
                (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            );
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
                if ($wantsJson) {
                    header('Content-Type: application/json; charset=UTF-8');
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please refresh the page and try again.']);
                    break;
                }
                http_response_code(400);
                echo 'Invalid CSRF';
                break;
            }
            $confirm = trim($_POST['confirm_reset'] ?? '');
            if ($confirm !== 'DELETE') {
                if ($wantsJson) {
                    header('Content-Type: application/json; charset=UTF-8');
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Type DELETE (all caps) to confirm reset.']);
                    break;
                }
                $files = [];
                foreach (glob($backupDir . DIRECTORY_SEPARATOR . 'tms_backup_*.sql') as $f) {
                    $files[] = ['name' => basename($f), 'size' => filesize($f), 'mtime' => filemtime($f)];
                }
                usort($files, fn($a,$b)=>$b['mtime']<=>$a['mtime']);
                $error = 'Type DELETE (all caps) to confirm reset.';
                Helpers::view('admin/backup', compact('files', 'error', 'success'));
                break;
            }
            $pdo = Database::pdo();
            $result = DataReset::performFullDatabaseReset($pdo);
            if ($result['success']) {
                Auth::logout();
                if ($wantsJson) {
                    header('Content-Type: application/json; charset=UTF-8');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Database reset completed successfully.',
                        'cleared' => $result['cleared'],
                        'tables_found' => $result['tables_found'],
                        'phases' => $result['phases'],
                        'redirect' => Helpers::baseUrl('index.php?page=login&reset=1'),
                    ], JSON_UNESCAPED_UNICODE);
                    break;
                }
                Helpers::redirect('index.php?page=login&reset=1');
                return;
            }
            $errorMessages = array_merge(
                $result['errors'],
                array_map(
                    static fn ($table, $count) => "Table {$table} still has {$count} row(s)",
                    array_keys($result['verification_failures']),
                    $result['verification_failures']
                ),
                array_map(
                    static fn ($table, $count) => "Accounting table {$table} still has {$count} row(s)",
                    array_keys($result['accounting_failures']),
                    $result['accounting_failures']
                )
            );
            if ($wantsJson) {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database reset failed. Some records could not be removed.',
                    'errors' => $errorMessages,
                    'verification_failures' => $result['verification_failures'],
                    'accounting_failures' => $result['accounting_failures'],
                    'cleared' => $result['cleared'],
                ], JSON_UNESCAPED_UNICODE);
                break;
            }
            $files = [];
            foreach (glob($backupDir . DIRECTORY_SEPARATOR . 'tms_backup_*.sql') as $f) {
                $files[] = ['name' => basename($f), 'size' => filesize($f), 'mtime' => filemtime($f)];
            }
            usort($files, fn($a,$b)=>$b['mtime']<=>$a['mtime']);
            $error = 'Reset failed: ' . implode('; ', $errorMessages);
            Helpers::view('admin/backup', compact('files', 'error', 'success'));
            break;
        }
        // index: list existing backups
        $files = [];
        foreach (glob($backupDir . DIRECTORY_SEPARATOR . 'tms_backup_*.sql') as $f) {
            $files[] = [
                'name' => basename($f),
                'size' => filesize($f),
                'mtime' => filemtime($f),
            ];
        }
        usort($files, fn($a,$b)=>$b['mtime']<=>$a['mtime']);
        Helpers::view('admin/backup', compact('files', 'error', 'success'));
        break;
    case 'dashboard':
        $pdo = Database::pdo();
        $user = Auth::user();
        $branchId = (int)($user['branch_id'] ?? 0);
        $today = date('Y-m-d');
        $isMain = Auth::isMainBranch();
        // Filters â€” validated Y-m-d (invalid GET params ignored)
        $df = Helpers::parseDateOr((string)($_GET['df'] ?? ''), $today);
        $dt = Helpers::parseDateOr((string)($_GET['dt'] ?? ''), $today);
        [$df, $dt] = Helpers::orderDateRange($df, $dt);
        $fb = (int)($_GET['fb'] ?? 0); // from_branch_id
        $tb = (int)($_GET['tb'] ?? 0); // to_branch_id
        $cust = (int)($_GET['cust'] ?? 0); // customer_id

        /** Main-branch user + â€œTo branch: Allâ€ â†’ metrics and lists across all branches */
        $scopeAllBranches = $isMain && $tb === 0;
        $pendingBranchId = $tb > 0 ? $tb : $branchId;
        $isSingleDay = ($df === $dt);
        $isTodayRange = ($isSingleDay && $df === $today);

        // Pending parcels for selected/to branch (ignores date)
        if ($scopeAllBranches) {
            $pendingParcels = (int)($pdo->query("SELECT COUNT(*) AS c FROM parcels WHERE status = 'pending'")->fetch()['c'] ?? 0);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM parcels WHERE to_branch_id = ? AND status = 'pending'");
            $stmt->execute([$pendingBranchId]);
            $pendingParcels = (int)($stmt->fetch()['c'] ?? 0);
        }

        // Total payment due for selected branch (or all branches when scoped)
        $dueSql = "SELECT COALESCE(SUM((dn.total_amount - COALESCE(dn.discount,0)) - COALESCE(paid.total_paid,0)),0) AS total_due
                   FROM delivery_notes dn
                   LEFT JOIN (
                     SELECT delivery_note_id, SUM(amount) AS total_paid FROM payments GROUP BY delivery_note_id
                   ) paid ON paid.delivery_note_id = dn.id";
        if ($scopeAllBranches) {
            $totalDue = (float)($pdo->query($dueSql)->fetch()['total_due'] ?? 0);
        } else {
            $dueSql .= ' WHERE dn.branch_id = ?';
            $stmt = $pdo->prepare($dueSql);
            $stmt->execute([$pendingBranchId]);
            $totalDue = (float)($stmt->fetch()['total_due'] ?? 0);
        }

        // Parcels list for range and filters with vehicle no
        $tpSql = "SELECT p.id, p.tracking_number, p.vehicle_no, p.status, p.created_at,
                         c.name AS customer_name, bt.name AS to_branch
                  FROM parcels p
                  LEFT JOIN customers c ON c.id = p.customer_id
                  LEFT JOIN branches bt ON bt.id = p.to_branch_id
                  WHERE DATE(p.created_at) BETWEEN ? AND ?";
        $tpParams = [$df, $dt];
        if ($tb > 0) {
            $tpSql .= ' AND p.to_branch_id = ?';
            $tpParams[] = $tb;
        } elseif (!$scopeAllBranches) {
            $tpSql .= ' AND p.to_branch_id = ?';
            $tpParams[] = $branchId;
        }
        if ($fb > 0) { $tpSql .= ' AND p.from_branch_id = ?'; $tpParams[] = $fb; }
        if ($cust > 0) { $tpSql .= ' AND p.customer_id = ?'; $tpParams[] = $cust; }
        $tpSql .= ' ORDER BY p.created_at DESC, p.id DESC LIMIT 50';
        $tpStmt = $pdo->prepare($tpSql);
        $tpStmt->execute($tpParams);
        $todayParcels = $tpStmt->fetchAll();

        // Collections (payments) for range and branch scope
        $colSql = "SELECT COALESCE(SUM(p.amount),0) AS s
                   FROM payments p
                   LEFT JOIN delivery_notes dn ON dn.id = p.delivery_note_id
                   WHERE DATE(p.paid_at) BETWEEN ? AND ?";
        $colParams = [$df, $dt];
        if (!$scopeAllBranches) {
            $colSql .= ' AND dn.branch_id = ?';
            $colParams[] = $pendingBranchId;
        }
        $colStmt = $pdo->prepare($colSql);
        $colStmt->execute($colParams);
        $collectionsToday = (float)($colStmt->fetch()['s'] ?? 0);

        // Expenses for branch scope within range
        if ($scopeAllBranches) {
            $expStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS s FROM expenses WHERE expense_date BETWEEN ? AND ?');
            $expStmt->execute([$df, $dt]);
        } else {
            $expStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS s FROM expenses WHERE branch_id = ? AND expense_date BETWEEN ? AND ?');
            $expStmt->execute([$pendingBranchId, $df, $dt]);
        }
        $expensesToday = (float)($expStmt->fetch()['s'] ?? 0);

        // Recent payments list (range + branch scope)
        $rpSql = "SELECT p.id, p.amount, p.paid_at, c.name AS customer_name, c.phone AS customer_phone
                  FROM payments p
                  LEFT JOIN delivery_notes dn ON dn.id = p.delivery_note_id
                  LEFT JOIN customers c ON c.id = dn.customer_id
                  WHERE ";
        $rpParams = [];
        if (!$scopeAllBranches) {
            $rpSql .= 'dn.branch_id = ? AND ';
            $rpParams[] = $pendingBranchId;
        }
        $rpSql .= "DATE(p.paid_at) BETWEEN ? AND ?
                  ORDER BY p.paid_at DESC, p.id DESC LIMIT 10";
        $rpParams[] = $df;
        $rpParams[] = $dt;
        $rp = $pdo->prepare($rpSql);
        $rp->execute($rpParams);
        $recentPayments = $rp->fetchAll();

        // Per-branch aggregates and status stats across all branches
        $branchesAll = BranchRepository::forDashboard($pdo);
        $pendingByBranch = [];
        $dueByBranch = [];
        $todayParcelsByBranch = [];
        $collectionsTodayByBranch = [];
        $expensesTodayByBranch = [];

        // Always compute these simple branch summaries for dashboard
        $q1 = $pdo->query("SELECT to_branch_id AS branch_id, COUNT(*) AS c FROM parcels WHERE status='pending' GROUP BY to_branch_id");
        foreach ($q1->fetchAll() as $r) { $pendingByBranch[(int)$r['branch_id']] = (int)$r['c']; }
        $q2 = $pdo->prepare("SELECT dn.branch_id, COALESCE(SUM((dn.total_amount - COALESCE(dn.discount,0)) - COALESCE(paid.total_paid,0)),0) AS due
                              FROM delivery_notes dn
                              LEFT JOIN (SELECT delivery_note_id, SUM(amount) AS total_paid FROM payments GROUP BY delivery_note_id) paid
                                ON paid.delivery_note_id = dn.id
                              GROUP BY dn.branch_id");
        $q2->execute(); foreach ($q2->fetchAll() as $r) { $dueByBranch[(int)$r['branch_id']] = (float)$r['due']; }
        $q3 = $pdo->prepare("SELECT to_branch_id AS branch_id, COUNT(*) AS c FROM parcels WHERE DATE(created_at)=? GROUP BY to_branch_id");
        $q3->execute([$today]); foreach ($q3->fetchAll() as $r) { $todayParcelsByBranch[(int)$r['branch_id']] = (int)$r['c']; }
        $q4 = $pdo->prepare("SELECT dn.branch_id, COALESCE(SUM(p.amount),0) AS s FROM payments p LEFT JOIN delivery_notes dn ON dn.id=p.delivery_note_id WHERE DATE(p.paid_at)=? GROUP BY dn.branch_id");
        $q4->execute([$today]); foreach ($q4->fetchAll() as $r) { $collectionsTodayByBranch[(int)$r['branch_id']] = (float)$r['s']; }
        $q5 = $pdo->prepare('SELECT branch_id, COALESCE(SUM(amount),0) AS s FROM expenses WHERE expense_date=? GROUP BY branch_id');
        $q5->execute([$today]); foreach ($q5->fetchAll() as $r) { $expensesTodayByBranch[(int)$r['branch_id']] = (float)$r['s']; }

        // Status counts for Today, Yesterday, and Last 30 days per branch
        $statusStats = ['today'=>[], 'yesterday'=>[], 'last30'=>[]];
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $date30 = date('Y-m-d', strtotime('-30 days'));
        // helper to run a grouped count
        $grouped = $pdo->prepare("SELECT to_branch_id AS branch_id, status, COUNT(*) AS c FROM parcels WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY to_branch_id, status");
        // today
        $grouped->execute([$today, $today]);
        foreach ($grouped->fetchAll() as $r) {
            $bid = (int)$r['branch_id']; $st = (string)$r['status']; $statusStats['today'][$bid][$st] = (int)$r['c'];
        }
        // yesterday
        $grouped->execute([$yesterday, $yesterday]);
        foreach ($grouped->fetchAll() as $r) {
            $bid = (int)$r['branch_id']; $st = (string)$r['status']; $statusStats['yesterday'][$bid][$st] = (int)$r['c'];
        }
        // last30
        $grouped->execute([$date30, $today]);
        foreach ($grouped->fetchAll() as $r) {
            $bid = (int)$r['branch_id']; $st = (string)$r['status']; $statusStats['last30'][$bid][$st] = (int)$r['c'];
        }

        // Customers list for filter
        $customersAll = $pdo->query('SELECT id, name, phone FROM customers ORDER BY name LIMIT 500')->fetchAll();
        $tvSummary = ['total_vouchers' => 0, 'total_amount' => 0, 'posted_count' => 0, 'draft_count' => 0, 'cancelled_count' => 0];
        $tvRecentAuditLogs = [];
        try {
            $tvSummary = TransferVoucherRepository::summary($pdo, $df, $dt);
            $tvRecentAuditLogs = TransferVoucherRepository::recentAuditLogs($pdo, 6);
        } catch (Throwable $e) {
            $tvSummary = ['total_vouchers' => 0, 'total_amount' => 0, 'posted_count' => 0, 'draft_count' => 0, 'cancelled_count' => 0];
            $tvRecentAuditLogs = [];
        }

        // Transfer voucher statistics for today
        $tvTodaySummary = ['total_vouchers' => 0, 'total_amount' => 0, 'posted_count' => 0, 'draft_count' => 0, 'cancelled_count' => 0];
        try {
            $tvTodaySummary = TransferVoucherRepository::summary($pdo, $today, $today);
        } catch (Throwable $e) {
            $tvTodaySummary = ['total_vouchers' => 0, 'total_amount' => 0, 'posted_count' => 0, 'draft_count' => 0, 'cancelled_count' => 0];
        }
        $transfersToday = (int)($tvTodaySummary['total_vouchers'] ?? 0);
        $transfersAmount = (float)($tvTodaySummary['total_amount'] ?? 0);
        $transfersPending = (int)($tvTodaySummary['draft_count'] ?? 0);
        $transfersPosted = (int)($tvTodaySummary['posted_count'] ?? 0);

        Helpers::view('dashboard', compact(
            'pendingParcels','totalDue','todayParcels','today','collectionsToday','expensesToday','recentPayments',
            'isMain','branchesAll','pendingByBranch','dueByBranch','todayParcelsByBranch','collectionsTodayByBranch','expensesTodayByBranch',
            'df','dt','fb','tb','cust','customersAll','statusStats','tvSummary','tvRecentAuditLogs',
            'transfersToday','transfersAmount','transfersPending','transfersPosted',
            'scopeAllBranches','isSingleDay','isTodayRange'
        ));
        break;

    case 'accounts':
        Helpers::redirect('index.php?page=accounting&action=chart');
        break;

    case 'daybook':
        Helpers::redirect('index.php?page=accounting&action=daybook');
        break;

    case 'ledger':
        Helpers::redirect('index.php?page=accounting&action=ledger');
        break;

    case 'cashbook':
        Helpers::redirect('index.php?page=accounting&action=cash_book');
        break;

    case 'api_cashbook':
        if (!Auth::hasAnyRole(['admin', 'accountant'])) {
            http_response_code(403);
            echo 'Forbidden';
            break;
        }
        CashbookApi::dispatch(Database::pdo());
        break;

    case 'transfer_voucher':
        if (!Auth::hasAnyRole(['admin', 'accountant'])) {
            http_response_code(403);
            echo 'Forbidden';
            break;
        }
        $tvAction = $_GET['tv_action'] ?? $_POST['tv_action'] ?? '';
        if ($tvAction !== '') {
            TransferVoucherApi::dispatch(Database::pdo());
            break;
        }
        Helpers::redirect('index.php?page=accounting&action=entry&voucher_type=TRANSFER');
        break;

    case 'routes':
        // Page removed as requested; redirect users to Delivery Route Planning
        Helpers::redirect('index.php?page=delivery_notes&action=route');
        break;

    case 'change_password':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $action = $_GET['action'] ?? 'form';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $current = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $user = Auth::user();
            $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE id=? LIMIT 1');
            $stmt->execute([(int)$user['id']]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($current, $row['password_hash'] ?? '')) {
                $error = 'Current password is incorrect.';
                Helpers::view('users/change_password', compact('error'));
                break;
            }
            if ($new === '' || strlen($new) < 6) {
                $error = 'New password must be at least 6 characters.';
                Helpers::view('users/change_password', compact('error'));
                break;
            }
            if ($new !== $confirm) {
                $error = 'Passwords do not match.';
                Helpers::view('users/change_password', compact('error'));
                break;
            }
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $upd = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
            $upd->execute([$hash, (int)$user['id']]);
            $success = 'Password updated successfully.';
            Helpers::view('users/change_password', compact('success'));
            break;
        }
        Helpers::view('users/change_password');
        break;

    case 'settings':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        if (!Auth::hasRole('admin')) { http_response_code(403); echo 'Forbidden'; break; }
        $pdoSettings = Database::pdo();
        $branchesMaster = BranchRepository::allOrderedForAdmin($pdoSettings);
        $config = Helpers::config();
        $company = $config['company'] ?? [];
        $error = '';
        $success = '';
        $allowedSettingsTabs = ['general', 'branches', 'users', 'system'];
        $settingsActiveTab = 'general';
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $t = strtolower(trim((string)($_GET['tab'] ?? '')));
            if (in_array($t, $allowedSettingsTabs, true)) {
                $settingsActiveTab = $t;
            }
            if (!empty($_SESSION['settings_flash_success'])) {
                $success = (string)$_SESSION['settings_flash_success'];
                unset($_SESSION['settings_flash_success']);
            }
        }

        $configDir = realpath(__DIR__ . '/../config');
        $companyJsonPath = ($configDir ? ($configDir . DIRECTORY_SEPARATOR . 'company.json') : (__DIR__ . '/../config/company.json'));

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['settings_section'])) {
            $error = 'Invalid request. Please refresh the page and try again.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings_section'])) {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
                $error = 'Invalid CSRF. Please refresh the page and try again.';
            } else {
            $section = $_POST['settings_section'] ?? '';
            $rt = strtolower(trim((string)($_POST['settings_return_tab'] ?? '')));
            if (in_array($rt, $allowedSettingsTabs, true)) {
                $settingsActiveTab = $rt;
            }

            if ($section === 'general') {
                $name = trim($_POST['company_name'] ?? '');
                $regNo = trim($_POST['reg_no'] ?? '');
                $logoDisplay = in_array($_POST['logo_display'] ?? '', ['image', 'builtin'], true) ? $_POST['logo_display'] : 'builtin';
                $logoInitials = trim($_POST['logo_initials'] ?? '') ?: 'TS';
                $logoInitials = preg_replace('/[^A-Za-z0-9]/', '', $logoInitials);
                if (function_exists('mb_substr')) {
                    $logoInitials = mb_substr($logoInitials, 0, 6);
                } else {
                    $logoInitials = substr($logoInitials, 0, 6);
                }
                $logoArchColor = preg_replace('/[^0-9a-fA-F]/', '', trim($_POST['logo_arch_color'] ?? 'c00')) ?: 'c00';
                $logoBarBg = preg_replace('/[^0-9a-fA-F]/', '', trim($_POST['logo_bar_bg'] ?? '000')) ?: '000';
                $logoBarColor = preg_replace('/[^0-9a-fA-F]/', '', trim($_POST['logo_bar_color'] ?? 'fff')) ?: 'fff';
                $logoTitleColor = preg_replace('/[^0-9a-fA-F]/', '', trim($_POST['logo_title_color'] ?? 'c00')) ?: 'c00';
                $logoUrl = trim($_POST['logo_url'] ?? '');
                $removeLogo = (string)($_POST['remove_logo'] ?? '') === '1';
                $footerNote = trim($_POST['footer_note'] ?? '');
                $routePart1 = trim($_POST['route_1'] ?? '');
                $routePart2 = trim($_POST['route_2'] ?? '');
                $routePart3 = trim($_POST['route_3'] ?? '');

                if ($removeLogo) {
                    $logoUrl = '';
                }

                if (!empty($_FILES['logo_file']['name']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/uploads';
                    if (!is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0755, true);
                    }
                    $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
                        $dest = $uploadDir . '/logo.' . $ext;
                        if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                            $error = 'Logo upload folder is not writable: ' . $uploadDir;
                        } else if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $dest)) {
                            $logoUrl = 'uploads/logo.' . $ext;
                        }
                    }
                }

                $branchesData = [];
                if ($error === '') {
                    try {
                        $branchesData = BranchRepository::buildMirrorForSettingsSlots($pdoSettings);
                    } catch (Throwable $e) {
                        $error = 'Could not read branch mirror: ' . $e->getMessage();
                    }
                }

                if ($error === '') {
                    $routeParts = array_filter([$routePart1, $routePart2, $routePart3]);
                    if (empty($routeParts)) {
                        $routeParts = ['à®•à¯Šà®´à¯à®®à¯à®ªà¯', 'à®•à®¿à®³à®¿à®¨à¯Šà®šà¯à®šà®¿', 'à®®à¯à®²à¯à®²à¯ˆà®¤à¯à®¤à¯€à®µà¯'];
                    }

                    $companyOverride = [
                        'name' => $name ?: ($company['name'] ?? 'TS Transport'),
                        'reg_no' => $regNo,
                        'logo_display' => $logoDisplay,
                        'logo_initials' => $logoInitials ?: 'TS',
                        'logo_arch_color' => $logoArchColor,
                        'logo_bar_bg' => $logoBarBg,
                        'logo_bar_color' => $logoBarColor,
                        'logo_title_color' => $logoTitleColor,
                        'logo_url' => $logoUrl,
                        'route_tamil_parts' => array_values($routeParts),
                        'branches' => $branchesData,
                        'footer_note' => $footerNote,
                    ];
                    $toSave = [
                        'company' => array_merge($company, $companyOverride),
                        'google_maps_api_key' => trim((string)($config['google_maps_api_key'] ?? '')),
                    ];
                    $json = json_encode($toSave, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    if ($json === false) {
                        $error = 'Could not encode settings to JSON.';
                    } else if (!is_dir(dirname($companyJsonPath))) {
                        $error = 'Config folder not found: ' . dirname($companyJsonPath);
                    } else if (!is_writable(dirname($companyJsonPath))) {
                        $error = 'Config folder is not writable: ' . dirname($companyJsonPath);
                    } else {
                        $written = @file_put_contents($companyJsonPath, $json, LOCK_EX);
                        if ($written !== false) {
                            $success = 'General settings saved.';
                            $company = array_merge($company, $companyOverride);
                        } else {
                            $last = error_get_last();
                            $extra = ($last && isset($last['message'])) ? (' Error: ' . $last['message']) : '';
                            $error = 'Could not save settings to ' . $companyJsonPath . '. Check write permissions.' . $extra;
                        }
                    }
                }
            } elseif ($section === 'branch_letterhead') {
                if ($error === '') {
                    try {
                        $sync = BranchRepository::syncSettingsFromCompanyPost($pdoSettings, $_POST);
                        $raw = @file_get_contents($companyJsonPath);
                        $data = ($raw !== false && $raw !== '') ? json_decode($raw, true) : [];
                        if (!is_array($data)) {
                            $data = [];
                        }
                        $data['company'] = isset($data['company']) && is_array($data['company'])
                            ? array_merge($company, $data['company'])
                            : $company;
                        $data['company']['branches'] = $sync['mirror'];
                        if (!array_key_exists('google_maps_api_key', $data)) {
                            $data['google_maps_api_key'] = trim((string)($config['google_maps_api_key'] ?? ''));
                        }
                        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        if ($json === false) {
                            $error = 'Could not encode settings to JSON.';
                        } else if (!is_dir(dirname($companyJsonPath)) || !is_writable(dirname($companyJsonPath))) {
                            $error = 'Config folder is not writable.';
                        } else {
                            $written = @file_put_contents($companyJsonPath, $json, LOCK_EX);
                            if ($written !== false) {
                                $success = 'Branch letterhead saved.';
                                $company['branches'] = $sync['mirror'];
                            } else {
                                $error = 'Could not save company.json.';
                            }
                        }
                    } catch (Throwable $e) {
                        $error = 'Could not save branch letterhead: ' . $e->getMessage();
                    }
                }
            } elseif ($section === 'system') {
                $googleMapsKey = trim($_POST['google_maps_api_key'] ?? '');
                $raw = @file_get_contents($companyJsonPath);
                $data = ($raw !== false && $raw !== '') ? json_decode($raw, true) : [];
                if (!is_array($data)) {
                    $data = [];
                }
                if (!isset($data['company']) || !is_array($data['company'])) {
                    $data['company'] = $company;
                }
                $data['google_maps_api_key'] = $googleMapsKey;
                $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                if ($json === false) {
                    $error = 'Could not encode settings to JSON.';
                } else if (!is_dir(dirname($companyJsonPath)) || !is_writable(dirname($companyJsonPath))) {
                    $error = 'Config folder is not writable.';
                } else {
                    $written = @file_put_contents($companyJsonPath, $json, LOCK_EX);
                    if ($written !== false) {
                        $success = 'System settings saved.';
                        $config['google_maps_api_key'] = $googleMapsKey;
                    } else {
                        $error = 'Could not save company.json.';
                    }
                }
            }
            }
        }

        $settingsBranchSlots = BranchRepository::getSettingsFormBranches($pdoSettings, $company);
        $defaultBranchSlotIndex = BranchRepository::getDefaultBranchSlotIndex($pdoSettings);
        Helpers::view('settings/index', compact('company', 'config', 'error', 'success', 'branchesMaster', 'settingsBranchSlots', 'defaultBranchSlotIndex', 'settingsActiveTab'));
        break;

    case 'branches':
        $action = $_GET['action'] ?? 'index';
        $pdo = Database::pdo();

        // Logged-in users (not admin-only): parcel forms and other screens need active branches.
        if ($action === 'json') {
            if (!Auth::check()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
                break;
            }
            $preserve = [];
            if (isset($_GET['preserve_from'])) {
                $preserve[] = (int)$_GET['preserve_from'];
            }
            if (isset($_GET['preserve_to'])) {
                $preserve[] = (int)$_GET['preserve_to'];
            }
            if (isset($_GET['preserve'])) {
                foreach (explode(',', (string)$_GET['preserve']) as $p) {
                    $preserve[] = (int)trim($p);
                }
            }
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => true, 'branches' => BranchRepository::toJsonList($pdo, $preserve)], JSON_UNESCAPED_UNICODE);
            break;
        }

        if (!Auth::hasRole('admin')) {
            http_response_code(403);
            echo 'Forbidden';
            break;
        }

        if ($action === 'index') {
            Helpers::redirect('index.php?page=settings&tab=branches#pane-branches');
            break;
        }

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0 || !BranchFixedMaster::isAllowedId($id)) {
                http_response_code(403);
                echo 'Only the three fixed branches (Colombo, Kilinochchi, Mullaitivu) can be updated. Use Settings â†’ Branches.';
                break;
            }
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '');
            $is_main = isset($_POST['is_main']) ? 1 : 0;
            $location = trim($_POST['location'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $canonical = BranchFixedMaster::CANONICAL[$id];
            $name = $canonical['name'];
            $code = $canonical['code'];
            if ($id === 2) {
                $is_main = 1;
            } else {
                $is_main = 0;
            }
            $wantsJson = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
                         || (strpos(($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false)
                         || (($_POST['ajax'] ?? '') === '1');

            BranchRepository::ensureSchema($pdo);
            $stmt = $pdo->prepare('UPDATE branches SET name=?, code=?, is_main=?, location=?, is_active=? WHERE id=?');
            $stmt->execute([$name, $code, $is_main, ($location !== '' ? $location : null), $is_active, $id]);
            if ($is_main) {
                $pdo->prepare('UPDATE branches SET is_main=0 WHERE id<>?')->execute([$id]);
                $pdo->prepare('UPDATE users SET is_main_branch = CASE WHEN branch_id = ? THEN 1 ELSE 0 END')->execute([$id]);
            }
            if ($wantsJson) {
                header('Content-Type: application/json');
                echo json_encode(['ok'=>false,'error'=>'Branch quick-add is disabled. System uses three fixed branches only.']);
                break;
            }
            Helpers::redirect('index.php?page=settings&tab=branches#pane-branches');
            break;
        }

        if ($action === 'set_default' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            BranchRepository::ensureSchema($pdo);
            if ($id > 0) {
                $chk = $pdo->prepare('SELECT id FROM branches WHERE id = ? AND is_active = 1');
                $chk->execute([$id]);
                if ($chk->fetch()) {
                    $pdo->exec('UPDATE branches SET is_default = 0');
                    $pdo->prepare('UPDATE branches SET is_default = 1 WHERE id = ?')->execute([$id]);
                    $_SESSION['settings_flash_success'] = 'Default header/billing branch updated.';
                } else {
                    $_SESSION['branch_list_flash_err'] = 'Cannot set default: branch not found or inactive.';
                }
            }
            Helpers::redirect('index.php?page=settings&tab=branches#pane-branches');
            break;
        }

        if ($action === 'email' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    // Load parcel, customer and branch names
                    $sel = $pdo->prepare('SELECT p.*, c.name AS customer_name, c.email AS customer_email, bf.name AS from_branch, bt.name AS to_branch FROM parcels p LEFT JOIN customers c ON c.id = p.customer_id LEFT JOIN branches bf ON bf.id = p.from_branch_id LEFT JOIN branches bt ON bt.id = p.to_branch_id WHERE p.id = ? LIMIT 1');
                    $sel->execute([$id]);
                    $row = $sel->fetch();
                    if ($row) {
                        $toEmail = trim((string)($row['customer_email'] ?? ''));
                        $toName  = (string)($row['customer_name'] ?? 'Customer');
                        if ($toEmail !== '' && isset($GLOBALS['mailer']) && $GLOBALS['mailer'] instanceof Mailer) {
                            // Items
                            $itStmt = $pdo->prepare('SELECT description, qty, COALESCE(rate,0) AS rate FROM parcel_items WHERE parcel_id=? ORDER BY id');
                            $itStmt->execute([$id]);
                            $itemsNow = $itStmt->fetchAll();
                            $rowsHtml = '';
                            $totalNow = 0.0;
                            foreach ($itemsNow as $it) {
                                $desc = (string)($it['description'] ?? '');
                                $qty = (float)($it['qty'] ?? 0);
                                $rate = (float)($it['rate'] ?? 0);
                                $amt = $qty * $rate; $totalNow += $amt;
                                $rowsHtml .= '<tr><td>'.htmlspecialchars($desc).'</td><td>'.number_format($qty,2).'</td><td class="text-end">'.number_format($rate,2).'</td><td class="text-end">'.number_format($amt,2).'</td></tr>';
                            }
                            if ($rowsHtml === '') { $rowsHtml = '<tr><td colspan="4">No item details available.</td></tr>'; }
                            $createdAt = (string)($row['created_at'] ?? date('Y-m-d H:i:s'));
                            $vehNo = trim((string)($row['vehicle_no'] ?? ''));
                            $fromName = (string)($row['from_branch'] ?? '');
                            $toNameBr = (string)($row['to_branch'] ?? '');
                            $itemCount = is_array($itemsNow) ? count($itemsNow) : 0;
                            $priceNow = isset($row['price']) ? (float)$row['price'] : (float)$totalNow;
                            $subject = 'Parcel Order #' . (int)$id . ' â€” ' . number_format($priceNow,2);
                            $html = '<div style="font-family:Arial,sans-serif">'
                                  . '<h3 style="margin:0 0 8px;">Parcel Order #' . (int)$id . '</h3>'
                                  . '<div style="color:#555;margin:0 0 6px;">Order Time: ' . htmlspecialchars($createdAt) . '</div>'
                                  . '<div style="color:#555;margin:0 0 6px;">From: ' . htmlspecialchars($fromName) . ' &rarr; To: ' . htmlspecialchars($toNameBr) . '</div>'
                                  . '<div style="color:#555;margin:0 0 12px;">Vehicle: ' . htmlspecialchars($vehNo) . ' | Items: ' . (int)$itemCount . ' | Price: ' . number_format($priceNow,2) . '</div>'
                                  . '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">'
                                  . '<thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody>'
                                  . $rowsHtml
                                  . '</tbody><tfoot><tr><th colspan="3" style="text-align:right">Total</th><th class="text-end">'.number_format($totalNow,2).'</th></tr></tfoot></table>'
                                  . '</div>';
                            $alt = "Parcel Order #" . (int)$id . "\nOrder Time: " . $createdAt . "\nFrom: " . $fromName . " -> To: " . $toNameBr . "\nVehicle: " . $vehNo . "\nItems: " . (int)$itemCount . "\nPrice: " . number_format($priceNow,2);
                            try { $GLOBALS['mailer']->send($toEmail, $toName, $subject, $html, $alt); } catch (Throwable $e) { /* ignore send failures */ }
                        }
                    }
                } catch (Throwable $e) { /* ignore and continue */ }
            }
            Helpers::redirect('index.php?page=parcels');
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION['branch_list_flash_err'] = 'Branches cannot be deleted. The system uses three fixed branches only.';
            Helpers::redirect('index.php?page=settings&tab=branches#pane-branches');
            break;
        }

        if ($action === 'new') {
            Helpers::redirect('index.php?page=settings&tab=branches#pane-branches');
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            if (!BranchFixedMaster::isAllowedId($id)) {
                Helpers::redirect('index.php?page=settings&tab=branches#pane-branches');
                break;
            }
            $stmt = $pdo->prepare('SELECT * FROM branches WHERE id=?');
            $stmt->execute([$id]);
            $branch = $stmt->fetch();
            if (!$branch) { http_response_code(404); echo 'Not found'; break; }
            Helpers::view('branches/form', compact('branch'));
            break;
        }

        // index
        $branches = BranchRepository::allOrderedForAdmin($pdo);
        Helpers::view('branches/index', compact('branches'));
        break;

    case 'users':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        if (!Auth::hasRole('admin')) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';
        $pdo = Database::pdo();
        $currentUserId = (int)(Auth::user()['id'] ?? 0);
        $roleData = _users_roles_for_forms($pdo);
        $rolesCatalog = $roleData['catalog'];
        $rolesDynamic = $roleData['dynamic'];
        $branchesAll = BranchRepository::forDropdowns($pdo);
        $existingUsernames = $pdo->query('SELECT username FROM users')->fetchAll(PDO::FETCH_COLUMN) ?: [];

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            // Normalize username: trim only (allow internal spaces)
            $username = trim($_POST['username'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $role = trim((string)($_POST['role'] ?? ''));
            $branch_id = (int)($_POST['branch_id'] ?? 0);
            $active = isset($_POST['active']) ? 1 : 0;
            $password = $_POST['password'] ?? '';
            if ($id > 0 && $id === $currentUserId) {
                // Cannot deactivate or demote yourself
                $active = 1;
            }
            if ($username === '' || $full_name === '') {
                $error = 'Username and Full Name are required.';
                $userRow = _users_sanitize_row_for_form(compact('id','username','full_name','role','branch_id','active'));
                $branchesAll = BranchRepository::forDropdowns($pdo, [(int)($userRow['branch_id'] ?? 0)]);
                Helpers::view('users/form', compact('userRow','branchesAll','error','rolesDynamic','rolesCatalog','currentUserId','existingUsernames'));
                break;
            }
            // Pre-check: unique username (case-insensitive, ignore leading/trailing spaces only)
            $unameNorm = strtolower(trim($username));
            $sqlChk = 'SELECT id, username FROM users WHERE LOWER(TRIM(username)) = ?' . ($id>0 ? ' AND id <> ?' : '') . ' LIMIT 1';
            $chk = $pdo->prepare($sqlChk);
            if ($id>0) { $chk->execute([$unameNorm, $id]); } else { $chk->execute([$unameNorm]); }
            $dup = $chk->fetch();
            if ($dup) {
                $conflict = (string)($dup['username'] ?? '');
                // Suggest next available username
                $base = preg_replace('/\s+/', ' ', trim($username));
                $suffix = 1;
                $suggest = $base . ' ' . $suffix;
                $checkStmt = $pdo->prepare('SELECT 1 FROM users WHERE LOWER(TRIM(username)) = LOWER(TRIM(?)) LIMIT 1');
                while (true) {
                    $checkStmt->execute([$suggest]);
                    if (!$checkStmt->fetch()) break;
                    $suffix++;
                    $suggest = $base . ' ' . $suffix;
                }
                $suggestedUsername = $suggest;
                $error = 'Username already exists: ' . htmlspecialchars($conflict) . '. Try: ' . htmlspecialchars($suggestedUsername);
                $userRow = _users_sanitize_row_for_form(compact('id','username','full_name','role','branch_id','active'));
                $branchesAll = BranchRepository::forDropdowns($pdo, [(int)($userRow['branch_id'] ?? 0)]);
                Helpers::view('users/form', compact('userRow','branchesAll','error','suggestedUsername','rolesDynamic','rolesCatalog','currentUserId','existingUsernames'));
                break;
            }
            // Empty role defaults to staff for DB compatibility
            $roleParam = ($role === '') ? 'staff' : $role;
            if ($id > 0 && $id === $currentUserId && $roleParam !== 'admin' && _users_is_last_active_admin($pdo, $id)) {
                $error = 'You are the only active admin. Assign another admin before changing your role.';
                $userRow = _users_sanitize_row_for_form(compact('id','username','full_name','role','branch_id','active'));
                $branchesAll = BranchRepository::forDropdowns($pdo, [(int)($userRow['branch_id'] ?? 0)]);
                Helpers::view('users/form', compact('userRow','branchesAll','error','rolesDynamic','rolesCatalog','currentUserId','existingUsernames'));
                break;
            }
            if ($id > 0 && $active !== 1 && _users_is_last_active_admin($pdo, $id)) {
                $error = 'Cannot deactivate the only active admin account.';
                $userRow = _users_sanitize_row_for_form(compact('id','username','full_name','role','branch_id','active'));
                $userRow['active'] = 1;
                $branchesAll = BranchRepository::forDropdowns($pdo, [(int)($userRow['branch_id'] ?? 0)]);
                Helpers::view('users/form', compact('userRow','branchesAll','error','rolesDynamic','rolesCatalog','currentUserId','existingUsernames'));
                break;
            }
            if ($id > 0 && $roleParam !== 'admin' && _users_is_last_active_admin($pdo, $id)) {
                $error = 'Cannot change role of the only active admin. Promote another user to admin first.';
                $userRow = _users_sanitize_row_for_form(compact('id','username','full_name','role','branch_id','active'));
                $branchesAll = BranchRepository::forDropdowns($pdo, [(int)($userRow['branch_id'] ?? 0)]);
                Helpers::view('users/form', compact('userRow','branchesAll','error','rolesDynamic','rolesCatalog','currentUserId','existingUsernames'));
                break;
            }
            try {
                $pdo->beginTransaction();
                if ($id > 0) {
                    if ($password !== '') {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare('UPDATE users SET username=?, full_name=?, role=?, branch_id=?, active=?, password_hash=? WHERE id=?');
                        $stmt->execute([$username,$full_name,$roleParam,($branch_id>0?$branch_id:null),$active,$hash,$id]);
                    } else {
                        $stmt = $pdo->prepare('UPDATE users SET username=?, full_name=?, role=?, branch_id=?, active=? WHERE id=?');
                        $stmt->execute([$username,$full_name,$roleParam,($branch_id>0?$branch_id:null),$active,$id]);
                    }
                } else {
                    if ($password === '') {
                        $error = 'Password is required for new user.';
                        $userRow = _users_sanitize_row_for_form(compact('id','username','full_name','role','branch_id','active'));
                        $pdo->rollBack();
                        $branchesAll = BranchRepository::forDropdowns($pdo, [(int)($userRow['branch_id'] ?? 0)]);
                        Helpers::view('users/form', compact('userRow','branchesAll','error','rolesDynamic','rolesCatalog','currentUserId','existingUsernames'));
                        break;
                    }
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare('INSERT INTO users (username, full_name, role, branch_id, active, password_hash) VALUES (?,?,?,?,?,?)');
                    $stmt->execute([$username,$full_name,$roleParam,($branch_id>0?$branch_id:null),$active,$hash]);
                }
                $pdo->commit();
                $_SESSION['flash_users_msg'] = $id > 0 ? 'User updated.' : 'User created.';
                Helpers::redirect('index.php?page=users');
                break;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $msg = 'Failed to save user.';
                // 23000: constraint violation. Try to detect username duplicate vs other constraints
                if ($e->getCode() === '23000') {
                    $em = $e->getMessage();
                    if (stripos($em, 'users.username') !== false || stripos($em, 'Duplicate entry') !== false) {
                        $msg = 'Username already exists. Please choose a different username.';
                    } else if (stripos($em, 'role') !== false && stripos($em, 'null') !== false) {
                        $msg = 'Role cannot be empty in the current database settings. Please choose a Role.';
                    }
                }
                $error = $msg;
                $userRow = _users_sanitize_row_for_form(compact('id','username','full_name','role','branch_id','active'));
                $branchesAll = BranchRepository::forDropdowns($pdo, [(int)($userRow['branch_id'] ?? 0)]);
                Helpers::view('users/form', compact('userRow','branchesAll','error','rolesDynamic','rolesCatalog','currentUserId','existingUsernames'));
                break;
            }
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                if ($id === $currentUserId) {
                    $_SESSION['flash_users_error'] = 'You cannot delete your own account while logged in.';
                    Helpers::redirect('index.php?page=users');
                    break;
                }
                if (_users_is_last_active_admin($pdo, $id)) {
                    $_SESSION['flash_users_error'] = 'Cannot delete the only active admin account.';
                    Helpers::redirect('index.php?page=users');
                    break;
                }
                try {
                    $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
                    $_SESSION['flash_users_msg'] = 'User deleted.';
                } catch (PDOException $e) {
                    $_SESSION['flash_users_error'] = ($e->getCode() === '23000')
                        ? 'Cannot delete this user because they are referenced by payments, expenses, or other records.'
                        : 'Failed to delete user.';
                }
            }
            Helpers::redirect('index.php?page=users');
            break;
        }

        if ($action === 'new') {
            $userRow = ['id'=>0,'username'=>'','full_name'=>'','role'=>'staff','branch_id'=>0,'active'=>1];
            $branchesAll = BranchRepository::forDropdowns($pdo);
            Helpers::view('users/form', compact('userRow','branchesAll','rolesDynamic','rolesCatalog','currentUserId','existingUsernames'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id=?');
            $stmt->execute([$id]);
            $userRow = _users_sanitize_row_for_form($stmt->fetch() ?: null);
            if (!$userRow) {
                $_SESSION['flash_users_error'] = 'User not found.';
                Helpers::redirect('index.php?page=users');
                break;
            }
            $branchesAll = BranchRepository::forDropdowns($pdo, [(int)($userRow['branch_id'] ?? 0)]);
            Helpers::view('users/form', compact('userRow','branchesAll','rolesDynamic','rolesCatalog','currentUserId','existingUsernames'));
            break;
        }

        // index with filters
        $usernameF = trim($_GET['username'] ?? '');
        $fullNameF = trim($_GET['full_name'] ?? '');
        $roleF = trim($_GET['role'] ?? '');
        $branchF = (int)($_GET['branch_id'] ?? 0);
        $activeF = $_GET['active'] ?? ''; // '', '1', '0'
        $sql = 'SELECT u.*, b.name AS branch_name FROM users u LEFT JOIN branches b ON b.id = u.branch_id WHERE 1=1';
        $params = [];
        if ($usernameF !== '') { $sql .= ' AND u.username LIKE ?'; $params[] = "%$usernameF%"; }
        if ($fullNameF !== '') { $sql .= ' AND u.full_name LIKE ?'; $params[] = "%$fullNameF%"; }
        if ($roleF !== '') { $sql .= ' AND u.role = ?'; $params[] = $roleF; }
        if ($branchF > 0) { $sql .= ' AND u.branch_id = ?'; $params[] = $branchF; }
        if ($activeF === '1' || $activeF === '0') { $sql .= ' AND u.active = ?'; $params[] = (int)$activeF; }
        $sql .= ' ORDER BY u.created_at DESC, u.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        $branchesAll = BranchRepository::forFilters($pdo);
        $success = $_SESSION['flash_users_msg'] ?? null;
        unset($_SESSION['flash_users_msg']);
        $error = $_SESSION['flash_users_error'] ?? null;
        unset($_SESSION['flash_users_error']);
        Helpers::view('users/index', compact('users','branchesAll','rolesDynamic','rolesCatalog','usernameF','fullNameF','roleF','branchF','activeF','currentUserId','success','error'));
        break;

    case 'customers':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';
        $pdo = Database::pdo();

        if ($action === 'purge_all' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::isAdmin()) { http_response_code(403); echo 'Forbidden'; break; }
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $confirm = trim((string)($_POST['confirm_purge'] ?? ''));
            if ($confirm !== 'DELETE CUSTOMERS') {
                Helpers::redirect('index.php?page=customers&purge_error=1');
                break;
            }
            $result = DataReset::deleteAllCustomerData($pdo);
            if ($result['success']) {
                Helpers::redirect('index.php?page=customers&purged=' . (int)($result['customers_deleted'] ?? 0));
                break;
            }
            $_SESSION['customer_purge_errors'] = $result['errors'] ?? [];
            Helpers::redirect('index.php?page=customers&purge_failed=1');
            break;
        }

        if ($action === 'import_template' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="customers_import_template.csv"');
            // Add BOM so Excel reads UTF-8 correctly
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'wb');

            $dataOnly = (string)($_GET['data_only'] ?? '') === '1';
            // Template columns (keep minimal + useful)
            if (!$dataOnly) {
                fputcsv($out, ['name','phone','delivery_location','address','email','customer_type'], ',', '"', '\\');
            }
            // Sample rows (edit/replace these)
            fputcsv($out, ['John Doe','0771234567','Ootuppulam','No. 10, Main Street, Colombo','john@example.com','regular'], ',', '"', '\\');
            fputcsv($out, ['ZEMIRA','0779321072','Kilinochchi','KILI','','regular'], ',', '"', '\\');
            fputcsv($out, ['à®œà¯‚à®Ÿà¯ à®…à®²à¯à®µà®¿à®¸à¯','0777550905','Ootuppulam','kilinochchi','mjudealvis@gmail.com','regular'], ',', '"', '\\');
            fclose($out);
            return;
        }

        if ($action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }

            $imported = 0;
            $failed = 0;
            $errors = [];

            $file = $_FILES['import_file'] ?? null;
            $tmp = is_array($file) ? ($file['tmp_name'] ?? '') : '';
            $name = is_array($file) ? ($file['name'] ?? '') : '';
            $err = is_array($file) ? (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;

            if ($err !== UPLOAD_ERR_OK || $tmp === '' || !is_file($tmp)) {
                Helpers::redirect('index.php?page=customers&import_failed=1');
                break;
            }

            $ext = strtolower(pathinfo((string)$name, PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                Helpers::redirect('index.php?page=customers&import_failed=1');
                break;
            }

            $fh = @fopen($tmp, 'rb');
            if (!$fh) {
                Helpers::redirect('index.php?page=customers&import_failed=1');
                break;
            }

            $header = fgetcsv($fh);
            if (!is_array($header) || empty($header)) {
                fclose($fh);
                Helpers::redirect('index.php?page=customers&import_failed=1');
                break;
            }

            $norm = function($v){
                $v = strtolower(trim((string)$v));
                $v = preg_replace('/\s+/', '_', $v);
                return $v;
            };
            $hmap = [];
            foreach ($header as $idx => $col) { $hmap[$norm($col)] = (int)$idx; }

            $get = function(array $row, string $key) use ($hmap) {
                if (!isset($hmap[$key])) return '';
                $i = (int)$hmap[$key];
                return isset($row[$i]) ? trim((string)$row[$i]) : '';
            };

            $line = 1;
            while (($row = fgetcsv($fh)) !== false) {
                $line++;
                if (!is_array($row)) { continue; }
                $nameV = $get($row, 'name');
                $phoneV = $get($row, 'phone');
                $emailV = $get($row, 'email');
                $addressV = $get($row, 'address');
                $dlV = $get($row, 'delivery_location');
                $typeV = $get($row, 'customer_type');

                // Allow alternative header
                if ($dlV === '') { $dlV = $get($row, 'deliverylocation'); }
                if ($typeV === '') { $typeV = $get($row, 'type'); }

                // Skip fully empty lines
                if ($nameV === '' && $phoneV === '' && $emailV === '' && $addressV === '' && $dlV === '' && $typeV === '') {
                    continue;
                }

                if ($nameV === '') {
                    $failed++;
                    $errors[] = 'Line ' . $line . ': Name is required.';
                    continue;
                }

                $phoneDb = ($phoneV === '') ? null : $phoneV;
                $emailDb = ($emailV === '') ? null : $emailV;
                $typeDb = null;
                if ($typeV !== '') {
                    $ct = strtolower(trim($typeV));
                    if ($ct === 'corporate' || $ct === 'regular') { $typeDb = $ct; }
                }

                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare('INSERT INTO customers (name, phone, email, address, delivery_location, place_id, lat, lng, customer_type) VALUES (?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([$nameV, $phoneDb, $emailDb, $addressV, $dlV, null, null, null, $typeDb]);
                    $newCid = (int) $pdo->lastInsertId();
                    if ($newCid > 0) {
                        CashbookRepository::ensureCustomerAccount($pdo, $newCid, $nameV);
                        _customer_ledger_ensure($pdo, $newCid, $nameV);
                    }
                    $pdo->commit();
                    $imported++;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $failed++;
                    $msg = 'Failed to import row.';
                    if ($e instanceof PDOException) {
                        $code = $e->getCode();
                        $emsg = $e->getMessage();
                        if ($code === '23000' && stripos($emsg, 'phone') !== false) {
                            $msg = 'Duplicate phone.';
                        }
                    }
                    $errors[] = 'Line ' . $line . ': ' . $msg;
                }
            }
            fclose($fh);

            // Store compact info in query params (avoid large payload)
            $q = 'index.php?page=customers&imported=' . (int)$imported . '&failed=' . (int)$failed;
            if (!empty($errors)) {
                $q .= '&import_errors=1';
                try { $_SESSION['import_customer_errors'] = array_slice($errors, 0, 30); } catch (Throwable $e) { /* ignore */ }
            } else {
                try { unset($_SESSION['import_customer_errors']); } catch (Throwable $e) { /* ignore */ }
            }
            Helpers::redirect($q);
            break;
        }

        // JSON summary for a customer (used by parcel form notification)
        if ($action === 'summary' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            header('Content-Type: application/json');
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['error' => 'invalid id']); return; }
            $user = Auth::user();
            $branchId = (int)($user['branch_id'] ?? 0);
            $c = $pdo->prepare('SELECT id, name, phone FROM customers WHERE id=? LIMIT 1');
            $c->execute([$id]);
            $cust = $c->fetch();
            if (!$cust) { echo json_encode(['error' => 'not found']); return; }
            $cntPar = $pdo->prepare('SELECT COUNT(*) AS c FROM parcels WHERE customer_id=?');
            $cntPar->execute([$id]);
            $total_parcels = (int)($cntPar->fetch()['c'] ?? 0);
            $dnStmt = $pdo->prepare('SELECT COUNT(*) AS c, MAX(delivery_date) AS last_date, COALESCE(SUM(total_amount),0) AS total_amount FROM delivery_notes WHERE customer_id=?');
            $dnStmt->execute([$id]);
            $dn = $dnStmt->fetch();
            $total_delivery_notes = (int)($dn['c'] ?? 0);
            $last_delivery_date = $dn['last_date'] ?? null;
            $total_amount = (float)($dn['total_amount'] ?? 0);
            $paidStmt = $pdo->prepare('SELECT COALESCE(SUM(p.amount),0) AS paid FROM payments p LEFT JOIN delivery_notes dn ON dn.id = p.delivery_note_id WHERE dn.customer_id=?');
            $paidStmt->execute([$id]);
            $total_paid = (float)($paidStmt->fetch()['paid'] ?? 0);
            $due = max(0, $total_amount - $total_paid);

            // Find today's delivery note for this customer at current branch (if any)
            $todayDnId = null;
            if ($branchId > 0) {
                $stmt = $pdo->prepare('SELECT id FROM delivery_notes WHERE customer_id=? AND branch_id=? AND delivery_date=CURDATE() LIMIT 1');
                $stmt->execute([$id, $branchId]);
                $row = $stmt->fetch();
                if ($row) { $todayDnId = (int)$row['id']; }
            }
            // Find the last delivery note id for this customer (any branch/date)
            $lastDnId = null;
            $stmt = $pdo->prepare('SELECT id FROM delivery_notes WHERE customer_id=? ORDER BY delivery_date DESC, id DESC LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) { $lastDnId = (int)$row['id']; }
            echo json_encode([
                'id' => (int)$cust['id'],
                'name' => (string)$cust['name'],
                'phone' => (string)$cust['phone'],
                'total_parcels' => $total_parcels,
                'total_delivery_notes' => $total_delivery_notes,
                'last_delivery_date' => $last_delivery_date,
                'total_amount' => $total_amount,
                'total_paid' => $total_paid,
                'due' => $due,
                'today_delivery_note_id' => $todayDnId,
                'last_delivery_note_id' => $lastDnId
            ]);
            return;
        }

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $wantsJsonSave = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
                             || (strpos(($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false)
                             || (($_POST['ajax'] ?? '') === '1');
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
                if ($wantsJsonSave) { header('Content-Type: application/json'); echo json_encode(['error'=>'Invalid CSRF. Please refresh the page and try again.']); return; }
                http_response_code(400); echo 'Invalid CSRF'; break;
            }
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            // Do not generate any placeholder; prefer NULL when empty.
            $phoneDb = ($phone === '') ? null : $phone;
            // Try to relax schema to allow NULL phones if needed
            try { $pdo->exec("ALTER TABLE customers MODIFY phone VARCHAR(20) NULL"); } catch (Throwable $e) { /* ignore if already NULL or insufficient privileges */ }
            $address = trim($_POST['address'] ?? '');
            $delivery_location = trim($_POST['delivery_location'] ?? '');
            $place_id = trim($_POST['place_id'] ?? '');
            $lat = ($_POST['lat'] ?? '') !== '' ? (float)$_POST['lat'] : null;
            $lng = ($_POST['lng'] ?? '') !== '' ? (float)$_POST['lng'] : null;
            // Normalize customer_type to enum values or null
            $customer_type_raw = $_POST['customer_type'] ?? null;
            $customer_type = null;
            if (is_string($customer_type_raw)) {
                $ct = strtolower(trim($customer_type_raw));
                if ($ct === 'corporate' || $ct === 'regular') { $customer_type = $ct; }
            }
            $wantsJson = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
                         || (strpos(($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false)
                         || (($_POST['ajax'] ?? '') === '1');
            if ($name === '') {
                if ($wantsJson) { header('Content-Type: application/json'); echo json_encode(['error'=>'Name is required.']); return; }
                $deliveryLocationOptions = _merge_delivery_location_options($pdo);
                $error = 'Name is required.'; $customer = compact('id','name','phone','email','address','delivery_location','place_id','lat','lng','customer_type'); Helpers::view('customers/form', compact('customer','error','deliveryLocationOptions')); break;
            }
            $cashbookAccountId = null;
            $customerCreated = false;
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE customers SET name=?, phone=?, email=?, address=?, delivery_location=?, place_id=?, lat=?, lng=?, customer_type=? WHERE id=?');
                    $stmt->execute([$name,$phoneDb,($email!==''?$email:null),$address,$delivery_location,($place_id!==''?$place_id:null),$lat,$lng,$customer_type,$id]);
                    _cashbook_sync_customer_account_name($pdo, $id, $name);
                    _customer_ledger_sync_name($pdo, $id, $name);
                    _customer_ledger_ensure($pdo, $id, $name);
                } else {
                    $pdo->beginTransaction();
                    try {
                        $stmt = $pdo->prepare('INSERT INTO customers (name, phone, email, address, delivery_location, place_id, lat, lng, customer_type) VALUES (?,?,?,?,?,?,?,?,?)');
                        $stmt->execute([$name,$phoneDb,($email!==''?$email:null),$address,$delivery_location,($place_id!==''?$place_id:null),$lat,$lng,$customer_type]);
                        $id = (int)$pdo->lastInsertId();
                        if ($id > 0) {
                            $cashbookAccountId = CashbookRepository::ensureCustomerAccount($pdo, $id, $name);
                            _customer_ledger_ensure($pdo, $id, $name);
                        }
                        $customerCreated = true;
                        $pdo->commit();
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        throw $e;
                    }
                }
            } catch (Throwable $e) {
                $msg = 'Failed to save customer.';
                if ($e instanceof PDOException) {
                    $code = $e->getCode();
                    $emsg = $e->getMessage();
                    if ($code === '23000') {
                        if (stripos($emsg, 'customers') !== false && stripos($emsg, 'phone') !== false) {
                            $msg = 'Phone number already exists. Use a different phone or edit the existing customer.';
                        }
                    } elseif ($code === '22001') {
                        // data too long
                        $msg = 'Some fields are too long (ensure Phone is at most 20 characters).';
                    }
                }
                if ($wantsJson) { header('Content-Type: application/json'); echo json_encode(['error'=>$msg]); return; }
                $deliveryLocationOptions = _merge_delivery_location_options($pdo);
                $error = $msg; $customer = compact('id','name','phone','email','address','delivery_location','place_id','lat','lng','customer_type'); Helpers::view('customers/form', compact('customer','error','deliveryLocationOptions')); break;
            }
            if ($cashbookAccountId === null && $id > 0) {
                $stCa = $pdo->prepare('SELECT id FROM cashbook_accounts WHERE customer_id = ? LIMIT 1');
                $stCa->execute([$id]);
                $cidRow = $stCa->fetchColumn();
                if ($cidRow) {
                    $cashbookAccountId = (int)$cidRow;
                }
            }
            if ($wantsJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'id' => $id,
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'delivery_location' => $delivery_location,
                    'cashbook_account_id' => $cashbookAccountId,
                    'customer_created' => $customerCreated,
                ]);
                return;
            }
            Helpers::redirect('index.php?page=customers');
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                // Guard against FK violations: check references before delete
                $refParcels = 0; $refDN = 0; $refLedger = 0;
                try {
                    $st1 = $pdo->prepare('SELECT COUNT(*) FROM parcels WHERE customer_id=?');
                    $st1->execute([$id]);
                    $refParcels = (int)$st1->fetchColumn();
                } catch (Throwable $e) { $refParcels = 0; }
                try {
                    $st2 = $pdo->prepare('SELECT COUNT(*) FROM delivery_notes WHERE customer_id=?');
                    $st2->execute([$id]);
                    $refDN = (int)$st2->fetchColumn();
                } catch (Throwable $e) { $refDN = 0; }
                try {
                    if (CustomerLedgerRepository::customerHasLedgerTransactions($pdo, $id)) {
                        $refLedger = 1;
                    }
                } catch (Throwable $e) { $refLedger = 0; }

                if ($refParcels > 0 || $refDN > 0 || $refLedger > 0) {
                    http_response_code(400);
                    $totalRefs = $refParcels + $refDN;
                    $msg = 'Cannot delete this customer because it is referenced by existing records: '
                         . $refParcels . ' parcel(s) and ' . $refDN . ' delivery note(s).';
                    if ($refLedger > 0) {
                        $msg .= ' The linked customer ledger has accounting transactions.';
                    }
                    echo '<!doctype html><html><head><meta charset="utf-8"><title>Delete Customer</title>'
                       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
                       . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body>'
                       . '<div class="container" style="max-width:780px;">'
                       . '<div class="alert alert-danger mt-4" role="alert">' . htmlspecialchars($msg) . ' '
                       . 'Please reassign or remove those records first.</div>'
                       . '<a class="btn btn-outline-secondary" href="' . Helpers::baseUrl('index.php?page=customers') . '">â† Back to Customers</a>'
                       . '</div></body></html>';
                    break;
                }

                CashbookRepository::detachCashbookAccountForDeletedCustomer($pdo, $id);
                try {
                    CustomerLedgerRepository::detachForDeletedCustomer($pdo, $id);
                } catch (Throwable $e) { /* non-fatal */ }

                // Safe to delete
                try {
                    $pdo->prepare('DELETE FROM customers WHERE id=?')->execute([$id]);
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        http_response_code(400);
                        $msg = 'Cannot delete this customer because it is referenced by existing records.';
                        echo '<!doctype html><html><head><meta charset="utf-8"><title>Delete Customer</title>'
                           . '<meta name="viewport" content="width=device-width,initial-scale=1">'
                           . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body>'
                           . '<div class="container" style="max-width:780px;">'
                           . '<div class="alert alert-danger mt-4" role="alert">' . htmlspecialchars($msg) . '</div>'
                           . '<a class="btn btn-outline-secondary" href="' . Helpers::baseUrl('index.php?page=customers') . '">â† Back to Customers</a>'
                           . '</div></body></html>';
                        break;
                    }
                    throw $e;
                }
            }
            Helpers::redirect('index.php?page=customers');
            break;
        }

        if ($action === 'new') {
            $customer = ['id'=>0,'name'=>'','phone'=>'','address'=>'','delivery_location'=>'','customer_type'=>null];
            $deliveryLocationOptions = _merge_delivery_location_options($pdo);
            Helpers::view('customers/form', compact('customer','deliveryLocationOptions'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM customers WHERE id=?');
            $stmt->execute([$id]);
            $customer = $stmt->fetch();
            if (!$customer) { http_response_code(404); echo 'Not found'; break; }
            $deliveryLocationOptions = _merge_delivery_location_options($pdo);
            $customerLedger = null;
            try {
                CustomerLedgerRepository::ensureSchema($pdo);
                $customerLedger = CustomerLedgerRepository::getByCustomerId($pdo, $id);
                if (!$customerLedger) {
                    _customer_ledger_ensure($pdo, $id, (string)($customer['name'] ?? ''));
                    $customerLedger = CustomerLedgerRepository::getByCustomerId($pdo, $id);
                }
            } catch (Throwable $e) { /* optional */ }
            Helpers::view('customers/form', compact('customer','deliveryLocationOptions','customerLedger'));
            break;
        }

        // index with filters (name/phone/address/delivery_location/type) and fallback to single 'q'
        $name = trim($_GET['name'] ?? '');
        $phone = trim($_GET['phone'] ?? '');
        $address = trim($_GET['address'] ?? '');
        $email = trim($_GET['email'] ?? '');
        $delivery_location = trim($_GET['delivery_location'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $q = trim($_GET['q'] ?? '');

        $hasFilters = ($name !== '' || $phone !== '' || $email !== '' || $address !== '' || $delivery_location !== '' || $type !== '');
        if ($hasFilters) {
            $sql = 'SELECT * FROM customers WHERE 1=1';
            $params = [];
            if ($name !== '') { $sql .= ' AND name LIKE ?'; $params[] = "%$name%"; }
            if ($phone !== '') { $sql .= ' AND phone LIKE ?'; $params[] = "%$phone%"; }
            if ($email !== '') { $sql .= ' AND email LIKE ?'; $params[] = "%$email%"; }
            if ($address !== '') { $sql .= ' AND address LIKE ?'; $params[] = "%$address%"; }
            if ($delivery_location !== '') { $sql .= ' AND delivery_location LIKE ?'; $params[] = "%$delivery_location%"; }
            if ($type !== '') { $sql .= ' AND customer_type = ?'; $params[] = $type; }
            $sql .= ' ORDER BY created_at DESC LIMIT 100';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $customers = $stmt->fetchAll();
        } else if ($q !== '') {
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone LIKE ? OR name LIKE ? OR email LIKE ? OR address LIKE ? OR delivery_location LIKE ? OR customer_type LIKE ? ORDER BY created_at DESC LIMIT 100");
            $like = "%$q%";
            $stmt->execute([$like,$like,$like,$like,$like,$like]);
            $customers = $stmt->fetchAll();
        } else {
            $customers = $pdo->query('SELECT * FROM customers ORDER BY created_at DESC LIMIT 100')->fetchAll();
        }
        try {
            CustomerLedgerRepository::ensureSchema($pdo);
            CustomerLedgerRepository::syncMissingIfNeeded($pdo);
            $customers = CustomerLedgerRepository::enrichCustomerRows($pdo, $customers);
        } catch (Throwable $e) { /* optional */ }
        Helpers::view('customers/index', compact('customers','q','name','phone','email','address','delivery_location','type'));
        break;

    case 'delivery_routes':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';
        $pdo = Database::pdo();
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS delivery_routes (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        } catch (Throwable $e) { /* ignore */ }

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $routes = $pdo->query('SELECT * FROM delivery_routes ORDER BY name')->fetchAll();
                $error = 'Route name is required.';
                Helpers::view('delivery_routes/index', compact('routes','error'));
                break;
            }
            $stmt = $pdo->prepare('INSERT INTO delivery_routes (name) VALUES (?)');
            $stmt->execute([$name]);
            Helpers::redirect('index.php?page=delivery_routes&saved=1');
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare('DELETE FROM delivery_routes WHERE id=?')->execute([$id]);
            }
            Helpers::redirect('index.php?page=delivery_routes&removed=1');
            break;
        }

        $routes = $pdo->query('SELECT * FROM delivery_routes ORDER BY name')->fetchAll();
        $success = null;
        if (isset($_GET['saved']) && $_GET['saved'] === '1') {
            $success = 'Delivery route added.';
        } elseif (isset($_GET['removed']) && $_GET['removed'] === '1') {
            $success = 'Route removed from the common list.';
        }
        $error = $error ?? null;
        Helpers::view('delivery_routes/index', compact('routes','success','error'));
        break;

    case 'vehicles':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';
        $pdo = Database::pdo();

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $vno = trim($_POST['vehicle_no'] ?? '');
            if ($vno === '') { http_response_code(400); echo 'Vehicle number required'; break; }

            // Use reg_number explicitly
            try {
                $q = $pdo->prepare('SELECT id FROM vehicles WHERE reg_number = ? LIMIT 1');
                $q->execute([$vno]);
                $r = $q->fetch();
                if ($r) {
                    $id = (int)$r['id'];
                } else {
                    $ins = $pdo->prepare('INSERT INTO vehicles (reg_number) VALUES (?)');
                    $ins->execute([$vno]);
                    $id = (int)$pdo->lastInsertId();
                }
            } catch (Throwable $e) {
                http_response_code(500); echo 'Failed to save vehicle'; break;
            }
            header('Content-Type: application/json');
            echo json_encode(['id'=>$id, 'vehicle_no'=>$vno]);
            return;
        }

        http_response_code(404); echo 'Not found';
        break;

    case 'suppliers':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';
        $pdo = Database::pdo();
        BranchRepository::ensureSchema($pdo);

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            // Disallow placeholder-like names such as 'none' or '-- none --'
            $nameNorm = strtolower(preg_replace('/[^a-z0-9]+/i','', $name));
            $phone = trim($_POST['phone'] ?? '');
            $branch_id = BranchRepository::resolveToFixedBranchId($pdo, (int)($_POST['branch_id'] ?? 0));
            $supplier_code = trim($_POST['supplier_code'] ?? '');
            $wantsJson = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
                         || (strpos(($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false)
                         || (($_POST['ajax'] ?? '') === '1');
            if ($name === '' || $nameNorm === 'none' || $nameNorm === 'nonenone' || $branch_id <= 0) {
                if ($wantsJson) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Valid Name and Branch are required.']);
                    return;
                }
                $error = 'Valid Name and Branch are required.';
                $supplier = compact('id','name','phone','branch_id','supplier_code');
                $branchesAll = BranchRepository::forDropdowns($pdo, [(int)($supplier['branch_id'] ?? 0)]);
                Helpers::view('suppliers/form', compact('supplier','branchesAll','error'));
                break;
            }
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE suppliers SET name=?, phone=?, branch_id=?, supplier_code=? WHERE id=?');
                $stmt->execute([$name,$phone,$branch_id,$supplier_code,$id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO suppliers (name, phone, branch_id, supplier_code) VALUES (?,?,?,?)');
                $stmt->execute([$name,$phone,$branch_id,$supplier_code]);
                $id = (int)$pdo->lastInsertId();
            }
            if ($wantsJson) {
                header('Content-Type: application/json');
                echo json_encode(['id'=>$id, 'name'=>$name, 'phone'=>$phone, 'branch_id'=>$branch_id, 'supplier_code'=>$supplier_code]);
                return;
            }
            Helpers::redirect('index.php?page=suppliers&saved=1');
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare('DELETE FROM suppliers WHERE id=?')->execute([$id]);
            }
            Helpers::redirect('index.php?page=suppliers&deleted=1');
            break;
        }

        if ($action === 'new') {
            $supplier = ['id'=>0,'name'=>'','phone'=>'','branch_id'=>0,'supplier_code'=>''];
            $branchesAll = BranchRepository::forDropdowns($pdo);
            Helpers::view('suppliers/form', compact('supplier','branchesAll'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id=?');
            $stmt->execute([$id]);
            $supplier = $stmt->fetch();
            if (!$supplier) { http_response_code(404); echo 'Not found'; break; }
            $rawBranchId = (int)($supplier['branch_id'] ?? 0);
            $fixedBranchId = BranchRepository::resolveToFixedBranchId($pdo, $rawBranchId);
            if ($fixedBranchId > 0 && $fixedBranchId !== $rawBranchId) {
                $pdo->prepare('UPDATE suppliers SET branch_id = ? WHERE id = ?')->execute([$fixedBranchId, $id]);
                $supplier['branch_id'] = $fixedBranchId;
            } elseif ($fixedBranchId > 0) {
                $supplier['branch_id'] = $fixedBranchId;
            }
            $branchesAll = BranchRepository::forDropdowns($pdo, [(int)($supplier['branch_id'] ?? 0)]);
            Helpers::view('suppliers/form', compact('supplier','branchesAll'));
            break;
        }

        // index with filters (name/phone/code/branch_id) and fallback to single 'q'
        $name = trim($_GET['name'] ?? '');
        $phone = trim($_GET['phone'] ?? '');
        $code = trim($_GET['code'] ?? '');
        $branch_id = BranchRepository::resolveToFixedBranchId($pdo, (int)($_GET['branch_id'] ?? 0));
        $q = trim($_GET['q'] ?? '');
        $suppliers = [];

        try {
            $orphans = $pdo->query('SELECT id, branch_id FROM suppliers WHERE branch_id IS NOT NULL AND branch_id NOT IN (1,2,3)')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($orphans as $orphan) {
                $fixed = BranchRepository::resolveToFixedBranchId($pdo, (int)($orphan['branch_id'] ?? 0));
                if ($fixed > 0) {
                    $pdo->prepare('UPDATE suppliers SET branch_id = ? WHERE id = ?')->execute([$fixed, (int)$orphan['id']]);
                }
            }
        } catch (Throwable $e) { /* best-effort legacy branch remap */ }

        $hasFilters = ($name !== '' || $phone !== '' || $code !== '' || $branch_id > 0);
        try {
            if ($hasFilters) {
                $sql = 'SELECT s.*, b.name AS branch_name FROM suppliers s LEFT JOIN branches b ON b.id = s.branch_id WHERE 1=1';
                $params = [];
                if ($name !== '') { $sql .= ' AND s.name LIKE ?'; $params[] = "%$name%"; }
                if ($phone !== '') { $sql .= ' AND s.phone LIKE ?'; $params[] = "%$phone%"; }
                if ($code !== '') { $sql .= ' AND s.supplier_code LIKE ?'; $params[] = "%$code%"; }
                if ($branch_id > 0) { $sql .= ' AND s.branch_id = ?'; $params[] = $branch_id; }
                $sql .= ' ORDER BY s.created_at DESC LIMIT 100';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $suppliers = $stmt->fetchAll();
            } else if ($q !== '') {
                $stmt = $pdo->prepare("SELECT s.*, b.name AS branch_name FROM suppliers s LEFT JOIN branches b ON b.id = s.branch_id WHERE s.name LIKE ? OR s.phone LIKE ? OR s.supplier_code LIKE ? ORDER BY s.created_at DESC LIMIT 100");
                $like = "%$q%";
                $stmt->execute([$like,$like,$like]);
                $suppliers = $stmt->fetchAll();
            } else {
                $suppliers = $pdo->query('SELECT s.*, b.name AS branch_name FROM suppliers s LEFT JOIN branches b ON b.id = s.branch_id ORDER BY s.created_at DESC LIMIT 100')->fetchAll();
            }
            foreach ($suppliers as &$supRow) {
                $bid = (int)($supRow['branch_id'] ?? 0);
                if ($bid > 0 && trim((string)($supRow['branch_name'] ?? '')) === '' && isset(BranchFixedMaster::CANONICAL[$bid])) {
                    $supRow['branch_name'] = BranchFixedMaster::CANONICAL[$bid]['name'];
                }
            }
            unset($supRow);
        } catch (Throwable $e) {
            $suppliers = [];
        }
        $success = null;
        if (isset($_GET['saved']) && $_GET['saved'] === '1') {
            $success = 'Supplier saved successfully.';
        } elseif (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
            $success = 'Supplier removed.';
        }
        $branchesAll = BranchRepository::forFilters($pdo);
        Helpers::view('suppliers/index', compact('suppliers','q','name','phone','code','branch_id','branchesAll','success','hasFilters'));
        break;

    case 'parcels':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        if (!Auth::canCreateParcels()) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';
        $pdo = Database::pdo();

        try { $pdo->exec("ALTER TABLE parcels ADD COLUMN delivery_route VARCHAR(255) NULL"); } catch (Throwable $e) { /* ignore if exists */ }
        try { $pdo->exec('ALTER TABLE parcels ADD COLUMN invoice_no INT UNSIGNED NULL'); } catch (Throwable $e) { /* ignore if exists */ }
        ParcelBillingService::ensureSchema($pdo);
        try { $pdo->exec('ALTER TABLE parcel_items ADD COLUMN additional_amount DECIMAL(12,2) NULL'); } catch (Throwable $e) { /* ignore if exists */ }
        try { $pdo->exec('ALTER TABLE parcel_items ADD COLUMN additional_amounts TEXT NULL'); } catch (Throwable $e) { /* ignore if exists */ }
        try {
            $pdo->exec("ALTER TABLE parcels MODIFY COLUMN status ENUM('pending','in_transit','delivered','cancelled','returned','failed','on_hold','out_for_delivery') NOT NULL DEFAULT 'pending'");
        } catch (Throwable $e) { /* ignore if not permitted or already migrated */ }
        $user = Auth::user();
        $userBranchCode = (string)($user['branch_code'] ?? '');
        $userBranchName = (string)($user['branch_name'] ?? '');
        BranchRepository::ensureSchema($pdo);
        $mainBranchId = BranchRepository::getMainBranchId($pdo);
        $isKilinochchi = $mainBranchId > 0 && (int)($user['branch_id'] ?? 0) === $mainBranchId;
        if (!$isKilinochchi) {
            $isKilinochchi = (strcasecmp($userBranchCode, 'KIL') === 0) || (strcasecmp($userBranchName, 'Kilinochchi') === 0);
        }
        $isColombo = (strcasecmp($userBranchCode, 'COL') === 0) || (strcasecmp($userBranchName, 'Colombo') === 0);
        $isMullaitivu = (strcasecmp($userBranchCode, 'MLT') === 0)
                         || (stripos($userBranchName, 'Mullaitivu') !== false)
                         || (stripos($userBranchName, 'Mullaithivu') !== false)
                         || (stripos($userBranchName, 'Mullaithiv') !== false);
        // Keep legacy isMain flag aligned to Colombo for pricing behavior
        $isMain = $isColombo;
        // Allow all branches to enter item RS/CTS (price) so price add fields work for new parcels
        $canEnterItemAmounts = true;
        // Allow all branches with parcel role to create parcels
        $canCreateParcels = true;

        // data for forms
        $branchesAll = BranchRepository::forDropdowns($pdo);
        $customersAll = $pdo->query('SELECT id, name, phone, delivery_location, lat, lng FROM customers ORDER BY created_at DESC LIMIT 500')->fetchAll();
        $suppliersAll = $pdo->query('SELECT id, name, phone FROM suppliers ORDER BY name')->fetchAll();
        // Ensure email log table exists
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS parcel_emails (
                id INT AUTO_INCREMENT PRIMARY KEY,
                parcel_id INT NOT NULL,
                to_email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                html_body MEDIUMTEXT NULL,
                text_body MEDIUMTEXT NULL,
                status ENUM('sent','failed') NOT NULL,
                error TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_parcel (parcel_id),
                CONSTRAINT fk_parcel_emails_parcel FOREIGN KEY (parcel_id) REFERENCES parcels(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) { /* ignore */ }
        // Ensure new body columns exist on older databases
        try { $pdo->exec("ALTER TABLE parcel_emails ADD COLUMN html_body MEDIUMTEXT NULL AFTER subject"); } catch (Throwable $e) { /* ignore if exists */ }
        try { $pdo->exec("ALTER TABLE parcel_emails ADD COLUMN text_body MEDIUMTEXT NULL AFTER html_body"); } catch (Throwable $e) { /* ignore if exists */ }
        // Ensure persistent status columns exist on parcels for fallback display
        try { $pdo->exec("ALTER TABLE parcels ADD COLUMN last_email_status VARCHAR(10) NULL AFTER updated_at"); } catch (Throwable $e) { /* ignore if exists */ }
        try { $pdo->exec("ALTER TABLE parcels ADD COLUMN last_emailed_at DATETIME NULL AFTER last_email_status"); } catch (Throwable $e) { /* ignore if exists */ }
        // Also store last email subject and text for synthesized display when logs are unavailable
        try { $pdo->exec("ALTER TABLE parcels ADD COLUMN last_email_subject VARCHAR(255) NULL AFTER last_emailed_at"); } catch (Throwable $e) { /* ignore if exists */ }
        try { $pdo->exec("ALTER TABLE parcels ADD COLUMN last_email_text MEDIUMTEXT NULL AFTER last_email_subject"); } catch (Throwable $e) { /* ignore if exists */ }

        if ($action === 'email_form') {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) { Helpers::redirect('index.php?page=parcels'); break; }
            $sel = $pdo->prepare('SELECT p.*, c.name AS customer_name, c.email AS customer_email, bf.name AS from_branch, bt.name AS to_branch FROM parcels p LEFT JOIN customers c ON c.id = p.customer_id LEFT JOIN branches bf ON bf.id = p.from_branch_id LEFT JOIN branches bt ON bt.id = p.to_branch_id WHERE p.id = ? LIMIT 1');
            $sel->execute([$id]);
            $row = $sel->fetch();
            if (!$row) { Helpers::redirect("index.php?page=parcels"); break; }
            $toEmail = trim((string)($row['customer_email'] ?? ''));
            $toName  = (string)($row['customer_name'] ?? 'Customer');
            $createdAt = (string)($row['created_at'] ?? date('Y-m-d H:i:s'));
            $vehNo = trim((string)($row['vehicle_no'] ?? ''));
            $fromName = (string)($row['from_branch'] ?? '');
            $toNameBr = (string)($row['to_branch'] ?? '');
            $itStmt = $pdo->prepare('SELECT description, qty, COALESCE(rate,0) AS rate FROM parcel_items WHERE parcel_id=? ORDER BY id');
            $itStmt->execute([$id]);
            $itemsNow = $itStmt->fetchAll();
            $rowsHtml = '';
            $totalNow = 0.0;
            foreach ($itemsNow as $it) {
                $desc = (string)($it['description'] ?? '');
                $qty = (float)($it['qty'] ?? 0);
                $rate = (float)($it['rate'] ?? 0);
                $amt = $qty * $rate; $totalNow += $amt;
                $rowsHtml .= '<tr><td>'.htmlspecialchars($desc).'</td><td>'.number_format($qty,2).'</td><td class="text-end">'.number_format($rate,2).'</td><td class="text-end">'.number_format($amt,2).'</td></tr>';
            }
            if ($rowsHtml === '') { $rowsHtml = '<tr><td colspan="4">No item details available.</td></tr>'; }
            $priceNow = isset($row['price']) ? (float)$row['price'] : (float)$totalNow;
            $itemCount = is_array($itemsNow) ? count($itemsNow) : 0;
            $defaultSubject = 'Parcel Order #' . (int)$id . ' â€” ' . number_format($priceNow,2);
            $defaultHtml = '<div style="font-family:Arial,sans-serif">'
                         . '<h3 style="margin:0 0 8px;">Parcel Order #' . (int)$id . '</h3>'
                         . '<div style="color:#555;margin:0 0 6px;">Order Time: ' . htmlspecialchars($createdAt) . '</div>'
                         . '<div style="color:#555;margin:0 0 6px;">From: ' . htmlspecialchars($fromName) . ' &rarr; To: ' . htmlspecialchars($toNameBr) . '</div>'
                         . '<div style="color:#555;margin:0 0 12px;">Vehicle: ' . htmlspecialchars($vehNo) . ' | Items: ' . (int)$itemCount . ' | Price: ' . number_format($priceNow,2) . '</div>'
                         . '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">'
                         . '<thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody>'
                         . $rowsHtml
                         . '</tbody><tfoot><tr><th colspan="3" style="text-align:right">Total</th><th class="text-end">'.number_format($totalNow,2).'</th></tr></tfoot></table>'
                         . '</div>';
            $prefill = ['id'=>(int)$id,'to_email'=>$toEmail,'to_name'=>$toName,'subject'=>$defaultSubject,'html'=>$defaultHtml];
            $flash_msg = $_SESSION['flash_msg'] ?? null; unset($_SESSION['flash_msg']);
            $flash_err = $_SESSION['flash_err'] ?? null; unset($_SESSION['flash_err']);
            Helpers::view('parcels/email_form', compact('prefill','flash_msg','flash_err'));
            break;
        }

        if ($action === 'email_send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $toEmail = trim((string)($_POST['to_email'] ?? ''));
            $toName = trim((string)($_POST['to_name'] ?? 'Customer'));
            $subject = trim((string)($_POST['subject'] ?? ''));
            $html = (string)($_POST['html'] ?? '');
            $alt = strip_tags($html);
            if ($toEmail === '' || $subject === '') {
                $_SESSION['flash_err'] = 'Please provide both To Email and Subject.';
                Helpers::redirect('index.php?page=parcels&action=email_form&id='.(int)$id);
                break;
            }
            $ok = false;
            if (isset($GLOBALS['mailer']) && $GLOBALS['mailer'] instanceof Mailer) {
                try { $ok = $GLOBALS['mailer']->send($toEmail, $toName, $subject, $html, $alt); } catch (Throwable $e) { $ok = false; }
            }
            // Log result
            try {
                $ins = $pdo->prepare('INSERT INTO parcel_emails (parcel_id, to_email, subject, html_body, text_body, status, error) VALUES (?,?,?,?,?,?,?)');
                $err = '';
                if (!$ok && isset($GLOBALS['mailer']) && method_exists($GLOBALS['mailer'], 'getLastError')) { $err = (string)$GLOBALS['mailer']->getLastError(); }
                $ins->execute([$id, $toEmail, $subject, $html, $alt, ($ok?'sent':'failed'), ($err!==''?$err:null)]);
            } catch (Throwable $e) { /* ignore */ }
            // Persist status and simple content on parcel row
            try {
                $up = $pdo->prepare('UPDATE parcels SET last_email_status=?, last_emailed_at=NOW(), last_email_subject=?, last_email_text=? WHERE id=?');
                $up->execute([ $ok ? 'sent' : 'failed', (string)$subject, (string)$alt, (int)$id ]);
            } catch (Throwable $e) { /* ignore */ }
            if ($ok) {
                $_SESSION['email_sent_parcel_id'] = (int)$id;
                $_SESSION['flash_msg'] = 'Email sent to ' . htmlspecialchars($toEmail);
                Helpers::redirect('index.php?page=parcels');
            } else {
                $_SESSION['flash_err'] = 'Failed to send email. Please check SMTP config or recipient address.';
                Helpers::redirect('index.php?page=parcels&action=email_form&id='.(int)$id);
            }
            break;
        }

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                   || (isset($_SERVER['HTTP_ACCEPT']) && stripos((string)$_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
            
            // Prevent double submission using idempotency token
            $idempotencyKey = $_POST['idempotency_key'] ?? '';
            if ($idempotencyKey === '') {
                $idempotencyKey = bin2hex(random_bytes(16));
            }
            $idempotencySessionKey = 'parcel_save_' . md5($idempotencyKey);
            if (isset($_SESSION[$idempotencySessionKey])) {
                // This exact submission was already processed, redirect to prevent duplicate
                $savedId = (int)$_SESSION[$idempotencySessionKey];
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok'=>true,'id'=>$savedId,'redirect'=>Helpers::baseUrl('index.php?page=parcels&action=new&saved_id=' . $savedId)]);
                    break;
                }
                Helpers::redirect('index.php?page=parcels&action=new&saved_id=' . $savedId);
                break;
            }
            
            $id = (int)($_POST['id'] ?? 0);
            /** True when creating a new parcel row (used for same-bill DN link) */
            $parcelWasCreate = ($id <= 0);
            $customer_id = (int)($_POST['customer_id'] ?? 0);
            $supplier_id = (int)($_POST['supplier_id'] ?? 0);
            if ($supplier_id <= 0) { $supplier_id = null; }
            $from_branch_id = BranchRepository::resolveToFixedBranchId($pdo, (int)($_POST['from_branch_id'] ?? 0));
            $to_branch_id = BranchRepository::resolveToFixedBranchId($pdo, (int)($_POST['to_branch_id'] ?? 0));
            $weight = (float)($_POST['weight'] ?? 0);
            $status = $_POST['status'] ?? 'pending';
            // Serial/tracking number handling
            $tracking_number_raw = trim($_POST['tracking_number'] ?? '');
            $tracking_number = $tracking_number_raw; // keep as-is for update/insert
            $vehicle_no = trim($_POST['vehicle_no'] ?? '');
            $delivery_route = trim((string)($_POST['delivery_route'] ?? ''));
            $delivery_location = trim((string)($_POST['delivery_location'] ?? ''));
            // Same-day billing: one invoice per customer per calendar day (resolved on create inside transaction)
            $billDate = ParcelBillingService::normalizeBillDate($_POST['created_date'] ?? '');
            $invoice_number = trim((string)($_POST['invoice_number'] ?? ''));
            $forceNewInvoice = isset($_POST['force_new_invoice']) && (string)$_POST['force_new_invoice'] === '1';
            $billReused = false;
            $invoice_id = 0;
            $invoice_no = (int)($_POST['invoice_no'] ?? 0);
            if ($id > 0 && $invoice_no <= 0) {
                $invoice_no = 0; // filled from $existing below when editing
            }
            // Lorry full flag
            $lorry_full = isset($_POST['lorry_full']) && ($_POST['lorry_full'] === '1' || $_POST['lorry_full'] === 'on');
            // Remember preference in session so the form can keep the checkbox state on next load
            $_SESSION['lorry_full_pref'] = $lorry_full ? 1 : 0;
            $items = $_POST['items'] ?? [];
            // Derive total weight as sum of item quantities for all branches
            $sumQty = 0.0;
            if (is_array($items)) {
                foreach ($items as $it) {
                    $sumQty += (float)($it['qty'] ?? 0);
                }
            }
            if ($sumQty > 0) { $weight = $sumQty; }

            // Fetch existing parcel for policy decisions on edit
            $existing = null; $isBilled = false;
            if ($id > 0) {
                $st = $pdo->prepare('SELECT * FROM parcels WHERE id=?');
                $st->execute([$id]);
                $existing = $st->fetch();
                if ($existing) {
                    $isBilled = ($existing['price'] !== null && (float)$existing['price'] > 0);
                    if ($invoice_no <= 0) {
                        $invoice_no = (int)($existing['invoice_no'] ?? 0);
                        if ($invoice_no <= 0) { $invoice_no = (int)$existing['id']; }
                    }
                    if ($invoice_number === '') {
                        $invoice_number = trim((string)($existing['invoice_number'] ?? ''));
                    }
                }
                // Status-only update: parcel is In Transit and form submitted with status_only_edit
                if ($existing && (string)($existing['status'] ?? '') === 'in_transit' && isset($_POST['status_only_edit']) && (int)$_POST['status_only_edit'] === 1) {
                    $status = $_POST['status'] ?? $existing['status'];
                    $allowedStatus = Helpers::parcelStatusValues();
                    if (!in_array($status, $allowedStatus, true)) { $status = 'in_transit'; }
                    $pdo->prepare('UPDATE parcels SET status=? WHERE id=?')->execute([$status, $id]);
                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['ok'=>true,'id'=>(int)$id,'redirect'=>Helpers::baseUrl('index.php?page=parcels&action=edit&id='.(int)$id)]);
                        break;
                    }
                    Helpers::redirect('index.php?page=parcels&action=edit&id=' . $id);
                    break;
                }
            }
            // Determine if this is a price-only edit at Kilinochchi (fields were disabled in UI)
            $priceOnlyEdit = ($id > 0 && $isKilinochchi && !$isBilled);

            // Price handling
            // Only Kilinochchi can set or change price. Others cannot set price at create or edit.
            $price = null;
            if ($id <= 0) {
                // Always defer pricing to Kilinochchi
                $price = null;
            } else {
                if ($isBilled) {
                    $error = 'This parcel has already been billed and cannot be edited.';
                    $parcel = $existing ?: ['id'=>$id];
                    // Load vehicles list for form
                    try { $vehiclesAll = $pdo->query('SELECT id, reg_number AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); }
                    catch (Throwable $e) { $vehiclesAll = []; }
                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        http_response_code(422);
                        echo json_encode(['ok'=>false,'error'=>$error]);
                        break;
                    }
                    $items = $items ?? [];
                    $vehiclesAll = $vehiclesAll ?? [];
                    $deliveryRoutesAll = $deliveryRoutesAll ?? [];
                    $lastParcel = null;
                    $branchesAll = BranchRepository::forParcelForm($pdo, $parcel);
                    Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','error','items','vehiclesAll','deliveryRoutesAll','lastParcel') + ['policy'=>['priceOnly'=>false,'lockAll'=>true,'canEnterItemAmounts'=>$canEnterItemAmounts]]);
                    break;
                }
                if ($isKilinochchi) {
                    $priceRaw = trim($_POST['price'] ?? '');
                    $discountRaw = trim($_POST['discount'] ?? '');
                    $p = ($priceRaw === '') ? null : (float)$priceRaw;
                    if ($p === null) {
                        // Fallback: compute from posted items (Qty Ã— Rate)
                        $sum = 0.0;
                        if (is_array($items)) {
                            foreach ($items as $it) {
                                $q = (float)($it['qty'] ?? 0);
                                $r = (float)($it['rate'] ?? 0);
                                if ($r <= 0) { $rs = (float)($it['rs'] ?? 0); $cts = (float)($it['cts'] ?? 0); $r = $rs + ($cts/100.0); }
                                $line = ($q > 0 && $r > 0) ? ($q * $r) : 0;
                                $addArr = $it['additional_amounts'] ?? [];
                                if (is_string($addArr)) { $addArr = json_decode($addArr, true) ?: []; }
                                $addAmt = 0;
                                foreach ((array)$addArr as $a) { $addAmt += (float)$a; }
                                if ($addAmt <= 0) { $addAmt = (float)($it['additional_amount'] ?? 0); }
                                $sum += $line + $addAmt;
                            }
                        }
                        if ($sum > 0) { $p = $sum; }
                    }
                    if ($p !== null) {
                        $d = ($discountRaw === '') ? 0.0 : (float)$discountRaw;
                        $p = max(0.0, $p - max(0.0, $d));
                    }
                    $price = $p;
                    if ($price === null) {
                        // Re-render with error for missing price
                        $error = 'Please enter a valid price (or Rate) for Kilinochchi.';
                        // Load vehicles for form
                        try { $vehiclesAll = $pdo->query('SELECT id, reg_number AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); }
                        catch (Throwable $e) { $vehiclesAll = []; }
                        $parcel = $existing ?: ['id'=>$id,'customer_id'=>$customer_id,'from_branch_id'=>$from_branch_id,'to_branch_id'=>$to_branch_id,'weight'=>$weight,'status'=>$status,'tracking_number'=>$tracking_number,'vehicle_no'=>$vehicle_no];
                        $policy = ['priceOnly'=>true, 'lockAll'=>false, 'canEnterItemAmounts'=>$canEnterItemAmounts];
                        if ($isAjax) {
                            header('Content-Type: application/json; charset=utf-8');
                            http_response_code(422);
                            echo json_encode(['ok'=>false,'error'=>$error]);
                            break;
                        }
                        $branchesAll = BranchRepository::forParcelForm($pdo, $parcel);
                        $lastParcel = null;
                        Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','error','items','vehiclesAll','policy','lastParcel'));
                        break;
                    }
                } else {
                    // Not Kilinochchi: keep existing price intact
                    $price = $existing ? ($existing['price'] ?? null) : null;
                }
            }

            if (!$priceOnlyEdit && ($customer_id <= 0 || $from_branch_id <= 0 || $to_branch_id <= 0)) {
                $error = 'Customer, From Branch and To Branch are required.';
                $parcel = compact('id','customer_id','supplier_id','from_branch_id','to_branch_id','weight','status','tracking_number','vehicle_no','delivery_route');
                // Load vehicles list: prefer reg_number, fallback to plate_no, then vehicle_no
                try {
                    $vehiclesAll = $pdo->query('SELECT id, reg_number AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll();
                } catch (Throwable $e) {
                    try { $vehiclesAll = $pdo->query('SELECT id, plate_no AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); }
                    catch (Throwable $e2) {
                        try { $vehiclesAll = $pdo->query('SELECT id, vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); }
                        catch (Throwable $e3) { $vehiclesAll = []; }
                    }
                }
                $deliveryRoutesAll = [];
                try { $deliveryRoutesAll = $pdo->query('SELECT id, name FROM delivery_routes ORDER BY name')->fetchAll(); } catch (Throwable $e) { $deliveryRoutesAll = []; }
                $policy = ['priceOnly'=>$isKilinochchi, 'lockAll'=>false, 'canEnterItemAmounts'=>$canEnterItemAmounts];
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(422);
                    echo json_encode(['ok'=>false,'error'=>$error]);
                    break;
                }
                $branchesAll = BranchRepository::forParcelForm($pdo, $parcel);
                $lastParcel = null;
                Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','error','items','vehiclesAll','policy','deliveryRoutesAll','lastParcel'));
                break;
            }

            if (!$priceOnlyEdit && $from_branch_id > 0 && $to_branch_id > 0 && $from_branch_id === $to_branch_id) {
                $error = 'From and To branch must be different.';
                $parcel = compact('id','customer_id','supplier_id','from_branch_id','to_branch_id','weight','status','tracking_number','vehicle_no','delivery_route');
                try {
                    $vehiclesAll = $pdo->query('SELECT id, reg_number AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll();
                } catch (Throwable $e) {
                    try { $vehiclesAll = $pdo->query('SELECT id, plate_no AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); }
                    catch (Throwable $e2) {
                        try { $vehiclesAll = $pdo->query('SELECT id, vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); }
                        catch (Throwable $e3) { $vehiclesAll = []; }
                    }
                }
                $deliveryRoutesAll = [];
                try { $deliveryRoutesAll = $pdo->query('SELECT id, name FROM delivery_routes ORDER BY name')->fetchAll(); } catch (Throwable $e) { $deliveryRoutesAll = []; }
                $policy = ['priceOnly'=>$isKilinochchi, 'lockAll'=>false, 'canEnterItemAmounts'=>$canEnterItemAmounts];
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(422);
                    echo json_encode(['ok'=>false,'error'=>$error]);
                    break;
                }
                $branchesAll = BranchRepository::forParcelForm($pdo, $parcel);
                $lastParcel = null;
                Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','error','items','vehiclesAll','policy','deliveryRoutesAll','lastParcel'));
                break;
            }

            if (!$priceOnlyEdit) {
                $err = ParcelSaveService::validateParcelPayload($weight, $delivery_location, $priceOnlyEdit);
                if ($err !== null) {
                    $error = $err;
                    $parcel = array_merge(
                        compact('id', 'customer_id', 'supplier_id', 'from_branch_id', 'to_branch_id', 'weight', 'status', 'tracking_number', 'vehicle_no', 'delivery_route'),
                        ['delivery_location' => $delivery_location]
                    );
                    try {
                        $vehiclesAll = $pdo->query('SELECT id, reg_number AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll();
                    } catch (Throwable $e) {
                        try {
                            $vehiclesAll = $pdo->query('SELECT id, plate_no AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll();
                        } catch (Throwable $e2) {
                            try {
                                $vehiclesAll = $pdo->query('SELECT id, vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll();
                            } catch (Throwable $e3) {
                                $vehiclesAll = [];
                            }
                        }
                    }
                    $deliveryRoutesAll = [];
                    try {
                        $deliveryRoutesAll = $pdo->query('SELECT id, name FROM delivery_routes ORDER BY name')->fetchAll();
                    } catch (Throwable $e) {
                        $deliveryRoutesAll = [];
                    }
                    $policy = ['priceOnly'=>$isKilinochchi, 'lockAll'=>false, 'canEnterItemAmounts'=>$canEnterItemAmounts];
                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        http_response_code(422);
                        echo json_encode(['ok'=>false,'error'=>$error]);
                        break;
                    }
                    $branchesAll = BranchRepository::forParcelForm($pdo, $parcel);
                    $lastParcel = null;
                    Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','error','items','vehiclesAll','policy','deliveryRoutesAll','lastParcel'));
                    break;
                }
            }

            $allowedStatus = Helpers::parcelStatusValues();
            if (!in_array($status, $allowedStatus, true)) { $status = 'pending'; }

            // Detect status transition to in_transit (for billing prompt)
            $statusWas = $existing ? (string)($existing['status'] ?? '') : '';
            $becameInTransit = ($id > 0 && $existing && $statusWas !== 'in_transit' && $status === 'in_transit');

            // Schema DDL must run outside transactions (MySQL implicit commit on ALTER/CREATE).
            ParcelBillingService::ensureSchema($pdo);

            try {
            $pdo->beginTransaction();
            if ($id > 0) {
                if ($isKilinochchi) {
                    // Kilinochchi: price-only update
                    $stmt = $pdo->prepare("UPDATE parcels SET price=? WHERE id=?");
                    $stmt->execute([$price, $id]);
                    // Sync any linked delivery note amounts to the updated parcel price
                    try {
                        $newAmount = (float)($price ?? 0);
                        if ($newAmount <= 0) {
                            $sumItems = $pdo->prepare('SELECT COALESCE(SUM(COALESCE(qty,0)*COALESCE(rate,0) + COALESCE(additional_amount,0)),0) AS s FROM parcel_items WHERE parcel_id=?');
                            $sumItems->execute([$id]);
                            $newAmount = (float)($sumItems->fetch()['s'] ?? 0);
                        }
                        $selDn = $pdo->prepare('SELECT DISTINCT delivery_note_id FROM delivery_note_parcels WHERE parcel_id=?');
                        $selDn->execute([$id]);
                        $dnRows = $selDn->fetchAll() ?: [];
                        if ($dnRows) {
                            $pdo->prepare('UPDATE delivery_note_parcels SET amount=? WHERE parcel_id=?')->execute([$newAmount, $id]);
                            $sumStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS s FROM delivery_note_parcels WHERE delivery_note_id=?');
                            $updStmt = $pdo->prepare('UPDATE delivery_notes SET total_amount=? WHERE id=?');
                            foreach ($dnRows as $r) {
                                $dnId = (int)($r['delivery_note_id'] ?? 0);
                                if ($dnId > 0) {
                                    $sumStmt->execute([$dnId]);
                                    $s = (float)($sumStmt->fetch()['s'] ?? 0);
                                    $updStmt->execute([$s, $dnId]);
                                }
                            }
                        }
                    } catch (Throwable $e) { /* ignore sync errors */ }
                } else {
                    // Other branches: full edit except price (kept same value as existing)
                    $stmt = $pdo->prepare("UPDATE parcels SET customer_id=?, supplier_id=?, from_branch_id=?, to_branch_id=?, weight=?, price=?, status=?, tracking_number = NULLIF(?, ''), vehicle_no=?, invoice_no=?, delivery_route=? WHERE id=?");
                    $stmt->execute([$customer_id,$supplier_id,$from_branch_id,$to_branch_id,$weight,$price,$status,$tracking_number,$vehicle_no,$invoice_no,($delivery_route!==''?$delivery_route:null),$id]);
                    // Replace items allowed for non-Kilinochchi
                    $pdo->prepare('DELETE FROM parcel_items WHERE parcel_id=?')->execute([$id]);
                    if (is_array($items)) {
                        $insItem = $pdo->prepare('INSERT INTO parcel_items (parcel_id, qty, description, rate, additional_amount, additional_amounts) VALUES (?,?,?,?,?,?)');
                        foreach ($items as $it) {
                            $desc = trim($it['description'] ?? '');
                            $qty = (float)($it['qty'] ?? 0);
                            $rate = (float)($it['rate'] ?? 0);
                            if ($rate <= 0) { $rs = (float)($it['rs'] ?? 0); $cts = (float)($it['cts'] ?? 0); $rate = $rs + ($cts/100.0); }
                            $rate = ($rate > 0) ? $rate : null;
                            $addArr = $it['additional_amounts'] ?? [];
                            if (is_string($addArr)) { $addArr = json_decode($addArr, true) ?: []; }
                            $addAmt = 0;
                            foreach ((array)$addArr as $a) { $addAmt += (float)$a; }
                            $addAmt = ($addAmt > 0) ? $addAmt : null;
                            $addJson = !empty($addArr) ? json_encode(array_values(array_filter(array_map('floatval', $addArr)))) : null;
                            if ($addJson === '[]') $addJson = null;
                            if ($desc !== '' || $qty > 0) {
                                $insItem->execute([$id, $qty, $desc, $rate, $addAmt, $addJson]);
                            }
                        }
                    }
                    // Sync any linked delivery note amounts to the (possibly) updated computed amount
                    try {
                        $newAmount = (float)($price ?? 0);
                        if ($newAmount <= 0) {
                            $sumItems = $pdo->prepare('SELECT COALESCE(SUM(COALESCE(qty,0)*COALESCE(rate,0) + COALESCE(additional_amount,0)),0) AS s FROM parcel_items WHERE parcel_id=?');
                            $sumItems->execute([$id]);
                            $newAmount = (float)($sumItems->fetch()['s'] ?? 0);
                        }
                        $selDn = $pdo->prepare('SELECT DISTINCT delivery_note_id FROM delivery_note_parcels WHERE parcel_id=?');
                        $selDn->execute([$id]);
                        $dnRows = $selDn->fetchAll() ?: [];
                        if ($dnRows) {
                            $pdo->prepare('UPDATE delivery_note_parcels SET amount=? WHERE parcel_id=?')->execute([$newAmount, $id]);
                            $sumStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS s FROM delivery_note_parcels WHERE delivery_note_id=?');
                            $updStmt = $pdo->prepare('UPDATE delivery_notes SET total_amount=? WHERE id=?');
                            foreach ($dnRows as $r) {
                                $dnId = (int)($r['delivery_note_id'] ?? 0);
                                if ($dnId > 0) {
                                    $sumStmt->execute([$dnId]);
                                    $s = (float)($sumStmt->fetch()['s'] ?? 0);
                                    $updStmt->execute([$s, $dnId]);
                                }
                            }
                        }
                    } catch (Throwable $e) { /* ignore sync errors */ }
                }
            } else {
                // Create - Check for duplicate entry (same data within last 10 seconds)
                // Check BEFORE starting transaction to avoid unnecessary rollback
                $duplicateCheck = $pdo->prepare('SELECT id FROM parcels WHERE customer_id = ? AND from_branch_id = ? AND to_branch_id = ? AND weight = ? AND COALESCE(vehicle_no, "") = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 SECOND) ORDER BY id DESC LIMIT 1');
                $duplicateCheck->execute([$customer_id, $from_branch_id, $to_branch_id, $weight, $vehicle_no ?: '']);
                $duplicate = $duplicateCheck->fetch();
                if ($duplicate) {
                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        http_response_code(409);
                        echo json_encode([
                            'ok' => false,
                            'error' => 'A similar parcel was just saved. Please verify or continue from the duplicate.',
                            'duplicate_id' => (int)$duplicate['id'],
                        ]);
                        break;
                    }
                    Helpers::redirect('index.php?page=parcels&action=new&duplicate=' . (int)$duplicate['id'] . '&customer_id=' . urlencode((string)$customer_id) . '&vehicle_no=' . urlencode((string)$vehicle_no) . '&from_branch_id=' . urlencode((string)$from_branch_id) . '&to_branch_id=' . urlencode((string)$to_branch_id) . '&date=' . urlencode($billDate));
                    break;
                }

                // Reuse today's bill for this customer or allocate a new INV-YYYYMMDD-NNN (row lock inside transaction)
                $invResolved = ParcelBillingService::resolveInvoiceForNewParcel(
                    $pdo,
                    $customer_id,
                    $billDate,
                    $from_branch_id,
                    $to_branch_id,
                    $invoice_no,
                    $invoice_number,
                    $forceNewInvoice
                );
                $invoice_no = (int)$invResolved['invoice_no'];
                $invoice_number = (string)$invResolved['invoice_number'];
                $invoice_id = (int)($invResolved['invoice_id'] ?? 0);
                $billReused = !empty($invResolved['is_reused']);
                $invNumParam = ($invoice_number !== '') ? $invoice_number : null;
                $invIdParam = $invoice_id > 0 ? $invoice_id : null;
                
                if ($isKilinochchi) {
                    // Compute price now for Kilinochchi create
                    $priceRaw = trim($_POST['price'] ?? '');
                    $discountRaw = trim($_POST['discount'] ?? '');
                    $p = ($priceRaw === '') ? null : (float)$priceRaw;
                    if ($p === null) {
                        $sum = 0.0;
                        if (is_array($items)) {
                            foreach ($items as $it) {
                                $q = (float)($it['qty'] ?? 0);
                                $r = (float)($it['rate'] ?? 0);
                                if ($r <= 0) { $rs = (float)($it['rs'] ?? 0); $cts = (float)($it['cts'] ?? 0); $r = $rs + ($cts/100.0); }
                                $line = ($q > 0 && $r > 0) ? ($q * $r) : 0;
                                $addArr = $it['additional_amounts'] ?? [];
                                if (is_string($addArr)) { $addArr = json_decode($addArr, true) ?: []; }
                                $addAmt = 0;
                                foreach ((array)$addArr as $a) { $addAmt += (float)$a; }
                                if ($addAmt <= 0) { $addAmt = (float)($it['additional_amount'] ?? 0); }
                                $sum += $line + $addAmt;
                            }
                        }
                        if ($sum > 0) { $p = $sum; }
                    }
                    if ($p !== null) {
                        $d = ($discountRaw === '') ? 0.0 : (float)$discountRaw;
                        $p = max(0.0, $p - max(0.0, $d));
                    }
                    $price = $p;
                    $createdAtOverride = trim($_POST['created_date'] ?? '');
                    if ($createdAtOverride) {
                        $stmt = $pdo->prepare("INSERT INTO parcels (customer_id, supplier_id, from_branch_id, to_branch_id, weight, price, status, tracking_number, vehicle_no, invoice_no, invoice_number, invoice_id, delivery_route, created_at) VALUES (?,?,?,?,?,?,?, NULLIF(?, ''), ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$customer_id,$supplier_id,$from_branch_id,$to_branch_id,$weight,$price,$status,$tracking_number,$vehicle_no,$invoice_no,$invNumParam,$invIdParam,($delivery_route!==''?$delivery_route:null),$createdAtOverride]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO parcels (customer_id, supplier_id, from_branch_id, to_branch_id, weight, price, status, tracking_number, vehicle_no, invoice_no, invoice_number, invoice_id, delivery_route) VALUES (?,?,?,?,?,?,?, NULLIF(?, ''), ?, ?, ?, ?, ?)");
                        $stmt->execute([$customer_id,$supplier_id,$from_branch_id,$to_branch_id,$weight,$price,$status,$tracking_number,$vehicle_no,$invoice_no,$invNumParam,$invIdParam,($delivery_route!==''?$delivery_route:null)]);
                    }
                } else {
                    // Other branches: do not set price at create
                    $createdAtOverride = trim($_POST['created_date'] ?? '');
                    if ($createdAtOverride) {
                        $stmt = $pdo->prepare("INSERT INTO parcels (customer_id, supplier_id, from_branch_id, to_branch_id, weight, status, tracking_number, vehicle_no, invoice_no, invoice_number, invoice_id, delivery_route, created_at) VALUES (?,?,?,?,?,?,NULLIF(?, ''),?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$customer_id,$supplier_id,$from_branch_id,$to_branch_id,$weight,$status,$tracking_number,$vehicle_no,$invoice_no,$invNumParam,$invIdParam,($delivery_route!==''?$delivery_route:null),$createdAtOverride]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO parcels (customer_id, supplier_id, from_branch_id, to_branch_id, weight, status, tracking_number, vehicle_no, invoice_no, invoice_number, invoice_id, delivery_route) VALUES (?,?,?,?,?,?,NULLIF(?, ''),?, ?, ?, ?, ?)");
                        $stmt->execute([$customer_id,$supplier_id,$from_branch_id,$to_branch_id,$weight,$status,$tracking_number,$vehicle_no,$invoice_no,$invNumParam,$invIdParam,($delivery_route!==''?$delivery_route:null)]);
                    }
                }
                $id = (int)$pdo->lastInsertId();
                // Auto-generate tracking number if not provided by user at create
                if (($tracking_number_raw === '' || $tracking_number_raw === null) && $id > 0) {
                    try {
                        $autoSerial = 'SR' . date('ymd') . '-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
                        $pdo->prepare('UPDATE parcels SET tracking_number=? WHERE id=?')->execute([$autoSerial, $id]);
                    } catch (Throwable $e) { /* ignore serial errors */ }
                }
                // Insert item rows on create so list can show descriptions immediately
                if ($id > 0 && is_array($items)) {
                    try {
                        $insItem = $pdo->prepare('INSERT INTO parcel_items (parcel_id, qty, description, rate, additional_amount, additional_amounts) VALUES (?,?,?,?,?,?)');
                        foreach ($items as $it) {
                            $desc = trim($it['description'] ?? '');
                            $qty = (float)($it['qty'] ?? 0);
                            $rate = (float)($it['rate'] ?? 0);
                            if ($rate <= 0) { $rs = (float)($it['rs'] ?? 0); $cts = (float)($it['cts'] ?? 0); $rate = $rs + ($cts/100.0); }
                            $rate = ($rate > 0) ? $rate : null;
                            $addArr = $it['additional_amounts'] ?? [];
                            if (is_string($addArr)) { $addArr = json_decode($addArr, true) ?: []; }
                            $addAmt = 0;
                            foreach ((array)$addArr as $a) { $addAmt += (float)$a; }
                            $addAmt = ($addAmt > 0) ? $addAmt : null;
                            $addJson = !empty($addArr) ? json_encode(array_values(array_filter(array_map('floatval', $addArr)))) : null;
                            if ($addJson === '[]') $addJson = null;
                            if ($desc !== '' || $qty > 0) {
                                $insItem->execute([$id, $qty, $desc, $rate, $addAmt, $addJson]);
                            }
                        }
                    } catch (Throwable $e) { /* ignore if table missing */ }
                }
            }
            // Daily invoice: link parcel, recalculate invoice totals, attach delivery note
            if ($parcelWasCreate && $id > 0 && $customer_id > 0) {
                try {
                    $dt = $pdo->prepare('SELECT DATE(created_at) AS d FROM parcels WHERE id=?');
                    $dt->execute([$id]);
                    $dd = $dt->fetch();
                    $billYmd = $dd && !empty($dd['d']) ? (string)$dd['d'] : $billDate;
                    if (!empty($invoice_id)) {
                        ParcelBillingService::linkParcelAndRecalculate(
                            $pdo,
                            $id,
                            $invoice_id,
                            $customer_id,
                            $billYmd,
                            $invoice_no,
                            $invoice_number,
                            $from_branch_id,
                            $to_branch_id
                        );
                    }
                    if ($to_branch_id > 0) {
                        $amt = ParcelBillingService::computeParcelAmount($pdo, $id, $price !== null ? (float)$price : null);
                        ParcelBillingService::attachParcelToBill(
                            $pdo,
                            $id,
                            $customer_id,
                            $to_branch_id,
                            $billYmd,
                            $amt,
                            $invoice_no,
                            $invoice_number
                        );
                        if (!empty($invoice_id)) {
                            ParcelBillingService::recalculateInvoiceTotals($pdo, $invoice_id);
                        }
                    }
                } catch (Throwable $e) { /* ignore link errors */ }
            }
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Parcel save failed: ' . $e->getMessage());
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(500);
                    echo json_encode(['ok'=>false,'error'=>'Could not save parcel. Please try again.']);
                    break;
                }
                throw $e;
            }

            $delivery_location = ParcelSaveService::sanitizeDeliveryLocation($delivery_location);
            if ($customer_id > 0 && $delivery_location !== '' && !$priceOnlyEdit) {
                ParcelSaveService::persistCustomerDeliveryLocation($pdo, $customer_id, $delivery_location);
            }
            $actorRow = Auth::user();
            $actorUid = isset($actorRow['id']) ? (int)$actorRow['id'] : null;
            ParcelSaveService::logParcelActivity(
                $pdo,
                (int)$id,
                $parcelWasCreate ? 'created' : 'updated',
                $actorUid,
                [
                    'weight' => $weight,
                    'status' => $status,
                    'tracking' => $tracking_number,
                ]
            );
            
            // Store idempotency key to prevent duplicate submissions
            if ($id > 0) {
                $_SESSION[$idempotencySessionKey] = $id;
                // Clear idempotency key after 1 minute to allow legitimate resubmissions
                // (but prevent immediate duplicates)
            }
            
            // Include RouteHelper for automatic route assignment
            require_once __DIR__ . '/../app/helpers/RouteHelper.php';
            
            // Auto-assign route and load number for the parcel
            if ($id > 0) {
                try {
                    // Assign route and load number for the main trip
                    RouteHelper::assignRouteAndLoadNumber($pdo, $id, $from_branch_id, $to_branch_id);
                    $hubId = BranchRepository::getMainBranchId($pdo);
                    if ($hubId > 0 && $to_branch_id === $hubId) {
                        RouteHelper::checkAndAssignReturnLoad($pdo, $id, $from_branch_id, $to_branch_id);
                    }
                } catch (Exception $e) {
                    // Log the error but don't fail the entire operation
                    error_log('Error assigning route/load number: ' . $e->getMessage());
                }
            }

            // Record delivery route assignment so parcel list shows Delivery Route for this customer/branch/date
            if ($id > 0 && $customer_id > 0 && trim($vehicle_no ?? '') !== '') {
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS delivery_route_assignments (
                        id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                        customer_id BIGINT UNSIGNED NOT NULL,
                        branch_id BIGINT UNSIGNED NOT NULL,
                        delivery_date DATE NOT NULL,
                        vehicle_no VARCHAR(60) NOT NULL,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY uniq_customer_branch_date (customer_id, branch_id, delivery_date)
                    ) ENGINE=InnoDB");
                    $dateStmt = $pdo->prepare('SELECT created_at FROM parcels WHERE id=? LIMIT 1');
                    $dateStmt->execute([$id]);
                    $dateRow = $dateStmt->fetch();
                    $parcelDate = $dateRow && !empty($dateRow['created_at']) ? date('Y-m-d', strtotime($dateRow['created_at'])) : date('Y-m-d');
                    $insDra = $pdo->prepare('INSERT INTO delivery_route_assignments (customer_id, branch_id, delivery_date, vehicle_no) VALUES (?,?,?,?)
                        ON DUPLICATE KEY UPDATE vehicle_no=VALUES(vehicle_no), updated_at=CURRENT_TIMESTAMP');
                    if ($to_branch_id > 0) {
                        $insDra->execute([$customer_id, $to_branch_id, $parcelDate, trim($vehicle_no)]);
                    }
                    if ($from_branch_id > 0) {
                        $insDra->execute([$customer_id, $from_branch_id, $parcelDate, trim($vehicle_no)]);
                    }
                } catch (Throwable $e) { /* ignore */ }
            }
            
            // Send customer notification email (best-effort) with enriched details
            try {
                $cStmt = $pdo->prepare('SELECT name, email, phone FROM customers WHERE id=? LIMIT 1');
                $cStmt->execute([$customer_id]);
                $cRow = $cStmt->fetch();
                $toEmail = trim((string)($cRow['email'] ?? ''));
                if ($toEmail !== '' && isset($GLOBALS['mailer']) && $GLOBALS['mailer'] instanceof Mailer) {
                    // Current parcel items
                    $itStmt = $pdo->prepare('SELECT description, qty, COALESCE(rate,0) AS rate FROM parcel_items WHERE parcel_id=? ORDER BY id');
                    $itStmt->execute([(int)$id]);
                    $itemsNow = $itStmt->fetchAll();
                    $rowsHtml = '';
                    $totalNow = 0.0;
                    foreach ($itemsNow as $it) {
                        $desc = (string)($it['description'] ?? '');
                        $qty = (float)($it['qty'] ?? 0);
                        $rate = (float)($it['rate'] ?? 0);
                        $amt = $qty * $rate; $totalNow += $amt;
                        $rowsHtml .= '<tr><td>'.htmlspecialchars($desc).'</td><td>'.number_format($qty,2).'</td><td class="text-end">'.number_format($rate,2).'</td><td class="text-end">'.number_format($amt,2).'</td></tr>';
                    }
                    if ($rowsHtml === '') { $rowsHtml = '<tr><td colspan="4">No item details available.</td></tr>'; }
                    // Recent history
                    $hStmt = $pdo->prepare('SELECT id, created_at, COALESCE(price,0) AS price FROM parcels WHERE customer_id=? ORDER BY created_at DESC, id DESC LIMIT 10');
                    $hStmt->execute([$customer_id]);
                    $histHtml = '';
                    foreach ($hStmt->fetchAll() as $h) {
                        $histHtml .= '<tr><td>#'.(int)$h['id'].'</td><td>'.htmlspecialchars((string)$h['created_at']).'</td><td class="text-end">'.number_format((float)$h['price'],2).'</td></tr>';
                    }
                    if ($histHtml === '') { $histHtml = '<tr><td colspan="3">No previous parcels.</td></tr>'; }
                    $custName = (string)($cRow['name'] ?? 'Customer');
                    // Fetch extra details for the newly saved parcel
                    $pRow = null;
                    try { $ps = $pdo->prepare('SELECT p.*, bf.name AS from_branch, bt.name AS to_branch FROM parcels p LEFT JOIN branches bf ON bf.id=p.from_branch_id LEFT JOIN branches bt ON bt.id=p.to_branch_id WHERE p.id=?'); $ps->execute([(int)$id]); $pRow = $ps->fetch(); } catch (Throwable $e2) { $pRow = null; }
                    $createdAt = $pRow['created_at'] ?? date('Y-m-d H:i:s');
                    $vehNo = trim((string)($pRow['vehicle_no'] ?? $vehicle_no));
                    $fromName = (string)($pRow['from_branch'] ?? '');
                    $toName = (string)($pRow['to_branch'] ?? '');
                    $priceNow = isset($pRow['price']) ? (float)$pRow['price'] : (float)$totalNow;
                    $itemCount = is_array($itemsNow) ? count($itemsNow) : 0;
                    $subject = 'Parcel Order #' . (int)$id . ' â€” ' . number_format($priceNow,2);
                    $html = '<div style="font-family:Arial,sans-serif">'
                          . '<h3 style="margin:0 0 8px;">Parcel Order #' . (int)$id . '</h3>'
                          . '<div style="color:#555;margin:0 0 6px;">Order Time: ' . htmlspecialchars((string)$createdAt) . '</div>'
                          . '<div style="color:#555;margin:0 0 6px;">From: ' . htmlspecialchars($fromName) . ' &rarr; To: ' . htmlspecialchars($toName) . '</div>'
                          . '<div style="color:#555;margin:0 0 12px;">Vehicle: ' . htmlspecialchars($vehNo) . ' | Items: ' . (int)$itemCount . ' | Price: ' . number_format($priceNow,2) . '</div>'
                          . '<p>Dear '.htmlspecialchars($custName).', the following item(s) were recorded for your parcel:</p>'
                          . '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">'
                          . '<thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody>'
                          . $rowsHtml
                          . '</tbody><tfoot><tr><th colspan="3" style="text-align:right">Total</th><th class="text-end">'.number_format($totalNow,2).'</th></tr></tfoot></table>'
                          . '<h4 style="margin-top:16px">Recent History</h4>'
                          . '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">'
                          . '<thead><tr><th>Parcel</th><th>Date</th><th>Total Price</th></tr></thead><tbody>'
                          . $histHtml
                          . '</tbody></table>'
                          . '</div>';
                    $alt = "Parcel Order #" . (int)$id . "\nOrder Time: " . (string)$createdAt . "\nFrom: " . $fromName . " -> To: " . $toName . "\nVehicle: " . $vehNo . "\nItems: " . (int)$itemCount . "\nPrice: " . number_format($priceNow,2);
                    $okMail = $GLOBALS['mailer']->send($toEmail, $custName, $subject, $html, $alt);
                    // Log result
                    try {
                        $ins = $pdo->prepare('INSERT INTO parcel_emails (parcel_id, to_email, subject, html_body, text_body, status, error) VALUES (?,?,?,?,?,?,?)');
                        $err = '';
                        if (!$okMail && isset($GLOBALS['mailer']) && method_exists($GLOBALS['mailer'], 'getLastError')) { $err = (string)$GLOBALS['mailer']->getLastError(); }
                        $ins->execute([(int)$id, $toEmail, $subject, $html, $alt, ($okMail?'sent':'failed'), ($err!==''?$err:null)]);
                    } catch (Throwable $e) { /* ignore */ }
                    // Persist status and simple content on parcel row
                    try {
                        $up = $pdo->prepare('UPDATE parcels SET last_email_status=?, last_emailed_at=NOW(), last_email_subject=?, last_email_text=? WHERE id=?');
                        $up->execute([ $okMail ? 'sent' : 'failed', (string)$subject, (string)$alt, (int)$id ]);
                    } catch (Throwable $e3) { /* ignore */ }
                }
                $toPhone = trim((string)($cRow['phone'] ?? ''));
                if ($toPhone !== '' && isset($GLOBALS['sms']) && method_exists($GLOBALS['sms'], 'sendText') && $GLOBALS['sms']->isEnabled()) {
                    $msg = 'Parcel #' . (int)$id . ' | ' . $fromName . ' -> ' . $toName . ' | Veh: ' . $vehNo . ' | Items: ' . (int)$itemCount . ' | Price: ' . number_format($priceNow,2);
                    $GLOBALS['sms']->sendText($toPhone, $msg);
                }
            } catch (Throwable $e) { /* ignore */ }

            // Remember this record's lorry_full state for edit form rendering in this session
            if (!isset($_SESSION['lorry_full_saved']) || !is_array($_SESSION['lorry_full_saved'])) {
                $_SESSION['lorry_full_saved'] = [];
            }
            $_SESSION['lorry_full_saved'][(int)$id] = $lorry_full ? 1 : 0;
            // Flash message with last saved parcel info
            $_SESSION['flash_parcel_saved'] = [
                'id' => (int)$id,
                'customer_id' => (int)$customer_id,
                'vehicle_no' => (string)$vehicle_no,
                'from_branch_id' => (int)$from_branch_id,
                'to_branch_id' => (int)$to_branch_id,
                'time' => date('Y-m-d H:i:s')
            ];

            // If status just changed to in_transit, prompt user to create a new bill (Delivery Note)
            if ($becameInTransit) {
                $_SESSION['flash_bill_prompt'] = [
                    'parcel_id' => (int)$id,
                    'customer_id' => (int)$customer_id,
                    'date' => date('Y-m-d')
                ];
                $redir = Helpers::baseUrl('index.php?page=parcels&action=edit&id='.(int)$id.'&prompt_bill=1');
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok'=>true,'id'=>(int)$id,'redirect'=>$redir,'prompt_bill'=>true]);
                    break;
                }
                Helpers::redirect($redir);
                break;
            }
            // After save: stay in create flow with previous selections
            // Prefill customer, branches and vehicle so user can add next parcel quickly
            $q = 'index.php?page=parcels&action=new'
               . '&customer_id=' . urlencode((string)$customer_id)
               . '&vehicle_no=' . urlencode((string)$vehicle_no)
               . '&from_branch_id=' . urlencode((string)$from_branch_id)
               . '&to_branch_id=' . urlencode((string)$to_branch_id)
               . '&date=' . urlencode($billDate);
            $billSummaryAfterSave = ($customer_id > 0)
                ? ParcelBillingService::findExistingBill($pdo, $customer_id, $billDate, $from_branch_id, $to_branch_id)
                : null;
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => true,
                    'id' => (int)$id,
                    'redirect' => Helpers::baseUrl($q),
                    'invoice_no' => $invoice_no,
                    'invoice_number' => $invoice_number,
                    'bill_reused' => $billReused,
                    'bill' => $billSummaryAfterSave,
                ]);
                break;
            }
            Helpers::redirect($q);
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    $pdo->beginTransaction();
                    // If parcel is linked to any delivery notes, detach and recalc totals
                    $sel = $pdo->prepare('SELECT DISTINCT delivery_note_id FROM delivery_note_parcels WHERE parcel_id=?');
                    $sel->execute([$id]);
                    $affectedDns = array_map(function($r){ return (int)$r['delivery_note_id']; }, $sel->fetchAll() ?: []);
                    if ($affectedDns) {
                        $pdo->prepare('DELETE FROM delivery_note_parcels WHERE parcel_id=?')->execute([$id]);
                        // Recalculate totals for affected delivery notes
                        $sumStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS s FROM delivery_note_parcels WHERE delivery_note_id=?');
                        $updStmt = $pdo->prepare('UPDATE delivery_notes SET total_amount=? WHERE id=?');
                        foreach ($affectedDns as $dnId) {
                            $sumStmt->execute([$dnId]);
                            $s = (float)($sumStmt->fetch()['s'] ?? 0);
                            $updStmt->execute([$s, $dnId]);
                        }
                    }
                    // Delete parcel items first to satisfy FK
                    try { $pdo->prepare('DELETE FROM parcel_items WHERE parcel_id=?')->execute([$id]); } catch (Throwable $e) { /* ignore if table absent */ }
                    $invSt = $pdo->prepare('SELECT invoice_id FROM parcels WHERE id=? LIMIT 1');
                    $invSt->execute([$id]);
                    $invRow = $invSt->fetch(PDO::FETCH_ASSOC);
                    $affectedInvoiceId = (int)($invRow['invoice_id'] ?? 0);
                    // Delete the parcel
                    $pdo->prepare('DELETE FROM parcels WHERE id=?')->execute([$id]);
                    if ($affectedInvoiceId > 0) {
                        ParcelBillingService::recalculateInvoiceTotals($pdo, $affectedInvoiceId);
                    }
                    $pdo->commit();
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) { $pdo->rollBack(); }
                    // Fall through to redirect; optionally could log $e
                }
            }
            Helpers::redirect('index.php?page=parcels');
            break;
        }

        if ($action === 'new') {
            $parcel = [
                'id'=>0,
                'customer_id'=>0,
                'supplier_id'=>0,
                'from_branch_id'=> (int)($user['branch_id'] ?? 0),
                'to_branch_id'=>0,
                'weight'=>0,
                'price'=>null,
                'status'=>'pending',
                'tracking_number'=>'',
                'vehicle_no'=>''
            ];
            // Prefill from query (e.g. from "Add more parcel")
            $preVeh = isset($_GET['vehicle_no']) ? trim((string)$_GET['vehicle_no']) : '';
            if ($preVeh !== '') { $parcel['vehicle_no'] = $preVeh; }
            $pre = (int)($_GET['customer_id'] ?? 0);
            if ($pre > 0) { $parcel['customer_id'] = $pre; }
            $preFrom = (int)($_GET['from_branch_id'] ?? 0);
            if ($preFrom > 0) { $parcel['from_branch_id'] = $preFrom; }
            $preTo = (int)($_GET['to_branch_id'] ?? 0);
            if ($preTo > 0) { $parcel['to_branch_id'] = $preTo; }
            // Same-day bill: prefill date from query (Y-m-d)
            $preDate = isset($_GET['date']) ? trim((string)$_GET['date']) : '';
            if ($preDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $preDate)) {
                $parcel['created_at'] = $preDate . ' 00:00:00';
            }
            $items = [];
            // Load vehicles list
            try {
                $vehiclesAll = $pdo->query('SELECT id, reg_number AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll();
            } catch (Throwable $e) {
                try { $vehiclesAll = $pdo->query('SELECT id, plate_no AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); }
                catch (Throwable $e2) {
                    try { $vehiclesAll = $pdo->query('SELECT id, vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); }
                    catch (Throwable $e3) { $vehiclesAll = []; }
                }
            }
            $deliveryRoutesAll = [];
            try { $deliveryRoutesAll = $pdo->query('SELECT id, name FROM delivery_routes ORDER BY name')->fetchAll(); } catch (Throwable $e) { $deliveryRoutesAll = []; }

            // If a customer is already preselected, prefill prior billing-related fields from the customer's most recent parcel
            // (only for fields not explicitly provided via query string).
            $cidPrefill = (int)($parcel['customer_id'] ?? 0);
            if ($cidPrefill > 0) {
                try {
                    $lp = $pdo->prepare('SELECT supplier_id, from_branch_id, to_branch_id, vehicle_no, delivery_route
                                         FROM parcels WHERE customer_id=? ORDER BY created_at DESC, id DESC LIMIT 1');
                    $lp->execute([$cidPrefill]);
                    $lastForCustomer = $lp->fetch();
                    if ($lastForCustomer) {
                        if ((int)$preFrom <= 0 && (int)($lastForCustomer['from_branch_id'] ?? 0) > 0) { $parcel['from_branch_id'] = (int)$lastForCustomer['from_branch_id']; }
                        if ((int)$preTo <= 0 && (int)($lastForCustomer['to_branch_id'] ?? 0) > 0) { $parcel['to_branch_id'] = (int)$lastForCustomer['to_branch_id']; }
                        if ($preVeh === '' && trim((string)($lastForCustomer['vehicle_no'] ?? '')) !== '') { $parcel['vehicle_no'] = (string)$lastForCustomer['vehicle_no']; }
                        if ((int)($parcel['supplier_id'] ?? 0) <= 0 && (int)($lastForCustomer['supplier_id'] ?? 0) > 0) { $parcel['supplier_id'] = (int)$lastForCustomer['supplier_id']; }
                        if (trim((string)($parcel['delivery_route'] ?? '')) === '' && trim((string)($lastForCustomer['delivery_route'] ?? '')) !== '') { $parcel['delivery_route'] = (string)$lastForCustomer['delivery_route']; }
                    }
                } catch (Throwable $e) { /* ignore */ }
            }
            if ((int)($parcel['from_branch_id'] ?? 0) <= 0) {
                $defFrom = BranchRepository::resolveToFixedBranchId($pdo, (int)($user['branch_id'] ?? 0));
                if ($defFrom <= 0) {
                    $defFrom = BranchRepository::getDefaultBranchIdForForms($pdo);
                }
                if ($defFrom > 0) {
                    $parcel['from_branch_id'] = $defFrom;
                }
            }
            BranchRepository::normalizeParcelBranchIds($pdo, $parcel);
            // Last bill (most recent parcel) for "Open last bill" / "Add more parcel" options
            $lastParcel = null;
            try {
                $lastStmt = $pdo->prepare('SELECT id, customer_id, vehicle_no, from_branch_id, to_branch_id, created_at
                                           FROM parcels
                                           WHERE DATE(created_at) = ?
                                           ORDER BY created_at DESC, id DESC
                                           LIMIT 1');
                $lastStmt->execute([date('Y-m-d')]);
                $lastParcel = $lastStmt->fetch() ?: null;
            } catch (Throwable $e) { /* ignore */ }

            // Same-day bill summary for UI (customer + parcel date)
            $todayBillSummary = null;
            $billDatePre = $preDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $preDate) ? $preDate : date('Y-m-d');
            if ($cidPrefill > 0) {
                $fromPre = BranchRepository::resolveToFixedBranchId($pdo, (int)($parcel['from_branch_id'] ?? 0));
                $toPre = BranchRepository::resolveToFixedBranchId($pdo, (int)($parcel['to_branch_id'] ?? 0));
                $todayBillSummary = ParcelBillingService::findExistingBill($pdo, $cidPrefill, $billDatePre, $fromPre, $toPre);
                if ($todayBillSummary) {
                    $parcel['invoice_no'] = (int)$todayBillSummary['invoice_no'];
                    $parcel['invoice_number'] = (string)$todayBillSummary['invoice_number'];
                }
            }

            // Determine lock/priceOnly flags for UI
            $policy = ['priceOnly'=>false,'lockAll'=>false,'canEnterItemAmounts'=>$canEnterItemAmounts];
            $branchesAll = BranchRepository::forParcelForm($pdo, $parcel);
            Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','items','vehiclesAll','policy','lastParcel','deliveryRoutesAll','todayBillSummary'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM parcels WHERE id=?');
            $stmt->execute([$id]);
            $parcel = $stmt->fetch();
            if (!$parcel) { http_response_code(404); echo 'Not found'; break; }
            BranchRepository::normalizeParcelBranchIds($pdo, $parcel);
            $itStmt = $pdo->prepare('SELECT * FROM parcel_items WHERE parcel_id=? ORDER BY id');
            $itStmt->execute([$id]);
            $items = $itStmt->fetchAll();
            // Load vehicles list
            try {
                $vehiclesAll = $pdo->query('SELECT id, vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll();
            } catch (Throwable $e) {
                try { $vehiclesAll = $pdo->query('SELECT id, plate_no AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); } catch (Throwable $e2) { $vehiclesAll = []; }
            }
            $deliveryRoutesAll = [];
            try { $deliveryRoutesAll = $pdo->query('SELECT id, name FROM delivery_routes ORDER BY name')->fetchAll(); } catch (Throwable $e) { $deliveryRoutesAll = []; }
            // When parcel is In Transit: only status change allowed (no other edit options)
            $policy = ['priceOnly'=>$isKilinochchi, 'lockAll'=>false, 'canEnterItemAmounts'=>$canEnterItemAmounts, 'statusOnlyEdit'=>false];
            if ((string)($parcel['status'] ?? '') === 'in_transit') {
                $policy['lockAll'] = true;
                $policy['statusOnlyEdit'] = true;
            }
            $branchesAll = BranchRepository::forParcelForm($pdo, $parcel);
            Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','isMain','items','vehiclesAll','policy','deliveryRoutesAll'));
            break;
        }

        // AJAX: same-day bill summary for customer + date (parcel form)
        if ($action === 'bill_for_customer_date') {
            header('Content-Type: application/json; charset=utf-8');
            if (!Auth::check()) {
                echo json_encode(['ok' => false, 'error' => 'Forbidden']);
                break;
            }
            ParcelBillingService::ensureSchema($pdo);
            $customer_id = (int)($_GET['customer_id'] ?? $_POST['customer_id'] ?? 0);
            $from_branch_id = BranchRepository::resolveToFixedBranchId($pdo, (int)($_GET['from_branch_id'] ?? $_POST['from_branch_id'] ?? 0));
            $to_branch_id = BranchRepository::resolveToFixedBranchId($pdo, (int)($_GET['to_branch_id'] ?? $_POST['to_branch_id'] ?? 0));
            $date = trim((string)($_GET['date'] ?? $_POST['date'] ?? ''));
            if ($date === '') {
                $date = date('Y-m-d');
            }
            if ($customer_id <= 0) {
                echo json_encode(['ok' => true, 'found' => false, 'bill' => null]);
                break;
            }
            $bill = ParcelBillingService::findExistingBill($pdo, $customer_id, $date, $from_branch_id, $to_branch_id);
            echo json_encode([
                'ok' => true,
                'found' => $bill !== null,
                'bill' => $bill,
            ]);
            break;
        }

        // AJAX: get delivery route (vehicle) for customer + branch + date (for auto-pick on parcel form)
        if ($action === 'route_for_customer') {
            header('Content-Type: application/json');
            if (!Auth::check()) { echo json_encode(['vehicle_no'=>null,'delivery_date'=>null,'delivery_route'=>null]); break; }
            $pdo = Database::pdo();
            $customer_id = (int)($_GET['customer_id'] ?? $_POST['customer_id'] ?? 0);
            $to_branch_id = (int)($_GET['to_branch_id'] ?? $_POST['to_branch_id'] ?? 0);
            $from_branch_id = (int)($_GET['from_branch_id'] ?? $_POST['from_branch_id'] ?? 0);
            $date = trim($_GET['date'] ?? $_POST['date'] ?? '');
            if ($date === '') $date = date('Y-m-d');
            if ($customer_id <= 0) { echo json_encode(['vehicle_no'=>null,'delivery_date'=>null,'delivery_route'=>null]); break; }
            $vehicle_no = null;
            $delivery_date = null;
            try {
                if ($to_branch_id > 0) {
                    $stmt = $pdo->prepare('SELECT vehicle_no, delivery_date FROM delivery_route_assignments WHERE customer_id=? AND branch_id=? AND delivery_date=? LIMIT 1');
                    $stmt->execute([$customer_id, $to_branch_id, $date]);
                    $row = $stmt->fetch();
                    if ($row && trim($row['vehicle_no'] ?? '') !== '') { $vehicle_no = trim($row['vehicle_no']); $delivery_date = $row['delivery_date'] ?? null; }
                }
                if (($vehicle_no === null || $vehicle_no === '') && $from_branch_id > 0) {
                    $stmt = $pdo->prepare('SELECT vehicle_no, delivery_date FROM delivery_route_assignments WHERE customer_id=? AND branch_id=? AND delivery_date=? LIMIT 1');
                    $stmt->execute([$customer_id, $from_branch_id, $date]);
                    $row = $stmt->fetch();
                    if ($row && trim($row['vehicle_no'] ?? '') !== '') { $vehicle_no = trim($row['vehicle_no']); $delivery_date = $row['delivery_date'] ?? null; }
                }
            } catch (Throwable $e) { /* ignore */ }

            // Vehicle from latest parcel if still unknown (common when To branch not selected yet)
            if (($vehicle_no === null || $vehicle_no === '')) {
                try {
                    $vst = $pdo->prepare('SELECT vehicle_no FROM parcels WHERE customer_id=? AND TRIM(COALESCE(vehicle_no, \'\'))<>\'\' ORDER BY created_at DESC, id DESC LIMIT 1');
                    $vst->execute([$customer_id]);
                    $vr = $vst->fetch();
                    if ($vr && trim((string)($vr['vehicle_no'] ?? '')) !== '') {
                        $vehicle_no = trim((string)$vr['vehicle_no']);
                    }
                } catch (Throwable $e) { /* ignore */ }
            }

            // Suggest delivery_route name from historical parcels + fallbacks
            $delivery_route = null;
            try {
                $st = $pdo->prepare("SELECT delivery_route FROM parcels WHERE customer_id=? AND DATE(created_at)=? AND delivery_route IS NOT NULL AND TRIM(delivery_route)<>'' ORDER BY created_at DESC, id DESC LIMIT 1");
                $st->execute([$customer_id, $date]);
                $rrow = $st->fetch();
                if ($rrow && trim((string)($rrow['delivery_route'] ?? '')) !== '') {
                    $delivery_route = trim((string)$rrow['delivery_route']);
                }
                $vehResolved = ($vehicle_no !== null && $vehicle_no !== '') ? $vehicle_no : null;
                if (($delivery_route === null || $delivery_route === '') && $vehResolved !== null) {
                    $st2 = $pdo->prepare("SELECT delivery_route FROM parcels WHERE customer_id=? AND DATE(created_at)=? AND TRIM(COALESCE(vehicle_no,''))=? AND delivery_route IS NOT NULL AND TRIM(delivery_route)<>'' ORDER BY created_at DESC LIMIT 1");
                    $st2->execute([$customer_id, $date, $vehResolved]);
                    $r2 = $st2->fetch();
                    if ($r2 && trim((string)($r2['delivery_route'] ?? '')) !== '') {
                        $delivery_route = trim((string)$r2['delivery_route']);
                    }
                }
                if ($delivery_route === null || $delivery_route === '') {
                    $st3 = $pdo->prepare("SELECT delivery_route FROM parcels WHERE customer_id=? AND delivery_route IS NOT NULL AND TRIM(delivery_route)<>'' ORDER BY created_at DESC, id DESC LIMIT 1");
                    $st3->execute([$customer_id]);
                    $r3 = $st3->fetch();
                    if ($r3 && trim((string)($r3['delivery_route'] ?? '')) !== '') {
                        $delivery_route = trim((string)$r3['delivery_route']);
                    }
                }
                // Same vehicle used elsewhere (parcel may have blank route but vehicle matches a billed route)
                if (($delivery_route === null || $delivery_route === '') && $vehResolved !== null) {
                    $st4 = $pdo->prepare("SELECT delivery_route FROM parcels WHERE TRIM(COALESCE(vehicle_no,''))=? AND delivery_route IS NOT NULL AND TRIM(delivery_route)<>'' ORDER BY created_at DESC, id DESC LIMIT 1");
                    $st4->execute([$vehResolved]);
                    $r4 = $st4->fetch();
                    if ($r4 && trim((string)($r4['delivery_route'] ?? '')) !== '') {
                        $delivery_route = trim((string)$r4['delivery_route']);
                    }
                }
                // Customer delivery_location contains a configured route name
                if ($delivery_route === null || $delivery_route === '') {
                    $cst = $pdo->prepare('SELECT TRIM(COALESCE(delivery_location, \'\')) AS loc FROM customers WHERE id=? LIMIT 1');
                    $cst->execute([$customer_id]);
                    $cr = $cst->fetch();
                    $loc = trim((string)($cr['loc'] ?? ''));
                    if ($loc !== '') {
                        try {
                            $rn = $pdo->query('SELECT name FROM delivery_routes ORDER BY CHAR_LENGTH(name) DESC');
                            $routes = $rn ? $rn->fetchAll(PDO::FETCH_ASSOC) : [];
                            foreach ($routes as $rnRow) {
                                $nm = trim((string)($rnRow['name'] ?? ''));
                                if ($nm !== '' && stripos($loc, $nm) !== false) {
                                    $delivery_route = $nm;
                                    break;
                                }
                            }
                        } catch (Throwable $e5) { /* ignore */ }
                    }
                }
            } catch (Throwable $e) { /* ignore */ }

            echo json_encode(['vehicle_no'=>$vehicle_no,'delivery_date'=>$delivery_date,'delivery_route'=>$delivery_route]);
            break;
        }

        // AJAX: fetch customer's last billing-related parcel info for auto-populate on new parcel form
        if ($action === 'last_billing_for_customer') {
            header('Content-Type: application/json; charset=utf-8');
            $customer_id = (int)($_GET['customer_id'] ?? 0);
            if ($customer_id <= 0) { echo json_encode(['ok'=>false,'error'=>'customer_id required']); break; }
            try {
                $st = $pdo->prepare('SELECT id, supplier_id, from_branch_id, to_branch_id, vehicle_no, delivery_route, created_at
                                     FROM parcels WHERE customer_id=? ORDER BY created_at DESC, id DESC LIMIT 1');
                $st->execute([$customer_id]);
                $row = $st->fetch();
                if (!$row) { echo json_encode(['ok'=>true,'data'=>null]); break; }
                $drOut = trim((string)($row['delivery_route'] ?? ''));
                $vehOut = trim((string)($row['vehicle_no'] ?? ''));
                if ($drOut === '' && $vehOut !== '') {
                    try {
                        $st4 = $pdo->prepare("SELECT delivery_route FROM parcels WHERE TRIM(COALESCE(vehicle_no,''))=? AND delivery_route IS NOT NULL AND TRIM(delivery_route)<>'' ORDER BY id DESC LIMIT 1");
                        $st4->execute([$vehOut]);
                        $r4 = $st4->fetch();
                        if ($r4 && trim((string)($r4['delivery_route'] ?? '')) !== '') {
                            $drOut = trim((string)$r4['delivery_route']);
                        }
                    } catch (Throwable $e) { /* ignore */ }
                }
                if ($drOut === '') {
                    try {
                        $cst = $pdo->prepare('SELECT TRIM(COALESCE(delivery_location, \'\')) AS loc FROM customers WHERE id=? LIMIT 1');
                        $cst->execute([$customer_id]);
                        $cr = $cst->fetch();
                        $loc = trim((string)($cr['loc'] ?? ''));
                        if ($loc !== '') {
                            $rn = $pdo->query('SELECT name FROM delivery_routes ORDER BY CHAR_LENGTH(name) DESC');
                            $routes = $rn ? $rn->fetchAll(PDO::FETCH_ASSOC) : [];
                            foreach ($routes as $rnRow) {
                                $nm = trim((string)($rnRow['name'] ?? ''));
                                if ($nm !== '' && stripos($loc, $nm) !== false) {
                                    $drOut = $nm;
                                    break;
                                }
                            }
                        }
                    } catch (Throwable $e) { /* ignore */ }
                }
                echo json_encode(['ok'=>true,'data'=>[
                    'parcel_id' => (int)($row['id'] ?? 0),
                    'supplier_id' => (int)($row['supplier_id'] ?? 0),
                    'from_branch_id' => (int)($row['from_branch_id'] ?? 0),
                    'to_branch_id' => (int)($row['to_branch_id'] ?? 0),
                    'vehicle_no' => $vehOut,
                    'delivery_route' => $drOut,
                    'created_at' => (string)($row['created_at'] ?? ''),
                ]], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            } catch (Throwable $e) {
                echo json_encode(['ok'=>false,'error'=>'failed']);
            }
            break;
        }

        // AJAX: inline quick edit from parcels list (status, delivery_route, vehicle_no)
        if ($action === 'quick_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Invalid CSRF']);
                break;
            }
            $id = (int)($_POST['id'] ?? 0);
            $field = trim((string)($_POST['field'] ?? ''));
            $value = $_POST['value'] ?? '';
            $allowed = ['status', 'delivery_route', 'vehicle_no'];
            if ($id <= 0 || !in_array($field, $allowed, true)) {
                echo json_encode(['ok' => false, 'error' => 'Invalid request']);
                break;
            }
            $st = $pdo->prepare('SELECT id, price, status FROM parcels WHERE id=?');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                echo json_encode(['ok' => false, 'error' => 'Parcel not found']);
                break;
            }
            $isBilled = ($row['price'] !== null && (float)$row['price'] > 0);
            if ($isBilled && $field === 'status') {
                echo json_encode(['ok' => false, 'error' => 'Cannot change status on a billed parcel']);
                break;
            }
            if ($field === 'status') {
                $v = trim((string)$value);
                if (!in_array($v, Helpers::parcelStatusValues(), true)) {
                    echo json_encode(['ok' => false, 'error' => 'Invalid status']);
                    break;
                }
                $pdo->prepare('UPDATE parcels SET status=? WHERE id=?')->execute([$v, $id]);
                echo json_encode([
                    'ok' => true,
                    'value' => $v,
                    'label' => Helpers::parcelStatusLabel($v),
                    'badgeClass' => Helpers::parcelStatusBadgeClass($v),
                ], JSON_UNESCAPED_UNICODE);
                break;
            }
            if ($field === 'delivery_route') {
                $v = trim((string)$value);
                if (strlen($v) > 255) {
                    $v = substr($v, 0, 255);
                }
                $pdo->prepare('UPDATE parcels SET delivery_route=? WHERE id=?')->execute([$v === '' ? null : $v, $id]);
                echo json_encode(['ok' => true, 'value' => $v, 'display' => $v !== '' ? $v : 'â€”'], JSON_UNESCAPED_UNICODE);
                break;
            }
            if ($field === 'vehicle_no') {
                $v = trim((string)$value);
                if (strlen($v) > 64) {
                    $v = substr($v, 0, 64);
                }
                $pdo->prepare('UPDATE parcels SET vehicle_no=? WHERE id=?')->execute([$v === '' ? null : $v, $id]);
                echo json_encode(['ok' => true, 'value' => $v, 'display' => $v !== '' ? $v : 'â€”'], JSON_UNESCAPED_UNICODE);
                break;
            }
            echo json_encode(['ok' => false, 'error' => 'Unsupported field']);
            break;
        }

        // AJAX: parcel line items for list expand (lazy load + cache on client)
        if ($action === 'parcel_items_json') {
            header('Content-Type: application/json; charset=utf-8');
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['ok' => false, 'error' => 'id required']);
                break;
            }
            try {
                $st = $pdo->prepare('SELECT id, qty, description, rate, additional_amount, additional_amounts FROM parcel_items WHERE parcel_id=? ORDER BY id');
                $st->execute([$id]);
                $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                echo json_encode(['ok' => false, 'error' => 'failed']);
                break;
            }
            $out = [];
            $n = 0;
            foreach ($rows as $r) {
                $n++;
                $qty = (float)($r['qty'] ?? 0);
                $rate = array_key_exists('rate', $r) && $r['rate'] !== null && $r['rate'] !== '' ? (float)$r['rate'] : null;
                $amount = ($rate !== null && $qty > 0) ? round($qty * $rate, 2) : null;
                $addStored = array_key_exists('additional_amount', $r) && $r['additional_amount'] !== null && $r['additional_amount'] !== ''
                    ? (float)$r['additional_amount'] : 0.0;
                $tags = [];
                if (!empty($r['additional_amounts'])) {
                    $decoded = json_decode((string)$r['additional_amounts'], true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $v) {
                            $tags[] = round((float)$v, 2);
                        }
                    }
                }
                $add = $addStored > 0 ? $addStored : ($tags ? round(array_sum($tags), 2) : null);
                $out[] = [
                    'no' => $n,
                    'description' => (string)($r['description'] ?? ''),
                    'qty' => $qty,
                    'rate' => $rate,
                    'amount' => $amount,
                    'additional' => $add !== null && $add > 0 ? $add : null,
                    'additionalTags' => $tags,
                ];
            }
            echo json_encode(['ok' => true, 'items' => $out], JSON_UNESCAPED_UNICODE);
            break;
        }

        // AJAX: dedicated last delivery note id (used by parcel last-bill modal)
        if ($action === 'last_delivery_note_id_for_customer') {
            header('Content-Type: application/json; charset=utf-8');
            $customer_id = (int)($_GET['customer_id'] ?? 0);
            if ($customer_id <= 0) { echo json_encode(['ok'=>false,'error'=>'customer_id required']); break; }
            try {
                $st = $pdo->prepare('SELECT id, delivery_date FROM delivery_notes WHERE customer_id=? ORDER BY delivery_date DESC, id DESC LIMIT 1');
                $st->execute([$customer_id]);
                $row = $st->fetch();
                $dnId = $row ? (int)($row['id'] ?? 0) : 0;
                echo json_encode(['ok'=>true,'data'=>[
                    'delivery_note_id' => $dnId,
                    'delivery_date' => $row ? (string)($row['delivery_date'] ?? '') : ''
                ]], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            } catch (Throwable $e) {
                echo json_encode(['ok'=>false,'error'=>'failed']);
            }
            break;
        }

        // index with search filters
        $q = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
        $vehicle_no = trim($_GET['vehicle_no'] ?? '');
        $customer_filter_id = (int)($_GET['customer_id'] ?? 0);
        $from_branch_filter_id = (int)($_GET['from_branch_id'] ?? 0);
        $to_branch_filter_id = (int)($_GET['to_branch_id'] ?? 0);
        $supplier_filter_id = (int)($_GET['supplier_id'] ?? 0);
        $tracking_filter = trim($_GET['tracking_number'] ?? '');
        $invoice_no_filter = trim($_GET['invoice_no'] ?? '');
        $delivery_location_filter = trim($_GET['delivery_location'] ?? '');
        $delivery_route_filter = trim($_GET['delivery_route'] ?? '');
        $route_date = trim($_GET['route_date'] ?? '');
        $filter_type = trim($_GET['filter_type'] ?? '');
        $idsCsv = trim($_GET['ids'] ?? '');

        // Handle date filter persistence in session
        // If dates are provided in GET, save them to session
        if (isset($_GET['from']) && $_GET['from'] !== '') {
            $_SESSION['parcels_filter_from'] = $_GET['from'];
        }
        if (isset($_GET['to']) && $_GET['to'] !== '') {
            $_SESSION['parcels_filter_to'] = $_GET['to'];
        }
        // If 'clear_dates' is set, clear the session dates
        if (isset($_GET['clear_dates']) && $_GET['clear_dates'] === '1') {
            unset($_SESSION['parcels_filter_from']);
            unset($_SESSION['parcels_filter_to']);
        }
        
        // Use GET params if provided, otherwise use session, otherwise default to last 30 days
        $from = $_GET['from'] ?? ($_SESSION['parcels_filter_from'] ?? date('Y-m-d', strtotime('-30 days')));
        $to = $_GET['to'] ?? ($_SESSION['parcels_filter_to'] ?? date('Y-m-d'));
        $where = [];
        $params = [];
        // Preset: Delivery Route Planning â€” only pending/in_transit (default today if dates not in GET)
        if ($filter_type === 'route_planning') {
            if (!isset($_GET['from']) || $_GET['from'] === '') { $from = date('Y-m-d'); }
            if (!isset($_GET['to']) || $_GET['to'] === '') { $to = date('Y-m-d'); }
            $where[] = "p.status IN ('pending','in_transit','out_for_delivery')";
        }
        // Preset: Vehicle Routes â€” only parcels with vehicle assigned
        if ($filter_type === 'vehicle_routes') {
            $where[] = "(p.vehicle_no IS NOT NULL AND TRIM(COALESCE(p.vehicle_no,'')) <> '')";
        }
        // Preset: Customers â€” only that customer's parcels (customer_filter_id applied below)
        if ($q !== '') {
            $where[] = '(c.phone LIKE ? OR c.name LIKE ? OR p.tracking_number LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like, $like);
        }
        if ($vehicle_no !== '') {
            $where[] = 'p.vehicle_no LIKE ?';
            $params[] = "%$vehicle_no%";
        }
        if ($customer_filter_id > 0) {
            $where[] = 'p.customer_id = ?';
            $params[] = $customer_filter_id;
        }
        if ($from_branch_filter_id > 0) {
            $where[] = 'p.from_branch_id = ?';
            $params[] = $from_branch_filter_id;
        }
        if ($to_branch_filter_id > 0) {
            $where[] = 'p.to_branch_id = ?';
            $params[] = $to_branch_filter_id;
        }
        if ($supplier_filter_id > 0) {
            $where[] = 'p.supplier_id = ?';
            $params[] = $supplier_filter_id;
        }
        if ($tracking_filter !== '') {
            $where[] = 'p.tracking_number LIKE ?';
            $params[] = '%' . $tracking_filter . '%';
        }
        if ($invoice_no_filter !== '') {
            $invNo = (int)$invoice_no_filter;
            if ($invNo > 0) {
                $where[] = 'p.invoice_no = ?';
                $params[] = $invNo;
            }
        }
        if ($delivery_location_filter !== '') {
            $where[] = 'c.delivery_location LIKE ?';
            $params[] = '%' . $delivery_location_filter . '%';
        }
        if ($delivery_route_filter !== '') {
            $where[] = 'p.delivery_route LIKE ?';
            $params[] = '%' . $delivery_route_filter . '%';
        }
        if ($route_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $route_date)) {
            $where[] = '(EXISTS (SELECT 1 FROM delivery_route_assignments dra WHERE dra.customer_id = p.customer_id AND ((dra.branch_id = p.to_branch_id AND dra.delivery_date = ?) OR (dra.branch_id = p.from_branch_id AND dra.delivery_date = ?))))';
            array_push($params, $route_date, $route_date);
        }
        if (in_array($status, Helpers::parcelStatusValues(), true)) {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        if ($from !== '' && $to !== '') {
            $where[] = 'DATE(p.created_at) BETWEEN ? AND ?';
            array_push($params, $from, $to);
        }
        if ($idsCsv !== '') {
            $idList = array_values(array_unique(array_filter(array_map('intval', explode(',', $idsCsv)))));
            if ($idList) {
                $inPh = implode(',', array_fill(0, count($idList), '?'));
                $where[] = 'p.id IN (' . $inPh . ')';
                foreach ($idList as $ii) {
                    $params[] = $ii;
                }
            }
        }
        // Build list query, optionally joining email status if table exists
        $hasEmailLog = false;
        try { $pdo->query('SELECT 1 FROM parcel_emails LIMIT 1'); $hasEmailLog = true; } catch (Throwable $e) { $hasEmailLog = false; }
        if ($hasEmailLog) {
            $sql = 'SELECT p.id, p.customer_id, p.supplier_id, p.from_branch_id, p.to_branch_id, p.weight, p.price, p.status, p.created_at, p.updated_at,
                           p.tracking_number,
                           COALESCE(NULLIF(p.vehicle_no, ""), dra_to.vehicle_no, dra_from.vehicle_no) AS vehicle_no,
                           NULLIF(TRIM(COALESCE(p.vehicle_no, "")), "") AS vehicle_no_db,
                           COALESCE(dra_to.delivery_date, "") AS route_date_to, COALESCE(dra_from.delivery_date, "") AS route_date_from,
                           p.delivery_route,
                           (SELECT COUNT(*) FROM parcel_items pi WHERE pi.parcel_id = p.id) AS item_line_count,
                           c.name AS customer_name, c.phone AS customer_phone, c.delivery_location AS customer_delivery_location, s.name AS supplier_name, bf.name AS from_branch, bt.name AS to_branch,
                           COALESCE(pe.status, p.last_email_status) AS email_status,
                           COALESCE(pe.created_at, p.last_emailed_at) AS emailed_at,
                           pit.item_descriptions
                    FROM parcels p
                    LEFT JOIN customers c ON c.id = p.customer_id
                    LEFT JOIN suppliers s ON s.id = p.supplier_id
                    LEFT JOIN branches bf ON bf.id = p.from_branch_id
                    LEFT JOIN branches bt ON bt.id = p.to_branch_id
                    LEFT JOIN (
                        SELECT parcel_id, GROUP_CONCAT(TRIM(description) ORDER BY id SEPARATOR ", ") AS item_descriptions
                        FROM parcel_items
                        GROUP BY parcel_id
                    ) pit ON pit.parcel_id = p.id
                    LEFT JOIN (
                        SELECT pe1.* FROM parcel_emails pe1
                        INNER JOIN (
                            SELECT parcel_id, MAX(id) AS max_id FROM parcel_emails GROUP BY parcel_id
                        ) x ON x.parcel_id = pe1.parcel_id AND x.max_id = pe1.id
                    ) pe ON pe.parcel_id = p.id
                    LEFT JOIN delivery_route_assignments dra_to
                      ON dra_to.customer_id = p.customer_id
                     AND dra_to.branch_id = p.to_branch_id
                     AND dra_to.delivery_date = DATE(p.created_at)
                    LEFT JOIN delivery_route_assignments dra_from
                      ON dra_from.customer_id = p.customer_id
                     AND dra_from.branch_id = p.from_branch_id
                     AND dra_from.delivery_date = DATE(p.created_at)';
        } else {
            $sql = 'SELECT p.id, p.customer_id, p.supplier_id, p.from_branch_id, p.to_branch_id, p.weight, p.price, p.status, p.created_at, p.updated_at,
                           p.tracking_number,
                           COALESCE(NULLIF(p.vehicle_no, ""), dra_to.vehicle_no, dra_from.vehicle_no) AS vehicle_no,
                           NULLIF(TRIM(COALESCE(p.vehicle_no, "")), "") AS vehicle_no_db,
                           COALESCE(dra_to.delivery_date, "") AS route_date_to, COALESCE(dra_from.delivery_date, "") AS route_date_from,
                           p.delivery_route,
                           (SELECT COUNT(*) FROM parcel_items pi WHERE pi.parcel_id = p.id) AS item_line_count,
                           c.name AS customer_name, c.phone AS customer_phone, c.delivery_location AS customer_delivery_location, s.name AS supplier_name, bf.name AS from_branch, bt.name AS to_branch,
                           p.last_email_status AS email_status, p.last_emailed_at AS emailed_at,
                           pit.item_descriptions
                    FROM parcels p
                    LEFT JOIN customers c ON c.id = p.customer_id
                    LEFT JOIN suppliers s ON s.id = p.supplier_id
                    LEFT JOIN branches bf ON bf.id = p.from_branch_id
                    LEFT JOIN branches bt ON bt.id = p.to_branch_id
                    LEFT JOIN (
                        SELECT parcel_id, GROUP_CONCAT(TRIM(description) ORDER BY id SEPARATOR ", ") AS item_descriptions
                        FROM parcel_items
                        GROUP BY parcel_id
                    ) pit ON pit.parcel_id = p.id
                    LEFT JOIN delivery_route_assignments dra_to
                      ON dra_to.customer_id = p.customer_id
                     AND dra_to.branch_id = p.to_branch_id
                     AND dra_to.delivery_date = DATE(p.created_at)
                    LEFT JOIN delivery_route_assignments dra_from
                      ON dra_from.customer_id = p.customer_id
                     AND dra_from.branch_id = p.from_branch_id
                     AND dra_from.delivery_date = DATE(p.created_at)';
        }
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        
        // Pagination: compact list â€” more rows per page (print view loads full filtered set)
        $page = max(1, (int)($_GET['page_num'] ?? 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;
        $isPrintList = ($action === 'print_list');
        
        // Get total count for pagination (simplified query without dra joins)
        $countSql = 'SELECT COUNT(DISTINCT p.id) as total FROM parcels p
                     LEFT JOIN customers c ON c.id = p.customer_id
                     LEFT JOIN suppliers s ON s.id = p.supplier_id
                     LEFT JOIN branches bf ON bf.id = p.from_branch_id
                     LEFT JOIN branches bt ON bt.id = p.to_branch_id';
        if ($where) { $countSql .= ' WHERE ' . implode(' AND ', $where); }
        $countStmt = $pdo->prepare($countSql);
        // Count query doesn't need dra join params, only the filter params
        $countParams = [];
        if ($q !== '') {
            $like = "%$q%";
            array_push($countParams, $like, $like, $like);
        }
        if ($vehicle_no !== '') {
            $countParams[] = "%$vehicle_no%";
        }
        if ($customer_filter_id > 0) {
            $countParams[] = $customer_filter_id;
        }
        if ($from_branch_filter_id > 0) {
            $countParams[] = $from_branch_filter_id;
        }
        if ($to_branch_filter_id > 0) {
            $countParams[] = $to_branch_filter_id;
        }
        if ($supplier_filter_id > 0) {
            $countParams[] = $supplier_filter_id;
        }
        if ($tracking_filter !== '') {
            $countParams[] = '%' . $tracking_filter . '%';
        }
        if ($invoice_no_filter !== '' && (int)$invoice_no_filter > 0) {
            $countParams[] = (int)$invoice_no_filter;
        }
        if ($delivery_location_filter !== '') {
            $countParams[] = '%' . $delivery_location_filter . '%';
        }
        if ($delivery_route_filter !== '') {
            $countParams[] = '%' . $delivery_route_filter . '%';
        }
        if ($route_date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $route_date)) {
            array_push($countParams, $route_date, $route_date);
        }
        if (in_array($status, Helpers::parcelStatusValues(), true)) {
            $countParams[] = $status;
        }
        if ($from !== '' && $to !== '') {
            array_push($countParams, $from, $to);
        }
        if ($idsCsv !== '') {
            $idList = array_values(array_unique(array_filter(array_map('intval', explode(',', $idsCsv)))));
            foreach ($idList as $ii) {
                $countParams[] = $ii;
            }
        }
        $countStmt->execute($countParams);
        $totalCount = (int)$countStmt->fetch()['total'];
        $totalPages = max(1, ceil($totalCount / $perPage));
        
        $sql .= ' ORDER BY p.created_at DESC, p.id DESC';
        if (!$isPrintList) {
            $sql .= ' LIMIT ? OFFSET ?';
            $params[] = $perPage;
            $params[] = $offset;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $parcels = $stmt->fetchAll();
        // If we just sent an email and DB join isn't available, mark that parcel as sent in-memory
        if (!empty($_SESSION['email_sent_parcel_id'])) {
            $lastSentId = (int)$_SESSION['email_sent_parcel_id'];
            foreach ($parcels as &$row) {
                if ((int)$row['id'] === $lastSentId && empty($row['email_status'])) {
                    $row['email_status'] = 'sent';
                    $row['emailed_at'] = date('Y-m-d H:i:s');
                    break;
                }
            }
            unset($row);
            unset($_SESSION['email_sent_parcel_id']);
        }
        $parcelItemsById = [];
        if ($parcels) {
            $pidList = [];
            foreach ($parcels as $prow) {
                $pid = (int)($prow['id'] ?? 0);
                if ($pid > 0) {
                    $pidList[$pid] = true;
                }
            }
            $ids = array_keys($pidList);
            if ($ids) {
                try {
                    $ph = implode(',', array_fill(0, count($ids), '?'));
                    $itSt = $pdo->prepare("SELECT parcel_id, id, qty, description, rate, additional_amount, additional_amounts FROM parcel_items WHERE parcel_id IN ($ph) ORDER BY parcel_id, id");
                    $itSt->execute($ids);
                    while ($ir = $itSt->fetch(PDO::FETCH_ASSOC)) {
                        $pk = (int)$ir['parcel_id'];
                        if (!isset($parcelItemsById[$pk])) {
                            $parcelItemsById[$pk] = [];
                        }
                        $parcelItemsById[$pk][] = $ir;
                    }
                } catch (Throwable $e) {
                    $parcelItemsById = [];
                }
            }
        }
        // customers, branches, suppliers for filter
        $customersList = $pdo->query('SELECT id, name, phone FROM customers ORDER BY name LIMIT 500')->fetchAll();
        $branchesFilterList = BranchRepository::forFilters($pdo);
        $suppliersFilterList = $pdo->query('SELECT id, name FROM suppliers ORDER BY name LIMIT 300')->fetchAll();
        $deliveryRoutesFilterList = [];
        try { $deliveryRoutesFilterList = $pdo->query('SELECT id, name FROM delivery_routes ORDER BY name')->fetchAll(); } catch (Throwable $e) { $deliveryRoutesFilterList = []; }
        $vehiclesQuickList = [];
        try {
            $vehiclesQuickList = $pdo->query('SELECT reg_number AS vehicle_no FROM vehicles WHERE reg_number IS NOT NULL AND TRIM(reg_number) <> "" ORDER BY reg_number ASC LIMIT 400')->fetchAll();
        } catch (Throwable $e) {
            try {
                $vehiclesQuickList = $pdo->query('SELECT plate_no AS vehicle_no FROM vehicles WHERE plate_no IS NOT NULL AND TRIM(plate_no) <> "" ORDER BY plate_no ASC LIMIT 400')->fetchAll();
            } catch (Throwable $e2) {
                try {
                    $vehiclesQuickList = $pdo->query('SELECT vehicle_no FROM vehicles WHERE vehicle_no IS NOT NULL AND TRIM(vehicle_no) <> "" ORDER BY vehicle_no ASC LIMIT 400')->fetchAll();
                } catch (Throwable $e3) {
                    $vehiclesQuickList = [];
                }
            }
        }
        $parcelRowStart = $isPrintList ? 0 : (($page - 1) * $perPage); // print: numbering from 1 over full set
        if ($action === 'print_list') {
            Helpers::view('parcels/print_list', compact('parcels','q','status','vehicle_no','customer_filter_id','from_branch_filter_id','to_branch_filter_id','supplier_filter_id','tracking_filter','invoice_no_filter','delivery_location_filter','delivery_route_filter','route_date','filter_type','from','to','totalCount','parcelRowStart','parcelItemsById'));
        } else {
            Helpers::view('parcels/index', compact('parcels','q','status','vehicle_no','customer_filter_id','from_branch_filter_id','to_branch_filter_id','supplier_filter_id','tracking_filter','invoice_no_filter','delivery_location_filter','delivery_route_filter','route_date','filter_type','customersList','from','to','branchesFilterList','suppliersFilterList','deliveryRoutesFilterList','vehiclesQuickList','isMain','canCreateParcels','isKilinochchi','page','totalPages','totalCount','parcelRowStart','parcelItemsById'));
        }
        break;

    case 'email_log':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        // Ensure required tables/columns exist even if user lands directly here
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS parcel_emails (
                id INT AUTO_INCREMENT PRIMARY KEY,
                parcel_id INT NOT NULL,
                to_email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                html_body MEDIUMTEXT NULL,
                text_body MEDIUMTEXT NULL,
                status ENUM('sent','failed') NOT NULL,
                error TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_parcel (parcel_id),
                CONSTRAINT fk_parcel_emails_parcel FOREIGN KEY (parcel_id) REFERENCES parcels(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) { /* ignore */ }
        try { $pdo->exec("ALTER TABLE parcels ADD COLUMN last_email_status VARCHAR(10) NULL"); } catch (Throwable $e) { /* ignore if exists */ }
        try { $pdo->exec("ALTER TABLE parcels ADD COLUMN last_emailed_at DATETIME NULL"); } catch (Throwable $e) { /* ignore if exists */ }
        try { $pdo->exec("ALTER TABLE parcels ADD COLUMN last_email_subject VARCHAR(255) NULL"); } catch (Throwable $e) { /* ignore if exists */ }
        try { $pdo->exec("ALTER TABLE parcels ADD COLUMN last_email_text MEDIUMTEXT NULL"); } catch (Throwable $e) { /* ignore if exists */ }
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { Helpers::redirect('index.php?page=parcels'); break; }
        $p = $pdo->prepare('SELECT p.id, p.last_email_status, p.last_emailed_at, p.last_email_subject, p.last_email_text, c.name AS customer_name, c.email AS customer_email FROM parcels p LEFT JOIN customers c ON c.id=p.customer_id WHERE p.id=?');
        $p->execute([$id]);
        $parcelHdr = $p->fetch();
        $logs = [];
        try {
            $pdo->query('SELECT 1 FROM parcel_emails LIMIT 1');
            $st = $pdo->prepare('SELECT id, to_email, subject, html_body, text_body, status, error, created_at FROM parcel_emails WHERE parcel_id=? ORDER BY id DESC');
            $st->execute([$id]);
            $logs = $st->fetchAll();
        } catch (Throwable $e) { $logs = []; }
        // Fallback: if no DB logs exist, but parcel has last_email_status/time, show one synthesized row
        if (empty($logs) && !empty($parcelHdr) && !empty($parcelHdr['last_email_status'])) {
            $logs[] = [
                'id' => 0,
                'to_email' => (string)($parcelHdr['customer_email'] ?? ''),
                'subject' => (string)($parcelHdr['last_email_subject'] ?? '(not recorded)'),
                'html_body' => null,
                'text_body' => (string)($parcelHdr['last_email_text'] ?? ''),
                'status' => (string)$parcelHdr['last_email_status'],
                'error' => null,
                'created_at' => (string)($parcelHdr['last_emailed_at'] ?? '')
            ];
        }
        Helpers::view('parcels/email_log', compact('parcelHdr','logs'));
        break;

    case 'parcel_print':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        // UTF-8 output for the invoice preview iframe (Tamil rendering issues on cPanel)
        header('Content-Type: text/html; charset=utf-8');
        $pdo = Database::pdo();
        try { $pdo->exec('ALTER TABLE parcels ADD COLUMN invoice_no INT UNSIGNED NULL'); } catch (Throwable $e) { /* ignore if exists */ }
        try { $pdo->exec('ALTER TABLE parcel_items ADD COLUMN additional_amount DECIMAL(12,2) NULL'); } catch (Throwable $e) { /* ignore if exists */ }
        try { $pdo->exec('ALTER TABLE parcel_items ADD COLUMN additional_amounts TEXT NULL'); } catch (Throwable $e) { /* ignore if exists */ }
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT p.*, c.name AS customer_name, c.phone AS customer_phone, c.delivery_location AS delivery_location, s.name AS supplier_name, s.phone AS supplier_phone, bf.name AS from_branch, bt.name AS to_branch FROM parcels p LEFT JOIN customers c ON c.id=p.customer_id LEFT JOIN suppliers s ON s.id = p.supplier_id LEFT JOIN branches bf ON bf.id=p.from_branch_id LEFT JOIN branches bt ON bt.id=p.to_branch_id WHERE p.id=?');
        $stmt->execute([$id]);
        $parcel = $stmt->fetch();
        if (!$parcel) { http_response_code(404); echo 'Not found'; break; }
        $it = $pdo->prepare('SELECT * FROM parcel_items WHERE parcel_id=? ORDER BY id');
        $it->execute([$id]);
        $items = $it->fetchAll();
        BranchRepository::ensureSchema($pdo);
        $invoiceHeaderBranches = BranchRepository::invoiceHeaderBranchesThree($pdo);
        $printEmbed = isset($_GET['embed']) && (string)$_GET['embed'] === '1';
        include __DIR__ . '/../views/parcels/print.php';
        break;

    case 'delivery_notes':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        if (!Auth::hasAnyRole(['admin','parcel_user','staff'])) { http_response_code(403); echo 'Forbidden'; break; }
        // UTF-8 output for print pages
        header('Content-Type: text/html; charset=utf-8');
        $pdo = Database::pdo();
        try { $pdo->exec('ALTER TABLE parcel_items ADD COLUMN additional_amount DECIMAL(12,2) NULL'); } catch (Throwable $e) { /* ignore if exists */ }
        try { $pdo->exec('ALTER TABLE parcel_items ADD COLUMN additional_amounts TEXT NULL'); } catch (Throwable $e) { /* ignore if exists */ }
        $user = Auth::user();
        $branchId = BranchRepository::resolveToFixedBranchId($pdo, (int)($user['branch_id'] ?? 0));
        if ($branchId <= 0) {
            $mainRow = $pdo->query('SELECT id FROM branches WHERE is_main = 1 ORDER BY id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
            $branchId = (int)($mainRow['id'] ?? 2);
        }
        $branchFilterId = Auth::isMainBranch() ? BranchRepository::resolveToFixedBranchId($pdo, (int)($_GET['branch_id'] ?? $branchId)) : $branchId;
        if ($branchFilterId <= 0) {
            $branchFilterId = $branchId;
        }
        $action = $_GET['action'] ?? 'index';
        // Ensure per-DN email status columns exist
        try { $pdo->exec("ALTER TABLE delivery_notes ADD COLUMN last_email_status VARCHAR(10) NULL"); } catch (Throwable $e) { /* ignore if exists */ }
        try { $pdo->exec("ALTER TABLE delivery_notes ADD COLUMN last_emailed_at DATETIME NULL"); } catch (Throwable $e) { /* ignore if exists */ }
        // Also store last email subject/text as fallback for logs
        try { $pdo->exec("ALTER TABLE delivery_notes ADD COLUMN last_email_subject VARCHAR(255) NULL"); } catch (Throwable $e) { /* ignore if exists */ }
        try { $pdo->exec("ALTER TABLE delivery_notes ADD COLUMN last_email_text MEDIUMTEXT NULL"); } catch (Throwable $e) { /* ignore if exists */ }
        // Ensure DN email log table exists
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS delivery_note_emails (
                id INT AUTO_INCREMENT PRIMARY KEY,
                delivery_note_id BIGINT UNSIGNED NOT NULL,
                to_email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                html_body MEDIUMTEXT NULL,
                text_body MEDIUMTEXT NULL,
                status ENUM('sent','failed') NOT NULL,
                error TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_dn (delivery_note_id),
                CONSTRAINT fk_dn_emails_dn FOREIGN KEY (delivery_note_id) REFERENCES delivery_notes(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) { /* ignore */ }

        if ($action === 'email_form') {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) { Helpers::redirect('index.php?page=delivery_notes'); break; }
            $st = $pdo->prepare('SELECT dn.*, c.name AS customer_name, c.email AS customer_email, b.name AS branch_name FROM delivery_notes dn LEFT JOIN customers c ON c.id=dn.customer_id LEFT JOIN branches b ON b.id=dn.branch_id WHERE dn.id=? LIMIT 1');
            $st->execute([$id]);
            $dnRow = $st->fetch();
            if (!$dnRow) { Helpers::redirect('index.php?page=delivery_notes'); break; }
            $it = $pdo->prepare('SELECT dnp.parcel_id, dnp.amount FROM delivery_note_parcels dnp WHERE dnp.delivery_note_id=? ORDER BY dnp.id');
            $it->execute([$id]);
            $lines = $it->fetchAll();
            $rowsHtml=''; $total=0.0; foreach ($lines as $ln){ $total+=(float)$ln['amount']; $rowsHtml.='<tr><td>#'.(int)$ln['parcel_id'].'</td><td class="text-end">'.number_format((float)$ln['amount'],2).'</td></tr>'; }
            if ($rowsHtml===''){ $rowsHtml='<tr><td colspan="2">No lines.</td></tr>'; }
            $disc=(float)($dnRow['discount']??0); $net=$total+$disc;
            $subject='Delivery Note #'.$id.' â€” '.number_format($net,2);
            $html = '<div style="font-family:Arial,sans-serif">'
                  . '<h3 style="margin:0 0 8px;">Delivery Note #'.$id.'</h3>'
                  . '<div style="color:#555;margin:0 0 6px;">Customer: '.htmlspecialchars((string)$dnRow['customer_name']).'</div>'
                  . '<div style="color:#555;margin:0 12px 12px 0;">Date: '.htmlspecialchars((string)$dnRow['delivery_date']).'</div>'
                  . '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">'
                  . '<thead><tr><th>Parcel</th><th>Amount</th></tr></thead><tbody>'
                  . $rowsHtml
                  . '</tbody><tfoot><tr><th class="text-end">Net</th><th class="text-end">'.number_format($net,2).'</th></tr></tfoot></table>'
                  . '</div>';
            $prefill = [
                'id'=>(int)$id,
                'to_email'=>trim((string)($dnRow['customer_email']??'')),
                'to_name'=>(string)($dnRow['customer_name']??'Customer'),
                'subject'=>$subject,
                'html'=>$html
            ];
            Helpers::view('delivery_notes/email_form', compact('prefill'));
            break;
        }

        if ($action === 'email_send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $toEmail = trim((string)($_POST['to_email'] ?? ''));
            $toName = trim((string)($_POST['to_name'] ?? 'Customer'));
            $subject = trim((string)($_POST['subject'] ?? ''));
            $html = (string)($_POST['html'] ?? '');
            $alt = strip_tags($html);
            if ($toEmail === '' || $subject === '') { Helpers::redirect('index.php?page=delivery_notes&action=email_form&id='.(int)$id); break; }
            $ok = false;
            if (isset($GLOBALS['mailer']) && $GLOBALS['mailer'] instanceof Mailer) {
                try { $ok = (bool)$GLOBALS['mailer']->send($toEmail, $toName, $subject, $html, $alt); } catch (Throwable $e) { $ok = false; }
            }
            // Log result in delivery_note_emails
            try {
                $ins = $pdo->prepare('INSERT INTO delivery_note_emails (delivery_note_id, to_email, subject, html_body, text_body, status, error) VALUES (?,?,?,?,?,?,?)');
                $err = '';
                if (!$ok && isset($GLOBALS['mailer']) && method_exists($GLOBALS['mailer'], 'getLastError')) { $err = (string)$GLOBALS['mailer']->getLastError(); }
                $ins->execute([(int)$id, $toEmail, $subject, $html, $alt, ($ok?'sent':'failed'), ($err!==''?$err:null)]);
            } catch (Throwable $e) { /* ignore */ }
            // Persist status and fallback content on DN
            try { $pdo->prepare('UPDATE delivery_notes SET last_email_status=?, last_emailed_at=NOW(), last_email_subject=?, last_email_text=? WHERE id=?')->execute([($ok?'sent':'failed'), (string)$subject, (string)$alt, (int)$id]); } catch (Throwable $e) { /* ignore */ }
            Helpers::redirect('index.php?page=delivery_notes');
            break;
        }

        // Inline update customer's delivery location (AJAX from route page)
        if ($action === 'update_location' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $cid = (int)($_POST['customer_id'] ?? 0);
            $delivery_location = trim((string)($_POST['delivery_location'] ?? ''));
            $ok = false;
            if ($cid > 0) {
                try {
                    $st = $pdo->prepare('UPDATE customers SET delivery_location=? WHERE id=?');
                    $ok = $st->execute([$delivery_location, $cid]);
                } catch (Throwable $e) { $ok = false; }
            }
            if (strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') === 0) {
                header('Content-Type: application/json'); echo json_encode(['ok'=>$ok?1:0,'delivery_location'=>$delivery_location]);
                break;
            }
            Helpers::redirect('index.php?page=delivery_notes&action=route&loc_saved=' . ($ok ? '1' : '0'));
            break;
        }

        // Bulk print manifest for selected customers on a route/date range
        if ($action === 'print_manifest' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $vehicle_no = trim((string)($_POST['vehicle_no'] ?? ''));
            $from = trim((string)($_POST['from'] ?? date('Y-m-d')));
            $to = trim((string)($_POST['to'] ?? date('Y-m-d')));
            // normalize dates
            try { if ($from) { $ts = strtotime($from); if ($ts) { $from = date('Y-m-d', $ts); } } } catch (Throwable $e) {}
            try { if ($to) { $ts2 = strtotime($to); if ($ts2) { $to = date('Y-m-d', $ts2); } } } catch (Throwable $e) {}
            $ids = $_POST['customer_ids'] ?? [];
            if (!is_array($ids)) { $ids = []; }
            $ids = array_values(array_unique(array_map(function($v){ return (int)$v; }, $ids)));
            if (count($ids) === 0) { Helpers::redirect('index.php?page=delivery_notes&action=route_detail&vehicle_no='.urlencode($vehicle_no).'&from='.$from.'&to='.$to); break; }

            // Fetch parcels for selected customers and filters
            $pdo = Database::pdo();
            $in = implode(',', array_fill(0, count($ids), '?'));
            $params = [$from.' 00:00:00', $to.' 23:59:59'];
            $sql = "SELECT p.*, c.name AS customer_name, c.phone AS customer_phone
                    FROM parcels p
                    LEFT JOIN customers c ON c.id = p.customer_id
                    WHERE p.customer_id IN ($in) AND p.created_at BETWEEN ? AND ?";
            // move ids first in params list to match placeholders
            $params = array_merge($ids, $params);
            if ($vehicle_no !== '') { $sql .= " AND p.vehicle_no = ?"; $params[] = $vehicle_no; }
            $sql .= " ORDER BY c.name, p.created_at, p.id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            // Group by customer
            $grouped = [];
            $customers = [];
            foreach ($rows as $r) {
                $cid = (int)($r['customer_id'] ?? 0);
                if (!isset($grouped[$cid])) { $grouped[$cid] = []; }
                $grouped[$cid][] = $r;
                if (!isset($customers[$cid])) {
                    $customers[$cid] = ['name' => (string)($r['customer_name'] ?? ''), 'phone' => (string)($r['customer_phone'] ?? '')];
                }
            }

            Helpers::view('delivery_notes/manifest', compact('vehicle_no','from','to','grouped','customers'));
            break;
        }

        // Ensure discount column exists on delivery_notes
        try {
            $pdo->exec("ALTER TABLE delivery_notes ADD COLUMN discount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER total_amount");
        } catch (Throwable $e) { /* ignore if exists */ }

        if ($action === 'generate') {
            // show form to pick customer and date, and POST to create
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
                $customer_id = (int)($_POST['customer_id'] ?? 0);
                $delivery_date = $_POST['delivery_date'] ?? date('Y-m-d');
            $direction = $_POST['direction'] ?? 'to';
                if ($customer_id <= 0) { $error = 'Select a customer.'; }
                if (!empty($error)) {
                    $customersAll = $pdo->query('SELECT id, name, phone FROM customers ORDER BY name')->fetchAll();
                    Helpers::view('delivery_notes/generate', compact('customersAll','error'));
                    break;
                }

                // Find PENDING parcels for that customer, not already in any DN, and not delivered.
                // Nonâ€“main-branch users: only parcels for their branch (from/to per direction).
                // Main-branch users: all open parcels for the customer, grouped by destination (or origin) so each bill uses the parcel's branch (avoids duplicate DNs for the same destination+date).
                // The selected delivery_date is used for the DN record, not as a filter for parcel created_at
                $branchColGen = ($direction === 'from') ? 'p.from_branch_id' : 'p.to_branch_id';
                $groupKey = ($direction === 'from') ? 'from_branch_id' : 'to_branch_id';
                $sql = "SELECT p.* FROM parcels p
                    LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id
                    WHERE p.customer_id = ? AND dnp.id IS NULL AND " . Helpers::parcelSqlEligibleForOpenBilling();
                $params = [$customer_id];
                if (!Auth::isMainBranch()) {
                    $sql .= " AND $branchColGen = ?";
                    $params[] = $branchId;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll();

                $groups = [];
                foreach ($rows as $r) {
                    $gid = (int)($r[$groupKey] ?? 0);
                    if ($gid <= 0) {
                        continue;
                    }
                    if (!isset($groups[$gid])) {
                        $groups[$gid] = [];
                    }
                    $groups[$gid][] = $r;
                }

                $dnId = 0;
                if (empty($groups)) {
                    // Legacy: no matching parcels â€” still upsert an empty DN tied to the user's branch (same as before)
                    $stmt = $pdo->prepare('SELECT id FROM delivery_notes WHERE customer_id=? AND branch_id=? AND delivery_date=? LIMIT 1');
                    $stmt->execute([$customer_id, $branchId, $delivery_date]);
                    $dn = $stmt->fetch();
                    if ($dn) {
                        $dnId = (int)$dn['id'];
                    } else {
                        $pdo->prepare('INSERT INTO delivery_notes (customer_id, branch_id, delivery_date, total_amount) VALUES (?,?,?,0)')->execute([$customer_id, $branchId, $delivery_date]);
                        $dnId = (int)$pdo->lastInsertId();
                    }
                } else {
                    foreach ($groups as $dnBranchId => $groupRows) {
                        $dnBranchId = (int)$dnBranchId;
                        if ($dnBranchId <= 0) {
                            continue;
                        }
                        $stmt = $pdo->prepare('SELECT id FROM delivery_notes WHERE customer_id=? AND branch_id=? AND delivery_date=? LIMIT 1');
                        $stmt->execute([$customer_id, $dnBranchId, $delivery_date]);
                        $dn = $stmt->fetch();
                        if ($dn) {
                            $dnId = (int)$dn['id'];
                        } else {
                            $pdo->prepare('INSERT INTO delivery_notes (customer_id, branch_id, delivery_date, total_amount) VALUES (?,?,?,0)')->execute([$customer_id, $dnBranchId, $delivery_date]);
                            $dnId = (int)$pdo->lastInsertId();
                        }
                        $parcelIds = [];
                        foreach ($groupRows as $r) {
                            $rawPrice = (string)($r['price'] ?? '0');
                            $amount = (float)str_replace([',',' '], '', $rawPrice);
                            if ($amount <= 0) {
                                try {
                                    $sumItems = $pdo->prepare('SELECT COALESCE(SUM(COALESCE(qty,0)*COALESCE(rate,0) + COALESCE(additional_amount,0)),0) AS s FROM parcel_items WHERE parcel_id=?');
                                    $sumItems->execute([(int)$r['id']]);
                                    $amount = (float)($sumItems->fetch()['s'] ?? 0);
                                } catch (Throwable $e) { /* ignore */ }
                            }
                            $ins = $pdo->prepare('INSERT IGNORE INTO delivery_note_parcels (delivery_note_id, parcel_id, amount) VALUES (?,?,?)');
                            $ins->execute([$dnId, (int)$r['id'], $amount]);
                            $parcelIds[] = (int)$r['id'];
                        }
                        $sum = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS s FROM delivery_note_parcels WHERE delivery_note_id=?');
                        $sum->execute([$dnId]);
                        $srow = $sum->fetch();
                        $pdo->prepare('UPDATE delivery_notes SET total_amount=? WHERE id=?')->execute([(float)$srow['s'], $dnId]);
                        if (!empty($parcelIds)) {
                            $in = implode(',', array_fill(0, count($parcelIds), '?'));
                            $upd = $pdo->prepare("UPDATE parcels SET status='delivered' WHERE id IN ($in)");
                            $upd->execute($parcelIds);
                        }
                    }
                }

                // Auto-email the generated delivery note to the customer (best-effort)
                try {
                    $cst = $pdo->prepare('SELECT name, email, phone FROM customers WHERE id=? LIMIT 1');
                    $cst->execute([$customer_id]);
                    $cr = $cst->fetch();
                    $toEmail = trim((string)($cr['email'] ?? ''));
                    if ($toEmail !== '' && isset($GLOBALS['mailer']) && $GLOBALS['mailer'] instanceof Mailer) {
                        // Fetch DN row + paid/due
                        $sql = 'SELECT dn.*, c.name AS customer_name, c.phone AS customer_phone, b.name AS branch_name,
                                       COALESCE(paid.total_paid,0) AS paid, (dn.total_amount - COALESCE(paid.total_paid,0)) AS due
                                FROM delivery_notes dn
                                LEFT JOIN customers c ON c.id = dn.customer_id
                                LEFT JOIN branches b ON b.id = dn.branch_id
                                LEFT JOIN (SELECT delivery_note_id, SUM(amount) AS total_paid FROM payments GROUP BY delivery_note_id) paid ON paid.delivery_note_id = dn.id
                                WHERE dn.id=? LIMIT 1';
                        $st = $pdo->prepare($sql); $st->execute([$dnId]); $dnRow = $st->fetch();
                        // Build DN items rows
                        $it = $pdo->prepare('SELECT dnp.parcel_id, dnp.amount FROM delivery_note_parcels dnp WHERE dnp.delivery_note_id=? ORDER BY dnp.id');
                        $it->execute([$dnId]);
                        $items = $it->fetchAll();
                        $rowsHtml = '';
                        foreach ($items as $ln) {
                            $rowsHtml .= '<tr><td>#'.(int)$ln['parcel_id'].'</td><td class="text-end">'.number_format((float)$ln['amount'],2).'</td></tr>';
                        }
                        if ($rowsHtml === '') { $rowsHtml = '<tr><td colspan="2">No items</td></tr>'; }
                        $subject = 'Delivery Note #'.$dnId.' â€” '.number_format((float)$dnRow['total_amount'],2);
                        $html = '<div style="font-family:Arial,sans-serif">'
                              . '<h3 style="margin:0 0 8px;">Delivery Note #'.$dnId.'</h3>'
                              . '<div style="margin:0 0 6px;color:#555;">Branch: '.htmlspecialchars((string)($dnRow['branch_name'] ?? '')).'</div>'
                              . '<div style="margin:0 0 12px;color:#555;">Customer: '.htmlspecialchars((string)($dnRow['customer_name'] ?? '')).'</div>'
                              . '<table cellspacing="0" cellpadding="6" border="1" style="border-collapse:collapse;width:100%;">'
                              . '<thead style="background:#f1f1f1;"><tr><th align="left">Parcel</th><th align="right">Amount</th></tr></thead>'
                              . '<tbody>'.$rowsHtml.'</tbody>'
                              . '<tfoot>'
                              . '<tr style="background:#fafafa;"><td align="right"><strong>Total</strong></td><td align="right"><strong>'.number_format((float)$dnRow['total_amount'],2).'</strong></td></tr>'
                              . '<tr><td align="right">Paid</td><td align="right">'.number_format((float)($dnRow['paid'] ?? 0),2).'</td></tr>'
                              . '<tr><td align="right">Due</td><td align="right">'.number_format((float)($dnRow['due'] ?? 0),2).'</td></tr>'
                              . '</tfoot></table>'
                              . '</div>';
                        $text = 'Delivery Note #'.$dnId.'\nTotal: '.number_format((float)$dnRow['total_amount'],2);
                        $okDn = false; try { $okDn = (bool)$GLOBALS['mailer']->send($toEmail, (string)($cr['name'] ?? $toEmail), $subject, $html, $text); } catch (Throwable $eSend) { $okDn=false; }
                        // Log result in delivery_note_emails
                        try {
                            $ins = $pdo->prepare('INSERT INTO delivery_note_emails (delivery_note_id, to_email, subject, html_body, text_body, status, error) VALUES (?,?,?,?,?,?,?)');
                            $err = '';
                            if (!$okDn && isset($GLOBALS['mailer']) && method_exists($GLOBALS['mailer'], 'getLastError')) { $err = (string)$GLOBALS['mailer']->getLastError(); }
                            $ins->execute([$dnId, $toEmail, $subject, $html, $text, ($okDn?'sent':'failed'), ($err!==''?$err:null)]);
                        } catch (Throwable $eIns) { /* ignore */ }
                        try { $pdo->prepare('UPDATE delivery_notes SET last_email_status=?, last_emailed_at=NOW(), last_email_subject=?, last_email_text=? WHERE id=?')->execute([($okDn?'sent':'failed'), (string)$subject, (string)$text, $dnId]); } catch (Throwable $e4) { /* ignore */ }
                    }
                } catch (Throwable $e) { /* ignore */ }
                // SMS notification (concise)
                $toPhone = trim((string)($cr['phone'] ?? ''));
                if ($toPhone !== '' && isset($GLOBALS['sms']) && method_exists($GLOBALS['sms'], 'sendText') && $GLOBALS['sms']->isEnabled()) {
                    $vehArr = [];
                    foreach (($items ?? []) as $it) {
                        $v = trim((string)($it['vehicle_no'] ?? ''));
                        if ($v !== '') { $vehArr[$v] = true; }
                    }
                    $vehices = implode(', ', array_keys($vehArr));
                    $msg = 'Delivery Note #' . (int)$dnRow['id'] . ' | Date: ' . (string)$dnRow['delivery_date']
                         . ($vehices !== '' ? ' | Vehicles: ' . $vehices : '')
                         . ' | Total: ' . number_format((float)$dnRow['total_amount'],2);
                    $GLOBALS['sms']->sendText($toPhone, $msg);
                }
                Helpers::redirect('index.php?page=delivery_notes&action=view&id=' . $dnId);
                break;
            }

            $customersAll = $pdo->query('SELECT id, name, phone FROM customers ORDER BY name')->fetchAll();
            Helpers::view('delivery_notes/generate', compact('customersAll'));
            break;
        }

        if ($action === 'route') {
            // Planning screen: customers with pending parcels to deliver from THIS branch
            $date = $_GET['date'] ?? date('Y-m-d');
            $direction = $_GET['direction'] ?? 'to'; // 'to' (arrivals) or 'from' (dispatch)
            // Filters (text + new dropdowns)
            $customer = trim($_GET['customer'] ?? '');
            $phone = trim($_GET['phone'] ?? '');
            $place = trim($_GET['place'] ?? '');
            $customer_id = (int)($_GET['customer_id'] ?? 0);
            $place_sel = trim($_GET['place_sel'] ?? '');
            // If 'All Customers' is selected, ignore name/phone text filters
            if ($customer_id === 0) { $customer = ''; $phone = ''; }

            // If user targeted a specific customer but didn't pick a location, auto-pick when only one location exists
            if ($place === '' && $place_sel === '' && ($customer_id > 0 || $customer !== '' || $phone !== '')) {
                $autoWhere = ['p.to_branch_id = ?', Helpers::parcelSqlEligibleForOpenBilling(), 'dnp.id IS NULL'];
                $autoParams = [$branchId];
                if ($customer_id > 0) { $autoWhere[] = 'c.id = ?'; $autoParams[] = $customer_id; }
                elseif ($customer !== '') { $autoWhere[] = 'c.name LIKE ?'; $autoParams[] = "%$customer%"; }
                if ($phone !== '') { $autoWhere[] = 'c.phone LIKE ?'; $autoParams[] = "%$phone%"; }
                $autoSql = 'SELECT DISTINCT TRIM(COALESCE(c.delivery_location, "")) AS loc
                            FROM parcels p JOIN customers c ON c.id=p.customer_id
                            LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id=p.id
                            WHERE ' . implode(' AND ', $autoWhere) . ' AND TRIM(COALESCE(c.delivery_location, "")) <> ""';
                $autoStmt = $pdo->prepare($autoSql);
                $autoStmt->execute($autoParams);
                $locs = $autoStmt->fetchAll();
                if (count($locs) === 1) { $place_sel = (string)($locs[0]['loc'] ?? ''); }
            }

            // Build from customers, LEFT JOIN pending/not-in-DN parcels for this branch so even 0-parcel customers are listed
            // Also LEFT JOIN planned vehicle assignments (per customer/date/branch)
            // Ensure table for planned vehicle assignments exists (for zero-parcel cases too)
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS delivery_route_assignments (
                    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    customer_id BIGINT UNSIGNED NOT NULL,
                    branch_id BIGINT UNSIGNED NOT NULL,
                    delivery_date DATE NOT NULL,
                    vehicle_no VARCHAR(60) NOT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_customer_branch_date (customer_id, branch_id, delivery_date)
                ) ENGINE=InnoDB");
            } catch (Throwable $e) { /* ignore */ }

            $branchCol = ($direction === 'from') ? 'p.from_branch_id' : 'p.to_branch_id';
            $parcelOpenSql = Helpers::parcelSqlEligibleForOpenBilling();
            $sql = "SELECT c.id AS customer_id, c.name AS customer_name, c.phone AS customer_phone,
                           COALESCE(c.delivery_location, '') AS delivery_location,
                           COUNT(p.id) AS parcels_count,
                           COALESCE(SUM(COALESCE(CAST(NULLIF(REPLACE(p.price, ',', ''), '') AS DECIMAL(18,2)), isum.items_total, 0)),0) AS est_total,
                           GROUP_CONCAT(DISTINCT COALESCE(p.vehicle_no, '')) AS veh_list,
                           SUM(CASE WHEN COALESCE(p.vehicle_no,'')<>'' THEN 1 ELSE 0 END) AS with_vehicle,
                           COALESCE(MAX(dra.vehicle_no), '') AS planned_vehicle
                    FROM customers c
                    LEFT JOIN parcels p
                      ON p.customer_id = c.id
                     AND $branchCol = ?
                     AND $parcelOpenSql
                    LEFT JOIN (
                      SELECT parcel_id, SUM(COALESCE(qty,0)*COALESCE(rate,0) + COALESCE(additional_amount,0)) AS items_total
                      FROM parcel_items
                      GROUP BY parcel_id
                    ) isum ON isum.parcel_id = p.id
                    LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id
                    LEFT JOIN delivery_route_assignments dra
                      ON dra.customer_id = c.id AND dra.branch_id = ? AND dra.delivery_date = ?
                    WHERE (dnp.id IS NULL OR dnp.id IS NULL)"; // keep rows when there are no parcels or parcel not in any DN yet
            $params = [$branchId, $branchId, ($date ?? date('Y-m-d'))];
            if ($customer_id > 0) { $sql .= ' AND c.id = ?'; $params[] = $customer_id; }
            elseif ($customer !== '') { $sql .= ' AND c.name LIKE ?'; $params[] = "%$customer%"; }
            if ($phone !== '') { $sql .= ' AND c.phone LIKE ?'; $params[] = "%$phone%"; }
            if ($place_sel !== '') { $sql .= ' AND COALESCE(c.delivery_location, "") = ?'; $params[] = $place_sel; }
            elseif ($place !== '') { $sql .= ' AND COALESCE(c.delivery_location, "") LIKE ?'; $params[] = "%$place%"; }
            $sql .= ' GROUP BY c.id, c.name, c.phone, c.delivery_location ORDER BY c.name';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $routes = $stmt->fetchAll();
            if ($customer_id > 0 && count($routes) === 1 && (int)($routes[0]['parcels_count'] ?? 0) === 0) {
                $tryDir = ($direction === 'from') ? 'to' : 'from';
                $branchCol2 = ($tryDir === 'from') ? 'p.from_branch_id' : 'p.to_branch_id';
                $sql2 = "SELECT c.id AS customer_id, c.name AS customer_name, c.phone AS customer_phone,
                               COALESCE(c.delivery_location, '') AS delivery_location,
                               COUNT(p.id) AS parcels_count,
                               COALESCE(SUM(COALESCE(CAST(NULLIF(REPLACE(p.price, ',', ''), '') AS DECIMAL(18,2)), isum.items_total, 0)),0) AS est_total,
                               GROUP_CONCAT(DISTINCT COALESCE(p.vehicle_no, '')) AS veh_list,
                               SUM(CASE WHEN COALESCE(p.vehicle_no,'')<>'' THEN 1 ELSE 0 END) AS with_vehicle,
                               COALESCE(MAX(dra.vehicle_no), '') AS planned_vehicle
                        FROM customers c
                        LEFT JOIN parcels p
                          ON p.customer_id = c.id
                         AND $branchCol2 = ?
                         AND $parcelOpenSql
                        LEFT JOIN (
                          SELECT parcel_id, SUM(COALESCE(qty,0)*COALESCE(rate,0) + COALESCE(additional_amount,0)) AS items_total
                          FROM parcel_items
                          GROUP BY parcel_id
                        ) isum ON isum.parcel_id = p.id
                        LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id
                        LEFT JOIN delivery_route_assignments dra
                          ON dra.customer_id = c.id AND dra.branch_id = ? AND dra.delivery_date = ?
                        WHERE (dnp.id IS NULL OR dnp.id IS NULL) AND c.id = ?
                        GROUP BY c.id, c.name, c.phone, c.delivery_location
                        ORDER BY c.name";
                $stmt2 = $pdo->prepare($sql2);
                $stmt2->execute([$branchId, $branchId, ($date ?? date('Y-m-d')), $customer_id]);
                $alt = $stmt2->fetchAll();
                if ($alt && (int)($alt[0]['parcels_count'] ?? 0) > 0) {
                    $routes = $alt;
                    $direction = $tryDir;
                }
            }
            // Totals for quick glance
            $totalsSql = 'SELECT COUNT(*) AS parcels FROM parcels p LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id=p.id WHERE ' . (($direction === 'from') ? 'p.from_branch_id' : 'p.to_branch_id') . ' = ? AND ' . Helpers::parcelSqlEligibleForOpenBilling() . ' AND dnp.id IS NULL';
            $totalsStmt = $pdo->prepare($totalsSql);
            $totalsStmt->execute([$branchId]);
            $parcels_total = (int)($totalsStmt->fetch()['parcels'] ?? 0);
            $customers_total = count($routes);
            $branchName = (string)($user['branch_name'] ?? '');
            // Build dropdown filter lists (all customers and all places)
            $customersFilter = $pdo->query('SELECT id, name, phone, COALESCE(delivery_location, "") AS delivery_location FROM customers ORDER BY name LIMIT 1000')->fetchAll();
            $pf = $pdo->query('SELECT DISTINCT COALESCE(delivery_location, "") AS place FROM customers WHERE COALESCE(delivery_location, "") <> "" ORDER BY place')->fetchAll();
            $placesFilter = array_map(function($r){ return $r['place']; }, $pf);
            Helpers::view('delivery_notes/route', compact('routes','date','parcels_total','customers_total','branchName','customer','phone','place','customer_id','place_sel','customersFilter','placesFilter','direction'));
            break;
        }

        if ($action === 'assign_vehicle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $customer_id = (int)($_POST['customer_id'] ?? 0);
            $vehicle_no = trim((string)($_POST['vehicle_no'] ?? ''));
            $delivery_date = $_POST['delivery_date'] ?? date('Y-m-d');
            if ($customer_id <= 0 || $vehicle_no === '') {
                http_response_code(400);
                // If ajax request, return JSON error
                if (strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') === 0) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok'=>0,'error'=>'Customer and Vehicle No are required.']);
                    break;
                } else {
                    echo 'Customer and Vehicle No are required.'; break;
                }
            }
            // Update pending parcels for this customer to this branch that are not in any DN yet
            $upd = $pdo->prepare('UPDATE parcels p
                                   LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id
                                   SET p.vehicle_no = ?
                                   WHERE p.customer_id = ? AND p.to_branch_id = ?
                                     AND ' . Helpers::parcelSqlEligibleForOpenBilling() . '
                                     AND dnp.id IS NULL');
            $upd->execute([$vehicle_no, $customer_id, $branchId]);
            // Also upsert a planned assignment so the badge shows even if there are zero parcels
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS delivery_route_assignments (
                    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    customer_id BIGINT UNSIGNED NOT NULL,
                    branch_id BIGINT UNSIGNED NOT NULL,
                    delivery_date DATE NOT NULL,
                    vehicle_no VARCHAR(60) NOT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_customer_branch_date (customer_id, branch_id, delivery_date)
                ) ENGINE=InnoDB");
            } catch (Throwable $e) { /* ignore */ }
            $ins = $pdo->prepare('INSERT INTO delivery_route_assignments (customer_id, branch_id, delivery_date, vehicle_no) VALUES (?,?,?,?)
                                  ON DUPLICATE KEY UPDATE vehicle_no=VALUES(vehicle_no), updated_at=CURRENT_TIMESTAMP');
            $ins->execute([$customer_id, $branchId, $delivery_date, $vehicle_no]);
            // If AJAX, return JSON success to allow inline update without full refresh
            if (strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') === 0) {
                header('Content-Type: application/json');
                echo json_encode(['ok'=>1,'vehicle_no'=>$vehicle_no,'customer_id'=>$customer_id,'delivery_date'=>$delivery_date]);
                break;
            }
            // Otherwise, stay on the same planning page with a success flag
            $direction = isset($_POST['direction']) ? $_POST['direction'] : 'to';
            $redir = 'index.php?page=delivery_notes&action=route&customer_id=' . $customer_id . '&date=' . urlencode($delivery_date) . '&direction=' . urlencode($direction) . '&saved=1';
            Helpers::redirect($redir);
            break;
        }

        if ($action === 'route_vehicles') {
            // Vehicle-wise route list for branch and selected date range (by parcels created_at)
            $from = Helpers::parseDateOr((string)($_GET['from'] ?? ''), date('Y-m-01'));
            $to = Helpers::parseDateOr((string)($_GET['to'] ?? ''), date('Y-m-d'));
            [$from, $to] = Helpers::orderDateRange($from, $to);
            $direction = ($_GET['direction'] ?? 'from') === 'to' ? 'to' : 'from';
            $vehicle = trim($_GET['vehicle'] ?? '');
            $routeBranchId = Auth::isMainBranch()
                ? BranchRepository::resolveToFixedBranchId($pdo, (int)($_GET['branch_id'] ?? 0))
                : $branchId;
            if ($routeBranchId <= 0) {
                $routeBranchId = $branchId;
            }
            $branchColumn = ($direction === 'to') ? 'p.to_branch_id' : 'p.from_branch_id';
            $dateExpr = ($direction === 'to') ? 'DATE(COALESCE(p.updated_at, p.created_at))' : 'DATE(p.created_at)';
            $sql = "SELECT COALESCE(NULLIF(TRIM(p.vehicle_no), ''), 'â€”') AS vehicle_no,
                           COUNT(*) AS parcels_count,
                           SUM(CASE WHEN p.status='delivered' THEN 1 ELSE 0 END) AS delivered_count,
                           MAX($dateExpr) AS last_date
                    FROM parcels p
                    WHERE $branchColumn = ? AND $dateExpr BETWEEN ? AND ?";
            $params = [$routeBranchId, $from, $to];
            if ($vehicle !== '') { $sql .= " AND COALESCE(p.vehicle_no, '') LIKE ?"; $params[] = "%$vehicle%"; }
            $sql .= "
                    GROUP BY COALESCE(NULLIF(TRIM(p.vehicle_no), ''), 'â€”')
                    HAVING vehicle_no <> 'â€”'
                    ORDER BY MAX($dateExpr) DESC, vehicle_no ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $routes = $stmt->fetchAll();
            $branchesAll = BranchRepository::forFilters($pdo);
            $isMain = Auth::isMainBranch();
            $hasFilters = ($vehicle !== '' || $routeBranchId !== $branchId || $from !== date('Y-m-01') || $to !== date('Y-m-d') || $direction !== 'from');
            Helpers::view('delivery_notes/route_vehicles', compact('routes','from','to','direction','vehicle','branchesAll','isMain','routeBranchId','hasFilters'));
            break;
        }

        if ($action === 'route_vehicles_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $from = Helpers::parseDateOr((string)($_POST['from'] ?? ''), date('Y-m-01'));
            $to = Helpers::parseDateOr((string)($_POST['to'] ?? ''), date('Y-m-d'));
            [$from, $to] = Helpers::orderDateRange($from, $to);
            $direction = ($_POST['direction'] ?? 'from') === 'to' ? 'to' : 'from';
            $routeBranchId = Auth::isMainBranch()
                ? BranchRepository::resolveToFixedBranchId($pdo, (int)($_POST['branch_id'] ?? 0))
                : $branchId;
            if ($routeBranchId <= 0) {
                $routeBranchId = $branchId;
            }
            $old_vehicle = (string)($_POST['old_vehicle'] ?? '');
            $new_vehicle = trim((string)($_POST['new_vehicle'] ?? ''));
            $redir = 'index.php?page=delivery_notes&action=route_vehicles&from=' . urlencode($from) . '&to=' . urlencode($to) . '&direction=' . urlencode($direction);
            if (Auth::isMainBranch()) {
                $redir .= '&branch_id=' . (int)$routeBranchId;
            }
            if ($new_vehicle === '') {
                Helpers::redirect($redir . '&err=vehicle_required');
                break;
            }
            $branchColumn = ($direction === 'to') ? 'to_branch_id' : 'from_branch_id';
            $dateExpr = ($direction === 'to') ? 'DATE(COALESCE(updated_at, created_at))' : 'DATE(created_at)';
            $sql = "UPDATE parcels SET vehicle_no = ? WHERE $branchColumn = ? AND $dateExpr BETWEEN ? AND ? AND COALESCE(vehicle_no,'') = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$new_vehicle, $routeBranchId, $from, $to, $old_vehicle]);
            Helpers::redirect($redir . '&saved=1');
            break;
        }

        if ($action === 'route_vehicles_clear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $from = Helpers::parseDateOr((string)($_POST['from'] ?? ''), date('Y-m-01'));
            $to = Helpers::parseDateOr((string)($_POST['to'] ?? ''), date('Y-m-d'));
            [$from, $to] = Helpers::orderDateRange($from, $to);
            $direction = ($_POST['direction'] ?? 'from') === 'to' ? 'to' : 'from';
            $routeBranchId = Auth::isMainBranch()
                ? BranchRepository::resolveToFixedBranchId($pdo, (int)($_POST['branch_id'] ?? 0))
                : $branchId;
            if ($routeBranchId <= 0) {
                $routeBranchId = $branchId;
            }
            $old_vehicle = trim((string)($_POST['old_vehicle'] ?? ''));
            $redir = 'index.php?page=delivery_notes&action=route_vehicles&from=' . urlencode($from) . '&to=' . urlencode($to) . '&direction=' . urlencode($direction);
            if (Auth::isMainBranch()) {
                $redir .= '&branch_id=' . (int)$routeBranchId;
            }
            if ($old_vehicle === '') {
                Helpers::redirect($redir . '&err=vehicle_required');
                break;
            }
            $branchColumn = ($direction === 'to') ? 'to_branch_id' : 'from_branch_id';
            $dateExpr = ($direction === 'to') ? 'DATE(COALESCE(updated_at, created_at))' : 'DATE(created_at)';
            $sql = "UPDATE parcels SET vehicle_no = '' WHERE $branchColumn = ? AND $dateExpr BETWEEN ? AND ? AND COALESCE(vehicle_no,'') = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$routeBranchId, $from, $to, $old_vehicle]);
            Helpers::redirect($redir . '&saved=1');
            break;
        }

        if ($action === 'route_detail') {
            // Show parcels for a given vehicle number, grouped by customer, only those not yet attached to a DN for this branch
            $vehicle_no = trim($_GET['vehicle_no'] ?? '');
            $from = Helpers::parseDateOr((string)($_GET['from'] ?? ''), date('Y-m-01'));
            $to = Helpers::parseDateOr((string)($_GET['to'] ?? ''), date('Y-m-d'));
            [$from, $to] = Helpers::orderDateRange($from, $to);
            $direction = ($_GET['direction'] ?? 'from') === 'to' ? 'to' : 'from';
            $placeFilter = trim($_GET['place'] ?? '');
            $detailBranchId = Auth::isMainBranch()
                ? BranchRepository::resolveToFixedBranchId($pdo, (int)($_GET['branch_id'] ?? 0))
                : $branchId;
            if ($detailBranchId <= 0) {
                $detailBranchId = $branchId;
            }
            $branchColumn = ($direction === 'to') ? 'p.to_branch_id' : 'p.from_branch_id';
            // Date range uses last update time for arrivals
            $dateExpr = ($direction === 'to') ? 'DATE(COALESCE(p.updated_at, p.created_at))' : 'DATE(p.created_at)';
            $where = ["$branchColumn = ?", "$dateExpr BETWEEN ? AND ?"];
            $params = [$detailBranchId, $from, $to];
            if ($vehicle_no !== '') { $where[] = 'COALESCE(p.vehicle_no,"") = ?'; $params[] = $vehicle_no; }
            if ($placeFilter !== '') { $where[] = 'COALESCE(c.delivery_location,"") LIKE ?'; $params[] = '%'.$placeFilter.'%'; }
            // Fetch parcels with customer info and delivered flags
            $sql = 'SELECT p.*, c.name AS customer_name, c.phone AS customer_phone, c.delivery_location AS customer_place,
                           (dnp.id IS NOT NULL) AS in_delivery_note
                    FROM parcels p
                    LEFT JOIN customers c ON c.id = p.customer_id
                    LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id
                    WHERE ' . implode(' AND ', $where) . ' ORDER BY c.name, p.created_at DESC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $parcels = $stmt->fetchAll();
            // Build grouped map customer_id => rows
            $grouped = [];
            foreach ($parcels as $row) { $grouped[(int)$row['customer_id']][] = $row; }
            // Also need a customers mini map
            $customers = [];
            foreach ($parcels as $row) {
                $cid = (int)$row['customer_id'];
                if (!isset($customers[$cid])) { $customers[$cid] = ['id'=>$cid,'name'=>$row['customer_name'] ?? '','phone'=>$row['customer_phone'] ?? '']; }
            }
            // Place-wise counts across all parcels in this route detail
            $placeCounts = [];
            foreach ($parcels as $row) {
                $place = trim((string)($row['customer_place'] ?? ''));
                if ($place === '') { $place = 'â€”'; }
                $placeCounts[$place] = ($placeCounts[$place] ?? 0) + 1;
            }
            arsort($placeCounts);
            Helpers::view('delivery_notes/route_detail', compact('vehicle_no','from','to','grouped','customers','direction','placeCounts','placeFilter'));
            break;
        }

        if ($action === 'previous_bill') {
            $custId = (int)($_GET['customer_id'] ?? 0);
            $deliveryDate = trim((string)($_GET['delivery_date'] ?? ''));
            if ($custId <= 0) {
                header('Content-Type: text/html; charset=utf-8');
                echo '<div class="alert alert-warning">No customer selected.</div>';
                break;
            }
            $dnSql = 'SELECT dn.*, c.name AS customer_name, c.phone AS customer_phone, b.name AS branch_name FROM delivery_notes dn LEFT JOIN customers c ON c.id = dn.customer_id LEFT JOIN branches b ON b.id = dn.branch_id WHERE dn.customer_id=?';
            $dnParams = [$custId];
            if ($deliveryDate !== '') {
                $dnSql .= ' AND dn.delivery_date=?';
                $dnParams[] = $deliveryDate;
            }
            $dnSql .= ' ORDER BY dn.delivery_date DESC, dn.id DESC LIMIT 1';
            $stmt = $pdo->prepare($dnSql);
            $stmt->execute($dnParams);
            $dn = $stmt->fetch();
            if (!$dn) {
                header('Content-Type: text/html; charset=utf-8');
                echo '<div class="alert alert-info">No previous bill for this customer' . ($deliveryDate !== '' ? ' on this date' : '') . '.</div>';
                break;
            }
            $id = (int)$dn['id'];
            $itemsStmt = $pdo->prepare('SELECT dnp.*, p.tracking_number, p.weight, p.vehicle_no, p.created_at,
                                               s.name AS supplier_name, s.phone AS supplier_phone,
                                               pit.item_descriptions
                                        FROM delivery_note_parcels dnp
                                        LEFT JOIN parcels p ON p.id = dnp.parcel_id
                                        LEFT JOIN suppliers s ON s.id = p.supplier_id
                                        LEFT JOIN (
                                          SELECT parcel_id, GROUP_CONCAT(TRIM(description) ORDER BY id SEPARATOR ", ") AS item_descriptions
                                          FROM parcel_items
                                          GROUP BY parcel_id
                                        ) pit ON pit.parcel_id = p.id
                                        WHERE dnp.delivery_note_id=?');
            $itemsStmt->execute([$id]);
            $items = $itemsStmt->fetchAll();
            $supNames = [];
            $supPhones = [];
            foreach ($items as $row) {
                if (!empty($row['supplier_name'])) { $supNames[$row['supplier_name']] = true; }
                if (!empty($row['supplier_phone'])) { $supPhones[$row['supplier_phone']] = true; }
            }
            $dn['suppliers_agg'] = implode(', ', array_keys($supNames));
            $dn['supplier_phones_agg'] = implode(', ', array_keys($supPhones));
            $vehSet = [];
            foreach ($items as $row) {
                $v = trim((string)($row['vehicle_no'] ?? ''));
                if ($v !== '') { $vehSet[$v] = true; }
            }
            $dn['vehicles_agg'] = implode(', ', array_keys($vehSet));
            header('Content-Type: text/html; charset=utf-8');
            extract(compact('dn','items'));
            include __DIR__ . '/../views/delivery_notes/previous_bill_modal.php';
            break;
        }

        if ($action === 'view') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT dn.*, c.name AS customer_name, c.phone AS customer_phone FROM delivery_notes dn LEFT JOIN customers c ON c.id = dn.customer_id WHERE dn.id=?');
            $stmt->execute([$id]);
            $dn = $stmt->fetch();
            if (!$dn) { http_response_code(404); echo 'Not found'; break; }
            $itemsStmt = $pdo->prepare('SELECT dnp.*, p.tracking_number, p.weight, p.vehicle_no,
                                               s.name AS supplier_name, s.phone AS supplier_phone,
                                               pit.item_descriptions
                                        FROM delivery_note_parcels dnp
                                        LEFT JOIN parcels p ON p.id = dnp.parcel_id
                                        LEFT JOIN suppliers s ON s.id = p.supplier_id
                                        LEFT JOIN (
                                          SELECT parcel_id, GROUP_CONCAT(TRIM(description) ORDER BY id SEPARATOR ", ") AS item_descriptions
                                          FROM parcel_items
                                          GROUP BY parcel_id
                                        ) pit ON pit.parcel_id = p.id
                                        WHERE dnp.delivery_note_id=?');
            $itemsStmt->execute([$id]);
            $items = $itemsStmt->fetchAll();
            // Backfill if empty
            if (!$items) {
                // Try arrivals to this branch first
                $sel = $pdo->prepare("SELECT p.* FROM parcels p LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id WHERE p.customer_id=? AND p.to_branch_id=? AND dnp.id IS NULL AND " . Helpers::parcelSqlEligibleForOpenBilling());
                $sel->execute([(int)$dn['customer_id'], (int)$dn['branch_id']]);
                $toAdd = $sel->fetchAll();
                // If none, try dispatch from this branch
                if (!$toAdd) {
                    $sel2 = $pdo->prepare("SELECT p.* FROM parcels p LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id WHERE p.customer_id=? AND p.from_branch_id=? AND dnp.id IS NULL AND " . Helpers::parcelSqlEligibleForOpenBilling());
                    $sel2->execute([(int)$dn['customer_id'], (int)$dn['branch_id']]);
                    $toAdd = $sel2->fetchAll();
                }
                if ($toAdd) {
                    $ids = [];
                    foreach ($toAdd as $r) {
                        $rawPrice = (string)($r['price'] ?? '0');
                        $amount = (float)str_replace([',',' '], '', $rawPrice);
                        $pdo->prepare('INSERT IGNORE INTO delivery_note_parcels (delivery_note_id, parcel_id, amount) VALUES (?,?,?)')->execute([$id, (int)$r['id'], $amount]);
                        $ids[] = (int)$r['id'];
                    }
                    if ($ids) {
                        $in = implode(',', array_fill(0, count($ids), '?'));
                        $pdo->prepare("UPDATE parcels SET status='delivered' WHERE id IN ($in)")->execute($ids);
                        // Update total
                        $sum = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS s FROM delivery_note_parcels WHERE delivery_note_id=?');
                        $sum->execute([$id]);
                        $srow = $sum->fetch();
                        $pdo->prepare('UPDATE delivery_notes SET total_amount=? WHERE id=?')->execute([(float)$srow['s'], $id]);
                    }
                    // Re-read items
                    $itemsStmt->execute([$id]);
                    $items = $itemsStmt->fetchAll();
                }
            }
            // Aggregate suppliers for summary
            $supNames = [];
            $supPhones = [];
            foreach ($items as $row) {
                if (!empty($row['supplier_name'])) { $supNames[$row['supplier_name']] = true; }
                if (!empty($row['supplier_phone'])) { $supPhones[$row['supplier_phone']] = true; }
            }
            $dn['suppliers_agg'] = implode(', ', array_keys($supNames));
            $dn['supplier_phones_agg'] = implode(', ', array_keys($supPhones));
            // Aggregate vehicles from items; fallback to planned assignment for this DN
            $vehSet = [];
            foreach ($items as $row) {
                $v = trim((string)($row['vehicle_no'] ?? ''));
                if ($v !== '') { $vehSet[$v] = true; }
            }
            $vehList = implode(', ', array_keys($vehSet));
            if ($vehList === '') {
                try {
                    $drv = $pdo->prepare('SELECT vehicle_no FROM delivery_route_assignments WHERE customer_id=? AND branch_id=? AND delivery_date=? LIMIT 1');
                    $drv->execute([(int)$dn['customer_id'], (int)$dn['branch_id'], (string)$dn['delivery_date']]);
                    $vehList = (string)($drv->fetch()['vehicle_no'] ?? '');
                } catch (Throwable $e) { /* ignore */ }
            }
            $dn['vehicles_agg'] = $vehList;
            Helpers::view('delivery_notes/show', compact('dn','items'));
            break;
        }

        if ($action === 'print') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT dn.*, c.name AS customer_name, c.phone AS customer_phone, b.name AS branch_name FROM delivery_notes dn LEFT JOIN customers c ON c.id = dn.customer_id LEFT JOIN branches b ON b.id=dn.branch_id WHERE dn.id=?');
            $stmt->execute([$id]);
            $dn = $stmt->fetch();
            if (!$dn) { http_response_code(404); echo 'Not found'; break; }
            $itemsStmt = $pdo->prepare('SELECT dnp.*, p.tracking_number, p.weight,
                                               s.name AS supplier_name, s.phone AS supplier_phone,
                                               pit.item_descriptions
                                        FROM delivery_note_parcels dnp
                                        LEFT JOIN parcels p ON p.id = dnp.parcel_id
                                        LEFT JOIN suppliers s ON s.id = p.supplier_id
                                        LEFT JOIN (
                                          SELECT parcel_id, GROUP_CONCAT(TRIM(description) ORDER BY id SEPARATOR ", ") AS item_descriptions
                                          FROM parcel_items
                                          GROUP BY parcel_id
                                        ) pit ON pit.parcel_id = p.id
                                        WHERE dnp.delivery_note_id=?');
            $itemsStmt->execute([$id]);
            $items = $itemsStmt->fetchAll();
            // Backfill if empty
            if (!$items) {
                $sel = $pdo->prepare("SELECT p.* FROM parcels p LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id WHERE p.customer_id=? AND p.to_branch_id=? AND dnp.id IS NULL AND " . Helpers::parcelSqlEligibleForOpenBilling());
                $sel->execute([(int)$dn['customer_id'], (int)$dn['branch_id']]);
                $toAdd = $sel->fetchAll();
                if ($toAdd) {
                    $ids = [];
                    foreach ($toAdd as $r) {
                        $amount = (float)($r['price'] ?? 0);
                        $pdo->prepare('INSERT IGNORE INTO delivery_note_parcels (delivery_note_id, parcel_id, amount) VALUES (?,?,?)')->execute([$id, (int)$r['id'], $amount]);
                        $ids[] = (int)$r['id'];
                    }
                    if ($ids) {
                        $in = implode(',', array_fill(0, count($ids), '?'));
                        $pdo->prepare("UPDATE parcels SET status='delivered' WHERE id IN ($in)")->execute($ids);
                        $sum = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS s FROM delivery_note_parcels WHERE delivery_note_id=?');
                        $sum->execute([$id]);
                        $srow = $sum->fetch();
                        $pdo->prepare('UPDATE delivery_notes SET total_amount=? WHERE id=?')->execute([(float)$srow['s'], $id]);
                    }
                    $itemsStmt->execute([$id]);
                    $items = $itemsStmt->fetchAll();
                }
            }
            // Aggregate suppliers for summary
            $supNames = [];
            $supPhones = [];
            foreach ($items as $row) {
                if (!empty($row['supplier_name'])) { $supNames[$row['supplier_name']] = true; }
                if (!empty($row['supplier_phone'])) { $supPhones[$row['supplier_phone']] = true; }
            }
            $dn['suppliers_agg'] = implode(', ', array_keys($supNames));
            $dn['supplier_phones_agg'] = implode(', ', array_keys($supPhones));
            include __DIR__ . '/../views/delivery_notes/print.php';
            break;
        }

        if ($action === 'email_log') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT dn.*, c.name AS customer_name, c.email AS customer_email FROM delivery_notes dn LEFT JOIN customers c ON c.id=dn.customer_id WHERE dn.id=?');
            $stmt->execute([$id]);
            $dn = $stmt->fetch();
            if (!$dn) { http_response_code(404); echo 'Not found'; break; }
            // Fetch logs if table exists
            $logs = [];
            try {
                $pdo->query('SELECT 1 FROM delivery_note_emails LIMIT 1');
                $st = $pdo->prepare('SELECT id, to_email, subject, html_body, text_body, status, error, created_at FROM delivery_note_emails WHERE delivery_note_id=? ORDER BY id DESC');
                $st->execute([$id]);
                $logs = $st->fetchAll();
            } catch (Throwable $e) { $logs = []; }
            // Fallback synthesized row from last_* on dn
            if (empty($logs) && !empty($dn['last_email_status'])) {
                $logs[] = [
                    'id' => 0,
                    'to_email' => (string)($dn['customer_email'] ?? ''),
                    'subject' => (string)($dn['last_email_subject'] ?? '(not recorded)'),
                    'html_body' => null,
                    'text_body' => (string)($dn['last_email_text'] ?? ''),
                    'status' => (string)$dn['last_email_status'],
                    'error' => null,
                    'created_at' => (string)($dn['last_emailed_at'] ?? '')
                ];
            }
            Helpers::view('delivery_notes/email_log', compact('dn','logs'));
            break;
        }

        // Update discount (+/-) for a DN
        if ($action === 'dn_update_discount' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $discount = (float)($_POST['discount'] ?? 0);
            $from = $_POST['from'] ?? date('Y-m-01');
            $to = $_POST['to'] ?? date('Y-m-d');
            $q = trim((string)($_POST['q'] ?? ''));
            if ($id <= 0) {
                Helpers::redirect('index.php?page=delivery_notes&from=' . urlencode($from) . '&to=' . urlencode($to) . ($q!==''?('&q='.urlencode($q)):'') . '&err=invalid_input');
                break;
            }
            // Only allow negative discounts (reduce total); coerce any positive to negative absolute
            $discount = -abs($discount);
            $stmt = $pdo->prepare('UPDATE delivery_notes SET discount=? WHERE id=? AND branch_id=?');
            $stmt->execute([$discount, $id, $branchId]);
            Helpers::redirect('index.php?page=delivery_notes&from=' . urlencode($from) . '&to=' . urlencode($to) . ($q!==''?('&q='.urlencode($q)):'') . '&saved=1');
            break;
        }

        // Update delivery note fields (delivery_date) and optionally vehicle number for all items
        if ($action === 'dn_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $delivery_date = trim((string)($_POST['delivery_date'] ?? ''));
            $vehicle_no = isset($_POST['vehicle_no']) ? trim((string)$_POST['vehicle_no']) : '';
            $from = $_POST['from'] ?? date('Y-m-01');
            $to = $_POST['to'] ?? date('Y-m-d');
            $q = trim((string)($_POST['q'] ?? ''));
            if ($id <= 0 || $delivery_date === '') {
                Helpers::redirect('index.php?page=delivery_notes&from=' . urlencode($from) . '&to=' . urlencode($to) . ($q!==''?('&q='.urlencode($q)):'') . '&err=invalid_input');
                break;
            }
            $stmt = $pdo->prepare('UPDATE delivery_notes SET delivery_date=? WHERE id=? AND branch_id=?');
            $stmt->execute([$delivery_date, $id, $branchId]);
            if ($vehicle_no !== '') {
                // Update vehicle for all parcels in this DN
                $upd = $pdo->prepare('UPDATE parcels p JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id SET p.vehicle_no = ? WHERE dnp.delivery_note_id = ?');
                $upd->execute([$vehicle_no, $id]);
            }
            Helpers::redirect('index.php?page=delivery_notes&from=' . urlencode($from) . '&to=' . urlencode($to) . ($q!==''?('&q='.urlencode($q)):'') . '&saved=1');
            break;
        }

        // Update vehicle number for all parcels attached to a Delivery Note
        if ($action === 'dn_update_vehicle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $new_vehicle = trim((string)($_POST['vehicle_no'] ?? ''));
            $from = $_POST['from'] ?? date('Y-m-01');
            $to = $_POST['to'] ?? date('Y-m-d');
            $q = trim((string)($_POST['q'] ?? ''));
            if ($id <= 0 || $new_vehicle === '') {
                Helpers::redirect('index.php?page=delivery_notes&from=' . urlencode($from) . '&to=' . urlencode($to) . ($q!==''?('&q='.urlencode($q)):'') . '&err=invalid_input');
                break;
            }
            // ensure DN belongs to this branch
            $own = $pdo->prepare('SELECT branch_id FROM delivery_notes WHERE id=?');
            $own->execute([$id]);
            $row = $own->fetch();
            if (!$row || (int)$row['branch_id'] !== (int)$branchId) { http_response_code(403); echo 'Forbidden'; break; }
            // update parcels in this DN
            $upd = $pdo->prepare('UPDATE parcels p
                                   JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id
                                   SET p.vehicle_no = ?
                                   WHERE dnp.delivery_note_id = ?');
            $upd->execute([$new_vehicle, $id]);
            Helpers::redirect('index.php?page=delivery_notes&from=' . urlencode($from) . '&to=' . urlencode($to) . ($q!==''?('&q='.urlencode($q)):'') . '&saved=1');
            break;
        }

        // Delete a delivery note and its items
        if ($action === 'dn_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $from = $_POST['from'] ?? date('Y-m-01');
            $to = $_POST['to'] ?? date('Y-m-d');
            $q = trim((string)($_POST['q'] ?? ''));
            if ($id <= 0) {
                Helpers::redirect('index.php?page=delivery_notes&from=' . urlencode($from) . '&to=' . urlencode($to) . ($q!==''?('&q='.urlencode($q)):'') . '&err=invalid_input');
                break;
            }
            try {
                $pdo->beginTransaction();
                $chk = $pdo->prepare('SELECT branch_id FROM delivery_notes WHERE id=? FOR UPDATE');
                $chk->execute([$id]);
                $row = $chk->fetch();
                if (!$row || (int)$row['branch_id'] !== (int)$branchId) { $pdo->rollBack(); Helpers::redirect('index.php?page=delivery_notes&err=not_allowed'); break; }
                $pdo->prepare('DELETE FROM delivery_note_parcels WHERE delivery_note_id=?')->execute([$id]);
                $pdo->prepare('DELETE FROM delivery_notes WHERE id=?')->execute([$id]);
                $pdo->commit();
                Helpers::redirect('index.php?page=delivery_notes&from=' . urlencode($from) . '&to=' . urlencode($to) . ($q!==''?('&q='.urlencode($q)):'') . '&deleted=1');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                Helpers::redirect('index.php?page=delivery_notes&from=' . urlencode($from) . '&to=' . urlencode($to) . ($q!==''?('&q='.urlencode($q)):'') . '&err=delete_failed');
            }
            break;
        }

        if ($action === 'collect_payment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::canCollectPayments()) { http_response_code(403); echo 'Forbidden'; break; }
            if (!Auth::isMainBranch()) { http_response_code(403); echo 'Forbidden'; break; }
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $from = $_POST['from'] ?? date('Y-m-01');
            $to = $_POST['to'] ?? date('Y-m-d');
            $q = trim((string)($_POST['q'] ?? ''));
            $redir = 'index.php?page=delivery_notes&from=' . urlencode($from) . '&to=' . urlencode($to) . ($q !== '' ? '&q=' . urlencode($q) : '');
            $dnId = (int)($_POST['delivery_note_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $paid_at = $_POST['paid_at'] ?? date('Y-m-d H:i:s');
            if ($dnId <= 0 || $amount <= 0) {
                Helpers::redirect($redir . '&err=payment_invalid');
                break;
            }
            $stmt = $pdo->prepare('SELECT dn.*, COALESCE((SELECT SUM(amount) FROM payments WHERE delivery_note_id=dn.id),0) AS paid FROM delivery_notes dn WHERE dn.id=?');
            $stmt->execute([$dnId]);
            $dn = $stmt->fetch();
            if (!$dn) {
                Helpers::redirect($redir . '&err=payment_not_found');
                break;
            }
            $undel = $pdo->prepare('SELECT COUNT(*) FROM delivery_note_parcels dnp JOIN parcels p ON p.id=dnp.parcel_id WHERE dnp.delivery_note_id=? AND ' . Helpers::parcelSqlBlocksPaymentUntilDelivery());
            $undel->execute([$dnId]);
            if ((int)$undel->fetchColumn() > 0) {
                Helpers::redirect($redir . '&err=payment_not_delivered');
                break;
            }
            $netTotal = (float)$dn['total_amount'] + (float)($dn['discount'] ?? 0);
            $due = $netTotal - (float)$dn['paid'];
            if ($amount > $due) {
                Helpers::redirect($redir . '&err=payment_overdue');
                break;
            }
            $ins = $pdo->prepare('INSERT INTO payments (delivery_note_id, amount, paid_at, received_by) VALUES (?,?,?,?)');
            $ins->execute([$dnId, $amount, $paid_at, (int)($user['id'] ?? 0)]);
            Helpers::redirect($redir . '&collected=1');
            break;
        }

        if ($action === 'customer_summary') {
            // Summary report: invoice count per customer for current branch, with date range
            $from = $_GET['from'] ?? date('Y-m-01');
            $to = $_GET['to'] ?? date('Y-m-d');
            $q = trim($_GET['q'] ?? '');
            $where = ['dn.branch_id = ?','dn.delivery_date BETWEEN ? AND ?'];
            $params = [$branchId, $from, $to];
            if ($q !== '') {
                $where[] = '(c.phone LIKE ? OR c.name LIKE ?)';
                $like = "%$q%";
                array_push($params, $like, $like);
            }
            $sql = 'SELECT c.id AS customer_id, c.name AS customer_name, c.phone AS customer_phone, COUNT(dn.id) AS invoices_count, COALESCE(SUM(dn.total_amount),0) AS total_amount
                    FROM delivery_notes dn
                    LEFT JOIN customers c ON c.id = dn.customer_id
                    WHERE ' . implode(' AND ', $where) . '
                    GROUP BY c.id, c.name, c.phone
                    ORDER BY invoices_count DESC, total_amount DESC
                    LIMIT 500';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            Helpers::view('delivery_notes/customer_summary', compact('rows','from','to','q'));
            break;
        }

        // index list with filters
        $from = Helpers::parseDateOr((string) ($_GET['from'] ?? ''), date('Y-m-01'));
        $to = Helpers::parseDateOr((string) ($_GET['to'] ?? ''), date('Y-m-d'));
        [$from, $to] = Helpers::orderDateRange($from, $to);
        $q = trim((string) ($_GET['q'] ?? ''));
        $vehicle = trim((string) ($_GET['vehicle'] ?? ''));
        $direction = (string) ($_GET['direction'] ?? '');
        if ($direction !== 'to' && $direction !== 'from') {
            $direction = '';
        }
        $isMain = Auth::isMainBranch();
        $filterBranchId = $isMain
            ? BranchRepository::resolveToFixedBranchId($pdo, (int) ($_GET['branch_id'] ?? $branchId))
            : $branchId;
        if ($filterBranchId <= 0) {
            $filterBranchId = $branchId;
        }
        $branchesAll = $isMain ? BranchRepository::forFilters($pdo) : [];
        $hasFilters = $q !== ''
            || $vehicle !== ''
            || $direction !== ''
            || ($isMain && $filterBranchId !== $branchId)
            || $from !== date('Y-m-01')
            || $to !== date('Y-m-d');

        $where = ['dn.branch_id = ?', 'dn.delivery_date BETWEEN ? AND ?'];
        $params = [$filterBranchId, $from, $to];
        if ($q !== '') {
            $where[] = '(c.phone LIKE ? OR c.name LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like);
        }
        if ($vehicle !== '') {
            $where[] = 'EXISTS (
                SELECT 1 FROM delivery_note_parcels dnp_v
                JOIN parcels p_v ON p_v.id = dnp_v.parcel_id
                WHERE dnp_v.delivery_note_id = dn.id AND COALESCE(p_v.vehicle_no, \'\') LIKE ?
            )';
            $params[] = '%' . $vehicle . '%';
        }
        if ($direction === 'to') {
            $where[] = 'EXISTS (
                SELECT 1 FROM delivery_note_parcels dnp_dir
                JOIN parcels p_dir ON p_dir.id = dnp_dir.parcel_id
                WHERE dnp_dir.delivery_note_id = dn.id AND p_dir.to_branch_id = ?
            )';
            $params[] = $filterBranchId;
        } elseif ($direction === 'from') {
            $where[] = 'EXISTS (
                SELECT 1 FROM delivery_note_parcels dnp_dir
                JOIN parcels p_dir ON p_dir.id = dnp_dir.parcel_id
                WHERE dnp_dir.delivery_note_id = dn.id AND p_dir.from_branch_id = ?
            )';
            $params[] = $filterBranchId;
        }
        // Best-effort: backfill items for DNs in range that currently have zero items so list columns can show data
        try {
            $findEmpty = $pdo->prepare('SELECT dn.id, dn.customer_id, dn.branch_id FROM delivery_notes dn LEFT JOIN delivery_note_parcels dnp ON dnp.delivery_note_id=dn.id WHERE dn.branch_id=? AND dn.delivery_date BETWEEN ? AND ? GROUP BY dn.id, dn.customer_id, dn.branch_id HAVING COUNT(dnp.id)=0 LIMIT 100');
            $findEmpty->execute([$filterBranchId, $from, $to]);
            $empties = $findEmpty->fetchAll();
            foreach ($empties as $row) {
                $dnId = (int)$row['id']; $cid = (int)$row['customer_id']; $bid = (int)$row['branch_id'];
                // Attach pending parcels not already in any DN, not delivered
                // Try arrivals (to_branch) first
                $sel = $pdo->prepare('SELECT p.id, COALESCE(p.price,0) AS price FROM parcels p LEFT JOIN delivery_note_parcels x ON x.parcel_id=p.id WHERE p.customer_id=? AND p.to_branch_id=? AND x.id IS NULL AND ' . Helpers::parcelSqlEligibleForOpenBilling());
                $sel->execute([$cid, $bid]);
                $prs = $sel->fetchAll();
                // If none, try dispatch (from_branch)
                if (!$prs) {
                    $sel2 = $pdo->prepare('SELECT p.id, COALESCE(p.price,0) AS price FROM parcels p LEFT JOIN delivery_note_parcels x ON x.parcel_id=p.id WHERE p.customer_id=? AND p.from_branch_id=? AND x.id IS NULL AND ' . Helpers::parcelSqlEligibleForOpenBilling());
                    $sel2->execute([$cid, $bid]);
                    $prs = $sel2->fetchAll();
                }
                if ($prs) {
                    $sum = 0.0; $ids=[];
                    $ins = $pdo->prepare('INSERT IGNORE INTO delivery_note_parcels (delivery_note_id, parcel_id, amount) VALUES (?,?,?)');
                    foreach ($prs as $p) { $amt=(float)str_replace([',',' '], '', (string)$p['price']); $ins->execute([$dnId,(int)$p['id'],$amt]); $sum += $amt; $ids[]=(int)$p['id']; }
                    // Update total
                    $sumQ = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS s FROM delivery_note_parcels WHERE delivery_note_id=?');
                    $sumQ->execute([$dnId]);
                    $srow = $sumQ->fetch();
                    $pdo->prepare('UPDATE delivery_notes SET total_amount=? WHERE id=?')->execute([(float)($srow['s'] ?? 0), $dnId]);
                }
            }
        } catch (Throwable $e) { /* ignore */ }

        $sql = 'SELECT dn.*, (dn.total_amount + COALESCE(dn.discount,0)) AS net_total,
                       COALESCE(paid.total_paid, 0) AS paid,
                       c.name AS customer_name, c.phone AS customer_phone,
                       dn.last_email_status AS email_status, dn.last_emailed_at AS emailed_at,
                       TRIM(BOTH "," FROM GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ", ")) AS suppliers,
                       TRIM(BOTH "," FROM GROUP_CONCAT(DISTINCT COALESCE(s.phone, "") ORDER BY s.phone SEPARATOR ", ")) AS supplier_phones,
                       COALESCE(NULLIF(TRIM(BOTH "," FROM GROUP_CONCAT(DISTINCT COALESCE(p.vehicle_no, "") ORDER BY p.vehicle_no SEPARATOR ", ")), ""), dra.vehicle_no, "") AS vehicles,
                       TRIM(BOTH "," FROM GROUP_CONCAT(DISTINCT TRIM(pit.description) ORDER BY pit.id SEPARATOR ", ")) AS item_descriptions
                FROM delivery_notes dn
                LEFT JOIN customers c ON c.id = dn.customer_id
                LEFT JOIN (
                    SELECT delivery_note_id, SUM(amount) AS total_paid
                    FROM payments GROUP BY delivery_note_id
                ) paid ON paid.delivery_note_id = dn.id
                LEFT JOIN delivery_note_parcels dnp ON dnp.delivery_note_id = dn.id
                LEFT JOIN parcels p ON p.id = dnp.parcel_id
                LEFT JOIN suppliers s ON s.id = p.supplier_id
                LEFT JOIN parcel_items pit ON pit.parcel_id = p.id
                LEFT JOIN delivery_route_assignments dra ON dra.customer_id = dn.customer_id AND dra.branch_id = dn.branch_id AND dra.delivery_date = dn.delivery_date
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY dn.id
                ORDER BY dn.delivery_date DESC, dn.id DESC
                LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $notes = $stmt->fetchAll();
        Helpers::view('delivery_notes/index', compact(
            'notes', 'from', 'to', 'q', 'vehicle', 'direction',
            'filterBranchId', 'isMain', 'branchesAll', 'hasFilters'
        ));
        break;

    case 'expenses':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        if (!Auth::canManageExpenses()) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $user = Auth::user();
        $branchId = (int)($user['branch_id'] ?? 0);
        $isAdmin = Auth::hasRole('admin');
        $action = $_GET['action'] ?? 'index';

        if (!empty($_GET['exp_action']) || !empty($_POST['exp_action'])) {
            ExpenseApi::dispatch($pdo, $user, $isAdmin);
            break;
        }

        // Legacy form routes (redirect to modal-based UI)
        if (in_array($action, ['new', 'edit'], true)) {
            $q = 'index.php?page=expenses';
            if ($action === 'edit' && !empty($_GET['id'])) {
                $q .= '&edit=' . (int)$_GET['id'];
            }
            Helpers::redirect($q);
            break;
        }

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            try {
                ExpenseSchemaRepository::ensureSchema($pdo);
                ExpenseRepository::save($pdo, $_POST, (int)($user['id'] ?? 0));
                Helpers::redirect('index.php?page=expenses&saved=1');
            } catch (Throwable $e) {
                $branchesAll = BranchRepository::forDropdowns($pdo);
                $expense = $_POST;
                $typesDynamic = [];
                $error = $e->getMessage();
                Helpers::view('expenses/form', compact('expense','branchesAll','error','typesDynamic'));
            }
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    ExpenseSchemaRepository::ensureSchema($pdo);
                    ExpenseRepository::delete($pdo, $id);
                } catch (Throwable $e) { /* ignore */ }
            }
            Helpers::redirect('index.php?page=expenses&deleted=1');
            break;
        }

        if ($action === 'approve' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$isAdmin) { http_response_code(403); echo 'Forbidden'; break; }
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    ExpenseSchemaRepository::ensureSchema($pdo);
                    ExpenseRepository::approve($pdo, $id, (int)($user['id'] ?? 0));
                } catch (Throwable $e) { Helpers::redirect('index.php?page=expenses&err=' . rawurlencode($e->getMessage())); break; }
            }
            Helpers::redirect('index.php?page=expenses&approved=1');
            break;
        }

        if ($action === 'settle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $expense_id = (int)($_POST['id'] ?? 0);
            $pay_amount = (float)($_POST['pay_amount'] ?? 0);
            $pay_notes = trim($_POST['pay_notes'] ?? '');
            if ($expense_id > 0) {
                try {
                    ExpenseSchemaRepository::ensureSchema($pdo);
                    ExpenseRepository::settle($pdo, $expense_id, $pay_amount, (int)($user['id'] ?? 0), $pay_notes);
                } catch (Throwable $e) { /* ignore */ }
            }
            Helpers::redirect('index.php?page=expenses&settled=1');
            break;
        }

        ExpenseSchemaRepository::ensureSchema($pdo);
        $branchesAll = BranchRepository::forFilters($pdo);
        $editId = (int)($_GET['edit'] ?? 0);
        Helpers::view('expenses/index', compact('branchesAll', 'isAdmin', 'branchId', 'editId'));
        break;

    case 'advances':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $user = Auth::user();
        $isAdmin = Auth::hasRole('admin');
        $isAccountant = (isset($user['role']) && $user['role'] === 'accountant');
        if (!($isAdmin || $isAccountant)) { http_response_code(403); echo 'Forbidden'; break; }
        $branchId = BranchRepository::resolveToFixedBranchId($pdo, (int)($user['branch_id'] ?? 0));
        $action = $_GET['action'] ?? 'index';

        // Best-effort ensure tables exist
        try { $pdo->query('SELECT 1 FROM employee_advances LIMIT 1'); } catch (Throwable $e) {
            try { $pdo->exec("CREATE TABLE IF NOT EXISTS employee_advances (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT, employee_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NOT NULL, amount DECIMAL(12,2) NOT NULL, advance_date DATE NOT NULL, purpose VARCHAR(255) NULL, settled TINYINT(1) NOT NULL DEFAULT 0, created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); } catch (Throwable $e2) {}
        }
        try { $pdo->query('SELECT 1 FROM employee_advance_payments LIMIT 1'); } catch (Throwable $e) {
            try { $pdo->exec("CREATE TABLE IF NOT EXISTS employee_advance_payments (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT, advance_id BIGINT UNSIGNED NOT NULL, amount DECIMAL(12,2) NOT NULL, paid_at DATETIME NOT NULL, notes VARCHAR(255) NULL, created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); } catch (Throwable $e2) {}
        }

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $employee_id = (int)($_POST['employee_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $advance_date = $_POST['advance_date'] ?? date('Y-m-d');
            $purpose = trim($_POST['purpose'] ?? '');
            try { if ($advance_date) { $ts = strtotime($advance_date); if ($ts) { $advance_date = date('Y-m-d', $ts); } } } catch (Throwable $dt) {}
            if ($employee_id<=0 || $amount<=0) {
                $error = 'Employee and Amount are required.';
                $employeesAll = $pdo->query('SELECT id, name FROM employees ORDER BY name')->fetchAll();
                $advance = compact('id','employee_id','amount','advance_date','purpose');
                Helpers::view('advances/form', compact('advance','employeesAll','error'));
                break;
            }
            $empBranchStmt = $pdo->prepare('SELECT branch_id FROM employees WHERE id=? LIMIT 1');
            $empBranchStmt->execute([$employee_id]);
            $empBranchId = BranchRepository::resolveToFixedBranchId($pdo, (int)($empBranchStmt->fetchColumn() ?: $branchId));
            if ($empBranchId <= 0) {
                $empBranchId = $branchId;
            }
            if ($id>0) {
                $stmt = $pdo->prepare('UPDATE employee_advances SET employee_id=?, branch_id=?, amount=?, advance_date=?, purpose=? WHERE id=?');
                $stmt->execute([$employee_id,$empBranchId,$amount,$advance_date,($purpose?:null),$id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO employee_advances (employee_id, branch_id, amount, advance_date, purpose, created_by) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$employee_id,$empBranchId,$amount,$advance_date,($purpose?:null),(int)($user['id'] ?? 0)]);
            }
            Helpers::redirect('index.php?page=advances');
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id>0) { $pdo->prepare('DELETE FROM employee_advances WHERE id=?')->execute([$id]); }
            Helpers::redirect('index.php?page=advances');
            break;
        }

        if ($action === 'settle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $pay_amount = (float)($_POST['pay_amount'] ?? 0);
            $pay_notes = trim($_POST['pay_notes'] ?? '');
            if ($id<=0) { http_response_code(400); echo 'Invalid'; break; }
            $row = $pdo->prepare('SELECT amount FROM employee_advances WHERE id=?');
            $row->execute([$id]); $adv = $row->fetch(); if (!$adv) { http_response_code(404); echo 'Not found'; break; }
            $agg = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS paid_total FROM employee_advance_payments WHERE advance_id=?');
            $agg->execute([$id]); $paid = (float)($agg->fetch()['paid_total'] ?? 0);
            $balance = max(0.0, (float)$adv['amount'] - $paid);
            $apply = ($pay_amount<=0) ? $balance : min($pay_amount,$balance);
            if ($apply>0) {
                $pdo->prepare('INSERT INTO employee_advance_payments (advance_id, amount, paid_at, notes, created_by) VALUES (?,?,?,?,?)')
                    ->execute([$id,$apply,date('Y-m-d H:i:s'),($pay_notes?:null),(int)($user['id'] ?? 0)]);
                if (($paid + $apply + 0.0001) >= (float)$adv['amount']) { $pdo->prepare('UPDATE employee_advances SET settled=1 WHERE id=?')->execute([$id]); }
            }
            Helpers::redirect('index.php?page=advances');
            break;
        }

        if ($action === 'new') {
            $employeesAll = $pdo->query('SELECT id, name FROM employees ORDER BY name')->fetchAll();
            $advance = ['id'=>0,'employee_id'=>0,'amount'=>'','advance_date'=>date('Y-m-d'),'purpose'=>''];
            Helpers::view('advances/form', compact('advance','employeesAll'));
            break;
        }
        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM employee_advances WHERE id=?');
            $stmt->execute([$id]); $advance = $stmt->fetch();
            if (!$advance) { http_response_code(404); echo 'Not found'; break; }
            $employeesAll = $pdo->query('SELECT id, name FROM employees ORDER BY name')->fetchAll();
            Helpers::view('advances/form', compact('advance','employeesAll'));
            break;
        }

        // index
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        $empFilter = (int)($_GET['employee_id'] ?? 0);
        $where = ['a.advance_date BETWEEN ? AND ?'];
        $params = [$from,$to];
        if ($empFilter>0) { $where[] = 'a.employee_id=?'; $params[] = $empFilter; }
        $sql = 'SELECT a.*, e.name AS employee_name, COALESCE(p.paid_total,0) AS paid_total, (a.amount - COALESCE(p.paid_total,0)) AS balance
                FROM employee_advances a
                LEFT JOIN employees e ON e.id = a.employee_id
                LEFT JOIN (SELECT advance_id, SUM(amount) AS paid_total FROM employee_advance_payments GROUP BY advance_id) p ON p.advance_id = a.id
                WHERE ' . implode(' AND ', $where) . ' ORDER BY a.advance_date DESC, a.id DESC LIMIT 300';
        $stmt = $pdo->prepare($sql); $stmt->execute($params); $advances = $stmt->fetchAll();
        $employeesAll = $pdo->query('SELECT id, name FROM employees ORDER BY name')->fetchAll();
        Helpers::view('advances/index', compact('advances','from','to','employeesAll','empFilter'));
        break;

    case 'reminders':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $user = Auth::user();
        $isAdmin = Auth::hasRole('admin');
        $isAccountant = (isset($user['role']) && $user['role'] === 'accountant');
        if (!($isAdmin || $isAccountant)) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';

        // Ensure table exists best-effort
        try { $pdo->query('SELECT 1 FROM reminders LIMIT 1'); }
        catch (Throwable $e) {
            try { $pdo->exec("CREATE TABLE IF NOT EXISTS reminders (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT, title VARCHAR(180) NOT NULL, category VARCHAR(60) NULL, due_date DATE NOT NULL, repeat_interval ENUM('none','monthly','quarterly','yearly') NOT NULL DEFAULT 'none', notify_before_days INT NOT NULL DEFAULT 7, notes VARCHAR(255) NULL, status ENUM('open','done') NOT NULL DEFAULT 'open', created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"); } catch (Throwable $e2) {}
        }

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $due_date = $_POST['due_date'] ?? date('Y-m-d');
            $repeat = $_POST['repeat_interval'] ?? 'none';
            $repeat_every_days = (int)($_POST['repeat_every_days'] ?? 0);
            $notify_days = (int)($_POST['notify_before_days'] ?? 7);
            $notes = trim($_POST['notes'] ?? '');
            try { if ($due_date) { $ts = strtotime($due_date); if ($ts) { $due_date = date('Y-m-d', $ts); } } } catch (Throwable $dt) {}
            if ($title==='') { $error = 'Title is required.'; }
            if (!empty($error ?? '')) {
                $reminder = compact('id','title','category','due_date','repeat','notify_days','notes');
                Helpers::view('reminders/form', compact('reminder','error'));
                break;
            }
            if ($id>0) {
                // Try to include repeat_every_days if column exists
                $hasDays = true; try { $pdo->query('SELECT repeat_every_days FROM reminders LIMIT 1'); } catch (Throwable $e) { $hasDays = false; }
                if ($hasDays) {
                    $stmt = $pdo->prepare('UPDATE reminders SET title=?, category=?, due_date=?, repeat_interval=?, repeat_every_days=?, notify_before_days=?, notes=? WHERE id=?');
                    $stmt->execute([$title,($category?:null),$due_date,$repeat,($repeat_every_days>0?$repeat_every_days:null),$notify_days,($notes?:null),$id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE reminders SET title=?, category=?, due_date=?, repeat_interval=?, notify_before_days=?, notes=? WHERE id=?');
                    $stmt->execute([$title,($category?:null),$due_date,$repeat,$notify_days,($notes?:null),$id]);
                }
            } else {
                $hasDays = true; try { $pdo->query('SELECT repeat_every_days FROM reminders LIMIT 1'); } catch (Throwable $e) { $hasDays = false; }
                if ($hasDays) {
                    $stmt = $pdo->prepare('INSERT INTO reminders (title, category, due_date, repeat_interval, repeat_every_days, notify_before_days, notes, created_by) VALUES (?,?,?,?,?,?,?,?)');
                    $stmt->execute([$title,($category?:null),$due_date,$repeat,($repeat_every_days>0?$repeat_every_days:null),$notify_days,($notes?:null),(int)($user['id'] ?? 0)]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO reminders (title, category, due_date, repeat_interval, notify_before_days, notes, created_by) VALUES (?,?,?,?,?,?,?)');
                    $stmt->execute([$title,($category?:null),$due_date,$repeat,$notify_days,($notes?:null),(int)($user['id'] ?? 0)]);
                }
            }
            Helpers::redirect('index.php?page=reminders');
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id>0) { $pdo->prepare('DELETE FROM reminders WHERE id=?')->execute([$id]); }
            Helpers::redirect('index.php?page=reminders');
            break;
        }

        if ($action === 'mark_done' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id>0) {
                $pdo->prepare("UPDATE reminders SET status='done' WHERE id=?")->execute([$id]);
                // Auto-create next occurrence if repeating
                $rowS = $pdo->prepare('SELECT title, category, due_date, repeat_interval, repeat_every_days, notify_before_days, notes FROM reminders WHERE id=?');
                $rowS->execute([$id]); $r = $rowS->fetch();
                $next = null;
                if ($r) {
                    if (!empty($r['repeat_every_days'])) {
                        $d = (int)$r['repeat_every_days']; if ($d>0) { $next = date('Y-m-d', strtotime($r['due_date'].' +'.$d.' days')); }
                    } else if (in_array($r['repeat_interval'], ['monthly','quarterly','yearly'], true)) {
                        if ($r['repeat_interval']==='monthly') { $next = date('Y-m-d', strtotime($r['due_date'].' +1 month')); }
                        else if ($r['repeat_interval']==='quarterly') { $next = date('Y-m-d', strtotime($r['due_date'].' +3 months')); }
                        else if ($r['repeat_interval']==='yearly') { $next = date('Y-m-d', strtotime($r['due_date'].' +1 year')); }
                    }
                    if ($next) {
                        $hasDays = true; try { $pdo->query('SELECT repeat_every_days FROM reminders LIMIT 1'); } catch (Throwable $e) { $hasDays = false; }
                        if ($hasDays) {
                            $pdo->prepare('INSERT INTO reminders (title, category, due_date, repeat_interval, repeat_every_days, notify_before_days, notes, created_by) VALUES (?,?,?,?,?,?,?,?)')
                                ->execute([$r['title'],($r['category']?:null),$next,$r['repeat_interval'],($r['repeat_every_days'] ?? null),(int)$r['notify_before_days'],($r['notes']?:null),(int)($user['id'] ?? 0)]);
                        } else {
                            $pdo->prepare('INSERT INTO reminders (title, category, due_date, repeat_interval, notify_before_days, notes, created_by) VALUES (?,?,?,?,?,?,?)')
                                ->execute([$r['title'],($r['category']?:null),$next,$r['repeat_interval'],(int)$r['notify_before_days'],($r['notes']?:null),(int)($user['id'] ?? 0)]);
                        }
                    }
                }
            }
            Helpers::redirect('index.php?page=reminders');
            break;
        }

        if ($action === 'new') {
            $reminder = ['id'=>0,'title'=>'','category'=>'','due_date'=>date('Y-m-d'),'repeat'=>'none','notify_days'=>7,'repeat_every_days'=>'','notes'=>''];
            Helpers::view('reminders/form', compact('reminder'));
            break;
        }
        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM reminders WHERE id=?');
            $stmt->execute([$id]); $reminder = $stmt->fetch();
            if (!$reminder) { http_response_code(404); echo 'Not found'; break; }
            Helpers::view('reminders/form', compact('reminder'));
            break;
        }

        // index
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        $cat = trim($_GET['category'] ?? '');
        $status = trim($_GET['status'] ?? ''); // '', open, done
        $where = ['r.due_date BETWEEN ? AND ?']; $params = [$from,$to];
        if ($cat!=='') { $where[] = 'COALESCE(r.category,"") = ?'; $params[] = $cat; }
        if ($status==='open') { $where[] = "r.status='open'"; }
        else if ($status==='done') { $where[] = "r.status='done'"; }
        $sql = 'SELECT r.* FROM reminders r WHERE ' . implode(' AND ', $where) . ' ORDER BY r.due_date ASC, r.id DESC LIMIT 500';
        $stmt = $pdo->prepare($sql); $stmt->execute($params); $reminders = $stmt->fetchAll();
        Helpers::view('reminders/index', compact('reminders','from','to','cat','status'));
        break;
    case 'employees':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $user = Auth::user();
        $isAdmin = Auth::hasRole('admin');
        $isAccountant = (isset($user['role']) && $user['role'] === 'accountant');
        if (!($isAdmin || $isAccountant)) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';

        if (!empty($_GET['emp_action']) || !empty($_POST['emp_action'])) {
            EmployeeApi::dispatch($pdo, $user, $isAdmin);
            break;
        }

        if ($action === 'list_json') {
            header('Content-Type: application/json; charset=utf-8');
            EmployeeSchemaRepository::ensureSchema($pdo);
            $result = EmployeeRepository::list($pdo, $_GET, 1, 500);
            echo json_encode(['ok' => true, 'success' => true, 'employees' => $result['rows'], 'count' => $result['total']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
        }

        if (in_array($action, ['new', 'edit'], true)) {
            $q = 'index.php?page=employees';
            if ($action === 'edit' && !empty($_GET['id'])) {
                $q .= '&edit=' . (int)$_GET['id'];
            } else {
                $q .= '&new=1';
            }
            Helpers::redirect($q);
            break;
        }

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            try {
                EmployeeSchemaRepository::ensureSchema($pdo);
                $saved = EmployeeRepository::save($pdo, $_POST, (int)($user['id'] ?? 0));
                $savedId = (int)($saved['id'] ?? 0);
                $redir = trim($_POST['redirect_to'] ?? '');
                if ($redir !== '') {
                    Helpers::redirect($redir);
                } elseif (!empty($_POST['ajax']) || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => true, 'success' => true, 'data' => $saved]);
                } else {
                    Helpers::redirect('index.php?page=employees&edit=' . $savedId . '&saved=1');
                }
            } catch (Throwable $ex) {
                $error = $ex->getMessage();
                $employee = $_POST;
                $branchesAll = BranchRepository::forDropdowns($pdo);
                try { $vehiclesAll = $pdo->query('SELECT id, reg_number AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); } catch (Throwable $e) { $vehiclesAll = []; }
                $rolesDynamic = [];
                try { $rolesDynamic = $pdo->query("SELECT DISTINCT role FROM employees WHERE role IS NOT NULL AND role<>'' ORDER BY role")->fetchAll(); } catch (Throwable $e) {}
                $cashbookAccountId = 0;
                Helpers::view('employees/form', compact('employee','branchesAll','vehiclesAll','rolesDynamic','error','cashbookAccountId'));
            }
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    EmployeeSchemaRepository::ensureSchema($pdo);
                    EmployeeRepository::softDelete($pdo, $id, (int)($user['id'] ?? 0));
                } catch (Throwable $e) { /* ignore */ }
            }
            Helpers::redirect('index.php?page=employees&deleted=1');
            break;
        }

        if ($action === 'new') {
            $employee = [
                'id'=>0,
                'name'=>'',
                'position'=>'',
                'branch_id'=>0
            ];
            $branchesAll = BranchRepository::forDropdowns($pdo);
            // Load vehicles for dropdown (use reg_number explicitly)
            try { $vehiclesAll = $pdo->query('SELECT id, reg_number AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); } catch (Throwable $e) { $vehiclesAll = []; }
            $rolesDynamic = [];
            try { $rolesDynamic = $pdo->query("SELECT DISTINCT role FROM employees WHERE role IS NOT NULL AND role<>'' ORDER BY role")->fetchAll(); } catch (Throwable $e) {}
            $cashbookAccountId = 0;
            Helpers::view('employees/form', compact('employee','branchesAll','vehiclesAll','rolesDynamic','cashbookAccountId'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM employees WHERE id=?');
            $stmt->execute([$id]);
            $employee = $stmt->fetch();
            if (!$employee) { http_response_code(404); echo 'Not found'; break; }
            $branchesAll = BranchRepository::forDropdowns($pdo);
            // Load vehicles for dropdown (id + best-guess number)
            try { $vehiclesAll = $pdo->query('SELECT id, COALESCE(vehicle_no, reg_number, plate_no) AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); } catch (Throwable $e) { $vehiclesAll = []; }
            $rolesDynamic = [];
            try { $rolesDynamic = $pdo->query("SELECT DISTINCT role FROM employees WHERE role IS NOT NULL AND role<>'' ORDER BY role")->fetchAll(); } catch (Throwable $e) {}
            $cashbookAccountId = 0;
            try {
                $stCa = $pdo->prepare('SELECT id FROM cashbook_accounts WHERE employee_id = ? LIMIT 1');
                $stCa->execute([$id]);
                $cashbookAccountId = (int)($stCa->fetchColumn() ?: 0);
            } catch (Throwable $e) {
            }
            Helpers::view('employees/form', compact('employee','branchesAll','vehiclesAll','rolesDynamic','cashbookAccountId'));
            break;
        }

        // payroll sub-view: list latest payroll row per employee (with simple filters)
        if ($action === 'payroll') {
            // Filters
            $emp_code = trim($_GET['emp_code'] ?? '');
            $name = trim($_GET['name'] ?? '');
            $position = trim($_GET['position'] ?? '');
            $branch_id = BranchRepository::resolveToFixedBranchId($pdo, (int)($_GET['branch_id'] ?? 0));
            $month_year = trim($_GET['month_year'] ?? ''); // YYYY-MM

            $sql = "SELECT e.id, e.emp_code, e.name, e.position, b.name AS branch_name,
                           p.id AS payroll_id, p.basic_salary, p.epf_employee, p.epf_employer, p.etf, p.allowance, p.deductions, p.net_salary, p.month_year
                    FROM employees e
                    LEFT JOIN branches b ON b.id = e.branch_id
                    LEFT JOIN (
                      SELECT ep.* FROM employee_payroll ep
                      INNER JOIN (
                        SELECT employee_id, MAX(month_year) AS mm FROM employee_payroll GROUP BY employee_id
                      ) m ON m.employee_id = ep.employee_id AND m.mm = ep.month_year
                    ) p ON p.employee_id = e.id
                    WHERE 1=1";
            $params = [];
            if ($emp_code !== '') { $sql .= ' AND e.emp_code LIKE ?'; $params[] = "%$emp_code%"; }
            if ($name !== '') { $sql .= ' AND e.name LIKE ?'; $params[] = "%$name%"; }
            if ($position !== '') { $sql .= ' AND e.position LIKE ?'; $params[] = "%$position%"; }
            if ($branch_id > 0) { $sql .= ' AND e.branch_id = ?'; $params[] = $branch_id; }
            if ($month_year !== '') { $sql .= ' AND COALESCE(p.month_year, "") = ?'; $params[] = $month_year; }
            $sql .= ' ORDER BY e.created_at DESC, e.id DESC LIMIT 500';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $employees = $stmt->fetchAll();
            $branchesAll = BranchRepository::forFilters($pdo);
            Helpers::view('employees/payroll', compact('employees','emp_code','name','position','branch_id','month_year','branchesAll'));
            break;
        }
        // new payroll entry form (choose employee and enter payroll fields)
        if ($action === 'new_payroll') {
            $employee = ['id'=>0,'month_year'=>date('Y-m'),'basic_salary'=>'0.00','epf_employee'=>'0.00','epf_employer'=>'0.00','etf'=>'0.00','allowance'=>'0.00','deductions'=>'0.00'];
            $selectedEmployeeId = (int)($_GET['employee_id'] ?? 0);
            $employeesAll = $pdo->query('SELECT id, emp_code, name FROM employees ORDER BY id DESC LIMIT 500')->fetchAll();
            Helpers::view('employees/payroll_form', compact('employee','employeesAll','selectedEmployeeId'));
            break;
        }
        // save payroll row (insert or update)
        if ($action === 'save_payroll' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $payroll_id  = (int)($_POST['id'] ?? 0);
            $employee_id = (int)($_POST['employee_id'] ?? 0);
            $month_year  = trim($_POST['month_year'] ?? date('Y-m'));
            $basic_salary = (float)($_POST['basic_salary'] ?? 0);
            $epf_employee = (float)($_POST['epf_employee'] ?? 0);
            $epf_employer = (float)($_POST['epf_employer'] ?? 0);
            $etf          = (float)($_POST['etf'] ?? 0);
            $allowance    = (float)($_POST['allowance'] ?? 0);
            $deductions   = (float)($_POST['deductions'] ?? 0);
            if ($employee_id <= 0) { http_response_code(400); echo 'Employee required'; break; }
            if ($payroll_id > 0) {
                $stmt = $pdo->prepare('UPDATE employee_payroll SET employee_id=?, month_year=?, basic_salary=?, epf_employee=?, epf_employer=?, etf=?, allowance=?, deductions=? WHERE id=?');
                $stmt->execute([$employee_id, $month_year, $basic_salary, $epf_employee, $epf_employer, $etf, $allowance, $deductions, $payroll_id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO employee_payroll (employee_id, month_year, basic_salary, epf_employee, epf_employer, etf, allowance, deductions) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->execute([$employee_id, $month_year, $basic_salary, $epf_employee, $epf_employer, $etf, $allowance, $deductions]);
            }
            Helpers::redirect('index.php?page=employees&action=payroll');
            break;
        }
        // edit payroll row
        if ($action === 'edit_payroll') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM employee_payroll WHERE id=?');
            $stmt->execute([$id]);
            $employee = $stmt->fetch();
            if (!$employee) { http_response_code(404); echo 'Not found'; break; }
            $employeesAll = $pdo->query('SELECT id, emp_code, name FROM employees ORDER BY id DESC LIMIT 500')->fetchAll();
            Helpers::view('employees/payroll_form', compact('employee','employeesAll'));
            break;
        }
        // delete payroll row
        if ($action === 'payroll_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) { $pdo->prepare('DELETE FROM employee_payroll WHERE id=?')->execute([$id]); }
            Helpers::redirect('index.php?page=employees&action=payroll');
            break;
        }
        // print payroll row. If id (payroll_id) provided, use it; else if employee_id provided, print blank/zero template for that employee
        if ($action === 'payroll_print') {
            $id = (int)($_GET['id'] ?? 0);
            $employee_id = (int)($_GET['employee_id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT ep.*, e.emp_code, e.name, e.position, b.name AS branch_name FROM employee_payroll ep JOIN employees e ON e.id=ep.employee_id LEFT JOIN branches b ON b.id=e.branch_id WHERE ep.id=?');
                $stmt->execute([$id]);
                $employee = $stmt->fetch();
                if (!$employee) { http_response_code(404); echo 'Not found'; break; }
            } else if ($employee_id > 0) {
                $stmt = $pdo->prepare('SELECT e.id, e.emp_code, e.name, e.position, b.name AS branch_name FROM employees e LEFT JOIN branches b ON b.id=e.branch_id WHERE e.id=?');
                $stmt->execute([$employee_id]);
                $emp = $stmt->fetch();
                if (!$emp) { http_response_code(404); echo 'Not found'; break; }
                // Build zeroed payroll structure compatible with the view
                $employee = [
                    'employee_id'   => (int)$emp['id'],
                    'emp_code'      => (string)($emp['emp_code'] ?? ''),
                    'name'          => (string)($emp['name'] ?? ''),
                    'position'      => (string)($emp['position'] ?? ''),
                    'branch_name'   => (string)($emp['branch_name'] ?? ''),
                    'month_year'    => date('Y-m'),
                    'status'        => '',
                    'basic_salary'  => 0.0,
                    'epf_employee'  => 0.0,
                    'epf_employer'  => 0.0,
                    'etf'           => 0.0,
                    'allowance'     => 0.0,
                    'deductions'    => 0.0,
                    'net_salary'    => 0.0,
                ];
            } else {
                http_response_code(400); echo 'Bad request'; break;
            }
            require __DIR__ . '/../views/employees/payroll_print.php';
            return;
        }

        // index — ERP HRMS dashboard (data via AJAX)
        EmployeeSchemaRepository::ensureSchema($pdo);
        $branchesAll = BranchRepository::forFilters($pdo);
        $editId = (int)($_GET['edit'] ?? 0);
        $openNew = !empty($_GET['new']);
        Helpers::view('employees/index', compact('branchesAll', 'isAdmin', 'editId', 'openNew'));
        break;

    case 'search':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $phone = trim($_GET['phone'] ?? '');
        $name = trim($_GET['name'] ?? '');
        $customer = null; $parcels = []; $notes = []; $dueSummary = null; $matches = [];
        if ($phone !== '') {
            // Exact phone match
            $stmt = $pdo->prepare('SELECT * FROM customers WHERE phone = ? LIMIT 1');
            $stmt->execute([$phone]);
            $customer = $stmt->fetch();
        } elseif ($name !== '') {
            // Name LIKE search (case-insensitive)
            $like = '%' . $name . '%';
            $stmt = $pdo->prepare('SELECT * FROM customers WHERE name LIKE ? ORDER BY name LIMIT 20');
            $stmt->execute([$like]);
            $matches = $stmt->fetchAll();
            if (count($matches) === 1) {
                $customer = $matches[0];
            }
        }
        if ($customer) {
            $pid = (int)$customer['id'];
            $parcelsStmt = $pdo->prepare('SELECT p.*, s.name AS supplier_name, bf.name AS from_branch, bt.name AS to_branch FROM parcels p LEFT JOIN suppliers s ON s.id=p.supplier_id LEFT JOIN branches bf ON bf.id=p.from_branch_id LEFT JOIN branches bt ON bt.id=p.to_branch_id WHERE p.customer_id=? ORDER BY p.created_at DESC LIMIT 200');
            $parcelsStmt->execute([$pid]);
            $parcels = $parcelsStmt->fetchAll();
            $notesStmt = $pdo->prepare('SELECT dn.*, b.name AS branch_name FROM delivery_notes dn LEFT JOIN branches b ON b.id=dn.branch_id WHERE dn.customer_id=? ORDER BY dn.delivery_date DESC, dn.id DESC LIMIT 200');
            $notesStmt->execute([$pid]);
            $notes = $notesStmt->fetchAll();
            // Due summary from delivery notes - payments
            $dueStmt = $pdo->prepare('SELECT COALESCE(SUM(dn.total_amount),0) AS total, COALESCE(SUM(p.amount),0) AS paid FROM delivery_notes dn LEFT JOIN payments p ON p.delivery_note_id = dn.id WHERE dn.customer_id=?');
            $dueStmt->execute([$pid]);
            $dueSummary = $dueStmt->fetch();
        }
        Helpers::view('search/customer', compact('phone','name','customer','parcels','notes','dueSummary','matches'));
        break;

    case 'reports':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        if (!Auth::canViewReports()) { http_response_code(403); echo 'Forbidden'; break; }
        if (!empty($_GET['rep_action']) || !empty($_POST['rep_action'])) {
            ReportsApi::dispatch(Database::pdo());
            break;
        }
        $branchesAll = BranchRepository::forFilters(Database::pdo());
        Helpers::view('reports/index', ['branchesAll' => $branchesAll]);
        break;

    case 'quick_add_customer':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method Not Allowed'; break; }
        if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error'=>'Invalid CSRF. Please refresh the page and try again.']);
            return;
        }
        $pdo = Database::pdo();
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $delivery_location = trim($_POST['delivery_location'] ?? '');
        $customer_type_raw = $_POST['customer_type'] ?? null;
        $customer_type = null;
        if (is_string($customer_type_raw)) {
            $ct = strtolower(trim($customer_type_raw));
            if ($ct === 'corporate' || $ct === 'regular') {
                $customer_type = $ct;
            }
        }
        $wantsJson = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
                     || (strpos(($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false)
                     || (($_POST['ajax'] ?? '') === '1');
        if ($name === '') {
            if ($wantsJson) { header('Content-Type: application/json'); echo json_encode(['error'=>'Name is required.']); return; }
            Helpers::redirect('index.php?page=parcels&action=new');
            break;
        }
        $phoneDb = ($phone === '') ? null : $phone;
        // Check if exists (by phone if provided)
        $cid = 0;
        if ($phone !== '') {
            $stmt = $pdo->prepare('SELECT id FROM customers WHERE phone=? LIMIT 1');
            $stmt->execute([$phone]);
            $existing = $stmt->fetch();
            if ($existing) { $cid = (int)$existing['id']; }
        }
        if ($cid === 0) {
            try { $pdo->exec("ALTER TABLE customers MODIFY phone VARCHAR(20) NULL"); } catch (Throwable $e) { /* ignore */ }
            try {
                $pdo->beginTransaction();
                $ins = $pdo->prepare('INSERT INTO customers (name, phone, email, address, delivery_location, customer_type) VALUES (?,?,?,?,?,?)');
                $ins->execute([$name, $phoneDb, ($email !== '' ? $email : null), $address, $delivery_location, $customer_type]);
                $cid = (int)$pdo->lastInsertId();
                if ($cid > 0) {
                    CashbookRepository::ensureCustomerAccount($pdo, $cid, $name);
                    _customer_ledger_ensure($pdo, $cid, $name);
                }
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($wantsJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'Failed to save customer. ' . $e->getMessage()]);
                    return;
                }
                throw $e;
            }
        }
        if ($wantsJson) {
            $cashbookAccountId = null;
            if ($cid > 0) {
                $stCb = $pdo->prepare('SELECT id FROM cashbook_accounts WHERE customer_id = ? LIMIT 1');
                $stCb->execute([$cid]);
                $rCb = $stCb->fetchColumn();
                if ($rCb) {
                    $cashbookAccountId = (int)$rCb;
                }
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'id' => $cid,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'delivery_location' => $delivery_location,
                'cashbook_account_id' => $cashbookAccountId,
            ]);
            return;
        }
        Helpers::redirect('index.php?page=parcels&action=new&customer_id=' . $cid);
        break;

    case 'accounting':
        if (!Auth::hasAnyRole(['admin', 'accountant'])) {
            http_response_code(403);
            echo 'Forbidden';
            break;
        }
        $accAction = $_GET['acc_action'] ?? $_POST['acc_action'] ?? '';
        if ($accAction !== '') {
            AccountingController::dispatch(Database::pdo());
            break;
        }
        $accPageAction = (string) ($_GET['action'] ?? 'dashboard');
        if ($accPageAction === '') {
            $accPageAction = 'dashboard';
        }
        $voucherType = strtoupper((string) ($_GET['voucher_type'] ?? 'PAYMENT'));
        $paymentMode = strtoupper((string) ($_GET['payment_mode'] ?? 'CASH'));
        AccountingModule::renderPage($accPageAction, compact('voucherType', 'paymentMode'));
        break;

    case 'api_accounting':
        if (!Auth::hasAnyRole(['admin', 'accountant'])) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Forbidden']);
            break;
        }
        AccountingController::dispatch(Database::pdo());
        break;

    default:
        http_response_code(404);
        echo 'Page not found';
        break;
}
