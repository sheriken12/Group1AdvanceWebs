<?php

require_once __DIR__ . '/BaseModel.php';

class Grade extends BaseModel
{
    protected string $table = 'grades';
    protected array $allowedFields = ['user_id','subject','prelim','midterm','final','grade','remarks','status'];

    private function userId(): int {
        return (int) ($_SESSION['user']['id'] ?? 0);
    }

    public function getAll(?int $limit = null, ?int $offset = null): array {
        $uid = $this->userId();
        $sql = 'SELECT * FROM grades WHERE user_id = :uid ORDER BY id ASC';
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
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS cnt FROM grades WHERE user_id = ?');
        $stmt->execute([$this->userId()]);
        return (int) ($stmt->fetch()['cnt'] ?? 0);
    }

    public function add(array $data): int {
        $data['user_id'] = $this->userId();
        return parent::add($data);
    }

    public function stats(): array {
        $stmt = $this->pdo->prepare('SELECT AVG(grade) AS avg_grade, MAX(grade) AS highest, MIN(grade) AS lowest FROM grades WHERE user_id = ?');
        $stmt->execute([$this->userId()]);
        $row = $stmt->fetch();
        return [
            'avg_grade' => isset($row['avg_grade']) ? (float)$row['avg_grade'] : 0,
            'highest'   => isset($row['highest'])   ? (int)$row['highest']     : 0,
            'lowest'    => isset($row['lowest'])     ? (int)$row['lowest']      : 0,
        ];
    }

    public function existsBySubject(string $subject): bool {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM grades WHERE subject = ? AND user_id = ?');
        $stmt->execute([$subject, $this->userId()]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function deleteBySubject(string $subject): void {
        $stmt = $this->pdo->prepare('DELETE FROM grades WHERE subject = ? AND user_id = ?');
        $stmt->execute([$subject, $this->userId()]);
    }
}