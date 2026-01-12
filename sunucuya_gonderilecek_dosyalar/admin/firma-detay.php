<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id === 0) {
    header("Location: firmalar.php");
    exit;
}

// Firma Bilgilerini Getir
$stmt = $db->prepare("SELECT f.*, 
                      (SELECT COUNT(*) FROM kullanicilar WHERE firma_id = f.id) as kullanici_sayisi,
                      (SELECT COUNT(*) FROM sozlesmeler WHERE firma_id = f.id) as sozlesme_sayisi
                      FROM firmalar f WHERE f.id = ?");
$stmt->execute([$id]);
$firma = $stmt->fetch();

if (!$firma) {
    die("Firma bulunamadı.");
}

$page_title = 'Firma Detayı - ' . $firma['firma_adi'];
$body_class = 'admin-theme';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <h2 class="fw-bold mb-0">Firma Detayı</h2>
            <div>
                <a href="firma-duzenle.php?id=<?php echo $firma['id']; ?>" class="btn btn-warning"><i
                        class="bi bi-pencil"></i> Düzenle</a>
                <a href="firmalar.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Geri Dön</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <?php if (!empty($firma['logo_yolu'])): ?>
                                <img src="..<?php echo htmlspecialchars($firma['logo_yolu']); ?>" alt="Logo"
                                    class="img-fluid rounded border p-1" style="max-height: 100px; max-width: 100%;">
                            <?php else: ?>
                                <div class="display-1 text-muted"><i class="bi bi-building"></i></div>
                            <?php endif; ?>
                        </div>
                        <h3 class="card-title"><?php echo htmlspecialchars($firma['firma_adi']); ?></h3>
                        <p class="text-muted"><?php echo htmlspecialchars($firma['domain'] ?? 'Domain Yok'); ?></p>
                        <hr>
                        <div class="text-start">
                            <p><strong>Yetkili:</strong> <?php echo htmlspecialchars($firma['yetkili_adi']); ?></p>
                            <p><strong>E-posta:</strong> <?php echo htmlspecialchars($firma['email']); ?></p>
                            <p><strong>Telefon:</strong> <?php echo htmlspecialchars($firma['telefon']); ?></p>
                            <p><strong>Adres:</strong> <?php echo htmlspecialchars($firma['adres']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Abonelik ve Kullanım</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4 text-center">
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light">
                                    <div class="text-muted mb-1">Paket</div>
                                    <h4 class="text-primary"><?php echo strtoupper($firma['plan']); ?></h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light">
                                    <div class="text-muted mb-1">Kullanıcı</div>
                                    <h4><?php echo $firma['kullanici_sayisi']; ?> /
                                        <?php echo $firma['kullanici_limiti']; ?>
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light">
                                    <div class="text-muted mb-1">Sözleşme</div>
                                    <h4><?php echo $firma['sozlesme_sayisi']; ?> / <?php echo $firma['belge_limiti']; ?>
                                    </h4>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    <i class="bi bi-calendar-event"></i> Üyelik Bitiş:
                                    <strong><?php echo $firma['uyelik_bitis'] ? date('d.m.Y', strtotime($firma['uyelik_bitis'])) : 'Süresiz'; ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Son Aktiviteler veya Kullanıcılar burada listelenebilir -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Bağlı Kullanıcılar</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $users = $db->prepare("SELECT * FROM kullanicilar WHERE firma_id = ?");
                        $users->execute([$id]);
                        $user_list = $users->fetchAll();
                        ?>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>İsim</th>
                                    <th>E-posta</th>
                                    <th>Rol</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_list as $u): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($u['isim']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><?php echo $u['rol']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $u['aktif'] ? 'success' : 'danger'; ?>">
                                                <?php echo $u['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>