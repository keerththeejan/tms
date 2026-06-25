<?php
class Helpers
{
    public static function config(): array
    {
        static $config;
        if (!$config) {
            $config = require __DIR__ . '/../config/config.php';
        }
        return $config;
    }

    /**
     * Dot-notation config access, e.g. configGet('currency.symbol').
     */
    public static function configGet(string $key, mixed $default = null): mixed
    {
        $value = self::config();
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    public static function currencySymbol(): string
    {
        return (string) self::configGet('currency.symbol', 'LKR');
    }

    /** Format monetary amount as "LKR 1,000.00". */
    public static function formatMoney(float|int|string $amount, bool $withSymbol = true): string
    {
        $decimals = (int) self::configGet('currency.decimals', 2);
        $symbol = self::currencySymbol();
        $formatted = number_format((float) $amount, $decimals, '.', ',');

        return $withSymbol ? $symbol . ' ' . $formatted : $formatted;
    }

    /** JSON-safe currency settings for front-end scripts. */
    public static function currencyJsConfig(): array
    {
        return [
            'code' => (string) self::configGet('currency.code', 'LKR'),
            'symbol' => self::currencySymbol(),
            'format' => (string) self::configGet('currency.format', 'LKR'),
            'locale' => (string) self::configGet('currency.locale', 'en-LK'),
            'decimals' => (int) self::configGet('currency.decimals', 2),
        ];
    }

    public static function company(): array
    {
        $cfg = self::config();
        return is_array($cfg['company'] ?? null) ? $cfg['company'] : [];
    }

    public static function companyBranches(): array
    {
        try {
            if (class_exists('BranchRepository')) {
                $rows = BranchRepository::branchesForCompanyPrint(Database::pdo());
                if ($rows !== []) {
                    return $rows;
                }
            }
        } catch (Throwable $e) {
            // fall back to JSON
        }
        $company = self::company();
        $branches = $company['branches'] ?? [];
        return is_array($branches) ? $branches : [];
    }

    /** Normalize phone strings for display (preserve | separators, trim segments). */
    public static function formatPhonesDisplay(string $phones): string
    {
        $phones = trim(str_replace(["\r", "\n"], '', $phones));
        if ($phones === '') {
            return '';
        }
        $parts = preg_split('/\s*\|\s*/u', $phones) ?: [];
        $parts = array_filter(array_map('trim', $parts), static function ($p) {
            return $p !== '';
        });
        return implode(' | ', $parts);
    }

    public static function companyDefaultBranch(): array
    {
        try {
            if (class_exists('BranchRepository')) {
                $row = BranchRepository::getDefaultForPrint(Database::pdo());
                if ($row) {
                    return [
                        'name' => (string)($row['name'] ?? ''),
                        'address_ta' => (string)($row['address_tamil'] ?? ''),
                        'address_en' => (string)($row['address_english'] ?? ''),
                        'phones' => (string)($row['phones'] ?? ''),
                    ];
                }
            }
        } catch (Throwable $e) {
            // fall back
        }
        $branches = self::companyBranches();
        foreach ($branches as $b) {
            if (!is_array($b)) { continue; }
            $name = trim((string)($b['name'] ?? ''));
            $addrTa = trim((string)($b['address_ta'] ?? ''));
            $addrEn = trim((string)($b['address_en'] ?? ''));
            $phones = trim((string)($b['phones'] ?? ''));
            if ($name !== '' || $addrTa !== '' || $addrEn !== '' || $phones !== '') {
                return $b;
            }
        }
        return [];
    }

    /**
     * Header subtitle lines for billing / app chrome (English, default branch first).
     *
     * @param 'default'|'all' $scope default = only the branch flagged is_default; all = every active branch
     */
    public static function companyHeaderAddressLines(?string $addrOverrideParam = null, int $limit = 3, string $scope = 'default'): array
    {
        $addrOverrideParam = (string)($addrOverrideParam ?? '');
        $addrOverrideParam = str_replace(["\r"], '', $addrOverrideParam);

        $lines = [];
        if (trim($addrOverrideParam) !== '') {
            $tmp = explode("\n", $addrOverrideParam);
            foreach ($tmp as $a) {
                $a = trim((string)$a);
                if ($a !== '') { $lines[] = $a; }
            }
            return array_slice($lines, 0, max(0, $limit));
        }

        $branches = self::companyBranches();
        if (!empty($branches)) {
            if ($scope === 'default') {
                $def = self::companyDefaultBranch();
                if (!empty($def)) {
                    $b = $def;
                    $name = trim((string)($b['name'] ?? ''));
                    $addrEn = trim((string)($b['address_en'] ?? ''));
                    $phones = self::formatPhonesDisplay((string)($b['phones'] ?? ''));
                    $parts = [];
                    if ($name !== '') { $parts[] = $name; }
                    if ($addrEn !== '') { $parts[] = $addrEn; }
                    if ($phones !== '') { $parts[] = $phones; }
                    if ($parts !== []) {
                        $lines[] = implode(' — ', $parts);
                    }
                    return array_slice($lines, 0, max(0, $limit));
                }
            }
            foreach ($branches as $b) {
                if (!is_array($b)) { continue; }
                $name = trim((string)($b['name'] ?? ''));
                $addrEn = trim((string)($b['address_en'] ?? ''));
                $phones = self::formatPhonesDisplay((string)($b['phones'] ?? ''));
                $line = trim(($name !== '' ? ($name . ': ') : '') . $addrEn);
                if ($phones !== '') {
                    $line = trim($line . ' | ' . $phones);
                }
                if ($line !== '') { $lines[] = $line; }
            }
            if (!empty($lines)) {
                return array_slice($lines, 0, max(0, $limit));
            }
        }

        $company = self::company();
        $legacy = $company['addresses'] ?? [];
        if (is_array($legacy)) {
            foreach ($legacy as $a) {
                $a = trim((string)$a);
                if ($a !== '') { $lines[] = $a; }
            }
        }

        return array_slice($lines, 0, max(0, $limit));
    }

    public static function baseUrl(string $path = ''): string
    {
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        $base = rtrim($scriptName, '/\\');
        return $base . '/' . ltrim($path, '/');
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . self::baseUrl($path));
        exit;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
    }

