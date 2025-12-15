<?php
declare(strict_types=1);

/**
 * Teacher Section/Subject Assignment API
 * Returns grouped sections with the subjects assigned to the authenticated teacher.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$config = require __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Core/Database.php';
require_once __DIR__ . '/../../app/Core/Session.php';

use Core\Database;
use Core\Session;

try {
    Session::start($config['session'] ?? []);
    $user = Session::get('user');

    if (!$user || !in_array($user['role'] ?? '', ['teacher', 'adviser'], true)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. Teacher privileges required.'
        ]);
        exit();
    }

    $pdo = Database::connection($config['database']);
    $teacherId = resolveTeacherId($pdo, (int)$user['id']);

    if ($teacherId === null) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Teacher record not found for this account.'
        ]);
        exit();
    }

    $sections = fetchTeacherSectionsWithSubjects($pdo, $teacherId, (int)$user['id']);

    echo json_encode([
        'success' => true,
        'sections' => $sections,
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

function resolveTeacherId(PDO $pdo, int $userId): ?int
{
    $stmt = $pdo->prepare('SELECT id FROM teachers WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $teacherId = $stmt->fetchColumn();

    if ($teacherId === false) {
        return null;
    }

    return (int)$teacherId;
}

function fetchTeacherSectionsWithSubjects(PDO $pdo, int $teacherId, int $teacherUserId): array
{
    $sql = '
        SELECT 
            sec.id AS section_id,
            sec.name AS section_name,
            sec.grade_level,
            sec.room AS section_room,
            sec.adviser_id AS adviser_user_id,
            (SELECT COUNT(*) FROM students st WHERE st.section_id = sec.id) AS student_count,
            c.id AS class_id,
            c.subject_id,
            sub.name AS subject_name,
            sub.code AS subject_code,
            c.schedule,
            c.room AS class_room,
            ts.day_of_week,
            ts.start_time,
            ts.end_time
        FROM classes c
        JOIN sections sec ON c.section_id = sec.id
        JOIN subjects sub ON c.subject_id = sub.id
        LEFT JOIN teacher_schedules ts 
            ON ts.class_id = c.id 
            AND ts.teacher_id = c.teacher_id
        WHERE c.teacher_id = ?
          AND c.is_active = 1
        ORDER BY sec.grade_level, sec.name, sub.name, ts.day_of_week, ts.start_time
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$teacherId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $sections = [];

    foreach ($rows as $row) {
        $sectionId = (int)$row['section_id'];
        if (!isset($sections[$sectionId])) {
            $sections[$sectionId] = [
                'section_id' => $sectionId,
                'section_name' => $row['section_name'],
                'grade_level' => (int)$row['grade_level'],
                'room' => $row['section_room'],
                'student_count' => isset($row['student_count']) ? (int)$row['student_count'] : 0,
                'is_adviser' => (int)($row['adviser_user_id'] ?? 0) === $teacherUserId,
                'subjects' => [],
            ];
        }

        if (!empty($row['subject_id'])) {
            $subjectKey = $row['class_id'] . ':' . $row['subject_id'];
            if (!isset($sections[$sectionId]['subjects'][$subjectKey])) {
                $sections[$sectionId]['subjects'][$subjectKey] = [
                    'class_id' => (int)$row['class_id'],
                    'subject_id' => (int)$row['subject_id'],
                    'subject_name' => $row['subject_name'],
                    'subject_code' => $row['subject_code'],
                    'schedule' => $row['schedule'],
                    'room' => $row['class_room'] ?? $row['section_room'],
                    'timeblocks' => [],
                ];
            }

            if (!empty($row['day_of_week'])) {
                $sections[$sectionId]['subjects'][$subjectKey]['timeblocks'][] = [
                    'day' => $row['day_of_week'],
                    'start' => $row['start_time'],
                    'end' => $row['end_time'],
                ];
            }
        }
    }

    // Extend sections array with adviser-only sections lacking classes
    $advisorSql = '
        SELECT 
            sec.id AS section_id,
            sec.name AS section_name,
            sec.grade_level,
            sec.room AS section_room,
            (SELECT COUNT(*) FROM students st WHERE st.section_id = sec.id) AS student_count
        FROM sections sec
        WHERE sec.adviser_id = ?
          AND sec.is_active = 1
          AND sec.id NOT IN (
              SELECT DISTINCT c.section_id
              FROM classes c
              WHERE c.teacher_id = ?
                AND c.is_active = 1
          )
        ORDER BY sec.grade_level, sec.name
    ';

    $advisorStmt = $pdo->prepare($advisorSql);
    $advisorStmt->execute([$teacherUserId, $teacherId]);
    $advisorySections = $advisorStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($advisorySections as $row) {
        $sectionId = (int)$row['section_id'];
        if (!isset($sections[$sectionId])) {
            $sections[$sectionId] = [
                'section_id' => $sectionId,
                'section_name' => $row['section_name'],
                'grade_level' => (int)$row['grade_level'],
                'room' => $row['section_room'],
                'student_count' => isset($row['student_count']) ? (int)$row['student_count'] : 0,
                'is_adviser' => true,
                'subjects' => [],
            ];
        } else {
            $sections[$sectionId]['is_adviser'] = true;
        }
    }

    // Normalize subjects arrays and set adviser flags
    foreach ($sections as &$section) {
        if (isset($section['subjects']) && is_array($section['subjects'])) {
            $section['subjects'] = array_values($section['subjects']);
        } else {
            $section['subjects'] = [];
        }

        if (!isset($section['is_adviser'])) {
            $section['is_adviser'] = false;
        }
    }
    unset($section);

    return array_values($sections);
}

