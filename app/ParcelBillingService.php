<?php
declare(strict_types=1);

/**
 * Daily consolidated invoicing: one invoice per customer per calendar day.
 * All parcels on the same date attach to the same invoices row; totals recalculate on each save.
 */
class ParcelBillingService
{
    public static function ensureSchema(PDO $pdo): void
    {
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS invoices (
                    invoice_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    customer_id BIGINT UNSIGNED NOT NULL,
                    invoice_date DATE NOT NULL,
                    invoice_no VARCHAR(32) NOT NULL,
                    parcel_count INT UNSIGNED NOT NULL DEFAULT 0,
                    total_quantity DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                    total_weight DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                    freight_charges DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                    delivery_charges DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                    tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                    discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                    grand_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                    status ENUM(\'open\',\'closed\',\'cancelled\') NOT NULL DEFAULT \'open\',
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (invoice_id),
                    UNIQUE KEY uq_invoices_customer_date (customer_id, invoice_date),
                    UNIQUE KEY uq_invoices_invoice_no (invoice_no),
                    KEY idx_invoices_date (invoice_date),
                    KEY idx_invoices_customer (customer_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );
        } catch (Throwable $e) { /* ignore */ }

        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS invoice_day_sequences (
                    bill_date DATE NOT NULL PRIMARY KEY,
                    last_seq INT UNSIGNED NOT NULL DEFAULT 0,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );
        } catch (Throwable $e) { /* ignore */ }

        try {
            $pdo->exec('ALTER TABLE parcels ADD COLUMN invoice_number VARCHAR(32) NULL AFTER invoice_no');
        } catch (Throwable $e) { /* ignore if exists */ }
        try {
            $pdo->exec('ALTER TABLE parcels ADD COLUMN invoice_id INT UNSIGNED NULL AFTER invoice_number');
        } catch (Throwable $e) { /* ignore if exists */ }
        try {
            $pdo->exec('ALTER TABLE parcels ADD INDEX idx_parcels_invoice_id (invoice_id)');
        } catch (Throwable $e) { /* ignore if exists */ }
        try {
            $pdo->exec('ALTER TABLE delivery_notes ADD COLUMN invoice_number VARCHAR(32) NULL AFTER total_amount');
        } catch (Throwable $e) { /* ignore if exists */ }
        try {
            $pdo->exec('ALTER TABLE invoices ADD COLUMN from_branch_id INT UNSIGNED NULL AFTER customer_id');
        } catch (Throwable $e) { /* ignore if exists */ }
        try {
            $pdo->exec('ALTER TABLE invoices ADD COLUMN to_branch_id INT UNSIGNED NULL AFTER from_branch_id');
        } catch (Throwable $e) { /* ignore if exists */ }
        try {
            $pdo->exec('ALTER TABLE invoices DROP INDEX uq_invoices_customer_date');
        } catch (Throwable $e) { /* ignore if missing */ }
        try {
            $pdo->exec(
                'ALTER TABLE invoices ADD UNIQUE KEY uq_invoices_bill_group (customer_id, invoice_date, from_branch_id, to_branch_id)'
            );
        } catch (Throwable $e) { /* ignore if exists */ }
        self::backfillInvoiceBranchColumns($pdo);
    }

    /** Copy branch ids from first linked parcel onto legacy invoice rows. */
    private static function backfillInvoiceBranchColumns(PDO $pdo): void
    {
        try {
            $pdo->exec(
                'UPDATE invoices i
                 INNER JOIN (
                     SELECT p.invoice_id, MIN(p.id) AS min_pid
                     FROM parcels p
                     WHERE p.invoice_id IS NOT NULL AND p.invoice_id > 0
                     GROUP BY p.invoice_id
                 ) x ON x.invoice_id = i.invoice_id
                 INNER JOIN parcels p ON p.id = x.min_pid
                 SET i.from_branch_id = COALESCE(i.from_branch_id, p.from_branch_id),
                     i.to_branch_id = COALESCE(i.to_branch_id, p.to_branch_id)
                 WHERE i.from_branch_id IS NULL OR i.to_branch_id IS NULL'
            );
        } catch (Throwable $e) { /* ignore */ }
    }

