<?php
/**
 * BnkApp Admin — HTTP Request Abstraction
 *
 * Wraps the incoming HTTP request: method, URI, query params,
 * parsed body, uploaded files, and request headers.
 */
declare(strict_types=1);

namespace BnkApp\Core;

class Request
{
    private string $method;
    private string $uri;
    private array  $queryParams;
    private array  $body;
    private array  $files;
    private array  $headers;

    public function __construct()
    {
        $this->method      = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri         = $this->parseUri();
        $this->queryParams = $_GET;
        $this->body        = $this->parseBody();
        $this->files       = $_FILES;
        $this->headers     = $this->parseHeaders();
    }

    // ------------------------------------------------------------------
    // Method & URI
    // ------------------------------------------------------------------

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function isGet(): bool    { return $this->method === 'GET'; }
    public function isPost(): bool   { return $this->method === 'POST'; }
    public function isPut(): bool    { return $this->method === 'PUT'; }
    public function isPatch(): bool  { return $this->method === 'PATCH'; }
    public function isDelete(): bool { return $this->method === 'DELETE'; }

    public function isAjax(): bool
    {
        return ($this->headers['X-Requested-With'] ?? '') === 'XMLHttpRequest';
    }

    // ------------------------------------------------------------------
    // Query string
    // ------------------------------------------------------------------

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function allQuery(): array
    {
        return $this->queryParams;
    }

    // ------------------------------------------------------------------
    // Request body (POST / JSON)
    // ------------------------------------------------------------------

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->body;
    }

    public function only(string ...$keys): array
    {
        return array_intersect_key($this->body, array_flip($keys));
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body);
    }

    // ------------------------------------------------------------------
    // Files
    // ------------------------------------------------------------------

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    // ------------------------------------------------------------------
    // Headers
    // ------------------------------------------------------------------

    public function header(string $name): ?string
    {
        $normalised = str_replace('-', '_', strtoupper($name));
        return $this->headers[$normalised] ?? null;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization') ?? '';
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function parseUri(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path       = parse_url($requestUri, PHP_URL_PATH) ?: '/';
        return '/' . trim($path, '/');
    }

    private function parseBody(): array
    {
        if ($this->isJson()) {
            $raw = file_get_contents('php://input');
            return (array)(json_decode($raw ?: '{}', true) ?? []);
        }
        return $_POST;
    }

    private function isJson(): bool
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains($ct, 'application/json');
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[$key] = $value;
            }
        }
        return $headers;
    }
}
