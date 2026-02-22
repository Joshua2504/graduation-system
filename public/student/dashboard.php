<?php
/**
 * Student Dashboard — status-aware view
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/lang.php';

require_role('student');

$userId = current_user_id();
$project = getUserProject($userId);
$status = $project['status'] ?? null;

$pageTitle = __('dashboard');
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container">
    <?php if (!$project): ?>
        <!-- No project yet -->
        <div class="row justify-content-center mt-5">
            <div class="col-md-8 text-center">
                <div class="card shadow p-5">
                    <i class="bi bi-folder-plus text-primary" style="font-size: 4rem;"></i>
                    <h3 class="mt-3"><?= getLang() === 'ar' ? 'مرحباً بك!' : 'Welcome!' ?></h3>
                    <p class="text-muted mb-4">
                        <?= getLang() === 'ar' ? 'لم تقم بإنشاء مشروع بعد. ابدأ الآن!' : "You haven't created a project yet. Start now!" ?>
                    </p>
                    <div>
                        <a href="/student/wizard.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-plus-circle me-2"></i><?= __('new_project') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($status === 'draft'): ?>
        <!-- Draft — resume -->
        <div class="row justify-content-center mt-4">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body text-center p-5">
                        <span class="badge bg-secondary fs-6 mb-3"><?= __('status_draft') ?></span>
                        <h4><?= sanitize($project['title']) ?></h4>
                        <p class="text-muted">
                            <?php 
                            $count = countProjectStudents($project['id']);
                            echo getLang() === 'ar' 
                                ? "تم إدخال بيانات $count من 7 طلاب" 
                                : "$count of 7 students completed";
                            ?>
                        </p>
                        <a href="/student/wizard.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-pencil-square me-2"></i><?= __('resume_project') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($status === 'under_review'): ?>
        <!-- Under review — locked -->
        <div class="row justify-content-center mt-4">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-hourglass-split text-warning" style="font-size: 4rem;"></i>
                        <span class="badge bg-warning text-dark fs-6 mt-3 mb-3 d-block mx-auto" style="width:fit-content"><?= __('status_under_review') ?></span>
                        <h4><?= sanitize($project['title']) ?></h4>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <?= __('project_submitted') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($status === 'accepted'): ?>
        <!-- Accepted — permanent lock -->
        <div class="row justify-content-center mt-4">
            <div class="col-md-8">
                <div class="card shadow border-success">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        <span class="badge bg-success fs-6 mt-3 mb-3 d-block mx-auto" style="width:fit-content"><?= __('status_accepted') ?></span>
                        <h4><?= sanitize($project['title']) ?></h4>
                        
                        <?php if ($project['group_number']): ?>
                            <div class="alert alert-success mt-3">
                                <strong><?= __('group_number') ?>:</strong> 
                                <span class="fs-4 fw-bold"><?= $project['group_number'] ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <?= __('project_accepted_msg') ?>
                        </div>
                        
                        <?php if (!empty($project['doctor_note'])): ?>
                            <div class="alert alert-secondary text-start mt-3">
                                <strong><i class="bi bi-chat-left-text me-2"></i><?= __('doctor_note') ?>:</strong>
                                <p class="mb-0 mt-2"><?= sanitize($project['doctor_note']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($status === 'rejected'): ?>
        <!-- Rejected — can re-edit -->
        <div class="row justify-content-center mt-4">
            <div class="col-md-8">
                <div class="card shadow border-danger">
                    <div class="card-body text-center p-5">
                        <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                        <span class="badge bg-danger fs-6 mt-3 mb-3 d-block mx-auto" style="width:fit-content"><?= __('status_rejected') ?></span>
                        <h4><?= sanitize($project['title']) ?></h4>
                        
                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?= __('project_rejected_msg') ?>
                        </div>
                        
                        <?php if (!empty($project['doctor_note'])): ?>
                            <div class="alert alert-secondary text-start">
                                <strong><i class="bi bi-chat-left-text me-2"></i><?= __('doctor_note') ?>:</strong>
                                <p class="mb-0 mt-2"><?= sanitize($project['doctor_note']) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <a href="/student/wizard.php" class="btn btn-warning btn-lg mt-3">
                            <i class="bi bi-pencil-square me-2"></i><?= __('edit_resubmit') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
