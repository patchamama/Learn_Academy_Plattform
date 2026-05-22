<?php
/** @var array  $comments */
/** @var string $csrf */
?>
<h2 style="margin-bottom:1.5rem"><?= e(t('admin.moderation')) ?></h2>

<?php if (empty($comments)): ?>
<p style="color:var(--color-text-muted)">No pending comments.</p>
<?php else: ?>
<?php foreach ($comments as $comment): ?>
<div style="background:var(--color-bg-card, #fff);border-radius:8px;padding:1rem;margin-bottom:1rem;border:1px solid rgba(0,0,0,0.08)">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.5rem">
        <div>
            <strong style="font-size:0.875rem"><?= e($comment['user_name']) ?></strong>
            <span style="font-size:0.75rem;color:var(--color-text-muted);margin-left:0.5rem">on</span>
            <em style="font-size:0.8rem;color:var(--color-text-muted)"><?= e($comment['lesson_title']) ?></em>
        </div>
        <span style="font-size:0.75rem;color:var(--color-text-muted)"><?= date('Y-m-d H:i', $comment['created_at']) ?></span>
    </div>
    <p style="font-size:0.9rem;margin:0 0 0.75rem;line-height:1.5"><?= e($comment['body']) ?></p>
    <div style="display:flex;gap:0.5rem">
        <form method="POST" action="/admin/comments/<?= (int)$comment['id'] ?>/moderate">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
            <input type="hidden" name="action" value="approve" />
            <button type="submit"
                    style="padding:0.35rem 0.9rem;background:#28a745;color:#fff;border:none;border-radius:5px;cursor:pointer;font-size:0.85rem">
                <?= e(t('admin.approve')) ?>
            </button>
        </form>
        <form method="POST" action="/admin/comments/<?= (int)$comment['id'] ?>/moderate">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
            <input type="hidden" name="action" value="reject" />
            <button type="submit"
                    style="padding:0.35rem 0.9rem;background:#dc3545;color:#fff;border:none;border-radius:5px;cursor:pointer;font-size:0.85rem">
                <?= e(t('admin.reject')) ?>
            </button>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
