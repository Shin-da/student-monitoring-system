<?php
/**
 * Admin: Teacher Management
 * List and manage all teachers
 */

$teachers = $teachers ?? [];
$stats = $stats ?? [];
$search = $search ?? '';
$status = $status ?? '';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Management</h2>
            <p class="text-muted mb-0">Manage teachers and their assignments</p>
        </div>
        <div>
            <a href="<?= url('/admin/users') ?>" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i>Add New Teacher
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Total Teachers</div>
                            <div class="h3 mb-0 text-primary"><?= $stats['total'] ?? 0 ?></div>
                        </div>
                        <div class="text-primary"><i class="fas fa-users fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Active</div>
                            <div class="h3 mb-0 text-success"><?= $stats['active'] ?? 0 ?></div>
                        </div>
                        <div class="text-success"><i class="fas fa-check-circle fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-info border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">With Classes</div>
                            <div class="h3 mb-0 text-info"><?= $stats['with_classes'] ?? 0 ?></div>
                        </div>
                        <div class="text-info"><i class="fas fa-book fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Advisers</div>
                            <div class="h3 mb-0 text-warning"><?= $stats['advisers'] ?? 0 ?></div>
                        </div>
                        <div class="text-warning"><i class="fas fa-user-shield fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" action="<?= url('/admin/teachers') ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by name or email..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Teachers Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Teacher List</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($teachers)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th class="text-center">Classes</th>
                                <th class="text-center">Advisory</th>
                                <th class="text-center">Status</th>
                                <th>Joined</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($teacher['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($teacher['email']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= $teacher['class_count'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?= $teacher['advisory_count'] > 0 ? 
                                            '<span class="badge bg-success"><i class="fas fa-check"></i></span>' : 
                                            '<span class="text-muted">-</span>' ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $teacher['account_status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= htmlspecialchars(ucfirst($teacher['account_status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($teacher['created_at'])) ?></td>
                                    <td class="text-center">
                                        <a href="<?= url('/admin/view-teacher?id=' . $teacher['user_id']) ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No teachers found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

