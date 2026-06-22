<?php

declare(strict_types=1);

/**
 * Unified Accounting Module — navigation, routing, and page rendering.
 * Wraps existing repositories/controllers without replacing business logic.
 */
class AccountingModule
{
    /** @return array<string, array{label: string, icon: string, items: list<array<string, string>>}> */
    public static function navSections(): array
    {
        return [
            'overview' => [
                'label' => 'Overview',
                'icon' => 'bi-speedometer2',
                'items' => [
                    ['action' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-grid-1x2'],
                ],
            ],
            'master' => [
                'label' => 'Master Data',
                'icon' => 'bi-diagram-3',
                'items' => [
                    ['action' => 'chart', 'label' => 'Chart of Accounts', 'icon' => 'bi-list-nested'],
                    ['action' => 'opening_balances', 'label' => 'Opening Balances', 'icon' => 'bi-currency-exchange'],
                ],
            ],
            'transactions' => [
                'label' => 'Transactions',
                'icon' => 'bi-journal-plus',
                'items' => [
                    ['action' => 'entry', 'label' => 'Journal Entry', 'icon' => 'bi-journal-text', 'params' => ['voucher_type' => 'JOURNAL']],
                    ['action' => 'entry', 'label' => 'Cash Payment', 'icon' => 'bi-cash-coin', 'params' => ['voucher_type' => 'PAYMENT', 'payment_mode' => 'CASH']],
                    ['action' => 'entry', 'label' => 'Cash Receipt', 'icon' => 'bi-cash-stack', 'params' => ['voucher_type' => 'RECEIPT', 'payment_mode' => 'CASH']],
                    ['action' => 'entry', 'label' => 'Bank Payment', 'icon' => 'bi-bank', 'params' => ['voucher_type' => 'PAYMENT', 'payment_mode' => 'BANK']],
                    ['action' => 'entry', 'label' => 'Bank Receipt', 'icon' => 'bi-bank2', 'params' => ['voucher_type' => 'RECEIPT', 'payment_mode' => 'BANK']],
                    ['action' => 'entry', 'label' => 'Contra Voucher', 'icon' => 'bi-arrow-left-right', 'params' => ['voucher_type' => 'CONTRA']],
                    ['action' => 'entry', 'label' => 'Transfer Voucher', 'icon' => 'bi-shuffle', 'params' => ['voucher_type' => 'TRANSFER']],
                    ['action' => 'vouchers', 'label' => 'Voucher Register', 'icon' => 'bi-table'],
                ],
            ],
            'ledgers' => [
                'label' => 'Books & Ledgers',
                'icon' => 'bi-journal-check',
                'items' => [
                    ['action' => 'daybook', 'label' => 'Day Book', 'icon' => 'bi-calendar-day'],
                    ['action' => 'ledger', 'label' => 'General Ledger', 'icon' => 'bi-journal-richtext'],
                    ['action' => 'customer_ledger', 'label' => 'Customer Ledger', 'icon' => 'bi-person-lines-fill'],
                    ['action' => 'supplier_ledger', 'label' => 'Supplier Ledger', 'icon' => 'bi-truck'],
                    ['action' => 'cash_book', 'label' => 'Cash Book', 'icon' => 'bi-wallet2'],
                    ['action' => 'bank_book', 'label' => 'Bank Book', 'icon' => 'bi-bank'],
                ],
            ],
            'reports' => [
                'label' => 'Financial Reports',
                'icon' => 'bi-bar-chart-line',
                'items' => [
                    ['action' => 'trial_balance', 'label' => 'Trial Balance', 'icon' => 'bi-balance-scale'],
                    ['action' => 'profit_loss', 'label' => 'Profit & Loss', 'icon' => 'bi-graph-up-arrow'],
                    ['action' => 'balance_sheet', 'label' => 'Balance Sheet', 'icon' => 'bi-clipboard-data'],
                    ['action' => 'reports', 'label' => 'All Reports', 'icon' => 'bi-folder2-open'],
                ],
            ],
            'integration' => [
                'label' => 'TMS Integration',
                'icon' => 'bi-plug',
                'items' => [
                    ['action' => 'integrations', 'label' => 'Module Links', 'icon' => 'bi-link-45deg'],
                    ['action' => 'settings', 'label' => 'Settings', 'icon' => 'bi-gear'],
                ],
            ],
        ];
    }

    public static function pageTitle(string $action): string
    {
        $titles = [
            'dashboard' => 'Accounting Dashboard',
            'chart' => 'Chart of Accounts',
            'opening_balances' => 'Opening Balances',
            'entry' => 'Voucher Entry',
            'vouchers' => 'Voucher Register',
            'daybook' => 'Day Book',
            'ledger' => 'General Ledger',
            'customer_ledger' => 'Customer Ledger',
            'supplier_ledger' => 'Supplier Ledger',
            'cash_book' => 'Cash Book',
            'bank_book' => 'Bank Book',
            'trial_balance' => 'Trial Balance',
            'profit_loss' => 'Profit & Loss Statement',
            'balance_sheet' => 'Balance Sheet',
            'reports' => 'Financial Reports',
            'integrations' => 'TMS Integration',
            'settings' => 'Accounting Settings',
        ];

        return $titles[$action] ?? 'Accounting';
    }

    public static function url(string $action, array $params = []): string
    {
        $query = array_merge(['page' => 'accounting', 'action' => $action], $params);

        return Helpers::baseUrl('index.php?' . http_build_query($query));
    }

    /** @return list<string> */
    public static function allowedActions(): array
    {
        $actions = ['dashboard', 'chart', 'opening_balances', 'entry', 'vouchers'];
        $actions = array_merge($actions, [
            'daybook', 'ledger', 'customer_ledger', 'supplier_ledger', 'cash_book', 'bank_book',
            'trial_balance', 'profit_loss', 'balance_sheet', 'reports', 'integrations', 'settings',
        ]);

        return $actions;
    }

    public static function resolveView(string $action): string
    {
        $map = [
            'dashboard' => 'accounting/dashboard',
            'chart' => 'accounting/accounts_master',
            'opening_balances' => 'accounting/accounts_master',
            'entry' => 'accounting/voucher_entry',
            'vouchers' => 'accounting/vouchers_list',
            'daybook' => 'accounting/day_book',
            'ledger' => 'accounting/ledger',
            'customer_ledger' => 'accounting/customer_ledger',
            'supplier_ledger' => 'accounting/ledger',
            'cash_book' => 'accounting/cash_book',
            'bank_book' => 'accounting/bank_book',
            'trial_balance' => 'accounting/trial_balance',
            'profit_loss' => 'accounting/profit_loss',
            'balance_sheet' => 'accounting/balance_sheet',
            'reports' => 'accounting/reports_hub',
            'integrations' => 'accounting/integrations',
            'settings' => 'accounting/settings',
        ];

        return $map[$action] ?? 'accounting/dashboard';
    }

    /** @param array<string, mixed> $data */
    public static function renderPage(string $action, array $data = []): void
    {
        if (!in_array($action, self::allowedActions(), true)) {
            $action = 'dashboard';
        }

        $data['accAction'] = $action;
        $data['accTitle'] = self::pageTitle($action);
        $data['accNav'] = self::navSections();
        $data['accBaseUrl'] = Helpers::baseUrl('');
        $data['accCsrf'] = Helpers::csrfToken();
        $data['accFullBleed'] = ($action === 'entry');

        $contentView = self::resolveView($action);
        $contentFile = __DIR__ . '/../views/' . $contentView . '.php';
        if (!is_file($contentFile)) {
            http_response_code(404);
            echo 'Accounting view not found';
            return;
        }

        extract($data);
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/accounting/partials/shell_start.php';
        include $contentFile;
        include __DIR__ . '/../views/accounting/partials/shell_end.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
}
