
<?php
require_once __DIR__ . '/db.php';

$msg = '';
$err = '';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Ҷойгир кардани донишҷӯ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $application_id = (int)($_POST['application_id'] ?? 0);
    $room_id = (int)($_POST['room_id'] ?? 0);
    $start_date = $_POST['start_date'] ?? date('Y-m-d');

    try {
        if ($application_id <= 0 || $room_id <= 0) {
            throw new RuntimeException('Ҳамаи майдонҳоро пур кунед.');
        }

        $pdo->beginTransaction();

        // Санҷиши дархости тасдиқшуда
        $stmt = $pdo->prepare("
            SELECT id, student_id
            FROM applications
            WHERE id = ?
              AND status = 'approved'
            FOR UPDATE
        ");
        $stmt->execute([$application_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            throw new RuntimeException(
                'Дархости тасдиқшуда ёфт нашуд.'
            );
        }

        $student_id = (int)$application['student_id'];

        // Санҷиши мавҷудияти ҷойгиркунии фаъол
        $stmt = $pdo->prepare("
            SELECT id
            FROM allocations
            WHERE student_id = ?
              AND status = 'active'
        ");
        $stmt->execute([$student_id]);

        if ($stmt->fetch()) {
            throw new RuntimeException(
                'Ин донишҷӯ аллакай ҷойгир шудааст.'
            );
        }

        // Санҷиши ҳуҷра
        $stmt = $pdo->prepare("
            SELECT
                r.id,
                r.capacity,
                r.status,
                (
                    SELECT COUNT(*)
                    FROM allocations al
                    WHERE al.room_id = r.id
                      AND al.status = 'active'
                ) AS occupied
            FROM rooms r
            WHERE r.id = ?
            FOR UPDATE
        ");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            throw new RuntimeException('Ҳуҷра ёфт нашуд.');
        }

        if ($room['status'] === 'maintenance') {
            throw new RuntimeException(
                'Ин ҳуҷра дар ҳолати таъмир қарор дорад.'
            );
        }

        if ((int)$room['occupied'] >= (int)$room['capacity']) {
            throw new RuntimeException(
                'Дар ин ҳуҷра ҷойи холӣ нест.'
            );
        }

        // Илова кардани ҷойгиркунӣ
        $stmt = $pdo->prepare("
            INSERT INTO allocations
                (student_id, room_id, application_id, start_date, status)
            VALUES
                (?, ?, ?, ?, 'active')
        ");

        $stmt->execute([
            $student_id,
            $room_id,
            $application_id,
            $start_date
        ]);

        // Нав кардани вазъияти ҳуҷра
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM allocations
            WHERE room_id = ?
              AND status = 'active'
        ");
        $stmt->execute([$room_id]);

        $occupied = (int)$stmt->fetchColumn();

        $new_status = ($occupied >= (int)$room['capacity'])
            ? 'full'
            : 'available';

        $stmt = $pdo->prepare("
            UPDATE rooms
            SET status = ?
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $room_id]);

        $pdo->commit();

        $msg = 'Донишҷӯ бомуваффақият ҷойгир карда шуд.';

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $err = $e->getMessage();
    }
}

// Дархостҳои тасдиқшуда
$approved = $pdo->query("
    SELECT
        a.id AS application_id,
        a.student_id,
        s.student_number,
        u.full_name
    FROM applications a
    INNER JOIN students s ON s.id = a.student_id
    INNER JOIN users u ON u.id = s.user_id
    WHERE a.status = 'approved'
      AND NOT EXISTS (
          SELECT 1
          FROM allocations al
          WHERE al.student_id = a.student_id
            AND al.status = 'active'
      )
    ORDER BY a.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Ҳуҷраҳои дастрас
$rooms = $pdo->query("
    SELECT
        r.id,
        r.room_number,
        r.floor,
        r.capacity,
        r.status,
        d.name AS dormitory_name,
        (
            SELECT COUNT(*)
            FROM allocations al
            WHERE al.room_id = r.id
              AND al.status = 'active'
        ) AS occupied
    FROM rooms r
    INNER JOIN dormitories d
        ON d.id = r.dormitory_id
    WHERE r.status IN ('available', 'full')
    ORDER BY d.name, r.room_number
")->fetchAll(PDO::FETCH_ASSOC);

// Рӯйхати ҷойгиркуниҳо
$rows = $pdo->query("
    SELECT
        al.id,
        al.start_date,
        al.status,
        u.full_name,
        s.student_number,
        r.room_number,
        d.name AS dormitory_name
    FROM allocations al
    INNER JOIN students s ON s.id = al.student_id
    INNER JOIN users u ON u.id = s.user_id
    INNER JOIN rooms r ON r.id = al.room_id
    INNER JOIN dormitories d ON d.id = r.dormitory_id
    ORDER BY al.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ҷойгиркунӣ | SDMS</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f1f5f9;
    font-family: Arial, sans-serif;
    color: #1e293b;
}

.layout {
    display: flex;
    min-height: 100vh;
}

.side {
    width: 245px;
    min-width: 245px;
    background: #172554;
    padding: 22px 18px;
    color: white;
}

.side a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 13px;
    border-radius: 8px;
    margin-bottom: 5px;
}

.side a:hover,
.side a.active {
    background: #2563eb;
}

.main {
    padding: 30px;
    flex: 1;
    min-width: 0;
}

.card {
    background: white;
    padding: 24px;
    border-radius: 14px;
    margin-bottom: 22px;
    box-shadow: 0 4px 18px #00000008;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 7px;
}

input,
select {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 15px;
}

button {
    background: #1d4ed8;
    color: white;
    border: 0;
    border-radius: 8px;
    padding: 13px 20px;
    margin-top: 15px;
    cursor: pointer;
}

button:hover {
    background: #1e40af;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #1e40af;
    color: white;
    text-align: left;
}

td,
th {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
}

.wrap {
    overflow-x: auto;
}

.ok {
    color: #047857;
    background: #d1fae5;
    padding: 12px;
    border-radius: 8px;
}

.bad {
    color: #b91c1c;
    background: #fee2e2;
    padding: 12px;
    border-radius: 8px;
}

@media(max-width: 750px) {
    .layout {
        display: block;
    }

    .side {
        width: 100%;
    }

    .grid {
        grid-template-columns: 1fr;
    }

    .main {
        padding: 15px;
    }
}
</style>
</head>

<body>

<div class="layout">

<nav class="side">
    <h2>SDMS | Хобгоҳ</h2>

    <a href="index.php">Панели асосӣ</a>
    <a href="students.php">Донишҷӯён</a>
    <a href="dormitories.php">Хобгоҳҳо</a>
    <a href="rooms.php">Ҳуҷраҳо</a>
    <a href="applications.php">Дархостҳо</a>
    <a href="allocations.php" class="active">Ҷойгиркунӣ</a>
    <a href="payments.php">Пардохтҳо</a>
</nav>

<main class="main">

<h1>Ҷойгиркунии донишҷӯён</h1>

<section class="card">

<h2>Ҷойгир кардани донишҷӯ</h2>

<?php if ($msg): ?>
    <p class="ok"><?= h($msg) ?></p>
<?php endif; ?>

<?php if ($err): ?>
    <p class="bad"><?= h($err) ?></p>
<?php endif; ?>

<form method="POST">

<div class="grid">

    <div>
        <label>Дархости тасдиқшуда</label>

        <select name="application_id" required>
            <option value="">Интихоб кунед</option>

            <?php foreach ($approved as $a): ?>
                <option value="<?= h($a['application_id']) ?>">
                    <?= h($a['full_name']) ?>
                    — <?= h($a['student_number']) ?>
                    (#<?= h($a['application_id']) ?>)
                </option>
            <?php endforeach; ?>

        </select>
    </div>

    <div>
        <label>Ҳуҷра</label>

        <select name="room_id" required>
            <option value="">Интихоб кунед</option>

            <?php foreach ($rooms as $r): ?>

                <?php
                $free = (int)$r['capacity'] - (int)$r['occupied'];
                ?>

                <?php if ($free > 0 && $r['status'] !== 'maintenance'): ?>
                    <option value="<?= h($r['id']) ?>">
                        <?= h($r['dormitory_name']) ?>
                        / Ҳуҷраи <?= h($r['room_number']) ?>
                        — <?= $free ?> ҷойи холӣ
                    </option>
                <?php endif; ?>

            <?php endforeach; ?>

        </select>
    </div>

    <div>
        <label>Санаи ҷойгиркунӣ</label>
        <input
            type="date"
            name="start_date"
            value="<?= date('Y-m-d') ?>"
            required
        >
    </div>

</div>

<button type="submit">Ҷойгир кардан</button>

</form>
</section>

<section class="card">

<h2>Рӯйхати ҷойгиркуниҳо</h2>

<div class="wrap">

<table>

<thead>
<tr>
    <th>ID</th>
    <th>Донишҷӯ</th>
    <th>Хобгоҳ</th>
    <th>Ҳуҷра</th>
    <th>Сана</th>
    <th>Вазъият</th>
</tr>
</thead>

<tbody>

<?php foreach ($rows as $r): ?>

<tr>
    <td><?= h($r['id']) ?></td>

    <td>
        <?= h($r['full_name']) ?>
        (<?= h($r['student_number']) ?>)
    </td>

    <td><?= h($r['dormitory_name']) ?></td>

    <td><?= h($r['room_number']) ?></td>

    <td><?= h($r['start_date']) ?></td>

    <td><?= h($r['status']) ?></td>
</tr>

<?php endforeach; ?>

<?php if (!$rows): ?>
<tr>
    <td colspan="6">Ҷойгиркунӣ нест.</td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>
</section>

</main>
</div>

</body>
</html>
