<?php

namespace App\Services;

use App\Core\Logger;

class MailService
{
    /**
     * @var array<string, mixed>
     */
    private array $config;
    
    /**
     * @var string[]
     */
    private array $smtpTranscript = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(string $toEmail, string $toName, string $subject, string $bodyText): bool
    {
        $driver = strtolower(trim((string)($this->config['driver'] ?? 'mail')));

        if ($driver === 'smtp') {
            return $this->sendViaSmtp($toEmail, $toName, $subject, $bodyText);
        }

        return $this->sendViaMail($toEmail, $subject, $bodyText);
    }

    private function sendViaMail(string $toEmail, string $subject, string $bodyText): bool
    {
        if (!function_exists('mail')) {
            return false;
        }

        $fromAddress = trim((string)($this->config['from_address'] ?? 'no-reply@localhost'));
        $fromName = trim((string)($this->config['from_name'] ?? 'Library App'));
        $encodedFromName = $this->encodeHeaderText($fromName);
        $encodedSubject = $this->encodeHeaderText($subject);

        $headers =
            "From: {$encodedFromName} <{$fromAddress}>\r\n" .
            "Reply-To: {$fromAddress}\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n";

        return @mail($toEmail, $encodedSubject, $bodyText, $headers);
    }

    private function sendViaSmtp(string $toEmail, string $toName, string $subject, string $bodyText): bool
    {
        $this->smtpTranscript = [];
        $host = trim((string)($this->config['host'] ?? ''));
        $port = (int)($this->config['port'] ?? 587);
        $encryption = strtolower(trim((string)($this->config['encryption'] ?? 'tls')));
        $username = (string)($this->config['username'] ?? '');
        $password = (string)($this->config['password'] ?? '');
        $timeout = max(5, (int)($this->config['timeout'] ?? 30));
        $fromAddress = trim((string)($this->config['from_address'] ?? 'no-reply@localhost'));
        $fromName = trim((string)($this->config['from_name'] ?? 'Library App'));

        if ($host === '' || $port <= 0) {
            Logger::error('SMTP configuration is incomplete.', ['host' => $host, 'port' => $port]);
            return false;
        }

        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            $encryption = 'tls';
        }

        $remoteHost = $encryption === 'ssl' ? "ssl://{$host}" : $host;
        $remoteSocket = "{$remoteHost}:{$port}";

        $errno = 0;
        $errstr = '';
        $socketWarning = null;
        set_error_handler(static function (int $severity, string $message) use (&$socketWarning): bool {
            $socketWarning = $message;
            return true;
        });
        $socket = stream_socket_client($remoteSocket, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
        restore_error_handler();

        if (!$socket) {
            Logger::error('SMTP connect failed.', [
                'remote' => $remoteSocket,
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption,
                'errno' => $errno,
                'error' => $errstr,
                'warning' => $socketWarning,
            ]);
            return false;
        }

        stream_set_timeout($socket, $timeout);

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO localhost', [250]);

            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                $cryptoEnabled = stream_socket_enable_crypto(
                    $socket,
                    true,
                    STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT
                );
                if ($cryptoEnabled !== true) {
                    throw new \RuntimeException('Unable to enable TLS encryption for SMTP connection.');
                }
                $this->command($socket, 'EHLO localhost', [250]);
            }

            if ($username !== '' || $password !== '') {
                $authOk = false;
                try {
                    $this->command($socket, 'AUTH LOGIN', [334]);
                    $this->command($socket, base64_encode($username), [334], false);
                    $this->command($socket, base64_encode($password), [235], false);
                    $authOk = true;
                } catch (\Throwable $e) {
                    $this->smtpTranscript[] = 'AUTH LOGIN failed, trying AUTH PLAIN.';
                    $authPayload = base64_encode("\0{$username}\0{$password}");
                    $this->command($socket, 'AUTH PLAIN ' . $authPayload, [235], false);
                    $authOk = true;
                }

                if (!$authOk) {
                    throw new \RuntimeException('SMTP authentication failed.');
                }
            }

