<?php
declare(strict_types=1);

/**
 * Shared employee list query for the Employees index page and list_json API.
 */
final class EmployeeListService
{
    /**
     * @param array<string, mixed> $get
     * @return array<int, array<string, mixed>>
     */
    public static function fetchFiltered(PDO $pdo, array $get): array
    {
        $emp_code = trim((string)($get['emp_code'] ?? ''));
        $name = trim((string)($get['name'] ?? ''));
        $first_name = trim((string)($get['first_name'] ?? ''));
        $last_name = trim((string)($get['last_name'] ?? ''));
        $email = trim((string)($get['email'] ?? ''));
        $phone = trim((string)($get['phone'] ?? ''));
        $address = trim((string)($get['address'] ?? ''));
        $position = trim((string)($get['position'] ?? ''));
        $role = trim((string)($get['role'] ?? ''));
        $license_number = trim((string)($get['license_number'] ?? ''));
        $license_from = trim((string)($get['license_from'] ?? ''));
        $license_to = trim((string)($get['license_to'] ?? ''));
        $vehicle_like = trim((string)($get['vehicle'] ?? ''));
        $branch_id = (int)($get['branch_id'] ?? 0);
        $join_from = trim((string)($get['join_from'] ?? ''));
        $join_to = trim((string)($get['join_to'] ?? ''));
        $status = trim((string)($get['status'] ?? ''));
        $q = trim((string)($get['q'] ?? ''));

        $sql = 'SELECT e.*, b.name AS branch_name, v.id AS vehicle_id_join, v.reg_number AS vehicle_no_join
                FROM employees e
                LEFT JOIN branches b ON b.id = e.branch_id
                LEFT JOIN vehicles v ON v.id = e.vehicle_id
                WHERE 1=1';
        $params = [];

        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql .= ' AND (e.name LIKE ? OR e.emp_code LIKE ? OR e.phone LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }

        if ($emp_code !== '') {
            $sql .= ' AND e.emp_code LIKE ?';
            $params[] = '%' . $emp_code . '%';
        }
        if ($name !== '') {
            $sql .= ' AND e.name LIKE ?';
            $params[] = '%' . $name . '%';
        }
        if ($first_name !== '') {
            $sql .= ' AND e.first_name LIKE ?';
            $params[] = '%' . $first_name . '%';
        }
        if ($last_name !== '') {
            $sql .= ' AND e.last_name LIKE ?';
            $params[] = '%' . $last_name . '%';
        }
        if ($email !== '') {
            $sql .= ' AND e.email LIKE ?';
            $params[] = '%' . $email . '%';
        }
        if ($phone !== '') {
            $sql .= ' AND e.phone LIKE ?';
            $params[] = '%' . $phone . '%';
        }
        if ($address !== '') {
            $sql .= ' AND e.address LIKE ?';
            $params[] = '%' . $address . '%';
        }
        if ($position !== '') {
            $sql .= ' AND e.position LIKE ?';
            $params[] = '%' . $position . '%';
        }
        if ($role !== '') {
            $sql .= ' AND e.role LIKE ?';
            $params[] = '%' . $role . '%';
        }
        if ($license_number !== '') {
            $sql .= ' AND e.license_number LIKE ?';
            $params[] = '%' . $license_number . '%';
        }
        if ($license_from !== '') {
            $sql .= ' AND e.license_expiry >= ?';
            $params[] = $license_from;
        }
        if ($license_to !== '') {
            $sql .= ' AND e.license_expiry <= ?';
            $params[] = $license_to;
        }
        if ($vehicle_like !== '') {
            $sql .= ' AND COALESCE(v.reg_number, v.plate_no, v.vehicle_no, "") LIKE ?';
            $params[] = '%' . $vehicle_like . '%';
        }
        if ($branch_id > 0) {
            $sql .= ' AND e.branch_id = ?';
            $params[] = $branch_id;
        }
        if ($join_from !== '') {
            $sql .= ' AND e.join_date >= ?';
            $params[] = $join_from;
        }
        if ($join_to !== '') {
            $sql .= ' AND e.join_date <= ?';
            $params[] = $join_to;
        }
        if ($status !== '') {
            $sql .= ' AND e.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY e.created_at DESC, e.id DESC LIMIT 500';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
