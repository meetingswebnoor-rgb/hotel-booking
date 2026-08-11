<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthController
{
    public function showLogin(Request $request): Response
    {
        if (Auth::check()) {
            return Response::redirect(route('dashboard'));
        }

        $html = view('auth/login', [
            'title' => 'Sign in — Hotezo',
        ], 'auth');

        return Response::html($html);
    }

    public function login(Request $request): Response
    {
        if (!Csrf::verify($request->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');

            return Response::redirect(route('login'));
        }

        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $remember = $request->input('remember') !== null;

        if ($email === '' || $password === '') {
            Session::flash('error', 'Please enter your email and password.');
            Session::flash('_old_input', ['email' => $email]);

            return Response::redirect(route('login'));
        }

        $user = Database::first('users', ['email' => $email]);

        if ($user !== null && $this->isLocked($user)) {
            $minutes = $this->minutesUntilUnlock($user);
            $label = $minutes === 1 ? 'minute' : 'minutes';
            Session::flash('error', "Too many failed attempts. Please try again in {$minutes} {$label}.");

            return Response::redirect(route('login'));
        }

        $valid = $user !== null
            && (int) $user['is_deleted'] === 0
            && $user['status'] === 'active'
            && password_verify($password, (string) $user['password_hash']);

        if (!$valid) {
            if ($user !== null) {
                $this->registerFailedAttempt($user);
            }

            Session::flash('error', 'The email or password you entered is incorrect.');
            Session::flash('_old_input', ['email' => $email]);

            return Response::redirect(route('login'));
        }

        Database::update('users', $user['id'], [
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login' => date('Y-m-d H:i:s'),
        ]);

        Auth::login($user);

        if ($remember) {
            $this->rememberMe((string) $user['id']);
        }

        Session::flash('success', 'Welcome back, ' . $user['full_name'] . '.');

        return Response::redirect(route('dashboard'));
    }

    public function logout(Request $request): Response
    {
        $userId = Auth::id();

        if ($userId !== null) {
            Database::update('users', $userId, ['remember_token' => null]);
        }

        Auth::logout();
        $this->forgetRememberCookie();

        Session::flash('success', 'You have been signed out.');

        return Response::redirect(route('login'));
    }

    private function isLocked(array $user): bool
    {
        return $user['locked_until'] !== null && strtotime((string) $user['locked_until']) > time();
    }

    private function minutesUntilUnlock(array $user): int
    {
        $seconds = strtotime((string) $user['locked_until']) - time();

        return max(1, (int) ceil($seconds / 60));
    }

    private function registerFailedAttempt(array $user): void
    {
        $attempts = (int) $user['failed_login_attempts'] + 1;
        $maxAttempts = (int) config('auth.max_login_attempts', 5);

        $data = ['failed_login_attempts' => $attempts];

        if ($attempts >= $maxAttempts) {
            $lockoutMinutes = (int) config('auth.lockout_minutes', 15);
            $data['failed_login_attempts'] = 0;
            $data['locked_until'] = date('Y-m-d H:i:s', time() + $lockoutMinutes * 60);
        }

        Database::update('users', $user['id'], $data);
    }

    private function rememberMe(string $userId): void
    {
        $token = bin2hex(random_bytes(32));
        Database::update('users', $userId, ['remember_token' => hash('sha256', $token)]);

        $days = (int) config('auth.remember_days', 30);

        setcookie(Auth::REMEMBER_COOKIE, $userId . '.' . $token, [
            'expires' => time() + $days * 86400,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']),
        ]);
    }

    private function forgetRememberCookie(): void
    {
        if (isset($_COOKIE[Auth::REMEMBER_COOKIE])) {
            setcookie(Auth::REMEMBER_COOKIE, '', ['expires' => time() - 3600, 'path' => '/']);
        }
    }
}
