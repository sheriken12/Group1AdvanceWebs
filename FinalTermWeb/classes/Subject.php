<?php

require_once __DIR__ . '/BaseModel.php';

class Subject extends BaseModel
{
    protected string $table = 'subjects';
    protected array $allowedFields = ['user_id','code','name','teacher','units','schedule','status'];

    private function userId(): int {
        return (int) ($_SESSION['user']['id'] ?? 0);
    }

    public function getAll(?int $limit = null, ?int $offset = null): array {
        $uid = $this->userId();
        $sql = 'SELECT * FROM subjects WHERE user_id = :uid ORDER BY id ASC';
        if ($limit !== null) {
            $sql .= ' LIMIT :limit';
            if ($offset !== null && $offset > 0) $sql .= ' OFFSET :offset';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            if ($offset !== null && $offset > 0)
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll(): int {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS cnt FROM subjects WHERE user_id = ?');
        $stmt->execute([$this->userId()]);
        return (int) ($stmt->fetch()['cnt'] ?? 0);
    }

    public function add(array $data): int {
        $data['user_id'] = $this->userId();
        return parent::add($data);
    }

    public function stats(): array {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(units),0) AS total_units FROM subjects WHERE user_id = ?');
        $stmt->execute([$this->userId()]);
        $row = $stmt->fetch();
        return [
            'total'       => (int) ($row['total']      ?? 0),
            'total_units' => (int) ($row['total_units'] ?? 0),
        ];
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare('DELETE FROM subjects WHERE id = ? AND user_id = ?');
        return $stmt->execute([$id, $this->userId()]);
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM subjects WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $this->userId()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}