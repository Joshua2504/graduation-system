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
