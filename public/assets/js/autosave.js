/**
 * Auto-save module — debounced save of project data
 */
const AutoSave = {
    _timer: null,
    _interval: 30000, // 30 seconds
    _enabled: false,

    /**
     * Start autosave. Call after project is created (has projectId).
     */
    start() {
        this._enabled = true;
        this._scheduleNext();
    },

    stop() {
        this._enabled = false;
        if (this._timer) {
            clearTimeout(this._timer);
            this._timer = null;
        }
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

        // Only auto-save step 1 data (title, type)
        try {
            const response = await fetch('/api/project.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    project_id: state.projectId,
                    title: state.projectData?.title || '',
                    type: state.projectData?.type || ''
                })
            });

            const data = await response.json();
            if (data.success) {
                // Show toast
                const toastEl = document.getElementById('autosave-toast');
                if (toastEl) {
                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();
                }
            }
        } catch (e) {
            console.warn('Autosave failed:', e);
        }

        this._scheduleNext();
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
