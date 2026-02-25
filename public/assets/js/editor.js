/**
 * Shared Rich Text Editor — used on project pages and dashboard create modals.
 *
 * Provides:
 *   editorCmd(command, targetId?)     — execCommand wrapper
 *   editorInsertLink(targetId?)       — insert link via prompt
 *   editorUploadFile(file, projectId) — upload image to description API
 *   initEditor(editorId, projectId)   — attach paste/drag-drop/resize listeners
 *   toggleEditMode(editing)           — toggle view/edit panels
 *
 * Global: PROJECT_ID must be set before calling editorUploadFile or initEditor
 *         with a project context.
 * 
 * For dashboard create modals (no project yet), use editorCmd/editorInsertLink
 * with targetId parameter.
 */

/**
 * Execute a rich text command (bold, italic, etc.)
 * @param {string} command - The execCommand name
 * @param {string} [targetId='editDescription'] - The editor element ID to re-focus
 */
function editorCmd(command, targetId) {
    document.execCommand(command, false, null);
    const el = document.getElementById(targetId || 'editDescription');
    if (el) el.focus();
}

/**
 * Insert a link via prompt
 * @param {string} [targetId] - Optional editor element ID (unused, kept for signature compat)
 */
function editorInsertLink(targetId) {
    const url = prompt('URL:', 'https://');
    if (url) document.execCommand('createLink', false, url);
}

/**
 * Upload an image file into the editor (for project descriptions)
 * @param {File} file - The image file
 * @param {number} projectId - The project ID
 * @param {string} [editorId='editDescription'] - The contenteditable element ID
 * @param {string} [uploadingText='Uploading...'] - Placeholder text while uploading
 */
async function editorUploadFile(file, projectId, editorId, uploadingText) {
    if (!file || !file.type.startsWith('image/')) return;
    const editor = document.getElementById(editorId || 'editDescription');
    if (!editor) return;

    const placeholder = document.createElement('span');
    placeholder.className = 'upload-placeholder';
    placeholder.textContent = uploadingText || 'Uploading...';
    editor.appendChild(placeholder);

    const fd = new FormData();
    fd.append('file', file);
    fd.append('project_id', projectId);
    try {
        const res = await fetch('/api/description-upload', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            const img = document.createElement('img');
            img.src = data.url;
            img.alt = file.name;
            img.className = 'desc-img';
            placeholder.replaceWith(img);
            editor.appendChild(document.createElement('br'));
        } else {
            placeholder.remove();
            alert(data.error);
        }
    } catch (err) {
        placeholder.remove();
        alert(err.message);
    }
    // Reset file input
    const fi = document.getElementById('editorFileInput');
    if (fi) fi.value = '';
}

/**
 * Toggle between view mode and edit mode for project details
 */
function toggleEditMode(editing) {
    document.getElementById('projectViewMode')?.classList.toggle('d-none', editing);
    document.getElementById('projectEditMode')?.classList.toggle('d-none', !editing);
    if (editing) {
        document.getElementById('editTitle')?.focus();
    }
}

/**
 * Initialize editor behaviors: paste image, drag-drop, image resize.
 * Call this after DOM is ready.
 *
 * @param {string} editorId - The contenteditable element ID (default: 'editDescription')
 * @param {number} projectId - The project ID for uploads
 * @param {string} [uploadingText] - Placeholder text while uploading
 */
function initEditor(editorId, projectId, uploadingText) {
    const editor = document.getElementById(editorId || 'editDescription');
    if (!editor) return;

    // Rebind file input so inline onchange gets proper args
    const fi = document.getElementById('editorFileInput');
    if (fi) {
        fi.onchange = function () {
            if (this.files[0]) editorUploadFile(this.files[0], projectId, editorId, uploadingText);
        };
    }

    // Paste image from clipboard
    editor.addEventListener('paste', (e) => {
        const items = e.clipboardData?.items;
        if (!items) return;
        for (const item of items) {
            if (item.type.startsWith('image/')) {
                e.preventDefault();
                editorUploadFile(item.getAsFile(), projectId, editorId, uploadingText);
                return;
            }
        }
    });

    // Drag & drop images
    editor.addEventListener('dragover', (e) => { e.preventDefault(); editor.classList.add('border-primary'); });
    editor.addEventListener('dragleave', () => { editor.classList.remove('border-primary'); });
    editor.addEventListener('drop', (e) => {
        e.preventDefault();
        editor.classList.remove('border-primary');
        const files = e.dataTransfer?.files;
        if (files) {
            for (const f of files) {
                if (f.type.startsWith('image/')) editorUploadFile(f, projectId, editorId, uploadingText);
            }
        }
    });

    // Image resize in editor
    let activeImg = null, startX, startW;

    editor.addEventListener('click', (e) => {
        if (activeImg && activeImg !== e.target) {
            activeImg.classList.remove('img-resizing');
            removeHandle();
            activeImg = null;
        }
        if (e.target.tagName === 'IMG' && editor.contains(e.target)) {
            e.preventDefault();
            activeImg = e.target;
            activeImg.classList.add('img-resizing');
            showHandle();
        }
    });

    document.addEventListener('click', (e) => {
        if (activeImg && !editor.contains(e.target)) {
            activeImg.classList.remove('img-resizing');
            removeHandle();
            activeImg = null;
        }
    });

    function showHandle() {
        removeHandle();
        const handle = document.createElement('div');
        handle.className = 'img-resize-handle';
        handle.id = 'imgResizeHandle';
        positionHandle(handle);
        editor.style.position = 'relative';
        editor.appendChild(handle);
        handle.addEventListener('mousedown', onMouseDown);
        handle.addEventListener('touchstart', onTouchStart, { passive: false });
    }

    function positionHandle(handle) {
        if (!activeImg || !handle) return;
        const editorRect = editor.getBoundingClientRect();
        const imgRect = activeImg.getBoundingClientRect();
        handle.style.left = (imgRect.right - editorRect.left - 5 + editor.scrollLeft) + 'px';
        handle.style.top = (imgRect.bottom - editorRect.top - 5 + editor.scrollTop) + 'px';
    }

    function removeHandle() {
        document.getElementById('imgResizeHandle')?.remove();
    }

    function onMouseDown(e) {
        e.preventDefault();
        startX = e.clientX;
        startW = activeImg.offsetWidth;
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    }

    function onTouchStart(e) {
        e.preventDefault();
        startX = e.touches[0].clientX;
        startW = activeImg.offsetWidth;
        document.addEventListener('touchmove', onTouchMove, { passive: false });
        document.addEventListener('touchend', onTouchEnd);
    }

    function onMouseMove(e) { resize(e.clientX); }
    function onTouchMove(e) { e.preventDefault(); resize(e.touches[0].clientX); }

    function resize(clientX) {
        if (!activeImg) return;
        const diff = clientX - startX;
        const newW = Math.max(50, startW + diff);
        const maxW = editor.clientWidth - 20;
        activeImg.style.width = Math.min(newW, maxW) + 'px';
        activeImg.style.height = 'auto';
        activeImg.removeAttribute('width');
        activeImg.removeAttribute('height');
        const handle = document.getElementById('imgResizeHandle');
        if (handle) positionHandle(handle);
    }

    function onMouseUp() {
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    }

    function onTouchEnd() {
        document.removeEventListener('touchmove', onTouchMove);
        document.removeEventListener('touchend', onTouchEnd);
    }
}
