<?php
class Sms
{
    private array $config;
    private bool $enabled;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->enabled = (bool)($config['enabled'] ?? false);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function sendText(string $phone, string $message): bool
    {
        if (!$this->enabled) { return false; }
        $phone = trim($phone);
        $message = trim($message);
        if ($phone === '' || $message === '') { return false; }
        $provider = strtolower((string)($this->config['provider'] ?? 'http'));
        try {
            if ($provider === 'twilio') {
                return $this->sendViaTwilio($phone, $message);
            }
            return $this->sendViaHttp($phone, $message);
        } catch (\Throwable $e) {
            @error_log('[TMS SMS] send error: ' . $e->getMessage());
            return false;
        }
    }

    private function sendViaTwilio(string $phone, string $message): bool
    {
        $sid = (string)($this->config['twilio']['account_sid'] ?? '');
        $token = (string)($this->config['twilio']['auth_token'] ?? '');
        $from = (string)($this->config['twilio']['from_number'] ?? '');
        if ($sid === '' || $token === '' || $from === '') { return false; }
        if (!class_exists('Twilio\Rest\Client')) { return false; }
        $client = new \Twilio\Rest\Client($sid, $token);
        $msg = $client->messages->create($phone, ['from' => $from, 'body' => $message]);
        return !empty($msg->sid);
    }

    private function sendViaHttp(string $phone, string $message): bool
    {
        $http = $this->config['http'] ?? [];
        $endpoint = (string)($http['endpoint'] ?? '');
        if ($endpoint === '') { return false; }
        $method = strtoupper((string)($http['method'] ?? 'POST'));
        $headers = (array)($http['headers'] ?? []);
        $fields = (array)($http['fields'] ?? []);
        // Replace placeholders
        $phoneParam = (string)($http['phone_param'] ?? 'phone');
        $messageParam = (string)($http['message_param'] ?? 'message');
        $fields[$phoneParam] = $fields[$phoneParam] ?? '{phone}';
        $fields[$messageParam] = $fields[$messageParam] ?? '{message}';
        foreach ($fields as $k => $v) {
            if (!is_string($v)) continue;
            $v = str_replace(['{phone}','{message}'], [$phone, $message], $v);
            $fields[$k] = $v;
        }
        // Build request
        $opts = [
            'http' => [
                'method' => $method,
                'timeout' => 20,
                'ignore_errors' => true,
            ]
        ];
        $hdrs = [];
        foreach ($headers as $hk => $hv) { $hdrs[] = $hk . ': ' . $hv; }
        $content = http_build_query($fields);
        if ($method === 'GET') {
            $endpoint .= (strpos($endpoint, '?') === false ? '?' : '&') . $content;
        } else {
            $opts['http']['content'] = $content;
            $hdrs[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        if ($hdrs) { $opts['http']['header'] = implode("\r\n", $hdrs); }
        $ctx = stream_context_create($opts);
        $res = @file_get_contents($endpoint, false, $ctx);
        return $res !== false;
    }
}
