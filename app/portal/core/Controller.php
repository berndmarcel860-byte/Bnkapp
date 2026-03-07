<?php
/**
 * BnkApp Portal — Base Controller
 */
declare(strict_types=1);

namespace BnkPortal\Core;

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $authUser = Auth::user();
        $path     = VIEWS_PATH . '/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($path)) {
            http_response_code(HTTP_NOT_FOUND);
            echo "View not found: {$view}";
            return;
        }
        require $path;
    }

    protected function redirect(string $url, int $code = HTTP_FOUND): never
    {
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }

    protected function json(array $data, int $code = HTTP_OK): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function success(mixed $data, string $message = 'OK', int $code = HTTP_OK): never
    {
        $this->json(['success' => true, 'message' => $message, 'data' => $data], $code);
    }

    protected function error(string $message, int $code = HTTP_BAD_REQUEST, array $errors = []): never
    {
        $this->json(['success' => false, 'message' => $message, 'errors' => $errors], $code);
    }

    protected function abort(int $code, string $message = ''): never
    {
        http_response_code($code);
        echo $message;
        exit;
    }

    protected function validate(Request $request, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleStr) {
            $value = $request->input($field);
            foreach (explode('|', $ruleStr) as $rule) {
                [$ruleName, $ruleArg] = array_pad(explode(':', $rule, 2), 2, null);
                match ($ruleName) {
                    'required' => $value === null || trim((string)$value) === ''
                        ? ($errors[$field][] = "{$field} is required.") : null,
                    'email'   => !filter_var($value, FILTER_VALIDATE_EMAIL)
                        ? ($errors[$field][] = "{$field} must be a valid email.") : null,
                    'min'     => mb_strlen((string)$value) < (int)$ruleArg
                        ? ($errors[$field][] = "{$field} must be at least {$ruleArg} characters.") : null,
                    'max'     => mb_strlen((string)$value) > (int)$ruleArg
                        ? ($errors[$field][] = "{$field} must not exceed {$ruleArg} characters.") : null,
                    'numeric' => !is_numeric($value)
                        ? ($errors[$field][] = "{$field} must be numeric.") : null,
                    default   => null,
                };
            }
        }
        return $errors;
    }
}
