<?php

namespace LearnAcademy\Generator;

/**
 * Minimal Markdown-to-HTML renderer with {{img:filename}} pattern support.
 * Handles headings, bold, italic, code, blockquotes, lists, links, and image patterns.
 */
class MarkdownRenderer
{
    private array $lessonImages;

    /**
     * @param array $lessonImages Array of image ContentFile arrays available in the lesson.
     */
    public function __construct(array $lessonImages = [])
    {
        $this->lessonImages = $lessonImages;
    }

    public function render(string $markdown): string
    {
        $html = $markdown;

        // Normalize line endings
        $html = str_replace(["\r\n", "\r"], "\n", $html);

        // {{img:filename}} pattern — replace with <img> tag
        $html = $this->renderImagePatterns($html);

        // Escape HTML (but preserve our image tags)
        // We'll work on the text between tags, so we do a token-based approach.
        // For simplicity: escape before any rendering, then unescape block patterns.
        // Actually we need to escape user content but allow markdown to produce HTML.
        // Strategy: process markdown → HTML, then sanitize. Here we trust the content files.

        // Headings
        $html = preg_replace('/^#{6}\s+(.+)$/m', '<h6>$1</h6>', $html);
        $html = preg_replace('/^#{5}\s+(.+)$/m', '<h5>$1</h5>', $html);
        $html = preg_replace('/^#{4}\s+(.+)$/m', '<h4>$1</h4>', $html);
        $html = preg_replace('/^#{3}\s+(.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^#{2}\s+(.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^#{1}\s+(.+)$/m', '<h1>$1</h1>', $html);

        // Code blocks (fenced)
        $html = preg_replace('/```[\w]*\n(.*?)```/s', '<pre><code>$1</code></pre>', $html);

        // Inline code
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

        // Blockquotes
        $html = preg_replace('/^>\s+(.+)$/m', '<blockquote>$1</blockquote>', $html);

        // Bold and italic
        $html = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $html);
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);

        // Horizontal rule
        $html = preg_replace('/^---+$/m', '<hr>', $html);

        // Unordered lists
        $html = preg_replace_callback('/(?:^[-*+]\s.+\n?)+/m', function ($m) {
            $items = preg_replace('/^[-*+]\s(.+)/m', '<li>$1</li>', $m[0]);
            return '<ul>' . $items . '</ul>';
        }, $html);

        // Ordered lists
        $html = preg_replace_callback('/(?:^\d+\.\s.+\n?)+/m', function ($m) {
            $items = preg_replace('/^\d+\.\s(.+)/m', '<li>$1</li>', $m[0]);
            return '<ol>' . $items . '</ol>';
        }, $html);

        // Links: [text](url)
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $html);

        // Standard markdown images: ![alt](url)
        $html = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1" class="content-image">', $html);

        // Paragraphs: wrap remaining text blocks
        $html = $this->wrapParagraphs($html);

        return $html;
    }

    private function renderImagePatterns(string $content): string
    {
        return preg_replace_callback('/\{\{img:([^}]+)\}\}/', function ($m) {
            $filename = trim($m[1]);
            // Find matching image in lesson files
            foreach ($this->lessonImages as $img) {
                if ($img['filename'] === $filename) {
                    // Path will be resolved by the generator (relative to output)
                    return '<img src="assets/media/' . htmlspecialchars($filename, ENT_QUOTES) . '" alt="' . htmlspecialchars($filename, ENT_QUOTES) . '" class="content-image">';
                }
            }
            // Image not found — render placeholder
            return '<span class="img-missing">[Image not found: ' . htmlspecialchars($filename, ENT_QUOTES) . ']</span>';
        }, $content);
    }

    private function wrapParagraphs(string $html): string
    {
        $blockTags = 'h[1-6]|ul|ol|li|blockquote|pre|hr|div|img';
        $lines     = explode("\n\n", $html);
        $result    = [];

        foreach ($lines as $block) {
            $block = trim($block);
            if ($block === '') continue;
            // Don't wrap blocks that are already block-level HTML
            if (preg_match('/^<(' . $blockTags . ')[\s>]/', $block)) {
                $result[] = $block;
            } else {
                $result[] = '<p>' . nl2br($block) . '</p>';
            }
        }

        return implode("\n", $result);
    }
}
