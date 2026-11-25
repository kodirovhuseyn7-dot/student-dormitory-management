<div class="card">
    <h2><?= e($title ?? 'Error') ?></h2>
    <p><?= e($message ?? 'An error occurred.') ?></p>
    <a class="button" href="<?= e(url('/')) ?>">Home</a>
</div>