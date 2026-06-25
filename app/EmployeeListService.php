<?php
declare(strict_types=1);

/**
 * @deprecated Use EmployeeRepository::fetchFiltered — kept for list_json backward compatibility.
 */
final class EmployeeListService
{
    /** @param array<string, mixed> $get @return array<int, array<string, mixed>> */
    public static function fetchFiltered(PDO $pdo, array $get): array
    {
        EmployeeSchemaRepository::ensureSchema($pdo);

        return EmployeeRepository::fetchFiltered($pdo, $get);
    }
}
