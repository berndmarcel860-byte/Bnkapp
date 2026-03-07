<?php
/**
 * BnkApp Customer Portal — Public Entry Point
 */
declare(strict_types=1);

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

require CORE_PATH . '/App.php';

(new \BnkPortal\Core\App())->run();
