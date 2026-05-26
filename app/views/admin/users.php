<?php
/** @var array  $users */
/** @var array  $courses */
/** @var string $csrf */
/** @var int    $page */
/** @var int    $pages */
/** @var int    $total */
?>
<h2 style="margin-bottom:0.25rem"><?= e(t('admin.users')) ?></h2>
<p style="color:var(--color-text-muted);font-size:0.85rem;margin-bottom:1.5rem"><?= (int)$total ?> users total</p>

<table style="width:100%;border-collapse:collapse;font-size:0.875rem;background:var(--color-bg-card, #fff);border-radius:8px;overflow:hidden">
    <thead>
        <tr style="background:var(--color-primary);color:#fff;text-align:left">
            <th style="padding:0.75rem 1rem">Name</th>
            <th style="padding:0.75rem 1rem">Email</th>
            <th style="padding:0.75rem 1rem">Role</th>
            <th style="padding:0.75rem 1rem">Enrolled Courses</th>
            <th style="padding:0.75rem 1rem"><?= e(t('admin.grant_access')) ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
        <tr style="border-bottom:1px solid rgba(0,0,0,0.06)">
            <td style="padding:0.75rem 1rem"><?= e($u['name']) ?></td>
            <td style="padding:0.75rem 1rem"><?= e($u['email']) ?></td>
            <td style="padding:0.75rem 1rem">
                <span style="background:<?= $u['role'] === 'admin' ? 'var(--color-accent)' : '#6c757d' ?>;color:#fff;padding:2px 8px;border-radius:4px;font-size:0.75rem">
                    <?= e($u['role']) ?>
                </span>
            </td>
            <td style="padding:0.75rem 1rem">
                <?php
                $enrolled = array_filter($courses, fn($c) => in_array($c['id'], $u['enrolled_course_ids'], true));
                if (empty($enrolled)):
                ?>
                <span style="color:var(--color-text-muted)">None</span>
                <?php else: ?>
                <?= e(implode(', ', array_column(array_values($enrolled), 'title'))) ?>
                <?php endif; ?>
            </td>
            <td style="padding:0.75rem 1rem">
                <form method="POST" action="/admin/users/<?= (int)$u['id'] ?>/access"
                      style="display:flex;gap:0.4rem;align-items:center;flex-wrap:wrap">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                    <select name="courseId" style="padding:0.3rem 0.5rem;border-radius:4px;border:1px solid #ccc;font-size:0.8rem">
                        <?php foreach ($courses as $course): ?>
                        <option value="<?= (int)$course['id'] ?>"><?= e($course['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="expiresAt"
                           style="padding:0.3rem 0.5rem;border-radius:4px;border:1px solid #ccc;font-size:0.8rem"
                           title="Expires at (optional)" />
                    <button type="submit"
                            style="padding:0.3rem 0.8rem;background:var(--color-accent);color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:0.8rem;font-weight:600">
                        <?= e(t('admin.grant_access')) ?>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if ($pages > 1): ?>
<nav style="display:flex;gap:0.4rem;align-items:center;margin-top:1.25rem;flex-wrap:wrap">
    <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?>"
       style="padding:0.35rem 0.75rem;border:1px solid #ccc;border-radius:5px;font-size:0.85rem;text-decoration:none;color:var(--color-accent)">
        &laquo; Prev
    </a>
    <?php endif; ?>

    <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
    <a href="?page=<?= $i ?>"
       style="padding:0.35rem 0.75rem;border:1px solid <?= $i === $page ? 'var(--color-accent)' : '#ccc' ?>;border-radius:5px;font-size:0.85rem;text-decoration:none;
              background:<?= $i === $page ? 'var(--color-accent)' : 'transparent' ?>;
              color:<?= $i === $page ? '#fff' : 'var(--color-accent)' ?>">
        <?= $i ?>
    </a>
    <?php endfor; ?>

    <?php if ($page < $pages): ?>
    <a href="?page=<?= $page + 1 ?>"
       style="padding:0.35rem 0.75rem;border:1px solid #ccc;border-radius:5px;font-size:0.85rem;text-decoration:none;color:var(--color-accent)">
        Next &raquo;
    </a>
    <?php endif; ?>

    <span style="font-size:0.8rem;color:var(--color-text-muted);margin-left:0.5rem">
        Page <?= $page ?> of <?= $pages ?>
    </span>
</nav>
<?php endif; ?>
