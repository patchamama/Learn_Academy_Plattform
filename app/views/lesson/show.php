<?php
/** @var array      $course */
/** @var array      $lesson */
/** @var array|null $mainContent */
/** @var array      $attachments */
/** @var array      $images */
/** @var array      $subtitles */
/** @var array|null $prevLesson */
/** @var array|null $nextLesson */

$config = json_decode($lesson['config_json'] ?? '{}', true);
$layout = $config['layout'] ?? 'default';
?>
<div class="content-header">
    <h1><?= e($lesson['title']) ?></h1>
</div>

<div class="content-body">

    <?php if ($mainContent && $mainContent['file_type'] === 'video'): ?>
    <!-- Video player -->
    <div class="video-wrapper">
        <video class="lesson-video" controls preload="metadata"
               data-lesson-id="<?= (int)$lesson['id'] ?>">
            <source src="<?= e($mainContent['path']) ?>">
            <?php foreach ($subtitles as $sub): ?>
            <track kind="subtitles" src="<?= e($sub['path']) ?>" label="Subtitles">
            <?php endforeach; ?>
            Your browser does not support the video element.
        </video>
    </div>
    <div class="video-controls-bar">
        <?php if (!empty($subtitles)): ?>
        <button id="subtitle-toggle">
            <i class="fa-solid fa-closed-captioning"></i> <?= e(t('lesson.subtitles')) ?>
        </button>
        <?php endif; ?>
        <label for="video-speed"><?= e(t('lesson.speed')) ?>:</label>
        <select id="video-speed" class="speed-select">
            <option value="0.5">0.5&times;</option>
            <option value="0.75">0.75&times;</option>
            <option value="1" selected>1&times;</option>
            <option value="1.25">1.25&times;</option>
            <option value="1.5">1.5&times;</option>
            <option value="1.75">1.75&times;</option>
            <option value="2">2&times;</option>
        </select>
    </div>

    <?php elseif ($mainContent && $mainContent['file_type'] === 'audio'): ?>
    <!-- Audio player -->
    <div class="audio-wrapper">
        <audio controls class="lesson-audio">
            <source src="<?= e($mainContent['path']) ?>">
        </audio>
    </div>

    <?php elseif ($mainContent && in_array($mainContent['file_type'], ['text', 'html', 'markdown'], true)): ?>
    <!-- Text content -->
    <div class="text-content">
        <?php if ($mainContent['file_type'] === 'html'): ?>
        <?= file_exists($mainContent['path']) ? file_get_contents($mainContent['path']) : '' ?>
        <?php elseif ($mainContent['file_type'] === 'markdown'): ?>
        <pre style="white-space:pre-wrap"><?= e(file_exists($mainContent['path']) ? file_get_contents($mainContent['path']) : '') ?></pre>
        <?php else: ?>
        <pre style="white-space:pre-wrap"><?= e(file_exists($mainContent['path']) ? file_get_contents($mainContent['path']) : '') ?></pre>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Image gallery -->
    <?php if (!empty($images) && ($config['show_image_gallery'] ?? true)): ?>
    <div class="image-gallery">
        <?php foreach ($images as $img): ?>
        <img src="<?= e($img['path']) ?>" alt="<?= e($img['filename']) ?>" loading="lazy" />
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Attachments panel -->
    <?php if (!empty($attachments) && ($config['show_attachments'] ?? true)):
        $attIcon = function(string $filename): string {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            return match($ext) {
                'pdf'         => 'fa-file-pdf',
                'docx', 'doc' => 'fa-file-word',
                'zip', 'rar'  => 'fa-file-zipper',
                'xlsx'        => 'fa-file-excel',
                'pptx'        => 'fa-file-powerpoint',
                default       => 'fa-file',
            };
        };
    ?>
    <div class="attachments-panel">
        <h4><i class="fa-solid fa-paperclip"></i> <?= e(t('course.sources')) ?></h4>
        <ul class="attachment-list">
            <?php foreach ($attachments as $att): ?>
            <li>
                <a href="<?= e($att['path']) ?>" download>
                    <i class="fa-solid <?= $attIcon($att['filename']) ?>"></i>
                    <?= e($att['filename']) ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

</div><!-- .content-body -->

<!-- Bottom bar -->
<div class="bottom-bar">
    <?php if ($prevLesson): ?>
    <a href="/courses/<?= e($course['slug']) ?>/lesson/<?= (int)$prevLesson['id'] ?>" class="btn-nav">
        <i class="fa-solid fa-arrow-left"></i> <?= e(t('lesson.prev')) ?>
    </a>
    <?php else: ?>
    <span></span>
    <?php endif; ?>

    <button id="btn-complete"
            class="btn-complete"
            data-lesson-id="<?= (int)$lesson['id'] ?>"
            data-next-url="<?= $nextLesson ? e('/courses/' . $course['slug'] . '/lesson/' . $nextLesson['id']) : '' ?>">
        <?= e(t('lesson.complete')) ?>
    </button>

    <?php if ($nextLesson): ?>
    <a href="/courses/<?= e($course['slug']) ?>/lesson/<?= (int)$nextLesson['id'] ?>" class="btn-nav">
        <?= e(t('lesson.next')) ?> <i class="fa-solid fa-arrow-right"></i>
    </a>
    <?php else: ?>
    <span></span>
    <?php endif; ?>