    public static function normalizeBillDate(?string $createdDatePost): string
    {
        $raw = trim((string)$createdDatePost);
        if ($raw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }
        return date('Y-m-d');
    }

    /**
     * Find open consolidated invoice for customer + date + branch pair (UI + pre-save lookup).
     *
     * @return array<string,mixed>|null
     */
    public static function findExistingBill(
        PDO $pdo,
        int $customerId,
        string $billDate,
        int $fromBranchId = 0,
        int $toBranchId = 0
    ): ?array {
        if ($customerId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $billDate)) {
            return null;
        }

        self::backfillInvoiceFromLegacyParcels($pdo, $customerId, $billDate, $fromBranchId, $toBranchId);

        $row = self::fetchOpenInvoiceRow($pdo, $customerId, $billDate, $fromBranchId, $toBranchId, false);
        if ($row) {
            return self::formatBillSummaryRow($row);
        }

        $fromParcels = self::findInvoiceFromParcels($pdo, $customerId, $billDate, $fromBranchId, $toBranchId);
        if (!$fromParcels) {
            return null;
        }

        $invoiceId = self::ensureInvoiceRowExists(
            $pdo,
            $customerId,
            $billDate,
            $fromBranchId,
            $toBranchId,
            (string)($fromParcels['invoice_number'] ?? ''),
            (int)($fromParcels['invoice_no'] ?? 0)
        );
        if ($invoiceId <= 0) {
            return null;
        }

