<?php
declare(strict_types=1);

/**
 * Parcel save validation, sanitization, and activity logging (prepared statements only).
 */
class ParcelSaveService
{
    public static function sanitizeDeliveryLocation(string $raw): string
    {
        $s = trim(preg_replace('/\s+/u', ' ', $raw));
        if (function_exists('mb_substr')) {
            return mb_substr($s, 0, 120);
        }
        return substr($s, 0, 120);
    }

    public static function validateParcelPayload(float $weight, string $delivery_location, bool $priceOnlyEdit): ?string
    {
        if ($priceOnlyEdit) {
            return null;
        }
        $dl = self::sanitizeDeliveryLocation($delivery_location);
        if ($dl === '') {
            return 'Delivery location is required.';
        }
        if ($weight <= 0) {
            return 'Enter line item quantity (or total weight) greater than zero.';
        }
        return null;
    }

    public static function ensureParcelActivityLogTable(PDO $pdo): void
    {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS parcel_activity_log (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                parcel_id BIGINT UNSIGNED NOT NULL,
                action VARCHAR(40) NOT NULL,
                user_id BIGINT UNSIGNED NULL,
                meta_json TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_parcel_activity_parcel (parcel_id),
                KEY idx_parcel_activity_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Throwable $e) {
            /* ignore */
        }
    }

    public static function persistCustomerDeliveryLocation(PDO $pdo, int $customerId, string $deliveryLocation): void
    {
        if ($customerId <= 0) {
            return;
        }
        $dl = self::sanitizeDeliveryLocation($deliveryLocation);
        if ($dl === '') {
            return;
        }
        try {
            $st = $pdo->prepare('UPDATE customers SET delivery_location = ? WHERE id = ? LIMIT 1');
            $st->execute([$dl, $customerId]);
        } catch (Throwable $e) {
            /* ignore */
        }
    }

    /**
     * @param array<string,mixed> $meta
     */
    public static function logParcelActivity(PDO $pdo, int $parcelId, string $action, ?int $userId, array $meta = []): void
    {
        if ($parcelId <= 0) {
            return;
        }
        self::ensureParcelActivityLogTable($pdo);
        try {
            $metaJson = $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $st = $pdo->prepare('INSERT INTO parcel_activity_log (parcel_id, action, user_id, meta_json) VALUES (?,?,?,?)');
            $st->execute([$parcelId, $action, $userId, $metaJson]);
        } catch (Throwable $e) {
            /* ignore */
        }
    }
}
