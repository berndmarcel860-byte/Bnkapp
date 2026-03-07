<?php
/**
 * BnkApp Admin — Application Configuration
 *
 * Copy this file to config.local.php and fill in real values.
 * Never commit secrets to version control.
 */
declare(strict_types=1);

return [

    /* -----------------------------------------------------------------------
     * Application
     * -------------------------------------------------------------------- */
    'app' => [
        'name'     => 'BnkApp Admin',
        'version'  => '1.0.0',
        'env'      => getenv('APP_ENV') ?: 'production', // 'development' | 'production'
        'debug'    => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
        'url'      => getenv('APP_URL')  ?: 'https://admin.bnkapp.example',
        'timezone' => 'Europe/Berlin',
        'locale'   => 'en_GB',
    ],

    /* -----------------------------------------------------------------------
     * Database (MySQL 8.0+)
     * -------------------------------------------------------------------- */
    'db' => [
        'host'     => getenv('DB_HOST')     ?: '127.0.0.1',
        'port'     => (int)(getenv('DB_PORT')     ?: 3306),
        'name'     => getenv('DB_NAME')     ?: 'bnkapp',
        'user'     => getenv('DB_USER')     ?: 'bnkapp_admin',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset'  => 'utf8mb4',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ],
    ],

    /* -----------------------------------------------------------------------
     * Session
     * -------------------------------------------------------------------- */
    'session' => [
        'name'      => 'BNKAPP_ADMIN_SESS',
        'lifetime'  => 3600,          // seconds
        'secure'    => true,          // HTTPS only
        'http_only' => true,
        'same_site' => 'Strict',
    ],

    /* -----------------------------------------------------------------------
     * Security
     * -------------------------------------------------------------------- */
    'security' => [
        'bcrypt_cost'              => 12,
        'csrf_token_length'        => 32,
        'max_login_attempts'       => 5,
        'lockout_duration_minutes' => 15,
        'session_regenerate'       => true, // regenerate session ID after login
        'password_min_length'      => 12,
    ],

    /* -----------------------------------------------------------------------
     * Pagination
     * -------------------------------------------------------------------- */
    'pagination' => [
        'per_page' => 25,
    ],

    /* -----------------------------------------------------------------------
     * Logging
     * -------------------------------------------------------------------- */
    'logging' => [
        'path'  => dirname(__DIR__) . '/storage/logs/app.log',
        'level' => getenv('LOG_LEVEL') ?: 'warning', // debug|info|warning|error
    ],

];
