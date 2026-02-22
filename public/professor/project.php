<?php
/**
 * Professor — Single Project Review Page
 */
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/lang.php';

require_role('doctor');

$projectId = (int)($_GET['id'] ?? 0);
if ($projectId === 0) {
    redirect('/professor/dashboard.php');
}

$project = getProject($projectId);
if (!$project) {
    redirect('/professor/dashboard.php');
}

$students = getProjectStudents($projectId);
$duplicates = findDuplicateProjects($projectId, $project['title']);
$isAr = getLang() === 'ar';

$statusLabels = [
    'draft' => __('status_draft'),
    'under_review' => __('status_under_review'),
    'accepted' => __('status_accepted'),
    'rejected' => __('status_rejected'),
];
$statusColors = [
    'draft' => 'secondary',
    'under_review' => 'warning',
    'accepted' => 'success',
    'rejected' => 'danger',
];

$pageTitle = sanitize($project['title']);
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container">
    <!-- Back button -->
    <a href="/professor/dashboard.php" class="btn btn-outline-secondary mb-3">
        <i class="bi bi-arrow-<?= $isAr ? 'right' : 'left' ?> me-1"></i>
        <?= $isAr ? 'العودة للقائمة' : 'Back to List' ?>
    </a>

    <!-- Duplicate Warning -->
    <?php if (!empty($duplicates)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong><?= __('duplicate_warning') ?></strong>
            <?php foreach ($duplicates as $dup): ?>
                <a href="/professor/project.php?id=<?= $dup['id'] ?>" class="btn btn-sm btn-warning ms-2">
                    <i class="bi bi-link-45deg me-1"></i><?= __('view_similar') ?> (#<?= $dup['id'] ?>)
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Project Info Card -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i><?= sanitize($project['title']) ?></h5>
            <span class="badge bg-<?= $statusColors[$project['status']] ?> fs-6">
                <?= $statusLabels[$project['status']] ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <strong><?= $isAr ? 'نوع المشروع' : 'Project Type' ?>:</strong>
                    <span><?= sanitize($project['type']) ?></span>
                </div>
                <div class="col-md-4 mb-2">
                    <strong><?= __('team_leader') ?>:</strong>
                    <span><?= sanitize($project['leader_name']) ?> (<?= sanitize($project['leader_email']) ?>)</span>
                </div>
                <div class="col-md-4 mb-2">
                    <strong><?= __('submission_date') ?>:</strong>
                    <span><?= $project['submission_date'] ? date('Y-m-d H:i', strtotime($project['submission_date'])) : '-' ?></span>
                </div>
                <?php if ($project['group_number']): ?>
                    <div class="col-md-4 mb-2">
                        <strong><?= __('group_number') ?>:</strong>
                        <span class="badge bg-success fs-6"><?= $project['group_number'] ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($project['doctor_note']): ?>
                    <div class="col-12 mt-2">
                        <strong><?= __('doctor_note') ?>:</strong>
                        <p class="mt-1 p-2 bg-light rounded"><?= sanitize($project['doctor_note']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Students -->
    <h5 class="mb-3"><i class="bi bi-people-fill me-2"></i><?= $isAr ? 'بيانات الطلاب' : 'Student Data' ?> (<?= count($students) ?>/7)</h5>

    <?php foreach ($students as $i => $student): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <?= $isAr ? 'الطالب' : 'Student' ?> <?= $i + 1 ?>: <?= sanitize($student['name']) ?>
                    <?php if ($i === 0): ?>
                        <span class="badge bg-primary ms-2"><?= __('team_leader') ?></span>
                    <?php endif; ?>
                </h6>
                <span class="badge bg-secondary"><?= sanitize($student['student_code']) ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <small class="text-muted"><?= $isAr ? 'الجنس' : 'Gender' ?></small><br>
                        <?= $student['gender'] === 'male' ? ($isAr ? 'ذكر' : 'Male') : ($isAr ? 'أنثى' : 'Female') ?>
                    </div>
                    <div class="col-md-3 mb-2">
                        <small class="text-muted"><?= $isAr ? 'الرقم القومي' : 'National ID' ?></small><br>
                        <?= sanitize($student['national_id']) ?>
                    </div>
                    <div class="col-md-3 mb-2">
                        <small class="text-muted"><?= $isAr ? 'تاريخ الميلاد' : 'Birth Date' ?></small><br>
                        <?= $student['birth_date'] ?>
                    </div>
                    <div class="col-md-3 mb-2">
                        <small class="text-muted"><?= $isAr ? 'المحافظة' : 'Governorate' ?></small><br>
                        <?= sanitize($student['governorate']) ?>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted"><?= $isAr ? 'العنوان' : 'Address' ?></small><br>
                        <?= sanitize($student['address']) ?>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted"><?= $isAr ? 'رقم الهاتف' : 'Phone' ?></small><br>
                        <?= sanitize($student['phone']) ?>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted"><?= $isAr ? 'القسم' : 'Department' ?></small><br>
                        <?= sanitize($student['section']) ?>
                    </div>
                </div>

                <!-- Images -->
                <hr>
                <div class="row">
                    <?php
                    $imageTypes = [
                        'card_image' => $isAr ? 'بطاقة المعهد' : 'Institute ID',
                        'national_id_image' => $isAr ? 'البطاقة الشخصية' : 'National ID',
                        'receipt_image' => $isAr ? 'إيصال الدفع' : 'Payment Receipt',
                    ];
                    foreach ($imageTypes as $field => $label):
                        $imgFile = $student[$field] ?? '';
                        $imgPath = "/uploads/project_{$projectId}/{$imgFile}";
                    ?>
                        <div class="col-md-4 text-center mb-2">
                            <small class="text-muted d-block mb-1"><?= $label ?></small>
                            <?php if ($imgFile): ?>
                                <img src="<?= $imgPath ?>" class="img-thumbnail student-image" 
                                     alt="<?= $label ?>" style="max-height: 150px; cursor: pointer;"
                                     onclick="showImageModal('<?= $imgPath ?>', '<?= $label ?>')">
                            <?php else: ?>
                                <span class="text-danger"><i class="bi bi-x-circle"></i> <?= $isAr ? 'غير متوفرة' : 'Missing' ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Review Actions (only if under_review) -->
    <?php if ($project['status'] === 'under_review'): ?>
        <div class="card shadow mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i><?= $isAr ? 'مراجعة المشروع' : 'Review Project' ?></h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label for="doctor_note" class="form-label fw-bold"><?= __('write_note') ?></label>
                    <textarea class="form-control" id="doctor_note" rows="3" 
                              placeholder="<?= $isAr ? 'اكتب ملاحظتك هنا...' : 'Write your note here...' ?>"></textarea>
                </div>
                <div id="review-error" class="alert alert-danger d-none"></div>
                <div id="review-success" class="alert alert-success d-none"></div>
                <div class="d-flex gap-3">
                    <button type="button" class="btn btn-success btn-lg flex-fill" id="btn-accept">
                        <i class="bi bi-check-circle me-2"></i><?= __('accept') ?>
                    </button>
                    <button type="button" class="btn btn-danger btn-lg flex-fill" id="btn-reject">
                        <i class="bi bi-x-circle me-2"></i><?= __('reject') ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="imageModalImg" class="img-fluid" alt="">
            </div>
        </div>
    </div>
</div>

<script>
function showImageModal(src, title) {
    document.getElementById('imageModalLabel').textContent = title;
    document.getElementById('imageModalImg').src = src;
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
}

<?php if ($project['status'] === 'under_review'): ?>
// Review actions
document.getElementById('btn-accept')?.addEventListener('click', () => reviewProject('accept'));
document.getElementById('btn-reject')?.addEventListener('click', () => reviewProject('reject'));

async function reviewProject(action) {
    const note = document.getElementById('doctor_note').value.trim();
    const errorEl = document.getElementById('review-error');
    const successEl = document.getElementById('review-success');

    // Confirm
    const confirmMsg = action === 'accept' 
        ? '<?= $isAr ? "هل أنت متأكد من قبول هذا المشروع؟" : "Are you sure you want to accept this project?" ?>'
        : '<?= $isAr ? "هل أنت متأكد من رفض هذا المشروع؟" : "Are you sure you want to reject this project?" ?>';
    
    if (!confirm(confirmMsg)) return;

    // Disable buttons
    document.getElementById('btn-accept').disabled = true;
    document.getElementById('btn-reject').disabled = true;

    try {
        const res = await fetch('/api/review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                project_id: <?= $projectId ?>,
                action: action,
                doctor_note: note
            })
        });

        const data = await res.json();

        if (data.success) {
            errorEl.classList.add('d-none');
            successEl.textContent = data.message;
            successEl.classList.remove('d-none');
            
            setTimeout(() => {
                window.location.href = '/professor/dashboard.php';
            }, 1500);
        } else {
            throw new Error(data.error || 'Operation failed');
        }
    } catch (err) {
        errorEl.textContent = err.message;
        errorEl.classList.remove('d-none');
        document.getElementById('btn-accept').disabled = false;
        document.getElementById('btn-reject').disabled = false;
    }
}
<?php endif; ?>
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
