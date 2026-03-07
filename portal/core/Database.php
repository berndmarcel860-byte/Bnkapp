<?php
/**
 * BnkApp Portal — Database (PDO Singleton)
 */
declare(strict_types=1);

namespace BnkPortal\Core;

class Database
{
    private static ?\PDO $instance = null;

    private function __construct() {}

    public static function getInstance(): \PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $cfg = require CONFIG_PATH . '/config.php';
        $db  = $cfg['database'];

        $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";

        self::$instance = new \PDO($dsn, $db['user'], $db['pass'], [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return self::$instance;
    }

    public static function prepare(string $sql): \PDOStatement
    {
        return self::getInstance()->prepare($sql);
    }

    public static function query(string $sql): \PDOStatement
    {
        return self::getInstance()->query($sql);
    }

    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }
}
