<?php
/**
 * Shared language switcher dropdown component.
 * 
 * Optional: set $langSwitcherExtraParams before including, e.g. 'token=abc'
 * to append extra query params to each language link.
 */
$langLabels = ['ar' => 'العربية', 'en' => 'English', 'de' => 'Deutsch'];
// Filter to only enabled languages
global $supportedLangs;
$activeLangLabels = array_intersect_key($langLabels, array_flip($supportedLangs ?? ['ar', 'en', 'de']));
$currentLangLabel = $activeLangLabels[getLang()] ?? reset($activeLangLabels) ?: 'العربية';
$langSwitcherExtra = !empty($langSwitcherExtraParams) ? '&' . $langSwitcherExtraParams : '';
?>
<?php if (count($activeLangLabels) > 1): ?>
<div class="btn-group">
    <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-translate me-1"></i><?= $currentLangLabel ?>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <?php foreach ($activeLangLabels as $code => $label): ?>
            <li><a class="dropdown-item <?= getLang() === $code ? 'active' : '' ?>" href="?lang=<?= $code ?><?= $langSwitcherExtra ?>"><?= $label ?></a></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
