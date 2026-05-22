<?php

namespace LearnAcademy\App\Controllers;

use LearnAcademy\App\App;

class DashboardController
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function index(): void
    {
        $this->app->auth->requireLogin();

        $db     = $this->app->db;
        $userId = $this->app->auth->user()['id'];
        $now    = time();

        // Fetch active enrollments with course info
        $enrollments = $db->fetchAll(
            'SELECT e.id AS enrollment_id, e.expires_at, c.id AS course_id,
                    c.slug, c.title, c.description, c.thumbnail
             FROM enrollments e
             JOIN courses c ON c.id = e.course_id
             WHERE e.user_id = ? AND e.expires_at > ?
             ORDER BY e.created_at DESC',
            [$userId, $now]
        );

        // For each enrollment, compute progress stats
        $courses = [];
        foreach ($enrollments as $enrollment) {
            $courseId = $enrollment['course_id'];

            // Total lessons in course
            $totalRow = $db->fetchOne(
                'SELECT COUNT(*) AS cnt
                 FROM lessons l
                 JOIN sections s ON s.id = l.section_id
                 WHERE s.course_id = ?',
                [$courseId]
            );
            $total = (int)($totalRow['cnt'] ?? 0);

            // Completed lessons by this user in this course
            $completedRow = $db->fetchOne(
                'SELECT COUNT(*) AS cnt
                 FROM progress p
                 JOIN lessons l ON l.id = p.lesson_id
                 JOIN sections s ON s.id = l.section_id
                 WHERE p.user_id = ? AND s.course_id = ? AND p.completed = 1',
                [$userId, $courseId]
            );
            $completed = (int)($completedRow['cnt'] ?? 0);

            $percent = $total > 0 ? round(($completed / $total) * 100) : 0;

            $courses[] = [
                'enrollment_id' => $enrollment['enrollment_id'],
                'expires_at'    => $enrollment['expires_at'],
                'course_id'     => $courseId,
                'slug'          => $enrollment['slug'],
                'title'         => $enrollment['title'],
                'description'   => $enrollment['description'],
                'thumbnail'     => $enrollment['thumbnail'],
                'total'         => $total,
                'completed'     => $completed,
                'percent'       => $percent,
            ];
        }

        $this->app->view->layout('dashboard/index', [
            'courses' => $courses,
        ]);
    }
}
