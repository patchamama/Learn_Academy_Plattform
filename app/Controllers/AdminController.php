<?php

namespace LearnAcademy\App\Controllers;

use LearnAcademy\App\App;
use LearnAcademy\App\CourseImporter;

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

        $userCount           = (int)$db->fetchOne('SELECT COUNT(*) AS cnt FROM users')['cnt'];
        $courseCount         = (int)$db->fetchOne('SELECT COUNT(*) AS cnt FROM courses')['cnt'];
        $pendingCommentCount = (int)$db->fetchOne("SELECT COUNT(*) AS cnt FROM comments WHERE status = 'pending'")['cnt'];

        $this->app->view->layout('admin/index', [
            'userCount'           => $userCount,
            'courseCount'         => $courseCount,
            'pendingCommentCount' => $pendingCommentCount,
        ]);
    }

    // ── Users & Access ───────────────────────────────────────────────────

    public function users(): void
    {
        $this->app->auth->requireAdmin();
        $db = $this->app->db;

        $perPage = 25;
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $totalRow = $db->fetchOne('SELECT COUNT(*) AS cnt FROM users');
        $total    = (int)($totalRow['cnt'] ?? 0);
        $pages    = (int)ceil($total / $perPage);

        $users   = $db->fetchAll(
            'SELECT id, name, email, role, locale, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$perPage, $offset]
        );
        $courses = $db->fetchAll('SELECT id, slug, title FROM courses ORDER BY title');

        $now = time();
        foreach ($users as &$u) {
            $rows = $db->fetchAll(
                'SELECT course_id, expires_at FROM enrollments WHERE user_id = ? AND expires_at > ?',
                [$u['id'], $now]
            );
            $u['enrolled_course_ids'] = array_column($rows, 'course_id');
        }
        unset($u);

        $this->app->view->layout('admin/users', [
            'users'   => $users,
            'courses' => $courses,
            'csrf'    => $this->app->auth->csrfToken(),
            'page'    => $page,
            'pages'   => $pages,
            'total'   => $total,
        ]);
    }

    public function grantAccess(int $userId): void
    {
        $this->app->auth->requireAdmin();
        $this->checkCsrf();

        $courseId  = (int)($_POST['courseId'] ?? 0);
        $expiresAt = null;

        if (!empty($_POST['expiresAt'])) {
            $ts = strtotime($_POST['expiresAt']);
            if ($ts !== false) $expiresAt = $ts;
        }

        if ($courseId > 0) {
            $this->app->auth->grantAccess($userId, $courseId, $this->app->auth->user()['id'], $expiresAt);
        }

        header('Location: /admin/users');
        exit;
    }

    public function revokeAccess(int $userId): void
    {
        $this->app->auth->requireAdmin();
        $this->checkCsrf();

        $courseId = (int)($_POST['courseId'] ?? 0);
        if ($courseId > 0) {
            $this->app->auth->revokeAccess($userId, $courseId);
        }

        header('Location: /admin/users');
        exit;
    }

    // ── Comment Moderation ───────────────────────────────────────────────

    public function moderation(): void
    {
        $this->app->auth->requireAdmin();
        $db = $this->app->db;

        $comments = $db->fetchAll(
            "SELECT c.*, u.name AS author_name, l.title AS lesson_title
             FROM comments c
             JOIN users u ON u.id = c.user_id
             JOIN lessons l ON l.id = c.lesson_id
             WHERE c.status = 'pending'
             ORDER BY c.created_at ASC"
        );

        $this->app->view->layout('admin/moderation', [
            'comments' => $comments,
            'csrf'     => $this->app->auth->csrfToken(),
        ]);
    }

    public function moderateComment(int $commentId): void
    {
        $this->app->auth->requireAdmin();
        $this->checkCsrf();

        $action = $_POST['action'] ?? '';
        if (in_array($action, ['approve', 'reject'], true)) {
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $this->app->db->execute(
                'UPDATE comments SET status = ? WHERE id = ?',
                [$status, $commentId]
            );
        }

        header('Location: /admin/moderation');
        exit;
    }

    // ── Courses ──────────────────────────────────────────────────────────

    public function courses(): void
    {
        $this->app->auth->requireAdmin();

        $courses = $this->app->db->fetchAll(
            'SELECT c.*, COUNT(DISTINCT s.id) AS section_count, COUNT(DISTINCT l.id) AS lesson_count
             FROM courses c
             LEFT JOIN sections s ON s.course_id = c.id
             LEFT JOIN lessons l ON l.section_id = s.id
             GROUP BY c.id ORDER BY c.title'
        );

        $this->app->view->layout('admin/courses', [
            'courses' => $courses,
            'csrf'    => $this->app->auth->csrfToken(),
        ]);
    }

    public function importCourse(): void
    {
        $this->app->auth->requireAdmin();
        $this->checkCsrf();

        $sourceDir = trim($_POST['source_dir'] ?? '');
        $title     = trim($_POST['title'] ?? '');
        $thumbnail = trim($_POST['thumbnail'] ?? '');

        if (empty($sourceDir) || !is_dir($sourceDir)) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid source directory.'];
            header('Location: /admin/courses');
            exit;
        }

        try {
            $importer = new CourseImporter($this->app->db);
            $courseId = $importer->import($sourceDir, $title, $thumbnail);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Course imported (ID: ' . $courseId . ')'];
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Import failed: ' . $e->getMessage()];
        }

        header('Location: /admin/courses');
        exit;
    }

    public function deleteCourse(int $courseId): void
    {
        $this->app->auth->requireAdmin();
        $this->checkCsrf();

        $this->app->db->execute('DELETE FROM courses WHERE id = ?', [$courseId]);

        header('Location: /admin/courses');
        exit;
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function checkCsrf(): void
    {
        if (!$this->app->auth->verifyCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }
    }
}
