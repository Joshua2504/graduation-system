/**
 * Step 1: Project Details
 * - Project Name
 * - Project Type
 * - 7 Student Names
 */
const Step1 = {
    render(state) {
        const isAr = state.lang === 'ar';
        const project = state.projectData || {};
        const types = state.projectTypes || [];
        const existingNames = (state.students || []).map(s => s.name);

        // Pre-fill student names from existing data or empty
        const studentNames = [];
        for (let i = 0; i < 7; i++) {
            studentNames.push(existingNames[i] || project.student_names?.[i] || '');
        }

        const labels = {
            title: isAr ? 'اسم المشروع' : 'Project Name',
            type: isAr ? 'نوع المشروع' : 'Project Type',
            selectType: isAr ? '-- اختر نوع المشروع --' : '-- Select Project Type --',
            studentNames: isAr ? 'أسماء الطلاب المشاركين (7 طلاب)' : 'Participating Student Names (7 students)',
            student: isAr ? 'الطالب' : 'Student',
            teamLeader: isAr ? '(قائد الفريق)' : '(Team Leader)',
            next: isAr ? 'التالي' : 'Next',
            stepTitle: isAr ? 'الخطوة 1: تفاصيل المشروع' : 'Step 1: Project Details',
        };

        // Project type is now a free text field

        let studentInputs = '';
        for (let i = 0; i < 7; i++) {
            const leaderBadge = i === 0 ? `<span class="badge bg-primary ms-2">${labels.teamLeader}</span>` : '';
            studentInputs += `
                <div class="mb-2">
                    <label class="form-label">${labels.student} ${i + 1} ${leaderBadge}</label>
                    <input type="text" class="form-control" id="student_name_${i}" 
                           value="${this._escape(studentNames[i])}" required
                           placeholder="${labels.student} ${i + 1}">
                </div>
            `;
        }

        return `
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>${labels.stepTitle}</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="project_title" class="form-label fw-bold">${labels.title} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="project_title" 
                               value="${this._escape(project.title || '')}" required>
                    </div>
                    <div class="mb-4">
                        <label for="project_type" class="form-label fw-bold">${labels.type} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="project_type"
                               value="${this._escape(project.type || '')}" required
                               placeholder="${isAr ? 'أدخل نوع المشروع' : 'Enter project type'}">
                    </div>
                    <hr>
                    <h6 class="fw-bold mb-3">${labels.studentNames}</h6>
                    ${studentInputs}
                    
                    <div id="step1-error" class="alert alert-danger mt-3 d-none"></div>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" class="btn btn-primary btn-lg" id="step1-next">
                            ${labels.next} <i class="bi bi-arrow-${isAr ? 'left' : 'right'} ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    },

    validate() {
        const title = document.getElementById('project_title')?.value.trim();
        const type = document.getElementById('project_type')?.value.trim();

        if (!title || !type) {
            return { valid: false, error: window.WIZARD_STATE.lang === 'ar' ? 'جميع الحقول مطلوبة' : 'All fields are required' };
        }

        const names = [];
        for (let i = 0; i < 7; i++) {
            const name = document.getElementById(`student_name_${i}`)?.value.trim();
            if (!name) {
                return { valid: false, error: window.WIZARD_STATE.lang === 'ar' ? `اسم الطالب ${i + 1} مطلوب` : `Student ${i + 1} name is required` };
            }
            names.push(name);
        }

        return { valid: true, data: { title, type, student_names: names } };
    },

    async save(data) {
        const state = window.WIZARD_STATE;

        // If project already exists, update it
        if (state.projectId) {
            const res = await fetch('/api/project.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    project_id: state.projectId,
                    title: data.title,
                    type: data.type
                })
            });
            const result = await res.json();
            if (!result.success && result.error) {
                throw new Error(result.error);
            }
            // Update local state
            state.projectData = { ...state.projectData, title: data.title, type: data.type };
            state.studentNames = data.student_names;
            return;
        }

        // Create new project
        const res = await fetch('/api/project.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title: data.title,
                type: data.type,
                student_names: data.student_names
            })
        });

        const result = await res.json();
        if (!result.success) {
            throw new Error(result.error || 'فشل إنشاء المشروع');
        }

        // Update state
        state.projectId = result.project_id;
        state.projectData = { title: data.title, type: data.type, status: 'draft' };
        state.studentNames = data.student_names;

        // Save to localStorage
        localStorage.setItem('grad_project_id', result.project_id);

        // Start autosave
        AutoSave.start();
    },

    _escape(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }
};
