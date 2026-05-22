<?php
/** @var array $course */
/** @var array $lesson */
?>
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;text-align:center;padding:2rem">
    <i class="fa-solid fa-lock" style="font-size:4rem;color:var(--color-accent);margin-bottom:1.5rem"></i>
    <h2><?= e(t('access.locked_title')) ?></h2>
    <p style="color:var(--color-text-muted);max-width:400px;margin:0.75rem auto 1.5rem">
        <?= e(t('access.locked_message')) ?>
    </p>
    <a href="/purchase/<?= e($course['slug']) ?>"
       style="padding:0.65rem 1.5rem;background:var(--color-accent);color:#fff;border-radius:6px;text-decoration:none;font-weight:600">
        <?= e(t('course.unlock')) ?>
    </a>
    <a href="/courses/<?= e($course['slug']) ?>"
       style="margin-top:1rem;font-size:0.875rem;color:var(--color-text-muted)">
        <?= e(t('general.back')) ?>
    </a>
</div>
