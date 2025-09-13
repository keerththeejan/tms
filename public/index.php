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
        Helpers::view('auth/login');
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
        $dueSql = "SELECT COALESCE(SUM(dn.total_amount - COALESCE(paid.total_paid,0)),0) AS total_due
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

        // If main branch, also compute per-branch aggregates
        $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
        $pendingByBranch = [];
        $dueByBranch = [];
        $todayParcelsByBranch = [];
        $collectionsTodayByBranch = [];
        $expensesTodayByBranch = [];
        if ($isMain) {
            $q1 = $pdo->query("SELECT to_branch_id AS branch_id, COUNT(*) AS c FROM parcels WHERE status='pending' GROUP BY to_branch_id");
            foreach ($q1->fetchAll() as $r) { $pendingByBranch[(int)$r['branch_id']] = (int)$r['c']; }
            $q2 = $pdo->prepare("SELECT dn.branch_id, COALESCE(SUM(dn.total_amount - COALESCE(paid.total_paid,0)),0) AS due
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
        }

        // Customers list for filter
        $customersAll = $pdo->query('SELECT id, name, phone FROM customers ORDER BY name LIMIT 500')->fetchAll();

        Helpers::view('dashboard', compact('pendingParcels','totalDue','todayParcels','today','collectionsToday','expensesToday','recentPayments','isMain','branchesAll','pendingByBranch','dueByBranch','todayParcelsByBranch','collectionsTodayByBranch','expensesTodayByBranch','df','dt','fb','tb','cust','customersAll'));
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
            Helpers::redirect('index.php?page=branches');
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
                $pdo->prepare('DELETE FROM branches WHERE id=?')->execute([$id]);
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
            $username = trim($_POST['username'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $role = $_POST['role'] ?? 'staff';
            $branch_id = (int)($_POST['branch_id'] ?? 0);
            $active = isset($_POST['active']) ? 1 : 0;
            $password = $_POST['password'] ?? '';
            if ($username === '' || $full_name === '' || !in_array($role, ['admin','staff','accountant','cashier','collector','parcel_user'], true)) {
                $error = 'Username, Full Name and valid Role are required.';
                $userRow = compact('id','username','full_name','role','branch_id','active');
                Helpers::view('users/form', compact('userRow','branchesAll','error'));
                break;
            }
            if ($id > 0) {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare('UPDATE users SET username=?, full_name=?, role=?, branch_id=?, active=?, password_hash=? WHERE id=?');
                    $stmt->execute([$username,$full_name,$role,($branch_id>0?$branch_id:null),$active,$hash,$id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET username=?, full_name=?, role=?, branch_id=?, active=? WHERE id=?');
                    $stmt->execute([$username,$full_name,$role,($branch_id>0?$branch_id:null),$active,$id]);
                }
            } else {
                if ($password === '') { $error = 'Password is required for new user.'; $userRow = compact('id','username','full_name','role','branch_id','active'); Helpers::view('users/form', compact('userRow','branchesAll','error')); break; }
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO users (username, full_name, role, branch_id, active, password_hash) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$username,$full_name,$role,($branch_id>0?$branch_id:null),$active,$hash]);
            }
            Helpers::redirect('index.php?page=users');
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
            $userRow = ['id'=>0,'username'=>'','full_name'=>'','role'=>'staff','branch_id'=>0,'active'=>1];
            Helpers::view('users/form', compact('userRow','branchesAll'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id=?');
            $stmt->execute([$id]);
            $userRow = $stmt->fetch();
            if (!$userRow) { http_response_code(404); echo 'Not found'; break; }
            Helpers::view('users/form', compact('userRow','branchesAll'));
            break;
        }

        $users = $pdo->query('SELECT u.*, b.name AS branch_name FROM users u LEFT JOIN branches b ON b.id = u.branch_id ORDER BY u.created_at DESC')->fetchAll();
        Helpers::view('users/index', compact('users'));
        break;

    case 'customers':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';
        $pdo = Database::pdo();

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $delivery_location = trim($_POST['delivery_location'] ?? '');
            $place_id = trim($_POST['place_id'] ?? '');
            $lat = ($_POST['lat'] ?? '') !== '' ? (float)$_POST['lat'] : null;
            $lng = ($_POST['lng'] ?? '') !== '' ? (float)$_POST['lng'] : null;
            $customer_type = $_POST['customer_type'] ?? null;
            if ($name === '' || $phone === '') { $error = 'Name and Phone are required.'; $customer = compact('id','name','phone','address','delivery_location','place_id','lat','lng','customer_type'); Helpers::view('customers/form', compact('customer','error')); break; }
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE customers SET name=?, phone=?, address=?, delivery_location=?, place_id=?, lat=?, lng=?, customer_type=? WHERE id=?');
                $stmt->execute([$name,$phone,$address,$delivery_location,($place_id!==''?$place_id:null),$lat,$lng,$customer_type,$id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO customers (name, phone, address, delivery_location, place_id, lat, lng, customer_type) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->execute([$name,$phone,$address,$delivery_location,($place_id!==''?$place_id:null),$lat,$lng,$customer_type]);
                $id = (int)$pdo->lastInsertId();
            }
            Helpers::redirect('index.php?page=customers');
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                // Consider FK constraints with parcels; this may fail if referenced
                $pdo->prepare('DELETE FROM customers WHERE id=?')->execute([$id]);
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

        // index with optional phone search
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone LIKE ? OR name LIKE ? ORDER BY created_at DESC LIMIT 100");
            $like = "%$q%";
            $stmt->execute([$like,$like]);
            $customers = $stmt->fetchAll();
        } else {
            $customers = $pdo->query('SELECT * FROM customers ORDER BY created_at DESC LIMIT 100')->fetchAll();
        }
        Helpers::view('customers/index', compact('customers','q'));
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
            $phone = trim($_POST['phone'] ?? '');
            $branch_id = (int)($_POST['branch_id'] ?? 0);
            $supplier_code = trim($_POST['supplier_code'] ?? '');
            if ($name === '' || $branch_id <= 0) {
                $error = 'Name and Branch are required.';
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

        // index with optional search by name/code/phone
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $stmt = $pdo->prepare("SELECT s.*, b.name AS branch_name FROM suppliers s LEFT JOIN branches b ON b.id = s.branch_id WHERE s.name LIKE ? OR s.phone LIKE ? OR s.supplier_code LIKE ? ORDER BY s.created_at DESC LIMIT 100");
            $like = "%$q%";
            $stmt->execute([$like,$like,$like]);
            $suppliers = $stmt->fetchAll();
        } else {
            $suppliers = $pdo->query('SELECT s.*, b.name AS branch_name FROM suppliers s LEFT JOIN branches b ON b.id = s.branch_id ORDER BY s.created_at DESC LIMIT 100')->fetchAll();
        }
        Helpers::view('suppliers/index', compact('suppliers','q'));
        break;

    case 'parcels':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        if (!Auth::hasAnyRole(['admin','parcel_user','staff'])) { http_response_code(403); echo 'Forbidden'; break; }
        $action = $_GET['action'] ?? 'index';
        $pdo = Database::pdo();
        $user = Auth::user();
        $isMain = (bool)($user['is_main_branch'] ?? false);

        // data for forms
        $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
        $customersAll = $pdo->query('SELECT id, name, phone, delivery_location, lat, lng FROM customers ORDER BY created_at DESC LIMIT 500')->fetchAll();
        $suppliersAll = $pdo->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $customer_id = (int)($_POST['customer_id'] ?? 0);
            $supplier_id = (int)($_POST['supplier_id'] ?? 0);
            if ($supplier_id <= 0) { $supplier_id = null; }
            $from_branch_id = (int)($_POST['from_branch_id'] ?? 0);
            $to_branch_id = (int)($_POST['to_branch_id'] ?? 0);
            $weight = (float)($_POST['weight'] ?? 0);
            $status = $_POST['status'] ?? 'pending';
            $tracking_number = trim($_POST['tracking_number'] ?? '');
            $vehicle_no = trim($_POST['vehicle_no'] ?? '');
            $items = $_POST['items'] ?? [];
            // Price only from Main Branch
            $price = null;
            if ($isMain) {
                // If items are provided, compute price from items; else fallback to posted price
                $sum = 0.0;
                $sumQty = 0.0;
                if (is_array($items)) {
                    foreach ($items as $it) {
                        $q = (float)($it['qty'] ?? 0);
                        $r = (float)($it['rate'] ?? 0);
                        $sum += $q * $r;
                        $sumQty += $q;
                    }
                }
                // derive total weight as sum of quantities if provided
                if ($sumQty > 0) { $weight = $sumQty; }
                if ($sum > 0) {
                    $price = $sum;
                } else {
                    $priceRaw = trim($_POST['price'] ?? '');
                    $price = ($priceRaw === '') ? null : (float)$priceRaw;
                }
            }

            if ($customer_id <= 0 || $from_branch_id <= 0 || $to_branch_id <= 0) {
                $error = 'Customer, From Branch and To Branch are required.';
                $parcel = compact('id','customer_id','supplier_id','from_branch_id','to_branch_id','weight','status','tracking_number','vehicle_no');
                Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','error','isMain','items'));
                break;
            }

            $allowedStatus = ['pending','in_transit','delivered'];
            if (!in_array($status, $allowedStatus, true)) { $status = 'pending'; }

            $pdo->beginTransaction();
            if ($id > 0) {
                if ($isMain) {
                    $stmt = $pdo->prepare('UPDATE parcels SET customer_id=?, supplier_id=?, from_branch_id=?, to_branch_id=?, weight=?, price=?, status=?, tracking_number=?, vehicle_no=? WHERE id=?');
                    $stmt->execute([$customer_id,$supplier_id,$from_branch_id,$to_branch_id,$weight,$price,$status,$tracking_number,$vehicle_no,$id]);
                } else {
                    // Non-main branches cannot change price
                    $stmt = $pdo->prepare('UPDATE parcels SET customer_id=?, supplier_id=?, from_branch_id=?, to_branch_id=?, weight=?, status=?, tracking_number=?, vehicle_no=? WHERE id=?');
                    $stmt->execute([$customer_id,$supplier_id,$from_branch_id,$to_branch_id,$weight,$status,$tracking_number,$vehicle_no,$id]);
                }
                // Replace items
                $pdo->prepare('DELETE FROM parcel_items WHERE parcel_id=?')->execute([$id]);
                if (is_array($items)) {
                    $insItem = $pdo->prepare('INSERT INTO parcel_items (parcel_id, qty, description, rate) VALUES (?,?,?,?)');
                    foreach ($items as $it) {
                        $desc = trim($it['description'] ?? '');
                        $qty = (float)($it['qty'] ?? 0);
                        $rate = $isMain ? (float)($it['rate'] ?? 0) : null;
                        if ($desc !== '' && $qty > 0) {
                            $insItem->execute([$id, $qty, $desc, $rate]);
                        }
                    }
                }
            } else {
                if ($isMain) {
                    $stmt = $pdo->prepare('INSERT INTO parcels (customer_id, supplier_id, from_branch_id, to_branch_id, weight, price, status, tracking_number, vehicle_no) VALUES (?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([$customer_id,$supplier_id,$from_branch_id,$to_branch_id,$weight,$price,$status,$tracking_number,$vehicle_no]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO parcels (customer_id, supplier_id, from_branch_id, to_branch_id, weight, status, tracking_number, vehicle_no) VALUES (?,?,?,?,?,?,?,?)');
                    $stmt->execute([$customer_id,$supplier_id,$from_branch_id,$to_branch_id,$weight,$status,$tracking_number,$vehicle_no]);
                }
                $id = (int)$pdo->lastInsertId();
                if (is_array($items)) {
                    $insItem = $pdo->prepare('INSERT INTO parcel_items (parcel_id, qty, description, rate) VALUES (?,?,?,?)');
                    foreach ($items as $it) {
                        $desc = trim($it['description'] ?? '');
                        $qty = (float)($it['qty'] ?? 0);
                        $rate = $isMain ? (float)($it['rate'] ?? 0) : null;
                        if ($desc !== '' && $qty > 0) {
                            $insItem->execute([$id, $qty, $desc, $rate]);
                        }
                    }
                }
            }
            $pdo->commit();
            Helpers::redirect('index.php?page=parcels');
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare('DELETE FROM parcels WHERE id=?')->execute([$id]);
            }
            Helpers::redirect('index.php?page=parcels');
            break;
        }

        if ($action === 'new') {
            $parcel = [
                'id'=>0,
                'customer_id'=>0,
                'supplier_id'=>0,
                'from_branch_id'=>0,
                'to_branch_id'=>0,
                'weight'=>0,
                'price'=>null,
                'status'=>'pending',
                'tracking_number'=>'',
                'vehicle_no'=>''
            ];
            $pre = (int)($_GET['customer_id'] ?? 0);
            if ($pre > 0) { $parcel['customer_id'] = $pre; }
            $items = [];
            Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','isMain','items'));
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
            Helpers::view('parcels/form', compact('parcel','branchesAll','customersAll','suppliersAll','isMain','items'));
            break;
        }

        // index with search filters
        $q = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
        $vehicle_no = trim($_GET['vehicle_no'] ?? '');
        $customer_filter_id = (int)($_GET['customer_id'] ?? 0);
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
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
        $sql = 'SELECT p.*, c.name AS customer_name, c.phone AS customer_phone, s.name AS supplier_name, bf.name AS from_branch, bt.name AS to_branch
                FROM parcels p
                LEFT JOIN customers c ON c.id = p.customer_id
                LEFT JOIN suppliers s ON s.id = p.supplier_id
                LEFT JOIN branches bf ON bf.id = p.from_branch_id
                LEFT JOIN branches bt ON bt.id = p.to_branch_id';
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY p.created_at DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $parcels = $stmt->fetchAll();
        // customers for filter
        $customersList = $pdo->query('SELECT id, name, phone FROM customers ORDER BY name LIMIT 500')->fetchAll();
        // branches for filter
        $branchesFilterList = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
        Helpers::view('parcels/index', compact('parcels','q','status','vehicle_no','customer_filter_id','customersList','from','to','to_branch_filter_id','branchesFilterList','isMain'));
        break;

    case 'parcel_print':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT p.*, c.name AS customer_name, c.phone AS customer_phone, bf.name AS from_branch, bt.name AS to_branch FROM parcels p LEFT JOIN customers c ON c.id=p.customer_id LEFT JOIN branches bf ON bf.id=p.from_branch_id LEFT JOIN branches bt ON bt.id=p.to_branch_id WHERE p.id=?');
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

        if ($action === 'generate') {
            // show form to pick customer and date, and POST to create
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
                $customer_id = (int)($_POST['customer_id'] ?? 0);
                $delivery_date = $_POST['delivery_date'] ?? date('Y-m-d');
                if ($customer_id <= 0) { $error = 'Select a customer.'; }
                if (!empty($error)) {
                    $customersAll = $pdo->query('SELECT id, name, phone FROM customers ORDER BY name')->fetchAll();
                    Helpers::view('delivery_notes/generate', compact('customersAll','error'));
                    break;
                }

                // Find parcels for that customer/date not already assigned, destined to this branch
                $stmt = $pdo->prepare("SELECT p.* FROM parcels p
                    LEFT JOIN delivery_note_parcels dnp ON dnp.parcel_id = p.id
                    WHERE p.customer_id = ? AND DATE(p.created_at) = ? AND p.to_branch_id = ? AND dnp.id IS NULL");
                $stmt->execute([$customer_id, $delivery_date, $branchId]);
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
                foreach ($rows as $r) {
                    $amount = (float)($r['price'] ?? 0);
                    // Try insert; ignore if already added due to race
                    $ins = $pdo->prepare('INSERT IGNORE INTO delivery_note_parcels (delivery_note_id, parcel_id, amount) VALUES (?,?,?)');
                    $ins->execute([$dnId, (int)$r['id'], $amount]);
                    $total += $amount;
                }
                // Recalculate total from table to be safe
                $sum = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS s FROM delivery_note_parcels WHERE delivery_note_id=?');
                $sum->execute([$dnId]);
                $srow = $sum->fetch();
                $pdo->prepare('UPDATE delivery_notes SET total_amount=? WHERE id=?')->execute([(float)$srow['s'], $dnId]);

                Helpers::redirect('index.php?page=delivery_notes&action=view&id=' . $dnId);
                break;
            }

            $customersAll = $pdo->query('SELECT id, name, phone FROM customers ORDER BY name')->fetchAll();
            Helpers::view('delivery_notes/generate', compact('customersAll'));
            break;
        }

        if ($action === 'view') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT dn.*, c.name AS customer_name, c.phone AS customer_phone FROM delivery_notes dn LEFT JOIN customers c ON c.id = dn.customer_id WHERE dn.id=?');
            $stmt->execute([$id]);
            $dn = $stmt->fetch();
            if (!$dn) { http_response_code(404); echo 'Not found'; break; }
            $items = $pdo->prepare('SELECT dnp.*, p.tracking_number, p.weight, s.name AS supplier_name FROM delivery_note_parcels dnp LEFT JOIN parcels p ON p.id = dnp.parcel_id LEFT JOIN suppliers s ON s.id = p.supplier_id WHERE dnp.delivery_note_id=?');
            $items->execute([$id]);
            $items = $items->fetchAll();
            Helpers::view('delivery_notes/show', compact('dn','items'));
            break;
        }

        if ($action === 'print') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT dn.*, c.name AS customer_name, c.phone AS customer_phone, b.name AS branch_name FROM delivery_notes dn LEFT JOIN customers c ON c.id = dn.customer_id LEFT JOIN branches b ON b.id=dn.branch_id WHERE dn.id=?');
            $stmt->execute([$id]);
            $dn = $stmt->fetch();
            if (!$dn) { http_response_code(404); echo 'Not found'; break; }
            $items = $pdo->prepare('SELECT dnp.*, p.tracking_number, p.weight, s.name AS supplier_name FROM delivery_note_parcels dnp LEFT JOIN parcels p ON p.id = dnp.parcel_id LEFT JOIN suppliers s ON s.id = p.supplier_id WHERE dnp.delivery_note_id=?');
            $items->execute([$id]);
            $items = $items->fetchAll();
            include __DIR__ . '/../views/delivery_notes/print.php';
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
        $sql = 'SELECT dn.*, c.name AS customer_name, c.phone AS customer_phone FROM delivery_notes dn LEFT JOIN customers c ON c.id = dn.customer_id WHERE ' . implode(' AND ', $where) . ' ORDER BY dn.delivery_date DESC, dn.id DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $notes = $stmt->fetchAll();
        Helpers::view('delivery_notes/index', compact('notes','from','to','q'));
        break;

    case 'payments':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        if (!Auth::hasAnyRole(['admin','accountant','cashier','collector'])) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $user = Auth::user();
        $branchId = (int)($user['branch_id'] ?? 0);
        $action = $_GET['action'] ?? 'index';

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $dnId = (int)($_POST['delivery_note_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $paid_at = $_POST['paid_at'] ?? date('Y-m-d H:i:s');
            if ($dnId <= 0 || $amount <= 0) { $error = 'Valid delivery note and positive amount required.'; }
            // fetch DN and due
            $stmt = $pdo->prepare('SELECT dn.*, COALESCE((SELECT SUM(amount) FROM payments WHERE delivery_note_id=dn.id),0) AS paid FROM delivery_notes dn WHERE dn.id=?');
            $stmt->execute([$dnId]);
            $dn = $stmt->fetch();
            if (!$dn) { http_response_code(404); echo 'Delivery note not found'; break; }
            $due = (float)$dn['total_amount'] - (float)$dn['paid'];
            if ($amount > $due) { $amount = $due; }
            if (!empty($error) || $amount <= 0) {
                $payment = ['delivery_note_id'=>$dnId,'amount'=>$amount,'paid_at'=>$paid_at];
                Helpers::view('payments/form', compact('payment','dn','error'));
                break;
            }
            $ins = $pdo->prepare('INSERT INTO payments (delivery_note_id, amount, paid_at, received_by) VALUES (?,?,?,?)');
            $ins->execute([$dnId, $amount, $paid_at, (int)($user['id'] ?? null)]);
            Helpers::redirect('index.php?page=payments');
            break;
        }

        if ($action === 'new') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT dn.*, c.name AS customer_name, c.phone AS customer_phone, COALESCE((SELECT SUM(amount) FROM payments WHERE delivery_note_id=dn.id),0) AS paid FROM delivery_notes dn LEFT JOIN customers c ON c.id = dn.customer_id WHERE dn.id=?');
            $stmt->execute([$id]);
            $dn = $stmt->fetch();
            if (!$dn) { http_response_code(404); echo 'Delivery note not found'; break; }
            $payment = ['delivery_note_id'=>$id,'amount'=>max(0, (float)$dn['total_amount'] - (float)$dn['paid']),'paid_at'=>date('Y-m-d H:i:s')];
            Helpers::view('payments/form', compact('payment','dn'));
            break;
        }

        // index: list DNs with outstanding due for this branch, filter by date range and search
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        $q = trim($_GET['q'] ?? '');
        $sql = 'SELECT dn.*, c.name AS customer_name, c.phone AS customer_phone,
                       (dn.total_amount - COALESCE(paid.total_paid,0)) AS due,
                       COALESCE(paid.total_paid,0) AS paid
                FROM delivery_notes dn
                LEFT JOIN customers c ON c.id = dn.customer_id
                LEFT JOIN (
                    SELECT delivery_note_id, SUM(amount) AS total_paid
                    FROM payments GROUP BY delivery_note_id
                ) paid ON paid.delivery_note_id = dn.id
                WHERE dn.branch_id = ? AND dn.delivery_date BETWEEN ? AND ?';
        $params = [$branchFilterId, $from, $to];
        if ($q !== '') {
            $sql .= ' AND (c.phone LIKE ? OR c.name LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like);
        }
        $sql .= ' HAVING due > 0 ORDER BY dn.delivery_date DESC, dn.id DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $dues = $stmt->fetchAll();
        Helpers::view('payments/index', compact('dues','from','to','q'));
        break;

    case 'expenses':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $user = Auth::user();
        $branchId = (int)($user['branch_id'] ?? 0);
        $isAdmin = Auth::hasRole('admin');
        $action = $_GET['action'] ?? 'index';

        if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            $expense_type = $_POST['expense_type'] ?? '';
            $amount = (float)($_POST['amount'] ?? 0);
            $branch_id = (int)($_POST['branch_id'] ?? $branchId);
            $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
            $notes = trim($_POST['notes'] ?? '');
            $allowedTypes = ['fuel','vehicle_maintenance','office','utilities','other'];
            if (!in_array($expense_type, $allowedTypes, true)) { $expense_type = 'other'; }
            if ($amount <= 0 || $branch_id <= 0) { $error = 'Amount and Branch are required.'; }
            if (!empty($error)) {
                $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
                $expense = compact('id','expense_type','amount','branch_id','expense_date','notes');
                Helpers::view('expenses/form', compact('expense','branchesAll','error'));
                break;
            }
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE expenses SET expense_type=?, amount=?, branch_id=?, expense_date=?, notes=? WHERE id=?');
                $stmt->execute([$expense_type,$amount,$branch_id,$expense_date,$notes,$id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO expenses (expense_type, amount, branch_id, expense_date, notes) VALUES (?,?,?,?,?)');
                $stmt->execute([$expense_type,$amount,$branch_id,$expense_date,$notes]);
            }
            Helpers::redirect('index.php?page=expenses');
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

        if ($action === 'new') {
            $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
            $expense = ['id'=>0,'expense_type'=>'other','amount'=>'','branch_id'=>$branchId,'expense_date'=>date('Y-m-d'),'notes'=>''];
            Helpers::view('expenses/form', compact('expense','branchesAll'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM expenses WHERE id=?');
            $stmt->execute([$id]);
            $expense = $stmt->fetch();
            if (!$expense) { http_response_code(404); echo 'Not found'; break; }
            $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
            Helpers::view('expenses/form', compact('expense','branchesAll'));
            break;
        }

        // index with filters and summaries
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        $branchFilter = (int)($_GET["branch_id"] ?? 0);
        $where = ['expense_date BETWEEN ? AND ?'];
        $params = [$from, $to];
        if ($branchFilter > 0) { $where[] = 'branch_id = ?'; $params[] = $branchFilter; }
        $sql = 'SELECT e.*, b.name AS branch_name FROM expenses e LEFT JOIN branches b ON b.id = e.branch_id WHERE ' . implode(' AND ', $where) . ' ORDER BY e.expense_date DESC, e.id DESC LIMIT 300';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $expenses = $stmt->fetchAll();

        // summaries
        $sumSql = 'SELECT branch_id, SUM(amount) AS total FROM expenses WHERE ' . implode(' AND ', $where) . ' GROUP BY branch_id';
        $sumStmt = $pdo->prepare($sumSql);
        $sumStmt->execute($params);
        $byBranch = $sumStmt->fetchAll();
        $overall = 0; foreach ($byBranch as $r) { $overall += (float)$r['total']; }
        $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
        Helpers::view('expenses/index', compact('expenses','from','to','branchFilter','byBranch','overall','branchesAll','isAdmin'));
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
            $name = trim($_POST['name'] ?? '');
            $position = trim($_POST['position'] ?? '');
            $salary_amount = (float)($_POST['salary_amount'] ?? 0);
            $branch_id = (int)($_POST['branch_id'] ?? 0);
            if ($name === '' || $position === '' || $salary_amount <= 0 || $branch_id <= 0) {
                $error = 'Name, Position, Salary and Branch are required.';
                $employee = compact('id','name','position','salary_amount','branch_id');
                $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
                Helpers::view('employees/form', compact('employee','branchesAll','error'));
                break;
            }
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE employees SET name=?, position=?, salary_amount=?, branch_id=? WHERE id=?');
                $stmt->execute([$name,$position,$salary_amount,$branch_id,$id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO employees (name, position, salary_amount, branch_id) VALUES (?,?,?,?)');
                $stmt->execute([$name,$position,$salary_amount,$branch_id]);
            }
            Helpers::redirect('index.php?page=employees');
            break;
        }

        if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Helpers::verifyCsrf($_POST['csrf_token'] ?? '')) { http_response_code(400); echo 'Invalid CSRF'; break; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) { $pdo->prepare('DELETE FROM employees WHERE id=?')->execute([$id]); }
            Helpers::redirect('index.php?page=employees');
            break;
        }

        if ($action === 'new') {
            $employee = ['id'=>0,'name'=>'','position'=>'','salary_amount'=>'','branch_id'=>0];
            $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
            Helpers::view('employees/form', compact('employee','branchesAll'));
            break;
        }

        if ($action === 'edit') {
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM employees WHERE id=?');
            $stmt->execute([$id]);
            $employee = $stmt->fetch();
            if (!$employee) { http_response_code(404); echo 'Not found'; break; }
            $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
            Helpers::view('employees/form', compact('employee','branchesAll'));
            break;
        }

        // index
        $employees = $pdo->query('SELECT e.*, b.name AS branch_name FROM employees e LEFT JOIN branches b ON b.id=e.branch_id ORDER BY e.created_at DESC, e.id DESC LIMIT 300')->fetchAll();
        Helpers::view('employees/index', compact('employees'));
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
            $empStmt = $pdo->query('SELECT id, salary_amount FROM employees');
            $employees = $empStmt->fetchAll();
            $ins = $pdo->prepare('INSERT IGNORE INTO salaries (employee_id, month, month_num, amount, status) VALUES (?,?,?,?,\'pending\')');
            foreach ($employees as $e) {
                $ins->execute([(int)$e['id'], $year, $month_num, (float)$e['salary_amount']]);
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

        // index with filters
        $year = (int)($_GET['year'] ?? date('Y'));
        $month_num = (int)($_GET['month_num'] ?? date('n'));
        $branchFilter = (int)($_GET['branch_id'] ?? 0);
        $where = ['s.month = ?','s.month_num = ?'];
        $params = [$year, $month_num];
        if ($branchFilter > 0) { $where[] = 'e.branch_id = ?'; $params[] = $branchFilter; }
        $sql = 'SELECT s.*, e.name AS employee_name, e.position, b.name AS branch_name
                FROM salaries s
                LEFT JOIN employees e ON e.id = s.employee_id
                LEFT JOIN branches b ON b.id = e.branch_id
                WHERE ' . implode(' AND ', $where) . ' ORDER BY b.name, e.name';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $branchesAll = $pdo->query('SELECT id, name FROM branches ORDER BY name')->fetchAll();
        // Totals
        $total = 0; $paid = 0; foreach ($rows as $r) { $total += (float)$r['amount']; if ($r['status']==='paid') $paid += (float)$r['amount']; }
        Helpers::view('salaries/index', compact('rows','year','month_num','branchFilter','branchesAll','total','paid'));
        break;

    case 'search':
        if (!Auth::check()) { http_response_code(403); echo 'Forbidden'; break; }
        $pdo = Database::pdo();
        $phone = trim($_GET['phone'] ?? '');
        $customer = null; $parcels = []; $notes = []; $dueSummary = null;
        if ($phone !== '') {
            $stmt = $pdo->prepare('SELECT * FROM customers WHERE phone = ? LIMIT 1');
            $stmt->execute([$phone]);
            $customer = $stmt->fetch();
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
        }
        Helpers::view('search/customer', compact('phone','customer','parcels','notes','dueSummary'));
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

        // Revenue by branch (delivery notes total_amount)
        $whereDN = ['dn.delivery_date BETWEEN ? AND ?'];
        $paramsDN = [$from, $to];
        if ($branchId > 0) { $whereDN[] = 'dn.branch_id = ?'; $paramsDN[] = $branchId; }
        $revSql = 'SELECT b.name AS branch_name, SUM(dn.total_amount) AS revenue
                   FROM delivery_notes dn LEFT JOIN branches b ON b.id=dn.branch_id
                   WHERE ' . implode(' AND ', $whereDN) . ' GROUP BY b.name ORDER BY b.name';
        $revStmt = $pdo->prepare($revSql);
        $revStmt->execute($paramsDN);
        $revenueByBranch = $revStmt->fetchAll();

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
        if ($phone === '' || $name === '') {
            Helpers::redirect('index.php?page=parcels&action=new');
            break;
        }
        // Check if exists
        $stmt = $pdo->prepare('SELECT id FROM customers WHERE phone=? LIMIT 1');
        $stmt->execute([$phone]);
        $existing = $stmt->fetch();
        if ($existing) {
            $cid = (int)$existing['id'];
        } else {
            $ins = $pdo->prepare('INSERT INTO customers (name, phone, address, delivery_location, customer_type) VALUES (?,?,?,?,?)');
            $ins->execute([$name, $phone, $address, $delivery_location, $type !== '' ? $type : null]);
            $cid = (int)$pdo->lastInsertId();
        }
        Helpers::redirect('index.php?page=parcels&action=new&customer_id=' . $cid);
        break;

    default:
        http_response_code(404);
        echo 'Page not found';
        break;
}
