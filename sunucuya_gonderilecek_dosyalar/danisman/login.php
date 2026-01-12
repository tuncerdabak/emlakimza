<?php

require_once '../config/database.php';

require_once '../includes/auth.php';

require_once '../includes/functions.php';



// Zaten giriş yapmışsa yönlendir

if (isLoggedIn()) {

    if (isAdmin()) {

        header("Location: ../admin/dashboard.php");

    } elseif (isFirmaSahibi()) {

        header("Location: ../firma/dashboard.php");

    } else {

        header("Location: dashboard.php");

    }

    exit;

}



$error = '';

$success = '';



// Giriş formu gönderimi

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $identifier = sanitizeInput($_POST['email'] ?? ''); // E-posta veya telefon

    $password = $_POST['password'] ?? '';



    if (empty($identifier) || empty($password)) {

        $error = 'Lütfen tüm alanları doldurun.';

    } else {

        $db = getDB();



        // E-posta mı telefon mu kontrol et

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {

            // E-posta ile giriş

            $sql = "SELECT * FROM kullanicilar WHERE email = :identifier AND aktif = 1";

        } else {

            // Telefon ile giriş (sadece rakamları al)

            $phone = preg_replace('/[^0-9]/', '', $identifier);

            $sql = "SELECT * FROM kullanicilar WHERE telefon = :identifier AND aktif = 1";

            $identifier = $phone;

        }



        $stmt = $db->prepare($sql);

        $stmt->execute([':identifier' => $identifier]);

        $user = $stmt->fetch();



        if ($user && verifyPassword($password, $user['parola_hash'])) {

            // Giriş başarılı

            login($user['id'], 'kullanici', [

                'firma_id' => $user['firma_id'],

                'user_rol' => $user['rol'],

                'user_isim' => $user['isim'],

                'user_email' => $user['email']

            ]);



            // Rol'e göre yönlendir

            if (in_array($user['rol'], ['super_admin', 'firma_sahibi', 'broker'])) {

                header("Location: ../firma/dashboard.php");

            } else {

                header("Location: dashboard.php");

            }

            exit;

        } else {

            $error = 'E-posta/Telefon veya şifre hatalı!';

            // Başarısız giriş denemesi - kullanici_id yerine null gönder

            try {

                $db_log = getDB();

                $sql_log = "INSERT INTO denetim_kayitlari (firma_id, kullanici_id, islem, meta, ip, tarih) 

                           VALUES (NULL, NULL, :islem, :meta, :ip, NOW())";

                $stmt_log = $db_log->prepare($sql_log);

                $stmt_log->execute([

                    ':islem' => 'Başarısız giriş denemesi',

                    ':meta' => json_encode(['identifier' => $identifier], JSON_UNESCAPED_UNICODE),

                    ':ip' => getUserIP()

                ]);

            } catch (Exception $e) {

                // Sessizce devam et

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

    <title>Giriş Yap - emlakimza.com</title>

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

        .btn-login {

            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

            border: none;

            border-radius: 10px;

            padding: 12px;

            color: white;

            font-weight: 600;

            width: 100%;

        }

        .btn-login:hover {

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

            <i class="bi bi-house-check"></i>

        </div>



        <h4>emlakimza.com</h4>

        <p class="subtitle">Hesabınıza giriş yapın</p>



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



        <form method="POST" action="">

            <div class="input-group mb-3">

                <span class="input-group-text">

                    <i class="bi bi-person"></i>

                </span>

                <input type="text" class="form-control" name="email" placeholder="E-posta veya Telefon" required
                    autofocus>

            </div>



            <div class="input-group mb-3">

                <span class="input-group-text">

                    <i class="bi bi-lock"></i>

                </span>

                <input type="password" class="form-control" name="password" placeholder="Şifreniz" required>

            </div>



            <div class="form-check mb-3">

                <input class="form-check-input" type="checkbox" id="remember" name="remember">

                <label class="form-check-label" for="remember">

                    Beni hatırla

                </label>

            </div>



            <button type="submit" name="login" class="btn-login">

                <i class="bi bi-box-arrow-in-right"></i> Giriş Yap

            </button>

        </form>



        <hr class="my-4">



        <div class="text-center">

            <small class="text-muted">

                <a href="sifremi-unuttum.php" class="text-decoration-none">Şifremi unuttum</a>

            </small>

        </div>

        <div class="text-center mt-3">

            <small class="text-muted">

                Firma Sahibi misiniz?

                <a href="../firma/login.php" class="text-decoration-none">Firma girişi</a>

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