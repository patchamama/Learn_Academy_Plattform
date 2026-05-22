<?php
/** @var array  $course */
/** @var string $stripePublicKey */
/** @var string $paypalClientId */
/** @var string $csrf */
?>
<div style="max-width:500px;margin:2rem auto">
    <h2 style="margin-bottom:0.5rem"><?= e(t('payment.title')) ?></h2>
    <p style="color:var(--color-text-muted);margin-bottom:2rem"><?= e($course['title']) ?></p>

    <div style="background:var(--color-bg-card,#fff);border-radius:10px;padding:1.5rem;margin-bottom:1rem">
        <p><?= e(t('payment.access_for')) ?></p>

        <!-- Stripe -->
        <form method="POST" action="/purchase/<?= e($course['slug']) ?>/stripe" style="margin-bottom:0.75rem">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
            <button type="submit"
                    style="width:100%;padding:0.75rem;background:#635bff;color:#fff;border:none;border-radius:7px;font-size:1rem;font-weight:600;cursor:pointer">
                <i class="fa-brands fa-stripe-s"></i> <?= e(t('payment.stripe')) ?>
            </button>
        </form>

        <!-- PayPal placeholder -->
        <div id="paypal-button-container"></div>
        <?php if (!empty($paypalClientId)): ?>
        <script src="https://www.paypal.com/sdk/js?client-id=<?= e($paypalClientId) ?>&currency=USD"></script>
        <script>
        paypal.Buttons({
            createOrder: function() { return Promise.reject('coming_soon'); }
        }).render('#paypal-button-container');
        </script>
        <?php else: ?>
        <button disabled
                style="width:100%;padding:0.75rem;background:#003087;color:#fff;border:none;border-radius:7px;font-size:1rem;font-weight:600;opacity:0.6;cursor:not-allowed">
            <i class="fa-brands fa-paypal"></i> <?= e(t('payment.paypal')) ?>
        </button>
        <?php endif; ?>
    </div>

    <p style="font-size:0.8rem;color:var(--color-text-muted);text-align:center">
        <a href="/courses/<?= e($course['slug']) ?>" style="color:var(--color-text-muted)"><?= e(t('general.back')) ?></a>
    </p>
</div>
