<?php
/** @var array $courses */
?>
<h2 style="margin-bottom:1.5rem">
    <i class="fa-solid fa-pen-ruler" style="color:var(--color-accent);margin-right:0.5rem"></i>
    Web Editor
</h2>

<?php if (!empty($_SESSION['flash'])): ?>
<div style="padding:0.75rem 1rem;border-radius:6px;margin-bottom:1.5rem;
    background:<?= $_SESSION['flash']['type'] === 'success' ? '#d4edda' : '#f8d7da' ?>;
    color:<?= $_SESSION['flash']['type'] === 'success' ? '#155724' : '#721c24' ?>">
    <?= e($_SESSION['flash']['msg']) ?>
</div>
<?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<?php if (empty($courses)): ?>
<div style="background:var(--color-bg-card,#fff);border-radius:8px;padding:2rem;text-align:center;border:1px solid rgba(0,0,0,0.08)">
    <i class="fa-solid fa-book-open" style="font-size:2rem;color:var(--color-text-muted);margin-bottom:1rem;display:block"></i>
    <p style="color:var(--color-text-muted);margin:0">No courses available yet. Import a course first from <a href="/admin/courses" style="color:var(--color-accent)">Admin &rsaquo; Courses</a>.</p>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem">
    <?php foreach ($courses as $c): ?>
    <div style="background:var(--color-bg-card,#fff);border-radius:8px;border:1px solid rgba(0,0,0,0.08);padding:1.25rem;display:flex;flex-direction:column;gap:0.75rem">
        <div style="display:flex;align-items:flex-start;gap:0.75rem">
            <?php if (!empty($c['thumbnail'])): ?>
            <img src="<?= e($c['thumbnail']) ?>" alt="" style="width:52px;height:52px;object-fit:cover;border-radius:6px;flex-shrink:0">
            <?php else: ?>
            <div style="width:52px;height:52px;border-radius:6px;background:var(--color-primary,#1e40af);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="fa-solid fa-graduation-cap" style="color:#fff;font-size:1.25rem"></i>
            </div>
            <?php endif; ?>
            <div style="min-width:0">
                <div style="font-weight:700;font-size:0.95rem;color:var(--color-accent);margin-bottom:0.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?= e($c['title']) ?>
                </div>
                <div style="font-size:0.78rem;color:var(--color-text-muted);font-family:monospace">
                    <?= e($c['slug']) ?>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:1rem;font-size:0.82rem;color:var(--color-text-muted)">
            <span><i class="fa-solid fa-layer-group" style="margin-right:0.25rem"></i><?= (int)$c['section_count'] ?> sections</span>
            <span><i class="fa-solid fa-file-video" style="margin-right:0.25rem"></i><?= (int)$c['lesson_count'] ?> lessons</span>
        </div>
        <?php if (!empty($c['source_dir'])): ?>
        <div style="font-size:0.75rem;color:var(--color-text-muted);font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= e($c['source_dir']) ?>">
            <i class="fa-solid fa-folder" style="margin-right:0.3rem"></i><?= e($c['source_dir']) ?>
        </div>
        <?php endif; ?>
        <div style="margin-top:auto;padding-top:0.5rem;border-top:1px solid rgba(0,0,0,0.06)">
            <a href="/admin/editor/<?= (int)$c['id'] ?>"
               style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.45rem 1rem;background:var(--color-accent,#2563eb);color:#fff;border-radius:5px;font-size:0.875rem;font-weight:600;text-decoration:none">
                <i class="fa-solid fa-pen-to-square"></i>
                Edit
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
