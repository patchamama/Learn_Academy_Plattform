<?php
/** @var array  $course */
/** @var string $stripePublicKey */
/** @var string $paypalClientId */
/** @var string $csrf */
$price = number_format(($config['course_price_cents'] ?? 2900) / 100, 2);
?>
<div style="max-width:500px;margin:2rem auto">
    <h2 style="margin-bottom:0.5rem"><?= e(t('payment.title')) ?></h2>
    <p style="color:var(--color-text-muted);margin-bottom:1rem"><?= e($course['title']) ?></p>

    <div style="background:var(--color-bg-card);border-radius:var(--radius);padding:1.5rem;margin-bottom:1.5rem">
        <div style="font-size:2rem;font-weight:700;margin-bottom:0.25rem">$<?= $price ?></div>
        <div style="font-size:0.85rem;color:var(--color-text-muted)"><?= e(t('payment.access_for')) ?></div>
    </div>

    <?php if (!empty($stripePublicKey)): ?>
    <!-- Stripe -->
    <form method="POST" action="/purchase/<?= e($course['slug']) ?>/stripe" style="margin-bottom:1rem">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <button type="submit"
                style="width:100%;padding:0.75rem;background:#635bff;color:#fff;border:none;border-radius:7px;font-size:1rem;font-weight:600;cursor:pointer">
            <i class="fa-solid fa-credit-card"></i> <?= e(t('payment.stripe')) ?>
        </button>
    </form>
    <?php endif; ?>

    <!-- PayPal -->
    <div id="paypal-button-container"></div>

    <?php if (empty($stripePublicKey) && empty($paypalClientId)): ?>
    <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:1rem;font-size:0.9rem">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Payment providers not configured. Ask the admin for manual access.
    </div>
    <?php endif; ?>

    <p style="text-align:center;margin-top:1.5rem;font-size:0.85rem">
        <a href="/courses/<?= e($course['slug']) ?>" style="color:var(--color-text-muted)">
            &larr; <?= e(t('general.back')) ?>
        </a>
    </p>
</div>

<?php if (!empty($paypalClientId)): ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?= e($paypalClientId) ?>&currency=USD"></script>
<script>
paypal.Buttons({
    createOrder: async () => {
        const res = await fetch('/purchase/<?= e($course['slug']) ?>/paypal/create', {
            method: 'POST',
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.error) throw new Error(data.error);
        return data.id;
    },
    onApprove: async (data) => {
        const res = await fetch('/purchase/<?= e($course['slug']) ?>/paypal/capture', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ orderID: data.orderID }),
        });
        const capture = await res.json();
        if (capture.status === 'COMPLETED') {
            window.location.href = '/courses/<?= e($course['slug']) ?>?payment=success';
        } else {
            alert('<?= e(t('payment.failed')) ?>');
        }
    },
    onError: () => alert('<?= e(t('payment.failed')) ?>')
}).render('#paypal-button-container');
</script>
<?php endif; ?>
