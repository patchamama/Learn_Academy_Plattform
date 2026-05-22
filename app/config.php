<?php

/**
 * Application configuration.
 * Copy to app/config.local.php and override values — local file is gitignored.
 */

return [
    'app_name'    => 'Learn Academy',
    'app_url'     => 'http://localhost:8080',

    'db_path'     => __DIR__ . '/../database/app.sqlite',

    'session_name' => 'lap_session',

    'stripe_public_key'  => '',
    'stripe_secret_key'  => '',
    'stripe_webhook_secret' => '',

    'paypal_client_id'   => '',
    'paypal_secret'      => '',
    'paypal_mode'        => 'sandbox',

    'content_base_dir'   => __DIR__ . '/../courses',
    'output_base_dir'    => __DIR__ . '/../public/courses',

    'default_locale'     => 'en',
    'supported_locales'  => ['en', 'es'],

    'course_access_days' => 365,

    'admin_email'        => '',
];
