<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\GradeModel;
use Helpers\PdfGenerator;
use Helpers\ErrorHandler;

class GradeController extends Controller
{
    /**
     * Generate and download SF9 (Form 137) - Permanent Record
     */
    public function generateSF9(): void
    {
        $user = Session::get('user');
        if (!$user) {
            ErrorHandler::unauthorized('Please login to access this page.');
            return;
        }

        $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
        $academicYear = $_GET['academic_year'] ?? null;

        // Check permissions
        if ($user['role'] === 'student') {
            // Students can only view their own SF9
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);
            $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$student) {
                ErrorHandler::notFound('Student profile not found.');
                return;
            }
            $studentId = (int)$student['id'];
        } elseif ($user['role'] === 'parent') {
            // Parents can view their child's SF9 - validate relationship
            if (!$studentId) {
                ErrorHandler::badRequest('Student ID required.');
                return;
            }
            
            // Validate parent-student relationship
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);
            
            // Check if parent is linked to this student via users table
            $stmt = $pdo->prepare("
                SELECT s.id 
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ? 
                    AND u.id = (
                        SELECT linked_student_user_id 
                        FROM users 
                        WHERE id = ? AND role = 'parent'
                    )
            ");
            $stmt->execute([$studentId, $user['id']]);
            $validStudent = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$validStudent) {
                ErrorHandler::forbidden('You do not have permission to access this student\'s records.');
                return;
            }
        } elseif (!in_array($user['role'], ['admin', 'teacher', 'adviser'])) {
            ErrorHandler::forbidden('You do not have permission to access this page.');
            return;
        }

        if (!$studentId) {
            ErrorHandler::badRequest('Student ID is required.');
            return;
        }

        try {
            $pdfGenerator = new PdfGenerator();
            $pdfContent = $pdfGenerator->generateSF9($studentId, $academicYear);

            // Set headers for PDF download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="SF9_' . $studentId . '_' . date('Y-m-d') . '.pdf"');
            header('Content-Length: ' . strlen($pdfContent));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');

            echo $pdfContent;
            exit;

        } catch (\Exception $e) {
            ErrorHandler::internalServerError('Failed to generate SF9: ' . $e->getMessage());
        }
    }

    /**
     * Generate and download SF10 (Form 138) - Report Card
     */
    public function generateSF10(): void
    {
        $user = Session::get('user');
        if (!$user) {
            ErrorHandler::unauthorized('Please login to access this page.');
            return;
        }

        $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
        $quarter = isset($_GET['quarter']) ? (int)$_GET['quarter'] : null;
        $academicYear = $_GET['academic_year'] ?? null;

        // Validate quarter
        if (!$quarter || !in_array($quarter, [1, 2, 3, 4])) {
            ErrorHandler::badRequest('Valid quarter (1-4) is required.');
            return;
        }

        // Check permissions
        if ($user['role'] === 'student') {
            // Students can only view their own SF10
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);
            $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$student) {
                ErrorHandler::notFound('Student profile not found.');
                return;
            }
            $studentId = (int)$student['id'];
        } elseif ($user['role'] === 'parent') {
            // Parents can view their child's SF10 - validate relationship
            if (!$studentId) {
                ErrorHandler::badRequest('Student ID required.');
                return;
            }
            
            // Validate parent-student relationship
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);
            
            // Check if parent is linked to this student via users table
            $stmt = $pdo->prepare("
                SELECT s.id 
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ? 
                    AND u.id = (
                        SELECT linked_student_user_id 
                        FROM users 
                        WHERE id = ? AND role = 'parent'
                    )
            ");
            $stmt->execute([$studentId, $user['id']]);
            $validStudent = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$validStudent) {
                ErrorHandler::forbidden('You do not have permission to access this student\'s records.');
                return;
            }
        } elseif (!in_array($user['role'], ['admin', 'teacher', 'adviser'])) {
            ErrorHandler::forbidden('You do not have permission to access this page.');
            return;
        }

        if (!$studentId) {
            ErrorHandler::badRequest('Student ID is required.');
            return;
        }

        try {
            $pdfGenerator = new PdfGenerator();
            $pdfContent = $pdfGenerator->generateSF10($studentId, $quarter, $academicYear);

            // Check if HTML fallback (for printing)
            if (isset($_GET['format']) && $_GET['format'] === 'html') {
                header('Content-Type: text/html; charset=utf-8');
                echo $pdfContent;
                exit;
            }

            // Set headers for PDF download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="SF10_' . $studentId . '_Q' . $quarter . '_' . date('Y-m-d') . '.pdf"');
            header('Content-Length: ' . strlen($pdfContent));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');

            echo $pdfContent;
            exit;

        } catch (\Exception $e) {
            ErrorHandler::internalServerError('Failed to generate SF10: ' . $e->getMessage());
        }
    }

    /**
     * View SF9/SF10 in browser (for printing)
     */
    public function viewSF9(): void
    {
        $user = Session::get('user');
        if (!$user) {
            ErrorHandler::unauthorized('Please login to access this page.');
            return;
        }

        $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
        $academicYear = $_GET['academic_year'] ?? null;

        // Permission checks (same as generateSF9)
        if ($user['role'] === 'student') {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);
            $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$student) {
                ErrorHandler::notFound('Student profile not found.');
                return;
            }
            $studentId = (int)$student['id'];
        } elseif ($user['role'] === 'parent') {
            // Validate parent-student relationship
            if (!$studentId) {
                ErrorHandler::badRequest('Student ID required.');
                return;
            }
            
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);
            $stmt = $pdo->prepare("
                SELECT s.id 
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ? 
                    AND u.id = (
                        SELECT linked_student_user_id 
                        FROM users 
                        WHERE id = ? AND role = 'parent'
                    )
            ");
            $stmt->execute([$studentId, $user['id']]);
            $validStudent = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$validStudent) {
                ErrorHandler::forbidden('You do not have permission to access this student\'s records.');
                return;
            }
        } elseif (!in_array($user['role'], ['admin', 'teacher', 'adviser'])) {
            ErrorHandler::forbidden('You do not have permission to access this page.');
            return;
        }

        if (!$studentId) {
            ErrorHandler::badRequest('Student ID is required.');
            return;
        }

        try {
            $pdfGenerator = new PdfGenerator();
            $content = $pdfGenerator->generateSF9($studentId, $academicYear);

            // If TCPDF was used, output as PDF inline
            if (class_exists('TCPDF')) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="SF9_' . $studentId . '.pdf"');
                echo $content;
            } else {
                // HTML fallback
                header('Content-Type: text/html; charset=utf-8');
                echo $content;
            }
            exit;

        } catch (\Exception $e) {
            ErrorHandler::internalServerError('Failed to generate SF9: ' . $e->getMessage());
        }
    }

    /**
     * View SF10 in browser (for printing)
     */
    public function viewSF10(): void
    {
        $user = Session::get('user');
        if (!$user) {
            ErrorHandler::unauthorized('Please login to access this page.');
            return;
        }

        $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
        $quarter = isset($_GET['quarter']) ? (int)$_GET['quarter'] : null;
        $academicYear = $_GET['academic_year'] ?? null;

        if (!$quarter || !in_array($quarter, [1, 2, 3, 4])) {
            ErrorHandler::badRequest('Valid quarter (1-4) is required.');
            return;
        }

        // Permission checks (same as generateSF10)
        if ($user['role'] === 'student') {
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);
            $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$student) {
                ErrorHandler::notFound('Student profile not found.');
                return;
            }
            $studentId = (int)$student['id'];
        } elseif ($user['role'] === 'parent') {
            // Validate parent-student relationship
            if (!$studentId) {
                ErrorHandler::badRequest('Student ID required.');
                return;
            }
            
            $config = require BASE_PATH . '/config/config.php';
            $pdo = \Core\Database::connection($config['database']);
            $stmt = $pdo->prepare("
                SELECT s.id 
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ? 
                    AND u.id = (
                        SELECT linked_student_user_id 
                        FROM users 
                        WHERE id = ? AND role = 'parent'
                    )
            ");
            $stmt->execute([$studentId, $user['id']]);
            $validStudent = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$validStudent) {
                ErrorHandler::forbidden('You do not have permission to access this student\'s records.');
                return;
            }
        } elseif (!in_array($user['role'], ['admin', 'teacher', 'adviser'])) {
            ErrorHandler::forbidden('You do not have permission to access this page.');
            return;
        }

        if (!$studentId) {
            ErrorHandler::badRequest('Student ID is required.');
            return;
        }

        try {
            $pdfGenerator = new PdfGenerator();
            $content = $pdfGenerator->generateSF10($studentId, $quarter, $academicYear);

            // If TCPDF was used, output as PDF inline
            if (class_exists('TCPDF')) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="SF10_' . $studentId . '_Q' . $quarter . '.pdf"');
                echo $content;
            } else {
                // HTML fallback
                header('Content-Type: text/html; charset=utf-8');
                echo $content;
            }
            exit;

        } catch (\Exception $e) {
            ErrorHandler::internalServerError('Failed to generate SF10: ' . $e->getMessage());
        }
    }
}

