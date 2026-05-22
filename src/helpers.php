<?php

/**
 * Translate a UI string key.
 * Falls back to the key itself if not found.
 */
function t(string $key, array $replace = []): string
{
    static $strings = null;

    if ($strings === null) {
        $locale = defined('APP_LOCALE') ? APP_LOCALE : 'en';
        $file   = __DIR__ . '/../i18n/' . $locale . '.php';
        $strings = file_exists($file) ? require $file : [];
    }

    $value = $strings[$key] ?? $key;

    foreach ($replace as $placeholder => $replacement) {
        $value = str_replace(':' . $placeholder, (string) $replacement, $value);
    }

    return $value;
}

/**
 * Escape a string for safe HTML output.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Generate a URL-safe slug.
 */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}
