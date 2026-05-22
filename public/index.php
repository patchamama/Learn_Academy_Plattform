<?php

/**
 * Learn Academy Platform — Front Controller
 */

define('ROOT_DIR', dirname(__DIR__));

// Autoload
if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    require ROOT_DIR . '/vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class): void {
        $prefixes = [
            'LearnAcademy\\App\\'  => ROOT_DIR . '/app/',
            'LearnAcademy\\'       => ROOT_DIR . '/src/',
        ];
        foreach ($prefixes as $prefix => $base) {
            if (!str_starts_with($class, $prefix)) continue;
            $relative = substr($class, strlen($prefix));
            $file     = $base . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) { require $file; return; }
        }
    });
    require_once ROOT_DIR . '/src/helpers.php';
}

// Boot and run
(new \LearnAcademy\App\App())->run();
