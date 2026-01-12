<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin('../danisman/login.php');

if (!isDanisman()) {
    header("Location: ../index.php");
    exit;
}

$db = getDB();
$danisman_id = $_SESSION['user_id'];
$firma_id = $_SESSION['firma_id'];
$message = '';
$message_type = '';

// Tablo ve Şema Kontrolü
try {
    $db->query("SELECT 1 FROM portfoyler LIMIT 1");

    // Eksik kolanları kontrol et ve ekle
    $columns = [
        'il' => "VARCHAR(50) DEFAULT ''",
        'ilce' => "VARCHAR(50) DEFAULT ''",
        'mahalle' => "VARCHAR(100) DEFAULT ''",
        'ada' => "VARCHAR(20) DEFAULT ''",
        'parsel' => "VARCHAR(20) DEFAULT ''",
        'bagimsiz_bolum' => "VARCHAR(20) DEFAULT ''",
        'nitelik' => "VARCHAR(50) DEFAULT ''"
    ];

    foreach ($columns as $col => $def) {
        try {
            $db->query("SELECT $col FROM portfoyler LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE portfoyler ADD COLUMN $col $def");
        }
    }

} catch (PDOException $e) {
    $db->exec("CREATE TABLE IF NOT EXISTS portfoyler (
        id INT AUTO_INCREMENT PRIMARY KEY,
        firma_id INT NOT NULL,
        danisman_id INT NOT NULL,
        baslik VARCHAR(255) NOT NULL,
        adres TEXT NOT NULL,
        il VARCHAR(50) DEFAULT '',
        ilce VARCHAR(50) DEFAULT '',
        mahalle VARCHAR(100) DEFAULT '',
        ada VARCHAR(20) DEFAULT '',
        parsel VARCHAR(20) DEFAULT '',
        bagimsiz_bolum VARCHAR(20) DEFAULT '',
        nitelik VARCHAR(50) DEFAULT '',
        fiyat DECIMAL(15,2) DEFAULT 0,
        notlar TEXT,
        durum ENUM('yayinda', 'pasif', 'satildi', 'kiralandi') DEFAULT 'yayinda',
        olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
}

// Şema Kontrolü: portfoy_id kolonu sozlesmeler tablosuna eklenmeli
try {
    $db->query("SELECT portfoy_id FROM sozlesmeler LIMIT 1");
} catch (PDOException $e) {
    try {
        $db->exec("ALTER TABLE sozlesmeler ADD COLUMN portfoy_id INT DEFAULT NULL");
    } catch (Exception $ex) {
        // Kolon eklenemezse sessiz kal
    }
}

// İşlemler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ekle'])) {
        $baslik = sanitizeInput($_POST['baslik']);
        $adres = sanitizeInput($_POST['adres']);
        $fiyat = (float) str_replace('.', '', $_POST['fiyat']);
        $notlar = sanitizeInput($_POST['notlar']);

        // Yeni Alanlar
        $il = sanitizeInput($_POST['il'] ?? '');
        $ilce = sanitizeInput($_POST['ilce'] ?? '');
        $mahalle = sanitizeInput($_POST['mahalle'] ?? '');
        $ada = sanitizeInput($_POST['ada'] ?? '');
        $parsel = sanitizeInput($_POST['parsel'] ?? '');
        $bagimsiz_bolum = sanitizeInput($_POST['bagimsiz_bolum'] ?? '');
        $nitelik = sanitizeInput($_POST['nitelik'] ?? '');

        $sql = "INSERT INTO portfoyler (firma_id, danisman_id, baslik, adres, fiyat, notlar, il, ilce, mahalle, ada, parsel, bagimsiz_bolum, nitelik) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);

        if ($stmt->execute([$firma_id, $danisman_id, $baslik, $adres, $fiyat, $notlar, $il, $ilce, $mahalle, $ada, $parsel, $bagimsiz_bolum, $nitelik])) {
            $message = 'Portföy eklendi.';
            $message_type = 'success';
        }
    } elseif (isset($_POST['guncelle'])) {
        $id = (int) $_POST['id'];
        $baslik = sanitizeInput($_POST['baslik']);
        $adres = sanitizeInput($_POST['adres']);
        $fiyat = (float) str_replace('.', '', $_POST['fiyat']);
        $notlar = sanitizeInput($_POST['notlar']);
        $durum = $_POST['durum'];

        // Yeni Alanlar
        $il = sanitizeInput($_POST['il'] ?? '');
        $ilce = sanitizeInput($_POST['ilce'] ?? '');
        $mahalle = sanitizeInput($_POST['mahalle'] ?? '');
        $ada = sanitizeInput($_POST['ada'] ?? '');
        $parsel = sanitizeInput($_POST['parsel'] ?? '');
        $bagimsiz_bolum = sanitizeInput($_POST['bagimsiz_bolum'] ?? '');
        $nitelik = sanitizeInput($_POST['nitelik'] ?? '');

        $sql = "UPDATE portfoyler SET baslik=?, adres=?, fiyat=?, notlar=?, durum=?, il=?, ilce=?, mahalle=?, ada=?, parsel=?, bagimsiz_bolum=?, nitelik=? WHERE id=? AND danisman_id=?";
        $stmt = $db->prepare($sql);

        if ($stmt->execute([$baslik, $adres, $fiyat, $notlar, $durum, $il, $ilce, $mahalle, $ada, $parsel, $bagimsiz_bolum, $nitelik, $id, $danisman_id])) {
            $message = 'Portföy güncellendi.';
            $message_type = 'success';
        }
    } elseif (isset($_POST['sil'])) {
        $id = (int) $_POST['id'];
        $stmt = $db->prepare("DELETE FROM portfoyler WHERE id=? AND danisman_id=?");
        if ($stmt->execute([$id, $danisman_id])) {
            $message = 'Portföy silindi.';
            $message_type = 'warning';
        }
    }
}

