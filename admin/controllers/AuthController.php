<?php
/**
 * BnkApp Admin — Authentication Controller
 *
 * Handles admin login and logout.
 */
declare(strict_types=1);

namespace BnkApp\Controllers;

use BnkApp\Core\Auth;
use BnkApp\Core\Controller;
use BnkApp\Core\Request;
use BnkApp\Core\Response;

class AuthController extends Controller
{
    /**
     * GET /auth/login
     * Display the login form.
     */
    public function showLogin(Request $request, array $params = []): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }

        $this->view('auth.login', [
            'title' => 'Admin Login',
        ]);
    }

    /**
     * POST /auth/login
     * Process login form submission.
     */
    public function login(Request $request, array $params = []): void
    {
        $errors = $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required|min:8',
        ]);

        if (!empty($errors)) {
            $this->view('auth.login', [
                'title'  => 'Admin Login',
                'errors' => $errors,
                'old'    => $request->only('email'),
            ]);
            return;
        }

        $email    = trim((string)$request->input('email'));
        $password = (string)$request->input('password');

        if (Auth::attempt($email, $password)) {
            $this->redirect('/dashboard');
        }

        $this->view('auth.login', [
            'title'  => 'Admin Login',
            'errors' => ['auth' => ['Invalid email or password, or your account is locked.']],
            'old'    => $request->only('email'),
        ]);
    }

    /**
     * POST /auth/logout
     * Log the current user out and redirect to login.
     */
    public function logout(Request $request, array $params = []): void
    {
        Auth::logout();
        $this->redirect('/auth/login');
    }
}
