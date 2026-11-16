<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$role = $_SESSION['user']['role'] ?? '';
$isManager = in_array($role, ['admin', 'manager'], true);
$userId = current_user_id();
$error = '';

// Барои донишҷӯ ID-и сабти students-ро аз user_id мегирем.
$studentId = null;
if ($role === 'student') {
    $studentStmt = $pdo->prepare('SELECT id FROM students WHERE user_id = ? LIMIT 1');
    $studentStmt->execute([$userId]);
    $studentId = $studentStmt->fetchColumn();
    if (!$studentId) {
        http_response_code(403);
        exit('Барои ҳисоби шумо маълумоти донишҷӯ ёфт нашуд. Ба маъмур муроҷиат кунед.');
    }
    $studentId = (int)$studentId;
}

// Танҳо администратор ва менеҷер метавонанд пардохт илова ё тағйир диҳанд.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isManager) {
        http_response_code(403);
        exit('Шумо барои тағйир додани пардохтҳо иҷозат надоред.');
    }
    verify_csrf();

    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $selectedStudentId = (int)($_POST['student_id'] ?? 0);
            $allocationId = ($_POST['allocation_id'] ?? '') !== '' ? (int)$_POST['allocation_id'] : null;
            $amount = (float)($_POST['amount'] ?? 0);
            $paymentDate = $_POST['payment_date'] ?? '';
            $period = trim($_POST['payment_period'] ?? '');
            $status = $_POST['status'] ?? 'pending';
            $method = $_POST['payment_method'] ?? 'cash';

            if ($selectedStudentId < 1 || $amount <= 0 || $paymentDate === '' || $period === '') {
                throw new RuntimeException('Маълумоти пардохт нодуруст аст.');
            }
            if (!in_array($status, ['pending', 'paid', 'overdue'], true)) {
                throw new RuntimeException('Вазъияти пардохт нодуруст аст.');
            }
            if (!in_array($method, ['cash', 'card', 'transfer', 'bank_transfer'], true)) {
                throw new RuntimeException('Усули пардохт нодуруст аст.');
            }

            // Агар ҷойгиркунӣ интихоб шуда бошад, он бояд ба ҳамон донишҷӯ тааллуқ дошта бошад.
            if ($allocationId !== null) {
                $check = $pdo->prepare('SELECT id FROM allocations WHERE id = ? AND student_id = ? LIMIT 1');
                $check->execute([$allocationId, $selectedStudentId]);
                if (!$check->fetchColumn()) {
                    throw new RuntimeException('Ҷойгиркунии интихобшуда ба ин донишҷӯ тааллуқ надорад.');
                }
            }

            $insert = $pdo->prepare(
                'INSERT INTO payments (student_id, allocation_id, amount, payment_date, payment_period, status, payment_method)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([$selectedStudentId, $allocationId, $amount, $paymentDate, $period, $status, $method]);
            flash('Пардохт бомуваффақият илова шуд.');
            redirect('payments.php');
        }

        if ($action === 'update') {
            $paymentId = (int)($_POST['id'] ?? 0);
            $newStatus = $_POST['status'] ?? '';
            if ($paymentId < 1 || !in_array($newStatus, ['pending', 'paid', 'overdue'], true)) {
                throw new RuntimeException('Маълумоти тағйирот нодуруст аст.');
            }
            $update = $pdo->prepare('UPDATE payments SET status = ? WHERE id = ?');
            $update->execute([$newStatus, $paymentId]);
            flash('Вазъияти пардохт нав карда шуд.');
            redirect('payments.php');
        }

        throw new RuntimeException('Амали дархостшуда дастгирӣ намешавад.');
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Ҳангоми коркарди пардохт хато ба вуҷуд омад. Маълумотро санҷед.';
    }
}

if ($isManager) {
    $students = $pdo->query(
        'SELECT s.id, u.full_name
         FROM students s
         INNER JOIN users u ON u.id = s.user_id
         ORDER BY u.full_name'
    )->fetchAll();

    $allocations = $pdo->query(
        "SELECT a.id, a.student_id, r.room_number, u.full_name
         FROM allocations a
         INNER JOIN rooms r ON r.id = a.room_id
         INNER JOIN students s ON s.id = a.student_id
         INNER JOIN users u ON u.id = s.user_id
         WHERE a.status = 'active'
         ORDER BY u.full_name, r.room_number"
    )->fetchAll();

    $paymentsStmt = $pdo->query(
        'SELECT p.*, u.full_name
         FROM payments p
         LEFT JOIN students s ON s.id = p.student_id
         LEFT JOIN users u ON u.id = s.user_id
         ORDER BY p.id DESC'
    );
} else {
    // Донишҷӯ танҳо пардохтҳои худашро мебинад.
    $students = [];
    $allocations = [];
    $paymentsStmt = $pdo->prepare(
        'SELECT p.*, u.full_name
         FROM payments p
         LEFT JOIN students s ON s.id = p.student_id
         LEFT JOIN users u ON u.id = s.user_id
         WHERE p.student_id = ?
         ORDER BY p.id DESC'
    );
    $paymentsStmt->execute([$studentId]);
}
$payments = $paymentsStmt->fetchAll();

