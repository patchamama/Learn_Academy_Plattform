<?php

namespace LearnAcademy\App\Controllers;

use LearnAcademy\App\App;

class AdminController
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function index(): void
    {
        $this->app->auth->requireAdmin();

        $db = $this->app->db;

        $userCount = (int)$db->fetchOne('SELECT COUNT(*) AS cnt FROM users')['cnt'];
        $courseCount = (int)$db->fetchOne('SELECT COUNT(*) AS cnt FROM courses')['cnt'];
        $pendingCommentCount = (int)$db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM comments WHERE status = 'pending'"
        )['cnt'];

        $this->app->view->layout('admin/index', [
            'userCount'           => $userCount,
            'courseCount'         => $courseCount,
            'pendingCommentCount' => $pendingCommentCount,
        ]);
    }

    public function users(): void
    {
        $this->app->auth->requireAdmin();

        $db = $this->app->db;

        $users = $db->fetchAll(
            'SELECT id, name, email, role, locale, created_at FROM users ORDER BY created_at DESC'
        );

        $courses = $db->fetchAll('SELECT id, slug, title FROM courses ORDER BY title');

        // For each user, collect which courses they are enrolled in
        $now = time();
        foreach ($users as &$user) {
            $enrollments = $db->fetchAll(
                'SELECT course_id, expires_at FROM enrollments WHERE user_id = ? AND expires_at > ?',
                [$user['id'], $now]
            );
            $user['enrolled_course_ids'] = array_column($enrollments, 'course_id');
        }
        unset($user);

        $this->app->view->layout('admin/users', [
            'users'   => $users,
            'courses' => $courses,
            'csrf'    => $this->app->auth->csrfToken(),
        ]);
    }

    public function grantAccess(int $userId): void
    {
        $this->app->auth->requireAdmin();

        if (!$this->app->auth->verifyCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        $courseId  = (int)($_POST['courseId'] ?? 0);
        $expiresAt = null;

        if (!empty($_POST['expiresAt'])) {
            $ts = strtotime($_POST['expiresAt']);
            if ($ts !== false) {
                $expiresAt = $ts;
            }
        }

        $adminId = $this->app->auth->user()['id'];

        if ($courseId > 0) {
            $this->app->auth->grantAccess($userId, $courseId, $adminId, $expiresAt);
        }

        header('Location: /admin/users');
        exit;
    }

    public function moderateComment(int $commentId): void
    {
        $this->app->auth->requireAdmin();

        if (!$this->app->auth->verifyCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }

        $action = $_POST['action'] ?? '';

        if (in_array($action, ['approve', 'reject'], true)) {
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $this->app->db->execute(
                'UPDATE comments SET status = ? WHERE id = ?',
                [$status, $commentId]
            );
        }

        header('Location: /admin');
        exit;
    }
}