// Listeleme

// Listeleme
// Not: portfoy_id kolonu sozlesmeler tablosuna eklenmeli
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM sozlesmeler WHERE portfoy_id = p.id) as sozlesme_sayisi 
        FROM portfoyler p 
        WHERE p.danisman_id = ? 
        ORDER BY p.olusturma_tarihi DESC";
$portfoyler = $db->prepare($sql);
$portfoyler->execute([$danisman_id]);
$liste = $portfoyler->fetchAll();

$page_title = 'Portföy Yönetimi';
$body_class = 'danisman-theme';
$extra_css = '
<style>
    .card {
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
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
            <h3 class="mb-0"><i class="bi bi-building"></i> Portföy Yönetimi</h3>
            <button class="btn btn-light text-primary fw-bold" data-bs-toggle="modal" data-bs-target="#ekleModal">
                <i class="bi bi-plus-lg"></i> Yeni Portföy
            </button>
        </div>

        <?php if ($message): ?>
            <?php echo showAlert($message, $message_type); ?>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($liste as $p): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title text-truncate" title="<?php echo htmlspecialchars($p['baslik']); ?>">
                                    <?php echo htmlspecialchars($p['baslik']); ?>
                                </h5>
                                <span class="badge bg-secondary"><?php echo strtoupper($p['durum']); ?></span>
                            </div>
                            <div class="mb-2">
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-file-text"></i> <?php echo $p['sozlesme_sayisi']; ?> Sözleşme
                                </span>
                            </div>
                            <h6 class="text-primary mb-3"><?php echo formatMoney($p['fiyat']); ?></h6>
                            <p class="card-text small text-muted" style="min-height: 40px;">
                                <i class="bi bi-geo-alt"></i>
                                <?php echo htmlspecialchars(substr($p['adres'], 0, 50)) . (strlen($p['adres']) > 50 ? '...' : ''); ?>
                            </p>

                            <hr>

                            <div class="d-grid gap-2">
                                <a href="sozlesme-gonder.php?portfoy_id=<?php echo $p['id']; ?>"
                                    class="btn btn-outline-success">
                                    <i class="bi bi-file-earmark-text"></i> Sözleşme Hazırla
                                </a>
                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-sm btn-primary w-50 me-1" data-bs-toggle="modal"
                                        data-bs-target="#editModal<?php echo $p['id']; ?>">
                                        Düzenle
                                    </button>
                                    <form method="POST" class="w-50 ms-1"
                                        onsubmit="return confirm('Silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="sil" value="1">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <button class="btn btn-sm btn-danger w-100">Sil</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Düzenle Modal -->
                    <div class="modal fade" id="editModal<?php echo $p['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Portföy Düzenle</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="guncelle" value="1">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">

                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <label>Başlık</label>
                                                <input type="text" name="baslik" class="form-control"
                                                    value="<?php echo htmlspecialchars($p['baslik']); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Fiyat</label>
                                                <input type="text" name="fiyat" class="form-control price-input"
                                                    value="<?php echo number_format($p['fiyat'], 0, '', '.'); ?>" required>
                                            </div>
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <div class="col-md-4">
                                                <label>İl</label>
                                                <input type="text" name="il" class="form-control" value="<?php echo htmlspecialchars($p['il'] ?? ''); ?>" placeholder="İstanbul">
                                            </div>
                                            <div class="col-md-4">
                                                <label>İlçe</label>
                                                <input type="text" name="ilce" class="form-control" value="<?php echo htmlspecialchars($p['ilce'] ?? ''); ?>" placeholder="Kadıköy">
                                            </div>
                                            <div class="col-md-4">
                                                <label>Mahalle</label>
                                                <input type="text" name="mahalle" class="form-control" value="<?php echo htmlspecialchars($p['mahalle'] ?? ''); ?>" placeholder="Caferağa">
                                            </div>
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <div class="col-md-3">
                                                <label>Ada</label>
                                                <input type="text" name="ada" class="form-control" value="<?php echo htmlspecialchars($p['ada'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label>Parsel</label>
                                                <input type="text" name="parsel" class="form-control" value="<?php echo htmlspecialchars($p['parsel'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label>Bağ. Bölüm</label>
                                                <input type="text" name="bagimsiz_bolum" class="form-control" value="<?php echo htmlspecialchars($p['bagimsiz_bolum'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label>Niteliği</label>
                                                <input type="text" name="nitelik" class="form-control" value="<?php echo htmlspecialchars($p['nitelik'] ?? ''); ?>" placeholder="Mesken">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label>Açık Adres</label>
                                            <textarea name="adres" class="form-control"
                                                required><?php echo htmlspecialchars($p['adres']); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label>Durum</label>
                                            <select name="durum" class="form-select">
                                                <option value="yayinda" <?php echo $p['durum'] == 'yayinda' ? 'selected' : ''; ?>>Yayında</option>
                                                <option value="pasif" <?php echo $p['durum'] == 'pasif' ? 'selected' : ''; ?>>
                                                    Pasif</option>
                                                <option value="satildi" <?php echo $p['durum'] == 'satildi' ? 'selected' : ''; ?>>Satıldı</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label>Notlar</label>
                                            <textarea name="notlar"
                                                class="form-control"><?php echo htmlspecialchars($p['notlar']); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Güncelle</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Ekle Modal -->
<div class="modal fade" id="ekleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Portföy Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="ekle" value="1">

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label>Başlık</label>
                            <input type="text" name="baslik" class="form-control" placeholder="Örn: 3+1 Lüks Daire" required>
                        </div>
                        <div class="col-md-6">
                            <label>Fiyat</label>
                            <input type="text" name="fiyat" class="form-control price-input" placeholder="Örn: 2.500.000" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label>İl</label>
                            <input type="text" name="il" class="form-control" placeholder="İstanbul">
                        </div>
                        <div class="col-md-4">
                            <label>İlçe</label>
                            <input type="text" name="ilce" class="form-control" placeholder="Kadıköy">
                        </div>
                        <div class="col-md-4">
                            <label>Mahalle</label>
                            <input type="text" name="mahalle" class="form-control" placeholder="Mahalle">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label>Ada</label>
                            <input type="text" name="ada" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label>Parsel</label>
                            <input type="text" name="parsel" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label>Bağ. Bölüm</label>
                            <input type="text" name="bagimsiz_bolum" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label>Niteliği</label>
                            <input type="text" name="nitelik" class="form-control" placeholder="Mesken">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Açık Adres</label>
                        <textarea name="adres" class="form-control" placeholder="Tam adres..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Notlar</label>
                        <textarea name="notlar" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script>
    // Fiyat Formatlama (Tüm price-input classlı alanlar için)
    document.querySelectorAll(".price-input").forEach(input => {
        input.addEventListener("keyup", function (e) {
            let value = this.value.replace(/[^\d]/g, "");
            if (value !== "") {
                this.value = parseInt(value).toLocaleString("tr-TR");
            }
        });
    });
</script>
';
include '../includes/footer.php';
?>