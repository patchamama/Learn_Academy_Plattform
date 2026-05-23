<?php
/** @var array      $course */
/** @var array      $lesson */
/** @var array|null $mainContent */
/** @var string     $renderedContent */
/** @var array      $attachments */
/** @var array      $images */
/** @var array      $subtitles */
/** @var array|null $prevLesson */
/** @var array|null $nextLesson */
/** @var callable   $mediaUrl */

$lessonConfig = json_decode($lesson['config_json'] ?? '{}', true);
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
<div class="content-header">
    <h1><?= e($lesson['title']) ?></h1>
</div>

<div class="content-body">

    <?php if ($mainContent && $mainContent['file_type'] === 'video'): ?>
    <!-- Video player -->
    <div class="video-wrapper">
        <video class="lesson-video" controls preload="metadata"
               data-lesson-id="<?= (int)$lesson['id'] ?>">
            <source src="<?= e($mediaUrl($mainContent)) ?>">
            <?php foreach ($subtitles as $sub): ?>
            <track kind="subtitles" src="<?= e($mediaUrl($sub)) ?>" label="Subtitles">
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
            <source src="<?= e($mediaUrl($mainContent)) ?>">
        </audio>
    </div>

    <?php elseif ($mainContent && in_array($mainContent['file_type'], ['text', 'html', 'markdown'], true)): ?>
    <!-- Text / HTML / Markdown content -->
    <div class="text-content prose">
        <?= $renderedContent ?>
    </div>
    <?php endif; ?>

    <!-- Image gallery -->
    <?php if (!empty($images) && ($lessonConfig['show_image_gallery'] ?? true)): ?>
    <div class="image-gallery">
        <?php foreach ($images as $img): ?>
        <img src="<?= e($mediaUrl($img)) ?>" alt="<?= e($img['filename']) ?>" loading="lazy" />
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Attachments panel -->
    <?php if (!empty($attachments) && ($lessonConfig['show_attachments'] ?? true)): ?>
    <div class="attachments-panel">
        <h4><i class="fa-solid fa-paperclip"></i> <?= e(t('course.sources')) ?></h4>
        <ul class="attachment-list">
            <?php foreach ($attachments as $att): ?>
            <li>
                <a href="<?= e($mediaUrl($att)) ?>" download>
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
    const lessonId     = <?= (int)$lesson['id'] ?>;
    const courseSlug   = <?= json_encode($course['slug']) ?>;
    const pendingLabel = <?= json_encode(t('comment.pending')) ?>;
    const replyLabel   = <?= json_encode(t('comment.reply')) ?>;
    const errorLabel   = <?= json_encode(t('general.error')) ?>;

    // --- Progress: mark complete button ---
    const btnComplete = document.getElementById('btn-complete');
    if (btnComplete) {
        fetch('/api/progress/' + courseSlug, { credentials: 'same-origin' })
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
                body: JSON.stringify({ lessonId, completed: true })
            })
            .then(r => r.json())
            .then(() => {
                btnComplete.textContent = '✓ Completed';
                btnComplete.style.background = '#4caf50';
                if (nextUrl) setTimeout(() => { window.location.href = nextUrl; }, 600);
            })
            .catch(() => {});
        });
    }

    // --- Comments ---
    let activeReplyParentId = null;

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function commentHtml(c, isReply) {
        const badge = c.pending
            ? `<span style="font-size:0.72rem;background:#fff3cd;color:#856404;padding:1px 6px;border-radius:4px;margin-left:6px">${escHtml(pendingLabel)}</span>`
            : '';
        const date   = new Date(c.createdAt * 1000).toLocaleDateString();
        const replyBtn = !isReply && !c.pending
            ? `<button class="reply-btn" data-id="${c.id}"
                 style="background:none;border:none;cursor:pointer;font-size:0.8rem;color:var(--color-accent);padding:0;margin-top:0.3rem">
                 <i class="fa-solid fa-reply"></i> ${escHtml(replyLabel)}
               </button>`
            : '';
        return `
        <div class="comment-item" data-comment-id="${c.id}"
             style="border-bottom:1px solid rgba(0,0,0,0.07);padding:0.75rem 0">
            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.3rem">
                <strong style="font-size:0.875rem">${escHtml(c.userName)}</strong>
                <span style="font-size:0.75rem;color:var(--color-text-muted)">${date}</span>
                ${badge}
            </div>
            <p style="font-size:0.9rem;margin:0 0 0.25rem;line-height:1.5">${escHtml(c.body)}</p>
            ${replyBtn}
            <div class="inline-reply-form" style="display:none;margin-top:0.5rem"></div>
        </div>`;
    }

    function buildInlineReplyForm(parentId) {
        return `
        <form class="reply-submit-form" data-parent-id="${parentId}" style="display:flex;gap:0.5rem;align-items:flex-start">
            <textarea rows="2" placeholder="${escHtml(pendingLabel)}…"
                      style="flex:1;padding:0.4rem;border:1px solid #ccc;border-radius:5px;font-size:0.85rem;resize:vertical;font-family:inherit"></textarea>
            <button type="submit"
                    style="padding:0.4rem 0.9rem;background:var(--color-accent);color:#fff;border:none;border-radius:5px;cursor:pointer;font-size:0.85rem;white-space:nowrap">
                ${escHtml(replyLabel)}
            </button>
        </form>`;
    }

    function renderComments(comments) {
        const list = document.getElementById('comments-list');
        if (!list) return;
        if (!comments.length) {
            list.innerHTML = '<p style="color:var(--color-text-muted);font-size:0.875rem">No comments yet.</p>';
            return;
        }

        const topLevel = comments.filter(c => !c.parentId);
        const replies  = comments.filter(c => c.parentId);

        list.innerHTML = topLevel.map(c => {
            const childHtml = replies
                .filter(r => r.parentId === c.id)
                .map(r => commentHtml(r, true))
                .join('');
            return commentHtml(c, false) +
                (childHtml ? `<div style="margin-left:2rem">${childHtml}</div>` : '');
        }).join('');

        // Wire reply buttons
        list.querySelectorAll('.reply-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const parentId    = parseInt(btn.dataset.id);
                const commentItem = btn.closest('.comment-item');
                const replyDiv    = commentItem.querySelector('.inline-reply-form');

                // Close any other open reply forms
                list.querySelectorAll('.inline-reply-form').forEach(d => {
                    if (d !== replyDiv) d.style.display = 'none';
                });

                if (replyDiv.style.display === 'none') {
                    replyDiv.innerHTML  = buildInlineReplyForm(parentId);
                    replyDiv.style.display = 'block';
                    replyDiv.querySelector('textarea').focus();

                    replyDiv.querySelector('.reply-submit-form').addEventListener('submit', ev => {
                        ev.preventDefault();
                        const text = replyDiv.querySelector('textarea').value.trim();
                        if (!text) return;
                        postComment(text, parentId, () => { replyDiv.style.display = 'none'; });
                    });
                } else {
                    replyDiv.style.display = 'none';
                }
            });
        });
    }

    function loadComments() {
        fetch('/api/comments/' + lessonId, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(renderComments)
            .catch(() => {
                const list = document.getElementById('comments-list');
                if (list) list.innerHTML = `<p style="color:var(--color-text-muted)">${escHtml(errorLabel)}</p>`;
            });
    }

    function postComment(text, parentId, onSuccess) {
        fetch('/api/comments', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ lessonId, body: text, parentId: parentId || null })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                if (onSuccess) onSuccess();
                loadComments();
            }
        })
        .catch(() => {});
    }

    loadComments();

    // Top-level post form
    const form = document.getElementById('comment-form');
    if (form) {
        form.addEventListener('submit', ev => {
            ev.preventDefault();
            const body = document.getElementById('comment-body').value.trim();
            if (!body) return;
            postComment(body, null, () => {
                document.getElementById('comment-body').value = '';
            });
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
