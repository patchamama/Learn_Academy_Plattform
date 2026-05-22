<?php

namespace LearnAcademy\Generator;

class AssetManager
{
    private string $outputDir;

    public function __construct(string $outputDir)
    {
        $this->outputDir = rtrim($outputDir, '/\\');
    }

    /**
     * Copy a media file to output/assets/media/ and return the relative URL.
     */
    public function copyMedia(string $sourcePath): string
    {
        $mediaDir = $this->outputDir . '/assets/media';
        $this->ensureDir($mediaDir);

        $filename = basename($sourcePath);
        $dest     = $mediaDir . '/' . $filename;

        if (!file_exists($dest)) {
            copy($sourcePath, $dest);
        }

        return 'assets/media/' . $filename;
    }

    /**
     * Copy all static template assets (CSS, JS) to output/assets/.
     * Source is the templates/static/assets directory.
     */
    public function copyTemplateAssets(string $templateAssetsDir): void
    {
        $destDir = $this->outputDir . '/assets';
        $this->copyDir($templateAssetsDir, $destDir);
    }

    /**
     * Return the absolute path for a file inside the output directory.
     */
    public function outputPath(string $relative): string
    {
        return $this->outputDir . '/' . ltrim($relative, '/\\');
    }

    public function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    private function copyDir(string $source, string $dest): void
    {
        $this->ensureDir($dest);
        $items = scandir($source);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $srcPath  = $source . '/' . $item;
            $destPath = $dest . '/' . $item;
            if (is_dir($srcPath)) {
                $this->copyDir($srcPath, $destPath);
            } else {
                copy($srcPath, $destPath);
            }
        }
    }
}
