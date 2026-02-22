/**
 * File Uploader — handles XHR upload with progress bar
 */
const Uploader = {
    /**
     * Upload a file to the server
     * @param {File} file - The file to upload
     * @param {number} projectId
     * @param {string} studentCode
     * @param {string} type - 'card' | 'national_id' | 'receipt'
     * @param {function} onProgress - callback(percent)
     * @returns {Promise<{success, filename, path}>}
     */
    upload(file, projectId, studentCode, type, onProgress) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('project_id', projectId);
            formData.append('student_code', studentCode);
            formData.append('type', type);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/api/upload.php', true);

            // Track upload progress
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && onProgress) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    onProgress(percent);
                }
            });

            xhr.addEventListener('load', () => {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (xhr.status === 200 && response.success) {
                        resolve(response);
                    } else {
                        reject(new Error(response.error || 'فشل رفع الملف'));
                    }
                } catch (e) {
                    reject(new Error('استجابة غير صالحة من الخادم'));
                }
            });

            xhr.addEventListener('error', () => {
                reject(new Error('فشل الاتصال بالخادم'));
            });

            xhr.addEventListener('abort', () => {
                reject(new Error('تم إلغاء الرفع'));
            });

            xhr.send(formData);
        });
    },

    /**
     * Validate a file before upload
     * @param {File} file
     * @returns {string|null} Error message or null if valid
     */
    validate(file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png'];

        if (!allowedTypes.includes(file.type)) {
            return window.WIZARD_STATE?.lang === 'ar'
                ? 'نوع الملف غير مسموح - يجب أن يكون JPG أو PNG'
                : 'Invalid file type - must be JPG or PNG';
        }

        if (file.size > maxSize) {
            return window.WIZARD_STATE?.lang === 'ar'
                ? 'حجم الملف يتجاوز 5 ميجابايت'
                : 'File size exceeds 5MB';
        }

        return null;
    }
};
