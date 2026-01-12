<?php

require_once '../config/database.php';

require_once '../includes/auth.php';

require_once '../includes/functions.php';



if (!isset($_SESSION['admin_id'])) {

    header("Location: login.php");

    exit;

}



$db = getDB();

$message = '';

$message_type = '';



// Log temizleme

if (isset($_POST['clear_logs'])) {

    $days = (int)$_POST['days'];

    if ($days == 0) {
        $sql = "DELETE FROM denetim_kayitlari";
        $stmt = $db->prepare($sql);
        $stmt->execute();
    } else {
        $sql = "DELETE FROM denetim_kayitlari WHERE tarih < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $db->prepare($sql);
        $stmt->execute([':days' => $days]);
    }

    $deleted = $stmt->rowCount();

    

    $message = "$deleted kayıt silindi!";

    $message_type = 'success';

}



// SQL Yedekleme

if (isset($_POST['backup_db'])) {

    $backup_file = __DIR__ . '/../backups/backup_' . date('Y-m-d_His') . '.sql';

    

    if (!file_exists(__DIR__ . '/../backups')) {

        mkdir(__DIR__ . '/../backups', 0755, true);

    }

    

    $db_config = [

        'host' => 'localhost',

        'name' => 'tuncerda_emlak_imza',

        'user' => 'tuncerda_eimza',

        'pass' => 'Td3492549/'

    ];

    

    $command = sprintf(

        'mysqldump --host=%s --user=%s --password=%s %s > %s',

        escapeshellarg($db_config['host']),

        escapeshellarg($db_config['user']),

        escapeshellarg($db_config['pass']),

        escapeshellarg($db_config['name']),

        escapeshellarg($backup_file)

    );

    

    exec($command, $output, $return);

    

    if ($return === 0 && file_exists($backup_file)) {

        $message = 'Yedek alındı: ' . basename($backup_file);

        $message_type = 'success';

    } else {

        $message = 'Yedekleme hatası! mysqldump komutu çalışmıyor olabilir.';

        $message_type = 'danger';

    }

}



// Session temizleme
if (isset($_POST['clear_sessions'])) {
    session_destroy();
    $message = 'Tüm oturumlar sonlandırıldı!';
    $message_type = 'warning';
}

// Ayarlar tablosunu kontrol et ve oluştur
$db->exec("CREATE TABLE IF NOT EXISTS site_ayarlari (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Varsayılan ayarları yükle
$default_settings = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_secure' => 'tls',
    'smtp_active' => '0'
];

foreach ($default_settings as $key => $value) {
    $stmt = $db->prepare("INSERT IGNORE INTO site_ayarlari (setting_key, setting_value) VALUES (:key, :value)");
    $stmt->execute([':key' => $key, ':value' => $value]);
}

// Ayarları kaydet
if (isset($_POST['save_smtp'])) {
    $settings = [
        'smtp_host' => $_POST['smtp_host'],
        'smtp_port' => $_POST['smtp_port'],
        'smtp_username' => $_POST['smtp_username'],
        'smtp_password' => $_POST['smtp_password'],
        'smtp_secure' => $_POST['smtp_secure'],
        'smtp_active' => isset($_POST['smtp_active']) ? '1' : '0'
    ];

    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("INSERT INTO site_ayarlari (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([':key' => $key, ':value' => $value]);
    }

    $message = "SMTP ayarları başarıyla güncellendi!";
    $message_type = "success";
}

// Shopier ayarlarını kaydet
if (isset($_POST['save_shopier'])) {
    $settings = [
        'shopier_api_key' => $_POST['shopier_api_key'],
        'shopier_secret' => $_POST['shopier_secret']
    ];

    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("INSERT INTO site_ayarlari (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([':key' => $key, ':value' => $value]);
    }

    $message = "Shopier ayarları başarıyla güncellendi!";
    $message_type = "success";
}

// Mevcut ayarları çek
$current_settings = [];
$stmt = $db->query("SELECT * FROM site_ayarlari");
while ($row = $stmt->fetch()) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}

// İstatistikler
$stats = [
    'total_logs' => $db->query("SELECT COUNT(*) FROM denetim_kayitlari")->fetchColumn(),
    'total_files' => 0,
    'db_size' => $db->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size 
                            FROM information_schema.tables 
                            WHERE table_schema = 'tuncerda_emlak_imza'")->fetchColumn()
];



