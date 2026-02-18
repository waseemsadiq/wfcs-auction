<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\AuthService;
use App\Services\PasswordResetService;
use App\Services\RateLimitService;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        // No auth checks here — Galvani rule #9
        $this->authService = new AuthService();
    }

    // -------------------------------------------------------------------------
    // GET /login
    // -------------------------------------------------------------------------

    public function showLogin(): void
    {
        global $basePath;

        $user = getAuthUser();
        if ($user) {
            $this->redirect($basePath . '/');
        }

        $content = $this->renderView('auth/login', []);

        $this->view('layouts/public', [
            'pageTitle' => 'Sign In',
            'content'   => $content,
            'mainWidth' => 'max-w-5xl',
            'bodyClass' => '',
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /login
    // -------------------------------------------------------------------------

    public function login(): void
    {
        global $basePath;

        // CSRF already validated by index.php for all POST requests

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        try {
            (new RateLimitService())->check($ip, 'login');
        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
            $this->redirect($basePath . '/login');
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        try {
            $payload = $this->authService->login($email, $password);
            $user    = (new \App\Repositories\UserRepository())->findByEmail($email);

            if ($user === null) {
                flash('Login failed. Please try again.', 'error');
                $this->redirect($basePath . '/login');
            }

            $token  = $this->authService->generateToken($user);
            $expiry = $remember
                ? time() + (90 * 24 * 3600)   // 90 days if "remember me"
                : time() + (30 * 24 * 3600);   // 30 days default

            setcookie('auth_token', $token, [
                'expires'  => $expiry,
                'path'     => '/',
                'httponly' => true,
                'secure'   => isset($_SERVER['HTTPS']),
                'samesite' => 'Strict',
            ]);

            // Clear rate limit on successful login
            (new RateLimitService())->clear($ip, 'login');

            flash('Welcome back, ' . ($payload['name'] ?: $email) . '!', 'success');

            $redirect = $_GET['redirect'] ?? '';
            if ($redirect && str_starts_with($redirect, '/')) {
                $this->redirect($basePath . $redirect);
            }
            if (($user['role'] ?? '') === 'admin') {
                $this->redirect($basePath . '/admin/dashboard');
            }
            $this->redirect($basePath . '/');

        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
            $this->redirect($basePath . '/login');
        }
    }

    // -------------------------------------------------------------------------
    // GET /logout
    // -------------------------------------------------------------------------

    public function logout(): void
    {
        global $basePath;

        // Expire the auth cookie
        setcookie('auth_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'secure'   => isset($_SERVER['HTTPS']),
            'samesite' => 'Strict',
        ]);

        flash('You have been signed out.', 'success');
        $this->redirect($basePath . '/');
    }

    // -------------------------------------------------------------------------
    // GET /register
    // -------------------------------------------------------------------------

    public function showRegister(): void
    {
        global $basePath;

        $user = getAuthUser();
        if ($user) {
            $this->redirect($basePath . '/');
        }

        $content = $this->renderView('auth/register', []);

        $this->view('layouts/public', [
            'pageTitle' => 'Create Account',
            'content'   => $content,
            'mainWidth' => 'max-w-2xl',
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /register
    // -------------------------------------------------------------------------

    public function register(): void
    {
        global $basePath;

        // CSRF already validated by index.php

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        try {
            (new RateLimitService())->check($ip, 'register');
        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
            $this->redirect($basePath . '/register');
        }

        // Name — same pattern as AccountService::updateProfile()
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $name      = trim($firstName . ' ' . $lastName);

        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Required consents
        if (empty($_POST['terms']) || empty($_POST['privacy']) || empty($_POST['data_processing'])) {
            flash('You must accept the Terms, Privacy Policy, and data processing consent to register.', 'error');
            $this->redirect($basePath . '/register');
        }

        // Password confirmation
        if ($password !== $passwordConfirm) {
            flash('Passwords do not match.', 'error');
            $this->redirect($basePath . '/register');
        }

        try {
            $result = $this->authService->register($name, $email, $password);
            $user   = $result['user'];

            // Gift Aid — persist if opted in
            $userRepo = new \App\Repositories\UserRepository();
            if (!empty($_POST['gift_aid'])) {
                $userRepo->updateProfile((int)$user['id'], [
                    'gift_aid_eligible' => 1,
                    'gift_aid_name'     => $name,
                    'gift_aid_address'  => trim($_POST['addr1'] ?? ''),
                    'gift_aid_city'     => trim($_POST['city'] ?? ''),
                    'gift_aid_postcode' => trim($_POST['postcode'] ?? ''),
                ]);
            }

            // Phone — persist if provided
            $phone = trim($_POST['phone'] ?? '');
            if ($phone !== '') {
                $userRepo->updateProfile((int)$user['id'], ['phone' => $phone]);
            }

            $token = $this->authService->generateToken($user);

            setcookie('auth_token', $token, [
                'expires'  => time() + (30 * 24 * 3600),
                'path'     => '/',
                'httponly' => true,
                'secure'   => isset($_SERVER['HTTPS']),
                'samesite' => 'Strict',
            ]);

            flash('Account created! Please check your email to verify your address.', 'success');
            $this->redirect($basePath . '/verify-email?pending=1');

        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
            $this->redirect($basePath . '/register');
        }
    }

    // -------------------------------------------------------------------------
    // GET /verify-email
    // -------------------------------------------------------------------------

    public function verifyEmail(): void
    {
        global $basePath;

        $token   = $_GET['token'] ?? '';
        $pending = !empty($_GET['pending']);

        if ($token !== '') {
            $user = $this->authService->verifyEmail($token);

            if ($user !== null) {
                // Reissue JWT with verified=true
                $jwt = $this->authService->generateToken($user);
                setcookie('auth_token', $jwt, [
                    'expires'  => time() + (30 * 24 * 3600),
                    'path'     => '/',
                    'httponly' => true,
                    'secure'   => isset($_SERVER['HTTPS']),
                    'samesite' => 'Strict',
                ]);

                flash('Your email has been verified. Welcome!', 'success');
                $this->redirect($basePath . '/');
            }

            // Invalid/expired token — fall through to view with error
            $verifyError = 'This verification link is invalid or has expired.';
        } else {
            $verifyError = null;
        }

        $content = $this->renderView('auth/verify-email', [
            'pending'     => $pending,
            'verifyError' => $verifyError,
        ]);

        $this->view('layouts/public', [
            'pageTitle' => 'Verify Email',
            'content'   => $content,
            'mainWidth' => 'max-w-xl',
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /resend-verification (show page)
    // -------------------------------------------------------------------------

    public function showResend(): void
    {
        global $basePath;

        $user = requireAuth();

        flash('A new verification email has been sent.', 'success');
        $this->authService->resendVerification((int)$user['id']);
        $this->redirect($basePath . '/verify-email?pending=1');
    }

    // -------------------------------------------------------------------------
    // POST /resend-verification
    // -------------------------------------------------------------------------

    public function resend(): void
    {
        global $basePath;

        $user = requireAuth();

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        try {
            (new RateLimitService())->check($ip, 'resend_verification');
        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
            $this->redirect($basePath . '/verify-email?pending=1');
        }

        $this->authService->resendVerification((int)$user['id']);
        flash('Verification email sent. Please check your inbox.', 'success');
        $this->redirect($basePath . '/verify-email?pending=1');
    }

    // -------------------------------------------------------------------------
    // GET /forgot-password
    // -------------------------------------------------------------------------

    public function showForgot(): void
    {
        global $basePath;

        // Already logged in and verified — no need to reset
        $user = getAuthUser();
        if ($user && !empty($user['verified'])) {
            $this->redirect($basePath . '/');
        }

        $sent = !empty($_GET['sent']);

        $content = $this->renderView('auth/forgot-password', [
            'sent' => $sent,
        ]);

        $this->view('layouts/public', [
            'pageTitle' => 'Forgot Password',
            'content'   => $content,
            'mainWidth' => 'max-w-4xl',
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /forgot-password
    // -------------------------------------------------------------------------

    public function forgot(): void
    {
        global $basePath;

        // CSRF already validated by index.php for all POST requests

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        try {
            (new RateLimitService())->check($ip, 'password_reset');
        } catch (\RuntimeException $e) {
            flash($e->getMessage(), 'error');
            $this->redirect($basePath . '/forgot-password');
        }

        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Please enter a valid email address.', 'error');
            $this->redirect($basePath . '/forgot-password');
        }

        $service = new PasswordResetService();
        $service->initiate($email);

        // Always show the same message regardless of whether the email exists
        flash("If that email exists, we've sent a reset link.", 'success');
        $this->redirect($basePath . '/forgot-password?sent=1');
    }

    // -------------------------------------------------------------------------
    // GET /reset-password
    // -------------------------------------------------------------------------

    public function showReset(): void
    {
        global $basePath;

        $token      = trim($_GET['token'] ?? '');
        $tokenError = null;

        if ($token === '') {
            $tokenError = 'No reset token provided. Please request a new password reset link.';
        } else {
            $service = new PasswordResetService();
            $user    = $service->validateToken($token);

            if ($user === null) {
                $tokenError = 'This reset link is invalid or has expired. Please request a new one.';
            }
        }

        $content = $this->renderView('auth/reset-password', [
            'token'      => $token,
            'tokenError' => $tokenError,
        ]);

        $this->view('layouts/public', [
            'pageTitle' => 'Reset Password',
            'content'   => $content,
            'mainWidth' => 'max-w-4xl',
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /reset-password
    // -------------------------------------------------------------------------

    public function reset(): void
    {
        global $basePath;

        // CSRF already validated by index.php for all POST requests

        $token    = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        if ($password !== $confirm) {
            flash('Passwords do not match.', 'error');
            $this->redirect($basePath . '/reset-password?token=' . urlencode($token));
        }

        try {
            $service = new PasswordResetService();
            $service->reset($token, $password);

            flash('Password reset. Please sign in.', 'success');
            $this->redirect($basePath . '/login');

        } catch (\InvalidArgumentException $e) {
            flash($e->getMessage(), 'error');
            $this->redirect($basePath . '/reset-password?token=' . urlencode($token));
        }
    }
}
