<?php

class Mailer
{
    private array $config;
    private string $lastError = '';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $this->lastError = '';
        // Try PHPMailer if available
        if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
            if (($this->config['use_smtp'] ?? false)) {
                return $this->sendViaPHPMailer($toEmail, $toName, $subject, $htmlBody, $textBody);
            }
            // Use PHPMailer's mail() transport (no SMTP) when use_smtp is false
            return $this->sendViaPHPMailerMail($toEmail, $toName, $subject, $htmlBody, $textBody);
        }
        // Fallback: native PHP mail
        return $this->sendViaMail($toEmail, $toName, $subject, $htmlBody);
    }

    private function sendViaPHPMailer(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->Host = (string)($this->config['host'] ?? '');
            $mail->Port = (int)($this->config['port'] ?? 587);
            $enc = (string)($this->config['encryption'] ?? 'tls');
            if ($enc === 'ssl') { $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; }
            elseif ($enc === 'tls') { $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; }
            $mail->SMTPAuth = true;
            if (!empty($this->config['auth_type'])) {
                $mail->AuthType = (string)$this->config['auth_type'];
            }
            // Optional debug level from config: prefer 'smtpDebug', fallback to 'debug'
            $mail->SMTPDebug = (int)($this->config['smtpDebug'] ?? ($this->config['debug'] ?? 0));
            if (isset($this->config['debugoutput'])) { $mail->Debugoutput = $this->config['debugoutput']; }
            // Optional: allow self-signed certs (not recommended for production)
            if (!empty($this->config['allow_self_signed'])) {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
            }
            // Credentials
            $mail->Username = (string)($this->config['username'] ?? '');
            $mail->Password = (string)($this->config['password'] ?? '');
            $mail->setFrom((string)($this->config['from_email'] ?? 'no-reply@example.com'), (string)($this->config['from_name'] ?? 'TMS'));
            // Optional reply-to
            if (!empty($this->config['reply_to'])) {
                $mail->addReplyTo((string)$this->config['reply_to']);
            }
            // Timeouts
            $mail->Timeout = (int)($this->config['timeout'] ?? 30);
            $mail->SMTPKeepAlive = false;

            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = ($textBody !== '') ? $textBody : strip_tags($htmlBody);
            return $mail->send();
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            @error_log('[TMS Mailer] send error: ' . $this->lastError);
            return false;
        }
    }

    // PHPMailer using PHP's mail() transport (no SMTP)
    private function sendViaPHPMailerMail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $headers = [];
        $from = (string)($this->config['from_email'] ?? 'no-reply@example.com');
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf-8';
        $headers[] = 'From: ' . ($this->config['from_name'] ?? 'TMS') . ' <' . $from . '>';
        $headersStr = implode("\r\n", $headers);
        $ok = @mail($toEmail, $subject, $htmlBody, $headersStr);
        if (!$ok) {
            $this->lastError = 'mail() returned false (local mail transport not configured).';
        }
        return $ok;
    }

    private function sendViaMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $headers = 'MIME-Version: 1.0' . "\r\n" .
                   'Content-type: text/html; charset=utf-8' . "\r\n" .
                   'From: TMS <no-reply@example.com>';
        $ok = @mail($toEmail, $subject, $htmlBody, $headers);
        if (!$ok) { $this->lastError = 'mail() returned false (no PHPMailer and no SMTP).'; }
        return $ok;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }
}
