<?php
/**
 * BnkApp Portal — Router
 * Identical to admin Router — dispatches named-capture URI patterns.
 */
declare(strict_types=1);

namespace BnkPortal\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function patch(string $path, string $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, string $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, string $handler, array $middleware): void
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        $this->routes[] = compact('method', 'path', 'pattern', 'handler', 'middleware');
    }

    public function dispatch(Request $request): void
    {
        $method = strtoupper($request->method());
        $uri    = parse_url($request->uri(), PHP_URL_PATH);
        $uri    = rtrim($uri, '/') ?: '/';

        // Support method override via _method POST field
        if ($method === 'POST' && in_array($request->input('_method'), ['PATCH','DELETE'], true)) {
            $method = $request->input('_method');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (!preg_match('#^' . $route['pattern'] . '$#', $uri, $matches)) continue;

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            foreach ($route['middleware'] as $mw) {
                $class = "BnkPortal\\Middleware\\{$mw}";
                (new $class())->handle($request);
            }

            [$class, $action] = explode('@', $route['handler']);
            $controller = "BnkPortal\\Controllers\\{$class}";
            (new $controller())->$action($request, $params);
            return;
        }

        http_response_code(HTTP_NOT_FOUND);
        echo '404 — Page not found.';
    }
}