            $this->command($socket, 'MAIL FROM:<' . $fromAddress . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);

            $headers = $this->buildHeaders($fromAddress, $fromName, $toEmail, $toName, $subject);
            $message = $headers . "\r\n" . $this->dotStuff($bodyText) . "\r\n.";
            $this->command($socket, $message, [250]);
            $this->command($socket, 'QUIT', [221]);

            fclose($socket);
            return true;
        } catch (\Throwable $e) {
            Logger::error('SMTP send failed.', [
                'error' => $e->getMessage(),
                'to' => $toEmail,
                'smtp_transcript' => $this->sanitizeTranscript($this->smtpTranscript),
            ]);
            fclose($socket);
            return false;
        }
    }

    private function buildHeaders(string $fromAddress, string $fromName, string $toEmail, string $toName, string $subject): string
    {
        $encodedFromName = $this->encodeHeaderText($fromName);
        $encodedToName = $toName !== '' ? $this->encodeHeaderText($toName) . " <{$toEmail}>" : $toEmail;
        $encodedSubject = $this->encodeHeaderText($subject);

        $headers = [];
        $headers[] = 'Date: ' . gmdate('D, d M Y H:i:s O');
        $headers[] = "From: {$encodedFromName} <{$fromAddress}>";
        $headers[] = "Reply-To: {$fromAddress}";
        $headers[] = "To: {$encodedToName}";
        $headers[] = "Subject: {$encodedSubject}";
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';

        return implode("\r\n", $headers);
    }

    private function dotStuff(string $body): string
    {
        $normalized = preg_replace("/\r\n|\r|\n/", "\r\n", $body) ?? $body;
        return preg_replace('/^\./m', '..', $normalized) ?? $normalized;
    }

    /**
     * @param resource $socket
     * @param int[] $expectedCodes
     */
    private function command($socket, string $command, array $expectedCodes, bool $logCommand = true): void
    {
        if ($logCommand) {
            $this->smtpTranscript[] = 'C: ' . $command;
        } else {
            $this->smtpTranscript[] = 'C: [redacted]';
        }

        $written = @fwrite($socket, $command . "\r\n");
        if ($written === false) {
            throw new \RuntimeException('Unable to write SMTP command.');
        }

        $this->expect($socket, $expectedCodes);
    }

    /**
     * @param resource $socket
     * @param int[] $expectedCodes
     */
    private function expect($socket, array $expectedCodes): void
    {
        $response = $this->readResponse($socket);
        foreach (explode("\n", str_replace("\r", '', $response)) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $this->smtpTranscript[] = 'S: ' . $line;
            }
        }
        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new \RuntimeException('Unexpected SMTP response: ' . trim($response));
        }
    }

    /**
     * @param resource $socket
     */
    private function readResponse($socket): string
    {
        $response = '';

        while (!feof($socket)) {
            $line = fgets($socket, 515);
            if ($line === false) {
                break;
            }

            $response .= $line;

            if (strlen($line) < 4) {
                continue;
            }

            if ($line[3] === ' ') {
                break;
            }
        }

        if ($response === '') {
            throw new \RuntimeException('Empty SMTP response.');
        }

        return $response;
    }

    private function encodeHeaderText(string $text): string
    {
        if ($text === '') {
            return '';
        }

        if (preg_match('/[^\x20-\x7E]/', $text) === 1) {
            return '=?UTF-8?B?' . base64_encode($text) . '?=';
        }

        return $text;
    }

    /**
     * @param string[] $lines
     * @return string[]
     */
    private function sanitizeTranscript(array $lines): array
    {
        $out = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, 'C: AUTH PLAIN ')) {
                $out[] = 'C: AUTH PLAIN [redacted]';
                continue;
            }
            $out[] = $line;
        }

        return $out;
    }
}