// Dosya boyutları

$upload_dir = __DIR__ . '/../assets/uploads';

if (file_exists($upload_dir)) {

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($upload_dir));

    foreach ($iterator as $file) {

        if ($file->isFile()) {

            $stats['total_files']++;

        }

    }

}



// Yedek listesi

$backups = [];

$backup_dir = __DIR__ . '/../backups';

if (file_exists($backup_dir)) {

    $files = scandir($backup_dir, SCANDIR_SORT_DESCENDING);

    foreach ($files as $file) {

        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {

            $backups[] = [

                'name' => $file,

                'size' => filesize($backup_dir . '/' . $file),

                'date' => filemtime($backup_dir . '/' . $file)

            ];

        }

    }

}


$page_title = 'Sistem Ayarları';
$body_class = 'admin-theme';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 text-white">
                <h2 class="fw-bold mb-0">Sistem Ayarları</h2>
            </div>




                <?php if ($message): ?>

                    <?php echo showAlert($message, $message_type); ?>

                <?php endif; ?>



                <!-- İstatistikler -->

                <div class="row mb-4">

                    <div class="col-md-4">

                        <div class="card stat-card">

                            <div class="card-body text-center">

                                <h3><?php echo number_format($stats['total_logs']); ?></h3>

                                <small>Toplam Log Kaydı</small>

                            </div>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="card stat-card">

                            <div class="card-body text-center">

                                <h3><?php echo $stats['total_files']; ?></h3>

                                <small>Yüklenen Dosya</small>

                            </div>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="card stat-card">

                            <div class="card-body text-center">

                                <h3><?php echo $stats['db_size']; ?> MB</h3>

                                <small>Veritabanı Boyutu</small>

                            </div>

                        </div>

                    </div>

                </div>



                <!-- SMTP Ayarları -->
                <div class="card">
                    <div class="card-body">
                        <h5><i class="bi bi-envelope"></i> E-posta (SMTP) Ayarları</h5>
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Sunucu</label>
                                    <input type="text" name="smtp_host" class="form-control" 
                                           value="<?php echo htmlspecialchars($current_settings['smtp_host'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Port</label>
                                    <input type="number" name="smtp_port" class="form-control" 
                                           value="<?php echo htmlspecialchars($current_settings['smtp_port'] ?? '587'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kullanıcı Adı (E-posta)</label>
                                    <input type="text" name="smtp_username" class="form-control" 
                                           value="<?php echo htmlspecialchars($current_settings['smtp_username'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Şifre</label>
                                    <div class="input-group">
                                        <input type="password" name="smtp_password" class="form-control" id="smtpPass"
                                               value="<?php echo htmlspecialchars($current_settings['smtp_password'] ?? ''); ?>">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePass()">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Güvenlik Protokolü</label>
                                    <select name="smtp_secure" class="form-select">
                                        <option value="tls" <?php echo ($current_settings['smtp_secure'] ?? '') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo ($current_settings['smtp_secure'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        <option value="" <?php echo ($current_settings['smtp_secure'] ?? '') == '' ? 'selected' : ''; ?>>Yok</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-block">Durum</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="smtp_active" 
                                               <?php echo ($current_settings['smtp_active'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label">E-posta Gönderimi Aktif</label>
                                    </div>
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" name="save_smtp" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Ayarları Kaydet
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Shopier Ayarları -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5><i class="bi bi-credit-card"></i> Ödeme (Shopier) Ayarları</h5>
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Shopier API Key</label>
                                    <input type="text" name="shopier_api_key" class="form-control" 
                                           value="<?php echo htmlspecialchars($current_settings['shopier_api_key'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Shopier API Secret</label>
                                    <div class="input-group">
                                        <input type="password" name="shopier_secret" class="form-control" id="shopierSecret"
                                               value="<?php echo htmlspecialchars($current_settings['shopier_secret'] ?? ''); ?>">
                                        <button class="btn btn-outline-secondary" type="button" onclick="toggleShopierPass()">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" name="save_shopier" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Shopier Ayarlarını Kaydet
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                function toggleShopierPass() {
                    var x = document.getElementById("shopierSecret");
                    if (x.type === "password") {
                        x.type = "text";
                    } else {
                        x.type = "password";
                    }
                }
                </script>

                <script>
                function togglePass() {
                    var x = document.getElementById("smtpPass");
                    if (x.type === "password") {
                        x.type = "text";
                    } else {
                        x.type = "password";
                    }
                }
                </script>

                <!-- Log Yönetimi -->

                <div class="card">

                    <div class="card-body">

                        <h5><i class="bi bi-journal-text"></i> Log Yönetimi</h5>

                        <form method="POST" class="row g-3">

                            <div class="col-md-8">

                                <select name="days" class="form-select">

                                    <option value="0">Tüm kayıtları sil</option>
                                    <option value="7">7 günden eski kayıtları sil</option>

                                    <option value="30">30 günden eski kayıtları sil</option>

                                    <option value="90">90 günden eski kayıtları sil</option>

                                    <option value="365">1 yıldan eski kayıtları sil</option>

                                </select>

                            </div>

                            <div class="col-md-4">

                                <button type="submit" name="clear_logs" class="btn btn-warning w-100"

                                        onclick="return confirm('Logları silmek istediğinize emin misiniz?')">

                                    <i class="bi bi-trash"></i> Logları Temizle

                                </button>

                            </div>

                        </form>

                    </div>

                </div>



                <!-- Yedekleme -->

                <div class="card">

                    <div class="card-body">

                        <h5><i class="bi bi-database"></i> Veritabanı Yedekleme</h5>

                        <form method="POST">

                            <button type="submit" name="backup_db" class="btn btn-success">

                                <i class="bi bi-download"></i> Yeni Yedek Al

                            </button>

                        </form>



                        <?php if (count($backups) > 0): ?>

                            <h6 class="mt-4">Son Yedekler</h6>

                            <div class="list-group">

                                <?php foreach (array_slice($backups, 0, 5) as $backup): ?>

                                    <div class="list-group-item d-flex justify-content-between align-items-center">

                                        <div>

                                            <i class="bi bi-file-earmark-zip"></i>

                                            <strong><?php echo $backup['name']; ?></strong>

                                            <br>

                                            <small class="text-muted">

                                                <?php echo number_format($backup['size'] / 1024 / 1024, 2); ?> MB - 

                                                <?php echo date('d.m.Y H:i', $backup['date']); ?>

                                            </small>

                                        </div>

                                        <a href="../backups/<?php echo $backup['name']; ?>" 

                                           class="btn btn-sm btn-primary" download>

                                            <i class="bi bi-download"></i>

                                        </a>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>



                <!-- Session Yönetimi -->

                <div class="card">

                    <div class="card-body">

                        <h5><i class="bi bi-people"></i> Oturum Yönetimi</h5>

                        <form method="POST">

                            <button type="submit" name="clear_sessions" class="btn btn-danger"

                                    onclick="return confirm('Tüm kullanıcılar çıkış yapacak!')">

                                <i class="bi bi-x-circle"></i> Tüm Oturumları Sonlandır

                            </button>

                        </form>

                        <small class="text-muted">Bu işlem tüm kullanıcıları sistemden çıkaracaktır.</small>

                    </div>

                </div>



                <!-- Sistem Bilgisi -->

                <div class="card">

                    <div class="card-body">

                        <h5><i class="bi bi-info-circle"></i> Sistem Bilgisi</h5>

                        <table class="table table-sm">

                            <tr><td><strong>PHP Versiyonu:</strong></td><td><?php echo PHP_VERSION; ?></td></tr>

                            <tr><td><strong>Server:</strong></td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor'; ?></td></tr>

                            <tr><td><strong>Max Upload:</strong></td><td><?php echo ini_get('upload_max_filesize'); ?></td></tr>

                            <tr><td><strong>Memory Limit:</strong></td><td><?php echo ini_get('memory_limit'); ?></td></tr>

                            <tr><td><strong>Session Timeout:</strong></td><td>30 dakika</td></tr>

                        </table>

                    </div>

                </div>

            </div>

        </div>
    </div>

<?php include '../includes/footer.php'; ?>