$pageTitle = 'Пардохтҳо';
require __DIR__ . '/includes/header.php';
?>

<h1><?= $isManager ? 'Идоракунии пардохтҳо' : 'Пардохтҳои ман' ?></h1>
<?php if ($error !== ''): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($isManager): ?>
<div class="panel">
    <h3>Иловаи пардохт</h3>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="row">
            <div class="field">
                <label for="student_id">Донишҷӯ</label>
                <select id="student_id" name="student_id" required>
                    <option value="">Интихоб кунед</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= (int)$student['id'] ?>"><?= e($student['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="allocation_id">Ҷойгиркунӣ (ихтиёрӣ)</label>
                <select id="allocation_id" name="allocation_id">
                    <option value="">Интихоб нашудааст</option>
                    <?php foreach ($allocations as $allocation): ?>
                        <option value="<?= (int)$allocation['id'] ?>">
                            #<?= (int)$allocation['id'] ?> — <?= e($allocation['full_name']) ?>, ҳуҷраи <?= e($allocation['room_number']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="amount">Маблағ (сомонӣ)</label>
                <input id="amount" type="number" name="amount" min="0.01" step="0.01" required>
            </div>
            <div class="field">
                <label for="payment_date">Сана</label>
                <input id="payment_date" type="date" name="payment_date" value="<?= e(date('Y-m-d')) ?>" required>
            </div>
            <div class="field">
                <label for="payment_period">Давраи пардохт</label>
                <input id="payment_period" name="payment_period" maxlength="30" placeholder="Моҳи ноябри 2026" required>
            </div>
            <div class="field">
                <label for="payment_method">Усул</label>
                <select id="payment_method" name="payment_method">
                    <option value="cash">Нақдӣ</option>
                    <option value="card">Корт</option>
                    <option value="transfer">Интиқол</option>
                </select>
            </div>
            <div class="field">
                <label for="status">Вазъият</label>
                <select id="status" name="status">
                    <option value="paid">Пардохтшуда</option>
                    <option value="pending">Интизорӣ</option>
                    <option value="overdue">Қарздорӣ</option>
                </select>
            </div>
        </div>
        <button type="submit">Сабт кардани пардохт</button>
    </form>
</div>
<?php else: ?>
    <div class="alert">Дар ин саҳифа танҳо маълумоти пардохти ҳисоби шумо нишон дода мешавад.</div>
<?php endif; ?>

<div class="table-wrap">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <?php if ($isManager): ?><th>Донишҷӯ</th><?php endif; ?>
            <th>Маблағ</th><th>Сана</th><th>Давра</th><th>Усул</th><th>Вазъият</th>
            <?php if ($isManager): ?><th>Тағйир</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php if (!$payments): ?>
        <tr><td colspan="<?= $isManager ? 8 : 6 ?>">Пардохтҳо ёфт нашуданд.</td></tr>
    <?php else: ?>
        <?php foreach ($payments as $payment): ?>
        <tr>
            <td><?= (int)$payment['id'] ?></td>
            <?php if ($isManager): ?><td><?= e($payment['full_name'] ?? '—') ?></td><?php endif; ?>
            <td><?= number_format((float)$payment['amount'], 2) ?> сомонӣ</td>
            <td><?= e($payment['payment_date']) ?></td>
            <td><?= e($payment['payment_period'] ?? '—') ?></td>
            <td><?= e($payment['payment_method'] ?? '—') ?></td>
            <td><?= e($payment['status']) ?></td>
            <?php if ($isManager): ?>
            <td>
                <form method="post" class="actions">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int)$payment['id'] ?>">
                    <select name="status" aria-label="Вазъияти нав">
                        <option value="pending" <?= $payment['status'] === 'pending' ? 'selected' : '' ?>>Интизорӣ</option>
                        <option value="paid" <?= $payment['status'] === 'paid' ? 'selected' : '' ?>>Пардохтшуда</option>
                        <option value="overdue" <?= $payment['status'] === 'overdue' ? 'selected' : '' ?>>Қарздорӣ</option>
                    </select>
                    <button type="submit">Сабт</button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
