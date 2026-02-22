/**
 * Steps 2–8: Student Details (reusable for each student)
 * Each step collects full info for one student + 3 image uploads
 */
const StudentStep = {
    /**
     * @param {object} state - wizard state
     * @param {number} studentIndex - 0..6
     */
    render(state, studentIndex) {
        const isAr = state.lang === 'ar';
        const stepNum = studentIndex + 2; // Step 2 = student 0, Step 8 = student 6
        const student = (state.students || [])[studentIndex] || {};
        const studentName = state.studentNames?.[studentIndex] || student.name || '';
        const governorates = state.governorates || [];

        const labels = {
            stepTitle: isAr ? `الخطوة ${stepNum}: بيانات الطالب ${studentIndex + 1}` : `Step ${stepNum}: Student ${studentIndex + 1} Data`,
            name: isAr ? 'اسم الطالب' : 'Student Name',
            studentCode: isAr ? 'كود الطالب' : 'Student Code',
            gender: isAr ? 'الجنس' : 'Gender',
            selectGender: isAr ? '-- اختر --' : '-- Select --',
            male: isAr ? 'ذكر' : 'Male',
            female: isAr ? 'أنثى' : 'Female',
            nationalId: isAr ? 'الرقم القومي' : 'National ID Number',
            birthDate: isAr ? 'تاريخ الميلاد' : 'Date of Birth',
            governorate: isAr ? 'المحافظة' : 'Governorate',
            selectGov: isAr ? '-- اختر المحافظة --' : '-- Select Governorate --',
            address: isAr ? 'العنوان' : 'Address',
            phone: isAr ? 'رقم الهاتف' : 'Phone Number',
            year: isAr ? 'السنة الدراسية' : 'Year',
            section: isAr ? 'القسم' : 'Department',
            cardImage: isAr ? 'صورة بطاقة المعهد' : 'Institute ID Card',
            nationalIdImage: isAr ? 'صورة البطاقة الشخصية' : 'National ID Card',
            receiptImage: isAr ? 'صورة إيصال دفع مشروع التخرج' : 'Payment Receipt',
            imgReq: isAr ? 'JPG أو PNG فقط - الحد الأقصى 5 ميجابايت' : 'JPG or PNG only - Max 5MB',
            next: isAr ? 'التالي' : 'Next',
            prev: isAr ? 'السابق' : 'Previous',
            teamLeader: isAr ? '(قائد الفريق)' : '(Team Leader)',
            uploadImage: isAr ? 'اختر صورة' : 'Choose Image',
        };

        const leaderBadge = studentIndex === 0 ? `<span class="badge bg-primary ms-2">${labels.teamLeader}</span>` : '';

        let govOptions = governorates.map(g => {
            const selected = student.governorate === g ? 'selected' : '';
            return `<option value="${g}" ${selected}>${g}</option>`;
        }).join('');

        const sections = [
            { ar: 'علوم الحاسب', en: 'Computer Science' },
            { ar: 'نظم المعلومات', en: 'Information Systems' },
            { ar: 'الذكاء الاصطناعي', en: 'Artificial Intelligence' },
            { ar: 'تكنولوجيا المعلومات', en: 'Information Technology' },
        ];
        let sectionOptions = sections.map(s => {
            const val = s[state.lang] || s.ar;
            const selected = student.section === val ? 'selected' : '';
            return `<option value="${val}" ${selected}>${val}</option>`;
        }).join('');

        // Image upload sections
        const imageTypes = [
            { key: 'card', label: labels.cardImage, field: 'card_image' },
            { key: 'national_id', label: labels.nationalIdImage, field: 'national_id_image' },
            { key: 'receipt', label: labels.receiptImage, field: 'receipt_image' },
        ];

        let imageUploads = imageTypes.map(img => {
            const existing = student[img.field] || '';
            const hasFile = !!existing;
            return `
                <div class="col-md-4 mb-3">
                    <div class="card h-100 ${hasFile ? 'border-success' : ''}">
                        <div class="card-body text-center">
                            <label class="form-label fw-bold">${img.label} <span class="text-danger">*</span></label>
                            <div class="upload-zone p-3 border rounded mb-2" id="zone_${img.key}">
                                ${hasFile 
                                    ? `<i class="bi bi-check-circle-fill text-success fs-3"></i><br><small class="text-success">${existing}</small>`
                                    : `<i class="bi bi-cloud-arrow-up fs-3 text-muted"></i><br><small class="text-muted">${labels.imgReq}</small>`
                                }
                            </div>
                            <input type="file" class="form-control form-control-sm" id="file_${img.key}" 
                                   accept="image/jpeg,image/png" data-type="${img.key}">
                            <input type="hidden" id="filename_${img.key}" value="${existing}">
                            <div class="progress mt-2 d-none" id="progress_${img.key}" style="height: 6px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                            </div>
                            <div class="text-danger small mt-1 d-none" id="error_${img.key}"></div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        return `
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-person-lines-fill me-2"></i>${labels.stepTitle} ${leaderBadge}
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">${labels.name} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="s_name" value="${this._escape(studentName)}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">${labels.studentCode} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="s_code" value="${this._escape(student.student_code || '')}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">${labels.gender} <span class="text-danger">*</span></label>
                            <select class="form-select" id="s_gender" required>
                                <option value="">${labels.selectGender}</option>
                                <option value="male" ${student.gender === 'male' ? 'selected' : ''}>${labels.male}</option>
                                <option value="female" ${student.gender === 'female' ? 'selected' : ''}>${labels.female}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">${labels.nationalId} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="s_national_id" value="${this._escape(student.national_id || '')}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">${labels.birthDate} <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="s_birth_date" value="${student.birth_date || ''}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">${labels.governorate} <span class="text-danger">*</span></label>
                            <select class="form-select" id="s_governorate" required>
                                <option value="">${labels.selectGov}</option>
                                ${govOptions}
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">${labels.address} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="s_address" value="${this._escape(student.address || '')}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">${labels.phone} <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="s_phone" value="${this._escape(student.phone || '')}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">${labels.year}</label>
                            <input type="text" class="form-control" value="${isAr ? 'الرابعة' : '4th'}" readonly disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">${labels.section} <span class="text-danger">*</span></label>
                            <select class="form-select" id="s_section" required>
                                <option value="">${labels.selectGender}</option>
                                ${sectionOptions}
                            </select>
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-bold mb-3"><i class="bi bi-images me-2"></i>${isAr ? 'الصور المطلوبة' : 'Required Images'}</h6>
                    <div class="row">
                        ${imageUploads}
                    </div>

                    <div id="student-step-error" class="alert alert-danger mt-3 d-none"></div>

                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary btn-lg" id="student-step-prev">
                            <i class="bi bi-arrow-${isAr ? 'right' : 'left'} me-2"></i>${labels.prev}
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" id="student-step-next">
                            ${labels.next} <i class="bi bi-arrow-${isAr ? 'left' : 'right'} ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Bind file upload event listeners after rendering
     */
    bindUploads(state, studentIndex) {
        const imageTypes = ['card', 'national_id', 'receipt'];
        
        imageTypes.forEach(type => {
            const fileInput = document.getElementById(`file_${type}`);
            if (!fileInput) return;

            fileInput.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                // Validate
                const error = Uploader.validate(file);
                const errorEl = document.getElementById(`error_${type}`);
                if (error) {
                    errorEl.textContent = error;
                    errorEl.classList.remove('d-none');
                    return;
                }
                errorEl.classList.add('d-none');

                // Get student code
                const studentCode = document.getElementById('s_code')?.value.trim();
                if (!studentCode) {
                    errorEl.textContent = state.lang === 'ar' ? 'يرجى إدخال كود الطالب أولاً' : 'Please enter student code first';
                    errorEl.classList.remove('d-none');
                    return;
                }

                // Show progress
                const progressEl = document.getElementById(`progress_${type}`);
                const progressBar = progressEl?.querySelector('.progress-bar');
                progressEl.classList.remove('d-none');
                progressBar.style.width = '0%';

                try {
                    const result = await Uploader.upload(
                        file,
                        state.projectId,
                        studentCode,
                        type,
                        (percent) => {
                            progressBar.style.width = percent + '%';
                        }
                    );

                    // Success
                    document.getElementById(`filename_${type}`).value = result.filename;
                    const zone = document.getElementById(`zone_${type}`);
                    zone.innerHTML = `<i class="bi bi-check-circle-fill text-success fs-3"></i><br><small class="text-success">${result.filename}</small>`;
                    zone.closest('.card').classList.add('border-success');
                    
                    setTimeout(() => progressEl.classList.add('d-none'), 1000);
                } catch (err) {
                    errorEl.textContent = err.message;
                    errorEl.classList.remove('d-none');
                    progressEl.classList.add('d-none');
                }
            });
        });
    },

    validate(studentIndex) {
        const isAr = window.WIZARD_STATE.lang === 'ar';
        const fields = {
            name: document.getElementById('s_name')?.value.trim(),
            student_code: document.getElementById('s_code')?.value.trim(),
            gender: document.getElementById('s_gender')?.value,
            national_id: document.getElementById('s_national_id')?.value.trim(),
            birth_date: document.getElementById('s_birth_date')?.value,
            governorate: document.getElementById('s_governorate')?.value,
            address: document.getElementById('s_address')?.value.trim(),
            phone: document.getElementById('s_phone')?.value.trim(),
            section: document.getElementById('s_section')?.value,
            card_image: document.getElementById('filename_card')?.value,
            national_id_image: document.getElementById('filename_national_id')?.value,
            receipt_image: document.getElementById('filename_receipt')?.value,
        };

        // Check each field
        for (const [key, val] of Object.entries(fields)) {
            if (!val) {
                const fieldLabels = {
                    name: isAr ? 'اسم الطالب' : 'Student name',
                    student_code: isAr ? 'كود الطالب' : 'Student code',
                    gender: isAr ? 'الجنس' : 'Gender',
                    national_id: isAr ? 'الرقم القومي' : 'National ID',
                    birth_date: isAr ? 'تاريخ الميلاد' : 'Date of Birth',
                    governorate: isAr ? 'المحافظة' : 'Governorate',
                    address: isAr ? 'العنوان' : 'Address',
                    phone: isAr ? 'رقم الهاتف' : 'Phone',
                    section: isAr ? 'القسم' : 'Department',
                    card_image: isAr ? 'صورة بطاقة المعهد' : 'Institute ID Card',
                    national_id_image: isAr ? 'صورة البطاقة الشخصية' : 'National ID Card',
                    receipt_image: isAr ? 'إيصال الدفع' : 'Payment Receipt',
                };
                const label = fieldLabels[key] || key;
                return {
                    valid: false,
                    error: isAr ? `${label} مطلوب` : `${label} is required`
                };
            }
        }

        return { valid: true, data: { ...fields, student_index: studentIndex } };
    },

    async save(data, projectId) {
        const res = await fetch('/api/student.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                project_id: projectId,
                ...data
            })
        });

        const result = await res.json();
        if (!result.success) {
            throw new Error(result.error || 'فشل حفظ بيانات الطالب');
        }

        return result;
    },

    _escape(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }
};
