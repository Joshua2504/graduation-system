/**
 * Shared Profile Image Upload — used on student/profile.php and professor/profile.php.
 *
 * Provides:
 *   uploadImage(type, file) — upload an image with progress bar, then reload
 *
 * Expects elements with IDs: progress-{type} containing a .progress-bar child.
 */

/**
 * Upload a profile image with XHR progress tracking
 * @param {string} type - Image type: card, national_id, receipt, or profile_picture
 * @param {File}   file - The file to upload
 */
function uploadImage(type, file) {
    if (!file) return;
    const progressEl = document.getElementById('progress-' + type);
    const progressBar = progressEl.querySelector('.progress-bar');
    progressEl.classList.remove('d-none');
    progressBar.style.width = '0%';

    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/profile', true);

    xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
            progressBar.style.width = Math.round((e.loaded / e.total) * 100) + '%';
        }
    };

    xhr.onload = () => {
        try {
            const data = JSON.parse(xhr.responseText);
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Upload failed');
                progressEl.classList.add('d-none');
            }
        } catch {
            alert('Upload failed');
            progressEl.classList.add('d-none');
        }
    };

    xhr.onerror = () => {
        alert('Upload failed');
        progressEl.classList.add('d-none');
    };

    xhr.send(formData);
}
