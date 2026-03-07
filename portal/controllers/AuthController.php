<?php
/**
 * BnkApp Portal — Auth Controller
 */
declare(strict_types=1);

namespace BnkPortal\Controllers;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Controller;
use BnkPortal\Core\Database;
use BnkPortal\Core\Request;
use BnkPortal\Core\Session;
use BnkPortal\Middleware\CsrfMiddleware;

class AuthController extends Controller
{
    public function showLogin(Request $request, array $params = []): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->view('auth.login', ['title' => 'Sign In']);
    }

    public function login(Request $request, array $params = []): void
    {
        // CSRF check
        (new CsrfMiddleware())->handle($request);

        $email    = strtolower(trim((string)$request->input('email', '')));
        $password = (string)$request->input('password', '');

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Session::flash('error', 'Invalid email or password.');
            $this->redirect('/login');
        }

        // Update last login
        $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?")->execute([$user['id']]);

        Auth::login($user);
        $next = $request->query('next', '/dashboard');
        $this->redirect(filter_var($next, FILTER_VALIDATE_URL) ? '/dashboard' : $next);
    }

    public function showRegister(Request $request, array $params = []): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->view('auth.register', ['title' => 'Create Account']);
    }

    public function register(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);

        $errors = $this->validate($request, [
            'first_name'    => 'required|max:100',
            'last_name'     => 'required|max:100',
            'email'         => 'required|email|max:255',
            'password'      => 'required|min:12',
            'date_of_birth' => 'required',
        ]);

        if (!empty($errors)) {
            Session::flash('error', 'Please fix the validation errors.');
            $this->redirect('/register');
        }

        $db = Database::getInstance();

        // Check existing
        $existing = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $existing->execute([strtolower(trim((string)$request->input('email', '')))]);
        if ($existing->fetch()) {
            Session::flash('error', 'An account with that email already exists.');
            $this->redirect('/register');
        }

        $cfg  = require CONFIG_PATH . '/config.php';
        $hash = password_hash((string)$request->input('password'), PASSWORD_BCRYPT, ['cost' => $cfg['security']['bcrypt_cost']]);
        $salt = bin2hex(random_bytes(16));

        $db->prepare(
            "INSERT INTO users (role_id, first_name, last_name, email, password_hash, password_salt, date_of_birth, kyc_status, is_active)
             VALUES (3, ?, ?, ?, ?, ?, ?, 'pending', 1)"
        )->execute([
            $request->input('first_name'),
            $request->input('last_name'),
            strtolower(trim((string)$request->input('email', ''))),
            $hash,
            $salt,
            $request->input('date_of_birth'),
        ]);

        Session::flash('success', 'Account created! Please sign in.');
        $this->redirect('/login');
    }

    public function logout(Request $request, array $params = []): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
