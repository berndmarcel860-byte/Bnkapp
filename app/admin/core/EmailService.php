<?php
/**
 * BnkApp Admin — Email Service
 *
 * Loads templates from the `email_templates` table, interpolates
 * {{variable}} placeholders, and sends HTML emails.
 *
 * Transport: SMTP (env vars) with fall-back to PHP mail().
 *
 * Environment variables:
 *   MAIL_FROM_ADDRESS  — sender address (default: noreply@bnkapp.example)
 *   MAIL_FROM_NAME     — sender name    (default: BnkApp)
 *   MAIL_HOST          — SMTP host      (omit to use mail())
 *   MAIL_PORT          — SMTP port      (default: 587)
 *   MAIL_USER          — SMTP username
 *   MAIL_PASS          — SMTP password
 *   MAIL_ENCRYPTION    — tls | ssl      (default: tls)
 */
declare(strict_types=1);

namespace BnkApp\Core;

class EmailService
{
    private string $fromAddress;
    private string $fromName;
    private ?string $smtpHost;
    private int    $smtpPort;
    private string $smtpUser;
    private string $smtpPass;
    private string $encryption;

    public function __construct()
    {
        $this->fromAddress = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@bnkapp.example';
        $this->fromName    = getenv('MAIL_FROM_NAME')    ?: 'BnkApp';
        $this->smtpHost    = getenv('MAIL_HOST')         ?: null;
        $this->smtpPort    = (int)(getenv('MAIL_PORT')   ?: 587);
        $this->smtpUser    = (string)(getenv('MAIL_USER') ?: '');
        $this->smtpPass    = (string)(getenv('MAIL_PASS') ?: '');
        $this->encryption  = strtolower((string)(getenv('MAIL_ENCRYPTION') ?: 'tls'));
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    /**
     * Send an email using a template slug.
     * Variables are interpolated as {{variable_name}}.
     *
     * @param string $slug      Template slug (email_templates.slug)
     * @param string $toAddress Recipient email address
     * @param string $toName    Recipient name
     * @param array  $vars      Associative array of placeholder values
     * @return bool             True on success, false on failure
     */
    public function sendTemplate(
        string $slug,
        string $toAddress,
        string $toName,
        array  $vars = []
    ): bool {
        $template = $this->loadTemplate($slug);
        if ($template === null || !$template['is_active']) {
            return false;
        }

        $subject  = $this->interpolate($template['subject'],   $vars);
        $bodyHtml = $this->interpolate($template['body_html'], $vars);
        $bodyText = $template['body_text']
            ? $this->interpolate($template['body_text'], $vars)
            : $this->htmlToText($bodyHtml);

        return $this->send($toAddress, $toName, $subject, $bodyHtml, $bodyText);
    }

    /**
     * Send a raw email (no template lookup).
     */
    public function send(
        string $toAddress,
        string $toName,
        string $subject,
        string $bodyHtml,
        string $bodyText = ''
    ): bool {
        if (!filter_var($toAddress, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if ($bodyText === '') {
            $bodyText = $this->htmlToText($bodyHtml);
        }

        try {
            if ($this->smtpHost !== null && $this->smtpHost !== '') {
                return $this->sendViaSMTP($toAddress, $toName, $subject, $bodyHtml, $bodyText);
            }
            return $this->sendViaMail($toAddress, $toName, $subject, $bodyHtml, $bodyText);
        } catch (\Throwable $e) {
            error_log('[EmailService] Send failed: ' . $e->getMessage());
            return false;
        }
    }

    // ------------------------------------------------------------------
    // Template loading
    // ------------------------------------------------------------------

    private function loadTemplate(string $slug): ?array
    {
        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM email_templates WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            return $row !== false ? $row : null;
        } catch (\PDOException $e) {
            error_log('[EmailService] Template load failed: ' . $e->getMessage());
            return null;
        }
    }

    // ------------------------------------------------------------------
    // Interpolation
    // ------------------------------------------------------------------

    private function interpolate(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{{' . $key . '}}', (string)$value, $template);
        }
        // Remove any unreplaced placeholders
        return (string)preg_replace('/\{\{[a-zA-Z0-9_]+\}\}/', '', $template);
    }

    // ------------------------------------------------------------------
    // PHP mail() transport
    // ------------------------------------------------------------------

    private function sendViaMail(
        string $toAddress,
        string $toName,
        string $subject,
        string $bodyHtml,
        string $bodyText
    ): bool {
        $boundary = 'bnkapp_' . bin2hex(random_bytes(12));
        $from     = $this->encodeHeader($this->fromName) . ' <' . $this->fromAddress . '>';
        $to       = $this->encodeHeader($toName) . ' <' . $toAddress . '>';

        $headers  = "From: {$from}\r\n";
        $headers .= "Reply-To: {$this->fromAddress}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: BnkApp/1.0\r\n";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= quoted_printable_encode($bodyText) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= quoted_printable_encode($bodyHtml) . "\r\n";
        $body .= "--{$boundary}--";

        return mail($to, $this->encodeHeader($subject), $body, $headers);
    }

    // ------------------------------------------------------------------
    // SMTP transport (native sockets — no external dependencies)
    // ------------------------------------------------------------------

    private function sendViaSMTP(
        string $toAddress,
        string $toName,
        string $subject,
        string $bodyHtml,
        string $bodyText
    ): bool {
        $host       = $this->smtpHost;
        $port       = $this->smtpPort;
        $encryption = $this->encryption;

        // Build socket address
        $socketAddr = match ($encryption) {
            'ssl'  => "ssl://{$host}:{$port}",
            default => "{$host}:{$port}",
        };

        $errno  = 0;
        $errstr = '';
        $socket = fsockopen($socketAddr, $port, $errno, $errstr, 10);
        if ($socket === false) {
            throw new \RuntimeException("SMTP connect failed ({$errno}): {$errstr}");
        }

        stream_set_timeout($socket, 15);

        $this->smtpExpect($socket, 220);
        $this->smtpSend($socket, "EHLO " . gethostname());
        $ehloResponse = $this->smtpRead($socket);

        // STARTTLS upgrade
        if ($encryption === 'tls' && str_contains($ehloResponse, 'STARTTLS')) {
            $this->smtpSend($socket, 'STARTTLS');
            $this->smtpExpect($socket, 220);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->smtpSend($socket, "EHLO " . gethostname());
            $this->smtpRead($socket);
        }

        // AUTH LOGIN
        if ($this->smtpUser !== '') {
            $this->smtpSend($socket, 'AUTH LOGIN');
            $this->smtpExpect($socket, 334);
            $this->smtpSend($socket, base64_encode($this->smtpUser));
            $this->smtpExpect($socket, 334);
            $this->smtpSend($socket, base64_encode($this->smtpPass));
            $this->smtpExpect($socket, 235);
        }

        // Envelope
        $this->smtpSend($socket, "MAIL FROM:<{$this->fromAddress}>");
        $this->smtpExpect($socket, 250);
        $this->smtpSend($socket, "RCPT TO:<{$toAddress}>");
        $this->smtpExpect($socket, [250, 251]);

        // DATA
        $this->smtpSend($socket, 'DATA');
        $this->smtpExpect($socket, 354);

        $boundary = 'bnkapp_' . bin2hex(random_bytes(12));
        $msgId    = bin2hex(random_bytes(16)) . '@' . gethostname();
        $date     = date('r');

        $message  = "Date: {$date}\r\n";
        $message .= "Message-ID: <{$msgId}>\r\n";
        $message .= "From: " . $this->encodeHeader($this->fromName) . " <{$this->fromAddress}>\r\n";
        $message .= "To: " . $this->encodeHeader($toName) . " <{$toAddress}>\r\n";
        $message .= "Subject: " . $this->encodeHeader($subject) . "\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $message .= "X-Mailer: BnkApp/1.0\r\n\r\n";

        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $message .= quoted_printable_encode($bodyText) . "\r\n";
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $message .= quoted_printable_encode($bodyHtml) . "\r\n";
        $message .= "--{$boundary}--\r\n";
        $message .= '.';

        fwrite($socket, $message . "\r\n");
        $this->smtpExpect($socket, 250);

        $this->smtpSend($socket, 'QUIT');
        fclose($socket);
        return true;
    }

    private function smtpSend($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    private function smtpRead($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if ($line[3] === ' ') break; // last line of multi-line response
        }
        return $response;
    }

    /** @param int|int[] $expected */
    private function smtpExpect($socket, int|array $expected): void
    {
        $response = $this->smtpRead($socket);
        $code     = (int)substr($response, 0, 3);
        $expected = (array)$expected;
        if (!in_array($code, $expected, true)) {
            throw new \RuntimeException("Unexpected SMTP response (expected " . implode('/', $expected) . "): {$response}");
        }
    }

    // ------------------------------------------------------------------
    // Utilities
    // ------------------------------------------------------------------

    private function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    private function htmlToText(string $html): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = preg_replace('/<\/p>/i',      "\n\n", $text ?? '');
        $text = preg_replace('/<\/tr>/i',     "\n",   $text ?? '');
        $text = preg_replace('/<\/td>/i',     "\t",   $text ?? '');
        $text = preg_replace('/<[^>]+>/',     '',     $text ?? '');
        $text = html_entity_decode($text ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string)preg_replace('/\n{3,}/', "\n\n", $text ?? ''));
    }
}
