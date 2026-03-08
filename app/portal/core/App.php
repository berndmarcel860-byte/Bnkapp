<?php
/**
 * BnkApp Customer Portal — Application Bootstrap
 */
declare(strict_types=1);

namespace BnkPortal\Core;

class App
{
    private Router $router;

    public function run(): void
    {
        $this->configure();
        $this->registerAutoloader();
        $this->router = new Router();
        Session::start();
        $this->sendSecurityHeaders();
        $this->registerRoutes();
        $this->router->dispatch(new Request());
    }

    private function configure(): void
    {
        $cfg = require CONFIG_PATH . '/config.php';
        $app = $cfg['app'];
        date_default_timezone_set($app['timezone']);
        ini_set('display_errors', $app['debug'] ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', PORTAL_ROOT . '/storage/logs/portal.log');
        error_reporting($app['debug'] ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_STRICT);
    }

    private function sendSecurityHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; connect-src 'self' https://cdn.jsdelivr.net;");
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }

    private function registerAutoloader(): void
    {
        spl_autoload_register(function (string $class): void {
            $prefix = 'BnkPortal\\';
            if (!str_starts_with($class, $prefix)) return;
            $relative = substr($class, strlen($prefix));

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
                    if (file_exists($file)) require_once $file;
                    return;
                }
            }
        });
    }

    private function registerRoutes(): void
    {
        $r = $this->router;

        // ---- Auth ----
        $r->get( '/login',    'AuthController@showLogin');
        $r->post('/login',    'AuthController@login');
        $r->get( '/register', 'AuthController@showRegister');
        $r->post('/register', 'AuthController@register');
        $r->post('/logout',   'AuthController@logout', ['AuthMiddleware']);

        // ---- Dashboard ----
        $r->get('/',          'DashboardController@index', ['AuthMiddleware']);
        $r->get('/dashboard', 'DashboardController@index', ['AuthMiddleware']);

        // ---- Accounts ----
        $r->get('/accounts',      'AccountController@index', ['AuthMiddleware']);
        $r->get('/accounts/{id}', 'AccountController@show',  ['AuthMiddleware']);

        // ---- Transactions ----
        $r->get('/transactions',      'TransactionController@index', ['AuthMiddleware']);
        $r->get('/transactions/{id}', 'TransactionController@show',  ['AuthMiddleware']);

        // ---- Transfer (SEPA) ----
        $r->get( '/transfer',       'TransferController@showForm', ['AuthMiddleware']);
        $r->post('/transfer',       'TransferController@execute',  ['AuthMiddleware']);
        $r->get( '/transfer/sepa',  'TransferController@sepaForm', ['AuthMiddleware']);
        $r->post('/transfer/sepa',  'TransferController@sepaExecute', ['AuthMiddleware']);

        // ---- Beneficiaries ----
        $r->get(   '/beneficiaries',       'BeneficiaryController@index',   ['AuthMiddleware']);
        $r->post(  '/beneficiaries',       'BeneficiaryController@store',   ['AuthMiddleware']);
        $r->delete('/beneficiaries/{id}',  'BeneficiaryController@destroy', ['AuthMiddleware']);

        // ---- Standing Orders ----
        $r->get(  '/standing-orders',             'StandingOrderController@index',  ['AuthMiddleware']);
        $r->post( '/standing-orders',             'StandingOrderController@store',  ['AuthMiddleware']);
        $r->patch('/standing-orders/{id}/pause',  'StandingOrderController@pause',  ['AuthMiddleware']);
        $r->patch('/standing-orders/{id}/cancel', 'StandingOrderController@cancel', ['AuthMiddleware']);

        // ---- Loans ----
        $r->get( '/loans',      'LoanController@index', ['AuthMiddleware']);
        $r->get( '/loans/{id}', 'LoanController@show',  ['AuthMiddleware']);
        $r->post('/loans',      'LoanController@apply',  ['AuthMiddleware']);

        // ---- Cards ----
        $r->get(   '/cards',            'CardController@index',  ['AuthMiddleware']);
        $r->get(   '/cards/{id}',       'CardController@show',   ['AuthMiddleware']);
        $r->patch( '/cards/{id}/freeze','CardController@freeze', ['AuthMiddleware']);

        // ---- Support ----
        $r->get( '/support',             'SupportController@index',  ['AuthMiddleware']);
        $r->post('/support',             'SupportController@create', ['AuthMiddleware']);
        $r->get( '/support/{id}',        'SupportController@show',   ['AuthMiddleware']);
        $r->post('/support/{id}/reply',  'SupportController@reply',  ['AuthMiddleware']);

        // ---- Notifications ----
        $r->get( '/notifications',          'NotificationController@index', ['AuthMiddleware']);
        $r->post('/notifications/{id}/read','NotificationController@markRead', ['AuthMiddleware']);
        $r->post('/notifications/read-all', 'NotificationController@markAllRead', ['AuthMiddleware']);

        // ---- Profile ----
        $r->get( '/profile',          'ProfileController@show',   ['AuthMiddleware']);
        $r->post('/profile',          'ProfileController@update', ['AuthMiddleware']);
        $r->post('/profile/password', 'ProfileController@changePassword', ['AuthMiddleware']);

        // ---- KYC ----
        $r->get( '/kyc',        'KycController@index',  ['AuthMiddleware']);
        $r->post('/kyc/upload', 'KycController@upload', ['AuthMiddleware']);
    }
}
