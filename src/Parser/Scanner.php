<?php

namespace LearnAcademy\Parser;

class Scanner
{
    /**
     * Scan a course root directory and return a structured array.
     *
     * Returns:
     * [
     *   'sections' => [
     *     [
     *       'folder'   => 'string',
     *       'order'    => int,
     *       'path'     => 'absolute/path',
     *       'lessons'  => [
     *         [
     *           'prefix'  => 'string',
     *           'numeric' => int,
     *           'letter'  => 'string',
     *           'files'   => [ ['filename', 'type', 'ext', 'path'], ... ],
     *           'config'  => array|null,
     *         ],
     *         ...
     *       ],
     *     ],
     *     ...
     *   ],
     * ]
     */
    public static function scan(string $rootDir): array
    {
        $rootDir = rtrim($rootDir, '/\\');

        if (!is_dir($rootDir)) {
            throw new \InvalidArgumentException("Directory not found: $rootDir");
        }

        $entries = scandir($rootDir);
        $sectionDirs = [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $fullPath = $rootDir . DIRECTORY_SEPARATOR . $entry;
            if (!is_dir($fullPath)) continue;

            $order = self::parseSectionOrder($entry);
            if ($order === null) continue;

            $sectionDirs[] = [
                'folder' => $entry,
                'order'  => $order,
                'path'   => $fullPath,
            ];
        }

        usort($sectionDirs, fn($a, $b) => $a['order'] <=> $b['order']);

        $sections = [];
        foreach ($sectionDirs as $sec) {
            $sec['lessons'] = self::scanSection($sec['path']);
            $sections[] = $sec;
        }

        return ['sections' => $sections];
    }

    /**
     * Extract numeric prefix from a section folder name.
     * Returns null if the folder doesn't start with digits.
     */
    private static function parseSectionOrder(string $folderName): ?int
    {
        if (preg_match('/^(\d+)/', $folderName, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    /**
     * Scan a section directory and group files into lessons.
     */
    private static function scanSection(string $sectionPath): array
    {
        $entries = scandir($sectionPath);
        $groups  = [];

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $fullPath = $sectionPath . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($fullPath)) continue;

            $parsed = FileNaming::parse($entry);
            if ($parsed === null) continue;

            $prefix = $parsed['prefix'];

            if (!isset($groups[$prefix])) {
                $groups[$prefix] = [
                    'prefix'  => $prefix,
                    'numeric' => $parsed['numeric'],
                    'letter'  => $parsed['letter'],
                    'files'   => [],
                    'config'  => null,
                ];
            }

            if ($parsed['type'] === 'config') {
                $groups[$prefix]['config'] = $fullPath;
            } else {
                $groups[$prefix]['files'][] = [
                    'filename' => $entry,
                    'type'     => $parsed['type'],
                    'ext'      => $parsed['ext'],
                    'path'     => $fullPath,
                ];
            }
        }

        // Sort lessons by prefix (numeric then letter)
        $lessons = array_values($groups);
        usort($lessons, fn($a, $b) =>
            FileNaming::comparePrefix($a['prefix'], $b['prefix'])
        );

        return $lessons;
    }
}
