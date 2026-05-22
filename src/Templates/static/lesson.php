<?php
/**
 * Static template: Lesson player page.
 * Variables injected by StaticGenerator per lesson:
 *   $course
 *   $lesson        — current lesson
 *   $lessonId      — unique lesson ID (for progress)
 *   $prevUrl       — URL to previous lesson (or null)
 *   $nextUrl       — URL to next lesson (or null)
 *   $sectionIndex  — current section index
 *   $allSections   — all sections with their lessons (for sidebar)
 *   $renderedContent — HTML string (pre-rendered by MarkdownRenderer)
 */
?>
<!DOCTYPE html>
<html lang="en" data-course-slug="<?= e($course['slug']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($lesson['title']) ?> — <?= e($course['title']) ?></title>
    <link rel="stylesheet" href="assets/lessons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- Topbar -->
<header class="topbar">
    <a href="index.html" class="topbar-back">
        <i class="fa-solid fa-arrow-left"></i>
        Back to dashboard
    </a>
    <div class="topbar-actions">
        <button id="theme-toggle" title="Toggle theme">
            <i class="fa-solid fa-circle-half-stroke"></i>
        </button>
        <button data-switch-lang="en">EN</button>
        <button data-switch-lang="es">ES</button>
    </div>
</header>

<div class="layout">
    <!-- Lesson Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-course-title"><?= e($course['title']) ?></div>
            <div class="sidebar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="lesson-search" placeholder="Search lessons...">
            </div>
        </div>
        <div class="sidebar-lessons">
            <?php foreach (($allSections ?? []) as $si => $section):
                $sectionLessonIds = [];
                foreach (($section['lessons'] ?? []) as $l) {
                    $sectionLessonIds[] = $course['slug'] . '-' . $si . '-' . $l['prefix'];
                }
                $sectionIdsJson = htmlspecialchars(json_encode($sectionLessonIds), ENT_QUOTES);
                $completed = 0; // runtime.js will fill this in
            ?>
            <div class="sidebar-section">
                <div class="sidebar-section-header">
                    <?= e($section['title']) ?>
                    <span class="section-progress"
                          data-section-id="sidebar-section-<?= $si ?>">
                        0 / <?= count($section['lessons'] ?? []) ?>
                    </span>
                </div>
                <div class="sidebar-section-lessons">
                    <?php foreach (($section['lessons'] ?? []) as $li => $l):
                        $lId     = $course['slug'] . '-' . $si . '-' . $l['prefix'];
                        $lFile   = 'lesson-' . $si . '-' . $l['prefix'] . '.html';
                        $isActive = $lId === ($lessonId ?? '');
                        $lType   = $l['main_content']['type'] ?? 'text';
                        $lIcon   = match($lType) {
                            'video' => 'fa-video',
                            'audio' => 'fa-music',
                            default => 'fa-file-lines',
                        };
                    ?>
                    <a href="<?= e($lFile) ?>"
                       class="sidebar-lesson-item<?= $isActive ? ' active' : '' ?>"
                       data-lesson-id="<?= e($lId) ?>">
                        <i class="fa-solid <?= $lIcon ?>"></i>
                        <?= e($l['title']) ?>
                        <i class="fa-solid fa-check check" style="display:none;margin-left:auto;color:#4caf50"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- Progress bars per section for sidebar (driven by runtime.js) -->
        <?php foreach (($allSections ?? []) as $si => $section):
            $ids = [];
            foreach (($section['lessons'] ?? []) as $l) {
                $ids[] = $course['slug'] . '-' . $si . '-' . $l['prefix'];
            }
            $idsJson = htmlspecialchars(json_encode($ids), ENT_QUOTES);
        ?>
        <div style="display:none">
            <div class="progress-bar-fill"
                 data-section-id="sidebar-section-<?= $si ?>"
                 data-lesson-ids="<?= $idsJson ?>">
            </div>
        </div>
        <?php endforeach; ?>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-header">
            <h1><?= e($lesson['title']) ?></h1>
        </div>

        <div class="content-body">
            <?php
            $mainContent = $lesson['main_content'] ?? null;
            $config      = $lesson['config'] ?? [];
            $layout      = $config['layout'] ?? 'default';
            ?>

            <?php if ($mainContent && $mainContent['type'] === 'video'): ?>
            <!-- Video player -->
            <div class="video-wrapper">
                <video class="lesson-video"
                       controls
                       preload="metadata"
                       data-lesson-id="<?= e($lessonId) ?>"
                       poster="">
                    <source src="assets/media/<?= e(basename($mainContent['path'])) ?>">
                    <?php foreach (($lesson['subtitles'] ?? []) as $sub): ?>
                    <track kind="subtitles"
                           src="assets/media/<?= e(basename($sub['path'])) ?>"
                           label="Subtitles">
                    <?php endforeach; ?>
                    Your browser does not support the video element.
                </video>
            </div>
            <div class="video-controls-bar">
                <?php if (!empty($lesson['subtitles'])): ?>
                <button id="subtitle-toggle">
                    <i class="fa-solid fa-closed-captioning"></i> Subtitles
                </button>
                <?php endif; ?>
                <label for="video-speed">Speed:</label>
                <select id="video-speed" class="speed-select">
                    <option value="0.5">0.5×</option>
                    <option value="0.75">0.75×</option>
                    <option value="1" selected>1×</option>
                    <option value="1.25">1.25×</option>
                    <option value="1.5">1.5×</option>
                    <option value="1.75">1.75×</option>
                    <option value="2">2×</option>
                </select>
            </div>

            <?php elseif ($mainContent && $mainContent['type'] === 'audio'): ?>
            <!-- Audio player -->
            <div class="audio-wrapper">
                <audio controls class="lesson-audio">
                    <source src="assets/media/<?= e(basename($mainContent['path'])) ?>">
                </audio>
            </div>
            <?php endif; ?>

            <?php
            // Text content (rendered markdown or secondary text)
            if ($layout !== 'video-first' || empty($mainContent) || $mainContent['type'] !== 'video'):
                if (!empty($renderedContent)):
            ?>
            <div class="text-content">
                <?= $renderedContent ?>
            </div>
            <?php
                endif;
            endif;

            // If layout is text-first and there's a video, show it after text
            if ($layout === 'text-first' && $mainContent && $mainContent['type'] === 'video'):
            ?>
            <div class="video-wrapper" style="margin-top:1.5rem">
                <video controls preload="metadata">
                    <source src="assets/media/<?= e(basename($mainContent['path'])) ?>">
                </video>
            </div>
            <?php endif; ?>

            <!-- Image gallery (supplemental images) -->
            <?php
            $images = array_filter($lesson['supplemental'] ?? [], fn($f) => $f['type'] === 'image');
            if (!empty($images) && ($config['show_image_gallery'] ?? true)):
            ?>
            <div class="image-gallery">
                <?php foreach ($images as $img): ?>
                <img src="assets/media/<?= e(basename($img['path'])) ?>"
                     alt="<?= e(basename($img['path'])) ?>"
                     loading="lazy">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Attachments panel -->
            <?php
            if (!empty($lesson['attachments']) && ($config['show_attachments'] ?? true)):
                $attIcon = fn($ext) => match(strtolower($ext)) {
                    'pdf'  => 'fa-file-pdf',
                    'docx', 'doc' => 'fa-file-word',
                    'zip', 'rar'  => 'fa-file-zipper',
                    'xlsx'        => 'fa-file-excel',
                    'pptx'        => 'fa-file-powerpoint',
                    default       => 'fa-file',
                };
            ?>
            <div class="attachments-panel">
                <h4><i class="fa-solid fa-paperclip"></i> Sources &amp; Attachments</h4>
                <ul class="attachment-list">
                    <?php foreach ($lesson['attachments'] as $att): ?>
                    <li>
                        <a href="assets/media/<?= e(basename($att['path'])) ?>" download>
                            <i class="fa-solid <?= $attIcon($att['ext'] ?? '') ?>"></i>
                            <?= e(basename($att['path'])) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div><!-- .content-body -->

        <!-- Bottom bar -->
        <div class="bottom-bar">
            <?php if ($prevUrl): ?>
            <a href="<?= e($prevUrl) ?>" class="btn-nav">
                <i class="fa-solid fa-arrow-left"></i> Previous
            </a>
            <?php else: ?>
            <span></span>
            <?php endif; ?>

            <button id="btn-complete"
                    class="btn-complete"
                    data-lesson-id="<?= e($lessonId) ?>"
                    data-next-url="<?= e($nextUrl ?? '') ?>"
                    data-label-complete="Complete &amp; continue →"
                    data-label-done="✓ Completed">
                Complete &amp; continue →
            </button>

            <?php if ($nextUrl): ?>
            <a href="<?= e($nextUrl) ?>" class="btn-nav">
                Next <i class="fa-solid fa-arrow-right"></i>
            </a>
            <?php else: ?>
            <span></span>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="assets/runtime.js"></script>
</body>
</html>
