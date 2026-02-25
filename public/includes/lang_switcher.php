<?php
/**
 * Shared language switcher dropdown component.
 * 
 * Optional: set $langSwitcherExtraParams before including, e.g. 'token=abc'
 * to append extra query params to each language link.
 */
$langLabels = ['ar' => 'العربية', 'en' => 'English', 'de' => 'Deutsch'];
$currentLangLabel = $langLabels[getLang()] ?? 'العربية';
$langSwitcherExtra = !empty($langSwitcherExtraParams) ? '&' . $langSwitcherExtraParams : '';
?>
<div class="btn-group">
    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-translate me-1"></i><?= $currentLangLabel ?>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <?php foreach ($langLabels as $code => $label): ?>
            <li><a class="dropdown-item <?= getLang() === $code ? 'active' : '' ?>" href="?lang=<?= $code ?><?= $langSwitcherExtra ?>"><?= $label ?></a></li>
        <?php endforeach; ?>
    </ul>
</div>
