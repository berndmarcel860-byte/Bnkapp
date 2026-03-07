<?php
/**
 * BnkApp Admin — Application Bootstrap
 *
 * Loaded by public/index.php.
 * Initialises constants, autoloader, session, security headers,
 * defines all routes, and dispatches the request.
 */
declare(strict_types=1);

namespace BnkApp\Core;

class App
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    /**
     * Boot the application:
     *   1. Set PHP runtime options
     *   2. Register the PSR-4-style autoloader
     *   3. Start the session
     *   4. Set security response headers
     *   5. Register all routes
     *   6. Dispatch the request
     */
    public function run(): void
    {
        $this->configure();
        $this->registerAutoloader();
        Session::start();
        Response::securityHeaders();
        $this->registerRoutes();
        $this->router->dispatch(new Request());
    }

    // ------------------------------------------------------------------
    // PHP runtime configuration
    // ------------------------------------------------------------------

    private function configure(): void
    {
        $cfg = require CONFIG_PATH . '/config.php';
        $app = $cfg['app'];

        date_default_timezone_set($app['timezone']);
        ini_set('display_errors', $app['debug'] ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', $cfg['logging']['path']);

        if ($app['debug']) {
            error_reporting(E_ALL);
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
        }
    }

    // ------------------------------------------------------------------
    // Autoloader (PSR-4 style)
    // ------------------------------------------------------------------

    private function registerAutoloader(): void
    {
        spl_autoload_register(function (string $class): void {
            // Namespace prefix: BnkApp\
            $prefix = 'BnkApp\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = substr($class, strlen($prefix));

            // Map sub-namespaces to directories
            $map = [
                'Core\\'        => CORE_PATH,
                'Controllers\\' => CONTROLLERS,
                'Models\\'      => MODELS_PATH,
                'Middleware\\'  => MIDDLEWARE,
                'Helpers\\'     => HELPERS_PATH,
            ];

            foreach ($map as $ns => $dir) {
                if (str_starts_with($relative, $ns)) {
                    $file = $dir . '/' . substr($relative, strlen($ns)) . '.php';
                    if (file_exists($file)) {
                        require_once $file;
                    }
                    return;
                }
            }
        });
    }

    // ------------------------------------------------------------------
    // Route definitions
    // ------------------------------------------------------------------

    private function registerRoutes(): void
    {
        $r = $this->router;

        // ---- Authentication (no auth middleware) ----
        $r->get( '/auth/login',  'AuthController@showLogin');
        $r->post('/auth/login',  'AuthController@login');
        $r->post('/auth/logout', 'AuthController@logout', ['AuthMiddleware']);

        // ---- Dashboard ----
        $r->get('/', 'DashboardController@index', ['AuthMiddleware']);
        $r->get('/dashboard', 'DashboardController@index', ['AuthMiddleware']);

        // ---- Users ----
        $r->get(   '/users',           'UserController@index',   ['AuthMiddleware', 'RoleMiddleware']);
        $r->get(   '/users/{id}',      'UserController@show',    ['AuthMiddleware', 'RoleMiddleware']);
        $r->post(  '/users',           'UserController@store',   ['AuthMiddleware', 'RoleMiddleware']);
        $r->patch( '/users/{id}',      'UserController@update',  ['AuthMiddleware', 'RoleMiddleware']);
        $r->delete('/users/{id}',      'UserController@destroy', ['AuthMiddleware', 'RoleMiddleware']);
        $r->patch( '/users/{id}/kyc',  'UserController@updateKyc', ['AuthMiddleware', 'RoleMiddleware']);

        // ---- Bank Accounts ----
        $r->get(  '/accounts',              'AccountController@index',  ['AuthMiddleware']);
        $r->get(  '/accounts/{id}',         'AccountController@show',   ['AuthMiddleware']);
        $r->post( '/accounts',              'AccountController@store',   ['AuthMiddleware', 'RoleMiddleware']);
        $r->patch('/accounts/{id}/freeze',  'AccountController@freeze',  ['AuthMiddleware', 'RoleMiddleware']);
        $r->patch('/accounts/{id}/close',   'AccountController@close',   ['AuthMiddleware', 'RoleMiddleware']);

        // ---- Transactions ----
        $r->get( '/transactions',       'TransactionController@index',   ['AuthMiddleware']);
        $r->get( '/transactions/{id}',  'TransactionController@show',    ['AuthMiddleware']);
        $r->post('/transactions/deposit',  'TransactionController@deposit',  ['AuthMiddleware', 'RoleMiddleware']);
        $r->post('/transactions/transfer', 'TransactionController@transfer', ['AuthMiddleware', 'RoleMiddleware']);
        $r->post('/transactions/{id}/reverse', 'TransactionController@reverse', ['AuthMiddleware', 'RoleMiddleware']);

        // ---- Loans ----
        $r->get(   '/loans',            'LoanController@index',   ['AuthMiddleware']);
        $r->get(   '/loans/{id}',       'LoanController@show',    ['AuthMiddleware']);
        $r->post(  '/loans',            'LoanController@store',   ['AuthMiddleware', 'RoleMiddleware']);
        $r->patch( '/loans/{id}/approve', 'LoanController@approve', ['AuthMiddleware', 'RoleMiddleware']);
        $r->patch( '/loans/{id}/reject',  'LoanController@reject',  ['AuthMiddleware', 'RoleMiddleware']);

        // ---- Cards ----
        $r->get(   '/cards',            'CardController@index',   ['AuthMiddleware']);
        $r->get(   '/cards/{id}',       'CardController@show',    ['AuthMiddleware']);
        $r->post(  '/cards',            'CardController@store',   ['AuthMiddleware', 'RoleMiddleware']);
        $r->patch( '/cards/{id}/block', 'CardController@block',   ['AuthMiddleware', 'RoleMiddleware']);

        // ---- Reports ----
        $r->get('/reports',              'ReportController@index',       ['AuthMiddleware', 'RoleMiddleware']);
        $r->get('/reports/transactions', 'ReportController@transactions', ['AuthMiddleware', 'RoleMiddleware']);
        $r->get('/reports/loans',        'ReportController@loans',        ['AuthMiddleware', 'RoleMiddleware']);
        $r->get('/reports/audit',        'ReportController@audit',        ['AuthMiddleware', 'RoleMiddleware']);

        // ---- Support Tickets ----
        $r->get(  '/support',            'SupportController@index',  ['AuthMiddleware']);
        $r->get(  '/support/{id}',       'SupportController@show',   ['AuthMiddleware']);
        $r->patch('/support/{id}/close', 'SupportController@close',  ['AuthMiddleware', 'RoleMiddleware']);
        $r->post( '/support/{id}/reply', 'SupportController@reply',  ['AuthMiddleware']);
    }
}
