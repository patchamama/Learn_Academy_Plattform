<?php
/**
 * Reusable comment partial.
 *
 * Expected variables:
 *   $comment  — array with keys: id, user_name, body, status, created_at, parent_id
 *   $indent   — (optional) bool, true for reply-level indent
 */
$indent = $indent ?? false;
$isPending = ($comment['status'] ?? '') === 'pending';
?>
<div class="comment-item" style="border-bottom:1px solid rgba(0,0,0,0.07);padding:0.75rem 0<?= $indent ? ';margin-left:2rem;opacity:0.9' : '' ?>">
    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.4rem">
        <strong style="font-size:0.875rem"><?= e($comment['user_name'] ?? 'User') ?></strong>
        <span style="font-size:0.75rem;color:var(--color-text-muted)">
            <?= date('Y-m-d', $comment['created_at'] ?? time()) ?>
        </span>
        <?php if ($isPending): ?>
        <span style="font-size:0.72rem;background:#fff3cd;color:#856404;padding:1px 6px;border-radius:4px">
            <?= e(t('comment.pending')) ?>
        </span>
        <?php endif; ?>
    </div>
    <p style="font-size:0.9rem;margin:0;line-height:1.5"><?= e($comment['body'] ?? '') ?></p>
</div>