        $st = $pdo->prepare(
            'SELECT i.*, c.name AS customer_name
             FROM invoices i
             LEFT JOIN customers c ON c.id = i.customer_id
             WHERE i.invoice_id = ?
             LIMIT 1'
        );
        $st->execute([$invoiceId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ? self::formatBillSummaryRow($row) : null;
    }

    /**
     * Resolve or create the daily invoice inside an open transaction.
     *
     * @return array{invoice_id:int,invoice_no:int,invoice_number:string,is_reused:bool}
     */
    public static function resolveInvoiceForNewParcel(
        PDO $pdo,
        int $customerId,
        string $billDate,
        int $fromBranchId,
        int $toBranchId,
        int $manualInvoiceNo,
        string $manualInvoiceNumber,
        bool $forceNewBill
    ): array {
        if ($customerId <= 0) {
            throw new InvalidArgumentException('Customer is required for daily invoice.');
        }

        self::backfillInvoiceFromLegacyParcels($pdo, $customerId, $billDate, $fromBranchId, $toBranchId);

        // Reuse open invoice: same customer, date, branches; never create when a match exists.
        $existing = self::fetchOpenInvoiceRow($pdo, $customerId, $billDate, $fromBranchId, $toBranchId, true);
        if ($existing) {
            return self::buildResolvedInvoice($existing, true);
        }

        $fromParcels = self::findInvoiceFromParcels($pdo, $customerId, $billDate, $fromBranchId, $toBranchId);
        if ($fromParcels) {
            $invoiceNumber = trim((string)($fromParcels['invoice_number'] ?? ''));
            if ($invoiceNumber === '' && $manualInvoiceNumber !== '') {
                $invoiceNumber = $manualInvoiceNumber;
            }
            if ($invoiceNumber === '' && $manualInvoiceNo > 0) {
                $invoiceNumber = self::formatInvoiceNumber($billDate, $manualInvoiceNo);
            }
            $seq = $invoiceNumber !== ''
                ? self::parseSeqFromInvoiceNumber($invoiceNumber, $billDate)
                : max(1, (int)($fromParcels['invoice_no'] ?? 1));
            if ($invoiceNumber === '') {
                $invoiceNumber = self::formatInvoiceNumber($billDate, $seq);
            }
            $invoiceId = self::ensureInvoiceRowExists(
                $pdo,
                $customerId,
                $billDate,
                $fromBranchId,
                $toBranchId,
                $invoiceNumber,
                $seq
            );
            return [
                'invoice_id' => $invoiceId,
                'invoice_no' => $seq,
                'invoice_number' => $invoiceNumber,
                'is_reused' => true,
            ];
        }

        if ($forceNewBill) {
            // Explicit new bill only when no matching open invoice exists.
        }

        if ($manualInvoiceNumber !== '') {
            $invoiceNumber = $manualInvoiceNumber;
            $seq = self::parseSeqFromInvoiceNumber($invoiceNumber, $billDate);
            $invoiceId = self::insertDailyInvoice($pdo, $customerId, $billDate, $invoiceNumber, $fromBranchId, $toBranchId);
            return [
                'invoice_id' => $invoiceId,
                'invoice_no' => $manualInvoiceNo > 0 ? $manualInvoiceNo : $seq,
                'invoice_number' => $invoiceNumber,
                'is_reused' => false,
            ];
        }

        if ($manualInvoiceNo > 0) {
            $invoiceNumber = self::formatInvoiceNumber($billDate, $manualInvoiceNo);
            $invoiceId = self::insertDailyInvoice($pdo, $customerId, $billDate, $invoiceNumber, $fromBranchId, $toBranchId);
            return [
                'invoice_id' => $invoiceId,
                'invoice_no' => $manualInvoiceNo,
                'invoice_number' => $invoiceNumber,
                'is_reused' => false,
            ];
        }

        $seq = self::nextDailySequence($pdo, $billDate);
        $invoiceNumber = self::formatInvoiceNumber($billDate, $seq);
        $invoiceId = self::insertDailyInvoice($pdo, $customerId, $billDate, $invoiceNumber, $fromBranchId, $toBranchId);

        return [
            'invoice_id' => $invoiceId,
            'invoice_no' => $seq,
            'invoice_number' => $invoiceNumber,
            'is_reused' => false,
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array{invoice_id:int,invoice_no:int,invoice_number:string,is_reused:bool}
     */
    private static function buildResolvedInvoice(array $row, bool $reused): array
    {
        $invoiceNumber = trim((string)($row['invoice_no'] ?? ''));
        $billDate = (string)($row['invoice_date'] ?? date('Y-m-d'));
        $seq = self::parseSeqFromInvoiceNumber($invoiceNumber, $billDate);

        return [
            'invoice_id' => (int)($row['invoice_id'] ?? 0),
            'invoice_no' => $seq,
            'invoice_number' => $invoiceNumber,
            'is_reused' => $reused,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function fetchOpenInvoiceRow(
        PDO $pdo,
        int $customerId,
        string $billDate,
        int $fromBranchId,
        int $toBranchId,
        bool $forUpdate
    ): ?array {
        $sql = 'SELECT i.*, c.name AS customer_name
                FROM invoices i
                LEFT JOIN customers c ON c.id = i.customer_id
                WHERE i.customer_id = ?
                  AND i.invoice_date = ?
                  AND COALESCE(i.status, \'open\') <> \'cancelled\'';
        $params = [$customerId, $billDate];

        if ($fromBranchId > 0 && $toBranchId > 0) {
            $sql .= ' AND i.from_branch_id = ? AND i.to_branch_id = ?';
            $params[] = $fromBranchId;
            $params[] = $toBranchId;
        }

        $sql .= ' ORDER BY i.invoice_id ASC LIMIT 1';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Locate invoice data from existing non-cancelled parcels on the same bill group.
     *
     * @return array{invoice_id:int,invoice_no:int,invoice_number:string}|null
     */
    private static function findInvoiceFromParcels(
        PDO $pdo,
        int $customerId,
        string $billDate,
        int $fromBranchId,
        int $toBranchId
    ): ?array {
        $sql = 'SELECT p.invoice_id, p.invoice_number, p.invoice_no
                FROM parcels p
                WHERE p.customer_id = ?
                  AND DATE(p.created_at) = ?
                  AND COALESCE(p.status, \'\') <> \'cancelled\'';
        $params = [$customerId, $billDate];

        if ($fromBranchId > 0 && $toBranchId > 0) {
            $sql .= ' AND p.from_branch_id = ? AND p.to_branch_id = ?';
            $params[] = $fromBranchId;
            $params[] = $toBranchId;
        }

        $sql .= ' ORDER BY p.id ASC';

        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!$rows) {
            return null;
        }

        foreach ($rows as $row) {
            $invId = (int)($row['invoice_id'] ?? 0);
            $invNum = trim((string)($row['invoice_number'] ?? ''));
            $invNo = (int)($row['invoice_no'] ?? 0);
            if ($invId > 0 || $invNum !== '' || $invNo > 0) {
                return [
                    'invoice_id' => $invId,
                    'invoice_no' => $invNo,
                    'invoice_number' => $invNum,
                ];
            }
        }

        return null;
    }

    private static function ensureInvoiceRowExists(
        PDO $pdo,
        int $customerId,
        string $billDate,
        int $fromBranchId,
        int $toBranchId,
        string $invoiceNumber,
        int $invoiceNoSeq
    ): int {
        $existing = self::fetchOpenInvoiceRow($pdo, $customerId, $billDate, $fromBranchId, $toBranchId, false);
        if ($existing) {
            return (int)$existing['invoice_id'];
        }

        if ($invoiceNumber === '') {
            $seq = max(1, $invoiceNoSeq);
            $invoiceNumber = self::formatInvoiceNumber($billDate, $seq);
        }

        return self::insertDailyInvoice($pdo, $customerId, $billDate, $invoiceNumber, $fromBranchId, $toBranchId);
    }

    /**
     * Link parcel to invoice and refresh invoice totals (call after items are saved).
     */
    public static function linkParcelAndRecalculate(
        PDO $pdo,
        int $parcelId,
        int $invoiceId,
        int $customerId,
        string $billDate,
        int $invoiceNo,
        string $invoiceNumber,
        int $fromBranchId = 0,
        int $toBranchId = 0
    ): void {
        if ($parcelId <= 0 || $invoiceId <= 0) {
            return;
        }

        $pdo->prepare(
            'UPDATE parcels
             SET invoice_id = ?, invoice_no = ?, invoice_number = ?
             WHERE id = ?'
        )->execute([
            $invoiceId,
            $invoiceNo > 0 ? $invoiceNo : null,
            $invoiceNumber !== '' ? $invoiceNumber : null,
            $parcelId,
        ]);

        self::recalculateInvoiceTotals($pdo, $invoiceId);

        self::syncInvoiceFieldsForCustomerDate(
            $pdo,
            $customerId,
            $billDate,
            $invoiceNo,
            $invoiceNumber,
            $invoiceId,
            $fromBranchId,
            $toBranchId
        );
    }

    /**
     * Recalculate all summary fields on the invoices row from linked parcels.
     */
    public static function recalculateInvoiceTotals(PDO $pdo, int $invoiceId): float
    {
        if ($invoiceId <= 0) {
            return 0.0;
        }

        $parcelSt = $pdo->prepare(
            'SELECT p.id, p.weight, p.price
             FROM parcels p
             WHERE p.invoice_id = ?
               AND COALESCE(p.status, \'\') <> \'cancelled\''
        );
        $parcelSt->execute([$invoiceId]);
        $parcels = $parcelSt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $parcelCount = count($parcels);
        $totalWeight = 0.0;
        $freightCharges = 0.0;
        $deliveryCharges = 0.0;

        foreach ($parcels as $p) {
            $totalWeight += (float)($p['weight'] ?? 0);
            $amount = self::computeParcelAmount(
                $pdo,
                (int)$p['id'],
                isset($p['price']) && $p['price'] !== null ? (float)$p['price'] : null
            );
            $freightCharges += $amount;
            $deliveryCharges += self::sumParcelDeliveryCharges($pdo, (int)$p['id']);
        }

        $totalQuantity = self::sumInvoiceQuantity($pdo, $invoiceId);
        $taxAmount = 0.0;
        $discountAmount = self::sumInvoiceDiscount($pdo, $invoiceId);
        $subtotal = $freightCharges + $deliveryCharges;
        $grandTotal = max(0.0, $subtotal + $taxAmount - max(0.0, $discountAmount));

        $pdo->prepare(
            'UPDATE invoices SET
                parcel_count = ?,
                total_quantity = ?,
                total_weight = ?,
                freight_charges = ?,
                delivery_charges = ?,
                tax_amount = ?,
                discount_amount = ?,
                subtotal = ?,
                grand_total = ?
             WHERE invoice_id = ?'
        )->execute([
            $parcelCount,
            $totalQuantity,
            $totalWeight,
            $freightCharges,
            $deliveryCharges,
            $taxAmount,
            $discountAmount,
            $subtotal,
            $grandTotal,
            $invoiceId,
        ]);

        return $grandTotal;
    }

    public static function formatInvoiceNumber(string $billDate, int $seq): string
    {
        $ymd = str_replace('-', '', $billDate);
        return 'INV-' . $ymd . '-' . str_pad((string)max(1, $seq), 3, '0', STR_PAD_LEFT);
    }

    public static function parseSeqFromInvoiceNumber(string $invoiceNumber, string $billDate): int
    {
        $ymd = str_replace('-', '', $billDate);
        if (preg_match('/^INV-' . preg_quote($ymd, '/') . '-(\d+)$/i', trim($invoiceNumber), $m)) {
            return max(1, (int)$m[1]);
        }
        if (preg_match('/-(\d+)$/', trim($invoiceNumber), $m)) {
            return max(1, (int)$m[1]);
        }
        return 1;
    }

    public static function computeParcelAmount(PDO $pdo, int $parcelId, ?float $price): float
    {
        if ($parcelId <= 0) {
            return 0.0;
        }
        if ($price !== null && $price > 0) {
            return $price;
        }
        try {
            $sumItems = $pdo->prepare(
                'SELECT COALESCE(SUM(COALESCE(qty,0)*COALESCE(rate,0) + COALESCE(additional_amount,0)),0) AS s
                 FROM parcel_items WHERE parcel_id=?'
            );
            $sumItems->execute([$parcelId]);
            return (float)($sumItems->fetch(PDO::FETCH_ASSOC)['s'] ?? 0);
        } catch (Throwable $e) {
            return 0.0;
        }
    }

    /**
     * Attach parcel to same-day delivery note and recalculate DN totals.
     *
     * @return array{delivery_note_id:int,total_amount:float}
     */
    public static function attachParcelToBill(
        PDO $pdo,
        int $parcelId,
        int $customerId,
        int $branchId,
        string $billDate,
        float $amount,
        int $invoiceNo,
        string $invoiceNumber
    ): array {
        if ($parcelId <= 0 || $customerId <= 0 || $branchId <= 0) {
            return ['delivery_note_id' => 0, 'total_amount' => 0.0];
        }

        $existsDnp = $pdo->prepare('SELECT delivery_note_id FROM delivery_note_parcels WHERE parcel_id=? LIMIT 1');
        $existsDnp->execute([$parcelId]);
        $linked = $existsDnp->fetch(PDO::FETCH_ASSOC);
        if ($linked) {
            $dnId = (int)$linked['delivery_note_id'];
            self::recalculateDeliveryNoteTotal($pdo, $dnId);
            return [
                'delivery_note_id' => $dnId,
                'total_amount' => self::getDeliveryNoteTotal($pdo, $dnId),
            ];
        }

        $findDn = $pdo->prepare(
            'SELECT id FROM delivery_notes WHERE customer_id=? AND branch_id=? AND delivery_date=? LIMIT 1 FOR UPDATE'
        );
        $findDn->execute([$customerId, $branchId, $billDate]);
        $dnR = $findDn->fetch(PDO::FETCH_ASSOC);
        if ($dnR) {
            $dnId = (int)$dnR['id'];
        } else {
            $insDn = $pdo->prepare(
                'INSERT INTO delivery_notes (customer_id, branch_id, delivery_date, total_amount, invoice_number)
                 VALUES (?,?,?,0,?)'
            );
            $insDn->execute([$customerId, $branchId, $billDate, $invoiceNumber !== '' ? $invoiceNumber : null]);
            $dnId = (int)$pdo->lastInsertId();
        }

        if ($invoiceNumber !== '') {
            try {
                $pdo->prepare(
                    'UPDATE delivery_notes SET invoice_number = ?
                     WHERE id = ? AND (invoice_number IS NULL OR TRIM(invoice_number) = \'\')'
                )->execute([$invoiceNumber, $dnId]);
            } catch (Throwable $e) { /* ignore */ }
        }

        $insDnp = $pdo->prepare(
            'INSERT IGNORE INTO delivery_note_parcels (delivery_note_id, parcel_id, amount) VALUES (?,?,?)'
        );
        $insDnp->execute([$dnId, $parcelId, max(0.0, $amount)]);

        $total = self::recalculateDeliveryNoteTotal($pdo, $dnId);

        return ['delivery_note_id' => $dnId, 'total_amount' => $total];
    }

    public static function recalculateDeliveryNoteTotal(PDO $pdo, int $deliveryNoteId): float
    {
        if ($deliveryNoteId <= 0) {
            return 0.0;
        }
        $sumStmt = $pdo->prepare(
            'SELECT COALESCE(SUM(amount),0) AS s FROM delivery_note_parcels WHERE delivery_note_id=?'
        );
        $sumStmt->execute([$deliveryNoteId]);
        $total = (float)($sumStmt->fetch(PDO::FETCH_ASSOC)['s'] ?? 0);
        $pdo->prepare('UPDATE delivery_notes SET total_amount=? WHERE id=?')->execute([$total, $deliveryNoteId]);
        return $total;
    }

    private static function getDeliveryNoteTotal(PDO $pdo, int $deliveryNoteId): float
    {
        $st = $pdo->prepare('SELECT COALESCE(total_amount,0) AS t FROM delivery_notes WHERE id=?');
        $st->execute([$deliveryNoteId]);
        return (float)($st->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);
    }

    public static function syncInvoiceFieldsForCustomerDate(
        PDO $pdo,
        int $customerId,
        string $billDate,
        int $invoiceNo,
        string $invoiceNumber,
        int $invoiceId = 0,
        int $fromBranchId = 0,
        int $toBranchId = 0
    ): void {
        if ($customerId <= 0) {
            return;
        }
        $params = [
            $invoiceId > 0 ? $invoiceId : null,
            $invoiceNo > 0 ? $invoiceNo : null,
            $invoiceNumber !== '' ? $invoiceNumber : null,
            $customerId,
            $billDate,
        ];
        $sql = 'UPDATE parcels
             SET invoice_id = COALESCE(?, invoice_id),
                 invoice_no = COALESCE(?, invoice_no),
                 invoice_number = COALESCE(?, invoice_number)
             WHERE customer_id = ? AND DATE(created_at) = ?
               AND COALESCE(status, \'\') <> \'cancelled\'';
        if ($fromBranchId > 0 && $toBranchId > 0) {
            $sql .= ' AND from_branch_id = ? AND to_branch_id = ?';
            $params[] = $fromBranchId;
            $params[] = $toBranchId;
        }
        $pdo->prepare($sql)->execute($params);

        if ($invoiceNumber !== '') {
            try {
                $pdo->prepare(
                    'UPDATE delivery_notes SET invoice_number = ? WHERE customer_id = ? AND delivery_date = ?'
                )->execute([$invoiceNumber, $customerId, $billDate]);
            } catch (Throwable $e) { /* ignore */ }
        }
    }

    /** @param array<string,mixed> $row */
    private static function formatBillSummaryRow(array $row): array
    {
        $invoiceNumber = trim((string)($row['invoice_no'] ?? ''));
        $seq = self::parseSeqFromInvoiceNumber($invoiceNumber, (string)($row['invoice_date'] ?? date('Y-m-d')));

        return [
            'invoice_id' => (int)($row['invoice_id'] ?? 0),
            'invoice_no' => $seq,
            'invoice_number' => $invoiceNumber,
            'customer_id' => (int)($row['customer_id'] ?? 0),
            'customer_name' => trim((string)($row['customer_name'] ?? '')),
            'parcel_count' => (int)($row['parcel_count'] ?? 0),
            'total_quantity' => (float)($row['total_quantity'] ?? 0),
            'total_weight' => (float)($row['total_weight'] ?? 0),
            'freight_charges' => (float)($row['freight_charges'] ?? 0),
            'delivery_charges' => (float)($row['delivery_charges'] ?? 0),
            'tax' => (float)($row['tax_amount'] ?? 0),
            'discount' => (float)($row['discount_amount'] ?? 0),
            'subtotal' => (float)($row['subtotal'] ?? 0),
            'grand_total' => (float)($row['grand_total'] ?? 0),
            'parcel_total' => (float)($row['grand_total'] ?? 0),
            'status_message' => 'Existing invoice found. New parcel will be added to Invoice ' . $invoiceNumber,
            'is_existing' => true,
        ];
    }

    private static function insertDailyInvoice(
        PDO $pdo,
        int $customerId,
        string $billDate,
        string $invoiceNumber,
        int $fromBranchId = 0,
        int $toBranchId = 0
    ): int {
        try {
            $ins = $pdo->prepare(
                'INSERT INTO invoices (customer_id, from_branch_id, to_branch_id, invoice_date, invoice_no) VALUES (?,?,?,?,?)'
            );
            $ins->execute([
                $customerId,
                $fromBranchId > 0 ? $fromBranchId : null,
                $toBranchId > 0 ? $toBranchId : null,
                $billDate,
                $invoiceNumber,
            ]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            // Concurrent create: unique key on bill group — reuse existing row.
            if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                $existing = self::fetchOpenInvoiceRow($pdo, $customerId, $billDate, $fromBranchId, $toBranchId, true);
                if ($existing) {
                    return (int)$existing['invoice_id'];
                }
                $st = $pdo->prepare(
                    'SELECT invoice_id FROM invoices WHERE customer_id = ? AND invoice_date = ? LIMIT 1 FOR UPDATE'
                );
                $st->execute([$customerId, $billDate]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return (int)$row['invoice_id'];
                }
            }
            throw $e;
        }
    }

    private static function nextDailySequence(PDO $pdo, string $billDate): int
    {
        $sel = $pdo->prepare('SELECT last_seq FROM invoice_day_sequences WHERE bill_date = ? FOR UPDATE');
        $sel->execute([$billDate]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $next = (int)$row['last_seq'] + 1;
            $upd = $pdo->prepare('UPDATE invoice_day_sequences SET last_seq = ? WHERE bill_date = ?');
            $upd->execute([$next, $billDate]);
            return $next;
        }
        $ins = $pdo->prepare('INSERT INTO invoice_day_sequences (bill_date, last_seq) VALUES (?, 1)');
        $ins->execute([$billDate]);
        return 1;
    }

    private static function sumInvoiceQuantity(PDO $pdo, int $invoiceId): float
    {
        try {
            $st = $pdo->prepare(
                'SELECT COALESCE(SUM(pi.qty),0) AS q
                 FROM parcel_items pi
                 INNER JOIN parcels p ON p.id = pi.parcel_id
                 WHERE p.invoice_id = ?'
            );
            $st->execute([$invoiceId]);
            return (float)($st->fetch(PDO::FETCH_ASSOC)['q'] ?? 0);
        } catch (Throwable $e) {
            return 0.0;
        }
    }

    private static function sumParcelDeliveryCharges(PDO $pdo, int $parcelId): float
    {
        try {
            $st = $pdo->prepare(
                'SELECT COALESCE(SUM(COALESCE(additional_amount,0)),0) AS d FROM parcel_items WHERE parcel_id=?'
            );
            $st->execute([$parcelId]);
            return (float)($st->fetch(PDO::FETCH_ASSOC)['d'] ?? 0);
        } catch (Throwable $e) {
            return 0.0;
        }
    }

    private static function sumInvoiceDiscount(PDO $pdo, int $invoiceId): float
    {
        try {
            $st = $pdo->prepare(
                'SELECT COALESCE(SUM(COALESCE(dn.discount,0)),0) AS d
                 FROM delivery_notes dn
                 WHERE dn.customer_id = (
                     SELECT customer_id FROM invoices WHERE invoice_id = ? LIMIT 1
                 )
                 AND dn.delivery_date = (
                     SELECT invoice_date FROM invoices WHERE invoice_id = ? LIMIT 1
                 )'
            );
            $st->execute([$invoiceId, $invoiceId]);
            return max(0.0, (float)($st->fetch(PDO::FETCH_ASSOC)['d'] ?? 0));
        } catch (Throwable $e) {
            return 0.0;
        }
    }

    /**
     * Migrate legacy same-day parcels (before invoices table) into one consolidated invoice row.
     */
    private static function backfillInvoiceFromLegacyParcels(
        PDO $pdo,
        int $customerId,
        string $billDate,
        int $fromBranchId = 0,
        int $toBranchId = 0
    ): void {
        if (self::fetchOpenInvoiceRow($pdo, $customerId, $billDate, $fromBranchId, $toBranchId, false)) {
            return;
        }

        $sql = 'SELECT COUNT(*) FROM parcels p
                WHERE p.customer_id = ? AND DATE(p.created_at) = ?
                  AND COALESCE(p.status, \'\') <> \'cancelled\'';
        $params = [$customerId, $billDate];
        if ($fromBranchId > 0 && $toBranchId > 0) {
            $sql .= ' AND p.from_branch_id = ? AND p.to_branch_id = ?';
            $params[] = $fromBranchId;
            $params[] = $toBranchId;
        }
        $legacy = $pdo->prepare($sql);
        $legacy->execute($params);
        if ((int)$legacy->fetchColumn() <= 0) {
            return;
        }

        $headSql = 'SELECT invoice_number, invoice_no, from_branch_id, to_branch_id FROM parcels p
                    WHERE p.customer_id = ? AND DATE(p.created_at) = ?
                      AND COALESCE(p.status, \'\') <> \'cancelled\'';
        $headParams = [$customerId, $billDate];
        if ($fromBranchId > 0 && $toBranchId > 0) {
            $headSql .= ' AND p.from_branch_id = ? AND p.to_branch_id = ?';
            $headParams[] = $fromBranchId;
            $headParams[] = $toBranchId;
        }
        $headSql .= ' ORDER BY p.id ASC LIMIT 1';

        $head = $pdo->prepare($headSql);
        $head->execute($headParams);
        $h = $head->fetch(PDO::FETCH_ASSOC);
        if (!$h) {
            return;
        }

        $fb = $fromBranchId > 0 ? $fromBranchId : (int)($h['from_branch_id'] ?? 0);
        $tb = $toBranchId > 0 ? $toBranchId : (int)($h['to_branch_id'] ?? 0);

        $invoiceNumber = trim((string)($h['invoice_number'] ?? ''));
        if ($invoiceNumber === '') {
            $seq = max(1, (int)($h['invoice_no'] ?? 1));
            $invoiceNumber = self::formatInvoiceNumber($billDate, $seq);
        }

        try {
            $invoiceId = self::insertDailyInvoice($pdo, $customerId, $billDate, $invoiceNumber, $fb, $tb);
            $seq = self::parseSeqFromInvoiceNumber($invoiceNumber, $billDate);
            $linkSql = 'UPDATE parcels SET invoice_id = ?, invoice_number = COALESCE(invoice_number, ?), invoice_no = COALESCE(invoice_no, ?)
                        WHERE customer_id = ? AND DATE(created_at) = ? AND (invoice_id IS NULL OR invoice_id = 0)
                          AND COALESCE(status, \'\') <> \'cancelled\'';
            $linkParams = [$invoiceId, $invoiceNumber, $seq, $customerId, $billDate];
            if ($fb > 0 && $tb > 0) {
                $linkSql .= ' AND from_branch_id = ? AND to_branch_id = ?';
                $linkParams[] = $fb;
                $linkParams[] = $tb;
            }
            $pdo->prepare($linkSql)->execute($linkParams);
            self::recalculateInvoiceTotals($pdo, $invoiceId);
        } catch (Throwable $e) {
            /* ignore backfill race */
        }
    }
}
