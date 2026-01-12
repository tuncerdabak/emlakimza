<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$page_title = 'Hata Logları';
$body_class = 'admin-theme';

// Hata Logu Silme İşlemi
if (isset($_POST['delete_log']) && isset($_POST['log_path'])) {
    $path = $_POST['log_path'];
    // Güvenlik kontrolü: Yol projenin içinde mi?
    $realBase = realpath(__DIR__ . '/../');
    $realPath = realpath($path);

    if ($realPath && strpos($realPath, $realBase) === 0 && basename($realPath) === 'error_log') {
        unlink($realPath);
        $success_msg = "Log dosyası silindi: " . htmlspecialchars($path);
    } else {
        $error_msg = "Geçersiz dosya yolu!";
    }
}

// Rekürsif olarak error.log dosyalarını bul
function findErrorLogs($dir)
{
    $results = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getBasename() === 'error_log') {
            $results[] = [
                'path' => $file->getPathname(),
                'relative_path' => str_replace(realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                'size' => $file->getSize(),
                'mtime' => $file->getMTime()
            ];
        }
    }
    return $results;
}

$baseDir = realpath(__DIR__ . '/../');
$logs = findErrorLogs($baseDir);

// Belirli bir logu görüntüleme
$view_content = '';
$view_path = '';
if (isset($_GET['view'])) {
    $target = $baseDir . DIRECTORY_SEPARATOR . $_GET['view'];
    $realTarget = realpath($target);

    // Güvenlik: Sadece error.log ve proje içinde
    if ($realTarget && strpos($realTarget, $baseDir) === 0 && basename($realTarget) === 'error_log') {
        if (file_exists($realTarget)) {
            $view_content = file_get_contents($realTarget);
            $view_path = $_GET['view'];
        }
    }
}

?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Hata Logları</h2>
                <p class="text-muted mb-0">Sistemdeki tüm error_log dosyaları</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Geri Dön
            </a>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Log Listesi -->
            <div class="<?php echo $view_content ? 'col-md-4' : 'col-md-12'; ?>">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Log Dosyaları</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($logs)): ?>
                            <div class="p-3 text-center text-muted">Hata logu bulunamadı.</div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($logs as $log): ?>
                                    <div
                                        class="list-group-item d-flex justify-content-between align-items-center <?php echo ($view_path == $log['relative_path']) ? 'bg-light fw-bold' : ''; ?>">
                                        <div class="text-truncate me-2"
                                            title="<?php echo htmlspecialchars($log['relative_path']); ?>">
                                            <i class="bi bi-file-earmark-text text-danger me-2"></i>
                                            <?php echo htmlspecialchars($log['relative_path']); ?>
                                            <div class="small text-muted">
                                                <?php echo number_format($log['size'] / 1024, 2); ?> KB •
                                                <?php echo date('d.m.Y H:i', $log['mtime']); ?>
                                            </div>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?view=<?php echo urlencode($log['relative_path']); ?>"
                                                class="btn btn-outline-primary" title="Görüntüle">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <form method="POST"
                                                onsubmit="return confirm('Bu log dosyasını silmek istediğinize emin misiniz?');"
                                                style="display:inline;">
                                                <input type="hidden" name="log_path"
                                                    value="<?php echo htmlspecialchars($log['path']); ?>">
                                                <button type="submit" name="delete_log" class="btn btn-outline-danger"
                                                    title="Sil">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Log İçeriği -->
            <?php if ($view_content): ?>
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-truncate" title="<?php echo htmlspecialchars($view_path); ?>">
                                İçerik: <?php echo htmlspecialchars($view_path); ?>
                            </h5>
                            <a href="error-logs.php" class="btn btn-sm btn-close" title="Kapat"></a>
                        </div>
                        <div class="card-body p-0 bg-dark">
                            <pre class="text-white p-3 mb-0"
                                style="max-height: 80vh; overflow-y: auto; white-space: pre-wrap; font-size: 0.85rem;"><?php echo htmlspecialchars($view_content); ?></pre>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>