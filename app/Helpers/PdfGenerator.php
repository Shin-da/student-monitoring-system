<?php
declare(strict_types=1);

namespace Helpers;

/**
 * PDF Generator Helper for SF9 and SF10 forms
 * Supports TCPDF (recommended) or HTML fallback
 */
class PdfGenerator
{
    private $pdf = null;
    private bool $useTcpdf = false;

    public function __construct()
    {
        // Check if TCPDF is available
        if (class_exists('TCPDF')) {
            $this->useTcpdf = true;
            /** @var \TCPDF $pdf */
            $this->pdf = new \TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
            $this->pdf->SetCreator('Student Monitoring System');
            $this->pdf->SetAuthor('Student Monitoring System');
            $this->pdf->SetTitle('DepEd Form');
            $this->pdf->SetMargins(10, 10, 10);
            $this->pdf->SetAutoPageBreak(true, 10);
            $this->pdf->SetFont('helvetica', '', 10);
        }
    }

    /**
     * Generate SF9 (Form 137) - Permanent Record
     */
    public function generateSF9(int $studentId, string $academicYear = null): string
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
        
        $gradeModel = new \Models\GradeModel();
        $config = require BASE_PATH . '/config/config.php';
        $pdo = \Core\Database::connection($config['database']);

        // Get student information
        $stmt = $pdo->prepare("
            SELECT s.*, u.name, u.email, sec.name AS section_name, sec.grade_level
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN sections sec ON s.section_id = sec.id
            WHERE s.id = ?
        ");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$student) {
            throw new \Exception('Student not found');
        }

        $academicYear = $academicYear ?? $gradeModel->getCurrentAcademicYear();

        // Get all subjects
        $stmt = $pdo->prepare("
            SELECT id, name, code, grade_level
            FROM subjects
            WHERE grade_level = ?
            ORDER BY name
        ");
        $stmt->execute([$student['grade_level']]);
        $subjects = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get grades for all quarters
        $allGrades = [];
        foreach ($subjects as $subject) {
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $grade = $gradeModel->calculateQuarterlyGrade(
                    $studentId,
                    (int)$subject['id'],
                    $quarter,
                    $academicYear
                );
                if ($grade) {
                    $allGrades[$subject['id']][$quarter] = $grade;
                }
            }
        }

