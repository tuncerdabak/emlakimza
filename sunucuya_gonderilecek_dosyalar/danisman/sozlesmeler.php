<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

if (!isDanisman()) {
    header("Location: ../index.php");
    exit;
}

$db = getDB();
$danisman_id = $_SESSION['user_id'];

// Filtreleme
$durum_filtre = $_GET['durum'] ?? 'all';
$arama = $_GET['q'] ?? '';

// SQL oluştur
$sql = "SELECT s.*, 
        COUNT(si.id) as imza_sayisi,
        CASE 
            WHEN s.durum = 'COMPLETED' THEN 'success'
            WHEN s.durum = 'PENDING' THEN 'warning'
        END as badge_class
        FROM sozlesmeler s
        LEFT JOIN sozlesme_imzalar si ON s.id = si.sozlesme_id
        WHERE s.danisman_id = :danisman_id";

if ($durum_filtre !== 'all') {
    $sql .= " AND s.durum = :durum";
}

if (!empty($arama)) {
    $sql .= " AND (s.islem_uuid LIKE :arama OR s.gayrimenkul_adres LIKE :arama OR s.musteri_email LIKE :arama)";
}

$sql .= " GROUP BY s.id ORDER BY s.olusturma_tarihi DESC";

$stmt = $db->prepare($sql);
$stmt->bindValue(':danisman_id', $danisman_id, PDO::PARAM_INT);

if ($durum_filtre !== 'all') {
    $stmt->bindValue(':durum', $durum_filtre);
}

if (!empty($arama)) {
    $stmt->bindValue(':arama', "%$arama%");
}

$stmt->execute();
$sozlesmeler = $stmt->fetchAll();

// İstatistikler
$stats_sql = "SELECT 
    COUNT(*) as toplam,
    SUM(CASE WHEN durum = 'COMPLETED' THEN 1 ELSE 0 END) as tamamlanan
    FROM sozlesmeler WHERE danisman_id = :danisman_id";

$stats_stmt = $db->prepare($stats_sql);
$stats_stmt->execute([':danisman_id' => $danisman_id]);

$stats = $stats_stmt->fetch();

$page_title = 'Sözleşmelerim - emlakimza.com';
$body_class = 'danisman-theme';
$extra_css = '
<style>
    .filter-card {
        background: white;
        border-radius: 15px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .contract-item {
        background: white;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        transition: transform 0.2s;
    }

    .contract-item:hover {
        transform: translateX(5px);
    }
</style>';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <h2 class="fw-bold mb-0"><i class="bi bi-file-text"></i> Sözleşmelerim</h2>
        </div>

        <!-- Stats Bar -->
        <div class="mb-4">
            <span class="badge bg-white text-primary p-2 me-2">
                <i class="bi bi-file-text"></i> <?php echo $stats['toplam']; ?> Toplam
            </span>
            <span class="badge bg-white text-success p-2 me-2">
                <i class="bi bi-check-circle"></i> <?php echo $stats['tamamlanan']; ?> Tamamlanan
            </span>
            <span class="badge bg-white text-warning p-2">
                <i class="bi bi-clock"></i> <?php echo $stats['toplam'] - $stats['tamamlanan']; ?> Bekleyen
            </span>
        </div>

        <!-- Filtreler -->
        <div class="filter-card">
            <form method="GET" class="row g-2">
                <div class="col-12">
                    <input type="text" name="q" class="form-control" placeholder="Ara... (UUID, adres, e-posta)"
                        value="<?php echo htmlspecialchars($arama); ?>">
                </div>
                <div class="col-6">
                    <select name="durum" class="form-select">
                        <option value="all" <?php echo $durum_filtre === 'all' ? 'selected' : ''; ?>>Tüm Durumlar
                        </option>
                        <option value="PENDING" <?php echo $durum_filtre === 'PENDING' ? 'selected' : ''; ?>>İmza
                            Bekliyor</option>
                        <option value="COMPLETED" <?php echo $durum_filtre === 'COMPLETED' ? 'selected' : ''; ?>>
                            İmzalandı</option>
                    </select>
                </div>
                <div class="col-6">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filtrele
                    </button>
                </div>
            </form>
        </div>

        <!-- Sözleşme Listesi -->
        <?php if (count($sozlesmeler) > 0): ?>
            <?php foreach ($sozlesmeler as $s): ?>
                <div class="contract-item" onclick="location.href='sozlesme-detay.php?id=<?php echo $s['id']; ?>'">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <strong><?php echo substr($s['islem_uuid'], 0, 18); ?>...</strong>
                            <?php echo getStatusBadge($s['durum']); ?>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-calendar"></i>
                            <?php echo formatDate($s['olusturma_tarihi'], 'd.m.Y'); ?>
                        </small>
                    </div>

                    <?php if ($s['gayrimenkul_adres']): ?>
                        <div class="mb-2">
                            <small class="text-muted">
                                <i class="bi bi-geo-alt"></i>
                                <?php echo htmlspecialchars(substr($s['gayrimenkul_adres'], 0, 50)); ?>...
                            </small>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center">
                        <small>
                            <i class="bi bi-pen"></i>
                            <?php echo $s['imza_sayisi']; ?> İmza
                        </small>

                        <?php if ($s['fiyat']): ?>
                            <strong class="text-success">
                                <?php echo formatMoney($s['fiyat']); ?>
                            </strong>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="filter-card text-center py-5">
                <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                <p class="text-muted mt-3">
                    <?php echo !empty($arama) ? 'Aramanızla eşleşen sözleşme bulunamadı' : 'Henüz sözleşme bulunmuyor'; ?>
                </p>
                <?php if (!empty($arama) || $durum_filtre !== 'all'): ?>
                    <a href="sozlesmeler.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-x-circle"></i> Filtreyi Temizle
                    </a>
                <?php else: ?>
                    <a href="sozlesme-gonder.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> İlk Sözleşmeni Gönder
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>