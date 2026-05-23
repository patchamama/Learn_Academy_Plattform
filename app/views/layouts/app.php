<!DOCTYPE html>
<html lang="<?= e(defined('APP_LOCALE') ? APP_LOCALE : 'en') ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($config['app_name'] ?? 'Learn Academy') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="/assets/styles.css" />
</head>
<body>
<div class="layout">

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <a href="/"><img src="/assets/logo.png" alt="<?= e($config['app_name'] ?? 'Learn Academy') ?>" onerror="this.style.display='none';this.nextSibling.style.display='block'"><span style="display:none;font-weight:700;font-size:1.1rem;color:var(--color-accent)"><?= e($config['app_name'] ?? 'Learn Academy') ?></span></a>
        </div>

        <ul class="menu">
            <li>
                <a class="menu-option<?= ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/dashboard') ? ' active' : '' ?>" href="/">
                    <i class="fa-solid fa-house icon"></i>
                    <span><?= e(t('nav.dashboard')) ?></span>
                </a>
            </li>
            <li>
                <a class="menu-option<?= str_starts_with($_SERVER['REQUEST_URI'], '/courses') ? ' active' : '' ?>" href="/courses">
                    <i class="fa-solid fa-chalkboard-user icon"></i>
                    <span><?= e(t('nav.courses')) ?></span>
                </a>
            </li>
        </ul>

        <ul class="bottom-menu">
            <li>
                <a class="menu-option<?= str_starts_with($_SERVER['REQUEST_URI'], '/account') ? ' active' : '' ?>" href="/account/settings">
                    <i class="fa-solid fa-gear icon"></i>
                    <span><?= e(t('nav.settings')) ?></span>
                </a>
            </li>
            <?php if (isset($auth) && $auth->isAdmin()): ?>
            <li>
                <a class="menu-option<?= str_starts_with($_SERVER['REQUEST_URI'], '/admin') ? ' active' : '' ?>" href="/admin">
                    <i class="fa-solid fa-shield-halved icon"></i>
                    <span>Admin</span>
                </a>
            </li>
            <li>
                <a class="menu-option<?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/courses') ? ' active' : '' ?>" href="/admin/courses">
                    <i class="fa-solid fa-chalkboard icon"></i>
                    <span><?= e(t('admin.courses')) ?></span>
                </a>
            </li>
            <li>
                <a class="menu-option<?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/editor') ? ' active' : '' ?>" href="/admin/editor">
                    <i class="fa-solid fa-pen-ruler icon"></i>
                    <span>Editor</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (isset($auth) && $auth->isLoggedIn()): ?>
            <li>
                <a class="menu-option" href="/logout">
                    <i class="fa-solid fa-right-from-bracket icon"></i>
                    <span><?= e(t('nav.logout')) ?></span>
                </a>
            </li>
            <?php else: ?>
            <li>
                <a class="menu-option" href="/login">
                    <i class="fa-solid fa-right-to-bracket icon"></i>
                    <span><?= e(t('nav.login')) ?></span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Main content area -->
    <div class="main-content">

        <!-- Header -->
        <div class="header">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass icon"></i>
                <input type="text" placeholder="<?= e(t('general.search')) ?>..." />
            </div>
            <div class="header-right">
                <!-- Language switcher -->
                <form method="POST" action="/locale" style="display:inline-flex;gap:4px;margin-right:1rem">
                    <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>" />
                    <button type="submit" name="locale" value="en"
                            style="background:none;border:none;cursor:pointer;font-size:0.8rem;color:var(--color-text-muted)<?= (defined('APP_LOCALE') && APP_LOCALE === 'en') ? ';font-weight:700;color:var(--color-accent)' : '' ?>">EN</button>
                    <button type="submit" name="locale" value="es"
                            style="background:none;border:none;cursor:pointer;font-size:0.8rem;color:var(--color-text-muted)<?= (defined('APP_LOCALE') && APP_LOCALE === 'es') ? ';font-weight:700;color:var(--color-accent)' : '' ?>">ES</button>
                </form>

                <?php if (isset($auth) && $auth->isLoggedIn() && isset($user)): ?>
                <div class="avatar" title="<?= e($user['name']) ?>">
                    <span style="display:flex;align-items:center;gap:0.5rem;font-size:0.875rem;color:var(--color-text-muted)">
                        <i class="fa-solid fa-circle-user" style="font-size:1.5rem;color:var(--color-accent)"></i>
                        <?= e($user['name']) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Page content -->
        <main>
            <?= $content ?>
        </main>
    </div>
</div>
</body>
</html>
