<?php
declare(strict_types=1);

$sections = $sections ?? [];
$stats = $statistics ?? ['sections' => 0, 'students' => 0, 'subjects' => 0, 'advisory_sections' => 0];
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Teaching Loads</h1>
        <p class="text-muted mb-0">Classes are created by the admin. Check your assignments, then jump to the correct tool.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= \Helpers\Url::to('/teacher/add-students') ?>">
        <svg width="16" height="16" fill="currentColor" class="me-2">
            <use href="#icon-plus"></use>
        </svg>
        Add Students to a Section
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-3">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Total Classes</div>
            <div class="h4 fw-bold mb-0"><?= number_format($stats['sections'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Students Across Classes</div>
            <div class="h4 fw-bold mb-0"><?= number_format($stats['students'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Subjects</div>
            <div class="h4 fw-bold mb-0"><?= number_format($stats['subjects'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="surface p-3 text-center">
            <div class="text-muted small">Advisory Sections</div>
            <div class="h4 fw-bold mb-0"><?= number_format($stats['advisory_sections'] ?? 0) ?></div>
        </div>
    </div>
</div>

<?php if (empty($sections)): ?>
    <div class="surface p-4 text-center text-muted">
        No teaching loads yet. Once the admin links you to a section, it will appear here together with the subjects you handle.
    </div>
<?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-semibold mb-0">Class List</h2>
        <span class="text-muted small">Tip: use the links on each card to go straight to grade entry or attendance.</span>
    </div>

    <div class="row g-3">
        <?php foreach ($sections as $section): ?>
            <div class="col-12 col-lg-6">
                <div class="surface p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($section['section_name'] ?? 'Section') ?></div>
                            <div class="text-muted small">Grade <?= htmlspecialchars((string)($section['grade_level'] ?? '')) ?> • <?= htmlspecialchars($section['subject_name'] ?? '') ?></div>
                        </div>
                        <?php if (!empty($section['is_adviser'])): ?>
                            <span class="badge bg-success-subtle text-success">Advisory</span>
                        <?php endif; ?>
                    </div>

                    <dl class="row mb-3 small">
                        <dt class="col-5 text-muted">Schedule</dt>
                        <dd class="col-7 mb-1">
                            <?= htmlspecialchars($section['schedule'] ?? 'TBA') ?>
                        </dd>
                        <dt class="col-5 text-muted">Room</dt>
                        <dd class="col-7 mb-1">
                            <?= htmlspecialchars($section['room'] ?? 'TBD') ?>
                        </dd>
                        <dt class="col-5 text-muted">Students</dt>
                        <dd class="col-7 mb-0">
                            <?= number_format($section['student_count'] ?? 0) ?> enrolled
                        </dd>
                    </dl>

                    <div class="d-flex gap-2 flex-wrap">
                        <a class="btn btn-outline-primary btn-sm" href="<?= \Helpers\Url::to('/teacher/grades?section=' . ($section['section_id'] ?? '') . '&subject=' . ($section['subject_id'] ?? '')) ?>">
                            Manage Grades
                        </a>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= \Helpers\Url::to('/teacher/attendance?section=' . ($section['section_id'] ?? '') . '&subject=' . ($section['subject_id'] ?? '') . '&date=' . date('Y-m-d')) ?>">
                            <svg width="14" height="14" fill="currentColor" class="me-1">
                                <use href="#icon-calendar"></use>
                            </svg>
                            Take Attendance
                        </a>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= \Helpers\Url::to('/teacher/students?section=' . ($section['section_id'] ?? '')) ?>">
                            View Students
                        </a>
                    </div>
                    
                    <!-- Quick Attendance Actions -->
                    <div class="mt-2 pt-2 border-top">
                        <small class="text-muted d-block mb-1">Quick Attendance:</small>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <button type="button" class="btn btn-outline-success btn-sm" 
                                    onclick="quickAttendance(<?= (int)($section['section_id'] ?? 0) ?>, <?= (int)($section['subject_id'] ?? 0) ?>, 'present')"
                                    title="Mark all students as Present for today">
                                <svg width="12" height="12" fill="currentColor" class="me-1">
                                    <use href="#icon-check"></use>
                                </svg>
                                All Present
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm" 
                                    onclick="quickAttendance(<?= (int)($section['section_id'] ?? 0) ?>, <?= (int)($section['subject_id'] ?? 0) ?>, 'late')"
                                    title="Mark all students as Late for today">
                                All Late
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                    onclick="quickAttendance(<?= (int)($section['section_id'] ?? 0) ?>, <?= (int)($section['subject_id'] ?? 0) ?>, 'absent')"
                                    title="Mark all students as Absent for today">
                                All Absent
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
async function quickAttendance(sectionId, subjectId, status) {
    if (!sectionId || !subjectId) {
        alert('Section and Subject are required.');
        return;
    }
    
    if (!confirm(`Mark all students as ${status.toUpperCase()} for today?`)) {
        return;
    }
    
    const today = new Date().toISOString().split('T')[0];
    const saveUrl = '<?= \Helpers\Url::to('/teacher/api/attendance/save') ?>';
    
    // Get students from section details API
    try {
        const sectionResponse = await fetch(`<?= \Helpers\Url::to('/teacher/api/section-details') ?>?section_id=${sectionId}&subject_id=${subjectId}`);
        const sectionData = await sectionResponse.json();
        
        if (!sectionData.success || !sectionData.data || !sectionData.data.students || sectionData.data.students.length === 0) {
            alert('No students found in this section.');
            return;
        }
        
        const students = sectionData.data.students;
        let successCount = 0;
        let failCount = 0;
        
        // Mark each student
        for (const student of students) {
            try {
                const saveResponse = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        student_id: student.id || student.student_id,
                        section_id: sectionId,
                        subject_id: subjectId,
                        date: today,
                        status: status
                    })
                });
                
                const result = await saveResponse.json();
                if (result.success) {
                    successCount++;
                } else {
                    failCount++;
                }
            } catch (error) {
                failCount++;
            }
        }
        
        if (successCount > 0) {
            const message = failCount > 0 
                ? `Marked ${successCount} student(s) as ${status}. ${failCount} failed.`
                : `Successfully marked ${successCount} student(s) as ${status}.`;
            alert(message);
            
            // Optionally redirect to attendance page
            if (confirm('View attendance page?')) {
                window.location.href = `<?= \Helpers\Url::to('/teacher/attendance') ?>?section=${sectionId}&subject=${subjectId}&date=${today}`;
            }
        } else {
            alert('Failed to mark attendance. Please try again.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error marking attendance. Please try again.');
    }
}
</script>
