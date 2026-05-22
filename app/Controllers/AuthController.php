<?php

namespace LearnAcademy\App\Controllers;

use LearnAcademy\App\App;

class AuthController
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function login(): void
    {
        $auth = $this->app->auth;

        if (!$auth->verifyCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $this->app->view->layout('auth/login', [
                'csrf'  => $auth->csrfToken(),
                'error' => 'auth.error.fields_required',
            ]);
            return;
        }

        $result = $auth->login($email, $password);

        if (!$result['ok']) {
            $this->app->view->layout('auth/login', [
                'csrf'  => $auth->csrfToken(),
                'error' => 'auth.error.invalid_credentials',
            ]);
            return;
        }

        $redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '/';
        // Sanitise redirect — only allow relative paths
        if (!str_starts_with($redirect, '/')) {
            $redirect = '/';
        }

        header('Location: ' . $redirect);
        exit;
    }

    public function register(): void
    {
        $auth = $this->app->auth;

        if (!$auth->verifyCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $locale   = in_array($_POST['locale'] ?? '', ['en', 'es'], true)
            ? $_POST['locale']
            : 'en';

        if ($name === '' || $email === '' || $password === '') {
            $this->app->view->layout('auth/register', [
                'csrf'  => $auth->csrfToken(),
                'error' => 'auth.error.fields_required',
            ]);
            return;
        }

        $result = $auth->register($email, $password, $name, $locale);

        if (!$result['ok']) {
            $errorKey = match($result['error']) {
                'invalid_email'    => 'auth.error.invalid_email',
                'password_too_short' => 'auth.error.password_too_short',
                'email_taken'      => 'auth.error.email_taken',
                default            => 'auth.error.generic',
            };

            $this->app->view->layout('auth/register', [
                'csrf'  => $auth->csrfToken(),
                'error' => $errorKey,
            ]);
            return;
        }

        header('Location: /');
        exit;
    }

    public function logout(): void
    {
        $this->app->auth->logout();
        header('Location: /login');
        exit;
    }
}
