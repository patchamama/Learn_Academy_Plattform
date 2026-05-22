<?php

namespace LearnAcademy\Parser;

class FileNaming
{
    private static array $videoExts      = ['mp4', 'webm', 'mov', 'avi', 'mkv'];
    private static array $audioExts      = ['mp3', 'wav', 'ogg', 'm4a'];
    private static array $textExts       = ['md', 'txt'];
    private static array $htmlExts       = ['html', 'htm'];
    private static array $imageExts      = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    private static array $attachmentExts = ['pdf', 'docx', 'doc', 'zip', 'rar', 'xlsx', 'pptx'];
    private static array $subtitleExts   = ['vtt', 'srt'];

    /**
     * Parse a filename into its components.
     * Returns null if the filename does not match the N+[letter].ext pattern.
     *
     * @return array{prefix: string, numeric: int, letter: string, ext: string, type: string}|null
     */
    public static function parse(string $filename): ?array
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Config files have their own special type
        if ($ext === 'txt' && str_ends_with($base, '.conf')) {
            $prefix = substr($base, 0, -5); // strip ".conf"
            $parsed = self::parsePrefix($prefix);
            if ($parsed === null) {
                return null;
            }
            return array_merge($parsed, ['ext' => 'conf.txt', 'type' => 'config']);
        }

        $parsed = self::parsePrefix($base);
        if ($parsed === null) {
            return null;
        }

        $type = self::resolveType($ext);

        return array_merge($parsed, ['ext' => $ext, 'type' => $type]);
    }

    /**
     * Parse N+[letter] prefix. Returns null if it doesn't match.
     *
     * @return array{prefix: string, numeric: int, letter: string}|null
     */
    private static function parsePrefix(string $base): ?array
    {
        if (!preg_match('/^(\d+)([a-z]*)(.*)$/i', $base, $m)) {
            return null;
        }

        // Only accept if the entire basename is digits + optional letters (no other chars after)
        // Allow: 001, 001a, 001ab, 01_title, 001-title — prefix is just the leading digit+letter group
        $numericStr = $m[1];
        $letter     = strtolower($m[2]);
        // Reject if there are remaining non-alpha chars right after the letter group that break the pattern
        // (We accept free-form names like 001_my_title — the prefix is the leading number block)

        return [
            'prefix'  => $numericStr . $letter,
            'numeric' => (int) $numericStr,
            'letter'  => $letter,
        ];
    }

    public static function resolveType(string $ext): string
    {
        $ext = strtolower($ext);
        if (in_array($ext, self::$videoExts, true))      return 'video';
        if (in_array($ext, self::$audioExts, true))      return 'audio';
        if (in_array($ext, self::$imageExts, true))      return 'image';
        if (in_array($ext, self::$subtitleExts, true))   return 'subtitle';
        if (in_array($ext, self::$attachmentExts, true)) return 'attachment';
        if (in_array($ext, self::$textExts, true))       return 'markdown';
        if (in_array($ext, self::$htmlExts, true))       return 'html';
        return 'unknown';
    }

    /**
     * Content priority: video(1) > audio(2) > markdown(3) > html(4)
     * Higher number = lower priority. Returns null for non-main-content types.
     */
    public static function mainContentPriority(string $type): ?int
    {
        return match ($type) {
            'video'    => 1,
            'audio'    => 2,
            'markdown' => 3,
            'html'     => 4,
            default    => null,
        };
    }

    public static function isMainContent(string $type): bool
    {
        return self::mainContentPriority($type) !== null;
    }

    public static function isAttachment(string $type): bool
    {
        return $type === 'attachment';
    }

    public static function isImage(string $type): bool
    {
        return $type === 'image';
    }

    public static function isSubtitle(string $type): bool
    {
        return $type === 'subtitle';
    }

    /**
     * Sort comparator for lesson prefixes: numeric first, then letter.
     * e.g. "1" < "1a" < "1b" < "2" < "10"
     */
    public static function comparePrefix(string $a, string $b): int
    {
        preg_match('/^(\d+)([a-z]*)/', $a, $ma);
        preg_match('/^(\d+)([a-z]*)/', $b, $mb);
        $numCmp = (int)$ma[1] <=> (int)$mb[1];
        if ($numCmp !== 0) return $numCmp;
        return strcmp($ma[2] ?? '', $mb[2] ?? '');
    }
}
