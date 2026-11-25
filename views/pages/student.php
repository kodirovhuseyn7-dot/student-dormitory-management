<div class="card">
    <h2><?= e($title) ?></h2>

    <p><strong>ID:</strong> <?= e($student['id']) ?></p>
    <p><strong>Name:</strong> <?= e($student['name']) ?></p>

    <a class="button" href="<?= e(url('/students')) ?>">Back</a>
</div>