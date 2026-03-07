<?php
/**
 * BnkApp Admin — Base Controller
 *
 * All admin controllers extend this class.
 * Provides convenience wrappers for rendering views, redirecting,
 * returning JSON, and validating request input.
 */
declare(strict_types=1);

namespace BnkApp\Core;

abstract class Controller
{
    // ------------------------------------------------------------------
    // View rendering
    // ------------------------------------------------------------------

    /**
     * Render a view and pass data to it.
     *
     * @param string $view  Dot-notation path, e.g. 'dashboard.index'
     * @param array  $data  Variables available inside the view
     * @param int    $status HTTP status code
     */
    protected function view(string $view, array $data = [], int $status = HTTP_OK): void
    {
        // Always inject the authenticated user so layouts can reference it
        $data['authUser'] = Auth::user();
        Response::view($view, $data, $status);
    }

    // ------------------------------------------------------------------
    // Redirects
    // ------------------------------------------------------------------

    protected function redirect(string $url, int $status = 302): never
    {
        Response::redirect($url, $status);
    }

    protected function redirectBack(string $fallback = '/'): never
    {
        Response::back($fallback);
    }

    // ------------------------------------------------------------------
    // JSON responses
    // ------------------------------------------------------------------

    protected function json(mixed $data, int $status = HTTP_OK): never
    {
        Response::json($data, $status);
    }

    protected function success(mixed $data = null, string $message = 'OK'): never
    {
        Response::success($data, $message);
    }

    protected function error(string $message, int $status = HTTP_BAD_REQUEST, ?array $errors = null): never
    {
        Response::error($message, $status, $errors);
    }

    // ------------------------------------------------------------------
    // Flash messages
    // ------------------------------------------------------------------

    protected function flashSuccess(string $message): void
    {
        Session::flash('success', $message);
    }

    protected function flashError(string $message): void
    {
        Session::flash('error', $message);
    }

    protected function flashWarning(string $message): void
    {
        Session::flash('warning', $message);
    }

    // ------------------------------------------------------------------
    // Validation helpers
    // ------------------------------------------------------------------

    /**
     * Validate request input against a simple rules array.
     * Rules supported: required | min:N | max:N | email | numeric | in:a,b,c
     *
     * Returns an array of field => [errors] on failure, or empty array on pass.
     */
    protected function validate(Request $request, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value     = $request->input($field);
            $ruleList  = explode('|', $ruleString);

            foreach ($ruleList as $rule) {
                [$ruleName, $ruleParam] = array_pad(explode(':', $rule, 2), 2, null);

                switch ($ruleName) {
                    case 'required':
                        if ($value === null || $value === '') {
                            $errors[$field][] = "The {$field} field is required.";
                        }
                        break;

                    case 'min':
                        if (is_string($value) && mb_strlen($value) < (int)$ruleParam) {
                            $errors[$field][] = "The {$field} must be at least {$ruleParam} characters.";
                        }
                        break;

                    case 'max':
                        if (is_string($value) && mb_strlen($value) > (int)$ruleParam) {
                            $errors[$field][] = "The {$field} must not exceed {$ruleParam} characters.";
                        }
                        break;

                    case 'email':
                        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "The {$field} must be a valid email address.";
                        }
                        break;

                    case 'numeric':
                        if ($value !== null && !is_numeric($value)) {
                            $errors[$field][] = "The {$field} must be a number.";
                        }
                        break;

                    case 'in':
                        $allowed = explode(',', $ruleParam ?? '');
                        if ($value !== null && !in_array($value, $allowed, true)) {
                            $errors[$field][] = "The {$field} must be one of: " . implode(', ', $allowed) . '.';
                        }
                        break;
                }
            }
        }

        return $errors;
    }

    /**
     * Abort with an HTTP error code.
     */
    protected function abort(int $status, string $message = ''): never
    {
        Response::abort($status, $message);
    }
}
