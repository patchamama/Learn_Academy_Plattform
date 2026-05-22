<?php

namespace LearnAcademy\App\Controllers;

use LearnAcademy\App\App;

class ApiController
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function requireLoginJson(): void
    {
        if (!$this->app->auth->isLoggedIn()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    private function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }

    // ── Progress ─────────────────────────────────────────────────────────────

    public function getProgress(string $courseSlug): void
    {
        $this->requireLoginJson();

        $db     = $this->app->db;
        $userId = $this->app->auth->user()['id'];

        $course = $db->fetchOne('SELECT id FROM courses WHERE slug = ?', [$courseSlug]);
        if (!$course) {
            $this->json(['error' => 'Course not found'], 404);
        }

        $rows = $db->fetchAll(
            'SELECT p.lesson_id, p.completed, p.completed_at
             FROM progress p
             JOIN lessons l ON l.id = p.lesson_id
             JOIN sections s ON s.id = l.section_id
             WHERE p.user_id = ? AND s.course_id = ?',
            [$userId, $course['id']]
        );

        $map = [];
        foreach ($rows as $row) {
            $map[$row['lesson_id']] = [
                'completed'   => (bool)$row['completed'],
                'completedAt' => $row['completed_at'],
            ];
        }

        $this->json($map);
    }

    public function saveProgress(): void
    {
        $this->requireLoginJson();

        $db     = $this->app->db;
        $userId = $this->app->auth->user()['id'];
        $body   = $this->jsonBody();

        $lessonId  = isset($body['lessonId']) ? (int)$body['lessonId'] : null;
        $completed = isset($body['completed']) ? (bool)$body['completed'] : false;

        if (!$lessonId) {
            $this->json(['error' => 'lessonId required'], 400);
        }

        // Verify lesson exists
        $lesson = $db->fetchOne('SELECT id FROM lessons WHERE id = ?', [$lessonId]);
        if (!$lesson) {
            $this->json(['error' => 'Lesson not found'], 404);
        }

        $completedAt = $completed ? time() : null;

        $existing = $db->fetchOne(
            'SELECT id FROM progress WHERE user_id = ? AND lesson_id = ?',
            [$userId, $lessonId]
        );

        if ($existing) {
            $db->execute(
                'UPDATE progress SET completed = ?, completed_at = ? WHERE user_id = ? AND lesson_id = ?',
                [(int)$completed, $completedAt, $userId, $lessonId]
            );
        } else {
            $db->insert(
                'INSERT INTO progress (user_id, lesson_id, completed, completed_at) VALUES (?, ?, ?, ?)',
                [$userId, $lessonId, (int)$completed, $completedAt]
            );
        }

        $this->json(['ok' => true]);
    }

    // ── Settings ─────────────────────────────────────────────────────────────

    public function getSettings(): void
    {
        $this->requireLoginJson();

        $userId = $this->app->auth->user()['id'];
        $row    = $this->app->db->fetchOne(
            'SELECT settings_json FROM settings WHERE user_id = ?',
            [$userId]
        );

        $settings = $row ? json_decode($row['settings_json'], true) : [];
        $this->json($settings);
    }

    public function saveSettings(): void
    {
        $this->requireLoginJson();

        $db     = $this->app->db;
        $userId = $this->app->auth->user()['id'];
        $body   = $this->jsonBody();

        // Sanitise accepted keys only
        $allowed   = ['theme', 'font_size', 'speed', 'subtitles'];
        $sanitised = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $body)) {
                $sanitised[$key] = $body[$key];
            }
        }

        $settingsJson = json_encode($sanitised, JSON_UNESCAPED_UNICODE);

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

        $this->json(['ok' => true]);
    }

    // ── Comments ─────────────────────────────────────────────────────────────

    public function getComments(int $lessonId): void
    {
        $this->requireLoginJson();

        $db     = $this->app->db;
        $userId = $this->app->auth->user()['id'];

        // Return approved comments + caller's own pending
        $rows = $db->fetchAll(
            'SELECT c.id, c.user_id, c.parent_id, c.body, c.status, c.created_at,
                    u.name AS user_name
             FROM comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.lesson_id = ?
               AND (c.status = \'approved\' OR (c.status = \'pending\' AND c.user_id = ?))
             ORDER BY c.created_at ASC',
            [$lessonId, $userId]
        );

        $comments = [];
        foreach ($rows as $row) {
            $comments[] = [
                'id'        => $row['id'],
                'userId'    => $row['user_id'],
                'userName'  => $row['user_name'],
                'parentId'  => $row['parent_id'],
                'body'      => $row['body'],
                'status'    => $row['status'],
                'pending'   => $row['status'] === 'pending',
                'createdAt' => $row['created_at'],
            ];
        }

        $this->json($comments);
    }

    public function postComment(): void
    {
        $this->requireLoginJson();

        $db     = $this->app->db;
        $userId = $this->app->auth->user()['id'];
        $body   = $this->jsonBody();

        $lessonId = isset($body['lessonId']) ? (int)$body['lessonId'] : null;
        $text     = trim($body['body'] ?? '');
        $parentId = isset($body['parentId']) ? (int)$body['parentId'] : null;

        if (!$lessonId || $text === '') {
            $this->json(['error' => 'lessonId and body are required'], 400);
        }

        // Verify lesson exists
        $lesson = $db->fetchOne('SELECT id FROM lessons WHERE id = ?', [$lessonId]);
        if (!$lesson) {
            $this->json(['error' => 'Lesson not found'], 404);
        }

        // Validate parent if provided
        if ($parentId !== null) {
            $parent = $db->fetchOne(
                'SELECT id FROM comments WHERE id = ? AND lesson_id = ?',
                [$parentId, $lessonId]
            );
            if (!$parent) {
                $parentId = null;
            }
        }

        $id = $db->insert(
            'INSERT INTO comments (user_id, lesson_id, parent_id, body, status) VALUES (?, ?, ?, ?, ?)',
            [$userId, $lessonId, $parentId, $text, 'pending']
        );

        $user = $this->app->auth->user();

        $this->json([
            'ok'      => true,
            'comment' => [
                'id'        => $id,
                'userId'    => $userId,
                'userName'  => $user['name'],
                'parentId'  => $parentId,
                'body'      => $text,
                'status'    => 'pending',
                'pending'   => true,
                'createdAt' => time(),
            ],
        ]);
    }
}
