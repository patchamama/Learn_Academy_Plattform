<?php

namespace LearnAcademy\App\Controllers;

use LearnAcademy\App\App;

class AccountController
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function settings(): void
    {
        $this->app->auth->requireLogin();

        $userId = $this->app->auth->user()['id'];
        $row    = $this->app->db->fetchOne(
            'SELECT settings_json FROM settings WHERE user_id = ?',
            [$userId]
        );

        $settings = $row ? json_decode($row['settings_json'], true) : [];

        $this->app->view->layout('account/settings', [
            'csrf'     => $this->app->auth->csrfToken(),
            'settings' => $settings,
            'user'     => $this->app->auth->user(),
        ]);
    }

    public function saveSettings(): void
    {
        $auth = $this->app->auth;

        $auth->requireLogin();

        if (!$auth->verifyCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        $userId = $auth->user()['id'];
        $db     = $this->app->db;

        // Collect settings fields from POST
        $settings = [
            'theme'     => in_array($_POST['theme'] ?? '', ['light', 'dark'], true)
                           ? $_POST['theme']
                           : 'light',
            'font_size' => in_array((int)($_POST['font_size'] ?? 16), [14, 16, 18, 20], true)
                           ? (int)$_POST['font_size']
                           : 16,
            'speed'     => in_array($_POST['speed'] ?? '1', ['0.5','0.75','1','1.25','1.5','1.75','2'], true)
                           ? $_POST['speed']
                           : '1',
            'subtitles' => isset($_POST['subtitles']) ? true : false,
        ];

        $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE);

        // Upsert settings row
        $existing = $db->fetchOne('SELECT id FROM settings WHERE user_id = ?', [$userId]);
        if ($existing) {
            $db->execute(
                'UPDATE settings SET settings_json = ? WHERE user_id = ?',
                [$settingsJson, $userId]
            );
        } else {
            $db->insert(
                'INSERT INTO settings (user_id, settings_json) VALUES (?, ?)',
                [$userId, $settingsJson]
            );
        }

        // Update locale if changed
        $locale = in_array($_POST['locale'] ?? '', ['en', 'es'], true)
            ? $_POST['locale']
            : null;

        if ($locale !== null) {
            $db->execute(
                'UPDATE users SET locale = ? WHERE id = ?',
                [$locale, $userId]
            );
            setcookie('locale', $locale, time() + 365 * 86400, '/');
        }

        header('Location: /account/settings?saved=1');
        exit;
    }
}
