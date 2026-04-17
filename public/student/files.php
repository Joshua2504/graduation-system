<?php
/**
 * Student — Shared Files (read-only view with download)
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_role('student');

$pdo = getDB();
$isAr = getLang() === 'ar';

// Fetch all shared files
$stmt = $pdo->query("
    SELECT sf.*, u.name AS uploader_name
    FROM shared_files sf
    LEFT JOIN users u ON u.id = sf.uploaded_by
    ORDER BY sf.created_at DESC
");
$files = $stmt->fetchAll();

$yearLabels = [
    '1st' => __('first_year'),
    '2nd' => __('second_year'),
    '3rd' => __('third_year'),
    '4th' => __('fourth_year'),
];

$pageTitle = __('files');
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/navbar.php';
?>

<div class="container">
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <h3 class="mb-0"><i class="bi bi-folder2-open me-2"></i><?= __('files') ?></h3>
        <div class="ms-auto d-flex gap-2 align-items-center flex-wrap justify-content-end">
            <span class="badge bg-primary fs-6"><?= count($files) ?> <?= __('files_count') ?></span>
            <input type="text" class="form-control form-control-sm" id="searchInput"
                   placeholder="<?= __('search_placeholder') ?>" style="min-width:140px; max-width:200px;">
        </div>
    </div>

    <?php if (empty($files)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle me-2"></i><?= __('no_files') ?>
        </div>
    <?php else: ?>
        <div class="card shadow">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="filesTable">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th><?= __('file_name') ?></th>
                            <th><?= __('year') ?></th>
                            <th><?= __('department') ?></th>
                            <th><?= __('file_note') ?></th>
                            <th><?= __('download_file') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $i => $f): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td class="fw-bold"><?= sanitize($f['name']) ?></td>
                            <td><?= sanitize($yearLabels[$f['year']] ?? $f['year']) ?></td>
                            <td><?= sanitize($f['department']) ?></td>
                            <td><?= sanitize($f['note'] ?? '') ?></td>
                            <td>
                                <a href="/api/shared-files?download=<?= $f['id'] ?>" class="text-primary text-decoration-none">
                                    <i class="bi bi-box-arrow-in-down-right me-1"></i><?= __('download_file') ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('searchInput')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#filesTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
