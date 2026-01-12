<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];

$message = '';
$message_type = '';

// Kullanıcı bilgilerini al
$sql = "SELECT * FROM kullanicilar WHERE id = :id";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $isim = sanitizeInput($_POST['isim'] ?? '');
        $telefon = sanitizeInput($_POST['telefon'] ?? '');

        if (empty($isim)) {
            $message = 'İsim boş bırakılamaz!';
            $message_type = 'danger';
        } else {
            try {
                $update_sql = "UPDATE kullanicilar SET isim = :isim, telefon = :telefon WHERE id = :id";
                $update_stmt = $db->prepare($update_sql);
                $update_stmt->execute([
                    ':isim' => $isim,
                    ':telefon' => $telefon,
                    ':id' => $user_id
                ]);

                $_SESSION['user_isim'] = $isim;
                $user['isim'] = $isim;
                $user['telefon'] = $telefon;

                $message = 'Profil bilgileri güncellendi!';
                $message_type = 'success';

                logActivity($user_id, 'Profil güncellendi');
            } catch (Exception $e) {
                $message = 'Güncelleme hatası!';
                $message_type = 'danger';
            }
        }
    }

    if (isset($_POST['change_password'])) {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($old_password) || empty($new_password)) {
            $message = 'Tüm alanları doldurun!';
            $message_type = 'danger';
        } elseif ($new_password !== $confirm_password) {
            $message = 'Yeni şifreler eşleşmiyor!';
            $message_type = 'danger';
        } elseif (strlen($new_password) < 6) {
            $message = 'Şifre en az 6 karakter olmalı!';
            $message_type = 'danger';
        } elseif (!verifyPassword($old_password, $user['parola_hash'])) {
            $message = 'Mevcut şifre yanlış!';
            $message_type = 'danger';
        } else {
            try {
                $new_hash = hashPassword($new_password);
                $pass_sql = "UPDATE kullanicilar SET parola_hash = :hash WHERE id = :id";
                $pass_stmt = $db->prepare($pass_sql);
                $pass_stmt->execute([
                    ':hash' => $new_hash,
                    ':id' => $user_id
                ]);

                $message = 'Şifre başarıyla değiştirildi!';
                $message_type = 'success';

                logActivity($user_id, 'Şifre değiştirildi');
            } catch (Exception $e) {
                $message = 'Şifre değiştirme hatası!';
                $message_type = 'danger';
            }
        }
    }
}
$page_title = 'Profil - emlakimza.com';
$body_class = 'danisman-theme';
$extra_css = '
<style>
    .profile-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 40px;
        margin: 0 auto 20px;
    }

    .form-control {
        border-radius: 10px;
        border: 2px solid #e0e0e0;
        padding: 12px;
    }

    .btn-save {
        border-radius: 10px;
        padding: 12px;
        font-weight: 600;
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
            <h2 class="fw-bold mb-0"><i class="bi bi-person-fill"></i> Profilim</h2>
        </div>

        <?php if ($message): ?>
            <?php echo showAlert($message, $message_type); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Profil Bilgileri Sol Kolon -->
            <div class="col-lg-4 mb-4">
                <!-- Profil Kartı -->
                <div class="profile-card text-center h-100">
                    <div class="avatar">
                        <i class="bi bi-person"></i>
                    </div>
                    <h5><?php echo htmlspecialchars($user['isim']); ?></h5>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                    <small class="text-muted">
                        <i class="bi bi-briefcase"></i> <?php echo ucfirst($user['rol']); ?>
                    </small>
                    <hr>

                    <!-- İstatistikler -->
                    <?php
                    $stats_sql = "SELECT 
                                COUNT(*) as toplam,
                                SUM(CASE WHEN durum = 'COMPLETED' THEN 1 ELSE 0 END) as basarili
                                FROM sozlesmeler WHERE danisman_id = :id";
                    $stats_stmt = $db->prepare($stats_sql);
                    $stats_stmt->execute([':id' => $user_id]);
                    $stats = $stats_stmt->fetch();
                    ?>
                    <div class="row text-center mt-4">
                        <div class="col-6">
                            <h3 class="text-primary"><?php echo $stats['toplam']; ?></h3>
                            <small class="text-muted">Toplam Sözleşme</small>
                        </div>
                        <div class="col-6">
                            <h3 class="text-success"><?php echo $stats['basarili']; ?></h3>
                            <small class="text-muted">Tamamlanan</small>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="../logout.php" class="btn btn-danger w-100">
                            <i class="bi bi-box-arrow-right"></i> Çıkış Yap
                        </a>
                    </div>
                </div>
            </div>

            <!-- Formlar Sağ Kolon -->
            <div class="col-lg-8">
                <!-- Profil Güncelleme -->
                <div class="profile-card">
                    <h6 class="mb-3"><i class="bi bi-person-gear"></i> Profil Bilgileri</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Ad Soyad</label>
                            <input type="text" class="form-control" name="isim"
                                value="<?php echo htmlspecialchars($user['isim']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-posta (Değiştirilemez)</label>
                            <input type="email" class="form-control"
                                value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="tel" class="form-control" name="telefon"
                                value="<?php echo htmlspecialchars($user['telefon'] ?? ''); ?>"
                                placeholder="05XX XXX XX XX">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary btn-save w-100">
                            <i class="bi bi-check-circle"></i> Güncelle
                        </button>
                    </form>
                </div>

                <!-- Şifre Değiştirme -->
                <div class="profile-card">
                    <h6 class="mb-3"><i class="bi bi-key"></i> Şifre Değiştir</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Mevcut Şifre</label>
                            <input type="password" class="form-control" name="old_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Yeni Şifre</label>
                            <input type="password" class="form-control" name="new_password" minlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Yeni Şifre (Tekrar)</label>
                            <input type="password" class="form-control" name="confirm_password" minlength="6" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning btn-save w-100">
                            <i class="bi bi-shield-lock"></i> Şifreyi Değiştir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>