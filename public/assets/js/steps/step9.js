/**
 * Step 9: Confirmation & Final Submit
 */
const Step9 = {
    render(state) {
        const isAr = state.lang === 'ar';

        const labels = {
            stepTitle: isAr ? 'الخطوة 9: التأكيد والإرسال' : 'Step 9: Confirmation & Submit',
            warning: isAr
                ? 'تنبيه هام: في حالة قبول المشروع، لن يمكن تعديله بشكل دائم. في حالة الرفض، يمكن التعديل وإعادة التقديم.'
                : 'Important: If the project is accepted, it cannot be edited permanently. If rejected, it can be edited and resubmitted.',
            confirm: isAr
                ? 'أؤكد أن جميع البيانات المُدخلة صحيحة'
                : 'I confirm that all entered data is correct',
            submit: isAr ? 'تقديم المشروع' : 'Submit Project',
            prev: isAr ? 'السابق' : 'Previous',
            uploading: isAr ? 'جاري رفع وتأكيد الملفات...' : 'Uploading and confirming files...',
            successTitle: isAr ? 'تم التقديم بنجاح!' : 'Successfully Submitted!',
            successMsg: isAr
                ? 'تم تقديم مشروعك بنجاح وهو الآن قيد المراجعة. يرجى التحقق خلال 24 ساعة.'
                : 'Your project has been successfully submitted and is under review. Please check back within 24 hours.',
            goToDashboard: isAr ? 'الذهاب للوحة التحكم' : 'Go to Dashboard',
            projectSummary: isAr ? 'ملخص المشروع' : 'Project Summary',
            projectName: isAr ? 'اسم المشروع' : 'Project Name',
            projectType: isAr ? 'نوع المشروع' : 'Project Type',
            students: isAr ? 'الطلاب' : 'Students',
        };

        // Build summary
        const project = state.projectData || {};
        const studentNames = state.studentNames || [];
        let summaryList = studentNames.map((name, i) => {
            const badge = i === 0 ? `<span class="badge bg-primary ms-2">${isAr ? 'قائد الفريق' : 'Team Leader'}</span>` : '';
            return `<li class="list-group-item">${i + 1}. ${this._escape(name)} ${badge}</li>`;
        }).join('');

        return `
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-check2-square me-2"></i>${labels.stepTitle}</h5>
                </div>
                <div class="card-body p-4" id="step9-form">
                    <!-- Summary -->
                    <div class="card mb-4">
                        <div class="card-header"><h6 class="mb-0">${labels.projectSummary}</h6></div>
                        <div class="card-body">
                            <p><strong>${labels.projectName}:</strong> ${this._escape(project.title || '')}</p>
                            <p><strong>${labels.projectType}:</strong> ${this._escape(project.type || '')}</p>
                            <p class="mb-1"><strong>${labels.students}:</strong></p>
                            <ul class="list-group list-group-flush">
                                ${summaryList}
                            </ul>
                        </div>
                    </div>

                    <!-- Warning -->
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>${labels.warning}</strong>
                    </div>

                    <!-- Checkbox -->
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="confirm_checkbox">
                        <label class="form-check-label fw-bold" for="confirm_checkbox">
                            ${labels.confirm} <span class="text-danger">*</span>
                        </label>
                    </div>

                    <div id="step9-error" class="alert alert-danger d-none"></div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary btn-lg" id="step9-prev">
                            <i class="bi bi-arrow-${isAr ? 'right' : 'left'} me-2"></i>${labels.prev}
                        </button>
                        <button type="button" class="btn btn-success btn-lg" id="step9-submit" disabled>
                            <i class="bi bi-send me-2"></i>${labels.submit}
                        </button>
                    </div>
                </div>

                <!-- Progress (hidden initially) -->
                <div class="card-body p-4 d-none" id="step9-progress">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <h5>${labels.uploading}</h5>
                        <div class="progress mt-3" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                 id="submit-progress-bar" style="width: 0%">0%</div>
                        </div>
                    </div>
                </div>

                <!-- Success (hidden initially) -->
                <div class="card-body p-4 d-none" id="step9-success">
                    <div class="text-center py-5">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                        <h3 class="mt-3 text-success">${labels.successTitle}</h3>
                        <p class="text-muted fs-5 mt-2">${labels.successMsg}</p>
                        <a href="/student/dashboard.php" class="btn btn-primary btn-lg mt-3">
                            <i class="bi bi-house-door me-2"></i>${labels.goToDashboard}
                        </a>
                    </div>
                </div>
            </div>
        `;
    },

    bind() {
        // Enable submit only when checkbox is checked
        const checkbox = document.getElementById('confirm_checkbox');
        const submitBtn = document.getElementById('step9-submit');

        if (checkbox && submitBtn) {
            checkbox.addEventListener('change', () => {
                submitBtn.disabled = !checkbox.checked;
            });
        }
    },

    async submit(projectId) {
        const formSection = document.getElementById('step9-form');
        const progressSection = document.getElementById('step9-progress');
        const successSection = document.getElementById('step9-success');
        const progressBar = document.getElementById('submit-progress-bar');

        // Switch to progress view
        formSection.classList.add('d-none');
        progressSection.classList.remove('d-none');

        // Animate progress bar (cosmetic — images already uploaded)
        let progress = 0;
        const totalImages = 21;
        const animateProgress = () => {
            return new Promise(resolve => {
                const interval = setInterval(() => {
                    progress += 1;
                    const percent = Math.min(Math.round((progress / totalImages) * 90), 90);
                    progressBar.style.width = percent + '%';
                    progressBar.textContent = percent + '%';
                    if (progress >= totalImages) {
                        clearInterval(interval);
                        resolve();
                    }
                }, 100);
            });
        };

        try {
            // Start animation
            const animPromise = animateProgress();

            // Submit to API
            const res = await fetch('/api/submit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ project_id: projectId })
            });

            const result = await res.json();

            // Wait for animation to finish
            await animPromise;

            if (!result.success) {
                throw new Error(result.error || 'فشل تقديم المشروع');
            }

            // Complete progress
            progressBar.style.width = '100%';
            progressBar.textContent = '100%';

            // Stop autosave
            AutoSave.stop();

            // Clear localStorage
            localStorage.removeItem('grad_project_id');

            // Show success after a short delay
            setTimeout(() => {
                progressSection.classList.add('d-none');
                successSection.classList.remove('d-none');
            }, 500);

        } catch (err) {
            // Show error — go back to form
            progressSection.classList.add('d-none');
            formSection.classList.remove('d-none');
            const errorEl = document.getElementById('step9-error');
            errorEl.textContent = err.message;
            errorEl.classList.remove('d-none');
        }
    },

    _escape(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }
};
