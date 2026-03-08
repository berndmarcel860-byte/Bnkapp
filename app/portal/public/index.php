<?php
/**
 * BnkApp Customer Portal — Public Entry Point
 *
 * Point your web server document root to app/portal/public/.
 *
 * Nginx example:
 *   root /var/www/bnkapp/app/portal/public;
 *   try_files $uri $uri/ /index.php?$query_string;
 */
declare(strict_types=1);

// ---------------------------------------------------------------------------
// Load environment variables written by the installer (app/env.php).
// ---------------------------------------------------------------------------
$_envFile = dirname(dirname(__DIR__)) . '/env.php';
if (file_exists($_envFile)) {
    require $_envFile;
}
unset($_envFile);

// Resolve the portal root
define('PORTAL_ROOT',   dirname(__DIR__));
define('CONFIG_PATH',   PORTAL_ROOT . '/config');
define('CORE_PATH',     PORTAL_ROOT . '/core');
define('CONTROLLERS',   PORTAL_ROOT . '/controllers');
define('MODELS_PATH',   PORTAL_ROOT . '/models');
define('MIDDLEWARE',    PORTAL_ROOT . '/middleware');
define('HELPERS_PATH',  PORTAL_ROOT . '/helpers');
define('VIEWS_PATH',    PORTAL_ROOT . '/views');

// HTTP status codes
define('HTTP_OK',                  200);
define('HTTP_CREATED',             201);
define('HTTP_FOUND',               302);
define('HTTP_BAD_REQUEST',         400);
define('HTTP_UNAUTHORIZED',        401);
define('HTTP_FORBIDDEN',           403);
define('HTTP_NOT_FOUND',           404);
define('HTTP_UNPROCESSABLE_ENTITY',422);
define('HTTP_INTERNAL_ERROR',      500);

// ---------------------------------------------------------------------------
// Composer autoloader (PHPMailer and any future Composer packages)
// ---------------------------------------------------------------------------
$_composerAutoload = realpath(__DIR__ . '/../../../../vendor/autoload.php');
if ($_composerAutoload !== false && file_exists($_composerAutoload)) {
    require_once $_composerAutoload;
}
unset($_composerAutoload);

require CORE_PATH . '/App.php';

(new \BnkPortal\Core\App())->run();
