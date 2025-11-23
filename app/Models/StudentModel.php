<?php
declare(strict_types=1);

namespace Models;

use PDO;

class StudentModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getProfileByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM student_profiles WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listClassmatesBySectionId(int $sectionId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT s.id as student_id, u.name as full_name, s.lrn, s.grade_level, u.email
            FROM students s
            JOIN users u ON s.user_id = u.id
            WHERE s.section_id = ?
            ORDER BY u.name
        ');
        $stmt->execute([$sectionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignStudentToSection(int $studentId, int $sectionId): void
    {
        $stmt = $this->pdo->prepare('UPDATE students SET section_id = ? WHERE id = ?');
        $stmt->execute([$sectionId, $studentId]);
    }
}


