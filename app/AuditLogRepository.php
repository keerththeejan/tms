<?php

declare(strict_types=1);

/**
 * Audit Log Repository
 * Manages audit logging for accounting operations
 */
class AuditLogRepository
{
    public static function ensureSchema(PDO $pdo): void
    {
        AccountingSchemaRepository::ensureSchema($pdo);
    }

    /**
     * Log an audit entry
     */
    public static function log(PDO $pdo, string $entityType, int $entityId, string $action, ?array $oldValues = null, ?array $newValues = null, ?int $userId = null): void
    {
        $st = $pdo->prepare(
            'INSERT INTO accounting_audit_log (entity_type, entity_id, action, old_values, new_values, user_id, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $entityType,
            $entityId,
            $action,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    /**
     * Get audit logs for an entity
     */
    public static function getLogsForEntity(PDO $pdo, string $entityType, int $entityId, int $limit = 50): array
    {
        $st = $pdo->prepare(
            'SELECT al.*, u.username AS user_name
             FROM accounting_audit_log al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.entity_type = ? AND al.entity_id = ?
             ORDER BY al.created_at DESC
             LIMIT ?'
        );
        $st->execute([$entityType, $entityId, $limit]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get recent audit logs
     */
    public static function getRecentLogs(PDO $pdo, int $limit = 50): array
    {
        $st = $pdo->prepare(
            'SELECT al.*, u.username AS user_name
             FROM accounting_audit_log al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC
             LIMIT ?'
        );
        $st->execute([$limit]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get audit logs by user
     */
    public static function getLogsByUser(PDO $pdo, int $userId, int $limit = 50): array
    {
        $st = $pdo->prepare(
            'SELECT al.*, u.username AS user_name
             FROM accounting_audit_log al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.user_id = ?
             ORDER BY al.created_at DESC
             LIMIT ?'
        );
        $st->execute([$userId, $limit]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get audit logs by action
     */
    public static function getLogsByAction(PDO $pdo, string $action, int $limit = 50): array
    {
        $st = $pdo->prepare(
            'SELECT al.*, u.username AS user_name
             FROM accounting_audit_log al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.action = ?
             ORDER BY al.created_at DESC
             LIMIT ?'
        );
        $st->execute([$action, $limit]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get audit logs by date range
     */
    public static function getLogsByDateRange(PDO $pdo, string $fromDate, string $toDate, int $limit = 100): array
    {
        $st = $pdo->prepare(
            'SELECT al.*, u.username AS user_name
             FROM accounting_audit_log al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE DATE(al.created_at) BETWEEN ? AND ?
             ORDER BY al.created_at DESC
             LIMIT ?'
        );
        $st->execute([$fromDate, $toDate, $limit]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
