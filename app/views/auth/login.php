<div style="display:flex;justify-content:center;align-items:center;min-height:70vh">
    <div style="background:var(--color-bg-card, #fff);border-radius:12px;padding:2.5rem;width:100%;max-width:420px;box-shadow:0 4px 24px rgba(0,0,0,0.08)">
        <h2 style="margin-bottom:1.5rem;font-size:1.5rem"><?= e(t('auth.login')) ?></h2>

        <?php if (!empty($error)): ?>
        <div style="background:#ffeaea;border:1px solid #f5c6cb;color:#721c24;padding:0.75rem 1rem;border-radius:6px;margin-bottom:1rem;font-size:0.875rem">
            <?= e(t($error)) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="/login<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />

            <div style="margin-bottom:1rem">
                <label for="email" style="display:block;margin-bottom:0.4rem;font-size:0.875rem;font-weight:500">
                    <?= e(t('auth.email')) ?>
                </label>
                <input type="email" id="email" name="email" required
                       value="<?= e($_POST['email'] ?? '') ?>"
                       style="width:100%;padding:0.6rem 0.8rem;border:1px solid #ccc;border-radius:6px;font-size:0.95rem;box-sizing:border-box" />
            </div>

            <div style="margin-bottom:1.5rem">
                <label for="password" style="display:block;margin-bottom:0.4rem;font-size:0.875rem;font-weight:500">
                    <?= e(t('auth.password')) ?>
                </label>
                <input type="password" id="password" name="password" required
                       style="width:100%;padding:0.6rem 0.8rem;border:1px solid #ccc;border-radius:6px;font-size:0.95rem;box-sizing:border-box" />
            </div>

            <button type="submit"
                    style="width:100%;padding:0.75rem;background:var(--color-accent, #a48fff);color:#fff;border:none;border-radius:6px;font-size:1rem;font-weight:600;cursor:pointer">
                <?= e(t('auth.login')) ?>
            </button>
        </form>

        <p style="margin-top:1.25rem;text-align:center;font-size:0.875rem;color:var(--color-text-muted)">
            <?= e(t('nav.register')) ?>?
            <a href="/register" style="color:var(--color-accent)"><?= e(t('auth.register')) ?></a>
        </p>
    </div>
</div>
