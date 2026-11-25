<div class="card">
    <h2><?= e($title) ?></h2>
    <p><?= e($message) ?></p>
    <p>This application demonstrates PHP routing using Composer and PSR-4.</p>
    <a class="button" href="<?= e(url('/students')) ?>">View Students</a>
</div>

<div class="card">
    <h3>Backend Architecture</h3>
    <ul>
        <li>Composer Autoload</li>
        <li>Front Controller</li>
        <li>Router</li>
        <li>Controllers</li>
        <li>Templates</li>
    </ul>
</div>