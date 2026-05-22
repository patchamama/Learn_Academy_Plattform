<?php

namespace LearnAcademy\App;

/**
 * Application bootstrap. Wires together DB, Auth, Router, View, and i18n.
 */
class App
{
    public Database $db;
    public Auth     $auth;
    public Router   $router;
    public View     $view;
    public array    $config;

    public function __construct()
    {
        // Load config
        $configFile = __DIR__ . '/config.php';
        $localFile  = __DIR__ . '/config.local.php';
        $this->config = require $configFile;
        if (file_exists($localFile)) {
            $this->config = array_merge($this->config, require $localFile);
        }

        // Define constants used by Database and helpers
        define('DB_PATH',     $this->config['db_path']);
        define('APP_URL',     $this->config['app_url']);
        define('VIEWS_DIR',   __DIR__ . '/views');

        // Boot subsystems
        $this->db     = Database::getInstance();
        $this->db->migrate();

        $this->auth   = new Auth($this->db);
        $this->router = new Router();
        $this->view   = new View(VIEWS_DIR);

        // Start session
        $this->auth->startSession();

        // Share global view data
        $this->view->share([
            'app'    => $this,
            'auth'   => $this->auth,
            'config' => $this->config,
            'user'   => $this->auth->user(),
        ]);

        // Set locale
        $this->bootLocale();
    }

    private function bootLocale(): void
    {
        $locale = $this->auth->user()['locale']
            ?? $_COOKIE['locale']
            ?? $this->detectBrowserLocale()
            ?? $this->config['default_locale'];

        if (!in_array($locale, $this->config['supported_locales'], true)) {
            $locale = $this->config['default_locale'];
        }

        if (!defined('APP_LOCALE')) {
            define('APP_LOCALE', $locale);
        }
    }

    private function detectBrowserLocale(): ?string
    {
        $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if (preg_match('/^([a-z]{2})/i', $header, $m)) {
            return strtolower($m[1]);
        }
        return null;
    }

    public function run(): void
    {
        $this->registerRoutes();

        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = $_SERVER['REQUEST_URI'];

        $this->router->dispatch($method, $uri);
    }

    private function registerRoutes(): void
    {
        $app = $this;
        $v   = $this->view;
        $db  = $this->db;
        $auth = $this->auth;

        // ── Auth ────────────────────────────────────────────────────────
        $this->router->get('/login', fn() =>
            $v->layout('auth/login', ['csrf' => $auth->csrfToken()])
        );
        $this->router->post('/login', fn() =>
            (new Controllers\AuthController($app))->login()
        );
        $this->router->get('/register', fn() =>
            $v->layout('auth/register', ['csrf' => $auth->csrfToken()])
        );
        $this->router->post('/register', fn() =>
            (new Controllers\AuthController($app))->register()
        );
        $this->router->get('/logout', fn() =>
            (new Controllers\AuthController($app))->logout()
        );

        // ── Dashboard ───────────────────────────────────────────────────
        $this->router->get('/', fn() =>
            (new Controllers\DashboardController($app))->index()
        );
        $this->router->get('/dashboard', fn() =>
            (new Controllers\DashboardController($app))->index()
        );

        // ── Courses ─────────────────────────────────────────────────────
        $this->router->get('/courses', fn() =>
            (new Controllers\CourseController($app))->index()
        );
        $this->router->get('/courses/:slug', fn($p) =>
            (new Controllers\CourseController($app))->detail($p['slug'])
        );
        $this->router->get('/courses/:slug/lesson/:lessonId', fn($p) =>
            (new Controllers\LessonController($app))->show($p['slug'], $p['lessonId'])
        );

        // ── Account ─────────────────────────────────────────────────────
        $this->router->get('/account/settings', fn() =>
            (new Controllers\AccountController($app))->settings()
        );
        $this->router->post('/account/settings', fn() =>
            (new Controllers\AccountController($app))->saveSettings()
        );

        // ── API ─────────────────────────────────────────────────────────
        $this->router->get('/api/progress/:courseSlug', fn($p) =>
            (new Controllers\ApiController($app))->getProgress($p['courseSlug'])
        );
        $this->router->post('/api/progress', fn() =>
            (new Controllers\ApiController($app))->saveProgress()
        );
        $this->router->get('/api/settings', fn() =>
            (new Controllers\ApiController($app))->getSettings()
        );
        $this->router->post('/api/settings', fn() =>
            (new Controllers\ApiController($app))->saveSettings()
        );
        $this->router->get('/api/comments/:lessonId', fn($p) =>
            (new Controllers\ApiController($app))->getComments((int)$p['lessonId'])
        );
        $this->router->post('/api/comments', fn() =>
            (new Controllers\ApiController($app))->postComment()
        );

        // ── Admin ────────────────────────────────────────────────────────
        $this->router->get('/admin', fn() =>
            (new Controllers\AdminController($app))->index()
        );
        $this->router->get('/admin/users', fn() =>
            (new Controllers\AdminController($app))->users()
        );
        $this->router->post('/admin/users/:id/access', fn($p) =>
            (new Controllers\AdminController($app))->grantAccess((int)$p['id'])
        );
        $this->router->post('/admin/comments/:id/moderate', fn($p) =>
            (new Controllers\AdminController($app))->moderateComment((int)$p['id'])
        );

        // ── Payments ─────────────────────────────────────────────────────
        $this->router->get('/purchase/:courseSlug', fn($p) =>
            (new Controllers\PaymentController($app))->checkout($p['courseSlug'])
        );
        $this->router->post('/purchase/:courseSlug/stripe', fn($p) =>
            (new Controllers\PaymentController($app))->stripeCheckout($p['courseSlug'])
        );
        $this->router->post('/api/webhooks/stripe', fn() =>
            (new Controllers\PaymentController($app))->stripeWebhook()
        );
        $this->router->post('/api/webhooks/paypal', fn() =>
            (new Controllers\PaymentController($app))->paypalWebhook()
        );

        // ── Language switch ───────────────────────────────────────────────
        $this->router->post('/locale', fn() => (function () use ($auth) {
            $locale = $_POST['locale'] ?? 'en';
            if (in_array($locale, ['en', 'es'], true)) {
                setcookie('locale', $locale, time() + 365 * 86400, '/');
                if ($auth->isLoggedIn()) {
                    Database::getInstance()->execute(
                        'UPDATE users SET locale = ? WHERE id = ?',
                        [$locale, $auth->user()['id']]
                    );
                }
            }
            header('Location: ' . ($_POST['redirect'] ?? '/'));
            exit;
        })());
    }
}
