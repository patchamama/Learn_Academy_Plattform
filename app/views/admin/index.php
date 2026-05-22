<?php
/** @var int $userCount */
/** @var int $courseCount */
/** @var int $pendingCommentCount */
?>
<h2 style="margin-bottom:1.5rem">Admin Dashboard</h2>

<div class="cards" style="max-width:720px">
    <a class="card" href="/admin/users">
        <i class="fa-solid fa-users" style="font-size:1.8rem;margin-bottom:0.5rem;display:block;color:var(--color-accent)"></i>
        <h3><?= e(t('admin.users')) ?></h3>
        <p style="font-size:1.5rem;font-weight:700;color:var(--color-accent)"><?= (int)$userCount ?></p>
    </a>

    <a class="card" href="/courses">
        <i class="fa-solid fa-chalkboard-user" style="font-size:1.8rem;margin-bottom:0.5rem;display:block;color:var(--color-accent)"></i>
        <h3><?= e(t('nav.courses')) ?></h3>
        <p style="font-size:1.5rem;font-weight:700;color:var(--color-accent)"><?= (int)$courseCount ?></p>
    </a>

    <div class="card">
        <i class="fa-solid fa-comments" style="font-size:1.8rem;margin-bottom:0.5rem;display:block;color:<?= $pendingCommentCount > 0 ? '#e67e22' : 'var(--color-accent)' ?>"></i>
        <h3><?= e(t('admin.moderation')) ?></h3>
        <p style="font-size:1.5rem;font-weight:700;color:<?= $pendingCommentCount > 0 ? '#e67e22' : 'var(--color-accent)' ?>">
            <?= (int)$pendingCommentCount ?>
            <span style="font-size:0.875rem;font-weight:400;color:var(--color-text-muted)">pending</span>
        </p>
        <?php if ($pendingCommentCount > 0): ?>
        <a href="/admin" style="font-size:0.8rem;color:var(--color-accent)">Moderate now</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($pendingCommentCount > 0): ?>
<!-- Pending comments moderation widget -->
<div style="margin-top:2rem;max-width:720px">
    <h3 style="margin-bottom:1rem"><?= e(t('admin.moderation')) ?></h3>
    <?php
    /** @var \LearnAcademy\App\App $app */
    $pendingComments = $app->db->fetchAll(
        "SELECT c.id, c.body, c.created_at, u.name AS user_name, l.title AS lesson_title
         FROM comments c
         JOIN users u ON u.id = c.user_id
         JOIN lessons l ON l.id = c.lesson_id
         WHERE c.status = 'pending'
         ORDER BY c.created_at ASC
         LIMIT 20"
    );
    ?>
    <?php foreach ($pendingComments as $comment): ?>
    <div style="background:var(--color-bg-card, #fff);border-radius:8px;padding:1rem;margin-bottom:1rem;border:1px solid rgba(0,0,0,0.08)">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.5rem">
            <div>
                <strong style="font-size:0.875rem"><?= e($comment['user_name']) ?></strong>
                <span style="font-size:0.75rem;color:var(--color-text-muted);margin-left:0.5rem">on</span>
                <em style="font-size:0.8rem;color:var(--color-text-muted)"><?= e($comment['lesson_title']) ?></em>
            </div>
            <span style="font-size:0.75rem;color:var(--color-text-muted)"><?= date('Y-m-d', $comment['created_at']) ?></span>
        </div>
        <p style="font-size:0.9rem;margin:0 0 0.75rem;line-height:1.5"><?= e($comment['body']) ?></p>
        <div style="display:flex;gap:0.5rem">
            <form method="POST" action="/admin/comments/<?= (int)$comment['id'] ?>/moderate">
                <input type="hidden" name="_csrf" value="<?= e($app->auth->csrfToken()) ?>" />
                <input type="hidden" name="action" value="approve" />
                <button type="submit"
                        style="padding:0.35rem 0.9rem;background:#28a745;color:#fff;border:none;border-radius:5px;cursor:pointer;font-size:0.85rem">
                    <?= e(t('admin.approve')) ?>
                </button>
            </form>
            <form method="POST" action="/admin/comments/<?= (int)$comment['id'] ?>/moderate">
                <input type="hidden" name="_csrf" value="<?= e($app->auth->csrfToken()) ?>" />
                <input type="hidden" name="action" value="reject" />
                <button type="submit"
                        style="padding:0.35rem 0.9rem;background:#dc3545;color:#fff;border:none;border-radius:5px;cursor:pointer;font-size:0.85rem">
                    <?= e(t('admin.reject')) ?>
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
