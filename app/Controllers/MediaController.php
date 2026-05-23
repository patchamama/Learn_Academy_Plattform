<?php

namespace LearnAcademy\App\Controllers;

use LearnAcademy\App\App;

/**
 * Serves media files from a course source directory.
 * Requires login for access-controlled content.
 */
class MediaController
{
    private App $app;

    private static array $mimeMap = [
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'mov'  => 'video/quicktime',
        'mp3'  => 'audio/mpeg',
        'wav'  => 'audio/wav',
        'ogg'  => 'audio/ogg',
        'm4a'  => 'audio/mp4',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        'pdf'  => 'application/pdf',
        'vtt'  => 'text/vtt',
        'srt'  => 'text/plain',
        'zip'  => 'application/zip',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function serve(string $courseSlug, string $filename): void
    {
        $this->app->auth->requireLogin();

        // Sanitize filename — no path traversal
        $filename = basename($filename);
        if ($filename === '' || str_contains($filename, '..')) {
            http_response_code(400);
            exit('Invalid filename.');
        }

        // Load course source directory from DB
        $course = $this->app->db->fetchOne(
            'SELECT id, source_dir FROM courses WHERE slug = ?',
            [$courseSlug]
        );

        if (!$course) {
            http_response_code(404);
            exit('Course not found.');
        }

        if (!$this->app->auth->hasAccess($course['id'])) {
            http_response_code(403);
            exit('Access denied.');
        }

        // Find the file in any section subdirectory
        $file = $this->findFile($course['source_dir'], $filename);

        if (!$file || !is_readable($file)) {
            http_response_code(404);
            exit('File not found.');
        }

        $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = self::$mimeMap[$ext] ?? 'application/octet-stream';
        $size = filesize($file);

        // Range request support (for video/audio scrubbing)
        $start = 0;
        $end   = $size - 1;

        if (isset($_SERVER['HTTP_RANGE'])) {
            if (preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
                $start = (int)$m[1];
                if ($m[2] !== '') $end = (int)$m[2];
            }
            http_response_code(206);
            header("Content-Range: bytes $start-$end/$size");
        } else {
            http_response_code(200);
        }

        $length = $end - $start + 1;

        header("Content-Type: $mime");
        header("Content-Length: $length");
        header("Accept-Ranges: bytes");
        header("Cache-Control: private, max-age=3600");

        $fp = fopen($file, 'rb');
        fseek($fp, $start);
        $remaining = $length;

        while ($remaining > 0 && !feof($fp)) {
            $chunk = min(8192, $remaining);
            echo fread($fp, $chunk);
            $remaining -= $chunk;
            flush();
        }

        fclose($fp);
    }

    private function findFile(string $sourceDir, string $filename): ?string
    {
        // Search recursively in section subdirectories
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            if ($file->isFile() && $file->getFilename() === $filename) {
                return $file->getPathname();
            }
        }

        return null;
    }
}
