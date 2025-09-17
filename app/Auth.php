<?php
class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function attempt(string $username, string $password): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT u.*, b.name AS branch_name, b.code AS branch_code FROM users u LEFT JOIN branches b ON b.id = u.branch_id WHERE u.username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            $_SESSION['user'] = $user;
            return true;
        }
        return false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function hasRole($role)
    {
        $user = self::user();
        return $user && isset($user['role']) && $user['role'] === $role;
    }

    public static function hasAnyRole(array $roles)
    {
        $user = self::user();
        if (!$user || !isset($user['role'])) return false;
        return in_array($user['role'], $roles, true);
    }

    public static function isMainBranch(): bool
    {
        $user = self::user();
        if (!$user) return false;
        return (bool)$user['is_main_branch'];
    }

    // ----- Centralized permission helpers -----
    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }

    // Parcels: who can create/edit parcels
    public static function canCreateParcels(): bool
    {
        // Admin and Parcel User can, allow Staff if needed for your ops
        return self::hasAnyRole(['admin','parcel_user','staff']);
    }

    // Payments: who can collect/record payments
    public static function canCollectPayments(): bool
    {
        // Admin, Accountant, Cashier, Due Collector
        return self::hasAnyRole(['admin','accountant','cashier','collector']);
    }

    // Expenses: who can create/manage expenses
    public static function canManageExpenses(): bool
    {
        // Admin and Accountant
        return self::hasAnyRole(['admin','accountant']);
    }

    // Reports: who can view reports (optional helper)
    public static function canViewReports(): bool
    {
        return self::hasAnyRole(['admin','accountant']);
    }
}
