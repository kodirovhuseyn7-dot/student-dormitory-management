<div class="card">
    <h2><?= e($title) ?></h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($students as $student): ?>
            <tr>
                <td><?= e($student['id']) ?></td>
                <td><?= e($student['name']) ?></td>
                <td>
                    <a class="button"
                       href="<?= e(url('/students/' . $student['id'])) ?>">
                        View
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>