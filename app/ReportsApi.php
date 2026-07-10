<?php

declare(strict_types=1);

/**
 * JSON API for Reports module (rep_action parameter).
 */
class ReportsApi
{
    public static function json(array $data, int $code = 200): void
    {
        if (isset($data['ok']) && !isset($data['success'])) {
            $data['success'] = (bool) $data['ok'];
        }
        if (!isset($data['ok']) && isset($data['success'])) {
            $data['ok'] = (bool) $data['success'];
        }

        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function dispatch(PDO $pdo): void
    {
        if (!Auth::canViewReports()) {
            self::json(['ok' => false, 'success' => false, 'message' => 'Forbidden'], 403);

            return;
        }

        $action = $_GET['rep_action'] ?? $_POST['rep_action'] ?? '';
        if ($action === '') {
            self::json(['ok' => false, 'success' => false, 'message' => 'Missing rep_action'], 400);

            return;
        }

        $filters = ReportsRepository::normalizeFilters(array_merge($_GET, $_POST));

        try {
            switch ($action) {
                case 'boot':
                    self::json([
                        'ok' => true,
                        'success' => true,
                        'data' => ReportsRepository::bootMeta($pdo),
                    ]);
                    break;

                case 'dashboard':
                    self::json([
                        'ok' => true,
                        'success' => true,
                        'data' => ReportsRepository::dashboard($pdo, $filters),
                    ]);
                    break;

                case 'export':
                    $type = preg_replace('/[^a-z_]/', '', strtolower((string) ($_GET['type'] ?? 'summary')));
                    ReportsRepository::exportCsv($pdo, $filters, $type);
                    break;

                default:
                    self::json(['ok' => false, 'success' => false, 'message' => 'Unknown rep_action'], 400);
            }
        } catch (Throwable $e) {
            self::json([
                'ok' => false,
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
