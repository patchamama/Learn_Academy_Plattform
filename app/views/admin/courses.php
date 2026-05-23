<?php
/** @var array  $courses */
/** @var string $csrf */
?>
<h2 style="margin-bottom:1.5rem"><?= e(t('admin.courses')) ?></h2>

<?php if (!empty($_SESSION['flash'])): ?>
<div style="padding:0.75rem 1rem;border-radius:6px;margin-bottom:1.5rem;
    background:<?= $_SESSION['flash']['type'] === 'success' ? '#d4edda' : '#f8d7da' ?>;
    color:<?= $_SESSION['flash']['type'] === 'success' ? '#155724' : '#721c24' ?>">
    <?= e($_SESSION['flash']['msg']) ?>
</div>
<?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- Import form -->
<details style="margin-bottom:2rem">
    <summary style="cursor:pointer;font-weight:600;color:var(--color-accent);font-size:0.95rem;user-select:none">
        Import new course
    </summary>
    <form method="POST" action="/admin/courses/import"
          style="margin-top:1rem;background:var(--color-bg-card,#fff);padding:1.25rem;border-radius:8px;border:1px solid rgba(0,0,0,0.08);max-width:560px">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />

        <label style="display:block;margin-bottom:1rem">
            <span style="font-size:0.875rem;font-weight:600;display:block;margin-bottom:0.3rem">Source directory *</span>
            <input type="text" name="source_dir" required placeholder="/absolute/path/to/course"
                   style="width:100%;padding:0.5rem 0.75rem;border:1px solid #ccc;border-radius:5px;font-size:0.9rem;box-sizing:border-box" />
        </label>

        <label style="display:block;margin-bottom:1rem">
            <span style="font-size:0.875rem;font-weight:600;display:block;margin-bottom:0.3rem">Title (optional — auto from directory)</span>
            <input type="text" name="title" placeholder="My Course Title"
                   style="width:100%;padding:0.5rem 0.75rem;border:1px solid #ccc;border-radius:5px;font-size:0.9rem;box-sizing:border-box" />
        </label>

        <label style="display:block;margin-bottom:1.25rem">
            <span style="font-size:0.875rem;font-weight:600;display:block;margin-bottom:0.3rem">Thumbnail path (optional)</span>
            <input type="text" name="thumbnail" placeholder="/path/to/thumbnail.jpg"
                   style="width:100%;padding:0.5rem 0.75rem;border:1px solid #ccc;border-radius:5px;font-size:0.9rem;box-sizing:border-box" />
        </label>

        <button type="submit"
                style="padding:0.5rem 1.25rem;background:var(--color-accent,#2563eb);color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:0.9rem;font-weight:600">
            Import Course
        </button>
    </form>
</details>

<!-- Courses table -->
<?php if (empty($courses)): ?>
<p style="color:var(--color-text-muted)">No courses imported yet.</p>
<?php else: ?>
<table style="width:100%;border-collapse:collapse;font-size:0.875rem;background:var(--color-bg-card,#fff);border-radius:8px;overflow:hidden">
    <thead>
        <tr style="background:var(--color-primary,#1e40af);color:#fff;text-align:left">
            <th style="padding:0.75rem 1rem">Title</th>
            <th style="padding:0.75rem 1rem">Slug</th>
            <th style="padding:0.75rem 1rem">Sections</th>
            <th style="padding:0.75rem 1rem">Lessons</th>
            <th style="padding:0.75rem 1rem">Source dir</th>
            <th style="padding:0.75rem 1rem">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($courses as $c): ?>
        <tr style="border-bottom:1px solid rgba(0,0,0,0.06)">
            <td style="padding:0.65rem 1rem">
                <a href="/courses/<?= rawurlencode($c['slug']) ?>" style="color:var(--color-accent,#2563eb);font-weight:600">
                    <?= e($c['title']) ?>
                </a>
            </td>
            <td style="padding:0.65rem 1rem;color:var(--color-text-muted);font-family:monospace;font-size:0.8rem">
                <?= e($c['slug']) ?>
            </td>
            <td style="padding:0.65rem 1rem;text-align:center"><?= (int)$c['section_count'] ?></td>
            <td style="padding:0.65rem 1rem;text-align:center"><?= (int)$c['lesson_count'] ?></td>
            <td style="padding:0.65rem 1rem;color:var(--color-text-muted);font-family:monospace;font-size:0.75rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <span title="<?= e($c['source_dir']) ?>"><?= e($c['source_dir']) ?></span>
            </td>
            <td style="padding:0.65rem 1rem">
                <div style="display:flex;gap:0.5rem;align-items:center">
                    <a href="/courses/<?= rawurlencode($c['slug']) ?>"
                       style="padding:0.3rem 0.7rem;background:#0d6efd;color:#fff;border-radius:4px;font-size:0.8rem;text-decoration:none">
                        View
                    </a>
                    <form method="POST" action="/admin/courses/<?= (int)$c['id'] ?>/delete"
                          onsubmit="return confirm('Delete <?= e(addslashes($c['title'])) ?>? This cannot be undone.')">
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                        <button type="submit"
                                style="padding:0.3rem 0.7rem;background:#dc3545;color:#fff;border:none;border-radius:4px;font-size:0.8rem;cursor:pointer">
                            Delete
                        </button>
                    </form>
                    <form method="POST" action="/admin/courses/import">
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
                        <input type="hidden" name="source_dir" value="<?= e($c['source_dir']) ?>" />
                        <input type="hidden" name="title" value="<?= e($c['title']) ?>" />
                        <input type="hidden" name="thumbnail" value="<?= e($c['thumbnail'] ?? '') ?>" />
                        <button type="submit"
                                style="padding:0.3rem 0.7rem;background:#6c757d;color:#fff;border:none;border-radius:4px;font-size:0.8rem;cursor:pointer"
                                title="Re-import from source directory">
                            Re-import
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
