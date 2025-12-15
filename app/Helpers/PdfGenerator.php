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
     * Check if TCPDF is being used (returns PDF) or HTML fallback
     */
    public function isUsingTcpdf(): bool
    {
        return $this->useTcpdf;
    }

    /**
     * Build display name from student data with proper fallback
     */
    private function buildDisplayName(array $student): string
    {
        $displayName = '';
        
        // Try to build from individual name parts first
        $firstName = isset($student['first_name']) ? trim((string)$student['first_name']) : '';
        $middleName = isset($student['middle_name']) ? trim((string)$student['middle_name']) : '';
        $lastName = isset($student['last_name']) ? trim((string)$student['last_name']) : '';
        
        // Build name from parts if we have at least first or last name
        if (!empty($firstName) || !empty($lastName)) {
            $nameParts = array_filter([$firstName, $middleName, $lastName], function($part) {
                return !empty($part) && $part !== '???';
            });
            if (!empty($nameParts)) {
                $displayName = implode(' ', $nameParts);
            }
        }
        
        // Fallback to users.name if individual parts didn't work
        if (empty($displayName) && isset($student['name'])) {
            $userName = trim((string)$student['name']);
            if (!empty($userName) && $userName !== '???') {
                $displayName = $userName;
            }
        }
        
        // If still empty or contains only question marks, return a proper placeholder
        if (empty($displayName) || trim($displayName) === '???' || trim($displayName) === '') {
            return 'N/A';
        }
        
        return $displayName;
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

        // Get comprehensive student information
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   COALESCE(NULLIF(TRIM(s.first_name), ''), NULL) AS first_name,
                   COALESCE(NULLIF(TRIM(s.middle_name), ''), NULL) AS middle_name,
                   COALESCE(NULLIF(TRIM(s.last_name), ''), NULL) AS last_name,
                   u.name, u.email, 
                   sec.name AS section_name, sec.grade_level AS section_grade_level
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

        // Build display name with proper fallback
        $student['display_name'] = $this->buildDisplayName($student);

        $academicYear = $academicYear ?? $gradeModel->getCurrentAcademicYear();

        // Get all subjects for the grade level
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

        // Get school information from config
        $schoolName = $config['app']['name'] ?? 'St. Ignatius Student Monitoring';
        $schoolInfo = [
            'name' => $schoolName,
            'address' => 'Philippines', // Can be enhanced later
            'principal' => 'School Principal' // Can be enhanced later
        ];

        if ($this->useTcpdf) {
            return $this->generateSF9TCPDF($student, $subjects, $allGrades, $academicYear, $schoolInfo);
        } else {
            return $this->generateSF9HTML($student, $subjects, $allGrades, $academicYear, $schoolInfo);
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

        // Get comprehensive student information
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   COALESCE(NULLIF(TRIM(s.first_name), ''), NULL) AS first_name,
                   COALESCE(NULLIF(TRIM(s.middle_name), ''), NULL) AS middle_name,
                   COALESCE(NULLIF(TRIM(s.last_name), ''), NULL) AS last_name,
                   u.name, u.email, 
                   sec.name AS section_name, sec.grade_level AS section_grade_level
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

        // Build display name with proper fallback
        $student['display_name'] = $this->buildDisplayName($student);

        $academicYear = $academicYear ?? $gradeModel->getCurrentAcademicYear();

        // Get all subjects for the grade level
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
        $overallAverage = 0;
        $subjectCount = 0;
        foreach ($subjects as $subject) {
            $grade = $gradeModel->calculateQuarterlyGrade(
                $studentId,
                (int)$subject['id'],
                $quarter,
                $academicYear
            );
            if ($grade && isset($grade['final_grade'])) {
                $quarterGrades[$subject['id']] = $grade;
                $overallAverage += (float)$grade['final_grade'];
                $subjectCount++;
            }
        }
        $overallAverage = $subjectCount > 0 ? round($overallAverage / $subjectCount, 2) : 0;

        // Get school information from config
        $schoolName = $config['app']['name'] ?? 'St. Ignatius Student Monitoring';
        $schoolInfo = [
            'name' => $schoolName,
            'address' => 'Philippines', // Can be enhanced later
            'principal' => 'School Principal' // Can be enhanced later
        ];

        // Get adviser information if available
        $adviserInfo = null;
        if (!empty($student['section_id'])) {
            $stmt = $pdo->prepare("
                SELECT u.name AS adviser_name
                FROM sections sec
                JOIN teachers t ON sec.adviser_id = t.id
                JOIN users u ON t.user_id = u.id
                WHERE sec.id = ?
            ");
            $stmt->execute([$student['section_id']]);
            $adviser = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($adviser) {
                $adviserInfo = $adviser['adviser_name'];
            }
        }

        if ($this->useTcpdf) {
            return $this->generateSF10TCPDF($student, $subjects, $quarterGrades, $quarter, $academicYear, $schoolInfo, $adviserInfo, $overallAverage);
        } else {
            return $this->generateSF10HTML($student, $subjects, $quarterGrades, $quarter, $academicYear, $schoolInfo, $adviserInfo, $overallAverage);
        }
    }

    /**
     * Generate SF9 using TCPDF
     */
    private function generateSF9TCPDF(array $student, array $subjects, array $allGrades, string $academicYear, array $schoolInfo): string
    {
        $this->pdf->AddPage();
        
        // Header with school information
        $this->pdf->SetFont('helvetica', 'B', 16);
        $this->pdf->Cell(0, 8, 'DEPARTMENT OF EDUCATION', 0, 1, 'C');
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 7, $schoolInfo['name'], 0, 1, 'C');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 5, $schoolInfo['address'], 0, 1, 'C');
        $this->pdf->Ln(3);
        $this->pdf->SetFont('helvetica', 'B', 13);
        $this->pdf->Cell(0, 8, 'PERMANENT RECORD (SF9)', 0, 1, 'C');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Ln(5);

        // Student Information Section
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 6, 'LEARNER INFORMATION', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Ln(2);

        // Name
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(35, 6, 'Name:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $displayName = $student['display_name'] ?? 'N/A';
        $this->pdf->Cell(0, 6, $displayName, 0, 1);
        
        // LRN
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(35, 6, 'LRN:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(60, 6, $student['lrn'] ?? 'N/A', 0, 0);
        
        // Birth Date
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(30, 6, 'Birth Date:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $birthDate = !empty($student['birth_date']) ? date('F d, Y', strtotime($student['birth_date'])) : 'N/A';
        $this->pdf->Cell(0, 6, $birthDate, 0, 1);
        
        // Grade Level and Section
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(35, 6, 'Grade Level:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(60, 6, 'Grade ' . ($student['grade_level'] ?? 'N/A'), 0, 0);
        
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(30, 6, 'Section:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $student['section_name'] ?? 'N/A', 0, 1);
        
        // School Year
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(35, 6, 'School Year:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $academicYear, 0, 1);
        
        // Address (if available)
        if (!empty($student['address'])) {
            $this->pdf->SetFont('helvetica', 'B', 10);
            $this->pdf->Cell(35, 6, 'Address:', 0, 0);
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->Cell(0, 6, $student['address'], 0, 1);
        }
        
        $this->pdf->Ln(5);

        // Grades Table
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(0, 6, 'ACADEMIC RECORD', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->Cell(70, 8, 'Subject', 1, 0, 'C');
        $this->pdf->Cell(25, 8, '1st Qtr', 1, 0, 'C');
        $this->pdf->Cell(25, 8, '2nd Qtr', 1, 0, 'C');
        $this->pdf->Cell(25, 8, '3rd Qtr', 1, 0, 'C');
        $this->pdf->Cell(25, 8, '4th Qtr', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Final Grade', 1, 1, 'C');

        $this->pdf->SetFont('helvetica', '', 9);
        $totalFinal = 0;
        $subjectCount = 0;
        
        foreach ($subjects as $subject) {
            $this->pdf->Cell(70, 7, $subject['name'], 1, 0, 'L');
            
            $finalSum = 0;
            $quarterCount = 0;
            
            for ($q = 1; $q <= 4; $q++) {
                $grade = $allGrades[$subject['id']][$q] ?? null;
                $gradeValue = $grade && isset($grade['final_grade']) ? number_format((float)$grade['final_grade'], 2) : '-';
                $this->pdf->Cell(25, 7, $gradeValue, 1, 0, 'C');
                
                if ($grade && isset($grade['final_grade'])) {
                    $finalSum += (float)$grade['final_grade'];
                    $quarterCount++;
                }
            }
            
            $finalGrade = $quarterCount > 0 ? round($finalSum / $quarterCount, 2) : '-';
            if ($finalGrade !== '-') {
                $totalFinal += $finalGrade;
                $subjectCount++;
            }
            $this->pdf->Cell(30, 7, $finalGrade !== '-' ? number_format((float)$finalGrade, 2) : '-', 1, 1, 'C');
        }
        
        // Overall Average Row
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->Cell(170, 7, 'GENERAL AVERAGE', 1, 0, 'R');
        $overallAverage = $subjectCount > 0 ? round($totalFinal / $subjectCount, 2) : '-';
        $this->pdf->Cell(30, 7, $overallAverage !== '-' ? number_format((float)$overallAverage, 2) : '-', 1, 1, 'C');
        
        $this->pdf->Ln(10);
        
        // Signature Section
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->Cell(95, 5, '', 0, 0);
        $this->pdf->Cell(95, 5, '', 0, 1);
        $this->pdf->Cell(95, 5, '', 0, 0);
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->Cell(95, 5, $schoolInfo['principal'], 'T', 1, 'C');
        $this->pdf->Cell(95, 3, '', 0, 0);
        $this->pdf->SetFont('helvetica', '', 8);
        $this->pdf->Cell(95, 3, 'School Principal', 0, 1, 'C');

        // Output PDF
        return $this->pdf->Output('', 'S');
    }

    /**
     * Generate SF9 as HTML (fallback)
     */
    private function generateSF9HTML(array $student, array $subjects, array $allGrades, string $academicYear, array $schoolInfo): string
    {
        $birthDate = !empty($student['birth_date']) ? date('F d, Y', strtotime($student['birth_date'])) : 'N/A';
        $totalFinal = 0;
        $subjectCount = 0;
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>SF9 - Permanent Record</title>
            <meta charset="UTF-8">
            <style>
                * { box-sizing: border-box; }
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0;
                    padding: 20px;
                    font-size: 11pt;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 25px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 10px;
                }
                .header h1 { 
                    margin: 5px 0; 
                    font-size: 16pt;
                    font-weight: bold;
                }
                .header h2 { 
                    margin: 5px 0; 
                    font-size: 14pt; 
                    font-weight: bold; 
                }
                .header .school-name {
                    font-size: 13pt;
                    font-weight: bold;
                    margin: 5px 0;
                }
                .header .school-address {
                    font-size: 10pt;
                    margin: 3px 0;
                }
                .section-title {
                    font-weight: bold;
                    font-size: 11pt;
                    margin: 15px 0 8px 0;
                    border-bottom: 1px solid #000;
                    padding-bottom: 3px;
                }
                .student-info { 
                    margin-bottom: 20px; 
                }
                .student-info-row {
                    display: flex;
                    margin: 4px 0;
                }
                .student-info-label {
                    font-weight: bold;
                    width: 120px;
                    flex-shrink: 0;
                }
                .student-info-value {
                    flex: 1;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 10px;
                    font-size: 10pt;
                }
                th, td { 
                    border: 1px solid #000; 
                    padding: 6px 4px; 
                    text-align: center; 
                }
                th { 
                    background-color: #f0f0f0; 
                    font-weight: bold;
                    font-size: 9pt;
                }
                .subject-col { 
                    text-align: left; 
                    padding-left: 8px;
                }
                .signature-section {
                    margin-top: 40px;
                    display: flex;
                    justify-content: flex-end;
                }
                .signature-box {
                    width: 200px;
                    text-align: center;
                }
                .signature-line {
                    border-top: 1px solid #000;
                    margin-top: 40px;
                    padding-top: 5px;
                }
                @media print {
                    body { 
                        margin: 0;
                        padding: 15px;
                    }
                    @page { 
                        margin: 1.5cm;
                        size: letter;
                    }
                    .no-print {
                        display: none;
                    }
                }
                @media screen {
                    .print-button {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #007bff;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 5px;
                        cursor: pointer;
                        font-size: 14px;
                        z-index: 1000;
                    }
                    .print-button:hover {
                        background: #0056b3;
                    }
                }
            </style>
        </head>
        <body>
            <button class="print-button no-print" onclick="window.print()">🖨️ Print</button>
            
            <div class="header">
                <h1>DEPARTMENT OF EDUCATION</h1>
                <div class="school-name"><?= htmlspecialchars($schoolInfo['name']) ?></div>
                <div class="school-address"><?= htmlspecialchars($schoolInfo['address']) ?></div>
                <h2>PERMANENT RECORD (SF9)</h2>
            </div>
            
            <div class="section-title">LEARNER INFORMATION</div>
            <div class="student-info">
                <div class="student-info-row">
                    <div class="student-info-label">Name:</div>
                    <div class="student-info-value"><?= htmlspecialchars($student['display_name'] ?? 'N/A') ?></div>
                </div>
                <div class="student-info-row">
                    <div class="student-info-label">LRN:</div>
                    <div class="student-info-value"><?= htmlspecialchars($student['lrn'] ?? 'N/A') ?></div>
                    <div class="student-info-label" style="margin-left: 30px;">Birth Date:</div>
                    <div class="student-info-value"><?= htmlspecialchars($birthDate) ?></div>
                </div>
                <div class="student-info-row">
                    <div class="student-info-label">Grade Level:</div>
                    <div class="student-info-value">Grade <?= htmlspecialchars((string)($student['grade_level'] ?? 'N/A')) ?></div>
                    <div class="student-info-label" style="margin-left: 30px;">Section:</div>
                    <div class="student-info-value"><?= htmlspecialchars($student['section_name'] ?? 'N/A') ?></div>
                </div>
                <div class="student-info-row">
                    <div class="student-info-label">School Year:</div>
                    <div class="student-info-value"><?= htmlspecialchars($academicYear) ?></div>
                </div>
                <?php if (!empty($student['address'])): ?>
                <div class="student-info-row">
                    <div class="student-info-label">Address:</div>
                    <div class="student-info-value"><?= htmlspecialchars($student['address']) ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="section-title">ACADEMIC RECORD</div>
            <table>
                <thead>
                    <tr>
                        <th class="subject-col">Subject</th>
                        <th>1st Qtr</th>
                        <th>2nd Qtr</th>
                        <th>3rd Qtr</th>
                        <th>4th Qtr</th>
                        <th>Final Grade</th>
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
                            $gradeValue = $grade && isset($grade['final_grade']) ? number_format((float)$grade['final_grade'], 2) : '-';
                            if ($grade && isset($grade['final_grade'])) {
                                $finalSum += (float)$grade['final_grade'];
                                $quarterCount++;
                            }
                        ?>
                        <td><?= $gradeValue ?></td>
                        <?php endfor; ?>
                        <?php 
                        $finalGrade = $quarterCount > 0 ? round($finalSum / $quarterCount, 2) : '-';
                        if ($finalGrade !== '-') {
                            $totalFinal += $finalGrade;
                            $subjectCount++;
                        }
                        ?>
                        <td><strong><?= $finalGrade !== '-' ? number_format($finalGrade, 2) : '-' ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background-color: #f9f9f9;">
                        <td class="subject-col" style="text-align: right; font-weight: bold; padding-right: 10px;">GENERAL AVERAGE</td>
                        <td colspan="4"></td>
                        <td><strong><?= $subjectCount > 0 ? number_format(round($totalFinal / $subjectCount, 2), 2) : '-' ?></strong></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line">
                        <strong><?= htmlspecialchars($schoolInfo['principal']) ?></strong><br>
                        <small>School Principal</small>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate SF10 using TCPDF
     */
    private function generateSF10TCPDF(array $student, array $subjects, array $quarterGrades, int $quarter, string $academicYear, array $schoolInfo, ?string $adviserInfo, float $overallAverage): string
    {
        $this->pdf->AddPage();
        
        // Header with school information
        $this->pdf->SetFont('helvetica', 'B', 16);
        $this->pdf->Cell(0, 8, 'DEPARTMENT OF EDUCATION', 0, 1, 'C');
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 7, $schoolInfo['name'], 0, 1, 'C');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 5, $schoolInfo['address'], 0, 1, 'C');
        $this->pdf->Ln(3);
        $this->pdf->SetFont('helvetica', 'B', 13);
        $this->pdf->Cell(0, 8, 'REPORT CARD (SF10)', 0, 1, 'C');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Ln(5);

        // Student Information Section
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 6, 'LEARNER INFORMATION', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Ln(2);

        // Name
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(35, 6, 'Name:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $displayName = $student['display_name'] ?? 'N/A';
        $this->pdf->Cell(0, 6, $displayName, 0, 1);
        
        // LRN
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(35, 6, 'LRN:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(60, 6, $student['lrn'] ?? 'N/A', 0, 0);
        
        // Quarter
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(30, 6, 'Quarter:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $this->getQuarterName($quarter), 0, 1);
        
        // Grade Level and Section
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(35, 6, 'Grade Level:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(60, 6, 'Grade ' . ($student['grade_level'] ?? 'N/A'), 0, 0);
        
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(30, 6, 'Section:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $student['section_name'] ?? 'N/A', 0, 1);
        
        // School Year
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(35, 6, 'School Year:', 0, 0);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, $academicYear, 0, 1);
        
        $this->pdf->Ln(5);

        // Grades Table
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(0, 6, 'ACADEMIC PERFORMANCE', 0, 1, 'L');
        $this->pdf->Ln(2);
        
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->Cell(70, 8, 'Subject', 1, 0, 'C');
        $this->pdf->Cell(25, 8, 'WW', 1, 0, 'C');
        $this->pdf->Cell(25, 8, 'PT', 1, 0, 'C');
        $this->pdf->Cell(25, 8, 'QE', 1, 0, 'C');
        $this->pdf->Cell(25, 8, 'Attendance', 1, 0, 'C');
        $this->pdf->Cell(30, 8, 'Final Grade', 1, 1, 'C');

        $this->pdf->SetFont('helvetica', '', 9);
        foreach ($subjects as $subject) {
            $grade = $quarterGrades[$subject['id']] ?? null;
            
            $this->pdf->Cell(70, 7, $subject['name'], 1, 0, 'L');
            $this->pdf->Cell(25, 7, $grade && isset($grade['ww_average']) ? number_format((float)$grade['ww_average'], 2) : '-', 1, 0, 'C');
            $this->pdf->Cell(25, 7, $grade && isset($grade['pt_average']) ? number_format((float)$grade['pt_average'], 2) : '-', 1, 0, 'C');
            $this->pdf->Cell(25, 7, $grade && isset($grade['qe_average']) ? number_format((float)$grade['qe_average'], 2) : '-', 1, 0, 'C');
            $attendance = $grade && isset($grade['attendance_average']) ? number_format((float)$grade['attendance_average'], 1) . '%' : '-';
            $this->pdf->Cell(25, 7, $attendance, 1, 0, 'C');
            $this->pdf->Cell(30, 7, $grade && isset($grade['final_grade']) ? number_format((float)$grade['final_grade'], 2) : '-', 1, 1, 'C');
        }
        
        // Overall Average Row
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->Cell(170, 7, 'QUARTERLY AVERAGE', 1, 0, 'R');
        $this->pdf->Cell(30, 7, number_format($overallAverage, 2), 1, 1, 'C');
        
        $this->pdf->Ln(8);
        
        // Remarks Section
        $this->pdf->SetFont('helvetica', 'B', 10);
        $this->pdf->Cell(0, 6, 'REMARKS:', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        $remarks = $overallAverage >= 75 ? 'PASSED' : ($overallAverage > 0 ? 'FAILED' : 'NO GRADES YET');
        $this->pdf->Cell(0, 6, $remarks, 0, 1, 'L');
        
        $this->pdf->Ln(15);
        
        // Signature Section
        $this->pdf->SetFont('helvetica', '', 9);
        $signatureY = $this->pdf->GetY();
        
        // Adviser Signature
        $this->pdf->SetXY(20, $signatureY);
        $this->pdf->Cell(80, 5, '', 0, 0);
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->Cell(80, 5, $adviserInfo ?? 'Class Adviser', 'T', 1, 'C');
        $this->pdf->SetX(100);
        $this->pdf->SetFont('helvetica', '', 8);
        $this->pdf->Cell(80, 3, 'Class Adviser', 0, 1, 'C');
        
        // Principal Signature
        $this->pdf->SetXY(120, $signatureY);
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->Cell(80, 5, $schoolInfo['principal'], 'T', 1, 'C');
        $this->pdf->SetX(120);
        $this->pdf->SetFont('helvetica', '', 8);
        $this->pdf->Cell(80, 3, 'School Principal', 0, 1, 'C');

        return $this->pdf->Output('', 'S');
    }

    /**
     * Generate SF10 as HTML (fallback)
     */
    private function generateSF10HTML(array $student, array $subjects, array $quarterGrades, int $quarter, string $academicYear, array $schoolInfo, ?string $adviserInfo, float $overallAverage): string
    {
        $remarks = $overallAverage >= 75 ? 'PASSED' : ($overallAverage > 0 ? 'FAILED' : 'NO GRADES YET');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>SF10 - Report Card</title>
            <meta charset="UTF-8">
            <style>
                * { box-sizing: border-box; }
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0;
                    padding: 20px;
                    font-size: 11pt;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 25px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 10px;
                }
                .header h1 { 
                    margin: 5px 0; 
                    font-size: 16pt;
                    font-weight: bold;
                }
                .header h2 { 
                    margin: 5px 0; 
                    font-size: 14pt; 
                    font-weight: bold; 
                }
                .header .school-name {
                    font-size: 13pt;
                    font-weight: bold;
                    margin: 5px 0;
                }
                .header .school-address {
                    font-size: 10pt;
                    margin: 3px 0;
                }
                .section-title {
                    font-weight: bold;
                    font-size: 11pt;
                    margin: 15px 0 8px 0;
                    border-bottom: 1px solid #000;
                    padding-bottom: 3px;
                }
                .student-info { 
                    margin-bottom: 20px; 
                }
                .student-info-row {
                    display: flex;
                    margin: 4px 0;
                }
                .student-info-label {
                    font-weight: bold;
                    width: 120px;
                    flex-shrink: 0;
                }
                .student-info-value {
                    flex: 1;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 10px;
                    font-size: 10pt;
                }
                th, td { 
                    border: 1px solid #000; 
                    padding: 6px 4px; 
                    text-align: center; 
                }
                th { 
                    background-color: #f0f0f0; 
                    font-weight: bold;
                    font-size: 9pt;
                }
                .subject-col { 
                    text-align: left; 
                    padding-left: 8px;
                }
                .remarks-section {
                    margin-top: 20px;
                    padding: 10px;
                    border: 1px solid #000;
                }
                .remarks-section strong {
                    font-size: 11pt;
                }
                .signature-section {
                    margin-top: 40px;
                    display: flex;
                    justify-content: space-between;
                }
                .signature-box {
                    width: 200px;
                    text-align: center;
                }
                .signature-line {
                    border-top: 1px solid #000;
                    margin-top: 40px;
                    padding-top: 5px;
                }
                @media print {
                    body { 
                        margin: 0;
                        padding: 15px;
                    }
                    @page { 
                        margin: 1.5cm;
                        size: letter;
                    }
                    .no-print {
                        display: none;
                    }
                }
                @media screen {
                    .print-button {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #007bff;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 5px;
                        cursor: pointer;
                        font-size: 14px;
                        z-index: 1000;
                    }
                    .print-button:hover {
                        background: #0056b3;
                    }
                }
            </style>
        </head>
        <body>
            <button class="print-button no-print" onclick="window.print()">🖨️ Print</button>
            
            <div class="header">
                <h1>DEPARTMENT OF EDUCATION</h1>
                <div class="school-name"><?= htmlspecialchars($schoolInfo['name']) ?></div>
                <div class="school-address"><?= htmlspecialchars($schoolInfo['address']) ?></div>
                <h2>REPORT CARD (SF10)</h2>
            </div>
            
            <div class="section-title">LEARNER INFORMATION</div>
            <div class="student-info">
                <div class="student-info-row">
                    <div class="student-info-label">Name:</div>
                    <div class="student-info-value"><?= htmlspecialchars($student['display_name'] ?? 'N/A') ?></div>
                </div>
                <div class="student-info-row">
                    <div class="student-info-label">LRN:</div>
                    <div class="student-info-value"><?= htmlspecialchars($student['lrn'] ?? 'N/A') ?></div>
                    <div class="student-info-label" style="margin-left: 30px;">Quarter:</div>
                    <div class="student-info-value"><?= htmlspecialchars($this->getQuarterName($quarter)) ?></div>
                </div>
                <div class="student-info-row">
                    <div class="student-info-label">Grade Level:</div>
                    <div class="student-info-value">Grade <?= htmlspecialchars((string)($student['grade_level'] ?? 'N/A')) ?></div>
                    <div class="student-info-label" style="margin-left: 30px;">Section:</div>
                    <div class="student-info-value"><?= htmlspecialchars($student['section_name'] ?? 'N/A') ?></div>
                </div>
                <div class="student-info-row">
                    <div class="student-info-label">School Year:</div>
                    <div class="student-info-value"><?= htmlspecialchars($academicYear) ?></div>
                </div>
            </div>
            
            <div class="section-title">ACADEMIC PERFORMANCE</div>
            <table>
                <thead>
                    <tr>
                        <th class="subject-col">Subject</th>
                        <th>WW</th>
                        <th>PT</th>
                        <th>QE</th>
                        <th>Attendance</th>
                        <th>Final Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): 
                        $grade = $quarterGrades[$subject['id']] ?? null;
                    ?>
                    <tr>
                        <td class="subject-col"><?= htmlspecialchars($subject['name']) ?></td>
                        <td><?= $grade && isset($grade['ww_average']) ? number_format((float)$grade['ww_average'], 2) : '-' ?></td>
                        <td><?= $grade && isset($grade['pt_average']) ? number_format((float)$grade['pt_average'], 2) : '-' ?></td>
                        <td><?= $grade && isset($grade['qe_average']) ? number_format((float)$grade['qe_average'], 2) : '-' ?></td>
                        <td><?= $grade && isset($grade['attendance_average']) ? number_format((float)$grade['attendance_average'], 1) . '%' : '-' ?></td>
                        <td><strong><?= $grade && isset($grade['final_grade']) ? number_format((float)$grade['final_grade'], 2) : '-' ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background-color: #f9f9f9;">
                        <td class="subject-col" style="text-align: right; font-weight: bold; padding-right: 10px;">QUARTERLY AVERAGE</td>
                        <td colspan="4"></td>
                        <td><strong><?= number_format($overallAverage, 2) ?></strong></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="remarks-section">
                <strong>REMARKS:</strong> <?= htmlspecialchars($remarks) ?>
            </div>
            
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line">
                        <strong><?= htmlspecialchars($adviserInfo ?? 'Class Adviser') ?></strong><br>
                        <small>Class Adviser</small>
                    </div>
                </div>
                <div class="signature-box">
                    <div class="signature-line">
                        <strong><?= htmlspecialchars($schoolInfo['principal']) ?></strong><br>
                        <small>School Principal</small>
                    </div>
                </div>
            </div>
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