</div>

<!-- Comment section -->
<section class="comments-section" style="margin:2rem 0;max-width:720px">
    <h3><?= e(t('comment.title')) ?></h3>

    <!-- Post comment form -->
    <form id="comment-form" style="margin-bottom:1.5rem">
        <textarea id="comment-body" rows="3" placeholder="<?= e(t('comment.placeholder')) ?>"
                  style="width:100%;padding:0.6rem;border:1px solid #ccc;border-radius:6px;resize:vertical;font-family:inherit;font-size:0.9rem;box-sizing:border-box"></textarea>
        <button type="submit"
                style="margin-top:0.5rem;padding:0.5rem 1.25rem;background:var(--color-accent);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600">
            <?= e(t('comment.submit')) ?>
        </button>
    </form>

    <div id="comments-list">
        <p style="color:var(--color-text-muted);font-size:0.875rem"><?= e(t('general.loading')) ?></p>
    </div>
</section>

<script>
(function () {
    const lessonId = <?= (int)$lesson['id'] ?>;

    // --- Progress: mark complete button ---
    const btnComplete = document.getElementById('btn-complete');
    if (btnComplete) {
        // Load current progress state
        fetch('/api/progress/<?= e($course['slug']) ?>', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (data[lessonId] && data[lessonId].completed) {
                    btnComplete.textContent = '✓ Completed';
                    btnComplete.style.background = '#4caf50';
                }
            })
            .catch(() => {});

        btnComplete.addEventListener('click', () => {
            const nextUrl = btnComplete.dataset.nextUrl;
            fetch('/api/progress', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lessonId: lessonId, completed: true })
            })
            .then(r => r.json())
            .then(() => {
                btnComplete.textContent = '✓ Completed';
                btnComplete.style.background = '#4caf50';
                if (nextUrl) {
                    setTimeout(() => { window.location.href = nextUrl; }, 600);
                }
            })
            .catch(() => {});
        });
    }

    // --- Comments ---
    function renderComments(comments) {
        const list = document.getElementById('comments-list');
        if (!list) return;
        if (!comments.length) {
            list.innerHTML = '<p style="color:var(--color-text-muted);font-size:0.875rem">No comments yet.</p>';
            return;
        }

        // Separate top-level and replies
        const topLevel = comments.filter(c => !c.parentId);
        const replies  = comments.filter(c => c.parentId);

        list.innerHTML = topLevel.map(c => {
            const childHtml = replies
                .filter(r => r.parentId === c.id)
                .map(r => commentHtml(r, true))
                .join('');
            return commentHtml(c, false) + (childHtml ? '<div style="margin-left:2rem">' + childHtml + '</div>' : '');
        }).join('');
    }

    function commentHtml(c, isReply) {
        const pendingBadge = c.pending
            ? '<span style="font-size:0.72rem;background:#fff3cd;color:#856404;padding:1px 6px;border-radius:4px;margin-left:6px"><?= e(t('comment.pending')) ?></span>'
            : '';
        const date = new Date(c.createdAt * 1000).toLocaleDateString();
        return `
        <div style="border-bottom:1px solid rgba(0,0,0,0.07);padding:0.75rem 0${isReply ? ';opacity:0.85' : ''}">
            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.4rem">
                <strong style="font-size:0.875rem">${c.userName}</strong>
                <span style="font-size:0.75rem;color:var(--color-text-muted)">${date}</span>
                ${pendingBadge}
            </div>
            <p style="font-size:0.9rem;margin:0;line-height:1.5">${escHtml(c.body)}</p>
        </div>`;
    }

    function escHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // Load comments
    fetch('/api/comments/' + lessonId, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(renderComments)
        .catch(() => {
            const list = document.getElementById('comments-list');
            if (list) list.innerHTML = '<p style="color:var(--color-text-muted)"><?= e(t('general.error')) ?></p>';
        });

    // Post comment
    const form = document.getElementById('comment-form');
    if (form) {
        form.addEventListener('submit', e => {
            e.preventDefault();
            const body = document.getElementById('comment-body').value.trim();
            if (!body) return;

            fetch('/api/comments', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lessonId: lessonId, body: body })
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    document.getElementById('comment-body').value = '';
                    // Reload comments to include the new pending one
                    fetch('/api/comments/' + lessonId, { credentials: 'same-origin' })
                        .then(r => r.json())
                        .then(renderComments)
                        .catch(() => {});
                }
            })
            .catch(() => {});
        });
    }

    // Video speed control
    const speedSelect = document.getElementById('video-speed');
    const video = document.querySelector('video.lesson-video');
    if (speedSelect && video) {
        speedSelect.addEventListener('change', () => {
            video.playbackRate = parseFloat(speedSelect.value);
        });
    }

    // Subtitle toggle
    const subToggle = document.getElementById('subtitle-toggle');
    if (subToggle && video) {
        subToggle.addEventListener('click', () => {
            const tracks = video.textTracks;
            for (let i = 0; i < tracks.length; i++) {
                tracks[i].mode = tracks[i].mode === 'showing' ? 'hidden' : 'showing';
            }
        });
    }
})();
</script>
