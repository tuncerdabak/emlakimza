<?php

require_once '../config/database.php';

require_once '../includes/auth.php';

require_once '../includes/functions.php';



// Zaten giriş yapmışsa yönlendir

if (isset($_SESSION['admin_id'])) {

    header("Location: dashboard.php");

    exit;

}



$error = '';



// Admin giriş formu

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {

    $identifier = sanitizeInput($_POST['email'] ?? ''); // E-posta veya telefon

    $password = $_POST['password'] ?? '';



    if (empty($identifier) || empty($password)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } else {
        $db = getDB();

        // Sadece e-posta ile giriş
        $sql = "SELECT * FROM yoneticiler WHERE email = :identifier";

        $stmt = $db->prepare($sql);

        $stmt->execute([':identifier' => $identifier]);

        $admin = $stmt->fetch();



        if ($admin && verifyPassword($password, $admin['sifre'])) {

            // Admin girişi başarılı

            session_regenerate_id(true);

            unset($_SESSION['user_id']);
            $_SESSION['admin_id'] = $admin['id'];

            $_SESSION['admin_adsoyad'] = $admin['adsoyad'];

            $_SESSION['admin_email'] = $admin['email'];

            $_SESSION['user_type'] = 'admin';

            $_SESSION['last_activity'] = time();



            // Denetim kaydı - admin girişi

            try {

                $db_log = getDB();

                $sql_log = "INSERT INTO denetim_kayitlari (firma_id, kullanici_id, islem, meta, ip, tarih) 

                           VALUES (NULL, NULL, :islem, :meta, :ip, NOW())";

                $stmt_log = $db_log->prepare($sql_log);

                $stmt_log->execute([

                    ':islem' => 'Admin girişi yapıldı',

                    ':meta' => json_encode(['admin_id' => $admin['id'], 'identifier' => $identifier], JSON_UNESCAPED_UNICODE),

                    ':ip' => getUserIP()

                ]);

            } catch (Exception $e) {

                // Sessizce devam et

            }



            header("Location: dashboard.php");

            exit;

        } else {

            $error = 'E-posta/Telefon veya şifre hatalı!';

        }

    }

}

?>

<!DOCTYPE html>

<html lang="tr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Giriş - emlakimza.com</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        body {

            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);

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

            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);

            max-width: 450px;

            width: 100%;

        }

        .admin-logo {

            width: 90px;

            height: 90px;

            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);

            border-radius: 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 0 auto 30px;

            color: white;

            font-size: 45px;

        }

        .form-control {

            border-radius: 10px;

            border: 2px solid #e0e0e0;

            padding: 12px 15px;

            margin-bottom: 15px;

        }

        .form-control:focus {

            border-color: #2a5298;

            box-shadow: 0 0 0 0.2rem rgba(42, 82, 152, 0.25);

        }

        .btn-admin-login {

            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);

            border: none;

            border-radius: 10px;

            padding: 12px;

            color: white;

            font-weight: 600;

            width: 100%;

        }

        h4 {

            text-align: center;

            margin-bottom: 10px;

            color: #1e3c72;

        }

        .subtitle {

            text-align: center;

            color: #666;

            margin-bottom: 30px;

        }

        .admin-badge {

            background: #dc3545;

            color: white;

            padding: 5px 15px;

            border-radius: 20px;

            display: inline-block;

            margin-bottom: 20px;

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

        <div class="admin-logo">

            <i class="bi bi-shield-check"></i>

        </div>



        <div class="text-center">

            <span class="admin-badge">

                <i class="bi bi-star-fill"></i> SÜPER ADMİN

            </span>

        </div>



        <h4>Yönetim Paneli</h4>

        <p class="subtitle">Sistem yöneticisi girişi</p>



        <?php if ($error): ?>

            <div class="alert alert-danger alert-dismissible fade show">

                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

            </div>

        <?php endif; ?>



        <form method="POST" action="">

            <div class="input-group mb-3">

                <span class="input-group-text">

                    <i class="bi bi-person"></i>

                </span>

                <input type="email" class="form-control" name="email" placeholder="Admin E-posta" required autofocus>

            </div>



            <div class="input-group mb-3">

                <span class="input-group-text">

                    <i class="bi bi-key"></i>

                </span>

                <input type="password" class="form-control" name="password" placeholder="Admin şifre" required>

            </div>



            <button type="submit" name="admin_login" class="btn-admin-login">

                <i class="bi bi-shield-lock"></i> Yönetici Girişi

            </button>

        </form>



        <hr class="my-4">

        <div class="text-center mt-3">

            <small class="text-muted">

                ?

                <a href="../giris.php" class="text-decoration-none">Giriş Sayfası</a>

            </small>

        </div>

        <div class="text-center">

            <small class="text-muted">

                <i class="bi bi-shield-lock"></i> Güvenli bağlantı ile korunmaktadır

            </small>

        </div>

    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>