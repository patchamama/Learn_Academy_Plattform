<?php

namespace LearnAcademy\App;

use LearnAcademy\Parser\CourseBuilder;

/**
 * Imports a course from a content directory into the SQLite database.
 * Creates or updates courses, sections, lessons, and lesson_files.
 */
class CourseImporter
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Import or re-import a course from a directory.
     * Returns the course ID.
     */
    public function import(string $sourceDir, string $title = '', string $thumbnail = ''): int
    {
        $builder = new CourseBuilder($sourceDir, $title);
        $course  = $builder->build();

        return $this->db->transaction(function (Database $db) use ($course, $thumbnail, $sourceDir) {
            // Upsert course
            $existing = $db->fetchOne('SELECT id FROM courses WHERE slug = ?', [$course['slug']]);

            if ($existing) {
                $courseId = (int)$existing['id'];
                $db->execute(
                    'UPDATE courses SET title = ?, description = ?, source_dir = ?, thumbnail = ? WHERE id = ?',
                    [$course['title'], $course['description'] ?? '', $sourceDir, $thumbnail, $courseId]
                );
                // Delete existing sections cascade (lesson_files, lessons, sections)
                $db->execute('DELETE FROM sections WHERE course_id = ?', [$courseId]);
            } else {
                $courseId = $db->insert(
                    'INSERT INTO courses (slug, title, description, source_dir, thumbnail)
                     VALUES (?, ?, ?, ?, ?)',
                    [$course['slug'], $course['title'], $course['description'] ?? '', $sourceDir, $thumbnail]
                );
            }

            foreach ($course['sections'] as $sectionOrder => $section) {
                $sectionId = $db->insert(
                    'INSERT INTO sections (course_id, folder_name, sort_order, title)
                     VALUES (?, ?, ?, ?)',
                    [$courseId, $section['folder_name'], $sectionOrder, $section['title']]
                );

                foreach ($section['lessons'] as $lessonOrder => $lesson) {
                    $lessonId = $db->insert(
                        'INSERT INTO lessons (section_id, prefix, sort_order, title, config_json)
                         VALUES (?, ?, ?, ?, ?)',
                        [
                            $sectionId,
                            $lesson['prefix'],
                            $lessonOrder,
                            $lesson['title'],
                            json_encode($lesson['config'] ?? []),
                        ]
                    );

                    // Insert all files for this lesson
                    $allFiles = array_merge(
                        $lesson['main_content'] ? [$lesson['main_content']] : [],
                        $lesson['supplemental'] ?? [],
                        $lesson['attachments'] ?? [],
                        $lesson['subtitles'] ?? []
                    );

                    foreach ($allFiles as $file) {
                        $db->execute(
                            'INSERT INTO lesson_files (lesson_id, filename, file_type, path)
                             VALUES (?, ?, ?, ?)',
                            [$lessonId, $file['filename'], $file['type'], $file['path']]
                        );
                    }
                }
            }

            return $courseId;
        });
    }

    /**
     * Load full course model from DB (replaces CourseBuilder for dynamic mode).
     * Returns a structure compatible with what CourseBuilder::build() returns.
     */
    public function loadFromDb(string $slug): ?array
    {
        $course = $this->db->fetchOne(
            'SELECT * FROM courses WHERE slug = ?',
            [$slug]
        );

        if (!$course) return null;

        $sections = $this->db->fetchAll(
            'SELECT * FROM sections WHERE course_id = ? ORDER BY sort_order',
            [$course['id']]
        );

        $courseSections = [];
        foreach ($sections as $section) {
            $lessons = $this->db->fetchAll(
                'SELECT * FROM lessons WHERE section_id = ? ORDER BY sort_order',
                [$section['id']]
            );

            $sectionLessons = [];
            foreach ($lessons as $lesson) {
                $files = $this->db->fetchAll(
                    'SELECT * FROM lesson_files WHERE lesson_id = ?',
                    [$lesson['id']]
                );

                $config = json_decode($lesson['config_json'] ?? '{}', true) ?? [];

                // Reconstruct lesson file groups
                $mainContent  = null;
                $supplemental = [];
                $attachments  = [];
                $subtitles    = [];

                foreach ($files as $file) {
                    $entry = [
                        'filename' => $file['filename'],
                        'type'     => $file['file_type'],
                        'path'     => $file['path'],
                        'ext'      => pathinfo($file['filename'], PATHINFO_EXTENSION),
                    ];

                    if (in_array($file['file_type'], ['video', 'audio', 'markdown', 'html'], true)) {
                        if ($mainContent === null) {
                            $mainContent = $entry;
                        } else {
                            $supplemental[] = $entry;
                        }
                    } elseif ($file['file_type'] === 'image') {
                        $supplemental[] = $entry;
                    } elseif ($file['file_type'] === 'attachment') {
                        $attachments[] = $entry;
                    } elseif ($file['file_type'] === 'subtitle') {
                        $subtitles[] = $entry;
                    }
                }

                preg_match('/^(\d+)([a-z]*)/', $lesson['prefix'], $m);

                $sectionLessons[] = [
                    'id'           => (int)$lesson['id'],
                    'prefix'       => $lesson['prefix'],
                    'numeric'      => (int)($m[1] ?? 0),
                    'letter'       => $m[2] ?? '',
                    'title'        => $lesson['title'],
                    'config'       => $config,
                    'main_content' => $mainContent,
                    'supplemental' => $supplemental,
                    'attachments'  => $attachments,
                    'subtitles'    => $subtitles,
                ];
            }

            $courseSections[] = [
                'id'          => (int)$section['id'],
                'folder_name' => $section['folder_name'],
                'order'       => (int)$section['sort_order'],
                'title'       => $section['title'],
                'config'      => [],
                'lessons'     => $sectionLessons,
            ];
        }

        return [
            'id'          => (int)$course['id'],
            'title'       => $course['title'],
            'slug'        => $course['slug'],
            'description' => $course['description'],
            'source_dir'  => $course['source_dir'],
            'thumbnail'   => $course['thumbnail'],
            'sections'    => $courseSections,
        ];
    }
}
