<?php
require_once __DIR__ . '/../app/bootstrap.php';

$page = $_GET['page'] ?? 'dashboard';
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
        $success = ($_GET['reset'] ?? '') === '1' ? 'All data has been reset. Run the seed script to create an admin user, then log in.' : null;
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
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $confirm = trim($_POST['confirm_reset'] ?? '');
            if ($confirm !== 'DELETE') {
                $files = [];
                foreach (glob($backupDir . DIRECTORY_SEPARATOR . 'tms_backup_*.sql') as $f) {
                    $files[] = ['name' => basename($f), 'size' => filesize($f), 'mtime' => filemtime($f)];
                }
                usort($files, fn($a,$b)=>$b['mtime']<=>$a['mtime']);
                $error = 'Type DELETE (all caps) to confirm reset.';
                Helpers::view('admin/backup', compact('files','error'));
                break;
            }
            $pdo = Database::pdo();
            $result = DataReset::deleteAllRecords($pdo);
            if ($result['success']) {
                Auth::logout();
                Helpers::redirect('index.php?page=login&reset=1');
                return;
            }
            $files = [];
            foreach (glob($backupDir . DIRECTORY_SEPARATOR . 'tms_backup_*.sql') as $f) {
                $files[] = ['name' => basename($f), 'size' => filesize($f), 'mtime' => filemtime($f)];
            }
            usort($files, fn($a,$b)=>$b['mtime']<=>$a['mtime']);
            $error = 'Reset failed: ' . implode('; ', $result['errors']);
            Helpers::view('admin/backup', compact('files','error'));
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
        Helpers::view('admin/backup', compact('files'));
        break;
    case 'dashboard':
        $pdo = Database::pdo();
        $user = Auth::user();
        $branchId = (int)($user['branch_id'] ?? 0);
        $today = date('Y-m-d');
        $isMain = Auth::isMainBranch();
        // Filters
        $df = $_GET['df'] ?? $today; // date from
        $dt = $_GET['dt'] ?? $today; // date to
        $fb = (int)($_GET['fb'] ?? 0); // from_branch_id
        $tb = (int)($_GET['tb'] ?? 0); // to_branch_id
        $cust = (int)($_GET['cust'] ?? 0); // customer_id

        // Pending parcels for selected/to branch (ignores date)
        $pendingBranchId = $tb > 0 ? $tb : $branchId;
        $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM parcels WHERE to_branch_id = ? AND status = 'pending'");
        $stmt->execute([$pendingBranchId]);
        $row = $stmt->fetch();
        $pendingParcels = (int)($row['c'] ?? 0);

        // Total payment due for selected branch (or current branch)
        $dueSql = "SELECT COALESCE(SUM((dn.total_amount - COALESCE(dn.discount,0)) - COALESCE(paid.total_paid,0)),0) AS total_due
                   FROM delivery_notes dn
                   LEFT JOIN (
                     SELECT delivery_note_id, SUM(amount) AS total_paid FROM payments GROUP BY delivery_note_id
                   ) paid ON paid.delivery_note_id = dn.id
                   WHERE dn.branch_id = ?";
        $stmt = $pdo->prepare($dueSql);
        $stmt->execute([$pendingBranchId]);
        $dueRow = $stmt->fetch();
        $totalDue = (float)($dueRow['total_due'] ?? 0);

        // Parcels list for range and filters with vehicle no
        $tpSql = "SELECT p.id, p.tracking_number, p.vehicle_no, p.status, p.created_at,
                         c.name AS customer_name, bt.name AS to_branch
                  FROM parcels p
                  LEFT JOIN customers c ON c.id = p.customer_id
                  LEFT JOIN branches bt ON bt.id = p.to_branch_id
                  WHERE DATE(p.created_at) BETWEEN ? AND ?";
        $tpParams = [$df, $dt];
        if ($tb > 0) { $tpSql .= ' AND p.to_branch_id = ?'; $tpParams[] = $tb; }
        else { $tpSql .= ' AND p.to_branch_id = ?'; $tpParams[] = $branchId; }
        if ($fb > 0) { $tpSql .= ' AND p.from_branch_id = ?'; $tpParams[] = $fb; }
        if ($cust > 0) { $tpSql .= ' AND p.customer_id = ?'; $tpParams[] = $cust; }
        $tpSql .= ' ORDER BY p.created_at DESC, p.id DESC LIMIT 50';
        $tpStmt = $pdo->prepare($tpSql);
        $tpStmt->execute($tpParams);
        $todayParcels = $tpStmt->fetchAll();

        // Collections (payments) for range and selected branch
        $colSql = "SELECT COALESCE(SUM(p.amount),0) AS s
                   FROM payments p
                   LEFT JOIN delivery_notes dn ON dn.id = p.delivery_note_id
                   WHERE dn.branch_id = ? AND DATE(p.paid_at) BETWEEN ? AND ?";
        $colStmt = $pdo->prepare($colSql);
        $colStmt->execute([$pendingBranchId, $df, $dt]);
        $collectionsToday = (float)($colStmt->fetch()['s'] ?? 0);

        // Expenses for this branch within range
        $expStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS s FROM expenses WHERE branch_id = ? AND expense_date BETWEEN ? AND ?');
        $expStmt->execute([$pendingBranchId, $df, $dt]);
        $expensesToday = (float)($expStmt->fetch()['s'] ?? 0);

        // Recent payments list (range + branch)
        $rpSql = "SELECT p.id, p.amount, p.paid_at, c.name AS customer_name, c.phone AS customer_phone
                  FROM payments p
                  LEFT JOIN delivery_notes dn ON dn.id = p.delivery_note_id
                  LEFT JOIN customers c ON c.id = dn.customer_id
                  WHERE dn.branch_id = ? AND DATE(p.paid_at) BETWEEN ? AND ?
                  ORDER BY p.paid_at DESC, p.id DESC LIMIT 10";
        $rp = $pdo->prepare($rpSql);
        $rp->execute([$pendingBranchId, $df, $dt]);
        $recentPayments = $rp->fetchAll();

        // Per-branch aggregates and status stats across all branches
        $branchesAll = $pdo->query('SELECT id, name, code FROM branches ORDER BY name')->fetchAll();
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

        Helpers::view('dashboard', compact('pendingParcels','totalDue','todayParcels','today','collectionsToday','expensesToday','recentPayments','isMain','branchesAll','pendingByBranch','dueByBranch','todayParcelsByBranch','collectionsTodayByBranch','expensesTodayByBranch','df','dt','fb','tb','cust','customersAll','statusStats'));
        break;

    case 'accounts':
        if (!Auth::hasAnyRole(['admin','accountant'])) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        $branchId = (int)(Auth::user()['branch_id'] ?? 0);
        // Placeholder: summary totals
        $totalPayments = (float)($pdo->query("SELECT COALESCE(SUM(amount),0) AS s FROM payments WHERE DATE(paid_at) BETWEEN '".$from."' AND '".$to."'")->fetch()['s'] ?? 0);
        $totalExpenses = (float)($pdo->prepare('SELECT COALESCE(SUM(amount),0) AS s FROM expenses WHERE expense_date BETWEEN ? AND ?')->execute([$from,$to]) || 0);
        // Use safer separate statements
        $expStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS s FROM expenses WHERE expense_date BETWEEN ? AND ?');
        $expStmt->execute([$from,$to]);
        $totalExpenses = (float)($expStmt->fetch()['s'] ?? 0);
        Helpers::view('accounts/index', compact('from','to','totalPayments','totalExpenses','branchId'));
        break;

    case 'daybook':
        if (!Auth::hasAnyRole(['admin','accountant'])) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $date = $_GET['date'] ?? date('Y-m-d');
        // Collect payments and expenses for the day
        $pb = $pdo->prepare("SELECT p.id, p.amount, p.paid_at, 'Payment' AS type FROM payments p WHERE DATE(p.paid_at)=? ORDER BY p.paid_at, p.id");
        $pb->execute([$date]);
        $payments = $pb->fetchAll();
        $eb = $pdo->prepare("SELECT e.id, e.amount, e.expense_date AS paid_at, 'Expense' AS type FROM expenses e WHERE e.expense_date=? ORDER BY e.expense_date, e.id");
        $eb->execute([$date]);
        $expenses = $eb->fetchAll();
        Helpers::view('daybook/index', compact('date','payments','expenses'));
        break;

    case 'ledger':
        if (!Auth::hasAnyRole(['admin','accountant'])) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        $account = $_GET['account'] ?? 'customers'; // placeholder selector
        // For now, show payments vs expenses in range
        // Daily series
        $pStmt = $pdo->prepare("SELECT DATE(paid_at) AS d, SUM(amount) AS s FROM payments WHERE DATE(paid_at) BETWEEN ? AND ? GROUP BY DATE(paid_at) ORDER BY d");
        $pStmt->execute([$from,$to]);
        $pSeries = $pStmt->fetchAll();
        $eStmt = $pdo->prepare("SELECT expense_date AS d, SUM(amount) AS s FROM expenses WHERE expense_date BETWEEN ? AND ? GROUP BY expense_date ORDER BY d");
        $eStmt->execute([$from,$to]);
        $eSeries = $eStmt->fetchAll();

        // Opening balance before From date: collections - expenses
        $stmtOpP = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS s FROM payments WHERE DATE(paid_at) < ?");
        $stmtOpP->execute([$from]);
        $openingPayments = (float)($stmtOpP->fetch()['s'] ?? 0);
        $stmtOpE = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS s FROM expenses WHERE expense_date < ?");
        $stmtOpE->execute([$from]);
        $openingExpenses = (float)($stmtOpE->fetch()['s'] ?? 0);
        $openingBalance = $openingPayments - $openingExpenses;

        // Period totals and net
        $stmtTP = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS s FROM payments WHERE DATE(paid_at) BETWEEN ? AND ?");
        $stmtTP->execute([$from,$to]);
        $totalPayments = (float)($stmtTP->fetch()['s'] ?? 0);
        $stmtTE = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS s FROM expenses WHERE expense_date BETWEEN ? AND ?");
        $stmtTE->execute([$from,$to]);
        $totalExpenses = (float)($stmtTE->fetch()['s'] ?? 0);
        $netMovement = $totalPayments - $totalExpenses;
        $closingBalance = $openingBalance + $netMovement;

        Helpers::view('ledger/index', compact('from','to','account','pSeries','eSeries','openingBalance','netMovement','closingBalance','totalPayments','totalExpenses'));
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
        $config = Helpers::config();
        $company = $config['company'] ?? [];
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings_section'])) {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $section = $_POST['settings_section'] ?? '';
            if ($section === 'company') {
                $name = trim($_POST['company_name'] ?? '');
                $regNo = trim($_POST['reg_no'] ?? '');
                $logoUrl = trim($_POST['logo_url'] ?? '');
                $footerNote = trim($_POST['footer_note'] ?? '');
                $routePart1 = trim($_POST['route_1'] ?? '');
                $routePart2 = trim($_POST['route_2'] ?? '');
                $routePart3 = trim($_POST['route_3'] ?? '');
                $googleMapsKey = trim($_POST['google_maps_api_key'] ?? '');

                // Logo file upload
                if (!empty($_FILES['logo_file']['name']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../public/uploads';
                    if (!is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0755, true);
                    }
                    $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
                        $dest = $uploadDir . '/logo.' . $ext;
                        if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $dest)) {
                            $logoUrl = 'uploads/logo.' . $ext;
                        }
                    }
                }

                $branchName = $_POST['branch_name'] ?? [];
                $branchAddressTa = $_POST['branch_address_ta'] ?? [];
                $branchAddressEn = $_POST['branch_address_en'] ?? [];
                $branchPhones = $_POST['branch_phones'] ?? [];
                $branchesData = [];
                for ($i = 0; $i < 3; $i++) {
                    $branchesData[] = [
                        'name' => trim($branchName[$i] ?? ''),
                        'address_ta' => trim($branchAddressTa[$i] ?? ''),
                        'address_en' => trim($branchAddressEn[$i] ?? ''),
                        'phones' => trim($branchPhones[$i] ?? ''),
                    ];
                }

                $routeParts = array_filter([$routePart1, $routePart2, $routePart3]);
                if (empty($routeParts)) {
                    $routeParts = ['கொழும்பு', 'கிளிநொச்சி', 'முல்லைத்தீவு'];
                }

                $companyOverride = [
                    'name' => $name ?: ($company['name'] ?? 'TS Transport'),
                    'reg_no' => $regNo,
                    'logo_url' => $logoUrl,
                    'route_tamil_parts' => array_values($routeParts),
                    'branches' => $branchesData,
                    'footer_note' => $footerNote,
                ];
                $toSave = [
                    'company' => array_merge($company, $companyOverride),
                    'google_maps_api_key' => $googleMapsKey,
                ];
                $jsonPath = __DIR__ . '/../config/company.json';
                $written = @file_put_contents($jsonPath, json_encode($toSave, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                if ($written !== false) {
                    $success = 'Company settings saved.';
                    $company = array_merge($company, $companyOverride);
                    $config['google_maps_api_key'] = $googleMapsKey;
                } else {
                    $error = 'Could not save settings. Check that config/company.json is writable.';
                }
            }
        }

        Helpers::view('settings/index', compact('company', 'config', 'error', 'success'));
        break;

    case 'branches':
        // Admin only
        if (!Auth::hasRole('admin')) { http_response_code(403); echo 'Forbidden'; break; }

        $action = $_GET['action'] ?? 'index';
        $pdo = Database::pdo();

        

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '');
            $is_main = isset($_POST['is_main']) ? 1 : 0;
            if ($name === '' || $code === '') { $error = 'Name and Code are required.'; Helpers::view('branches/form', compact('error')); break; }
            $wantsJson = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
                         || (strpos(($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false)
                         || (($_POST['ajax'] ?? '') === '1');

            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE branches SET name=?, code=?, is_main=? WHERE id=?');
                $stmt->execute([$name, $code, $is_main, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO branches (name, code, is_main) VALUES (?,?,?)');
                $stmt->execute([$name, $code, $is_main]);
                $id = (int)$pdo->lastInsertId();
            }
            if ($is_main) {
                // Ensure only one main branch
                $stmt = $pdo->prepare('UPDATE branches SET is_main=0 WHERE id<>?');
                $stmt->execute([$id]);
                // Mark all users of that branch as is_main_branch=1, others 0
                $pdo->prepare('UPDATE users SET is_main_branch = CASE WHEN branch_id = ? THEN 1 ELSE 0 END')->execute([$id]);
            }
            if ($wantsJson) {
                header('Content-Type: application/json');
                echo json_encode(['id'=>$id, 'name'=>$name, 'code'=>$code, 'is_main'=>$is_main]);
                return;
            }
            Helpers::redirect('index.php?page=branches');
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
                            $subject = 'Parcel Order #' . (int)$id . ' — ' . number_format($priceNow,2);
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
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                // Prevent deleting the only main branch
                $isMain = $pdo->prepare('SELECT is_main FROM branches WHERE id=?');
                $isMain->execute([$id]);
                $row = $isMain->fetch();
                if ($row && (int)$row['is_main'] === 1) {
                    $countMain = $pdo->query('SELECT COUNT(*) AS c FROM branches WHERE is_main=1')->fetch();
                    if ((int)$countMain['c'] <= 1) {
                        $error = 'Cannot delete the only Main Branch.';
                        $branches = $pdo->query('SELECT * FROM branches ORDER BY is_main DESC, name')->fetchAll();
                        Helpers::view('branches/index', compact('branches','error'));
                        break;
                    }
                }
                try {
                    $pdo->prepare('DELETE FROM branches WHERE id=?')->execute([$id]);
                } catch (PDOException $e) {
                    // Integrity constraint (e.g., referenced by expenses, users, parcels, etc.)
                    if ($e->getCode() === '23000') {
                        $error = 'Cannot delete this branch because it is used by other records (e.g., expenses, users, parcels). Delete or reassign those records first.';
                        $branches = $pdo->query('SELECT * FROM branches ORDER BY is_main DESC, name')->fetchAll();
                        Helpers::view('branches/index', compact('branches','error'));
                        break;
                    }
                    throw $e;
                }
            }
            Helpers::redirect('index.php?page=branches');
            break;
        }

        if ($action === 'new') {
            $branch = ['id'=>0,'name'=>'','code'=>'','is_main'=>0];
            Helpers::view('branches/form', compact('branch'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM branches WHERE id=?');
            $stmt->execute([$id]);
            $branch = $stmt->fetch();
            if (!$branch) { http_response_code(404); echo 'Not found'; break; }
            Helpers::view('branches/form', compact('branch'));
            break;
        }

        // index
        $branches = $pdo->query('SELECT * FROM branches ORDER BY is_main DESC, name')->fetchAll();
        Helpers::view('branches/index', compact('branches'));
        break;

    case 'users':
        // Admin only
        if (!Auth::hasRole('admin')) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';
        $pdo = Database::pdo();
        $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            // Normalize username: trim only (allow internal spaces)
            $username = trim($_POST['username'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $role = $_POST['role'] ?? '';
            $branch_id = (int)($_POST['branch_id'] ?? 0);
            $active = isset($_POST['active']) ? 1 : 0;
            $password = $_POST['password'] ?? '';
            if ($username === '' || $full_name === '') {
                $error = 'Username and Full Name are required.';
                $userRow = compact('id','username','full_name','role','branch_id','active');
                $rolesDynamic = $pdo->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL AND role<>'' ORDER BY role")->fetchAll();
                Helpers::view('users/form', compact('userRow','branchesAll','error','rolesDynamic'));
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
                $userRow = compact('id','username','full_name','role','branch_id','active');
                $rolesDynamic = $pdo->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL AND role<>'' ORDER BY role")->fetchAll();
                Helpers::view('users/form', compact('userRow','branchesAll','error','suggestedUsername','rolesDynamic'));
                break;
            }
            // If your DB schema requires NOT NULL on users.role, fallback to 'staff' when empty
            $roleParam = ($role === '') ? 'staff' : $role;
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
                    if ($password === '') { $error = 'Password is required for new user.'; $userRow = compact('id','username','full_name','role','branch_id','active'); $pdo->rollBack(); Helpers::view('users/form', compact('userRow','branchesAll','error')); break; }
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare('INSERT INTO users (username, full_name, role, branch_id, active, password_hash) VALUES (?,?,?,?,?,?)');
                    $stmt->execute([$username,$full_name,$roleParam,($branch_id>0?$branch_id:null),$active,$hash]);
                }
                $pdo->commit();
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
                $userRow = compact('id','username','full_name','role','branch_id','active');
                $rolesDynamic = $pdo->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL AND role<>'' ORDER BY role")->fetchAll();
                Helpers::view('users/form', compact('userRow','branchesAll','error','rolesDynamic'));
                break;
            }
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            }
            Helpers::redirect('index.php?page=users');
            break;
        }

        if ($action === 'new') {
            $userRow = ['id'=>0,'username'=>'','full_name'=>'','role'=>'','branch_id'=>0,'active'=>1];
            $rolesDynamic = $pdo->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL AND role<>'' ORDER BY role")->fetchAll();
            Helpers::view('users/form', compact('userRow','branchesAll','rolesDynamic'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id=?');
            $stmt->execute([$id]);
            $userRow = $stmt->fetch();
            if (!$userRow) { http_response_code(404); echo 'Not found'; break; }
            $rolesDynamic = $pdo->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL AND role<>'' ORDER BY role")->fetchAll();
            Helpers::view('users/form', compact('userRow','branchesAll','rolesDynamic'));
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
        $rolesDynamic = $pdo->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL AND role<>'' ORDER BY role")->fetchAll();
        Helpers::view('users/index', compact('users','branchesAll','rolesDynamic','usernameF','fullNameF','roleF','branchF','activeF'));
        break;

    case 'customers':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';
        $pdo = Database::pdo();

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
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
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
            if ($name === '') { $error = 'Name is required.'; $customer = compact('id','name','phone','email','address','delivery_location','place_id','lat','lng','customer_type'); Helpers::view('customers/form', compact('customer','error')); break; }
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE customers SET name=?, phone=?, email=?, address=?, delivery_location=?, place_id=?, lat=?, lng=?, customer_type=? WHERE id=?');
                    $stmt->execute([$name,$phoneDb,($email!==''?$email:null),$address,$delivery_location,($place_id!==''?$place_id:null),$lat,$lng,$customer_type,$id]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO customers (name, phone, email, address, delivery_location, place_id, lat, lng, customer_type) VALUES (?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([$name,$phoneDb,($email!==''?$email:null),$address,$delivery_location,($place_id!==''?$place_id:null),$lat,$lng,$customer_type]);
                    $id = (int)$pdo->lastInsertId();
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
                $error = $msg; $customer = compact('id','name','phone','email','address','delivery_location','place_id','lat','lng','customer_type'); Helpers::view('customers/form', compact('customer','error')); break;
            }
            if ($wantsJson) { header('Content-Type: application/json'); echo json_encode(['id'=>$id,'name'=>$name,'phone'=>$phone,'email'=>$email,'delivery_location'=>$delivery_location]); return; }
            Helpers::redirect('index.php?page=customers');
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                // Guard against FK violations: check references before delete
                $refParcels = 0; $refDN = 0;
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

                if ($refParcels > 0 || $refDN > 0) {
                    http_response_code(400);
                    $totalRefs = $refParcels + $refDN;
                    $msg = 'Cannot delete this customer because it is referenced by existing records: '
                         . $refParcels . ' parcel(s) and ' . $refDN . ' delivery note(s).';
                    echo '<!doctype html><html><head><meta charset="utf-8"><title>Delete Customer</title>'
                       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
                       . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head><body>'
                       . '<div class="container" style="max-width:780px;">'
                       . '<div class="alert alert-danger mt-4" role="alert">' . htmlspecialchars($msg) . ' '
                       . 'Please reassign or remove those records first.</div>'
                       . '<a class="btn btn-outline-secondary" href="' . Helpers::baseUrl('index.php?page=customers') . '">← Back to Customers</a>'
                       . '</div></body></html>';
                    break;
                }

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
                           . '<a class="btn btn-outline-secondary" href="' . Helpers::baseUrl('index.php?page=customers') . '">← Back to Customers</a>'
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
            Helpers::view('customers/form', compact('customer'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM customers WHERE id=?');
            $stmt->execute([$id]);
            $customer = $stmt->fetch();
            if (!$customer) { http_response_code(404); echo 'Not found'; break; }
            Helpers::view('customers/form', compact('customer'));
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
        Helpers::view('customers/index', compact('customers','q','name','phone','email','address','delivery_location','type'));
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

        // Fetch branches for forms
        $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            // Disallow placeholder-like names such as 'none' or '-- none --'
            $nameNorm = strtolower(preg_replace('/[^a-z0-9]+/i','', $name));
            $phone = trim($_POST['phone'] ?? '');
            $branch_id = (int)($_POST['branch_id'] ?? 0);
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
            Helpers::redirect('index.php?page=suppliers');
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare('DELETE FROM suppliers WHERE id=?')->execute([$id]);
            }
            Helpers::redirect('index.php?page=suppliers');
            break;
        }

        if ($action === 'new') {
            $supplier = ['id'=>0,'name'=>'','phone'=>'','branch_id'=>0,'supplier_code'=>''];
            Helpers::view('suppliers/form', compact('supplier','branchesAll'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id=?');
            $stmt->execute([$id]);
            $supplier = $stmt->fetch();
            if (!$supplier) { http_response_code(404); echo 'Not found'; break; }
            Helpers::view('suppliers/form', compact('supplier','branchesAll'));
            break;
        }

        // index with filters (name/phone/code/branch_id) and fallback to single 'q'
        $name = trim($_GET['name'] ?? '');
        $phone = trim($_GET['phone'] ?? '');
        $code = trim($_GET['code'] ?? '');
        $branch_id = (int)($_GET['branch_id'] ?? 0);
        $q = trim($_GET['q'] ?? '');

        $hasFilters = ($name !== '' || $phone !== '' || $code !== '' || $branch_id > 0);
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
        Helpers::view('suppliers/index', compact('suppliers','q','name','phone','code','branch_id','branchesAll'));
        break;

    case 'parcels':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        if (!Auth::canCreateParcels()) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';
        $pdo = Database::pdo();
        $user = Auth::user();
        $userBranchCode = (string)($user['branch_code'] ?? '');
        $userBranchName = (string)($user['branch_name'] ?? '');
        $isKilinochchi = (strcasecmp($userBranchCode, 'KIL') === 0) || (strcasecmp($userBranchName, 'Kilinochchi') === 0);
        $isColombo = (strcasecmp($userBranchCode, 'COL') === 0) || (strcasecmp($userBranchName, 'Colombo') === 0);
        $isMullaitivu = (strcasecmp($userBranchCode, 'MLT') === 0)
                         || (stripos($userBranchName, 'Mullaitivu') !== false)
                         || (stripos($userBranchName, 'Mullaithivu') !== false)
                         || (stripos($userBranchName, 'Mullaithiv') !== false);
        // Keep legacy isMain flag aligned to Colombo for pricing behavior
        $isMain = $isColombo;
        // Policy: Item amount entry should NOT work in Colombo or Mullaitivu; enable only at Kilinochchi if ever needed
        $canEnterItemAmounts = $isKilinochchi;
        // Allow all branches with parcel role to create parcels
        $canCreateParcels = true;

        // data for forms
        $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
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
            $defaultSubject = 'Parcel Order #' . (int)$id . ' — ' . number_format($priceNow,2);
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
            
            // Prevent double submission using idempotency token
            $idempotencyKey = $_POST['idempotency_key'] ?? '';
            if ($idempotencyKey === '') {
                $idempotencyKey = bin2hex(random_bytes(16));
            }
            $idempotencySessionKey = 'parcel_save_' . md5($idempotencyKey);
            if (isset($_SESSION[$idempotencySessionKey])) {
                // This exact submission was already processed, redirect to prevent duplicate
                $savedId = (int)$_SESSION[$idempotencySessionKey];
                Helpers::redirect('index.php?page=parcels&action=new&saved_id=' . $savedId);
                break;
            }
            
            $id = (int)($_POST['id'] ?? 0);
            $customer_id = (int)($_POST['customer_id'] ?? 0);
            $supplier_id = (int)($_POST['supplier_id'] ?? 0);
            if ($supplier_id <= 0) { $supplier_id = null; }
            $from_branch_id = (int)($_POST['from_branch_id'] ?? 0);
            $to_branch_id = (int)($_POST['to_branch_id'] ?? 0);
            // If From Branch not provided, default to current user's branch
            if ($from_branch_id <= 0) {
                $from_branch_id = (int)($user['branch_id'] ?? 0);
            }
            $weight = (float)($_POST['weight'] ?? 0);
            $status = $_POST['status'] ?? 'pending';
            // Serial/tracking number handling
            $tracking_number_raw = trim($_POST['tracking_number'] ?? '');
            $tracking_number = $tracking_number_raw; // keep as-is for update/insert
            $vehicle_no = trim($_POST['vehicle_no'] ?? '');
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
                if ($existing) { $isBilled = ($existing['price'] !== null && (float)$existing['price'] > 0); }
                // Status-only update: parcel is In Transit and form submitted with status_only_edit
                if ($existing && (string)($existing['status'] ?? '') === 'in_transit' && isset($_POST['status_only_edit']) && (int)$_POST['status_only_edit'] === 1) {
                    $status = $_POST['status'] ?? $existing['status'];
                    $allowedStatus = ['pending', 'in_transit', 'delivered'];
                    if (!in_array($status, $allowedStatus, true)) { $status = 'in_transit'; }
                    $pdo->prepare('UPDATE parcels SET status=? WHERE id=?')->execute([$status, $id]);
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
                    Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','error') + ['policy'=>['priceOnly'=>false,'lockAll'=>true,'canEnterItemAmounts'=>$canEnterItemAmounts]]);
                    break;
                }
                if ($isKilinochchi) {
                    $priceRaw = trim($_POST['price'] ?? '');
                    $discountRaw = trim($_POST['discount'] ?? '');
                    $p = ($priceRaw === '') ? null : (float)$priceRaw;
                    if ($p === null) {
                        // Fallback: compute from posted items
                        $sum = 0.0;
                        if (is_array($items)) {
                            foreach ($items as $it) {
                                $q = (float)($it['qty'] ?? 0);
                                $rs = (float)($it['rs'] ?? 0);
                                $cts = (float)($it['cts'] ?? 0);
                                $r = $rs + ($cts/100.0);
                                if ($q > 0 && $r > 0) { $sum += $q * $r; }
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
                        $error = 'Please enter a valid price (or RS/CTS) for Kilinochchi.';
                        // Load vehicles for form
                        try { $vehiclesAll = $pdo->query('SELECT id, reg_number AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); }
                        catch (Throwable $e) { $vehiclesAll = []; }
                        $parcel = $existing ?: ['id'=>$id,'customer_id'=>$customer_id,'from_branch_id'=>$from_branch_id,'to_branch_id'=>$to_branch_id,'weight'=>$weight,'status'=>$status,'tracking_number'=>$tracking_number,'vehicle_no'=>$vehicle_no];
                        $policy = ['priceOnly'=>true, 'lockAll'=>false, 'canEnterItemAmounts'=>$canEnterItemAmounts];
                        Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','error','items','vehiclesAll','policy'));
                        break;
                    }
                } else {
                    // Not Kilinochchi: keep existing price intact
                    $price = $existing ? ($existing['price'] ?? null) : null;
                }
            }

            if (!$priceOnlyEdit && ($customer_id <= 0 || $from_branch_id <= 0 || $to_branch_id <= 0)) {
                $error = 'Customer, From Branch and To Branch are required.';
                $parcel = compact('id','customer_id','supplier_id','from_branch_id','to_branch_id','weight','status','tracking_number','vehicle_no');
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
                $policy = ['priceOnly'=>$isKilinochchi, 'lockAll'=>false, 'canEnterItemAmounts'=>$canEnterItemAmounts];
                Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','error','items','vehiclesAll','policy'));
                break;
            }

            $allowedStatus = ['pending','in_transit','delivered'];
            if (!in_array($status, $allowedStatus, true)) { $status = 'pending'; }

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
                            $sumItems = $pdo->prepare('SELECT COALESCE(SUM(COALESCE(qty,0)*COALESCE(rate,0)),0) AS s FROM parcel_items WHERE parcel_id=?');
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
                    $stmt = $pdo->prepare("UPDATE parcels SET customer_id=?, supplier_id=?, from_branch_id=?, to_branch_id=?, weight=?, price=?, status=?, tracking_number = NULLIF(?, ''), vehicle_no=? WHERE id=?");
                    $stmt->execute([$customer_id,$supplier_id,$from_branch_id,$to_branch_id,$weight,$price,$status,$tracking_number,$vehicle_no,$id]);
                    // Replace items allowed for non-Kilinochchi
                    $pdo->prepare('DELETE FROM parcel_items WHERE parcel_id=?')->execute([$id]);
                    if (is_array($items)) {
                        $insItem = $pdo->prepare('INSERT INTO parcel_items (parcel_id, qty, description, rate) VALUES (?,?,?,?)');
                        foreach ($items as $it) {
                            $desc = trim($it['description'] ?? '');
                            $qty = (float)($it['qty'] ?? 0);
                            $rs = (float)($it['rs'] ?? 0);
                            $cts = (float)($it['cts'] ?? 0);
                            $rate = $canEnterItemAmounts ? ($rs + ($cts/100.0)) : null;
                            if ($desc !== '' || $qty > 0) {
                                $insItem->execute([$id, $qty, $desc, $rate]);
                            }
                        }
                    }
                    // Sync any linked delivery note amounts to the (possibly) updated computed amount
                    try {
                        $newAmount = (float)($price ?? 0);
                        if ($newAmount <= 0) {
                            $sumItems = $pdo->prepare('SELECT COALESCE(SUM(COALESCE(qty,0)*COALESCE(rate,0)),0) AS s FROM parcel_items WHERE parcel_id=?');
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
                    // Duplicate detected - redirect to prevent double entry
                    Helpers::redirect('index.php?page=parcels&action=new&duplicate=' . (int)$duplicate['id'] . '&customer_id=' . urlencode((string)$customer_id) . '&vehicle_no=' . urlencode((string)$vehicle_no) . '&from_branch_id=' . urlencode((string)$from_branch_id) . '&to_branch_id=' . urlencode((string)$to_branch_id));
                    break;
                }
                
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
                                $rs = (float)($it['rs'] ?? 0);
                                $cts = (float)($it['cts'] ?? 0);
                                $r = $rs + ($cts/100.0);
                                if ($q > 0 && $r > 0) { $sum += $q * $r; }
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
                        $stmt = $pdo->prepare("INSERT INTO parcels (customer_id, supplier_id, from_branch_id, to_branch_id, weight, price, status, tracking_number, vehicle_no, created_at) VALUES (?,?,?,?,?,?,?, NULLIF(?, ''), ?, ?)");
                        $stmt->execute([$customer_id,$supplier_id,$from_branch_id,$to_branch_id,$weight,$price,$status,$tracking_number,$vehicle_no,$createdAtOverride]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO parcels (customer_id, supplier_id, from_branch_id, to_branch_id, weight, price, status, tracking_number, vehicle_no) VALUES (?,?,?,?,?,?,?, NULLIF(?, ''), ?)");
                        $stmt->execute([$customer_id,$supplier_id,$from_branch_id,$to_branch_id,$weight,$price,$status,$tracking_number,$vehicle_no]);
                    }
                } else {
                    // Other branches: do not set price at create
                    $createdAtOverride = trim($_POST['created_date'] ?? '');
                    if ($createdAtOverride) {
                        $stmt = $pdo->prepare("INSERT INTO parcels (customer_id, supplier_id, from_branch_id, to_branch_id, weight, status, tracking_number, vehicle_no, created_at) VALUES (?,?,?,?,?,?,NULLIF(?, ''),?, ?)");
                        $stmt->execute([$customer_id,$supplier_id,$from_branch_id,$to_branch_id,$weight,$status,$tracking_number,$vehicle_no,$createdAtOverride]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO parcels (customer_id, supplier_id, from_branch_id, to_branch_id, weight, status, tracking_number, vehicle_no) VALUES (?,?,?,?,?,?,NULLIF(?, ''),?)");
                        $stmt->execute([$customer_id,$supplier_id,$from_branch_id,$to_branch_id,$weight,$status,$tracking_number,$vehicle_no]);
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
                        $insItem = $pdo->prepare('INSERT INTO parcel_items (parcel_id, qty, description, rate) VALUES (?,?,?,?)');
                        foreach ($items as $it) {
                            $desc = trim($it['description'] ?? '');
                            $qty = (float)($it['qty'] ?? 0);
                            $rs = (float)($it['rs'] ?? 0);
                            $cts = (float)($it['cts'] ?? 0);
                            $rate = $canEnterItemAmounts ? ($rs + ($cts/100.0)) : null;
                            if ($desc !== '' || $qty > 0) {
                                $insItem->execute([$id, $qty, $desc, $rate]);
                            }
                        }
                    } catch (Throwable $e) { /* ignore if table missing */ }
                }
            }
            $pdo->commit();
            
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
                    
                    // If this is a delivery to Kilinochchi, assign a return load
                    if ($to_branch_id == 1) { // Kilinochchi is branch ID 1
                        RouteHelper::checkAndAssignReturnLoad($pdo, $id, $from_branch_id, $to_branch_id);
                    }
                } catch (Exception $e) {
                    // Log the error but don't fail the entire operation
                    error_log('Error assigning route/load number: ' . $e->getMessage());
                }
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
                    $subject = 'Parcel Order #' . (int)$id . ' — ' . number_format($priceNow,2);
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
            // After save: stay in create flow with previous selections
            // Prefill customer, branches and vehicle so user can add next parcel quickly
            $q = 'index.php?page=parcels&action=new'
               . '&customer_id=' . urlencode((string)$customer_id)
               . '&vehicle_no=' . urlencode((string)$vehicle_no)
               . '&from_branch_id=' . urlencode((string)$from_branch_id)
               . '&to_branch_id=' . urlencode((string)$to_branch_id);
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
                    // Delete the parcel
                    $pdo->prepare('DELETE FROM parcels WHERE id=?')->execute([$id]);
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
            // Last bill (most recent parcel) for "Open last bill" / "Add more parcel" options
            $lastParcel = null;
            try {
                $lastStmt = $pdo->query('SELECT id, customer_id, vehicle_no, from_branch_id, to_branch_id, created_at FROM parcels ORDER BY created_at DESC, id DESC LIMIT 1');
                $lastParcel = $lastStmt->fetch();
            } catch (Throwable $e) { /* ignore */ }

            // Determine lock/priceOnly flags for UI
            $policy = ['priceOnly'=>false,'lockAll'=>false,'canEnterItemAmounts'=>$canEnterItemAmounts];
            Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','items','vehiclesAll','policy','lastParcel'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM parcels WHERE id=?');
            $stmt->execute([$id]);
            $parcel = $stmt->fetch();
            if (!$parcel) { http_response_code(404); echo 'Not found'; break; }
            $itStmt = $pdo->prepare('SELECT * FROM parcel_items WHERE parcel_id=? ORDER BY id');
            $itStmt->execute([$id]);
            $items = $itStmt->fetchAll();
            // Load vehicles list
            try {
                $vehiclesAll = $pdo->query('SELECT id, vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll();
            } catch (Throwable $e) {
                try { $vehiclesAll = $pdo->query('SELECT id, plate_no AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); } catch (Throwable $e2) { $vehiclesAll = []; }
            }
            // When parcel is In Transit: only status change allowed (no other edit options)
            $policy = ['priceOnly'=>$isKilinochchi, 'lockAll'=>false, 'canEnterItemAmounts'=>$canEnterItemAmounts, 'statusOnlyEdit'=>false];
            if ((string)($parcel['status'] ?? '') === 'in_transit') {
                $policy['lockAll'] = true;
                $policy['statusOnlyEdit'] = true;
            }
            Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','isMain','items','vehiclesAll','policy'));
            break;
        }

        // index with search filters
        $q = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
        $vehicle_no = trim($_GET['vehicle_no'] ?? '');
        $customer_filter_id = (int)($_GET['customer_id'] ?? 0);
        
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
        $to_branch_filter_id = (int)($_GET['to_branch_id'] ?? 0);
        $where = [];
        $params = [];
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
        if ($to_branch_filter_id > 0) {
            $where[] = 'p.to_branch_id = ?';
            $params[] = $to_branch_filter_id;
        }
        if (in_array($status, ['pending','in_transit','delivered'], true)) {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        if ($from !== '' && $to !== '') {
            $where[] = 'DATE(p.created_at) BETWEEN ? AND ?';
            array_push($params, $from, $to);
        }
        // Build list query, optionally joining email status if table exists
        $hasEmailLog = false;
        try { $pdo->query('SELECT 1 FROM parcel_emails LIMIT 1'); $hasEmailLog = true; } catch (Throwable $e) { $hasEmailLog = false; }
        if ($hasEmailLog) {
            $sql = 'SELECT p.id, p.customer_id, p.supplier_id, p.from_branch_id, p.to_branch_id, p.weight, p.price, p.status, p.created_at, p.updated_at,
                           COALESCE(NULLIF(p.vehicle_no, ""), dra_to.vehicle_no, dra_from.vehicle_no) AS vehicle_no,
                           c.name AS customer_name, c.phone AS customer_phone, s.name AS supplier_name, bf.name AS from_branch, bt.name AS to_branch,
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
                     AND dra_to.delivery_date BETWEEN ? AND ?
                    LEFT JOIN delivery_route_assignments dra_from
                      ON dra_from.customer_id = p.customer_id
                     AND dra_from.branch_id = p.from_branch_id
                     AND dra_from.delivery_date BETWEEN ? AND ?';
            // add params for dra joins at the beginning of params later
            array_unshift($params, $from, $to, $from, $to);
        } else {
            $sql = 'SELECT p.id, p.customer_id, p.supplier_id, p.from_branch_id, p.to_branch_id, p.weight, p.price, p.status, p.created_at, p.updated_at,
                           COALESCE(NULLIF(p.vehicle_no, ""), dra_to.vehicle_no, dra_from.vehicle_no) AS vehicle_no,
                           c.name AS customer_name, c.phone AS customer_phone, s.name AS supplier_name, bf.name AS from_branch, bt.name AS to_branch,
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
                     AND dra_to.delivery_date BETWEEN ? AND ?
                    LEFT JOIN delivery_route_assignments dra_from
                      ON dra_from.customer_id = p.customer_id
                     AND dra_from.branch_id = p.from_branch_id
                     AND dra_from.delivery_date BETWEEN ? AND ?';
            array_unshift($params, $from, $to, $from, $to);
        }
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        
        // Pagination: 10 items per page
        $page = max(1, (int)($_GET['page_num'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
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
        if ($to_branch_filter_id > 0) {
            $countParams[] = $to_branch_filter_id;
        }
        if (in_array($status, ['pending','in_transit','delivered'], true)) {
            $countParams[] = $status;
        }
        if ($from !== '' && $to !== '') {
            array_push($countParams, $from, $to);
        }
        $countStmt->execute($countParams);
        $totalCount = (int)$countStmt->fetch()['total'];
        $totalPages = max(1, ceil($totalCount / $perPage));
        
        $sql .= ' ORDER BY p.created_at DESC, p.id DESC LIMIT ? OFFSET ?';
        $stmt = $pdo->prepare($sql);
        $params[] = $perPage;
        $params[] = $offset;
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
        // customers for filter
        $customersList = $pdo->query('SELECT id, name, phone FROM customers ORDER BY name LIMIT 500')->fetchAll();
        // branches for filter
        $branchesFilterList = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
        Helpers::view('parcels/index', compact('parcels','q','status','vehicle_no','customer_filter_id','customersList','from','to','to_branch_filter_id','branchesFilterList','isMain','canCreateParcels','isKilinochchi','page','totalPages','totalCount'));
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
        $pdo = Database::pdo();
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT p.*, c.name AS customer_name, c.phone AS customer_phone, s.name AS supplier_name, s.phone AS supplier_phone, bf.name AS from_branch, bt.name AS to_branch FROM parcels p LEFT JOIN customers c ON c.id=p.customer_id LEFT JOIN suppliers s ON s.id = p.supplier_id LEFT JOIN branches bf ON bf.id=p.from_branch_id LEFT JOIN branches bt ON bt.id=p.to_branch_id WHERE p.id=?');
        $stmt->execute([$id]);
        $parcel = $stmt->fetch();
        if (!$parcel) { http_response_code(404); echo 'Not found'; break; }
        $it = $pdo->prepare('SELECT * FROM parcel_items WHERE parcel_id=? ORDER BY id');
        $it->execute([$id]);
        $items = $it->fetchAll();
        include __DIR__ . '/../views/parcels/print.php';
        break;

    case 'delivery_notes':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        if (!Auth::hasAnyRole(['admin','parcel_user','staff'])) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $user = Auth::user();
        $branchId = (int)($user['branch_id'] ?? 0);
        $branchFilterId = Auth::isMainBranch() ? (int)($_GET['branch_id'] ?? $branchId) : $branchId;
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
            $subject='Delivery Note #'.$id.' — '.number_format($net,2);
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
            Helpers::redirect('index.php?page=delivery_notes&action=route&saved=' . ($ok?1:0));
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

                // Find PENDING parcels for that customer for THIS branch and direction, not already in any DN, and not delivered
                // The selected delivery_date is used for the DN record, not as a filter for parcel created_at
                $branchColGen = ($direction === 'from') ? 'p.from_branch_id' : 'p.to_branch_id';
                $stmt = $pdo->prepare("SELECT p.* FROM parcels p
                    LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id
                    WHERE p.customer_id = ? AND $branchColGen = ? AND dnp.id IS NULL AND (p.status IS NULL OR p.status <> 'delivered')");
                $stmt->execute([$customer_id, $branchId]);
                $rows = $stmt->fetchAll();

                // Upsert delivery note
                $stmt = $pdo->prepare('SELECT id FROM delivery_notes WHERE customer_id=? AND branch_id=? AND delivery_date=? LIMIT 1');
                $stmt->execute([$customer_id, $branchId, $delivery_date]);
                $dn = $stmt->fetch();
                if ($dn) {
                    $dnId = (int)$dn['id'];
                } else {
                    $pdo->prepare('INSERT INTO delivery_notes (customer_id, branch_id, delivery_date, total_amount) VALUES (?,?,?,0)')->execute([$customer_id, $branchId, $delivery_date]);
                    $dnId = (int)$pdo->lastInsertId();
                }

                $total = 0;
                $parcelIds = [];
                foreach ($rows as $r) {
                    $rawPrice = (string)($r['price'] ?? '0');
                    $amount = (float)str_replace([',',' '], '', $rawPrice);
                    if ($amount <= 0) {
                        try {
                            $sumItems = $pdo->prepare('SELECT COALESCE(SUM(COALESCE(qty,0)*COALESCE(rate,0)),0) AS s FROM parcel_items WHERE parcel_id=?');
                            $sumItems->execute([(int)$r['id']]);
                            $amount = (float)($sumItems->fetch()['s'] ?? 0);
                        } catch (Throwable $e) { /* ignore */ }
                    }
                    // Try insert; ignore if already added due to race
                    $ins = $pdo->prepare('INSERT IGNORE INTO delivery_note_parcels (delivery_note_id, parcel_id, amount) VALUES (?,?,?)');
                    $ins->execute([$dnId, (int)$r['id'], $amount]);
                    $total += $amount;
                    $parcelIds[] = (int)$r['id'];
                }
                // Recalculate total from table to be safe
                $sum = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS s FROM delivery_note_parcels WHERE delivery_note_id=?');
                $sum->execute([$dnId]);
                $srow = $sum->fetch();
                $pdo->prepare('UPDATE delivery_notes SET total_amount=? WHERE id=?')->execute([(float)$srow['s'], $dnId]);

                // Mark included parcels as delivered (branch-to-customer leg completed)
                if (!empty($parcelIds)) {
                    $in = implode(',', array_fill(0, count($parcelIds), '?'));
                    $upd = $pdo->prepare("UPDATE parcels SET status='delivered' WHERE id IN ($in)");
                    $upd->execute($parcelIds);
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
                        $subject = 'Delivery Note #'.$dnId.' — '.number_format((float)$dnRow['total_amount'],2);
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
                $autoWhere = ['p.to_branch_id = ?','(p.status IS NULL OR p.status <> "delivered")','dnp.id IS NULL'];
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
                     AND (p.status IS NULL OR p.status <> 'delivered')
                    LEFT JOIN (
                      SELECT parcel_id, SUM(COALESCE(qty,0)*COALESCE(rate,0)) AS items_total
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
                         AND (p.status IS NULL OR p.status <> 'delivered')
                        LEFT JOIN (
                          SELECT parcel_id, SUM(COALESCE(qty,0)*COALESCE(rate,0)) AS items_total
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
            $totalsSql = 'SELECT COUNT(*) AS parcels FROM parcels p LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id=p.id WHERE ' . (($direction === 'from') ? 'p.from_branch_id' : 'p.to_branch_id') . ' = ? AND (p.status IS NULL OR p.status<>"delivered") AND dnp.id IS NULL';
            $totalsStmt = $pdo->prepare($totalsSql);
            $totalsStmt->execute([$branchId]);
            $parcels_total = (int)($totalsStmt->fetch()['parcels'] ?? 0);
            $customers_total = count($routes);
            $branchName = (string)($user['branch_name'] ?? '');
            // Build dropdown filter lists (all customers and all places)
            $customersFilter = $pdo->query('SELECT id, name, phone FROM customers ORDER BY name LIMIT 1000')->fetchAll();
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
                                     AND (p.status IS NULL OR p.status <> "delivered")
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
            $redir = 'index.php?page=delivery_notes&action=route&customer_id=' . $customer_id . '&date=' . urlencode($delivery_date) . '&direction=' . urlencode($direction) . '&saved=1';
            Helpers::redirect($redir);
            break;
        }

        if ($action === 'route_vehicles') {
            // Vehicle-wise route list for THIS branch and selected date range (by parcels created_at)
            $from = $_GET['from'] ?? date('Y-m-01');
            $to = $_GET['to'] ?? date('Y-m-d');
            $direction = $_GET['direction'] ?? 'from'; // 'from' (dispatch) or 'to' (arrivals)
            $vehicle = trim($_GET['vehicle'] ?? '');
            $branchColumn = ($direction === 'to') ? 'p.to_branch_id' : 'p.from_branch_id';
            // For arrivals (to-branch), consider last update time; for dispatch (from-branch), use created_at
            $dateExpr = ($direction === 'to') ? 'DATE(COALESCE(p.updated_at, p.created_at))' : 'DATE(p.created_at)';
            $sql = "SELECT COALESCE(p.vehicle_no,'—') AS vehicle_no,
                           COUNT(*) AS parcels_count,
                           SUM(CASE WHEN p.status='delivered' THEN 1 ELSE 0 END) AS delivered_count,
                           MAX($dateExpr) AS last_date
                    FROM parcels p
                    LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id
                    WHERE $branchColumn = ? AND $dateExpr BETWEEN ? AND ?";
            $params = [$branchId, $from, $to];
            if ($vehicle !== '') { $sql .= ' AND COALESCE(p.vehicle_no, "") LIKE ?'; $params[] = "%$vehicle%"; }
            $sql .= "
                    GROUP BY COALESCE(p.vehicle_no,'—')
                    ORDER BY (COALESCE(p.vehicle_no,'')='') ASC, MAX($dateExpr) DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $routes = $stmt->fetchAll();
            Helpers::view('delivery_notes/route_vehicles', compact('routes','from','to','direction','vehicle'));
            break;
        }

        if ($action === 'route_vehicles_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $from = $_POST['from'] ?? date('Y-m-01');
            $to = $_POST['to'] ?? date('Y-m-d');
            $direction = $_POST['direction'] ?? 'from';
            $old_vehicle = (string)($_POST['old_vehicle'] ?? '');
            $new_vehicle = trim((string)($_POST['new_vehicle'] ?? ''));
            if ($new_vehicle === '') {
                Helpers::redirect('index.php?page=delivery_notes&action=route_vehicles&from=' . urlencode($from) . '&to=' . urlencode($to) . '&direction=' . urlencode($direction) . '&err=vehicle_required');
                break;
            }
            $branchColumn = ($direction === 'to') ? 'to_branch_id' : 'from_branch_id';
            $dateExpr = ($direction === 'to') ? 'DATE(COALESCE(updated_at, created_at))' : 'DATE(created_at)';
            $sql = "UPDATE parcels SET vehicle_no = ? WHERE $branchColumn = ? AND $dateExpr BETWEEN ? AND ? AND COALESCE(vehicle_no,'') = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$new_vehicle, $branchId, $from, $to, $old_vehicle]);
            Helpers::redirect('index.php?page=delivery_notes&action=route_vehicles&from=' . urlencode($from) . '&to=' . urlencode($to) . '&direction=' . urlencode($direction) . '&saved=1');
            break;
        }

        if ($action === 'route_vehicles_clear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $from = $_POST['from'] ?? date('Y-m-01');
            $to = $_POST['to'] ?? date('Y-m-d');
            $direction = $_POST['direction'] ?? 'from';
            $old_vehicle = trim((string)($_POST['old_vehicle'] ?? ''));
            if ($old_vehicle === '') {
                Helpers::redirect('index.php?page=delivery_notes&action=route_vehicles&from=' . urlencode($from) . '&to=' . urlencode($to) . '&direction=' . urlencode($direction) . '&err=vehicle_required');
                break;
            }
            $branchColumn = ($direction === 'to') ? 'to_branch_id' : 'from_branch_id';
            $dateExpr = ($direction === 'to') ? 'DATE(COALESCE(updated_at, created_at))' : 'DATE(created_at)';
            $sql = "UPDATE parcels SET vehicle_no = '' WHERE $branchColumn = ? AND $dateExpr BETWEEN ? AND ? AND COALESCE(vehicle_no,'') = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$branchId, $from, $to, $old_vehicle]);
            Helpers::redirect('index.php?page=delivery_notes&action=route_vehicles&from=' . urlencode($from) . '&to=' . urlencode($to) . '&direction=' . urlencode($direction) . '&saved=1');
            break;
        }

        if ($action === 'route_detail') {
            // Show parcels for a given vehicle number, grouped by customer, only those not yet attached to a DN for this branch
            $vehicle_no = trim($_GET['vehicle_no'] ?? '');
            $from = $_GET['from'] ?? date('Y-m-01');
            $to = $_GET['to'] ?? date('Y-m-d');
            $direction = $_GET['direction'] ?? 'from';
            $placeFilter = trim($_GET['place'] ?? '');
            $branchColumn = ($direction === 'to') ? 'p.to_branch_id' : 'p.from_branch_id';
            // Date range uses last update time for arrivals
            $dateExpr = ($direction === 'to') ? 'DATE(COALESCE(p.updated_at, p.created_at))' : 'DATE(p.created_at)';
            $where = ["$branchColumn = ?", "$dateExpr BETWEEN ? AND ?"];
            $params = [$branchId, $from, $to];
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
                if ($place === '') { $place = '—'; }
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
                $sel = $pdo->prepare("SELECT p.* FROM parcels p LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id WHERE p.customer_id=? AND p.to_branch_id=? AND dnp.id IS NULL AND (p.status IS NULL OR p.status <> 'delivered')");
                $sel->execute([(int)$dn['customer_id'], (int)$dn['branch_id']]);
                $toAdd = $sel->fetchAll();
                // If none, try dispatch from this branch
                if (!$toAdd) {
                    $sel2 = $pdo->prepare("SELECT p.* FROM parcels p LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id WHERE p.customer_id=? AND p.from_branch_id=? AND dnp.id IS NULL AND (p.status IS NULL OR p.status <> 'delivered')");
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
                $sel = $pdo->prepare("SELECT p.* FROM parcels p LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id WHERE p.customer_id=? AND p.to_branch_id=? AND dnp.id IS NULL AND (p.status IS NULL OR p.status <> 'delivered')");
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
        // Best-effort: backfill items for DNs in range that currently have zero items so list columns can show data
        try {
            $findEmpty = $pdo->prepare('SELECT dn.id, dn.customer_id, dn.branch_id FROM delivery_notes dn LEFT JOIN delivery_note_parcels dnp ON dnp.delivery_note_id=dn.id WHERE dn.branch_id=? AND dn.delivery_date BETWEEN ? AND ? GROUP BY dn.id, dn.customer_id, dn.branch_id HAVING COUNT(dnp.id)=0 LIMIT 100');
            $findEmpty->execute([$branchId, $from, $to]);
            $empties = $findEmpty->fetchAll();
            foreach ($empties as $row) {
                $dnId = (int)$row['id']; $cid = (int)$row['customer_id']; $bid = (int)$row['branch_id'];
                // Attach pending parcels not already in any DN, not delivered
                // Try arrivals (to_branch) first
                $sel = $pdo->prepare('SELECT p.id, COALESCE(p.price,0) AS price FROM parcels p LEFT JOIN delivery_note_parcels x ON x.parcel_id=p.id WHERE p.customer_id=? AND p.to_branch_id=? AND x.id IS NULL AND (p.status IS NULL OR p.status<>"delivered")');
                $sel->execute([$cid, $bid]);
                $prs = $sel->fetchAll();
                // If none, try dispatch (from_branch)
                if (!$prs) {
                    $sel2 = $pdo->prepare('SELECT p.id, COALESCE(p.price,0) AS price FROM parcels p LEFT JOIN delivery_note_parcels x ON x.parcel_id=p.id WHERE p.customer_id=? AND p.from_branch_id=? AND x.id IS NULL AND (p.status IS NULL OR p.status<>"delivered")');
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

        $sql = 'SELECT dn.*, (dn.total_amount + COALESCE(dn.discount,0)) AS net_total, c.name AS customer_name, c.phone AS customer_phone,
                       dn.last_email_status AS email_status, dn.last_emailed_at AS emailed_at,
                       TRIM(BOTH "," FROM GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ", ")) AS suppliers,
                       TRIM(BOTH "," FROM GROUP_CONCAT(DISTINCT COALESCE(s.phone, "") ORDER BY s.phone SEPARATOR ", ")) AS supplier_phones,
                       COALESCE(NULLIF(TRIM(BOTH "," FROM GROUP_CONCAT(DISTINCT COALESCE(p.vehicle_no, "") ORDER BY p.vehicle_no SEPARATOR ", ")), ""), dra.vehicle_no, "") AS vehicles,
                       TRIM(BOTH "," FROM GROUP_CONCAT(DISTINCT TRIM(pit.description) ORDER BY pit.id SEPARATOR ", ")) AS item_descriptions
                FROM delivery_notes dn
                LEFT JOIN customers c ON c.id = dn.customer_id
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
        Helpers::view('delivery_notes/index', compact('notes','from','to','q'));
        break;

    case 'payments':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        if (!Auth::canCollectPayments()) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $user = Auth::user();
        $branchId = (int)($user['branch_id'] ?? 0);
        $isMain = Auth::isMainBranch();
        $action = $_GET['action'] ?? 'index';
        // For Main branch users, allow selecting any branch or All (0). Default to All.
        $branchFilterId = $isMain ? (int)($_GET['branch_id'] ?? 0) : $branchId;

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$isMain) { http_response_code(403); echo 'Forbidden'; break; }
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $dnId = (int)($_POST['delivery_note_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $paid_at = $_POST['paid_at'] ?? date('Y-m-d H:i:s');
            if ($dnId <= 0 || $amount <= 0) { $error = 'Valid delivery note and positive amount required.'; }
            // fetch DN with customer info and due
            $stmt = $pdo->prepare('SELECT dn.*, c.name AS customer_name, c.phone AS customer_phone, COALESCE((SELECT SUM(amount) FROM payments WHERE delivery_note_id=dn.id),0) AS paid FROM delivery_notes dn LEFT JOIN customers c ON c.id = dn.customer_id WHERE dn.id=?');
            $stmt->execute([$dnId]);
            $dn = $stmt->fetch();
            if (!$dn) { http_response_code(404); echo 'Delivery note not found'; break; }
            // Enforce AFTER delivery: ensure all parcels in this DN are delivered
            $undel = $pdo->prepare('SELECT COUNT(*) AS cnt FROM delivery_note_parcels dnp JOIN parcels p ON p.id=dnp.parcel_id WHERE dnp.delivery_note_id=? AND p.status <> "delivered"');
            $undel->execute([$dnId]);
            $undelCnt = (int)$undel->fetchColumn();
            if ($undelCnt > 0) {
                $error = 'Payments are allowed only after delivery. Some parcels in this delivery note are not delivered yet.';
            }
            // Recompute current due and prevent overpayment
            $netTotal = (float)$dn['total_amount'] + (float)($dn['discount'] ?? 0);
            $due = $netTotal - (float)$dn['paid'];
            if ($amount > $due) {
                $error = 'Amount exceeds due. Please enter an amount up to the current due.';
            }
            if (!empty($error)) {
                $payment = ['delivery_note_id'=>$dnId,'amount'=>max(0,$amount),'paid_at'=>$paid_at];
                Helpers::view('payments/form', compact('payment','dn','error','isMain'));
                break;
            }
            $ins = $pdo->prepare('INSERT INTO payments (delivery_note_id, amount, paid_at, received_by) VALUES (?,?,?,?)');
            $ins->execute([$dnId, $amount, $paid_at, (int)($user['id'] ?? null)]);
            Helpers::redirect('index.php?page=payments');
            break;
        }

        if ($action === 'new') {
            if (!$isMain) { http_response_code(403); echo 'Forbidden'; break; }
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT dn.*, c.name AS customer_name, c.phone AS customer_phone, COALESCE((SELECT SUM(amount) FROM payments WHERE delivery_note_id=dn.id),0) AS paid FROM delivery_notes dn LEFT JOIN customers c ON c.id = dn.customer_id WHERE dn.id=?');
            $stmt->execute([$id]);
            $dn = $stmt->fetch();
            if (!$dn) { http_response_code(404); echo 'Delivery note not found'; break; }
            // Block payments before delivery if any parcels are undelivered
            $undel = $pdo->prepare('SELECT COUNT(*) AS cnt FROM delivery_note_parcels dnp JOIN parcels p ON p.id=dnp.parcel_id WHERE dnp.delivery_note_id=? AND p.status <> "delivered"');
            $undel->execute([$id]);
            $undelCnt = (int)$undel->fetchColumn();
            $error = '';
            if ($undelCnt > 0) {
                $error = 'Payments are allowed only after delivery. Some parcels in this delivery note are not delivered yet.';
            }
            $netTotal = (float)$dn['total_amount'] + (float)($dn['discount'] ?? 0);
            $payment = ['delivery_note_id'=>$id,'amount'=>max(0, $netTotal - (float)$dn['paid']),'paid_at'=>date('Y-m-d H:i:s')];
            Helpers::view('payments/form', compact('payment','dn','isMain','error'));
            break;
        }

        // index: list DNs with outstanding due for selected branch (or All for main), filter by date range and search
        // Default range: last 30 days, so recent dues and payments are visible without manual filtering
        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $to = $_GET['to'] ?? date('Y-m-d');
        $groupMode = $_GET['group'] ?? 'customer'; // 'customer' (default) or 'dn'
        $q = trim($_GET['q'] ?? '');
            $sql = 'SELECT dn.*, c.name AS customer_name, c.phone AS customer_phone,
                       ((dn.total_amount + COALESCE(dn.discount,0)) - COALESCE(paid.total_paid,0)) AS due,
                       COALESCE(paid.total_paid,0) AS paid,
                       COALESCE(dn.discount, 0) AS discount,
                       (dn.total_amount + COALESCE(dn.discount, 0)) AS amount_after_discount,
                       dn.total_amount AS display_total
                FROM delivery_notes dn
                LEFT JOIN customers c ON c.id = dn.customer_id
                LEFT JOIN (
                    SELECT delivery_note_id, SUM(amount) AS total_paid
                    FROM payments GROUP BY delivery_note_id
                ) paid ON paid.delivery_note_id = dn.id
                WHERE dn.delivery_date BETWEEN ? AND ?';
        $params = [$from, $to];
        if ($branchFilterId > 0) {
            $sql .= ' AND dn.branch_id = ?';
            $params[] = $branchFilterId;
        }
        if ($q !== '') {
            $sql .= ' AND (c.phone LIKE ? OR c.name LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like);
        }
        $sql .= ' HAVING due > 0.01 ORDER BY dn.delivery_date DESC, dn.id DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $dues = $stmt->fetchAll();

        // Build grouped data by customer when requested
        $dueGroups = [];
        if ($groupMode === 'customer') {
            foreach ($dues as $row) {
                $cid = (int)($row['customer_id'] ?? 0);
                if ($cid <= 0) { $cid = -1; }
                if (!isset($dueGroups[$cid])) {
                    $dueGroups[$cid] = [
                        'customer_id' => $cid,
                        'customer_name' => (string)($row['customer_name'] ?? 'Unknown'),
                        'customer_phone' => (string)($row['customer_phone'] ?? ''),
                        'bills' => [],
                        'total_amount' => 0.0,
                        'discount' => 0.0,
                        'amount_after_discount' => 0.0,
                        'paid' => 0.0,
                        'due' => 0.0,
                    ];
                }
                $dueGroups[$cid]['bills'][] = [
                    'id' => (int)$row['id'],
                    'delivery_date' => (string)$row['delivery_date'],
                    'total_amount' => (float)$row['total_amount'],
                    'discount' => (float)($row['discount'] ?? 0),
                    'amount_after_discount' => (float)($row['amount_after_discount'] ?? ((float)$row['total_amount'] + (float)($row['discount'] ?? 0))),
                    'paid' => (float)$row['paid'],
                    'due' => (float)$row['due'],
                ];
                $dueGroups[$cid]['total_amount'] += (float)$row['total_amount'];
                $dueGroups[$cid]['discount'] += (float)($row['discount'] ?? 0);
                $dueGroups[$cid]['amount_after_discount'] += (float)($row['amount_after_discount'] ?? ((float)$row['total_amount'] + (float)($row['discount'] ?? 0)));
                $dueGroups[$cid]['paid'] += (float)$row['paid'];
                $dueGroups[$cid]['due'] += (float)$row['due'];
            }
            // Reindex to a numeric array for the view
            $dueGroups = array_values($dueGroups);
        }
        
        // Get payment history for DataTable

        
        $fromStart = $from . ' 00:00:00';
        $toEnd = $to . ' 23:59:59';
        $paymentsSql = 'SELECT p.*, dn.id as dn_id, c.name AS customer_name, c.phone AS customer_phone, 
                               u.full_name AS received_by_name, b.name AS branch_name
                        FROM payments p
                        LEFT JOIN delivery_notes dn ON dn.id = p.delivery_note_id
                        LEFT JOIN customers c ON c.id = dn.customer_id
                        LEFT JOIN users u ON u.id = p.received_by
                        LEFT JOIN branches b ON b.id = dn.branch_id
                        WHERE p.paid_at BETWEEN ? AND ?';
        $paymentsParams = [$fromStart, $toEnd];
        if ($branchFilterId > 0) {
            $paymentsSql .= ' AND dn.branch_id = ?';
            $paymentsParams[] = $branchFilterId;
        }
        if ($q !== '') {
            $paymentsSql .= ' AND (c.phone LIKE ? OR c.name LIKE ?)';
            $like = "%$q%";
            array_push($paymentsParams, $like, $like);
        }
        $paymentsSql .= ' ORDER BY p.paid_at DESC, p.id DESC LIMIT 500';
        $paymentsStmt = $pdo->prepare($paymentsSql);
        $paymentsStmt->execute($paymentsParams);
        $payments = $paymentsStmt->fetchAll();
        
        // For main users, provide branches list for dropdown
        $branchesAll = $isMain ? $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll() : [];
        Helpers::view('payments/index', compact('dues','dueGroups','groupMode','payments','from','to','q','isMain','branchesAll','branchFilterId'));
        break;

    case 'expenses':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        if (!Auth::canManageExpenses()) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $user = Auth::user();
        $branchId = (int)($user['branch_id'] ?? 0);
        $isAdmin = Auth::hasRole('admin');
        $action = $_GET['action'] ?? 'index';

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $expense_type = trim($_POST['expense_type'] ?? '');
            $amount = (float)($_POST['amount'] ?? 0);
            $branch_id = (int)($_POST['branch_id'] ?? $branchId);
            $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
            $notes = trim($_POST['notes'] ?? '');
            $payment_mode = ($_POST['payment_mode'] ?? 'cash') === 'credit' ? 'credit' : 'cash';
            $credit_party = trim($_POST['credit_party'] ?? '');
            $credit_due_date = $_POST['credit_due_date'] ?? null;
            // Normalize dates to YYYY-MM-DD
            try { if ($expense_date) { $ts = strtotime($expense_date); if ($ts) { $expense_date = date('Y-m-d', $ts); } } } catch (Throwable $dtEx) {}
            try { if ($credit_due_date) { $ts2 = strtotime($credit_due_date); if ($ts2) { $credit_due_date = date('Y-m-d', $ts2); } } } catch (Throwable $dtEx2) {}
            if ($expense_type === '') { $expense_type = 'other'; }
            if ($payment_mode === 'credit') {
                if ($credit_party === '' || ($credit_due_date === null || $credit_due_date === '')) {
                    $error = 'For Credit expenses, Credit Party and Due Date are required.';
                }
            }
            if ($amount <= 0 || $branch_id <= 0) { $error = 'Amount and Branch are required.'; }
            if (!empty($error)) {
                $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
                $expense = compact('id','expense_type','amount','branch_id','expense_date','notes','payment_mode','credit_party','credit_due_date');
                $typesDynamic = $pdo->query("SELECT DISTINCT expense_type FROM expenses WHERE expense_type IS NOT NULL AND expense_type<>'' ORDER BY expense_type")->fetchAll();
                Helpers::view('expenses/form', compact('expense','branchesAll','error','typesDynamic'));
                break;
            }
            try {
                // Check if new credit columns exist; if not, try to add them once
                $hasNewCols = true;
                try { $pdo->query("SELECT payment_mode, credit_party, credit_due_date, credit_settled FROM expenses LIMIT 1"); }
                catch (Throwable $exCols) {
                    $hasNewCols = false;
                    try {
                        $pdo->exec("ALTER TABLE expenses
                          ADD COLUMN payment_mode ENUM('cash','credit') NOT NULL DEFAULT 'cash' AFTER expense_type,
                          ADD COLUMN credit_party VARCHAR(150) NULL AFTER notes,
                          ADD COLUMN credit_due_date DATE NULL AFTER credit_party,
                          ADD COLUMN credit_settled TINYINT(1) NOT NULL DEFAULT 0 AFTER credit_due_date");
                        $hasNewCols = true;
                    } catch (Throwable $ign) { /* leave as false and fall back */ }
                }

                if ($id > 0) {
                    if ($hasNewCols) {
                        $stmt = $pdo->prepare('UPDATE expenses SET expense_type=?, amount=?, branch_id=?, expense_date=?, notes=?, payment_mode=?, credit_party=?, credit_due_date=?, credit_settled = CASE WHEN ? = "cash" THEN 1 ELSE credit_settled END WHERE id=?');
                        $stmt->execute([$expense_type,$amount,$branch_id,$expense_date,$notes,$payment_mode,($credit_party?:null),($credit_due_date?:null),$payment_mode,$id]);
                    } else {
                        // Legacy fallback without new columns
                        $stmt = $pdo->prepare('UPDATE expenses SET expense_type=?, amount=?, branch_id=?, expense_date=?, notes=? WHERE id=?');
                        $stmt->execute([$expense_type,$amount,$branch_id,$expense_date,$notes,$id]);
                        // Try to add columns now and backfill this row
                        try {
                            $pdo->exec("ALTER TABLE expenses
                              ADD COLUMN payment_mode ENUM('cash','credit') NOT NULL DEFAULT 'cash' AFTER expense_type,
                              ADD COLUMN credit_party VARCHAR(150) NULL AFTER notes,
                              ADD COLUMN credit_due_date DATE NULL AFTER credit_party,
                              ADD COLUMN credit_settled TINYINT(1) NOT NULL DEFAULT 0 AFTER credit_due_date");
                            $upd2 = $pdo->prepare('UPDATE expenses SET payment_mode=?, credit_party=?, credit_due_date=?, credit_settled=? WHERE id=?');
                            $upd2->execute([$payment_mode, ($credit_party?:null), ($credit_due_date?:null), ($payment_mode==='cash'?1:0), $id]);
                        } catch (Throwable $ign2) { /* ignore */ }
                    }
                } else {
                    if ($hasNewCols) {
                        $stmt = $pdo->prepare('INSERT INTO expenses (expense_type, amount, branch_id, expense_date, notes, payment_mode, credit_party, credit_due_date, credit_settled) VALUES (?,?,?,?,?,?,?,?,?)');
                        $stmt->execute([$expense_type,$amount,$branch_id,$expense_date,$notes,$payment_mode,($credit_party?:null),($credit_due_date?:null),($payment_mode==='cash'?1:0)]);
                    } else {
                        // Legacy fallback without new columns
                        $stmt = $pdo->prepare('INSERT INTO expenses (expense_type, amount, branch_id, expense_date, notes) VALUES (?,?,?,?,?)');
                        $stmt->execute([$expense_type,$amount,$branch_id,$expense_date,$notes]);
                        $newId = (int)$pdo->lastInsertId();
                        // Try to add columns and then update this newly inserted row
                        try {
                            $pdo->exec("ALTER TABLE expenses
                              ADD COLUMN payment_mode ENUM('cash','credit') NOT NULL DEFAULT 'cash' AFTER expense_type,
                              ADD COLUMN credit_party VARCHAR(150) NULL AFTER notes,
                              ADD COLUMN credit_due_date DATE NULL AFTER credit_party,
                              ADD COLUMN credit_settled TINYINT(1) NOT NULL DEFAULT 0 AFTER credit_due_date");
                            if ($newId > 0) {
                                $upd3 = $pdo->prepare('UPDATE expenses SET payment_mode=?, credit_party=?, credit_due_date=?, credit_settled=? WHERE id=?');
                                $upd3->execute([$payment_mode, ($credit_party?:null), ($credit_due_date?:null), ($payment_mode==='cash'?1:0), $newId]);
                            }
                        } catch (Throwable $ign3) { /* ignore */ }
                    }
                }
                Helpers::redirect('index.php?page=expenses');
            } catch (PDOException $e) {
                // Likely cause: expenses.expense_type is ENUM and rejects custom value
                $msg = 'Failed to save expense.';
                if ($e->getCode() === '22007' || $e->getCode() === 'HY000' || $e->getCode() === '23000') {
                    $em = $e->getMessage();
                    if (stripos($em, 'enum') !== false || stripos($em, 'Incorrect') !== false) {
                        $msg = 'This database does not allow new Expense Types. Please change expenses.expense_type to VARCHAR(50), or choose an existing type.';
                    }
                }
                // Append DB error for visibility
                $msg .= ' (' . $e->getMessage() . ')';
                $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
                $expense = compact('id','expense_type','amount','branch_id','expense_date','notes','payment_mode','credit_party','credit_due_date');
                $typesDynamic = $pdo->query("SELECT DISTINCT expense_type FROM expenses WHERE expense_type IS NOT NULL AND expense_type<>'' ORDER BY expense_type")->fetchAll();
                $error = $msg;
                Helpers::view('expenses/form', compact('expense','branchesAll','error','typesDynamic'));
            }
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) { $pdo->prepare('DELETE FROM expenses WHERE id=?')->execute([$id]); }
            Helpers::redirect('index.php?page=expenses');
            break;
        }

        if ($action === 'approve' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$isAdmin) { http_response_code(403); echo 'Forbidden'; break; }
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE expenses SET approved_by=? WHERE id=?');
                $stmt->execute([(int)($user['id'] ?? 0), $id]);
            }
            Helpers::redirect('index.php?page=expenses');
            break;
        }

        // Mark an expense explicitly as Credit
        if ($action === 'mark_credit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    $pdo->query("SELECT payment_mode, credit_settled FROM expenses LIMIT 1");
                    $pdo->prepare("UPDATE expenses SET payment_mode='credit', credit_settled=0 WHERE id=?")->execute([$id]);
                } catch (Throwable $e) { /* ignore */ }
            }
            Helpers::redirect('index.php?page=expenses');
            break;
        }

        // Record a settlement payment against a credit expense
        if ($action === 'settle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $expense_id = (int)($_POST['id'] ?? 0);
            $pay_amount = (float)($_POST['pay_amount'] ?? 0);
            $pay_notes = trim($_POST['pay_notes'] ?? '');
            if ($expense_id <= 0) { http_response_code(400); echo 'Invalid request'; break; }
            if ($pay_amount < 0) { $pay_amount = 0; }
            // Compute current balance
            $row = null;
            $sel = $pdo->prepare('SELECT e.amount, e.payment_mode FROM expenses e WHERE e.id=? LIMIT 1');
            $sel->execute([$expense_id]);
            $row = $sel->fetch();
            if (!$row) { http_response_code(404); echo 'Not found'; break; }
            if (($row['payment_mode'] ?? 'cash') !== 'credit') { Helpers::redirect('index.php?page=expenses'); break; }
            // Ensure expense_payments table exists (in case migration not run)
            try { $pdo->query('SELECT 1 FROM expense_payments LIMIT 1'); }
            catch (Throwable $e) {
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS expense_payments (
                      id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                      expense_id BIGINT UNSIGNED NOT NULL,
                      amount DECIMAL(12,2) NOT NULL,
                      paid_at DATETIME NOT NULL,
                      paid_by BIGINT UNSIGNED NULL,
                      notes VARCHAR(255) NULL,
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      CONSTRAINT fk_exp_pay_expense FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE,
                      CONSTRAINT fk_exp_pay_user FOREIGN KEY (paid_by) REFERENCES users(id)
                    ) ENGINE=InnoDB;");
                } catch (Throwable $e2) { /* ignore create failure and continue */ }
            }
            $agg = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS paid_total FROM expense_payments WHERE expense_id=?');
            $agg->execute([$expense_id]);
            $paid_total = (float)($agg->fetch()['paid_total'] ?? 0);
            $balance = max(0.0, (float)$row['amount'] - $paid_total);
            // Default to full balance if no amount provided
            $apply = ($pay_amount <= 0) ? $balance : min($pay_amount, $balance);
            if ($apply <= 0) { Helpers::redirect('index.php?page=expenses'); break; }
            // Insert payment and update settled flag if needed
            $ins = $pdo->prepare('INSERT INTO expense_payments (expense_id, amount, paid_at, paid_by, notes) VALUES (?,?,?,?,?)');
            $ins->execute([$expense_id, $apply, date('Y-m-d H:i:s'), (int)($user['id'] ?? 0), ($pay_notes?:null)]);
            $newPaid = $paid_total + $apply;
            $settled = ($newPaid + 0.0001) >= (float)$row['amount'] ? 1 : 0;
            if ($settled) { $pdo->prepare('UPDATE expenses SET credit_settled=1 WHERE id=?')->execute([$expense_id]); }
            Helpers::redirect('index.php?page=expenses');
            break;
        }

        if ($action === 'new') {
            $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
            $expense = ['id'=>0,'expense_type'=>'other','amount'=>'','branch_id'=>$branchId,'expense_date'=>date('Y-m-d'),'notes'=>'','payment_mode'=>'cash','credit_party'=>'','credit_due_date'=>''];
            $typesDynamic = $pdo->query("SELECT DISTINCT expense_type FROM expenses WHERE expense_type IS NOT NULL AND expense_type<>'' ORDER BY expense_type")->fetchAll();
            Helpers::view('expenses/form', compact('expense','branchesAll','typesDynamic'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM expenses WHERE id=?');
            $stmt->execute([$id]);
            $expense = $stmt->fetch();
            if (!$expense) { http_response_code(404); echo 'Not found'; break; }
            $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
            $typesDynamic = $pdo->query("SELECT DISTINCT expense_type FROM expenses WHERE expense_type IS NOT NULL AND expense_type<>'' ORDER BY expense_type")->fetchAll();
            Helpers::view('expenses/form', compact('expense','branchesAll','typesDynamic'));
            break;
        }

        // index with filters and summaries
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        $branchFilter = (int)($_GET["branch_id"] ?? 0);
        $notesFilter = trim($_GET['notes'] ?? '');
        $approved = trim($_GET['approved'] ?? ''); // '', 'yes', 'no'
        $typeFilter = trim($_GET['type'] ?? '');
        $modeFilter = trim($_GET['mode'] ?? ''); // '', 'cash', 'credit'
        $creditStatus = trim($_GET['credit_status'] ?? ''); // '', 'open', 'settled', 'overdue'
        // Detect if credit columns exist
        $hasCreditCols = true;
        try { $pdo->query("SELECT payment_mode, credit_party, credit_due_date FROM expenses LIMIT 1"); }
        catch (Throwable $e) { $hasCreditCols = false; }
        // One-time data correction only if columns exist
        if ($hasCreditCols) {
            try {
                $pdo->exec("UPDATE expenses SET payment_mode='credit' WHERE payment_mode IS NULL AND (credit_party IS NOT NULL OR credit_due_date IS NOT NULL)");
                $pdo->exec("UPDATE expenses SET payment_mode='cash' WHERE payment_mode IS NULL AND credit_party IS NULL AND credit_due_date IS NULL");
            } catch (Throwable $e2) { /* ignore */ }
        }

        $where = ['e.expense_date BETWEEN ? AND ?'];
        $params = [$from, $to];
        if ($branchFilter > 0) { $where[] = 'e.branch_id = ?'; $params[] = $branchFilter; }
        if ($typeFilter !== '') { $where[] = 'e.expense_type = ?'; $params[] = $typeFilter; }
        if ($notesFilter !== '') { $where[] = 'COALESCE(e.notes, "") LIKE ?'; $params[] = "%$notesFilter%"; }
        if ($approved === 'yes') { $where[] = 'e.approved_by IS NOT NULL'; }
        else if ($approved === 'no') { $where[] = 'e.approved_by IS NULL'; }
        $creditHintExpr = $hasCreditCols ? "(e.credit_party IS NOT NULL OR e.credit_due_date IS NOT NULL)" : "0";
        if ($modeFilter === 'cash') {
            $where[] = "(COALESCE(e.payment_mode,'cash')='cash' AND NOT (" . $creditHintExpr . "))";
        } else if ($modeFilter === 'credit') {
            $where[] = "(e.payment_mode='credit' OR " . $creditHintExpr . ")";
        }
        if ($creditStatus !== '') {
            if ($creditStatus === 'settled') { $where[] = "(e.payment_mode='credit' AND e.credit_settled=1)"; }
            else if ($creditStatus === 'open') { $where[] = "(e.payment_mode='credit' AND e.credit_settled=0)"; }
            else if ($creditStatus === 'overdue') { $where[] = "(e.payment_mode='credit' AND e.credit_settled=0 AND e.credit_due_date IS NOT NULL AND e.credit_due_date < CURDATE())"; }
        }
        $hasPayments = true;
        try { $pdo->query('SELECT 1 FROM expense_payments LIMIT 1'); } catch (Throwable $e) { $hasPayments = false; }
        if ($hasPayments) {
            $sql = "SELECT e.*, b.name AS branch_name,
                           CASE 
                             WHEN " . ($hasCreditCols ? "(e.payment_mode='credit' OR (e.credit_party IS NOT NULL OR e.credit_due_date IS NOT NULL))" : "e.payment_mode='credit'") . " THEN COALESCE(paid.paid_total,0)
                             ELSE e.amount
                           END AS paid_total,
                           CASE 
                             WHEN " . ($hasCreditCols ? "(e.payment_mode='credit' OR (e.credit_party IS NOT NULL OR e.credit_due_date IS NOT NULL))" : "e.payment_mode='credit'") . " THEN (e.amount - COALESCE(paid.paid_total,0))
                             ELSE 0
                           END AS balance,
                           " . ($hasCreditCols ? "CASE WHEN e.payment_mode='credit' OR (e.credit_party IS NOT NULL OR e.credit_due_date IS NOT NULL) THEN 'credit' ELSE 'cash' END" : "CASE WHEN e.payment_mode='credit' THEN 'credit' ELSE 'cash' END") . " AS mode_effective
                    FROM expenses e
                    LEFT JOIN branches b ON b.id = e.branch_id
                    LEFT JOIN (
                      SELECT expense_id, SUM(amount) AS paid_total
                      FROM expense_payments
                      GROUP BY expense_id
                    ) paid ON paid.expense_id = e.id
                    WHERE " . implode(' AND ', $where) . " ORDER BY e.expense_date DESC, e.id DESC LIMIT 300";
        } else {
            $sql = "SELECT e.*, b.name AS branch_name,
                           CASE 
                             WHEN " . ($hasCreditCols ? "(e.payment_mode='credit' OR (e.credit_party IS NOT NULL OR e.credit_due_date IS NOT NULL))" : "e.payment_mode='credit'") . " THEN 0.0
                             ELSE e.amount
                           END AS paid_total,
                           CASE 
                             WHEN " . ($hasCreditCols ? "(e.payment_mode='credit' OR (e.credit_party IS NOT NULL OR e.credit_due_date IS NOT NULL))" : "e.payment_mode='credit'") . " THEN e.amount
                             ELSE 0.0
                           END AS balance,
                           " . ($hasCreditCols ? "CASE WHEN e.payment_mode='credit' OR (e.credit_party IS NOT NULL OR e.credit_due_date IS NOT NULL) THEN 'credit' ELSE 'cash' END" : "CASE WHEN e.payment_mode='credit' THEN 'credit' ELSE 'cash' END") . " AS mode_effective
                    FROM expenses e
                    LEFT JOIN branches b ON b.id = e.branch_id
                    WHERE " . implode(' AND ', $where) . " ORDER BY e.expense_date DESC, e.id DESC LIMIT 300";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $expenses = $stmt->fetchAll();

        // summaries (apply same filters)
        $sumSql = 'SELECT branch_id, SUM(amount) AS total FROM expenses e WHERE ' . implode(' AND ', $where) . ' GROUP BY branch_id';
        $sumStmt = $pdo->prepare($sumSql);
        $sumStmt->execute($params);
        $byBranch = $sumStmt->fetchAll();
        $overall = 0; foreach ($byBranch as $r) { $overall += (float)$r['total']; }
        $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
        $typesDynamic = $pdo->query("SELECT DISTINCT expense_type FROM expenses WHERE expense_type IS NOT NULL AND expense_type<>'' ORDER BY expense_type")->fetchAll();
        // Totals card: Cash purchases, Credit purchases, Settlements paid in range (use effective mode)
        $cashWhere = $where; $cashParams = $params;
        $creditWhere = $where; $creditParams = $params;
        if ($hasCreditCols) {
            $cashWhere[] = "NOT (e.payment_mode='credit' OR (e.credit_party IS NOT NULL OR e.credit_due_date IS NOT NULL))";
            $creditWhere[] = "(e.payment_mode='credit' OR (e.credit_party IS NOT NULL OR e.credit_due_date IS NOT NULL))";
        } else {
            $cashWhere[] = "e.payment_mode<>'credit'";
            $creditWhere[] = "e.payment_mode='credit'";
        }
        $cashTotalStmt = $pdo->prepare('SELECT COALESCE(SUM(e.amount),0) AS s FROM expenses e WHERE ' . implode(' AND ', $cashWhere));
        $cashTotalStmt->execute($cashParams); $cashTotal = (float)($cashTotalStmt->fetch()['s'] ?? 0);

        $creditTotalStmt = $pdo->prepare('SELECT COALESCE(SUM(e.amount),0) AS s FROM expenses e WHERE ' . implode(' AND ', $creditWhere));
        $creditTotalStmt->execute($creditParams); $creditTotal = (float)($creditTotalStmt->fetch()['s'] ?? 0);

        // Settlements use paid_at date range; apply branch/type/notes/approval filters (mapped where possible)
        $hasPayments2 = true; try { $pdo->query('SELECT 1 FROM expense_payments LIMIT 1'); } catch (Throwable $e) { $hasPayments2 = false; }
        $settlementsTotal = 0.0;
        if ($hasPayments2) {
            $setSql = 'SELECT COALESCE(SUM(ep.amount),0) AS s
                       FROM expense_payments ep
                       JOIN expenses e ON e.id = ep.expense_id
                       WHERE DATE(ep.paid_at) BETWEEN ? AND ?';
            $setParams = [$from, $to];
            if ($branchFilter > 0) { $setSql .= ' AND e.branch_id = ?'; $setParams[] = $branchFilter; }
            if ($typeFilter !== '') { $setSql .= ' AND e.expense_type = ?'; $setParams[] = $typeFilter; }
            if ($notesFilter !== '') { $setSql .= ' AND COALESCE(e.notes, "") LIKE ?'; $setParams[] = "%$notesFilter%"; }
            if ($approved === 'yes') { $setSql .= ' AND e.approved_by IS NOT NULL'; }
            else if ($approved === 'no') { $setSql .= ' AND e.approved_by IS NULL'; }
            if ($modeFilter === 'cash') { $setSql .= " AND e.payment_mode='cash'"; }
            else if ($modeFilter === 'credit') { $setSql .= " AND e.payment_mode='credit'"; }
            $st = $pdo->prepare($setSql);
            $st->execute($setParams);
            $settlementsTotal = (float)($st->fetch()['s'] ?? 0);
        }

        Helpers::view('expenses/index', compact('expenses','from','to','branchFilter','byBranch','overall','branchesAll','isAdmin','notesFilter','approved','typeFilter','typesDynamic','modeFilter','creditStatus','cashTotal','creditTotal','settlementsTotal'));
        break;

    case 'advances':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $user = Auth::user();
        $isAdmin = Auth::hasRole('admin');
        $isAccountant = (isset($user['role']) && $user['role'] === 'accountant');
        if (!($isAdmin || $isAccountant)) { http_response_code(403); echo 'Forbidden'; break; }
        $branchId = (int)($user['branch_id'] ?? 0);
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
            if ($id>0) {
                $stmt = $pdo->prepare('UPDATE employee_advances SET employee_id=?, branch_id=?, amount=?, advance_date=?, purpose=? WHERE id=?');
                $stmt->execute([$employee_id,$branchId,$amount,$advance_date,($purpose?:null),$id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO employee_advances (employee_id, branch_id, amount, advance_date, purpose, created_by) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$employee_id,$branchId,$amount,$advance_date,($purpose?:null),(int)($user['id'] ?? 0)]);
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

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $emp_code = trim($_POST['emp_code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $position = trim($_POST['position'] ?? '');
            $role = trim($_POST['role'] ?? '');
            // salary_amount removed from schema/UI
            $salary_amount = 0.0;
            // Payroll fields are no longer managed on the Employees form (moved to employee_payroll)
            $license_number = trim($_POST['license_number'] ?? '');
            $license_expiry = $_POST['license_expiry'] ?? null;
            // Normalize vehicle_id: empty => NULL, else integer
            $vehicle_id_raw = trim($_POST['vehicle_id'] ?? '');
            $vehicle_id = ($vehicle_id_raw === '') ? null : (int)$vehicle_id_raw;
            $branch_id = (int)($_POST['branch_id'] ?? 0);
            $join_date = $_POST['join_date'] ?? null;
            $status = $_POST['status'] ?? 'active';
            
            if ($name === '' || $position === '' || $branch_id <= 0) {
                $error = 'Name, Position and Branch are required.';
                $employee = compact('id','emp_code','name','first_name','last_name','email','phone','address','position','role','license_number','license_expiry','vehicle_id','branch_id','join_date','status');
                $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
                // Load vehicles for dropdown (use reg_number explicitly)
                try { $vehiclesAll = $pdo->query('SELECT id, reg_number AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); } catch (Throwable $e) { $vehiclesAll = []; }
                $rolesDynamic = [];
                try { $rolesDynamic = $pdo->query("SELECT DISTINCT role FROM employees WHERE role IS NOT NULL AND role<>'' ORDER BY role")->fetchAll(); } catch (Throwable $e) {}
                Helpers::view('employees/form', compact('employee','branchesAll','vehiclesAll','rolesDynamic','error'));
                break;
            }
            
            // Validate vehicle_id exists if provided; if not found, set to NULL (do not block save)
            if ($vehicle_id !== null) {
                $vehChk = $pdo->prepare('SELECT 1 FROM vehicles WHERE id=?');
                $vehChk->execute([$vehicle_id]);
                if (!$vehChk->fetchColumn()) {
                    $vehicle_id = null; // fallback to NULL to satisfy FK
                }
            }

            // Ensure unique Employee Code (emp_code). If blank or duplicate, auto-generate next EMP###
            $emp_code = strtoupper($emp_code);
            $needsGenerate = ($emp_code === '');
            if (!$needsGenerate) {
                $chk = $pdo->prepare('SELECT id FROM employees WHERE emp_code = ? LIMIT 1');
                $chk->execute([$emp_code]);
                $exists = $chk->fetch();
                if ($exists && (int)$exists['id'] !== (int)$id) {
                    $needsGenerate = true; // duplicate for another employee
                }
            }
            if ($needsGenerate) {
                // Find max EMP number and increment
                try {
                    $mx = $pdo->query("SELECT MAX(CAST(SUBSTRING(emp_code, 4) AS UNSIGNED)) AS m FROM employees WHERE emp_code REGEXP '^EMP[0-9]+' ")->fetch();
                    $next = (int)($mx['m'] ?? 0) + 1;
                } catch (Throwable $e) { $next = 1; }
                $emp_code = 'EMP' . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
            }

            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE employees SET emp_code=?, name=?, first_name=?, last_name=?, email=?, phone=?, address=?, position=?, role=?, license_number=?, license_expiry=?, vehicle_id = ?, branch_id=?, join_date=?, status=? WHERE id=?");
                    $stmt->execute([$emp_code,$name,$first_name,$last_name,$email,$phone,$address,$position,$role,$license_number,($license_expiry?:null),$vehicle_id,$branch_id,($join_date?:null),$status,$id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO employees (emp_code, name, first_name, last_name, email, phone, address, position, role, license_number, license_expiry, vehicle_id, branch_id, join_date, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$emp_code,$name,$first_name,$last_name,$email,$phone,$address,$position,$role,$license_number,($license_expiry?:null),$vehicle_id,$branch_id,($join_date?:null),$status]);
                }
                $redir = trim($_POST['redirect_to'] ?? '');
                if ($redir !== '') {
                    Helpers::redirect($redir);
                } else {
                    Helpers::redirect('index.php?page=employees');
                }
                break;
            } catch (PDOException $ex) {
                $msg = $ex->getMessage();
                if (str_contains($msg, 'Duplicate entry') && str_contains($msg, "employees.email")) {
                    $error = 'This Email is already used by another employee.';
                } elseif (str_contains($msg, 'Duplicate entry') && str_contains($msg, "employees.emp_code")) {
                    $error = 'This Employee Code already exists.';
                } else {
                    $error = 'Could not save employee. ' . $msg;
                }
                $employee = compact('id','emp_code','name','first_name','last_name','email','phone','address','position','role','license_number','license_expiry','vehicle_id','branch_id','join_date','status');
                $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
                try { $vehiclesAll = $pdo->query('SELECT id FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); } catch (Throwable $e) { $vehiclesAll = []; }
                Helpers::view('employees/form', compact('employee','branchesAll','vehiclesAll','error'));
                break;
            }
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) { $pdo->prepare('DELETE FROM employees WHERE id=?')->execute([$id]); }
            Helpers::redirect('index.php?page=employees');
            break;
        }

        if ($action === 'new') {
            $employee = [
                'id'=>0,
                'name'=>'',
                'position'=>'',
                'branch_id'=>0
            ];
            $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
            // Load vehicles for dropdown (use reg_number explicitly)
            try { $vehiclesAll = $pdo->query('SELECT id, reg_number AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); } catch (Throwable $e) { $vehiclesAll = []; }
            $rolesDynamic = [];
            try { $rolesDynamic = $pdo->query("SELECT DISTINCT role FROM employees WHERE role IS NOT NULL AND role<>'' ORDER BY role")->fetchAll(); } catch (Throwable $e) {}
            Helpers::view('employees/form', compact('employee','branchesAll','vehiclesAll','rolesDynamic'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM employees WHERE id=?');
            $stmt->execute([$id]);
            $employee = $stmt->fetch();
            if (!$employee) { http_response_code(404); echo 'Not found'; break; }
            $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
            // Load vehicles for dropdown (id + best-guess number)
            try { $vehiclesAll = $pdo->query('SELECT id, COALESCE(vehicle_no, reg_number, plate_no) AS vehicle_no FROM vehicles ORDER BY id DESC LIMIT 500')->fetchAll(); } catch (Throwable $e) { $vehiclesAll = []; }
            Helpers::view('employees/form', compact('employee','branchesAll','vehiclesAll'));
            break;
        }

        // payroll sub-view: list latest payroll row per employee (with simple filters)
        if ($action === 'payroll') {
            // Filters
            $emp_code = trim($_GET['emp_code'] ?? '');
            $name = trim($_GET['name'] ?? '');
            $position = trim($_GET['position'] ?? '');
            $branch_id = (int)($_GET['branch_id'] ?? 0);
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
            $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
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

        // index - filters for many fields
        $emp_code = trim($_GET['emp_code'] ?? '');
        $name = trim($_GET['name'] ?? '');
        $first_name = trim($_GET['first_name'] ?? '');
        $last_name = trim($_GET['last_name'] ?? '');
        $email = trim($_GET['email'] ?? '');
        $phone = trim($_GET['phone'] ?? '');
        $address = trim($_GET['address'] ?? '');
        $position = trim($_GET['position'] ?? '');
        $role = trim($_GET['role'] ?? '');
        $license_number = trim($_GET['license_number'] ?? '');
        $license_from = trim($_GET['license_from'] ?? '');
        $license_to = trim($_GET['license_to'] ?? '');
        $vehicle_like = trim($_GET['vehicle'] ?? '');
        $branch_id = (int)($_GET['branch_id'] ?? 0);
        $join_from = trim($_GET['join_from'] ?? '');
        $join_to = trim($_GET['join_to'] ?? '');
        $status = trim($_GET['status'] ?? ''); // '', 'active', 'inactive'

        $sql = 'SELECT e.*, b.name AS branch_name, v.id AS vehicle_id_join, v.reg_number AS vehicle_no_join
                FROM employees e
                LEFT JOIN branches b ON b.id = e.branch_id
                LEFT JOIN vehicles v ON v.id = e.vehicle_id
                WHERE 1=1';
        $params = [];
        if ($emp_code !== '') { $sql .= ' AND e.emp_code LIKE ?'; $params[] = "%$emp_code%"; }
        if ($name !== '') { $sql .= ' AND e.name LIKE ?'; $params[] = "%$name%"; }
        if ($first_name !== '') { $sql .= ' AND e.first_name LIKE ?'; $params[] = "%$first_name%"; }
        if ($last_name !== '') { $sql .= ' AND e.last_name LIKE ?'; $params[] = "%$last_name%"; }
        if ($email !== '') { $sql .= ' AND e.email LIKE ?'; $params[] = "%$email%"; }
        if ($phone !== '') { $sql .= ' AND e.phone LIKE ?'; $params[] = "%$phone%"; }
        if ($address !== '') { $sql .= ' AND e.address LIKE ?'; $params[] = "%$address%"; }
        if ($position !== '') { $sql .= ' AND e.position LIKE ?'; $params[] = "%$position%"; }
        if ($role !== '') { $sql .= ' AND e.role LIKE ?'; $params[] = "%$role%"; }
        if ($license_number !== '') { $sql .= ' AND e.license_number LIKE ?'; $params[] = "%$license_number%"; }
        if ($license_from !== '') { $sql .= ' AND e.license_expiry >= ?'; $params[] = $license_from; }
        if ($license_to !== '') { $sql .= ' AND e.license_expiry <= ?'; $params[] = $license_to; }
        if ($vehicle_like !== '') { $sql .= ' AND COALESCE(v.reg_number, v.plate_no, v.vehicle_no, "") LIKE ?'; $params[] = "%$vehicle_like%"; }
        if ($branch_id > 0) { $sql .= ' AND e.branch_id = ?'; $params[] = $branch_id; }
        if ($join_from !== '') { $sql .= ' AND e.join_date >= ?'; $params[] = $join_from; }
        if ($join_to !== '') { $sql .= ' AND e.join_date <= ?'; $params[] = $join_to; }
        if ($status !== '') { $sql .= ' AND e.status = ?'; $params[] = $status; }
        $sql .= ' ORDER BY e.created_at DESC, e.id DESC LIMIT 500';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $employees = $stmt->fetchAll();
        $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
        Helpers::view('employees/index', compact('employees','emp_code','name','first_name','last_name','email','phone','address','position','role','license_number','license_from','license_to','vehicle_like','branch_id','join_from','join_to','status','branchesAll'));
        break;

    case 'salaries':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $user = Auth::user();
        $isAdmin = Auth::hasRole('admin');
        $isAccountant = (isset($user['role']) && $user['role'] === 'accountant');
        if (!($isAdmin || $isAccountant)) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';

        if ($action === 'generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $year = (int)($_POST['year'] ?? date('Y'));
            $month_num = (int)($_POST['month_num'] ?? date('n'));
            // Generate salary entries for all employees if missing
            $sqlEmpGen = "
                SELECT e.id,
                       COALESCE(ep.basic_salary, 0) AS basic_salary
                FROM employees e
                LEFT JOIN (
                  SELECT ep1.employee_id, ep1.basic_salary
                  FROM employee_payroll ep1
                  INNER JOIN (
                    SELECT employee_id, MAX(month_year) AS mm
                    FROM employee_payroll
                    GROUP BY employee_id
                  ) m ON m.employee_id = ep1.employee_id AND m.mm = ep1.month_year
                ) ep ON ep.employee_id = e.id";
            try {
                $empStmt = $pdo->query($sqlEmpGen);
                $employees = $empStmt->fetchAll();
            } catch (Throwable $e) { $employees = []; }
            $ins = $pdo->prepare('INSERT IGNORE INTO salaries (employee_id, month, month_num, amount, status) VALUES (?,?,?,?,\'pending\')');
            foreach ($employees as $e) {
                $amt = (float)($e['basic_salary'] ?? 0);
                $ins->execute([(int)$e['id'], $year, $month_num, $amt]);
            }
            Helpers::redirect('index.php?page=salaries&year=' . $year . '&month_num=' . $month_num);
            break;
        }

        if ($action === 'pay' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
            if ($id > 0) {
                $pdo->prepare("UPDATE salaries SET status='paid', payment_date=? WHERE id=?")->execute([$payment_date, $id]);
            }
            Helpers::redirect('index.php?page=salaries');
            break;
        }

        // index with OPTIONAL filters: Year, Month, Branch, Employee, Position, Payment Date range
        $year = (int)($_GET['year'] ?? 0);
        $month_num = (int)($_GET['month_num'] ?? 0);
        if ($year <= 0) { $year = (int)date('Y'); }
        if ($month_num <= 0) { $month_num = (int)date('n'); }
        $branchFilter = (int)($_GET['branch_id'] ?? 0);
        $employeeFilter = trim($_GET['employee'] ?? '');
        $positionFilter = trim($_GET['position'] ?? '');
        $payFrom = trim($_GET['pay_from'] ?? '');
        $payTo = trim($_GET['pay_to'] ?? '');
        $where = [];
        $params = [];
        if ($year > 0) { $where[] = 's.month = ?'; $params[] = $year; }
        if ($month_num > 0) { $where[] = 's.month_num = ?'; $params[] = $month_num; }
        if ($branchFilter > 0) { $where[] = 'e.branch_id = ?'; $params[] = $branchFilter; }
        if ($employeeFilter !== '') { $where[] = 'e.name LIKE ?'; $params[] = "%$employeeFilter%"; }
        if ($positionFilter !== '') { $where[] = 'e.position LIKE ?'; $params[] = "%$positionFilter%"; }
        if ($payFrom !== '') { $where[] = '(s.payment_date IS NOT NULL AND s.payment_date >= ?)'; $params[] = $payFrom; }
        if ($payTo !== '') { $where[] = '(s.payment_date IS NOT NULL AND s.payment_date <= ?)'; $params[] = $payTo; }

        // Ensure salaries exist for selected Year/Month so that all employees show up
        if ($year > 0 && $month_num > 0) {
            // Insert missing rows as pending with each employee's latest basic_salary from employee_payroll (fallback 0)
            $sqlEmp = "
                SELECT e.id,
                       COALESCE(ep.basic_salary, 0) AS basic_salary
                FROM employees e
                LEFT JOIN (
                  SELECT ep1.employee_id, ep1.basic_salary
                  FROM employee_payroll ep1
                  INNER JOIN (
                    SELECT employee_id, MAX(month_year) AS mm
                    FROM employee_payroll
                    GROUP BY employee_id
                  ) m ON m.employee_id = ep1.employee_id AND m.mm = ep1.month_year
                ) ep ON ep.employee_id = e.id";
            try {
                $empStmt = $pdo->query($sqlEmp);
                $employeesForGen = $empStmt->fetchAll();
            } catch (Throwable $e) { $employeesForGen = []; }
            if ($employeesForGen) {
                $ins = $pdo->prepare("INSERT IGNORE INTO salaries (employee_id, month, month_num, amount, status) VALUES (?,?,?,?, 'pending')");
                foreach ($employeesForGen as $eRow) {
                    $amt = (float)($eRow['basic_salary'] ?? 0);
                    $ins->execute([(int)$eRow['id'], $year, $month_num, $amt]);
                }
            }
        }
        $sql = 'SELECT s.*, e.name AS employee_name, e.position, b.name AS branch_name
                FROM salaries s
                LEFT JOIN employees e ON e.id = s.employee_id
                LEFT JOIN branches b ON b.id = e.branch_id';
        if (!empty($where)) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY s.month DESC, s.month_num DESC, b.name, e.name LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
        // Totals for current result set
        $total = 0; $paid = 0; $countTotal = 0; $countPaid = 0; $countPending = 0;
        foreach ($rows as $r) {
            $amt = (float)$r['amount'];
            $total += $amt; $countTotal++;
            if ($r['status'] === 'paid') { $paid += $amt; $countPaid++; } else { $countPending++; }
        }

        // Totals by branch for CURRENT date filters (include ALL branches even if zero)
        // Apply ONLY year/month filters to salaries; do NOT apply branch filter here so every branch (e.g., Mullaitivu) appears
        $sqlBranch = 'SELECT b.id AS branch_id, b.name AS branch_name,
                             COALESCE(SUM(s.amount),0) AS total,
                             COALESCE(SUM(CASE WHEN s.status="paid" THEN s.amount ELSE 0 END),0) AS paid,
                             COALESCE(SUM(CASE WHEN s.status<>"paid" AND s.status IS NOT NULL THEN s.amount ELSE 0 END),0) AS pending
                      FROM branches b
                      LEFT JOIN employees e ON e.branch_id = b.id
                      LEFT JOIN (
                          SELECT * FROM salaries 
                          WHERE 1=1';
        $paramsB = [];
        if ($year > 0) { $sqlBranch .= ' AND month = ?'; $paramsB[] = $year; }
        if ($month_num > 0) { $sqlBranch .= ' AND month_num = ?'; $paramsB[] = $month_num; }
        $sqlBranch .= ' ) s ON s.employee_id = e.id';
        // No WHERE clause here to ensure all branches appear
        $sqlBranch .= ' GROUP BY b.id, b.name ORDER BY b.name';
        $stmtB = $pdo->prepare($sqlBranch);
        $stmtB->execute($paramsB);
        $byBranchTotals = $stmtB->fetchAll();

        // Status counts for CURRENT FILTERS (only for non-NULL status)
        $sqlStatus = 'SELECT s.status, COUNT(*) AS c
                      FROM salaries s JOIN employees e ON e.id = s.employee_id';
        $whereStatus = [];
        $paramsStatus = [];
        if ($year > 0) { $whereStatus[] = 's.month = ?'; $paramsStatus[] = $year; }
        if ($month_num > 0) { $whereStatus[] = 's.month_num = ?'; $paramsStatus[] = $month_num; }
        if ($branchFilter > 0) { $whereStatus[] = 'e.branch_id = ?'; $paramsStatus[] = $branchFilter; }
        if (!empty($whereStatus)) { $sqlStatus .= ' WHERE ' . implode(' AND ', $whereStatus); }
        $sqlStatus .= ' AND s.status IS NOT NULL GROUP BY s.status';
        $stmtS = $pdo->prepare($sqlStatus);
        $stmtS->execute($paramsStatus);
        $statusCounts = [];
        foreach ($stmtS->fetchAll() as $r) { $statusCounts[$r['status']] = (int)$r['c']; }

        // Last 6 months trend (optionally filtered by branch only)
        $trendParams = [];
        $sqlTrend = 'SELECT s.month, s.month_num,
                            COALESCE(SUM(s.amount),0) AS total,
                            COALESCE(SUM(CASE WHEN s.status="paid" THEN s.amount ELSE 0 END),0) AS paid
                     FROM salaries s
                     JOIN employees e ON e.id = s.employee_id';
        $trendWhere = [];
        if ($branchFilter > 0) { $trendWhere[] = 'e.branch_id = ?'; $trendParams[] = $branchFilter; }
        if (!empty($trendWhere)) { $sqlTrend .= ' WHERE ' . implode(' AND ', $trendWhere); }
        $sqlTrend .= ' GROUP BY s.month, s.month_num ORDER BY s.month DESC, s.month_num DESC LIMIT 6';
        $stmtT = $pdo->prepare($sqlTrend);
        $stmtT->execute($trendParams);
        $trend = $stmtT->fetchAll();

        Helpers::view('salaries/index', compact('rows','year','month_num','branchFilter','branchesAll','total','paid','countTotal','countPaid','countPending','byBranchTotals','statusCounts','trend','employeeFilter','positionFilter','payFrom','payTo'));
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
        $pdo = Database::pdo();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        $branchId = (int)($_GET['branch_id'] ?? 0);
        $supplierId = (int)($_GET['supplier_id'] ?? 0);
        $export = $_GET['export'] ?? '';
        $type = $_GET['type'] ?? '';

        // Revenue by branch (include ALL branches even if no data)
        $revSql = 'SELECT b.id AS branch_id, b.name AS branch_name, 
                          COALESCE(SUM(CASE WHEN dn.delivery_date BETWEEN ? AND ? THEN dn.total_amount ELSE 0 END), 0) AS revenue
                   FROM branches b
                   LEFT JOIN delivery_notes dn ON dn.branch_id = b.id ';
        $revWhere = [];
        $revParams = [$from, $to];
        if ($branchId > 0) { 
            $revWhere[] = 'b.id = ?'; 
            $revParams[] = $branchId; 
        }
        if (!empty($revWhere)) { 
            $revSql .= ' WHERE ' . implode(' AND ', $revWhere); 
        }
        $revSql .= ' GROUP BY b.id, b.name ORDER BY b.name';
        $revStmt = $pdo->prepare($revSql);
        $revStmt->execute($revParams);
        $revenueByBranch = $revStmt->fetchAll();
        
        // If no branch filter, ensure all branches are included (even with 0 revenue)
        if ($branchId === 0) {
            $allBranches = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
            $branchMap = [];
            foreach ($revenueByBranch as $row) {
                $branchMap[$row['branch_id']] = $row;
            }
            $revenueByBranch = [];
            foreach ($allBranches as $branch) {
                $revenueByBranch[] = [
                    'branch_id' => $branch['id'],
                    'branch_name' => $branch['name'],
                    'revenue' => $branchMap[$branch['id']]['revenue'] ?? 0
                ];
            }
        }

        // Parcels by supplier
        $whereP = ['p.created_at BETWEEN ? AND ?'];
        $paramsP = [$from . ' 00:00:00', $to . ' 23:59:59'];
        if ($supplierId > 0) { $whereP[] = 'p.supplier_id = ?'; $paramsP[] = $supplierId; }
        if ($branchId > 0) { $whereP[] = 'p.to_branch_id = ?'; $paramsP[] = $branchId; }
        $parcSql = 'SELECT s.name AS supplier_name, COUNT(*) AS parcels_count, SUM(p.price) AS total_price
                    FROM parcels p LEFT JOIN suppliers s ON s.id=p.supplier_id
                    WHERE ' . implode(' AND ', $whereP) . ' GROUP BY s.name ORDER BY parcels_count DESC';
        $parcStmt = $pdo->prepare($parcSql);
        $parcStmt->execute($paramsP);
        $parcelsBySupplier = $parcStmt->fetchAll();

        // Expenses summary
        $whereE = ['expense_date BETWEEN ? AND ?'];
        $paramsE = [$from, $to];
        if ($branchId > 0) { $whereE[] = 'branch_id = ?'; $paramsE[] = $branchId; }
        $expSql = 'SELECT expense_type, SUM(amount) AS total FROM expenses WHERE ' . implode(' AND ', $whereE) . ' GROUP BY expense_type ORDER BY total DESC';
        $expStmt = $pdo->prepare($expSql);
        $expStmt->execute($paramsE);
        $expenseSummary = $expStmt->fetchAll();

        // Due Collections summary (payments)
        $wherePay = ['p.paid_at BETWEEN ? AND ?'];
        $paramsPay = [$from . ' 00:00:00', $to . ' 23:59:59'];
        if ($branchId > 0) { $wherePay[] = 'dn.branch_id = ?'; $paramsPay[] = $branchId; }
        $paySql = 'SELECT DATE(p.paid_at) AS pay_date, SUM(p.amount) AS collected
                   FROM payments p LEFT JOIN delivery_notes dn ON dn.id = p.delivery_note_id
                   WHERE ' . implode(' AND ', $wherePay) . ' GROUP BY DATE(p.paid_at) ORDER BY pay_date';
        $payStmt = $pdo->prepare($paySql);
        $payStmt->execute($paramsPay);
        $dueCollections = $payStmt->fetchAll();

        // Suppliers for filter dropdown
        $suppliers = $pdo->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();
        $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();

        // CSV export
        if ($export === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="report_' . $type . '_' . $from . '_to_' . $to . '.csv"');
            $out = fopen('php://output', 'w');
            if ($type === 'revenue') {
                fputcsv($out, ['Branch', 'Revenue']);
                foreach ($revenueByBranch as $r) {
                    fputcsv($out, [$r['branch_name'], $r['revenue']]);
                }
            } elseif ($type === 'parcels') {
                fputcsv($out, ['Supplier', 'Parcels Count', 'Total Price']);
                foreach ($parcelsBySupplier as $r) {
                    fputcsv($out, [$r['supplier_name'], $r['parcels_count'], $r['total_price']]);
                }
            } elseif ($type === 'expenses') {
                fputcsv($out, ['Expense Type', 'Total Amount']);
                foreach ($expenseSummary as $e) {
                    fputcsv($out, [$e['expense_type'], $e['total']]);
                }
            } else {
                fputcsv($out, ['No data']);
            }
            fclose($out);
            exit;
        }
        Helpers::view('reports/index', compact('from','to','branchId','supplierId','revenueByBranch','parcelsBySupplier','expenseSummary','dueCollections','suppliers','branchesAll'));
        break;

    case 'quick_add_customer':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method Not Allowed'; break; }
        if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
        $pdo = Database::pdo();
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $delivery_location = trim($_POST['delivery_location'] ?? '');
        $type = trim($_POST['customer_type'] ?? '');
        $wantsJson = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
                     || (strpos(($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false)
                     || (($_POST['ajax'] ?? '') === '1');
        if ($name === '') {
            if ($wantsJson) { header('Content-Type: application/json'); echo json_encode(['error'=>'Name is required.']); return; }
            Helpers::redirect('index.php?page=parcels&action=new');
            break;
        }
        // Check if exists
        $cid = 0;
        if ($phone !== '') {
            $stmt = $pdo->prepare('SELECT id FROM customers WHERE phone=? LIMIT 1');
            $stmt->execute([$phone]);
            $existing = $stmt->fetch();
            if ($existing) { $cid = (int)$existing['id']; }
        }
        if ($cid === 0) {
            // allow null phone
            try { $pdo->exec("ALTER TABLE customers MODIFY phone VARCHAR(20) NULL"); } catch (Throwable $e) { /* ignore */ }
            $ins = $pdo->prepare('INSERT INTO customers (name, phone, address, delivery_location, customer_type) VALUES (?,?,?,?,?)');
            $ins->execute([$name, ($phone!==''?$phone:null), $address, $delivery_location, $type !== '' ? $type : null]);
            $cid = (int)$pdo->lastInsertId();
        }
        if ($wantsJson) { header('Content-Type: application/json'); echo json_encode(['id'=>$cid,'name'=>$name,'phone'=>$phone,'address'=>$address,'delivery_location'=>$delivery_location]); return; }
        Helpers::redirect('index.php?page=parcels&action=new&customer_id=' . $cid);
        break;

    default:
        http_response_code(404);
        echo 'Page not found';
        break;
}
