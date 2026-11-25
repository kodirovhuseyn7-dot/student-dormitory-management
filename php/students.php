<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/repositories/StudentRepository.php';

require_role(['admin', 'manager']);

$repository = new StudentRepository($pdo);

// Танзимоти ҷустуҷӯ ва филтрҳо
$search = trim($_GET['search'] ?? '');
$faculty = trim($_GET['faculty'] ?? '');

$courseValue = filter_var(
    $_GET['course'] ?? '',
    FILTER_VALIDATE_INT
);

$course = (
    $courseValue !== false &&
    $courseValue >= 1 &&
    $courseValue <= 6
) ? $courseValue : null;

// Танзимоти пагинатсия
$limit = 10;

$pageValue = filter_var(
    $_GET['page'] ?? 1,
    FILTER_VALIDATE_INT
);

$page = ($pageValue !== false && $pageValue > 0)
    ? $pageValue
    : 1;

// Нест кардани донишҷӯ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $id = filter_var(
        $_POST['id'] ?? 0,
        FILTER_VALIDATE_INT
    );

    try {
        if (!$id || $id < 1) {
            throw new InvalidArgumentException('ID нодуруст аст.');
        }

        $deleted = $repository->delete($id);

        if ($deleted) {
            flash('Донишҷӯ бомуваффақият нест карда шуд.');
        } else {
            flash('Донишҷӯ ёфт нашуд.');
        }

    } catch (PDOException $e) {
        if (
            $e->getCode() === '23000' ||
            (int) ($e->errorInfo[1] ?? 0) === 1451
        ) {
            flash(
                'Ин донишҷӯ сабтҳои вобаста дорад. Аввал онҳоро санҷед.'
            );
        } else {
            error_log($e->getMessage());
            flash('Ҳангоми нест кардани донишҷӯ хатогӣ рух дод.');
        }

    } catch (Throwable $e) {
        flash($e->getMessage());
    }

    redirect('students.php');
}

// Рӯйхати факултетҳо барои филтр
$faculties = $repository->faculties();

// Шумораи умумии донишҷӯён
$totalStudents = $repository->count(
    $search,
    $faculty,
    $course
);

// Ҳисоб кардани саҳифаҳо
$totalPages = max(1, (int) ceil($totalStudents / $limit));

$page = min($page, $totalPages);

$offset = ($page - 1) * $limit;

// Гирифтани донишҷӯён
$rows = $repository->paginate(
    $search,
    $faculty,
    $course,
    $limit,
    $offset
);

$startNumber = $totalStudents > 0 ? $offset + 1 : 0;
$endNumber = min($offset + $limit, $totalStudents);

// Сохтани URL барои пагинатсия
$queryParams = [
    'search' => $search,
    'faculty' => $faculty,
    'course' => $course ?? '',
];

$pageUrl = function (int $number) use ($queryParams): string {
    return 'students.php?' . http_build_query(
        array_merge($queryParams, ['page' => $number])
    );
};

$pageTitle = 'Донишҷӯён';

require __DIR__ . '/includes/header.php';
?>

<h1>Донишҷӯён</h1>

<div class="actions">
    <a class="btn" href="add_student.php">
        + Иловаи донишҷӯ
    </a>
</div>

<!-- Ҷустуҷӯ ва филтрҳо -->
<div class="card" style="margin-bottom: 20px; padding: 20px;">

    <form method="get" action="students.php">

        <div class="form-grid">

            <div class="form-group">
                <label for="search">Ҷустуҷӯ</label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    value="<?= e($search) ?>"
                    placeholder="Ном, рақами донишҷӯ ё гурӯҳ"
                >
            </div>

            <div class="form-group">
                <label for="faculty">Факултет</label>

                <select id="faculty" name="faculty">
                    <option value="">Ҳамаи факултетҳо</option>

                    <?php foreach ($faculties as $item): ?>
                        <option
                            value="<?= e($item) ?>"
                            <?= $faculty === $item ? 'selected' : '' ?>
                        >
                            <?= e($item) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group">
                <label for="course">Курс</label>

                <select id="course" name="course">
                    <option value="">Ҳамаи курсҳо</option>

                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option
                            value="<?= $i ?>"
                            <?= $course === $i ? 'selected' : '' ?>
                        >
                            Курси <?= $i ?>
                        </option>
                    <?php endfor; ?>

                </select>
            </div>

        </div>

        <div class="actions" style="margin-top: 15px;">
            <button type="submit" class="btn">
                Ҷустуҷӯ
            </button>

            <a href="students.php" class="btn secondary">
                Пок кардани филтрҳо
            </a>
        </div>

    </form>
</div>

<!-- Маълумоти умумӣ -->
<p>
    Ҳамагӣ: <strong><?= $totalStudents ?></strong> донишҷӯ

    <?php if ($totalStudents > 0): ?>
        | Намоиш: <?= $startNumber ?>–<?= $endNumber ?>
    <?php endif; ?>
</p>

<!-- Ҷадвали донишҷӯён -->
<div class="table-wrap">

    <table>

        <thead>
            <tr>
                <th>ID</th>
                <th>Ному насаб</th>
                <th>Рақами донишҷӯ</th>
                <th>Факултет</th>
                <th>Гурӯҳ</th>
                <th>Курс</th>
                <th>Телефон</th>
                <th>Амалҳо</th>
            </tr>
        </thead>

        <tbody>

            <?php if (empty($rows)): ?>

                <tr>
                    <td colspan="8" style="text-align: center;">
                        Донишҷӯён ёфт нашуданд.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($rows as $r): ?>

                    <tr>
                        <td><?= (int) $r['id'] ?></td>

                        <td><?= e($r['full_name']) ?></td>

                        <td><?= e($r['student_number']) ?></td>

                        <td><?= e($r['faculty']) ?></td>

                        <td><?= e($r['study_group']) ?></td>

                        <td><?= (int) $r['course'] ?></td>

                        <td><?= e($r['phone'] ?? '') ?></td>

                        <td>
                            <form
                                method="post"
                                action="students.php"
                                onsubmit="return confirm('Оё донишҷӯро нест кардан мехоҳед?')"
                            >

                                <input
                                    type="hidden"
                                    name="csrf"
                                    value="<?= e(csrf_token()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $r['id'] ?>"
                                >

                                <button type="submit" class="danger">
                                    Нест кардан
                                </button>

                            </form>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<!-- Пагинатсия -->
<?php if ($totalPages > 1): ?>

    <div class="actions" style="margin-top: 20px;">

        <?php if ($page > 1): ?>
            <a class="btn secondary" href="<?= e($pageUrl($page - 1)) ?>">
                Қаблӣ
            </a>
        <?php endif; ?>

        <span>
            Саҳифаи <?= $page ?> аз <?= $totalPages ?>
        </span>

        <?php if ($page < $totalPages): ?>
            <a class="btn" href="<?= e($pageUrl($page + 1)) ?>">
                Баъдӣ
            </a>
        <?php endif; ?>

    </div>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
