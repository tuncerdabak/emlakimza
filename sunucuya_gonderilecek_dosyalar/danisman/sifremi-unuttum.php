<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Zaten giriş yapmışsa yönlendir
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

// Form gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_request'])) {
    $email = sanitizeInput($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Lütfen e-posta adresinizi girin.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } else {
        $db = getDB();

        // E-posta adresine sahip kullanıcıyı bul (danışman olmayan roller hariç)
        $sql = "SELECT * FROM kullanicilar 
                WHERE email = :email 
                AND aktif = 1 
                AND rol NOT IN ('super_admin', 'firma_sahibi', 'broker')";
        $stmt = $db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Token oluştur ve e-posta gönder
            if (createPasswordResetToken($user['id'], $email)) {
                $success = 'Şifre sıfırlama linki e-posta adresinize gönderildi.';
            } else {
                $error = 'E-posta gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
            }
        } else {
            // Güvenlik için her durumda başarılı mesajı göster
            $success = 'Eğer bu e-posta adresi sistemde kayıtlıysa, şifre sıfırlama linki gönderilecektir.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum - emlakimza.com</title>
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
        }

        .login-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: white;
            font-size: 40px;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            margin-bottom: 15px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            color: white;
            font-weight: 600;
            width: 100%;
        }

        .btn-reset:hover {
            opacity: 0.9;
        }

        h4 {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="logo">
            <i class="bi bi-key"></i>
        </div>

        <h4>emlakimza.com</h4>
        <p class="subtitle">Şifrenizi mi unuttunuz?</p>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="POST" action="">
                <div class="input-group mb-3">
                    <span class="input-group-text">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" class="form-control" name="email" placeholder="E-posta adresiniz" required
                        autofocus>
                </div>

                <button type="submit" name="reset_request" class="btn-reset">
                    <i class="bi bi-send"></i> Şifre Sıfırlama Linki Gönder
                </button>
            </form>
        <?php endif; ?>

        <hr class="my-4">

        <div class="text-center">
            <small class="text-muted">
                <a href="login.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Giriş sayfasına
                    dön</a>
            </small>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">
                © 2025 emlakimza.com. Tüm hakları saklıdır.
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>