<?php
/**
 * BnkApp Portal — Email Service
 *
 * Delegates to the shared admin EmailService implementation.
 * The admin and portal share the same database and email_templates table.
 */
declare(strict_types=1);

namespace BnkPortal\Core;

class EmailService
{
    /** @var \BnkApp\Core\EmailService */
    private \BnkApp\Core\EmailService $delegate;

    public function __construct()
    {
        $this->bootstrap();
        $this->delegate = new \BnkApp\Core\EmailService();
    }

    public function sendTemplate(string $slug, string $toAddress, string $toName, array $vars = []): bool
    {
        return $this->delegate->sendTemplate($slug, $toAddress, $toName, $vars);
    }

    public function send(string $toAddress, string $toName, string $subject, string $bodyHtml, string $bodyText = ''): bool
    {
        return $this->delegate->send($toAddress, $toName, $subject, $bodyHtml, $bodyText);
    }

    /**
     * Load the composer autoloader (for PHPMailer), the database bridge,
     * and the shared admin EmailService class, if they have not already
     * been loaded in this request.
     */
    private function bootstrap(): void
    {
        // Composer autoloader (PHPMailer lives here)
        $autoloaders = [
            realpath(__DIR__ . '/../../../../vendor/autoload.php'),
            realpath(__DIR__ . '/../../../vendor/autoload.php'),
        ];
        foreach ($autoloaders as $al) {
            if ($al !== false && file_exists($al) && !class_exists(\PHPMailer\PHPMailer\PHPMailer::class, false)) {
                require_once $al;
                break;
            }
        }

        if (!class_exists(\BnkApp\Core\Database::class, false)) {
            require_once __DIR__ . '/bridge/DatabaseBridge.php';
        }

        if (!class_exists(\BnkApp\Core\EmailService::class, false)) {
            $adminEmailService = dirname(dirname(dirname(__DIR__))) . '/admin/core/EmailService.php';
            if (file_exists($adminEmailService)) {
                require_once $adminEmailService;
            }
        }
    }
}
