/**
 * Auto-save module — debounced save of project data
 */
const AutoSave = {
    _timer: null,
    _interval: 15000, // 15 seconds
    _enabled: false,

    /**
     * Start autosave. Call after project is created (has projectId).
     */
    start() {
        if (this._enabled) return; // prevent double-start
        this._enabled = true;
        console.log('[AutoSave] Started');
        this._scheduleNext();
    },

    stop() {
        this._enabled = false;
        if (this._timer) {
            clearTimeout(this._timer);
            this._timer = null;
        }
        console.log('[AutoSave] Stopped');
    },

    _scheduleNext() {
        if (!this._enabled) return;
        this._timer = setTimeout(() => this._doSave(), this._interval);
    },

    async _doSave() {
        if (!this._enabled) return;

        const state = window.WIZARD_STATE;
        if (!state || !state.projectId) {
            this._scheduleNext();
            return;
        }

        const step = state.currentStep || 1;

        try {
            let saved = false;

            if (step === 1) {
                // Save project data (title, type)
                saved = await this._saveProjectData(state);
            } else if (step >= 2 && step <= 8) {
                // Save student data for current step
                saved = await this._saveStudentData(state, step);
            }
            // Step 9 = confirmation, nothing to autosave

            if (saved) {
                const toastEl = document.getElementById('autosave-toast');
                if (toastEl) {
                    const toast = new bootstrap.Toast(toastEl, { delay: 2000 });
                    toast.show();
                }
                console.log('[AutoSave] Saved successfully (step ' + step + ')');
            }
        } catch (e) {
            console.warn('[AutoSave] Failed:', e);
        }

        this._scheduleNext();
    },

    async _saveProjectData(state) {
        const titleEl = document.getElementById('project_title');
        const typeEl = document.getElementById('project_type');
        const title = titleEl ? titleEl.value.trim() : (state.projectData?.title || '');
        const type = typeEl ? typeEl.value.trim() : (state.projectData?.type || '');

        if (!title) return false;

        const response = await fetch('/api/project.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                project_id: state.projectId,
                title: title,
                type: type
            })
        });

        const data = await response.json();
        if (data.success && state.projectData) {
            state.projectData.title = title;
            state.projectData.type = type;
        }
        return data.success || false;
    },

    async _saveStudentData(state, step) {
        const studentIndex = step - 2;

        // Collect whatever is filled in the DOM
        const fields = {
            project_id: state.projectId,
            student_index: studentIndex,
            autosave: true,
            name: document.getElementById('s_name')?.value.trim() || '',
            student_code: document.getElementById('s_code')?.value.trim() || '',
            gender: document.getElementById('s_gender')?.value || '',
            national_id: document.getElementById('s_national_id')?.value.trim() || '',
            birth_date: document.getElementById('s_birth_date')?.value || '',
            governorate: document.getElementById('s_governorate')?.value || '',
            address: document.getElementById('s_address')?.value.trim() || '',
            phone: document.getElementById('s_phone')?.value.trim() || '',
            section: document.getElementById('s_section')?.value.trim() || '',
            card_image: document.getElementById('filename_card')?.value || '',
            national_id_image: document.getElementById('filename_national_id')?.value || '',
            receipt_image: document.getElementById('filename_receipt')?.value || '',
        };

        // Only autosave if at least name or student_code is filled
        if (!fields.name && !fields.student_code) return false;

        const response = await fetch('/api/student.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(fields)
        });

        const data = await response.json();
        if (data.success) {
            // Update state
            if (!state.students) state.students = [];
            state.students[studentIndex] = { ...state.students[studentIndex], ...fields };
        }
        return data.success || false;
    },

    /**
     * Save immediately (e.g., before navigating away)
     */
    async saveNow() {
        if (this._timer) {
            clearTimeout(this._timer);
        }
        await this._doSave();
    }
};
