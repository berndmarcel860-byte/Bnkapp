<?php
/**
 * BnkApp Admin — URL Router
 *
 * Maps URI patterns (with optional named captures) to controller actions.
 *
 * Usage in public/index.php:
 *   $router = new Router();
 *   $router->get('/',               'DashboardController@index');
 *   $router->post('/auth/login',    'AuthController@login');
 *   $router->get('/users/{id}',     'UserController@show');
 *   $router->dispatch(new Request());
 */
declare(strict_types=1);

namespace BnkApp\Core;

use RuntimeException;

class Router
{
    /** @var array<string, array{method: string, pattern: string, handler: string, middleware: string[]}> */
    private array $routes = [];

    // ------------------------------------------------------------------
    // Registration helpers
    // ------------------------------------------------------------------

    public function get(string $uri, string $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $uri, $handler, $middleware);
    }

    public function post(string $uri, string $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $uri, $handler, $middleware);
    }

    public function put(string $uri, string $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $uri, $handler, $middleware);
    }

    public function patch(string $uri, string $handler, array $middleware = []): self
    {
        return $this->addRoute('PATCH', $uri, $handler, $middleware);
    }

    public function delete(string $uri, string $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $uri, $handler, $middleware);
    }

    private function addRoute(string $method, string $uri, string $handler, array $middleware): self
    {
        $pattern = $this->uriToPattern($uri);
        $this->routes[] = compact('method', 'pattern', 'handler', 'middleware');
        return $this;
    }

    // ------------------------------------------------------------------
    // Dispatch
    // ------------------------------------------------------------------

    /**
     * Match the incoming request against registered routes and invoke
     * the matching controller action.
     *
     * @throws RuntimeException if the handler is malformed
     */
    public function dispatch(Request $request): void
    {
        $method = $request->getMethod();
        $uri    = $request->getUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['pattern'], $uri, $matches)) {
                continue;
            }

            // Extract named captures (route parameters)
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            // Run middleware stack
            foreach ($route['middleware'] as $middlewareClass) {
                $fqcn = 'BnkApp\\Middleware\\' . $middlewareClass;
                (new $fqcn())->handle($request);
            }

            // Resolve and call controller@method
            [$controllerName, $action] = $this->parseHandler($route['handler']);
            $fqcn       = 'BnkApp\\Controllers\\' . $controllerName;
            $controller = new $fqcn();
            $controller->$action($request, $params);
            return;
        }

        // No route matched
        Response::abort(HTTP_NOT_FOUND, 'The requested page was not found.');
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /**
     * Convert a URI pattern like /users/{id} to a named-capture regex.
     */
    private function uriToPattern(string $uri): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    /**
     * Split "ControllerName@methodName" into its two parts.
     */
    private function parseHandler(string $handler): array
    {
        $parts = explode('@', $handler, 2);
        if (count($parts) !== 2) {
            throw new RuntimeException("Invalid handler format: [{$handler}]. Expected 'Controller@method'.");
        }
        return $parts;
    }
}
