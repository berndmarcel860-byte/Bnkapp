<?php
/**
 * BnkApp Portal — Configuration
 * Reads settings from environment variables (same DB as admin).
 */
declare(strict_types=1);

return [
    'app' => [
        'name'     => 'BnkApp Portal',
        'url'      => getenv('APP_URL')   ?: 'http://localhost/app/portal/public',
        'debug'    => (bool)(getenv('APP_DEBUG') ?: false),
        'timezone' => getenv('APP_TIMEZONE') ?: 'Europe/Berlin',
    ],

    'database' => [
        'host'    => getenv('DB_HOST')    ?: '127.0.0.1',
        'port'    => (int)(getenv('DB_PORT')    ?: 3306),
        'name'    => getenv('DB_NAME')    ?: 'bnkapp',
        'user'    => getenv('DB_USER')    ?: 'bnkapp',
        'pass'    => getenv('DB_PASS')    ?: '',
        'charset' => 'utf8mb4',
    ],

    'security' => [
        'bcrypt_cost' => (int)(getenv('BCRYPT_COST') ?: 12),
    ],
];
