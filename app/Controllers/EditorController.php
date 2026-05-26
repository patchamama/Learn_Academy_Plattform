<?php

namespace LearnAcademy\App\Controllers;

use LearnAcademy\App\App;
use LearnAcademy\App\CourseImporter;
use LearnAcademy\Parser\ConfigParser;

class EditorController
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    // ── Index ────────────────────────────────────────────────────────────────

    public function index(): void
    {
        $this->app->auth->requireAdmin();

        $courses = $this->app->db->fetchAll(
            'SELECT c.*, COUNT(DISTINCT s.id) AS section_count, COUNT(DISTINCT l.id) AS lesson_count
             FROM courses c
             LEFT JOIN sections s ON s.course_id = c.id
             LEFT JOIN lessons l ON l.section_id = s.id
             GROUP BY c.id ORDER BY c.title'
        );

        $this->app->view->layout('admin/editor/index', [
            'courses' => $courses,
        ]);
    }

    // ── Course editor ────────────────────────────────────────────────────────

    public function course(int $courseId): void
    {
        $this->app->auth->requireAdmin();
        $db = $this->app->db;

        $course = $db->fetchOne('SELECT * FROM courses WHERE id = ?', [$courseId]);
        if (!$course) {
            http_response_code(404);
            exit('Course not found.');
        }

        $sections = $db->fetchAll(
            'SELECT * FROM sections WHERE course_id = ? ORDER BY sort_order',
            [$courseId]
        );

        $tree = [];
        foreach ($sections as $section) {
            $lessons = $db->fetchAll(
                'SELECT * FROM lessons WHERE section_id = ? ORDER BY sort_order',
                [$section['id']]
            );

            $lessonsData = [];
            foreach ($lessons as $lesson) {
                $files = $db->fetchAll(
                    'SELECT * FROM lesson_files WHERE lesson_id = ? ORDER BY id',
                    [$lesson['id']]
                );
                $config = json_decode($lesson['config_json'] ?? '{}', true) ?? [];

                $lessonsData[] = [
                    'id'                  => (int)$lesson['id'],
                    'title'               => $lesson['title'],
                    'prefix'              => $lesson['prefix'],
                    'sort_order'          => (int)$lesson['sort_order'],
                    'config'              => $config,
                    'files'               => $files,
                ];
            }

            $tree[] = [
                'id'          => (int)$section['id'],
                'title'       => $section['title'],
                'folder_name' => $section['folder_name'],
                'sort_order'  => (int)$section['sort_order'],
                'lessons'     => $lessonsData,
            ];
        }

        $this->app->view->layout('admin/editor/course', [
            'course' => $course,
            'tree'   => $tree,
            'csrf'   => $this->app->auth->csrfToken(),
        ]);
    }

    // ── Save lesson config ───────────────────────────────────────────────────

    public function saveLesson(int $courseId, int $lessonId): void
    {
        $this->app->auth->requireAdmin();
        $this->checkCsrf();

        $db     = $this->app->db;
        $course = $db->fetchOne('SELECT * FROM courses WHERE id = ?', [$courseId]);
        if (!$course) {
            $this->flash('error', 'Course not found.');
            $this->back("/admin/editor/{$courseId}");
        }

        $lesson = $db->fetchOne(
            'SELECT l.*, s.folder_name FROM lessons l JOIN sections s ON s.id = l.section_id WHERE l.id = ?',
            [$lessonId]
        );
        if (!$lesson) {
            $this->flash('error', 'Lesson not found.');
            $this->back("/admin/editor/{$courseId}");
        }

        $title             = trim($_POST['title'] ?? '');
        $layout            = $_POST['layout'] ?? 'default';
        $description       = trim($_POST['description'] ?? '');
        $showAttachments   = isset($_POST['show_attachments']);
        $showImageGallery  = isset($_POST['show_image_gallery']);

        if (!in_array($layout, ['default', 'video-first', 'text-first', 'audio-only'], true)) {
            $layout = 'default';
        }

        $config = [
            'title'              => $title !== '' ? $title : null,
            'layout'             => $layout,
            'description'        => $description !== '' ? $description : null,
            'show_attachments'   => $showAttachments,
            'show_image_gallery' => $showImageGallery,
        ];

        // Keep existing subtitle_file if present
        $existing = json_decode($lesson['config_json'] ?? '{}', true) ?? [];
        if (!empty($existing['subtitle_file'])) {
            $config['subtitle_file'] = $existing['subtitle_file'];
        }

        $db->execute(
            'UPDATE lessons SET title = ?, config_json = ? WHERE id = ?',
            [$title !== '' ? $title : $lesson['title'], json_encode($config), $lessonId]
        );

        // Write .conf.txt if course has a source_dir
        if (!empty($course['source_dir']) && !empty($lesson['folder_name']) && !empty($lesson['prefix'])) {
            $confPath = rtrim($course['source_dir'], '/') . '/' . $lesson['folder_name'] . '/' . $lesson['prefix'] . '.conf.txt';
            try {
                ConfigParser::write($confPath, $config);
            } catch (\Throwable $e) {
                // Non-fatal — DB is the source of truth
            }
        }

        $this->flash('success', 'Lesson config saved.');
        $this->back("/admin/editor/{$courseId}");
    }

    // ── Upload file ──────────────────────────────────────────────────────────

    public function uploadFile(int $courseId, int $lessonId): void
    {
        $this->app->auth->requireAdmin();
        $this->checkCsrf();

        $db     = $this->app->db;
        $course = $db->fetchOne('SELECT * FROM courses WHERE id = ?', [$courseId]);
        if (!$course) {
            $this->flash('error', 'Course not found.');
            $this->back("/admin/editor/{$courseId}");
        }

        $lesson = $db->fetchOne(
            'SELECT l.*, s.folder_name FROM lessons l JOIN sections s ON s.id = l.section_id WHERE l.id = ?',
            [$lessonId]
        );
        if (!$lesson) {
            $this->flash('error', 'Lesson not found.');
            $this->back("/admin/editor/{$courseId}");
        }

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Upload failed or no file selected.');
            $this->back("/admin/editor/{$courseId}");
        }

        $allowedExtensions = [
            'mp4', 'webm', 'mov',                   // video
            'mp3', 'wav', 'ogg', 'm4a',              // audio
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', // image
            'pdf', 'zip', 'docx',                    // attachment
            'vtt', 'srt',                            // subtitle
            'md', 'txt', 'html',                     // text/markdown/html
        ];

        $originalName = $_FILES['file']['name'];
        $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions, true)) {
            $this->flash('error', 'File type not allowed: ' . $ext);
            $this->back("/admin/editor/{$courseId}");
        }

        $fileType = $this->detectFileType($ext);

        // Determine destination directory
        if (!empty($course['source_dir']) && !empty($lesson['folder_name'])) {
            $destDir = rtrim($course['source_dir'], '/') . '/' . $lesson['folder_name'];
        } else {
            $this->flash('error', 'Course has no source directory configured.');
            $this->back("/admin/editor/{$courseId}");
        }

        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            $this->flash('error', 'Could not create destination directory.');
            $this->back("/admin/editor/{$courseId}");
        }

        $destPath = $destDir . '/' . $originalName;
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $destPath)) {
            $this->flash('error', 'Failed to move uploaded file.');
            $this->back("/admin/editor/{$courseId}");
        }

        $db->execute(
            'INSERT INTO lesson_files (lesson_id, filename, file_type, path) VALUES (?, ?, ?, ?)',
            [$lessonId, $originalName, $fileType, $destPath]
        );

        $this->flash('success', 'File uploaded: ' . $originalName);
        $this->back("/admin/editor/{$courseId}");
    }

    // ── Delete file ──────────────────────────────────────────────────────────

    public function deleteFile(int $courseId, int $fileId): void
    {
        $this->app->auth->requireAdmin();
        $this->checkCsrf();

        $db   = $this->app->db;
        $file = $db->fetchOne('SELECT * FROM lesson_files WHERE id = ?', [$fileId]);

        if ($file) {
            $course = $db->fetchOne('SELECT source_dir FROM courses WHERE id = ?', [$courseId]);

            // Delete from disk only if file lives inside source_dir
            if ($course && !empty($course['source_dir']) && !empty($file['path'])) {
                $sourceDir = realpath($course['source_dir']);
                $filePath  = realpath($file['path']);
                if ($filePath && $sourceDir && str_starts_with($filePath, $sourceDir)) {
                    @unlink($filePath);
                }
            }

            $db->execute('DELETE FROM lesson_files WHERE id = ?', [$fileId]);
            $this->flash('success', 'File deleted.');
        } else {
            $this->flash('error', 'File not found.');
        }

        $this->back("/admin/editor/{$courseId}");
    }

    // ── Add section ──────────────────────────────────────────────────────────

    public function addSection(int $courseId): void
    {
        $this->app->auth->requireAdmin();
        $this->checkCsrf();

        $db     = $this->app->db;
        $course = $db->fetchOne('SELECT id FROM courses WHERE id = ?', [$courseId]);
        if (!$course) {
            $this->flash('error', 'Course not found.');
            $this->back("/admin/editor/{$courseId}");
        }

        $title      = trim($_POST['title'] ?? 'New Section');
        $folderName = trim($_POST['folder_name'] ?? 'new-section-' . time());

        $maxOrder = $db->fetchOne(
            'SELECT MAX(sort_order) AS m FROM sections WHERE course_id = ?',
            [$courseId]
        )['m'] ?? -1;

        $db->insert(
            'INSERT INTO sections (course_id, folder_name, sort_order, title) VALUES (?, ?, ?, ?)',
            [$courseId, $folderName, (int)$maxOrder + 1, $title]
        );

        $this->flash('success', 'Section added.');
        $this->back("/admin/editor/{$courseId}");
    }

    // ── Delete section ───────────────────────────────────────────────────────

    public function deleteSection(int $courseId, int $sectionId): void
    {
        $this->app->auth->requireAdmin();
        $this->checkCsrf();

        $db = $this->app->db;

        // Cascade: lesson_files → lessons → section
        $lessons = $db->fetchAll('SELECT id FROM lessons WHERE section_id = ?', [$sectionId]);
        foreach ($lessons as $lesson) {
            $db->execute('DELETE FROM lesson_files WHERE lesson_id = ?', [$lesson['id']]);
        }
        $db->execute('DELETE FROM lessons WHERE section_id = ?', [$sectionId]);
        $db->execute('DELETE FROM sections WHERE id = ?', [$sectionId]);

        $this->flash('success', 'Section deleted.');
        $this->back("/admin/editor/{$courseId}");
    }

    // ── Add lesson ───────────────────────────────────────────────────────────

    public function addLesson(int $courseId, int $sectionId): void
    {
        $this->app->auth->requireAdmin();
        $this->checkCsrf();

        $db      = $this->app->db;
        $section = $db->fetchOne('SELECT id FROM sections WHERE id = ? AND course_id = ?', [$sectionId, $courseId]);
        if (!$section) {
            $this->flash('error', 'Section not found.');
            $this->back("/admin/editor/{$courseId}");
        }

        $title  = trim($_POST['title'] ?? 'New Lesson');
        $prefix = '01';

        // Pick next available prefix number
        $maxLesson = $db->fetchOne(
            'SELECT MAX(CAST(prefix AS INTEGER)) AS m FROM lessons WHERE section_id = ?',
            [$sectionId]
        );
        if ($maxLesson && $maxLesson['m'] !== null) {
            $prefix = str_pad((int)$maxLesson['m'] + 1, 2, '0', STR_PAD_LEFT);
        }

        $maxOrder = $db->fetchOne(
            'SELECT MAX(sort_order) AS m FROM lessons WHERE section_id = ?',
            [$sectionId]
        )['m'] ?? -1;

        $db->insert(
            'INSERT INTO lessons (section_id, prefix, sort_order, title, config_json) VALUES (?, ?, ?, ?, ?)',
            [$sectionId, $prefix, (int)$maxOrder + 1, $title, json_encode(['title' => $title, 'layout' => 'default'])]
        );

        $this->flash('success', 'Lesson added.');
        $this->back("/admin/editor/{$courseId}");
    }

    // ── Delete lesson ────────────────────────────────────────────────────────

    public function deleteLesson(int $courseId, int $lessonId): void
    {
        $this->app->auth->requireAdmin();
        $this->checkCsrf();

        $db = $this->app->db;
        $db->execute('DELETE FROM lesson_files WHERE lesson_id = ?', [$lessonId]);
        $db->execute('DELETE FROM lessons WHERE id = ?', [$lessonId]);

        $this->flash('success', 'Lesson deleted.');
        $this->back("/admin/editor/{$courseId}");
    }

    // ── Reorder ──────────────────────────────────────────────────────────────

    public function reorder(int $courseId): void
    {
        $this->app->auth->requireAdmin();

        // Accept JSON body
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (!is_array($data)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid JSON']);
            exit;
        }

        // Verify CSRF from header or body
        $token = $data['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$this->app->auth->verifyCsrf($token)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $db = $this->app->db;

        if (!empty($data['sections']) && is_array($data['sections'])) {
            foreach ($data['sections'] as $item) {
                if (isset($item['id'], $item['order'])) {
                    $db->execute(
                        'UPDATE sections SET sort_order = ? WHERE id = ? AND course_id = ?',
                        [(int)$item['order'], (int)$item['id'], $courseId]
                    );
                }
            }
        }

        if (!empty($data['lessons']) && is_array($data['lessons'])) {
            foreach ($data['lessons'] as $item) {
                if (!isset($item['id'], $item['order'])) continue;
                if (isset($item['sectionId'])) {
                    $db->execute(
                        'UPDATE lessons SET sort_order = ?, section_id = ? WHERE id = ?',
                        [(int)$item['order'], (int)$item['sectionId'], (int)$item['id']]
                    );
                } else {
                    $db->execute(
                        'UPDATE lessons SET sort_order = ? WHERE id = ?',
                        [(int)$item['order'], (int)$item['id']]
                    );
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    // ── Re-import ────────────────────────────────────────────────────────────

    public function reimport(int $courseId): void
    {
        $this->app->auth->requireAdmin();
        $this->checkCsrf();

        $db     = $this->app->db;
        $course = $db->fetchOne('SELECT * FROM courses WHERE id = ?', [$courseId]);

        if (!$course || empty($course['source_dir'])) {
            $this->flash('error', 'Course has no source directory configured.');
            $this->back("/admin/editor/{$courseId}");
        }

        try {
            $importer = new CourseImporter($db);
            $importer->import($course['source_dir'], $course['title'], $course['thumbnail'] ?? '');
            $this->flash('success', 'Course re-imported from disk.');
        } catch (\Throwable $e) {
            $this->flash('error', 'Re-import failed: ' . $e->getMessage());
        }

        $this->back("/admin/editor/{$courseId}");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function checkCsrf(): void
    {
        if (!$this->app->auth->verifyCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }
    }

    private function flash(string $type, string $msg): void
    {
        $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    }

    private function back(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    private function detectFileType(string $ext): string
    {
        return match (true) {
            in_array($ext, ['mp4', 'webm', 'mov'], true)                     => 'video',
            in_array($ext, ['mp3', 'wav', 'ogg', 'm4a'], true)               => 'audio',
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true) => 'image',
            in_array($ext, ['vtt', 'srt'], true)                             => 'subtitle',
            $ext === 'md'                                                     => 'markdown',
            $ext === 'html'                                                   => 'html',
            in_array($ext, ['txt'], true)                                    => 'text',
            default                                                           => 'attachment',
        };
    }
}
