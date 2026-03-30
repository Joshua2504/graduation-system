<?php
/**
 * Student Profile — edit personal data & upload documents
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_role('student');

$userId = current_user_id();
$user = getUserProfile($userId);
$isAr = getLang() === 'ar';
$governorates = getGovernorates();
$departments = getDepartments();
$profileComplete = isProfileComplete($user);

$pageTitle = __('my_profile');
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Profile Header -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="mb-0">
                    <i class="bi bi-person-circle me-2"></i><?= __('my_profile') ?>
                </h4>
                <span class="badge fs-6 <?= $profileComplete ? 'bg-success' : 'bg-warning text-dark' ?>">
                    <i class="bi bi-<?= $profileComplete ? 'check-circle' : 'exclamation-triangle' ?> me-1"></i>
                    <?= $profileComplete ? __('profile_complete') : __('profile_incomplete') ?>
                </span>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <?= __('profile_info') ?>
            </div>

            <!-- Profile Picture Card -->
            <?php
                $profilePicsEnabled = !empty($settings['profile_pictures_enabled'] ?? getSettings()['profile_pictures_enabled'] ?? 1);
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
                            <!-- Student Code (read-only from registration) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('student_code') ?></label>
                                <input type="text" class="form-control" value="<?= sanitize($user['student_code'] ?? '') ?>" readonly disabled>
                            </div>
                            <!-- Email (read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('email') ?></label>
                                <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" readonly disabled>
                            </div>
                            <!-- Year -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('year') ?> <span class="text-danger">*</span></label>
                                <select class="form-select" name="year" id="year" required>
                                    <option value=""><?= __('select_option_full') ?></option>
                                    <option value="1st" <?= ($user['year'] ?? '') === '1st' ? 'selected' : '' ?>><?= __('first_year') ?></option>
                                    <option value="2nd" <?= ($user['year'] ?? '') === '2nd' ? 'selected' : '' ?>><?= __('second_year') ?></option>
                                    <option value="3rd" <?= ($user['year'] ?? '') === '3rd' ? 'selected' : '' ?>><?= __('third_year') ?></option>
                                    <option value="4th" <?= ($user['year'] ?? '') === '4th' ? 'selected' : '' ?>><?= __('fourth_year') ?></option>
                                </select>
                            </div>
                            <hr>
                            <!-- Gender -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('gender') ?> <span class="text-danger">*</span></label>
                                <select class="form-select" name="gender" id="gender" required>
                                    <option value=""><?= __('select_option_full') ?></option>
                                    <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>><?= __('male') ?></option>
                                    <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>><?= __('female') ?></option>
                                </select>
                            </div>
                            <!-- National ID -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('national_id') ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="national_id" id="national_id" 
                                       value="<?= sanitize($user['national_id'] ?? '') ?>" 
                                       maxlength="14" pattern="\d{14}" placeholder="<?= __('fourteen_digits') ?>" required>
                            </div>
                            <!-- Birth Date -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('birth_date') ?> <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="birth_date" id="birth_date" 
                                       value="<?= sanitize($user['birth_date'] ?? '') ?>" required>
                            </div>
                            <!-- Governorate -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('governorate') ?> <span class="text-danger">*</span></label>
                                <select class="form-select" name="governorate" id="governorate" required>
                                    <option value=""><?= __('select_governorate_full') ?></option>
                                    <?php foreach ($governorates as $gov): ?>
                                        <option value="<?= $gov ?>" <?= ($user['governorate'] ?? '') === $gov ? 'selected' : '' ?>><?= $gov ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Address -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('address') ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="address" id="address" 
                                       value="<?= sanitize($user['address'] ?? '') ?>" required>
                            </div>
                            <!-- Phone -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('phone') ?> <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="phone" id="phone" 
                                       value="<?= sanitize($user['phone'] ?? '') ?>" 
                                       maxlength="11" pattern="\d{11}" placeholder="<?= __('eleven_digits') ?>" required>
                            </div>
                            <!-- Section/Department -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= __('section') ?> <span class="text-danger">*</span></label>
                                <select class="form-select" name="section" id="section" required
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

            <!-- Documents Card -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-image me-2"></i><?= __('documents') ?></h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-3"><small><?= __('image_requirements') ?></small></p>
                    <div class="row g-4">
                        <?php
                        $imageTypes = [
                            'card' => ['label' => __('card_image'), 'field' => 'card_image'],
                            'national_id' => ['label' => __('national_id_image'), 'field' => 'national_id_image'],
                            'receipt' => ['label' => __('receipt_image'), 'field' => 'receipt_image'],
                        ];
                        foreach ($imageTypes as $type => $info):
                            $imgFile = $user[$info['field']] ?? '';
                            $imgPath = $imgFile ? secureFileUrl($userId, $imgFile) : '';
                        ?>
                            <div class="col-md-4">
                                <div class="card h-100 <?= $imgFile ? 'border-success' : '' ?>">
                                    <div class="card-body text-center">
                                        <h6 class="mb-3"><?= $info['label'] ?></h6>
                                        <div class="upload-zone p-3 mb-2" id="zone-<?= $type ?>">
                                            <?php if ($imgFile): ?>
                                                <img src="<?= $imgPath ?>" class="img-thumbnail mb-2" style="max-height: 120px;" 
                                                     id="preview-<?= $type ?>">
                                            <?php else: ?>
                                                <i class="bi bi-cloud-arrow-up fs-2 text-muted" id="icon-<?= $type ?>"></i>
                                                <p class="small text-muted mb-0" id="text-<?= $type ?>"><?= __('upload_image') ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" class="d-none" id="file-<?= $type ?>" accept="image/jpeg,image/png"
                                               onchange="uploadImage('<?= $type ?>', this.files[0])">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="document.getElementById('file-<?= $type ?>').click()">
                                            <i class="bi bi-upload me-1"></i><?= $imgFile ? __('change') : __('upload_image') ?>
                                        </button>
                                        <div class="progress mt-2 d-none" id="progress-<?= $type ?>" style="height: 4px;">
                                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
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
    ['gender', 'national_id', 'birth_date', 'governorate', 'address', 'phone', 'year', 'section'].forEach(f => {
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
            // Reload after a beat to update completion badge
            setTimeout(() => location.reload(), 1000);
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
