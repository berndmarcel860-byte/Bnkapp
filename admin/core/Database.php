<?php
/**
 * BnkApp Admin — Database Connection (PDO Singleton)
 *
 * Provides a single shared PDO instance to every model.
 * Usage: $pdo = Database::getInstance();
 */
declare(strict_types=1);

namespace BnkApp\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    /** Prevent direct instantiation */
    private function __construct() {}
    private function __clone() {}

    /**
     * Returns the single shared PDO connection.
     * Creates it on first call using values from config/config.php.
     *
     * @throws RuntimeException on connection failure
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $cfg = require CONFIG_PATH . '/config.php';
            $db  = $cfg['db'];

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $db['host'],
                $db['port'],
                $db['name'],
                $db['charset']
            );

            try {
                self::$instance = new PDO($dsn, $db['user'], $db['password'], $db['options']);
            } catch (PDOException $e) {
                // Never expose credentials or detailed messages outside logs
                error_log('[BnkApp] DB connection failed: ' . $e->getMessage());
                throw new RuntimeException('Database connection failed. Please try again later.');
            }
        }

        return self::$instance;
    }
}
