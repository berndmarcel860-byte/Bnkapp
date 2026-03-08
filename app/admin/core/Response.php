<?php
/**
 * BnkApp Admin — HTTP Response Helper
 *
 * Provides convenience methods for sending HTML, JSON, or redirect responses.
 */
declare(strict_types=1);

namespace BnkApp\Core;

class Response
{
    // ------------------------------------------------------------------
    // JSON
    // ------------------------------------------------------------------

    /**
     * Send a JSON response and terminate execution.
     *
     * @param mixed $data    Data to encode
     * @param int   $status  HTTP status code
     */
    public static function json(mixed $data, int $status = HTTP_OK): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Convenience: JSON success envelope.
     */
    public static function success(mixed $data = null, string $message = 'OK', int $status = HTTP_OK): never
    {
        self::json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    /**
     * Convenience: JSON error envelope.
     */
    public static function error(string $message, int $status = HTTP_BAD_REQUEST, ?array $errors = null): never
    {
        $payload = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        self::json($payload, $status);
    }

    // ------------------------------------------------------------------
    // Redirect
    // ------------------------------------------------------------------

    /**
     * Redirect to a URL and terminate execution.
     */
    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Redirect back to the referring page (or to a fallback URL).
     */
    public static function back(string $fallback = '/'): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
        self::redirect($referer);
    }

    // ------------------------------------------------------------------
    // View rendering
    // ------------------------------------------------------------------

    /**
     * Render a view file, passing variables into its scope.
     *
     * @param string $view   Dot-notation path relative to views/ (e.g. 'dashboard.index')
     * @param array  $data   Variables made available inside the view
     * @param int    $status HTTP status code
     */
    public static function view(string $view, array $data = [], int $status = HTTP_OK): void
    {
        $file = VIEWS_PATH . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($file)) {
            self::abort(HTTP_NOT_FOUND, "View [{$view}] not found.");
        }

        http_response_code($status);
        extract($data, EXTR_SKIP);
        require $file;
    }

    // ------------------------------------------------------------------
    // Errors
    // ------------------------------------------------------------------

    /**
     * Abort with an HTTP error code.
     */
    public static function abort(int $status, string $message = ''): never
    {
        http_response_code($status);
        $errorView = VIEWS_PATH . "/errors/{$status}.php";
        if (file_exists($errorView)) {
            extract(['message' => $message], EXTR_SKIP);
            require $errorView;
        } else {
            echo htmlspecialchars($message ?: "HTTP {$status}");
        }
        exit;
    }

    // ------------------------------------------------------------------
    // Security headers
    // ------------------------------------------------------------------

    /**
     * Set recommended security headers for HTML responses.
     * Call once before outputting any content.
     */
    public static function securityHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; connect-src 'self' https://cdn.jsdelivr.net;");
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
}
