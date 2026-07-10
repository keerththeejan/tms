<?php

declare(strict_types=1);

/**
 * Voucher Detail Repository
 * Manages voucher line items for the new accounting module
 */
class VoucherDetailRepository
{
    public static function ensureSchema(PDO $pdo): void
    {
        AccountingSchemaRepository::ensureSchema($pdo);
    }

    /** @return list<array<string,mixed>> */
    public static function getByVoucherId(PDO $pdo, int $voucherId): array
    {
        $st = $pdo->prepare(
            'SELECT vd.*, a.account_code, a.account_name, ag.group_name
             FROM voucher_details vd
             INNER JOIN accounts a ON a.id = vd.account_id
             INNER JOIN account_groups ag ON ag.id = a.account_group_id
             WHERE vd.voucher_id = ?
             ORDER BY vd.line_number ASC'
        );
        $st->execute([$voucherId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string,mixed> */
    public static function create(PDO $pdo, array $data): array
    {
        $st = $pdo->prepare(
            'INSERT INTO voucher_details (voucher_id, line_number, account_id, debit_amount, credit_amount, narration, cost_center_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $data['voucher_id'],
            $data['line_number'],
            $data['account_id'],
            (float) ($data['debit_amount'] ?? 0),
            (float) ($data['credit_amount'] ?? 0),
            $data['narration'] ?? null,
            $data['cost_center_id'] ?? null,
        ]);
        
        $id = (int) $pdo->lastInsertId();
        $st = $pdo->prepare('SELECT * FROM voucher_details WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string,mixed>> */
    public static function createBatch(PDO $pdo, int $voucherId, array $details): array
    {
        $ownTxn = !$pdo->inTransaction();
        if ($ownTxn) {
            $pdo->beginTransaction();
        }
        try {
            $st = $pdo->prepare('DELETE FROM voucher_details WHERE voucher_id = ?');
            $st->execute([$voucherId]);

            $created = [];
            foreach ($details as $index => $detail) {
                $detail['voucher_id'] = $voucherId;
                $detail['line_number'] = $index + 1;
                $created[] = self::create($pdo, $detail);
            }

            if ($ownTxn) {
                $pdo->commit();
            }
            return $created;
        } catch (Throwable $e) {
            if ($ownTxn && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<string,mixed> */
    public static function update(PDO $pdo, int $id, array $data): array
    {
        $fields = [];
        $params = [];
        
        foreach (['account_id', 'debit_amount', 'credit_amount', 'narration', 'cost_center_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            $st = $pdo->prepare('SELECT * FROM voucher_details WHERE id = ? LIMIT 1');
            $st->execute([$id]);
            return $st->fetch(PDO::FETCH_ASSOC) ?: [];
        }
        
        $params[] = $id;
        $sql = 'UPDATE voucher_details SET ' . implode(', ', $fields) . ' WHERE id = ?';
        
        $st = $pdo->prepare($sql);
        $st->execute($params);
        
        $st = $pdo->prepare('SELECT * FROM voucher_details WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public static function delete(PDO $pdo, int $id): bool
    {
        $st = $pdo->prepare('DELETE FROM voucher_details WHERE id = ?');
        return $st->execute([$id]);
    }

    public static function deleteByVoucherId(PDO $pdo, int $voucherId): bool
    {
        $st = $pdo->prepare('DELETE FROM voucher_details WHERE voucher_id = ?');
        return $st->execute([$voucherId]);
    }

    /** @return array<string,mixed> */
    public static function getTotals(PDO $pdo, int $voucherId): array
    {
        $st = $pdo->prepare(
            'SELECT SUM(debit_amount) AS total_debit, SUM(credit_amount) AS total_credit
             FROM voucher_details
             WHERE voucher_id = ?'
        );
        $st->execute([$voucherId]);
        $result = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        
        return [
            'total_debit' => (float) ($result['total_debit'] ?? 0),
            'total_credit' => (float) ($result['total_credit'] ?? 0),
            'difference' => (float) (($result['total_debit'] ?? 0) - ($result['total_credit'] ?? 0)),
        ];
    }
}
