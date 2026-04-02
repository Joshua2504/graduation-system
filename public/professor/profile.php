<?php
/**
 * Professor Profile — edit personal data & upload profile picture
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_role('doctor');

$userId = current_user_id();
$user = getUserProfile($userId);
$isAr = getLang() === 'ar';
$departments = getDepartments();

$pageTitle = __('my_profile');
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Profile Header -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="mb-0">
                    <i class="bi bi-person-circle me-2"></i><?= __('my_profile') ?>
                </h4>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <?= __('professor_profile_info') ?>
            </div>

            <!-- Profile Picture Card -->
            <?php
                $profilePicsEnabled = !empty(getSettings()['profile_pictures_enabled'] ?? 1);
                $profilePic = $user['profile_picture'] ?? '';
                $profilePicUrl = $profilePic ? secureFileUrl($userId, $profilePic) : '';
            ?>
            <?php if ($profilePicsEnabled): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-camera me-2"></i><?= __('profile_picture') ?></h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-4">
                        <div class="profile-picture-wrapper text-center">
                            <?php if ($profilePic): ?>
                                <img src="<?= $profilePicUrl ?>" class="profile-picture-lg rounded-circle" 
                                     id="profile-picture-preview" alt="<?= __('profile_picture') ?>">
                            <?php else: ?>
                                <div class="profile-picture-lg rounded-circle bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center" 
                                     id="profile-picture-placeholder">
                                    <i class="bi bi-person-fill text-secondary" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-2"><small><?= __('profile_picture_hint') ?></small></p>
                            <p class="text-muted mb-2"><small><?= __('image_requirements') ?></small></p>
                            <input type="file" class="d-none" id="file-profile_picture" accept="image/jpeg,image/png"
                                   onchange="uploadImage('profile_picture', this.files[0])">
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="document.getElementById('file-profile_picture').click()">
                                <i class="bi bi-upload me-1"></i><?= $profilePic ? __('change') : __('upload_image') ?>
                            </button>
                            <div class="progress mt-2 d-none" id="progress-profile_picture" style="height: 4px;">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Personal Info Card -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-vcard me-2"></i><?= __('personal_info') ?></h5>
                </div>
                <div class="card-body p-4">
                    <form id="profileForm">
                        <div class="row g-3">
                            <!-- Name (read-only from registration) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('name') ?></label>
                                <input type="text" class="form-control" value="<?= sanitize($user['name']) ?>" readonly disabled>
                            </div>
                            <!-- Email (read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('email') ?></label>
                                <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" readonly disabled>
                            </div>
                            <hr>
                            <!-- Gender -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('gender') ?></label>
                                <select class="form-select" name="gender" id="gender">
                                    <option value=""><?= __('select_option_full') ?></option>
                                    <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>><?= __('male') ?></option>
                                    <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>><?= __('female') ?></option>
                                </select>
                            </div>
                            <!-- Phone -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('phone') ?></label>
                                <input type="tel" class="form-control" name="phone" id="phone" 
                                       value="<?= sanitize($user['phone'] ?? '') ?>" 
                                       maxlength="11" pattern="\d{11}" placeholder="<?= __('eleven_digits') ?>"
                                       oninvalid="this.setCustomValidity('<?= htmlspecialchars(__('phone_format'), ENT_QUOTES, 'UTF-8') ?>')"
                                       oninput="this.setCustomValidity('')">
                            </div>
                            <!-- Section/Department -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('section') ?></label>
                                <select class="form-select" name="section" id="section"
                                        oninvalid="this.setCustomValidity('<?= __('select_department') ?>')"
                                        oninput="this.setCustomValidity('')">
                                    <option value=""><?= __('select_department') ?></option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= sanitize($dept['name']) ?>" <?= ($user['section'] ?? '') === $dept['name'] ? 'selected' : '' ?>><?= sanitize($dept['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div id="profile-alert" class="alert d-none mt-3"></div>

                        <button type="submit" class="btn btn-primary mt-4 w-100">
                            <i class="bi bi-check-lg me-2"></i><?= __('save') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Profile form submission
document.getElementById('profileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const alertEl = document.getElementById('profile-alert');
    alertEl.classList.add('d-none');

    const data = {};
    ['gender', 'phone', 'section'].forEach(f => {
        data[f] = document.getElementById(f)?.value?.trim() || '';
    });

    try {
        const res = await fetch('/api/profile', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            alertEl.className = 'alert alert-success mt-3';
            alertEl.innerHTML = '<i class="bi bi-check-circle me-2"></i>' + result.message;
            alertEl.classList.remove('d-none');
        } else {
            throw new Error(result.error || 'Error');
        }
    } catch (err) {
        alertEl.className = 'alert alert-danger mt-3';
        alertEl.innerHTML = '<i class="bi bi-x-circle me-2"></i>' + err.message;
        alertEl.classList.remove('d-none');
    }
});
</script>
<script src="/assets/js/profile-upload.js"></script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
