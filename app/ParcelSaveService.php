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

    public static function ensureParcelItemsManualColumns(PDO $pdo): void
    {
        try {
            $pdo->exec('ALTER TABLE parcel_items ADD COLUMN is_manual TINYINT(1) NOT NULL DEFAULT 0');
        } catch (Throwable $e) {
            /* ignore if exists */
        }
        try {
            $pdo->exec('ALTER TABLE parcel_items ADD COLUMN manual_item_name VARCHAR(200) NULL');
        } catch (Throwable $e) {
            /* ignore if exists */
        }
        try {
            $pdo->exec('ALTER TABLE parcel_items ADD COLUMN manual_unit VARCHAR(50) NULL');
        } catch (Throwable $e) {
            /* ignore if exists */
        }
    }

    /**
     * Normalize a posted parcel item row for persistence.
     * Catalog rows keep description from the Select Item dropdown.
     * Manual rows store the typed name in description (for print/invoice) plus manual_* columns.
     *
     * @param array<string,mixed> $it
     * @return array{
     *   description:string,
     *   qty:float,
     *   rate:?float,
     *   additional_amount:?float,
     *   additional_amounts:?string,
     *   is_manual:int,
     *   manual_item_name:?string,
     *   manual_unit:?string
     * }|null
     */
    public static function normalizeItemRow(array $it): ?array
    {
        $isManual = !empty($it['is_manual']) && (string) $it['is_manual'] !== '0';
        $manualName = trim((string) ($it['manual_item_name'] ?? ''));
        $manualUnit = trim((string) ($it['manual_unit'] ?? ''));
        $desc = $isManual
            ? ($manualName !== '' ? $manualName : trim((string) ($it['description'] ?? '')))
            : trim((string) ($it['description'] ?? ''));
        $qty = (float) ($it['qty'] ?? 0);
        $rate = (float) ($it['rate'] ?? 0);
        if ($rate <= 0) {
            $rs = (float) ($it['rs'] ?? 0);
            $cts = (float) ($it['cts'] ?? 0);
            $rate = $rs + ($cts / 100.0);
        }
        $rate = ($rate > 0) ? $rate : null;

        $addArr = $it['additional_amounts'] ?? [];
        if (is_string($addArr)) {
            $addArr = json_decode($addArr, true) ?: [];
        }
        $addAmt = 0.0;
        foreach ((array) $addArr as $a) {
            $addAmt += (float) $a;
        }
        $addAmt = ($addAmt > 0) ? $addAmt : null;
        $addJson = !empty($addArr)
            ? json_encode(array_values(array_filter(array_map('floatval', (array) $addArr))))
            : null;
        if ($addJson === '[]') {
            $addJson = null;
        }

        if ($desc === '' && $qty <= 0) {
            return null;
        }

        return [
            'description' => $desc,
            'qty' => $qty,
            'rate' => $rate,
            'additional_amount' => $addAmt,
            'additional_amounts' => $addJson,
            'is_manual' => $isManual ? 1 : 0,
            'manual_item_name' => $isManual ? ($manualName !== '' ? $manualName : $desc) : null,
            'manual_unit' => $isManual ? ($manualUnit !== '' ? $manualUnit : null) : null,
        ];
    }

    /**
     * Validate non-empty item rows (manual and catalog).
     *
     * @param array<int|string,mixed> $items
     */
    public static function validateItemRows(array $items): ?string
    {
        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }
            $normalized = self::normalizeItemRow($it);
            if ($normalized === null) {
                continue;
            }
            $isManual = (int) $normalized['is_manual'] === 1;
            if ($isManual && trim((string) $normalized['description']) === '') {
                return 'Item Name is required for manual entries.';
            }
            if (!$isManual && trim((string) $normalized['description']) === '') {
                return 'Please select an item or enable Manual Entry.';
            }
            if ((float) $normalized['qty'] <= 0) {
                return 'Quantity must be greater than zero for each item line.';
            }
            if ($normalized['rate'] !== null && (float) $normalized['rate'] < 0) {
                return 'Rate must be zero or greater.';
            }
        }

        return null;
    }
}
