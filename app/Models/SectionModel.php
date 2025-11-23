<?php
declare(strict_types=1);

namespace Models;

use PDO;

class SectionModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listSectionsWithCapacity(?int $adminUserId = null, ?string $schoolYear = null): array
    {
        $query = '
            SELECT 
                s.id,
                s.name,
                s.grade_level,
                s.room,
                s.max_students,
                s.school_year,
                s.is_active,
                s.created_by,
                COUNT(st.id) as enrolled_students,
                CASE 
                    WHEN COUNT(st.id) >= s.max_students THEN "full"
                    WHEN COUNT(st.id) >= s.max_students * 0.8 THEN "nearly_full"
                    ELSE "available"
                END as status,
                adv.name as adviser_name,
                adv.email as adviser_email
            FROM sections s
            LEFT JOIN students st ON st.section_id = s.id
            LEFT JOIN users adv ON s.adviser_id = adv.id
            WHERE 1=1';

        $params = [];
        if ($adminUserId !== null) {
            $query .= ' AND (s.created_by = ? OR s.created_by IS NULL)';
            $params[] = $adminUserId;
        }
        if ($schoolYear !== null) {
            $query .= ' AND s.school_year = ?';
            $params[] = $schoolYear;
        }
        $query .= ' GROUP BY s.id, s.name, s.grade_level, s.room, s.max_students, s.school_year, s.is_active, s.created_by, adv.name, adv.email
                    ORDER BY s.grade_level, s.name';

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createSection(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO sections (name, grade_level, room, max_students, school_year, description, adviser_id, is_active, created_by)
            VALUES (:name, :grade_level, :room, :max_students, :school_year, :description, :adviser_id, 1, :created_by)
        ');
        $stmt->execute([
            'name' => $data['name'],
            'grade_level' => (int)$data['grade_level'],
            'room' => $data['room'] ?? null,
            'max_students' => (int)$data['max_students'],
            'school_year' => $data['school_year'] ?? '2025-2026',
            'description' => $data['description'] ?? null,
            'adviser_id' => isset($data['adviser_id']) && !empty($data['adviser_id']) ? (int)$data['adviser_id'] : null,
            'created_by' => $data['created_by'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateSection(int $sectionId, array $data): void
    {
        $fields = [];
        $params = [];
        if (array_key_exists('max_students', $data)) {
            $fields[] = 'max_students = ?';
            $params[] = (int)$data['max_students'];
        }
        if (array_key_exists('room', $data)) {
            $fields[] = 'room = ?';
            $params[] = $data['room'];
        }
        if (array_key_exists('description', $data)) {
            $fields[] = 'description = ?';
            $params[] = $data['description'];
        }
        if (!$fields) {
            return;
        }
        $params[] = $sectionId;
        $stmt = $this->pdo->prepare('UPDATE sections SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);
    }

    public function getSectionCapacity(int $sectionId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                s.id as section_id,
                s.name as section_name,
                s.max_students,
                COUNT(st.id) as current_students,
                (s.max_students - COUNT(st.id)) as available_slots
            FROM sections s
            LEFT JOIN students st ON s.id = st.section_id
            WHERE s.id = ?
            GROUP BY s.id, s.name, s.max_students
        ');
        $stmt->execute([$sectionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}


