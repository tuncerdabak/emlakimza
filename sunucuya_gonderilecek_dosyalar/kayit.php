<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();

    $firma_adi = sanitizeInput($_POST['firma_adi']);
    $yetkili_adi = sanitizeInput($_POST['yetkili_adi']);
    $email = sanitizeInput($_POST['email']);
    $telefon = sanitizeInput($_POST['telefon']);
    $sifre = $_POST['sifre'];

    // Basit validasyon
    if (empty($firma_adi) || empty($email) || empty($sifre)) {
        $message = 'Lütfen zorunlu alanları doldurun.';
        $message_type = 'danger';
    } else {
        // Email kontrolü
        $stmt = $db->prepare("SELECT id FROM firmalar WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $message = 'Bu e-posta adresi zaten kayıtlı.';
            $message_type = 'warning';
        } else {
            // Kayıt işlemi
            $hashed_password = password_hash($sifre, PASSWORD_DEFAULT);

            // Varsayılan olarak 'free' paket ve onaylı (admin onayı istenirse durum 0 yapılabilir)
            $sql = "INSERT INTO firmalar (firma_adi, yetkili_adi, email, telefon, sifre, plan, durum, olusturma_tarihi) 
                    VALUES (?, ?, ?, ?, ?, 'free', 1, NOW())";
            $stmt = $db->prepare($sql);

            if ($stmt->execute([$firma_adi, $yetkili_adi, $email, $telefon, $hashed_password])) {
                $success = true;
                $message = 'Kayıt başarılı! Giriş sayfasına yönlendiriliyorsunuz...';
                $message_type = 'success';

                // 3 saniye sonra yönlendir
                header("refresh:3;url=firma/login.php");
            } else {
                $message = 'Kayıt sırasında bir hata oluştu.';
                $message_type = 'danger';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - emlakimza.com</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
        }

        .logo-area {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            margin-bottom: 15px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.25);
            border-color: #667eea;
        }

        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
        }

        .btn-register:hover {
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="logo-area">
            <div class="logo-circle">
                <i class="bi bi-person-plus-fill"></i>
            </div>
            <h3 class="fw-bold">Hemen Kayıt Olun</h3>
            <p class="text-muted">Emlak ofisiniz için dijital dönüşümü başlatın</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Firma Adı</label>
                <input type="text" name="firma_adi" class="form-control" required placeholder="Örn: Güneş Emlak">
            </div>
            <div class="mb-3">
                <label class="form-label">Yetkili Adı Soyadı</label>
                <input type="text" name="yetkili_adi" class="form-control" required placeholder="Örn: Ahmet Yılmaz">
            </div>
            <div class="mb-3">
                <label class="form-label">Email Adresi</label>
                <input type="email" name="email" class="form-control" required placeholder="ornek@email.com">
            </div>
            <div class="mb-3">
                <label class="form-label">Telefon</label>
                <input type="tel" name="telefon" class="form-control" required placeholder="05XX XXX XX XX">
            </div>
            <div class="mb-3">
                <label class="form-label">Şifre</label>
                <input type="password" name="sifre" class="form-control" required placeholder="******">
            </div>

            <button type="submit" class="btn btn-primary btn-register">
                Kayıt Ol ve Başla
            </button>
        </form>

        <div class="text-center mt-4">
            <p class="mb-0 text-muted">Zaten hesabınız var mı? <a href="firma/login.php"
                    class="text-primary text-decoration-none fw-bold">Giriş Yap</a></p>
            <p class="mt-2"><a href="index.php" class="text-secondary text-decoration-none small"><i
                        class="bi bi-arrow-left"></i> Ana Sayfaya Dön</a></p>
        </div>
    </div>
</body>

</html>