<?php
/**
 * BnkApp — BnkApp\Core\Database bridge for portal context.
 *
 * Provides a minimal BnkApp\Core\Database class that forwards to the
 * portal's Database singleton so that the shared EmailService can access
 * the database without requiring a separate connection.
 *
 * Included once by the portal EmailService before loading the shared
 * admin EmailService class.
 */
declare(strict_types=1);

namespace BnkApp\Core;

class Database
{
    public static function getInstance(): \PDO
    {
        return \BnkPortal\Core\Database::getInstance();
    }
}
