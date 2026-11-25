<?php
declare(strict_types=1);

/**
 * Repository барои идоракунии донишҷӯён.
 * Ҷадвалҳо: students, users.
 */
final class StudentRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Шумораи донишҷӯён бо ҷустуҷӯ ва филтр.
     */
    public function count(
        string $search = '',
        string $faculty = '',
        ?int $course = null
    ): int {
        $sql = "SELECT COUNT(*)
                FROM students s
                INNER JOIN users u ON u.id = s.user_id
                WHERE 1 = 1";

        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                u.full_name LIKE :search_name
                OR s.student_number LIKE :search_number
                OR s.study_group LIKE :search_group
            )";

            $value = '%' . $search . '%';

            $params['search_name'] = $value;
            $params['search_number'] = $value;
            $params['search_group'] = $value;
        }

        if ($faculty !== '') {
            $sql .= " AND s.faculty = :faculty";
            $params['faculty'] = $faculty;
        }

        if ($course !== null && $course >= 1 && $course <= 6) {
            $sql .= " AND s.course = :course";
            $params['course'] = $course;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Рӯйхати донишҷӯён бо LIMIT ва OFFSET.
     */
    public function paginate(
        string $search = '',
        string $faculty = '',
        ?int $course = null,
        int $limit = 10,
        int $offset = 0
    ): array {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);

        $sql = "SELECT
                    s.id,
                    s.user_id,
                    s.student_number,
                    s.faculty,
                    s.study_group,
                    s.course,
                    s.birth_date,
                    u.full_name,
                    u.username,
                    u.email,
                    u.phone
                FROM students s
                INNER JOIN users u ON u.id = s.user_id
                WHERE 1 = 1";

        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                u.full_name LIKE :search_name
                OR s.student_number LIKE :search_number
                OR s.study_group LIKE :search_group
            )";

            $value = '%' . $search . '%';

            $params['search_name'] = $value;
            $params['search_number'] = $value;
            $params['search_group'] = $value;
        }

        if ($faculty !== '') {
            $sql .= " AND s.faculty = :faculty";
            $params['faculty'] = $faculty;
        }

        if ($course !== null && $course >= 1 && $course <= 6) {
            $sql .= " AND s.course = :course";
            $params['course'] = $course;
        }

        $sql .= " ORDER BY s.id DESC LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ҷустуҷӯи як донишҷӯ аз рӯи ID.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                s.*,
                u.full_name,
                u.username,
                u.email,
                u.phone
             FROM students s
             INNER JOIN users u ON u.id = s.user_id
             WHERE s.id = :id
             LIMIT 1"
        );

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Рӯйхати факултетҳо.
     */
    public function faculties(): array
    {
        $stmt = $this->pdo->query(
            "SELECT DISTINCT faculty
             FROM students
             WHERE faculty IS NOT NULL
             AND faculty <> ''
             ORDER BY faculty"
        );

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Эҷоди донишҷӯ ва ҳисоби корбар.
     */
    public function create(array $data): int
    {
        $this->pdo->beginTransaction();

        try {
            $user = $this->pdo->prepare(
                "INSERT INTO users
                    (full_name, username, email, password_hash, role, phone)
                 VALUES
                    (:full_name, :username, :email, :password_hash, 'student', :phone)"
            );

            $user->execute([
                'full_name' => $data['full_name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password_hash' => password_hash(
                    $data['password'],
                    PASSWORD_DEFAULT
                ),
                'phone' => $data['phone'] !== ''
                    ? $data['phone']
                    : null,
            ]);

            $userId = (int) $this->pdo->lastInsertId();

            $student = $this->pdo->prepare(
                "INSERT INTO students
                    (
                        user_id,
                        student_number,
                        faculty,
                        study_group,
                        course,
                        birth_date
                    )
                 VALUES
                    (
                        :user_id,
                        :student_number,
                        :faculty,
                        :study_group,
                        :course,
                        :birth_date
                    )"
            );

            $student->execute([
                'user_id' => $userId,
                'student_number' => $data['student_number'],
                'faculty' => $data['faculty'],
                'study_group' => $data['study_group'],
                'course' => (int) $data['course'],
                'birth_date' => $data['birth_date'] !== ''
                    ? $data['birth_date']
                    : null,
            ]);

            $studentId = (int) $this->pdo->lastInsertId();

            $this->pdo->commit();

            return $studentId;

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Тағйир додани маълумоти донишҷӯ.
     */
    public function update(int $id, array $data): bool
    {
        $student = $this->find($id);

        if (!$student) {
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            $user = $this->pdo->prepare(
                "UPDATE users
                 SET
                    full_name = :full_name,
                    username = :username,
                    email = :email,
                    phone = :phone
                 WHERE id = :user_id"
            );

            $user->execute([
                'full_name' => $data['full_name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'phone' => $data['phone'] !== ''
                    ? $data['phone']
                    : null,
                'user_id' => $student['user_id'],
            ]);

            if (!empty($data['password'])) {
                $password = $this->pdo->prepare(
                    "UPDATE users
                     SET password_hash = :password_hash
                     WHERE id = :user_id"
                );

                $password->execute([
                    'password_hash' => password_hash(
                        $data['password'],
                        PASSWORD_DEFAULT
                    ),
                    'user_id' => $student['user_id'],
                ]);
            }

            $stmt = $this->pdo->prepare(
                "UPDATE students
                 SET
                    student_number = :student_number,
                    faculty = :faculty,
                    study_group = :study_group,
                    course = :course,
                    birth_date = :birth_date
                 WHERE id = :id"
            );

            $stmt->execute([
                'student_number' => $data['student_number'],
                'faculty' => $data['faculty'],
                'study_group' => $data['study_group'],
                'course' => (int) $data['course'],
                'birth_date' => $data['birth_date'] !== ''
                    ? $data['birth_date']
                    : null,
                'id' => $id,
            ]);

            $this->pdo->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Нест кардани донишҷӯ.
     * Агар сабтҳои вобаста дошта бошад, FK хатогиро бозмедорад.
     */
    public function delete(int $id): bool
    {
        $student = $this->find($id);

        if (!$student) {
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM students WHERE id = :id"
            );

            $stmt->execute(['id' => $id]);

            $user = $this->pdo->prepare(
                "DELETE FROM users WHERE id = :id"
            );

            $user->execute([
                'id' => $student['user_id']
            ]);

            $this->pdo->commit();

            return true;

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}
