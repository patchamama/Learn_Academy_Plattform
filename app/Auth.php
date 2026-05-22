<?php

namespace LearnAcademy\App;

class Auth
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // ── Session ──────────────────────────────────────────────────────────

    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user']);
    }

    public function isAdmin(): bool
    {
        return ($this->user()['role'] ?? '') === 'admin';
    }

    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    public function requireAdmin(): void
    {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            http_response_code(403);
            exit('Access denied.');
        }
    }

    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function verifyCsrf(string $token): bool
    {
        return isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    // ── Register ─────────────────────────────────────────────────────────

    public function register(string $email, string $password, string $name, string $locale = 'en'): array
    {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'invalid_email'];
        }

        if (strlen($password) < 8) {
            return ['ok' => false, 'error' => 'password_too_short'];
        }

        $exists = $this->db->fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
        if ($exists) {
            return ['ok' => false, 'error' => 'email_taken'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $id   = $this->db->insert(
            'INSERT INTO users (email, password_hash, name, locale) VALUES (?, ?, ?, ?)',
            [$email, $hash, trim($name), $locale]
        );

        // Create empty settings row
        $this->db->execute(
            'INSERT INTO settings (user_id, settings_json) VALUES (?, ?)',
            [$id, '{}']
        );

        $user = $this->db->fetchOne('SELECT id, email, name, role, locale FROM users WHERE id = ?', [$id]);
        $_SESSION['user'] = $user;

        return ['ok' => true, 'user' => $user];
    }

    // ── Login ────────────────────────────────────────────────────────────

    public function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $row   = $this->db->fetchOne('SELECT * FROM users WHERE email = ?', [$email]);

        if (!$row || !password_verify($password, $row['password_hash'])) {
            return ['ok' => false, 'error' => 'invalid_credentials'];
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $user = [
            'id'     => $row['id'],
            'email'  => $row['email'],
            'name'   => $row['name'],
            'role'   => $row['role'],
            'locale' => $row['locale'],
        ];
        $_SESSION['user'] = $user;

        return ['ok' => true, 'user' => $user];
    }

    // ── Logout ───────────────────────────────────────────────────────────

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    // ── Access Control ───────────────────────────────────────────────────

    /**
     * Check if the current user has active enrollment for a course.
     */
    public function hasAccess(int $courseId): bool
    {
        if (!$this->isLoggedIn()) return false;
        if ($this->isAdmin()) return true;

        $userId = $this->user()['id'];
        $now    = time();

        $row = $this->db->fetchOne(
            'SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND expires_at > ?',
            [$userId, $courseId, $now]
        );

        return $row !== null;
    }

    /**
     * Check if the current user has access to a specific lesson.
     * Falls back to course-level access if no per-lesson grant exists.
     */
    public function hasLessonAccess(int $courseId, int $lessonId): bool
    {
        if (!$this->hasAccess($courseId)) return false;

        // Check if the lesson has granular access grants in any enrollment
        $userId = $this->user()['id'];
        $now    = time();

        // If no access_grants exist for this course's enrollment, full access is granted
        $enrollment = $this->db->fetchOne(
            'SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND expires_at > ?',
            [$userId, $courseId, $now]
        );

        if (!$enrollment) return false;

        // Check if ANY access_grants exist for this enrollment (partial unlock mode)
        $grantCount = $this->db->fetchOne(
            'SELECT COUNT(*) as cnt FROM access_grants WHERE enrollment_id = ?',
            [$enrollment['id']]
        );

        if ((int)$grantCount['cnt'] === 0) {
            // No per-lesson grants = full course access
            return true;
        }

        // Per-lesson grants exist — check if this lesson is unlocked
        $grant = $this->db->fetchOne(
            'SELECT id FROM access_grants WHERE enrollment_id = ? AND lesson_id = ?',
            [$enrollment['id'], $lessonId]
        );

        return $grant !== null;
    }

    /**
     * Grant full course access to a user.
     */
    public function grantAccess(int $userId, int $courseId, int $grantedBy, ?int $expiresAt = null): bool
    {
        $expiresAt ??= strtotime('+1 year');

        try {
            $this->db->execute(
                'INSERT OR REPLACE INTO enrollments (user_id, course_id, granted_by, expires_at) VALUES (?, ?, ?, ?)',
                [$userId, $courseId, $grantedBy, $expiresAt]
            );
            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    /**
     * Revoke a user's access to a course.
     */
    public function revokeAccess(int $userId, int $courseId): bool
    {
        return $this->db->execute(
            'DELETE FROM enrollments WHERE user_id = ? AND course_id = ?',
            [$userId, $courseId]
        ) > 0;
    }
}
