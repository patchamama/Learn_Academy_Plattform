#!/usr/bin/env php
<?php

/**
 * Learn Academy Platform — CLI Course Generator
 *
 * Usage:
 *   php generate.php --source /path/to/content --output /path/to/dist --mode static
 *   php generate.php --source /path/to/content --output /path/to/dist --mode dynamic --title "My Course"
 */

// Autoload
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    // Manual autoload fallback (before composer install)
    spl_autoload_register(function (string $class): void {
        $prefix = 'LearnAcademy\\';
        if (!str_starts_with($class, $prefix)) return;

        $relative = substr($class, strlen($prefix));
        $file     = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) require $file;
    });
    require_once __DIR__ . '/src/helpers.php';
}

use LearnAcademy\Parser\CourseBuilder;
use LearnAcademy\Generator\StaticGenerator;

// ── Parse CLI arguments ─────────────────────────────────────────────────

$opts = getopt('', ['source:', 'output:', 'mode:', 'title:', 'help']);

if (isset($opts['help']) || empty($opts['source']) || empty($opts['output'])) {
    echo <<<HELP
Learn Academy Platform — Course Generator

Usage:
  php generate.php --source <dir> --output <dir> [--mode static|dynamic] [--title "Course Title"]

Options:
  --source   Path to the course content directory (required)
  --output   Path to write the generated course (required)
  --mode     Output mode: static (default) or dynamic
  --title    Course title override (default: directory name)
  --help     Show this help

Example:
  php generate.php --source ./my-course-content --output ./dist/my-course --mode static

HELP;
    exit(0);
}

$sourceDir = rtrim($opts['source'], '/\\');
$outputDir = rtrim($opts['output'], '/\\');
$mode      = strtolower($opts['mode'] ?? 'static');
$title     = $opts['title'] ?? '';

// ── Validate ────────────────────────────────────────────────────────────

if (!is_dir($sourceDir)) {
    fwrite(STDERR, "Error: source directory not found: $sourceDir\n");
    exit(1);
}

if (!in_array($mode, ['static', 'dynamic'], true)) {
    fwrite(STDERR, "Error: --mode must be 'static' or 'dynamic'\n");
    exit(1);
}

// ── Build course model ──────────────────────────────────────────────────

echo "Scanning: $sourceDir\n";

try {
    $builder = new CourseBuilder($sourceDir, $title);
    $course  = $builder->build();
} catch (\Exception $e) {
    fwrite(STDERR, "Error building course: " . $e->getMessage() . "\n");
    exit(1);
}

$sectionCount = count($course['sections']);
$lessonCount  = array_sum(array_map(fn($s) => count($s['lessons']), $course['sections']));
echo "Found: $sectionCount sections, $lessonCount lessons\n";
echo "Title: {$course['title']}\n";

// ── Generate ────────────────────────────────────────────────────────────

if ($mode === 'static') {
    echo "Generating static course...\n";
    try {
        $generator = new StaticGenerator($outputDir);
        $generator->generate($course);
    } catch (\Exception $e) {
        fwrite(STDERR, "Generation error: " . $e->getMessage() . "\n");
        exit(1);
    }
} else {
    echo "Dynamic mode not yet implemented. Use --mode static for now.\n";
    exit(1);
}
