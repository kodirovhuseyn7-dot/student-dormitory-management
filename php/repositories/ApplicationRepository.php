<?php
declare(strict_types=1);

/**
 * Repository барои аризаҳо: рӯйхат, эҷод, хондан, баррасӣ ва несткунӣ.
 * Ҷадвалҳо: applications, students, users, dormitories.
 */
final class ApplicationRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function count(string $status = '', ?int $dormitoryId = null): int
    {
        $sql = "SELECT COUNT(*) FROM applications a WHERE 1 = 1";
        $params = [];

        if ($status !== '') {
            $sql .= " AND a.status = :status";
            $params['status'] = $status;
        }
        if ($dormitoryId !== null && $dormitoryId > 0) {
            $sql .= " AND a.dormitory_id = :dormitory_id";
            $params['dormitory_id'] = $dormitoryId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function paginate(
        string $status = '',
        ?int $dormitoryId = null,
        int $limit = 10,
        int $offset = 0
    ): array {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);

        $sql = "SELECT a.*, s.student_number, s.faculty, s.study_group,
                       u.full_name, u.email, d.name AS dormitory_name,
                       reviewer.full_name AS reviewer_name
                FROM applications a
                INNER JOIN students s ON s.id = a.student_id
                INNER JOIN users u ON u.id = s.user_id
                INNER JOIN dormitories d ON d.id = a.dormitory_id
                LEFT JOIN users reviewer ON reviewer.id = a.reviewed_by
                WHERE 1 = 1";
        $params = [];

        if ($status !== '') {
            $sql .= " AND a.status = :status";
            $params['status'] = $status;
        }
        if ($dormitoryId !== null && $dormitoryId > 0) {
            $sql .= " AND a.dormitory_id = :dormitory_id";
            $params['dormitory_id'] = $dormitoryId;
        }

        $sql .= " ORDER BY a.submitted_at DESC, a.id DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, s.student_number, u.full_name, d.name AS dormitory_name
             FROM applications a
             INNER JOIN students s ON s.id = a.student_id
             INNER JOIN users u ON u.id = s.user_id
             INNER JOIN dormitories d ON d.id = a.dormitory_id
             WHERE a.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO applications (student_id, dormitory_id, status, comment)
             VALUES (:student_id, :dormitory_id, 'pending', :comment)"
        );
        $stmt->execute([
            'student_id' => (int) $data['student_id'],
            'dormitory_id' => (int) $data['dormitory_id'],
            'comment' => $data['comment'] !== '' ? $data['comment'] : null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function review(int $id, string $status, ?int $reviewedBy, string $comment = ''): bool
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Статуси баррасӣ нодуруст аст.');
        }

        $stmt = $this->pdo->prepare(
            "UPDATE applications
             SET status = :status, reviewed_by = :reviewed_by,
                 reviewed_at = CURRENT_TIMESTAMP, comment = :comment
             WHERE id = :id"
        );
        $stmt->execute([
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'comment' => $comment !== '' ? $comment : null,
            'id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM applications WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
