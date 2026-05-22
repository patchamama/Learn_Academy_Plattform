<?php

namespace LearnAcademy\Parser;

class ConfigParser
{
    private static array $defaults = [
        'title'              => null,
        'layout'             => 'default',
        'description'        => null,
        'show_attachments'   => true,
        'show_image_gallery' => true,
        'subtitle_file'      => null,
    ];

    /**
     * Parse a .conf.txt file into a config array.
     * Returns defaults merged with whatever is found in the file.
     */
    public static function parse(string $path): array
    {
        $config = self::$defaults;

        if (!is_file($path)) {
            return $config;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $sep = strpos($line, ':');
            if ($sep === false) {
                continue;
            }

            $key   = trim(substr($line, 0, $sep));
            $value = trim(substr($line, $sep + 1));

            switch ($key) {
                case 'title':
                    $config['title'] = $value !== '' ? $value : null;
                    break;
                case 'layout':
                    if (in_array($value, ['default', 'video-first', 'text-first', 'audio-only'], true)) {
                        $config['layout'] = $value;
                    }
                    break;
                case 'description':
                    $config['description'] = $value !== '' ? $value : null;
                    break;
                case 'show_attachments':
                    $config['show_attachments'] = self::parseBool($value);
                    break;
                case 'show_image_gallery':
                    $config['show_image_gallery'] = self::parseBool($value);
                    break;
                case 'subtitle_file':
                    $config['subtitle_file'] = $value !== '' ? $value : null;
                    break;
            }
        }

        return $config;
    }

    /**
     * Write a config array to a .conf.txt file.
     * Creates the file if it doesn't exist.
     */
    public static function write(string $path, array $config): void
    {
        $lines = [];

        if (!empty($config['title'])) {
            $lines[] = 'title: ' . $config['title'];
        }
        if (isset($config['layout']) && $config['layout'] !== 'default') {
            $lines[] = 'layout: ' . $config['layout'];
        }
        if (!empty($config['description'])) {
            $lines[] = 'description: ' . $config['description'];
        }
        if (isset($config['show_attachments']) && $config['show_attachments'] === false) {
            $lines[] = 'show_attachments: false';
        }
        if (isset($config['show_image_gallery']) && $config['show_image_gallery'] === false) {
            $lines[] = 'show_image_gallery: false';
        }
        if (!empty($config['subtitle_file'])) {
            $lines[] = 'subtitle_file: ' . $config['subtitle_file'];
        }

        file_put_contents($path, implode("\n", $lines) . "\n");
    }

    /**
     * Derive a title from the highest-priority filename in a lesson.
     * Strips extension (and optionally the numeric prefix) from the filename.
     */
    public static function titleFromFilename(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        // Strip leading numeric+letter prefix and separator chars
        $title = preg_replace('/^\d+[a-z]*[-_\s]+/i', '', $base);
        if ($title === '' || $title === null) {
            $title = $base;
        }
        return ucfirst(str_replace(['-', '_'], ' ', $title));
    }

    private static function parseBool(string $value): bool
    {
        return in_array(strtolower($value), ['true', '1', 'yes'], true);
    }

    public static function defaults(): array
    {
        return self::$defaults;
    }
}