    /** Parcel workflow statuses (DB values => display labels) */
    public static function parcelStatusMap(): array
    {
        return [
            'pending' => 'Pending',
            'in_transit' => 'In Transit',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'returned' => 'Returned',
            'failed' => 'Failed',
            'on_hold' => 'On Hold',
            'out_for_delivery' => 'Out for Delivery',
        ];
    }

    /** Ordered list of status keys for validation and select options */
    public static function parcelStatusValues(): array
    {
        return array_keys(self::parcelStatusMap());
    }

    public static function parcelStatusLabel(string $st): string
    {
        $m = self::parcelStatusMap();
        return $m[$st] ?? ucfirst(str_replace('_', ' ', $st));
    }

    /** Statuses excluded from delivery-note generation / route “open” parcel lists */
    public static function parcelStatusesExcludedFromOpenBilling(): array
    {
        return ['delivered', 'cancelled', 'returned', 'failed', 'on_hold'];
    }

    /** SQL fragment: parcel row is eligible for billing / route queues (alias p.) */
    public static function parcelSqlEligibleForOpenBilling(): string
    {
        $ex = self::parcelStatusesExcludedFromOpenBilling();
        return '(p.status IS NULL OR p.status NOT IN (\'' . implode('\',\'', $ex) . '\'))';
    }

    /** Statuses that do not block “payment after delivery” checks (delivered or terminal) */
    public static function parcelStatusesPaymentNotBlocking(): array
    {
        return ['delivered', 'cancelled', 'returned', 'failed'];
    }

    /** SQL fragment: parcel still requires delivery before payment (alias p.) */
    public static function parcelSqlBlocksPaymentUntilDelivery(): string
    {
        $ok = self::parcelStatusesPaymentNotBlocking();
        return '(p.status IS NULL OR p.status NOT IN (\'' . implode('\',\'', $ok) . '\'))';
    }

    /** CSS class for parcel list status badge (Bootstrap soft badges) */
    public static function parcelStatusBadgeClass(string $st): string
    {
        switch ($st) {
            case 'delivered':
                return 'badge-soft-success';
            case 'in_transit':
            case 'out_for_delivery':
                return 'badge-soft-info';
            case 'cancelled':
            case 'failed':
            case 'returned':
                return 'badge-soft-danger';
            case 'on_hold':
                return 'badge-soft-secondary';
            default:
                return 'badge-soft-warning';
        }
    }

    public static function view(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found: ' . htmlspecialchars($view);
            return;
        }
        include __DIR__ . '/../views/layout/header.php';
        include $viewFile;
        include __DIR__ . '/../views/layout/footer.php';
    }

    /** Accept only Y-m-d from user input; otherwise return $fallback. */
    public static function parseDateOr(string $value, string $fallback): string
    {
        $v = trim($value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : $fallback;
    }

    /** Ensure chronological order for two Y-m-d strings. */
    public static function orderDateRange(string $from, string $to): array
    {
        if ($from > $to) {
            return [$to, $from];
        }

        return [$from, $to];
    }
}

if (!function_exists('config')) {
    /**
     * Global configuration accessor.
     * Examples: config('currency_symbol'), config('currency.format')
     */
    function config(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'currency_symbol' => Helpers::currencySymbol(),
            'currency_format' => (string) Helpers::configGet('currency.format', 'LKR'),
            'currency_code' => (string) Helpers::configGet('currency.code', 'LKR'),
            'currency_locale' => (string) Helpers::configGet('currency.locale', 'en-LK'),
            default => Helpers::configGet($key, $default),
        };
    }
}
