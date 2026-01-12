<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Admin kontrolü - session'da admin_id olmalı
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$db = getDB();

// Genel sistem istatistikleri
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM firmalar WHERE durum = 1) as toplam_firma,
                (SELECT COUNT(*) FROM kullanicilar WHERE aktif = 1) as toplam_kullanici,
                (SELECT COUNT(*) FROM sozlesmeler) as toplam_sozlesme,
                (SELECT COUNT(*) FROM sozlesmeler WHERE durum = 'COMPLETED') as tamamlanan_sozlesme";

$stats_stmt = $db->query($stats_sql);
$stats = $stats_stmt->fetch();

// Gelir İstatistikleri
$gelir_sql = "SELECT 
    COALESCE(SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END), 0) as toplam_gelir,
    COALESCE(SUM(CASE WHEN status = 'success' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN amount ELSE 0 END), 0) as aylik_gelir,
    COALESCE(SUM(CASE WHEN status = 'success' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN amount ELSE 0 END), 0) as haftalik_gelir,
    COUNT(CASE WHEN status = 'success' THEN 1 END) as basarili_odeme,
    COUNT(*) as toplam_odeme
FROM payments";
$gelir_stmt = $db->query($gelir_sql);
$gelir = $gelir_stmt->fetch();

// Son 6 ay gelir trendi
$trend_sql = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as ay,
    DATE_FORMAT(created_at, '%b %Y') as ay_adi,
    SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END) as gelir,
    COUNT(CASE WHEN status = 'success' THEN 1 END) as odeme_sayisi
FROM payments 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY ay ASC";
$trend_stmt = $db->query($trend_sql);
$gelir_trend = $trend_stmt->fetchAll();

// Son 6 ay firma kayıt trendi
$firma_trend_sql = "SELECT 
    DATE_FORMAT(olusturma_tarihi, '%Y-%m') as ay,
    DATE_FORMAT(olusturma_tarihi, '%b %Y') as ay_adi,
    COUNT(*) as kayit_sayisi
FROM firmalar 
WHERE olusturma_tarihi >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
GROUP BY DATE_FORMAT(olusturma_tarihi, '%Y-%m')
ORDER BY ay ASC";
$firma_trend_stmt = $db->query($firma_trend_sql);
$firma_trend = $firma_trend_stmt->fetchAll();

// Süresi dolacak firmalar (7 gün içinde)
$suresi_dolacak_sql = "SELECT f.*, 
    DATEDIFF(f.uyelik_bitis, NOW()) as kalan_gun
FROM firmalar f 
WHERE f.durum = 1 
    AND f.uyelik_bitis IS NOT NULL 
    AND f.uyelik_bitis BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
ORDER BY f.uyelik_bitis ASC";
$suresi_dolacak_stmt = $db->query($suresi_dolacak_sql);
$suresi_dolacaklar = $suresi_dolacak_stmt->fetchAll();

// Süresi dolmuş firmalar
$suresi_dolmus_sql = "SELECT COUNT(*) FROM firmalar WHERE durum = 1 AND uyelik_bitis IS NOT NULL AND uyelik_bitis < NOW()";
$suresi_dolmus = $db->query($suresi_dolmus_sql)->fetchColumn();

// Son firmalar
$firmalar_sql = "SELECT f.*, 
                 (SELECT COUNT(*) FROM kullanicilar WHERE firma_id = f.id) as kullanici_sayisi,
                 (SELECT COUNT(*) FROM sozlesmeler WHERE firma_id = f.id) as sozlesme_sayisi
                 FROM firmalar f
                 WHERE f.durum = 1
                 ORDER BY f.olusturma_tarihi DESC
                 LIMIT 10";

$firmalar_stmt = $db->query($firmalar_sql);
$firmalar = $firmalar_stmt->fetchAll();

// Son aktiviteler
$aktivite_sql = "SELECT dk.*, k.isim, f.firma_adi
                FROM denetim_kayitlari dk
                LEFT JOIN kullanicilar k ON dk.kullanici_id = k.id
                LEFT JOIN firmalar f ON dk.firma_id = f.id
                ORDER BY dk.tarih DESC
                LIMIT 20";

$aktivite_stmt = $db->query($aktivite_sql);
$aktiviteler = $aktivite_stmt->fetchAll();

$page_title = 'Süper Admin Panel';
$body_class = 'admin-theme';

