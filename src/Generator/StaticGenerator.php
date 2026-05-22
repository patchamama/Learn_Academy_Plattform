<?php

namespace LearnAcademy\Generator;

/**
 * Generates a self-contained static HTML course from a course data model.
 * Output: outputDir/index.html + course.html + lesson-*.html + assets/
 */
class StaticGenerator
{
    private string $outputDir;
    private string $templatesDir;
    private AssetManager $assets;

    public function __construct(string $outputDir)
    {
        $this->outputDir    = rtrim($outputDir, '/\\');
        $this->templatesDir = dirname(__DIR__) . '/Templates/static';
        $this->assets       = new AssetManager($this->outputDir);
    }

    /**
     * Generate the full static course.
     *
     * @param array $course  Course data model from CourseBuilder::build()
     */
    public function generate(array $course): void
    {
        $this->assets->ensureDir($this->outputDir);

        // Copy CSS/JS template assets
        $this->assets->copyTemplateAssets($this->templatesDir . '/assets');

        // Copy all media files from lessons
        $this->copyAllMedia($course);

        // Count totals
        $totalSections = count($course['sections'] ?? []);
        $totalLessons  = 0;
        foreach ($course['sections'] as $section) {
            $totalLessons += count($section['lessons'] ?? []);
        }

        // Build flat lesson list for navigation
        $lessonNav = $this->buildLessonNav($course);

        // Render dashboard (index.html)
        $allLessonIds = array_column($lessonNav, 'id');
        $this->renderPage('dashboard.php', 'index.html', [
            'course'        => $course,
            'totalLessons'  => $totalLessons,
            'totalSections' => $totalSections,
            'allLessonIds'  => json_encode($allLessonIds),
        ]);

        // Render course detail page
        $this->renderPage('course.php', 'course.html', [
            'course'        => $course,
            'totalLessons'  => $totalLessons,
            'totalSections' => $totalSections,
        ]);

        // Render settings page
        $this->renderPage('settings.php', 'settings.html', [
            'course' => $course,
        ]);

        // Render each lesson page
        foreach ($lessonNav as $navIndex => $navItem) {
            $this->renderLessonPage($course, $navItem, $lessonNav, $navIndex);
        }

        echo "✓ Static course generated in: {$this->outputDir}\n";
        echo "  Open index.html to start.\n";
    }

    /**
     * Build a flat navigation list of all lessons in order.
     * Each entry: ['id', 'file', 'sectionIndex', 'lesson']
     */
    private function buildLessonNav(array $course): array
    {
        $nav = [];
        foreach ($course['sections'] as $si => $section) {
            foreach ($section['lessons'] as $lesson) {
                $id   = $course['slug'] . '-' . $si . '-' . $lesson['prefix'];
                $file = 'lesson-' . $si . '-' . $lesson['prefix'] . '.html';
                $nav[] = [
                    'id'           => $id,
                    'file'         => $file,
                    'sectionIndex' => $si,
                    'lesson'       => $lesson,
                ];
            }
        }
        return $nav;
    }

    private function renderLessonPage(array $course, array $navItem, array $allNav, int $navIndex): void
    {
        $lesson       = $navItem['lesson'];
        $lessonId     = $navItem['id'];
        $si           = $navItem['sectionIndex'];

        $prevUrl = $navIndex > 0 ? $allNav[$navIndex - 1]['file'] : null;
        $nextUrl = $navIndex < count($allNav) - 1 ? $allNav[$navIndex + 1]['file'] : null;

        // Render markdown/text content
        $renderedContent = $this->renderLessonContent($lesson);

        $this->renderPage('lesson.php', $navItem['file'], [
            'course'          => $course,
            'lesson'          => $lesson,
            'lessonId'        => $lessonId,
            'sectionIndex'    => $si,
            'prevUrl'         => $prevUrl,
            'nextUrl'         => $nextUrl,
            'allSections'     => $course['sections'],
            'renderedContent' => $renderedContent,
        ]);
    }

    private function renderLessonContent(array $lesson): string
    {
        $config = $lesson['config'] ?? [];
        $layout = $config['layout'] ?? 'default';

        // For audio-only layout with no text, show nothing
        if ($layout === 'audio-only') {
            return '';
        }

        // Collect text content from supplemental files
        $textContent = '';

        // Check main content for text/markdown
        $main = $lesson['main_content'] ?? null;
        if ($main && in_array($main['type'], ['markdown', 'text', 'html'], true)) {
            $textContent = $this->readContentFile($main, $lesson['supplemental'] ?? []);
        }

        // Also gather secondary text from supplemental
        foreach ($lesson['supplemental'] ?? [] as $file) {
            if (in_array($file['type'], ['markdown', 'text', 'html'], true)) {
                $textContent .= "\n\n" . $this->readContentFile($file, $lesson['supplemental'] ?? []);
            }
        }

        return trim($textContent);
    }

    private function readContentFile(array $file, array $images): string
    {
        if (!is_file($file['path'])) {
            return '';
        }

        $raw = file_get_contents($file['path']);

        if ($file['type'] === 'html') {
            return $raw;
        }

        // Markdown / plain text → render
        $renderer = new MarkdownRenderer($images);
        return $renderer->render($raw);
    }

    private function copyAllMedia(array $course): void
    {
        foreach ($course['sections'] as $section) {
            foreach ($section['lessons'] as $lesson) {
                $allFiles = array_merge(
                    $lesson['main_content'] ? [$lesson['main_content']] : [],
                    $lesson['supplemental'] ?? [],
                    $lesson['attachments'] ?? [],
                    $lesson['subtitles'] ?? []
                );

                foreach ($allFiles as $file) {
                    if (isset($file['path']) && is_file($file['path'])) {
                        $this->assets->copyMedia($file['path']);
                    }
                }
            }
        }
    }

    /**
     * Render a PHP template with variables and write to output file.
     */
    private function renderPage(string $template, string $outputFile, array $vars = []): void
    {
        $templatePath = $this->templatesDir . '/' . $template;

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template not found: $templatePath");
        }

        // Make variables available in template scope
        extract($vars);



        ob_start();
        include $templatePath;
        $html = ob_get_clean();

        $dest = $this->outputDir . '/' . $outputFile;
        file_put_contents($dest, $html);
    }
}
