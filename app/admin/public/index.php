<?php
/**
 * BnkApp Admin — Public Entry Point
 *
 * This is the ONLY file that should be web-accessible.
 * Point your web server document root to admin/public/.
 *
 * Apache example (.htaccess in this directory):
 *   RewriteEngine On
 *   RewriteCond %{REQUEST_FILENAME} !-f
 *   RewriteRule ^ index.php [QSA,L]
 *
 * Nginx example:
 *   root /var/www/bnkapp/admin/public;
 *   try_files $uri $uri/ /index.php?$query_string;
 */
declare(strict_types=1);

// ---------------------------------------------------------------------------
// Bootstrap constants (paths used by autoloader, config, views, etc.)
// ---------------------------------------------------------------------------
define('ADMIN_ROOT',    dirname(__DIR__));
define('CONFIG_PATH',   ADMIN_ROOT . '/config');
define('CORE_PATH',     ADMIN_ROOT . '/core');
define('CONTROLLERS',   ADMIN_ROOT . '/controllers');
define('MODELS_PATH',   ADMIN_ROOT . '/models');
define('MIDDLEWARE',    ADMIN_ROOT . '/middleware');
define('HELPERS_PATH',  ADMIN_ROOT . '/helpers');
define('VIEWS_PATH',    ADMIN_ROOT . '/views');
define('STORAGE_PATH',  ADMIN_ROOT . '/storage');
define('LOGS_PATH',     STORAGE_PATH . '/logs');

// HTTP status code constants (duplicated from constants.php for early use)
require CONFIG_PATH . '/constants.php';

// ---------------------------------------------------------------------------
// Ensure storage/logs directory exists
// ---------------------------------------------------------------------------
if (!is_dir(LOGS_PATH)) {
    mkdir(LOGS_PATH, 0750, true);
}

// ---------------------------------------------------------------------------
// Boot the application
// ---------------------------------------------------------------------------
require CORE_PATH . '/App.php';
require CORE_PATH . '/Router.php';
require CORE_PATH . '/Request.php';
require CORE_PATH . '/Response.php';
require CORE_PATH . '/Session.php';
require CORE_PATH . '/Auth.php';
require CORE_PATH . '/Database.php';
require CORE_PATH . '/Controller.php';
require CORE_PATH . '/Model.php';

(new \BnkApp\Core\App())->run();
