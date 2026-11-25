<?php
declare(strict_types=1);

/**
 * Repository барои CRUD ва ҷустуҷӯи ҳуҷраҳо.
 * Ҷадвалҳо: rooms, dormitories, allocations.
 */
final class RoomRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function count(
        string $search = '',
        string $status = '',
        ?int $dormitoryId = null
    ): int {
        $sql = "SELECT COUNT(*)
                FROM rooms r
                INNER JOIN dormitories d ON d.id = r.dormitory_id
                WHERE 1 = 1";

        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                r.room_number LIKE :search_room
                OR d.name LIKE :search_dormitory
            )";

            $value = '%' . $search . '%';

            $params['search_room'] = $value;
            $params['search_dormitory'] = $value;
        }

        if ($status !== '') {
            $sql .= " AND r.status = :status";
            $params['status'] = $status;
        }

        if ($dormitoryId !== null && $dormitoryId > 0) {
            $sql .= " AND r.dormitory_id = :dormitory_id";
            $params['dormitory_id'] = $dormitoryId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function paginate(
        string $search = '',
        string $status = '',
        ?int $dormitoryId = null,
        int $limit = 10,
        int $offset = 0
    ): array {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);

        $sql = "SELECT
                    r.*,
                    d.name AS dormitory_name,
                    (
                        SELECT COUNT(*)
                        FROM allocations a
                        WHERE a.room_id = r.id
                        AND a.status = 'active'
                    ) AS occupied_count
                FROM rooms r
                INNER JOIN dormitories d ON d.id = r.dormitory_id
                WHERE 1 = 1";

        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                r.room_number LIKE :search_room
                OR d.name LIKE :search_dormitory
            )";

            $value = '%' . $search . '%';

            $params['search_room'] = $value;
            $params['search_dormitory'] = $value;
        }

        if ($status !== '') {
            $sql .= " AND r.status = :status";
            $params['status'] = $status;
        }

        if ($dormitoryId !== null && $dormitoryId > 0) {
            $sql .= " AND r.dormitory_id = :dormitory_id";
            $params['dormitory_id'] = $dormitoryId;
        }

        $sql .= " ORDER BY
                    d.name,
                    r.floor,
                    r.room_number
                  LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM rooms
             WHERE id = :id
             LIMIT 1"
        );

        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function dormitories(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, name
             FROM dormitories
             ORDER BY name"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO rooms
                (dormitory_id, room_number, floor, capacity, status)
             VALUES
                (:dormitory_id, :room_number, :floor, :capacity, :status)"
        );

        $stmt->execute([
            'dormitory_id' => (int) $data['dormitory_id'],
            'room_number' => $data['room_number'],
            'floor' => (int) $data['floor'],
            'capacity' => (int) $data['capacity'],
            'status' => $data['status'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE rooms
             SET
                dormitory_id = :dormitory_id,
                room_number = :room_number,
                floor = :floor,
                capacity = :capacity,
                status = :status
             WHERE id = :id"
        );

        $stmt->execute([
            'dormitory_id' => (int) $data['dormitory_id'],
            'room_number' => $data['room_number'],
            'floor' => (int) $data['floor'],
            'capacity' => (int) $data['capacity'],
            'status' => $data['status'],
            'id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM rooms WHERE id = :id"
        );

        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