        if ($this->useTcpdf) {
            return $this->generateSF9TCPDF($student, $subjects, $allGrades, $academicYear);
        } else {
            return $this->generateSF9HTML($student, $subjects, $allGrades, $academicYear);
        }
    }

    /**
     * Generate SF10 (Form 138) - Report Card
     */
    public function generateSF10(int $studentId, int $quarter, string $academicYear = null): string
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
        
        $gradeModel = new \Models\GradeModel();
        $config = require BASE_PATH . '/config/config.php';
        $pdo = \Core\Database::connection($config['database']);

        // Get student information
        $stmt = $pdo->prepare("
            SELECT s.*, u.name, u.email, sec.name AS section_name, sec.grade_level
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN sections sec ON s.section_id = sec.id
            WHERE s.id = ?
        ");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$student) {
            throw new \Exception('Student not found');
        }

        $academicYear = $academicYear ?? $gradeModel->getCurrentAcademicYear();

        // Get all subjects
        $stmt = $pdo->prepare("
            SELECT id, name, code, grade_level
            FROM subjects
            WHERE grade_level = ?
            ORDER BY name
        ");
        $stmt->execute([$student['grade_level']]);
        $subjects = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get grades for the specified quarter
        $quarterGrades = [];
        foreach ($subjects as $subject) {
            $grade = $gradeModel->calculateQuarterlyGrade(
                $studentId,
                (int)$subject['id'],
                $quarter,
                $academicYear
            );
            if ($grade) {
                $quarterGrades[$subject['id']] = $grade;
            }
        }

        if ($this->useTcpdf) {
            return $this->generateSF10TCPDF($student, $subjects, $quarterGrades, $quarter, $academicYear);
        } else {
            return $this->generateSF10HTML($student, $subjects, $quarterGrades, $quarter, $academicYear);
        }
    }

    /**
     * Generate SF9 using TCPDF
     */
    private function generateSF9TCPDF(array $student, array $subjects, array $allGrades, string $academicYear): string
    {
        $this->pdf->AddPage();
        
        // Header
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'DEPARTMENT OF EDUCATION', 0, 1, 'C');
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'PERMANENT RECORD (SF9)', 0, 1, 'C');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Ln(5);

        // Student Information
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(40, 6, 'Name:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $student['name'], 0, 1);
        
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(40, 6, 'LRN:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $student['lrn'] ?? 'N/A', 0, 1);
        
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(40, 6, 'Grade Level:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, 'Grade ' . $student['grade_level'], 0, 1);
        
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(40, 6, 'Section:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $student['section_name'] ?? 'N/A', 0, 1);
        
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(40, 6, 'School Year:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $academicYear, 0, 1);
        
        $this->pdf->Ln(5);

        // Grades Table
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->Cell(60, 8, 'Subject', 1, 0, 'C');
        $this->pdf->Cell(30, 8, '1st Qtr', 1, 0, 'C');
        $this->pdf->Cell(30, 8, '2nd Qtr', 1, 0, 'C');
        $this->pdf->Cell(30, 8, '3rd Qtr', 1, 0, 'C');
        $this->pdf->Cell(30, 8, '4th Qtr', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Final', 1, 1, 'C');

        $this->pdf->SetFont('helvetica', '', 9);
        foreach ($subjects as $subject) {
            $this->pdf->Cell(60, 7, $subject['name'], 1, 0);
            
            $finalSum = 0;
            $quarterCount = 0;
            
            for ($q = 1; $q <= 4; $q++) {
                $grade = $allGrades[$subject['id']][$q] ?? null;
                $gradeValue = $grade ? number_format((float)($grade['final_grade'] ?? 0), 2) : '-';
                $this->pdf->Cell(30, 7, $gradeValue, 1, 0, 'C');
                
                if ($grade) {
                    $finalSum += (float)($grade['final_grade'] ?? 0);
                    $quarterCount++;
                }
            }
            
            $finalGrade = $quarterCount > 0 ? round($finalSum / $quarterCount, 2) : '-';
            $this->pdf->Cell(30, 7, $finalGrade !== '-' ? number_format((float)$finalGrade, 2) : '-', 1, 1, 'C');
        }

        // Output PDF
        return $this->pdf->Output('', 'S');
    }

    /**
     * Generate SF9 as HTML (fallback)
     */
    private function generateSF9HTML(array $student, array $subjects, array $allGrades, string $academicYear): string
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>SF9 - Permanent Record</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h1 { margin: 5px 0; font-size: 14px; }
                .header h2 { margin: 5px 0; font-size: 12px; font-weight: bold; }
                .student-info { margin-bottom: 20px; }
                .student-info p { margin: 5px 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #000; padding: 8px; text-align: center; }
                th { background-color: #f0f0f0; font-weight: bold; }
                .subject-col { text-align: left; }
                @media print {
                    body { margin: 0; }
                    @page { margin: 1cm; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>DEPARTMENT OF EDUCATION</h1>
                <h2>PERMANENT RECORD (SF9)</h2>
            </div>
            
            <div class="student-info">
                <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
                <p><strong>LRN:</strong> <?= htmlspecialchars($student['lrn'] ?? 'N/A') ?></p>
                <p><strong>Grade Level:</strong> Grade <?= htmlspecialchars((string)($student['grade_level'] ?? '')) ?></p>
                <p><strong>Section:</strong> <?= htmlspecialchars($student['section_name'] ?? 'N/A') ?></p>
                <p><strong>School Year:</strong> <?= htmlspecialchars($academicYear) ?></p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th class="subject-col">Subject</th>
                        <th>1st Qtr</th>
                        <th>2nd Qtr</th>
                        <th>3rd Qtr</th>
                        <th>4th Qtr</th>
                        <th>Final</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): 
                        $finalSum = 0;
                        $quarterCount = 0;
                    ?>
                    <tr>
                        <td class="subject-col"><?= htmlspecialchars($subject['name']) ?></td>
                        <?php for ($q = 1; $q <= 4; $q++): 
                            $grade = $allGrades[$subject['id']][$q] ?? null;
                            $gradeValue = $grade ? number_format((float)($grade['final_grade'] ?? 0), 2) : '-';
                            if ($grade) {
                                $finalSum += (float)($grade['final_grade'] ?? 0);
                                $quarterCount++;
                            }
                        ?>
                        <td><?= $gradeValue ?></td>
                        <?php endfor; ?>
                        <td><?= $quarterCount > 0 ? number_format(round($finalSum / $quarterCount, 2), 2) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate SF10 using TCPDF
     */
    private function generateSF10TCPDF(array $student, array $subjects, array $quarterGrades, int $quarter, string $academicYear): string
    {
        $this->pdf->AddPage();
        
        // Header
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'DEPARTMENT OF EDUCATION', 0, 1, 'C');
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'REPORT CARD (SF10)', 0, 1, 'C');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Ln(5);

        // Student Information
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(40, 6, 'Name:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $student['name'], 0, 1);
        
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(40, 6, 'LRN:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $student['lrn'] ?? 'N/A', 0, 1);
        
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(40, 6, 'Grade Level:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, 'Grade ' . $student['grade_level'], 0, 1);
        
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(40, 6, 'Section:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $student['section_name'] ?? 'N/A', 0, 1);
        
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(40, 6, 'Quarter:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $this->getQuarterName($quarter), 0, 1);
        
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(40, 6, 'School Year:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $academicYear, 0, 1);
        
        $this->pdf->Ln(5);

        // Grades Table
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->Cell(80, 8, 'Subject', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'WW', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'PT', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'QE', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Final', 1, 1, 'C');

        $this->pdf->SetFont('helvetica', '', 9);
        foreach ($subjects as $subject) {
            $grade = $quarterGrades[$subject['id']] ?? null;
            
            $this->pdf->Cell(80, 7, $subject['name'], 1, 0);
            $this->pdf->Cell(30, 7, $grade ? number_format((float)($grade['ww_average'] ?? 0), 2) : '-', 1, 0, 'C');
            $this->pdf->Cell(30, 7, $grade ? number_format((float)($grade['pt_average'] ?? 0), 2) : '-', 1, 0, 'C');
            $this->pdf->Cell(30, 7, $grade ? number_format((float)($grade['qe_average'] ?? 0), 2) : '-', 1, 0, 'C');
            $this->pdf->Cell(30, 7, $grade ? number_format((float)($grade['final_grade'] ?? 0), 2) : '-', 1, 1, 'C');
        }

        return $this->pdf->Output('', 'S');
    }

    /**
     * Generate SF10 as HTML (fallback)
     */
    private function generateSF10HTML(array $student, array $subjects, array $quarterGrades, int $quarter, string $academicYear): string
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>SF10 - Report Card</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h1 { margin: 5px 0; font-size: 14px; }
                .header h2 { margin: 5px 0; font-size: 12px; font-weight: bold; }
                .student-info { margin-bottom: 20px; }
                .student-info p { margin: 5px 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #000; padding: 8px; text-align: center; }
                th { background-color: #f0f0f0; font-weight: bold; }
                .subject-col { text-align: left; }
                @media print {
                    body { margin: 0; }
                    @page { margin: 1cm; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>DEPARTMENT OF EDUCATION</h1>
                <h2>REPORT CARD (SF10)</h2>
            </div>
            
            <div class="student-info">
                <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
                <p><strong>LRN:</strong> <?= htmlspecialchars($student['lrn'] ?? 'N/A') ?></p>
                <p><strong>Grade Level:</strong> Grade <?= htmlspecialchars((string)($student['grade_level'] ?? '')) ?></p>
                <p><strong>Section:</strong> <?= htmlspecialchars($student['section_name'] ?? 'N/A') ?></p>
                <p><strong>Quarter:</strong> <?= htmlspecialchars($this->getQuarterName($quarter)) ?></p>
                <p><strong>School Year:</strong> <?= htmlspecialchars($academicYear) ?></p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th class="subject-col">Subject</th>
                        <th>WW</th>
                        <th>PT</th>
                        <th>QE</th>
                        <th>Final</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): 
                        $grade = $quarterGrades[$subject['id']] ?? null;
                    ?>
                    <tr>
                        <td class="subject-col"><?= htmlspecialchars($subject['name']) ?></td>
                        <td><?= $grade ? number_format((float)($grade['ww_average'] ?? 0), 2) : '-' ?></td>
                        <td><?= $grade ? number_format((float)($grade['pt_average'] ?? 0), 2) : '-' ?></td>
                        <td><?= $grade ? number_format((float)($grade['qe_average'] ?? 0), 2) : '-' ?></td>
                        <td><?= $grade ? number_format((float)($grade['final_grade'] ?? 0), 2) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private function getQuarterName(int $quarter): string
    {
        return match($quarter) {
            1 => '1st Quarter',
            2 => '2nd Quarter',
            3 => '3rd Quarter',
            4 => '4th Quarter',
            default => 'Unknown'
        };
    }
}

