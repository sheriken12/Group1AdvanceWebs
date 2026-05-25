<?php

require_once __DIR__ . '/BaseModel.php';

class Grade extends BaseModel
{
    protected string $table = 'grades';
    protected array $allowedFields = ['subject','prelim','midterm','final','grade','remarks','status'];

    public function stats(): array
    {
        $stmt = $this->pdo->query('SELECT AVG(grade) AS avg_grade, MAX(grade) AS highest, MIN(grade) AS lowest FROM grades');
        $row = $stmt->fetch();
        return [
            'avg_grade' => isset($row['avg_grade']) ? (float)$row['avg_grade'] : 0,
            'highest' => isset($row['highest']) ? (int)$row['highest'] : 0,
            'lowest' => isset($row['lowest']) ? (int)$row['lowest'] : 0,
        ];
    }
}
