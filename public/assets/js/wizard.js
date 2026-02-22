/**
 * Wizard Controller — orchestrates all 9 steps
 *
 * Steps:
 *   1      → Project Details (Step1)
 *   2..8   → Student Data    (StudentStep with studentIndex 0..6)
 *   9      → Confirmation    (Step9)
 */
(function () {
    'use strict';

    const state = window.WIZARD_STATE;
    const root = document.getElementById('wizard-root');

    if (!state || !root) return;

    // Determine initial step
    let currentStep = 1;

    // If resuming (project exists), figure out which step to go to
    if (state.projectId && state.students && state.students.length > 0) {
        // Go to the step after the last completed student
        const completedStudents = state.students.length;
        if (completedStudents >= 7) {
            currentStep = 9; // All students done, go to confirmation
        } else {
            currentStep = completedStudents + 2; // e.g., 3 students done → step 5
        }
    } else if (state.projectId) {
        // Project exists but no students yet
        currentStep = 2;
    }

    // Also check localStorage for project_id if not in state
    if (!state.projectId) {
        const savedId = localStorage.getItem('grad_project_id');
        if (savedId) {
            // Try to load from API
            loadProjectFromApi(parseInt(savedId));
            return;
        }
    }

    // Start autosave if project exists
    if (state.projectId && state.projectData?.status === 'draft') {
        AutoSave.start();
    }

    renderCurrentStep();

    // ——————————————————————————————————————————————
    // Functions
    // ——————————————————————————————————————————————

    async function loadProjectFromApi(projectId) {
        try {
            const res = await fetch(`/api/project.php?id=${projectId}`);
            const data = await res.json();
            if (data.project) {
                state.projectId = data.project.id;
                state.projectData = data.project;
                state.students = data.students || [];

                // Rebuild student names
                state.studentNames = state.students.map(s => s.name);

                // Determine step
                const count = data.student_count || 0;
                if (count >= 7) {
                    currentStep = 9;
                } else if (count > 0) {
                    currentStep = count + 2;
                } else {
                    currentStep = 2;
                }

                if (state.projectData.status === 'draft') {
                    AutoSave.start();
                }

                renderCurrentStep();
            } else {
                localStorage.removeItem('grad_project_id');
                currentStep = 1;
                renderCurrentStep();
            }
        } catch (e) {
            console.error('Failed to load project:', e);
            localStorage.removeItem('grad_project_id');
            currentStep = 1;
            renderCurrentStep();
        }
    }

    function renderCurrentStep() {
        // Build step indicator
        const indicator = buildStepIndicator(currentStep);

        let html = '';

        if (currentStep === 1) {
            html = Step1.render(state);
        } else if (currentStep >= 2 && currentStep <= 8) {
            const studentIndex = currentStep - 2;
            html = StudentStep.render(state, studentIndex);
        } else if (currentStep === 9) {
            html = Step9.render(state);
        }

        root.innerHTML = indicator + html;

        // Bind events
        bindStepEvents();
    }

    function buildStepIndicator(active) {
        const isAr = state.lang === 'ar';
        let steps = '';

        for (let i = 1; i <= 9; i++) {
            let label = '';
            if (i === 1) label = isAr ? 'المشروع' : 'Project';
            else if (i >= 2 && i <= 8) label = `${isAr ? 'طالب' : 'S'}${i - 1}`;
            else if (i === 9) label = isAr ? 'تأكيد' : 'Confirm';

            let className = 'step-item';
            if (i < active) className += ' completed';
            else if (i === active) className += ' active';

            steps += `
                <div class="${className}">
                    <div class="step-circle">${i < active ? '<i class="bi bi-check"></i>' : i}</div>
                    <div class="step-label">${label}</div>
                </div>
            `;
        }

        return `<div class="step-indicator mb-4">${steps}</div>`;
    }

    function bindStepEvents() {
        if (currentStep === 1) {
            const nextBtn = document.getElementById('step1-next');
            if (nextBtn) {
                nextBtn.addEventListener('click', handleStep1Next);
            }
        } else if (currentStep >= 2 && currentStep <= 8) {
            const studentIndex = currentStep - 2;

            // Bind file uploads
            StudentStep.bindUploads(state, studentIndex);

            // Next button
            const nextBtn = document.getElementById('student-step-next');
            if (nextBtn) {
                nextBtn.addEventListener('click', () => handleStudentStepNext(studentIndex));
            }

            // Previous button
            const prevBtn = document.getElementById('student-step-prev');
            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    currentStep--;
                    renderCurrentStep();
                    window.scrollTo(0, 0);
                });
            }
        } else if (currentStep === 9) {
            Step9.bind();

            // Submit button
            const submitBtn = document.getElementById('step9-submit');
            if (submitBtn) {
                submitBtn.addEventListener('click', () => {
                    Step9.submit(state.projectId);
                });
            }

            // Previous button
            const prevBtn = document.getElementById('step9-prev');
            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    currentStep = 8;
                    renderCurrentStep();
                    window.scrollTo(0, 0);
                });
            }
        }
    }

    async function handleStep1Next() {
        const errorEl = document.getElementById('step1-error');
        const result = Step1.validate();

        if (!result.valid) {
            errorEl.textContent = result.error;
            errorEl.classList.remove('d-none');
            return;
        }

        errorEl.classList.add('d-none');
        const nextBtn = document.getElementById('step1-next');
        nextBtn.disabled = true;
        nextBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + 
                           (state.lang === 'ar' ? 'جاري الحفظ...' : 'Saving...');

        try {
            await Step1.save(result.data);
            currentStep = 2;
            renderCurrentStep();
            window.scrollTo(0, 0);
        } catch (err) {
            errorEl.textContent = err.message;
            errorEl.classList.remove('d-none');
            nextBtn.disabled = false;
            nextBtn.innerHTML = (state.lang === 'ar' ? 'التالي' : 'Next') + 
                               ` <i class="bi bi-arrow-${state.lang === 'ar' ? 'left' : 'right'} ms-2"></i>`;
        }
    }

    async function handleStudentStepNext(studentIndex) {
        const errorEl = document.getElementById('student-step-error');
        const result = StudentStep.validate(studentIndex);

        if (!result.valid) {
            errorEl.textContent = result.error;
            errorEl.classList.remove('d-none');
            return;
        }

        errorEl.classList.add('d-none');
        const nextBtn = document.getElementById('student-step-next');
        nextBtn.disabled = true;
        nextBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + 
                           (state.lang === 'ar' ? 'جاري الحفظ...' : 'Saving...');

        try {
            await StudentStep.save(result.data, state.projectId);

            // Update state with saved student
            if (!state.students) state.students = [];
            state.students[studentIndex] = result.data;

            // Update student names
            if (!state.studentNames) state.studentNames = [];
            state.studentNames[studentIndex] = result.data.name;

            currentStep++;
            renderCurrentStep();
            window.scrollTo(0, 0);
        } catch (err) {
            errorEl.textContent = err.message;
            errorEl.classList.remove('d-none');
            nextBtn.disabled = false;
            const isAr = state.lang === 'ar';
            nextBtn.innerHTML = (isAr ? 'التالي' : 'Next') + 
                               ` <i class="bi bi-arrow-${isAr ? 'left' : 'right'} ms-2"></i>`;
        }
    }

})();
