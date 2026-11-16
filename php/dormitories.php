<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$role = $_SESSION['user']['role'] ?? '';
$isManager = in_array($role, ['admin', 'manager'], true);
$error = '';

// Танҳо администратор ва менеҷер метавонанд хобгоҳ илова кунанд.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isManager) {
        http_response_code(403);
        exit('Шумо барои илова кардани хобгоҳ иҷозат надоред.');
    }

    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $floors = (int)($_POST['floors'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($name === '' || $address === '' || $floors < 1) {
        $error = 'Лутфан ҳамаи майдонҳои ҳатмиро дуруст пур кунед.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO dormitories (name, address, total_floors, description) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$name, $address, $floors, $description]);
            flash('Хобгоҳ бомуваффақият илова шуд.');
            redirect('dormitories.php');
        } catch (Throwable $e) {
            $error = 'Ҳангоми илова кардани хобгоҳ хато ба вуҷуд омад.';
        }
    }
}

$stmt = $pdo->query(
    'SELECT d.id, d.name, d.address, d.total_floors, d.description,
            COUNT(r.id) AS room_count
     FROM dormitories d
     LEFT JOIN rooms r ON r.dormitory_id = d.id
     GROUP BY d.id, d.name, d.address, d.total_floors, d.description
     ORDER BY d.id DESC'
);
$dormitories = $stmt->fetchAll();

$pageTitle = 'Хобгоҳҳо';
require __DIR__ . '/includes/header.php';
?>

<h1>Хобгоҳҳо</h1>
<p class="muted">Рӯйхати хобгоҳҳои ба низом воридшуда.</p>

<?php if ($error !== ''): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($isManager): ?>
<div class="panel">
    <h3>Иловаи хобгоҳи нав</h3>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="row">
            <div class="field">
                <label for="name">Номи хобгоҳ</label>
                <input id="name" name="name" maxlength="120" required>
            </div>
            <div class="field">
                <label for="address">Суроға</label>
                <input id="address" name="address" maxlength="255" required>
            </div>
            <div class="field">
                <label for="floors">Шумораи ошёнаҳо</label>
                <input id="floors" type="number" name="floors" min="1" max="255" required>
            </div>
            <div class="field">
                <label for="description">Тавсиф</label>
                <input id="description" name="description">
            </div>
        </div>
        <button type="submit">Илова кардан</button>
    </form>
</div>
<?php else: ?>
    <div class="alert">Шумо метавонед маълумоти хобгоҳҳоро бинед. Илова ё тағйир додани маълумот танҳо барои менеҷер ва администратор дастрас аст.</div>
<?php endif; ?>

<div class="table-wrap">
<table>
    <thead>
        <tr>
            <th>ID</th><th>Ном</th><th>Суроға</th><th>Ошёнаҳо</th><th>Шумораи ҳуҷраҳо</th><th>Тавсиф</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$dormitories): ?>
        <tr><td colspan="6">Ҳоло ягон хобгоҳ сабт нашудааст.</td></tr>
    <?php else: ?>
        <?php foreach ($dormitories as $dormitory): ?>
        <tr>
            <td><?= (int)$dormitory['id'] ?></td>
            <td><?= e($dormitory['name']) ?></td>
            <td><?= e($dormitory['address']) ?></td>
            <td><?= (int)$dormitory['total_floors'] ?></td>
            <td><?= (int)$dormitory['room_count'] ?></td>
            <td><?= e($dormitory['description'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
