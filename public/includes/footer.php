<?php
/**
 * Shared HTML footer template
 */
?>
    <footer class="text-center text-muted py-4 mt-5">
        <small>&copy; <?= date('Y') ?> <?= __('app_name') ?></small>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Sortable table columns — add data-sortable to any <th> to enable
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('th[data-sortable]').forEach(function (th) {
            th.style.cursor = 'pointer';
            th.style.userSelect = 'none';
            th.style.whiteSpace = 'nowrap';
            th.addEventListener('click', function () { _sortByTh(this); });
        });
    });

    function _sortByTh(th) {
        const tbody = th.closest('table').querySelector('tbody');
        const ths = Array.from(th.closest('tr').querySelectorAll('th'));
        const col = ths.indexOf(th);
        const newDir = th.dataset.sortDir === 'asc' ? 'desc' : 'asc';

        ths.forEach(function (h) {
            if ('sortable' in h.dataset) {
                delete h.dataset.sortDir;
                const ic = h.querySelector('.th-sort-icon');
                if (ic) ic.remove();
            }
        });
        th.dataset.sortDir = newDir;
        const ic = document.createElement('i');
        ic.className = 'bi bi-sort-' + (newDir === 'asc' ? 'up' : 'down') + '-alt ms-1 th-sort-icon';
        th.appendChild(ic);

        // Collect row groups — keeps paired collapse/detail rows together
        const rowGroups = [];
        const rows = Array.from(tbody.children);
        let i = 0;
        while (i < rows.length) {
            const row = rows[i];
            const next = rows[i + 1];
            if (!row.classList.contains('collapse') && next && next.classList.contains('collapse')) {
                rowGroups.push([row, next]);
                i += 2;
            } else {
                rowGroups.push([row]);
                i++;
            }
        }

        rowGroups.sort(function (a, b) {
            const aCell = a[0].querySelectorAll('td')[col];
            const bCell = b[0].querySelectorAll('td')[col];
            const aText = aCell ? aCell.textContent.trim() : '';
            const bText = bCell ? bCell.textContent.trim() : '';
            const dateRe = /^\d{4}-\d{2}-\d{2}/;
            if (dateRe.test(aText) && dateRe.test(bText)) {
                const c = aText.localeCompare(bText);
                return newDir === 'asc' ? c : -c;
            }
            const aN = parseFloat(aText.replace(/[^\d.-]/g, ''));
            const bN = parseFloat(bText.replace(/[^\d.-]/g, ''));
            if (!isNaN(aN) && !isNaN(bN)) return newDir === 'asc' ? aN - bN : bN - aN;
            const c = aText.localeCompare(bText);
            return newDir === 'asc' ? c : -c;
        });

        rowGroups.forEach(function (g) { g.forEach(function (r) { tbody.appendChild(r); }); });
    }
    </script>
    <script>
    function toggleTheme() {
        const html = document.documentElement;
        const current = html.getAttribute('data-bs-theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', next);
        localStorage.setItem('theme', next);
        updateThemeIcon(next);
    }
    function updateThemeIcon(theme) {
        const icon = document.getElementById('themeIcon');
        if (icon) {
            icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        }
    }
    updateThemeIcon(document.documentElement.getAttribute('data-bs-theme') || 'light');
    </script>
</body>
</html>