// Chart.js için veri hazırla
$chart_labels = array_map(function ($item) {
    return $item['ay_adi'];
}, $gelir_trend);
$chart_gelir = array_map(function ($item) {
    return (float) $item['gelir'];
}, $gelir_trend);
$chart_firma_labels = array_map(function ($item) {
    return $item['ay_adi'];
}, $firma_trend);
$chart_firma = array_map(function ($item) {
    return (int) $item['kayit_sayisi'];
}, $firma_trend);
?>
<?php include '../includes/header.php'; ?>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include '../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Header Info -->
        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <div>
                <h2 class="fw-bold mb-0">Dashboard</h2>
                <div class="d-flex align-items-center gap-3">
                    <p class="mb-0 opacity-75">Sistem genel durum özeti</p>
                    <a href="error-logs.php" class="btn btn-sm btn-outline-light bg-opacity-10">
                        <i class="bi bi-bug"></i> Hata Logları
                    </a>
                </div>
            </div>
            <div class="d-none d-md-block">
                <span class="badge bg-white text-primary">
                    <i class="bi bi-calendar"></i> <?php echo date('d.m.Y'); ?>
                </span>
            </div>
        </div>

        <!-- İstatistikler -->
        <div class="row">
            <div class="col-md-6 col-lg-3">
                <div class="custom-card stat-card">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-building"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['toplam_firma']; ?></h3>
                    <p class="text-muted mb-0">Aktif Firma</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="custom-card stat-card">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['toplam_kullanici']; ?></h3>
                    <p class="text-muted mb-0">Toplam Kullanıcı</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="custom-card stat-card">
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-file-text"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['toplam_sozlesme']; ?></h3>
                    <p class="text-muted mb-0">Toplam Sözleşme</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="custom-card stat-card">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h3 class="mb-0"><?php echo $stats['tamamlanan_sozlesme']; ?></h3>
                    <p class="text-muted mb-0">Tamamlanan</p>
                </div>
            </div>
        </div>

        <!-- Gelir İstatistikleri -->
        <div class="row mt-3">
            <div class="col-md-6 col-lg-3">
                <div class="custom-card stat-card" style="border-left: 4px solid #198754;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Toplam Gelir</p>
                            <h3 class="mb-0 text-success">
                                <?php echo number_format($gelir['toplam_gelir'], 2, ',', '.'); ?> ₺
                            </h3>
                        </div>
                        <i class="bi bi-cash-stack fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="custom-card stat-card" style="border-left: 4px solid #0d6efd;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Aylık Gelir (30 gün)</p>
                            <h3 class="mb-0 text-primary">
                                <?php echo number_format($gelir['aylik_gelir'], 2, ',', '.'); ?> ₺
                            </h3>
                        </div>
                        <i class="bi bi-calendar-month fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="custom-card stat-card" style="border-left: 4px solid #6f42c1;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Haftalık Gelir (7 gün)</p>
                            <h3 class="mb-0" style="color: #6f42c1;">
                                <?php echo number_format($gelir['haftalik_gelir'], 2, ',', '.'); ?> ₺
                            </h3>
                        </div>
                        <i class="bi bi-calendar-week fs-1 opacity-25" style="color: #6f42c1;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="custom-card stat-card" style="border-left: 4px solid #20c997;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Başarılı Ödeme</p>
                            <h3 class="mb-0" style="color: #20c997;"><?php echo $gelir['basarili_odeme']; ?> /
                                <?php echo $gelir['toplam_odeme']; ?>
                            </h3>
                        </div>
                        <i class="bi bi-credit-card-2-front fs-1 opacity-25" style="color: #20c997;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hızlı Eylemler ve Uyarılar -->
        <div class="row mt-3">
            <!-- Hızlı Eylemler -->
            <div class="col-lg-6">
                <div class="custom-card">
                    <h5 class="mb-3"><i class="bi bi-lightning-charge"></i> Hızlı Eylemler</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="firmalar.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Yeni Firma
                        </a>
                        <a href="sistem-ayarlari.php" class="btn btn-success">
                            <i class="bi bi-database-down"></i> Yedek Al
                        </a>
                        <a href="payments.php" class="btn btn-info text-white">
                            <i class="bi bi-credit-card"></i> Ödemeler
                        </a>
                        <a href="error-logs.php" class="btn btn-warning">
                            <i class="bi bi-bug"></i> Hata Logları
                        </a>
                        <a href="kullanicilar.php" class="btn btn-secondary">
                            <i class="bi bi-people"></i> Kullanıcılar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Süre Dolum Uyarıları -->
            <div class="col-lg-6">
                <div class="custom-card">
                    <h5 class="mb-3">
                        <i class="bi bi-exclamation-triangle text-warning"></i>
                        Üyelik Uyarıları
                        <?php if (count($suresi_dolacaklar) > 0 || $suresi_dolmus > 0): ?>
                            <span class="badge bg-danger"><?php echo count($suresi_dolacaklar) + $suresi_dolmus; ?></span>
                        <?php endif; ?>
                    </h5>

                    <?php if ($suresi_dolmus > 0): ?>
                        <div class="alert alert-danger py-2 mb-2">
                            <i class="bi bi-x-circle"></i> <strong><?php echo $suresi_dolmus; ?></strong> firmanın üyelik
                            süresi dolmuş!
                        </div>
                    <?php endif; ?>

                    <?php if (count($suresi_dolacaklar) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($suresi_dolacaklar as $firma): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <strong><?php echo htmlspecialchars($firma['firma_adi']); ?></strong>
                                        <br><small class="text-muted"><?php echo strtoupper($firma['plan']); ?> Plan</small>
                                    </div>
                                    <span class="badge bg-warning text-dark">
                                        <?php echo $firma['kalan_gun']; ?> gün kaldı
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <?php if ($suresi_dolmus == 0): ?>
                            <p class="text-muted mb-0"><i class="bi bi-check-circle text-success"></i> Yakın zamanda süresi
                                dolacak firma yok.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Grafikler -->
        <div class="row mt-3">
            <div class="col-lg-6">
                <div class="custom-card">
                    <h5 class="mb-3"><i class="bi bi-graph-up"></i> Gelir Trendi (Son 6 Ay)</h5>
                    <div style="position: relative; height: 250px; max-height: 250px;">
                        <canvas id="gelirChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="custom-card">
                    <h5 class="mb-3"><i class="bi bi-bar-chart"></i> Firma Kayıtları (Son 6 Ay)</h5>
                    <div style="position: relative; height: 250px; max-height: 250px;">
                        <canvas id="firmaChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Firmalar -->
            <div class="col-lg-8">
                <div class="custom-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="bi bi-building"></i> Son Firmalar</h5>
                        <a href="firmalar.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus"></i> Yeni Firma
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Firma Adı</th>
                                    <th>Domain</th>
                                    <th>Plan</th>
                                    <th class="text-center">Kullanıcı</th>
                                    <th class="text-center">Sözleşme</th>
                                    <th>Kayıt Tarihi</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($firmalar as $firma): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($firma['firma_adi']); ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($firma['domain'] ?? '-'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php
                                            echo $firma['plan'] === 'enterprise' ? 'primary' :
                                                ($firma['plan'] === 'pro' ? 'success' : 'secondary');
                                            ?>">
                                                <?php echo strtoupper($firma['plan']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?php echo $firma['kullanici_sayisi']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?php echo $firma['sozlesme_sayisi']; ?></span>
                                        </td>
                                        <td>
                                            <small><?php echo formatDate($firma['olusturma_tarihi'], 'd.m.Y'); ?></small>
                                        </td>
                                        <td>
                                            <a href="firma-detay.php?id=<?php echo $firma['id']; ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Aktiviteler -->
            <div class="col-lg-4">
                <div class="custom-card">
                    <h5 class="mb-3"><i class="bi bi-clock-history"></i> Son Aktiviteler</h5>
                    <div style="max-height: 500px; overflow-y: auto;">
                        <?php foreach ($aktiviteler as $aktivite): ?>
                            <div class="p-2 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?php echo htmlspecialchars($aktivite['isim'] ?? 'Sistem'); ?></strong>
                                        <br>
                                        <small
                                            class="text-muted"><?php echo htmlspecialchars($aktivite['islem']); ?></small>
                                        <?php if ($aktivite['firma_adi']): ?>
                                            <br>
                                            <small class="text-primary">
                                                <i class="bi bi-building"></i>
                                                <?php echo htmlspecialchars($aktivite['firma_adi']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo formatDate($aktivite['tarih'], 'H:i'); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Grafik Scriptleri -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Gelir Trendi Grafiği
        const gelirCtx = document.getElementById('gelirChart');
        if (gelirCtx) {
            new Chart(gelirCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [{
                        label: 'Gelir (₺)',
                        data: <?php echo json_encode($chart_gelir); ?>,
                        borderColor: 'rgb(25, 135, 84)',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return value.toLocaleString('tr-TR') + ' ₺';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Firma Kayıt Grafiği
        const firmaCtx = document.getElementById('firmaChart');
        if (firmaCtx) {
            new Chart(firmaCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($chart_firma_labels); ?>,
                    datasets: [{
                        label: 'Yeni Kayıt',
                        data: <?php echo json_encode($chart_firma); ?>,
                        backgroundColor: 'rgba(13, 110, 253, 0.7)',
                        borderColor: 'rgb(13, 110, 253)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<?php include '../includes/footer.php'; ?>