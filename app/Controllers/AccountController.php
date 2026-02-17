<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\AccountService;
use App\Services\AuthService;
use App\Repositories\UserRepository;

class AccountController extends Controller
{
    private AccountService $accountService;

    public function __construct()
    {
        // No auth checks here — Galvani rule #9
        $this->accountService = new AccountService();
    }

    // -------------------------------------------------------------------------
    // GET /account
    // -------------------------------------------------------------------------

    public function index(): void
    {
        global $basePath;
        requireAuth();
        $this->redirect($basePath . '/account/profile');
    }

    // -------------------------------------------------------------------------
    // GET /account/profile
    // -------------------------------------------------------------------------

    public function showProfile(): void
    {
        global $basePath;

        $authUser = requireAuth();
        $user     = (new UserRepository())->findById((int)$authUser['id']);

        if ($user === null) {
            $this->abort(404);
        }

        $content = $this->renderView('account/profile', [
            'user' => $user,
        ]);

        $this->view('layouts/public', [
            'pageTitle' => 'Account Settings',
            'content'   => $content,
            'mainWidth' => 'max-w-4xl',
            'user'      => $user,
            'activeNav' => '',
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /account/profile
    // -------------------------------------------------------------------------

    public function updateProfile(): void
    {
        global $basePath;

        $authUser = requireAuth();
        validateCsrf();

        try {
            $updatedUser = $this->accountService->updateProfile((int)$authUser['id'], $_POST);

            // Regenerate JWT so header user pill reflects any name change
            $token = (new AuthService())->generateToken($updatedUser);
            setcookie('auth_token', $token, [
                'expires'  => time() + (30 * 24 * 3600),
                'path'     => '/',
                'httponly' => true,
                'secure'   => isset($_SERVER['HTTPS']),
                'samesite' => 'Strict',
            ]);

            flash('Profile updated successfully.', 'success');
            $this->redirect($basePath . '/account/profile');

        } catch (\InvalidArgumentException $e) {
            flash($e->getMessage(), 'error');
            $this->redirect($basePath . '/account/profile');
        }
    }

    // -------------------------------------------------------------------------
    // POST /account/notifications
    // -------------------------------------------------------------------------

    public function updateNotifications(): void
    {
        global $basePath;

        $authUser = requireAuth();
        validateCsrf();

        (new UserRepository())->updateProfile((int)$authUser['id'], [
            'notify_outbid'      => !empty($_POST['notify_outbid'])      ? 1 : 0,
            'notify_ending_soon' => !empty($_POST['notify_ending_soon']) ? 1 : 0,
            'notify_win'         => !empty($_POST['notify_win'])         ? 1 : 0,
            'notify_payment'     => !empty($_POST['notify_payment'])     ? 1 : 0,
        ]);

        flash('Notification preferences saved.', 'success');
        $this->redirect($basePath . '/account/profile');
    }

    // -------------------------------------------------------------------------
    // GET /account/password
    // -------------------------------------------------------------------------

    public function showPassword(): void
    {
        global $basePath;

        $authUser = requireAuth();
        $user     = (new UserRepository())->findById((int)$authUser['id']);

        if ($user === null) {
            $this->abort(404);
        }

        $content = $this->renderView('account/password', [
            'user' => $user,
        ]);

        $this->view('layouts/public', [
            'pageTitle' => 'Change Password',
            'content'   => $content,
            'mainWidth' => 'max-w-4xl',
            'user'      => $user,
            'activeNav' => '',
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /account/password
    // -------------------------------------------------------------------------

    public function updatePassword(): void
    {
        global $basePath;

        $authUser = requireAuth();
        validateCsrf();

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        try {
            $this->accountService->changePassword(
                (int)$authUser['id'],
                $currentPassword,
                $newPassword,
                $confirmPassword
            );

            flash('Password changed successfully.', 'success');
            $this->redirect($basePath . '/account/password');

        } catch (\InvalidArgumentException $e) {
            flash($e->getMessage(), 'error');
            $this->redirect($basePath . '/account/password');
        }
    }
}
