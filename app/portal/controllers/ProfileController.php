<?php
/**
 * BnkApp Portal — Profile Controller
 */
declare(strict_types=1);

namespace BnkPortal\Controllers;

use BnkPortal\Core\Auth;
use BnkPortal\Core\Controller;
use BnkPortal\Core\Database;
use BnkPortal\Core\Request;
use BnkPortal\Core\Session;
use BnkPortal\Middleware\CsrfMiddleware;

class ProfileController extends Controller
{
    public function show(Request $request, array $params = []): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch();

        $this->view('profile.index', [
            'title' => 'My Profile',
            'user'  => $user,
        ]);
    }

    public function update(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        $errors = $this->validate($request, [
            'first_name' => 'required|max:100',
            'last_name'  => 'required|max:100',
            'phone'      => 'max:20',
        ]);

        if (!empty($errors)) {
            Session::flash('error', 'Please check your input.');
            $this->redirect('/profile');
        }

        Database::getInstance()->prepare(
            "UPDATE users SET first_name=?, last_name=?, phone=?, address_line1=?, city=?, postal_code=?, updated_at=NOW()
              WHERE id=?"
        )->execute([
            $request->input('first_name'),
            $request->input('last_name'),
            $request->input('phone'),
            $request->input('address_line1'),
            $request->input('city'),
            $request->input('postal_code'),
            Auth::id(),
        ]);

        // Update session
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([Auth::id()]);
        Session::set('portal_user', $stmt->fetch());

        Session::flash('success', 'Profile updated.');
        $this->redirect('/profile');
    }

    public function changePassword(Request $request, array $params = []): void
    {
        (new CsrfMiddleware())->handle($request);
        $errors = $this->validate($request, [
            'current_password' => 'required',
            'new_password'     => 'required|min:12',
        ]);

        if (!empty($errors)) {
            Session::flash('error', 'Please fill in both password fields.');
            $this->redirect('/profile');
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch();

        if (!$user || !password_verify((string)$request->input('current_password'), $user['password_hash'])) {
            Session::flash('error', 'Current password is incorrect.');
            $this->redirect('/profile');
        }

        $cfg  = require CONFIG_PATH . '/config.php';
        $hash = password_hash((string)$request->input('new_password'), PASSWORD_BCRYPT, ['cost' => $cfg['security']['bcrypt_cost']]);
        $db->prepare("UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?")->execute([$hash, Auth::id()]);

        Session::flash('success', 'Password changed successfully.');
        $this->redirect('/profile');
    }
}
