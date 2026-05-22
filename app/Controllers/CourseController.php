<?php

namespace LearnAcademy\App\Controllers;

use LearnAcademy\App\App;

class CourseController
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function index(): void
    {
        $db = $this->app->db;

        $courses = $db->fetchAll(
            'SELECT id, slug, title, description, thumbnail, created_at FROM courses ORDER BY created_at DESC'
        );

        // For each course, attach lesson count
        foreach ($courses as &$course) {
            $row = $db->fetchOne(
                'SELECT COUNT(*) AS cnt
                 FROM lessons l
                 JOIN sections s ON s.id = l.section_id
                 WHERE s.course_id = ?',
                [$course['id']]
            );
            $course['lesson_count'] = (int)($row['cnt'] ?? 0);

            // Check enrollment for current user
            $course['enrolled'] = false;
            if ($this->app->auth->isLoggedIn()) {
                $course['enrolled'] = $this->app->auth->hasAccess($course['id']);
            }
        }
        unset($course);

        $this->app->view->layout('course/index', [
            'courses' => $courses,
        ]);
    }

    public function detail(string $slug): void
    {
        $db   = $this->app->db;
        $auth = $this->app->auth;

        $course = $db->fetchOne(
            'SELECT id, slug, title, description, thumbnail, source_dir FROM courses WHERE slug = ?',
            [$slug]
        );

        if (!$course) {
            http_response_code(404);
            echo '404 Course not found';
            return;
        }

        // Load sections and lessons
        $sections = $db->fetchAll(
            'SELECT id, title, folder_name, sort_order FROM sections WHERE course_id = ? ORDER BY sort_order',
            [$course['id']]
        );

        foreach ($sections as &$section) {
            $section['lessons'] = $db->fetchAll(
                'SELECT id, title, prefix, sort_order, config_json FROM lessons WHERE section_id = ? ORDER BY sort_order',
                [$section['id']]
            );
        }
        unset($section);

        // Access state
        $hasAccess = $auth->isLoggedIn() && $auth->hasAccess($course['id']);
        $isAdmin   = $auth->isAdmin();

        // Count stats
        $totalLessons = 0;
        foreach ($sections as $sec) {
            $totalLessons += count($sec['lessons']);
        }

        $this->app->view->layout('course/detail', [
            'course'       => $course,
            'sections'     => $sections,
            'hasAccess'    => $hasAccess,
            'isAdmin'      => $isAdmin,
            'totalLessons' => $totalLessons,
        ]);
    }
}
