<?php
/** @var string $message */
?>
<div style="text-align:center;padding:4rem 2rem;max-width:480px;margin:auto">
    <i class="fa-solid fa-clock" style="font-size:3rem;color:var(--color-accent);display:block;margin-bottom:1rem"></i>
    <h2 style="margin-bottom:0.75rem">Coming Soon</h2>
    <p style="color:var(--color-text-muted)"><?= e($message ?? '') ?></p>
    <a href="/" style="display:inline-block;margin-top:1.5rem;padding:0.6rem 1.5rem;background:var(--color-accent);color:#fff;border-radius:6px;text-decoration:none;font-weight:600">
        <?= e(t('lesson.back')) ?>
    </a>
</div>
