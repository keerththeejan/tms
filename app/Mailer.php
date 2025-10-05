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
        if (class_exists('\\PHPMailer\PHPMailer\PHPMailer')) {
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
            // Optional debug level from config: 0 (off), 1 (client msg), 2 (client+server)
            $mail->SMTPDebug = (int)($this->config['debug'] ?? 0);
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
            // IMPORTANT: set SMTP credentials (were missing)
            $mail->Username = (string)($this->config['username'] ?? '');
            $mail->Password = (string)($this->config['password'] ?? '');
            $mail->setFrom((string)($this->config['from_email'] ?? 'no-reply@example.com'), (string)($this->config['from_name'] ?? 'TMS'));
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = ($textBody !== '') ? $textBody : strip_tags($htmlBody);
            return $mail->send();
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
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

    public function getLastError(): string
    {
        return $this->lastError;
    }
}
