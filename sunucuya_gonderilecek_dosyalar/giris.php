<?php

/**

 * Ana Sayfa - Otomatik Yönlendirme

 * Kullanıcıyı giriş durumuna göre yönlendirir

 */



require_once 'config/database.php';

require_once 'includes/auth.php';



// Zaten giriş yapmışsa ilgili panele yönlendir
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard");
    } elseif (isFirmaSahibi()) {
        header("Location: firma/dashboard");
    } elseif (isDanisman()) {
        header("Location: danisman/dashboard");
    } else {
        header("Location: danisman/dashboard");
    }
    exit;
}

?>

<!DOCTYPE html>

<html lang="tr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>emlakimza.com - Dijital İmza Platformu</title>
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

        .welcome-container {

            background: white;

            border-radius: 30px;

            padding: 50px;

            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);

            max-width: 900px;

            width: 100%;

            text-align: center;

        }

        .logo-circle {

            width: 120px;

            height: 120px;

            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

            border-radius: 30px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 0 auto 30px;

            color: white;

            font-size: 60px;

            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);

        }

        h1 {

            color: #333;

            margin-bottom: 15px;

            font-weight: 700;

        }

        .subtitle {

            color: #666;

            font-size: 18px;

            margin-bottom: 40px;

        }

        .login-cards {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));

            gap: 20px;

            margin-top: 40px;

        }

        .login-card {

            background: #f8f9fa;

            border-radius: 20px;

            padding: 30px 20px;

            text-decoration: none;

            color: #333;

            transition: all 0.3s;

            border: 2px solid transparent;

        }

        .login-card:hover {

            transform: translateY(-10px);

            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);

            border-color: #667eea;

        }

        .login-card i {

            font-size: 50px;

            margin-bottom: 15px;

            color: #667eea;

        }

        .login-card h5 {

            margin-bottom: 10px;

            color: #333;

        }

        .login-card p {

            color: #666;

            font-size: 14px;

            margin: 0;

        }

        .features {

            margin-top: 50px;

            padding-top: 40px;

            border-top: 2px solid #e0e0e0;

        }

        .feature-item {

            display: flex;

            align-items: center;

            margin-bottom: 15px;

            text-align: left;

        }

        .feature-item i {

            color: #28a745;

            font-size: 24px;

            margin-right: 15px;

            min-width: 24px;

        }

        @media (max-width: 768px) {

            .welcome-container {

                padding: 30px 20px;

            }

            .logo-circle {

                width: 90px;

                height: 90px;

                font-size: 45px;

            }

            h1 {

                font-size: 24px;

            }

        }
    </style>

</head>

<body>

    <div class="welcome-container">

        <div class="text-center mb-4">
            <a href="https://emlakimza.com">
                <img src="emlakimza.png" alt="emlakimza.com" style="max-height: 120px;" class="mb-3">
            </a>
        </div>

        <h1>emlakimza.com</h1>

        <p class="subtitle">
            <i class="bi bi-shield-check"></i>
            Dijital imza ile hızlı ve güvenli sözleşme yönetimi
        </p>

        <div class="login-cards">
            <a href="firma/login" class="login-card">
                <i class="bi bi-building"></i>
                <h5>Firma Girişi</h5>
                <p>Firma paneline giriş yapın</p>
            </a>

            <a href="danisman/login" class="login-card">
                <i class="bi bi-person-badge"></i>
                <h5>Danışman Girişi</h5>
                <p>Danışman paneline giriş yapın</p>
            </a>
        </div>
        <div class="login-cards">
            <a href="kayit" class="login-card" style="border-color: #667eea; background: #f0f4ff;">
                <i class="bi bi-person-plus-fill"></i>
                <h5>Kayıt Ol</h5>
                <p>Yeni firma hesabı oluşturun</p>
            </a>
        </div>

        <div class="features">

            <div class="row">

                <div class="col-md-6">

                    <div class="feature-item">

                        <i class="bi bi-check-circle-fill"></i>

                        <span>WhatsApp entegrasyonu ile hızlı gönderim</span>

                    </div>

                    <div class="feature-item">

                        <i class="bi bi-check-circle-fill"></i>

                        <span>Mobil uyumlu dokunmatik imza</span>

                    </div>

                    <div class="feature-item">

                        <i class="bi bi-check-circle-fill"></i>

                        <span>Otomatik PDF oluşturma</span>

                    </div>

                </div>

                <div class="col-md-6">

                    <div class="feature-item">

                        <i class="bi bi-check-circle-fill"></i>

                        <span>Çift imza doğrulama sistemi</span>

                    </div>

                    <div class="feature-item">

                        <i class="bi bi-check-circle-fill"></i>

                        <span>Detaylı denetim kayıtları</span>

                    </div>

                    <div class="feature-item">

                        <i class="bi bi-check-circle-fill"></i>

                        <span>E-posta ile otomatik gönderim</span>

                    </div>

                </div>

            </div>

        </div>

        <hr class="my-4">

        <div class="login-cards">
            <a href="admin/login" class="login-card">
                <i class="bi bi-shield-lock-fill"></i>
                <h5>Yönetici</h5>
                <p>Sistem yönetimi ve kontrol paneli</p>
            </a>
        </div>

        <p class="text-muted small mb-0">

            <i class="bi bi-lock-fill"></i>

            SSL ile güvenli bağlantı | © 2025 Tüm hakları saklıdır

        </p>

    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>