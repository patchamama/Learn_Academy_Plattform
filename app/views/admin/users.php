<?php
/** @var array  $users */
/** @var array  $courses */
/** @var string $csrf */
?>
<h2 style="margin-bottom:1.5rem"><?= e(t('admin.users')) ?></h2>

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
