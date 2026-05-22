<?php

namespace LearnAcademy\Parser;

/**
 * Transforms raw scanner output into a clean Course data model.
 */
class CourseBuilder
{
    private string $rootDir;
    private string $courseTitle;

    public function __construct(string $rootDir, string $courseTitle = '')
    {
        $this->rootDir     = rtrim($rootDir, '/\\');
        $this->courseTitle = $courseTitle;
    }

    /**
     * Build and return the full course model.
     *
     * @return array{title: string, slug: string, source_dir: string, sections: array}
     */
    public function build(): array
    {
        $raw = Scanner::scan($this->rootDir);

        $sections = [];
        foreach ($raw['sections'] as $rawSection) {
            $sections[] = $this->buildSection($rawSection);
        }

        $title = $this->courseTitle !== ''
            ? $this->courseTitle
            : basename($this->rootDir);

        return [
            'title'      => $title,
            'slug'       => $this->slugify($title),
            'source_dir' => $this->rootDir,
            'sections'   => $sections,
        ];
    }

    private function buildSection(array $raw): array
    {
        $folderName = $raw['folder'];
        $sectionConfig = $this->readSectionConfig($raw['path']);

        // Derive section title: conf.txt > strip numeric prefix from folder name
        if (!empty($sectionConfig['title'])) {
            $title = $sectionConfig['title'];
        } else {
            $title = ConfigParser::titleFromFilename($folderName);
        }

        $lessons = [];
        foreach ($raw['lessons'] as $rawLesson) {
            $lessons[] = $this->buildLesson($rawLesson);
        }

        return [
            'folder_name' => $folderName,
            'order'       => $raw['order'],
            'title'       => $title,
            'config'      => $sectionConfig,
            'lessons'     => $lessons,
        ];
    }

    private function buildLesson(array $raw): array
    {
        $configPath = $raw['config'];
        $config     = $configPath !== null
            ? ConfigParser::parse($configPath)
            : ConfigParser::defaults();

        $files = $raw['files'];

        // Separate by role
        $mainCandidates  = [];
        $images          = [];
        $attachments     = [];
        $subtitles       = [];
        $supplemental    = [];

        foreach ($files as $file) {
            $type = $file['type'];
            if (FileNaming::isMainContent($type)) {
                $mainCandidates[] = $file;
            } elseif (FileNaming::isImage($type)) {
                $images[] = $file;
            } elseif (FileNaming::isAttachment($type)) {
                $attachments[] = $file;
            } elseif (FileNaming::isSubtitle($type)) {
                $subtitles[] = $file;
            } else {
                $supplemental[] = $file;
            }
        }

        // Select main content: lowest priority number wins
        usort($mainCandidates, fn($a, $b) =>
            FileNaming::mainContentPriority($a['type'])
            <=> FileNaming::mainContentPriority($b['type'])
        );
        $mainContent = $mainCandidates[0] ?? null;

        // Secondary text content (everything except the chosen main)
        $secondaryText = array_slice($mainCandidates, 1);

        // Determine lesson title
        if (!empty($config['title'])) {
            $title = $config['title'];
        } elseif ($mainContent !== null) {
            $title = ConfigParser::titleFromFilename($mainContent['filename']);
        } elseif (!empty($files)) {
            $title = ConfigParser::titleFromFilename($files[0]['filename']);
        } else {
            $title = 'Lesson ' . $raw['prefix'];
        }

        // Auto-create .conf.txt if absent and we derived a title
        if ($configPath === null && $mainContent !== null) {
            $sectionDir  = dirname($mainContent['path']);
            $confFile    = $sectionDir . DIRECTORY_SEPARATOR . $raw['prefix'] . '.conf.txt';
            ConfigParser::write($confFile, array_merge($config, ['title' => $title]));
        }

        return [
            'prefix'       => $raw['prefix'],
            'numeric'      => $raw['numeric'],
            'letter'       => $raw['letter'],
            'title'        => $title,
            'config'       => $config,
            'main_content' => $mainContent,
            'supplemental' => array_merge($secondaryText, $images),
            'attachments'  => $attachments,
            'subtitles'    => $subtitles,
        ];
    }

    /**
     * Read section-level config from .conf.txt in section root (if exists).
     */
    private function readSectionConfig(string $sectionPath): array
    {
        $confPath = $sectionPath . DIRECTORY_SEPARATOR . '.conf.txt';
        return ConfigParser::parse($confPath);
    }

    private function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
