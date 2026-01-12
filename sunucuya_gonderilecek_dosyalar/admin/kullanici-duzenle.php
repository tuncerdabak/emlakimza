<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$message = '';
$message_type = '';

if ($id === 0) {
    header("Location: kullanicilar.php");
    exit;
}

// Güncelleme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isim = sanitizeInput($_POST['isim']);
    $email = sanitizeInput($_POST['email']);
    $telefon = sanitizeInput($_POST['telefon']);
    $rol = $_POST['rol'];
    $firma_id = (int)$_POST['firma_id'];
    $aktif = isset($_POST['aktif']) ? 1 : 0;

    // Şifre değiştirme
    $password_sql = "";
    $password_param = [];
    if (!empty($_POST['yeni_sifre'])) {
        $password_sql = ", sifre = ?";
        $password_param = [password_hash($_POST['yeni_sifre'], PASSWORD_DEFAULT)];
    }

    $sql = "UPDATE kullanicilar SET 
            isim = ?, email = ?, telefon = ?, rol = ?, firma_id = ?, aktif = ? $password_sql
            WHERE id = ?";

    $params = [$isim, $email, $telefon, $rol, $firma_id, $aktif];
    $params = array_merge($params, $password_param);
    $params[] = $id;

    $stmt = $db->prepare($sql);
    if ($stmt->execute($params)) {
        $message = 'Kullanıcı bilgileri güncellendi.';
        $message_type = 'success';
    } else {
        $message = 'Güncelleme hatası.';
        $message_type = 'danger';
    }
}

// Kullanıcı Bilgilerini Getir
$stmt = $db->prepare("SELECT k.*, f.firma_adi FROM kullanicilar k LEFT JOIN firmalar f ON k.firma_id = f.id WHERE k.id = ?");
$stmt->execute([$id]);
$kullanici = $stmt->fetch();

if (!$kullanici) {
    echo "Kullanıcı bulunamadı.";
    exit;
}

// Firmalar listesi
$firmalar = $db->query("SELECT id, firma_adi FROM firmalar ORDER BY firma_adi")->fetchAll();

$page_title = 'Kullanıcı Düzenle - ' . $kullanici['isim'];
$body_class = 'admin-theme';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <h2 class="fw-bold mb-0"><i class="bi bi-person-gear"></i> Kullanıcı Düzenle</h2>
            <a href="kullanicilar.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Geri Dön</a>
        </div>

        <?php if ($message): ?>
            <?php echo showAlert($message, $message_type); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Ad Soyad</label>
                                    <input type="text" name="isim" class="form-control"
                                        value="<?php echo htmlspecialchars($kullanici['isim']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">E-posta</label>
                                    <input type="email" name="email" class="form-control"
                                        value="<?php echo htmlspecialchars($kullanici['email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Telefon</label>
                                    <input type="text" name="telefon" class="form-control"
                                        value="<?php echo htmlspecialchars($kullanici['telefon']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Rol</label>
                                    <select name="rol" class="form-select">
                                        <option value="firma_sahibi" <?php echo $kullanici['rol'] == 'firma_sahibi' ? 'selected' : ''; ?>>Firma Sahibi</option>
                                        <option value="danisman" <?php echo $kullanici['rol'] == 'danisman' ? 'selected' : ''; ?>>Danışman</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Firma</label>
                                    <select name="firma_id" class="form-select">
                                        <option value="0">-- Firma Yok --</option>
                                        <?php foreach ($firmalar as $firma): ?>
                                            <option value="<?php echo $firma['id']; ?>" 
                                                <?php echo $kullanici['firma_id'] == $firma['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($firma['firma_adi']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-block">Durum</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="aktif" <?php echo $kullanici['aktif'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Kullanıcı Aktif</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <hr>
                                    <h6>Şifre Değiştir</h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Yeni Şifre</label>
                                    <input type="password" name="yeni_sifre" class="form-control" placeholder="Boş bırakılırsa değişmez">
                                    <small class="text-muted">Boş bırakırsanız şifre değişmez.</small>
                                </div>

                                <div class="col-12 text-end mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">Değişiklikleri Kaydet</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="bi bi-info-circle"></i> Kullanıcı Bilgileri</h5>
                        <hr>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>ID:</strong></td>
                                <td><?php echo $kullanici['id']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Firma:</strong></td>
                                <td><?php echo htmlspecialchars($kullanici['firma_adi'] ?? 'Yok'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Kayıt Tarihi:</strong></td>
                                <td><?php echo formatDate($kullanici['olusturma_tarihi'], 'd.m.Y H:i'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Son Giriş:</strong></td>
                                <td>
                                    <?php 
                                    if (isset($kullanici['son_giris']) && $kullanici['son_giris']) {
                                        echo formatDate($kullanici['son_giris'], 'd.m.Y H:i');
                                    } else {
                                        echo '<span class="text-muted">Hiç giriş yapmamış</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
