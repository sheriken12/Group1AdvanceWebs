<?php

class BaseModel
{
    protected $pdo;
    protected string $table = '';
    protected array $allowedFields = [];

    public function __construct()
    {
        global $pdo;
        if (isset($pdo) && $pdo instanceof PDO) {
            $this->pdo = $pdo;
            return;
        }

        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            return;
        }

        throw new RuntimeException('No PDO available. Ensure admin/db.php defines a $pdo or DB_* constants.');
    }

    protected function filterData(array $data): array
    {
        if (empty($this->allowedFields)) return $data;
        return array_intersect_key($data, array_flip($this->allowedFields));
    }

    public function getAll(?int $limit = null, ?int $offset = null): array
    {
        if (empty($this->table)) return [];
        if ($limit === null) {
            $stmt = $this->pdo->query('SELECT * FROM ' . $this->table . ' ORDER BY id DESC');
            return $stmt->fetchAll();
        }

        $sql = 'SELECT * FROM ' . $this->table . ' ORDER BY id DESC LIMIT :limit';
        if ($offset !== null && $offset > 0) {
            $sql .= ' OFFSET :offset';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        if ($offset !== null && $offset > 0) {
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll(): int
    {
        if (empty($this->table)) return 0;
        $stmt = $this->pdo->query('SELECT COUNT(*) AS cnt FROM ' . $this->table);
        $row = $stmt->fetch();
        return (int) ($row['cnt'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        if (empty($this->table)) return null;
        $stmt = $this->pdo->prepare('SELECT * FROM ' . $this->table . ' WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function add(array $data): int
    {
        $data = $this->filterData($data);
        if (empty($data) || empty($this->table)) return 0;
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);
        $sql = 'INSERT INTO ' . $this->table . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->pdo->prepare($sql);
        $params = [];
        foreach ($data as $k => $v) $params[':' . $k] = $v;
        $stmt->execute($params);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->filterData($data);
        if (empty($data) || empty($this->table)) return false;
        $sets = [];
        foreach (array_keys($data) as $col) $sets[] = $col . ' = :' . $col;
        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $params = [];
        foreach ($data as $k => $v) $params[':' . $k] = $v;
        $params[':id'] = $id;
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        if (empty($this->table)) return false;
        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->table . ' WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function toggleStatus(int $id): bool
    {
        $row = $this->getById($id);
        if (!$row || !isset($row['status'])) return false;
        $new = ($row['status'] === 'Active') ? 'Inactive' : 'Active';
        $stmt = $this->pdo->prepare('UPDATE ' . $this->table . ' SET status = :status WHERE id = :id');
        return $stmt->execute([':status' => $new, ':id' => $id]);
    }
}
