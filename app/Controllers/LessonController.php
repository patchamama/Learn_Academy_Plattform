<?php

namespace LearnAcademy\App\Controllers;

use LearnAcademy\App\App;

class LessonController
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function show(string $courseSlug, string $lessonId): void
    {
        $auth = $this->app->auth;
        $db   = $this->app->db;

        $auth->requireLogin();

        // Load course
        $course = $db->fetchOne(
            'SELECT id, slug, title, description FROM courses WHERE slug = ?',
            [$courseSlug]
        );

        if (!$course) {
            http_response_code(404);
            echo '404 Course not found';
            return;
        }

        // Load lesson
        $lesson = $db->fetchOne(
            'SELECT l.id, l.title, l.prefix, l.config_json, l.section_id,
                    s.title AS section_title, s.sort_order AS section_order,
                    s.course_id
             FROM lessons l
             JOIN sections s ON s.id = l.section_id
             WHERE l.id = ? AND s.course_id = ?',
            [(int)$lessonId, $course['id']]
        );

        if (!$lesson) {
            http_response_code(404);
            echo '404 Lesson not found';
            return;
        }

        // Check access
        if (!$auth->hasLessonAccess($course['id'], $lesson['id'])) {
            http_response_code(403);
            $this->app->view->layout('lesson/locked', [
                'course' => $course,
                'lesson' => $lesson,
            ]);
            return;
        }

        // Load lesson files
        $files = $db->fetchAll(
            'SELECT id, filename, file_type, path FROM lesson_files WHERE lesson_id = ? ORDER BY id',
            [$lesson['id']]
        );

        // Categorise files
        $mainContent = null;
        $attachments = [];
        $images      = [];
        $subtitles   = [];

        foreach ($files as $file) {
            if (in_array($file['file_type'], ['video', 'audio', 'text', 'html', 'markdown'], true)
                && $mainContent === null
            ) {
                $mainContent = $file;
            } elseif ($file['file_type'] === 'image') {
                $images[] = $file;
            } elseif ($file['file_type'] === 'subtitle') {
                $subtitles[] = $file;
            } elseif ($file['file_type'] === 'attachment') {
                $attachments[] = $file;
            }
        }

        // Build ordered lesson list for prev/next navigation
        $allLessons = $db->fetchAll(
            'SELECT l.id, l.title
             FROM lessons l
             JOIN sections s ON s.id = l.section_id
             WHERE s.course_id = ?
             ORDER BY s.sort_order, l.sort_order',
            [$course['id']]
        );

        $prevLesson = null;
        $nextLesson = null;
        $currentIndex = null;

        foreach ($allLessons as $i => $l) {
            if ((int)$l['id'] === (int)$lesson['id']) {
                $currentIndex = $i;
                break;
            }
        }

        if ($currentIndex !== null) {
            if ($currentIndex > 0) {
                $prevLesson = $allLessons[$currentIndex - 1];
            }
            if ($currentIndex < count($allLessons) - 1) {
                $nextLesson = $allLessons[$currentIndex + 1];
            }
        }

        // Load all sections for sidebar
        $sections = $db->fetchAll(
            'SELECT id, title, sort_order FROM sections WHERE course_id = ? ORDER BY sort_order',
            [$course['id']]
        );

        foreach ($sections as &$section) {
            $section['lessons'] = $db->fetchAll(
                'SELECT id, title, prefix, sort_order FROM lessons WHERE section_id = ? ORDER BY sort_order',
                [$section['id']]
            );
        }
        unset($section);

        $this->app->view->layout('lesson/show', [
            'course'      => $course,
            'lesson'      => $lesson,
            'mainContent' => $mainContent,
            'attachments' => $attachments,
            'images'      => $images,
            'subtitles'   => $subtitles,
            'prevLesson'  => $prevLesson,
            'nextLesson'  => $nextLesson,
            'sections'    => $sections,
        ], 'layouts/lesson');
    }
}
