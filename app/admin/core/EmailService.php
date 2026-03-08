<?php
/**
 * BnkApp Admin — Email Service (PHPMailer)
 *
 * Loads SMTP credentials from the `smtp_settings` table (id = 1).
 * Falls back to environment variables for compatibility when no DB row
 * has been configured yet.
 *
 * Environment-variable fallbacks:
 *   MAIL_FROM_ADDRESS  MAIL_FROM_NAME
 *   MAIL_HOST          MAIL_PORT          MAIL_ENCRYPTION
 *   MAIL_USER          MAIL_PASS
 */
declare(strict_types=1);

namespace BnkApp\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailService
{
    private string  $fromAddress;
    private string  $fromName;
    private string  $smtpHost;
    private int     $smtpPort;
    private string  $smtpUser;
    private string  $smtpPass;
    private string  $encryption; // 'tls' | 'ssl' | 'none' | ''

    public function __construct()
    {
        $this->loadSettings();
    }

    // ------------------------------------------------------------------
    // Settings loader
    // ------------------------------------------------------------------

    /**
     * Load SMTP config from the smtp_settings table (row id = 1).
     * Falls back to env vars when the table row is empty or unavailable.
     */
    private function loadSettings(): void
    {
        $row = $this->fetchSettingsRow();

        // Decide whether to use DB values or env vars
        $dbHost = trim((string)($row['host'] ?? ''));

        if ($dbHost !== '') {
            // DB-configured SMTP
            $this->fromAddress = (string)($row['from_address'] ?? 'noreply@example.com');
            $this->fromName    = (string)($row['from_name']    ?? 'BnkApp');
            $this->smtpHost    = $dbHost;
            $this->smtpPort    = (int)($row['port'] ?? 587);
            $this->smtpUser    = (string)($row['username'] ?? '');
            $this->smtpPass    = (string)($row['password'] ?? '');
            $this->encryption  = strtolower((string)($row['encryption'] ?? 'tls'));
        } else {
            // Env-var fallback
            $this->fromAddress = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@bnkapp.example';
            $this->fromName    = getenv('MAIL_FROM_NAME')    ?: 'BnkApp';
            $this->smtpHost    = (string)(getenv('MAIL_HOST') ?: '');
            $this->smtpPort    = (int)(getenv('MAIL_PORT')   ?: 587);
            $this->smtpUser    = (string)(getenv('MAIL_USER') ?: '');
            $this->smtpPass    = (string)(getenv('MAIL_PASS') ?: '');
            $this->encryption  = strtolower((string)(getenv('MAIL_ENCRYPTION') ?: 'tls'));
        }
    }

    private function fetchSettingsRow(): array
    {
        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM smtp_settings WHERE id = 1 LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch();
            return is_array($row) ? $row : [];
        } catch (\Throwable) {
            return [];
        }
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    /**
     * Send an email using a stored template slug.
     * Variables are interpolated as {{variable_name}}.
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
            : '';

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

        try {
            return $this->sendViaPHPMailer($toAddress, $toName, $subject, $bodyHtml, $bodyText);
        } catch (\Throwable $e) {
            error_log('[EmailService] Send failed: ' . $e->getMessage());
            return false;
        }
    }

    // ------------------------------------------------------------------
    // PHPMailer transport
    // ------------------------------------------------------------------

    private function sendViaPHPMailer(
        string $toAddress,
        string $toName,
        string $subject,
        string $bodyHtml,
        string $bodyText
    ): bool {
        $this->requirePhpMailer();

        $mail = new PHPMailer(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Encoding = PHPMailer::ENCODING_QUOTED_PRINTABLE;

        if ($this->smtpHost !== '') {
            // Use SMTP
            $mail->isSMTP();
            $mail->Host       = $this->smtpHost;
            $mail->Port       = $this->smtpPort;

            // SMTPSecure
            $mail->SMTPSecure = match ($this->encryption) {
                'ssl'  => PHPMailer::ENCRYPTION_SMTPS,
                'tls'  => PHPMailer::ENCRYPTION_STARTTLS,
                default => '',
            };

            if ($this->smtpUser !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $this->smtpUser;
                $mail->Password = $this->smtpPass;
            }
        } else {
            // Fall back to PHP mail()
            $mail->isMail();
        }

        $mail->setFrom($this->fromAddress, $this->fromName);
        $mail->addAddress($toAddress, $toName);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $bodyHtml;
        $mail->AltBody = $bodyText !== '' ? $bodyText : $this->htmlToText($bodyHtml);

        $mail->send();
        return true;
    }

    /**
     * Bootstrap the PHPMailer autoloader if needed.
     * Supports both a project-root vendor/ (composer install) and a
     * vendor/ that sits relative to the admin directory.
     */
    private function requirePhpMailer(): void
    {
        if (class_exists(PHPMailer::class, false)) {
            return;
        }

        $candidates = [
            // Project root vendor (composer install in repo root)
            realpath(__DIR__ . '/../../../vendor/autoload.php'),
            // Vendor next to the admin directory
            realpath(__DIR__ . '/../../vendor/autoload.php'),
        ];

        foreach ($candidates as $path) {
            if ($path !== false && file_exists($path)) {
                require_once $path;
                return;
            }
        }

        throw new \RuntimeException(
            'PHPMailer not found. Run: composer require phpmailer/phpmailer'
        );
    }

    // ------------------------------------------------------------------
    // Test SMTP connection
    // ------------------------------------------------------------------

    /**
     * Attempt to connect to the SMTP server, verify credentials, and
     * persist the result in smtp_settings.
     *
     * @return array{ok: bool, message: string}
     */
    public function testSmtpConnection(): array
    {
        $this->requirePhpMailer();

        $ok      = false;
        $message = '';

        try {
            if ($this->smtpHost === '') {
                throw new \RuntimeException('No SMTP host configured.');
            }

            $smtp = new SMTP();
            $smtp->Timeout = 10;

            $secure = match ($this->encryption) {
                'ssl'  => 'ssl',
                default => '',
            };

            if (!$smtp->connect($this->smtpHost, $this->smtpPort, 10, $secure)) {
                throw new \RuntimeException('Could not connect to SMTP server.');
            }

            // EHLO
            $smtp->hello(gethostname());

            // STARTTLS
            if ($this->encryption === 'tls') {
                $smtp->startTLS();
                $smtp->hello(gethostname());
            }

            // Auth
            if ($this->smtpUser !== '') {
                if (!$smtp->authenticate($this->smtpUser, $this->smtpPass)) {
                    throw new \RuntimeException('SMTP authentication failed.');
                }
            }

            $smtp->quit();
            $ok      = true;
            $message = 'Connection and authentication successful.';

        } catch (\Throwable $e) {
            $message = $e->getMessage();
        }

        // Persist test result
        try {
            $db = Database::getInstance();
            $db->prepare(
                "UPDATE smtp_settings
                    SET last_tested_at = NOW(), last_test_ok = ?, last_test_error = ?
                  WHERE id = 1"
            )->execute([$ok ? 1 : 0, $ok ? null : $message]);
        } catch (\Throwable) {
            // Non-fatal — do not mask the actual result
        }

        return ['ok' => $ok, 'message' => $message];
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
            return is_array($row) ? $row : null;
        } catch (\Throwable $e) {
            error_log('[EmailService] Template load failed: ' . $e->getMessage());
            return null;
        }
    }

    // ------------------------------------------------------------------
    // Utilities
    // ------------------------------------------------------------------

    private function interpolate(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{{' . $key . '}}', (string)$value, $template);
        }
        return (string)preg_replace('/\{\{[a-zA-Z0-9_]+\}\}/', '', $template);
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
